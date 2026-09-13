<?php

declare(strict_types=1);

use ViewMinimalApp\Runtime;

require dirname(__DIR__) . '/vendor/autoload.php';

Runtime::run(str_replace('\\', '/', dirname(__DIR__)));
