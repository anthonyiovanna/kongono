# Storage Application Workflow

## Status and boundary

Phase 21F accepts the separately optional Experimental
`kongono/storage-application` workflow, three supported Console profile
commands, and the copy-once `storage-minimal` example. It builds on the
accepted neutral contract, profile build, adapter protocol, and exact-current
runtime. Phase 21G inspection is Accepted. Phase 21H adds root-owned local
acceptance with separate validation, audit and checkpoint gates.

The profile workflow has no object-operation, adapter, credential, provider,
or authorization effect. Application code must supply an exact current build,
selected profile, expected adapter identity, adapter, and business authorizer
before using the guarded runtime. An artifact or path alone grants none of
those authorities.

## Profile source and exact artifact

One source file contains a UTF-8 JSON object with schema
`kongono/storage-application-profile-source/1` and one through 256 complete
profile declarations. Each declaration contains exactly `name`,
`contract_version`, `namespace`, `capabilities`, `digest_algorithm`, and
`limits`. The limits object contains exactly `max_chunk_bytes`,
`max_object_bytes`, and `max_list_page_size`. Duplicate members, unexpected
keys, unsupported values, malformed UTF-8, a BOM, excessive depth, and
oversized source fail closed. The accepted lower profile builder defines
canonical order and artifact identity.

The application definition names an explicit application root, a contained
ordinary source file, and an existing separate output directory. `plan`
reads and validates the source and exact target without writing; it reports
whether publication is required. `build` uses the accepted contained
no-clobber publisher and validates the exact target after publication.
`check` requires that exact target to exist and be current. The workflow
does not scan for newest output, select a fallback, or repair corrupt bytes.

`StorageApplicationSummary` has schema
`kongono/storage-application-summary/1`, version 1, `profile_count`,
`current`, `publication_required`, and optional `execution_intent` (`plan`
or `apply`). Current means only exact artifact currentness. It does not mean
provider availability, authorization, or operation success. Console human
and JSON output expose only these bounded meanings.

## Supported commands

`storage:plan`, `storage:build`, and `storage:check` require
`--application-root`, `--source-file`, and `--output-directory`, all supplied
explicitly. No command prompts or chooses a default. Plan and check are
read-only. Build requires `--plan` or `--apply`; only apply can publish. Build
plan and the plan command share effect and currentness semantics. A missing,
stale, or corrupt target is never silently fixed by check. The three path
arguments are ordinary Console inputs; command-line arguments can be visible
to the host operating system and must not contain secrets.

## Guarded object operations

The factory independently revalidates the supplied current profile and
artifact, then binds one directly supplied compatible neutral adapter.
Binding may inspect the adapter descriptor but performs no object operation.
The required `StorageOperationAuthorizer` is implemented by the application
and receives the exact unchanged neutral request. Its strict `true` decision
allows one matching runtime delegation. False denies. A thrown collaborator
failure is reported separately with fixed nondisclosing presentation.

Read, create, replace, delete, and list are guarded independently for every
invocation. The wrapper rejects same-instance synchronous reentrancy and a
create/replace method mismatch before delegation. It has no default allow
rule, retained decision, retry, fallback, or method to retrieve its lower
runtime. A denied or failed write leaves the caller-owned source untouched.
Once delegated, the accepted runtime owns write-source retirement. A
completed read returns the lower validating source; the caller must exhaust
or explicitly close it. An outstanding read can cause a later authorized
dispatch to be rejected by the lower runtime. No mid-stream revocation is
claimed. A direct caller of `ExactCurrentStorageRuntime` is outside this
application authorization wrapper.

Conflict, absence, rejection, unavailability, and uncertain mutation remain
distinct. An uncertain outcome is not success or permission to retry. Lower
primary and cleanup failures retain their separate typed truth.

## Example and disclosure

`skeletons/storage-minimal` is copied once and becomes application-owned. Its
two executables require explicit roots and profile locations; the demo also
requires a separate existing local object root. Copy or install does not run
the demo or create an object root. The narrow example authorizer allows only
the documented demo identity and operations. It is an example business rule,
not authentication or a production authorization service. The demo uses
accepted result versions for conditional changes, preserves other objects,
and never retries an uncertain mutation.

Safe diagnostics and command output omit object bytes, raw identities,
namespaces, source text, paths, digests, credentials, metadata, provider
messages, and collaborator error details. Trusted in-process callers may
inspect exception causes and traces; those are not sanitized public exports.

## Related material

- [ADR 0212](../adr/0212-supported-storage-application-workflow-and-explicit-authorization.md)
- [Storage Platform](storage-platform.md)
- [Storage authoring](../engineering/storage-authoring.md)
- [Phase 21 roadmap](phase-21-roadmap.md)
