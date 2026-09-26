# Kongono Storage Runtime

`kongono/storage-runtime` is the separately optional Experimental exact-current
operation runtime for the accepted neutral Storage Platform. It binds one
explicit current profile artifact to one directly supplied neutral adapter,
validates requests before effects and distrusts all returned evidence.

The runtime supplies no adapter discovery, local-filesystem dependency,
credentials, authorization, application workflow, command, retry, fallback,
cloud compatibility, or production-readiness promise. Read sources and write
sources have explicit operation-scoped ownership and cleanup.
