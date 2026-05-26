<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Auth\LoginDemoUser;
use App\Http\Requests\Auth\DemoLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

/**
 * Starts an authenticated sandbox session for portfolio reviewers and demo users.
 */
final class DemoLoginController extends Controller
{
    /**
     * Authenticate the reserved demo account and continue to the dashboard.
     */
    public function __invoke(DemoLoginRequest $request, LoginDemoUser $loginDemoUser): RedirectResponse
    {
        $loginDemoUser->execute();
        $request->session()->regenerate();

        return new RedirectResponse(URL::route('dashboard', absolute: false));
    }
}
