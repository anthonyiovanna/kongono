TASK COMPLETION REPORT
STATUS
Blocked — production defect exposed by the required test proof.
SUMMARY
The new disclosure test demonstrated that raw rejected URI input appears in (string) $exception through PHP’s captured stack-trace arguments. Production is frozen, so work stopped immediately.
FILES CREATED
None.
FILES MODIFIED
Only the seven authorized existing tests:
[HttpMessageDiagnosticTest.php](C:\\Users\\antho\\Dev\\kongono\\packages\\http-message\\tests\\Unit\\Diagnostic\\HttpMessageDiagnosticTest.php)
[HttpMessageExceptionTest.php](C:\\Users\\antho\\Dev\\kongono\\packages\\http-message\\tests\\Unit\\Exception\\HttpMessageExceptionTest.php)
[HeaderCollectionTest.php](C:\\Users\\antho\\Dev\\kongono\\packages\\http-message\\tests\\Unit\\Internal\\HeaderCollectionTest.php)
[ResponseTest.php](C:\\Users\\antho\\Dev\\kongono\\packages\\http-message\\tests\\Unit\\Message\\ResponseTest.php)
[ServerRequestTest.php](C:\\Users\\antho\\Dev\\kongono\\packages\\http-message\\tests\\Unit\\Message\\ServerRequestTest.php)
[StreamTest.php](C:\\Users\\antho\\Dev\\kongono\\packages\\http-message\\tests\\Unit\\Message\\StreamTest.php)
[UploadedFileTest.php](C:\\Users\\antho\\Dev\\kongono\\packages\\http-message\\tests\\Unit\\Message\\UploadedFileTest.php)
ARCHITECTURAL DECISIONS
None made.
Architect decision required: how Kongono guarantees that raw rejected values cannot appear in exception stringification.
Options:
Authorize a bounded production exception-string correction. Strongest application-level guarantee, but changes frozen production.
Require zend.exception_ignore_args=On. Avoids production code changes but depends on runtime configuration.
Revise the proof to exclude stack traces from the disclosure boundary. Smallest change, but weakens the requested security guarantee.
Recommendation: authorize an explicit safe exception-string strategy rather than relying solely on environment configuration.
DEVIATIONS FROM DIRECTIVE
None. The directive required stopping when a test exposed a production defect.
DEPENDENCIES ADDED/REMOVED
None.
ENVIRONMENT
PHP 8.5.9, PHPUnit 13.3.0.
COMMANDS RUNSeven changed-test php -l checks
composer quality
Authorized identical composer quality retry with network/cache access
php vendor/bin/phpunit --configuration phpunit.xml.dist packages/http-message/tests
Read-only preservation checks

VALIDATION RESULTSPre-change canonical quality: PASS — 326 tests, 5,588 assertions.
Changed-test syntax: PASS.
Focused HTTP tests: FAIL — 75 tests, 855 assertions, 1 failure, 1 error.
Later gates: not run because the stop condition activated.

Disclosure failure:
Uri::withHost('private host')
The literal private host was absent from the diagnostic message and structured context but present in (string) $exception inside stack-trace arguments.
A separate test-authoring error also remains: PHPUnit 13 treats the third assertContains() argument as the message, not a strict-comparison flag.
DOCUMENTATION REVIEW
The Phase 2C.5 report was not populated, as required when a non-report gate fails.
WARNINGS / KNOWN ISSUES
The newly added tests remain in the authorized dirty worktree pending architect direction.
QUESTIONS / BLOCKERS
Approval is required either to correct production exception stringification or revise the authenticated disclosure requirement.
GIT DIFF SUMMARY
Final preservation state:
18 modified
61 untracked
79 total
0 staged
No commit, ref, branch, lockfile, or history mutation
[Phase_2C_5_correction.md](C:\Users\antho\Dev\kongono\docs\task reports\Phase_2C_5_correction.md) remains zero bytes
ACCEPTANCE CRITERIA
FAIL — raw-value disclosure proof exposed a frozen-production defect.
NEXT STEP
Await authenticated architect correction or production-change authorization.
