# Extension Inspection and Developer Intelligence

Phase 12H externally proves this detached aggregate boundary and the supported
human and machine `extension:inspect` forms. Inspection still establishes no
caller-relative currentness, host state, execution, health, trust,
installation, authorization, or production readiness. See
[Phase 12 external acceptance](phase-12-external-acceptance.md).

## Purpose and context

Phase 12G provides one bounded detached inspection boundary above the accepted declaration and generated-wiring evidence. Use it when a caller already has two explicit artifact paths and needs presentation-neutral Safe aggregate evidence. Do not use it to decide whether an extension is current, trusted, healthy, approved, installed, authorized, or production-ready.

## Inputs and validation

The loader accepts exactly one Phase 12C canonical declaration-artifact JSON file and one Phase 12E/F generated-wiring PHP file. Both must be safe, bounded, stable ordinary files with no linked or reparse-backed traversal. The existing package-owned validators prove structural validity. Their declaration-set identities must match exactly.

Generated PHP is validated as bytes and is never loaded, required, independently tokenized, reflected, executed, instantiated, or retained by inspection. Neither validator's currentness method is called. Structural validity therefore leaves currentness `not-established`.

## Output and visibility

The inspection contains exact artifact, declaration-set, admission, and grant-plan identities; the extension contract version; bounded aggregate counts; and total ordered maps for every current category, lifecycle, and contribution-visibility case. Capability totals distinguish required and optional requests. Raw declarations, extension identities, provenance, capabilities, contribution details, paths, class names, namespaces, and generated code remain omitted.

Every view carries these limitation claims: scope `extension-platform-v1`, visibility `safe-aggregate-only`, currentness `not-established`, authority `evidence-only`, host state `not-observed`, execution `not-observed`, and trusted-code isolation `not-sandboxed`.

## Chie, Hantei, and Oshie

Role addressing changes only the role token. The package has no Chie, Hantei, or Oshie dependency and grants those tools no authority. Independent verification compares identity binding, declaration counts, category/lifecycle counts, capability counts, contribution counts, visibility semantics, and authority/runtime semantics in that order. Failures are fixed ordered tokens and never interpolate caller content.

## Supported command

`kongo extension:inspect` is read-only, noninteractive, and observe-only. It requires `declaration-artifact-path` and `generated-wiring-path`. Its successful record is Safe aggregate evidence; rejected input maps to one fixed precondition diagnostic without disclosing either path or private input bytes.

## Security, lifecycle, and limitations

The Extension Host is not observed. No extension is started, stopped, retired, or recovered. Trusted/in-process PHP is not sandboxed. Inspection performs no extension discovery, installation, update, removal, marketplace or remote lookup, capability grant, self-authorization, model or agent call, auto-fix, or mutation. Kongono remains Experimental, pre-1.0, and not production-ready. Phase 12H externally proves this boundary without changing it.

## Related material

- [ADR 0137](../adr/0137-visibility-safe-extension-inspection-and-developer-intelligence-evidence.md)
- [Extension declaration artifact](extension-declaration-artifact.md)
- [Extension capability grants and generated wiring](extension-capability-grants-and-generated-wiring.md)
- [Trusted in-process Extension Host](trusted-in-process-extension-host.md)
- [Developer intelligence](developer-intelligence.md)
- [Phase 12 roadmap](phase-12-roadmap.md)
