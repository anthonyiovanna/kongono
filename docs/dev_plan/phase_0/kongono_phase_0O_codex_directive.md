# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0O

## Bounded Diagnostic Context and Disclosure Doctrine

## Mission

Define the exact architecture Kongono will later use to attach precise, bounded, disclosure-aware facts to deliberate framework diagnostics.

Phase 0M provides package-owned diagnostic identity, title, and remediation. It intentionally leaves raw exception messages protected and provides no structured context. Phase 0O closes the next design gap before any error page or renderer is implemented:

1. distinguish catalog-controlled facts from throw-site values;
2. define `Safe` and `Protected` disclosure classes;
3. prohibit secret-bearing or unbounded capture;
4. require package-owned schemas for every diagnostic identifier;
5. define deterministic ordering, bounds, immutability, and persistent-runtime behavior;
6. reserve a presentation-neutral shared diagnostics package without creating it;
7. preserve the three separate package exception catch boundaries; and
8. record the exact future context fields allowed for all 42 current diagnostics.

Phase 0O is documentation and architecture only. It creates no PHP type, package, dependency, context object, exception accessor, formatter, logger, renderer, development error page, HTTP/CLI mapping, telemetry hook, retry policy, cleanup runner, or incident system.

The developer-friendly error experience remains a required later capability. This phase makes that capability safe and precise enough to implement without guessing.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the Phase 0O executor, not the architect.

Read completely before changing anything:

1. `AGENTS.md`;
2. `README.md`, `ARCHITECTURE.md`, `SECURITY.md`, `CONTRIBUTING.md`;
3. `docs/doctrine.md`, `docs/documentation-standard.md`, `docs/glossary.md`;
4. `docs/architecture/overview.md` and `docs/architecture/runtime-model.md`;
5. `docs/engineering/error-handling.md` and `docs/engineering/api-status.md`;
6. ADRs 0001 through 0027;
7. the current Kernel, Application Graph, and Dependency Injection package READMEs;
8. all three package exception markers, Internal bases, diagnostic catalogs, deliberate throw sites, and production tests;
9. `docs/task reports/Phase_0N_4_correction.md`;
10. this directive.

Historical directives and task reports are evidence, not active instructions, except where this directive explicitly cites an immutable baseline fact.

## Mandatory stop rule

```text
STOP
ASK
DO NOT GUESS
DO NOT IMPLEMENT THE FUTURE API
```

Stop before changing files if:

- the repository baseline differs from Section 3;
- any caller record is missing, misnamed, unexpectedly populated, staged, tracked, or has the wrong hash;
- a required context field cannot fit the model in Sections 6 through 9;
- the accepted schema would require capturing a credential, token, cookie, authorization value, private key, full request body, environment dump, arbitrary provider payload, or other forbidden material;
- a new diagnostic identifier, package domain, exception family, or catch boundary appears necessary;
- a runtime API, constructor, sanitizer, renderer, transport mapping, or localization mechanism would need to be selected;
- any file outside Section 5 needs to change;
- a quality, link, encoding, catalog, or security check fails; or
- completing the documentation would require an assumption not explicitly resolved here.

Report the exact decision, viable options, tradeoffs, and a recommendation. Do not implement the recommendation.

---

# 2. EXACT PHASE BOUNDARY

Phase 0O authorizes only:

1. one Accepted ADR defining bounded diagnostic context and disclosure;
2. documentation of one future shared, presentation-neutral diagnostics package;
3. documentation of future conceptual types named `DiagnosticContext`, `DiagnosticField`, and `DiagnosticDisclosure`;
4. the exact field-key, value-kind, disclosure, ordering, and bounding doctrine in this directive;
5. exact package-owned future context schemas for the current 42 diagnostic identifiers;
6. documentation updates that distinguish accepted future architecture from current Phase 0M runtime behavior; and
7. validation of documentation consistency and the unchanged implementation.

Phase 0O does not authorize runtime implementation. In particular, do not add `diagnosticContext()` to any exception marker, do not alter a catalog or throw site, and do not add a Composer package.

The intended sequencing is:

```text
Phase 0M: ID + title + remediation runtime metadata (implemented)
Phase 0O: bounded context + disclosure architecture (documentation only)
Later phase: exact PHP value types and throw-site capture
Later phase: trusted development presentation
Later phase: transport-specific production mapping
```

Do not collapse those phases.

---

# 3. ACCEPTED BASELINE AND CALLER RECORDS

Before the caller adds Phase 0O records, require exactly:

```text
Branch: main
Only local branch: main
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
HEAD parent count: 1
Commit count: 5
HEAD subject: Establish Kongono framework baseline through Phase 0M
Tracked files: 214
Tracked modes: 214 x 100644
Tracked modifications: 0
Staged paths: 0
Untracked paths: 1
Remotes: 0
Tags: 0
Repository-wide non-default alternate streams: 0
```

The sole pre-Phase-0O untracked path is:

