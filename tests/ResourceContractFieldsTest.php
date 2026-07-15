<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TheOneDigi\TourSdk\Generated\Resource\TourCalendarPriceResource;
use TheOneDigi\TourSdk\Generated\Resource\TourPriceResource;
use TheOneDigi\TourSdk\Generated\Resource\PartnerTourResource;

/**
 * Checks resource *fields* against the contract, which ContractCoverageTest does
 * not do — it matches paths and methods only.
 *
 * That gap is what let TourPriceResource carry cost_adult/cost_child/cost_infant
 * for as long as it did: the contract declares them on TourCalendarPriceResource,
 * not here, so on a tour's catalog prices they resolved to 0.0 and read as "this
 * tour costs us nothing" rather than "no data".
 */
final class ResourceContractFieldsTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function schema(string $name): array
    {
        $spec = json_decode(
            (string) file_get_contents(__DIR__ . '/fixtures/partner-api.openapi.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $schema = $spec['components']['schemas'][$name] ?? [];

        return $schema['anyOf'][0] ?? $schema;
    }

    /**
     * @return list<string>
     */
    private function declaredProperties(string $class): array
    {
        return array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass($class))->getProperties(\ReflectionProperty::IS_PUBLIC),
        );
    }

    private function propertyName(string $key): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $key) ?: [$key];
        $name = array_shift($words) ?: '';
        $name = strtolower(substr($name, 0, 1)) . substr($name, 1);

        foreach ($words as $word) {
            $name .= ucfirst($word);
        }

        return $name;
    }

    public function test_tour_prices_do_not_claim_a_cost_the_contract_never_sends(): void
    {
        $properties = $this->declaredProperties(TourPriceResource::class);

        foreach (['costAdult', 'costChild', 'costInfant'] as $phantom) {
            self::assertNotContains(
                $phantom,
                $properties,
                "TourPriceResource must not expose {$phantom}: the contract does not declare it, so it would silently report 0.0.",
            );
        }

        self::assertArrayNotHasKey('cost_adult', $this->schema('TourPriceResource')['properties'] ?? []);
    }

    public function test_the_deprecated_alias_inherits_the_same_absence(): void
    {
        // The alias is what consumers still type-hint, so the fix has to hold there.
        self::assertNotContains('costAdult', $this->declaredProperties(\TheOneDigi\TourSdk\Generated\Resource\TourPriceResource::class));
    }

    public function test_calendar_prices_do_expose_cost_because_the_contract_declares_it(): void
    {
        self::assertArrayHasKey('cost_adult', $this->schema('TourCalendarPriceResource')['properties'] ?? []);

        $price = TourCalendarPriceResource::fromArray(['cost_adult' => '20.14']);

        self::assertContains('costAdult', $this->declaredProperties(TourCalendarPriceResource::class));
        self::assertSame(20.14, $price->costAdult);
    }

    public function test_every_contract_field_on_partner_tour_resource_is_reachable(): void
    {
        $properties = $this->declaredProperties(PartnerTourResource::class);
        $missing = [];

        foreach (array_keys($this->schema('PartnerTourResource')['properties'] ?? []) as $key) {
            if (! in_array($this->propertyName((string) $key), $properties, true)) {
                $missing[] = $key;
            }
        }

        self::assertSame([], $missing, "Contract fields with no property:\n  " . implode("\n  ", $missing));
    }
}
