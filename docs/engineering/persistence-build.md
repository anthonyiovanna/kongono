# Persistence Build

Phase 7C provides the Experimental `kongono/persistence-build` build package.
It validates application-owned persistence intent and produces a deterministic,
provenance-bearing JSON artifact. It does not provide a usable ORM runtime.

## Source

An application owns exactly one `kongono.persistence.json`. It references the
existing mapping source instead of copying physical storage facts:

```json
{
  "schema": "kongono.persistence",
  "version": 1,
  "mapping_source": "config/kongono.data-mapping.json",
  "generated_namespace": "App\\Generated\\Persistence",
  "entities": [
    {
      "entity": "App\\Domain\\Account",
      "storage_record": "App\\Data\\AccountRecord",
      "construction": {"kind": "constructor", "method": null},
      "identity": "id",
      "concurrency": "version",
      "fields": [
        {"field": "id", "record_field": "id", "factory_parameter": "id", "accessor": "id", "role": "identity"},
        {"field": "version", "record_field": "version", "factory_parameter": "version", "accessor": "version", "role": "concurrency"}
      ]
    }
  ],
  "aggregates": [
    {"name": "account", "root": "App\\Domain\\Account", "members": ["App\\Domain\\Account"]}
  ],
  "relationships": []
}
```

The mapping source remains authoritative for record fields, tables, columns,
database scalar kinds, and nullability. Persistence fields bind those record
fields to domain construction and accessors. Constructor and public static
factory construction are supported; untyped, ambiguous, variadic, by-reference,
non-public, inherited-magic, or incompatible contracts fail closed.

## Build, validate, publish

The caller supplies every path explicitly:

```php
$artifact = (new PersistenceArtifactBuilder())->build(
    $applicationRoot,
    'config/kongono.persistence.json',
);

(new PersistenceArtifactValidator())->validateStructure($artifact->canonicalBytes());
(new PersistenceArtifactValidator())->validateCurrent(
    $applicationRoot,
    'config/kongono.persistence.json',
    $artifact->canonicalBytes(),
);

$publishedPath = (new PersistenceArtifactPublisher())->publish(
    $artifact,
    $existingApplicationArtifactRoot,
);
```

`validateStructure()` proves canonical schema and internal provenance
consistency. `validateCurrent()` additionally rebuilds current mapping and
persistence evidence and requires byte equality. Use the second proof whenever
the artifact is intended to represent the current application state.

The publication root must already exist. Publication creates only the fixed
content-addressed child structure, writes through a same-directory temporary
file, accepts an identical existing artifact, and refuses conflicting bytes.

## Determinism and provenance

Repeated builds from identical bytes produce identical artifact bytes and
SHA-256. Artifact JSON is UTF-8 without BOM, LF-only, compact, and ends with one
LF. Model ordering is ordinal and independent of JSON object-key order.

Source byte identity is intentionally exact. Changing object-key order or
CRLF/LF changes source provenance and therefore the artifact, even when the
normalized model is semantically equal. Provenance also binds Composer files,
mapping source/artifact/model, both builder implementations, domain source
files, generated namespace, and canonical persistence-model bytes.

## Failure interpretation

Diagnostics `KGN-PERSISTENCE-BUILD-1001` through `1010` distinguish source,
mapping, construction, field, aggregate, relationship, artifact, current-state,
publication, and changed-during-read failures. Correct the named contract and
rebuild; do not suppress a failure or reuse mixed evidence.

Diagnostic messages and Safe context are fixed. They do not contain raw JSON,
source excerpts, private paths, entity values, SQL, credentials, connection
data, or arbitrary throwable messages.

## Security boundary

Sources are untrusted inert JSON. Duplicate keys, unknown keys, invalid UTF-8,
oversized collections, traversal, aliases, absolute paths, alternate streams,
links, junctions, reparse points, and changed inputs are rejected. Reflection is
build-time validation only and is absent from the detached model.

Phase 7F adds a second strict source,
`kongono.persistence-repositories.json`, without changing persistence source
version 1. `AggregateRepositorySourceCompiler` validates exact-current
persistence evidence, resolves only accepted aggregate/relationship names, and
derives bounded repository definitions. Generated repository validation remains
non-executing and exact-current; publication remains atomic, content-addressed,
idempotent for identical bytes, initial-only, and no-clobber.

The build boundary still performs no database connection, SQL execution,
transaction, migration, runtime loading, identity map, automatic flush, lazy
loading, recursive cascade, or Composition integration. See the
[aggregate repository guide](aggregate-repositories.md).
