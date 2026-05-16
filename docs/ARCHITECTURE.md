# Architecture & Technical Standards

ProposalKit is a Laravel 13 application with an Inertia.js and Vue 3 frontend. The current architecture favors explicit tenant boundaries, strict financial storage rules, typed backend code, and predictable CRUD workflows.

## Multi-Tenancy

ProposalKit uses a single-database, shared-schema tenancy model. Tenant-owned records carry a `tenant_id` foreign key and are isolated through Eloquent global scopes rather than separate databases or schemas.

Tenant-aware models use `App\Models\Concerns\BelongsToTenant`. The trait registers `App\Models\Scopes\TenantScope` and automatically assigns `tenant_id` during model creation when an authenticated `App\Models\User` is available. Current tenant-owned models include `User`, `Client`, and `Service`.

`TenantScope` applies the authenticated user's tenant boundary to Eloquent queries:

- Queries are constrained with the model-qualified `tenant_id` column.
- Console execution is skipped unless tests are running, so maintenance commands are not unexpectedly scoped.
- Unauthenticated queries are left unscoped.

The scope and trait intentionally use `Auth::guard()->hasUser()` before calling `user()`. This avoids recursive authentication resolution. Because `User` itself is tenant-scoped, resolving the authenticated user through a tenant-scoped query can re-enter the tenant scope while the guard is still loading the user. Checking whether the guard already has a resolved user prevents that recursion while still applying the tenant filter after authentication middleware has resolved the session user.

Tenant isolation is reinforced in policies and route model binding. Policies verify ownership for tenant-owned records, and scoped route model queries prevent cross-tenant records from being resolved for authenticated users.

## Financial Integrity

Monetary values are stored as integers in the database, using cents as the canonical unit. Floating-point values must not be persisted.

The `services.price` column is an unsigned integer and the `Service` model casts it as `integer`. Service create and update requests validate `price` as an integer with a minimum of zero. Backend actions receive validated cents and persist them without currency conversion.

The frontend may present money as major units for usability, but it must convert back to cents before submission. Service forms use PrimeVue `InputNumber` in currency mode for display and editing, then transform the entered amount with `Math.round(amount * 100)` before posting or putting through Inertia.

API and Inertia payloads should expose money through resources. `ServiceResource` returns both the raw cent value and a localized formatted string:

- `price.cents` is the canonical numeric value for forms and calculations.
- `price.formatted` is display-only text produced by the backend.

New financial fields should follow the same boundary: integer cents in migrations, integer validation in form requests, conversion in Vue form submission, and formatting through backend resources.

## Frontend Architecture

The frontend is built with Inertia.js, Vue 3, TypeScript, PrimeVue, and Tailwind CSS v4.

`resources/js/app.ts` initializes the Inertia application, resolves Vue pages from `resources/js/pages`, and assigns layouts centrally:

- `Welcome` renders without the authenticated app layout.
- `auth/*` pages use the auth layout.
- `settings/*` pages compose the app layout with the settings layout.
- All other authenticated pages use `AppLayout`.

PrimeVue is configured globally with the Aura theme, ripple support, dark-mode integration through the `.dark` selector, and the PrimeVue toast service. Components are auto-imported through `unplugin-vue-components` with `PrimeVueResolver` in `vite.config.ts`, so page components can use PrimeVue components without local imports.

Tailwind CSS v4 is loaded through the `@tailwindcss/vite` Vite plugin. Application CSS imports `tailwindcss` and `tailwindcss-primeui`, which keeps PrimeVue styling and Tailwind utility usage aligned.

Toast notifications are centralized through the application shell. `AppLayout` watches Inertia flash success messages and sends them through PrimeVue's toast service. `initializeFlashToast` also listens for Inertia flash events carrying structured toast data. Feature pages should set flash messages on redirects and avoid creating local notification systems.

## Backend Architecture

Controllers render Inertia pages, authorize operations, delegate writes to action classes, and return redirects with flash messages. Request validation and authorization live in dedicated form request classes. Resource classes define the payload shape consumed by Vue pages.

The standard backend flow for writes is:

1. Route to a resource controller action.
2. Authorize with a policy or form request.
3. Validate with a form request.
4. Execute the write through an action class.
5. Redirect to the index route with a flash message.

This keeps controllers thin while preserving explicit authorization and validation at the HTTP boundary.

## Code Quality

PHP source files must use `declare(strict_types=1);` unless the file type or framework convention prevents it. Existing application, model, request, resource, action, factory, migration, and feature test files follow this convention.

Static analysis is enforced with Larastan at level 8. Type annotations, generic PHPDoc, casts, and resource payload declarations should be kept precise enough to satisfy that level without suppressing real issues.

Formatting and quality checks are defined in Composer and npm scripts:

- `composer lint` runs Laravel Pint.
- `composer test` clears config, checks formatting, and runs the PHP test suite.
- `composer ci:check` runs frontend linting, formatting checks, type checks, and backend tests.
- `npm run types:check` runs Vue TypeScript checks.

Tests should scale with the behavior being changed. Tenant isolation and authorization-sensitive behavior require feature tests. CRUD features should cover create, update, delete, validation, authorization, and tenant boundaries where applicable. Existing coverage includes tenant scope tests, registration tenancy tests, and feature tests for client and service management.

## Navigation & CRUD Patterns

Authenticated CRUD routes use Laravel resource controllers and omit `show` routes where standalone detail pages are not needed. The current `clients` and `services` resources both follow this pattern.

The standard CRUD user experience is:

- Index pages use PrimeVue `DataTable` with row actions.
- Create and edit flows use standalone Inertia pages.
- Detail views use PrimeVue dialogs launched from the index page.
- Delete flows use confirmation dialogs and Inertia `router.delete`.
- Successful writes redirect back to the index page and show a toast.

Create and edit pages set breadcrumbs through `setLayoutProps`. Index pages own transient UI state such as selected records and pending deletes. Resource payloads include route URLs needed by the frontend, such as edit links, so page components do not duplicate backend routing logic beyond simple collection URLs.

New CRUD modules should follow the same structure: model, policy, form requests, action classes, resource class, resource controller, feature tests, an index page, a reusable form page, and optional detail and delete dialogs.
