<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the authenticated tenant user that caused an auditable action.
 */
trait ResolvesAuthenticatedActor
{
    /**
     * Resolve the authenticated tenant user that caused the proposal event.
     *
     * @return array{actor_type: class-string<User>|null, actor_id: int|null}
     */
    private function authenticatedActor(): array
    {
        if (! Auth::check()) {
            return [
                'actor_type' => null,
                'actor_id' => null,
            ];
        }

        $userId = Auth::id();

        return [
            'actor_type' => User::class,
            'actor_id' => $userId === null ? null : (int) $userId,
        ];
    }
}
