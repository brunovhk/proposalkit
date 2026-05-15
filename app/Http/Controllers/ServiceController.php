<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Services\CreateService;
use App\Actions\Services\DeleteService;
use App\Actions\Services\UpdateService;
use App\Enums\ServiceType;
use App\Http\Requests\Services\DeleteServiceRequest;
use App\Http\Requests\Services\StoreServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders and processes the tenant-scoped service catalog management screens.
 */
class ServiceController extends Controller
{
    /**
     * Display the tenant-scoped service catalog.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Service::class);

        return Inertia::render('services/Index', [
            'services' => ServiceResource::collection(
                Service::query()
                    ->latest()
                    ->get(),
            ),
        ]);
    }

    /**
     * Display the service creation form.
     */
    public function create(): Response
    {
        Gate::authorize('create', Service::class);

        return Inertia::render('services/Form', [
            'service' => null,
            'serviceTypes' => $this->serviceTypeOptions(),
        ]);
    }

    /**
     * Persist a newly created service.
     */
    public function store(StoreServiceRequest $request, CreateService $createService): RedirectResponse
    {
        $createService->execute($request->validated());

        return to_route('services.index')
            ->with('success', __('ui.services.created'));
    }

    /**
     * Display the service editing form.
     */
    public function edit(Service $service): Response
    {
        Gate::authorize('update', $service);

        return Inertia::render('services/Form', [
            'service' => ServiceResource::make($service),
            'serviceTypes' => $this->serviceTypeOptions(),
        ]);
    }

    /**
     * Persist updates to an existing service.
     */
    public function update(
        UpdateServiceRequest $request,
        Service $service,
        UpdateService $updateService,
    ): RedirectResponse {
        $updateService->execute($service, $request->validated());

        return to_route('services.index')
            ->with('success', __('ui.services.updated'));
    }

    /**
     * Delete an existing service.
     */
    public function destroy(
        DeleteServiceRequest $request,
        Service $service,
        DeleteService $deleteService,
    ): RedirectResponse {
        $deleteService->execute($service);

        return to_route('services.index')
            ->with('success', __('ui.services.deleted'));
    }

    /**
     * Get service type options for frontend form controls.
     *
     * @return list<array{label: string, value: string}>
     */
    private function serviceTypeOptions(): array
    {
        return array_map(
            static fn (ServiceType $type): array => [
                'label' => __("ui.services.types.{$type->value}"),
                'value' => $type->value,
            ],
            ServiceType::cases(),
        );
    }
}
