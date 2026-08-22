# PHASE 2E TASK COMPLETION REPORT

## 1. STATUS

Complete.

All 30 acceptance criteria in the authenticated Phase 2E directive pass. The
report was kept at zero bytes until every non-report gate passed. No Git staging,
commit, history, branch, tag, remote, ref, fetch, merge, rebase, reset, restore,
clean, stash, or push operation was performed.

## 2. SUMMARY

Phase 2E implements the eleventh first-party package, `kongono/http-server`, in
the exact `Kongono\Http\Server\` namespace. It installs and uses the PSR-15
handler and middleware interfaces to provide:

- an explicit immutable pipeline of zero through 128 declaration-ordered
  middleware components around one terminal handler;
- a complete canonical route-name-to-handler map over the existing Phase 2D
  routing boundary;
- reserved `RouteMatch::class` request-attribute projection for selected
  matches, without upgrading trust or authorization;
- explicit delegation of unmatched requests without choosing response status,
  headers, or body; and
- three bounded package-owned construction diagnostics.

The implementation adds no bootstrap, Kernel web lifecycle, adapter, transport,
emitter, supplied error middleware, generic production failure response, or
runnable application. Phase 2F is next. Phase 2H remains the first runnable
black-box milestone.

## 3. BASELINE VERIFIED

- The directive was read completely only after its independently supplied
  identity matched exactly: 41,768 bytes and SHA-256
  `53CD9AA94E6F5A2518E94C4FB1FAA9E3461C5E87A13C02E923BF4F0F38CF270A`.
- All 29 authority files were read completely in the mandated order.
- The caller-managed report prerequisite was an ordinary, untracked, unstaged,
  HEAD-absent, zero-byte file with SHA-256
  `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`.
- Git baseline: branch `main`; HEAD
  `d7846f137a1d53f5306f48d19196b916fde44ac2`; tree
  `bfa1908c619069b699bc9b08668db17ea2555eb3`; parent
  `8fe793b124384744ac43887037360154c149241a`; 9 commits; one local branch;
  zero remotes; zero tags; 39 `refs/codex/*` refs; no unexpected refs.
- Tracked baseline: 358 files, all mode `100644`.
- Status baseline: 18 tracked modifications, 89 untracked paths, 107 total
  status paths, and 0 staged paths.
- Inventory baseline: 10 package directories, 10 package manifests, 80
  production PHP files, 104 test PHP files, 184 PHP CS Fixer files, and 106
  unique production diagnostic IDs.
- Index baseline: 44,541 bytes, SHA-256
  `768B60DC6F0E2C2A9C4040C2A48EB78035A37713AF482649B16C72592BD0AC2B`.
- Every preexisting Git-visible file was snapshotted by path, byte count, and
  SHA-256 before mutation: 447 files total.
- File-safety baseline found ordinary files only, with no reparse point,
  alternate stream, invalid mode, NUL, or invalid UTF-8 defect.
- The unchanged baseline `composer quality` passed after an identical
  environment-authorized retry: 387 tests, 6,721 assertions, 0/184 style
  findings, PHPStan clean, platform clean, and no security advisories.
- Existing Composer warnings were recorded: no license and the already
  authorized unbounded local monorepo constraints.

## 4. CALLER RECORDS

The immediate caller records were independently reverified before report
population and retained exact bytes:

| Record | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_2D_codex_directive.md` | 54,316 | `7A0F803802704EA8E95B7DFA8E4A49809264493CC941686648621079EBEA58FF` |
| `docs/task reports/Phase_2D.md` | 26,060 | `834582F8A0A18510F72F89F5800BA2CA9DDE460A5DAF3D780B4F81AAD0367EAD` |
| `docs/dev_plan/kongono_phase_2D_1_codex_directive_correction.md` | 23,051 | `1C745CDC6F4F920EE67355FD2C2525A738631A7FB8A11ABB58587AC56DD03A92` |
| `docs/task reports/Phase_2D_1_correction.md` | 19,893 | `9880FBB39CB2C7B6A659C51F468633A64D8DC82E5A11BAB7A2E97B7FFA8A9580` |
| `docs/dev_plan/kongono_phase_2E_codex_directive.md` | 41,768 | `53CD9AA94E6F5A2518E94C4FB1FAA9E3461C5E87A13C02E923BF4F0F38CF270A` |

## 5. FILES CREATED

Exactly these 16 authorized paths were created:

1. `packages/http-server/composer.json`
2. `packages/http-server/README.md`
3. `packages/http-server/src/Diagnostic/HttpServerDiagnostic.php`
4. `packages/http-server/src/Exception/ExceptionInterface.php`
5. `packages/http-server/src/Exception/InvalidHttpServerDefinitionException.php`
6. `packages/http-server/src/Internal/MiddlewareLayer.php`
7. `packages/http-server/src/MiddlewarePipeline.php`
8. `packages/http-server/src/RoutingRequestHandler.php`
9. `packages/http-server/tests/Fixture/CallbackMiddleware.php`
10. `packages/http-server/tests/Fixture/CallbackRequestHandler.php`
11. `packages/http-server/tests/Unit/Diagnostic/HttpServerDiagnosticTest.php`
12. `packages/http-server/tests/Unit/Exception/HttpServerExceptionTest.php`
13. `packages/http-server/tests/Unit/MiddlewarePipelineTest.php`
14. `packages/http-server/tests/Unit/RoutingRequestHandlerTest.php`
15. `docs/adr/0047-explicit-psr-15-middleware-and-terminal-routing.md`
16. `docs/architecture/http-server-model.md`

No extra production, test, documentation, fixture, configuration, generated, or
convenience path was created.

## 6. FILES MODIFIED

Exactly these 24 authorized existing paths changed:

1. `.php-cs-fixer.dist.php` — includes HTTP Server source and tests.
2. `ARCHITECTURE.md` — records the eleventh package and implemented Phase 2E boundary.
3. `README.md` — reconciles current status and related package/model links.
4. `SECURITY.md` — records match trust, delegation, and still-absent outer boundaries.
5. `composer.json` — adds the local package, test namespace, and package validation entry.
6. `composer.lock` — resolves the exact local package and PSR-15 interface dependencies.
7. `docs/adr/0004-microkernel-capability-architecture.md` — adds a historical current-state amendment.
8. `docs/adr/README.md` — indexes Accepted ADR 0047.
9. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md` — records completed 2E and remaining 2F-2H work.
10. `docs/adr/0044-http-message-and-interoperability-architecture.md` — preserves the historical decision and adds the 2E amendment.
11. `docs/adr/0046-deterministic-route-declaration-and-matching.md` — records the separate 2E execution boundary.
12. `docs/architecture/http-message-model.md` — reconciles installed and used PSR-15 contracts.
13. `docs/architecture/overview.md` — reconciles current package topology and capabilities.
14. `docs/architecture/phase-2-roadmap.md` — marks 2E complete and 2F next.
15. `docs/architecture/routing-model.md` — records terminal mapping without changing routing ownership.
16. `docs/architecture/runtime-model.md` — records long-lived PSR-15 object and explicit-call-flow behavior.
17. `docs/doctrine.md` — reconciles the implemented eleventh package and remaining exclusions.
18. `docs/engineering/api-status.md` — updates the exact 86-declaration status inventory.
19. `docs/engineering/error-handling.md` — adds the marker, exact diagnostics, propagation, and exclusions.
20. `docs/engineering/package-layout.md` — updates the exact package graph and tooling integration.
21. `docs/glossary.md` — defines the HTTP Server package and current PSR-15 use.
22. `phpstan.neon.dist` — includes HTTP Server source and tests.
23. `phpunit.xml.dist` — includes the HTTP Server package tests.
24. `tests/Architecture/ApiStatusConventionTest.php` — enforces exact inventory, status, dependency, diagnostic, namespace, and shape contracts.

This report is the only file populated after the non-report gates passed.

## 7. ARCHITECTURAL DECISIONS

None beyond the directive-authorized Accepted ADR 0047. No unresolved
architectural decision was inferred or implemented.

## 8. PACKAGE AND DEPENDENCY CONTRACT

- Composer identity: `kongono/http-server`.
- Production namespace: `Kongono\Http\Server\` mapped to `src/`.
- Test namespace: `Kongono\Http\Server\Tests\` mapped to `tests/`.
- Direct requirements are exactly PHP `^8.5`, API Metadata `*`, Diagnostics `*`,
  Routing `*`, `psr/http-message:^2.0`, `psr/http-server-handler:^1.0`, and
  `psr/http-server-middleware:^1.0`.
- Production has no dependency on Kongono HTTP Message concrete classes,
  Kernel, Application Graph, Dependency Injection, Diagnostic Activation,
  Diagnostic Presentation, or Diagnostic Handler.
- No existing package depends on HTTP Server. The package graph remains acyclic.
- Root Composer, autoload-dev, package validation, PHPUnit, PHPStan, and style
  configuration include HTTP Server.
- Strict optimized autoload generated 3,181 classes with no PSR-4 warning.
- Production contains exactly six declarations with the exact statuses: Public
  marker; Experimental pipeline, routing handler, and concrete exception;
  Internal layer and diagnostic catalog.

## 9. MIDDLEWARE PIPELINE CONTRACT

- `MiddlewarePipeline` is final and readonly, implements
  `RequestHandlerInterface`, and has exactly the authorized three private
  properties and public constructor/accessors/handler shape.
- Construction accepts one explicit terminal handler plus a detached list of
  zero through 128 middleware objects.
- More than 128 layers fail before layer construction with
  `KGN-HTTP-SERVER-1001` and bounded counts.
- The chain compiles once by decorating backward from the last declared
  middleware. Request flow follows declaration order and response unwinding
  follows reverse order.
- Empty, ordered, transformed-request, transformed-response, short-circuit,
  repeated-next, detachment, bound, propagation, and reuse behavior are tested.
- Handling has no mutable queue, cursor, replacement chain, discovery, sorting,
  container lookup, ambient state, I/O, or catch boundary.

## 10. TERMINAL ROUTING CONTRACT

- `RoutingRequestHandler` is final and readonly, implements
  `RequestHandlerInterface`, and owns the exact reserved key
  `RouteMatch::class`.
- Construction receives one `RouteTable`, one handler map, and one unmatched
  handler. Key-set completeness and absence of extras are checked before value
  inspection.
- Valid handlers are inspected and stored in canonical route-name order,
  independent of caller array order.
- Missing/extra keys use `KGN-HTTP-SERVER-1002`; a non-handler value uses
  `KGN-HTTP-SERVER-1003` with canonical position and closed token
  `not_request_handler`.
- A selected handler receives exactly one derived request decoration at the
  reserved key. A preexisting caller value is overwritten without inspection.
- The fresh match and encoded parameters remain untrusted and establish no
  identity, authentication, authorization, permission, or diagnostic eligibility.
- An ordinary unmatched request is delegated unchanged to the explicit
  unmatched handler. HTTP Server chooses no status, headers, body, redirect, or
  method-not-allowed representation.
- Matcher, request accessor, request decoration, selected-handler, unmatched-
  handler, middleware, and response failures propagate unchanged by identity.

## 11. DIAGNOSTIC AND SECURITY REVIEW

- Framework diagnostic inventory increases exactly from 106 to 109 unique
  production IDs with no duplicate.
- `KGN-HTTP-SERVER-1001` contains required Safe integers
  `middleware_count` and `maximum_middleware`.
- `KGN-HTTP-SERVER-1002` contains required Safe integers `route_count`,
  `handler_count`, `missing_count`, and `unexpected_count`.
- `KGN-HTTP-SERVER-1003` contains required Safe integer `handler_position` and
  required Safe text `violation`, closed to `not_request_handler`.
- Diagnostic metadata has exact ID, title, remediation, operation, invariant,
  field order, kinds, disclosures, and requiredness.
- No raw route name, method, template, path, parameter, request, response,
  middleware, handler, callable, object, exception message, credential, secret,
  or unbounded caller value enters diagnostic context.
- The package-local Public marker remains separate from every sibling marker;
  no universal catch boundary was introduced.
- There is no supplied error middleware, generic production response, trusted-
  diagnostic delivery, or authorization inference.

## 12. PERSISTENT-RUNTIME REVIEW

- Pipeline and routing-handler instances may be reused across calls without
  storing a current request, response, match, parameter, exception, permission,
  principal, tenant, operation, or middleware cursor.
- The pipeline retains only its detached declaration list, terminal handler,
  and compiled immutable entry chain.
- The routing handler retains only the immutable route table, matcher,
  canonical detached handler map, and unmatched handler.
- Consumer middleware and handlers remain shallow caller-owned object
  references; the implementation does not claim those objects are immutable,
  reentrant, or concurrency-safe.
- No global, static, singleton, container-current, thread-local, fiber-local,
  previous-operation, SAPI, environment, filesystem, network, clock, random,
  logging, telemetry, or I/O state is used.
- Bootstrap, Kernel operation-scope ownership and cleanup remain Phase 2F work.

## 13. DEVIATIONS FROM DIRECTIVE

None.

Environment-limited commands were retried unchanged exactly as authorized. No
policy, assertion, baseline, suppression, dependency rule, or quality gate was
weakened or skipped.

## 14. DEPENDENCIES ADDED/REMOVED

The mandated command `composer update kongono/http-server --with-dependencies
--no-interaction` made exactly three immediate installations:

| Package | Resolved version/reference |
| --- | --- |
| `kongono/http-server` | `dev-main` / `7a29fe13bdf929ce218dedc5525603ffbe82fc3f` |
| `psr/http-server-handler` | `1.0.2` / `84c4fb66179be4caaf8e97bd239203245302e7d4` |
| `psr/http-server-middleware` | `1.0.2` / `c1481f747daaa6a0782775cd6a8c26a1bf4a3829` |

Removed: none. Updated existing resolved package versions/references: none.

The caller-managed preexisting dirty lock state already contained the Phase 2C
and 2D packages; Composer explicitly reported their references were not
refreshed. Relative to committed HEAD, the cumulative lock delta contains seven
additions: Kongono HTTP Message, Routing, and HTTP Server; PSR HTTP Message,
HTTP Factory, HTTP Server Handler, and HTTP Server Middleware. This phase added
only the authorized latter local package and two PSR-15 interfaces.

`composer.lock` changed from 188,589 bytes / SHA-256
`B058A44C1AE4087203B85319273B5B5417975954E4FCA1A95B50291DB3AF24FA`
to 193,651 bytes / SHA-256
`521CFB7BACF023C463B95C7146D6077CC34BD0871E2DFEFB3807DA89CD4552DC`.
The content hash changed from `bbdabd04715b1f806e7552b5aa1e4bad` at HEAD to
`2ef926aca39c3047e0746eba1fffab61`; cumulative HEAD comparison found zero
removals and zero changed existing package versions/references.

## 15. ENVIRONMENT

- OS/shell: Windows PowerShell.
- PHP: 8.5.9 ZTS, Zend Engine 4.5.9, OPcache 8.5.9.
- Composer: 2.10.2.
- PHPUnit: 13.3.0.
- PHPStan: 2.2.8.
- PHP CS Fixer: 3.95.18.
- Git branch and checkpoint: `main` at
  `d7846f137a1d53f5306f48d19196b916fde44ac2`.

## 16. COMMANDS RUN

Authority, identity, preservation, inventory, and source review used read-only
`Get-Content`, `Get-Item`, `Get-FileHash -Algorithm SHA256`, `Get-ChildItem`,
`Test-Path`, `Resolve-Path`, `Select-String`, `rg`, and these Git forms:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' status --porcelain=v1 -uall
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --cached --others --exclude-standard
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --stage
git -c safe.directory='C:/Users/antho/Dev/kongono' rev-parse HEAD
git -c safe.directory='C:/Users/antho/Dev/kongono' rev-parse 'HEAD^{tree}'
git -c safe.directory='C:/Users/antho/Dev/kongono' rev-parse HEAD^
git -c safe.directory='C:/Users/antho/Dev/kongono' rev-list --count HEAD
git -c safe.directory='C:/Users/antho/Dev/kongono' for-each-ref --format='%(refname)'
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --check
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --summary
```

Mutation and mandated validation commands were:

```powershell
composer quality
composer update kongono/http-server --with-dependencies --no-interaction
composer dump-autoload --optimize --strict-psr
php vendor/bin/phpunit --configuration phpunit.xml.dist packages/http-server/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist --display-phpunit-notices packages/http-server/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/http-server/src packages/http-server/tests tests/Architecture/ApiStatusConventionTest.php
composer cs
composer analyse -- --memory-limit=512M
composer quality
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --check
```

Each of the 14 new or modified PHP/configuration files was also passed to its
own `php -l <path>` invocation. `php --version`, `composer --version`, and each
tool's version command recorded the environment. No prohibited Git mutation
command was run.

## 17. VALIDATION RESULTS

Final clean outcomes:

| Gate | Result |
| --- | --- |
| Strict optimized Composer autoload | PASS; 3,181 classes; no PSR-4 warning |
| HTTP Server focused PHPUnit | PASS; 23 tests, 205 assertions |
| HTTP Server focused PHPUnit with notices displayed | PASS; 23 tests, 205 assertions; no notice/risk/skip/incomplete/output |
| Root API-status architecture test | PASS; 6 tests, 1,797 assertions |
| Exact focused PHPStan command | PASS; no errors |
| PHP CS Fixer | PASS; 0 of 196 files fixable |
| Full PHPStan with 512 MB | PASS; no errors |
| Canonical `composer quality` | PASS; 410 tests, 7,275 assertions; 0/196 style; PHPStan clean; platform clean; no advisories |
| Individual PHP lint | PASS; 14/14 files |
| `git diff --check` | PASS; no output |
| Local Markdown links | PASS; 235 links checked across 21 affected Markdown files; 0 broken |
| Encoding and filesystem safety | PASS; 463 Git-visible files; 0 NUL, invalid UTF-8, reparse point, alternate stream, or bad tracked mode |

Observed implementation iterations, all resolved before final validation:

- The first sandboxed baseline quality and Composer update attempts could not
  use the Composer cache/network; each identical authorized retry passed.
- Initial focused tests exposed invalid test setup and one PHPUnit notice; the
  tests were corrected without changing the production contract.
- Initial focused PHPStan exhausted its default 128 MB worker and then exposed
  12 genuine test/static-analysis issues at 512 MB. The issues were corrected;
  both the directive's exact default-memory focused command and the full 512 MB
  command now pass.
- Initial style validation found two import issues; both were corrected.
- The first final quality attempt was sandbox-blocked at the locked audit. The
  first unchanged authorized retry reached Packagist but received HTTP/2 502.
  The second unchanged authorized retry passed with no advisories.

No failed non-report gate remains.

## 18. DOCUMENTATION REVIEW

- Accepted ADR 0047 records package ownership, dependency direction, official
  PSR-15 evidence, immutable decoration, the 128-layer bound, ordering,
  complete mapping, reserved attribute, unmatched delegation, unchanged
  propagation, persistent-runtime consequences, rejected alternatives, and
  exact 2F-2H deferrals.
- The HTTP Server model includes inspectable empty/ordered/short-circuit flow,
  route mapping, attribute access and trust limits, unmatched delegation,
  propagation, long-lived reuse, package graph, and current/planned behavior.
- Every authorized related current-state document records Phase 2E and the
  eleventh package as implemented, PSR-15 as installed and used, Phase 2F as
  next, and Phase 2H as the first runnable black-box milestone.
- Historical ADR context was preserved through explicit current-state
  amendments rather than rewriting earlier decisions as if made later.
- Documentation does not present bootstrap, Kernel web composition, adapter,
  delivery, 404 body, generic production failure, trusted-diagnostic delivery,
  emission, or a runnable application as implemented.
- Current behavior and planned behavior, decision semantics, package ownership,
  stability, security, lifecycle, failure, performance, and extension limits
  are explicit and consistent.
- All affected local Markdown links resolve; no encoding or whitespace defect
  remains.

## 19. WARNINGS / KNOWN ISSUES

- Composer continues to emit the accepted preexisting warnings for no declared
  license and unbounded local monorepo development constraints. They were not
  changed because dependency policy and release/versioning are outside scope.
- PHP CS Fixer continues to use its existing ignored cache file. It is not
  Git-visible and no generated artifact entered the authorized path inventory.
- HTTP Server APIs are Experimental except for the Public package exception
  marker and Internal implementation/catalog types. No Stable API exists.
- Consumer-supplied middleware and handlers may be stateful; applications must
  evaluate their own reentrancy and concurrency safety.
- No unresolved code, validation, security-audit, documentation, filesystem,
  dependency, or Git issue remains for Phase 2E.

## 20. QUESTIONS / BLOCKERS

None.

## 21. GIT DIFF SUMMARY

- Created paths: exactly 16.
- Authorized existing paths changed: exactly 24.
- Protected preexisting paths outside the authorized existing set and caller
  report: 0 byte/hash mismatches across the 447-file baseline snapshot.
- Pre-report and post-report status classification: 18 tracked modifications,
  105 untracked paths, 123 total status paths, 0 staged paths.
- Final inventories: 11 package directories, 11 package manifests, 86
  production PHP files, 110 test PHP files, 196 PHP CS Fixer files, and 109
  unique production diagnostic IDs.
- Tracked modes remain `100644`; `git diff --summary` reports no mode change.
- Index remains 44,541 bytes with SHA-256
  `768B60DC6F0E2C2A9C4040C2A48EB78035A37713AF482649B16C72592BD0AC2B`.
- Branch, HEAD, tree, parent, commit count, refs, remotes, and tags remain exact.
- `git diff --check` passes with no output.

## 22. ACCEPTANCE CRITERIA

1. PASS — all 29 authorities were read completely in required order.
2. PASS — directive bytes and SHA-256 matched the caller-supplied identity.
3. PASS — zero-byte report prerequisite and immutable caller records matched.
4. PASS — exact Git, hash, status, inventory, and file-safety baseline matched.
5. PASS — pre-change quality passed at 387 tests / 6,721 assertions.
6. PASS — created paths equal the exact 16-path list.
7. PASS — modified existing paths equal and are confined to the exact 24-path list.
8. PASS — package identity, namespace, requirements, and dependency direction are exact.
9. PASS — only HTTP Server and the two accepted PSR-15 packages were newly resolved by this phase.
10. PASS — production inventory is exactly six declarations with exact statuses.
11. PASS — pipeline shape, bound, detachment, compilation, and order are exact.
12. PASS — short-circuit, transforms, repeated-next, reuse, and propagation are proved.
13. PASS — route-handler construction shape and complete canonical map are exact.
14. PASS — selected route attribute overwrite, trust limits, and exact decoration are proved.
15. PASS — unmatched delegation is explicit and no response semantics are hard-coded.
16. PASS — exact marker, exception parent, metadata, context, and cause identity are proved.
17. PASS — all three diagnostics have exact IDs, schemas, vocabulary, and bounds.
18. PASS — framework diagnostics total exactly 109 with no duplicate.
19. PASS — persistent-runtime and shallow consumer-object boundaries are documented and tested.
20. PASS — prohibited bootstrap, lifecycle, adapter, emission, error middleware, and runnable scope is absent.
21. PASS — root Composer, autoload, PHPUnit, PHPStan, style, and package validation integration is exact.
22. PASS — architecture suite proves inventories, edges, statuses, shapes, marker separation, and no reverse dependency.
23. PASS — focused tests, architecture tests, focused/full PHPStan, strict autoload, style, audit, platform, and quality pass.
24. PASS — ADR 0047 is Accepted and model/current/future documentation is reconciled.
25. PASS — no bootstrap, Kernel web lifecycle, adapter, delivery, emission, generic failure response, or runnable app was introduced.
26. PASS — final inventories are exactly 11/11/86/110/196/109.
27. PASS — final status is exactly 18 modified / 105 untracked / 123 total / 0 staged.
28. PASS — protected bytes, caller records, modes, streams, refs, history, and index remain exact.
29. PASS — this report was populated only after items 1-28 passed.
30. PASS — this report uses the exact required structure, records verified outcomes, and ends with the exact required sentence.

## 23. NEXT STEP

Await architect review before Phase 2F.
