<?php

declare(strict_types=1);

/**
 * Generate request/response DTOs from the Partner API OpenAPI fixture.
 *
 * Generated resources are the contract surface the SDK serves: an API field
 * change is a generator run, not a guessing exercise. src/Resource keeps thin
 * deprecated subclasses so existing consumers do not break, plus the response
 * envelopes the contract does not name.
 *
 * Resource files carry four marked regions:
 *
 *   AUTO FIELDS / AUTO HYDRATION      rewritten from the spec on every run
 *   MANUAL FIELDS / MANUAL HYDRATION  preserved across runs — edit these
 *
 * A manual field of the same property name wins: the generator drops the auto
 * field it shadows. This is what makes the generator usable against travelo-api's
 * Scramble output, which is uneven — some schemas arrive with no properties at
 * all (TourCalendarDetailResource is `type: array`), and the only place to say
 * what they really contain is a region a regeneration will not clobber.
 */

$root = dirname(__DIR__);
$options = parseOptions($argv);
$specPath = $options['spec'] ?? $root . '/tests/fixtures/partner-api.openapi.json';
$outDir = $options['out'] ?? $root . '/src/Generated';
$check = array_key_exists('check', $options);

$spec = readSpec($specPath);
$generator = new OpenApiDtoGenerator($spec);

if ($check) {
    $tmpDir = sys_get_temp_dir() . '/tour-sdk-generated-' . bin2hex(random_bytes(6));
    // Manual regions are read from the real output, so --check compares like for
    // like instead of reporting every hand-edited region as drift.
    $generator->writeTo($tmpDir, $outDir);

    $diff = compareDirectories($tmpDir, $outDir);
    removeDirectory($tmpDir);

    if ($diff !== []) {
        fwrite(STDERR, "Generated DTOs are not up to date:\n  " . implode("\n  ", $diff) . "\n");
        fwrite(STDERR, "Run: composer generate:contract\n");
        exit(1);
    }

    echo "Generated DTOs are up to date.\n";
    exit(0);
}

// Written in place. Wiping the directory first would take the MANUAL regions with it.
$written = $generator->writeTo($outDir, $outDir);

foreach (pruneStale($outDir, $written) as $stale) {
    echo "Removed stale {$stale}\n";
}

foreach ($generator->warnings() as $warning) {
    fwrite(STDERR, "Warning: {$warning}\n");
}

echo "Generated DTOs written to {$outDir}\n";

/**
 * Delete files that no longer correspond to anything in the spec.
 *
 * @param list<string> $written Relative paths the generator just produced.
 * @return list<string>
 */
function pruneStale(string $outDir, array $written): array
{
    $keep = array_flip($written);
    $removed = [];

    foreach (array_keys(directoryFiles($outDir)) as $relative) {
        if (array_key_exists($relative, $keep)) {
            continue;
        }

        unlink($outDir . '/' . $relative);
        $removed[] = $relative;
    }

    sort($removed);

    return $removed;
}

/**
 * @param list<string> $argv
 * @return array<string, string|bool>
 */
function parseOptions(array $argv): array
{
    $options = [];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--check') {
            $options['check'] = true;
            continue;
        }

        foreach (['spec', 'out'] as $name) {
            $prefix = "--{$name}=";
            if (str_starts_with($arg, $prefix)) {
                $options[$name] = substr($arg, strlen($prefix));
                continue 2;
            }
        }
    }

    return $options;
}

/**
 * @return array<string, mixed>
 */
function readSpec(string $path): array
{
    if (! is_file($path)) {
        throw new RuntimeException("OpenAPI spec not found: {$path}");
    }

    $spec = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    if (! is_array($spec)) {
        throw new RuntimeException("OpenAPI spec is not an object: {$path}");
    }

    return $spec;
}

/**
 * @return list<string>
 */
function compareDirectories(string $expectedDir, string $actualDir): array
{
    $expected = directoryFiles($expectedDir);
    $actual = is_dir($actualDir) ? directoryFiles($actualDir) : [];
    $diff = [];

    foreach (array_unique(array_merge(array_keys($expected), array_keys($actual))) as $relative) {
        if (! array_key_exists($relative, $actual)) {
            $diff[] = "missing {$relative}";
            continue;
        }

        if (! array_key_exists($relative, $expected)) {
            $diff[] = "stale {$relative}";
            continue;
        }

        if ($expected[$relative] !== $actual[$relative]) {
            $diff[] = "changed {$relative}";
        }
    }

    sort($diff);

    return $diff;
}