```text
docs/task reports/Phase_0N_4_correction.md
  17,247 bytes
  SHA-256 8AFD9BC46D29B0DB0570B87C8B947C65AE82FD9C967DCCBDEDB68C3CEC62661C
```

It is an immutable accepted completion record. Do not edit, stage, commit, rename, delete, normalize, or replace it.

The caller will add exactly:

```text
docs/dev_plan/kongono_phase_0O_codex_directive.md
docs/task reports/Phase_0O.md
```

The directive must be a non-empty byte-identical copy of this artifact. Record its size and SHA-256; never edit it.

The report must begin as an ordinary zero-byte, untracked, unstaged placeholder. Populate it only after all Phase 0O validation passes. It remains outside `HEAD` and outside the index.

After caller setup and before executor changes, require:

```text
Tracked modifications: 0
Staged paths: 0
Untracked paths: 3
Total status paths: 3
```

The exact untracked paths are the accepted Phase 0N.4 report, this directive, and the zero-byte Phase 0O report.

Preserve the six committed Phase 0N-family terminal-blank-line exceptions byte-for-byte. They are historical exceptions only and do not permit a new Phase 0O whitespace defect.

Do not stage, commit, branch, tag, push, reset, amend, rebase, merge, or change Git configuration in Phase 0O.

---

# 4. LOCKED ARCHITECTURAL DECISIONS

Document all of these as Accepted future architecture, while stating clearly that none is implemented.

## 4.1 Package ownership

A future package named `kongono/diagnostics` will own presentation-neutral diagnostic context value types.

It will not own Kernel, Graph, or DI diagnostic meanings. Each originating package continues to own:

- its diagnostic identifiers;
- title and remediation prose;
- allowed context schema per identifier;
- values supplied at each throw site; and
- its separate Public exception marker.

The shared package must not define a universal Kongono exception interface or catch boundary. Kernel, Graph, and DI markers remain pairwise unrelated. A future renderer may adapt each known package marker to shared value types without changing recovery ownership.

No dependency or package is added in Phase 0O.

## 4.2 Conceptual value types

Reserve these future Public concepts:

```text
Kongono\Diagnostics\Context\DiagnosticContext
Kongono\Diagnostics\Context\DiagnosticField
Kongono\Diagnostics\Context\DiagnosticDisclosure
```

`DiagnosticDisclosure` has exactly two semantic cases:

- `Safe`: catalog-approved information eligible for intentional disclosure after a future presentation or transport policy allows it.
- `Protected`: diagnostic information available only to a trusted development or protected operational boundary.

There is no `Secret` case. Forbidden material must not be captured at all.

`DiagnosticContext` is an immutable, ordered, bounded snapshot created eagerly when the deliberate exception is constructed. It contains:

1. one catalog-controlled operation description;
2. one catalog-controlled violated invariant or expectation;
3. zero or more ordered `DiagnosticField` values approved by the owning diagnostic schema.

`DiagnosticField` conceptually contains:

1. canonical key;
2. catalog-controlled human label;
3. bounded scalar value;
4. disclosure classification;
5. whether a text representation was truncated.

Exact PHP constructors, factories, method signatures, interfaces, iteration behavior, and sanitizer implementation remain for the later implementation directive. Phase 0O documentation must not invent executable examples that imply those details are already selected.

## 4.3 Future exception access

A later implementation may add a `diagnosticContext()`-style accessor to each existing package marker, returning the shared context value. That accessor name and exact PHP signature remain unselected until the implementation directive.

The current three string accessors remain the only implemented Public metadata API:

```php
diagnosticId(): string
diagnosticTitle(): string
diagnosticRemediation(): string
```

Do not document the future accessor as callable today.

---

# 5. EXACT FILE-CHANGE BOUNDARY

## Create exactly one content file

```text
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
```

