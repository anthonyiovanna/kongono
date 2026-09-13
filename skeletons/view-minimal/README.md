# Kongono compiled-view minimal application

This copy-once example owns one structured view source and serves one rendered
`GET /` response through Kongono's real routing, middleware, web-runtime,
PHP-CGI, HTTP-message, compiled-view, and response-adaptation paths. The extra
`kongono.composition.json` and `var/kongono/build/.gitignore` files are required
by that real web-runtime composition path.

The view adapter first verifies its exact `Content-Length`; the application then
removes only that transport-framing header because the accepted CGI emitter
computes and emits the final length itself.

After copying the directory, point the Composer path repository at the Kongono
`packages` directory, then run:

```console
composer install --no-interaction
composer view:build
composer view:check
composer view:run
```

Build explicitly publishes deterministic composition and view artifacts. Check
is byte-for-byte write-free. Run checks both artifacts before loading them and
executes one bounded local CGI request; it never listens on a port or compiles.
Generated files under `var/kongono` and `vendor` are not application source and
must not be committed. This development proof is not a production server.
