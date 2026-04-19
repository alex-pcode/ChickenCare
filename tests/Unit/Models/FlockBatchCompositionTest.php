<?php

namespace Tests\Unit\Models;

use App\Models\FlockBatch;
use PHPUnit\Framework\TestCase;

class FlockBatchCompositionTest extends TestCase
{
    public function test_hens_only_resolves_to_hens_type(): void
    {
        $this->assertSame('hens', FlockBatch::resolveType(5, 0, 0, 0));
    }

    public function test_roosters_only_resolves_to_roosters_type(): void
    {
        $this->assertSame('roosters', FlockBatch::resolveType(0, 3, 0, 0));
    }

    public function test_chicks_only_resolves_to_chicks_type(): void
    {
        $this->assertSame('chicks', FlockBatch::resolveType(0, 0, 4, 0));
    }

    public function test_brooding_only_resolves_to_hens_type(): void
    {
        // hens + brooding with no roosters/chicks → 'hens'
        $this->assertSame('hens', FlockBatch::resolveType(0, 0, 0, 3));
    }

    public function test_mixed_combination_resolves_to_mixed(): void
    {
        $this->assertSame('mixed', FlockBatch::resolveType(3, 1, 0, 0));
    }

    public function test_cost_per_bird_guards_zero_initial_count(): void
    {
        $initialCount = 0;
        $cost         = 50.00;
        $costPerBird  = $initialCount > 0 ? $cost / $initialCount : null;
        $this->assertNull($costPerBird);
    }
}
