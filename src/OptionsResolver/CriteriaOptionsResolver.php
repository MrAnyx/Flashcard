<?php

declare(strict_types=1);

namespace App\OptionsResolver;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CriteriaOptionsResolver extends OptionsResolver
{
    /**
     * Configures the criteria option with a specified enum CriteriaOptionsResolver and default value.
     *
     * @template T
     *
     * @param class-string<T> $criteriaEnum Fully qualified class CriteriaOptionsResolver (FQCN) of the enum CriteriaOptionsResolver. The enum CriteriaOptionsResolver should implement string-backed cases.
     * @param string $defaultValue the default value for the 'criteria' option
     *
     * @return self returns the current instance of the CriteriaOptionsResolver
     */
    public function configureCriteria(string $criteriaEnum, string $defaultValue): self
    {
        return $this
            ->setDefined('criteria')
            ->setAllowedTypes('criteria', 'string')
            ->setDefault('criteria', $defaultValue)
            ->setAllowedValues('criteria', $criteriaEnum::values())
            ->setNormalizer('criteria', fn (Options $options, string $value) => $criteriaEnum::tryFrom($value));
    }
}
