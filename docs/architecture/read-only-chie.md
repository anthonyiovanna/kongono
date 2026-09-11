# Read-Only Kongono Chie

Phase 12H externally proves Chie's Phase 12G extension view as deterministic
Safe aggregate evidence. It does not let Chie load, execute, grant, authorize,
repair, or certify an extension. See
[Phase 12 external acceptance](phase-12-external-acceptance.md).

## Purpose and current status

Phase 4D implements the first Experimental Chie runtime in `kongono/chie`;
Phase 4G adds one explicit candidate-proposal operation without application
authority.
Its promise is **Chie helps you build it.** This slice answers only from one
explicit, current, deterministic Phase 4C artifact. It remains completely
offline and useful without a model, provider, account, subscription, or
network.

Use Chie when a developer needs one exact cited section for a supported Kongono
topic or wants to locate one supplied Safe Kongono diagnostic identifier in the
version-matched framework sources. Do not use it for free-form conversation,
project-file analysis, error payloads, recommendations, patches, verification,
deployment, production access, or operations.

## Data flow and authority

```text
explicit framework root + explicit content-addressed artifact
                         |
       structural validation, then exact current validation
                         |
          closed topic or Safe diagnostic observation
                         |
       exact cited claims + visible bounded response state
```

The artifact remains derived evidence. Governing sources remain authoritative.
Chie's response is also derived material and cannot replace a source, accepted
ADR, implementation proof, quality gate, or Hantei verdict. Retrieved text is
untrusted inert data; prompt, command, or tool language inside an excerpt is
never executed or obeyed.

## Requests

`ChieRequest::explain()` accepts one lowercase ASCII token of at most 64 bytes.
The closed catalog has six entries:

| Token | Exact source and section |
|---|---|
| `developer-intelligence` | `docs/architecture/developer-intelligence.md`, `## Roles and promises` |
| `error-handling` | `docs/engineering/error-handling.md`, `## Purpose and current status` |
| `knowledge-artifact` | `docs/architecture/framework-knowledge-artifact.md`, `## Purpose and status` |
| `knowledge-foundation` | `docs/architecture/knowledge-foundation.md`, `## Purpose and status` |
| `phase-4-roadmap` | `docs/architecture/phase-4-roadmap.md`, `## Fixed A-H roadmap` |
| `php-support` | `docs/engineering/php-support-policy.md`, `## Accepted rolling one-minor policy` |

An admitted unknown token returns `unsupported`; it is never corrected or
matched approximately.

`ChieRequest::diagnose()` accepts one `DiagnosticObservation` containing only
an admitted `KGN-...-NNNN` identifier. The observation means that the caller
supplied this Safe token for read-only diagnosis. It does not prove occurrence
and grants no authority to read messages, traces, context, logs, environment,
source code, or arbitrary workspace files.

## Explanation and diagnosis

Explanation resolves the exact catalog source, requires governing authority or
current explanatory documentation, finds the configured heading exactly once,
and returns the complete section up to the next unfenced level-two heading or
source end. It preserves every byte and cites the exact source reference,
relative path, authority, SHA-256, and inclusive line range.

Diagnosis scans artifact sources in their canonical ordinal order and lines in
one-based order. Matching is literal, case-sensitive, and token-bounded. It
returns at most eight exact source lines. The first claim is the fixed
caller-observation statement; every located line is a cited accepted framework
fact. No match returns `diagnostic-not-found` without guessing cause,
remediation, package, or nearest identifier.

## Response schema and states

Canonical response keys are `schema`, `version`, `role`, `status`, `operation`,
`framework_artifact_sha256`, `source_set_sha256`, `claims`, and
`uncertainties`, in that order. Claim keys are `class`, `text`, and `citations`.
Citation keys are `reference`, `path`, `authority`, `sha256`, `line_start`, and
`line_end`. JSON is compact UTF-8 with unescaped slashes and Unicode and exactly
one final LF.

| State | Meaning |
|---|---|
| `answered` | Exact current validation and bounded resolution succeeded. |
| `unsupported` | The admitted topic is outside the catalog or the identifier has no exact occurrence. |
| `unavailable` | The explicit artifact, root, or required source cannot be read safely. |
| `stale` | A structurally valid artifact does not equal the current explicit source state. |
| `blocked` | Artifact shape, identity, metadata, citation, section, or bound validation failed. |

Answered responses have claims and no uncertainty. Unsupported responses carry
current artifact identities, no claims, and one closed uncertainty. Artifact
failure responses use null identities, no claims, and exactly one of
`artifact-unavailable`, `artifact-stale`, or `artifact-invalid`.

