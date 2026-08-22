# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0W

## Trusted Developer Diagnostic Projection Architecture

## Mission

Establish the architecture for Kongono's first developer-facing diagnostic
presentation boundary without implementing it.

Phase 0W must decide:

- which future package owns diagnostic presentation;
- how that package remains a leaf above the existing package-local exception
  boundaries;
- the exact information a trusted developer projection may contain;
- how Safe and Protected values remain classified and bounded;
- how trusted activation stays outside client control;
- how the projection remains persistent-runtime-safe; and
- which capabilities remain deliberately deferred.

The outcome is an Accepted ADR and reconciled documentation for a future pure
trusted-developer projection. Do not create the package directory, PHP types,
renderer, error page, transport adapter, trusted-mode selector, or production
surface in this phase.

This phase converts renderer ownership and the first presentation-model boundary
from open questions into explicit architecture. Exact implementation remains a
later directive.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the executor, not the architect.

Read completely before any edit:

1. `AGENTS.md`;
2. `README.md`;
3. `ARCHITECTURE.md`;
4. `SECURITY.md`;
5. `docs/doctrine.md`;
6. `docs/documentation-standard.md`;
7. `docs/glossary.md`;
8. `docs/adr/README.md` and ADRs 0001 through 0033;
9. `docs/architecture/overview.md`;
10. `docs/architecture/runtime-model.md`;
11. `docs/engineering/error-handling.md`;
12. `docs/engineering/package-layout.md`;
13. all five package manifests and READMEs;
14. all four package exception markers and the six Diagnostics structural
    value types;
15. the Phase 0V directive and populated report;
16. the Phase 0V.1 directive and populated correction report;
17. this Phase 0W directive; and
18. the zero-byte Phase 0W report placeholder.

Prior directives and reports are historical evidence, not active instructions.
Phase 0W may reconcile only the current documentation paths explicitly listed in
Section 11.

If any authority, caller record, Git fact, locked file identity, package edge,
diagnostic contract, or current-versus-planned statement differs:

```text
STOP
REPORT
DO NOT GUESS
DO NOT IMPLEMENT
DO NOT BROADEN THE DOCUMENTATION BOUNDARY
```

---

# 2. LOCKED PRE-CALLER BASELINE

Before the caller installs the Phase 0W records, require exactly:

```text
branch                         main
only local branch              main
HEAD                           7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree                      b1d0057925158e0f0f37ca13694d1d0d525ce5f0
HEAD parent                    75749a37a184996203e569e039bddbc3ec10cdf7
HEAD subject                   Establish Kongono framework baseline through Phase 0U
reachable commits              6
tracked files                  277
tracked modified paths         1
untracked paths                3
total status paths             4
staged paths                   0
remotes                        0
tags                           0
```

The sole tracked modification is the accepted Phase 0V.1 correction:

```text
docs/engineering/package-layout.md
  5,004 bytes
  SHA-256 1F890ED538A5248F75E8627DA21C8533503054B517D1914DD497ED7E36637B07
  cumulative diff from HEAD: 13 insertions, 13 deletions
```

The exact three untracked paths are:

```text
docs/dev_plan/kongono_phase_0V_1_codex_directive_correction.md
docs/task reports/Phase_0V.md
docs/task reports/Phase_0V_1_correction.md
```

Their identities are:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0V_1_codex_directive_correction.md` | 15,434 | `C0DF348516E2A8848278F51A768835A95BA24FDDEAAD49478B4D5F8B3037FBC5` |
| `docs/task reports/Phase_0V.md` | 24,353 | `6C5053A469664994C5494598AF0D29E53089F0EACAD08D7ADA744838F404A4A3` |
| `docs/task reports/Phase_0V_1_correction.md` | 10,110 | `AB965D2542B243A038B2213106D3C739DBF40F29913D72FED72D275181EF45E3` |

Raw pre-caller inventory hashes are:

```text
git status --porcelain=v1 -z -uall | git hash-object --stdin
  3311141c28fdaf084e2ea20e8333c53bf3abf05d

git diff --name-only -z | git hash-object --stdin
  9aca16e26e17d279c9d1922173e113d6f3388ada

