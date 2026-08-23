# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.8

## Safe Native-Compatible Chie Value-Validation Exception

## 1. Purpose and architectural decision

This is the architect-authenticated correction for the six focused PHPUnit failures exposed by Phase 4D.7.

The failures prove a real disclosure boundary: native `InvalidArgumentException::__toString()` includes a stack trace, and the trace can contain rejected arguments supplied to public constructors or factories. Fixed exception messages alone therefore do not satisfy the Phase 4D.2 requirement that `(string) $exception` remain free of rejected caller values.

The accepted correction is one package-owned Internal final subtype of native `InvalidArgumentException` with:

- a private constructor;
- six zero-argument named factories that supply only fixed package messages;
- deterministic final `__toString()` that renders only the exception class and fixed message;
- no diagnostic, context, raw value, previous exception, custom code, I/O, logging, or ambient state.

This preserves native `InvalidArgumentException` catch compatibility while preventing `(string) $exception` from rendering native trace frames. It does not claim that native `getTrace()` or `getTraceAsString()` is sanitized; those remain protected native diagnostic data and are not a Chie presentation surface.

This correction authorizes one new Internal production type, replacement of the six native throw families in the existing Chie value boundaries, bounded tests, and API-status architecture registration. All other Phase 4D.2 rules as corrected through Phase 4D.7 remain controlling.

The executor is an executor, not an architect. Stop, report expected and observed values, preserve the worktree, index, history, and caller records, and ask rather than guessing or expanding scope. Do not begin Phase 4E.

## 2. Independent authentication

Directive path:

`docs/dev_plan/phase_4/kongono_phase_4D_8_codex_directive_correction.md`

Active report path:

`docs/task_reports/phase_4/Phase_4D_8_correction.md`

The architect will supply this directive's byte count and SHA-256 independently outside this file. Do not self-authenticate from the local artifact. Stop before further validation or mutation if the independent identity is absent or differs.

The active report must initially be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

## 3. Locked caller records

Authenticate all earlier Phase 4D caller records:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md` | 53533 | `504EF58E8999FB0BFF61A630BA0FB2594363972C13C04C2BC3D169B807A2BAC9` |
| `docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md` | 13286 | `BCAC386F0468CBD592F686ADD979B44E5996DB58CC970FA0006A314C83D7BB9C` |
| `docs/dev_plan/phase_4/kongono_phase_4D_2_codex_directive_correction.md` | 21229 | `EBAA4F85E60F205DC943B6A78186E1F3B6B49044392C56FB49136036066844E0` |
| `docs/dev_plan/phase_4/kongono_phase_4D_3_codex_directive_correction.md` | 10606 | `3E893F2F556A69DC2612871AEE7E90A494BC5D13AE59648E5C540387BDC8D67B` |
| `docs/dev_plan/phase_4/kongono_phase_4D_4_codex_directive_correction.md` | 12371 | `BC1F2A3FCE2A2BB6BBDD9982C2D68C8E87CDF2D5B039B88E405092371D2A27F5` |
| `docs/dev_plan/phase_4/kongono_phase_4D_5_codex_directive_correction.md` | 13267 | `0A32FA43EB0F191DB09BD69A423296D1C89B26888C6934C9FF074D3FD758EAC8` |
| `docs/dev_plan/phase_4/kongono_phase_4D_6_codex_directive_correction.md` | 13936 | `B3ECA84B8B6A8E181853EDE82679C0CBED9598635FB02E8146DA0A6E30E18F0B` |
| `docs/dev_plan/phase_4/kongono_phase_4D_7_codex_directive_correction.md` | 13984 | `6C57C4C3C35755A2F056FAB993492528FD9385825C90B6B1C4A1091EF86F92D8` |
| `docs/task_reports/phase_4/Phase_4D.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_1_correction.md` | 30739 | `497FF940A9204B4BF68E3A68664994177430A469756994475B068AC84E8B9FD8` |
| `docs/task_reports/phase_4/Phase_4D_2_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_3_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_4_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_5_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_6_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_7_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All earlier directives and reports remain immutable. Populate only the Phase 4D.8 report after full non-report success.

## 4. Exact entry state

