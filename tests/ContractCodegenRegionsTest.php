<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Guards the generator's marked regions.
 *
 * The whole point of MANUAL regions is that travelo-api's contract is incomplete
 * in ways only a human knows about — the input_* money snapshot, day_slots. If a
 * regeneration quietly ate them the SDK would go back to reporting 0.0 for money
 * it cannot see, so "the region survived" is worth a test of its own.
 */
final class ContractCodegenRegionsTest extends TestCase
{
    private string $outDir;

    protected function setUp(): void
    {
        $this->outDir = sys_get_temp_dir() . '/tour-sdk-regions-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (! is_dir($this->outDir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->outDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->outDir);
    }

    private function generate(): string
    {
        $output = [];
        $exitCode = 0;

        exec(sprintf(
            '%s %s --out=%s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(dirname(__DIR__) . '/tools/generate-contract-dtos.php'),
            escapeshellarg($this->outDir),
        ), $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));

        return implode("\n", $output);
    }

    private function path(string $class): string
    {
        return $this->outDir . '/Resource/' . $class . '.php';
    }

    public function test_manual_regions_survive_regeneration(): void
    {
        $this->generate();

        $file = $this->path('TourPriceResource');
        $source = (string) file_get_contents($file);

        file_put_contents($file, str_replace(
            "/* BEGIN MANUAL FIELDS */\n    /* END MANUAL FIELDS */",
            "/* BEGIN MANUAL FIELDS */\n    public readonly ?string \$handWritten;\n    /* END MANUAL FIELDS */",
            $source,
        ));

        $this->generate();

        self::assertStringContainsString(
            'public readonly ?string $handWritten;',
            (string) file_get_contents($file),
            'A regeneration wiped a MANUAL FIELDS region.',
        );
    }

    public function test_a_manual_field_replaces_the_auto_field_of_the_same_name(): void
    {
        $this->generate();

        $file = $this->path('TourPriceResource');
        self::assertStringContainsString('public readonly float $adultPrice;', (string) file_get_contents($file));

        // Claim adultPrice by hand: the auto declaration must step aside, because
        // PHP fatals on a redeclared readonly property.
        file_put_contents($file, str_replace(
            "/* BEGIN MANUAL FIELDS */\n    /* END MANUAL FIELDS */",
            "/* BEGIN MANUAL FIELDS */\n    public readonly string \$adultPrice;\n    /* END MANUAL FIELDS */",
            (string) file_get_contents($file),
        ));

        $this->generate();
        $regenerated = (string) file_get_contents($file);

        self::assertStringContainsString('public readonly string $adultPrice;', $regenerated);
        self::assertStringNotContainsString('public readonly float $adultPrice;', $regenerated);
        self::assertSame(
            1,
            substr_count($regenerated, '$adultPrice;'),
            'adultPrice must be declared exactly once.',
        );
    }

    public function test_a_schema_the_contract_cannot_describe_is_still_emitted_as_a_stub(): void
    {
        // Driven by a purpose-built spec rather than a real one. This used to assert
        // against TourCalendarDetailResource, which travelo-api described as a bare
        // `array` — then travelo-api was fixed and the test failed for the best
        // possible reason. A guard for "what happens when the contract says nothing"
        // must not depend on the contract still being broken somewhere.
        $spec = sys_get_temp_dir() . '/tour-sdk-spec-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($spec, json_encode([
            'openapi' => '3.1.0',
            'paths' => [],
            'components' => ['schemas' => [
                'UndescribedResource' => ['type' => 'array', 'items' => new \stdClass()],
            ]],
        ]));

        $output = [];
        $exitCode = 0;
        exec(sprintf(
            '%s %s --spec=%s --out=%s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(dirname(__DIR__) . '/tools/generate-contract-dtos.php'),
            escapeshellarg($spec),
            escapeshellarg($this->outDir),
        ), $output, $exitCode);
        unlink($spec);

        self::assertSame(0, $exitCode, implode("\n", $output));
        self::assertFileExists(
            $this->path('UndescribedResource'),
            'A schema with no properties must still produce a class: $refs to it have to resolve, and its MANUAL region is the only place its real shape can be recorded.',
        );
        self::assertStringContainsString('UndescribedResource has no object properties', implode("\n", $output));
    }

    public function test_a_ref_array_becomes_a_typed_resource_list(): void
    {
        $this->generate();

        self::assertStringContainsString(
            'self::resourceList($this->array(\'prices\'), TourCalendarPriceResource::class)',
            (string) file_get_contents($this->path('TourCalendarResource')),
            'An array of $ref must hydrate into typed resources, not a bare array.',
        );
    }

    public function test_an_operation_envelope_data_ref_generates_auto_fields(): void
    {
        $this->generate();

        $source = (string) file_get_contents($this->path('TourCalendarDateResource'));

        self::assertStringContainsString('public readonly int $calendarId;', $source);
        self::assertStringContainsString('public readonly bool $isAvailable;', $source);

        // A $ref field becomes a nullable resource, hydrated through get() and an
        // is_array() check rather than array(). array() would return [] for an
        // absent price and hand the caller a fully-empty TourCalendarPriceResource
        // — truthy, so `if ($availability->price)` would pass on a price that is
        // not there. The null has to survive.
        self::assertStringContainsString('public readonly ?TourCalendarPriceResource $price;', $source);
        self::assertMatchesRegularExpression(
            '/\$this->price = is_array\(\$this->get\(\'price\'\)\)\s*\?\s*TourCalendarPriceResource::fromArray\(\$this->get\(\'price\'\)\)\s*:\s*null;/',
            $source,
        );
    }
}
