<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Capability\CapabilityRequest;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;

/** Projects admitted declaration values into the one exact artifact field order. */
#[Api(ApiStatus::Internal)]
final class DeclarationArtifactCodec
{
    /**
     * @param list<ExtensionDeclaration> $declarations
     * @return list<array<string, mixed>>
     */
    public static function declarations(array $declarations): array
    {
        return array_map(self::declaration(...), $declarations);
    }

    /** @return array<string, mixed> */
    private static function declaration(ExtensionDeclaration $declaration): array
    {
        return [
            'contract_version' => $declaration->contractVersion()->value,
            'identity' => $declaration->identity()->value(),
            'extension_version' => $declaration->version()->value(),
            'category' => $declaration->category()->value,
            'provenance' => [
                'source_identity' => $declaration->provenance()->sourceIdentity()->value(),
                'source_sha256' => $declaration->provenance()->sourceSha256(),
            ],
            'lifecycle' => $declaration->lifecycle()->value,
            'capability_requests' => array_map(self::capability(...), $declaration->capabilityRequests()),
            'contributions' => array_map(self::contribution(...), $declaration->contributions()),
        ];
    }

    /** @return array{identity:string,version:int,purpose:string,requirement:string} */
    private static function capability(CapabilityRequest $request): array
    {
        return [
            'identity' => $request->identity()->value(),
            'version' => $request->identity()->version(),
            'purpose' => $request->purpose()->value(),
            'requirement' => $request->requirement()->value,
        ];
    }

    /** @return array{identity:string,schema:string,schema_version:int,visibility:string,source_sha256:string} */
    private static function contribution(ContributionDeclaration $contribution): array
    {
        return [
            'identity' => $contribution->identity()->value(),
            'schema' => $contribution->schema()->name(),
            'schema_version' => $contribution->schema()->version(),
            'visibility' => $contribution->visibility()->value,
            'source_sha256' => $contribution->sourceSha256(),
        ];
    }

    private function __construct() {}
}
