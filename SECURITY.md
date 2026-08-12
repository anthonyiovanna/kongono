# Security Policy

## Project status

Kongono is pre-alpha and currently contains architectural governance and documentation rather than runnable framework functionality. There are no stable public APIs or supported production releases.

## Reporting a vulnerability

Kongono does not yet have an authorized private vulnerability-reporting address or disclosure workflow. Do not publish suspected sensitive vulnerability details in a public issue. Request a private reporting channel from the project maintainers or architect before sharing reproduction details.

The reporting address, response targets, disclosure timeline, supported-version policy, and security advisory process remain open governance decisions.

## Architectural security expectations

Planned framework work must make authorization, trust, identity, delegation, and security metadata deterministic and inspectable. AI output may assist analysis but must never be authoritative for authorization or security policy.

Extensions must not automatically receive unrestricted access to databases, filesystems, secrets, networks, shells, or processes. The planned Extension Host will govern permissions, capabilities, lifecycle, storage, network access, secrets, events, UI contributions, agent capabilities, updates, removal, and health. Exact mechanisms remain open and must not be inferred from this policy.

Persistent runtimes require explicit lifecycle and operation scopes so that identity, authorization, tenant, request, and sensitive state cannot leak between operations. Runtime-specific security contracts have not yet been designed.

## No security guarantees yet

This repository is not production-ready. Planned security architecture is not implemented security functionality.
