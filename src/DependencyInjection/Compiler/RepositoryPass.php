<?php
declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\EventSourcing\Aggregate\AggregateRepository;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RepositoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $repositories = $container->findTaggedServiceIds(ProophEventStoreExtension::TAG_REPOSITORY);

        foreach ($repositories as $id => $repository) {
            $definition = $container->getDefinition($id);

            $reflClass = new ReflectionClass($definition->getClass());
            if (! $reflClass->isSubclassOf(AggregateRepository::class)) {
                throw new RuntimeException(sprintf(
                    'Tagged service "%s" must extend "%s"',
                    $id,
                    AggregateRepository::class
                ));
            }

            $tags = $definition->getTag(ProophEventStoreExtension::TAG_PROJECTION);
            foreach ($tags as $tag) {

                $repositoryConfig['repository_class'],
                            new Reference($eventStoreId),
                            $repositoryConfig['aggregate_type'],
                            new Reference($repositoryConfig['aggregate_translator']),
                            $repositoryConfig['snapshot_store'] ? new Reference($repositoryConfig['snapshot_store']) : null,
                            $repositoryConfig['stream_name'],
                            $repositoryConfig['one_stream_per_aggregate'],

                if (! isset($tag['projection_name'])) {
                    throw new RuntimeException(sprintf('"projection_name" argument is missing from on "prooph_event_store.projection" tagged service "%s"',
                        $id));
                }

                if (! isset($tag['projection_manager'])) {
                    throw new RuntimeException(sprintf('"projection_manager" argument is missing from on "prooph_event_store.projection" tagged service "%s"',
                        $id));
                }

                if (in_array(ReadModelProjection::class, class_implements($definition->getClass()))) {
                    if (! isset($tag['read_model'])) {
                        throw new RuntimeException(sprintf('"read_model" argument is missing from on "prooph_event_store.projection" tagged service "%s"',
                            $id));
                    }
                    $container->setAlias(
                        sprintf('%s.%s.read_model', ProophEventStoreExtension::TAG_PROJECTION, $tag['projection_name']),
                        $tag['read_model']
                    );
                }

                //alias definition for using the correct ProjectionManager
                $container->setAlias(
                    sprintf('%s.%s.projection_manager', ProophEventStoreExtension::TAG_PROJECTION, $tag['projection_name']),
                    sprintf('prooph_event_store.projection_manager.%s', $tag['projection_manager'])
                );

                if ($id !== sprintf('%s.%s', ProophEventStoreExtension::TAG_PROJECTION, $tag['projection_name'])) {
                    $container->setAlias(sprintf('%s.%s', ProophEventStoreExtension::TAG_PROJECTION, $tag['projection_name']), $id);
                }
            }
        }
    }
}
