<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\AbstractRestController;
use App\Entity\User;
use App\Repository\ReviewRepository;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', 'api_', format: 'json')]
class ReviewController extends AbstractRestController
{
    #[Route('/reviews/count', name: 'get_reviews', methods: ['GET'])]
    public function getCountReviews(ReviewRepository $reviewRepository)
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->jsonStd([
            'actual' => $reviewRepository->countReviews($user, false),
            'total' => $reviewRepository->countReviews($user, true),
        ]);
    }
}
