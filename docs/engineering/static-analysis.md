# Static Analysis

## Purpose and policy

PHPStan checks contracts and control flow before runtime defects reach tests or consumers. Kongono runs PHPStan 2.2 at `level: max`, includes the maintained strict-rules configuration, and analyzes against PHP 8.5.0 so newer contributor patch releases cannot silently become the assumed language target.

Run analysis with:

```bash
composer analyse
```

The current analysis scope is `tests/Architecture`, `tests/Tooling`, and the source and test trees for Kernel, Application Graph, and Dependency Injection. Future production paths may be added only with their authorized implementation packages.

## No-baseline contract

Kongono begins with zero baseline and no ignored errors. A failure should be corrected in code when it represents a real defect. Do not add a baseline, `ignoreErrors`, inline ignore annotation, error-identifier suppression, blanket exclusion, lower level, or removal of strict rules to make analysis pass.

If a finding appears to be a PHPStan false positive and resolving it would degrade the code or falsify its contract, stop and request architect review. Report the exact finding, attempted analysis, and viable options; do not suppress it first.

The local analysis cache lives under `.cache/phpstan` and is generated state, not repository evidence.
