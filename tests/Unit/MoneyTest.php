<?php

namespace Tests\Unit;

use App\Services\Commission;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_commission_is_calculated_in_integer_minor_units(): void
    {
        // ₦1,000,000 at 15% → ₦150,000 commission, ₦850,000 net (the PRD example).
        $split = Commission::split(100_000_000, 1500);
        $this->assertSame(15_000_000, $split['commission']);
        $this->assertSame(85_000_000, $split['net']);
        $this->assertSame($split['gross'], $split['commission'] + $split['net']);
    }

    public function test_commission_rounds_half_up_to_the_nearest_kobo(): void
    {
        $this->assertSame(2, Commission::of(10, 1500));   // 1.5 kobo → 2
        $this->assertSame(1, Commission::of(7, 1500));    // 1.05 → 1
        $this->assertSame(0, Commission::of(100, 0));
    }

    public function test_to_minor_parses_user_input_without_floats(): void
    {
        require_once __DIR__.'/../../app/helpers.php';
        $this->assertSame(15000050, to_minor('150,000.50'));
        $this->assertSame(10, to_minor('0.1'));
        $this->assertSame(30, to_minor('0.3'));
        $this->assertNull(to_minor(''));
        $this->assertNull(to_minor('1.2.3'));
        $this->assertSame('1500.5', rtrim(to_major(150050), '0'));
    }
}
