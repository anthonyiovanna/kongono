# Contributing to Kongono

Kongono is pre-alpha. Contributions must preserve the authorized architecture and must not turn planned or experimental concepts into accidental public APIs.

## Before changing the repository

Read, in order:

1. [`AGENTS.md`](AGENTS.md), which is binding for AI coding agents and a useful scope discipline for all contributors.
2. [`ARCHITECTURE.md`](ARCHITECTURE.md) and [`docs/doctrine.md`](docs/doctrine.md).
3. The relevant architecture documentation and accepted ADRs.
4. [`docs/documentation-standard.md`](docs/documentation-standard.md).

If a change requires an unapproved architectural, security, public-API, package-boundary, dependency, protocol, runtime, compatibility, licensing, or data-model decision, stop and request architect approval. State the required decision, viable options, tradeoffs, and a recommendation without implementing it.

## Change discipline

- Implement the smallest correct change within the approved phase.
- Do not refactor unrelated work or introduce speculative abstractions.
- Do not add dependencies, package boundaries, public APIs, or technology substitutions without authorization.
- Keep planned architecture clearly separate from implemented behavior.
- Update affected documentation and ADRs when an approved architectural decision changes.
- Use precise types, names, and structure before adding comments.
- Explain non-obvious purpose, invariants, security, lifecycle, concurrency, failure, and side effects where relevant.
- Do not manually maintain exhaustive caller/callee lists; those relationships are intended to become machine-derived.

## Validation

Kongono development requires PHP 8.5 or newer and Composer 2.10 or newer. Install the reproducible root development environment with:

```bash
composer install
```

Before submitting a PHP change, run the complete non-mutating gate:

```bash
composer quality
```

The gate validates the root and package Composer manifests, audits the locked dependency graph, checks the actual runtime platform, checks coding style, runs maximum-level static analysis with strict rules, and executes the test suite. The individual commands are available when narrowing down a failure:

```bash
composer validate
composer validate:composer
composer validate:packages
composer audit
composer audit:locked
composer platform
composer cs
composer analyse
composer test
```

`composer validate` remains Composer's built-in root-manifest validation command. Kongono's explicit root script is `composer validate:composer`, and `composer validate:packages` validates Application Graph, Dependency Injection, and Kernel package manifests in lexicographic directory order. Both omit publication-readiness checks because the license remains intentionally undecided. `composer audit` remains Composer's built-in installed-dependency audit. Kongono's deterministic lock-file audit is `composer audit:locked`, and the complete quality gate uses the explicit repository scripts. Use `composer cs:fix` only as an explicit formatting action, then rerun the non-mutating checks. Do not bypass dependency policy, platform requirements, static-analysis findings, or tests. A dependency addition, Composer plugin, baseline, suppression, or policy exception requires architect approval. Report exactly what ran and do not describe an unexecuted check as passing.

## Commits and review

Keep changes focused and reviewable. Commit, branch, push, release, and publishing policy will be established separately; do not infer it from this document.
