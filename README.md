# ProposalKit

ProposalKit is a Laravel 13 and Vue 3 SaaS platform for building, sending, tracking, and digitally accepting commercial proposals inside tenant-isolated workspaces.

ProposalKit is developed with a strict focus on data integrity, explicit multi-tenant boundaries, and immutable logs. The architecture isolates commercial documents, enforces cryptographic protections against client-side tampering, and tracks all lifecycle state transitions with structural evidence variables.

## Features

- Multi-tenant workspace isolation backed by a single database, tenant-aware models, and dedicated isolation tests.
- Service catalog management for tenant-owned billable services and pricing defaults.
- Client management for tenant-owned customer records.
- Proposal builder with itemized pricing, totals, validity windows, and controlled lifecycle transitions.
- Asynchronous proposal PDF generation and delivery workflows.
- Secure public proposal experience for unauthenticated recipients using signed URLs, typed-name acceptance, rejection capture, PDF download, and replay protection.

## Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| Frontend | Inertia.js, Vue 3, TypeScript |
| UI | PrimeVue, Tailwind CSS v4 |
| Database | MySQL 8 |
| Cache / Queue | Redis, Laravel Queues |
| Auth | Laravel Fortify |
| PDF | DOMPDF-backed proposal rendering |
| State Machine | Spatie Laravel Model States |
| Code Quality | Laravel Pint, Rector, Larastan Level 8, PHPUnit, ESLint, Prettier, Vue TypeScript checks |

## Requirements

- PHP 8.4+
- Composer 2
- Node.js 22+
- npm
- MySQL 8
- Redis

## Architecture Highlights

### Tenant Isolation

Tenant ownership is enforced through tenant-scoped models and global scope conventions. Feature code does not manually filter by tenant IDs; tenant boundaries live in the model layer and are verified through automated tests.

### Proposal State Machine

Proposal status is modeled through `spatie/laravel-model-states`, not raw strings. The application transitions proposals through concrete state classes such as `Draft`, `Sent`, `Viewed`, `Accepted`, and `Declined`, with invalid transitions blocked by the state machine and covered by tests. This prevents illegal business mutations, such as modifying or re-accepting a proposal after the commercial document has already been finalized.

### Polymorphic Lifecycle Auditing

Proposal activity is persisted in an append-only `proposal_events` audit trail. Events support polymorphic actors for authenticated tenant users while keeping unauthenticated guest client activity decoupled from application user accounts. Public interactions capture legal evidence such as IP address, User-Agent, timestamps, typed-name acceptance data, rejection reason, and referrer metadata where applicable. This creates a durable record of who acted, what changed, and which client-side evidence accompanied the decision.

### HMAC-Signed Public Access

Public proposal routes use the secure layout `/p/{tenant_slug}/{secure_token}` and Laravel signed URL validation. Public links avoid auto-increment identifiers, eliminate ID enumeration exposure, include per-proposal secure tokens, expire against the proposal validity window, and return `410 Gone` for finalized records where replay or document tampering must be prevented.

### Decoupled Action Layer

Models remain clean from HTTP session concerns. Actor resolution, public evidence capture, state transitions, PDF streaming, and proposal response handling are delegated to dedicated Action classes and shared concerns. Controllers orchestrate requests and responses; they do not own business rules. This keeps compliance-sensitive behavior explicit, testable, and isolated from persistence models.

### PHP 8.4 Runtime

The backend targets PHP 8.4 to keep the application aligned with the Laravel 13 ecosystem and modern package requirements. Strong typing, current language features, and static-analysis-friendly code reduce ambiguity in business-critical flows such as proposal transitions, tenant ownership, and client acceptance evidence.

### Private PDF Delivery

Generated proposal PDFs are stored outside the public web root and streamed through signed public routes. This keeps document access governed by the same token, signature, and lifecycle policies as the public proposal interface.

## Getting Started

Install PHP dependencies:

```bash
composer install
```

Install Node dependencies. Use `npm ci` for lockfile-exact installs, or `npm install` during dependency work:

```bash
npm ci
```

Create the environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database and Redis connection values in `.env`, then build the database:

```bash
php artisan migrate:fresh --seed
```

Generate Wayfinder routes:

```bash
php artisan wayfinder:generate
```

Build frontend assets:

```bash
npm run build
```

## Development

Run the Laravel application server:

```bash
php artisan serve
```

In a separate terminal, run the Vite development server:

```bash
npm run dev
```

## Quality Gates

Run the full backend verification pipeline:

```bash
composer test
```

This command clears cached configuration, applies or verifies PHP code style with Laravel Pint, verifies Rector refactoring rules in dry-run mode, runs Larastan at Level 8, and executes the complete PHPUnit integration test suite in sequence.

Run individual backend checks when isolating a failure:

```bash
./vendor/bin/pint --parallel --test
./vendor/bin/rector process --dry-run
./vendor/bin/phpstan analyse
php artisan test
```

Run frontend type, formatting, and lint checks:

```bash
npm run types:check
npm run format:check
npm run lint:check
```

## Documentation

- [Architecture & Technical Standards](docs/ARCHITECTURE.md)
- [ADR: Enforce PHP 8.4 for Modern Packages](docs/decisions/001-enforce-php84-for-modern-packages.md)
