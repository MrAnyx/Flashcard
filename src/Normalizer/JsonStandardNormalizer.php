<?php

declare(strict_types=1);

namespace App\Normalizer;

use App\Model\JsonStandard;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class JsonStandardNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer
    ) {
    }

    /**
     * @param JsonStandard $data
     */
    public function normalize($data, ?string $format = null, array $context = []): array
    {
        if (\is_array($data->data)) {
            $normalizedData = array_map(fn ($el) => $this->normalizer->normalize($el, $format, $context), $data->data);
        } else {
            $normalizedData = $this->normalizer->normalize($data->data, $format, $context);
        }

        return [
            '@timestamp' => $data->timestamp->format(\DateTimeImmutable::ATOM),
            '@status' => $data->status,
            '@pagination' => $data->pagination,
            'data' => $normalizedData,
        ];
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof JsonStandard;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            JsonStandard::class => true,
        ];
    }
}
