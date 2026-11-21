# Security Policy

## Supported Versions

| Version | Supported |
| --- | --- |
| `1.x` | Yes |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, report them privately using GitHub Security Advisories or by emailing `anthonyiovanna@gmail.com` with:

- A clear description of the vulnerability and impact
- Minimal reproduction steps or a failing PHPUnit test case
- Affected Kongono package(s) and PHP version

We aim to acknowledge reports within 48 hours and provide a coordinated fix and advisory as quickly as possible.

## Security Design Guarantees

Kongono enforces fail-closed security defaults throughout the framework:

- **Nondisclosing Diagnostics:** `SecretScrubber` automatically redacts API keys, Bearer tokens, passwords, and connection strings from logs, `DevErrorRenderer`, `HealthCheck` errors, `McpServer` tool output, and AI prompts.
- **Debug Isolation:** `DevDashboardMiddleware` (`/_kongono`) and stack-trace rendering in `DevErrorRenderer` fail closed when `$app->isDebug()` is `false`.
- **SSRF Hardening:** `SafeHttpClient` and `WebhookDispatcher` enforce HTTPS, optional host allowlists, DNS IP validation rejecting loopback/private/link-local/cloud-metadata IPs, and `CURLOPT_RESOLVE` IP pinning.
- **Modern Cryptography:** Argon2id password hashing (`PasswordHasher`), AEAD `XChaCha20-Poly1305` (`Encryptor` via `ext-sodium` with key rotation), constant-time HMAC-SHA256 (`TokenSigner`, `SignedUrl`, `WebhookSigner`), and RFC 6238 `Totp`.
- **SQL & Path Containment:** All `QueryBuilder` values are bound via real PDO prepared statements (`ATTR_EMULATE_PREPARES = false`), unscoped `UPDATE`/`DELETE` fails closed, and `LocalStorage`, `ViewEngine`, and `ProjectContext` reject path traversal and `.env` exposure.
