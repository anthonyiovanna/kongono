# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 3F.1
## Documentation Reconciliation and Completion-Evidence Correction

**Status:** Authorized correction only

**Date:** 2026-08-21

**Repository:** `C:\Users\antho\Dev\kongono`
**Executor role:** Executor, not architect

---

## 1. Mission

Correct the two independently verified Phase 3F closure defects without
changing the accepted Phase 3F runtime implementation:

1. several current nonhistorical documents still describe concrete PHP-CGI
   delivery, native capture, permission evidence, production emission, or the
   Phase 3F composition entry as future or unimplemented; and
2. the populated Phase 3F report claims `DEVIATIONS FROM DIRECTIVE: None` even
   though it records an unapproved focused PHPStan retry after a Windows worker
   crash and continuation after a focused style failure under a directive whose
   strict rule said any failure must stop.

Phase 3F.1 authorizes only the exact documentation reconciliation, fresh
sequential validation, and a new correction report that supersedes the
inaccurate completion-evidence claim. Preserve the original Phase 3F report as
historical evidence. Do not alter production PHP, tests, Composer files, the
lockfile, tooling, dependencies, Git history, or any earlier caller record.

Do not begin Phase 3G.

---

## 2. Mandatory authority and authentication

Before validation or mutation, read completely:

```text
AGENTS.md
ARCHITECTURE.md
README.md
SECURITY.md
docs/documentation-standard.md
docs/doctrine.md
docs/architecture/phase-3-roadmap.md
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md
docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md
docs/dev_plan/phase_3/kongono_phase_3F_codex_directive.md
docs/task_reports/phase_3/Phase_3F.md
docs/dev_plan/phase_3/kongono_phase_3F_1_codex_directive_correction.md
```

The caller must independently provide this correction directive's exact byte
count and SHA-256 outside the file. Do not treat the local file as
self-authenticating. If either value is absent or differs, stop before quality
or mutation and report expected and observed values.

The caller alone creates:

```text
docs/dev_plan/phase_3/kongono_phase_3F_1_codex_directive_correction.md
docs/task_reports/phase_3/Phase_3F_1_correction.md
```

The report must begin as an ordinary untracked, unstaged, zero-byte file absent
from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Locked Phase 3F caller identities:

```text
docs/dev_plan/phase_3/kongono_phase_3F_codex_directive.md
bytes:   43927
SHA-256: B9AC0C2D78918BF95E351376788F51082168F35481EAED52278C3FC523D8B71A

docs/task_reports/phase_3/Phase_3F.md
bytes:   21223
SHA-256: 3C4AB076AE5B5A03553C9F9AC9B752F003D7CCFAA76ECF092101C32E0DDD6E90
```

Preserve both byte-for-byte. The Phase 3F report's inaccurate claims are
historical evidence to be superseded by the Phase 3F.1 report, not rewritten.

---

## 3. Exact accepted entry state

Before the caller adds the two Phase 3F.1 caller records, the independently
verified state is:

```text
branch:                         main
HEAD:                           647053d4bddc4c76090bca9bf3d693faa5880c52
modified tracked paths:         30
deleted tracked paths:          185
untracked paths:                249
total status paths:             464
staged paths:                   0
physical Git-visible files:     600
tracked index paths:            536
package directories/manifests: 14 / 14
production PHP files:           124
test PHP files:                 139
style-covered PHP files:        263
phase_3 dev-plan files:         10
phase_3 task-report files:      10
```

After the caller adds the two active Phase 3F.1 records, require exactly:

```text
modified tracked paths:         30
deleted tracked paths:          185
untracked paths:                251
total status paths:             466
staged paths:                   0
physical Git-visible files:     602
tracked index paths:            536
phase_3 dev-plan files:         11
phase_3 task-report files:      11
```

Reconstruct the accepted 600-file Phase 3F state by excluding only the two
active Phase 3F.1 caller records. Use relative forward-slash paths, ordinal
case-sensitive sorting, and one UTF-8-without-BOM record per file:

```text
path<TAB>decimal-byte-count<TAB>uppercase-SHA-256<LF>
```

Require:

```text
records:          600
manifest bytes: 75532
manifest SHA-256: E231921FB8D11E73A2DC63490DCBB694263ADC03831E349EFDB8AFD04E51B673
aggregate bytes: 6611361
first path:       .editorconfig
last path:        tests/Tooling/EnvironmentTest.php
```

The 204-record historical numbered archive must remain:

