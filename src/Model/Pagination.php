<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[Groups(['read:pagination'])]
class Pagination
{
    public function __construct(
        public readonly int $total,
        public readonly int $itemsPerPage,
        public readonly int $count,
        public readonly int $totalpages,
        public readonly int $page,
        public readonly int $offset,
    ) {
    }

    #[Groups(['read:pagination'])]
    #[SerializedName('hasNextPage')]
    public function hasNextPage(): bool
    {
        return $this->page >= 1 && $this->page < $this->totalpages;
    }

    #[Groups(['read:pagination'])]
    #[SerializedName('hasPreviousPage')]
    public function hasPreviousPage(): bool
    {
        return $this->page > 1 && $this->page <= $this->totalpages;
    }
}
