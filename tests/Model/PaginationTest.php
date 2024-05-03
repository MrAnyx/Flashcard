<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaginationTest extends KernelTestCase
{
    private Paginator $paginator;

    protected function setUp(): void
    {
        // Create the Query object
        $em = self::getContainer()->get('doctrine')->getManager();
        $query = $em->createQueryBuilder()
            ->select('f')
            ->from(\App\Entity\Flashcard::class, 'f')
            ->getQuery();

        // Create the Paginator object
        $this->paginator = new Paginator($query, 2);
    }

    public function testTotal(): void
    {
        $this->assertIsInt($this->paginator->getTotal());
    }

    public function testData(): void
    {
        $this->assertIsArray($this->paginator->getData());
    }

    public function testCount(): void
    {
        $this->assertIsInt($this->paginator->getCount());
    }

    public function testTotalPages(): void
    {
        $this->assertIsInt($this->paginator->getTotalPages());
    }

    public function testCurrentPage(): void
    {
        $this->assertIsInt($this->paginator->getCurrentPage());
    }

    public function testOffset(): void
    {
        $this->assertIsInt($this->paginator->getOffset());
    }

    public function testItemsPerPage(): void
    {
        $this->assertIsInt($this->paginator->getItemsPerPage());
    }

    public function testHasNextPage(): void
    {
        $this->assertIsBool($this->paginator->hasNextPage());
        $this->assertTrue($this->paginator->hasNextPage());
    }

    public function testHasNextPageWithoutNextPage(): void
    {
        // Create the Query object
        $em = self::getContainer()->get('doctrine')->getManager();
        $query = $em->createQueryBuilder()
            ->select('f')
            ->from(\App\Entity\Flashcard::class, 'f')
            ->where('f.id = -1')
            ->getQuery();

        // Create the Paginator object
        $paginator = new Paginator($query);

        $this->assertIsBool($paginator->hasNextPage());
        $this->assertFalse($paginator->hasNextPage());
    }

    public function testHasPreviousPage(): void
    {
        $this->assertIsBool($this->paginator->hasPreviousPage());
        $this->assertTrue($this->paginator->hasPreviousPage());
    }

    public function testHasPreviousPageWithoutNextPage(): void
    {
        // Create the Query object
        $em = self::getContainer()->get('doctrine')->getManager();
        $query = $em->createQueryBuilder()
            ->select('f')
            ->from(\App\Entity\Flashcard::class, 'f')
            ->where('f.id = -1')
            ->getQuery();

        // Create the Paginator object
        $paginator = new Paginator($query);

        $this->assertIsBool($paginator->hasPreviousPage());
        $this->assertFalse($paginator->hasPreviousPage());
    }
}
