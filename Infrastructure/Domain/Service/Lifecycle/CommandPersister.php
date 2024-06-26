<?php

namespace Ivoz\Core\Infrastructure\Domain\Service\Lifecycle;

use Ivoz\Core\Domain\Event\CommandWasExecuted;
use Ivoz\Core\Domain\Service\CommandEventSubscriber;
use Ivoz\Core\Domain\Event\EntityEventInterface;
use Ivoz\Core\Domain\Service\EntityEventSubscriber;
use Ivoz\Core\Domain\Service\EntityPersisterInterface;
use Ivoz\Core\Domain\Model\Changelog\Changelog;
use Ivoz\Core\Domain\Model\Commandlog\Commandlog;
use Psr\Log\LoggerInterface;

class CommandPersister
{
    protected $latestCommandlog;

    public function __construct(
        private CommandEventSubscriber $commandEventSubscriber,
        private EntityEventSubscriber $entityEventSubscriber,
        private EntityPersisterInterface $entityPersister,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return void
     */
    public function persistEvents()
    {
        /** @var EntityEventInterface[] $entityEvents */
        $entityEvents = $this
            ->entityEventSubscriber
            ->getEvents();

        if (empty($entityEvents)) {
            return;
        }

        $entityChangeMap = [];

        $commandNum = $this
            ->commandEventSubscriber
            ->countEvents();

        if (!$this->latestCommandlog && !$commandNum) {
            $this->registerFallbackCommand();
        }

        $command = $this
            ->commandEventSubscriber
            ->popEvent();

        if ($command) {
            $commandlog = Commandlog::fromEvent($command);
            $this->latestCommandlog = $commandlog;
            $this->entityPersister->persist($commandlog);

            $this->logger->info(
                sprintf(
                    '%s > %s::%s(%s)',
                    (new \ReflectionClass($command))->getShortName(),
                    $commandlog->getClass(),
                    $commandlog->getMethod(),
                    json_encode($commandlog->getArguments())
                )
            );
        } else {
            /**
             * Command is null when first persisted entity comes from pre_persist event:
             * changelog will require to hit db twice
             */
            $this
                ->commandEventSubscriber
                ->getLatest();

            $commandlog = $this->latestCommandlog;
        }

        $this
            ->entityEventSubscriber
            ->clearEvents();

        foreach ($entityEvents as $event) {
            $changeLog = Changelog::fromEvent(
                $event,
                $commandlog
            );

            $entity = $changeLog->getEntity() . '#' . $changeLog->getEntityId();
            $prevEntityData = array_key_exists($entity, $entityChangeMap)
                ? $entityChangeMap[$entity]
                : [];

            if (!isset($entityChangeMap[$entity])) {
                $entityChangeMap[$entity] = [];
            }

            $currentEntityData = $changeLog->getData();
            if (!is_array($currentEntityData)) {
                $currentEntityData = [];
            }
            $dataDiff = array_diff(
                $currentEntityData,
                $prevEntityData
            );
            $entityChangeMap[$entity] = $currentEntityData;

            if (count($dataDiff) !== count($currentEntityData)) {
                $changeLog->replaceData($dataDiff);
            }

            $this->entityPersister->persist($changeLog);

            $data = json_encode($dataDiff);
            if (strlen($data) > 140) {
                $data = substr($data, 0, 140) . '...';
            }

            $this->logger->info(
                sprintf(
                    '%s > %s#%s > %s',
                    (new \ReflectionClass($event))->getShortName(),
                    $changeLog->getEntity(),
                    $changeLog->getEntityId(),
                    $data
                )
            );
        }

        $this->entityPersister->dispatchQueued();
    }

    /**
     * @return CommandWasExecuted
     * @throws \Exception
     */
    private function registerFallbackCommand(): CommandWasExecuted
    {
        $command = new CommandWasExecuted(
            '0',
            'Unregistered',
            'Unregistered',
            [],
            []
        );

        $this
            ->commandEventSubscriber
            ->handle($command);

        return $command;
    }
}
