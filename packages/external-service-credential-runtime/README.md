# Kongono External Service Credential Runtime

This optional Experimental package coordinates one operation-scoped credential lease. An application explicitly selects a `CredentialLeaseSource`, creates a secret-free `CredentialLeaseRequest`, supplies the matching source identity, a `CredentialMaterialConsumer`, and a monotonic clock, then calls one reusable `CredentialLeaseRuntime` instance for one synchronous operation at a time. Applications without external credentials do not install this package.

The runtime calls the selected source once, validates the returned lease's exact reference, purpose, and acquired state, delegates directly to `$lease->withMaterial($consumer)` at most once, and attempts retirement exactly once for every acquired lease. It checks the inclusive maximum lifetime after acquisition, after material use, and after successful retirement. These checks are cooperative observations; they cannot interrupt a blocking collaborator and provide no portable deadline or cancellation guarantee.

The runtime never receives credential material as a parameter or return value. The lease implementation alone passes borrowed material to the existing consumer parameter. This boundary cannot control copies held by the application, source, lease, consumer, extensions, PHP, or the operating system, and it does not promise memory zeroization.

Operation and cleanup failures remain separate. A primary failure with successful retirement produces cleanup-complete terminal evidence. A retirement failure produces uncertain terminal evidence and an exact detached one-item cleanup-failure list. When both occur, both original throwable objects remain deliberately accessible. Foreign throwable objects and their text are not presentation-safe; package messages, diagnostic context, and `__toString()` remain bounded and nondisclosing.

The package supplies no credential source, store, environment lookup, file lookup, keychain, vault, cloud integration, database, provider adapter, service locator, registry, refresh, renewal, rotation, pool, cache, retry, fallback, networking, background work, or authorization decision. Credential access and network access remain independent caller-owned permissions. Phase 16F application orchestration remains separately planned.

## Stability

The package is Experimental. Its sole Public declaration is the package exception marker. Runtime evidence is inert: it neither grants credential access nor establishes external truth.