## Command behavior

```text
kongono-chie explain <framework-root> <artifact-path> <topic>
kongono-chie diagnose <framework-root> <artifact-path> <diagnostic-id>
```

There are no flags, aliases, defaults, prompts, configuration lookup, current-
directory fallback, environment override, or interactive mode. The artifact
must be one exact ordinary local file named
`kongono-framework-knowledge-v1-<sha256>.json`; links, aliases, alternate
streams, special files, changed reads, oversized files, and digest/name
mismatches fail closed.

Answered uses exit `0`; invalid usage `64`; unsupported, stale, invalid, or
blocked `65`; unavailable `66`; and unexpected internal failure `70`.
Recognized response states write only stdout. Usage and unexpected failure
write only a fixed safe stderr object. Output never includes absolute roots,
artifact paths, native messages, rejected input, environment values, secrets,
Protected context, or stack traces.

## Security, lifecycle, concurrency, and performance

Every read is bounded and compares metadata before and after. Chie retains no
artifact, cache, pointer, history, workspace state, telemetry, training data,
or cross-project memory. Concurrent calls share no mutable semantic state.
Work is linear in the fixed artifact, section, and occurrence bounds.

Chie performs no write, network call, dependency operation, Git operation,
process launch, publication, deployment, production access, or application
request work. No retrieved instruction can enlarge that authority. Safe and
Protected classification occurs before role consumption; this package's eighteen
diagnostics expose only one Safe violation token.

## API status and related phases

The package exception marker is Public. The role service, request/response
values, topic/state/claim enums, observation, citations, and seven concrete
exceptions are Experimental. Command, diagnostics, exception base, catalog,
readers, extractors, locators, proposal/diff builders, and canonical encoding
are Internal. The proposal value objects are Experimental. Nothing is Stable.

## Phase 4G candidate proposal

The `propose` operation uses one explicit canonical spec and one exact current
artifact source classified as current explanatory documentation. It returns an
unapplied reversible diff with exact provenance and identities, fixed warnings,
and a pending human/fresh-Hantei handoff. Safe candidate text is a caller
assertion, not sanitization. Chie cannot apply or verify the candidate and has
no arbitrary code, write, Git, Hantei, model, provider, network, publication, or
deployment authority. See [reviewable Chie proposals](reviewable-chie-patch-proposals.md).

Phase 4A and 4B remain accepted architecture; Phase 4C knowledge and Phase 4D
read-only Chie and Phase 4G review candidates are implemented. Phase 4E Hantei
and Phase 4F Oshie are also implemented independently. Phase 4H external
acceptance remains planned. Natural-
language model integration, arbitrary workspace access, auto-fix, operations,
and production access require later explicit architecture and are not implied.

Related material: [ADR 0063](../adr/0063-read-only-chie-explanation-and-diagnosis.md),
[developer intelligence](developer-intelligence.md), [knowledge artifact](framework-knowledge-artifact.md),
[knowledge foundation](knowledge-foundation.md), [Phase 4 roadmap](phase-4-roadmap.md),
and [error handling](../engineering/error-handling.md).

## External acceptance

Phase 4H proves the real Chie CLI explanation, Safe diagnostic observation, and
exact-line proposal against one current artifact. It resolves citations to
accepted bytes, applies and reverses the candidate only in detached temporary
bytes, and proves the repository target remains unchanged. Offline output is
byte-identical; stale, invalid, missing, and malicious inputs remain visible or
inert without disclosure. The candidate remains unapplied and unverified, and
Chie gains no Hantei, write, Git, network, deployment, or production authority.
Phase 5 was not begun.

Phase 12G may address a detached Safe extension aggregate view to Chie. This
does not enlarge Chie's tools or authority and provides no currentness, raw
declaration, generated code, host state, execution, trust, or mutation access.

Phase 17A permits Chie to explain the accepted [OpenAI Responses adapter
architecture](openai-responses-provider-adapter.md) from accepted sources only.
Phase 17B adds an accepted offline mapping source and [ADR 0172](../adr/0172-deterministic-openai-responses-offline-protocol-mapping.md),
but no Chie connection or generated knowledge.
It does not connect Chie to a live model or let Chie discover credentials,
authorize network access, choose a provider/model, send a request, execute a
tool, elevate provider output, or repair adapter state. Any future Chie
application integration requires separate architecture after a concrete
adapter is accepted. Phase 17C-H remain Planned.