git ls-files -o --exclude-standard -z | git hash-object --stdin
  98dd7dc599f7e5c0b15070c053a8383bb166ee3f
```

Hash the raw command output without sorting or normalization. The managed Git
global-ignore warning may be recorded only after the closed inventories pass by
independent direct comparison.

---

# 3. CALLER SETUP

The caller copies this directive byte-for-byte to:

```text
docs/dev_plan/kongono_phase_0W_codex_directive.md
```

and creates an ordinary zero-byte file at:

```text
docs/task reports/Phase_0W.md
```

Record the copied directive's byte count and SHA-256 and compare them with the
architect-delivered artifact identity. The report must initially have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

After caller setup require exactly:

```text
tracked modified paths         1
untracked paths                5
total status paths             6
staged paths                   0
```

The only additions to Section 2 are the Phase 0W directive and report. Do not
populate the report until every non-report acceptance gate passes.

---

# 4. CURRENT IMPLEMENTED REALITY TO PRESERVE

Documentation must continue to state accurately:

- the runtime has five implemented package directories and six Composer
  manifests including the root;
- API Metadata owns compatibility classification;
- Diagnostics owns presentation-neutral bounded schemas and immutable values;
- Kernel, Application Graph, and Dependency Injection independently depend on
  Diagnostics and retain separate Public exception markers;
- all 42 original Kernel/Graph/DI failures expose exact package-owned context,
  split 6/11/25;
- the catalog has exactly 53 unique IDs, including 11 context-free Diagnostics
  construction failures;
- Safe means eligibility for a later explicit policy, never automatic output;
- Protected is limited to a trusted developer or protected operational
  boundary;
- forbidden material must not be captured even as Protected;
- native messages, causes, traces, source, paths, headers, bodies, environment,
  and provider payloads are not part of current diagnostic context; and
- no universal diagnostic interface, presentation package, projector, trusted-
  mode selector, renderer, error page, HTTP/CLI mapping, logger, telemetry hook,
  incident system, source provider, or documentation registry currently exists.

Phase 0W changes documentation only. Every planned type and package in this
directive must be labeled planned, future, or not yet implemented.

---

# 5. ACCEPTED FUTURE PACKAGE OWNERSHIP

Record this architectural decision:

```text
Composer package:  kongono/diagnostic-presentation
PHP namespace:     Kongono\DiagnosticPresentation\
role:              leaf integration and presentation boundary
status in Phase 0W: planned only; no directory or manifest exists
```

The future package owns developer and production presentation models,
projection policy, and renderers. It does not own diagnostic identifiers,
titles, remediation, context schemas, throw-site values, exception meaning, or
recovery boundaries.

`kongono/diagnostics` remains presentation-neutral. Do not place developer view
models, renderers, trusted-mode logic, HTML, JSON, CLI formatting, or transport
policy in it.

The planned package is a leaf. Its first implementation may depend directly on:

```text
diagnostic-presentation -> api-metadata
diagnostic-presentation -> diagnostics
diagnostic-presentation -> kernel
diagnostic-presentation -> application-graph
diagnostic-presentation -> dependency-injection
```

No existing package may depend on Diagnostic Presentation. There is no reverse
edge, service registration, Kernel binding, DI definition, Graph node, or
ambient discovery. The graph remains acyclic.

The root may consume the package for monorepo testing only when a later
implementation directive creates it. Phase 0W changes no manifest, lock,
autoload, tool configuration, package directory, or source namespace.

---

# 6. ACCEPTED TRUST AND ACTIVATION BOUNDARY

The first presentation model is for a trusted developer boundary only.

Trust is a runtime-composition decision. It must originate from explicit,
server-controlled configuration supplied to a future outer adapter or
composition root. It may never be enabled, widened, or inferred from:

- a query parameter, route value, request body, request header, cookie, session
  value, form field, user input, database row, provider payload, or other
  client-controlled data;
- an exception message, class name, context field, application environment
  string, hostname, or heuristic;
- an AI model, prompt, response, tool output, or recommendation; or
- ambient global, superglobal, container, service-locator, thread-local,
  fiber-local, static, mutable process-wide, or previous-operation state.

Phase 0W does not select the exact trusted-configuration type or runtime adapter.
The pure projection planned below does not activate trust and cannot prove that
its caller is authorized. Calling it is not itself authorization. A later
runtime integration must perform the trusted server-controlled decision before
projecting or rendering Protected data.

The type and package names must make trusted-developer intent conspicuous. A
production adapter must not reuse the trusted developer projection and then try
to redact it. Production requires a separately designed deny-by-default model
and explicit allowlists under a later ADR.

---

# 7. FIRST PLANNED DEVELOPER PROJECTION CONTRACT

The first future implementation is a pure, deterministic projection boundary,
not a renderer.

Reserve these planned Experimental concepts:

```text
Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField
Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector
```

These names are architectural reservations only in Phase 0W. Do not create PHP
declarations, stubs, examples that claim executable constructors, or autoload
mappings.

The planned owner vocabulary is exact:

```text
kernel
application_graph
dependency_injection
```

The projector accepts exactly one of the three existing contextual package
marker contracts:

```text
Kongono\Kernel\Exception\ExceptionInterface
Kongono\ApplicationGraph\Exception\ExceptionInterface
Kongono\DependencyInjection\Exception\ExceptionInterface
```

This union does not create a universal exception interface. The projector does
not accept arbitrary `Throwable`, the context-free Diagnostics marker, raw
metadata strings, arrays, maps, or a generic object. Direct arbitrary
construction of a trusted developer diagnostic is not part of the planned
public calling contract.

An object satisfying more than one of the three markers is ambiguous and must
be rejected before any metadata/context accessor is called. Exact construction-
failure identifiers and exception types remain for the implementation directive,
but they must stay package-local and context-free to avoid recursive diagnostic
construction.

The projector must call each accepted source accessor at most once, validate
the returned catalog prose before storing it, detach every list, perform no
catch-all wrapping, and allow an accessor-thrown native `Throwable` to propagate
unchanged.

---

# 8. EXACT PROJECTED INFORMATION

One future `TrustedDeveloperDiagnostic` contains only:

1. the exact `DeveloperDiagnosticOwner` derived from the single marker family;
2. the stable diagnostic identifier;
3. the precise catalog title;
4. the fixed operation from `DiagnosticContext`;
5. the fixed violated invariant or expectation from `DiagnosticContext`;
6. the catalog remediation direction; and
7. the context fields in canonical schema order.

One future `TrustedDeveloperDiagnosticField` contains only:

1. canonical key;
2. fixed label;
3. exact `DiagnosticValueKind`;
4. already-bounded `string|int|bool` value;
5. exact Safe or Protected disclosure; and
6. nullable text-truncation state exactly as supplied by `DiagnosticField`.

The field projection copies values and metadata into an immutable detached
snapshot. It does not retain the originating exception, context, or field
object. Returned lists are detached and preserve canonical order.

The projection includes both Safe and Protected fields because it is explicitly
trusted-developer data. It must preserve each field's disclosure classification
so a future renderer can label and group honestly. It may not promote,
reclassify, merge, omit, redact, sanitize, or infer disclosure.

The projection contains no native message, cause, trace, class name, source
path, source excerpt, stack frame, request or operation envelope, header, body,
environment/configuration value, SQL, provider payload, log data, telemetry
attribute, incident reference, documentation URL, localization key, or arbitrary
extension metadata.

---

# 9. PROJECTION VALIDATION AND BOUNDS

The future projector must preserve existing structural bounds and add only these
presentation-boundary checks:

- the marker family determines the exact allowed identifier prefix:
  `KGN-KERNEL-`, `KGN-GRAPH-`, or `KGN-DI-`;
- the suffix is exactly four ASCII decimal digits;
- title is non-empty valid UTF-8 and at most 160 bytes;
- remediation is non-empty valid UTF-8 and at most 256 bytes;
- operation and invariant remain the already-validated non-empty UTF-8 values
  bounded to 160 and 256 bytes by Diagnostics;
- fields remain limited to 16 and text remains the already-bounded deterministic
  ASCII representation of at most 512 bytes; and
- owner, identifier, title, remediation, context, and each field are read once
  into local values before the immutable result is constructed.

The current 42 package-owned identifiers, titles, and remediations fit these
bounds. These checks protect the presentation boundary from unsupported custom
marker implementations; they do not make consumer-defined metadata a
Kongono-owned catalog entry.

The projector performs no secret detection. Forbidden material remains excluded
at schema ownership and capture time. A projector or renderer may never claim
that bounded text is sanitized, safe HTML, safe JSON, safe shell output, or free
of secrets.

---

# 10. DEVELOPER INFORMATION ARCHITECTURE

The future trusted developer experience maps projected data as follows:

```text
Diagnostic header     identifier + precise title + owning package
What happened         fixed operation
Why it happened       fixed violated invariant or expectation
Context               ordered labeled fields with disclosure and truncation state
How to fix it          catalog remediation
```

This mapping is deterministic and explains known failures without internet,
remote service, AI call, source crawl, or arbitrary reflection.

The later renderer must be readable, accessible, server-deterministic, bounded,
and useful without mandatory client-side JavaScript. However Phase 0W selects
no HTML, CSS, templates, JavaScript, Markdown, JSON, terminal formatting,
clipboard action, color palette, typography, component library, content type,
HTTP status, problem-details format, or CLI exit code.

Escaping belongs to the later concrete renderer for its exact output grammar.
The projection must not pre-escape values or produce display markup.

---

# 11. EXACT FILE BOUNDARY

Phase 0W may create exactly one architecture record:

```text
docs/adr/0034-trusted-developer-diagnostic-projection.md
```

Phase 0W may modify exactly these 14 existing files:

```text
README.md
ARCHITECTURE.md
SECURITY.md
docs/doctrine.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/diagnostics/README.md
```

The current identities are locked in Appendix A. The Phase 0V.1 bytes already
present in `package-layout.md` are accepted and must not be reversed.

No other path may be created, modified, deleted, renamed, staged, or committed.

After documentation work and before report population require exactly:

```text
tracked modified paths         14
untracked paths                6
total status paths             20
staged paths                   0
```

The six untracked paths must be the three inherited records, the Phase 0W
directive and report, and new ADR 0034.

---

# 12. ADR 0034 REQUIRED CONTRACT

Create `docs/adr/0034-trusted-developer-diagnostic-projection.md` with:

```text
Title:  Trusted Developer Diagnostic Projection
Status: Accepted
Date:   2026-08-14
```

Use every required ADR section exactly once:

```text
Title
Status
Date
Context
Decision
Rationale
Consequences
Rejected alternatives
What remains open
```

The Decision must record all of Sections 5 through 10, including:

- separate leaf-package ownership and exact planned namespace;
- exact one-way dependency direction;
- absence of the package and runtime behavior in Phase 0W;
- server-controlled trust as an outer integration responsibility;
- rejection of all client/AI/ambient trust activation;
- exact four planned Experimental concepts;
- three-marker union without a universal marker;
- rejection of ambiguous multi-marker sources before accessor calls;
- exact owner vocabulary, projected properties, field properties, bounds, and
  detachment;
- inclusion of Safe and Protected fields only in the trusted developer model;
- preservation of disclosure and prohibition on sanitization claims;
- no message/cause/trace/source/request/environment/transport data;
- stateless, deterministic, bounded, I/O-free persistent-runtime behavior; and
- separation of projection from concrete rendering and production policy.

Rejected alternatives must include:

- adding presentation behavior to `kongono/diagnostics`;
- adding it to Kernel, Graph, or DI;
- a universal exception or diagnostic marker;
- accepting arbitrary `Throwable`, strings, arrays, or metadata bags;
- a global/ambient projector or static registry;
- request-, environment-string-, heuristic-, or AI-controlled trusted mode;
- using the trusted developer model for production and redacting afterward;
- including native messages, traces, source, or request data now;
- implementing HTML/HTTP/CLI before the projection boundary; and
- creating empty/speculative package or source stubs in this phase.

What remains open must include:

- exact PHP constructors, method names, package-local failures, and identifiers;
- implementation and Composer lock mechanics;
- exact trusted configuration/capability type and runtime adapter;
- concrete accessible server-rendered development format;
- safe copy/export formats;
- native cause-chain policy;
- version-matched documentation lookup;
- source and stack-frame provider contracts;
- production model, allowlists, transport mappings, incident references, and
  status/exit-code policy;
- localization;
- logging and telemetry;
- simultaneous primary/context/projection/rendering failure representation; and
- serialization and cross-process/cross-language representation.

Do not list package ownership, dependency direction, trusted activation
prohibitions, the projection data set, or developer-versus-production separation
as still open.

---

# 13. DOCUMENT RECONCILIATION REQUIREMENTS

## `README.md`

State that Phase 0W accepts the future Diagnostic Presentation leaf boundary and
trusted developer projection architecture only. Preserve the implemented
Phase 0U runtime and state plainly that no package, projector, renderer, or
trusted-mode selector exists yet.

## `ARCHITECTURE.md`

Add the planned leaf above the current five-package graph without changing the
implemented graph. Summarize the exact trusted projection, security boundary,
and separation from Diagnostics and production presentation.

## `SECURITY.md`

Record server-controlled trusted activation, prohibited activation sources,
trusted inclusion of classified Safe/Protected fields, prohibition on using the
developer model for production, and the continued exclusion of forbidden
material and unselected native/request/source detail.

## `docs/doctrine.md`

Record the locked ownership and trust principles. Preserve that current runtime
behavior ends at Phase 0U and that Phase 0W is architecture-only.

## ADRs 0026, 0028, and 0029

Add concise Phase 0W amendments that link ADR 0034 and distinguish resolved
presentation ownership/projection decisions from still-open renderers,
transports, production policy, and runtime integration. Do not rewrite their
historical decisions.

ADR 0028's What remains open must no longer say renderer package ownership or
the first presentation model is wholly open. It must retain concrete renderer
APIs/formats and all other genuinely open items.

## `docs/adr/README.md`

Add ADR 0034 as item 34 with the exact title.

## Architecture overview and runtime model

Document the planned leaf position, no reverse dependencies, no ambient state,
no caches, no cross-operation retention, and no implemented runtime adapter.
Preserve current package counts and behavior.

## `docs/engineering/error-handling.md`

Promote the trusted developer projection information architecture from wholly
open to Accepted future architecture. Add exact ownership, input markers,
projected data, bounds, disclosure behavior, trust prohibitions, and explicit
non-goals. Keep the concrete renderer and production surface future.

Update testing expectations so later implementation must test all three marker
families, owner/prefix mapping, ambiguous multi-marker rejection, accessor
single-read behavior, exact data projection, canonical order, detachment,
bounds, Safe/Protected preservation, native accessor-failure propagation, no
ambient state/I/O/cache, and marker separation.

## `docs/engineering/package-layout.md`

Preserve the Phase 0V.1 corrected implemented five-package graph. Add a clearly
separate planned section for `packages/diagnostic-presentation/` and its future
leaf dependencies. Do not add it to the current tree or current package count.

## `docs/glossary.md`

Define Trusted Developer Diagnostic Projection, Diagnostic Presentation, and
Developer Diagnostic Owner as planned Phase 0W concepts. Reconcile Safe,
Protected, and trusted developer mode entries without claiming activation or
rendering exists.

## `packages/diagnostics/README.md`

State that Accepted future presentation ownership belongs to the separate leaf
package and that Diagnostics remains presentation-neutral with no reverse
dependency. Do not imply the future package currently exists.

---

# 14. LOCKED NON-GOALS

Do not create `packages/diagnostic-presentation`, any PHP file, test, fixture,
manifest, namespace, class, interface, enum, attribute, exception, diagnostic ID,
service, factory, renderer, template, CSS, JavaScript, image, or other asset.

Do not modify root or package Composer manifests, `composer.lock`, autoload
metadata, PHPUnit, PHPStan, PHP CS Fixer, `.editorconfig`, `.gitattributes`,
`.gitignore`, vendor state, or cache configuration.

Do not implement:

- HTML, Markdown, JSON, CLI, terminal, HTTP, RFC 9457, or other rendering;
- an error page, exception handler, middleware, outer runtime boundary, content
  negotiation, response status, exit code, or transport adapter;
- trusted-mode selection, environment lookup, server configuration, capability
  tokens, authorization, authentication, or request integration;
- native messages, causes, traces, source excerpts, stack frames, documentation
  lookup, localization, copy/export, incident references, logging, telemetry,
  persistence, serialization, or network calls;
- production disclosure, allowlists, redaction, sanitization, secret detection,
  or a production diagnostic model;
- a universal exception/diagnostic interface or changes to any existing marker;
- DI bindings, Kernel lifecycle integration, Application Graph metadata,
  service location, agents, extensions, ORM, CLI, frontend, or deployment; or
- any later Phase 0X implementation.

Do not stage, commit, reset, amend, switch branches, create refs, add remotes,
tag, push, or rewrite history.

---

# 15. VALIDATION

Run a complete pre-edit baseline validation before documentation changes and a
complete post-edit validation afterward.

Required checks include:

- exact Git baseline, caller records, closed file identities, inventories,
  modes, refs, remotes, tags, index, and history;
- strict UTF-8 without BOM, LF-only, exactly one final LF, no trailing
  whitespace, NUL, replacement character, mojibake, or conflict marker for all
  changed/new Phase 0W documents;
- ordinary-file, non-reparse, mode `100644` where tracked, and zero alternate
  streams;
- exactly one new ADR and exactly 14 modified existing paths;
- all local Markdown links with zero broken targets;
- current-versus-planned phrase inspection;
- package-directory and manifest counts remain exactly five and six;
- absence of `packages/diagnostic-presentation` and all reserved FQCNs;
- no manifest, lock, source, test, fixture, or tooling change;
- `git diff --check` returns zero; and
- canonical `composer quality` before and after documentation work.

Canonical quality must remain exactly:

```text
PHPUnit         204 tests, 3,377 assertions
PHPStan         zero errors
PHP CS Fixer    0 of 113 files fixable
security audit  zero advisories
platform        pass
```

Record actual versions of PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer.
The accepted versions are PHP 8.5.9, Composer 2.10.2, Git 2.45.1.windows.1,
PHPUnit 13.3.0, PHPStan 2.2.8, and PHP CS Fixer 3.95.18.

If an unchanged fail-closed Composer audit is blocked solely by managed network
or cache restrictions, request the standard approved access and rerun the
identical command. Do not skip or weaken the gate.

After documentation work and before report population, current status must be
exactly the Section 11 20-path state. No staged path is permitted.

---

# 16. COMPLETION REPORT

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_0W.md
```

