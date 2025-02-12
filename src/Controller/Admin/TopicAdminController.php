<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractRestController;
use App\Entity\Topic;
use App\Exception\ApiException;
use App\Repository\TopicRepository;
use App\Utility\Regex;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', 'api_admin_', format: 'json')]
class TopicAdminController extends AbstractRestController
{
    // #[Route('/topics', name: 'get_topics', methods: ['GET'])]
    // public function getAllTopics(
    //     Request $request,
    //     TopicRepository $topicRepository,
    // ): JsonResponse {
    //     $pagination = $this->getPaginationParameter(Topic::class, $request);
    //     $filter = $this->getFilterParameter(Topic::class, $request);

    //     // Get data with pagination
    //     $topics = $topicRepository->paginateAndFilterAll($pagination, $filter);

    //     // Return paginate data
    //     return $this->jsonStd($topics, context: ['groups' => ['read:topic:admin']]);
    // }

    // #[Route('/topics/{id}', name: 'get_topic', methods: ['GET'], requirements: ['id' => Regex::INTEGER])]
    // public function getOneTopic(int $id, TopicRepository $topicRepository): JsonResponse
    // {
    //     // Retrieve the element by id
    //     $topic = $topicRepository->find($id);

    //     // Check if the element exists
    //     if ($topic === null) {
    //         throw new ApiException(Response::HTTP_NOT_FOUND, 'Topic with id %d was not found', [$id]);
    //     }

    //     return $this->jsonStd($topic, context: ['groups' => ['read:topic:admin']]);
    // }

    // #[Route('/topics', name: 'create_topic', methods: ['POST'])]
    // public function createTopic(
    //     Request $request,
    //     EntityManagerInterface $em,
    //     TopicOptionsResolver $topicOptionsResolver,
    // ): JsonResponse {
    //     // Retrieve the request body
    //     $body = $this->getRequestPayload($request);

    //     try {
    //         // Validate the content of the request body
    //         $data = $topicOptionsResolver
    //             ->configureName(true)
    //             ->configureDescription(true)
    //             ->configureAuthor(true)
    //             ->configureFavorite(true)
    //             ->resolve($body);
    //     } catch (\Exception $e) {
    //         throw new ApiException(Response::HTTP_BAD_REQUEST, $e->getMessage());
    //     }

    //     // Temporarly create the element
    //     $topic = new Topic();
    //     $topic
    //         ->setName($data['name'])
    //         ->setAuthor($data['author'])
    //         ->setDescription($data['description'])
    //         ->setFavorite($data['favorite']);

    //     // Second validation using the validation constraints
    //     $this->validateEntity($topic);

    //     // Save the new element
    //     $em->persist($topic);
    //     $em->flush();

    //     // Return the element with the the status 201 (Created)
    //     return $this->jsonStd(
    //         $topic,
    //         Response::HTTP_CREATED,
    //         ['Location' => $this->generateUrl('api_admin_get_topic', ['id' => $topic->getId()])],
    //         ['groups' => ['read:topic:admin']]
    //     );
    // }

    // #[Route('/topics/{id}', name: 'delete_topic', methods: ['DELETE'], requirements: ['id' => Regex::INTEGER])]
    // public function deleteTopic(int $id, TopicRepository $topicRepository, EntityManagerInterface $em): JsonResponse
    // {
    //     // Retrieve the element by id
    //     $topic = $topicRepository->find($id);

    //     // Check if the element exists
    //     if ($topic === null) {
    //         throw new ApiException(Response::HTTP_NOT_FOUND, 'Topic with id %d was not found', [$id]);
    //     }

    //     // Remove the element
    //     $em->remove($topic);
    //     $em->flush();

    //     // Return a response with status 204 (No Content)
    //     return $this->jsonStd(null, Response::HTTP_NO_CONTENT);
    // }

    // #[Route('/topics/{id}', name: 'update_topic', methods: ['PATCH', 'PUT'], requirements: ['id' => Regex::INTEGER])]
    // public function updateTopic(
    //     int $id,
    //     TopicRepository $topicRepository,
    //     EntityManagerInterface $em,
    //     Request $request,
    //     TopicOptionsResolver $flashcardOptionsResolver,
    // ): JsonResponse {
    //     // Retrieve the element by id
    //     $topic = $topicRepository->find($id);

    //     // Check if the element exists
    //     if ($topic === null) {
    //         throw new ApiException(Response::HTTP_NOT_FOUND, 'Topic with id %d was not found', [$id]);
    //     }

    //     // Retrieve the request body
    //     $body = $this->getRequestPayload($request);

    //     try {
    //         // Check if the request method is PUT. In this case, all parameters must be provided in the request body.
    //         // Otherwise, all parameters are optional.
    //         $mandatoryParameters = $request->getMethod() === 'PUT';

    //         // Validate the content of the request body
    //         $data = $flashcardOptionsResolver
    //             ->configureName($mandatoryParameters)
    //             ->configureAuthor($mandatoryParameters)
    //             ->configureDescription($mandatoryParameters)
    //             ->configureFavorite($mandatoryParameters)
    //             ->resolve($body);
    //     } catch (\Exception $e) {
    //         throw new ApiException(Response::HTTP_BAD_REQUEST, $e->getMessage());
    //     }

    //     // Update each fields if necessary
    //     foreach ($data as $field => $value) {
    //         switch ($field) {
    //             case 'name':
    //                 $topic->setName($value);
    //                 break;
    //             case 'author':
    //                 $topic->setAuthor($value);
    //                 break;
    //             case 'description':
    //                 $topic->setDescription($value);
    //                 break;
    //             case 'favorite':
    //                 $topic->setFavorite($value);
    //                 break;
    //         }
    //     }

    //     // Second validation using the validation constraints
    //     $this->validateEntity($topic);

    //     // Save the element information
    //     $em->flush();

    //     // Return the element
    //     return $this->jsonStd($topic, context: ['groups' => ['read:topic:admin']]);
    // }
}
