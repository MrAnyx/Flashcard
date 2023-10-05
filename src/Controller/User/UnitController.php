<?php

namespace App\Controller\User;

use Exception;
use App\Entity\Unit;
use App\Utility\Regex;
use App\Voter\UnitVoter;
use App\Voter\TopicVoter;
use App\Exception\ApiException;
use App\Repository\UnitRepository;
use App\Repository\ReviewRepository;
use App\Service\RequestPayloadService;
use App\Repository\FlashcardRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\AbstractRestController;
use App\OptionsResolver\UnitOptionsResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/api', 'api_', format: 'json')]
class UnitController extends AbstractRestController
{
    #[Route('/units', name: 'get_units', methods: ['GET'])]
    public function getAllUnits(
        Request $request,
        UnitRepository $unitRepository
    ): JsonResponse {

        $pagination = $this->getPaginationParameter(Unit::class, $request);

        $user = $this->getUser();

        // Get data with pagination
        $units = $unitRepository->findAllWithPagination(
            $pagination['page'],
            $pagination['sort'],
            $pagination['order'],
            $user
        );

        // Return paginate data
        return $this->json($units, context: ['groups' => ['read:unit:user']]);
    }

    #[Route('/units/{id}', name: 'get_unit', methods: ['GET'], requirements: ['id' => Regex::INTEGER])]
    public function getOneUnit(int $id, UnitRepository $unitRepository): JsonResponse
    {
        $unit = $this->getResourceById(Unit::class, $id);

        $this->denyAccessUnlessGranted(UnitVoter::OWNER, $unit, 'You can not access this resource');

        return $this->json($unit, context: ['groups' => ['read:unit:user']]);
    }

    #[Route('/units', name: 'create_unit', methods: ['POST'])]
    public function createUnit(
        Request $request,
        EntityManagerInterface $em,
        UnitOptionsResolver $unitOptionsResolver,
        RequestPayloadService $requestPayloadService
    ): JsonResponse {

        try {
            // Retrieve the request body
            $body = $requestPayloadService->getRequestPayload($request);

            // Validate the content of the request body
            $data = $unitOptionsResolver
                ->configureName(true)
                ->configureTopic(true)
                ->resolve($body);
        } catch (Exception $e) {
            throw new ApiException(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        $this->denyAccessUnlessGranted(TopicVoter::OWNER, $data['topic'], 'You can not use this resource');

        // Temporarly create the element
        $unit = new Unit();
        $unit
            ->setName($data['name'])
            ->setTopic($data['topic']);

        // Second validation using the validation constraints
        $this->validateEntity($unit);

        // Save the new element
        $em->persist($unit);
        $em->flush();

        // Return the element with the the status 201 (Created)
        return $this->json(
            $unit,
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('api_get_unit', ['id' => $unit->getId()])],
            ['groups' => ['read:unit:user']]
        );
    }

    #[Route('/units/{id}', name: 'delete_unit', methods: ['DELETE'], requirements: ['id' => Regex::INTEGER])]
    public function deleteUnit(int $id, UnitRepository $unitRepository, EntityManagerInterface $em): JsonResponse
    {
        $unit = $this->getResourceById(Unit::class, $id);

        $this->denyAccessUnlessGranted(UnitVoter::OWNER, $unit, 'You can not delete this resource');

        // Remove the element
        $em->remove($unit);
        $em->flush();

        // Return a response with status 204 (No Content)
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/units/{id}', name: 'update_unit', methods: ['PATCH', 'PUT'], requirements: ['id' => Regex::INTEGER])]
    public function updateUnit(
        int $id,
        UnitRepository $unitRepository,
        EntityManagerInterface $em,
        RequestPayloadService $requestPayloadService,
        Request $request,
        UnitOptionsResolver $unitOptionsResolver,
    ): JsonResponse {

        $unit = $this->getResourceById(Unit::class, $id);

        $this->denyAccessUnlessGranted(UnitVoter::OWNER, $unit, 'You can not update this resource');

        try {
            // Retrieve the request body
            $body = $requestPayloadService->getRequestPayload($request);

            // Check if the request method is PUT. In this case, all parameters must be provided in the request body.
            // Otherwise, all parameters are optional.
            $mandatoryParameters = $request->getMethod() === 'PUT';

            // Validate the content of the request body
            $data = $unitOptionsResolver
                ->configureName($mandatoryParameters)
                ->configureTopic($mandatoryParameters)
                ->resolve($body);
        } catch (Exception $e) {
            throw new ApiException(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        // Update each fields if necessary
        foreach ($data as $field => $value) {
            switch ($field) {
                case 'name':
                    $unit->setName($value);
                    break;
                case 'topic':
                    $this->denyAccessUnlessGranted(TopicVoter::OWNER, $value, 'You can not use this resource');
                    $unit->setTopic($value);
                    break;
            }
        }

        // Second validation using the validation constraints
        $this->validateEntity($unit);

        // Save the element information
        $em->flush();

        // Return the element
        return $this->json($unit, context: ['groups' => ['read:unit:user']]);
    }

    #[Route('/units/{id}/flashcards', name: 'get_flashcards_by_unit', methods: ['GET'], requirements: ['id' => Regex::INTEGER])]
    public function getUnitsFromTopic(int $id, Request $request, UnitRepository $unitRepository, FlashcardRepository $flashcardRepository): JsonResponse
    {
        $unit = $this->getResourceById(Unit::class, $id);

        $this->denyAccessUnlessGranted(UnitVoter::OWNER, $unit, 'You can not access this resource');

        $pagination = $this->getPaginationParameter(Unit::class, $request);

        // Get data with pagination
        $flashcards = $flashcardRepository->findByUnitWithPagination(
            $pagination['page'],
            $pagination['sort'],
            $pagination['order'],
            $unit
        );

        return $this->json($flashcards, context: ['groups' => ['read:flashcard:user']]);
    }

    #[Route('/units/{id}/reset', name: 'reset_unit', methods: ['PATCH'], requirements: ['id' => Regex::INTEGER])]
    public function resetUnit(
        int $id,
        UnitRepository $unitRepository,
        EntityManagerInterface $em,
        ReviewRepository $reviewRepository,
        FlashcardRepository $flashcardRepository
    ): JsonResponse {

        $unit = $this->getResourceById(Unit::class, $id);

        $this->denyAccessUnlessGranted(UnitVoter::OWNER, $unit, 'You can not update this resource');

        $reviewRepository->resetBy($unit, $this->getUser());
        $flashcardRepository->resetBy($unit, $this->getUser());

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
