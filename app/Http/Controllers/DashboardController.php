<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardIndexRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ProposalAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders tenant-scoped proposal intelligence dashboards.
 */
final class DashboardController extends Controller
{
    /**
     * Display cached pipeline metrics for the authenticated tenant.
     */
    public function index(
        DashboardIndexRequest $request,
        ProposalAnalyticsService $analytics,
    ): Response {
        $user = $request->user();
        assert($user instanceof User);

        $tenant = $user->tenant;
        assert($tenant instanceof Tenant);

        $metrics = $analytics->dashboard(
            tenant: $tenant,
            from: $request->startDate(),
            to: $request->endDate(),
        );

        return Inertia::render('dashboard/Index', [
            'metrics' => $metrics,
            'filters' => [
                'start_date' => $request->startDate()?->toDateString(),
                'end_date' => $request->endDate()?->toDateString(),
            ],
        ]);
    }
}
