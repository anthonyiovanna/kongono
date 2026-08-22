<?php

declare(strict_types=1);

use Kongono\PhpCgi\Bootstrap\CgiOperatorProfile;
use Kongono\Tests\External\PhpCgi\Fixture\Phase3FastCgiEntry;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

Phase3FastCgiEntry::run(CgiOperatorProfile::Production);
