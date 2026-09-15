<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Asset;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies one closed asset content type without extension inference. */
#[Api(ApiStatus::Experimental)]
enum AssetContentType: string
{
    case TextCss = 'text/css';
    case TextJavascript = 'text/javascript';
    case TextPlain = 'text/plain';
    case ApplicationJson = 'application/json';
    case ApplicationWasm = 'application/wasm';
    case ApplicationOctetStream = 'application/octet-stream';
    case ImageAvif = 'image/avif';
    case ImageGif = 'image/gif';
    case ImageJpeg = 'image/jpeg';
    case ImagePng = 'image/png';
    case ImageSvgXml = 'image/svg+xml';
    case ImageWebp = 'image/webp';
    case FontWoff2 = 'font/woff2';
    case AudioMpeg = 'audio/mpeg';
    case AudioOgg = 'audio/ogg';
    case VideoMp4 = 'video/mp4';
    case VideoWebm = 'video/webm';
}
