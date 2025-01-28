<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Session;
use App\Entity\User;
use App\Hydrator\VirtualHydrator;
use App\Model\Filter;
use App\Model\Page;
use App\Model\Paginator;
use App\Model\Period;
use App\Model\Streak;
use App\Trait\UseRepositoryExtension;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionRepository extends ServiceEntityRepository
{
    use UseRepositoryExtension;

    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Session::class);
    }

    public function paginateAndFilterAll(Page $page, ?Filter $filter, ?User $user = null)
    {
        $query = $this->createQueryBuilder('s')
            ->select('s, COUNT(r.id) as totalReviews')
            ->leftJoin('s.reviews', 'r')
            ->addGroupBy('s.id');

        if ($user !== null) {
            $query
                ->where('s.author = :user')
                ->setParameter('user', $user);
        }

        if ($filter !== null) {
            $this->addFilter($query, 's', $filter);
        }

        $this->addSort($query, 's', $page);

        return new Paginator($query, $page, VirtualHydrator::class);
    }

    public function countAll(?User $user, ?Period $period): int
    {
        $query = $this->createQueryBuilder('s')
            ->select('count(s.id)');

        if ($user !== null) {
            $query
                ->where('s.author = :user')
                ->setParameter('user', $user);
        }

        if ($period !== null) {
            $query
                ->andWhere('s.startedAt BETWEEN :start AND :end')
                ->setParameter('start', $period->start)
                ->setParameter('end', $period->end);
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    public function countAllByDate(User $user, ?Period $period)
    {
        $query = $this->createQueryBuilder('s')
            ->select('DATE(s.startedAt) AS date, count(s.id) total')
            ->where('s.author = :user')
            ->groupBy('date')
            ->setParameter('user', $user);

        if ($period !== null) {
            $query
                ->andWhere('s.startedAt BETWEEN :start AND :end')
                ->setParameter('start', $period->start)
                ->setParameter('end', $period->end);
        }

        return $query
            ->getQuery()
            ->getResult();
    }

    public function getStreak(User $user, bool $withReset = false)
    {
        $query = $this->createQueryBuilder('s')
            ->select('DATE(s.startedAt) AS date') // Select only the date
            ->distinct()
            ->leftJoin('s.reviews', 'r') // Join with the reviews table
            ->where('s.author = :user') // Filter by the author
            ->andWhere('r.id IS NOT NULL') // Ensure at least one review exists
            ->setParameter('user', $user); // Bind the user parameter

        if (!$withReset) {
            $query
                ->andWhere('r.reset = :reset') // And the review is not a reset
                ->setParameter('reset', false); // Bind the reset parameter;
        }

        $datesRaw = $query->getQuery()->getResult();

        if (count($datesRaw) === 0) {
            return new Streak(current: 0, longest: 0, inDanger: true);
        }

        $dates = array_map(fn ($item) => new DateTimeImmutable($item['date']), $datesRaw);
        usort($dates, fn ($a, $b) => $a <=> $b);

        $previousDate = null;
        $longestStreak = 0;
        $currentStreak = 0;
        $currentPeriod = 0;
        $today = new DateTimeImmutable();
        $streakIncludesToday = false;

        foreach ($dates as $date) {
            if ($previousDate) {
                $interval = $date->diff($previousDate)->days;

                if ($interval === 1) { // Consecutive day
                    $currentPeriod++;
                } else { // Streak breaks
                    $longestStreak = max($longestStreak, $currentPeriod);
                    $currentPeriod = 1; // Reset streak
                }
            } else {
                $currentPeriod = 1; // First date starts the streak
            }

            // Check if the current streak includes today or yesterday
            if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
                $streakIncludesToday = true;
                $currentStreak = $currentPeriod;
            } elseif ($date->format('Y-m-d') === $today->modify('-1 day')->format('Y-m-d') && !$streakIncludesToday) {
                $streakIncludesToday = true;
                $currentStreak = $currentPeriod;
            }

            $previousDate = $date;
        }

        // Final check to update the longest streak
        $longestStreak = max($longestStreak, $currentPeriod);

        // If today or yesterday doesn't continue the streak, set current streak to 0
        if (!$streakIncludesToday) {
            $currentStreak = 0;
        }

        return new Streak(
            current: $currentStreak,
            longest: $longestStreak,
            inDanger: end($dates)?->format('Y-m-d') !== $today->format('Y-m-d'),
        );
    }

    public function getTotalSecondesInPractice(User $user)
    {
        /** @var Period[] $periods */
        $periods = $this->createQueryBuilder('s')
            ->select(sprintf(
                'NEW %s(s.startedAt, s.endedAt)',
                Period::class
            ))
            ->where('s.author = :user')
            ->andWhere('s.endedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $totalSeconds = array_reduce(
            $periods,
            fn ($carry, $item) => $carry + max(0, $item->end->getTimestamp() - $item->start->getTimestamp()),
            0
        );

        return $totalSeconds;
    }
}
