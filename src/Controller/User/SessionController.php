<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\AbstractRestController;
use App\Entity\Session;
use App\Entity\User;
use App\Repository\SessionRepository;
use App\Voter\SessionVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', 'api_', format: 'json')]
class SessionController extends AbstractRestController
{
    #[Route('/sessions/{id}/stop', name: 'session_stop', methods: ['POST'])]
    public function stopSession(
        int $id,
        EntityManagerInterface $em
    ): JsonResponse {
        $session = $this->getResourceById(Session::class, $id);
        $this->denyAccessUnlessGranted(SessionVoter::OWNER, $session, 'You can not update this resource');

        $session->setEndedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->jsonStd($session, context: ['groups' => ['read:session:user']]);
    }

    #[Route('/sessions/count', name: 'sessions_count', methods: ['GET'])]
    public function countSessions(
        SessionRepository $sessionRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $count = $sessionRepository->countAll($user);

        return $this->jsonStd($count);
    }
}
