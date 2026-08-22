# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0P

## Presentation-Neutral Diagnostic Value and Closed-Schema Foundation

## Mission

Implement the smallest complete runtime foundation selected by Accepted ADR
0028 without connecting it to the existing Kernel, Application Graph, or
Dependency Injection exceptions.

Phase 0P creates `kongono/diagnostics` with immutable bounded diagnostic values,
closed typed schemas, deterministic text representation, package-local
actionable failures, and production tests. It resolves the PHP construction,
manifest, structural-schema, and text-representation decisions needed for this
foundation only.

Phase 0P does **not** add context to the existing 42 Kernel/Graph/DI failures,
change their markers or constructors, implement a renderer, or select any
transport or disclosure policy.

---

# 1. ROLE, AUTHORITY, AND MANDATORY STOP RULE

You are the executor, not the architect.

Read completely before editing:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `README.md`;
4. `SECURITY.md`;
5. `docs/doctrine.md`;
6. `docs/documentation-standard.md`;
7. `docs/architecture/runtime-model.md`;
8. `docs/engineering/api-status.md`;
9. `docs/engineering/error-handling.md`;
10. `docs/engineering/package-layout.md`;
11. `docs/engineering/php-style.md`;
12. `docs/engineering/static-analysis.md`;
13. `docs/engineering/testing.md`;
14. `docs/engineering/toolchain.md`;
15. ADRs 0003, 0006, 0014 through 0021, 0026, 0027, and 0028;
16. `docs/dev_plan/kongono_phase_0O_codex_directive.md`;
17. `docs/task reports/Phase_0O.md`;
18. both Phase 0O correction directives and reports; and
19. this directive.

The authorities above and this directive are cumulative. This directive
supersedes Phase 0O future-only wording only for the exact implementation
authorized here.

Stop before editing if:

- any locked Git identity, inventory, byte count, digest, stream, mode, or
  caller record does not match;
- a file outside the exact created/modified inventories must change;
- the exact API below is infeasible on PHP 8.5;
- Composer would update an unrelated third-party package or weaken policy;
- an existing Kernel, Graph, or DI runtime type would need to depend on the new
  package;
- a schema, renderer, transport, logging, telemetry, HTTP, CLI, source, incident,
  or exception-integration decision is missing; or
- a required quality or security check fails for a non-environmental reason.

Report the exact decision, options, tradeoffs, and recommendation. Do not infer
authority, widen scope, suppress a finding, or partially claim completion.

---

# 2. LOCKED ACCEPTED BASELINE

Before the caller copies this directive or creates its report placeholder, the
accepted repository is exactly:

```text
branch: main
only local branch: main
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
commit count: 5
HEAD subject: Establish Kongono framework baseline through Phase 0M
tracked files: 214
tracked modes: all 100644
tracked modifications: 13
staged paths: 0
untracked paths: 8
total status paths: 21
tracked diff: 13 files, 497 insertions, 32 deletions
tracked diff Git object ID: 3be355443ee88c8fc3466b5600a395866170ba05
remotes: 0
tags: 0
non-default alternate data streams: 0
```

The exact 13 modified tracked files and their current contents are:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `ARCHITECTURE.md` | 6,780 | `A0DAF583BDF98EB6DB9E5550CE0A6ABF96158855800065727F4981A7A1926D6B` |
| `README.md` | 4,641 | `5C7A49D90E8645D5BA1880F16BDC461985DFB7E02BBC8D2748E83B4D44AE59BA` |
| `SECURITY.md` | 5,628 | `922783D9796AEBD7DD36D1CB3B797E34340ECD72F0A66C2A4C99CCD0E740C25C` |
| `docs/adr/README.md` | 3,066 | `F588AF5DC132CB59017945B2D7B1C9F8F8A5FFD2CCEE78F8B8D88B9E1305775A` |
| `docs/architecture/overview.md` | 5,744 | `67F72E90A5687B1F8CBE9A13086FAC2801A12A573D85880B86F9586B8A76A117` |
| `docs/architecture/runtime-model.md` | 4,572 | `03C4A369DCE0897E11A18D688ED2A495D327F1F27C3FC0575922745D29AAD548` |
| `docs/doctrine.md` | 11,918 | `1CF4DFEF34D3084F36502120BC3B5C9F661B87ADA7075F4DDA2EEF70EABD2DC8` |
| `docs/documentation-standard.md` | 6,108 | `34B06410FBDC9785ED78226529098315EA00BE872A1794F3BB6BE7F5F45A60D3` |
| `docs/engineering/error-handling.md` | 41,828 | `C71C78E0DDCB2FE58FD29C9937C2ACAACD9864A842401F37100DAEBC8D55B4CA` |
| `docs/glossary.md` | 15,907 | `14310DE12DA133CCFFF7198C005FF99203686539A5FD070807BE8BCF9043D275` |
| `packages/application-graph/README.md` | 6,746 | `992B539A77C651027A52849E1E0E12BE7B55C31C79D4A44A18C3DE490173FDA9` |
| `packages/dependency-injection/README.md` | 6,514 | `6B5BEE0303C5865121E0CA9A5F08F7FD7A290DEECAD86A82D33FBB1254FA5285` |
| `packages/kernel/README.md` | 4,064 | `454F7186F2B03D3DE6824B52AAF1929FC3458FAABB8AA086AB3E295322A4F397` |

