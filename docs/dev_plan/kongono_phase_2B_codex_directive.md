# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 2B

## HTTP Message and Interoperability Architecture Decision

**Directive type:** architecture and documentation only

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Branch:** `main`

**Date:** 2026-08-16

**Phase:** 2B

**Predecessor:** accepted Phase 2A

**Next implementation phase:** 2C, only after architect acceptance of this report

---

## 1. Mission

Phase 2B makes the explicit HTTP interoperability decision required by Phase
2A before any web runtime implementation begins.

This phase must:

1. accept PSR-7 through the `psr/http-message` 2.x interface package as
   Kongono's HTTP message interoperability contract;
2. accept the server-side portion of PSR-17 as Kongono's HTTP factory
   interoperability contract;
3. accept PSR-15 as the future server request-handler and middleware
   interoperability contract for Phase 2E;
4. select the exact future first-party HTTP message package boundary and
   dependency direction;
5. define the message, stream, URI, factory, security, ownership, and
   persistent-runtime rules that Phase 2C must implement;
6. preserve a Kongono-owned implementation rather than selecting Symfony,
   Guzzle, Laminas, Nyholm, Slim, Laravel, or another framework or message
   implementation as the production foundation;
7. reconcile the Phase 2 roadmap and current-versus-planned documentation; and
8. stop before PHP, Composer, tests, routing, middleware, bootstrap, adapter,
   emission, or production error implementation.

Phase 2B is documentation-only. It makes decisions that authorize a later
implementation; it does not install the selected interface packages or claim
conformance.

The intended outcome is precise:

> Kongono will build its own bounded server-side HTTP message implementation
> against small PHP-FIG interoperability interfaces. The standards are
> contracts at the edge, not a substitute framework and not the owner of
> Kongono's architecture.

No broader inference is authorized.

---

## 2. Authority, authentication, and stop rule

### 2.1 Required authority reading

