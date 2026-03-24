# Agent Guidelines for laravel-flipt

This document provides guidance for AI coding agents working in this repository.

## Project Overview

`clearlyip/laravel-flipt` is a Laravel package that integrates [Flipt](https://flipt.io/) feature flags into Laravel applications. It provides:

- A Laravel Pennant driver (`FliptFeatureDriver`)
- A fluent `Flipt` client class for direct API access
- Laravel cache integration with tagging support
- Artisan commands for cache management

**Namespace:** `Clearlyip\LaravelFlipt`
**PHP version:** 8.4+
**Laravel version:** 12.x / 13.x

## Repository Layout

```
src/
  Flipt.php                 # Main client class; exposes API modules via magic __get
  ServiceProvider.php       # Registers the Flipt binding and Pennant driver
  Commands/
    CacheClear.php          # php artisan flipt:cache:clear
    UserCacheClear.php      # php artisan flipt:cache:user:clear {userId}
  Contracts/
    Response.php            # Base contract for response models
  Enums/                    # All package enumerations
  Flipt/
    ClientEvaluation.php    # Batch/client-side evaluation API
    Environments.php        # Environments API
    Evaluate.php            # Boolean & variant flag evaluation
    Flags.php               # Flag listing/definition API
    Internal.php            # Internal Flipt API
    OpenFeature.php         # OpenFeature-compatible evaluation
  Models/                   # Readonly value-object models (fromArray factories)
  Pennant/
    FliptFeatureDriver.php  # Laravel Pennant driver implementation
config/
  flipt.php                 # Package configuration (published to app's config/)
routes/
  flipt.php                 # Reserved for future routes (currently empty)
tests/
  Unit/                     # Pest test suites
```

## Commands

All commands are run from the repository root.

| Command                 | Purpose                                                             |
| :---------------------- | :------------------------------------------------------------------ |
| `composer test`         | Run the full test suite with coverage (`XDEBUG_MODE=coverage pest`) |
| `composer psalm`        | Run static analysis with Psalm                                      |
| `composer mago:lint`    | Lint source files with Mago                                         |
| `composer mago:format`  | Auto-format source files with Mago                                  |
| `composer mago:analyze` | Deep analysis with Mago                                             |
| `composer rector`       | Apply automated refactors with Rector                               |

> Always run `composer test` after making changes to verify nothing is broken.

## Code Style & Conventions

- **PHP 8.4+** features are encouraged — use readonly classes, constructor property promotion, enums, and match expressions.
- All source classes use `declare(strict_types=1)` is not required at the file level but is enforced structurally through Mago linting rules.
- **Formatting** is managed by Mago (see `mago.toml`): single quotes, 80-char print width, trailing commas, same-line braces.
- Run `composer mago:format` before committing to ensure consistent formatting.
- **Static analysis** is enforced by Psalm at error level 4. New code must not introduce new Psalm errors.
- **Rector** enforces modern PHP idioms — run `composer rector` to check and apply upgrades.
- Models in `src/Models/` are readonly value objects and must implement a static `fromArray(array $data): static` factory method.
- API modules in `src/Flipt/` receive the parent `Flipt` instance via constructor and should use `$this->client->request(...)` for HTTP calls.

## Testing

- Tests use [Pest](https://pestphp.com/) and live in `tests/Unit/`.
- The test bootstrap is `tests/Pest.php` / `tests/TestCase.php`, using [Orchestra Testbench](https://github.com/orchestral/testbench).
- HTTP mocking is done with [Guzzler](https://github.com/blastcloud/guzzler).
- Tests must not make real HTTP calls to a Flipt instance.
- When adding a new feature, add corresponding tests in `tests/Unit/`.

## Architecture Notes

- `Flipt` is a **readonly** class (uses `spatie/php-cloneable`). New instances with modified properties are created via `$this->with(...)`. Do not add mutable state.
- API module classes (under `src/Flipt/`) are instantiated on-demand via `Flipt::__get()` — they are not singletons and should be stateless.
- The Pennant driver (`FliptFeatureDriver`) implements both `Driver` and `DefinesFeaturesExternally`. The `define()` method intentionally throws — flags are defined externally in Flipt, not in code.
- Cache keys are prefixed with the value from `config('flipt.cache.prefix')` and tagged with `config('flipt.cache.tags')`. Only tag-supporting cache stores (Redis, Memcached) are fully supported for cache clearing.

## What to Avoid

- Do not add mutable state to the `Flipt` class or any API module.
- Do not bypass the cache layer in production code paths — use `withSkipCache()` only when the caller explicitly requests it.
- Do not introduce new Composer dependencies without discussion.
- Do not add routes to `routes/flipt.php` without a corresponding feature implementation and tests.
- Do not commit with failing tests or Psalm errors.
