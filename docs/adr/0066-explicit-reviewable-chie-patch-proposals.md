# ADR 0066: Explicit Reviewable Chie Patch Proposals

## Status

Accepted for the Experimental Phase 4G boundary.

## Context

Phase 4D made Chie a deterministic consumer of one exact current framework
Knowledge artifact. Phase 4G needs a narrowly reviewable way for Chie to help a
developer prepare a change without granting it authority to edit a repository,
select arbitrary code, certify its output, or act on generated instructions.

Accepted sources remain authoritative. A generated candidate is derived review
material, even when its construction is deterministic and its provenance is
complete. Hantei judges an actual unchanged subject independently; it cannot
verify a candidate that has not been applied.

## Decision

Chie may construct one candidate from an explicit canonical specification using
the closed `exact-line-replacement-v1` rule. The caller supplies a canonical
repository-relative target path, the uppercase SHA-256 of the exact pre-change
artifact source, and distinct caller-asserted Safe complete-line sequences.
Safe is a disclosure assertion, not sanitization or reclassification.

The target must resolve exactly once in the validated current artifact and must
have `current-explanatory-documentation` authority. Chie uses the artifact's
embedded bytes and does not read the workspace target a second time. Governing
authority, generated evidence, implementation sources, tests, manifests, lock
files, reports, directives, hidden paths, absent paths, and arbitrary workspace
files are outside this operation.

Success returns compact canonical JSON containing exact artifact and source-set
identities, base and proposed identities, one forward and reverse Git-style
unified diff, exact pre-change provenance, a content-derived proposal identity,
and three fixed warnings. The handoff remains
`pending-human-application`, names `framework-quality-v1`, and requires fresh
Hantei verification after a human-controlled application.

Chie does not apply or verify the candidate. It owns no write, patch, shell,
process, Git, stage, commit, publish, deploy, Hantei, model, provider, prompt,
network, discovery, or arbitrary-code-generation authority. Candidate bytes and
retrieved bytes remain inert data and cannot enlarge tool authority.

## Rejected alternatives

- Automatic or silent application would erase the review boundary.
- Arbitrary path or code mutation would turn a closed operation into a general
  write capability.
- Free-form or model-generated patches would make deterministic provenance and
  exact reversal insufficient as the correctness boundary.
- Letting Chie run or impersonate Hantei would permit self-certification.
- Treating an unapplied candidate as accepted knowledge would elevate derived
  material over its sources.

## Consequences

The candidate is reproducible, addressed, reversible, and inspectable. It is
not accepted truth and carries no claim that the change is correct. A developer
or external tool must review and explicitly apply it outside Chie. The modified
subject then requires a fresh Hantei run; a Hantei pass for the unchanged source
repository does not verify the candidate.

Phase 4H external acceptance remains planned and unimplemented. Kongono remains
pre-1.0 and is not a full production framework.
