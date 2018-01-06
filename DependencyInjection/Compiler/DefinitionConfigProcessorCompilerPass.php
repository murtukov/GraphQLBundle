<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DefinitionConfigProcessorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition(ConfigProcessor::class);
        $taggedServices = $container->findTaggedServiceIds('overblog_graphql.definition_config_processor');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addConfigProcessor',
                [
                    new Reference($id),
                    isset($tags[0]['priority']) ? $tags[0]['priority'] : 0,
                ]
            );
        }
    }
}
