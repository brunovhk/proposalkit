<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Clients\CreateClient;
use App\Actions\Clients\DeleteClient;
use App\Actions\Clients\UpdateClient;
use App\Http\Requests\Clients\DeleteClientRequest;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders and processes tenant-scoped client management screens.
 */
class ClientController extends Controller
{
    /**
     * Display the tenant-scoped client list.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Client::class);

        return Inertia::render('clients/Index', [
            'clients' => ClientResource::collection(
                Client::query()
                    ->latest()
                    ->get(),
            ),
        ]);
    }

    /**
     * Display the client creation form.
     */
    public function create(): Response
    {
        Gate::authorize('create', Client::class);

        return Inertia::render('clients/ClientForm', [
            'client' => null,
        ]);
    }

    /**
     * Persist a newly created client.
     */
    public function store(StoreClientRequest $request, CreateClient $createClient): RedirectResponse
    {
        $createClient->execute($request->validated());

        return to_route('clients.index')
            ->with('success', __('ui.clients.created'));
    }

    /**
     * Display the client editing form.
     */
    public function edit(Client $client): Response
    {
        Gate::authorize('update', $client);

        return Inertia::render('clients/ClientForm', [
            'client' => ClientResource::make($client),
        ]);
    }

    /**
     * Persist updates to an existing client.
     */
    public function update(
        UpdateClientRequest $request,
        Client $client,
        UpdateClient $updateClient,
    ): RedirectResponse {
        $updateClient->execute($client, $request->validated());

        return to_route('clients.index')
            ->with('success', __('ui.clients.updated'));
    }

    /**
     * Delete an existing client.
     */
    public function destroy(
        DeleteClientRequest $request,
        Client $client,
        DeleteClient $deleteClient,
    ): RedirectResponse {
        $deleteClient->execute($client);

        return to_route('clients.index')
            ->with('success', __('ui.clients.deleted'));
    }
}
