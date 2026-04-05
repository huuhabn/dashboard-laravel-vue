<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Api\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

final class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        if (! URL::hasValidSignature($request)) {
            abort(403);
        }

        $user = User::query()->findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/login?verified=1');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect('/login?verified=1');
    }
}
