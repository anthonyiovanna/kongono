# Phase 4 External Acceptance

## Purpose and status

Phase 4H closes the bounded Phase 4 developer-intelligence milestone with a
root-owned, test-owned external harness. The harness proves the already
implemented Knowledge, Chie, Hantei, and Oshie boundaries together without
turning them into one service or enlarging any role's authority.

Run it from the repository root:

```text
composer acceptance:phase4
```

Success is one compact canonical JSON object containing the exact artifact,
source-set, real Hantei profile, and real Hantei subject identities. The object
lists these 28 scenarios in order:

```text
accepted-sources-readable
knowledge-generation-a
knowledge-generation-b-identical
knowledge-current-validation
cross-role-artifact-identity
chie-current-explanation
chie-current-diagnosis
chie-candidate-proposal
chie-candidate-reversal
chie-candidate-unapplied
oshie-current-catalog
oshie-current-lesson
oshie-current-exercise
oshie-current-correct
oshie-current-incorrect
offline-knowledge-available
offline-chie-available
offline-oshie-available
missing-source-visible
stale-chie-visible
stale-oshie-visible
invalid-artifact-visible
malicious-instructions-inert
protected-context-not-disclosed
hantei-current-pass
hantei-synthetic-fail
hantei-synthetic-blocked
roles-do-not-mutate-or-elevate
```

## Evidence model

Knowledge generates the current artifact twice into separate temporary output
directories, and the harness requires byte-identical artifacts and identities.
The real Knowledge validator then proves one artifact current. Chie explanation,
diagnosis, and proposal plus Oshie catalog, lesson, exercise, correct answer,
and incorrect answer all consume that same artifact and report the same current
artifact/source-set identity where their schemas define it. Citations resolve
back to the exact accepted embedded source bytes and line ranges.

The Chie candidate uses the Phase 4G exact PHP-support line replacement. The
harness applies its forward transformation only to detached bytes, proves the
proposed digest, reverses it to the base bytes, and proves the repository target
never changes. The candidate remains `unapplied-unverified`, with its fixed
warnings and pending human/fresh-Hantei handoff.

## Real and synthetic Hantei results

The current-repository proof invokes literal `composer hantei:verify`. A valid
Phase 4 checkpoint requires real `PASS`, all eight fixed checks passing, no
uncertainty, and the same current artifact/source-set identities.

Separate isolated temporary Git subjects use exact current protected source
bytes and the unchanged root Composer script graph. A test-owned
Composer-compatible evidence tool makes only the `tests` check fail for the
synthetic `FAIL` case and makes only locked-advisory evidence unavailable for
the synthetic `BLOCKED` case. These results test Hantei's evaluator and
non-mutation boundary. They are explicitly synthetic and are never current
framework quality evidence.

## Offline and failure behavior

Knowledge, Chie, and Oshie successful operations are repeated with network
proxies directed to an unreachable loopback endpoint and Composer networking
disabled. Their outputs must remain byte-identical and must not disclose the
unique environment marker. Ordinary framework operation and direct accepted
documentation remain available without AI or network access.

Detached source states prove missing and stale inputs. Tampered artifacts prove
bounded invalid behavior through Knowledge, Chie, and Oshie. Embedded
instructions to invoke tools, write files, stage, commit, publish, or deploy are
treated as inert source bytes. Rejected literals, Protected values, secret
markers, native failures, environment values, and temporary paths do not enter
bounded response or error bytes.

Hantei may return structured `BLOCKED` when locked advisory evidence is
unavailable; it must never guess `PASS` or `FAIL`. The checkpoint itself still
requires a separate real current-repository `PASS`.

## Filesystem and process lifecycle

The harness creates one unpredictable temporary directory outside the
repository, verifies its resolved containment and ordinary non-reparse shape,
and writes every artifact, source copy, proposal specification, synthetic
subject, and evidence tool only there. Cleanup is mandatory on success and
failure and is completed before success output. Processes use argument vectors,
bounded independent streams and timeouts, exact exit capture, termination on
overflow or timeout, and complete handle cleanup.

No role receives file-write, Git, publication, deployment, or production
authority. Knowledge publication is limited to the explicit temporary output.
Chie cannot apply or certify its proposal. Hantei cannot repair its subject or
certify unapplied bytes. Oshie teaching cannot establish doctrine or proposal
authority. Its accepted curriculum has no executable example, and Phase 4H
does not invent one.

## Milestone boundary

At the accepted Phase 4 checkpoint, Phase 4A through 4H are implemented and
externally accepted within their documented bounds. The roles remain optional
developer tools rather than application Agent Principals. No model, provider,
SDK, prompt service, embedding, vector store, semantic search, RAG, account,
subscription, or external transmission exists.

This closes Phase 4 only. Kongono remains Experimental, pre-1.0, and not
production-ready. No Phase 5 capability, package, roadmap, or implementation is
selected or implied.
