<?php

declare(strict_types=1);

namespace App\Utility;

class Random
{
    public static function getFloat(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
