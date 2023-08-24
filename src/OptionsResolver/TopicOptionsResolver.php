<?php

namespace App\OptionsResolver;

use App\Repository\UserRepository;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class TopicOptionsResolver extends OptionsResolver
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function configureName(bool $isRequired = true): self
    {
        $this->setDefined('name')->setAllowedTypes('name', 'string');

        if ($isRequired) {
            $this->setRequired('name');
        }

        return $this;
    }

    public function configureAuthor(bool $isRequired = true): self
    {
        $this->setDefined('author')->setAllowedTypes('author', 'int');

        if ($isRequired) {
            $this->setRequired('author');
        }

        $this->setNormalizer('author', function (Options $options, $value) {
            $user = $this->userRepository->find($value);

            if ($user === null) {
                throw new InvalidOptionsException("User with id $value was not found");
            }

            return $user;
        });

        return $this;
    }

    public function configureAll(bool $isRequired = true): self
    {
        $this
            ->configureName($isRequired)
            ->configureAuthor($isRequired);

        return $this;
    }
}
