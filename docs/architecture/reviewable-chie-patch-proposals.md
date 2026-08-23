# Reviewable Chie Patch Proposals

## Purpose and status

Phase 4G extends the Experimental `kongono/chie` package with one deterministic
candidate-proposal operation. It helps prepare a reviewable documentation change
without applying it. The candidate remains derived material; the exact accepted
source remains authoritative.

Use this operation only for one explicit exact-line replacement in a current
Phase 4C artifact source classified as current explanatory documentation. Do
not use it for arbitrary application understanding, arbitrary code generation,
governing sources, implementation files, tests, manifests, lock files, reports,
directives, hidden paths, or workspace discovery.

## Calling contract

The non-interactive CLI grammar is:

```text
kongono-chie propose <framework-root> <artifact-path> <proposal-spec-path>
```

The proposal specification is compact canonical UTF-8 JSON with one terminal
LF and these ordered keys: `schema`, `version`, `rule`, `disclosure`,
`target_path`, `base_sha256`, `before`, and `after`. Its fixed values are
`kongono.chie-proposal-request`, version `1`,
`exact-line-replacement-v1`, and disclosure `safe`.

`before` and `after` are detached caller-asserted Safe, LF-only complete lines.
That assertion permits bounded presentation; Chie does not sanitize, interpret,
or elevate the text. The base identity must equal the uppercase SHA-256 of the
one exact current artifact source.

## Output and review

A successful response has status `candidate` and includes exact artifact,
source-set, base, proposed, and proposal identities. The forward and reverse
unified diffs contain three available context lines and no timestamps. Exact
provenance binds the pre-change source and replaced complete-line range.

The three warnings say that the generated candidate is not authority, was not
applied, and was not verified. Its Hantei handoff is explicitly
`pending-human-application`. A human-controlled tool may later review and apply
the diff. The resulting subject then requires a fresh independent Hantei run.
Hantei verification of the unchanged repository does not verify this candidate.

## Security, failure, and lifecycle

The spec, artifact, source, and candidate are inert data. Proposal construction
performs no network, model, provider, prompt, search, process, shell, write,
rename, delete, Git, stage, commit, publish, deploy, or Hantei operation. It
retains no request, path, artifact, diff, proposal, stream, or failure state.

Invalid grammar uses fixed stderr and exit 64. Invalid, stale, or unsupported
proposal state uses bounded stdout and exit 65. Unavailable explicit inputs use
bounded stdout and exit 66. Unexpected failures use fixed stderr and exit 70.
Failures disclose no rejected text, hash, path, source, native message, or trace.
This bounded presentation does not claim PHP native trace APIs are sanitized.

Phase 4H externally proves one real proposal from the current artifact, exact
identity and fixed warnings, detached forward application, detached reversal,
and unchanged repository bytes. The success result labels the candidate
`unapplied-unverified`. Hantei verifies only actual subjects and cannot certify
the unapplied candidate. Chie does not guarantee a fix or replace human review,
and Kongono remains pre-1.0. Phase 5 was not begun.
