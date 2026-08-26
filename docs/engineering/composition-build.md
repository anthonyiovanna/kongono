# Building and Validating Composition Artifacts

## Phase 8G planning and application

`ApplicationBuildWorkflow::plan()` is the non-mutating counterpart to the
existing build workflow. It resolves the same guarded application and output
paths, builds the Composition artifact and generated wiring in memory, computes
their content-addressed basenames and SHA-256 identities, and assesses the two
expected output paths. Both absent predicts publication; both exact current
predicts no publication. Partial, linked, tampered, outside, or changing state
fails closed.

The Phase 8G `application:build` handler uses plan without writing for
`--plan`. For `--apply`, it plans, calls the existing atomic, contained,
initial-only, no-clobber publication workflow, then replans and requires the
same predicted identities to be exact current. It never deletes or chooses an
older content-addressed output after source changes. No transaction, runtime
loader, provider, deployment, or cleanup authority is added.

Phase 8H runs the supported command through both executable edges and verifies
that predicted hashes match exact published bytes. A pre-existing conflicting
content-addressed target remains unchanged, publishes no sibling partial, and
returns the accepted Precondition exit 3. The root-owned proof is
`composer acceptance:phase8`; see the [external acceptance guide](../architecture/phase-8-external-acceptance.md).

Phase 5D exposes an Experimental PHP API, not a command. Callers provide an
explicit resolved application root containing ordinary exact-case
`kongono.composition.json`, `composer.json`, `composer.lock`, and the source
files declaring every admitted symbol.

```php
use Kongono\Composition\Build\CompositionArtifactBuilder;
use Kongono\Composition\Build\CompositionArtifactPublisher;
use Kongono\Composition\Build\CompositionArtifactValidator;

$artifact = (new CompositionArtifactBuilder())->build($applicationRoot);
(new CompositionArtifactValidator())->validateCurrent(
    $applicationRoot,
    $artifact->canonicalBytes(),
);
$publishedPath = (new CompositionArtifactPublisher())->publish(
    $artifact,
    $existingOutputDirectory,
);
```

The source must be strict UTF-8 JSON without BOM, CR, NUL, duplicate members,
unknown keys, or extended syntax. It has exactly one final LF and a 1,048,576-
byte maximum. Validation checks all version-1 declaration bounds, canonical
symbol spelling, constructors, explicit bindings, roots, lifetimes, cycles,
cleanup signatures, and contained declaring-source provenance.

Use `validateStructure()` for detached canonical-byte validation. Use
`validateCurrent()` before relying on an artifact in the same application
state; it rebuilds and requires exact byte equality. A semantically unchanged
but raw-byte-different source is stale by design even if its `model_sha256` is
unchanged.

Publication never chooses an output directory. Create and secure an ordinary
directory first, then pass it explicitly. An identical content-addressed target
is safe to reuse; a different existing target at that identity is an error.

Catch `Kongono\Composition\Exception\ExceptionInterface` only at the package
boundary. Its ID, title, remediation, and bounded diagnostic context are safe
for controlled handling; do not infer rejected source text or paths from them.

For repository development, run the composition package tests, focused static
analysis, and cache-disabled style check before the complete `composer quality`
gate. The tests use fresh system-temporary application and output directories.

Phase 5E provides generated wiring after an exact current Phase 5D artifact is
available, and Phase 5F provides the fail-closed loader. Phase 5G adds the
reusable `ApplicationBuildWorkflow`: build publishes both content-addressed
files and rechecks them; check derives and validates both exact files without
writing or scanning. The package still has no CLI, container, current/latest
pointer, automatic cleanup, listener, or deployment integration. See the
[generated composition wiring guide](../architecture/generated-composition-wiring.md)
and [application workflow guide](application-workflow.md).
