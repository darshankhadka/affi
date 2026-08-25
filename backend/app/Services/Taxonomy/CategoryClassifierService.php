<?php

namespace App\Services\Taxonomy;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class CategoryClassifierService
{
    public const TAXONOMY_VERSION = 'v2.0';

    /**
     * Complete standard taxonomy definitions.
     */
    public const TAXONOMY_DEFINITIONS = [
        // COMPUTING & LAPTOPS
        ['slug' => 'laptops', 'name' => 'Laptops', 'icon' => 'laptop', 'display_order' => 1],
        ['slug' => 'gaming-laptops', 'name' => 'Gaming Laptops', 'icon' => 'gamepad-2', 'display_order' => 2],
        ['slug' => 'macbooks', 'name' => 'MacBooks', 'icon' => 'apple', 'display_order' => 3],
        ['slug' => 'desktops-mini-pcs', 'name' => 'Desktops & Mini PCs', 'icon' => 'server', 'display_order' => 4],
        ['slug' => 'laptop-bags-cases', 'name' => 'Laptop Bags & Cases', 'icon' => 'briefcase', 'display_order' => 5],
        ['slug' => 'laptop-accessories', 'name' => 'Laptop Accessories', 'icon' => 'hard-drive', 'display_order' => 6],

        // MOBILE & TABLETS
        ['slug' => 'smartphones', 'name' => 'Smartphones', 'icon' => 'smartphone', 'display_order' => 7],
        ['slug' => 'tablets-ipads', 'name' => 'Tablets & iPads', 'icon' => 'tablet', 'display_order' => 8],
        ['slug' => 'smartwatches', 'name' => 'Smartwatches', 'icon' => 'watch', 'display_order' => 9],

        // HARDWARE & COMPONENTS
        ['slug' => 'gpus-graphics-cards', 'name' => 'GPUs & Graphics Cards', 'icon' => 'cpu', 'display_order' => 10],
        ['slug' => 'cpus-processors', 'name' => 'CPUs & Processors', 'icon' => 'microchip', 'display_order' => 11],
        ['slug' => 'ram-memory', 'name' => 'RAM & Memory', 'icon' => 'memory-stick', 'display_order' => 12],
        ['slug' => 'ssds-storage', 'name' => 'SSDs & Storage', 'icon' => 'database', 'display_order' => 13],
        ['slug' => 'motherboards', 'name' => 'Motherboards', 'icon' => 'circuit-board', 'display_order' => 14],
        ['slug' => 'power-supplies-cases', 'name' => 'Power Supplies & Cases', 'icon' => 'box', 'display_order' => 15],

        // MONITORS, TVS & PROJECTORS
        ['slug' => 'gaming-monitors', 'name' => 'Gaming Monitors', 'icon' => 'monitor', 'display_order' => 16],
        ['slug' => '4k-oled-tvs', 'name' => '4K & OLED TVs', 'icon' => 'tv', 'display_order' => 17],
        ['slug' => 'projectors-screens', 'name' => 'Projectors & Screens', 'icon' => 'projector', 'display_order' => 18],

        // PERIPHERALS & NETWORKING
        ['slug' => 'mechanical-keyboards', 'name' => 'Mechanical Keyboards', 'icon' => 'keyboard', 'display_order' => 19],
        ['slug' => 'gaming-mice', 'name' => 'Gaming Mice', 'icon' => 'mouse', 'display_order' => 20],
        ['slug' => 'headphones-audio', 'name' => 'Headphones & Audio', 'icon' => 'headphones', 'display_order' => 21],
        ['slug' => 'routers-mesh-wifi', 'name' => 'Routers & Mesh WiFi', 'icon' => 'wifi', 'display_order' => 22],
        ['slug' => 'cables-docks', 'name' => 'Cables & Docks', 'icon' => 'cable', 'display_order' => 23],

        // SPECIALTY TECH & MAKER
        ['slug' => '3d-printers-engravers', 'name' => '3D Printers & Engravers', 'icon' => 'printer', 'display_order' => 24],
        ['slug' => 'gaming-consoles-handhelds', 'name' => 'Gaming & Consoles', 'icon' => 'joystick', 'display_order' => 25],
        ['slug' => 'drones-rc', 'name' => 'Drones & RC Vehicles', 'icon' => 'navigation', 'display_order' => 26],
        ['slug' => 'ebikes-scooters', 'name' => 'E-Bikes & Electric Scooters', 'icon' => 'bike', 'display_order' => 27],
        ['slug' => 'power-stations-solar', 'name' => 'Power Stations & Solar', 'icon' => 'sun', 'display_order' => 28],
        ['slug' => 'cameras-optics', 'name' => 'Cameras & Optics', 'icon' => 'camera', 'display_order' => 29],

        // EXPANDED CATEGORIES FOR MERCHANTS
        ['slug' => 'tyres-automotive', 'name' => 'Tyres & Automotive', 'icon' => 'car', 'display_order' => 30],
        ['slug' => 'smart-home-cleaning', 'name' => 'Smart Home & Cleaning', 'icon' => 'sparkles', 'display_order' => 31],
        ['slug' => 'personal-care-beauty', 'name' => 'Personal Care & Beauty', 'icon' => 'heart', 'display_order' => 32],
        ['slug' => 'merch-promotional', 'name' => 'Promotional & Office Supplies', 'icon' => 'tag', 'display_order' => 33],
        ['slug' => 'consumer-electronics', 'name' => 'Consumer Electronics', 'icon' => 'plug', 'display_order' => 34],
    ];

