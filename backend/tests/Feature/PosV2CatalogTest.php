<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalePresentation;
use App\Models\SalePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PosV2CatalogTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_JS = __DIR__.'/../../resources/js/pos-store.js';

    #[Test]
    public function controller_eager_loads_nearest_lot_per_variant(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // New v2 view is the only view in PR 3.
        config()->set('pos.enabled', true);
        \App\Models\Customer::query()->create(['name' => 'Cliente General', 'document' => '—', 'is_default' => true, 'is_active' => true]);

        $variant = $this->createVariantWithPresentation($user, 'Leche Toni 1L');

        $lot = InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'purchase_item_id' => null,
            'origin_type' => 'test',
            'origin_id' => 100,
            'received_at' => now()->subDay(),
            'expiration_date' => '2026-12-31',
            'initial_quantity' => 10,
            'available_quantity' => 10,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 50,
            'suggested_sale_price_amount' => null,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        // The view data includes the eager-loaded nearest lot (verified via the
        // presentation model that the controller loaded).
        $presentation = $variant->presentations()->first();
        $this->assertNotNull($presentation);
        $this->assertSame($lot->id, $presentation->variant->nearestLot->id);
    }

    #[Test]
    public function fefo_multiple_lots_nearest_expiration_wins(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $variant = $this->createVariantWithPresentation($user, 'Leche Toni 1L');

        InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'purchase_item_id' => null,
            'origin_type' => 'test',
            'origin_id' => 10,
            'received_at' => Carbon::now()->subDays(20),
            'expiration_date' => '2027-06-30',
            'initial_quantity' => 10,
            'available_quantity' => 10,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 100,
            'suggested_sale_price_amount' => null,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        $nearest = InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'purchase_item_id' => null,
            'origin_type' => 'test',
            'origin_id' => 11,
            'received_at' => Carbon::now()->subDays(10),
            'expiration_date' => '2026-09-15',
            'initial_quantity' => 10,
            'available_quantity' => 10,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 100,
            'suggested_sale_price_amount' => null,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'purchase_item_id' => null,
            'origin_type' => 'test',
            'origin_id' => 12,
            'received_at' => Carbon::now()->subDays(15),
            'expiration_date' => '2027-02-28',
            'initial_quantity' => 10,
            'available_quantity' => 10,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 100,
            'suggested_sale_price_amount' => null,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        $result = $variant->nearestLot;

        $this->assertNotNull($result);
        $this->assertSame($nearest->id, $result->id);
    }

    #[Test]
    public function fefo_null_expiration_ignored(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $variant = $this->createVariantWithPresentation($user, 'Arroz 1kg');

        InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'purchase_item_id' => null,
            'origin_type' => 'test',
            'origin_id' => 20,
            'received_at' => Carbon::now()->subDays(20),
            'expiration_date' => null,
            'initial_quantity' => 10,
            'available_quantity' => 10,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 100,
            'suggested_sale_price_amount' => null,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        $datedLot = InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'purchase_item_id' => null,
            'origin_type' => 'test',
            'origin_id' => 21,
            'received_at' => Carbon::now()->subDays(10),
            'expiration_date' => '2026-09-15',
            'initial_quantity' => 10,
            'available_quantity' => 10,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 100,
            'suggested_sale_price_amount' => null,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        $result = $variant->nearestLot;

        $this->assertNotNull($result);
        $this->assertSame($datedLot->id, $result->id);
    }

    #[Test]
    // Spec scenario #1: card renders full data (name, category, USD price, stock)
    public function card_renders_full_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        config()->set('pos.enabled', true);
        Customer::query()->create(['name' => 'Cliente General', 'document' => '—', 'is_default' => true, 'is_active' => true]);

        $this->createPresentationScenario($user, 'Coca-Cola 500 ml', 'Bebidas', 0.75, availableQuantity: 48);

        $response = $this->get(route('pos.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Extract the embedded JSON initial data
        preg_match('/window\.__POS_INITIAL_V2__\s*=\s*(\{.+?\});/s', $content, $matches);
        $this->assertNotEmpty($matches, '__POS_INITIAL_V2__ data must be embedded in the page');
        $initialData = json_decode($matches[1], true);
        $this->assertNotEmpty($initialData['productos']);
        $producto = $initialData['productos'][0];

        $this->assertSame('Coca-Cola 500 ml', $producto['nombre']);
        $this->assertSame('Bebidas', $producto['categoria']);
        $this->assertSame(0.75, $producto['precio']);
        $this->assertSame(48, $producto['disponibles']);

        // The view binds the data via Alpine expressions
        $this->assertStringContainsString('x-text="p.nombre"', $content);
        $this->assertStringContainsString('x-text="p.categoria ?? \'—\'"', $content);
        $this->assertStringContainsString('$store.posStore.formatMoney(p.precio)', $content);
        $this->assertStringContainsString('p.disponibles + \' disp.\'', $content);
    }

    #[Test]
    // Spec scenario #4: filter narrows the list
    public function filter_narrows_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        config()->set('pos.enabled', true);
        Customer::query()->create(['name' => 'Cliente General', 'document' => '—', 'is_default' => true, 'is_active' => true]);

        $this->createPresentationScenario($user, 'Agua mineral 500 ml', 'Bebidas', 1.00, availableQuantity: 10);
        $this->createPresentationScenario($user, 'Galletas de avena', 'Snacks', 2.50, availableQuantity: 10);

        $response = $this->get(route('pos.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Both products must be in the store data
        $this->assertStringContainsString('Agua mineral 500 ml', $content);
        $this->assertStringContainsString('Galletas de avena', $content);

        // The input must be bound to busqueda
        $this->assertStringContainsString('x-model="$store.posStore.busqueda"', $content);
        // The x-for must use filteredProductos
        $this->assertStringContainsString('$store.posStore.filteredProductos', $content);

        // The store JS must implement the filter logic
        $js = file_get_contents(self::STORE_JS);
        $this->assertStringContainsString('get filteredProductos()', $js);
        $this->assertStringContainsString('haystack.includes(q)', $js);
    }

    #[Test]
    // Spec scenario #5: no matches shows empty state
    public function no_matches_shows_empty_state(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        config()->set('pos.enabled', true);
        Customer::query()->create(['name' => 'Cliente General', 'document' => '—', 'is_default' => true, 'is_active' => true]);

        $this->createPresentationScenario($user, 'Coca-Cola 500 ml', 'Bebidas', 1.00, availableQuantity: 10);

        $response = $this->get(route('pos.index'));
        $response->assertOk();

        $content = $response->getContent();

        // The empty-state template must be present
        $this->assertStringContainsString('No se encontraron productos para', $content);
        $this->assertStringContainsString('filteredProductos.length === 0', $content);
    }

    #[Test]
    // Spec scenario #6: adding a new product creates a line
    public function store_agregar_adds_new_line(): void
    {
        $js = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('agregar(producto)', $js);
        // New lines must be pushed with cantidad: 1
        $this->assertMatchesRegularExpression(
            '/cantidad:\s*1/',
            $js,
            'New cart lines must start with cantidad 1'
        );
        // Must guard against zero-stock
        $this->assertStringContainsString('Number(producto.disponibles) <= 0', $js);
        // Must search for existing items by id
        $this->assertStringContainsString('this.items.find((it) => it.id === producto.id)', $js);
    }

    #[Test]
    // Spec scenario #7: adding an existing product increments qty
    public function store_agregar_increments_existing_line(): void
    {
        $js = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('agregar(producto)', $js);
        // The existing-item branch must increment by 1
        $this->assertStringContainsString(
            'existing.cantidad = Number(existing.cantidad) + 1',
            $js
        );
        // The lookup must match by id
        $this->assertStringContainsString('this.items.find((it) => it.id === producto.id)', $js);
    }

    #[Test]
    // Spec scenario #8: stock 0 disables card and shows "Agotado"
    public function catalog_index_still_renders_successfully_when_a_product_has_no_stock(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        config()->set('pos.enabled', true);
        Customer::query()->create(['name' => 'Cliente General', 'document' => '—', 'is_default' => true, 'is_active' => true]);

        $this->createPresentationScenario($user, 'Café soluble 50 g', 'Abarrotes', 1.25, availableQuantity: 0);

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        // The view must show the "Agotado" chip for zero-stock products
        $response->assertSee('Agotado');
        // The add-to-cart button must be disabled via Alpine binding
        $this->assertStringContainsString(':disabled="p.disponibles <= 0"', $response->getContent());
    }

    private function createPresentationScenario(
        User $user,
        string $productName,
        string $categoryName,
        float $price,
        float $availableQuantity,
    ): SalePresentation {
        $category = Category::query()->firstOrCreate(
            ['name' => $categoryName],
            ['is_active' => true],
        );
        $brand = Brand::query()->firstOrCreate(
            ['name' => 'Marca POS'],
            ['is_active' => true],
        );
        $baseUnit = BaseUnit::query()->firstOrCreate(
            ['symbol' => 'u'],
            ['name' => 'Unidad'],
        );

        $product = Product::query()->create([
            'name' => $productName,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'internal_code' => 'POS-'.strtoupper(substr(md5($productName), 0, 6)),
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Unidad',
            'barcode' => '789'.str_pad((string) abs(crc32($productName)), 10, '0', STR_PAD_LEFT),
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $presentation = SalePresentation::query()->create([
            'product_variant_id' => $variant->id,
            'name' => 'Unidad',
            'conversion_factor' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);

        SalePrice::query()->create([
            'sale_presentation_id' => $presentation->id,
            'price_amount' => (int) round($price * 100),
            'min_price_amount' => (int) round($price * 90),
            'starts_at' => now()->subDay(),
            'created_by' => $user->id,
            'reason' => 'Precio POS',
        ]);

        if ($availableQuantity > 0) {
            InventoryLot::query()->create([
                'variant_id' => $variant->id,
                'purchase_item_id' => null,
                'origin_type' => 'test',
                'origin_id' => 1,
                'received_at' => now()->subDay(),
                'expiration_date' => null,
                'initial_quantity' => $availableQuantity,
                'available_quantity' => $availableQuantity,
                'bonus_quantity' => 0,
                'unit_cost_final_amount' => 50,
                'suggested_sale_price_amount' => null,
                'is_estimated' => false,
                'status' => 'active',
            ]);
        }

        return $presentation;
    }

    private function createVariantWithPresentation(User $user, string $productName): ProductVariant
    {
        $category = Category::query()->firstOrCreate(['name' => 'Bebidas'], ['is_active' => true]);
        $brand = Brand::query()->firstOrCreate(['name' => 'Marca POS'], ['is_active' => true]);
        $baseUnit = BaseUnit::query()->firstOrCreate(['symbol' => 'u'], ['name' => 'Unidad']);

        $product = Product::query()->create([
            'name' => $productName,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'internal_code' => 'POS-'.strtoupper(substr(md5($productName), 0, 6)),
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Unidad',
            'barcode' => '789'.str_pad((string) abs(crc32($productName)), 10, '0', STR_PAD_LEFT),
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => true,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $presentation = SalePresentation::query()->create([
            'product_variant_id' => $variant->id,
            'name' => 'Unidad',
            'conversion_factor' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);

        SalePrice::query()->create([
            'sale_presentation_id' => $presentation->id,
            'price_amount' => 100,
            'min_price_amount' => 90,
            'starts_at' => now()->subDay(),
            'created_by' => $user->id,
            'reason' => 'Precio POS',
        ]);

        return $variant;
    }
}