The exact eight untracked caller/architecture records are:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md` | 10,151 | `78E1DC7F4058A798EB411867FE06C817B1BABDD20BB7C6E58EF17A842ADF7016` |
| `docs/dev_plan/kongono_phase_0O_codex_directive.md` | 42,924 | `4FD514E5D92B2AD0AAAF852FABD48844C0C3F5CCE189F9DC4F9DBA2F9070FB5A` |
| `docs/dev_plan/kongono_phase_0O_1_codex_directive_correction.md` | 15,805 | `E916B3751C69BD7E917B11B461F6A32B61761F7F52557AC2164A952D7B16445C` |
| `docs/dev_plan/kongono_phase_0O_2_codex_directive_correction.md` | 13,840 | `60DF483DCB075B94F452FDE09CD87240B758DFC30578ECDA233B6A3BE773F90B` |
| `docs/task reports/Phase_0N_4_correction.md` | 17,247 | `8AFD9BC46D29B0DB0570B87C8B947C65AE82FD9C967DCCBDEDB68C3CEC62661C` |
| `docs/task reports/Phase_0O.md` | 21,340 | `5913B03A4CF6ADC22E298AF81B1819E462742F3A5982E9899202D87BE4AB17E6` |
| `docs/task reports/Phase_0O_1_correction.md` | 2,512 | `F3FD7B7F2CF91193C76E4060E0AEB71B6A94B4BC603AEB162E2970ED3A1B825B` |
| `docs/task reports/Phase_0O_2_correction.md` | 9,054 | `C9800BDF74F7B687B834815D0A4AEDD3BF1901806E0B09678CF98975156C5916` |

`composer.lock` is 177,449 bytes with SHA-256
`9AB5928904039B26A0C7EE7B0CEF25FAB78848978D158F33201F2FE06DAE5190`.

Canonical quality passes with zero advisories, platform success, 0 of 92 files
fixable, zero PHPStan errors, and 134 tests / 1,099 assertions.

After caller setup, these two additional paths must exist:

```text
docs/dev_plan/kongono_phase_0P_codex_directive.md
docs/task reports/Phase_0P.md
```

The copied directive must match the architect-delivered byte count and SHA-256.
The report must initially be exactly zero bytes with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

At that point the exact pre-edit state is 13 modified tracked paths, 10
untracked paths, zero staged paths, and 23 total status paths.

---

# 3. PHASE 0P BOUNDARY

Phase 0P implements one new package:

```text
kongono/diagnostics
Kongono\Diagnostics\
```

It owns:

- bounded immutable context and field values;
- the two-case disclosure vocabulary;
- the three-case scalar-kind vocabulary;
- closed presentation-neutral field and context schemas;
- deterministic byte-safe text representation;
- its own package-local diagnostic catalog and exception marker; and
- no domain meaning belonging to Kernel, Graph, DI, applications, transports,
  or renderers.

The schema objects are structural plans. Originating packages still own which
schema is correct for a diagnostic identifier and which values a throw site
supplies. A structurally valid consumer-created schema does not become a
Kongono-owned diagnostic schema by construction.

Phase 0P does not integrate the Accepted 42 Kernel/Graph/DI schemas. Those
catalogs, throws, exception constructors, and three Public markers remain
unchanged.

---

# 4. EXACT CREATED FILE INVENTORY

Create exactly these 20 implementation files:

```text
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
packages/diagnostics/composer.json
packages/diagnostics/README.md
packages/diagnostics/src/Context/DiagnosticContext.php
packages/diagnostics/src/Context/DiagnosticContextSchema.php
packages/diagnostics/src/Context/DiagnosticDisclosure.php
packages/diagnostics/src/Context/DiagnosticField.php
packages/diagnostics/src/Context/DiagnosticFieldSchema.php
packages/diagnostics/src/Context/DiagnosticValueKind.php
packages/diagnostics/src/Diagnostic/DiagnosticsDiagnostic.php
packages/diagnostics/src/Exception/DiagnosticsException.php
packages/diagnostics/src/Exception/ExceptionInterface.php
packages/diagnostics/src/Exception/InvalidDiagnosticValueException.php
packages/diagnostics/tests/Unit/DiagnosticContextSchemaTest.php
packages/diagnostics/tests/Unit/DiagnosticContextTest.php
packages/diagnostics/tests/Unit/DiagnosticDisclosureTest.php
packages/diagnostics/tests/Unit/DiagnosticFieldSchemaTest.php
packages/diagnostics/tests/Unit/DiagnosticFieldTest.php
packages/diagnostics/tests/Unit/DiagnosticValueKindTest.php
packages/diagnostics/tests/Unit/StructuralValueTest.php
```

Do not create fixture files. Use focused scalar inputs and existing declared
types. Do not place multiple production class-like declarations in one file.

The caller-created Phase 0P report is not an executor-created implementation
file.

---

# 5. EXACT MODIFIED FILE INVENTORY

Modify exactly these 28 tracked files:

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0003-monorepo-initial-development.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/php-style.md
docs/engineering/README.md
docs/engineering/static-analysis.md
docs/engineering/testing.md
docs/engineering/toolchain.md
docs/glossary.md
packages/application-graph/README.md
packages/dependency-injection/README.md
packages/kernel/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

No other tracked or existing untracked file may change. In particular, all
prior directives, reports, and ADR 0028 are immutable caller records.

---

# 6. PACKAGE AND DEPENDENCY CONTRACT

`packages/diagnostics/composer.json` must define:

```json
{
  "name": "kongono/diagnostics",
  "description": "Presentation-neutral bounded diagnostic values for Kongono Framework.",
  "type": "library",
  "require": {
    "kongono/kernel": "*",
    "php": ">=8.5"
  },
  "autoload": {
    "psr-4": {
      "Kongono\\Diagnostics\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kongono\\Diagnostics\\Tests\\": "tests/"
    }
  }
}
```

The Kernel dependency exists solely for `Api`/`ApiStatus` compatibility
metadata. Diagnostics production source may statically import from Kernel only:

```text
Kongono\Kernel\Api\Api
Kongono\Kernel\Api\ApiStatus
```

It must not import Kernel lifecycle, Application Graph, or Dependency Injection.
No existing package manifest gains a diagnostics dependency in Phase 0P.

Update the root manifest to:

- require `kongono/diagnostics: @dev` in lexicographic package order;
- add `Kongono\Diagnostics\Tests\` to root `autoload-dev`;
- validate packages in exact order: application-graph, dependency-injection,
  diagnostics, kernel; and
- preserve every existing dependency-security and script contract.

Update `composer.lock` only through Composer. Do not manually edit it. The lock
may add only the local `kongono/diagnostics` package and required root metadata;
no third-party version may change.

Use a scoped noninteractive Composer update for the new package, then inspect
the lock delta. If Composer proposes any unrelated package update, stop.

---

# 7. API STATUS AND TYPE INVENTORY

Phase 0P adds exactly ten production declarations:

| Type | Status |
| --- | --- |
| `Kongono\Diagnostics\Context\DiagnosticContext` | Public |
| `Kongono\Diagnostics\Context\DiagnosticContextSchema` | Public |
| `Kongono\Diagnostics\Context\DiagnosticDisclosure` | Public |
| `Kongono\Diagnostics\Context\DiagnosticField` | Public |
| `Kongono\Diagnostics\Context\DiagnosticFieldSchema` | Public |
| `Kongono\Diagnostics\Context\DiagnosticValueKind` | Public |
| `Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic` | Internal |
| `Kongono\Diagnostics\Exception\DiagnosticsException` | Internal |
| `Kongono\Diagnostics\Exception\ExceptionInterface` | Public |
| `Kongono\Diagnostics\Exception\InvalidDiagnosticValueException` | Experimental |

Every type must carry exactly the stated `#[Api(...)]`. Update the root exact
inventory from 30 to 40 declarations. The two Kernel metadata bootstrap types
remain the only self-classification exceptions.

