# Storage Minimal

This copy-once example uses one explicit profile source and exact content-addressed artifact. It does not create a profile-output directory or object root. Supply existing canonical absolute directories. Keep the object root separate from the application and its profile-output directory.

After installing the copied application's dependencies, run:

```text
php bin/storage.php plan <application-root> kongono.storage.json <existing-profile-output-directory>
php bin/storage.php build <application-root> kongono.storage.json <existing-profile-output-directory>
php bin/storage.php check <application-root> kongono.storage.json <existing-profile-output-directory>
php bin/demo.php <application-root> kongono.storage.json <existing-profile-output-directory> <existing-separate-object-root>
```

The demo uses an application example policy for one namespace and logical object. It is not authentication or a production authorization policy. The demo creates only if absent, reads with explicit close, and mutates only with versions returned by completed operations. A pre-existing object is left intact. Conflicts and uncertain outcomes are reported without retry or success claims.