Use the standard 16 headings from `AGENTS.md` exactly once and in order:

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

The report must distinguish implemented Phase 0U behavior, the accepted Phase
0W future architecture, and still-open implementation/rendering decisions.

For completion:

- list exactly one created ADR and 14 modified existing documents;
- state that no runtime/package/projector/renderer was implemented;
- record every Section 5 through 10 decision;
- record exact pre/post Git state and changed/untracked inventories;
- record pre/post quality, links, package/manifest counts, reserved-FQCN
  absence, text integrity, stream, and protected-scope results;
- disclose every environmental failure, retry, and warning;
- Sections 6, 7, and 13 say `None`;
- the populated report remains untracked, unstaged, absent from `HEAD`, strict
  UTF-8 without BOM, LF-only, exactly one final LF, ordinary, non-reparse, and
  stream-safe; and
- Section 16 is exactly:

```text
Await architect review before Phase 0X.
```

Return the report's final byte count and SHA-256 outside it after the last write.

---

# 17. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- [ ] Exact Phase 0V.1 baseline, histories, caller records, raw hashes, and
  corrected package-layout identity are preserved.
- [ ] Caller setup adds only the exact Phase 0W directive and zero-byte report.
- [ ] Exactly ADR 0034 is created and exactly the 14 authorized existing files
  are modified.
