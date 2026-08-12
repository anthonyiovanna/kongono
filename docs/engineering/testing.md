# Testing

## Purpose and runner

PHPUnit 13 is the official runner for Kongono's own framework repository. This decision gives core development one test lifecycle and assertion model; it does not constrain applications built with Kongono or create a framework testing API.

Run tests with:

```bash
composer test
```

The root suite discovers repository tooling and architecture tests beneath `tests/`, Kernel tests beneath `packages/kernel/tests/`, and structural Graph tests beneath `packages/application-graph/tests/`. Each package declares its own development test namespace, which the root repeats in `autoload-dev` because dependency development mappings are not imported. Phase 0D moves the deterministic production-symbol and API-status inventory check to `tests/Architecture` so root architecture owns discovery across every `packages/*/src` tree; its token scanner remains test-only infrastructure rather than a Graph source frontend.

## Failure behavior

The PHPUnit configuration explicitly fails on warnings, risky tests, deprecations, PHPUnit deprecations, notices, incomplete tests, an empty configured suite, and unexpected output. These signals often expose broken assumptions or accidental behavior and must not be hidden by skipping a test, marking it incomplete, or weakening configuration.

Coverage metadata and a coverage percentage are not selected in Phase 0B. Their absence is not permission to claim coverage. Future coverage policy requires separate authorization.

Tests may write PHPUnit's local cache, but they must not introduce unapproved framework functionality, network dependencies, persistent state, or public contracts merely to exercise the runner.
