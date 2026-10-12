<?php

namespace App\Services;

/**
 * Commission maths in integer minor units. Basis points: 1500 = 15%.
 * Rounds half-up to the nearest minor unit, in the platform's favour only at exactly .5.
 */
final class Commission
{
    public static function of(int $amountMinor, int $bps): int
    {
        return intdiv($amountMinor * $bps + 5000, 10000);
    }

    /** @return array{gross:int, commission:int, net:int} */
    public static function split(int $amountMinor, int $bps): array
    {
        $commission = self::of($amountMinor, $bps);

        return ['gross' => $amountMinor, 'commission' => $commission, 'net' => $amountMinor - $commission];
    }
}