After the caller places the Phase 4D.8 directive and report placeholder, require exactly:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 101 untracked paths;
- 127 total status paths;
- zero staged paths;
- 730 Git-visible paths;
- 629 tracked files;
- 16 package directories and manifests;
- 167 production PHP files;
- 163 test PHP files;
- 332 files in canonical style scope;
- 64 ADR files;
- 15 files in `docs/dev_plan/phase_4`;
- 15 files in `docs/task_reports/phase_4`.

The new exception source must be absent, untracked, unstaged, and absent from `HEAD` at entry:

`packages/chie/src/Exception/InvalidChieValueException.php`

No dependency, Composer, lockfile, artifact, documentation, configuration, index, ref, branch, tag, remote, or history mutation is authorized.

## 5. Exact accepted starting identities

Authenticate these nine existing files before mutation:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/chie/src/ChieCitation.php` | 2442 | `F3A20BBB21AF55EE5B5656E15D6CD4B2E90E54B6B3D27B9DF06CF1F7D1CE21AC` |
| `packages/chie/src/ChieClaim.php` | 2669 | `6081397E53BF267C677F3FB479EE3A414BA76DB27486D019920621F4BBB913DD` |
| `packages/chie/src/ChieResponse.php` | 5489 | `AA7D6930E247C717622D5E7ACBD69D7EF9A2A96772913B2B174EDE584C8CCF91` |
| `packages/chie/src/Diagnostic/ChieDiagnostic.php` | 7026 | `832A3B389161E2EC180C48208946491E6D34F3125221BB35CA9247FA1983F913` |
| `packages/chie/src/Exception/ChieException.php` | 2449 | `A13A2D2A7AFFD2BCEF5496FFE69C679730386C0AB761CC0CB445F3D94021AC6C` |
| `packages/chie/src/Internal/CanonicalJson.php` | 1843 | `ED183C94506C6E13371BE43B8ADB063C8C212BD622E2701D610067951919B2F8` |
| `packages/chie/tests/Unit/ChieResponseTest.php` | 13104 | `B13074C6A98D68F9A85BC176CDF38FC3EC96238C0E37885F2BB8DD50A0A524D3` |
| `packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php` | 9335 | `F7ACA53AD7E56BDEF0EDFA00A4128B59C5A2B36658DC4665D5A893EAD9F15E96` |
| `tests/Architecture/ApiStatusConventionTest.php` | 102163 | `930DE788096A7041A155C59E81EA354967F0D11DED40867027A40BE0F4075D09` |

Stop before mutation if any differs.

## 6. Exact entry manifests

Exclude the eighteen Phase 4D caller paths—nine directives and nine reports. Use the locked ordinal/no-terminal-LF construction.

| Set | Records | Manifest bytes | SHA-256 | Aggregate bytes |
|---|---:|---:|---|---:|
| Complete current implementation | 712 | 89259 | `20ACE12C9F5863700C84DE8DB09CE54667F7E2D000905F08339A3A3E91B110F7` | 7701988 |
| Protected | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical excluding eighteen callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

Complete-manifest endpoints remain `.editorconfig` and `tests/Tooling/EnvironmentTest.php`. Stop on any mismatch.

## 7. Accepted prior validation evidence

Accept and do not repeat solely for this correction:

- Phase 4D.6 sequential focused PHPStan: complete, exit code 0, zero errors;
- Phase 4D.7 six mechanical style replacements and syntax checks;
- Phase 4D.7 cache-disabled focused PHP CS Fixer: 0 of 30 files fixable;
- Phase 4D.7 focused PHPUnit: 36 tests, 387 assertions, six failures, all caused by native exception stringification retaining rejected call arguments;
- no later Phase 4D.7 gate ran.

The six failed disclosure assertions remain required and may not be removed, weakened, redirected to `getMessage()` only, or made environment-dependent.

## 8. New Internal exception type

Create exactly:

`packages/chie/src/Exception/InvalidChieValueException.php`

Required declaration:

- namespace `Kongono\Chie\Exception`;
- `final class InvalidChieValueException extends InvalidArgumentException`;
- `#[Api(ApiStatus::Internal)]`;
- no implemented package diagnostic marker;
- no declared property;
- private constructor accepting exactly one `string $message` and calling `parent::__construct($message)` with default code and no previous exception;
- exactly six public static zero-argument factories returning `self`;
- one final public `__toString(): string`;
- no other method, state, side effect, or dependency.

Factories and fixed messages:

| Factory | Fixed message |
|---|---|
| `citation()` | `Invalid Chie citation.` |
| `claim()` | `Invalid Chie claim.` |
| `response()` | `Invalid Chie response.` |
| `diagnosticViolation()` | `Invalid Chie diagnostic violation.` |
| `exceptionDiagnostic()` | `Invalid Chie exception diagnostic.` |
| `errorEnvelope()` | `Invalid Chie error.` |

Each factory must construct with only its fixed literal and accept no argument. The private constructor prevents caller-selected message construction.

`__toString()` must return exactly:

```php
sprintf('%s: %s', self::class, $this->getMessage())
```

It must not call `parent::__toString()`, inspect or render traces, include file/line, serialize state, or throw.

## 9. Exact throw-site replacement

Replace native `InvalidArgumentException` construction with the new zero-argument factories in exactly these files:

| File | Factory used for every validation throw in that file |
|---|---|
| `ChieCitation.php` | `InvalidChieValueException::citation()` |
| `ChieClaim.php` | `InvalidChieValueException::claim()` |
| `ChieResponse.php` | `InvalidChieValueException::response()` |
| `Diagnostic/ChieDiagnostic.php` | `InvalidChieValueException::diagnosticViolation()` |
| `Exception/ChieException.php` | `InvalidChieValueException::exceptionDiagnostic()` |
| `Internal/CanonicalJson.php` | `InvalidChieValueException::errorEnvelope()` |

Remove obsolete native imports and add the package-owned import where namespaces require it, maintaining canonical import order. Within `Kongono\Chie\Exception`, reference the sibling type directly.

Every condition, branch, validation order, state matrix, property, return, serialization result, and fixed message must remain otherwise unchanged. Do not pass rejected values or any argument to a factory.

## 10. Required tests

Modify only the two existing focused test files and the architecture test.

### 10.1 Focused Chie tests

Within existing test methods only, retain all current assertions and add proof that:

- every caught value-validation failure is both `InvalidChieValueException` and native `InvalidArgumentException`;
- each of the six factories produces its exact fixed `getMessage()`;
- its string form is exactly `Kongono\Chie\Exception\InvalidChieValueException: <fixed message>`;
- string form contains no trace prefix, file path, line number, rejected input, private path, oversized payload, raw claim, raw diagnostic field, or uncertainty value;
- code is 0 and previous exception is null;
- existing valid construction/serialization assertions remain byte-identical.

Do not add a test method. Focused count must remain exactly 36.

### 10.2 API-status architecture test

In the existing applicable architecture test method:

- import `InvalidChieValueException`;
- register it as `ApiStatus::Internal` in the exact expected-status inventory;
- prove it is final;
- prove its immediate parent is native `InvalidArgumentException`;
- prove it implements none of the package diagnostic exception markers;
- prove it has no declared properties;
- prove its constructor is private and accepts exactly one string parameter;
- prove its declared public surface is exactly the six named factories and `__toString()`;
- prove every factory has zero parameters and returns `self`;
- prove `__toString()` is final, public, has zero parameters, and returns `string`.

Do not add a test method or modify unrelated architecture expectations.

## 11. Exact mutation boundary

Created:

- `packages/chie/src/Exception/InvalidChieValueException.php`.

Modified:

1. `packages/chie/src/ChieCitation.php`
2. `packages/chie/src/ChieClaim.php`
3. `packages/chie/src/ChieResponse.php`
4. `packages/chie/src/Diagnostic/ChieDiagnostic.php`
5. `packages/chie/src/Exception/ChieException.php`
6. `packages/chie/src/Internal/CanonicalJson.php`
7. `packages/chie/tests/Unit/ChieResponseTest.php`
8. `packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php`
9. `tests/Architecture/ApiStatusConventionTest.php`

No other non-report file may change. The active Phase 4D.8 report is mutable only after all non-report gates pass.

After implementation, expected status is 26 tracked modifications, 102 untracked paths, 128 total paths, and zero staged; 731 Git-visible paths; 168 production PHP files; 163 test PHP files; 333 files in canonical style scope; and 31 focused Chie style files including the extensionless executable.

## 12. Validation sequence

After exact implementation:

1. Verify the one-created/nine-modified boundary and all other starting identities.
2. Run syntax checks on the new type and all nine modified PHP files.
3. Run sequential focused PHPStan exactly once:

```powershell
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug packages/chie tests/Architecture/ApiStatusConventionTest.php
```

4. Run cache-disabled focused style exactly once:

```powershell
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff --using-cache=no packages/chie/src packages/chie/tests packages/chie/bin/kongono-chie tests/Architecture/ApiStatusConventionTest.php
```

5. Run focused tests exactly once:

```powershell
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/chie/tests tests/Architecture/ApiStatusConventionTest.php
```

Focused Chie count within that result must remain exactly 36; the report must separately identify the architecture-test count and total. All six former disclosure failures must pass.

6. Run the adversarial direct-construction proof and all valid CLI regressions required by Phase 4D.2.
7. Verify Knowledge artifact/source-set identities and 31 sources.
8. Run final literal `composer quality`. It must pass with zero advisories, 0 of 333 style findings, zero PHPStan errors, and exactly 633 tests. Report the final assertion count.
9. Reconstruct final complete, protected, and historical manifests using twenty excluded Phase 4D callers. Protected and historical values remain exact; the complete delta is limited to the one created and nine modified files.
10. Run final file-boundary, API-status, autoload, disclosure, Git, index, history, refs, configuration, dependency, documentation, encoding, stream, and ordinary-file preservation checks.

The only permitted retry remains one identical approved-access retry when the locked Composer audit is blocked solely by managed network/cache access. Stop without retry on every other failure.

## 13. Prohibited scope

Do not:

- weaken or delete any `(string) $exception` assertion;
- depend on `zend.exception_ignore_args`;
- claim native traces are sanitized;
- make the new constructor public/protected or accept raw caller input in it;
- add diagnostic context, a diagnostic ID, package exception marker, previous chain, code, file, line, or trace rendering;
- change public value-object/factory signatures or canonical JSON;
- alter Composer/autoload configuration; PSR-4 already covers the new type;
- change documentation, ADRs, dependencies, tooling configuration, or artifacts;
- add another source/test file;
- suppress PHPStan/style/test findings;
- stage, commit, tag, create/delete refs, alter history/configuration, clean, restore, reset, or stash;
- populate earlier reports;
- begin Phase 4E.

## 14. Completion report

Populate only `docs/task_reports/phase_4/Phase_4D_8_correction.md` after every non-report gate passes. Use the standard 16 sections and include:

- independent directive and every caller identity;
- entry state, nine existing identities, absent-created-path proof, and all three manifests;
- accepted Phase 4D.6/4D.7 validation evidence;
- the new type's exact API, status, inheritance, private-construction, factory, and stringification proof;
- all six throw families and non-disclosure evidence;
- exact created/modified inventory and final identities;
- syntax, sequential PHPStan, style, focused tests, adversarial, CLI, artifact, canonical quality, final manifest, and preservation results;
- focused Chie/architecture/complete test and assertion counts separately;
- confirmation that native traces were not presented or claimed sanitized;
- final report byte count and uppercase SHA-256.

The final line before the report identity appendix remains exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

## 15. Acceptance criteria

Phase 4D.8 completes only when:

1. Independent directive authentication passes.
2. Caller records, entry state, identities, absent path, and manifests match.
3. Exactly one Internal exception type is created with the closed required surface.
4. Exactly six native throw families use zero-argument fixed factories.
5. Native `InvalidArgumentException` catch compatibility remains.
6. Deterministic stringification contains only class and fixed message.
7. All former raw-value stringification failures pass without weakening.
8. API status, inheritance, construction, methods, and marker exclusions are proven.
9. Exactly one created and nine modified non-report files comprise the correction.
10. Syntax, sequential PHPStan, focused style, and focused tests pass.
11. Chie test count remains exactly 36 and complete count exactly 633.
12. Adversarial and valid CLI regressions pass.
13. Artifact/source-set identities and 31-source count remain exact.
14. Final canonical quality passes with 333 style files and zero findings.
15. Protected/historical manifests remain exact; complete delta is closed.
16. No unauthorized dependency, documentation, configuration, Git, or historical mutation occurs.
17. Only the Phase 4D.8 report is populated after full non-report success.
18. Nothing is staged or committed.
19. Phase 4E is not begun.

STOP

REPORT EXPECTED AND OBSERVED VALUES

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND SCOPE