## Modify exactly these 13 tracked content files

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/error-handling.md
docs/glossary.md
packages/application-graph/README.md
packages/dependency-injection/README.md
packages/kernel/README.md
```

## Preserve every other path

Do not modify:

- `AGENTS.md`;
- any Composer manifest or lockfile;
- PHPStan, PHPUnit, PHP CS Fixer, EditorConfig, Git, or security-tool configuration;
- any PHP source, test, fixture, attribute, enum, exception, catalog, or throw site;
- any prior ADR, except `docs/adr/README.md`;
- any prior directive or task report;
- `docs/engineering/api-status.md` or `docs/engineering/testing.md`;
- any package boundary or autoload rule.

After implementation and before report population, the exact content delta is:

```text
13 modified tracked files
1 untracked ADR
1 unchanged untracked Phase 0N.4 report
1 unchanged untracked Phase 0O directive
1 zero-byte untracked Phase 0O report
0 staged paths
17 total status paths
```

After report population, the path count remains 17 and only the Phase 0O report size changes from zero to nonzero.

---

# 6. BOUNDED CONTEXT CONTRACT

Document the following exact rules.

## 6.1 Catalog-controlled schema

Context is not an arbitrary map or bag.

For every diagnostic identifier, the owning Internal catalog will eventually define:

- fixed operation prose;
- fixed invariant/expectation prose;
- exact allowed field keys;
- fixed label for each key;
- allowed scalar kind for each key;
- disclosure for each key;
- canonical field order; and
- whether the field is required or optional.

A throw site may supply values only for that identifier's schema. It may not invent a key, label, disclosure, or ordering decision.

A field missing because the fact is unavailable is omitted only when its schema says optional. No `null` placeholder is used.

## 6.2 Canonical keys

Future field keys must:

- be lowercase ASCII snake case;
- match `^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$`;
- contain at most 64 bytes;
- be unique within one diagnostic context;
- retain one semantic meaning wherever reused; and
- never encode a value, index, locale, version, or environment in the key.

Labels are fixed English catalog prose in the first implementation. Labels are not stable machine identity and may later be localized under a separate decision.

## 6.3 Allowed value kinds

Only these semantic scalar kinds are allowed:

```text
text
integer
boolean
```

The future stored PHP representation may use `string|int|bool` after the implementation directive selects exact APIs.

Prohibit:

- float;
- null;
- arrays, maps, or lists as values;
- objects, enums, resources, callables, closures, or `Throwable` values;
- lazy suppliers;
- serialized values;
- raw JSON or arbitrary stringified objects; and
- nested context.

Multiple facts require multiple schema-approved fields. Do not smuggle a list into delimited text.

## 6.4 Bounds

The future runtime contract must enforce:

- at most 16 fields per context;
- at most 64 bytes per key;
- at most 96 UTF-8 bytes per fixed label;
- at most 512 bytes in the bounded representation of a text value;
- exactly one truncation flag per text field;
- no truncation flag for integer or boolean values; and
- deterministic truncation with an explicit visible marker.

The exact UTF-8/binary escaping and truncation algorithm remains open for the implementation directive. Documentation must not claim an algorithm exists.

Integer values must fit PHP's native integer range on the supported 64-bit platform. Counts must be non-negative when their schema describes a count.

## 6.5 Ordering and immutability

Field order is schema order, never caller insertion order, hash order, reflection order, locale order, or alphabetical order unless the schema explicitly chooses it.

The future context and fields are immutable value snapshots. Input arrays must be detached. No reference, object identity, closure, mutable collection, ambient service, or later state lookup may affect an already-created context.

Repeated access to one exception must produce semantically identical operation, invariant, fields, order, values, disclosures, and truncation flags.

---

# 7. DISCLOSURE AND CAPTURE RULES

## 7.1 Safe

`Safe` means the owning catalog has determined that the field's semantic category can be eligible for disclosure.

Examples include:

- Kernel lifecycle state names;
- non-negative counts;
- zero-based positions;
- a fixed rejection-reason token;
- a broad PHP value-kind token such as `string` or `object`; and
- fixed expected values.

`Safe` does not mean automatically public. No current transport or renderer exists. A future production surface still needs an explicit allowlist and policy.

## 7.2 Protected

`Protected` includes values useful for diagnosis that may reveal application structure or caller-controlled data:

- Graph keys and open kind labels;
- service interface names;
- implementation class names;
- dependency-cycle contracts;
- arbitrary array keys;
- provider or adapter identifiers; and
- source or configuration locations.

Protected values may be eligible for a trusted developer surface or protected record. They are never emitted to an untrusted production response merely because they exist.

## 7.3 Forbidden capture

Never capture these as diagnostic context, even as `Protected`:

- passwords, password hashes, passphrases, PINs;
- API keys, access tokens, refresh tokens, bearer tokens;
- cookies, session identifiers, CSRF values;
- authorization headers or credentials;
- private keys, signing keys, encryption keys, recovery codes;
- database connection strings containing credentials;
- complete environment or configuration dumps;
- complete request/response bodies;
- arbitrary headers;
- raw SQL with bound values;
- full provider payloads;
- file contents or source excerpts;
- serialized objects;
- memory dumps; or
- any value whose secrecy is uncertain and whose capture is not required by an accepted schema.

A future boundary may record that forbidden data was omitted. It may not record the data itself.

## 7.4 Classification discipline

Disclosure is fixed by the schema, not chosen by the throw-site caller, renderer, request, environment variable, or AI system.

Opaque consumer-provided text defaults to `Protected`. It cannot be promoted to `Safe` because it resembles a class name, path, key, route, or identifier.

An AI tool may explain or suggest remediation. It may never classify a field, override disclosure, recover forbidden data, or become necessary to interpret a known diagnostic.

---

# 8. PERSISTENT-RUNTIME AND FAILURE CONTRACT

The future context mechanism must be safe for persistent workers:

- capture occurs synchronously and eagerly at the deliberate failure site;
- capture reads only explicit arguments and already-owned immutable framework state;
- no global request, superglobal, container, current-user, thread-local, fiber-local, environment, clock, random source, filesystem, network, database, logger, tracer, or AI call is consulted;
- no context survives independently after its owning exception is released;
- no static registry accumulates per-failure values;
- schema metadata may later be immutable process-wide data, but per-failure values may not be cached globally;
- context creation performs no I/O;
- context creation must have bounded memory and work proportional to its maximum 16 fields;
- context-capture failure must never replace the primary deliberate failure silently; and
- the exact representation of simultaneous primary and capture failure remains an implementation decision requiring explicit authorization.

Phase 0O does not select serialization. PHP exception serialization, process transfer, queue transport, worker restart behavior, and cross-language representation remain open.

---

# 9. EXACT CURRENT DIAGNOSTIC SCHEMAS

Document this table as the accepted schema target. `-` means no dynamic field is required; fixed operation and invariant still exist.

Field syntax below is:

```text
key : value kind : disclosure : requirement
```

All listed fields are required unless marked optional.

| Diagnostic | Fixed operation | Fixed violated invariant or expectation | Ordered future fields |
| --- | --- | --- | --- |
| `KGN-KERNEL-1001` | Boot Kernel | Boot requires `Unbooted` | `actual_state:text:Safe`; `expected_state:text:Safe` |
| `KGN-KERNEL-1002` | Shut down Kernel | Shutdown requires `Booted` | `actual_state:text:Safe`; `expected_state:text:Safe` |
| `KGN-KERNEL-1003` | Shut down Kernel | Shutdown requires zero active operations | `active_operation_count:integer:Safe`; `expected_active_operation_count:integer:Safe` |
| `KGN-KERNEL-1004` | Begin operation | Beginning work requires `Booted` | `actual_state:text:Safe`; `expected_state:text:Safe` |
| `KGN-KERNEL-1005` | End operation | Ending a scope requires `Booted` | `actual_state:text:Safe`; `expected_state:text:Safe` |
| `KGN-KERNEL-1006` | End operation | Scope must be active and owned by this Kernel | `scope_condition:text:Safe` |
| `KGN-GRAPH-1001` | Create node key | Node-key value must be non-empty | `actual_length:integer:Safe` |
| `KGN-GRAPH-1002` | Create node kind | Node-kind value must be non-empty | `actual_length:integer:Safe` |
| `KGN-GRAPH-1003` | Create relation kind | Relation-kind value must be non-empty | `actual_length:integer:Safe` |
| `KGN-GRAPH-1004` | Construct Application Graph | Nodes input must be a PHP list | `entry_count:integer:Safe`; `first_unexpected_key:text:Protected:optional` |
| `KGN-GRAPH-1005` | Construct Application Graph | Relations input must be a PHP list | `entry_count:integer:Safe`; `first_unexpected_key:text:Protected:optional` |
| `KGN-GRAPH-1006` | Validate node entry | Every nodes-list entry must be a `Node` | `entry_position:integer:Safe`; `actual_value_kind:text:Safe` |
| `KGN-GRAPH-1007` | Validate relation entry | Every relations-list entry must be a `Relation` | `entry_position:integer:Safe`; `actual_value_kind:text:Safe` |
| `KGN-GRAPH-1008` | Validate node uniqueness | Node keys must be unique within a snapshot | `node_key:text:Protected` |
| `KGN-GRAPH-1009` | Validate relation source | Relation source must reference an existing node | `relation_position:integer:Safe`; `source_key:text:Protected` |
| `KGN-GRAPH-1010` | Validate relation target | Relation target must reference an existing node | `relation_position:integer:Safe`; `target_key:text:Protected` |
| `KGN-GRAPH-1011` | Validate relation uniqueness | Source/kind/target triples must be unique | `source_key:text:Protected`; `relation_kind:text:Protected`; `target_key:text:Protected` |
| `KGN-DI-1001` | Validate service contract | Contract must name an existing interface | `subject_role:text:Safe`; `contract_name:text:Protected` |
| `KGN-DI-1002` | Validate service contract | Contract must use its canonical declared name | `subject_role:text:Safe`; `contract_name:text:Protected` |
| `KGN-DI-1003` | Validate service contract | Resolver contract cannot enter the service graph | `subject_role:text:Safe`; `contract_name:text:Protected` |
| `KGN-DI-1004` | Validate implementation | Implementation must name an existing class | `implementation_name:text:Protected` |
| `KGN-DI-1005` | Validate implementation | Implementation must use its canonical declared name | `implementation_name:text:Protected` |
| `KGN-DI-1006` | Validate implementation | Implementation must be publicly instantiable | `implementation_name:text:Protected` |
| `KGN-DI-1007` | Validate implementation | Implementation must satisfy the declared contract | `contract_name:text:Protected`; `implementation_name:text:Protected` |
| `KGN-DI-1008` | Validate implementation | Implementation must not expose the resolver contract | `implementation_name:text:Protected` |
| `KGN-DI-1009` | Validate dependency list | Dependencies must be a PHP list | `entry_count:integer:Safe`; `first_unexpected_key:text:Protected:optional` |
| `KGN-DI-1010` | Validate dependency entry | Dependency entry must be a contract-name string | `dependency_position:integer:Safe`; `actual_value_kind:text:Safe` |
| `KGN-DI-1011` | Validate constructor dependencies | Dependency declarations require a constructor | `implementation_name:text:Protected`; `dependency_count:integer:Safe` |
| `KGN-DI-1012` | Validate constructor | Constructor must be public | `implementation_name:text:Protected` |
| `KGN-DI-1013` | Validate constructor arity | Constructor and definition arity must match exactly | `implementation_name:text:Protected`; `expected_parameter_count:integer:Safe`; `actual_parameter_count:integer:Safe` |
| `KGN-DI-1014` | Validate constructor parameter mode | Parameter must be required, by-value, non-variadic, and non-defaulted | `implementation_name:text:Protected`; `parameter_position:integer:Safe`; `unsupported_mode:text:Safe` |
| `KGN-DI-1015` | Validate constructor parameter type | Parameter must have one non-nullable interface type | `implementation_name:text:Protected`; `parameter_position:integer:Safe`; `actual_type_shape:text:Safe` |
| `KGN-DI-1016` | Validate constructor dependency type | Parameter type must equal the ordered dependency contract | `implementation_name:text:Protected`; `parameter_position:integer:Safe`; `expected_contract:text:Protected`; `actual_contract:text:Protected` |
| `KGN-DI-1017` | Validate definition list | Definitions must be a PHP list | `entry_count:integer:Safe`; `first_unexpected_key:text:Protected:optional` |
| `KGN-DI-1018` | Validate definition entry | Every definitions-list entry must be a `ServiceDefinition` | `definition_position:integer:Safe`; `actual_value_kind:text:Safe` |
| `KGN-DI-1019` | Validate definition uniqueness | Each service contract may have one definition | `contract_name:text:Protected` |
| `KGN-DI-1020` | Validate definition graph completeness | Every dependency contract must have a definition | `owner_contract:text:Protected`; `missing_dependency:text:Protected` |
| `KGN-DI-1021` | Validate definition graph acyclicity | Definition graph must be acyclic | `cycle_contract:text:Protected` |
| `KGN-DI-1022` | Resolve service | Requested service must name an existing interface | `requested_contract:text:Protected` |
| `KGN-DI-1023` | Resolve service | Requested contract must be canonical and not the resolver contract | `requested_contract:text:Protected`; `rejection_reason:text:Safe` |
| `KGN-DI-1024` | Resolve service | Requested service must have a definition | `requested_contract:text:Protected` |
| `KGN-DI-1025` | Construct service | Constructed object must satisfy the validated contract | `contract_name:text:Protected`; `implementation_name:text:Protected` |

## 9.1 Controlled Safe tokens

Future `Safe` text fields are closed tokens, not arbitrary strings:

- `actual_state` and `expected_state`: exact Kernel state names;
- `scope_condition`: one of `inactive`, `already_ended`, `foreign_owner`, or `inactive_or_foreign` until a later semantic split is authorized;
- `subject_role`: `service_contract` or `dependency_contract`;
- `actual_value_kind`: a broad non-identifying PHP kind such as `string`, `integer`, `array`, `object`, or `resource`, never an object class name;
- `unsupported_mode`: a deterministic combination selected from `optional`, `defaulted`, `variadic`, and `by_reference`;
- `actual_type_shape`: a closed reason token such as `missing`, `builtin`, `union`, `intersection`, `nullable`, or `non_interface`;
- `rejection_reason`: `noncanonical` or `resolver_contract`.

Exact token combination and ordering algorithms remain for the implementation directive. Do not add tokens during Phase 0O.

## 9.2 Schema constraints

- Every current identifier appears exactly once in the table.
- No identifier is added, removed, renamed, or repurposed.
- Context fields do not change diagnostic identity.
- The two DI invariant-defense throw sites continue to share `KGN-DI-1025` and one schema.
- Optional `first_unexpected_key` is absent when no single key can be identified safely and deterministically.
- Protected names retain exact diagnostic value before future bounded representation; they are not claimed safe for production.
- The table authorizes future capture only. Current exceptions expose none of these fields.

---

# 10. FUTURE PRESENTATION CONTRACT

Document these consequences without implementing them.

A future trusted developer presentation may combine:

1. diagnostic ID;
2. title;
3. operation;
4. violated invariant;
5. Safe fields;
6. Protected fields only when trusted developer disclosure is enabled by server-side configuration;
7. remediation;
8. protected native message and cause chain under a separate disclosure decision;
9. bounded source/location information under a future source-provider contract; and
10. version-matched documentation under a future lookup contract.

A future production surface must not infer disclosure from developer context. It must use an explicit transport policy. `Safe` fields are eligible, not automatically emitted. `Protected` fields, native messages, traces, paths, source, headers, bodies, and environment values remain excluded by default.

Trusted developer mode must never be activated by a query parameter, request header, cookie, route value, user input, or AI response.

Phase 0O does not select HTML, CSS, templates, JSON, Markdown, HTTP status, RFC 9457 problem details, CLI formatting, exit codes, localization, source excerpts, stack-frame grouping, copy buttons, or incident references.

---

# 11. REQUIRED ADR 0028

Create `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md` with:

```text
Title: Bounded Diagnostic Context and Disclosure
Status: Accepted
Date: 2026-08-13
```

It must record:

1. current Phase 0M behavior;
2. the need for precise context before presentation;
3. future `kongono/diagnostics` ownership;
4. continued package-local semantic ownership and separate catch markers;
5. `DiagnosticContext`, `DiagnosticField`, and `DiagnosticDisclosure` concepts;
6. exactly two disclosure classes and forbidden-capture behavior;
7. schema-controlled rather than arbitrary fields;
8. allowed value kinds and exact bounds;
9. ordering and immutability;
10. persistent-runtime and no-ambient-context constraints;
11. the exact 42-schema table or an explicit normative reference to its complete copy in `docs/engineering/error-handling.md`;
12. production non-disclosure and trusted developer-mode principles;
13. rejection of arbitrary arrays, universal exception interfaces, raw message parsing, automatic production disclosure, and premature renderer implementation;
14. consequences and compatibility impact;
15. security considerations;
16. performance implications;
17. alternatives considered;
18. explicit current-versus-future status; and
19. remaining open decisions.

Remaining open decisions must include:

- exact PHP constructors, factories, and accessors;
- exact text encoding, escaping, and truncation algorithm;
- context-capture failure representation;
- implementation-phase package manifests and dependency direction;
- version-matched documentation lookup;
- renderer package and presentation APIs;
- source excerpt and stack-frame providers;
- HTTP and CLI mappings;
- production allowlists;
- localization;
- incident storage/reference;
- logging and telemetry;
- retry/cancellation;
- cleanup failure representation;
- serialization and cross-process/cross-language form.

Do not mark the architecture Proposed. Do not imply the PHP API exists.

---

# 12. REQUIRED DOCUMENTATION UPDATES

## README.md

State:

- Phase 0M runtime metadata remains implemented;
- Phase 0O accepts bounded context/disclosure architecture only;
- no runtime context or renderer exists;
- future context is schema-owned, bounded, and disclosure-aware;
- the error experience remains offline-capable and developer-focused by doctrine, not yet implementation.

## ARCHITECTURE.md

Add:

- future `kongono/diagnostics` presentation-neutral value ownership;
- package-local semantic ownership;
- no universal catch boundary;
- context flow from catalog schema plus explicit throw-site values to a future immutable snapshot;
- persistent-runtime and no-ambient capture constraints;
- renderer/transport remain later.

## SECURITY.md

Add:

- Safe versus Protected semantics;
- forbidden capture list;
- Safe is eligibility, not automatic disclosure;
- protected values never enter untrusted production output by default;
- trusted developer mode is server-controlled;
- no arbitrary context or secret capture.

## docs/adr/README.md

Add ADR 0028 with correct status and a concise description.

## docs/architecture/overview.md

Update the current/future boundary and show the future diagnostics package as presentation-neutral, not a catch owner.

## docs/architecture/runtime-model.md

Document eager bounded capture, immutable per-exception lifetime, no ambient state, no I/O, no global accumulation, and unresolved capture-failure representation.

## docs/doctrine.md

Lock:

- schema-owned context;
- two disclosure classes;
- forbidden capture;
- no arbitrary bags;
- separate package catch boundaries;
- AI cannot classify or disclose fields.

## docs/documentation-standard.md

Require future deliberate diagnostic documentation to state operation, invariant, field schema, value kind, disclosure, bounds, and capture prohibition. State this is accepted architecture, not implemented validation.

## docs/engineering/error-handling.md

This is the normative operational guide. It must include:

- all Sections 4 and 6 through 10 rules;
- the exact complete 42-row schema table;
- controlled Safe tokens;
- examples clearly labeled future pseudocode;
- one example showing Safe and Protected fields;
- one example showing forbidden data omitted rather than redacted into context;
- current marker methods versus future context;
- production and developer disclosure rules;
- persistent-runtime behavior;
- unchanged open decisions.

Do not remove the existing renderer wireframe. Mark it future.

## docs/glossary.md

Define:

- diagnostic context;
- diagnostic field;
- Safe disclosure;
- Protected disclosure;
- forbidden capture;
- context schema;
- trusted developer mode.

Do not claim executable types exist.

## Package READMEs

For Kernel, Application Graph, and Dependency Injection:

- state the package owns its future schema and values;
- summarize its fields from Section 9;
- identify which fields are Protected;
- preserve current exception APIs;
- state the shared value package and accessor are future;
- state no renderer/logging/transport behavior exists.

---

# 13. COMPOSER, API, AND IMPLEMENTATION IMMUTABILITY

Do not change:

- root or package `composer.json`;
- `composer.lock`;
- repository declarations;
- production or development dependencies;
- autoload or autoload-dev;
- Composer scripts or security policy;
- PHPStan, PHPUnit, or PHP CS Fixer configuration;
- API status inventory;
- any PHP declaration count;
- any exception constructor or marker method;
- any diagnostic catalog case, title, remediation, or ID;
- any throw message or state behavior;
- any test or fixture.

Expected unchanged inventories:

```text
Production PHP declarations/files: 30
Test PHP files: 62
Syntax inventory: 93
Style inventory: 92
Root tests: 134
Root assertions: 1099
Diagnostic IDs: 42
```

Phase 0O creates no license decision and accepts the existing Composer warning categories only.

---

# 14. EXPLICITLY PROHIBITED SCOPE

Do not implement or select:

- any PHP context/disclosure/value type;
- a new Composer package or dependency;
- a universal exception or diagnostic interface;
- changes to package marker inheritance;
- arbitrary arrays, maps, bags, metadata, annotations, or attributes;
- runtime sanitizer, escaper, truncator, redactor, serializer, registry, factory, or schema compiler;
- renderer, template, error page, CSS, JavaScript, JSON/Markdown output, or source reader;
- HTTP, router, request/response, middleware, problem details, status mapping;
- CLI commands, output, or exit codes;
- logging, telemetry, OpenTelemetry, tracing, metrics, exporters;
- incident IDs, storage, correlation, reporting, or remote services;
- retry, timeout, cancellation, cleanup, recovery, supervision, or worker adapter;
- DI/container expansion, factories, scopes, caching, autowiring, or service location;
- Application Graph schema, IDs, serialization, persistence, traversal, query DSL, indexing, mutation, discovery, or compilation;
- ORM, database, events, workflows, authorization, agents, extensions, frontend, Vite, localization, release packaging, CI, or deployment;
- dependencies, plugins, suppressions, baselines, exclusions, ignored findings, or weakened gates;
- Git staging, commits, branches, tags, remotes, pushes, or history changes.

---

# 15. REQUIRED EXECUTION AND VALIDATION

## 15.1 Preflight

Record:

```powershell
php -v
php --ini
composer --version
git --version
vendor/bin/phpunit --version
vendor/bin/phpstan --version
vendor/bin/php-cs-fixer --version
git branch --show-current
git for-each-ref --format="%(refname:short)" refs/heads
git log --reverse --format="%H|%T|%P|%s"
git status --short --untracked-files=all
git diff --exit-code
git diff --cached --exit-code
git ls-files
git remote
git tag
```

Verify Section 3 exactly, including the Phase 0N.4 report hash and zero alternate streams.

## 15.2 Implementation sequence

1. create ADR 0028;
2. update the normative error-handling guide with the full contract and 42 schemas;
3. update security, doctrine, architecture, runtime, and documentation standards;
4. update package READMEs;
5. update root summaries and ADR index;
6. reconcile all current-versus-future wording.

Do not touch the report until all validation passes.

## 15.3 Documentation validation

Prove:

- exactly one new ADR and exactly 13 tracked modifications;
- every other tracked file is byte-identical to `HEAD`;
- all 42 IDs appear exactly once in the normative schema table;
- Kernel/Graph/DI schema counts are exactly 6/11/25;
- every field key matches the key syntax;
- every value kind is `text`, `integer`, or `boolean`;
- every disclosure is `Safe` or `Protected`;
- optionality appears only where authorized;
- no schema exceeds 16 fields;
- no field is `Secret` or arbitrary;
- all documents agree runtime context is unimplemented;
- the three catch boundaries remain separate;
- no executable API signature is claimed beyond current methods;
- all Markdown local links resolve;
- historical absolute links are handled only under the established historical-record exception;
- every changed/new text file is strict UTF-8 without BOM, LF-only, final-newline terminated, with no extra terminal blank line, NUL, replacement character, mojibake, conflict marker, or trailing whitespace;
- the six committed Phase 0N exceptions remain unchanged and do not appear in the Phase 0O diff; and
- repository-wide alternate-stream count remains zero.

## 15.4 Implementation immutability

Prove:

- no PHP file changed;
- no manifest, lockfile, tool config, or dependency changed;
- production/test/API inventories remain exact;
- `git diff --cached --exit-code` passes;
- no new ignored-sensitive file exists;
- no secret-pattern finding exists in the Phase 0O delta;
- no prohibited term is presented as implemented.

## 15.5 Quality

Run:

```powershell
composer analyse
composer cs
composer audit:locked
composer platform
composer quality
```

Require:

- PHPStan zero errors;
- PHP CS Fixer zero fixable files across 92;
- zero advisories;
- platform pass;
- canonical 134 tests and 1099 assertions or a justified increase caused only by authorized changes—which should not occur because tests are immutable;
- warning categories limited to missing license and authorized local `@dev` or `@dev/*` constraints.

If the fail-closed audit encounters only sandbox network/cache denial, rerun the unchanged command through approved access. Do not alter policy.

## 15.6 Final Git proof

Before report population, require:

```text
HEAD unchanged: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree unchanged: 5d8afbdbb163c571efb0564c5dd31fc141533839
Commit count: 5
Staged paths: 0
Tracked modifications: 13
Untracked paths: 4
Total status paths: 17
```

The four untracked paths are:

```text
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/dev_plan/kongono_phase_0O_codex_directive.md
docs/task reports/Phase_0N_4_correction.md
docs/task reports/Phase_0O.md
```

After report population, counts remain the same.

---

# 16. STRICT ACCEPTANCE CRITERIA

Every item must pass.

## Repository safety

- [ ] Exact Phase 0N checkpoint baseline and caller records match.
- [ ] HEAD, tree, history, index, branches, remotes, tags, and prior records remain unchanged.
- [ ] Exactly one ADR is created and exactly 13 tracked files are modified.
- [ ] No unauthorized path, alternate stream, unsafe type, or secret appears.
- [ ] Phase 0O report remains unstaged and outside `HEAD`.

## Architecture

- [ ] ADR 0028 is Accepted and dated 2026-08-13.
- [ ] Future `kongono/diagnostics` ownership is presentation-neutral.
- [ ] Package semantic ownership and separate catch boundaries remain.
- [ ] Exactly two disclosure classes exist; forbidden capture is not a third class.
- [ ] Context is schema-controlled, immutable, ordered, eager, and bounded.
- [ ] Allowed kinds and all bounds are exact.
- [ ] No ambient state, I/O, global accumulation, or arbitrary bag is authorized.
- [ ] Safe means eligible, not automatically public.
- [ ] Protected and forbidden rules are exact.
- [ ] AI has no classification or disclosure authority.

## Catalog reconciliation

- [ ] Exact 42-row schema exists with 6 Kernel, 11 Graph, and 25 DI entries.
- [ ] Every current ID appears once and retains its meaning.
- [ ] Field keys, kinds, disclosures, order, and optionality are exact.
- [ ] Controlled Safe tokens are documented.
- [ ] Two DI-1025 throw sites retain one identifier and schema.
- [ ] No runtime context is claimed.

## Documentation and quality

- [ ] All 13 required documents and ADR index are reconciled.
- [ ] Package READMEs accurately summarize their schemas.
- [ ] Current versus planned behavior is explicit everywhere.
- [ ] Links and text-integrity checks pass with no new exception.
- [ ] No PHP, test, manifest, lockfile, dependency, or tool configuration changes.
- [ ] PHPStan, style, audit, platform, and canonical quality pass unchanged.
- [ ] All prohibited scope remains absent.

## Completion report

- [ ] Report follows Section 17 exactly.
- [ ] It records exact commands, counts, hashes, warnings, and results.
- [ ] Final report is nonzero, strict UTF-8, LF-only, one final LF, untracked, unstaged, and absent from `HEAD`.
- [ ] Next step says only: `Await architect review before Phase 0P.`

Any failure makes status `Blocked` or `Partial`, never `Complete`.

---

# 17. STANDARD EXECUTOR COMPLETION REPORT

Populate `docs/task reports/Phase_0O.md` only after all gates pass.

Use exactly one outer `text` fence, no content outside it, and exactly these 16 numbered sections:

```text
1. STATUS
2. SUMMARY
3. FILES CREATED
4. FILES MODIFIED
5. ARCHITECTURAL DECISIONS
6. DEVIATIONS FROM DIRECTIVE
7. DEPENDENCIES ADDED/REMOVED
8. ENVIRONMENT
9. COMMANDS RUN
10. VALIDATION RESULTS
11. DOCUMENTATION REVIEW
12. WARNINGS / KNOWN ISSUES
13. QUESTIONS / BLOCKERS
14. GIT DIFF SUMMARY
15. ACCEPTANCE CRITERIA
16. NEXT STEP
```

Mandatory evidence:

- exact baseline commit/tree/history/status and caller hashes;
- exact created/modified inventories;
- ADR title/status/date;
- exact disclosure, bounds, persistent-runtime, and forbidden-capture decisions;
- exact 42 schema rows and 6/11/25 counts;
- documentation current-versus-future reconciliation;
- link, encoding, whitespace, alternate-stream, secret, and prohibited-scope results;
- unchanged PHP, dependency, tool, API, and Git-state proof;
- exact PHPStan, style, audit, platform, PHPUnit test/assertion results;
- accepted warning categories and environmental retries;
- `None` for decisions made by the executor, deviations, dependencies, or blockers when none;
- line-by-line PASS/FAIL against Section 16;
- final report byte count and SHA-256 in the accompanying executor response after its last write, noting inside the report why it cannot embed its own final digest; and
- Section 16 exactly:

```text
Await architect review before Phase 0P.
```

After the report's final write, rerun status and report-integrity checks. Do not stage or commit anything.

---

# 18. FINAL EXECUTOR INSTRUCTION

Document only the bounded diagnostic context and disclosure doctrine.

Make future errors precise without turning raw values into an arbitrary bag, secrets into context, Safe into automatic production disclosure, or a shared value package into a universal catch boundary.

Do not implement the context types or error page. Do not change PHP, dependencies, tooling, or Git history. Do not begin Phase 0P.

Stop rather than infer any missing runtime API, sanitizer, renderer, transport, or failure-handling decision.
