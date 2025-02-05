<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\DependencyInjection\Compiler;

use MakinaCorpus\DbToolsBundle\Anonymizer\Loader\AttributesLoader;
use MakinaCorpus\DbToolsBundle\Anonymizer\Loader\YamlLoader;
use MakinaCorpus\DbToolsBundle\DependencyInjection\DbToolsConfiguration;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DbToolsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $config = $this->getProcessedConfiguration($container);

        if ($container->has('db_tools.backupper.factory.registry')) {
            $definition = $container->getDefinition('db_tools.backupper.factory.registry');

            $taggedServices = $container->findTaggedServiceIds('db_tools.backupper.factory');
            foreach ($taggedServices as $id => $tags) {
                $definition->addMethodCall('register', [new Reference($id)]);
            }
        }

        if ($container->has('db_tools.restorer.factory.registry')) {
            $definition = $container->getDefinition('db_tools.restorer.factory.registry');

            $taggedServices = $container->findTaggedServiceIds('db_tools.restorer.factory');
            foreach ($taggedServices as $id => $tags) {
                $definition->addMethodCall('register', [new Reference($id)]);
            }
        }

        if ($container->has('db_tools.stats_provider.factory.registry')) {
            $definition = $container->getDefinition('db_tools.stats_provider.factory.registry');

            $taggedServices = $container->findTaggedServiceIds('db_tools.stats_provider.factory');
            foreach ($taggedServices as $id => $tags) {
                $definition->addMethodCall('register', [new Reference($id)]);
            }
        }

        if ($container->has('db_tools.anonymization.anonymizator.factory')) {
            $anonymazorFactoryDef = $container->getDefinition('db_tools.anonymization.anonymizator.factory');

            if ($container->has('doctrine.orm.command.entity_manager_provider')) {
                $loaderId = $this->registerAttributesLoader($container);
                $anonymazorFactoryDef->addMethodCall('addConfigurationLoader', [new Reference($loaderId)]);
            }

            if (isset($config['anonymization']) && isset($config['anonymization']['yaml'])) {
                foreach($config['anonymization']['yaml'] as $connectionName => $file) {
                    $loaderId = $this->registerYamlLoader($file, $connectionName, $container);
                    $anonymazorFactoryDef->addMethodCall('addConfigurationLoader', [new Reference($loaderId)]);
                }
            }
        }
    }

    private function registerYamlLoader(string $file, string $connectionName, ContainerBuilder $container): string
    {
        $definition = new Definition();
        $definition->setClass(YamlLoader::class);
        $definition->setArguments([$file, $connectionName]);

        $loaderId = 'db_tools.anonymization.loader.yaml.' . $connectionName;
        $container->setDefinition($loaderId, $definition);

        return $loaderId;
    }

    private function registerAttributesLoader(ContainerBuilder $container): string
    {
        $definition = new Definition();
        $definition->setClass(AttributesLoader::class);
        $definition->setArguments([new Reference('doctrine.orm.command.entity_manager_provider')]);

        $loaderId = 'db_tools.anonymization.loader.attributes';
        $container->setDefinition($loaderId, $definition);

        return $loaderId;
    }

    private function getProcessedConfiguration(ContainerBuilder $container)
    {
        $processor = new Processor();
        $rawConfig = $container->getExtensionConfig('db_tools');

        return $processor->processConfiguration(new DbToolsConfiguration(), $rawConfig);
    }
}
