# Cache Application Workflow

Phase 20F accepts the optional Experimental `kongono/cache-application` package above the accepted cache contract, build, adapter-contract, and runtime leaves. It supplies explicit profile plan/build/check operations and exact-current runtime composition; it does not cache application values automatically.

## Profile source and workflow

An application supplies an absolute canonical application root, a relative contained source file, and an absolute contained output directory:

```json
{"schema":"kongono/cache-application-profile-source/1","profiles":[{"name":"application","contract_version":1,"namespace":"application","codec":{"name":"application-json","version":1},"lifetime_microseconds":1000000,"max_value_bytes":1024}]}
```

The document is a complete set. Empty sets are valid. Parsing rejects duplicate members, unknown or missing fields, unsupported versions and types, invalid lower values, excessive bytes, depth, or profile count, and ambiguous paths. Reading is stable, bounded, ordinary-file-only, and contained.

Programmatic use is explicit:

```php
$definition = new CacheApplicationDefinition($root, 'kongono.cache.json', $root . '/var/cache');
$workflow = new CacheApplicationWorkflow();
$plan = $workflow->plan($definition);   // write-free
$current = $workflow->build($definition); // publish then exact-current validate
$checked = $workflow->check($definition); // write-free
```

Only `build` may write. Publication remains content-addressed, atomic, contained, no-clobber, and idempotent only when existing bytes match exactly. Plan and check never create directories, repair evidence, select a newest artifact, or fall back to a build.

Equivalent supported command operations are:

```text
kongo cache:plan --application-root=... --source-file=kongono.cache.json --output-directory=...
kongo cache:build --plan  --application-root=... --source-file=kongono.cache.json --output-directory=...
kongo cache:build --apply --application-root=... --source-file=kongono.cache.json --output-directory=...
kongo cache:check --application-root=... --source-file=kongono.cache.json --output-directory=...
```

Human and JSON output present one semantic `CommandResultRecord`. No cache get, set, delete, flush, warm, enumerate, inspect, or benchmark command exists.

## Explicit codec and runtime composition

The application owns validation and deterministic encoding:

```php
$codec = new ApplicationJsonCodec();
$mapping = new CacheProfileCodecMapping(new CacheProfileName('application'), $codec->identity());
$adapter = new BoundedMemoryCacheAdapter($capacity, MemoryEvictionPolicy::OldestInsertionFirst);
$runtime = (new CacheApplicationRuntimeFactory())->create(
    $checked,
    $mapping,
    $adapter->identity(),
    $adapter,
);

$bytes = $codec->encode($applicationValue);
$stored = $runtime->store(new CacheStoreRequest($logicalKey, $bytes, new CacheInstant(100)));
$lookup = $runtime->lookup(new CacheLookupRequest($logicalKey, new CacheInstant(101)));
```

The mapping contains identity evidence only. Kongono never invokes a codec, serializes a PHP object, infers a schema, discovers an adapter, or reads a clock. A missing profile, mismatched codec, stale artifact, mismatched adapter identity, or insufficient adapter limit fails before any adapter operation.

Fresh hit, miss, expiry, rejection, conflict, unavailable, and uncertain mutation truth remain distinct accepted outcomes. Application code validates decoded bytes under its own schema and uses application truth or ordinary recomputation after miss, expiry, failure, eviction, or complete adapter loss.

## No-cache and replaceable-adapter use

Applications that do not benefit from cache install none of the cache packages. An application may also choose an empty profile set and create no runtime. A supplied adapter can be replaced without changing profile truth, codec policy, application correctness, or authority; only disposable cache evidence is lost.

The copy-once `skeletons/cache-minimal` slice demonstrates these boundaries with one application-owned codec and the bounded process-local memory adapter. It is educational composition, not a framework facade or provider promise.

See [ADR 0203](../adr/0203-supported-cache-application-workflow-and-explicit-codec-policy-mapping.md), the [Cache Platform](cache-platform.md), the optional upper [cache inspection guide](cache-inspection-and-intelligence.md), the [external acceptance guide](phase-20-external-cache-acceptance.md), and the [Phase 20 roadmap](phase-20-roadmap.md).