The six presentation-neutral context/value/schema/vocabulary types are Public
at pre-alpha maturity. Public does not mean Stable; their exact contracts are
nevertheless intentional consumer surfaces under Kongono's current release
maturity. The concrete failure remains Experimental. Internal catalog/base
types are not consumer extension points.

Do not add per-member status, Stable, Deprecated, attributes beyond `Api`, or
an extension interface.

---

# 8. EXACT VALUE VOCABULARIES

## 8.1 `DiagnosticDisclosure`

Implement a string-backed enum with exactly:

```php
case Safe = 'safe';
case Protected = 'protected';
```

There is no `Secret`, `Public`, `Private`, `Redacted`, or fallback case.
Forbidden material is excluded before construction; disclosure is not a
sanitizer or transport decision.

## 8.2 `DiagnosticValueKind`

Implement a string-backed enum with exactly:

```php
case Text = 'text';
case Integer = 'integer';
case Boolean = 'boolean';
```

There is no float, null, array, object, enum, resource, JSON, list, map, nested,
lazy, or arbitrary kind.

---

# 9. `DiagnosticFieldSchema` CONTRACT

Implement a final readonly Public class with exactly five private
instance properties:

```text
key: string
label: string
kind: DiagnosticValueKind
disclosure: DiagnosticDisclosure
required: bool
```

Exact public constructor:

```php
public function __construct(
    string $key,
    string $label,
    DiagnosticValueKind $kind,
    DiagnosticDisclosure $disclosure,
    bool $required = true,
)
```

Exact public accessors, with no other public method:

```php
public function key(): string
public function label(): string
public function kind(): DiagnosticValueKind
public function disclosure(): DiagnosticDisclosure
public function isRequired(): bool
```

Validate eagerly before assignment:

- key is non-empty lowercase ASCII snake case matching
  `^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$`;
- key contains at most 64 bytes;
- label is non-empty valid UTF-8;
- label contains at most 96 bytes; and
- no trimming, case folding, normalization, localization, or inferred
  disclosure occurs.

Use `strlen()` for every byte bound and `preg_match('//u', $value) === 1` for
UTF-8 validity. Validate key before label when both are invalid.

Invalid key throws `InvalidDiagnosticValueException` with
`InvalidFieldKey`. Invalid label throws it with `InvalidFieldLabel`.

The schema is presentation-neutral structural metadata. It does not know a
diagnostic identifier, package, transport, renderer, secret vocabulary, or
application meaning.

---

# 10. `DiagnosticContextSchema` CONTRACT

Implement a final readonly Public class with exactly three private
instance properties:

```text
operation: string
violatedInvariant: string
fields: list<DiagnosticFieldSchema>
```

Exact public constructor:

```php
/** @param array<array-key, mixed> $fields */
public function __construct(
    string $operation,
    string $violatedInvariant,
    array $fields = [],
)
```

Exact public accessors, with no other public method:

```php
public function operation(): string
public function violatedInvariant(): string
/** @return list<DiagnosticFieldSchema> */
public function fields(): array
```

Exact invariants:

- operation is non-empty valid UTF-8 and at most 160 bytes;
- violated invariant is non-empty valid UTF-8 and at most 256 bytes;
- fields must be a PHP list;
- copy list entries by value into a fresh detached list before storing them;
- at most 16 field schemas;
- every entry is exactly a `DiagnosticFieldSchema` instance;
- field keys are unique by exact binary string equality;
- preserve supplied schema order exactly; and
- `fields()` returns a fresh array whose mutation cannot affect the schema.

Validation order is operation, violated invariant, list shape, detached copy,
field-count limit, then entries in list order. For each entry, reject an invalid
type before considering its key; reject the first duplicate encountered in list
order. Use `strlen()` and `preg_match('//u', $value) === 1` for prose bounds and
UTF-8 validity.

