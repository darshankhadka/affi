<?php

namespace App\Services\Taxonomy;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class CategoryClassifierService
{
    public const TAXONOMY_VERSION = 'v3.0-strict20';

    /**
     * The EXACT 20 approved canonical categories.
     * No more. No less.
     */
    public const TAXONOMY_DEFINITIONS = [
        // COMPUTERS & SYSTEMS
        [
            'slug' => 'laptops',
            'name' => 'Laptops',
            'icon' => 'laptop',
            'display_order' => 1,
            'group' => 'Computers',
            'seo_title' => 'Compare Laptops & Ultrabooks | Best Prices at ARIKARTECH',
            'seo_description' => 'Find the best laptop deals across trusted tech retailers. Compare lightweight ultrabooks, business laptops, and creator notebooks.',
        ],
        [
            'slug' => 'gaming-laptops',
            'name' => 'Gaming Laptops',
            'icon' => 'gamepad-2',
            'display_order' => 2,
            'group' => 'Computers',
            'seo_title' => 'Best Gaming Laptop Deals & Price Comparison | ARIKARTECH',
            'seo_description' => 'Compare high-performance gaming laptops with RTX graphics, high-refresh displays, and advanced cooling.',
        ],
        [
            'slug' => 'macbooks',
            'name' => 'MacBooks',
            'icon' => 'apple',
            'display_order' => 3,
            'group' => 'Computers',
            'seo_title' => 'Compare Apple MacBook Pro & MacBook Air Prices | ARIKARTECH',
            'seo_description' => 'Find live prices and discounts on Apple MacBook Air and MacBook Pro models powered by Apple Silicon.',
        ],
        [
            'slug' => 'desktops-mini-pcs',
            'name' => 'Desktops & Mini PCs',
            'icon' => 'server',
            'display_order' => 4,
            'group' => 'Computers',
            'seo_title' => 'Mini PCs & Desktop Computers Price Comparison | ARIKARTECH',
            'seo_description' => 'Compare compact mini PCs, all-in-one desktops, and workstations from top tech brands.',
        ],

        // CORE COMPONENTS
        [
            'slug' => 'gpus-graphics-cards',
            'name' => 'GPUs & Graphics Cards',
            'icon' => 'cpu',
            'display_order' => 5,
            'group' => 'Components',
            'seo_title' => 'Compare Graphics Cards (GPUs) & Real-Time Prices | ARIKARTECH',
            'seo_description' => 'Track GPU prices and compare NVIDIA GeForce RTX and AMD Radeon graphics cards across verified sellers.',
        ],
        [
            'slug' => 'cpus-processors',
            'name' => 'CPUs & Processors',
            'icon' => 'microchip',
            'display_order' => 6,
            'group' => 'Components',
            'seo_title' => 'Compare Desktop CPUs & Processors | ARIKARTECH',
            'seo_description' => 'Compare Intel Core and AMD Ryzen desktop processor prices and live stock availability.',
        ],
        [
            'slug' => 'ram-memory',
            'name' => 'RAM & Memory',
            'icon' => 'memory-stick',
            'display_order' => 7,
            'group' => 'Components',
            'seo_title' => 'Compare DDR4 & DDR5 RAM Kits | ARIKARTECH',
            'seo_description' => 'Find the lowest prices on high-speed DDR5 and DDR4 desktop and laptop memory kits.',
        ],
        [
            'slug' => 'ssds-storage',
            'name' => 'SSDs & Storage',
            'icon' => 'database',
            'display_order' => 8,
            'group' => 'Components',
            'seo_title' => 'Compare NVMe SSDs & External Storage | ARIKARTECH',
            'seo_description' => 'Compare PCIe 4.0 and PCIe 5.0 NVMe SSDs, portable hard drives, and high-speed storage.',
        ],
        [
            'slug' => 'motherboards',
            'name' => 'Motherboards',
            'icon' => 'circuit-board',
            'display_order' => 9,
            'group' => 'Components',
            'seo_title' => 'Compare Motherboards for AMD & Intel | ARIKARTECH',
            'seo_description' => 'Compare ATX, Micro-ATX, and Mini-ITX motherboards supporting Intel LGA1700 and AMD AM5 sockets.',
        ],
        [
            'slug' => 'power-supplies-cases',
            'name' => 'Power Supplies & Cases',
            'icon' => 'box',
            'display_order' => 10,
            'group' => 'Components',
            'seo_title' => 'Compare PC Power Supplies & Cases | ARIKARTECH',
            'seo_description' => 'Compare ATX 3.0 power supplies, modular PSUs, and PC cases with optimized airflow.',
        ],

        // MOBILE & WEARABLES
        [
            'slug' => 'smartphones',
            'name' => 'Smartphones',
            'icon' => 'smartphone',
            'display_order' => 11,
            'group' => 'Mobile',
            'seo_title' => 'Compare Smartphone Prices & Deals | ARIKARTECH',
            'seo_description' => 'Compare flagships, mid-range phones, and rugged Android smartphones across trusted retailers.',
        ],
        [
            'slug' => 'tablets-ipads',
            'name' => 'Tablets & iPads',
            'icon' => 'tablet',
            'display_order' => 12,
            'group' => 'Mobile',
            'seo_title' => 'Compare Tablets & Apple iPads | ARIKARTECH',
            'seo_description' => 'Find the best prices on Apple iPads, Android tablets, and drawing tablets.',
        ],
        [
            'slug' => 'smartwatches',
            'name' => 'Smartwatches',
            'icon' => 'watch',
            'display_order' => 13,
            'group' => 'Mobile',
            'seo_title' => 'Compare Smartwatches & Fitness Trackers | ARIKARTECH',
            'seo_description' => 'Compare Apple Watch, Galaxy Watch, Garmin, and Android smartwatches.',
        ],

        // DISPLAYS & ENTERTAINMENT
        [
            'slug' => 'gaming-monitors',
            'name' => 'Gaming Monitors',
            'icon' => 'monitor',
            'display_order' => 14,
            'group' => 'Displays & Entertainment',
            'seo_title' => 'Compare Gaming Monitors (144Hz, 240Hz, OLED) | ARIKARTECH',
            'seo_description' => 'Compare high-refresh gaming monitors, 4K displays, and curved ultrawide screens.',
        ],
        [
            'slug' => '4k-oled-tvs',
            'name' => '4K & OLED TVs',
            'icon' => 'tv',
            'display_order' => 15,
            'group' => 'Displays & Entertainment',
            'seo_title' => 'Compare 4K OLED & Smart TVs | ARIKARTECH',
            'seo_description' => 'Find the best prices on 4K OLED, QLED, and smart home televisions.',
        ],

        // GAMING & PERIPHERALS
        [
            'slug' => 'mechanical-keyboards',
            'name' => 'Mechanical Keyboards',
            'icon' => 'keyboard',
            'display_order' => 16,
            'group' => 'Gaming & Peripherals',
            'seo_title' => 'Compare Mechanical Keyboards | ARIKARTECH',
            'seo_description' => 'Compare hot-swappable, wireless, and RGB mechanical keyboards from Keychron, Ajazz, Akko, and more.',
        ],
        [
            'slug' => 'gaming-mice',
            'name' => 'Gaming Mice',
            'icon' => 'mouse',
            'display_order' => 17,
            'group' => 'Gaming & Peripherals',
            'seo_title' => 'Compare Lightweight & Wireless Gaming Mice | ARIKARTECH',
            'seo_description' => 'Compare optical sensors, wireless latency, and ergonomic gaming mice across top stores.',
        ],
        [
            'slug' => 'headphones-audio',
            'name' => 'Headphones & Audio',
            'icon' => 'headphones',
            'display_order' => 18,
            'group' => 'Gaming & Peripherals',
            'seo_title' => 'Compare ANC Headphones & Bluetooth Speakers | ARIKARTECH',
            'seo_description' => 'Compare noise-cancelling wireless headphones, gaming headsets, and portable Bluetooth speakers.',
        ],
        [
            'slug' => 'routers-mesh-wifi',
            'name' => 'Routers & Mesh WiFi',
            'icon' => 'wifi',
            'display_order' => 19,
            'group' => 'Gaming & Peripherals',
            'seo_title' => 'Compare WiFi 6 & Mesh Routers | ARIKARTECH',
            'seo_description' => 'Compare high-speed WiFi 6 and WiFi 7 routers, mesh home systems, and gigabit switches.',
        ],
        [
            'slug' => 'cables-docks',
            'name' => 'Cables & Docks',
            'icon' => 'cable',
            'display_order' => 20,
            'group' => 'Gaming & Peripherals',
            'seo_title' => 'Compare Thunderbolt Docks & USB-C Cables | ARIKARTECH',
            'seo_description' => 'Compare multi-port USB-C docking stations, Thunderbolt 4 docks, and HDMI 2.1 cables.',
        ],
    ];

