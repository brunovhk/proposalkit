<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * @var list<string>
     */
    private const array FLASH_KEYS = ['success', 'warning', 'error'];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    #[\Override]
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => fn (): array => $this->flashMessages($request),
            'locale' => app()->getLocale(),
            'live_demo_enabled' => config('app.live_demo_enabled') === true,
            'translations' => fn (): array => $this->translations(app()->getLocale()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function flashMessages(Request $request): array
    {
        return collect(self::FLASH_KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => $this->stringFlash($request, $key)])
            ->all();
    }

    private function stringFlash(Request $request, string $key): ?string
    {
        $message = $request->session()->get($key);

        return is_string($message) ? $message : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function translations(string $locale): array
    {
        $path = base_path("lang/{$locale}");

        if (! is_dir($path)) {
            return [];
        }

        return collect(File::files($path))
            ->mapWithKeys(fn ($file): array => [
                $file->getFilenameWithoutExtension() => require $file->getPathname(),
            ])
            ->all();
    }
}
