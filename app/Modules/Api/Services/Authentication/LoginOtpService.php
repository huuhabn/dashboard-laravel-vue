<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\Authentication;

use App\Modules\Api\Contracts\LoginOtpSmsSenderContract;
use App\Modules\Api\DTOs\OtpOutcome;
use App\Modules\Api\Mail\LoginOtpMail;
use App\Modules\Api\Models\OtpCode;
use App\Modules\Api\Models\User;
use App\Modules\Api\Repositories\Contracts\UserRepositoryContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Passwordless login: send and verify one-time codes (email / SMS), stored in otp_codes.
 */
final class LoginOtpService
{
    private const OTP_TTL_SECONDS = 600;

    private const COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly LoginOtpSmsSenderContract $smsSender,
    ) {}

    public function requestCode(?string $email, ?string $phoneE164): OtpOutcome
    {
        if ($email !== null && $email !== '') {
            return $this->requestCodeForEmail(mb_strtolower(trim($email)));
        }

        if ($phoneE164 !== null && $phoneE164 !== '') {
            return $this->requestCodeForPhone($phoneE164);
        }

        return OtpOutcome::silent();
    }

    public function verifyCode(?string $email, ?string $phoneE164, string $code): ?User
    {
        if ($email !== null && $email !== '') {
            return $this->verifyEmailCode(mb_strtolower(trim($email)), $code);
        }

        if ($phoneE164 !== null && $phoneE164 !== '') {
            return $this->verifyPhoneCode($phoneE164, $code);
        }

        return null;
    }

    private function requestCodeForEmail(string $normalizedEmail): OtpOutcome
    {
        $user = $this->users->findByEmail($normalizedEmail);

        if ($user === null) {
            usleep(random_int(100_000, 350_000));

            return OtpOutcome::silent();
        }

        $cooldown = $this->cooldownOutcomeForUser($user->id, 'email');

        if ($cooldown !== null) {
            return $cooldown;
        }

        $plain = $this->generateCode();
        $this->persistOtpForUser($user->id, $plain);

        Mail::to($user)->send(new LoginOtpMail($plain, $user->name));

        return OtpOutcome::sent(now()->addSeconds(self::COOLDOWN_SECONDS));
    }

    private function requestCodeForPhone(string $e164): OtpOutcome
    {
        $user = $this->users->findByPhone($e164);

        if ($user === null) {
            usleep(random_int(100_000, 350_000));

            return OtpOutcome::silent();
        }

        $cooldown = $this->cooldownOutcomeForUser($user->id, 'phone');

        if ($cooldown !== null) {
            return $cooldown;
        }

        $plain = $this->generateCode();
        $this->persistOtpForUser($user->id, $plain);

        $this->smsSender->sendLoginOtp($e164, $plain, $user->name);

        return OtpOutcome::sent(now()->addSeconds(self::COOLDOWN_SECONDS));
    }

    private function cooldownOutcomeForUser(int $userId, string $errorField): ?OtpOutcome
    {
        $latest = OtpCode::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        if ($latest === null) {
            return null;
        }

        $availableAt = $latest->created_at->copy()->addSeconds(self::COOLDOWN_SECONDS);

        if ($availableAt->isPast()) {
            return null;
        }

        $retryAfter = max(1, $availableAt->timestamp - now()->timestamp);

        return OtpOutcome::cooldown($retryAfter, $errorField);
    }

    private function persistOtpForUser(int $userId, string $code): void
    {
        DB::transaction(function () use ($userId, $code): void {
            OtpCode::query()->where('user_id', $userId)->delete();

            OtpCode::query()->create([
                'user_id' => $userId,
                'code' => Hash::make($code),
                'expires_at' => now()->addSeconds(self::OTP_TTL_SECONDS),
            ]);
        });
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
    }

    private function verifyEmailCode(string $normalizedEmail, string $code): ?User
    {
        $user = $this->users->findByEmail($normalizedEmail);

        if ($user === null) {
            return null;
        }

        return $this->consumeOtpForUser($user, $code);
    }

    private function verifyPhoneCode(string $e164, string $code): ?User
    {
        $user = $this->users->findByPhone($e164);

        if ($user === null) {
            return null;
        }

        return $this->consumeOtpForUser($user, $code);
    }

    private function consumeOtpForUser(User $user, string $code): ?User
    {
        $trimmed = preg_replace('/\s+/', '', $code) ?? '';

        if ($trimmed === '') {
            return null;
        }

        $row = OtpCode::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if ($row === null || ! Hash::check($trimmed, $row->code)) {
            return null;
        }

        $row->delete();

        return $user;
    }
}