    /**
     * Ensure the 20 approved categories exist and are active.
     * Deactivate all other categories.
     */
    public function ensureTaxonomy(): void
    {
        $approvedSlugs = array_column(self::TAXONOMY_DEFINITIONS, 'slug');

        // Deactivate non-approved categories in database
        Category::whereNotIn('slug', $approvedSlugs)->update(['is_active' => false]);

        // Upsert approved 20 categories
        foreach (self::TAXONOMY_DEFINITIONS as $def) {
            Category::updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'icon' => $def['icon'],
                    'display_order' => $def['display_order'],
                    'is_active' => true,
                    'description' => $def['seo_description'] ?? null,
                ]
            );
        }
    }

    /**
     * Classify a product deterministically into one of the 20 approved categories.
     * Returns category_id = null and is_excluded = true for all non-matching products.
     *
     * @param array{
     *   name: string,
     *   description?: ?string,
     *   merchant_category?: ?string,
     *   brand_name?: ?string,
     *   model_number?: ?string,
     * } $data
     * @return array{
     *   category_id: ?int,
     *   category_slug: ?string,
     *   category_name: ?string,
     *   confidence: float,
     *   source: string,
     *   merchant_category: ?string,
     *   taxonomy_version: string,
     *   is_excluded: bool,
     * }
     */
    public function classify(array $data): array
    {
        $name = strtolower($data['name'] ?? '');
        $desc = strtolower($data['description'] ?? '');
        $merchantCat = strtolower($data['merchant_category'] ?? '');
        $brand = strtolower($data['brand_name'] ?? '');
        $combined = trim("{$name} {$brand} {$merchantCat} {$desc}");

        // =========================================================================
        // STRICT NEGATIVE EXCLUSIONS (Products that do NOT belong to the 20 categories)
        // =========================================================================

        // 1. Tyres & Automotive
        if (
            preg_match('/\b(tl\s+\d+|zr1[0-9]|zr2[0-9]|baghjul|forhjul|m\/c|dæk|daek|diablo rosso|roadtec|sportmax|conticlassicattack|contitwist|snowtex|mfe99|c9273|k58|k81|g525|m7305|cobra chrome|scooter dæk|motorcykeldæk|sommerdæk|vinterdæk|helårsdæk)\b/i', $combined)
            || (preg_match('/\b(pirelli|metzeler|dunlop|heidenau|maxxis|bridgestone|continental|michelin|avon|mitas|kenda)\b/i', $brand) && preg_match('/\b(\d{2,3}\/\d{2,3}|\d+\.\d+-\d+|baghjul|forhjul|tl|tt)\b/i', $name))
            || preg_match('/\b(automotive tool|clamp meter|multimeter|car charger|diagnostic scanner)\b/i', $combined)
        ) {
            return $this->excludedResult('automotive_exclusion', $data['merchant_category'] ?? null);
        }

        // 2. Personal Care & Beauty & Toiletries
        if (
            preg_match('/\b(eau de parfum|eau de toilette|bodylotion|body lotion|shampoo|conditioner|shower gel|læbepomade|lip balm|læbepleje|deodorant|anti-perspirant|roll-on|body wash|shave cream|shaving cream|barberskum|skincare|hårpleje|ansigtspleje|sæbe|soap slice|soap bar|håndcreme|hand cream|badesalt|hårspray|tandpasta|parfume|body mist|hudpleje|badesæbe)\b/i', $combined)
            || preg_match('/\b(dove|nivea|l\'oréal|loreal|labello|garnier|faith in nature|armaf|bomb cosmetics|curalene|erasmic|sanex|palmolive|colgate|biotherm|clinique|axe|old spice|hugo boss|calvin klein)\b/i', $brand)
            || (preg_match('/\b(ml|cl|stk|g|oz)\b/i', $name) && preg_match('/\b(parfum|cream|lotion|spray|shampoo|balm|deodorant|soap|sæbe|læbe)\b/i', $name))
        ) {
            return $this->excludedResult('personal_care_exclusion', $data['merchant_category'] ?? null);
        }

        // 3. Smart Home, Cleaning, Kitchen & Household Appliances
        if (
            preg_match('/\b(mop cloth|robot vacuum|vacuum cleaner|cleaning sponge|magic eraser|toiletblok|opvask|rengøring|rengøringsmiddel|toilet rens|afkalker|vaskemiddel|skuresvamp|skuresvampe|støvsugerpose|støvsuger|gulvmoppe|klude|domestos|cillit bang|vileda|ajax|swiffer|finish|vanish|ariel|comfort|duck|bref|espressokande|moka pot|coffee maker|air fryer|blender|toaster|cookware|knife set|pillow|mattress)\b/i', $combined)
            || (preg_match('/\b(vileda|domestos|proscenic|cillit bang|ajax|jimmy)\b/i', $brand) && preg_match('/\b(sponge|mop|cleaner|cloth|svamp|rengøring|toilet|vacuum)\b/i', $name))
        ) {
            return $this->excludedResult('cleaning_household_exclusion', $data['merchant_category'] ?? null);
        }

        // 4. Promotional, Merchandise, Pens, Office Supplies
        if (
            preg_match('/\b(promotional|customized|custom promotional|dia\.\s*x|thick round mousepad|rectangle classic mousepad|origin\'l fabric|heavy duty base|custom juga|hail storm|icamcover|security webcam cover|branded giveaway|promo pen|ballpoint pen|corporate gift)\b/i', $name)
        ) {
            return $this->excludedResult('merchandise_exclusion', $data['merchant_category'] ?? null);
        }

        // 5. 3D Printers, Laser Engravers, Heat Presses, Smokers
        if (
            preg_match('/\b(laser engraver|laser engraving|engraving machine|3d printer|3d-printer|smoke purifier|laser bed|rotary roller|heat press|laser module|laser cutter|air assist|lightburn|creality ender|sculpfun|mecpow|algolaser|ortur|twotrees|longer|atomstack)\b/i', $combined)
        ) {
            return $this->excludedResult('maker_3d_engraver_exclusion', $data['merchant_category'] ?? null);
        }

        // 6. Projectors & Projector Screens
        if (
            preg_match('/\b(projector screen|alr motorized|alr\/clr|floor rising screen|ultra short throw|laser projector|4k projector|home theater projector|ust projector|870 iso lumens|2600 lumens|formovie|fengmi|awol vision|nothingprojector|jmgo|xgimi|dangbei)\b/i', $combined)
        ) {
            return $this->excludedResult('projector_exclusion', $data['merchant_category'] ?? null);
        }

        // 7. Drones, RC Toys & Vehicles
        if (
            preg_match('/\b(drone|quadcopter|fpv|obstacle avoidance|rc car|rc truck|wltoys|brushless rc|rc boat|rc vehicle|toy vehicle)\b/i', $combined)
        ) {
            return $this->excludedResult('drone_rc_exclusion', $data['merchant_category'] ?? null);
        }

        // 8. E-Bikes, Electric Scooters & Cycling Gear
        if (
            preg_match('/\b(electric scooter|e-scooter|escooter|electric bike|ebike|e-bike|folding electric scooter|cycling backpack|windscreen helmet|bike helmet|cycling helmet|bike front bag|crnk|iscooter|pvy|kukirin|eleglide|duotts|engwe)\b/i', $combined)
        ) {
            return $this->excludedResult('ebike_scooter_exclusion', $data['merchant_category'] ?? null);
        }

        // 9. Power Stations & Solar Panels
        if (
            preg_match('/\b(power station|solar panel|portable power|solar energy kit|foldable solar|flashfish|bluetti|ecoflow|jackery|anker solix|oukitel power)\b/i', $combined)
        ) {
            return $this->excludedResult('solar_power_exclusion', $data['merchant_category'] ?? null);
        }

        // 10. Trail Cameras, Wildlife, Security Optics & Binoculars
        if (
            preg_match('/\b(trail camera|wildlife camera|game camera|night vision camera|hunting camera|blazevideo|dash cam|dashcam|action camera|binoculars|monocular|telescope|security camera|cctv)\b/i', $combined)
        ) {
            return $this->excludedResult('optics_camera_exclusion', $data['merchant_category'] ?? null);
        }

        // 11. Bags, Sleeves, Backpacks & Carrying Cases
        if (
            preg_match('/\b(laptop sleeve|laptop bag|laptop backpack|laptop briefcase|messenger bag|shoulder bag|briefcase|backpack|laptop case|carry case|sleeves|sleeve case|polyester laptop|protective case for gpd|waterproof bag|travel pouch)\b/i', $name)
        ) {
            return $this->excludedResult('bags_cases_exclusion', $data['merchant_category'] ?? null);
        }

        // 12. Retro Handheld Emulators & Toy Consoles
        if (
            preg_match('/\b(handheld game console|retro game console|retro console|arcade games|emulators|anbernic|powkiddy|miyoo|sjgam|rg351|rg353|rg556|retroid pocket|retro handheld)\b/i', $combined)
        ) {
            return $this->excludedResult('retro_console_exclusion', $data['merchant_category'] ?? null);
        }

        // 13. Generic accessories (Laptop stands, cooling pads, webcam covers, generic mounts)
        if (
            preg_match('/\b(laptop stand|cooling pad|laptop cooler|laptop fan|webcam cover|privacy screen|laptop skin|keyboard cover|stylus pen|screen protector|glass protector|cleaning wipe)\b/i', $name)
        ) {
            return $this->excludedResult('generic_accessory_exclusion', $data['merchant_category'] ?? null);
        }

        // =========================================================================
        // POSITIVE MATCHING — EXACT 20 APPROVED TECH CATEGORIES
        // =========================================================================

        // 1. MACBOOKS
        if (
            preg_match('/\b(macbook pro|macbook air|apple macbook)\b/i', $name)
        ) {
            return $this->result('macbooks', 0.98, 'macbook_rule', $data['merchant_category'] ?? null);
        }

        // 2. GAMING LAPTOPS
        if (
            preg_match('/\b(gaming laptop|nbook turbo|alienware|rog strix|tuf gaming|predator helios|legion pro|omen 16)\b/i', $name)
        ) {
            return $this->result('gaming-laptops', 0.95, 'gaming_laptop_rule', $data['merchant_category'] ?? null);
        }

        // 3. LAPTOPS (General & Business Laptops)
        if (
            preg_match('/\b(laptop|notebook|ultrabook|thinkpad|ideapad|yoga|zenbook|vivobook|gram|matebook|acebook|nbook|ninkear n1|ninkear a1|ninkear s1|ninkear n15|ninkear n14|ninkear n16|ninkear a15|ninkear a16|n-one nbook|blackview acebook|dynabook|latitude|precision|inspiron|vostro|elitebook|probook)\b/i', $name)
            && !preg_match('/\b(bag|sleeve|case|backpack|briefcase|stand|cooler|pad|cover|skin|cable|adapter|battery|screen protector|cloth|toy)\b/i', $name)
        ) {
            return $this->result('laptops', 0.95, 'laptop_rule', $data['merchant_category'] ?? null);
        }

        // 4. DESKTOPS & MINI PCS
        if (
            preg_match('/\b(mini pc|desktop pc|all-in-one pc|aio pc|nuc|barebone|minisforum|beelink|chatreey|gmktec|geekom|tower pc|optiplex|thinkcentre)\b/i', $combined)
            && !preg_match('/\b(bracket|mount|cable)\b/i', $name)
        ) {
            return $this->result('desktops-mini-pcs', 0.95, 'desktop_minipc_rule', $data['merchant_category'] ?? null);
        }

        // 5. GPUS & GRAPHICS CARDS
        if (
            preg_match('/\b(rtx 4090|rtx 4080|rtx 4070|rtx 4060|rtx 5080|rtx 5090|rtx 3060|rtx 3070|rtx 3080|radeon rx|geforce rtx|graphics card|gpu|video card|vga card|sapphire nitro|xfx speedster|powercolor)\b/i', $combined)
            && !preg_match('/\b(holder|bracket|stand|cable|case)\b/i', $name)
        ) {
            return $this->result('gpus-graphics-cards', 0.98, 'gpu_rule', $data['merchant_category'] ?? null);
        }

        // 6. CPUS & PROCESSORS
        if (
            preg_match('/\b(core i9|core i7|core i5|core i3|ryzen 9|ryzen 7|ryzen 5|ryzen 3|intel ultra 7|intel ultra 9|cpu processor|desktop processor|lga1700|am5 cpu)\b/i', $combined)
            && !preg_match('/\b(laptop|notebook|cooler|fan|motherboard)\b/i', $name)
        ) {
            return $this->result('cpus-processors', 0.98, 'cpu_rule', $data['merchant_category'] ?? null);
        }

        // 7. RAM & MEMORY
        if (
            preg_match('/\b(ddr5|ddr4|ram memory|memory kit|desktop ram|laptop ram|so-dimm|dimm|cl30|cl36|cl40|vengeance rgb|trident z|fury beast|corsair vengeance|g\.skill|ddr5 16gb|ddr5 32gb|ddr4 16gb|ddr4 32gb)\b/i', $combined)
            && !preg_match('/\b(ssd|storage|hard drive|laptop|phone|tablet)\b/i', $name)
        ) {
            return $this->result('ram-memory', 0.95, 'ram_rule', $data['merchant_category'] ?? null);
        }

        // 8. SSDS & STORAGE
        if (
            preg_match('/\b(nvme|pcie 4\.0 ssd|pcie 5\.0 ssd|m\.2 2280|samsung 990|samsung 980|crucial t700|crucial p3|wd black sn|solid state drive|portable ssd|external ssd|internal ssd|sata ssd|hard drive|external hdd|seagate barracuda|wd blue|ssd 1tb|ssd 2tb|ssd 512gb|kingston nv2|lexar nm|sandisk portable)\b/i', $combined)
            && !preg_match('/\b(enclosure|caddy|case|cable)\b/i', $name)
        ) {
            return $this->result('ssds-storage', 0.95, 'ssd_storage_rule', $data['merchant_category'] ?? null);
        }

        // 9. MOTHERBOARDS
        if (
            preg_match('/\b(motherboard|mainboard|b650|x670|z790|b760|x870|z890|am5 motherboard|lga1700 motherboard|asus rog strix z|msi mag b|gigabyte aorus)\b/i', $combined)
            && !preg_match('/\b(laptop|desktop|cpu|cooler)\b/i', $name)
        ) {
            return $this->result('motherboards', 0.95, 'motherboard_rule', $data['merchant_category'] ?? null);
        }

        // 10. POWER SUPPLIES & PC CASES
        if (
            preg_match('/\b(power supply|psu 850w|psu 750w|psu 1000w|atx 3\.0|80 plus gold|80 plus platinum|modular psu|pc case|computer chassis|mid tower case|liquid cooler|aio cooler|cpu cooler|noctua nh)\b/i', $combined)
        ) {
            return $this->result('power-supplies-cases', 0.95, 'psu_case_rule', $data['merchant_category'] ?? null);
        }

        // 11. SMARTPHONES
        if (
            preg_match('/\b(smartphone|smart phone|iphone 1|iphone 15|iphone 16|galaxy s2|galaxy a|xiaomi 1|redmi note|poco|oneplus|pixel 8|pixel 9|motorola edge|honor magic|fossibot f105|rugged smartphone)\b/i', $name)
            && !preg_match('/\b(case|cover|holder|stand|cable|screen protector|glass)\b/i', $name)
        ) {
            return $this->result('smartphones', 0.95, 'smartphone_rule', $data['merchant_category'] ?? null);
        }

        // 12. TABLETS & IPADS
        if (
            preg_match('/\b(tablet|ipad pro|ipad air|ipad mini|galaxy tab|xiaomi pad|lenovo tab|redmi pad|teclast|alldocube|chuwi pad|android tablet)\b/i', $name)
            && !preg_match('/\b(case|cover|screen protector|holder|stand)\b/i', $name)
        ) {
            return $this->result('tablets-ipads', 0.95, 'tablet_rule', $data['merchant_category'] ?? null);
        }

        // 13. SMARTWATCHES
        if (
            preg_match('/\b(smartwatch|smart watch|apple watch|galaxy watch|garmin fenix|amazfit|fitness tracker|lokmat|appllp|ticwatch)\b/i', $combined)
            && !preg_match('/\b(strap|band|charger|protector|case)\b/i', $name)
        ) {
            return $this->result('smartwatches', 0.95, 'smartwatch_rule', $data['merchant_category'] ?? null);
        }

        // 14. GAMING MONITORS
        if (
            preg_match('/\b(gaming monitor|144hz monitor|165hz monitor|240hz monitor|oled gaming monitor|ultrawide monitor|curved monitor|ips monitor|qhd monitor|4k monitor|titan army|ktc monitor|innocn|27 inch monitor|32 inch monitor|34 inch monitor)\b/i', $combined)
            && !preg_match('/\b(arm|mount|stand|light|cable|desk)\b/i', $name)
        ) {
            return $this->result('gaming-monitors', 0.95, 'gaming_monitor_rule', $data['merchant_category'] ?? null);
        }

        // 15. 4K & OLED TVS
        if (
            preg_match('/\b(oled tv|4k tv|qled tv|smart tv|55 inch tv|65 inch tv|75 inch tv|lg oled|samsung tv|tcl tv|hisense tv)\b/i', $combined)
            && !preg_match('/\b(mount|bracket|remote|cable|box)\b/i', $name)
        ) {
            return $this->result('4k-oled-tvs', 0.95, 'tv_rule', $data['merchant_category'] ?? null);
        }

        // 16. MECHANICAL KEYBOARDS
        if (
            preg_match('/\b(mechanical keyboard|custom keyboard|gaming keyboard|hot-swappable keyboard|ajazz|keychron|akko|rk royal kludge|epomaker|nuphy|aula f|gasket mount keyboard)\b/i', $combined)
            && !preg_match('/\b(mousepad|keycaps|switch puller|cable)\b/i', $name)
        ) {
            return $this->result('mechanical-keyboards', 0.95, 'keyboard_rule', $data['merchant_category'] ?? null);
        }

        // 17. GAMING MICE
        if (
            preg_match('/\b(gaming mouse|wireless mouse|optical mouse|lightweight mouse|logitech g pro|razer viper|attack shark|darmoshark|zaopin|scyrox|vxe r1|paw3395)\b/i', $combined)
            && !preg_match('/\b(pad|mousepad|mat|grip|skates|cable)\b/i', $name)
        ) {
            return $this->result('gaming-mice', 0.95, 'gaming_mouse_rule', $data['merchant_category'] ?? null);
        }

        // 18. HEADPHONES & AUDIO
        if (
            preg_match('/\b(headphones|earbuds|gaming headset|tronsmart|bluetooth speaker|soundbar|anc earbuds|noise cancelling|sennheiser|sony wh-|bose|airpods|anker soundcore)\b/i', $combined)
            && !preg_match('/\b(stand|cable|case|earpads)\b/i', $name)
        ) {
            return $this->result('headphones-audio', 0.95, 'headphones_audio_rule', $data['merchant_category'] ?? null);
        }

        // 19. ROUTERS & MESH WIFI
        if (
            preg_match('/\b(wifi 6 router|wifi 7 router|mesh wifi|gaming router|tp-link|asus rt-|netgear orbi|unifi|gl\.inet|gl-mt|gl-ax|gigabit router|ethernet switch)\b/i', $combined)
        ) {
            return $this->result('routers-mesh-wifi', 0.95, 'router_wifi_rule', $data['merchant_category'] ?? null);
        }

        // 20. CABLES & DOCKS
        if (
            preg_match('/\b(thunderbolt dock|usb-c hub|docking station|usb-c dock|hdmi 2\.1 cable|thunderbolt 4 cable|displayport cable|usb-c cable 100w|usb-c cable 240w|multiport adapter|data transfer cable|male to male usb|ugreen usb 3\.0 data transfer cable)\b/i', $combined)
        ) {
            return $this->result('cables-docks', 0.95, 'cables_docks_rule', $data['merchant_category'] ?? null);
        }

        // Default: If no strict tech category match, EXCLUDE from public catalog
        return $this->excludedResult('no_strict_taxonomy_match', $data['merchant_category'] ?? null);
    }

    /**
     * Helper for matched approved category.
     */
    protected function result(string $slug, float $confidence, string $source, ?string $merchantCategory): array
    {
        $cat = Category::where('slug', $slug)->first();

        return [
            'category_id' => $cat?->id,
            'category_slug' => $slug,
            'category_name' => $cat?->name ?? Str::headline($slug),
            'confidence' => $confidence,
            'source' => $source,
            'merchant_category' => $merchantCategory,
            'taxonomy_version' => self::TAXONOMY_VERSION,
            'is_excluded' => false,
        ];
    }

    /**
     * Helper for excluded products.
     */
    protected function excludedResult(string $source, ?string $merchantCategory): array
    {
        return [
            'category_id' => null,
            'category_slug' => null,
            'category_name' => null,
            'confidence' => 0.0,
            'source' => $source,
            'merchant_category' => $merchantCategory,
            'taxonomy_version' => self::TAXONOMY_VERSION,
            'is_excluded' => true,
        ];
    }
}
