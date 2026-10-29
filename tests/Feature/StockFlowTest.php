<?php

namespace Tests\Feature;

use App\Models\DisplayStock;
use App\Models\MainStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StockFlowTest extends TestCase
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

    protected function day(string $date): ?DisplayStock
    {
        return DisplayStock::where('product_id', $this->product->id)->whereDate('stock_date', $date)->first();
    }

    public function test_gudang_can_input_main_stock(): void
    {
        $this->actingAs($this->gudang)
            ->post(route('main-stocks.store'), [
                'product_id' => $this->product->id,
                'qty' => 500,
                'trx_date' => today()->toDateString(),
                'note' => 'Stok awal',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 500]);
        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $this->product->id,
            'type' => 'INITIAL_STOCK',
            'qty' => 500,
        ]);
    }

    public function test_transfer_moves_main_to_display(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);

        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => today()->toDateString(),
        ]);

        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 450]);

        $day = $this->day(today()->toDateString());
        $this->assertNotNull($day);
        $this->assertEquals(50, $day->in_qty);
        $this->assertEquals(50, $day->closing_qty);

        $this->assertDatabaseHas('stock_transfers', ['trx_no' => 'ST-'.today()->format('Ymd').'-001', 'qty' => 50]);
    }

    public function test_out_reduces_display_not_main(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => today()->toDateString(),
        ]);

        $this->actingAs($this->gudang)->post(route('stock-outs.store'), [
            'product_id' => $this->product->id, 'qty' => 10, 'trx_date' => today()->toDateString(),
        ]);

        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 450]);

        $day = $this->day(today()->toDateString());
        $this->assertNotNull($day);
        $this->assertEquals(10, $day->out_qty);
        $this->assertEquals(40, $day->closing_qty);

        $this->assertDatabaseHas('stock_outs', ['trx_no' => 'OUT-'.today()->format('Ymd').'-001', 'qty' => 10]);
    }

    public function test_cannot_transfer_more_than_main_stock(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 100, 'trx_date' => today()->toDateString(),
        ]);

        $this->actingAs($this->gudang)
            ->post(route('stock-transfers.store'), [
                'product_id' => $this->product->id, 'qty' => 120, 'trx_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('qty');

        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 100]);
        $this->assertDatabaseCount('stock_transfers', 0);
    }

    public function test_cannot_out_more_than_display(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => today()->toDateString(),
        ]);

        $this->actingAs($this->gudang)
            ->post(route('stock-outs.store'), [
                'product_id' => $this->product->id, 'qty' => 60, 'trx_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('qty');

        $day = $this->day(today()->toDateString());
        $this->assertEquals(0, $day->out_qty);
        $this->assertEquals(50, $day->closing_qty);
        $this->assertDatabaseCount('stock_outs', 0);
    }

    public function test_daily_carry_over_to_next_day(): void
    {
        $yesterday = today()->subDay()->toDateString();

        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => $yesterday,
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => $yesterday,
        ]);
        $this->actingAs($this->gudang)->post(route('stock-outs.store'), [
            'product_id' => $this->product->id, 'qty' => 10, 'trx_date' => $yesterday,
        ]);

        // Today's OUT uses carried-over balance (40)
        $this->actingAs($this->gudang)->post(route('stock-outs.store'), [
            'product_id' => $this->product->id, 'qty' => 5, 'trx_date' => today()->toDateString(),
        ]);

        $prevDay = $this->day($yesterday);
        $this->assertNotNull($prevDay);
        $this->assertEquals(40, $prevDay->closing_qty);

        $nextDay = $this->day(today()->toDateString());
        $this->assertNotNull($nextDay);
        $this->assertEquals(40, $nextDay->opening_qty);
        $this->assertEquals(5, $nextDay->out_qty);
        $this->assertEquals(35, $nextDay->closing_qty);
    }

    public function test_adjustment_requires_reason_and_updates_display(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => today()->toDateString(),
        ]);

        // Gudang cannot adjust
        $this->actingAs($this->gudang)
            ->post(route('stock-adjustments.store'), [
                'product_id' => $this->product->id, 'scope' => 'display', 'qty' => -5,
                'reason' => 'Rusak', 'trx_date' => today()->toDateString(),
            ])
            ->assertForbidden();

        // Admin without reason fails
        $this->actingAs($this->admin)
            ->post(route('stock-adjustments.store'), [
                'product_id' => $this->product->id, 'scope' => 'display', 'qty' => -5,
                'trx_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('reason');

        // Admin with reason succeeds
        $this->actingAs($this->admin)
            ->post(route('stock-adjustments.store'), [
                'product_id' => $this->product->id, 'scope' => 'display', 'qty' => -5,
                'reason' => 'Barang rusak', 'trx_date' => today()->toDateString(),
            ])
            ->assertRedirect();

        $day = $this->day(today()->toDateString());
        $this->assertNotNull($day);
        $this->assertEquals(-5, $day->adjust_qty);
        $this->assertEquals(45, $day->closing_qty);

        $this->assertDatabaseHas('audit_logs', ['action' => 'Stock Adjustment', 'reason' => 'Barang rusak']);
    }

    public function test_cannot_adjust_stock_below_zero(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 100, 'trx_date' => today()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('stock-adjustments.store'), [
                'product_id' => $this->product->id, 'scope' => 'main', 'qty' => -500,
                'reason' => 'Test', 'trx_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('qty');

        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 100]);
    }

    public function test_gudang_cannot_access_admin_only_pages(): void
    {
        foreach ([route('users.index'), route('audit-logs.index'), route('stock-adjustments.index')] as $url) {
            $this->actingAs($this->gudang)->get($url)->assertForbidden();
        }

        $this->actingAs($this->gudang)->post(route('products.store'), [
            'name' => 'X', 'sku' => 'X1', 'carton_qty' => 1, 'unit' => 'PCS', 'min_stock' => 0, 'status' => 'active',
        ])->assertForbidden();
    }

    public function test_admin_can_access_all_pages(): void
    {
        foreach ([
            route('dashboard'), route('products.index'), route('main-stocks.index'),
            route('display-stocks.index'), route('stock-transfers.index'), route('stock-outs.index'),
            route('sisa-display.index'),
            route('stock-adjustments.index'), route('reports.stock-history'),
            route('reports.daily-stock'), route('reports.stock-movement'),
            route('users.index'), route('audit-logs.index'),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_gudang_can_access_stock_pages(): void
    {
        foreach ([
            route('dashboard'), route('products.index'), route('main-stocks.index'),
            route('display-stocks.index'), route('stock-transfers.index'), route('stock-outs.index'),
            route('sisa-display.index'),
            route('reports.stock-history'), route('reports.daily-stock'), route('reports.stock-movement'),
        ] as $url) {
            $this->actingAs($this->gudang)->get($url)->assertOk();
        }
    }

    public function test_sku_must_be_unique(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), [
                'name' => 'Duplikat', 'sku' => 'PRD001', 'carton_qty' => 1,
                'unit' => 'PCS', 'min_stock' => 0, 'status' => 'active',
            ])
            ->assertSessionHasErrors('sku');
    }

    public function test_carton_qty_cannot_be_negative(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), [
                'name' => 'Minus', 'sku' => 'PRD999', 'carton_qty' => -1,
                'unit' => 'PCS', 'min_stock' => 0, 'status' => 'active',
            ])
            ->assertSessionHasErrors('carton_qty');
    }

    public function test_inactive_product_hidden_from_transaction_product_lists(): void
    {
        $this->product->update(['status' => 'inactive']);

        $this->actingAs($this->gudang)->get(route('stock-outs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('StockOuts/Index')
                ->where('products', []));
    }

    public function test_backdated_out_recalculates_following_days(): void
    {
        $day0 = today()->subDays(2)->toDateString();
        $day1 = today()->subDay()->toDateString();

        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => $day0,
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => $day0,
        ]);
        $this->actingAs($this->gudang)->post(route('stock-outs.store'), [
            'product_id' => $this->product->id, 'qty' => 5, 'trx_date' => $day1,
        ]);

        // Backdated OUT on day0
        $this->actingAs($this->gudang)->post(route('stock-outs.store'), [
            'product_id' => $this->product->id, 'qty' => 10, 'trx_date' => $day0,
        ]);

        $row0 = $this->day($day0);
        $this->assertNotNull($row0);
        $this->assertEquals(10, $row0->out_qty);
        $this->assertEquals(40, $row0->closing_qty);

        $row1 = $this->day($day1);
        $this->assertNotNull($row1);
        $this->assertEquals(40, $row1->opening_qty);
        $this->assertEquals(5, $row1->out_qty);
        $this->assertEquals(35, $row1->closing_qty);
    }

    public function test_product_used_in_transaction_cannot_be_deleted(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 100, 'trx_date' => today()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('products.destroy', $this->product))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $this->product->id]);
    }

    public function test_sisa_display_input_creates_out_for_difference(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => today()->toDateString(),
        ]);

        // Sisa fisik 40 dari display 50 → OUT 10
        $this->actingAs($this->gudang)->post(route('sisa-display.store'), [
            'product_id' => $this->product->id, 'qty' => 40, 'trx_date' => today()->toDateString(),
        ])->assertRedirect();

        $day = $this->day(today()->toDateString());
        $this->assertEquals(10, $day->out_qty);
        $this->assertEquals(40, $day->closing_qty);

        $this->assertDatabaseHas('stock_outs', [
            'trx_no' => 'OUT-'.today()->format('Ymd').'-001',
            'qty' => 10,
            'note' => 'Sisa display diinput manual',
        ]);
        $this->assertDatabaseHas('main_stocks', ['product_id' => $this->product->id, 'qty' => 450]);
    }

    public function test_sisa_display_equal_balance_creates_no_out(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => today()->toDateString(),
        ]);

        $this->actingAs($this->gudang)->post(route('sisa-display.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => today()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseCount('stock_outs', 0);
        $day = $this->day(today()->toDateString());
        $this->assertEquals(0, $day->out_qty);
        $this->assertEquals(50, $day->closing_qty);
    }

    public function test_sisa_display_cannot_exceed_display(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 50, 'trx_date' => today()->toDateString(),
        ]);

        $this->actingAs($this->gudang)
            ->post(route('sisa-display.store'), [
                'product_id' => $this->product->id, 'qty' => 60, 'trx_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('qty');

        $this->assertDatabaseCount('stock_outs', 0);
        $day = $this->day(today()->toDateString());
        $this->assertEquals(50, $day->closing_qty);
    }

    public function test_dashboard_shows_low_and_out_of_stock_counts(): void
    {
        $this->actingAs($this->gudang)->post(route('main-stocks.store'), [
            'product_id' => $this->product->id, 'qty' => 500, 'trx_date' => today()->toDateString(),
        ]);
        $this->actingAs($this->gudang)->post(route('stock-transfers.store'), [
            'product_id' => $this->product->id, 'qty' => 15, 'trx_date' => today()->toDateString(),
        ]);

        $this->actingAs($this->gudang)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('cards.low_stock', 1)
                ->where('cards.out_of_stock', 0));
    }
}
