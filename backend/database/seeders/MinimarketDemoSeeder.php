<?php

namespace Database\Seeders;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\OpeningInventoryEntry;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SalePresentation;
use App\Models\SalePrice;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Money;
use App\Support\Purchases\CreateDetailedPurchase;
use App\Support\Purchases\CreateQuickPurchaseService;
use App\Support\Purchases\PurchaseCorrectionService;
use App\Support\Sales\CreateSaleService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MinimarketDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->resetDataset();
        $this->call([
            RolesTableSeeder::class,
            AdminUserSeeder::class,
        ]);

        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $assistant = $this->seedAssistantUser();
        $timeline = $this->timeline();

        $catalog = $this->seedCatalog($admin, $timeline['price_starts_at']);
        $suppliers = $this->seedSuppliers();
        $customers = $this->seedCustomers();

        $pastCashSession = $this->openCashSession($assistant, $timeline['past_cash_opened_at'], 120.00);

        $this->seedOpeningInventory(
            $catalog['variants']['funda_mediana'],
            $admin,
            $timeline['opening_inventory_at'],
            150,
            0.01,
            true,
            'Saldo inicial auditado para cobros de fundas.'
        );

        $consumablePurchase = $this->createDetailedPurchase($admin, $suppliers, $catalog, $timeline['detailed_purchase_at']);
        $this->createQuickPurchase($admin, $suppliers, $catalog, $timeline['quick_purchase_at']);
        $this->createVoidedPurchase($admin, $suppliers, $catalog, $timeline['voided_purchase_at']);

        $this->createPastSales($assistant, $pastCashSession, $customers, $catalog, $timeline);
        $this->createManualCashMovement($pastCashSession, $assistant, $timeline['past_manual_expense_at'], 'expense', 1.25, 'cash', 'Compra menor de cambio y fundas.');
        $this->closeCashSession($pastCashSession, $timeline['past_cash_closed_at'], 124.75, 'Cierre con faltante leve de demostración.');

        $currentCashSession = $this->openCashSession($assistant, $timeline['current_cash_opened_at'], 80.00);
        $this->createCurrentSales($assistant, $currentCashSession, $customers, $catalog, $timeline);
        $this->createReceivablePayment(
            Receivable::query()->where('status', 'open')->oldest('opened_at')->firstOrFail(),
            $assistant,
            $currentCashSession,
            $timeline['current_receivable_payment_at'],
            0.75,
            'cash',
            'Abono parcial de cliente frecuente.'
        );
        $this->createManualCashMovement($currentCashSession, $assistant, $timeline['current_manual_income_at'], 'manual_income', 3.00, 'cash', 'Ingreso extraordinario por reposición de caja demo.');

        $this->command?->info('Dataset demo minimarket cargado.');
        $this->command?->info('Admin: admin / admin12345');
        $this->command?->info('Cajero: cajero / cajero12345');
        $this->command?->info(sprintf(
            'Resumen: %d productos, %d compras, %d ventas, %d clientes, %d cajas.',
            Product::query()->count(),
            Purchase::query()->count(),
            Sale::query()->count(),
            Customer::query()->count(),
            CashSession::query()->count(),
        ));

        if ($consumablePurchase->isConfirmed()) {
            $this->command?->info('La compra detallada quedó confirmada y con lotes consumidos por ventas posteriores.');
        }
    }

    private function resetDataset(): void
    {
        $tables = [
            'receivable_payments',
            'receivables',
            'sale_payments',
            'sale_item_lot_consumptions',
            'sale_items',
            'sales',
            'cash_movements',
            'cash_sessions',
            'inventory_movements',
            'inventory_lots',
            'opening_inventory_entries',
            'purchase_items',
            'purchases',
            'supplier_variant_refs',
            'customers',
            'sale_prices',
            'sale_presentations',
            'product_variants',
            'products',
            'suppliers',
            'categories',
            'brands',
            'base_units',
            'role_user',
        ];

        DB::beginTransaction();

        try {
            DB::statement('PRAGMA foreign_keys = OFF');

            foreach ($tables as $table) {
                DB::table($table)->delete();
            }

            User::query()->where('username', '!=', 'admin')->delete();

            DB::statement('PRAGMA foreign_keys = ON');
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            DB::statement('PRAGMA foreign_keys = ON');

            throw $exception;
        }
    }

    private function seedAssistantUser(): User
    {
        $assistant = User::query()->updateOrCreate(
            ['email' => 'cajero@xpos.local'],
            [
                'name' => 'Caja Principal',
                'username' => 'cajero',
                'password' => 'cajero12345',
                'is_active' => true,
            ]
        );

        $assistantRole = Role::query()->where('name', 'assistant')->first();

        if ($assistantRole !== null) {
            $assistant->roles()->sync([$assistantRole->id]);
        }

        return $assistant;
    }

    private function seedCatalog(User $admin, CarbonImmutable $priceStartsAt): array
    {
        $categories = collect([
            'bebidas' => Category::query()->create(['name' => 'Bebidas', 'is_active' => true]),
            'abarrotes' => Category::query()->create(['name' => 'Abarrotes', 'is_active' => true]),
            'snacks' => Category::query()->create(['name' => 'Snacks', 'is_active' => true]),
            'limpieza' => Category::query()->create(['name' => 'Limpieza', 'is_active' => true]),
            'insumos' => Category::query()->create(['name' => 'Insumos', 'is_active' => true]),
        ]);

        $brands = collect([
            'coca_cola' => Brand::query()->create(['name' => 'Coca-Cola', 'is_active' => true]),
            'tesalia' => Brand::query()->create(['name' => 'Tesalia', 'is_active' => true]),
            'toni' => Brand::query()->create(['name' => 'Toni', 'is_active' => true]),
            'real' => Brand::query()->create(['name' => 'Real', 'is_active' => true]),
            'favorita' => Brand::query()->create(['name' => 'La Favorita', 'is_active' => true]),
            'festival' => Brand::query()->create(['name' => 'Festival', 'is_active' => true]),
            'ruffles' => Brand::query()->create(['name' => 'Ruffles', 'is_active' => true]),
            'deja' => Brand::query()->create(['name' => 'Deja', 'is_active' => true]),
            'lavatodo' => Brand::query()->create(['name' => 'Lava Todo', 'is_active' => true]),
            'bimbo' => Brand::query()->create(['name' => 'Bimbo', 'is_active' => true]),
            'nescafe' => Brand::query()->create(['name' => 'Nescafé', 'is_active' => true]),
            'genérica' => Brand::query()->create(['name' => 'Genérica', 'is_active' => true]),
        ]);

        $unit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $definitions = [
            [
                'key' => 'coca_500',
                'product_name' => 'Coca-Cola 500 ml',
                'variant_name' => 'Botella 500 ml',
                'category' => 'bebidas',
                'brand' => 'coca_cola',
                'internal_code' => 'MIN-001',
                'sku' => 'CC500',
                'barcode' => '7861001000011',
                'tracks_expiration' => false,
                'notes' => 'Producto de alta rotación.',
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 0.75, 'min_price' => 0.70, 'margin' => 32],
                    ['key' => 'pack6', 'name' => 'Pack x6', 'factor' => 6, 'default' => false, 'price' => 4.20, 'min_price' => 4.00, 'margin' => 32],
                ],
            ],
            [
                'key' => 'agua_600',
                'product_name' => 'Agua Tesalia 600 ml',
                'variant_name' => 'Botella 600 ml',
                'category' => 'bebidas',
                'brand' => 'tesalia',
                'internal_code' => 'MIN-002',
                'sku' => 'AG600',
                'barcode' => '7861001000028',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 0.50, 'min_price' => 0.45, 'margin' => 35],
                ],
            ],
            [
                'key' => 'leche_1l',
                'product_name' => 'Leche Toni 1L',
                'variant_name' => 'Entera 1 litro',
                'category' => 'bebidas',
                'brand' => 'toni',
                'internal_code' => 'MIN-003',
                'sku' => 'LECH1L',
                'barcode' => '7861001000035',
                'tracks_expiration' => true,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 1.35, 'min_price' => 1.25, 'margin' => 24],
                ],
            ],
            [
                'key' => 'atun_170',
                'product_name' => 'Atún Real 170 g',
                'variant_name' => 'Lata 170 g',
                'category' => 'abarrotes',
                'brand' => 'real',
                'internal_code' => 'MIN-004',
                'sku' => 'ATUN170',
                'barcode' => '7861001000042',
                'tracks_expiration' => true,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 1.65, 'min_price' => 1.50, 'margin' => 27],
                ],
            ],
            [
                'key' => 'arroz_1kg',
                'product_name' => 'Arroz envejecido 1 kg',
                'variant_name' => 'Funda 1 kg',
                'category' => 'abarrotes',
                'brand' => 'genérica',
                'internal_code' => 'MIN-005',
                'sku' => 'ARROZ1K',
                'barcode' => '7861001000059',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 1.10, 'min_price' => 1.00, 'margin' => 26],
                ],
            ],
            [
                'key' => 'azucar_1kg',
                'product_name' => 'Azúcar 1 kg',
                'variant_name' => 'Funda 1 kg',
                'category' => 'abarrotes',
                'brand' => 'genérica',
                'internal_code' => 'MIN-006',
                'sku' => 'AZUCAR1K',
                'barcode' => '7861001000066',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 1.00, 'min_price' => 0.92, 'margin' => 24],
                ],
            ],
            [
                'key' => 'galletas_festival',
                'product_name' => 'Galletas Festival',
                'variant_name' => 'Paquete personal',
                'category' => 'snacks',
                'brand' => 'festival',
                'internal_code' => 'MIN-007',
                'sku' => 'FESTI',
                'barcode' => '7861001000073',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 0.60, 'min_price' => 0.55, 'margin' => 28],
                ],
            ],
            [
                'key' => 'papas_ruffles',
                'product_name' => 'Papas Ruffles 40 g',
                'variant_name' => 'Funda 40 g',
                'category' => 'snacks',
                'brand' => 'ruffles',
                'internal_code' => 'MIN-008',
                'sku' => 'RUF40',
                'barcode' => '7861001000080',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 0.55, 'min_price' => 0.50, 'margin' => 30],
                ],
            ],
            [
                'key' => 'detergente_500',
                'product_name' => 'Detergente Deja 500 g',
                'variant_name' => 'Funda 500 g',
                'category' => 'limpieza',
                'brand' => 'deja',
                'internal_code' => 'MIN-009',
                'sku' => 'DEJA500',
                'barcode' => '7861001000097',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 1.90, 'min_price' => 1.75, 'margin' => 25],
                ],
            ],
            [
                'key' => 'jabon_200',
                'product_name' => 'Jabón Lava Todo 200 g',
                'variant_name' => 'Barra 200 g',
                'category' => 'limpieza',
                'brand' => 'lavatodo',
                'internal_code' => 'MIN-010',
                'sku' => 'JAB200',
                'barcode' => '7861001000103',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 0.80, 'min_price' => 0.72, 'margin' => 27],
                ],
            ],
            [
                'key' => 'aceite_900',
                'product_name' => 'Aceite La Favorita 900 ml',
                'variant_name' => 'Botella 900 ml',
                'category' => 'abarrotes',
                'brand' => 'favorita',
                'internal_code' => 'MIN-011',
                'sku' => 'ACE900',
                'barcode' => '7861001000110',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 2.35, 'min_price' => 2.20, 'margin' => 20],
                ],
            ],
            [
                'key' => 'huevo_unidad',
                'product_name' => 'Huevos blancos',
                'variant_name' => 'Unidad',
                'category' => 'abarrotes',
                'brand' => 'genérica',
                'internal_code' => 'MIN-012',
                'sku' => 'HUEVO',
                'barcode' => '7861001000127',
                'tracks_expiration' => true,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 0.20, 'min_price' => 0.18, 'margin' => 25],
                    ['key' => 'docena', 'name' => 'Docena', 'factor' => 12, 'default' => false, 'price' => 2.25, 'min_price' => 2.10, 'margin' => 25],
                ],
            ],
            [
                'key' => 'pan_bimbo',
                'product_name' => 'Pan tajado Bimbo',
                'variant_name' => 'Paquete 680 g',
                'category' => 'abarrotes',
                'brand' => 'bimbo',
                'internal_code' => 'MIN-013',
                'sku' => 'PAN680',
                'barcode' => '7861001000134',
                'tracks_expiration' => true,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 1.75, 'min_price' => 1.65, 'margin' => 20],
                ],
            ],
            [
                'key' => 'cafe_50',
                'product_name' => 'Café soluble 50 g',
                'variant_name' => 'Frasco 50 g',
                'category' => 'abarrotes',
                'brand' => 'nescafe',
                'internal_code' => 'MIN-014',
                'sku' => 'CAFE50',
                'barcode' => '7861001000141',
                'tracks_expiration' => false,
                'notes' => 'Se deja sin stock para provocar warning controlado.',
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 1.25, 'min_price' => 1.15, 'margin' => 22],
                ],
            ],
            [
                'key' => 'funda_mediana',
                'product_name' => 'Funda mediana',
                'variant_name' => 'Unidad',
                'category' => 'insumos',
                'brand' => 'genérica',
                'internal_code' => 'MIN-015',
                'sku' => 'FUNMED',
                'barcode' => '7861001000158',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 0.05, 'min_price' => 0.05, 'margin' => 80],
                ],
            ],
            [
                'key' => 'yogurt_200',
                'product_name' => 'Yogurt Toni 200 g',
                'variant_name' => 'Vaso 200 g',
                'category' => 'bebidas',
                'brand' => 'toni',
                'internal_code' => 'MIN-016',
                'sku' => 'YOG200',
                'barcode' => '7861001000165',
                'tracks_expiration' => true,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 0.70, 'min_price' => 0.65, 'margin' => 30],
                ],
            ],
            [
                'key' => 'sardina_155',
                'product_name' => 'Sardina Real 155 g',
                'variant_name' => 'Lata 155 g',
                'category' => 'abarrotes',
                'brand' => 'real',
                'internal_code' => 'MIN-017',
                'sku' => 'SARD155',
                'barcode' => '7861001000172',
                'tracks_expiration' => true,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 1.30, 'min_price' => 1.20, 'margin' => 24],
                ],
            ],
            [
                'key' => 'avena_400',
                'product_name' => 'Avena 400 g',
                'variant_name' => 'Funda 400 g',
                'category' => 'abarrotes',
                'brand' => 'genérica',
                'internal_code' => 'MIN-018',
                'sku' => 'AVENA400',
                'barcode' => '7861001000189',
                'tracks_expiration' => false,
                'presentations' => [
                    ['key' => 'default', 'name' => 'Unidad', 'factor' => 1, 'default' => true, 'price' => 1.20, 'min_price' => 1.10, 'margin' => 23],
                ],
            ],
        ];

        $variants = [];
        $defaultPresentations = [];
        $presentations = [];

        foreach ($definitions as $definition) {
            $product = Product::query()->create([
                'name' => $definition['product_name'],
                'category_id' => $categories[$definition['category']]->id,
                'brand_id' => $brands[$definition['brand']]->id,
                'internal_code' => $definition['internal_code'],
                'status' => 'active',
                'notes' => $definition['notes'] ?? null,
            ]);

            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'name' => $definition['variant_name'],
                'sku' => $definition['sku'],
                'barcode' => $definition['barcode'],
                'base_unit_id' => $unit->id,
                'tracks_expiration' => $definition['tracks_expiration'],
                'is_returnable' => false,
                'is_active' => true,
                'notes' => $definition['notes'] ?? null,
            ]);

            $variants[$definition['key']] = $variant;

            foreach ($definition['presentations'] as $presentationDefinition) {
                $presentation = SalePresentation::query()->create([
                    'product_variant_id' => $variant->id,
                    'name' => $presentationDefinition['name'],
                    'conversion_factor' => $presentationDefinition['factor'],
                    'is_default' => $presentationDefinition['default'],
                    'is_active' => true,
                ]);

                SalePrice::query()->create([
                    'sale_presentation_id' => $presentation->id,
                    'price_amount' => $this->cents($presentationDefinition['price']),
                    'min_price_amount' => $this->cents($presentationDefinition['min_price']),
                    'suggested_margin_percent' => $presentationDefinition['margin'],
                    'starts_at' => $priceStartsAt,
                    'ends_at' => null,
                    'created_by' => $admin->id,
                    'reason' => 'Carga demo minimarket',
                ]);

                $presentations[$definition['key'] . '.' . $presentationDefinition['key']] = $presentation;

                if ($presentationDefinition['default']) {
                    $defaultPresentations[$definition['key']] = $presentation;
                }
            }
        }

        return [
            'variants' => $variants,
            'default_presentations' => $defaultPresentations,
            'presentations' => $presentations,
        ];
    }

    private function seedSuppliers(): array
    {
        return [
            'distribuidora' => Supplier::query()->create([
                'name' => 'Distribuidora Centro Norte',
                'tax_id' => '1790010012001',
                'phone' => '0991111111',
                'address' => 'Av. Principal y Sucre',
                'notes' => 'Proveedor principal de abarrotes y bebidas.',
                'is_active' => true,
            ]),
            'lacteos' => Supplier::query()->create([
                'name' => 'Lácteos Sierra',
                'tax_id' => '1790010012002',
                'phone' => '0992222222',
                'address' => 'Mercado Mayorista, nave 3',
                'notes' => 'Rotación corta para lácteos y pan.',
                'is_active' => true,
            ]),
            'mayorista' => Supplier::query()->create([
                'name' => 'Mayorista El Ahorro',
                'tax_id' => '1790010012003',
                'phone' => '0993333333',
                'address' => 'Parque Industrial lote 12',
                'notes' => 'Complementa snacks, limpieza e insumos.',
                'is_active' => true,
            ]),
        ];
    }

    private function seedCustomers(): array
    {
        return [
            'general' => Customer::query()->create([
                'name' => 'Cliente General',
                'document' => '—',
                'phone' => null,
                'address' => null,
                'notes' => 'Cliente por defecto del POS. No acumula deuda.',
                'is_active' => true,
                'is_default' => true,
            ]),
            'maria' => Customer::query()->create([
                'name' => 'María Gómez',
                'document' => '0912345678',
                'phone' => '0981001001',
                'address' => 'Barrio Central, casa 12',
                'notes' => 'Cliente frecuente con fiado controlado.',
                'is_active' => true,
                'is_default' => false,
            ]),
            'jose' => Customer::query()->create([
                'name' => 'José Paredes',
                'document' => '0923456789',
                'phone' => '0981001002',
                'address' => 'Cdla. Los Helechos, mz 4',
                'notes' => 'Prefiere pagar por transferencia.',
                'is_active' => true,
                'is_default' => false,
            ]),
            'lucia' => Customer::query()->create([
                'name' => 'Lucía Torres',
                'document' => '0934567890',
                'phone' => '0981001003',
                'address' => 'Calle Bolívar y Olmedo',
                'notes' => 'Compra snacks y bebidas para oficina.',
                'is_active' => true,
                'is_default' => false,
            ]),
        ];
    }

    private function seedOpeningInventory(
        ProductVariant $variant,
        User $user,
        CarbonImmutable $recordedAt,
        float $quantity,
        float $estimatedUnitCost,
        bool $isAudited,
        string $notes,
    ): void {
        $costAmount = $this->cents($estimatedUnitCost);

        $entry = OpeningInventoryEntry::query()->create([
            'variant_id' => $variant->id,
            'quantity' => $quantity,
            'estimated_unit_cost_amount' => $costAmount,
            'recorded_at' => $recordedAt,
            'is_audited' => $isAudited,
            'notes' => $notes,
            'created_by' => $user->id,
        ]);

        $lot = InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'purchase_item_id' => null,
            'origin_type' => 'opening_inventory',
            'origin_id' => $entry->id,
            'received_at' => $recordedAt,
            'expiration_date' => null,
            'initial_quantity' => $quantity,
            'available_quantity' => $quantity,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => $costAmount,
            'suggested_sale_price_amount' => null,
            'is_estimated' => ! $isAudited,
            'status' => 'active',
        ]);

        InventoryMovement::query()->create([
            'variant_id' => $variant->id,
            'lot_id' => $lot->id,
            'movement_type' => 'opening_inventory',
            'quantity' => $quantity,
            'unit_cost_amount' => $costAmount,
            'reference_type' => 'opening_inventory',
            'reference_id' => $entry->id,
            'movement_at' => $recordedAt,
            'notes' => $notes,
            'created_by' => $user->id,
        ]);
    }

    private function createDetailedPurchase(User $user, array $suppliers, array $catalog, CarbonImmutable $purchasedAt): Purchase
    {
        $service = app(CreateDetailedPurchase::class);

        return $service->handle([
            'supplier_id' => $suppliers['distribuidora']->id,
            'invoice_number' => 'FAC-DEM-001',
            'purchased_at' => $purchasedAt,
            'payment_type' => 'cash',
            'global_discount_amount' => 1.25,
            'global_tax_iva_amount' => 1.80,
            'global_tax_ice_amount' => 0,
            'global_tax_other_amount' => 0.35,
            'extra_costs_amount' => 1.10,
            'notes' => 'Compra detallada demo con impuestos y bonificación.',
            'items' => [
                [
                    'variant_id' => $catalog['variants']['coca_500']->id,
                    'quantity' => 24,
                    'bonus_quantity' => 0,
                    'unit_cost' => 0.47,
                    'tax_iva_amount' => 0.65,
                    'eligible_for_global_iva' => true,
                    'notes' => 'Percha de bebidas frías.',
                ],
                [
                    'variant_id' => $catalog['variants']['leche_1l']->id,
                    'quantity' => 18,
                    'bonus_quantity' => 2,
                    'unit_cost' => 0.98,
                    'tax_iva_amount' => 0.55,
                    'eligible_for_global_iva' => true,
                    'expiration_date' => $purchasedAt->addDays(18)->toDateString(),
                    'notes' => 'Lote refrigerado con bonificación del proveedor.',
                ],
                [
                    'variant_id' => $catalog['variants']['atun_170']->id,
                    'quantity' => 12,
                    'bonus_quantity' => 0,
                    'unit_cost' => 1.15,
                    'tax_other_amount' => 0.15,
                    'eligible_for_global_other' => true,
                    'notes' => 'Conserva de alta rotación.',
                ],
                [
                    'variant_id' => $catalog['variants']['arroz_1kg']->id,
                    'quantity' => 12,
                    'bonus_quantity' => 0,
                    'unit_cost' => 0.80,
                    'notes' => 'Abarrote básico de margen estable.',
                ],
                [
                    'variant_id' => $catalog['variants']['detergente_500']->id,
                    'quantity' => 8,
                    'bonus_quantity' => 0,
                    'unit_cost' => 1.40,
                    'tax_iva_amount' => 0.45,
                    'eligible_for_global_iva' => true,
                    'notes' => 'Limpieza del hogar.',
                ],
            ],
        ], $user->id);
    }

    private function createQuickPurchase(User $user, array $suppliers, array $catalog, CarbonImmutable $purchasedAt): Purchase
    {
        $service = app(CreateQuickPurchaseService::class);

        return $service->handle([
            'supplier_id' => $suppliers['mayorista']->id,
            'invoice_number' => 'FAC-DEM-002',
            'purchased_at' => $purchasedAt,
            'payment_type' => 'transfer',
            'is_credit' => false,
            'notes' => 'Reposición rápida de alta rotación.',
            'items' => [
                [
                    'variant_id' => $catalog['variants']['agua_600']->id,
                    'quantity' => 24,
                    'unit_cost' => 0.26,
                    'notes' => 'Agua embotellada para mostrador.',
                ],
                [
                    'variant_id' => $catalog['variants']['papas_ruffles']->id,
                    'quantity' => 20,
                    'unit_cost' => 0.30,
                    'notes' => 'Snacks de impulso.',
                ],
                [
                    'variant_id' => $catalog['variants']['azucar_1kg']->id,
                    'quantity' => 10,
                    'unit_cost' => 0.72,
                    'notes' => 'Reposición de abarrotes base.',
                ],
                [
                    'variant_id' => $catalog['variants']['pan_bimbo']->id,
                    'quantity' => 10,
                    'unit_cost' => 1.30,
                    'expiration_date' => $purchasedAt->addDays(7)->toDateString(),
                    'notes' => 'Producto de vencimiento corto.',
                ],
                [
                    'variant_id' => $catalog['variants']['yogurt_200']->id,
                    'quantity' => 24,
                    'unit_cost' => 0.42,
                    'expiration_date' => $purchasedAt->addDays(10)->toDateString(),
                    'notes' => 'Refrigerado de rotación media.',
                ],
                [
                    'variant_id' => $catalog['variants']['huevo_unidad']->id,
                    'quantity' => 60,
                    'unit_cost' => 0.13,
                    'expiration_date' => $purchasedAt->addDays(12)->toDateString(),
                    'notes' => 'Carga por unidad para venta suelta y docena.',
                ],
                [
                    'variant_id' => $catalog['variants']['sardina_155']->id,
                    'quantity' => 10,
                    'unit_cost' => 0.92,
                    'notes' => 'Complemento de percha de enlatados.',
                ],
            ],
        ], $user->id);
    }

    private function createVoidedPurchase(User $user, array $suppliers, array $catalog, CarbonImmutable $purchasedAt): void
    {
        $quickPurchaseService = app(CreateQuickPurchaseService::class);
        $correctionService = app(PurchaseCorrectionService::class);

        $purchase = $quickPurchaseService->handle([
            'supplier_id' => $suppliers['lacteos']->id,
            'invoice_number' => 'FAC-DEM-003',
            'purchased_at' => $purchasedAt,
            'payment_type' => 'cash',
            'is_credit' => false,
            'notes' => 'Compra que se anula por duplicidad de carga.',
            'items' => [
                [
                    'variant_id' => $catalog['variants']['aceite_900']->id,
                    'quantity' => 6,
                    'unit_cost' => 1.90,
                    'notes' => 'Se anula para dejar caso de corrección visible.',
                ],
                [
                    'variant_id' => $catalog['variants']['avena_400']->id,
                    'quantity' => 6,
                    'unit_cost' => 0.92,
                    'notes' => 'También queda sin stock por anulación.',
                ],
            ],
        ], $user->id);

        $purchase->refresh();

        $correctionService->void($purchase, 'Factura duplicada durante preparación demo', $user->id);
    }

    private function createPastSales(User $user, CashSession $cashSession, array $customers, array $catalog, array $timeline): void
    {
        $saleService = app(CreateSaleService::class);

        $saleService->handle([
            'sold_at' => $timeline['past_sale_cash_at'],
            'notes' => 'Venta de mostrador al contado.',
            'payments' => [
                'cash' => 3.05,
                'transfer' => 0,
            ],
            'items' => [
                [
                    'sale_presentation_id' => $catalog['default_presentations']['coca_500']->id,
                    'quantity' => 2,
                ],
                [
                    'sale_presentation_id' => $catalog['default_presentations']['papas_ruffles']->id,
                    'quantity' => 1,
                ],
                [
                    'sale_presentation_id' => $catalog['default_presentations']['azucar_1kg']->id,
                    'quantity' => 1,
                ],
            ],
        ], $user->id, $cashSession);

        $saleService->handle([
            'sold_at' => $timeline['past_sale_credit_at'],
            'customer_id' => $customers['maria']->id,
            'notes' => 'Venta con fiado parcial a cliente habitual.',
            'payments' => [
                'cash' => 2.50,
                'transfer' => 1.00,
            ],
            'items' => [
                [
                    'sale_presentation_id' => $catalog['default_presentations']['leche_1l']->id,
                    'quantity' => 2,
                ],
                [
                    'sale_presentation_id' => $catalog['default_presentations']['pan_bimbo']->id,
                    'quantity' => 1,
                ],
                [
                    'sale_presentation_id' => $catalog['default_presentations']['atun_170']->id,
                    'quantity' => 1,
                ],
            ],
        ], $user->id, $cashSession);
    }

    private function createCurrentSales(User $user, CashSession $cashSession, array $customers, array $catalog, array $timeline): void
    {
        $saleService = app(CreateSaleService::class);

        $saleService->handle([
            'sold_at' => $timeline['current_sale_override_at'],
            'notes' => 'Venta con descuento manual controlado.',
            'payments' => [
                'cash' => 2.10,
                'transfer' => 0,
            ],
            'items' => [
                [
                    'sale_presentation_id' => $catalog['default_presentations']['coca_500']->id,
                    'quantity' => 1,
                    'manual_unit_price' => 0.70,
                    'manual_price_reason' => 'Promoción de mostrador',
                ],
                [
                    'sale_presentation_id' => $catalog['default_presentations']['yogurt_200']->id,
                    'quantity' => 2,
                ],
            ],
        ], $user->id, $cashSession);

        $saleService->handle([
            'sold_at' => $timeline['current_sale_warning_at'],
            'customer_id' => $customers['lucia']->id,
            'notes' => 'Venta con warning explícito por producto sin stock real.',
            'confirm_stock_warnings' => true,
            'confirm_cost_warnings' => true,
            'payments' => [
                'cash' => 2.50,
                'transfer' => 0,
            ],
            'items' => [
                [
                    'sale_presentation_id' => $catalog['default_presentations']['cafe_50']->id,
                    'quantity' => 2,
                ],
            ],
        ], $user->id, $cashSession);

        $saleService->handle([
            'sold_at' => $timeline['current_sale_transfer_at'],
            'customer_id' => $customers['jose']->id,
            'notes' => 'Venta pagada por transferencia para probar conciliación.',
            'payments' => [
                'cash' => 0,
                'transfer' => 5.95,
            ],
            'items' => [
                [
                    'sale_presentation_id' => $catalog['default_presentations']['agua_600']->id,
                    'quantity' => 3,
                ],
                [
                    'sale_presentation_id' => $catalog['default_presentations']['arroz_1kg']->id,
                    'quantity' => 2,
                ],
                [
                    'sale_presentation_id' => $catalog['presentations']['huevo_unidad.docena']->id,
                    'quantity' => 1,
                ],
            ],
        ], $user->id, $cashSession);
    }

    private function openCashSession(User $user, CarbonImmutable $openedAt, float $openingAmount): CashSession
    {
        $session = CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => $openedAt,
            'opening_amount' => $this->cents($openingAmount),
            'status' => 'open',
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $session->id,
            'movement_type' => 'opening',
            'amount' => $session->opening_amount,
            'payment_method' => 'cash',
            'reference_type' => 'cash_session',
            'reference_id' => $session->id,
            'notes' => 'Apertura de caja demo',
            'created_by' => $user->id,
            'created_at' => $openedAt,
        ]);

        return $session;
    }

    private function createManualCashMovement(
        CashSession $cashSession,
        User $user,
        CarbonImmutable $createdAt,
        string $movementType,
        float $amount,
        string $paymentMethod,
        string $notes,
    ): void {
        CashMovement::query()->create([
            'cash_session_id' => $cashSession->id,
            'movement_type' => $movementType,
            'amount' => $this->cents($amount),
            'payment_method' => $paymentMethod,
            'reference_type' => 'manual_cash_movement',
            'reference_id' => null,
            'notes' => $notes,
            'created_by' => $user->id,
            'created_at' => $createdAt,
        ]);
    }

    private function createReceivablePayment(
        Receivable $receivable,
        User $user,
        CashSession $cashSession,
        CarbonImmutable $paidAt,
        float $amount,
        string $paymentMethod,
        string $notes,
    ): ReceivablePayment {
        $amountCents = $this->cents($amount);

        $payment = ReceivablePayment::query()->create([
            'receivable_id' => $receivable->id,
            'cash_session_id' => $cashSession->id,
            'amount' => $amountCents,
            'payment_method' => $paymentMethod,
            'paid_at' => $paidAt,
            'notes' => $notes,
            'created_by' => $user->id,
            'is_reversed' => false,
        ]);

        $newPending = max($receivable->pending_amount - $amountCents, 0);

        $receivable->update([
            'pending_amount' => $newPending,
            'status' => $newPending === 0 ? 'paid' : 'open',
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $cashSession->id,
            'movement_type' => 'receivable_payment',
            'amount' => $amountCents,
            'payment_method' => $paymentMethod,
            'reference_type' => 'receivable',
            'reference_id' => $receivable->id,
            'notes' => $notes,
            'created_by' => $user->id,
            'created_at' => $paidAt,
        ]);

        return $payment;
    }

    private function closeCashSession(CashSession $cashSession, CarbonImmutable $closedAt, float $countedCashAmount, string $notes): void
    {
        $cashSession->load('movements');

        $expectedCash = (int) $cashSession->movements
            ->where('payment_method', 'cash')
            ->sum(fn (CashMovement $movement) => $this->signedAmount($movement));

        $expectedTransfer = (int) $cashSession->movements
            ->where('payment_method', 'transfer')
            ->sum(fn (CashMovement $movement) => $this->signedAmount($movement));

        $countedCash = $this->cents($countedCashAmount);

        $cashSession->update([
            'status' => 'closed',
            'closed_at' => $closedAt,
            'expected_cash_amount' => $expectedCash,
            'counted_cash_amount' => $countedCash,
            'expected_transfer_amount' => $expectedTransfer,
            'difference_amount' => $countedCash - $expectedCash,
            'closing_notes' => $notes,
        ]);
    }

    private function signedAmount(CashMovement $movement): int
    {
        return match ($movement->movement_type) {
            'expense', 'withdrawal' => -1 * $movement->amount,
            default => $movement->amount,
        };
    }

    private function cents(float|int|string $amount): int
    {
        return Money::dollarsToCents($amount);
    }

    private function timeline(): array
    {
        $now = CarbonImmutable::now();

        return [
            'price_starts_at' => $now->subDays(10)->setTime(6, 0),
            'opening_inventory_at' => $now->subDays(5)->setTime(8, 30),
            'detailed_purchase_at' => $now->subDays(4)->setTime(9, 15),
            'quick_purchase_at' => $now->subDays(2)->setTime(7, 40),
            'past_cash_opened_at' => $now->subDays(2)->setTime(7, 0),
            'past_sale_cash_at' => $now->subDays(2)->setTime(9, 10),
            'past_sale_credit_at' => $now->subDays(2)->setTime(11, 20),
            'past_manual_expense_at' => $now->subDays(2)->setTime(18, 5),
            'past_cash_closed_at' => $now->subDays(2)->setTime(20, 15),
            'voided_purchase_at' => $now->subDay()->setTime(10, 10),
            'current_cash_opened_at' => $now->setTime(8, 0),
            'current_sale_override_at' => $now->setTime(9, 5),
            'current_sale_warning_at' => $now->setTime(10, 15),
            'current_receivable_payment_at' => $now->setTime(11, 0),
            'current_sale_transfer_at' => $now->setTime(12, 10),
            'current_manual_income_at' => $now->setTime(12, 45),
        ];
    }
}
