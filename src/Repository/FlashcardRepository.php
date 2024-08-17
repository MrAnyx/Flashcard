<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Flashcard;
use App\Entity\Topic;
use App\Entity\Unit;
use App\Entity\User;
use App\Enum\GradeType;
use App\Enum\StateType;
use App\Model\Page;
use App\Model\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Flashcard>
 *
 * @method Flashcard|null find($id, $lockMode = null, $lockVersion = null)
 * @method Flashcard|null findOneBy(array $criteria, array $orderBy = null)
 * @method Flashcard[] findAll()
 * @method Flashcard[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FlashcardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Flashcard::class);
    }

    public function countAll(?User $user): int
    {
        $query = $this->createQueryBuilder('f')
            ->select('count(f.id)');

        if ($user !== null) {
            $query
                ->join('f.unit', 'u')
                ->join('u.topic', 't')
                ->where('t.author = :user')
                ->setParameter('user', $user);
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    public function findAllWithPagination(Page $page, ?User $user = null): Paginator
    {
        $query = $this->createQueryBuilder('f');

        if ($user !== null) {
            $query
                ->join('f.unit', 'u')
                ->join('u.topic', 't')
                ->andWhere('t.author = :user')
                ->setParameter('user', $user);
        }

        $query
            ->addOrderBy("CASE WHEN f.{$page->sort} IS NULL THEN 1 ELSE 0 END", 'ASC') // To put null values last
            ->addOrderBy("f.{$page->sort}", $page->order);

        return new Paginator($query, $page);
    }

    public function findByUnitWithPagination(Page $page, Unit $unit): Paginator
    {
        $query = $this->createQueryBuilder('f')
            ->where('f.unit = :unit')
            ->setParameter('unit', $unit)
            ->addOrderBy("CASE WHEN f.{$page->sort} IS NULL THEN 1 ELSE 0 END", 'ASC')
            ->addOrderBy("f.{$page->sort}", $page->order);

        return new Paginator($query, $page);
    }

    public function resetBy(User $user, Flashcard|Unit|Topic|null $resetBy = null)
    {
        // On met des 2 pour les alias car sinon, il y a des conflits avec la requête principale
        $flashcardsToReset = $this->createQueryBuilder('f2')
            ->select('f2.id')
            ->join('f2.unit', 'u2')
            ->join('u2.topic', 't2')
            ->where('t2.author = :user');

        if ($resetBy instanceof Flashcard) {
            $flashcardsToReset->andWhere('f2 = :resetBy');
        } elseif ($resetBy instanceof Unit) {
            $flashcardsToReset->andWhere('u2 = :resetBy');
        } elseif ($resetBy instanceof Topic) {
            $flashcardsToReset->andWhere('u2.topic = :resetBy');
        }

        $qb = $this->createQueryBuilder('f');

        $qb
            ->update()
            ->set('f.previousReview', ':previousReview')
            ->set('f.state', ':state')
            ->set('f.nextReview', ':nextReview')
            ->set('f.difficulty', ':difficulty')
            ->set('f.stability', ':stability')
            ->andWhere($qb->expr()->in('f.id', $flashcardsToReset->getDQL()))
            ->setParameter('previousReview', null)
            ->setParameter('state', StateType::New)
            ->setParameter('nextReview', null)
            ->setParameter('difficulty', null)
            ->setParameter('stability', null);

        if ($resetBy !== null) {
            $qb->setParameter('resetBy', $resetBy);
        }

        return $qb->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function findFlashcardToReview(User $user, int $cardsToReview)
    {
        $result = $this->createQueryBuilder('f')
            ->join('f.unit', 'u')
            ->join('u.topic', 't')
            ->where('t.author = :user')
            ->andWhere('f.nextReview <= :today OR f.nextReview IS NULL')
            ->orderBy('f.nextReview', 'ASC')
            ->setMaxResults($cardsToReview)
            ->setParameter('user', $user)
            ->setParameter('today', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findFlashcardToReviewBy(Unit|Topic $reviewBy, User $user, int $cardsToReview)
    {
        $qb = $this->createQueryBuilder('f')
            ->join('f.unit', 'u')
            ->join('u.topic', 't')
            ->where('t.author = :user')
            ->andWhere('f.nextReview <= :today OR f.nextReview IS NULL');

        if ($reviewBy instanceof Unit) {
            $qb->andWhere('f.unit = :reviewBy');
        } elseif ($reviewBy instanceof Topic) {
            $qb->andWhere('u.topic = :reviewBy');
        }

        $qb->orderBy('f.nextReview', 'ASC')
            ->setMaxResults($cardsToReview)
            ->setParameter('user', $user)
            ->setParameter('today', (new \DateTimeImmutable())->format('Y-m-d'))
            ->setParameter('reviewBy', $reviewBy);

        return $qb->getQuery()->getResult();
    }

    public function countFlashcardsToReview(?User $user): int
    {
        $query = $this->createQueryBuilder('f')
            ->select('count(f.id)')
            ->where('f.nextReview < :now OR f.state = :state')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('state', StateType::New);

        if ($user !== null) {
            $query
                ->join('f.unit', 'u')
                ->join('u.topic', 't')
                ->andWhere('t.author = :user')
                ->setParameter('user', $user);
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    public function countCorrectFlashcards(?User $user): int
    {
        $query = $this->createQueryBuilder('f')
            ->select('count(f.id)')
            ->join('f.reviewHistory', 'r')
            ->where('r.grade > :threshold')
            ->setParameter('threshold', GradeType::HARD->value);

        if ($user !== null) {
            $query
                ->join('f.unit', 'u')
                ->join('u.topic', 't')
                ->andWhere('t.author = :user')
                ->setParameter('user', $user);
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    public function averageGrade(?User $user): float
    {
        $query = $this->createQueryBuilder('f')
            ->select('avg(r.grade)')
            ->join('f.reviewHistory', 'r');

        if ($user !== null) {
            $query
                ->join('f.unit', 'u')
                ->join('u.topic', 't')
                ->andWhere('t.author = :user')
                ->setParameter('user', $user);
        }

        $result = $query->getQuery()->getSingleScalarResult();

        if (is_numeric($result)) {
            return (float) $result;
        }

        return 0;
    }
}
