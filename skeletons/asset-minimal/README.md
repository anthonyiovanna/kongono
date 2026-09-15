# Asset minimal application

This copy-once application demonstrates Kongono's explicit native/no-bundler asset workflow. After copying, the non-generated files are application-owned; Kongono does not upgrade or merge them.

Run `composer asset:build`, `composer asset:check`, and `composer asset:resolve`. Build output is content-addressed beneath `var/assets` and is intentionally ignored. Check and resolve are write-free. The application explicitly chooses its public URL, cache, integrity, and preload policies.

`ViewAssetContext` shows how application code can map already resolved metadata into ordinary Phase 18 presentation values. Neither package discovers the other and no HTML tags are generated.
