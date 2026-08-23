<?php

namespace Tests\Feature;

use App\Services\Affiliate\AffiliateRegistry;
use App\Services\Affiliate\AmazonProvider;
use App\Services\Affiliate\AwinProvider;
use App\Services\Affiliate\CjProvider;
use App\Services\Affiliate\ImpactProvider;
use InvalidArgumentException;
use Tests\TestCase;

class ProviderRegistryTest extends TestCase
{
    public function test_registry_contains_all_core_phase3_providers(): void
    {
        $registry = app(AffiliateRegistry::class);

        $this->assertTrue($registry->has('amazon'));
        $this->assertTrue($registry->has('awin'));
        $this->assertTrue($registry->has('cj'));
        $this->assertTrue($registry->has('impact'));

        $this->assertInstanceOf(AmazonProvider::class, $registry->get('amazon'));
        $this->assertInstanceOf(AwinProvider::class, $registry->get('awin'));
        $this->assertInstanceOf(CjProvider::class, $registry->get('cj'));
        $this->assertInstanceOf(ImpactProvider::class, $registry->get('impact'));
    }

    public function test_registry_throws_exception_for_unknown_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $registry = app(AffiliateRegistry::class);
        $registry->get('unknown_provider_network');
    }
}
