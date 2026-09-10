# Kongono External Service Contract

`kongono/external-service-contract` is an optional Experimental neutral value
and port package for secret-free external-service bindings. It sits above the
accepted Phase 15 network-egress contracts and below future binding artifacts,
resolver and credential-source implementations, runtime coordination, and
service/provider adapters.

The package reuses `CanonicalOrigin`, `TransportCapabilitySet`,
`EgressResolver`, and `EgressResolutionResult`. A binding identifies one
profile, endpoint, credential reference, and required capability set; it is
inert evidence and grants neither credential access nor network access.

Credential material appears only as the sensitive parameter of
`CredentialMaterialConsumer::consume()`. A `CredentialLease` lends material to
that explicit caller-owned consumer and returns no secret string. The
interfaces cannot prevent a consumer from copying material, and ordinary PHP
strings provide no reliable memory-zeroization guarantee.

The package provides interfaces, values, lifecycle/result vocabulary,
actionable diagnostics, and nondisclosing exceptions only. It provides no
resolver, credential source, lease implementation, artifact, generated code,
runtime orchestration, provider adapter, command, discovery, registry, cache,
environment access, filesystem access, DNS, socket, HTTP client, or network
effect. Phase 16C and later phases remain planned and separately authorized.

All declarations except the Public exception-family marker and the Internal
diagnostic/exception guard machinery are Experimental.
