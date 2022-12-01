<?php

namespace Ivoz\Core\Infrastructure\Domain\Service\Lifecycle;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Events as DbalEvents;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\{OnFlushEventArgs, PreUpdateEventArgs,};
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Ivoz\Core\Application\Helper\{EntityClassHelper, LifecycleServiceHelper,};
use Ivoz\Core\Domain\Event\{EntityWasCreated, EntityWasDeleted, EntityWasUpdated,};
use Ivoz\Core\Domain\Model\{EntityInterface, LoggableEntityInterface, LoggerEntityInterface,};
use Ivoz\Core\Domain\Service\{CommonLifecycleServiceCollection, DomainEventPublisher,};
use Ivoz\Core\Domain\Service\{LifecycleEventHandlerInterface, LifecycleServiceCollectionInterface};
use Ivoz\Core\Infrastructure\Persistence\Doctrine\{Events as CustomEvents, OnCommitEventArgs, OnErrorEventArgs};
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrineEventSubscriber implements EventSubscriber
{
    const UnaccesibleChangeset = 'Unaccesible changeset';

    protected $em;
    protected $serviceContainer;
    protected $eventPublisher;
    protected $commandPersister;
    protected $forcedEntityChangeLog;
    protected $schemaManager;

    /**
     * @var EntityInterface[]
     */
    protected $flushedEntities = [];

    public function __construct(
        ContainerInterface $serviceContainer,
        EntityManagerInterface $em,
        DomainEventPublisher $eventPublisher,
        CommandPersister $commandPersister,
        bool $forcedEntityChangeLog = false
    ) {
        $this->serviceContainer = $serviceContainer;
        $this->em = $em;
        $this->eventPublisher = $eventPublisher;
        $this->commandPersister = $commandPersister;
        $this->forcedEntityChangeLog = $forcedEntityChangeLog;
        $this->schemaManager = $em->getConnection()->createSchemaManager();
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::postPersist,

            Events::preUpdate,
            Events::postUpdate,

            Events::preRemove,
            Events::postRemove,

            Events::onFlush,

            CustomEvents::preCommit,
            CustomEvents::onCommit,
            CustomEvents::onError,
            CustomEvents::onHydratorComplete,

            DbalEvents::onSchemaColumnDefinition,
            ToolEvents::postGenerateSchema,
        ];
    }

    public function onHydratorComplete(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof EntityInterface) {
            return;
        }

        if (!$object->isInitialized()) {
            $object->initChangelog();
        }
    }

    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args)
    {
        (function () use ($args) {
            $tableColumn = $args->getTableColumn();
            unset($tableColumn['CharacterSet']);
            unset($tableColumn['Collation']);

            $column = $this->_getPortableTableColumnDefinition(
                $tableColumn
            );
            $args->setColumn($column);
            $args->preventDefault();
        })->call($this->schemaManager);

        return $args;
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args)
    {
        $schema = $args->getSchema();
        foreach ($schema->getTables() as $table) {
            foreach ($table->getColumns() as $column) {
                $platformOptions = $column->getPlatformOptions();
                unset($platformOptions['version']);
                $column->setPlatformOptions($platformOptions);
            }
        }

        return $args;
    }

    /**
     * @return void
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->run('pre_persist', $args, true);
    }

    /**
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->run('post_persist', $args, true);
    }

    /**
     * @return void
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->run('pre_persist', $args);
    }

    /**
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->run('post_persist', $args);
    }

    /**
     * @return void
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->run('pre_remove', $args);
    }

    /**
     * @return void
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $this->run('post_remove', $args);
    }

    /**
     * @return void
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->flushedEntities[] = $entity;
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->flushedEntities[] = $entity;
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->flushedEntities[] = $entity;
        }

        /** @var PersistentCollection $col */
        foreach ($uow->getScheduledCollectionDeletions() as $col) {
            foreach ($col->unwrap()->toArray() as $entity) {
                $this->flushedEntities[] = $entity;
            }
        }

        /** @var PersistentCollection $col */
        foreach ($uow->getScheduledCollectionUpdates() as $col) {
            foreach ($col->unwrap()->toArray() as $entity) {
                $this->flushedEntities[] = $entity;
            }
        }

        $uniqueObjectIds = array_unique(
            array_map('spl_object_id', $this->flushedEntities)
        );

        foreach ($this->flushedEntities as $key => $entity) {
            if (array_key_exists($key, $uniqueObjectIds)) {
                continue;
            }

            unset($this->flushedEntities[$key]);
        }
    }

    /**
     * @return void
     */
    public function preCommit()
    {
        $this
            ->commandPersister
            ->persistEvents();
    }

    /**
     * @return void
     */
    public function onCommit(OnCommitEventArgs $args)
    {
        foreach ($this->flushedEntities as $entity) {
            $this->run(
                'on_commit',
                new LifecycleEventArgs($entity, $args->getEntityManager()),
                $entity->isNew()
            );
        }

        foreach ($this->flushedEntities as $entity) {
            if (
                !property_exists($entity, '__isInitialized__')
                || !$entity->__isInitialized__
            ) {
                continue;
            }
            $entity->initChangelog();
        }

        $this->flushedEntities = [];
    }

    /**
     * @return void
     */
    public function onError(OnErrorEventArgs $args)
    {
        $this->handleError(
            $args->getEntity(),
            $args->getException()
        );
    }

    /**
     * @return void
     */
    private function run($eventName, LifecycleEventArgs $args, bool $isNew = false)
    {
        $entity = $args->getObject();
        if (!$entity instanceof EntityInterface) {
            return;
        }

        $this->triggerDomainEvents($eventName, $args, $isNew);
        $this->runSharedServices($eventName, $args);
        $this->runEntityServices($eventName, $args, $isNew);
    }

    /**
     * @return void
     */
    private function triggerDomainEvents($eventName, LifecycleEventArgs $args, bool $isNew)
    {
        $entity = $args->getObject();

        if ($entity instanceof LoggerEntityInterface) {
            return;
        }

        if (!$entity instanceof LoggableEntityInterface
            && !$this->forcedEntityChangeLog
        ) {
            return;
        }

        $event = null;

        switch ($eventName) {
            case 'pre_remove':
                // We use pre_remove because Id value is gone on post_remove
                $event = new EntityWasDeleted(
                    EntityClassHelper::getEntityClass($entity),
                    $entity->getId(),
                    null
                );

                break;
            case 'post_persist':
                $changeSet =  $entity instanceof LoggableEntityInterface
                    ? $entity->getChangeSet()
                    : [self::UnaccesibleChangeset];

                if (empty($changeSet)) {
                    return;
                }

                $eventClass = $isNew
                    ? EntityWasCreated::class
                    : EntityWasUpdated::class;

                $event = new $eventClass(
                    EntityClassHelper::getEntityClass($entity),
                    $entity->getId(),
                    $changeSet
                );

                break;
        }

        if (!is_null($event)) {
            $this->eventPublisher->publish($event);
        }
    }

    /**
     * @return void
     */
    private function runSharedServices($eventName, LifecycleEventArgs $args)
    {
        /** @var EntityInterface $entity */
        $entity = $args->getObject();

        /**
         * @var CommonLifecycleServiceCollection $service
         */
        $service = $this->serviceContainer->get(
            CommonLifecycleServiceCollection::class
        );
        $service->execute($eventName, $entity);
    }

    /**
     * @return void
     */
    private function runEntityServices($eventName, LifecycleEventArgs $args, bool $isNew)
    {
        $entity = $args->getObject();
        if ($isNew === false && $entity instanceof EntityInterface) {
            $entity->markAsPersisted();
        }

        $serviceName = LifecycleServiceHelper::getServiceNameByEntity($entity);

        if (!$this->serviceContainer->has($serviceName)) {
            return;
        }

        /**
         * @var LifecycleServiceCollectionInterface $service
         */
        $service = $this->serviceContainer->get($serviceName);

        try {
            $service->execute($eventName, $entity);
        } catch (\Exception $exception) {
            $this->handleError($entity, $exception);
        }
    }

    /**
     * @return void
     */
    private function handleError(EntityInterface $entity, \Exception $exception)
    {
        $event = LifecycleEventHandlerInterface::EVENT_ON_ERROR;
        $serviceCollection = LifecycleServiceHelper::getServiceNameByEntity($entity);
        if ($this->serviceContainer->has($serviceCollection)) {
            $errorHandler = $this->serviceContainer->get($serviceCollection);
            $errorHandler->handle($exception);
        }

        $commonErrorHandler = $this->serviceContainer->get(
            CommonLifecycleServiceCollection::class
        );
        $commonErrorHandler->handle($exception);

        throw $exception;
    }
}