```text
records:          204
manifest bytes: 25591
manifest SHA-256: A2690631F47C2631E9A0F01E5B9CD0982FD6CBBDAFA2AB6BFFEA8C0F4FFA1D69
aggregate bytes: 4157431
```

The protected set is the accepted 600-file state excluding only the ten
authorized documentation targets in Section 5. Require it before and after the
correction:

```text
records:          590
manifest bytes: 74537
manifest SHA-256: 877E9769C3B7B9F308E565E8E19FDA9A484C256D346B3D5453AE0CAE1FD29EE0
aggregate bytes: 6369904
first path:       .editorconfig
last path:        tests/Tooling/EnvironmentTest.php
```

Also require:

- semantic index equal to `HEAD` and zero staged paths;
- one local branch named `main`, no remotes, no tags, and no unexpected refs
  beyond application-managed `refs/codex/**`;
- no non-default streams, reparse points, nested repositories, or mode changes;
- normal PHP `memory_limit=128M` and absent `PHP_INI_SCAN_DIR`;
- `composer.lock` exactly 197527 bytes with SHA-256
  `823D849823E7403BD5F45C3DD85BAC17974D2FC66D004D454D9D06FE5AA1EC84`;
- the Phase 3F production source and ADR identities below remain exact:

```text
packages/php-cgi/src/Delivery/CgiWebDelivery.php
8293 bytes
A6D324B988ABC3EB33780FF52BFD2740EC4F1909C6D3EF3EC28C856BA7D90E83

packages/php-cgi/src/Entry/CgiWebEntry.php
535 bytes
CB728FFBC31662C69C4FB358DE1BD6D57D6CCB7C64D8995304AE2C1B5D5ADF60

packages/php-cgi/src/Exception/CgiDeliveryCleanupException.php
1365 bytes
E2732A136973221B3B5C89A70CCF98C1C76A54124D44F3FE9B2C7DA3D8CC5443

docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md
8159 bytes
EFBEECF295080BBFE39729D59D7059DD4E6A673E98B14149CF2046E83E6218E0
```

If any entry invariant differs, stop before validation or mutation. Do not
repair, normalize, restore, stage, or adopt an observed mismatch as authority.

---

## 4. Accepted implementation decision

The Phase 3F PHP implementation is accepted and frozen for this correction.
Phase 3F.1 does not reopen its architecture.

The accepted current state is:

- `CgiWebDelivery` is the Internal concrete one-delivery PHP-CGI implementation
  of Web Runtime's Public delivery port;
- `CgiWebEntry` is the Experimental thin entry that supplies one fresh delivery
  to one already-composed `WebRuntimeAdapter`;
- Phase 3E supplies the bounded literal operator bootstrap and exact
  current-delivery evidence evaluation;
- Phase 3F supplies bounded native capture, lazy input acquisition, conversion,
  emission, and explicit cleanup;
- Web Runtime owns failure selection, one send, and one close;
- Web Application alone owns the Kernel operation scope;
- PHP CGI depends directly on Web Runtime and not directly on Web Application;
- Phase 3G alone remains next for bounded front-controller failure/shutdown
  composition and deployment constraints; and
- Phase 3H remains external CGI/FastCGI acceptance.

Do not claim that Phase 3F is a listener, server, FastCGI protocol parser,
process manager, general SAPI adapter, deployment solution, hardening
certification, external acceptance, complete framework, Stable API, or 1.0.

---

## 5. Exact correction boundary