    /**
     * Ensure all taxonomy categories exist in the database.
     */
    public function ensureTaxonomy(): void
    {
        foreach (self::TAXONOMY_DEFINITIONS as $def) {
            Category::updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'icon' => $def['icon'],
                    'display_order' => $def['display_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Classify a product or raw feed item deterministically.
     *
     * @param array{
     *   name: string,
     *   description?: ?string,
     *   merchant_category?: ?string,
     *   brand_name?: ?string,
     *   model_number?: ?string,
     * } $data
     * @return array{
     *   category_id: int,
     *   category_slug: string,
     *   category_name: string,
     *   confidence: float,
     *   source: string,
     *   merchant_category: ?string,
     * }
     */
    public function classify(array $data): array
    {
        $this->ensureTaxonomy();

        $name = strtolower($data['name'] ?? '');
        $desc = strtolower($data['description'] ?? '');
        $merchantCat = strtolower($data['merchant_category'] ?? '');
        $brand = strtolower($data['brand_name'] ?? '');
        $combined = trim("{$name} {$brand} {$merchantCat}");

        // 1. TYRES & AUTOMOTIVE (DK Tyre Merchants mcdaekonline, etc.)
        if (
            preg_match('/\b(tl\s+\d+|zr1[0-9]|zr2[0-9]|baghjul|forhjul|m\/c|dæk|daek|diablo rosso|roadtec|sportmax|conticlassicattack|contitwist|snowtex|mfe99|c9273|k58|k81|g525|m7305|cobra chrome|scooter dæk|motorcykeldæk|sommerdæk|vinterdæk|helårsdæk)\b/i', $combined)
            || preg_match('/\b(pirelli|metzeler|dunlop|heidenau|maxxis|bridgestone|continental|michelin|avon|mitas|kenda)\b/i', $brand) && preg_match('/\b(\d{2,3}\/\d{2,3}|\d+\.\d+-\d+|baghjul|forhjul|tl|tt)\b/i', $name)
            || preg_match('/\b(digital clamp meter|multimeter|clamp meter|automotive tool)\b/i', $combined)
        ) {
            return $this->result('tyres-automotive', 0.98, 'automotive_rule', $data['merchant_category'] ?? null);
        }

        // 2. PERSONAL CARE & BEAUTY (Bazta DK products: Dove, Nivea, L'Oreal, Labello, Perfumes, Soaps, Shampoos)
        if (
            preg_match('/\b(eau de parfum|eau de toilette|bodylotion|body lotion|shampoo|conditioner|shower gel|læbepomade|lip balm|læbepleje|deodorant|anti-perspirant|roll-on|body wash|shave cream|shaving cream|barberskum|skincare|hårpleje|ansigtspleje|sæbe|soap slice|soap bar|håndcreme|hand cream|badesalt|hårspray|tandpasta|parfume|body mist|hudpleje|badesæbe)\b/i', $combined)
            || preg_match('/\b(dove|nivea|l\'oréal|loreal|labello|garnier|faith in nature|armaf|bomb cosmetics|curalene|erasmic|sanex|palmolive|colgate|biotherm|clinique|axe|old spice|hugo boss|calvin klein)\b/i', $brand)
            || preg_match('/\b(ml|cl|stk|g|oz)\b/i', $name) && preg_match('/\b(parfum|cream|lotion|spray|shampoo|balm|deodorant|soap|sæbe|læbe)\b/i', $name)
        ) {
            return $this->result('personal-care-beauty', 0.98, 'personal_care_rule', $data['merchant_category'] ?? null);
        }

        // 3. SMART HOME & HOUSEHOLD CLEANING (Bazta DK cleaning, Proscenic vacuum parts)
        if (
            preg_match('/\b(mop cloth|robot vacuum|vacuum cleaner|cleaning sponge|magic eraser|toiletblok|opvask|rengøring|rengøringsmiddel|toilet rens|afkalker|vaskemiddel|skuresvamp|skuresvampe|støvsugerpose|støvsuger|gulvmoppe|klude|domestos|cillit bang|vileda|ajax|swiffer|finish|vanish|ariel|comfort|duck|bref)\b/i', $combined)
            || preg_match('/\b(vileda|domestos|proscenic|cillit bang|ajax)\b/i', $brand) && preg_match('/\b(sponge|mop|cleaner|cloth|svamp|rengøring|toilet)\b/i', $name)
        ) {
            return $this->result('smart-home-cleaning', 0.98, 'cleaning_rule', $data['merchant_category'] ?? null);
        }

        // 4. PROMOTIONAL & MERCH PRODUCTS (Custom mousepads, pens, webcam covers, giveaways)
        if (
            preg_match('/\b(promotional|customized|custom promotional|dia\.\s*x|thick round mousepad|rectangle classic mousepad|origin\'l fabric|heavy duty base|custom juga|hail storm|icamcover|security webcam cover|branded giveaway|promo pen)\b/i', $name)
        ) {
            return $this->result('merch-promotional', 0.95, 'promotional_rule', $data['merchant_category'] ?? null);
        }

        // 5. 3D PRINTERS, LASER ENGRAVERS & HEAT PRESSES (Creality, SCULPFUN, Mecpow, AlgoLaser, Ortur)
        if (
            preg_match('/\b(laser engraver|laser engraving|engraving machine|3d printer|3d-printer|smoke purifier|laser bed|rotary roller|heat press|laser module|laser cutter|air assist|lightburn|creality ender|sculpfun|mecpow|algolaser|ortur|twotrees|longer)\b/i', $combined)
        ) {
            return $this->result('3d-printers-engravers', 0.98, 'maker_engraver_rule', $data['merchant_category'] ?? null);
        }

        // 6. PROJECTORS & PROJECTOR SCREENS (NothingProjector, Formovie, AWOL Vision, ALR screens)
        if (
            preg_match('/\b(projector screen|alr motorized|alr\/clr|floor rising screen|ultra short throw|laser projector|4k projector|home theater projector|ust projector|870 iso lumens|2600 lumens|formovie|fengmi|awol vision|nothingprojector|jmgo|xgimi|dangbei)\b/i', $combined)
        ) {
            return $this->result('projectors-screens', 0.98, 'projector_screen_rule', $data['merchant_category'] ?? null);
        }

        // 7. DRONES & RC VEHICLES (Wltoys, Mini Drones, Quadcopters)
        if (
            preg_match('/\b(drone|quadcopter|fpv|obstacle avoidance|rc car|rc truck|wltoys|brushless rc|rc boat|rc vehicle)\b/i', $combined)
        ) {
            return $this->result('drones-rc', 0.95, 'drone_rc_rule', $data['merchant_category'] ?? null);
        }

        // 8. E-BIKES & ELECTRIC SCOOTERS (PVY, iScooter, CRNK Helmets, Cycling Gear)
        if (
            preg_match('/\b(electric scooter|e-scooter|escooter|electric bike|ebike|e-bike|folding electric scooter|cycling backpack|windscreen helmet|bike helmet|cycling helmet|bike front bag|crnk|iscooter|pvy|kukirin|eleglide|duotts|engwe)\b/i', $combined)
        ) {
            return $this->result('ebikes-scooters', 0.95, 'ebike_scooter_rule', $data['merchant_category'] ?? null);
        }

        // 9. POWER STATIONS & SOLAR (Flashfish, Bluetti, EcoFlow, Solar Panels)
        if (
            preg_match('/\b(power station|solar panel|portable power|solar energy kit|foldable solar|flashfish|bluetti|ecoflow|jackery|anker solix|oukitel power)\b/i', $combined)
        ) {
            return $this->result('power-stations-solar', 0.95, 'solar_power_rule', $data['merchant_category'] ?? null);
        }

        // 10. CAMERAS & OPTICS (BlazeVideo Trail Cameras, Dash Cams, Action Cams)
        if (
            preg_match('/\b(trail camera|wildlife camera|game camera|night vision camera|hunting camera|blazevideo|dash cam|dashcam|action camera|binoculars|monocular|telescope|security camera)\b/i', $combined)
        ) {
            return $this->result('cameras-optics', 0.95, 'camera_optics_rule', $data['merchant_category'] ?? null);
        }

        // 11. GAMING CONSOLES & RETRO HANDHELDS (Anbernic, Powkiddy, Miyoo, SJGAM)
        if (
            preg_match('/\b(handheld game console|retro game console|retro console|arcade games|emulators|anbernic|powkiddy|miyoo|sjgam|rg351|rg353|rg556|retroid pocket|gamesir|game controller|gamepad)\b/i', $combined)
        ) {
            return $this->result('gaming-consoles-handhelds', 0.95, 'gaming_console_rule', $data['merchant_category'] ?? null);
        }

        // 12. LAPTOP BAGS, SLEEVES & CASES (Strict negative check so they NEVER classify as Laptops!)
        if (
            preg_match('/\b(laptop sleeve|laptop bag|laptop backpack|laptop briefcase|messenger bag|shoulder bag|briefcase|backpack|laptop case|carry case|sleeves|sleeve case|polyester laptop)\b/i', $name)
        ) {
            return $this->result('laptop-bags-cases', 0.98, 'laptop_bag_rule', $data['merchant_category'] ?? null);
        }

        // 13. LAPTOP ACCESSORIES (Coolers, Stands, Adapters, Webcam covers)
        if (
            preg_match('/\b(laptop stand|cooling pad|laptop cooler|laptop fan|webcam cover|privacy screen|laptop skin|keyboard cover|stylus pen|laptop charger|laptop battery)\b/i', $name)
        ) {
            return $this->result('laptop-accessories', 0.95, 'laptop_acc_rule', $data['merchant_category'] ?? null);
        }

        // 14. MACBOOKS (Apple MacBooks)
        if (
            preg_match('/\b(macbook pro|macbook air|apple macbook)\b/i', $name)
        ) {
            return $this->result('macbooks', 0.98, 'macbook_rule', $data['merchant_category'] ?? null);
        }

        // 15. GAMING LAPTOPS
        if (
            preg_match('/\b(gaming laptop|nbook turbo|alienware|rog strix|tuf gaming|predator helios|legion pro|omen 16)\b/i', $name)
        ) {
            return $this->result('gaming-laptops', 0.95, 'gaming_laptop_rule', $data['merchant_category'] ?? null);
        }

        // 16. GENUINE LAPTOPS & NOTEBOOKS
        if (
            preg_match('/\b(laptop|notebook|ultrabook|thinkpad|ideapad|yoga|zenbook|vivobook|gram|matebook|acebook|nbook|ninkear n1|ninkear a1|ninkear s1|n-one nbook|blackview acebook|dynabook|latitude|precision|inspiron|vostro|elitebook|probook)\b/i', $name)
            && !preg_match('/\b(bag|sleeve|case|backpack|briefcase|stand|cooler|pad|cover|skin|cable|adapter|battery|screen protector|cloth|toy)\b/i', $name)
        ) {
            return $this->result('laptops', 0.95, 'laptop_rule', $data['merchant_category'] ?? null);
        }

        // 17. DESKTOPS & MINI PCS
        if (
            preg_match('/\b(mini pc|desktop pc|all-in-one pc|aio pc|nuc|barebone|minisforum|beelink|chatreey|gmktec|geekom|tower pc)\b/i', $combined)
        ) {
            return $this->result('desktops-mini-pcs', 0.95, 'desktop_minipc_rule', $data['merchant_category'] ?? null);
        }

        // 18. SMARTPHONES & MOBILE PHONES
        if (
            preg_match('/\b(smartphone|smart phone|iphone|galaxy s2|galaxy a|xiaomi 1|redmi note|poco|oneplus|pixel 8|pixel 9|motorola edge|honor magic)\b/i', $name)
            && !preg_match('/\b(case|cover|holder|stand|cable|screen protector|glass)\b/i', $name)
        ) {
            return $this->result('smartphones', 0.95, 'smartphone_rule', $data['merchant_category'] ?? null);
        }

        // 19. TABLETS & IPADS
        if (
            preg_match('/\b(tablet|ipad pro|ipad air|galaxy tab|xiaomi pad|lenovo tab|redmi pad|teclast|alldocube|chuwi)\b/i', $name)
            && !preg_match('/\b(case|cover|screen protector|holder)\b/i', $name)
        ) {
            return $this->result('tablets-ipads', 0.95, 'tablet_rule', $data['merchant_category'] ?? null);
        }

        // 20. SMARTWATCHES & WEARABLES
        if (
            preg_match('/\b(smartwatch|smart watch|smart band|fitness tracker|apple watch|galaxy watch|garmin|fitbit|amazfit)\b/i', $combined)
            && !preg_match('/\b(strap|band replacement|charger)\b/i', $name)
        ) {
            return $this->result('smartwatches', 0.95, 'smartwatch_rule', $data['merchant_category'] ?? null);
        }

        // 21. GAMING MONITORS
        if (
            preg_match('/\b(gaming monitor|curved monitor|144hz|165hz|240hz|360hz|ultrawide monitor|ips monitor|qhd monitor|4k monitor)\b/i', $combined)
            && !preg_match('/\b(stand|arm|mount|cable)\b/i', $name)
        ) {
            return $this->result('gaming-monitors', 0.95, 'monitor_rule', $data['merchant_category'] ?? null);
        }

        // 22. 4K & OLED TVS
        if (
            preg_match('/\b(4k tv|oled tv|qled tv|smart tv|bravia|television|amFramebuffer|hisense tv|tcl tv|lg c3|lg g3|s90c)\b/i', $combined)
        ) {
            return $this->result('4k-oled-tvs', 0.95, 'tv_rule', $data['merchant_category'] ?? null);
        }

        // 23. HEADPHONES, EARBUDS & AUDIO
        if (
            preg_match('/\b(headphones|headset|earbuds|earphones|bluetooth speaker|portable speaker|soundbar|audio jack|anc headphones|tronsmart|soundcore|edifier|jbl|sony wh|sony wf|bose|sennheiser)\b/i', $combined)
        ) {
            return $this->result('headphones-audio', 0.95, 'audio_rule', $data['merchant_category'] ?? null);
        }

        // 24. MECHANICAL KEYBOARDS
        if (
            preg_match('/\b(mechanical keyboard|gaming keyboard|rgb keyboard|keycaps|hot swappable keyboard|red switch|blue switch|brown switch|keychron|akko|epomaker|redragon|royal kludge)\b/i', $combined)
        ) {
            return $this->result('mechanical-keyboards', 0.95, 'keyboard_rule', $data['merchant_category'] ?? null);
        }

        // 25. GAMING MICE & MOUSEPADS
        if (
            preg_match('/\b(gaming mouse|optical mouse|wireless mouse|mousepad|mouse pad|desk mat|razer deathadder|logitech g|pulsar mouse)\b/i', $combined)
        ) {
            return $this->result('gaming-mice', 0.95, 'mouse_rule', $data['merchant_category'] ?? null);
        }

        // 26. CABLES, ADAPTERS & DOCKS
        if (
            preg_match('/\b(usb cable|hdmi cable|displayport cable|usb-c cable|usba to usbc|usb hub|docking station|usb adapter|ethernet cable|ugreen|anker cable|baseus cable)\b/i', $combined)
        ) {
            return $this->result('cables-docks', 0.95, 'cables_docks_rule', $data['merchant_category'] ?? null);
        }

        // 27. ROUTERS & MESH WIFI
        if (
            preg_match('/\b(router|mesh wifi|wifi 6 router|wifi 7 router|wireless router|access point|range extender|repeater|gigabit router)\b/i', $combined)
        ) {
            return $this->result('routers-mesh-wifi', 0.95, 'router_rule', $data['merchant_category'] ?? null);
        }

        // 28. PC COMPONENTS (GPUs, CPUs, RAM, SSDs, Motherboards, Power Supplies)
        if (preg_match('/\b(rtx 40|rtx 30|rx 7|rx 6|geforce rtx|graphics card|gpu)\b/i', $combined)) {
            return $this->result('gpus-graphics-cards', 0.95, 'gpu_rule', $data['merchant_category'] ?? null);
        }
        if (preg_match('/\b(intel core i\d|ryzen \d \d|processor|cpu)\b/i', $combined) && !preg_match('/\b(laptop|notebook|pc)\b/i', $name)) {
            return $this->result('cpus-processors', 0.90, 'cpu_rule', $data['merchant_category'] ?? null);
        }
        if (preg_match('/\b(ddr4|ddr5|sodimm|ram 16gb|ram 32gb|desktop memory|ram memory)\b/i', $combined) && !preg_match('/\b(laptop|notebook|pc)\b/i', $name)) {
            return $this->result('ram-memory', 0.90, 'ram_rule', $data['merchant_category'] ?? null);
        }
        if (preg_match('/\b(nvme ssd|pcie ssd|m\.2 ssd|portable ssd|external ssd|hard drive|storage ssd)\b/i', $combined) && !preg_match('/\b(laptop|notebook|pc)\b/i', $name)) {
            return $this->result('ssds-storage', 0.90, 'ssd_rule', $data['merchant_category'] ?? null);
        }
        if (preg_match('/\b(motherboard|mainboard|b650|z790|am5|lga1700)\b/i', $combined) && !preg_match('/\b(laptop|notebook|pc)\b/i', $name)) {
            return $this->result('motherboards', 0.90, 'motherboard_rule', $data['merchant_category'] ?? null);
        }
        if (preg_match('/\b(power supply|psu 850w|psu 750w|pc case|atx case|computer chassis)\b/i', $combined) && !preg_match('/\b(laptop|notebook|pc)\b/i', $name)) {
            return $this->result('power-supplies-cases', 0.90, 'psu_case_rule', $data['merchant_category'] ?? null);
        }

        // 29. TOYS & GADGETS
        if (preg_match('/\b(toy|cocomelon|cat toy|hundelegetøj|legetøj|tegnebog|spil|bamse|puzzle)\b/i', $name)) {
            return $this->result('consumer-electronics', 0.70, 'toy_gadget_rule', $data['merchant_category'] ?? null);
        }

        // SAFE GENERAL FALLBACK
        return $this->result('consumer-electronics', 0.50, 'general_fallback', $data['merchant_category'] ?? null);
    }

    /**
     * Format output result with category ID lookup.
     */
    protected function result(string $slug, float $confidence, string $source, ?string $merchantCategory): array
    {
        $cat = Category::where('slug', $slug)->first();
        if (!$cat) {
            $cat = Category::create([
                'name' => Str::title(str_replace('-', ' ', $slug)),
                'slug' => $slug,
                'is_active' => true,
                'display_order' => 99,
            ]);
        }

        return [
            'category_id' => $cat->id,
            'category_slug' => $cat->slug,
            'category_name' => $cat->name,
            'confidence' => $confidence,
            'source' => $source,
            'merchant_category' => $merchantCategory,
            'taxonomy_version' => self::TAXONOMY_VERSION,
        ];
    }
}
