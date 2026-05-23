<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Creates a tenant-backed user account during Fortify registration.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user and its initial tenant atomically.
     *
     * Preconditions: the input contains a valid profile and password payload.
     * Side effects: persists one tenant and one user assigned to that tenant.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $this->ensureRegistrationAllowed();

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $tenant = Tenant::create([
                'name' => "{$input['name']}'s Company",
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);
        });
    }

    /**
     * Block registration submissions when self-service signups are disabled.
     */
    private function ensureRegistrationAllowed(): void
    {
        if (Config::boolean('app.live_demo_enabled', false)) {
            throw new NotFoundHttpException;
        }
    }
}
