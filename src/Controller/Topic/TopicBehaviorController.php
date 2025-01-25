<?php

declare(strict_types=1);

namespace App\Controller\Topic;

use App\Attribute\RelativeToEntity;
use App\Attribute\Resource;
use App\Controller\AbstractRestController;
use App\Entity\Session;
use App\Entity\Topic;
use App\Entity\User;
use App\Enum\SettingName;
use App\Repository\FlashcardRepository;
use App\Repository\ReviewRepository;
use App\Utility\Regex;
use App\Voter\TopicVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route(name: 'api_', format: 'json')]
#[RelativeToEntity(Topic::class)]
class TopicBehaviorController extends AbstractRestController
{
    #[Route('/topics/{id}/reset', name: 'reset_topic', methods: ['POST'], requirements: ['id' => Regex::INTEGER])]
    public function resetTopic(
        ReviewRepository $reviewRepository,
        FlashcardRepository $flashcardRepository,
        #[CurrentUser] User $user,
        #[Resource(TopicVoter::OWNER)] Topic $topic,
    ): JsonResponse {
        $reviewRepository->resetBy($user, $topic);
        $flashcardRepository->resetBy($user, $topic);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/topics/{id}/session', name: 'topic_session', methods: ['GET'], requirements: ['id' => Regex::INTEGER])]
    public function getTopicSession(
        FlashcardRepository $flashcardRepository,
        EntityManagerInterface $em,
        #[CurrentUser] User $user,
        #[Resource(TopicVoter::OWNER)] Topic $topic,
    ): JsonResponse {
        $cardsToReview = $flashcardRepository->findFlashcardToReviewBy($topic, $user, $this->getUserSetting(SettingName::FLASHCARD_PER_SESSION));

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
