<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SystemUserEmail;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents the reserved demo account from mutating tenant-owned workspace data.
 */
final class BlockDemoWriteOperations
{
    /**
     * Block unsafe HTTP methods for the public sandbox account.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.live_demo_enabled') !== true) {
            return $next($request);
        }

        if ($this->isProtectedProfileWrite($request)) {
            session()->flash('warning', 'Create, Edit, and Delete operations are disabled in the Live Demo.');

            return back();
        }

        if (! $this->isDemoWriteAttempt($request) || $this->isAllowedException($request)) {
            return $next($request);
        }

        session()->flash('warning', 'Create, Edit, and Delete operations are disabled in the Live Demo.');

        return back();
    }

    private function isDemoWriteAttempt(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User
            && $user->email === SystemUserEmail::Demo->value
            && $this->isWriteMethod($request);
    }

    private function isWriteMethod(Request $request): bool
    {
        return array_any(
            ['POST', 'PUT', 'PATCH', 'DELETE'],
            static fn (string $method): bool => $request->isMethod($method),
        );
    }

    private function isAllowedException(Request $request): bool
    {
        return $request->routeIs('demo.login')
            || $request->routeIs('dashboard');
    }

    private function isProtectedProfileWrite(Request $request): bool
    {
        return $this->isWriteMethod($request)
            && $request->routeIs(
                'profile.update',
                'profile.destroy',
                'user-password.update',
                'user-profile-information.update',
            );
    }
}
