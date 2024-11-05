<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Enum\JsonStandardStatus;
use App\Model\ErrorStandard;
use App\Model\JsonStandard;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        $error = new ErrorStandard(
            Response::$statusTexts[$statusCode],
            $exception->getMessage(),
            $exception->getTraceAsString()
        );

        $response = new JsonResponse(
            $this->normalizer->normalize(
                new JsonStandard($error, JsonStandardStatus::INVALID),
                'json',
            ),
            $statusCode
        );

        $this->logger->error($exception);

        $event->setResponse($response);
        $event->stopPropagation();
    }
}