Modify exactly these ten existing documentation files and no others:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/architecture/runtime-model.md
docs/architecture/web-runtime-model.md
docs/engineering/error-handling.md
docs/glossary.md
packages/diagnostic-activation/README.md
packages/diagnostic-handler/README.md
```

Their accepted starting identities are:

```text
ARCHITECTURE.md	23630	3B62891728838478C1729271B634DA74A90A62BBA6483FFA2F73364ED1E62A25
README.md	16411	83099B52B46911089E411767E152C9D311FF1CD4A6D5DC7D069499B5F928A7AF
SECURITY.md	25512	AF00DE56405D1D6AE4998A3A9BC879BDA5F3D57497783587152A04E53749747C
docs/architecture/php-cgi-fastcgi-profile.md	23292	4B96ACF5D9F3A7DD6F751C6132167D3E725B22633D7B5FD5A21BBCD1A9128B1D
docs/architecture/runtime-model.md	16037	3D780C8D820332EDCC165FBB20BE01EAB239E0A9A505F23BB1FCA6678E1135A4
docs/architecture/web-runtime-model.md	6864	791194E68746DE2F6D7005F3A5042AEB5400E36C3EFF37DE4688A7E6E81F0BEE
docs/engineering/error-handling.md	79396	FC72907797BCCB3F36DADFFF8BAF41654FFDD42EDD57242AE3B9CD005964D2B1
docs/glossary.md	36491	6E9EEB31BE0D8844FF9159990E819597C9050085C903646345485338C9C4FD41
packages/diagnostic-activation/README.md	7181	A820AAD70BA9FDE4A4CCEAB9BA95D73CB438F89FC1F11C45DCF1A82F182A0647
packages/diagnostic-handler/README.md	6643	61FE21F099E58D58583C51C055AEB359B4D86D9AB0610133149B226DF6ADA3D3
```

Each of the ten files must receive a real, narrow reconciliation. Do not add a
new ADR, modify ADR 0056, rewrite historical Phase directives/reports, or touch
another path.

### 5.1 Required reconciliation by file

`ARCHITECTURE.md`

- Replace the current conceptual diagram's `future production PHP CGI
  delivery` with the implemented Phase 3F delivery/entry composition.
- Replace the statement that a future production host will construct and
  supply the concrete delivery. State that operator-owned composition now
  builds the application/runtime and invokes `CgiWebEntry`, while the selected
  host still owns listener, process, deployment, and shutdown policy.

`README.md`

- Reconcile both current paragraphs that still say raw operator/evidence
  evaluation, production-host conversion, or emission remain unimplemented.
- State exactly that Phase 3E implements the bounded literal operator and
  delivery-evidence inputs and Phase 3F implements concrete host capture,
  conversion, emission, and cleanup. Keep general configuration, deployment
  hardening, and external acceptance future.

`SECURITY.md`

- Reconcile every current statement that still calls concrete PHP-CGI delivery
  future or unimplemented, including the passages containing `future leaf
  delivery`, `future PHP CGI delivery`, and `concrete PHP CGI delivery remain
  unimplemented`.
- State that Phase 3F's delivery owns bounded ambient capture, exact conversion,
  response emission, cleanup, and current-delivery permission evidence; Phase
  3E owns the bounded operator decision.
- State that Phase 3F package tests prove local sequential isolation, while
  external host reuse, deployment hardening, and served acceptance remain for
  Phase 3G/3H.
- Do not weaken the rule that Production denies trusted diagnostics, client
  input cannot authorize them, and uncertain evidence fails closed.

`docs/architecture/php-cgi-fastcgi-profile.md`

- Replace the `future kongono/php-cgi delivery` diagram label with the current
  `CgiWebDelivery` boundary.
- Replace `Phase 3F will own concrete delivery and composition` with implemented
  current state and Phase 3G as next.
- Reconcile the diagnostic range from `1001` through `1017` to the implemented
  `1001` through `1018`, including the closed cleanup token without changing
  existing diagnostic doctrine.

`docs/architecture/runtime-model.md`

- Reconcile every current statement saying native capture, outer-adapter
  composition, transport delivery, response behavior, or every adapter remains
  unimplemented.
- Replace future-adapter wording where it now describes the implemented PHP-CGI
  and Web Runtime path. Preserve generic future-adapter doctrine for other
  transports only when explicitly distinguished from the current PHP-CGI path.
- State that broader server/listener/process/deployment ownership and external
  acceptance remain future.

`docs/architecture/web-runtime-model.md`

- Replace the statement that the concrete delivery, capture, Web Runtime
  composition, and entrypoint remain Phase 3F work with the exact implemented
  Phase 3F state and Phase 3G/3H limits.

`docs/engineering/error-handling.md`

- Reconcile present-tense `future outer boundary` and `future outer adapter`
  claims that describe the now-implemented PHP-CGI/Web Runtime path.
- Clearly label Phase 2 wording as historical design where retained.
- State that generic/trusted Web Runtime response selection and exact PHP-CGI
  send/close are implemented, while incident storage, logging, localization,
  broader formats, deployment hardening, and external acceptance remain open.

`docs/glossary.md`

- Replace `Concrete delivery composition remains Phase 3F work.` with the
  current Phase 3F implementation and the exact Phase 3G/3H boundary.

`packages/diagnostic-activation/README.md`

- Replace the statement that concrete delivery will own fresh capture and
  authority with the Phase 3F current delivery behavior. Preserve that the
  enum itself owns no evidence or authority.

`packages/diagnostic-handler/README.md`

- Replace the present-tense statement that a future outer adapter must
  construct fresh delivery permission where it describes the selected PHP-CGI
  path. State that Phase 3F plus Web Runtime now perform that explicit
  per-delivery composition, while other adapters remain future and Handler
  itself still owns none of it.

### 5.2 Closed wording outcomes

After editing, current nonhistorical documentation must contain no unresolved
claim that:

```text
concrete delivery composition remains Phase 3F work
Phase 3F will own concrete delivery and composition
concrete WebDeliveryInterface implementation remains Phase 3F work
future PHP CGI delivery must later establish current evidence
concrete PHP CGI delivery remains unimplemented
a future leaf delivery will own ambient PHP CGI capture or emission
```

Historical ADR context may retain phase-relative future wording only when the
same ADR clearly contains a later integration-status section that supersedes
it. This exception does not permit stale current wording in the ten files above.

Preserve the fixed Phase 3 A-H roadmap table byte-for-byte and keep Phase 3G
alone next.

---

## 6. Explicitly prohibited scope

Do not:

- modify any PHP source or test;
- modify Composer manifests, `composer.lock`, tooling, configuration, or
  dependencies;
- modify ADRs, historical directives, historical reports, or the original
  Phase 3F report;
- add a runtime API, entry script, listener, server, deployment file,
  configuration mechanism, container wiring, route, middleware, handler, CLI,
  ORM, template engine, telemetry, agent, extension, or later capability;
- stage, commit, amend, reset, restore, clean, tag, branch, update refs, or alter
  history;
- run a formatter that writes files or perform broad mechanical rewriting;
- suppress, ignore, baseline, skip, weaken, or retry an unapproved failing
  validation; or
- begin Phase 3G.

---

## 7. Validation gates

### Gate A - authentication and preservation

Require Sections 2 and 3 to pass before mutation.

### Gate B - exact documentation delta

After editing require:

- exactly the ten Section 5 paths differ from their authenticated starting
  identities because of Phase 3F.1;
- every other file matches the 590-record protected manifest;
- all Section 5.2 stale outcomes are absent from current nonhistorical docs;
- every current statement distinguishes implemented 3E/3F behavior from future
  3G/3H work;
- the fixed Phase 3 A-H roadmap table is byte-identical;
- local links resolve;
- strict UTF-8 without BOM, LF-only, one final LF, no trailing whitespace, and
  no NUL;
- ordinary default-stream files, no reparse points, no nested repositories, and
  unchanged modes; and
- `git diff --check` passes.

### Gate C - fresh sequential focused proof

The Phase 3F report's crashed parallel PHPStan attempt is not accepted as a
clean formal gate. Run the exact focused commands sequentially:

```powershell
php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/php-cgi tests/Architecture
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff packages/php-cgi tests/Architecture
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/php-cgi/tests
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
```

Require zero PHPStan errors, zero style findings, and complete test success.
Run no parallel wrapper. If any command crashes, fails, reports a finding, or
returns a nonzero status, stop immediately, preserve the reached state, leave
the Phase 3F.1 report zero bytes, and request the smallest authenticated
correction. No retry is authorized at Gate C.

### Gate D - canonical quality

Run literal:

```powershell
composer quality
```

Require validation, locked audit with zero advisories, platform pass, zero
style findings, zero PHPStan errors, and complete PHPUnit success. If and only
if the unchanged locked audit fails solely because the managed environment
cannot access its normal cache or Packagist, repeat the identical command once
with approved network/cache access. No other retry, alternate command, memory
overlay, suppression, baseline, skip, or policy change is authorized.

Normal PHP must remain at 128M and `PHP_INI_SCAN_DIR` must remain absent.

### Gate E - final preservation

Before report population require:

```text
modified tracked paths:         30
deleted tracked paths:          185
untracked paths:                251
total status paths:             466
staged paths:                   0
physical Git-visible files:     602
tracked index paths:            536
package directories/manifests: 14 / 14
production PHP files:           124
test PHP files:                 139
style-covered PHP files:        263
phase_3 dev-plan files:         11
phase_3 task-report files:      11
```

Reverify:

- branch, `HEAD`, history, refs, index, lockfile, package counts, modes, streams,
  caller records, and 204-record historical archive;
- the exact 590-record protected manifest;
- the original Phase 3F directive and report identities;
- all frozen production/test/ADR bytes;
- only the ten authorized docs changed during Phase 3F.1; and
- no staged path or commit.

### Gate F - correction report

Only after Gates A through E pass, populate exactly:

```text
docs/task_reports/phase_3/Phase_3F_1_correction.md
```

Use these exact headings in order:

```text
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. DOCUMENTATION RECONCILIATION
## 8. PHASE 3F IMPLEMENTATION PRESERVATION
## 9. COMPLETION-EVIDENCE CORRECTION
## 10. ARCHITECTURAL DECISIONS
## 11. DEVIATIONS FROM DIRECTIVE
## 12. DEPENDENCIES ADDED/REMOVED
## 13. ENVIRONMENT
## 14. COMMANDS RUN
## 15. VALIDATION RESULTS
## 16. SECURITY AND FILE-SAFETY REVIEW
## 17. WARNINGS / KNOWN ISSUES
## 18. QUESTIONS / BLOCKERS
## 19. FINAL GIT STATUS
## 20. ACCEPTANCE CRITERIA
## 21. NEXT STEP
```

The report must state explicitly:

- Phase 3F.1 supersedes only the inaccurate completion-evidence and stale
  current-documentation claims in the original Phase 3F report;
- the original Phase 3F executor continued after a focused PHPStan worker crash
  and a focused style finding even though the original strict stop rule did not
  authorize those continuations;
- those actions are historical deviations from Phase 3F and may not be reported
  as `None`;
- Phase 3F.1 does not retroactively claim the actions complied; instead it
  supplies fresh authenticated sequential proof under this correction;
- the Phase 3F PHP implementation, tests, dependency edge, lockfile, ADR 0056,
  and original report bytes were preserved;
- exact focused and canonical command results and counts;
- exact final Git/inventory state; and
- no Phase 3G work began.

For `## 11. DEVIATIONS FROM DIRECTIVE`, report deviations from this Phase 3F.1
directive only. If none occurred, write `None.` Then separately retain the
required historical Phase 3F deviation disclosure in Section 9.