/**
 * @return array<string, string>
 */
function directoryFiles(string $dir): array
{
    if (! is_dir($dir)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
        $files[$relative] = (string) file_get_contents($file->getPathname());
    }

    ksort($files);

    return $files;
}

function removeDirectory(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo) {
            continue;
        }

        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($dir);
}

final class OpenApiDtoGenerator
{
    public const MANUAL_BODY_START = '/* BEGIN MANUAL BODY */';
    public const MANUAL_BODY_END = '/* END MANUAL BODY */';
    public const MANUAL_IMPORTS_START = '/* BEGIN MANUAL IMPORTS */';
    public const MANUAL_IMPORTS_END = '/* END MANUAL IMPORTS */';
    public const AUTO_FIELDS_START = '/* BEGIN AUTO FIELDS */';
    public const AUTO_FIELDS_END = '/* END AUTO FIELDS */';
    public const MANUAL_FIELDS_START = '/* BEGIN MANUAL FIELDS */';
    public const MANUAL_FIELDS_END = '/* END MANUAL FIELDS */';
    public const AUTO_HYDRATION_START = '/* BEGIN AUTO HYDRATION */';
    public const AUTO_HYDRATION_END = '/* END AUTO HYDRATION */';
    public const MANUAL_HYDRATION_START = '/* BEGIN MANUAL HYDRATION */';
    public const MANUAL_HYDRATION_END = '/* END MANUAL HYDRATION */';

    /**
     * Response envelopes worth a class of their own, keyed by operationId.
     *
     * The contract does not name these: each is an inline `data` schema on one
     * operation. Names are pinned here rather than derived from the operationId so
     * they stay the ones consumers already import.
     */
    private const ENVELOPES = [
        'partnerToursSearch' => 'TourListResource',
        'partnerAccountListBookings' => 'BookingListResource',
        'partnerToursGetAvailability' => 'TourCalendarDateResource',
    ];

    private const DEFAULT_MANUAL_BODY = "\n    ";
    private const DEFAULT_MANUAL_IMPORTS = "\n";
    private const DEFAULT_MANUAL_FIELDS = "\n    ";
    private const DEFAULT_MANUAL_HYDRATION = "\n    ";

    /** @var list<string> */
    private array $warnings = [];

