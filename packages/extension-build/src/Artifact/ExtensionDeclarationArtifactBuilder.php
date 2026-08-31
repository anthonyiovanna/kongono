<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\ArtifactVersion;
use Kongono\ExtensionBuild\Exception\InvalidExtensionBuildException;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionBuild\Internal\CanonicalJson;
use Kongono\ExtensionBuild\Internal\DeclarationArtifactCodec;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;

/** Builds deterministic evidence from one exact caller-supplied canonical list. */
#[Api(ApiStatus::Experimental)]
final class ExtensionDeclarationArtifactBuilder
{
    public const int CONTRACT_VERSION = 1;
    public const int MAX_DECLARATIONS = 256;
    public const int MAX_ARTIFACT_BYTES = 4_194_304;

    /** @param list<ExtensionDeclaration> $declarations */
    public function build(array $declarations): ExtensionDeclarationArtifact
    {
        $declarations = $this->declarations($declarations);
        $data = DeclarationArtifactCodec::declarations($declarations);
        $declarationSetBytes = CanonicalJson::encode(['declarations' => $data]);
        $declarationSetSha256 = hash('sha256', $declarationSetBytes);
        $identity = new DeclarationSetIdentity($declarationSetSha256);
        $artifactBytes = CanonicalJson::encode([
            'schema' => 'kongono.extension-declaration-artifact',
            'version' => ArtifactVersion::Version1->value,
            'declaration_set_identity' => $identity->value(),
            'declaration_set_sha256' => $declarationSetSha256,
            'builder' => [
                'package' => 'kongono/extension-build',
                'contract_version' => self::CONTRACT_VERSION,
            ],
            'declaration_count' => count($data),
            'declarations' => $data,
        ]);
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw InvalidExtensionBuildException::limit('artifact_bytes', 'artifact', null, strlen($artifactBytes));
        }

        return new ExtensionDeclarationArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $identity,
            $declarationSetSha256,
            count($data),
        );
    }

    /**
     * @param array<array-key, mixed> $declarations
     * @return list<ExtensionDeclaration>
     */
    private function declarations(array $declarations): array
    {
        if (!array_is_list($declarations)) {
            throw InvalidExtensionBuildException::input('declaration_not_list', 'declaration', count($declarations));
        }
        if (count($declarations) > self::MAX_DECLARATIONS) {
            throw InvalidExtensionBuildException::limit('declaration_count', 'declaration', count($declarations));
        }

        $accepted = [];
        $seen = [];
        $previous = null;
        foreach ($declarations as $position => $declaration) {
            if (!$declaration instanceof ExtensionDeclaration) {
                throw InvalidExtensionBuildException::input('invalid_declaration_entry', 'declaration', count($declarations), $position);
            }
            $identity = $declaration->identity()->value();
            if (isset($seen[$identity])) {
                throw InvalidExtensionBuildException::input('duplicate_extension_identity', 'identity', count($declarations), $position);
            }
            if (null !== $previous && strcmp($previous, $identity) >= 0) {
                throw InvalidExtensionBuildException::input('noncanonical_declaration_order', 'declaration', count($declarations), $position);
            }
            $seen[$identity] = true;
            $previous = $identity;
            $accepted[] = $declaration;
        }

        return $accepted;
    }
}