Do not sort fields. Do not retain the caller array or any reference slot. The
immutable schema objects may retain identity because they are final readonly.

Map failures exactly to the catalog cases in Section 13.

---

# 11. `DiagnosticField` CONTRACT

Implement a final readonly Public class with exactly six private instance
properties:

```text
key: string
label: string
kind: DiagnosticValueKind
value: string|int|bool
disclosure: DiagnosticDisclosure
textWasTruncated: ?bool
```

Exact public constructor:

```php
public function __construct(
    DiagnosticFieldSchema $schema,
    string|int|bool $value,
)
```

Exact public accessors, with no other public method:

```php
public function key(): string
public function label(): string
public function kind(): DiagnosticValueKind
public function value(): string|int|bool
public function disclosure(): DiagnosticDisclosure
public function textWasTruncated(): ?bool
```

The constructor copies schema metadata into the value and does not retain the
schema object.

The native input type must match the schema kind exactly:

| Kind | Accepted native type |
| --- | --- |
| Text | `string` |
| Integer | `int` |
| Boolean | `bool` |

PHP strict typing must prevent coercion at ordinary calls. A union member of the
wrong accepted scalar type is a deliberate kind mismatch, not coerced.

For Integer and Boolean:

- store the exact native scalar;
- `textWasTruncated()` returns `null`; and
- do not perform range, non-negative-count, or token semantics here. Those
  remain schema-owner invariants.

For Text, store only the deterministic bounded representation in Section 12;
`textWasTruncated()` returns an actual boolean.

---

# 12. EXACT TEXT REPRESENTATION ALGORITHM

The input string is an arbitrary byte sequence. Do not require UTF-8 and do not
retain the original string.

Encode each input byte independently, in order:

1. ASCII bytes `0x20` through `0x7E`, except backslash `0x5C`, remain the same
   one-byte character.
2. Backslash becomes the two ASCII bytes `\\`.
3. Tab `0x09`, line feed `0x0A`, and carriage return `0x0D` become `\t`, `\n`,
   and `\r` respectively.
4. Every other byte becomes four ASCII bytes `\xHH`, using uppercase
   hexadecimal digits.

This intentionally represents non-ASCII UTF-8 as its escaped bytes. It is
binary-safe, locale-independent, deterministic, and does not call `mbstring`.
It is representation safety only—not secret detection, redaction, disclosure,
HTML escaping, JSON escaping, shell quoting, or transport encoding.

The encoded representation is limited to 512 bytes. Do not first encode or copy
the complete input. Accumulate complete tokens only through the first token that
would make encoded length exceed 512 bytes, then stop reading input. This bounds
representation work and intermediate memory even when the caller supplies a
very large string. The exact visible
truncation marker is:

```text
...[truncated]
```

It is 14 ASCII bytes.

If input exhaustion occurs while the encoded representation is at most 512
bytes, store it exactly
and set `textWasTruncated` to `false`.

If the first over-bound token is encountered:

- retain the longest prefix consisting only of complete encoded-byte tokens
  whose bytes plus the 14-byte marker do not exceed 512;
- append the marker;
- omit that token and all remaining input bytes;
- set `textWasTruncated` to `true`; and
- never split `\\`, `\t`, `\n`, `\r`, or `\xHH`.

The resulting representation may be shorter than 512 bytes when the next
complete token would exceed the remaining space. Empty text is valid and
produces empty representation with `false`.

Do not add a public encoder/sanitizer service, cache, static mutable state, or
alternate algorithm.

---

# 13. `DiagnosticContext` CONTRACT

Implement a final readonly Public class with exactly three private instance
properties:

```text
operation: string
violatedInvariant: string
fields: list<DiagnosticField>
```

Exact public constructor:

```php
/** @param array<array-key, mixed> $values */
public function __construct(
    DiagnosticContextSchema $schema,
    array $values = [],
)
```

Exact public accessors, with no other public method:

```php
public function operation(): string
public function violatedInvariant(): string
/** @return list<DiagnosticField> */
public function fields(): array
```

Construction rules:

1. Copy the schema operation and invariant values; do not retain the schema.
2. Copy every input map entry by value into a detached local map before field
   construction; do not store the caller array or its reference slots.
3. Every input key must be a string equal to one exact schema field key.
4. Reject integer keys and unknown string keys.
5. Every required schema field must have a supplied value.
6. Omit an absent optional field; do not synthesize `null`.
7. Reject `null`, float, array, object, enum, resource, callable, or other value
   through the exact kind-mismatch diagnostic.
8. Construct fields in schema order, never input-map order.
9. Retain no unused or extra input value.
10. `fields()` returns a fresh array whose mutation cannot affect the context.

Validate all input keys before missing values or value kinds. When more than
one input key is invalid, choose deterministically: integer keys precede string
keys; among integers select the numerically smallest; otherwise select the
binary-smallest unknown string by `strcmp()`. After key validation, walk schema
order and reject the first missing required value or first kind mismatch. This
selection is independent of input-map insertion order.

The resulting context is eager and detached. Repeated access returns identical
operation, invariant, order, keys, labels, kinds, represented values,
disclosures, and truncation flags. It performs no I/O and consults no global,
superglobal, environment, clock, randomness, container, Kernel, request,
thread-local, fiber-local, filesystem, network, database, logger, tracer, or AI
state.

`DiagnosticContext` validates structural schema compliance. It cannot determine
whether a schema is semantically authorized for a diagnostic or whether a text
value contains forbidden material. Schema owners must never pass secret or
forbidden content.

---

# 14. DIAGNOSTICS PACKAGE FAILURE FOUNDATION

## 14.1 Catalog

Implement Internal string-backed enum
`Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic` with exactly:

| Case | Identifier | Title | Remediation |
| --- | --- | --- | --- |
| `InvalidFieldKey` | `KGN-DIAGNOSTICS-1001` | Diagnostic field key violates the canonical key contract | Use non-empty lowercase ASCII snake case containing at most 64 bytes |
| `InvalidFieldLabel` | `KGN-DIAGNOSTICS-1002` | Diagnostic field label violates the fixed-label contract | Use non-empty valid UTF-8 containing at most 96 bytes |
| `InvalidOperation` | `KGN-DIAGNOSTICS-1003` | Diagnostic context operation violates its prose contract | Use non-empty valid UTF-8 containing at most 160 bytes |
| `InvalidInvariant` | `KGN-DIAGNOSTICS-1004` | Diagnostic context invariant violates its prose contract | Use non-empty valid UTF-8 containing at most 256 bytes |
| `FieldSchemasNotList` | `KGN-DIAGNOSTICS-1005` | Diagnostic field schemas are not a zero-based consecutive list | Reindex field schemas as a PHP list |
| `InvalidFieldSchemaEntry` | `KGN-DIAGNOSTICS-1006` | Diagnostic schema list contains a non-field-schema entry | Replace the reported entry with a DiagnosticFieldSchema |
| `TooManyFieldSchemas` | `KGN-DIAGNOSTICS-1007` | Diagnostic context schema exceeds the field limit | Define at most 16 field schemas |
| `DuplicateFieldSchemaKey` | `KGN-DIAGNOSTICS-1008` | Diagnostic context schema contains a duplicate field key | Keep each canonical field key exactly once |
| `InvalidContextValueKey` | `KGN-DIAGNOSTICS-1009` | Diagnostic context values contain an unapproved field key | Supply values only for exact keys in the closed schema |
| `MissingRequiredContextValue` | `KGN-DIAGNOSTICS-1010` | Diagnostic context is missing a required field value | Supply every value required by the closed schema |
| `ContextValueKindMismatch` | `KGN-DIAGNOSTICS-1011` | Diagnostic field value does not match its schema kind | Supply the exact string, integer, or boolean type required by the field schema |

Implement exact `title(): string` and `remediation(): string` matches. IDs are
globally unique. Do not add aliases, ranges, severity, retry, HTTP, exit-code,
transport, documentation, or context metadata.

## 14.2 Marker and exceptions

Implement Public `Kongono\Diagnostics\Exception\ExceptionInterface` extending
only `Throwable`, with exactly:

```php
public function diagnosticId(): string;
public function diagnosticTitle(): string;
public function diagnosticRemediation(): string;
```

It is a fourth pairwise-unrelated package catch boundary. It does not extend or
be extended by the Kernel, Graph, or DI markers and does not add
`diagnosticContext()`.

Implement Internal abstract `DiagnosticsException` extending
`RuntimeException` and implementing the marker. Match the established package
base shape exactly:

```php
final public function __construct(
    private readonly DiagnosticsDiagnostic $diagnostic,
    string $message,
    ?Throwable $previous = null,
)
```

It must call `parent::__construct($message, 0, $previous)` and expose the three
final string accessors. It contains exactly the one private readonly diagnostic
property and no logging, rendering, serialization, transport, retry, or context
behavior.

Implement Experimental final `InvalidDiagnosticValueException` extending only
`DiagnosticsException`, with no declared property or method.

## 14.3 Exact failure mapping and messages

Throw `InvalidDiagnosticValueException` at each validation boundary with the
matching catalog case and exact message family:

- invalid key:
  `Diagnostic field key must match lowercase ASCII snake case and contain at most 64 bytes.`
- invalid label:
  `Diagnostic field label must be non-empty valid UTF-8 and contain at most 96 bytes.`
- invalid operation:
  `Diagnostic context operation must be non-empty valid UTF-8 and contain at most 160 bytes.`
- invalid invariant:
  `Diagnostic context invariant must be non-empty valid UTF-8 and contain at most 256 bytes.`
- schemas not list:
  `Diagnostic field schemas must be supplied as a zero-based consecutive list.`
- invalid schema entry:
  `Diagnostic field schema at position %d must be a DiagnosticFieldSchema.`
- too many schemas:
  `Diagnostic context schema may contain at most 16 fields; received %d.`
- duplicate key:
  `Diagnostic context schema contains duplicate field key "%s".`
- invalid context key: integer key uses
  `Diagnostic context value key must be a schema-approved string; received integer key %d.`;
  unknown string uses
  `Diagnostic context value key "%s" is not present in the closed schema.`
- missing required value:
  `Diagnostic context is missing required field value "%s".`
- kind mismatch:
  `Diagnostic field "%s" requires %s; received %s.`

Use zero-based positions. Use `get_debug_type()` for received kind text. Messages
are protected diagnostic detail and may contain schema keys; they are not
transport output.

Do not wrap PHP `TypeError`, engine errors, autoloader failures, or unexpected
external `Throwable` merely to enter this marker. Preserve an explicitly passed
previous exception by identity and code zero in direct base-shape tests.

The diagnostics package's own construction failures expose ID/title/remediation
only. Giving context-construction failures a context would be recursive and is
not authorized.

---

# 15. ADR 0029

Create `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md` with:

```text
Status: Accepted
Date: 2026-08-13
```

It must record:

1. the new package and exact dependency direction;
2. the six Public presentation-neutral value/schema/vocabulary types;
3. closed schema construction rather than arbitrary context bags;
4. exact key, label, operation, invariant, field-count, scalar-kind, order,
   optionality, and detachment rules;
