# Testing

## Purpose and runner

PHPUnit 13 is the official runner for Kongono's own framework repository. This decision gives core development one test lifecycle and assertion model; it does not constrain applications built with Kongono or create a framework testing API.

Run tests with:

```bash
composer test
```

The root suite discovers repository tooling and architecture tests beneath `tests/`, plus package tests beneath the Kernel, Application Graph, and Dependency Injection trees. Each package declares its own development test namespace, which the root repeats in `autoload-dev` because dependency development mappings are not imported. Root architecture owns the exact 30-type API-status inventory and pairwise exception-marker separation across every `packages/*/src` tree; its token scanner remains test-only infrastructure rather than a Graph or dependency-discovery frontend.

Phase 0M tests cover all 42 package-local catalog cases, exact enum case names,
identifiers, titles, remediation strings, global uniqueness and domain syntax;
the exact Public marker and Internal base reflection shapes; every reachable
deliberate throw mapping; both static DI-1025 invariant-defense sites; unchanged
messages and state guarantees; optional-cause identity and native code behavior;
and unchanged autoloader and consumer-constructor propagation. Catalog tests
duplicate the authorized expectations explicitly; production catalogs do not
read Markdown or external data at runtime.

## Failure behavior

The PHPUnit configuration explicitly fails on warnings, risky tests, deprecations, PHPUnit deprecations, notices, incomplete tests, an empty configured suite, and unexpected output. These signals often expose broken assumptions or accidental behavior and must not be hidden by skipping a test, marking it incomplete, or weakening configuration.

Coverage metadata and a coverage percentage are not selected in Phase 0B. Their absence is not permission to claim coverage. Future coverage policy requires separate authorization.

Tests may write PHPUnit's local cache, but they must not introduce unapproved framework functionality, network dependencies, persistent state, or public contracts merely to exercise the runner.
