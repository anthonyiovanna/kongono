# Kongono View Application

`kongono/view-application` is the optional Experimental composition boundary for one explicit compiled-view source and its exact-current content-addressed artifact.

The package provides deterministic write-free planning, explicit publication, write-free currentness checks, authenticated loading, bounded rendering, and explicit PSR-7 response adaptation. It does not discover sources or artifacts, compile at runtime, route requests, select browser policy, emit responses, or retain operation state.

The exception marker is the package's only Public declaration. All workflow, profile, application, and summary values are Experimental; implementation helpers and diagnostics are Internal.