- [ ] ADR 0034 is Accepted, dated 2026-08-14, and contains every required section
  and decision.
- [ ] Future ownership belongs only to the planned Diagnostic Presentation leaf
  package; Diagnostics remains presentation-neutral.
- [ ] Planned dependency direction is exact and acyclic, with no reverse edge.
- [ ] Trust is server-controlled at a later outer runtime integration and every
  client/AI/ambient activation source is rejected.
- [ ] Exact four planned Experimental concepts, three owner tokens, three input
  markers, projection fields, bounds, detachment, and ambiguous-source behavior
  are documented consistently.
- [ ] The trusted projection includes classified Safe and Protected fields but
  is explicitly prohibited from production reuse.
- [ ] No message, cause, trace, source, request, environment, transport,
  telemetry, incident, documentation, localization, or arbitrary metadata is
  included.
- [ ] Projection and concrete rendering remain separate; no output grammar or
  runtime API is selected.
- [ ] Current five-package/six-manifest runtime and all Phase 0U behavior remain
  stated accurately.
- [ ] No package directory, code, test, fixture, manifest, lock, autoload,
  tooling, dependency, API, or runtime behavior changes.
- [ ] Links, encoding, whitespace, final newline, file type, mode, reparse,
  alternate-stream, current/future wording, and protected-scope checks pass.
