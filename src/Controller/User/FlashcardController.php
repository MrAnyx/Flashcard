<?php

namespace App\Controller\User;

use Exception;
use App\Utility\Regex;
use App\Voter\UnitVoter;
use App\Entity\Flashcard;
use App\Voter\FlashcardVoter;
use App\Exception\ApiException;
use App\Service\RequestPayloadService;
use App\Repository\FlashcardRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\AbstractRestController;
use App\Service\SpacedRepetitionScheduler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\OptionsResolver\FlashcardOptionsResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\OptionsResolver\SpacedRepetitionOptionsResolver;

#[Route('/api', 'api_', format: 'json')]
class FlashcardController extends AbstractRestController
{
    #[Route('/flashcards', name: 'get_flashcards', methods: ['GET'])]
    public function getAllFlashcards(Request $request, FlashcardRepository $flashcardRepository): JsonResponse
    {
        $pagination = $this->getPaginationParameter(Flashcard::class, $request);

        $user = $this->getUser();

        $flashcards = $flashcardRepository->findAllWithPagination(
            $pagination['page'],
            $pagination['sort'],
            $pagination['order'],
            $user
        );

        return $this->json($flashcards, context: ['groups' => ['read:flashcard:user']]);
    }

    #[Route('/flashcards/{id}', name: 'get_flashcard', methods: ['GET'], requirements: ['id' => Regex::INTEGER])]
    public function getOneFlashcard(int $id, FlashcardRepository $flashcardRepository): JsonResponse
    {
        // Retrieve the flashcard by id
        $flashcard = $flashcardRepository->find($id);

        // Check if the flashcard exists
        if ($flashcard === null) {
            throw new ApiException(Response::HTTP_NOT_FOUND, 'Flashcard with id %d was not found', [$id]);
        }

        $this->denyAccessUnlessGranted(FlashcardVoter::OWNER, $flashcard, 'You can not access this resource');

        return $this->json($flashcard, context: ['groups' => ['read:flashcard:user']]);
    }

