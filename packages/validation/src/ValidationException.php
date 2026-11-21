<?php

declare(strict_types=1);

namespace Kongono\Validation;

use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\Diagnostic;
use Kongono\Core\Diagnostics\Disclosure;

final class ValidationException extends CoreException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(
        public readonly array $errors,
        string $message = 'Validation failed.',
    ) {
        parent::__construct(
            $message,
            new Diagnostic(
                id: 'KGN-VALIDATION-422',
                title: 'Input validation failed',
                remediation: 'Fix the reported input fields and retry the request.',
                fields: [
                    'field_count' => ['value' => count($errors), 'disclosure' => Disclosure::Safe],
                ],
            ),
        );
    }

    /** @return array{error: string, message: string, fields: array<string, list<string>>} */
    public function toArray(): array
    {
        return [
            'error' => 'validation_failed',
            'message' => $this->getMessage(),
            'fields' => $this->errors,
        ];
    }
}
