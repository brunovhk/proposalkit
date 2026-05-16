# ProposalKit

ProposalKit is a Laravel 13 application for managing proposal-related business data in a tenant-isolated workspace.

## Features

- Multi-tenant architecture with single-database tenant isolation.
- Service Catalog for managing tenant-owned billable services and pricing.
- Client Management for maintaining tenant-owned customer records.

## Stack

- Laravel 13 and PHP 8.3
- Inertia.js with Vue 3 and TypeScript
- PrimeVue with auto-imported components
- Tailwind CSS v4 with PrimeVue integration
- Laravel Fortify for authentication
- Larastan, Pint, ESLint, Prettier, and Vue TypeScript checks

## Requirements

- PHP 8.3+
- Node.js 22+
- MySQL 8
- Redis
- Composer 2
- npm

## Getting Started

Install PHP dependencies:

```bash
composer install
```

Install Node dependencies. Use `npm ci` for a lockfile-exact install, or `npm install` during local dependency work:

```bash
npm ci
```

Create the environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database and Redis connection values in `.env`, then migrate and seed the database:

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

## Development Server

Run the Laravel application server:

```bash
php artisan serve
```

In a separate terminal, run the Vite development server:

```bash
npm run dev
```

## Testing & Code Quality

Run Larastan at level 8:

```bash
./vendor/bin/phpstan analyse
```

Run the PHP test suite:

```bash
php artisan test
```

Run frontend type checks, formatting checks, and linting:

```bash
npm run types:check
npm run format:check
npm run lint:check
```

Run backend formatting checks with Laravel Pint:

```bash
composer run lint:check
```

## Technical Documentation

- [Architecture & Technical Standards](docs/ARCHITECTURE.md)