    #[Route('/flashcards', name: 'create_flashcard', methods: ['POST'])]
    public function createFlashcard(
        Request $request,
        EntityManagerInterface $em,
        FlashcardOptionsResolver $flashcardOptionsResolver,
        RequestPayloadService $requestPayloadService
    ): JsonResponse {

        try {
            // Retrieve the request body
            $body = $requestPayloadService->getRequestPayload($request);

            // Validate the content of the request body
            $data = $flashcardOptionsResolver
                ->configureFront(true)
                ->configureBack(true)
                ->configureDetails(true)
                ->configureUnit(true)
                ->resolve($body);
        } catch (Exception $e) {
            throw new ApiException(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        $this->denyAccessUnlessGranted(UnitVoter::OWNER, $data['unit'], 'You can not use this resource');

        // Temporarly create the flashcard
        $flashcard = new Flashcard();
        $flashcard
            ->setFront($data['front'])
            ->setBack($data['back'])
            ->setDetails($data['details'])
            ->setUnit($data['unit']);

        // Second validation using the validation constraints
        $this->validateEntity($flashcard);

        // Save the new flashcard
        $em->persist($flashcard);
        $em->flush();

        // Return the flashcard with the the status 201 (Created)
        return $this->json(
            $flashcard,
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('api_get_flashcard', ['id' => $flashcard->getId()])],
            ['groups' => ['read:flashcard:user']]
        );
    }

    #[Route('/flashcards/{id}', name: 'delete_flashcard', methods: ['DELETE'], requirements: ['id' => Regex::INTEGER])]
    public function deleteFlashcard(int $id, FlashcardRepository $flashcardRepository, EntityManagerInterface $em): JsonResponse
    {
        // Retrieve the flashcard by id
        $flashcard = $flashcardRepository->find($id);

        // Check if the flashcard exists
        if ($flashcard === null) {
            throw new ApiException(Response::HTTP_NOT_FOUND, 'Flashcard with id %d was not found', [$id]);
        }

        $this->denyAccessUnlessGranted(FlashcardVoter::OWNER, $flashcard, 'You can not delete this resource');

        // Remove the flashcard
        $em->remove($flashcard);
        $em->flush();

        // Return a response with status 204 (No Content)
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/flashcards/{id}', name: 'update_flashcard', methods: ['PATCH', 'PUT'], requirements: ['id' => Regex::INTEGER])]
    public function updateFlashcard(
        int $id,
        FlashcardRepository $flashcardRepository,
        EntityManagerInterface $em,
        RequestPayloadService $requestPayloadService,
        Request $request,
        FlashcardOptionsResolver $flashcardOptionsResolver
    ): JsonResponse {

        // Retrieve the flashcard by id
        $flashcard = $flashcardRepository->find($id);

        // Check if the flashcard exists
        if ($flashcard === null) {
            throw new ApiException(Response::HTTP_NOT_FOUND, 'Flashcard with id %d was not found', [$id]);
        }

        $this->denyAccessUnlessGranted(FlashcardVoter::OWNER, $flashcard, 'You can not update this resource');

        try {
            // Retrieve the request body
            $body = $requestPayloadService->getRequestPayload($request);

            // Check if the request method is PUT. In this case, all parameters must be provided in the request body.
            // Otherwise, all parameters are optional.
            $mandatoryParameters = $request->getMethod() === 'PUT';

            // Validate the content of the request body
            $data = $flashcardOptionsResolver
                ->configureFront($mandatoryParameters)
                ->configureBack($mandatoryParameters)
                ->configureDetails($mandatoryParameters)
                ->configureUnit($mandatoryParameters)
                ->resolve($body);
        } catch (Exception $e) {
            throw new ApiException(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        // Update each fields if necessary
        foreach ($data as $field => $value) {
            switch ($field) {
                case 'front':
                    $flashcard->setFront($value);
                    break;
                case 'back':
                    $flashcard->setBack($value);
                    break;
                case 'details':
                    $flashcard->setDetails($value);
                    break;
                case 'unit':
                    $this->denyAccessUnlessGranted(UnitVoter::OWNER, $value, 'You can not use this resource');
                    $flashcard->setUnit($value);
                    break;
            }
        }

        // Second validation using the validation constraints
        $this->validateEntity($flashcard);

        // Save the flashcard information
        $em->flush();

        // Return the flashcard
        return $this->json($flashcard, context: ['groups' => ['read:flashcard:user']]);
    }

    #[Route('/flashcards/{id}/review', name: 'review_flashcard', methods: ['PATCH'], requirements: ['id' => Regex::INTEGER])]
    public function reviewFlashcard(
        int $id,
        FlashcardRepository $flashcardRepository,
        EntityManagerInterface $em,
        RequestPayloadService $requestPayloadService,
        Request $request,
        SpacedRepetitionOptionsResolver $spacedRepetitionOptionsResolver,
        SpacedRepetitionScheduler $SpacedRepetitionScheduler
    ): JsonResponse {

        // Retrieve the flashcard by id
        $flashcard = $flashcardRepository->find($id);

        // Check if the flashcard exists
        if ($flashcard === null) {
            throw new ApiException(Response::HTTP_NOT_FOUND, 'Flashcard with id %d was not found', [$id]);
        }

        $this->denyAccessUnlessGranted(FlashcardVoter::OWNER, $flashcard, 'You can not update this resource');

        try {
            // Retrieve the request body
            $body = $requestPayloadService->getRequestPayload($request);

            // Validate the content of the request body
            $data = $spacedRepetitionOptionsResolver
                ->configureQuality()
                ->resolve($body);

            $flashcard = $SpacedRepetitionScheduler->review($flashcard, $data['quality']);
        } catch (Exception $e) {
            throw new ApiException(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        // Second validation using the validation constraints
        $this->validateEntity($flashcard);

        // Save the flashcard information
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