The final next step must be exactly:

```text
Await architect review before Phase 3G.
```

After report population, reverify its headings, UTF-8/LF/file safety, all final
state invariants, and compute its byte count and SHA-256 independently. Return
those two values outside the report body. Do not place self-referential identity
values inside it.

---

## 8. Strict acceptance criteria

All must pass:

1. Independent Phase 3F.1 directive authentication matched.
2. Every required authority was read completely.
3. Entry Git state, caller records, 600-file accepted manifest, 204-record
   historical archive, 590-record protected manifest, lockfile, and frozen
   source/ADR identities matched.
4. Exactly ten existing documentation files changed; no other accepted path
   changed.
5. All current stale Phase 3F future/unimplemented statements in Section 5 were
   reconciled.
6. Implemented 3E/3F behavior and future 3G/3H boundaries are stated accurately.
7. Security doctrine, Production denial, two independent permissions, and
   persistent-runtime isolation were not weakened.
8. The fixed Phase 3 A-H roadmap table is byte-identical.
9. Phase 3F production PHP, tests, ADR 0056, dependency edge, Composer files,
   lockfile, and tooling are unchanged.
10. Fresh focused PHPStan ran sequentially with zero errors and no retry.
11. Fresh focused style reported zero findings and no retry.
12. Focused package and architecture PHPUnit passed completely.
13. Canonical quality passed under the exact retry policy.
14. Links, UTF-8, LF, final LF, whitespace, NUL, ordinary-file, stream, reparse,
    mode, and `git diff --check` proofs passed.
15. Final status is exactly `30 modified / 185 deleted / 251 untracked / 466
    total / 0 staged`, with 602 physical files and 536 tracked paths.
16. The Phase 3F original report remains byte-identical and is explicitly
    superseded by the truthful Phase 3F.1 completion-evidence record.
17. No staging, commit, ref/history change, prohibited implementation, or Phase
    3G work occurred.
18. Only the Phase 3F.1 report was populated after every non-report gate passed.
19. The correction report has the exact structure, truthful evidence, exact
    next step, and separately returned byte/hash identity.

Any failure means Phase 3F remains open. Preserve the exact reached state, leave
the Phase 3F.1 report zero bytes unless every non-report gate passed, and request
the smallest authenticated correction. Do not begin Phase 3G.

---

## 9. Final instruction

Execute Phase 3F.1 only. Correct current documentation and completion evidence;
do not reopen or alter the accepted runtime implementation.

Authenticate first. Stop on every unapproved mismatch or validation failure.
Do not stage, commit, or begin Phase 3G.

Stop after returning the populated Phase 3F.1 report's independently computed
byte count and SHA-256.
