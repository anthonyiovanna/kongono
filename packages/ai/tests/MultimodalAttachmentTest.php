<?php

declare(strict_types=1);

namespace Kongono\Ai\Tests;

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\Attachment;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\Message;
use Kongono\Ai\Provider\AnthropicProvider;
use Kongono\Ai\Provider\GeminiProvider;
use Kongono\Ai\Provider\GroqProvider;
use Kongono\Ai\Provider\OllamaProvider;
use Kongono\Ai\Provider\OpenAiProvider;
use Kongono\Ai\Transport\HttpResponse;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;
use PHPUnit\Framework\TestCase;

final readonly class ExtractedReceiptDto
{
    public function __construct(
        #[Required]
        public string $vendor,
        #[Min(1)]
        public int $totalCents,
    ) {}
}

final class MultimodalAttachmentTest extends TestCase
{
    public function testMagicByteAndSizeValidationEnforcedFailClosed(): void
    {
        $jpeg = Attachment::image("\xFF\xD8\xFF\xE0fake-jpeg-data", 'image/jpg', detail: 'high');
        self::assertSame('image/jpeg', $jpeg->mimeType);
        self::assertTrue($jpeg->isImage());
        self::assertFalse($jpeg->isPdf());
        self::assertStringStartsWith('data:image/jpeg;base64,', $jpeg->dataUri());

        $png = Attachment::image("\x89PNG\r\n\x1A\nfake-png", 'image/png');
        self::assertSame('image/png', $png->mimeType);

        $webp = Attachment::image("RIFF\x10\x00\x00\x00WEBPVP8 ", 'image/webp');
        self::assertSame('image/webp', $webp->mimeType);

        $gif = Attachment::image('GIF89a1234', 'image/gif');
        self::assertSame('image/gif', $gif->mimeType);

        $pdf = Attachment::pdf('%PDF-1.7 lease agreement bytes', '../evil/lease 2026.pdf');
        self::assertTrue($pdf->isPdf());
        self::assertSame('lease_2026.pdf', $pdf->filename);

        $fromB64 = Attachment::fromBase64($pdf->base64Data, 'application/pdf', filename: 'invoice.pdf');
        self::assertTrue($fromB64->isPdf());
        self::assertSame('invoice.pdf', $fromB64->filename);

        // Spoofed PHP payload claiming to be JPEG fails closed
        try {
            Attachment::image('<?php echo "evil";', 'image/jpeg');
            self::fail('Expected spoofed JPEG magic bytes to fail.');
        } catch (AiException $e) {
            self::assertStringContainsString('binary signature', strtolower($e->getMessage()));
        }

        // Oversized payload fails closed
        try {
            Attachment::image("\xFF\xD8\xFF" . str_repeat('a', 100), 'image/jpeg', maxBytes: 32);
            self::fail('Expected oversized attachment to fail.');
        } catch (AiException $e) {
            self::assertStringContainsString('between 1 and 32', $e->getMessage());
        }
    }

