<?php

declare(strict_types=1);

namespace App\Controller\Flashcard;

use App\Attribute\RelativeToEntity;
use App\Attribute\Resource;
use App\Controller\AbstractRestController;
use App\Entity\Flashcard;
use App\Entity\Review;
use App\Entity\Session;
use App\Entity\User;
use App\Enum\GradeType;
use App\Enum\SettingName;
use App\Modifier\Modifier;
use App\Modifier\Transformer\EntityByIdTransformer;
use App\Modifier\Transformer\EnumTransformer;
use App\Repository\FlashcardRepository;
use App\Repository\ReviewRepository;
use App\SpacedRepetition\Fsrs4_5Algorithm;
use App\SpacedRepetition\SpacedRepetitionScheduler;
use App\Utility\Regex;
use App\Voter\FlashcardVoter;
use App\Voter\SessionVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('', 'api_', format: 'json')]
#[RelativeToEntity(Flashcard::class)]
class FlashcardBehaviorController extends AbstractRestController
{
    #[Route('/flashcards/{id}/review', name: 'review_flashcard', methods: ['POST'], requirements: ['id' => Regex::INTEGER])]
    public function reviewFlashcard(
        EntityManagerInterface $em,
        SpacedRepetitionScheduler $spacedRepetitionScheduler,
        #[Resource(FlashcardVoter::OWNER)] Flashcard $flashcard,
    ): JsonResponse {
        if ($flashcard->getNextReview() > (new \DateTimeImmutable())) {
            throw new BadRequestHttpException(\sprintf('You can not review the flashcard with id %d yet. The next review is scheduled for %s', $flashcard->getId(), $flashcard->getNextReview()->format('jS \\of F Y')));
        }

        $review = $this->decodeBody(
            classname: Review::class,
            deserializationGroups: ['write:review:user'],
            transformers: [
                new Modifier('session', EntityByIdTransformer::class, ['entity' => Session::class, 'voter' => SessionVoter::OWNER]),
                new Modifier('grade', EnumTransformer::class, ['enum' => GradeType::class]),
            ],
            validationGroups: null
        );

        if ($review->getSession()->getEndedAt() !== null) {
            throw new BadRequestHttpException(\sprintf('The session with id %d ended at %s. You can not associate a new review with this session', $review->getSession()->getId(), $review->getSession()->getEndedAt()->format('jS \\of F Y')));
        }

        $spacedRepetitionScheduler->review($flashcard, $review->getGrade(), new Fsrs4_5Algorithm());
        $this->validateEntity($flashcard);

        $review
            ->setFlashcard($flashcard)
            ->setDifficulty($flashcard->getDifficulty())
            ->setStability($flashcard->getStability());

        $this->validateEntity($review);
        $em->persist($review);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/flashcards/reset', name: 'reset_all_flashcard', methods: ['POST'])]
    public function resetFlashcards(
        FlashcardRepository $flashcardRepository,
        ReviewRepository $reviewRepository,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $reviewRepository->resetBy($user);
        $flashcardRepository->resetBy($user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/flashcards/{id}/reset', name: 'reset_flashcard', methods: ['POST'], requirements: ['id' => Regex::INTEGER])]
    public function resetFlashcard(
        FlashcardRepository $flashcardRepository,
        ReviewRepository $reviewRepository,
        #[Resource(FlashcardVoter::OWNER)] Flashcard $flashcard,
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
    ): JsonResponse {
        $reviewRepository->resetBy($user, $flashcard);
        $flashcardRepository->resetBy($user, $flashcard);

        $em->refresh($flashcard);

        return $this->json($flashcard, context: ['groups' => ['read:flashcard:user', 'read:unit:user', 'read:topic:user']]);
    }

    #[Route('/flashcards/session', name: 'session_flashcard', methods: ['GET'])]
    public function getFlashcardSession(
        FlashcardRepository $flashcardRepository,
        EntityManagerInterface $em,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $cardsToReview = $flashcardRepository->findFlashcardToReviewBy(null, $user, $this->getUserSetting(SettingName::FLASHCARD_PER_SESSION));

        if (\count($cardsToReview) === 0) {
            return $this->json([
                'session' => null,
                'flashcards' => [],
            ]);
        }

        shuffle($cardsToReview);

        $session = new Session();
        $session->setAuthor($user);
        $this->validateEntity($session);
        $em->persist($session);
        $em->flush();

        return $this->json([
            'session' => $session,
            'flashcards' => $cardsToReview,
        ], context: ['groups' => ['read:flashcard:user', 'read:unit:user', 'read:topic:user', 'read:session:user']]);
    }
}
