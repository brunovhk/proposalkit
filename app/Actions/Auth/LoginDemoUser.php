<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\SystemUserEmail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Authenticates the seeded sandbox account for the public live demo flow.
 */
final class LoginDemoUser
{
    /**
     * Authenticate the reserved demo user through Laravel's web guard.
     */
    public function execute(): User
    {
        $user = User::query()
            ->where('email', SystemUserEmail::Demo->value)
            ->firstOrFail();

        Auth::guard('web')->login($user);

        return $user;
    }
}
