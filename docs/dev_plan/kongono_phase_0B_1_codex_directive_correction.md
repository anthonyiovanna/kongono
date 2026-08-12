CODEX CORRECTIVE DIRECTIVE — KONGONO PHASE 0B.1

Phase 0B has received conditional architectural acceptance.

Do not begin Phase 0C.

The architect has resolved the Composer audit command conflict as follows.

LOCKED DECISION

1. Composer's built-in `composer audit` command remains untouched.
2. Kongono's deterministic lock-file audit command is:
   `composer audit:locked`
3. `composer audit:locked` must execute:
   `composer audit --locked`
4. `composer quality` must use the locked audit behavior rather than bare
   `composer audit`.
5. Do not introduce or authorize any Composer plugin.
6. Do not weaken any existing Composer security policy.

AUTHORIZED CHANGES

Modify only what is necessary in:

- composer.json
- engineering documentation
- CONTRIBUTING.md if command documentation requires it
- AGENTS.md only if its quality-command language requires correction
- ADR 0019 and/or relevant toolchain ADR only if necessary to accurately
  record the resolved command contract

Do not alter any unrelated Phase 0A or Phase 0B architecture.

VALIDATION

Run:

composer validate
composer audit
composer audit:locked
composer platform
composer cs
composer analyse
composer test
composer quality

Confirm that `composer quality` executes the locked audit form.

Do not commit or push.

RETURN:

PHASE 0B.1 CORRECTION REPORT

1. STATUS
2. FILES MODIFIED
3. EXACT COMMAND CONTRACT AFTER CORRECTION
4. VALIDATION RESULTS
5. DEVIATIONS
6. QUESTIONS/BLOCKERS
7. GIT STATUS SUMMARY
8. NEXT STEP

For NEXT STEP state only:

"Await architect review before Phase 0C."
