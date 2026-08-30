# Kongono Application Graph Intelligence

Experimental `kongono/application-graph-intelligence` derives one bounded,
visibility-safe view from an already validated Application Graph artifact
inspection. The view addresses Chie, Hantei, or Oshie as a purpose audience;
the role token grants no tool, read, write, network, security, or runtime
authority.

The package copies only artifact and Graph identities, contract version, total
counts, and the complete closed node/relation vocabulary counts. It attaches
fixed `composition-v1`, `safe-aggregate-only`, `not-established`, and
`evidence-only` semantics plus explicit omission, currentness, and authorization
uncertainties. Omitted facts are unavailable or out of scope, never false.

An independent verifier compares a candidate view with the supplied inspection
without invoking the deriver, repairing the view, exposing mismatch values, or
granting authority. Both operations are deterministic, offline, stateless, and
bounded by the closed version-1 vocabulary.

No artifact path or bytes, raw Graph, individual fact, field, provenance, source
location, arbitrary query, model, provider, prompt, network, cache, registry,
watcher, mutation, Git, deployment, production, or runtime integration exists.
Currentness relative to Composition, source, generated wiring, workspace,
deployment, or runtime is not established. Existing Chie, Hantei, and Oshie
operations remain unchanged. Kongono is pre-1.0 and not production-ready.