- [ ] Pre/post canonical quality remains exactly 204 tests/3,377 assertions,
  PHPStan zero, fixer 0/113, zero advisories, and platform pass.
- [ ] Final state is exactly 14 tracked modifications, six untracked paths,
  20 total status paths, and zero staged paths.
- [ ] No stage, commit, ref, configuration, remote, tag, push, or history
  mutation occurs.
- [ ] Report has the exact 16 headings, evidence, integrity, and NEXT STEP.
- [ ] Final report byte count and SHA-256 are returned outside the report.

Any failed or unreached item makes status `Blocked` or `Partial`, never
`Complete`.

---

# 18. FINAL EXECUTOR INSTRUCTION

Document only the Accepted future trusted developer projection architecture.
Keep Diagnostic Presentation a planned leaf, keep Diagnostics presentation-
neutral, preserve the exact Phase 0U runtime, and leave every concrete package,
API, renderer, trust selector, production policy, and transport for later work.

Do not implement Phase 0X.

---

# APPENDIX A - LOCKED AUTHORIZED EXISTING-FILE IDENTITIES

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `README.md` | 7,246 | `1B4B75A088FDD88EFB0290DC068A1527ACD8F8247F390FBBE735B8FAE4511816` |
| `ARCHITECTURE.md` | 8,743 | `542598665D7A0A0B497E1B05A92CCFD83CE624041DD7AF81A39AAC25871E52E7` |
| `SECURITY.md` | 6,819 | `120ACE2670082C9D37EE8ED761EF1B152EA0DBCD2EDE9747D7A491E00130709F` |
| `docs/doctrine.md` | 13,470 | `7CB80DC5959AB6B1859F4E0330133D84E9E212179FC2221F798117075CB32BC1` |
| `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md` | 21,648 | `E141131E2B223B11268820F4FFB4FA387DA7C0ABCF50D29A37E5D8040A4BDC6A` |
| `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md` | 10,915 | `B04B3BBE0F87F6C443607FE53258163619D2CC2FD107EFFA2333DB3B6EA6F25D` |
| `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md` | 5,744 | `25C932808F060409C900D58613278C5930FE5B45AF439F3B1CD283B540A11064` |
| `docs/adr/README.md` | 3,665 | `2926D2670271B073BD300DDD1BD0DE9000365949EB0BF7380948190B16D9B75B` |
| `docs/architecture/overview.md` | 7,115 | `24725CFC0EB98571DBB0651FF03139F0142C2D78421C461BC6F08444B94E956C` |
| `docs/architecture/runtime-model.md` | 4,829 | `BF0A5347A9CBF5081F247F12FE707EA9CFC85B9ED13A47BF239D03AB99963167` |
| `docs/engineering/error-handling.md` | 54,108 | `E5356B6D1532FE37C93A20B4DEE982ADB1A0A983F04C50CC54898E028F1863D6` |
| `docs/engineering/package-layout.md` | 5,004 | `1F890ED538A5248F75E8627DA21C8533503054B517D1914DD497ED7E36637B07` |
| `docs/glossary.md` | 18,209 | `410A178E7D72F3A6C6408B6ADFC663EE8E44BC2DACC6DCC1B287A41CFCA620C1` |
| `packages/diagnostics/README.md` | 5,250 | `F61BE330B11F466E704EA8EE4F3688386E99F5F145C5899E7DA3C81B59BAFF65` |

All identities are the accepted post-Phase 0V.1 worktree values. A mismatch is a
stop condition, not permission to normalize or reconcile an adjacent file.
