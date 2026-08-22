PHASE 0B.1 CORRECTION REPORT
STATUS
Complete
The Composer audit command conflict is resolved exactly as directed.
FILES MODIFIED[composer.json](C:/Users/antho/Dev/kongono/composer.json) — replaced the conflicting audit script with audit:locked and updated quality.
[CONTRIBUTING.md](C:/Users/antho/Dev/kongono/CONTRIBUTING.md) — documented installed-graph versus locked-graph audit commands.
[toolchain.md](C:/Users/antho/Dev/kongono/docs/engineering/toolchain.md) — documented the corrected quality-gate contract.
[dependency-security.md](C:/Users/antho/Dev/kongono/docs/engineering/dependency-security.md) — documented deterministic lock-file auditing.
[0014-php-engineering-toolchain.md](C:/Users/antho/Dev/kongono/docs/adr/0014-php-engineering-toolchain.md) — recorded the resolved toolchain command.
[0019-composer-dependency-security-policy.md](C:/Users/antho/Dev/kongono/docs/adr/0019-composer-dependency-security-policy.md) — recorded the locked security-audit contract.

AGENTS.md required no correction.
EXACT COMMAND CONTRACT AFTER CORRECTION
"audit:locked": "composer audit --locked"
The quality sequence is:
"quality": [
"@validate",
"@audit:locked",
"@platform",
"@cs",
"@analyse",
"@test"
]
Therefore:
composer audit remains Composer’s built-in installed-dependency audit.
composer audit:locked executes composer audit --locked.
composer quality uses the deterministic lock-file audit.
Verbose validation confirmed:
quality: @audit:locked
audit:locked: composer audit --locked
No Composer plugin was introduced or authorized. Existing security policy remains unchanged.
VALIDATION RESULTScomposer validate — PASS; intentional missing-license warning only
composer audit — PASS; no security advisories found
composer audit:locked — PASS; no security advisories found
composer platform — PASS; PHP 8.5.9 and all package requirements satisfied
composer cs — PASS; 1 file checked, 0 violations
composer analyse — PASS; 0 errors
composer test — PASS; 1 test, 2 assertions
composer quality — PASS
composer -vvv quality — PASS; confirmed dispatch to composer audit --locked

Validation used PHP 8.5.9. The temporary per-process PHP extension configuration was removed afterward.
DEVIATIONS
None.
Only the authorized Composer command contract and directly affected documentation were changed.
QUESTIONS/BLOCKERS
None.
GIT STATUS SUMMARYNo commit created.
No push performed.
Repository still has no HEAD commit and no configured remote.
Existing repository files remain untracked from the Phase 0A/0B setup.
This correction modified only the six files listed above.
Unrelated task-report files visible in status were not modified.
