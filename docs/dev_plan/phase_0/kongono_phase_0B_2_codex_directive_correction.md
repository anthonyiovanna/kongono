CODEX CORRECTIVE DIRECTIVE — KONGONO PHASE 0B.2

Phase 0B.1 repository changes are architecturally accepted.

Do not begin Phase 0C.

A second Composer reserved-command collision has been identified in the
real developer environment:

A script named `validate` conflicts with Composer's built-in
`composer validate` command.

LOCKED DECISION

1. Composer's built-in `composer validate` remains untouched.

2. Remove the conflicting Kongono script named:
   validate

3. Introduce Kongono's explicit validation script:
   validate:composer

4. `composer validate:composer` must perform the same Composer manifest
   validation required by Phase 0B.

5. `composer quality` must use:
   @validate:composer
   @audit:locked
   @platform
   @cs
   @analyse
   @test

6. Do not suppress the intentionally missing license warning by inventing
   or selecting a license.

7. Do not change any Composer dependency security controls.

8. Do not change dependencies.

9. Do not authorize Composer plugins.

10. Do not begin framework implementation.

AUTHORIZED CHANGES

Modify only:
- composer.json
- CONTRIBUTING.md if necessary
- docs/engineering/toolchain.md if necessary
- the directly relevant existing ADR if command documentation requires it

Do not alter unrelated architecture or documentation.

VALIDATION

Run:

composer validate
composer validate:composer
composer audit
composer audit:locked
composer platform
composer cs
composer analyse
composer test
composer quality
composer -vvv quality

Verify specifically:

- no "script named validate would override a Composer command" warning exists
- no "script named audit would override a Composer command" warning exists
- composer quality dispatches to validate:composer
- composer quality dispatches to audit:locked
- audit:locked invokes composer audit --locked
- all quality stages pass

Do not commit or push.

RETURN:

PHASE 0B.2 CORRECTION REPORT

1. STATUS
2. FILES MODIFIED
3. FINAL COMPOSER COMMAND CONTRACT
4. VALIDATION RESULTS
5. QUALITY DISPATCH VERIFICATION
6. DEVIATIONS
7. QUESTIONS / BLOCKERS
8. GIT STATUS SUMMARY
9. NEXT STEP

For NEXT STEP state only:

"Await architect review before Phase 0C."