Before broad validation or mutation, read completely:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `README.md`;
4. `SECURITY.md`;
5. `CONTRIBUTING.md`;
6. `docs/doctrine.md`;
7. `docs/documentation-standard.md`;
8. `docs/engineering/api-status.md`;
9. `docs/engineering/error-handling.md`;
10. `docs/engineering/package-layout.md`;
11. `docs/architecture/overview.md`;
12. `docs/architecture/runtime-model.md`;
13. `docs/architecture/phase-2-roadmap.md`;
14. `docs/adr/0001-record-architecture-decisions.md`;
15. `docs/adr/0003-monorepo-package-boundaries.md`;
16. `docs/adr/0004-microkernel-capability-architecture.md`;
17. `docs/adr/0006-persistent-runtime-first.md`;
18. `docs/adr/0013-error-handling-and-actionable-diagnostics.md`;
19. `docs/adr/0014-api-stability-and-versioning.md`;
20. `docs/adr/0016-package-dependency-direction-and-composition-root.md`;
21. `docs/adr/0022-dependency-injection-package-boundary.md`;
22. `docs/adr/0026-actionable-diagnostic-runtime-metadata.md`;
23. `docs/adr/0028-presentation-neutral-diagnostic-context.md`;
24. `docs/adr/0031-neutral-api-metadata-package-boundary.md`;
25. `docs/adr/0036-diagnostic-presentation-package-architecture.md`;
26. `docs/adr/0038-trusted-developer-diagnostic-projection.md`;
27. `docs/adr/0039-two-gate-trusted-diagnostic-activation.md`;
28. `docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md`;
29. `docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md`;
30. `docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md`;
31. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md`;
32. `docs/dev_plan/kongono_phase_2A_codex_directive.md`; and
33. `docs/task reports/Phase_2A.md`.

The accepted ADRs and doctrine remain binding. This directive narrows only the
previously open Phase 2 HTTP interoperability decisions.

### 2.2 Independent directive identity

The architect will provide this directive's byte count and uppercase SHA-256
outside the file. Compare those independently supplied values with the local
copy before running `composer quality`, editing, staging, or creating any other
file.

Do not calculate the values and then treat the same local artifact as its own
authority. If the independently delivered values are absent or different:

```text
STOP
ASK
DO NOT GUESS
```

### 2.3 Caller-managed report placeholder

Before execution, the caller creates exactly:

```text
docs/task reports/Phase_2B.md
```

It must initially be an ordinary, untracked, unstaged, zero-byte file absent
from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create it for the caller. Do not populate it until every permitted
non-report gate passes.

### 2.4 Stop rather than assume

At the first mismatch, ambiguity, unexpected path, failed check, protected-byte
change, new warning category, unauthorized side effect, or conflict between
authorities:

1. stop immediately;
2. preserve the repository, worktree, and index exactly as observed;
3. do not repair, normalize, restore, reset, stage, commit, or widen scope;
4. leave the Phase 2B report zero bytes unless Section 13 authorizes
   population; and
5. report the exact expected and observed values, viable options, and one
   recommendation.

No undocumented architectural choice is authorized.

---

## 3. Locked accepted starting state

### 3.1 Git identity before Phase 2B caller setup

The accepted post-Phase-2A state is:

| Item | Required value |
|---|---|
| Branch | `main` |
| Local branches | `1` |
| `HEAD` | `d7846f137a1d53f5306f48d19196b916fde44ac2` |
| `HEAD` tree | `bfa1908c619069b699bc9b08668db17ea2555eb3` |
| `HEAD` parent | `8fe793b124384744ac43887037360154c149241a` |
| Commit count | `9` |
| Remotes | `0` |
| Tags | `0` |
| Staged paths | `0` |
| Modified tracked paths | `11` |
| Untracked paths with `-uall` | `8` |
| Total status paths with `-uall` | `19` |
| Package directories | `8` |
| Package manifests | `8` |
| Composer manifests including root | `9` |
| Production PHP files | `53` |
| Test PHP and fixture files | `85` |

After the caller copies this directive and creates the zero-byte report, and
before executor edits, only these counts change:

```text
modified tracked paths: 11
untracked paths: 10
total status paths: 21
staged paths: 0
```

The two added caller records are:

```text
docs/dev_plan/kongono_phase_2B_codex_directive.md
docs/task reports/Phase_2B.md
```

### 3.2 Exact accepted status-path identities

Before caller setup, the exact 19 status paths and identities are:

| State | Path | Bytes | SHA-256 |
|---|---|---:|---|
| M | `ARCHITECTURE.md` | 16242 | `6471D1E7911E61FD18B7E464ECF148FACFC062067E13D287BD480FC452D33478` |
| M | `README.md` | 12470 | `2B4BAB62A3A76491BC3086F53DDED4260DD41DDD8FA4042D7B7663254BCFAD19` |
| M | `SECURITY.md` | 15839 | `D676696F41277CA86EEAC86DF1B64396D52EF5C3C32D262A9DEF4BE7A50DD593` |
| M | `docs/adr/0004-microkernel-capability-architecture.md` | 2965 | `36FFDC327D54F78B707B20FF6ABCD7E06539F0A8F9BBE69089491C77B29ED86A` |
| M | `docs/adr/README.md` | 6104 | `492459A8AEA1540C8A5503EDD31F263CC707800BF0FDB0DDC63D6039659926B5` |
| M | `docs/architecture/overview.md` | 13757 | `CF65CC214D24645FF2CECBA61F6D7C3C6188382CCEBD48F5F65EDD0851C9EB32` |
| M | `docs/architecture/runtime-model.md` | 11058 | `35DBB2E748CE33D2ECFC5CB265AFEBCCEED9A757BC353B5EBBF645B47AB2D5A0` |
| M | `docs/doctrine.md` | 18020 | `52318F1D2470E78485AC0D4AFE4ADA59BFE32E6A381D14B30E04A9EF75E4C2DF` |
| M | `docs/engineering/error-handling.md` | 72532 | `7D814E417E979063745AB4742BA0C8799CCA17B0FF13F6FAF16ADEE92072B5C9` |
| M | `docs/engineering/package-layout.md` | 11325 | `096939F16A488621725026212FA5EFD832593619CC3EB8721B60BE156DB38B04` |
| M | `docs/glossary.md` | 27126 | `D375EFD1ABCFDD247675B577228A90113597C3B967D5AD449B90688FB0BDD016` |
| ?? | `docs/adr/0043-phase-2-runnable-web-framework-milestone.md` | 13476 | `E2C6F8081C29162B28298DDCD382892414A4DC54F558329444E5CCC83E295DD1` |
| ?? | `docs/architecture/phase-2-roadmap.md` | 9640 | `1129101A8617E6A560AB10C35233681FB0016D56947950083EAE55FBB153424D` |
| ?? | `docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md` | 20148 | `2A1687E99B78B7FFE5E877E26E40B8DA73F69F44DA8714FACC82411121923936` |
| ?? | `docs/dev_plan/kongono_phase_1I_codex_directive.md` | 30470 | `85E3293A9FF52D35FBFF775E45E77C5BEDDD2D6EF125A31DA469EAADDCF6013B` |
| ?? | `docs/dev_plan/kongono_phase_2A_codex_directive.md` | 31809 | `6A472F370094BB605F10857A24262E91069BA1898A142FC8D7A545BF41B5A5E1` |
| ?? | `docs/task reports/Phase_1I.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| ?? | `docs/task reports/Phase_1I_1_correction.md` | 18259 | `B53903B9E8D3808D9F13A100D85598786DADAA13539CE2F1EAC824CCFA2BD6A4` |
| ?? | `docs/task reports/Phase_2A.md` | 16218 | `454396E69FEE973940E781A08A0D14E5C4C7C0EA8635ED965ED2753B4FD6194A` |

