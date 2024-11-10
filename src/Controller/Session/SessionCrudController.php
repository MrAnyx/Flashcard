<?php

declare(strict_types=1);

namespace App\Controller\Session;

use App\Attribute\RelativeToEntity;
use App\Controller\AbstractRestController;
use App\Entity\Session;
use App\Entity\User;
use App\Model\Filter;
use App\Model\Page;
use App\Repository\SessionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', 'api_', format: 'json')]
class SessionCrudController extends AbstractRestController
{
    #[Route('/sessions', name: 'get_sessions', methods: ['GET'])]
    public function getSessions(
        SessionRepository $sessionRepository,
        #[RelativeToEntity(Session::class)] Page $page,
        #[RelativeToEntity(Session::class)] Filter $filter,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $sessions = $sessionRepository->paginateAndFilterAll($page, $filter, $user);

        return $this->jsonStd($sessions, context: ['groups' => ['read:session:user']]);
    }
}
