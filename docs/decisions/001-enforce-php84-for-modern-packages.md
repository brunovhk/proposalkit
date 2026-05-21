# 1. Enforce PHP 8.4 to Support Modern Laravel 13 Ecosystem

Date: 2026-05-17

## Context

The `spatie/laravel-model-states` package, which is critical for the proposal lifecycle, requires PHP 8.4 when used with Laravel 13. The project rules also mandate readiness for PHP 8.4 features such as property hooks and asymmetric visibility. Running CI against PHP 8.3 creates a false constraint and will break builds as soon as modern syntax is used.

## Decision

Drop PHP 8.3 support entirely. Keep `composer.json` requirements and CI workflow matrices pinned to PHP 8.4.

## Consequences

Deployment targets must provide PHP 8.4 or newer. In exchange, the project can rely on a robust modern state machine package, avoid compatibility debt, and keep CI aligned with the codebase's syntax and runtime requirements.
