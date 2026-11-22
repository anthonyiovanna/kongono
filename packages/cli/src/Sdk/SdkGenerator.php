<?php

declare(strict_types=1);

namespace Kongono\Cli\Sdk;

use InvalidArgumentException;
use Kongono\Http\App;
use Kongono\Http\OpenApi\OpenApiGenerator;

/**
 * Generates typed, zero-runtime-bloat client SDKs for TypeScript (Web/React/RN),
 * Swift (iOS/macOS), Kotlin (Android/JVM), and Dart (Flutter) directly from
 * Kongono's live OpenAPI 3.1 spec (PHP 8.5 attribute routes + readonly DTOs).
 */
final class SdkGenerator
{
    /** @var list<string> */
    public const array SUPPORTED_LANGUAGES = ['ts', 'swift', 'kotlin', 'dart'];

    public function generate(App $app, string $language, string $clientName = 'KongonoClient', string $version = '1.0.0'): string
    {
        $lang = $this->normalizeLanguage($language);
        $safeClient = $this->sanitizeIdentifier($clientName, 'KongonoClient');
        $spec = (new OpenApiGenerator())->generate($app, $version);

        return $this->generateFromSpec($spec, $lang, $safeClient);
    }

    /**
     * @param array<string, mixed> $spec
     */
    public function generateFromSpec(array $spec, string $language, string $clientName = 'KongonoClient'): string
    {
        $lang = $this->normalizeLanguage($language);
        $safeClient = $this->sanitizeIdentifier($clientName, 'KongonoClient');
        $info = is_array($spec['info'] ?? null) ? $spec['info'] : [];
        $appTitle = is_string($info['title'] ?? null) ? $info['title'] : 'Kongono API';
        $appVersion = is_string($info['version'] ?? null) ? $info['version'] : '1.0.0';

        $schemas = $this->extractSchemas($spec);
        $operations = $this->extractOperations($spec);

        return match ($lang) {
            'ts' => $this->renderTypeScript($appTitle, $appVersion, $safeClient, $schemas, $operations),
            'swift' => $this->renderSwift($appTitle, $appVersion, $safeClient, $schemas, $operations),
            'kotlin' => $this->renderKotlin($appTitle, $appVersion, $safeClient, $schemas, $operations),
            'dart' => $this->renderDart($appTitle, $appVersion, $safeClient, $schemas, $operations),
        };
    }