5. the exact byte-oriented text algorithm and 14-byte marker;
6. structural validation versus originating-package semantic ownership;
7. Safe eligibility versus actual disclosure;
8. forbidden material remains excluded before value construction;
9. no ambient state, I/O, or retained per-failure global state;
10. the fourth package-local exception marker and 11 new diagnostic IDs;
11. no context on the diagnostics package's own construction failures;
12. no integration with the preexisting 42 failures in this phase;
13. security and persistent-runtime consequences;
14. bounded performance: at most 16 schemas/fields and work linear in schema
    count plus represented input bytes;
15. compatibility impact and API-status decisions;
16. rejected arbitrary bags, lazy values, raw unbounded text, locale-dependent
    representation, universal exception markers, and premature rendering; and
17. open decisions: existing-exception integration, catalog instances for the
    42 schemas, context-capture failure representation, renderer/transport,
    production allowlists, serialization, localization, source providers,
    logging/telemetry, incident storage, and documentation lookup.

ADR 0029 refines but does not replace or edit ADR 0028. It amends ADR 0003 only
for the post-Phase-0F package count and exact root local-package inventory. Add a
short linked amendment note to ADR 0003 without rewriting its original Phase 0C
through 0F history. Add ADR 0029 to the ADR index.

---

# 16. TEST CONTRACT

Create exactly the seven test files in Section 4. Tests must be deterministic,
perform no I/O beyond ordinary source/reflection inspection already used by the
repository, and create no fixture declarations.

## `DiagnosticDisclosureTest`

Prove exact two case names/values, order, backed-string behavior, Public status,
and absence of a third case.

## `DiagnosticValueKindTest`

Prove exact three case names/values, order, backed-string behavior, and Public
status.

## `DiagnosticFieldSchemaTest`

Prove:

- exact accessors and default/false required state;
- valid one-, 64-byte, digit-containing, and underscore keys;
- reject empty, uppercase, leading/trailing/doubled underscore, leading digit,
  punctuation, non-ASCII, NUL, and 65-byte keys;
- accept valid UTF-8 labels through exactly 96 bytes;
- reject empty, invalid UTF-8, and 97-byte labels;
- every rejection has the exact package marker, catalog ID, title, remediation,
  message, code zero, and no unrelated marker; and
- final readonly shape, exact properties, exact public method inventory, and no
  schema mutation.

## `DiagnosticContextSchemaTest`

Prove:

- zero through 16 schemas and exact order;
- exact 160-byte operation and 256-byte invariant acceptance;
- reject empty, invalid UTF-8, and one-byte-over-bound prose;
- reject non-list arrays, invalid entries, 17 schemas, and duplicate exact keys;
- byte-distinct keys remain distinct;
- caller array mutation, referenced-element reassignment, and returned-array
  mutation cannot affect stored state;
- fresh returned arrays retain original immutable schema instances; and
- exact final readonly property/public-method shape and failure metadata.

## `DiagnosticFieldTest`

Prove:

- exact metadata copying without retaining a schema property;
- exact integer and boolean identity with null text-truncation state;
- empty and printable text;
- backslash, tab, line feed, carriage return, NUL, DEL, `0x80`, `0xFF`, and
  multibyte UTF-8 byte escaping;
- lowercase input bytes produce uppercase hex digits;
- exactly 512 represented bytes do not truncate;
- longer literal, backslash-heavy, and binary inputs truncate with the exact
  marker, at most 512 bytes, and no split token;
- a very large input yields the same bounded result without constructing a
  complete encoded copy;
- source text is not recoverably retained as another property;
- all cross-kind scalar mismatches reject with exact metadata/message; and
- final readonly exact property/public-method shape.

## `DiagnosticContextTest`

Prove:

- empty schema/value construction;
- required and optional fields;
- absent optional omission without null;
- schema-order output independent of reversed value-map order;
- exact represented values, kinds, disclosures, and flags;
- integer and unknown string keys reject;
- every missing required key rejects deterministically in schema order;
- extra values reject before missing-value selection, independent of input-map
  order;
- all invalid native value kinds reject without coercion;
- caller map/reference mutation and returned-array mutation cannot change the
  snapshot;
- repeated access is semantically identical;
- two contexts do not share mutable state; and
- exact final readonly property/public-method shape.

## `StructuralValueTest`

Prove:

- exact 11 catalog cases, IDs, titles, remediations, order, global syntax, and
  uniqueness;
- exact marker/base/concrete reflection shapes;
- previous-exception identity and code zero;
- diagnostics marker is pairwise unrelated to Kernel, Graph, and DI markers;
- diagnostics failures implement only their own package marker;
- no `diagnosticContext` accessor exists;
- all ten production types carry exact API status;
- no source dependency on Graph, DI, Kernel lifecycle, I/O, environment,
  randomness, clock, logging, telemetry, or AI symbols; and
- no static mutable property or cache exists.

Update the root architecture test to:

- enumerate exactly 40 production declarations;
- include the ten exact statuses;
- enforce four-way package marker separation;
- include all four catalogs and exactly 53 globally unique diagnostic IDs,
  split 6 Kernel, 11 Graph, 25 DI, and 11 Diagnostics;
- include the diagnostics base/catalog in exact shape checks; and
- preserve every existing test and boundary.

Do not weaken an existing assertion, use snapshots as a substitute for exact
contracts, skip tests, or fabricate invalid readonly state.

The final suite must exceed the 134-test baseline. Record the exact final test
and assertion totals; do not claim a preselected total before PHPUnit reports it.

---

# 17. TOOLING AND AUTOLOAD EXPANSION

Update exact first-party scopes:

- PHPUnit source and test suites include diagnostics;
- PHPStan paths include diagnostics source/tests;
- PHP CS Fixer finder includes diagnostics source/tests;
- root autoload-dev includes diagnostics tests; and
- package validation includes diagnostics in lexicographic order.

