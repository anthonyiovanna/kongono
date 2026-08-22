# CODEX CORRECTIVE DIRECTIVE — KONGONO PHASE 0C.1

Phase 0C has received conditional architectural acceptance.

Do not begin Phase 0D.

## Finding

The `kongono/kernel` package correctly declares:

```json
"Kongono\\Kernel\\Tests\\": "tests/"
```

under its own `autoload-dev`.

However, Composer treats `autoload-dev` as root-only. Because `kongono/kernel` is consumed as a dependency of the root project, that package-level development mapping is not included in the root `vendor/autoload.php`.

The generated root autoloader currently resolves:

```text
Kongono\Kernel\Kernel
```

but does not resolve:

```text
Kongono\Kernel\Tests\Unit\KernelTest
```

This contradicts the Phase 0C requirement and completion-report claim that package tests autoload through their intended namespace.

## Locked correction

Add this root development mapping:

```json
"autoload-dev": {
  "psr-4": {
    "Kongono\\Kernel\\Tests\\": "packages/kernel/tests/",
    "Kongono\\Tests\\": "tests/"
  }
}
```

Preserve the package-level `autoload-dev` mapping because it remains correct when `kongono/kernel` is developed as the root package.

Do not add the Kernel test namespace to production `autoload`.

## Authorized changes

Modify only:

- `composer.json`
- `docs/engineering/package-layout.md` if needed to explain the root monorepo test mapping
- the Phase 0C task report or a directly associated Phase 0C.1 correction report

Do not change production Kernel behavior, lifecycle contracts, API classifications, exception contracts, tests, dependencies, security policy, or unrelated documentation.

Do not manually edit `composer.lock`. If Composer determines that a generated lock update is necessary, report why before retaining it.

## Report-inventory correction

`docs/task reports/Phase_0C.md` exists as a newly created repository file but was omitted from the original “FILES CREATED” inventory and 39-file total.

Determine whether it was created during Phase 0C. If so, record it and correct the total. If it predated execution, provide the baseline evidence.

## Validation

Run:

```bash
composer dump-autoload --optimize --strict-psr
php -r "require 'vendor/autoload.php'; var_dump(class_exists('Kongono\\Kernel\\Tests\\Unit\\KernelTest'));"
composer validate
composer validate:composer
composer validate:packages
composer audit:locked
composer platform
composer cs
composer analyse
composer test
composer quality
composer -vvv quality
git diff --check
git status --short
```

The direct autoload check must return:

```text
bool(true)
```

Also verify:

- `vendor/composer/autoload_psr4.php` contains `Kongono\Kernel\Tests\`
- the mapping targets `packages/kernel/tests`
- Kernel test classes remain absent from production autoload
- all 20 tests and 99 assertions still pass, unless an accurately explained test-count change occurs
- the quality-stage order remains unchanged
- no external dependency or plugin changes
- no security-policy changes
- no baseline or suppression
- no commit, branch, or push

## Return

```text
PHASE 0C.1 CORRECTION REPORT

1. STATUS
2. ROOT TEST AUTOLOAD CORRECTION
3. FILES MODIFIED
4. DIRECT AUTOLOAD VERIFICATION
5. QUALITY RESULTS
6. DEPENDENCY AND SECURITY CONFIRMATION
7. REPORT-INVENTORY CORRECTION
8. DEVIATIONS
9. QUESTIONS / BLOCKERS
10. GIT STATUS SUMMARY
11. NEXT STEP
```

For NEXT STEP state only:

“Await architect review before Phase 0D.”