    /** @return 'ts'|'swift'|'kotlin'|'dart' */
    public function normalizeLanguage(string $language): string
    {
        return match (strtolower(trim($language))) {
            'ts', 'typescript' => 'ts',
            'swift', 'ios' => 'swift',
            'kotlin', 'kt', 'android' => 'kotlin',
            'dart', 'flutter' => 'dart',
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported SDK language "%s". Supported languages: %s.',
                $language,
                implode(', ', self::SUPPORTED_LANGUAGES),
            )),
        };
    }

    public function defaultExtension(string $language): string
    {
        return match ($this->normalizeLanguage($language)) {
            'ts' => 'ts',
            'swift' => 'swift',
            'kotlin' => 'kt',
            'dart' => 'dart',
        };
    }

    /**
     * @param array<string, mixed> $spec
     * @return array<string, array{properties: array<string, array{type: string, required: bool, enum: list<string>}>}>
     */
    private function extractSchemas(array $spec): array
    {
        $components = is_array($spec['components'] ?? null) ? $spec['components'] : [];
        $rawSchemas = is_array($components['schemas'] ?? null) ? $components['schemas'] : [];
        $schemas = [];

        foreach ($rawSchemas as $name => $rawSchema) {
            if (!is_string($name) || !is_array($rawSchema)) {
                continue;
            }
            $requiredList = [];
            if (is_array($rawSchema['required'] ?? null)) {
                foreach ($rawSchema['required'] as $reqField) {
                    if (is_string($reqField)) {
                        $requiredList[] = $reqField;
                    }
                }
            }
            $rawProps = is_array($rawSchema['properties'] ?? null) ? $rawSchema['properties'] : [];
            $props = [];
            foreach ($rawProps as $propName => $propDef) {
                if (!is_string($propName) || !is_array($propDef)) {
                    continue;
                }
                $enumValues = [];
                if (is_array($propDef['enum'] ?? null)) {
                    foreach ($propDef['enum'] as $ev) {
                        if (is_string($ev) || is_int($ev) || is_float($ev)) {
                            $enumValues[] = (string) $ev;
                        }
                    }
                }
                $props[$propName] = [
                    'type' => is_string($propDef['type'] ?? null) ? $propDef['type'] : 'string',
                    'required' => in_array($propName, $requiredList, true),
                    'enum' => $enumValues,
                ];
            }
            $schemas[$this->sanitizeIdentifier($name, 'Dto')] = ['properties' => $props];
        }

        return $schemas;
    }

    /**
     * @param array<string, mixed> $spec
     * @return list<array{methodName: string, httpMethod: string, path: string, params: list<array{name: string, type: string}>, bodyType: ?string}>
     */
    private function extractOperations(array $spec): array
    {
        $rawPaths = is_array($spec['paths'] ?? null) ? $spec['paths'] : [];
        $operations = [];
        /** @var array<string, int> $usedNames */
        $usedNames = [];

        foreach ($rawPaths as $path => $methods) {
            if (!is_string($path) || !is_array($methods)) {
                continue;
            }
            foreach ($methods as $httpMethod => $op) {
                if (!is_string($httpMethod) || !is_array($op)) {
                    continue;
                }
                $upperMethod = strtoupper($httpMethod);
                $rawOpId = is_string($op['operationId'] ?? null) ? $op['operationId'] : $httpMethod . '_' . $path;
                $baseName = $this->toCamelCase($rawOpId);
                if ('' === $baseName || 'root' === $baseName && '/' !== $path) {
                    $baseName = strtolower($upperMethod) . 'Root';
                }
                if (isset($usedNames[$baseName])) {
                    $candidate = strtolower($upperMethod) . ucfirst($baseName);
                    $idx = 2;
                    while (isset($usedNames[$candidate])) {
                        $candidate = strtolower($upperMethod) . ucfirst($baseName) . $idx;
                        ++$idx;
                    }
                    $methodName = $candidate;
                } else {
                    $methodName = $baseName;
                }
                $usedNames[$methodName] = 1;

                $params = [];
                if (is_array($op['parameters'] ?? null)) {
                    foreach ($op['parameters'] as $param) {
                        if (!is_array($param) || !is_string($param['name'] ?? null)) {
                            continue;
                        }
                        $pSchema = is_array($param['schema'] ?? null) ? $param['schema'] : [];
                        $params[] = [
                            'name' => $this->toCamelCase($param['name']),
                            'type' => is_string($pSchema['type'] ?? null) ? $pSchema['type'] : 'string',
                        ];
                    }
                }

                $bodyType = null;
                if (is_array($op['requestBody'] ?? null)) {
                    $content = is_array($op['requestBody']['content'] ?? null) ? $op['requestBody']['content'] : [];
                    $jsonContent = is_array($content['application/json'] ?? null) ? $content['application/json'] : [];
                    $bodySchema = is_array($jsonContent['schema'] ?? null) ? $jsonContent['schema'] : [];
                    $ref = is_string($bodySchema['$ref'] ?? null) ? $bodySchema['$ref'] : '';
                    if (str_starts_with($ref, '#/components/schemas/')) {
                        $bodyType = $this->sanitizeIdentifier(substr($ref, strlen('#/components/schemas/')), 'Input');
                    }
                }

                $operations[] = [
                    'methodName' => $methodName,
                    'httpMethod' => $upperMethod,
                    'path' => $path,
                    'params' => $params,
                    'bodyType' => $bodyType,
                ];
            }
        }

        return $operations;
    }

    /**
     * @param array<string, array{properties: array<string, array{type: string, required: bool, enum: list<string>}>}>                              $schemas
     * @param list<array{methodName: string, httpMethod: string, path: string, params: list<array{name: string, type: string}>, bodyType: ?string}> $operations
     */
    private function renderTypeScript(string $appTitle, string $appVersion, string $clientName, array $schemas, array $operations): string
    {
        $lines = [
            sprintf('// Auto-generated by Kongono SDK Generator (%s v%s)', $appTitle, $appVersion),
            '// Do not edit by hand — re-run `kongo sdk:generate --lang=ts`.',
            '',
        ];

        foreach ($schemas as $name => $schema) {
            $lines[] = sprintf('export interface %s {', $name);
            foreach ($schema['properties'] as $propName => $prop) {
                $tsType = $this->tsType($prop['type'], $prop['enum']);
                $opt = $prop['required'] ? '' : '?';
                $lines[] = sprintf('  %s%s: %s;', $propName, $opt, $tsType);
            }
            $lines[] = '}';
            $lines[] = '';
        }

        $lines[] = 'export interface RequestOptions {';
        $lines[] = '  headers?: Record<string, string>;';
        $lines[] = '  idempotencyKey?: string;';
        $lines[] = '  signal?: AbortSignal;';
        $lines[] = '}';
        $lines[] = '';
        $lines[] = sprintf('export interface %sOptions {', $clientName);
        $lines[] = '  baseUrl: string;';
        $lines[] = '  bearerToken?: string;';
        $lines[] = '  defaultHeaders?: Record<string, string>;';
        $lines[] = '  fetchImpl?: typeof fetch;';
        $lines[] = '}';
        $lines[] = '';
        $lines[] = 'export class KongonoApiError extends Error {';
        $lines[] = '  constructor(';
        $lines[] = '    public readonly status: number,';
        $lines[] = '    public readonly body: unknown,';
        $lines[] = '    message?: string,';
        $lines[] = '  ) {';
        $lines[] = '    super(message ?? `Kongono API request failed with HTTP ${status}`);';
        $lines[] = '    this.name = "KongonoApiError";';
        $lines[] = '  }';
        $lines[] = '}';
        $lines[] = '';
        $lines[] = sprintf('export class %s {', $clientName);
        $lines[] = '  private readonly baseUrl: string;';
        $lines[] = '  private readonly bearerToken?: string;';
        $lines[] = '  private readonly defaultHeaders: Record<string, string>;';
        $lines[] = '  private readonly fetchImpl: typeof fetch;';
        $lines[] = '';
        $lines[] = sprintf('  constructor(options: %sOptions) {', $clientName);
        $lines[] = '    this.baseUrl = options.baseUrl.replace(/\/+$/, "");';
        $lines[] = '    this.bearerToken = options.bearerToken;';
        $lines[] = '    this.defaultHeaders = options.defaultHeaders ?? {};';
        $lines[] = '    this.fetchImpl = options.fetchImpl ?? globalThis.fetch.bind(globalThis);';
        $lines[] = '  }';

        foreach ($operations as $op) {
            $sigArgs = [];
            foreach ($op['params'] as $param) {
                $sigArgs[] = sprintf('%s: %s', $param['name'], $this->tsType($param['type'], []));
            }
            if (null !== $op['bodyType']) {
                $sigArgs[] = sprintf('body: %s', $op['bodyType']);
            }
            $sigArgs[] = 'options?: RequestOptions';

            $interpolatedPath = (string) preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                fn(array $m): string => '${encodeURIComponent(String(' . $this->toCamelCase($m[1]) . '))}',
                $op['path'],
            );

            $lines[] = '';
            $lines[] = sprintf('  async %s<T = unknown>(%s): Promise<T> {', $op['methodName'], implode(', ', $sigArgs));
            $bodyArg = null !== $op['bodyType'] ? 'body' : 'undefined';
            $lines[] = sprintf('    return this.request<T>("%s", `%s`, %s, options);', $op['httpMethod'], $interpolatedPath, $bodyArg);
            $lines[] = '  }';
        }

        $lines[] = '';
        $lines[] = '  private async request<T>(method: string, path: string, body?: unknown, options?: RequestOptions): Promise<T> {';
        $lines[] = '    const headers: Record<string, string> = {';
        $lines[] = '      Accept: "application/json",';
        $lines[] = '      ...this.defaultHeaders,';
        $lines[] = '      ...(options?.headers ?? {}),';
        $lines[] = '    };';
        $lines[] = '    if (this.bearerToken) {';
        $lines[] = '      headers.Authorization = `Bearer ${this.bearerToken}`;';
        $lines[] = '    }';
        $lines[] = '    if (options?.idempotencyKey) {';
        $lines[] = '      headers["Idempotency-Key"] = options.idempotencyKey;';
        $lines[] = '    }';
        $lines[] = '    let payload: string | undefined;';
        $lines[] = '    if (body !== undefined) {';
        $lines[] = '      headers["Content-Type"] = "application/json";';
        $lines[] = '      payload = JSON.stringify(body);';
        $lines[] = '    }';
        $lines[] = '    const response = await this.fetchImpl(`${this.baseUrl}${path}`, {';
        $lines[] = '      method,';
        $lines[] = '      headers,';
        $lines[] = '      body: payload,';
        $lines[] = '      signal: options?.signal,';
        $lines[] = '    });';
        $lines[] = '    const contentType = response.headers.get("content-type") ?? "";';
        $lines[] = '    const parsed: unknown = contentType.includes("application/json") ? await response.json() : await response.text();';
        $lines[] = '    if (!response.ok) {';
        $lines[] = '      throw new KongonoApiError(response.status, parsed);';
        $lines[] = '    }';
        $lines[] = '    return parsed as T;';
        $lines[] = '  }';
        $lines[] = '}';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string, array{properties: array<string, array{type: string, required: bool, enum: list<string>}>}>                              $schemas
     * @param list<array{methodName: string, httpMethod: string, path: string, params: list<array{name: string, type: string}>, bodyType: ?string}> $operations
     */
    private function renderSwift(string $appTitle, string $appVersion, string $clientName, array $schemas, array $operations): string
    {
        $lines = [
            sprintf('// Auto-generated by Kongono SDK Generator (%s v%s)', $appTitle, $appVersion),
            '// Do not edit by hand — re-run `kongo sdk:generate --lang=swift`.',
            '',
            'import Foundation',
            '',
        ];

        foreach ($schemas as $name => $schema) {
            $lines[] = sprintf('public struct %s: Codable, Sendable {', $name);
            $initArgs = [];
            foreach ($schema['properties'] as $propName => $prop) {
                $swiftType = $this->swiftType($prop['type']) . ($prop['required'] ? '' : '?');
                $lines[] = sprintf('    public let %s: %s', $propName, $swiftType);
                $initArgs[] = sprintf('%s: %s%s', $propName, $swiftType, $prop['required'] ? '' : ' = nil');
            }
            $lines[] = '';
            $lines[] = sprintf('    public init(%s) {', implode(', ', $initArgs));
            foreach ($schema['properties'] as $propName => $_) {
                $lines[] = sprintf('        self.%s = %s', $propName, $propName);
            }
            $lines[] = '    }';
            $lines[] = '}';
            $lines[] = '';
        }

        $lines[] = 'public enum KongonoApiError: Error, Sendable {';
        $lines[] = '    case invalidURL(String)';
        $lines[] = '    case httpStatus(Int, String)';
        $lines[] = '}';
        $lines[] = '';
        $lines[] = sprintf('public final class %s: Sendable {', $clientName);
        $lines[] = '    private let baseURL: String';
        $lines[] = '    private let bearerToken: String?';
        $lines[] = '    private let session: URLSession';
        $lines[] = '';
        $lines[] = '    public init(baseURL: String, bearerToken: String? = nil, session: URLSession = .shared) {';
        $lines[] = '        self.baseURL = baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))';
        $lines[] = '        self.bearerToken = bearerToken';
        $lines[] = '        self.session = session';
        $lines[] = '    }';

        foreach ($operations as $op) {
            $sigArgs = [];
            foreach ($op['params'] as $param) {
                $sigArgs[] = sprintf('%s: %s', $param['name'], $this->swiftType($param['type']));
            }
            if (null !== $op['bodyType']) {
                $sigArgs[] = sprintf('body: %s', $op['bodyType']);
            }
            $sigArgs[] = 'idempotencyKey: String? = nil';

            $interpolatedPath = (string) preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                fn(array $m): string => '\\(' . $this->toCamelCase($m[1]) . ')',
                $op['path'],
            );

            $lines[] = '';
            $lines[] = sprintf('    public func %s(%s) async throws -> Data {', $op['methodName'], implode(', ', $sigArgs));
            if (null !== $op['bodyType']) {
                $lines[] = '        let payload = try JSONEncoder().encode(body)';
                $lines[] = sprintf('        return try await request(method: "%s", path: "%s", body: payload, idempotencyKey: idempotencyKey)', $op['httpMethod'], $interpolatedPath);
            } else {
                $lines[] = sprintf('        return try await request(method: "%s", path: "%s", body: nil, idempotencyKey: idempotencyKey)', $op['httpMethod'], $interpolatedPath);
            }
            $lines[] = '    }';
        }

        $lines[] = '';
        $lines[] = '    private func request(method: String, path: String, body: Data?, idempotencyKey: String?) async throws -> Data {';
        $lines[] = '        let full = "\(baseURL)\(path)"';
        $lines[] = '        guard let url = URL(string: full) else {';
        $lines[] = '            throw KongonoApiError.invalidURL(full)';
        $lines[] = '        }';
        $lines[] = '        var req = URLRequest(url: url)';
        $lines[] = '        req.httpMethod = method';
        $lines[] = '        req.setValue("application/json", forHTTPHeaderField: "Accept")';
        $lines[] = '        if let token = bearerToken {';
        $lines[] = '            req.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")';
        $lines[] = '        }';
        $lines[] = '        if let key = idempotencyKey {';
        $lines[] = '            req.setValue(key, forHTTPHeaderField: "Idempotency-Key")';
        $lines[] = '        }';
        $lines[] = '        if let body {';
        $lines[] = '            req.setValue("application/json", forHTTPHeaderField: "Content-Type")';
        $lines[] = '            req.httpBody = body';
        $lines[] = '        }';
        $lines[] = '        let (data, response) = try await session.data(for: req)';
        $lines[] = '        if let http = response as? HTTPURLResponse, !(200...299).contains(http.statusCode) {';
        $lines[] = '            throw KongonoApiError.httpStatus(http.statusCode, String(data: data, encoding: .utf8) ?? "")';
        $lines[] = '        }';
        $lines[] = '        return data';
        $lines[] = '    }';
        $lines[] = '}';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string, array{properties: array<string, array{type: string, required: bool, enum: list<string>}>}>                              $schemas
     * @param list<array{methodName: string, httpMethod: string, path: string, params: list<array{name: string, type: string}>, bodyType: ?string}> $operations
     */
    private function renderKotlin(string $appTitle, string $appVersion, string $clientName, array $schemas, array $operations): string
    {
        $lines = [
            sprintf('// Auto-generated by Kongono SDK Generator (%s v%s)', $appTitle, $appVersion),
            '// Do not edit by hand — re-run `kongo sdk:generate --lang=kotlin`.',
            '',
            'package kongono.sdk',
            '',
            'import java.net.HttpURLConnection',
            'import java.net.URI',
            'import java.net.URLEncoder',
            'import java.nio.charset.StandardCharsets',
            '',
        ];

        foreach ($schemas as $name => $schema) {
            $props = [];
            foreach ($schema['properties'] as $propName => $prop) {
                $ktType = $this->kotlinType($prop['type']) . ($prop['required'] ? '' : '? = null');
                $props[] = sprintf('    val %s: %s,', $propName, $ktType);
            }
            $lines[] = sprintf('data class %s(', $name);
            foreach ($props as $line) {
                $lines[] = $line;
            }
            $lines[] = ') {';
            $lines[] = '    fun toJson(): String {';
            $pairs = [];
            foreach ($schema['properties'] as $propName => $prop) {
                $encoded = match ($prop['type']) {
                    'integer', 'number', 'boolean' => sprintf('"%s":%s', $propName, '$' . $propName),
                    default => sprintf('"%s":"%s"', $propName, '${' . $propName . '?.toString()?.replace("\\", "\\\\")?.replace("\"", "\\\"") ?: ""}'),
                };
                $pairs[] = $encoded;
            }
            $lines[] = '        return "{' . implode(',', $pairs) . '}"';
            $lines[] = '    }';
            $lines[] = '}';
            $lines[] = '';
        }

        $lines[] = 'class KongonoApiException(val status: Int, val responseBody: String) :';
        $lines[] = '    RuntimeException("Kongono API request failed with HTTP $status")';
        $lines[] = '';
        $lines[] = sprintf('class %s(', $clientName);
        $lines[] = '    baseUrl: String,';
        $lines[] = '    private val bearerToken: String? = null,';
        $lines[] = ') {';
        $lines[] = '    private val normalizedBaseUrl: String = baseUrl.trimEnd(\'/\')';

        foreach ($operations as $op) {
            $sigArgs = [];
            foreach ($op['params'] as $param) {
                $sigArgs[] = sprintf('%s: %s', $param['name'], $this->kotlinType($param['type']));
            }
            if (null !== $op['bodyType']) {
                $sigArgs[] = sprintf('body: %s', $op['bodyType']);
            }
            $sigArgs[] = 'idempotencyKey: String? = null';

            $interpolatedPath = (string) preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                fn(array $m): string => '${encodePart(' . $this->toCamelCase($m[1]) . '.toString())}',
                $op['path'],
            );

            $lines[] = '';
            $lines[] = sprintf('    fun %s(%s): String {', $op['methodName'], implode(', ', $sigArgs));
            $bodyExpr = null !== $op['bodyType'] ? 'body.toJson()' : 'null';
            $lines[] = sprintf('        return request("%s", "%s", %s, idempotencyKey)', $op['httpMethod'], $interpolatedPath, $bodyExpr);
            $lines[] = '    }';
        }

        $lines[] = '';
        $lines[] = '    private fun encodePart(value: String): String =';
        $lines[] = '        URLEncoder.encode(value, StandardCharsets.UTF_8.name())';
        $lines[] = '';
        $lines[] = '    private fun request(method: String, path: String, jsonBody: String?, idempotencyKey: String?): String {';
        $lines[] = '        val conn = URI("$normalizedBaseUrl$path").toURL().openConnection() as HttpURLConnection';
        $lines[] = '        conn.requestMethod = method';
        $lines[] = '        conn.setRequestProperty("Accept", "application/json")';
        $lines[] = '        if (!bearerToken.isNullOrBlank()) {';
        $lines[] = '            conn.setRequestProperty("Authorization", "Bearer $bearerToken")';
        $lines[] = '        }';
        $lines[] = '        if (!idempotencyKey.isNullOrBlank()) {';
        $lines[] = '            conn.setRequestProperty("Idempotency-Key", idempotencyKey)';
        $lines[] = '        }';
        $lines[] = '        if (jsonBody != null) {';
        $lines[] = '            conn.doOutput = true';
        $lines[] = '            conn.setRequestProperty("Content-Type", "application/json")';
        $lines[] = '            conn.outputStream.use { it.write(jsonBody.toByteArray(StandardCharsets.UTF_8)) }';
        $lines[] = '        }';
        $lines[] = '        val status = conn.responseCode';
        $lines[] = '        val stream = if (status in 200..299) conn.inputStream else conn.errorStream';
        $lines[] = '        val bodyText = stream?.bufferedReader(StandardCharsets.UTF_8)?.use { it.readText() } ?: ""';
        $lines[] = '        if (status !in 200..299) {';
        $lines[] = '            throw KongonoApiException(status, bodyText)';
        $lines[] = '        }';
        $lines[] = '        return bodyText';
        $lines[] = '    }';
        $lines[] = '}';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string, array{properties: array<string, array{type: string, required: bool, enum: list<string>}>}>                              $schemas
     * @param list<array{methodName: string, httpMethod: string, path: string, params: list<array{name: string, type: string}>, bodyType: ?string}> $operations
     */
    private function renderDart(string $appTitle, string $appVersion, string $clientName, array $schemas, array $operations): string
    {
        $lines = [
            sprintf('// Auto-generated by Kongono SDK Generator (%s v%s)', $appTitle, $appVersion),
            '// Do not edit by hand — re-run `kongo sdk:generate --lang=dart`.',
            '',
            "import 'dart:convert';",
            "import 'dart:io';",
            '',
        ];

        foreach ($schemas as $name => $schema) {
            $lines[] = sprintf('final class %s {', $name);
            $ctorArgs = [];
            foreach ($schema['properties'] as $propName => $prop) {
                $dartType = $this->dartType($prop['type']) . ($prop['required'] ? '' : '?');
                $lines[] = sprintf('  final %s %s;', $dartType, $propName);
                $ctorArgs[] = ($prop['required'] ? 'required ' : '') . 'this.' . $propName;
            }
            $lines[] = '';
            $lines[] = sprintf('  const %s({%s});', $name, implode(', ', $ctorArgs));
            $lines[] = '';
            $lines[] = '  Map<String, dynamic> toJson() => {';
            foreach ($schema['properties'] as $propName => $_) {
                $lines[] = sprintf("    '%s': %s,", $propName, $propName);
            }
            $lines[] = '  };';
            $lines[] = '}';
            $lines[] = '';
        }

        $lines[] = 'final class KongonoApiException implements Exception {';
        $lines[] = '  final int status;';
        $lines[] = '  final dynamic body;';
        $lines[] = '  const KongonoApiException(this.status, this.body);';
        $lines[] = '  @override';
        $lines[] = "  String toString() => 'KongonoApiException(HTTP \$status)';";
        $lines[] = '}';
        $lines[] = '';
        $lines[] = sprintf('final class %s {', $clientName);
        $lines[] = '  final String baseUrl;';
        $lines[] = '  final String? bearerToken;';
        $lines[] = '  final HttpClient _http;';
        $lines[] = '';
        $lines[] = sprintf('  %s({required String baseUrl, this.bearerToken, HttpClient? httpClient})', $clientName);
        $lines[] = "      : baseUrl = baseUrl.replaceAll(RegExp(r'/+\$'), ''),";
        $lines[] = '        _http = httpClient ?? HttpClient();';

        foreach ($operations as $op) {
            $sigArgs = [];
            foreach ($op['params'] as $param) {
                $sigArgs[] = sprintf('required %s %s', $this->dartType($param['type']), $param['name']);
            }
            if (null !== $op['bodyType']) {
                $sigArgs[] = sprintf('required %s body', $op['bodyType']);
            }
            $sigArgs[] = 'String? idempotencyKey';

            $interpolatedPath = (string) preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                fn(array $m): string => '${Uri.encodeComponent(' . $this->toCamelCase($m[1]) . '.toString())}',
                $op['path'],
            );

            $lines[] = '';
            $lines[] = sprintf('  Future<dynamic> %s({%s}) {', $op['methodName'], implode(', ', $sigArgs));
            $bodyArg = null !== $op['bodyType'] ? 'body.toJson()' : 'null';
            $lines[] = sprintf("    return _request('%s', '%s', body: %s, idempotencyKey: idempotencyKey);", $op['httpMethod'], $interpolatedPath, $bodyArg);
            $lines[] = '  }';
        }

        $lines[] = '';
        $lines[] = '  Future<dynamic> _request(String method, String path, {Map<String, dynamic>? body, String? idempotencyKey}) async {';
        $lines[] = '    final uri = Uri.parse("$baseUrl$path");';
        $lines[] = '    final req = await _http.openUrl(method, uri);';
        $lines[] = "    req.headers.set(HttpHeaders.acceptHeader, 'application/json');";
        $lines[] = '    if (bearerToken != null && bearerToken!.isNotEmpty) {';
        $lines[] = "      req.headers.set(HttpHeaders.authorizationHeader, 'Bearer \$bearerToken');";
        $lines[] = '    }';
        $lines[] = '    if (idempotencyKey != null && idempotencyKey.isNotEmpty) {';
        $lines[] = "      req.headers.set('Idempotency-Key', idempotencyKey);";
        $lines[] = '    }';
        $lines[] = '    if (body != null) {';
        $lines[] = '      req.headers.contentType = ContentType.json;';
        $lines[] = '      req.write(jsonEncode(body));';
        $lines[] = '    }';
        $lines[] = '    final res = await req.close();';
        $lines[] = '    final raw = await utf8.decodeStream(res);';
        $lines[] = "    final isJson = (res.headers.contentType?.mimeType ?? '').contains('json');";
        $lines[] = '    final parsed = isJson && raw.isNotEmpty ? jsonDecode(raw) : raw;';
        $lines[] = '    if (res.statusCode < 200 || res.statusCode >= 300) {';
        $lines[] = '      throw KongonoApiException(res.statusCode, parsed);';
        $lines[] = '    }';
        $lines[] = '    return parsed;';
        $lines[] = '  }';
        $lines[] = '}';

        return implode("\n", $lines) . "\n";
    }

    /** @param list<string> $enum */
    private function tsType(string $openApiType, array $enum): string
    {
        if ([] !== $enum) {
            return implode(' | ', array_map(static fn(string $v): string => json_encode($v, JSON_THROW_ON_ERROR), $enum));
        }

        return match ($openApiType) {
            'integer', 'number' => 'number',
            'boolean' => 'boolean',
            'array' => 'unknown[]',
            'object' => 'Record<string, unknown>',
            default => 'string',
        };
    }

    private function swiftType(string $openApiType): string
    {
        return match ($openApiType) {
            'integer' => 'Int',
            'number' => 'Double',
            'boolean' => 'Bool',
            'array' => '[String]',
            default => 'String',
        };
    }

    private function kotlinType(string $openApiType): string
    {
        return match ($openApiType) {
            'integer' => 'Int',
            'number' => 'Double',
            'boolean' => 'Boolean',
            'array' => 'List<String>',
            default => 'String',
        };
    }

    private function dartType(string $openApiType): string
    {
        return match ($openApiType) {
            'integer' => 'int',
            'number' => 'double',
            'boolean' => 'bool',
            'array' => 'List<dynamic>',
            'object' => 'Map<String, dynamic>',
            default => 'String',
        };
    }

    private function sanitizeIdentifier(string $raw, string $fallback): string
    {
        $clean = (string) preg_replace('/[^a-zA-Z0-9_]/', '', $raw);
        if ('' === $clean || ctype_digit($clean[0])) {
            return $fallback;
        }

        return $clean;
    }

    private function toCamelCase(string $raw): string
    {
        $parts = preg_split('/[^a-zA-Z0-9]+/', trim($raw));
        if (!is_array($parts)) {
            return 'call';
        }
        $filtered = array_values(array_filter($parts, static fn(string $p): bool => '' !== $p));
        if ([] === $filtered) {
            return 'root';
        }

        $first = strtolower($filtered[0]);
        $rest = array_slice($filtered, 1);
        $camel = $first . implode('', array_map(static fn(string $p): string => ucfirst(strtolower($p)), $rest));

        if (ctype_digit($camel[0])) {
            return 'op' . ucfirst($camel);
        }

        return $camel;
    }
}