Expected final inventories before report population:

```text
production PHP declarations/files: 40
test PHP files: 69
PHP syntax inventory including .php-cs-fixer.dist.php: 110
PHP CS Fixer inventory: 109
package manifests: 4
production diagnostic IDs: 53
```

Run optimized strict PSR autoload generation and prove every diagnostics
production and test type is autoloadable in the development root. Prove
production types remain autoloadable after optimized `--no-dev` generation and
diagnostics test types do not. Restore development autoload before canonical
quality.

Do not add a custom autoloader, Composer plugin, minimum-stability setting,
version field, license assumption, suppression, baseline, ignored error, or
tool dependency.

---

# 18. DOCUMENTATION RECONCILIATION

Update only the exact documentation inventory in Section 5.

Every document must distinguish:

```text
Implemented in Phase 0P:
- diagnostics package
- bounded values and schemas
- deterministic text representation
- structural closed-schema capture
- package-local construction failures

Still future:
- the 42 Kernel/Graph/DI schema instances in code
- context on existing exceptions
- marker accessor changes
- throw-site capture
- forbidden-material detection or redaction
- renderer/error page
- trusted developer mode implementation
- production disclosure/transport allowlists
- serialization, source, documentation lookup, logging, telemetry, incidents
```

Required reconciliation:

- root/architecture/doctrine/security documents state the narrow implemented
  boundary and preserve all later-capability disclaimers;
- ADR 0003 retains its original monorepo decision and records the linked Phase
  0P fourth-package amendment;
- runtime model changes context values/schema foundation to implemented but
  keeps per-exception capture future;
- documentation standard distinguishes structurally valid values from
  semantically authorized package schemas;
- error-handling guide documents exact APIs, bounds, algorithm, 11 new package
  diagnostics, four markers/53 IDs, and the unchanged future 42 integration;
- glossary marks value/schema vocabulary accurately as Public or Experimental;
- package layout documents four packages and exact dependency direction;
- API-status guide records 40 types and exact new statuses;
- testing/static-analysis/style/toolchain guides record expanded paths and
  exact inventories;
- the engineering index and contributor guide say package validation covers
  all four manifests in lexicographic order;
- Kernel/Graph/DI READMEs state the shared value package now exists but their
  schemas, exceptions, and accessors remain unintegrated; and
- diagnostics README covers purpose, direct construction contract, schema
  ownership, fields, text encoding, disclosure limits, security, lifecycle,
  failures, performance, API status, examples, and explicit non-goals.

Do not describe text representation as secret sanitization. Do not claim Safe
is automatically public. Do not claim the package recognizes forbidden data.
Do not show existing exceptions returning context.

---

# 19. IMPLEMENTATION SEQUENCE

Execute in this order:

1. complete authority and baseline preflight;
2. run unchanged pre-change `composer quality`;
3. create ADR 0029 and diagnostics manifest/README;
4. implement vocabularies, schemas, values, catalog, and exceptions;
5. implement focused package tests;
6. update root manifest and generate the lock through Composer;
7. update root architecture test and tool scopes;
8. run syntax, optimized strict-PSR, focused PHPUnit, PHPStan, and style checks;
9. reconcile the exact documentation inventory;
10. run link, text-integrity, stream, secret, and prohibited-scope audits;
11. run canonical `composer quality`;
12. prove final Git/inventory state with an empty index;
13. populate the Phase 0P report only after all other gates pass; and
14. return the report digest and stop.

Do not populate the report early. Do not stage or commit.

---

# 20. REQUIRED VALIDATION

## 20.1 Baseline and immutability

Prove all Section 2 values, closed path inventories, hashes, modes, streams,
caller records, and zero-byte Phase 0P report. Verify every tracked file outside
the authorized modified inventory remains byte-identical to `HEAD` or, for the
13 accepted Phase 0O modifications, to its locked Section 2 content.

## 20.2 Composer and lock

Run and record:

```powershell
composer validate:composer
composer validate:packages
composer audit:locked
composer platform
composer dump-autoload --optimize --strict-psr
```

Also validate the diagnostics manifest directly. Prove:

- no third-party package version changed;
- no plugin or policy change;
- lock content is Composer-generated and consistent;
- only the authorized local package/dependency graph changed; and
- accepted warnings remain limited to missing license and authorized local
  `@dev`/`*` constraints.

## 20.3 PHP and focused behavior

Run syntax checks over all 110 PHP files/configuration entries. Run all seven
diagnostics test files, the root API architecture test, and every preexisting
package suite. Record exact tests/assertions for each.

Run PHPStan at the locked maximum level and PHP CS Fixer dry-run across the
expanded inventories. No suppression, exclusion, baseline, ignored error,
skipped test, incomplete test, warning, risky test, notice, or deprecation is
accepted.

## 20.4 Documentation and safety

Prove:

- ADR 0029 is Accepted and indexed;
- all local Markdown links resolve under the existing historical-record
  exception;
- current versus future statements match Section 18;
- exact algorithm and all bounds agree everywhere;
- no secret example or real credential appears;
- every changed/new text file is strict UTF-8 without BOM, LF-only, exactly
  one-final-LF terminated, and free of extra terminal blank line, NUL,
  replacement character, mojibake, conflict marker, and trailing whitespace;
- new files are ordinary non-reparse files;
- repository-wide non-default alternate streams remain zero; and
- prohibited packages, APIs, integrations, and runtime state remain absent.

## 20.5 Canonical quality

Run exactly:

```powershell
composer quality
```

The expected substantive boundary is:

```text
security advisories: 0
platform requirements: pass
PHP CS Fixer: 0 of 109 files fixable
PHPStan: 0 errors
PHPUnit: more than 134 tests; exact total reported
```