Only the exact Section 7 documentation targets may change. Every other path in
this table is an immutable caller or accepted record for Phase 2B.

### 3.3 Lockfile and protected implementation set

The accepted `composer.lock` identity is:

```text
bytes: 182894
SHA-256: 804B276750164A634D25ED97A613D9B51FC1E59F9D170A9D345C733299B3498B
```

The protected implementation/tooling manifest contains exactly:

- root `composer.json` and `composer.lock`;
- all eight `packages/*/composer.json` manifests;
- `.php-cs-fixer.dist.php`, `phpstan.neon.dist`, and `phpunit.xml.dist`;
- all 53 production PHP files under package `src/` directories; and
- all 85 PHP test and fixture files under root or package `tests/` directories.

Construct it in memory with records:

```text
<ordinal repository-relative path with /><NUL><decimal byte length><NUL><uppercase SHA-256><LF>
```

Sort using ordinal bytewise semantics, not culture-sensitive ordering.

Required identity:

```text
entries: 151
manifest bytes: 20081
manifest SHA-256: D28E569D6D0F1D2DFC98856BC387F279767D79AC1972923294BD28DF3DFB5622
```

Do not create a repository manifest file.

---

## 4. Evidence base and exact interoperability decision

### 4.1 Primary sources

The architectural decision is based on these primary sources:

