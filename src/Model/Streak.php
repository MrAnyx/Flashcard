<?php

declare(strict_types=1);

namespace App\Model;

readonly class Streak
{
    public function __construct(
        public int $current,
        public int $longest,
        public bool $inDanger,
    ) {
    }
}