    public function testStructuredOcrAndMultimodalEncodingAcrossAllProviders(): void
    {
        $jpeg = Attachment::image("\xFF\xD8\xFF\xE0receipt-bytes", 'image/jpeg', detail: 'high');
        $pdf = Attachment::pdf('%PDF-1.7 invoice-bytes', 'receipt.pdf');

        // 1. OpenAI (structured Receipt OCR with image + PDF)
        $openAiCaptured = [];
        $openAiTransport = $this->captureTransport($openAiCaptured, json_encode([
            'model' => 'gpt-4.1-mini',
            'choices' => [['message' => ['content' => '{"vendor":"Home Depot","totalCents":14599}'], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 420, 'completion_tokens' => 25],
        ], JSON_THROW_ON_ERROR));

        $ai = new AiClient(new OpenAiProvider('sk-test', transport: $openAiTransport));
        $receipt = $ai->structured('Extract receipt details.', ExtractedReceiptDto::class, attachments: [$jpeg, $pdf]);
        self::assertSame('Home Depot', $receipt->vendor);
        self::assertSame(14599, $receipt->totalCents);

        $openAiPayload = json_decode($openAiCaptured[0]['body'], true, 64, JSON_THROW_ON_ERROR);
        self::assertIsArray($openAiPayload);
        $userParts = $openAiPayload['messages'][1]['content'];
        self::assertSame('text', $userParts[0]['type']);
        self::assertSame('image_url', $userParts[1]['type']);
        self::assertSame('high', $userParts[1]['image_url']['detail']);
        self::assertSame('file', $userParts[2]['type']);
        self::assertSame('receipt.pdf', $userParts[2]['file']['filename']);

        // 2. Groq (OpenAI-compatible delegation)
        $groqCaptured = [];
        $groqTransport = $this->captureTransport($groqCaptured, json_encode([
            'model' => 'llama-3.2-11b-vision-preview',
            'choices' => [['message' => ['content' => 'Leaking P-trap under sink.'], 'finish_reason' => 'stop']],
        ], JSON_THROW_ON_ERROR));
        $groqAi = new AiClient(new GroqProvider('gsk-test', transport: $groqTransport));
        self::assertSame('Leaking P-trap under sink.', $groqAi->ask('Triage this photo.', attachments: [$jpeg]));
        $groqPayload = json_decode($groqCaptured[0]['body'], true, 64, JSON_THROW_ON_ERROR);
        self::assertSame('image_url', $groqPayload['messages'][0]['content'][1]['type']);

        // 3. Anthropic Claude (image + document blocks)
        $anthropicCaptured = [];
        $anthropicTransport = $this->captureTransport($anthropicCaptured, json_encode([
            'model' => 'claude-sonnet-4-20250514',
            'content' => [['type' => 'text', 'text' => 'Verified lease PDF and photo.']],
            'usage' => ['input_tokens' => 500, 'output_tokens' => 12],
        ], JSON_THROW_ON_ERROR));
        $claudeAi = new AiClient(new AnthropicProvider('sk-ant-test', transport: $anthropicTransport));
        self::assertSame('Verified lease PDF and photo.', $claudeAi->ask('Inspect these.', attachments: [$jpeg, $pdf]));
        $claudePayload = json_decode($anthropicCaptured[0]['body'], true, 64, JSON_THROW_ON_ERROR);
        $claudeBlocks = $claudePayload['messages'][0]['content'];
        self::assertSame('image', $claudeBlocks[0]['type']);
        self::assertSame('image/jpeg', $claudeBlocks[0]['source']['media_type']);
        self::assertSame('document', $claudeBlocks[1]['type']);
        self::assertSame('application/pdf', $claudeBlocks[1]['source']['media_type']);
        self::assertSame('text', $claudeBlocks[2]['type']);

        // 4. Google Gemini (inlineData parts)
        $geminiCaptured = [];
        $geminiTransport = $this->captureTransport($geminiCaptured, json_encode([
            'candidates' => [['content' => ['parts' => [['text' => 'Receipt parsed.']]], 'finishReason' => 'STOP']],
        ], JSON_THROW_ON_ERROR));
        $geminiAi = new AiClient(new GeminiProvider('AIza-test', transport: $geminiTransport));
        self::assertSame('Receipt parsed.', $geminiAi->ask('Parse receipt.', attachments: [$jpeg, $pdf]));
        $geminiPayload = json_decode($geminiCaptured[0]['body'], true, 64, JSON_THROW_ON_ERROR);
        $geminiParts = $geminiPayload['contents'][0]['parts'];
        self::assertSame('Parse receipt.', $geminiParts[0]['text']);
        self::assertSame('image/jpeg', $geminiParts[1]['inlineData']['mimeType']);
        self::assertSame('application/pdf', $geminiParts[2]['inlineData']['mimeType']);

        // 5. Ollama (images array on message + rejects PDF cleanly)
        $ollamaCaptured = [];
        $ollamaTransport = $this->captureTransport($ollamaCaptured, json_encode([
            'model' => 'llama3.2-vision',
            'message' => ['role' => 'assistant', 'content' => 'Water heater photo analyzed.'],
        ], JSON_THROW_ON_ERROR));
        $ollamaAi = new AiClient(new OllamaProvider('llama3.2-vision', transport: $ollamaTransport));
        self::assertSame('Water heater photo analyzed.', $ollamaAi->ask('Check water heater.', attachments: [$jpeg]));
        $ollamaPayload = json_decode($ollamaCaptured[0]['body'], true, 64, JSON_THROW_ON_ERROR);
        self::assertSame([$jpeg->base64Data], $ollamaPayload['messages'][0]['images']);

        try {
            $ollamaAi->chat(new ChatRequest([Message::user('Read PDF', [$pdf])]));
            self::fail('Expected Ollama to reject PDF attachment.');
        } catch (AiException $e) {
            self::assertStringContainsString('raster images only', strtolower($e->getMessage()));
        }
    }

    /**
     * @param list<array{url: string, headers: array<string, string>, body: string}> $captured
     */
    private function captureTransport(array &$captured, string $responseBody): HttpTransportInterface
    {
        return new class ($captured, $responseBody) implements HttpTransportInterface {
            /** @param list<array{url: string, headers: array<string, string>, body: string}> $captured */
            public function __construct(
                private array &$captured,
                private readonly string $responseBody,
            ) {}

            public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 30): HttpResponse
            {
                $this->captured[] = ['url' => $url, 'headers' => $headers, 'body' => $jsonBody];

                return new HttpResponse(200, $this->responseBody);
            }
        };
    }
}
