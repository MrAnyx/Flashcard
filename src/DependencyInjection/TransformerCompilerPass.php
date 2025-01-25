<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TransformerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $modifiers = array_merge(
            $container->findTaggedServiceIds('app.modifier.transformer'),
            $container->findTaggedServiceIds('app.modifier.mutator')
        );

        foreach ($modifiers as $id => $tags) {
            $definition = $container->getDefinition($id);
            $class = $definition->getClass();

            // Skip services without a class
            if (null === $class) {
                continue;
            }

            $definition->setPublic(true);
        }
    }
}
