<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\UnitFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

use function Zenstruck\Foundry\Persistence\flush_after;

class UnitFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['all'];
    }

    public function getDependencies(): array
    {
        return [
            TopicFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        flush_after(function (): void {
            UnitFactory::createMany(150);
        });
    }
}
