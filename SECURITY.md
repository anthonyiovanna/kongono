# Security Policy

Kongono is engineered from the ground up for **fail-closed security, persistent-runtime isolation, multi-tenant data safety, and governed AI execution**.

## Supported Versions

| Version | Supported | Notes |
| :--- | :---: | :--- |
| **1.7.x** | :white_check_mark: | Active security & bugfix releases |
| **1.6.x** | :white_check_mark: | Security patches only |
| **< 1.6** | :x: | Upgrade to `^1.7` (`Encryptor` automatically decrypts legacy `v1.` ciphertexts while emitting `v2.`) |

## Reporting a Vulnerability

**Please do NOT report security vulnerabilities via public GitHub issues, discussions, or pull requests.**

Instead, report vulnerabilities privately via **GitHub Security Advisories (GHSA)**:

1. Open the **Security** tab of `anthonyiovanna/kongono` -> **Advisories** -> **Report a vulnerability**.
2. Include:
   - Affected package(s), file path(s), and Kongono version (`php vendor/bin/kongo version`).
   - Minimal, deterministic reproduction steps or proof-of-concept test case.
   - Impact assessment (e.g., auth bypass, cross-tenant read/write, SSRF, XSS, RCE, DoS).

### Response SLA

- **Acknowledgement:** within **48 hours**
- **Triage & Severity Assessment (CVSS v4.0):** within **5 business days**
- **Fix & Coordinated Disclosure:** Critical/High issues target a patch release + GHSA/CVE publication within **7–14 days** (with full researcher credit unless anonymity is requested).

## Security Architecture & Invariants

Kongono enforces the following invariants continuously in CI:

1. **Zero Legacy / Zero Symfony Runtime Bloat:** Pure PHP 8.5+ with zero external framework runtime dependencies.
2. **Cryptographic Domain Separation:** HKDF-SHA256 isolates keys across `Encryptor` (`v2.` XChaCha20-Poly1305 with version-bound AAD), `SignedUrl`, `MagicLinkAuth`, and `TokenSigner`.
3. **Fail-Closed Egress & SSRF Defense:** `SafeHttpClient` and `CurlTransport` reject non-canonical IPv4 literals (`127.1`, octal, hex, decimal, IDN), enforce an IANA Global Unicast IPv6 (`2000::/3`) allowlist minus RFC 6890 special-use blocks, and pin resolved IPs.
4. **Two-Layer Multi-Tenant Isolation:** Application-layer `QueryBuilder::forTenant()` / `requireTenant()` (across `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `UPSERT`) paired with database-enforced PostgreSQL `RowLevelSecurity` (`FORCE ROW LEVEL SECURITY` + transaction-local `set_config(..., true)`).
5. **WHATWG HTML5 Allowlist Sanitization:** `HtmlSanitizer` + `@safeHtml(...)` parse untrusted HTML via PHP 8.5's native Lexbor `Dom\HTMLDocument` and reconstruct strictly allowlisted output.
6. **Governed AI & MCP Execution:** `PromptGuard` canary isolation, `UntrustedInput` Unicode/delimiter neutralization, `#[RequiresApproval]` human-in-the-loop gates, undeclared tool denial, and strict token/cost/call budgets.
7. **Continuous Verification & Supply-Chain Integrity:**
   - PHPStan Level 9 + strict rules (`0` errors)
   - Automated parser fuzzing & property-based testing (`composer fuzz`)
   - In-process red-team attack simulation (`kongo audit --attack --strict`) and 12-vector PromptGuard eval (`kongo eval --redteam`)
   - Automated `composer audit --locked` dependency advisory checks
   - GitHub Releases ship with a **CycloneDX 1.5 JSON SBOM**, **SHA-256 checksums**, and **SLSA Build Provenance attestations**.

## Verifying Release Artifacts

```bash
# Verify SHA-256 checksums
sha256sum -c SHA256SUMS.txt

# Verify SLSA Build Provenance via GitHub CLI
gh attestation verify kongono-v1.7.0.tar.gz --repo anthonyiovanna/kongono
gh attestation verify kongono-v1.7.0.cdx.json --repo anthonyiovanna/kongono
```
