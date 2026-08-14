# Testing

## Purpose and runner

PHPUnit 13 is the official runner for Kongono's own framework repository. This decision gives core development one test lifecycle and assertion model; it does not constrain applications built with Kongono or create a framework testing API.

Run tests with:

```bash
composer test
```

The root suite discovers repository tooling and architecture tests beneath `tests/`, plus package tests beneath the API Metadata, Kernel, Application Graph, Dependency Injection, and Diagnostics trees. Each package declares its own development test namespace, which the root repeats in `autoload-dev` because dependency development mappings are not imported. The exact test inventory is 73 PHP files. Root architecture owns the exact 40-type API-status inventory, four-way exception-marker separation, and 53 globally unique diagnostic IDs split 6 Kernel, 11 Graph, 25 DI, and 11 Diagnostics across every `packages/*/src` tree; its token scanner remains test-only infrastructure rather than a Graph or dependency-discovery frontend.

The 40 production PHP files plus 73 test PHP files form an exact 113-file
source-and-test inventory. With `.php-cs-fixer.dist.php`, syntax validation has
114 entries; style checks cover the 113 PHP files.

Phase 0S adds one focused five-method API Metadata test covering the exact enum
vocabulary, attribute shape, bootstrap exemptions, removal of the legacy Kernel
FQCNs, and the two-source-file package boundary. Root enforcement discovers all
five package source trees while preserving the 40-type inventory.

Phase 0M tests cover all 42 package-local catalog cases, exact enum case names,
identifiers, titles, remediation strings, global uniqueness and domain syntax;
the exact Public marker and Internal base reflection shapes; every reachable
deliberate throw mapping; both static DI-1025 invariant-defense sites; unchanged
messages and state guarantees; optional-cause identity and native code behavior;
and unchanged autoloader and consumer-constructor propagation. Catalog tests
duplicate the authorized expectations explicitly; production catalogs do not
read Markdown or external data at runtime.

Phase 0P adds seven focused Diagnostics test files covering the exact
vocabularies, schemas, detached snapshots, scalar-kind checks, byte-oriented text
representation and truncation, package-local construction failures, API status,
and absence of ambient or mutable global state.

Phase 0Q adds focused Graph coverage for all 11 catalog-owned schemas and exact
throw-site values, numeric first-mismatch capture, arbitrary string-key omission,
the closed broad kind-token vocabulary, schema order, bounded byte
representation, same-object context access, detachment, cause/code/message
preservation, failure priority, success behavior, exact marker/base shapes, and
the one-way Graph-to-Diagnostics dependency. Phase 0T adds one exact seven-method
Kernel diagnostic-context test covering all six schemas and maps, closed values,
same-object ownership, detachment, reflection shapes, marker separation, and the
one-way Kernel-to-Diagnostics dependency. Phase 0U adds one exact ten-method DI
diagnostic-context test covering all 25 schemas, 26 throw-site maps, bounded token
algorithms, context ownership, native propagation, and the independent
DI-to-Diagnostics edge. The canonical suite contains exactly 204 tests.

## Failure behavior

The PHPUnit configuration explicitly fails on warnings, risky tests, deprecations, PHPUnit deprecations, notices, incomplete tests, an empty configured suite, and unexpected output. These signals often expose broken assumptions or accidental behavior and must not be hidden by skipping a test, marking it incomplete, or weakening configuration.

Coverage metadata and a coverage percentage are not selected in Phase 0B. Their absence is not permission to claim coverage. Future coverage policy requires separate authorization.

Tests may write PHPUnit's local cache, but they must not introduce unapproved framework functionality, network dependencies, persistent state, or public contracts merely to exercise the runner.