- [PHP-FIG PSR-7 HTTP Message Interfaces](https://www.php-fig.org/psr/psr-7/)
- [PHP-FIG PSR-15 HTTP Server Request Handlers](https://www.php-fig.org/psr/psr-15/)
- [PHP-FIG PSR-17 HTTP Factories](https://www.php-fig.org/psr/psr-17/)
- [RFC 3986 URI Generic Syntax](https://www.rfc-editor.org/rfc/rfc3986.html)
- [RFC 9110 HTTP Semantics](https://www.rfc-editor.org/rfc/rfc9110.html)
- [RFC 9112 HTTP/1.1](https://www.rfc-editor.org/rfc/rfc9112.html)
- [`psr/http-message`](https://packagist.org/packages/psr/http-message)
- [`psr/http-factory`](https://packagist.org/packages/psr/http-factory)
- [`psr/http-server-handler`](https://packagist.org/packages/psr/http-server-handler)
- [`psr/http-server-middleware`](https://packagist.org/packages/psr/http-server-middleware)

Do not replace these with framework documentation, tutorials, popularity
claims, or a third-party implementation's behavior.

### 4.2 Accepted standards

Phase 2B accepts:

| Standard | Future Composer interface package | Constraint | Use |
|---|---|---|---|
| PSR-7 via interface package 2.x | `psr/http-message` | `^2.0` | HTTP message, server request, response, URI, stream, and uploaded-file contracts |
| PSR-17 | `psr/http-factory` | `^1.1` | Exact server-side factories selected in Section 5.3 |
| PSR-15 | `psr/http-server-handler` | `^1.0` | Future terminal/request-handler contract in Phase 2E |
| PSR-15 | `psr/http-server-middleware` | `^1.0` | Future middleware contract in Phase 2E |

These are interface packages only. They do not supply a message implementation,
router, dispatcher, framework, adapter, emitter, or runtime.

Phase 2B adds none of them to Composer. Phase 2C may add only the PSR-7 and
PSR-17 packages after a separate authenticated directive. The PSR-15 packages
remain deferred until the Phase 2E implementation boundary.

### 4.3 Rejected alternatives

Record these rejections with rationale:

1. **A private Kongono-only HTTP contract is rejected.** It would create
   needless adapters and isolate middleware and application code.
2. **Symfony HttpFoundation as the framework substrate is rejected.** It would
   import another framework's HTTP object and lifecycle assumptions rather
   than preserve Kongono ownership.
3. **A third-party PSR-7 implementation as Kongono's required production
   foundation is rejected.** Guzzle, Laminas, Nyholm, Slim, and similar
   implementations may eventually be compatibility-test peers, but none owns
   Kongono's implementation.
4. **Runtime discovery is rejected.** No discovery package, global registry,
   service locator, first-installed-wins rule, or ambient factory lookup is
   allowed.
5. **Direct SAPI globals in the message package are rejected.** `$_SERVER`,
   `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`, input streams, and emitted headers
   belong to a future outer adapter.
6. **Mutable bespoke message objects are rejected.** PSR-7 copy-on-change
   semantics are the public interoperability contract, with the stream caveat
   explicitly documented.
7. **Client-side HTTP scope is rejected for Phase 2.** PSR-18 and a general
   outgoing request factory are not selected by this server-side milestone.

### 4.4 What adoption does not mean

Adopting the interfaces does not:

- adopt a PHP-FIG implementation;
- surrender Kongono's package dependency direction;
- make the Kernel aware of HTTP or PSR namespaces;
- authorize middleware, routing, bootstrap, emission, or adapter code;
- authorize framework auto-discovery;
- treat external interfaces as Kongono API-status metadata subjects;
- claim Phase 2B or the repository is PSR compliant before implementation and
  conformance tests exist; or
- imply Laravel, Symfony, or another framework is a dependency.

---

## 5. Locked future HTTP message package boundary

### 5.1 Exact package identity

Phase 2C is authorized to implement one future first-party package with this
exact identity:

```text
directory: packages/http-message
Composer package: kongono/http-message
production namespace: Kongono\Http\Message\
test namespace: Kongono\Http\Message\Tests\
```

Phase 2B creates none of these paths.

### 5.2 Exact future dependency direction

The Phase 2C package may depend only on:

```text
php: ^8.5
kongono/api-metadata: *
kongono/diagnostics: *
psr/http-message: ^2.0
psr/http-factory: ^1.1
```

The root may later require `kongono/http-message: @dev` under the existing
monorepo path-repository convention.

No reverse dependency from API Metadata or Diagnostics is allowed. The HTTP
message package must not depend on Kernel, Dependency Injection, Application
Graph, Diagnostic Activation, Diagnostic Presentation, Diagnostic Handler,
PSR-15, a router, an adapter, a runtime server, or any third-party PSR-7
implementation.

The future package is a low-level value and factory package. Composition stays
at the application root.

### 5.3 Exact server-side interface surface

Phase 2C must implement first-party concrete roles satisfying exactly these
PSR-7 interfaces:

```text
Psr\Http\Message\ServerRequestInterface
Psr\Http\Message\ResponseInterface
Psr\Http\Message\UriInterface
Psr\Http\Message\StreamInterface
Psr\Http\Message\UploadedFileInterface
```

`ServerRequestInterface` already extends `RequestInterface`, which extends
`MessageInterface`; no separate client request object is required by the Phase
2 server milestone.

Phase 2C must implement exactly these PSR-17 factory roles:

```text
Psr\Http\Message\ServerRequestFactoryInterface
Psr\Http\Message\ResponseFactoryInterface
Psr\Http\Message\UriFactoryInterface
Psr\Http\Message\StreamFactoryInterface
Psr\Http\Message\UploadedFileFactoryInterface
```

`RequestFactoryInterface` is explicitly outside the Phase 2 server-side
boundary. Phase 2B does not lock concrete class names, constructor signatures,
default values, exception inventories, or file counts; the Phase 2C directive
must lock them before implementation.

No extra convenience API, mutable setter API, static global factory, discovery
hook, `fromGlobals()` method, emitter, parser, router helper, or framework base
class is authorized by this decision.

### 5.4 PSR-15 future boundary

Phase 2E must use:

```text
Psr\Http\Server\RequestHandlerInterface
Psr\Http\Server\MiddlewareInterface
```

The terminal application handler and middleware pipeline must speak PSR-7
`ServerRequestInterface` and `ResponseInterface`. Response-producing middleware
must receive an explicit response factory or prototype through composition; it
must not locate one globally.

The exact router API, route result, middleware dispatcher class, terminal
application mapping, error middleware, and owning package names remain Phase
2D and 2E decisions. Do not invent them in Phase 2B.

---

## 6. Locked semantic, security, and runtime principles

### 6.1 Message copy-on-change semantics

- PSR-7 `with*()` methods retain the original message state and return a value
  representing the change.
- Do not call this deep immutability. `StreamInterface` is stateful and can
  change cursor position or underlying resource contents.
- A message copy may share its body stream under PSR-7 semantics. Documentation
  and tests must make this visible rather than implying an isolated body copy.
- Request-specific messages and streams cannot be retained in global, static,
  singleton, container-current, thread-local, fiber-local, or prior-operation
  state.
- A message object represents an HTTP message. Its existence does not prove
  trust, authentication, authorization, routing, or delivery.

### 6.2 Headers

- Header lookup is case-insensitive while the originally supplied field-name
  case is preserved for retrieval.
- Header value order and repeated values are preserved as required by PSR-7.
- Field names and values are validated before storage.
- CR, LF, NUL, invalid field-name characters, and ambiguous line folding are
  rejected; no repair, replacement, or silent normalization is authorized.
- No header name or value enters a diagnostic Safe field merely because its
  syntax is valid.
- Host-header and URI-host interaction follows the PSR-7 `withUri()` contract;
  Phase 2C must test its full decision table.

### 6.3 URI

- URI component behavior follows PSR-7 and RFC 3986.
- Scheme and host canonicalization, authority composition, percent encoding,
  path rules, query, fragment, user information, port handling, and string
  serialization require exact Phase 2C tests.
- Do not decode reserved delimiters into a different semantic meaning.
- Do not perform DNS, filesystem, network, base-URI guessing, or scheme-specific
  resolution in the URI value.
- A syntactically valid URI remains untrusted input.

### 6.4 Streams and uploaded files

- Streams are explicit resource-backed values with observable readability,
  writability, seekability, cursor, size, metadata, detach, and close behavior.
- Phase 2C must bound every read used by its own tests and helpers. It must not
  introduce an unbounded request-body parser.
- Stream factory file access is explicit caller-requested local I/O. It is not
  adapter input parsing, network access, or a reason to expose paths in
  diagnostics.
- Uploaded-file move behavior is one-way and explicit. Invalid target paths,
  prior moves, stream failures, and runtime failures must fail loudly under the
  PSR contract.
- No message, stream, or uploaded-file value owns Kernel lifecycle behavior.

### 6.5 Server request data

- Server parameters, cookies, query parameters, uploaded files, parsed bodies,
  and attributes remain distinct PSR-7 domains.
- The message package stores already-supplied representations; it does not read
  SAPI globals or decide parsing, normalization, size limits, duplicate
  handling, proxy trust, authentication, or authorization.
- Attributes are explicit per-request call-flow data, not an ambient context,
  authorization proof, or permission upgrade.
- Arbitrary mutable consumer values placed in attributes or parsed bodies are
  consumer-owned. Kongono must not claim deep immutability for them.
- Adapter conversion and input parsing remain Phase 2G concerns, after exact
  limits and rejection behavior are selected.

### 6.6 Exceptions and actionable diagnostics

- Phase 2C must use package-local typed failures compatible with PSR-required
  `InvalidArgumentException` or `RuntimeException` categories where applicable.
- Deliberate Kongono failures must retain actionable diagnostic identifiers,
  bounded context, titles, and remediation under existing doctrine.
- Diagnostic context must never contain raw header values, cookies, query
  strings, body contents, credentials, tokens, uploaded content, local file
  contents, or unbounded URI text.
- Safe context may describe only bounded structural facts such as an ordinal,
  field category, status category, or reason case explicitly authorized by the
  Phase 2C schema.
- Consumer/resource/autoloader failures propagate unchanged unless the Phase 2C
  directive explicitly owns and maps them.
- Phase 2B does not allocate diagnostic IDs or exception class names.

### 6.7 Persistent-runtime ownership

- Each inbound delivery will be owned by a future outer adapter and one Kernel
  operation scope.
- Message values move through explicit parameters and return values.
- The Kernel remains transport-neutral and has no dependency on PSR packages.
- Factories are explicit composition dependencies; they are not ambient
  singletons or service locators.
- Successful and failing paths eventually close the owned operation scope
  exactly once.
- Cleanup APIs, simultaneous-failure representation, cancellation, recovery,
  adapter evidence, response emission, and uncertain-runtime retirement remain
  later explicit decisions.

### 6.8 API status

- PHP-FIG interfaces retain their external status; Kongono does not annotate or
  redefine them.
- Future Kongono concrete message and factory types are Public and Experimental
  unless a later directive expressly selects otherwise.
- Internal validation helpers remain Internal.
- No Stable Kongono web API is created by Phase 2B.

---

## 7. Exact documentation scope

### 7.1 Files to create

Create exactly:

```text
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/architecture/http-message-model.md
```

### 7.2 Existing files authorized for modification

Modify exactly:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/package-layout.md
docs/glossary.md
```

Do not modify `docs/adr/0004-microkernel-capability-architecture.md` or
`docs/engineering/error-handling.md`; their accepted Phase 2A bytes remain
present in the worktree and are protected during Phase 2B.

### 7.3 ADR 0044 required contents

ADR 0044 must have:

```text
Title: HTTP Message and Interoperability Architecture
Status: Accepted
Date: 2026-08-16
```

It must include:

1. current eight-package, non-runnable reality;
2. primary-source evidence and version observations;
3. the exact PSR-7, PSR-17, and PSR-15 decisions from Section 4;
4. the exact future package name, namespace, and dependency direction;
5. exact selected server-side PSR-7 and PSR-17 roles;
6. the PSR-15 Phase 2E boundary;
7. message, header, URI, stream, uploaded-file, server-request, diagnostic,
   trust, and persistent-runtime principles;
8. rejected alternatives with rationale;
9. consequences for Phases 2C through 2H;
10. performance and security consequences;
11. explicit current versus planned behavior;
12. what remains open; and
13. related ADRs and documentation.

The `What remains open` section must retain concrete class names, constructors,
defaults, exception inventory and IDs, route grammar, router package, middleware
dispatcher, bootstrap, adapter conversion, parsing limits, proxy trust, response
emission, production failure mapping, cleanup API, simultaneous failures,
testing fixtures, and promotion decisions for their owning phases.

### 7.4 HTTP message model guide required contents

`docs/architecture/http-message-model.md` must be the approachable normative
guide. Include:

- decision summary;
- standards and package versions;
- interface-to-future-role table;
- exact package and dependency graph;
- message copy-on-change and mutable-stream caveat;
- header rules;
- URI rules;
- streams and uploaded files;
- server-request data domains;
- factory injection and no discovery;
- PSR-15 future flow;
- trust, diagnostics, and sensitive-data prohibitions;
- persistent-runtime ownership;
- current versus planned behavior;
- explicit deferrals; and
- links to ADR 0044, ADR 0043, the Phase 2 roadmap, runtime model, security
  policy, package layout, doctrine, API-status guide, error-handling guide, and
  the official standards.

Do not include invented PHP signatures beyond the external interface names.

### 7.5 Reconciliation by file

- `ARCHITECTURE.md`: record the Accepted interface decision and continued lack
  of implementation.
- `README.md`: add a concise Phase 2B status and HTTP message-model link without
  calling Kongono runnable.
- `SECURITY.md`: record header/URI/stream/request trust rules, the mutable-stream
  caveat, and continued adapter/parser absence.
- ADR 0043: amend the formerly open PSR and message-package decisions while
  leaving later runtime decisions open.
- ADR index: add ADR 0044 in exact sequence.
- Architecture overview: show PSR interfaces at the interoperability edge and
  Kongono ownership behind them.
- Phase 2 roadmap: mark 2B as the Accepted architecture decision and keep 2C as
  the first implementation step.
- Runtime model: add explicit message/stream operation-lifetime rules without
  claiming deep immutability or scoped storage.
- Doctrine: record the interface-only adoption and rejection of framework
  substrate/discovery.
- Package layout: record the planned ninth `kongono/http-message` package and
  exact future dependency direction while stating that it does not yet exist.
- Glossary: add or reconcile `HTTP Message`, `PSR-7`, `PSR-15`, `PSR-17`, and
  `HTTP Message Package`, distinguishing external contracts from future Kongono
  implementation.

Do not rewrite historical evidence that accurately described an earlier phase.

### 7.6 Documentation file rules

Every changed or created document must be:

- strict UTF-8 without BOM;
- LF-only;
- terminated by exactly one final LF;
- free of trailing whitespace, NUL, conflict markers, private email, secrets,
  credentials, machine-specific absolute paths, and generated text residue;
- an ordinary default-stream file, not a symlink or reparse point;
- repository-relative and link-clean; and
- explicit about current versus planned behavior.

Use `apply_patch` for repository edits.

---

## 8. Prohibited scope

Phase 2B must not:

- create or modify PHP, tests, fixtures, package READMEs, Composer manifests,
  `composer.lock`, tooling, generated artifacts, or vendor content;
- create `packages/http-message` or any package directory;
- add, remove, update, install, or authorize execution-time use of a dependency;
- run `composer update`, `composer require`, or a lockfile write;
- copy, fork, vendor, or depend on a third-party PSR-7 implementation;
- add Symfony HttpFoundation or any Symfony, Laravel, Guzzle, Laminas, Nyholm,
  Slim, discovery, router, middleware, emitter, or runtime-server package;
- implement or claim PSR conformance;
- implement message values, factories, validation helpers, diagnostics, parsing,
  routing, middleware, bootstrap, adapters, emission, or production failures;
- define client-side HTTP, PSR-18, WebSockets, sessions, cookies policy, forms,
  content negotiation, uploads policy, authentication, authorization, CSRF,
  CORS, proxies, TLS, logging, telemetry, CLI, frontend, ORM, agents, or
  extensions;
- change Kernel, DI, Graph, Diagnostics, Activation, Presentation, or Handler
  contracts;
- introduce ambient current-request state, factory discovery, a service
  locator, or global registry;
- stage, commit, push, tag, branch, add a remote, or mutate history;
- change any Phase 1I or Phase 2A caller record;
- populate the Phase 2B report before every non-report gate passes;
- begin Phase 2C; or
- call Kongono runnable, production-ready, full-featured, 1.0, or PSR compliant.

---

## 9. Gate A - authenticated baseline

Before broad validation or editing, prove:

1. all Section 2.1 authorities were read completely;
2. the independently supplied Phase 2B directive identity matches;
3. the Phase 2B report is the required zero-byte caller placeholder;
4. the accepted Phase 2A directive and report identities match Section 3.2;
5. all Phase 1I caller records match Section 3.2;
6. branch, history, tree, parent, refs, index, counts, and exact `-uall` path
   inventory match Section 3 after caller setup;
7. every Section 3.2 path has the required byte identity before edits;
8. `composer.lock` matches Section 3.3;
9. the 151-entry protected manifest matches Section 3.3;
10. every current status path is an ordinary default-stream non-reparse file;
11. the Phase 2B directive and report are untracked, unstaged, and absent from
    `HEAD`; and
12. no staged path exists.

At any mismatch, stop before `composer quality` and before editing.

---

## 10. Gate B - pre-change quality

Run:

```powershell
composer quality
```

Required result:

```text
security advisories: 0
platform requirements: pass on PHP 8.5.9
PHP CS Fixer: 0 of 138 files fixable
PHPStan: 0 errors
PHPUnit: 254 tests, 4279 assertions
```

Accepted warnings remain limited to missing license metadata, unbounded local
development constraints, and the optional curl performance warning if actually
emitted.

If audit access is blocked only by the managed environment, an unchanged retry
with approved network/cache access is permitted. Do not skip the audit or
weaken the quality script.

After quality, re-prove Gate A. No accepted byte may have changed.

---

## 11. Gate C - documentation implementation

Create and modify only Section 7 paths. Make the smallest coherent prose
changes satisfying Sections 4 through 7.

The executor may choose wording and document organization. The executor may not
change the selected architecture, infer runtime APIs, or expand scope.

Do not populate the report yet.

---

## 12. Gate D - focused architecture and file validation

Before final quality, prove:

1. exactly two new non-caller documentation files were created during Phase 2B;
2. only the eleven Section 7.2 existing documents changed during Phase 2B;
3. all other accepted Phase 2A and caller bytes remain exact;
4. ADR 0044 is Accepted, dated 2026-08-16, and indexed after ADR 0043;
5. PSR-7 through `psr/http-message` 2.x, the selected PSR-17 server factories,
   and PSR-15 are accepted;
6. the exact package identity and dependency direction match Section 5;
7. no third-party implementation or framework substrate is selected;
8. no document claims the interface packages provide an implementation;
9. `RequestFactoryInterface` and PSR-18 remain outside Phase 2;
10. current versus planned behavior is explicit in every relevant document;
11. the mutable-stream and shallow message-immutability caveat is explicit;
12. header CR/LF/NUL rejection and sensitive diagnostic exclusions are explicit;
13. no SAPI-global parsing, routing, middleware, adapter, emission, or runtime
    behavior is claimed;
14. the Kernel remains transport-neutral and PSR-free;
15. factory injection is explicit and discovery/service-location is rejected;
16. all later open decisions remain assigned to their owning phases;
17. `composer.lock` remains exact;
18. the 151-entry protected manifest remains exact;
19. all local Markdown links resolve;
20. strict encoding, whitespace, stream, reparse, ordinary-file, mode, secret,
    and path checks pass; and
21. `git diff --check` passes.

Do not create a validation script or generated evidence file.

---

## 13. Gate E - final quality, Git proof, and report

### 13.1 Final quality

Run the unchanged:

```powershell
composer quality
```

The required result remains:

```text
security advisories: 0
platform requirements: pass on PHP 8.5.9
PHP CS Fixer: 0 of 138 files fixable
PHPStan: 0 errors
PHPUnit: 254 tests, 4279 assertions
```

Then re-prove every Gate D item.

### 13.2 Exact final Git state before report population

Required:

```text
branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree: bfa1908c619069b699bc9b08668db17ea2555eb3
commit count: 9
modified tracked paths: 11
untracked paths with -uall: 12
total status paths with -uall: 23
staged paths: 0
remotes: 0
tags: 0
```

The 12 untracked paths must be exactly:

```text
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/architecture/http-message-model.md
docs/architecture/phase-2-roadmap.md
docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_1I_codex_directive.md
docs/dev_plan/kongono_phase_2A_codex_directive.md
docs/dev_plan/kongono_phase_2B_codex_directive.md
docs/task reports/Phase_1I.md
docs/task reports/Phase_1I_1_correction.md
docs/task reports/Phase_2A.md
docs/task reports/Phase_2B.md
```

The exact 11 modified tracked paths remain the Section 3.2 tracked inventory.
Phase 2B may not restore accepted Phase 2A changes merely because two of those
paths were not edited in this phase.

### 13.3 Completion report

Only after every non-report gate passes, populate:

```text
docs/task reports/Phase_2B.md
```

The report remains untracked, unstaged, absent from `HEAD`, strict UTF-8 without
BOM, LF-only, free of NUL and trailing whitespace, and terminated by exactly
one final LF.

Report population must not change the status-path counts. After population,
return its final byte count and uppercase SHA-256 outside the report body.

---

## 14. Acceptance criteria

Phase 2B is accepted only if all items pass:

1. every required authority was read completely;
2. the independent directive identity and zero-byte report matched;
3. the exact Git baseline and all accepted caller records matched;
4. the protected manifest and lockfile matched;
5. pre-change quality passed with 254 tests and 4,279 assertions;
6. exactly two authorized documentation files were created;
7. only the eleven authorized existing documents changed during Phase 2B;
8. all non-authorized accepted bytes remained exact;
9. ADR 0044 is Accepted, dated, indexed, and complete;
10. PSR-7 through `psr/http-message` 2.x, the selected PSR-17 server factories,
    and PSR-15 are explicitly accepted as interoperability contracts;
11. `kongono/http-message`, `packages/http-message`, and
    `Kongono\Http\Message\` are the exact future first-party boundary;
12. the future dependency graph is exact and acyclic;
13. Kongono retains its own implementation and no framework substrate or
    third-party PSR implementation is selected;
14. the five PSR-7 roles and five PSR-17 server factory roles are exact;
15. `RequestFactoryInterface`, PSR-18, and client HTTP remain out of scope;
16. PSR-15 is assigned to Phase 2E and not implemented;
17. message, header, URI, stream, upload, request-data, trust, diagnostic,
    factory, and persistent-runtime principles are complete and consistent;
18. no document falsely claims deep immutability of body streams;
19. no current-versus-planned contradiction exists;
20. no PHP, test, manifest, lockfile, tooling, package README, dependency, or
    runtime behavior changed;
21. final quality passed with 254 tests and 4,279 assertions;
22. links, encoding, whitespace, streams, reparse points, modes, secrets, and
    file safety passed;
23. Git history, refs, index, protected bytes, and lockfile remained unchanged;
24. nothing was staged or committed;
25. the report was populated only after all non-report gates passed; and
26. Phase 2C was not started.

No partial result is a pass.

---

## 15. Standard executor completion report

Use exactly these headings in this order:

```markdown
# PHASE 2B TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. HTTP INTEROPERABILITY DECISION

## 8. PACKAGE AND DEPENDENCY DECISION

## 9. ARCHITECTURAL DECISIONS

## 10. DEVIATIONS FROM DIRECTIVE

## 11. DEPENDENCIES ADDED OR REMOVED

## 12. ENVIRONMENT

## 13. COMMANDS RUN

## 14. VALIDATION RESULTS

## 15. DOCUMENTATION REVIEW

## 16. SECURITY AND FILE-SAFETY REVIEW

## 17. WARNINGS AND KNOWN ISSUES

## 18. QUESTIONS OR BLOCKERS

## 19. GIT DIFF SUMMARY

## 20. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must include:

- directive and report-placeholder authentication;
- exact baseline and caller-record proof;
- protected-manifest and lockfile proof;
- pre-change and final quality results;
- exact created and modified inventories;
- the selected PSR versions, future package identity, dependency graph, and
  interface roles;
- evidence that no implementation or dependency was added;
- link, encoding, file-safety, Git, and prohibited-scope evidence;
- any accepted environment-only warning; and
- an explicit statement that Phase 2C did not begin.

The final line must be exactly:

```text
Await architect review before Phase 2C.
```

Return the report byte count and uppercase SHA-256 separately after completion.

---

## 16. Executor behavior

- Be an executor, not an architect.
- Read before editing.
- Use ordinal comparisons where this directive requires them.
- Use literal path inventories; do not use wildcards for any mutation.
- Preserve caller-managed dirty state.
- Do not clean up adjacent prose or historical records.
- Do not relax a check because the change is documentation-only.
- Do not turn a framework-interface decision into a dependency installation.
- Do not describe implementation plans as implemented behavior.
- Stop at the first genuine mismatch.

---

## 17. Required final outcome

At successful completion, Kongono has an Accepted, evidence-backed decision to
use PSR-7 through `psr/http-message` 2.x, the selected server-side PSR-17
factories, and PSR-15 as narrow interoperability contracts while retaining a
first-party Kongono HTTP message implementation boundary.

It still has:

- eight implemented first-party packages;
- no `packages/http-message` directory;
- no PSR Composer dependency;
- no HTTP message implementation;
- no router;
- no middleware runtime;
- no bootstrap or composition root;
- no SAPI or runtime adapter;
- no response emitter;
- no generic production web failure model; and
- no runnable web fixture application.

Phase 2C may begin only after architect acceptance of the Phase 2B completion
report and a separate authenticated implementation directive.
