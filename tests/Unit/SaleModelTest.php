<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(BelongsTo::class, $sale->user());
        $this->assertTrue($sale->user->is($user));
    }

    public function test_sale_belongs_to_customer_nullable(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new Sale)->customer());
    }

    public function test_sale_customer_with_default_returns_walk_in_when_null(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id, 'customer_id' => null]);

        $this->assertEquals('Walk-in / No Customer', $sale->customer->name);
    }

    public function test_sale_fillable_attributes(): void
    {
        $sale = new Sale;
        $fillable = $sale->getFillable();

        $this->assertContains('customer_id', $fillable);
        $this->assertContains('sale_date', $fillable);
        $this->assertContains('dozen_count', $fillable);
        $this->assertContains('individual_count', $fillable);
        $this->assertContains('total_amount', $fillable);
        $this->assertContains('paid', $fillable);
        $this->assertContains('notes', $fillable);
        $this->assertNotContains('user_id', $fillable);
    }

    public function test_sale_casts_paid_to_boolean(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->paid()->create(['user_id' => $user->id]);

        $this->assertIsBool($sale->paid);
        $this->assertTrue($sale->paid);
    }

    public function test_sale_casts_sale_date_to_date(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $sale->sale_date);
    }

    public function test_sale_paid_scope_returns_only_paid(): void
    {
        $user = User::factory()->create();
        Sale::factory()->paid()->count(3)->create(['user_id' => $user->id]);
        Sale::factory()->unpaid()->count(2)->create(['user_id' => $user->id]);

        $paid = $user->sales()->paid()->get();

        $this->assertCount(3, $paid);
        $paid->each(fn ($s) => $this->assertTrue($s->paid));
    }

    public function test_sale_unpaid_scope_returns_only_unpaid(): void
    {
        $user = User::factory()->create();
        Sale::factory()->paid()->count(2)->create(['user_id' => $user->id]);
        Sale::factory()->unpaid()->count(3)->create(['user_id' => $user->id]);

        $unpaid = $user->sales()->unpaid()->get();

        $this->assertCount(3, $unpaid);
        $unpaid->each(fn ($s) => $this->assertFalse($s->paid));
    }
}
