<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Market;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedInitialRolesAndMarketsCommand extends Command
{
    protected $signature = 'system:init-foundation {--admin-email=admin@arikartech.com : Default admin email} {--admin-password=ChangeMeProduction123! : Initial admin password}';
    protected $description = 'Initialize core production roles, permissions, currencies, markets, and categories without mock data';

    public function handle(): int
    {
        $this->info('Initializing ARIKARTECH production foundations...');

        // 1. Permissions
        $permissions = [
            'catalog.view', 'catalog.create', 'catalog.edit', 'catalog.delete',
            'offers.view', 'offers.manage',
            'affiliates.view', 'affiliates.manage',
            'analytics.view',
            'seo.view', 'seo.manage',
            'automation.view', 'automation.trigger',
            'settings.view', 'settings.manage',
            'users.view', 'users.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $this->info('Permissions registered.');

        // 2. Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $editorRole = Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);
        $analystRole = Role::firstOrCreate(['name' => 'Analyst', 'guard_name' => 'web']);

        $superAdminRole->syncPermissions(Permission::all());
        $adminRole->syncPermissions([
            'catalog.view', 'catalog.create', 'catalog.edit', 'catalog.delete',
            'offers.view', 'offers.manage',
            'affiliates.view', 'affiliates.manage',
            'analytics.view',
            'seo.view', 'seo.manage',
            'automation.view', 'automation.trigger',
            'settings.view',
        ]);
        $editorRole->syncPermissions([
            'catalog.view', 'catalog.create', 'catalog.edit',
            'offers.view',
            'seo.view', 'seo.manage',
        ]);
        $analystRole->syncPermissions([
            'catalog.view',
            'offers.view',
            'analytics.view',
            'automation.view',
        ]);
        $this->info('Roles and permissions configured.');

        // 3. Default Currencies
        $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'rate_to_usd' => 1.0, 'decimals' => 2, 'is_active' => true]);
        $gbp = Currency::firstOrCreate(['code' => 'GBP'], ['name' => 'British Pound', 'symbol' => '£', 'rate_to_usd' => 1.28, 'decimals' => 2, 'is_active' => true]);
        $eur = Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'rate_to_usd' => 1.09, 'decimals' => 2, 'is_active' => true]);
        $aud = Currency::firstOrCreate(['code' => 'AUD'], ['name' => 'Australian Dollar', 'symbol' => 'A$', 'rate_to_usd' => 0.66, 'decimals' => 2, 'is_active' => true]);
        $nzd = Currency::firstOrCreate(['code' => 'NZD'], ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'rate_to_usd' => 0.61, 'decimals' => 2, 'is_active' => true]);

        // 4. Default Target Markets (US, UK, DE, FR, ES, IT, NL, AU, NZ)
        $marketsData = [
            ['code' => 'us', 'name' => 'United States', 'default_currency_id' => $usd->id, 'locale' => 'en-US', 'hreflang' => 'en-us', 'is_active' => true, 'display_order' => 1],
            ['code' => 'uk', 'name' => 'United Kingdom', 'default_currency_id' => $gbp->id, 'locale' => 'en-GB', 'hreflang' => 'en-gb', 'is_active' => true, 'display_order' => 2],
            ['code' => 'de', 'name' => 'Germany', 'default_currency_id' => $eur->id, 'locale' => 'de-DE', 'hreflang' => 'de', 'is_active' => true, 'display_order' => 3],
            ['code' => 'fr', 'name' => 'France', 'default_currency_id' => $eur->id, 'locale' => 'fr-FR', 'hreflang' => 'fr', 'is_active' => true, 'display_order' => 4],
            ['code' => 'es', 'name' => 'Spain', 'default_currency_id' => $eur->id, 'locale' => 'es-ES', 'hreflang' => 'es', 'is_active' => true, 'display_order' => 5],
            ['code' => 'it', 'name' => 'Italy', 'default_currency_id' => $eur->id, 'locale' => 'it-IT', 'hreflang' => 'it', 'is_active' => true, 'display_order' => 6],
            ['code' => 'nl', 'name' => 'Netherlands', 'default_currency_id' => $eur->id, 'locale' => 'nl-NL', 'hreflang' => 'nl', 'is_active' => true, 'display_order' => 7],
            ['code' => 'au', 'name' => 'Australia', 'default_currency_id' => $aud->id, 'locale' => 'en-AU', 'hreflang' => 'en-au', 'is_active' => true, 'display_order' => 8],
            ['code' => 'nz', 'name' => 'New Zealand', 'default_currency_id' => $nzd->id, 'locale' => 'en-NZ', 'hreflang' => 'en-nz', 'is_active' => true, 'display_order' => 9],
        ];

        $iso3Map = [
            'us' => 'USA',
            'uk' => 'GBR',
            'de' => 'DEU',
            'fr' => 'FRA',
            'es' => 'ESP',
            'it' => 'ITA',
            'nl' => 'NLD',
            'au' => 'AUS',
            'nz' => 'NZL',
        ];

        foreach ($marketsData as $mData) {
            $m = Market::updateOrCreate(['code' => $mData['code']], $mData);
            // Link country
            $iso2 = strtoupper($mData['code'] === 'uk' ? 'GB' : $mData['code']);
            $iso3 = $iso3Map[$mData['code']] ?? strtoupper($mData['code']);
            Country::updateOrCreate(
                ['iso_code_2' => $iso2],
                [
                    'iso_code_3' => $iso3,
                    'name' => $mData['name'],
                    'currency_id' => $mData['default_currency_id'],
                    'market_id' => $m->id,
                ]
            );
        }
        $this->info('Currencies and markets created.');

        // 5. Initial Locked Tech Categories
        $categories = [
            ['name' => 'Laptops', 'icon' => 'laptop', 'display_order' => 1],
            ['name' => 'Gaming Laptops', 'icon' => 'laptop', 'display_order' => 2],
            ['name' => 'MacBooks', 'icon' => 'laptop', 'display_order' => 3],
            ['name' => 'Desktops & Mini PCs', 'icon' => 'monitor', 'display_order' => 4],
            ['name' => 'Smartphones', 'icon' => 'smartphone', 'display_order' => 5],
            ['name' => 'Tablets & iPads', 'icon' => 'tablet', 'display_order' => 6],
            ['name' => 'Smartwatches', 'icon' => 'watch', 'display_order' => 7],
            ['name' => 'GPUs & Graphics Cards', 'icon' => 'cpu', 'display_order' => 8],
            ['name' => 'CPUs & Processors', 'icon' => 'cpu', 'display_order' => 9],
            ['name' => 'RAM & Memory', 'icon' => 'server', 'display_order' => 10],
            ['name' => 'SSDs & Storage', 'icon' => 'hard-drive', 'display_order' => 11],
            ['name' => 'Motherboards', 'icon' => 'layers', 'display_order' => 12],
            ['name' => 'Power Supplies & Cases', 'icon' => 'box', 'display_order' => 13],
            ['name' => 'Gaming Monitors', 'icon' => 'monitor', 'display_order' => 14],
            ['name' => '4K & OLED TVs', 'icon' => 'tv', 'display_order' => 15],
            ['name' => 'Mechanical Keyboards', 'icon' => 'keyboard', 'display_order' => 16],
            ['name' => 'Gaming Mice', 'icon' => 'mouse', 'display_order' => 17],
            ['name' => 'Headphones & Audio', 'icon' => 'headphones', 'display_order' => 18],
            ['name' => 'Routers & Mesh WiFi', 'icon' => 'wifi', 'display_order' => 19],
            ['name' => 'Cables & Docks', 'icon' => 'cable', 'display_order' => 20],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'display_order' => $cat['display_order'],
                    'is_active' => true,
                ]
            );
        }
        $this->info('Categories initialized.');

        // 6. Affiliate Providers Foundation (Truthful status by default)
        $providers = [
            [
                'code' => 'awin',
                'name' => 'Awin Publisher Network',
                'type' => 'api',
                'is_active' => true,
                'status' => 'disconnected',
                'rate_limit_per_minute' => 120,
            ],
            [
                'code' => 'cj',
                'name' => 'CJ Affiliate (Commission Junction)',
                'type' => 'api',
                'is_active' => true,
                'status' => 'disconnected',
                'rate_limit_per_minute' => 60,
            ],
            [
                'code' => 'impact',
                'name' => 'Impact (Impact.com)',
                'type' => 'api',
                'is_active' => true,
                'status' => 'disconnected',
                'rate_limit_per_minute' => 90,
            ],
            [
                'code' => 'amazon',
                'name' => 'Amazon Associates & PA-API 5.0',
                'type' => 'api',
                'is_active' => false,
                'status' => 'deferred',
                'rate_limit_per_minute' => 60,
            ],
        ];

        foreach ($providers as $prov) {
            AffiliateProvider::updateOrCreate(['code' => $prov['code']], $prov);
        }
        $this->info('Affiliate providers initialized (Awin, CJ, Impact, Amazon).');

        // 7. Initial Super Admin Account
        $adminEmail = $this->option('admin-email');
        $adminPassword = $this->option('admin-password');

        $adminUser = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'ARIKARTECH Super Admin',
                'password' => Hash::make($adminPassword),
                'is_active' => true,
            ]
        );

        $adminUser->assignRole('Super Admin');
        $this->info("Super Admin user created: {$adminEmail}");

        $this->info('System foundation initialized successfully.');
        return Command::SUCCESS;
    }
}
