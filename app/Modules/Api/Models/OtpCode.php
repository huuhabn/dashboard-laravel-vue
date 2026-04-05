<?php

declare(strict_types=1);

namespace App\Modules\Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Short-lived login OTP row (hashed code). One active row per user is enforced in LoginOtpService.
 */
final class OtpCode extends Model
{
    protected $table = 'otp_codes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