    /**
     * @param array<string, mixed> $spec
     */
    public function __construct(private readonly array $spec)
    {
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * @param string|null $sourceDir Where to read existing MANUAL regions from.
     *                               Defaults to $outDir; --check points it at the
     *                               real output while writing to a temp dir.
     * @return list<string> Relative paths written.
     */
    public function writeTo(string $outDir, ?string $sourceDir = null): array
    {
        $sourceDir ??= $outDir;
        $written = [];

        foreach ($this->requestSpecs() as $request) {
            $relative = 'Request/' . $request['class'] . '.php';
            $existing = $this->readIfExists($sourceDir . '/' . $relative);
            $this->writeFile($outDir . '/' . $relative, $this->requestClass($request, $existing));
            $written[] = $relative;
        }

        foreach ($this->resourceSpecs() as $resource) {
            $relative = 'Resource/' . $resource['class'] . '.php';
            $existing = $this->readIfExists($sourceDir . '/' . $relative);
            $this->writeFile($outDir . '/' . $relative, $this->resourceClass($resource, $existing));
            $written[] = $relative;
        }

        sort($written);

        return $written;
    }

    private function readIfExists(string $path): ?string
    {
        return is_file($path) ? (string) file_get_contents($path) : null;
    }

    /**
     * Return what sits between two markers, or $fallback when the file is new or
     * the region was removed by hand.
     */
    private function extractRegion(?string $source, string $start, string $end, string $fallback): string
    {
        if ($source === null) {
            return $fallback;
        }

        $from = strpos($source, $start);
        $to = strpos($source, $end);

        if ($from === false || $to === false || $to < $from) {
            return $fallback;
        }

        $from += strlen($start);

        return substr($source, $from, $to - $from);
    }

    /**
     * Property names declared in a MANUAL FIELDS region. An auto field of the same
     * name is dropped so the manual declaration is the only one — PHP fatals on a
     * redeclared readonly property, so "manual wins" has to mean "auto is absent".
     *
     * @return array<string, true>
     */
    private function manualPropertyNames(string $manualFields): array
    {
        preg_match_all('/\$([A-Za-z_][A-Za-z0-9_]*)\s*(?:;|=)/', $manualFields, $matches);

        return array_fill_keys($matches[1], true);
    }

    /**
     * @return list<array{class:string, operation_id:string, method:string, path:string, fields:list<array<string, mixed>>}>
     */
    private function requestSpecs(): array
    {
        $requests = $this->requestComponentSpecs();

        foreach (($this->spec['paths'] ?? []) as $path => $pathItem) {
            if (! is_array($pathItem)) {
                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (! in_array(strtolower((string) $method), ['get', 'post', 'put', 'patch', 'delete'], true) || ! is_array($operation)) {
                    continue;
                }

                $operationId = (string) ($operation['operationId'] ?? $this->operationId((string) $method, (string) $path));
                $fields = $this->requestFields($operation);

                if ($fields === []) {
                    continue;
                }

                $requests[] = [
                    'class' => $this->className($operationId . 'Request'),
                    'operation_id' => $operationId,
                    'method' => strtoupper((string) $method),
                    'path' => (string) $path,
                    'fields' => $fields,
                ];
            }
        }

        usort($requests, static fn (array $a, array $b): int => $a['class'] <=> $b['class']);

        return $requests;
    }

    /**
     * Named request-body components are emitted as request payload DTOs, not
     * response resources. This covers nested request items such as applicants[].
     *
     * @return list<array{class:string, operation_id:string, method:string, path:string, fields:list<array<string, mixed>>}>
     */
    private function requestComponentSpecs(): array
    {
        $requests = [];

        foreach (($this->spec['components']['schemas'] ?? []) as $schemaName => $schema) {
            if (! is_array($schema) || ! $this->isRequestSchema((string) $schemaName)) {
                continue;
            }

            $objectSchema = $this->objectSchema($schema);

            $requests[] = [
                'class' => $this->className((string) $schemaName),
                'operation_id' => (string) $schemaName,
                'method' => 'SCHEMA',
                'path' => (string) $schemaName,
                'fields' => $objectSchema === null ? [] : $this->fieldsFromSchema($objectSchema, 'request'),
            ];
        }

        return $requests;
    }

    /**
     * @param array<string, mixed> $operation
     * @return list<array<string, mixed>>
     */
    private function requestFields(array $operation): array
    {
        $bodySchema = $operation['requestBody']['content']['application/json']['schema'] ?? null;
        $bodySchema = is_array($bodySchema) ? $this->objectSchema($bodySchema) : null;

        if ($bodySchema !== null) {
            return $this->fieldsFromSchema($bodySchema, 'request');
        }

        $fields = [];

        foreach (($operation['parameters'] ?? []) as $parameter) {
            if (! is_array($parameter) || ($parameter['in'] ?? null) !== 'query') {
                continue;
            }

            $schema = is_array($parameter['schema'] ?? null) ? $parameter['schema'] : [];
            $fields[] = $this->fieldSpec(
                (string) $parameter['name'],
                $schema,
                (bool) ($parameter['required'] ?? false),
                'request',
            );
        }

        return $this->sortRequiredFirst($fields);
    }

    /**
     * @return list<array{class:string, schema:string, fields:list<array<string, mixed>>, stub:bool}>
     */
    private function resourceSpecs(): array
    {
        $resources = [];

        foreach (($this->spec['components']['schemas'] ?? []) as $schemaName => $schema) {
            if (! is_array($schema)) {
                continue;
            }

            if ($this->isRequestSchema((string) $schemaName)) {
                continue;
            }

            $objectSchema = $this->objectSchema($schema);

            // A named schema the spec failed to describe as an object still gets a
            // class: $refs to it must resolve, and its MANUAL region is the only
            // place anyone can record what it actually contains. Skipping it
            // silently is how the SDK ended up with no calendar-detail DTO at all.
            if ($objectSchema === null) {
                $this->warnings[] = sprintf(
                    'Schema %s has no object properties (type: %s) — emitted as a stub; describe it in its MANUAL regions.',
                    (string) $schemaName,
                    json_encode($schema['type'] ?? null) ?: 'unknown',
                );
            }

            $resources[] = [
                'class' => $this->className((string) $schemaName),
                'schema' => (string) $schemaName,
                'fields' => $objectSchema === null ? [] : $this->fieldsFromSchema($objectSchema, 'resource'),
                'stub' => $objectSchema === null,
            ];
        }

        foreach ($this->envelopeSpecs() as $envelope) {
            $resources[] = $envelope;
        }

        usort($resources, static fn (array $a, array $b): int => $a['class'] <=> $b['class']);

        return $resources;
    }

    /**
     * Classes for the inline `data` schema of the operations in self::ENVELOPES.
     *
     * @return list<array{class:string, schema:string, fields:list<array<string, mixed>>, stub:bool}>
     */
    private function envelopeSpecs(): array
    {
        $envelopes = [];

        foreach (($this->spec['paths'] ?? []) as $path => $pathItem) {
            if (! is_array($pathItem)) {
                continue;
            }

            foreach ($pathItem as $method => $operation) {
                $operationId = is_array($operation) ? (string) ($operation['operationId'] ?? '') : '';
                $class = self::ENVELOPES[$operationId] ?? null;

                if ($class === null) {
                    continue;
                }

                $data = $operation['responses']['200']['content']['application/json']['schema']['properties']['data'] ?? null;
                $objectSchema = is_array($data) ? $this->objectSchema($data) : null;

                if ($objectSchema === null) {
                    $this->warnings[] = sprintf(
                        'Envelope %s (%s %s) has no object properties (data: %s) — emitted as a stub; describe it in its MANUAL regions.',
                        $class,
                        strtoupper((string) $method),
                        (string) $path,
                        json_encode(is_array($data) ? ($data['type'] ?? null) : null) ?: 'unknown',
                    );
                }

                $envelopes[] = [
                    'class' => $class,
                    'schema' => sprintf('the `data` of %s %s (%s)', strtoupper((string) $method), (string) $path, $operationId),
                    'fields' => $objectSchema === null ? [] : $this->fieldsFromSchema($objectSchema, 'resource'),
                    'stub' => $objectSchema === null,
                ];
            }
        }

        return $envelopes;
    }

    /**
     * @param array<string, mixed> $schema
     * @return list<array<string, mixed>>
     */
    private function fieldsFromSchema(array $schema, string $context = 'resource'): array
    {
        $required = array_flip(array_map('strval', is_array($schema['required'] ?? null) ? $schema['required'] : []));
        $fields = [];
        $usedNames = [];

        foreach (($schema['properties'] ?? []) as $key => $propertySchema) {
            if (! is_array($propertySchema)) {
                $propertySchema = [];
            }

            $field = $this->fieldSpec((string) $key, $propertySchema, array_key_exists((string) $key, $required), $context);
            $baseName = $field['name'];
            $suffix = 2;

            while (isset($usedNames[$field['name']])) {
                $field['name'] = $baseName . $suffix;
                $suffix++;
            }

            $usedNames[$field['name']] = true;
            $fields[] = $field;
        }

        return $this->sortRequiredFirst($fields);
    }

    /**
     * @param list<array<string, mixed>> $fields
     * @return list<array<string, mixed>>
     */
    private function sortRequiredFirst(array $fields): array
    {
        usort($fields, static function (array $a, array $b): int {
            if ($a['required'] === $b['required']) {
                return $a['position'] <=> $b['position'];
            }

            return $a['required'] ? -1 : 1;
        });

        return array_values($fields);
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>|null
     */
    private function objectSchema(array $schema): ?array
    {
        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            $resolved = $this->schemaForRef($schema['$ref']);

            return $resolved === null ? null : $this->objectSchema($resolved);
        }

        if (($schema['type'] ?? null) === 'object' || isset($schema['properties'])) {
            return $schema;
        }

        foreach (['anyOf', 'oneOf', 'allOf'] as $key) {
            if (! is_array($schema[$key] ?? null)) {
                continue;
            }

            foreach ($schema[$key] as $variant) {
                if (is_array($variant) && ($objectSchema = $this->objectSchema($variant)) !== null) {
                    return $objectSchema;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function schemaForRef(string $ref): ?array
    {
        $prefix = '#/components/schemas/';

        if (! str_starts_with($ref, $prefix)) {
            return null;
        }

        $name = substr($ref, strlen($prefix));
        $schema = $this->spec['components']['schemas'][$name] ?? null;

        return is_array($schema) ? $schema : null;
    }

    private function isRequestSchema(string $schemaName): bool
    {
        return str_ends_with($this->className($schemaName), 'Request');
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function fieldSpec(string $key, array $schema, bool $required, string $context = 'resource'): array
    {
        static $position = 0;

        $resource = $this->refClass($schema);
        $itemResource = ($schema['type'] ?? null) === 'array' && is_array($schema['items'] ?? null)
            ? $this->refClass($schema['items'])
            : null;

        if ($context === 'request' && $resource !== null && $this->isRequestSchema($resource)) {
            $kind = 'request';
        } elseif ($context === 'request' && $itemResource !== null && $this->isRequestSchema($itemResource)) {
            $kind = 'request_list';
            $resource = $itemResource;
        } elseif ($resource !== null) {
            $kind = 'resource';
        } elseif ($itemResource !== null) {
            $kind = 'resource_list';
            $resource = $itemResource;
        } else {
            $kind = $this->schemaKind($schema);
        }

        $nullable = $this->schemaNullable($schema) || ! $required;

        return [
            'key' => $key,
            'name' => $this->propertyName($key),
            'kind' => $kind,
            'resource' => $resource,
            'nullable' => $nullable,
            'required' => $required,
            'position' => $position++,
        ];
    }

    /**
     * The resource class a schema points at, unwrapping the anyOf/[$ref, null]
     * shape Scramble emits for nullable relations.
     *
     * @param array<string, mixed> $schema
     */
    private function refClass(array $schema): ?string
    {
        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            $name = substr($schema['$ref'], (int) strrpos($schema['$ref'], '/') + 1);

            return $name === '' ? null : $this->className($name);
        }

        foreach (['anyOf', 'oneOf', 'allOf'] as $key) {
            foreach (is_array($schema[$key] ?? null) ? $schema[$key] : [] as $variant) {
                if (is_array($variant) && ($class = $this->refClass($variant)) !== null) {
                    return $class;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function schemaKind(array $schema): string
    {
        if (isset($schema['$ref'])) {
            return 'array';
        }

        foreach (['anyOf', 'oneOf', 'allOf'] as $key) {
            if (! is_array($schema[$key] ?? null)) {
                continue;
            }

            $kinds = [];
            foreach ($schema[$key] as $variant) {
                if (is_array($variant) && ($variant['type'] ?? null) !== 'null') {
                    $kinds[] = $this->schemaKind($variant);
                }
            }

            $kinds = array_values(array_unique($kinds));

            return count($kinds) === 1 ? $kinds[0] : 'mixed';
        }

        $type = $schema['type'] ?? 'mixed';
        $types = is_array($type) ? array_values(array_filter($type, static fn (mixed $item): bool => $item !== 'null')) : [(string) $type];

        return match ($types[0] ?? 'mixed') {
            'integer' => 'int',
            'number' => 'float',
            'boolean' => 'bool',
            'array', 'object' => 'array',
            'string' => 'string',
            default => 'mixed',
        };
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function schemaNullable(array $schema): bool
    {
        $type = $schema['type'] ?? null;

        if (is_array($type) && in_array('null', $type, true)) {
            return true;
        }

        foreach (['anyOf', 'oneOf'] as $key) {
            foreach (is_array($schema[$key] ?? null) ? $schema[$key] : [] as $variant) {
                if (is_array($variant) && ($variant['type'] ?? null) === 'null') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array{class:string, operation_id:string, method:string, path:string, fields:list<array<string, mixed>>} $request
     */
    private function requestClass(array $request, ?string $existing = null): string
    {
        $manualBody = $this->extractRegion(
            $existing,
            self::MANUAL_BODY_START,
            self::MANUAL_BODY_END,
            self::DEFAULT_MANUAL_BODY,
        );
        $manualImports = $this->extractRegion(
            $existing,
            self::MANUAL_IMPORTS_START,
            self::MANUAL_IMPORTS_END,
            self::DEFAULT_MANUAL_IMPORTS,
        );
        $manualBodyStart = self::MANUAL_BODY_START;
        $manualBodyEnd = self::MANUAL_BODY_END;

        $constructor = [];
        $fromArray = [];
        $toArray = [];

        foreach ($request['fields'] as $field) {
            $constructor[] = '        public readonly ' . $this->phpType($field) . ' $' . $field['name'] . $this->defaultValue($field) . ',';
            $fromArray[] = '            ' . $field['name'] . ': ' . $this->fromArrayExpression($field) . ',';
            $toArray[] = "            '" . $field['key'] . "' => " . $this->toArrayExpression($field) . ',';
        }

        return $this->phpFile(
            'TheOneDigi\\TourSdk\\Generated\\Request',
            [
                'TheOneDigi\\TourSdk\\Common\\BuildsPayload',
                'TheOneDigi\\TourSdk\\Common\\RequestPayload',
            ],
            <<<PHP
/**
 * Generated from OpenAPI operation {$request['operation_id']} ({$request['method']} {$request['path']}).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class {$request['class']} implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
{$this->indentLines($constructor)}
        /**
         * Payload keys fromArray() actually saw, so an explicit null survives
         * toArray(). Empty when the request is built with named arguments.
         *
         * @var list<string>
         */
        protected readonly array \$providedKeys = [],
    ) {
        \$this->validateManual();
    }

    /**
     * @param array<string, mixed> \$payload
     */
    public static function fromArray(array \$payload): static
    {
        \$payload = static::normalizeManual(\$payload);

        return new static(
{$this->indentLines($fromArray)}
            providedKeys: array_keys(\$payload),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return \$this->withoutNulls([
{$this->indentLines($toArray)}
        ]);
    }

    {$manualBodyStart}{$manualBody}{$manualBodyEnd}
}
PHP,
            $manualImports,
        );
    }

    /**
     * @param array{class:string, schema:string, fields:list<array<string, mixed>>, stub:bool} $resource
     */
    private function resourceClass(array $resource, ?string $existing = null): string
    {
        $manualFields = $this->extractRegion(
            $existing,
            self::MANUAL_FIELDS_START,
            self::MANUAL_FIELDS_END,
            self::DEFAULT_MANUAL_FIELDS,
        );
        $manualHydration = $this->extractRegion(
            $existing,
            self::MANUAL_HYDRATION_START,
            self::MANUAL_HYDRATION_END,
            self::DEFAULT_MANUAL_HYDRATION,
        );
        $manualNames = $this->manualPropertyNames($manualFields);

        $properties = [];
        $assignments = [];

        foreach ($resource['fields'] as $field) {
            if (array_key_exists($field['name'], $manualNames)) {
                continue;
            }

            foreach ($this->propertyDoc($field) as $line) {
                $properties[] = $line;
            }

            $properties[] = '    public readonly ' . $this->phpType($field) . ' $' . $field['name'] . ';';
            $assignments[] = $this->resourceAssignment($field);
        }

        $stubNote = $resource['stub']
            ? "\n *\n * The contract describes this schema with no properties, so AUTO FIELDS is\n * empty. Declare what it really returns in the MANUAL regions below."
            : '';

        $manualImports = $this->extractRegion(
            $existing,
            self::MANUAL_IMPORTS_START,
            self::MANUAL_IMPORTS_END,
            self::DEFAULT_MANUAL_IMPORTS,
        );

        // Constants do not interpolate into a heredoc.
        $manualImportsStart = self::MANUAL_IMPORTS_START;
        $manualImportsEnd = self::MANUAL_IMPORTS_END;
        $manualFieldsStart = self::MANUAL_FIELDS_START;
        $manualFieldsEnd = self::MANUAL_FIELDS_END;
        $manualHydrationStart = self::MANUAL_HYDRATION_START;
        $manualHydrationEnd = self::MANUAL_HYDRATION_END;

        // Non-final: src/Resource keeps deprecated subclasses of these so existing
        // consumers keep their type hints while the fields come from the contract.
        return $this->phpFile(
            'TheOneDigi\\TourSdk\\Generated\\Resource',
            ['TheOneDigi\\TourSdk\\Common\\ArrayBackedResource'],
            <<<PHP
/**
 * Generated from OpenAPI schema {$resource['schema']}.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.{$stubNote}
 */
class {$resource['class']} extends ArrayBackedResource
{
    {$this->markerBlock(self::AUTO_FIELDS_START, $properties, self::AUTO_FIELDS_END)}

    {$manualFieldsStart}{$manualFields}{$manualFieldsEnd}

    /**
     * @param array<string, mixed> \$attributes
     */
    public function __construct(array \$attributes)
    {
        parent::__construct(\$attributes);

        {$this->markerBlock(self::AUTO_HYDRATION_START, $assignments, self::AUTO_HYDRATION_END, 2)}

        \$this->hydrateManual();
    }

    {$manualHydrationStart}{$manualHydration}{$manualHydrationEnd}
}
PHP,
            $manualImports,
        );
    }

    /**
     * @param list<string> $lines
     */
    private function markerBlock(string $start, array $lines, string $end, int $indentLevel = 1): string
    {
        $indent = str_repeat('    ', $indentLevel);

        if ($lines === []) {
            return $start . "\n" . $indent . $end;
        }

        return $start . "\n" . $this->indentLines($lines) . "\n" . $indent . $end;
    }

    /**
     * @param array<string, mixed> $field
     * @return list<string>
     */
    private function propertyDoc(array $field): array
    {
        if ($field['kind'] === 'resource_list') {
            return ['    /** @var list<' . $field['resource'] . '> */'];
        }

        if ($field['kind'] === 'array') {
            return ['    /** @var array<string, mixed>|list<mixed>' . ($field['nullable'] ? '|null' : '') . ' */'];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $field
     */
    private function phpType(array $field): string
    {
        if ($field['kind'] === 'mixed') {
            return 'mixed';
        }

        if ($field['kind'] === 'resource_list' || $field['kind'] === 'request_list') {
            // A missing list is an empty list, never null: callers foreach over it.
            return 'array';
        }

        if ($field['kind'] === 'resource' || $field['kind'] === 'request') {
            return '?' . $field['resource'];
        }

        return ($field['nullable'] ? '?' : '') . $field['kind'];
    }

    /**
     * @param array<string, mixed> $field
     */
    private function defaultValue(array $field): string
    {
        return $field['required'] ? '' : ' = null';
    }

    /**
     * @param array<string, mixed> $field
     */
    private function fromArrayExpression(array $field): string
    {
        $key = var_export($field['key'], true);

        return match ($field['kind']) {
            'request' => $field['required']
                ? "(is_array(\$payload[{$key}] ?? null) ? {$field['resource']}::fromArray(\$payload[{$key}]) : {$field['resource']}::fromArray([]))"
                : "(array_key_exists({$key}, \$payload) && is_array(\$payload[{$key}]) ? {$field['resource']}::fromArray(\$payload[{$key}]) : null)",
            'request_list' => $field['required']
                ? "self::payloadListFromArray((isset(\$payload[{$key}]) && is_array(\$payload[{$key}]) ? \$payload[{$key}] : []), {$field['resource']}::class)"
                : "(array_key_exists({$key}, \$payload) ? self::payloadListFromArray(\$payload[{$key}], {$field['resource']}::class) : null)",
            'string' => $field['required']
                ? "(string) (\$payload[{$key}] ?? '')"
                : "(array_key_exists({$key}, \$payload) && \$payload[{$key}] !== null ? (string) \$payload[{$key}] : null)",
            'int' => $field['required']
                ? "(int) (\$payload[{$key}] ?? 0)"
                : "(array_key_exists({$key}, \$payload) && \$payload[{$key}] !== null ? (int) \$payload[{$key}] : null)",
            'float' => $field['required']
                ? "(float) (\$payload[{$key}] ?? 0)"
                : "(array_key_exists({$key}, \$payload) && \$payload[{$key}] !== null ? (float) \$payload[{$key}] : null)",
            'bool' => $field['required']
                ? "(bool) (\$payload[{$key}] ?? false)"
                : "(array_key_exists({$key}, \$payload) && \$payload[{$key}] !== null ? (bool) \$payload[{$key}] : null)",
            'array' => $field['required']
                ? "(isset(\$payload[{$key}]) && is_array(\$payload[{$key}]) ? \$payload[{$key}] : [])"
                : "(array_key_exists({$key}, \$payload) && is_array(\$payload[{$key}]) ? \$payload[{$key}] : null)",
            default => "\$payload[{$key}] ?? null",
        };
    }

    /**
     * @param array<string, mixed> $field
     */
    private function toArrayExpression(array $field): string
    {
        if ($field['kind'] === 'request_list') {
            return "\$this->payloadListToArray(\$this->{$field['name']}, {$field['resource']}::class)";
        }

        if ($field['kind'] === 'request') {
            return "\$this->{$field['name']}?->toArray()";
        }

        return "\$this->{$field['name']}";
    }

    /**
     * @param array<string, mixed> $field
     */
    private function resourceAssignment(array $field): string
    {
        $key = var_export($field['key'], true);
        $name = $field['name'];

        if ($field['kind'] === 'resource_list') {
            return "        \$this->{$name} = self::resourceList(\$this->array({$key}), {$field['resource']}::class);";
        }

        if ($field['kind'] === 'resource') {
            return "        \$this->{$name} = is_array(\$this->get({$key})) ? {$field['resource']}::fromArray(\$this->get({$key})) : null;";
        }

        if ($field['nullable']) {
            return match ($field['kind']) {
                'string' => "        \$this->{$name} = \$this->nullableString({$key});",
                'int' => "        \$this->{$name} = \$this->nullableInt({$key});",
                'float' => "        \$this->{$name} = is_numeric(\$this->get({$key})) ? (float) \$this->get({$key}) : null;",
                'bool' => "        \$this->{$name} = \$this->get({$key}) === null ? null : (bool) \$this->get({$key});",
                'array' => "        \$this->{$name} = is_array(\$this->get({$key})) ? \$this->get({$key}) : null;",
                default => "        \$this->{$name} = \$this->get({$key});",
            };
        }

        return match ($field['kind']) {
            'string' => "        \$this->{$name} = \$this->string({$key});",
            'int' => "        \$this->{$name} = \$this->int({$key});",
            'float' => "        \$this->{$name} = \$this->float({$key});",
            'bool' => "        \$this->{$name} = \$this->bool({$key});",
            'array' => "        \$this->{$name} = \$this->array({$key});",
            default => "        \$this->{$name} = \$this->get({$key});",
        };
    }

    /**
     * @param list<string> $lines
     */
    private function indentLines(array $lines): string
    {
        return implode("\n", $lines);
    }

    /**
     * @param list<string> $uses
     */
    private function phpFile(string $namespace, array $uses, string $body, ?string $manualImports = null): string
    {
        sort($uses);
        $useBlock = implode("\n", array_map(static fn (string $use): string => "use {$use};", $uses));

        if ($manualImports !== null) {
            $useBlock .= "\n" . self::MANUAL_IMPORTS_START . $manualImports . self::MANUAL_IMPORTS_END;
        }

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

{$useBlock}

{$body}

PHP;
    }

    private function operationId(string $method, string $path): string
    {
        return strtolower($method) . str_replace(' ', '', ucwords(str_replace(['/', '{', '}', '-'], ' ', $path)));
    }

    private function className(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?: 'Dto';
        $value = str_replace(' ', '', ucwords($value));

        return preg_match('/^[A-Za-z]/', $value) ? $value : 'Dto' . $value;
    }

    private function propertyName(string $key): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $key) ?: [$key];
        $name = array_shift($words) ?: 'value';
        $name = strtolower(substr($name, 0, 1)) . substr($name, 1);

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            $name .= ucfirst($word);
        }

        $name = preg_replace('/[^A-Za-z0-9_]/', '', $name) ?: 'value';

        return preg_match('/^[A-Za-z_]/', $name) ? $name : 'value' . $name;
    }

    private function writeFile(string $path, string $contents): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $contents);
    }
}
