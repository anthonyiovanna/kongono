<?php

declare(strict_types=1);

namespace Kongono\Storage;

enum StorageOutcome: string
{
    case Created = 'created';
    case Replaced = 'replaced';
    case AlreadyExists = 'already_exists';
    case Conflict = 'conflict';
    case Absent = 'absent';
    case Deleted = 'deleted';
    case Uncertain = 'uncertain';
}
