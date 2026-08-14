# Kongono Engineering Foundation

## Purpose and current scope

Kongono's engineering foundation makes the repository's PHP quality expectations executable across the root, API Metadata, Kernel, Application Graph, Dependency Injection, and Diagnostics work. It gives contributors one reproducible development environment and one non-mutating local gate without implying that Experimental behavior is Stable or that planned capabilities exist.

Phase 0U expands the automatically discovered Dependency Injection coverage by
one exact ten-method diagnostic-context test. The quality configuration and
policy remain unchanged.

The root command is:

```bash
composer quality
```

It validates the root and all five package manifests in lexicographic order, audits the locked dependency graph, checks the actual runtime platform, checks formatting, performs maximum-level static analysis with strict rules, and runs tests. A passing gate means those configured checks passed for the current checkout and environment; it is not a claim of framework completeness, production readiness, or security certification.

## Guides

- [Toolchain and contributor commands](toolchain.md)
- [PHP style](php-style.md)
- [Static analysis](static-analysis.md)
- [Testing](testing.md)
- [Dependency security](dependency-security.md)
- [Package layout](package-layout.md)
- [API status](api-status.md)
- [Application Graph structure](application-graph-structure.md)
- [Explicit typed dependency resolution](dependency-injection.md)
- [Error handling and actionable diagnostics](error-handling.md)

## Authority and exceptions

These controls are repository architecture, not optional developer preferences. Do not bypass a failure, weaken policy, authorize a Composer plugin, add a dependency, create a PHPStan baseline, or add a suppression without architect approval. When an approved tool appears to conflict with an approved requirement, stop and report the conflict rather than making the gate artificially green.

**Stability:** Internal engineering policy for the pre-alpha repository. It does not create a public Kongono framework API.
