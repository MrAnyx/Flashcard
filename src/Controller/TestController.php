<?php

declare(strict_types=1);

namespace App\Controller;

use App\Attribute\RelativeToEntity;
use App\Entity\Topic;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/_internal', name: 'api_', format: 'json')]
#[RelativeToEntity(Topic::class)]
class TestController extends AbstractRestController
{
    #[Route('/test', name: 'test')]
    public function index(
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        return $this->json($user, context: ['groups' => ['read:user:user']]);
    }
}