If the managed sandbox alone blocks advisory network/cache access, rerun the
identical fail-closed command once with authorized normal access. Do not change
flags, dependencies, policy, or configuration.

## 20.6 Final Git proof

Before report population, prove:

```text
branch: main
only local branch: main
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
commit count: 5
tracked files: 214
modified tracked paths: 28
untracked paths: 30
total status paths: 58
staged paths: 0
remotes: 0
tags: 0
```

The 28 modified paths and 30 untracked paths must be exact closed inventories.
The 30 untracked paths are the eight accepted caller/ADR records, the Phase 0P
directive/report, and the exact 20 new implementation files.

Record exact final diff insertions/deletions; they are evidence, not a
preselected acceptance value.

Only after all other gates pass may the executor populate
`docs/task reports/Phase_0P.md`. Then re-prove the same Git state and all caller
record/report integrity. Return final report bytes and SHA-256 outside the
report.

---

# 21. STRICT ACCEPTANCE CRITERIA

Every item must pass:

## Repository boundary

- [ ] Exact accepted Phase 0O.2 baseline and caller records match.
- [ ] Phase 0P directive matches architect-delivered bytes/hash and its report
  began as the exact zero-byte placeholder.
- [ ] Exactly 20 implementation files are created and exactly 28 tracked files
  are modified.
- [ ] No unauthorized path, stream, mode, ref, index, or history mutation occurs.
- [ ] Final state is exactly 28 modified, 30 untracked, zero staged, and 58
  status paths.

## Package and API

- [ ] `kongono/diagnostics` is the fourth exact package with the authorized
  Kernel-metadata-only dependency.
- [ ] Exactly ten production declarations and statuses are added.
- [ ] Disclosure has exactly Safe/Protected; kind has exactly Text/Integer/Boolean.
- [ ] Field and context schemas enforce every exact bound, shape, order,
  uniqueness, required/optional, and detachment rule.
- [ ] Field/context values are final readonly detached snapshots with exact APIs.
- [ ] Text encoding/truncation is byte-exact, binary-safe, bounded, visibly
  marked, and never splits a token.
- [ ] No forbidden kind, arbitrary nested bag, lazy value, or mutable/global
  state exists.

## Failure and security

- [ ] Exactly 11 new globally unique diagnostic IDs and mappings exist.
- [ ] Diagnostics has its own fourth pairwise-unrelated Public marker.
- [ ] Diagnostics construction failures expose no recursive context accessor.
- [ ] Safe remains eligibility; text representation is not called sanitization
  or secret detection.
- [ ] No forbidden material, I/O, ambient state, logging, telemetry, renderer,
  or transport behavior is introduced.

## Tests, tooling, and docs

- [ ] Seven focused test files cover every Section 16 contract.
- [ ] Root architecture enforces exactly 40 types and 53 IDs split 6/11/25/11.
- [ ] Inventories are exactly 40 production, 69 test, 110 syntax, and 109 style.
- [ ] Strict PSR dev/no-dev autoload proofs pass.
- [ ] Root/package validation, lock audit, platform, style, PHPStan, focused
  suites, all preexisting suites, and canonical quality pass.
- [ ] No third-party dependency version or security policy changes.
- [ ] ADR 0029 and all exact documentation are reconciled without claiming
  exception integration or presentation exists.
- [ ] Links, encoding, whitespace, streams, secrets, and prohibited-scope audits
  pass.

## Report

- [ ] Report follows Section 22 exactly and distinguishes verified/current from
  planned/future behavior.
- [ ] It records exact commands, versions, counts, hashes, warnings, lock delta,
  test/assertion results, inventories, and Git proof.
- [ ] Report is nonzero, strict UTF-8, LF-only, exactly one-final-LF terminated,
  untracked, unstaged, and absent from `HEAD`.
- [ ] Next step is exact.

Any failure makes status Partial or Blocked, never Complete.

---

# 22. STANDARD EXECUTOR COMPLETION REPORT

Write `docs/task reports/Phase_0P.md` with exactly:

```text
1. STATUS
2. SUMMARY
3. FILES CREATED
4. FILES MODIFIED
5. ARCHITECTURAL DECISIONS
6. DEVIATIONS FROM DIRECTIVE
7. DEPENDENCIES ADDED/REMOVED
8. ENVIRONMENT
9. COMMANDS RUN
10. VALIDATION RESULTS
11. DOCUMENTATION REVIEW
12. WARNINGS / KNOWN ISSUES
13. QUESTIONS / BLOCKERS
14. GIT DIFF SUMMARY
15. ACCEPTANCE CRITERIA
16. NEXT STEP
```

The report must include:

- exact 20-created and 28-modified inventories;
- exact API/type/status and Composer dependency delta;
- `composer.lock` before/after bytes and SHA-256 plus package-version audit;
- exact 11 diagnostics cases and 53-ID split;
- field/context/schema shape, bounds, algorithm, immutability, and security proof;
- focused and complete test/assertion totals;
- exact 40/69/110/109 inventories;
- dev/no-dev autoload evidence;
- all validation results and accepted warnings;
- exact caller-record and final Git state;
- deviations/blockers stated plainly; and
- final report digest returned separately.

Section 16 must say exactly:

```text
Await architect review before Phase 0Q.
```

---

# 23. FINAL EXECUTOR INSTRUCTION

Implement only the presentation-neutral diagnostics value and closed-schema
foundation. Keep the existing 42 failures, markers, catalogs, constructors, and
throw sites unchanged. Do not render, disclose, log, serialize, transport, or
integrate context. Preserve the exact repository boundary, validate everything,
write the report only after success, and stop before Phase 0Q.
