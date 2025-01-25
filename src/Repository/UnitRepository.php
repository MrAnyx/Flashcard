<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Topic;
use App\Entity\Unit;
use App\Entity\User;
use App\Model\Filter;
use App\Model\Page;
use App\Model\Paginator;
use App\Trait\UseRepositoryExtension;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Unit>
 *
 * @method Unit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Unit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Unit[] findAll()
 * @method Unit[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnitRepository extends ServiceEntityRepository
{
    use UseRepositoryExtension;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Unit::class);
    }

    public function paginateAndFilterAll(Page $page, ?Filter $filter, ?User $user = null): Paginator
    {
        $query = $this->createQueryBuilder('u')
            ->select('u', 't')
            ->join('u.topic', 't');

        if ($user !== null) {
            $query
                ->where('t.author = :user')
                ->setParameter('user', $user);
        }

        if ($filter !== null) {
            $this->addFilter($query, 'u', $filter);
        }

        $this->addSort($query, 'u', $page);

        return new Paginator($query, $page);
    }

    public function paginateAndFilterByTopic(Page $page, ?Filter $filter, Topic $topic): Paginator
    {
        $query = $this->createQueryBuilder('u')
            ->select('u', 't')
            ->join('u.topic', 't')
            ->where('u.topic = :topic')
            ->setParameter('topic', $topic);

        if ($filter !== null) {
            $this->addFilter($query, 'u', $filter);
        }

        $this->addSort($query, 'u', $page);

        return new Paginator($query, $page);
    }

    public function findRecentUnitsByTopic(User $user, ?Topic $topic, int $maxResults = 4): array
    {
        $query = $this->createQueryBuilder('u')
            ->select('u', 't', 'COUNT(r.id) AS HIDDEN nbReviews')
            ->join('u.topic', 't')
            ->join('u.flashcards', 'f')
            ->join('f.reviewHistory', 'r')
            ->where('t.author = :user')
            ->andWhere('r.reset = :reset')
            ->groupBy('u.id', 't.id', 'r.date')
            ->orderBy('DATE(r.date)', 'DESC')
            ->addOrderBy('nbReviews', 'DESC')
            ->addOrderBy('r.date', 'DESC')
            ->setMaxResults($maxResults)
            ->setParameter('user', $user)
            ->setParameter('reset', false);

        if ($topic !== null) {
            $query
                ->andWhere('t = :topic')
                ->setParameter('topic', $topic);
        }

        return $query->getQuery()->getResult();
    }

    public function countAll(?User $user): int
    {
        $query = $this->createQueryBuilder('u')
            ->select('count(u.id)');

        if ($user !== null) {
            $query
                ->join('u.topic', 't')
                ->where('t.author = :user')
                ->setParameter('user', $user);
        }

        return $query->getQuery()->getSingleScalarResult();
    }
}
