<?php

namespace Tests\Feature;

use App\Models\MainStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExcelImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $gudang;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Monica', 'email' => 'monica@stockecm.test',
            'password' => 'password', 'role' => 'admin', 'is_active' => true,
        ]);

        $this->gudang = User::create([
            'name' => 'Novan', 'email' => 'novan@stockecm.test',
            'password' => 'password', 'role' => 'gudang', 'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Produk A', 'sku' => 'PRD001', 'carton_qty' => 24,
            'unit' => 'PCS', 'min_stock' => 20, 'status' => 'active',
        ]);
    }

    /**
     * Build a real .xlsx upload using the given headings and rows.
     */
    protected function makeXlsx(array $headings, array $rows, string $name = 'import.xlsx'): UploadedFile
    {
        Storage::fake('local');

        Excel::store(new class($headings, $rows) implements FromCollection, WithHeadings
        {
            public function __construct(private array $headings, private array $rows)
            {
            }

            public function headings(): array
            {
                return $this->headings;
            }

            public function collection(): Collection
            {
                return collect($this->rows);
            }
        }, $name);

        return UploadedFile::fake()->createWithContent($name, Storage::disk('local')->get($name));
    }

    protected function seedMainAndDisplay(int $mainQty = 500, int $transferQty = 50): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => $mainQty, 'trx_date' => today()->toDateString(),
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => $transferQty, 'trx_date' => today()->toDateString(),
        ]);
    }

    public function test_all_exports_download_as_xlsx(): void
    {
        $urls = [
            route('products.export'),
            route('main-stocks.export'),
            route('display-stocks.export'),
            route('stock-transfers.export'),
            route('stock-outs.export'),
            route('sisa-display.export'),
            route('stock-adjustments.export'),
            route('users.export'),
            route('reports.stock-history.export'),
            route('reports.daily-stock.export'),
            route('reports.stock-movement.export'),
            route('audit-logs.export'),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertOk();
            $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'), "Export gagal untuk {$url}");
        }
    }

    public function test_all_templates_download_as_xlsx(): void
    {
        $urls = [
            route('products.template'),
            route('main-stocks.template'),
            route('display-stocks.template'),
            route('stock-transfers.template'),
            route('stock-outs.template'),
            route('sisa-display.template'),
            route('stock-adjustments.template'),
            route('users.template'),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertOk();
            $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'), "Template gagal untuk {$url}");
        }
    }

    public function test_products_import_creates_new_and_updates_existing(): void
    {
        $file = $this->makeXlsx(
            ['Nama Produk', 'SKU', 'Isi Carton', 'Satuan', 'Min Stock', 'Status'],
            [
                ['Produk Baru', 'PRD002', 10, 'PCS', 2, 'active'],
                ['Produk A Baru', 'PRD001', 48, 'BOTOL', 30, 'inactive'],
            ]
        );

        $this->actingAs($this->admin)
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['sku' => 'PRD002', 'name' => 'Produk Baru', 'carton_qty' => 10, 'status' => 'active']);
        $this->assertDatabaseHas('products', ['sku' => 'PRD001', 'name' => 'Produk A Baru', 'carton_qty' => 48, 'unit' => 'BOTOL', 'min_stock' => 30, 'status' => 'inactive']);
    }

    public function test_products_import_rejects_invalid_rows(): void
    {
        $file = $this->makeXlsx(
            ['Nama Produk', 'SKU', 'Isi Carton', 'Satuan', 'Min Stock', 'Status'],
            [
                ['Produk Valid', 'PRD003', 5, 'PCS', 1, 'active'],
                ['', 'PRD004', -5, 'PCS', 1, 'active'],
            ]
        );

        $this->actingAs($this->admin)
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['sku' => 'PRD003']);
        $this->assertDatabaseMissing('products', ['sku' => 'PRD004']);
    }

    public function test_main_stocks_import_records_stock_in(): void
    {
        $file = $this->makeXlsx(
            ['SKU Produk', 'Qty', 'Tanggal', 'Keterangan'],
            [['PRD001', 300, today()->toDateString(), 'Import stok']]
        );

        $this->actingAs($this->gudang)
            ->post(route('main-stocks.import'), ['file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 300]);
        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $this->product->id,
            'type' => 'INITIAL_STOCK',
            'qty' => 300,
        ]);
    }

    public function test_stock_transfers_import_moves_main_to_display(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);

        $file = $this->makeXlsx(
            ['SKU Produk', 'Qty', 'Tanggal', 'Keterangan'],
            [['PRD001', 50, today()->toDateString(), '']]
        );

        $this->actingAs($this->gudang)
            ->post(route('stock-transfers.import'), ['file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 450]);
        $this->assertDatabaseHas('stock_transfers', ['trx_no' => 'ST-'.today()->format('Ymd').'-001', 'qty' => 50]);
        $this->assertDatabaseHas('display_stocks', [
            'product_id' => $this->product->id,
            'in_qty' => 50,
            'closing_qty' => 50,
        ]);
    }

    public function test_display_stocks_import_creates_transfer_to_display(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);

        $file = $this->makeXlsx(
            ['SKU Produk', 'Qty', 'Tanggal', 'Keterangan'],
            [['PRD001', 40, today()->toDateString(), 'Import display']]
        );

        $this->actingAs($this->gudang)
            ->post(route('display-stocks.import'), ['file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 460]);
        $this->assertDatabaseHas('stock_transfers', ['trx_no' => 'ST-'.today()->format('Ymd').'-001', 'qty' => 40, 'note' => 'Import display']);
        $this->assertDatabaseHas('display_stocks', [
            'product_id' => $this->product->id,
            'in_qty' => 40,
            'closing_qty' => 40,
        ]);
    }

    public function test_stock_outs_import_reduces_display(): void
    {
        $this->seedMainAndDisplay();

        $file = $this->makeXlsx(
            ['SKU Produk', 'Qty', 'Tanggal', 'Keterangan'],
            [['PRD001', 10, today()->toDateString(), 'Import OUT']]
        );

        $this->actingAs($this->gudang)
            ->post(route('stock-outs.import'), ['file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stock_outs', ['trx_no' => 'OUT-'.today()->format('Ymd').'-001', 'qty' => 10]);
        $this->assertDatabaseHas('display_stocks', [
            'product_id' => $this->product->id,
            'out_qty' => 10,
            'closing_qty' => 40,
        ]);
    }

    public function test_sisa_display_import_records_out_for_difference(): void
    {
        $this->seedMainAndDisplay();

        $file = $this->makeXlsx(
            ['SKU Produk', 'Sisa Display', 'Tanggal', 'Keterangan'],
            [['PRD001', 40, today()->toDateString(), 'Hitung fisik']]
        );

        $this->actingAs($this->gudang)
            ->post(route('sisa-display.import'), ['file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stock_outs', ['qty' => 10, 'note' => 'Hitung fisik']);
        $this->assertDatabaseHas('display_stocks', [
            'product_id' => $this->product->id,
            'out_qty' => 10,
            'closing_qty' => 40,
        ]);
    }

    public function test_stock_adjustments_import_updates_stock(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 100, 'trx_date' => today()->toDateString(),
        ]);

        $file = $this->makeXlsx(
            ['SKU Produk', 'Scope', 'Qty', 'Alasan', 'Tanggal'],
            [['PRD001', 'main', -10, 'Salah input', today()->toDateString()]]
        );

        $this->actingAs($this->admin)
            ->post(route('stock-adjustments.import'), ['file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 90]);
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $this->product->id, 'scope' => 'main', 'qty' => -10]);
    }

    public function test_users_import_creates_users(): void
    {
        $file = $this->makeXlsx(
            ['Nama', 'Email', 'Password', 'Role', 'Aktif'],
            [['User Baru', 'baru@stockecm.test', 'password', 'gudang', 1]]
        );

        $this->actingAs($this->admin)
            ->post(route('users.import'), ['file' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => 'baru@stockecm.test', 'name' => 'User Baru', 'role' => 'gudang', 'is_active' => true]);
    }

    public function test_import_reports_failed_rows_in_flash(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 100, 'trx_date' => today()->toDateString(),
        ]);

        $file = $this->makeXlsx(
            ['SKU Produk', 'Qty', 'Tanggal', 'Keterangan'],
            [
                ['PRD001', 30, today()->toDateString(), ''],
                ['PRD001', 999, today()->toDateString(), ''],
            ]
        );

        $response = $this->actingAs($this->gudang)
            ->post(route('stock-transfers.import'), ['file' => $file]);

        $response->assertSessionHas('success', fn (string $msg) => str_contains($msg, '1 baris berhasil') && str_contains($msg, 'gagal'));
        $this->assertDatabaseHas('stock_transfers', ['qty' => 30]);
    }

    public function test_gudang_cannot_import_admin_only_modules(): void
    {
        $file = $this->makeXlsx(['Nama Produk', 'SKU', 'Isi Carton', 'Satuan', 'Min Stock', 'Status'], []);

        $this->actingAs($this->gudang)->post(route('products.import'), ['file' => $file])->assertForbidden();
        $this->actingAs($this->gudang)->get(route('products.template'))->assertForbidden();
        $this->actingAs($this->gudang)->get(route('stock-adjustments.template'))->assertForbidden();
        $this->actingAs($this->gudang)->get(route('users.template'))->assertForbidden();
    }

    public function test_gudang_can_export_shared_modules(): void
    {
        foreach ([
            route('products.export'),
            route('main-stocks.export'),
            route('stock-transfers.export'),
            route('stock-outs.export'),
            route('sisa-display.export'),
        ] as $url) {
            $response = $this->actingAs($this->gudang)->get($url);
            $response->assertOk();
            $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
        }
    }
}