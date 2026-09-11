<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks failures owned by the OpenAI Responses inspection package. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable {}
