# Phase 12 External Extension Platform Acceptance

## Purpose and status

Phase 12H adds the root-owned `composer acceptance:phase12` process harness and
closes the accepted Phase 12 Extension Platform milestone. The harness is
repository acceptance evidence, not a production API, installed application
feature, loader, registry, installer, marketplace, or extension authority.

## Accepted process contract

The command accepts no arguments and uses real accepted package APIs plus the
real `vendor/bin/kongo extension:inspect` executable. It operates in one unique
system-temporary directory outside the repository, bounds captured stdout and
stderr, rejects unsafe path forms, fingerprints the repository before and
after, cleans every child process, and removes all temporary state.

Success is one canonical JSON document with the
`kongono-phase12-extension-platform` profile. Failure exposes only the fixed
`phase12_acceptance_failed` token. Neither form exposes fixture secrets,
implementation classes, capability purposes, contribution payloads,
provenance, absolute paths, environment secrets, generated PHP, or exception
details.

## Evidence covered

The harness proves the coherent path from exact v1 declarations through
deterministic declaration artifacts, explicit selection and admission,
attributable neutral Application Graph IR, explicit capability grant or denial,
deterministic ordinary PHP wiring, exact one-file trusted in-process hosting,
detached visibility-safe inspection, the supported human and machine CLI
forms, and deterministic Chie/Hantei/Oshie role evidence. It also exercises
tampering, stale evidence, conflict, mismatch, incomplete planning, host
lifecycle, retirement, repeatability, concurrency, failure, disclosure,
repository preservation, and cleanup paths.

These proofs do not merge the underlying authorities. Declaration evidence is
not admission, a request is not a grant, neutral Graph IR is not an
authoritative Graph or execution authority, and visibility is not
authorization. Generated PHP is loaded only by the explicit host path, and
trusted in-process PHP is not sandboxed.

Inspection remains detached structural evidence. It does not establish
caller-relative currentness, host state, execution, health, trust,
installation, authorization, or production readiness. Role views are bounded
evidence rather than executing agents or self-authorizing decisions.

## Outside this boundary

Sandboxed and external execution, package installation/update/removal,
marketplace and distribution, publisher trust and signing, remote networking,
Agent Principal runtime, frontend extension APIs, Stable/1.0, and
production-readiness certification remain outside Phase 12.

Directives and task reports are excluded from the checkpoint. Phase 12H creates
one local checkpoint and authorizes no push, tag, release, second commit, or
Phase 13 work.

## Related material

- [ADR 0138](../adr/0138-phase-12-external-extension-platform-acceptance-and-checkpoint.md)
- [Phase 12 roadmap](phase-12-roadmap.md)
- [Extension Platform](extension-platform.md)
- [Extension inspection and developer intelligence](extension-inspection-and-developer-intelligence.md)
- [Trusted in-process Extension Host](trusted-in-process-extension-host.md)
