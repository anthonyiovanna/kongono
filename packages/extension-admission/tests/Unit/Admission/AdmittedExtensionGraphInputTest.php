<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit\Admission;

use Kongono\ExtensionAdmission\Admission\AdmittedExtensionGraphInput;
use Kongono\ExtensionAdmission\Identity\AdmissionIdentity;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use PHPUnit\Framework\TestCase;

final class AdmittedExtensionGraphInputTest extends TestCase
{
    public function testAccessorsReturnDetachedListsAndExactIdentities(): void
    {
        $set = new DeclarationSetIdentity(str_repeat('a', 64));
        $admission = new AdmissionIdentity(str_repeat('b', 64));
        $input = new AdmittedExtensionGraphInput($set, $admission, [], [], [], []);
        self::assertSame($set, $input->declarationSetIdentity());
        self::assertSame($admission, $input->admissionIdentity());
        self::assertSame([], $input->admittedExtensions());
        self::assertSame([], $input->nodeRecords());
    }
}
