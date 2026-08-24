# Minimal Kongono application

This copy-once skeleton demonstrates Kongono's Experimental generated application workflow. Inside the framework repository, Kongono owns and tests these template bytes. After copying the directory, every non-generated file belongs to the application.

Install dependencies with `composer install`, then use only these application-owned commands:

```text
composer app:build
composer app:check
composer app:run
```

`app:build` publishes exact content-addressed Composition and generated PHP files beneath `var/kongono/build/`. Rebuilding identical application state reuses those files. Changed state publishes new identities; old derived files are not selected or deleted. `app:check` performs no writes and fails closed unless both exact current files exist. There is no current/latest pointer or fallback.

`app:run` checks before loading, executes one fixed local `GET /` request through the installed `php-cgi` executable, emits the response body, and exits. The CGI snapshot includes the inert fixed query marker `kongono_proof=1` so the one-shot Windows executable honors its command-line hardening settings; routing and application behavior remain bound to `/` and do not depend on that marker. It is a bounded development proof, not a listener, development server, process manager, or deployment workflow.

`vendor/` and generated build contents are derived and replaceable. Kongono never upgrades, merges, or overwrites a copied skeleton automatically; adopting a later template is an explicit reviewed application change.
