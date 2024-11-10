<?php

declare(strict_types=1);

namespace App\Controller\Flashcard;

use App\Controller\AbstractRestController;
use App\Entity\User;
use App\Enum\CountCriteria\FlashcardCountCriteria;
use App\Repository\FlashcardRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', 'api_', format: 'json')]
class FlashcardScalarController extends AbstractRestController
{
    #[Route('/flashcards/count', name: 'flashcard_count', methods: ['GET'])]
    public function countFlashcards(
        FlashcardRepository $flashcardRepository,
        Request $request,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $criteria = $this->getCountCriteria($request, FlashcardCountCriteria::class, FlashcardCountCriteria::ALL->value);

        $count = match ($criteria) {
            FlashcardCountCriteria::ALL => $flashcardRepository->countAll($user),
            FlashcardCountCriteria::TO_REVIEW => $flashcardRepository->countFlashcardsToReview($user),
            FlashcardCountCriteria::CORRECT => $flashcardRepository->countCorrectFlashcards($user),
        };

        return $this->jsonStd($count);
    }

    #[Route('/flashcards/averageGrade', name: 'flashcard_average_grade', methods: ['GET'])]
    public function getAverageGrade(
        FlashcardRepository $flashcardRepository,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $count = $flashcardRepository->averageGrade($user);

        return $this->jsonStd($count);
    }
}
