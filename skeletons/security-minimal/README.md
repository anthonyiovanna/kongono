# Minimal Security Application

This directory is a copy-once example of Kongono's supported Phase 9G security build, check, and application workflow. Kongono owns these bytes only while they remain in the framework repository. After copying the directory, every non-generated file is application-owned; Kongono does not merge, patch, watch, or overwrite the copy.

Run `composer install`, then `composer app:build`, `composer app:check`, and `composer app:run`. Build publishes the exact Composition and rule-free policy identity artifacts into the existing `var/kongono/build` directory. Check performs no writes and rejects missing, stale, or tampered output. Run loads those exact outputs, begins an anonymous browser-session operation with no reserved cookie, obtains an application-owned exact Permit for one fixed public example capability, executes only through `ProtectedCapabilityEnforcer`, and closes the operation. It then proves an exact Deny prevents capability execution.

`PublicExampleEvaluator` is deliberately narrow example policy code, not framework policy or a production authorization model. Replace it with an application-owned evaluator whose semantics are reviewed for the application's exact actions, resources, environmental facts, and current policy evidence. Never replace it with wildcard permission, role-string shortcuts, middleware authority, or an ambient current user.

`NoSessionVerifier` is registered explicitly for `browser.session.v1` only to make the anonymous example complete. It rejects every presented cookie and is not a production provider. A real verifier must validate live application-owned session state and bind CSRF evidence using reviewed current cryptographic facilities without exposing identifiers or credentials.

Generated outputs and `vendor/` are derived and ignored. The scripts never clean or overwrite outputs, create directories, reveal native exception details, or treat authentication success as authorization.
