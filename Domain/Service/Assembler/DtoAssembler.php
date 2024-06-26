<?php

namespace Ivoz\Core\Domain\Service\Assembler;

use Ivoz\Core\Domain\Helper\EntityClassHelper;
use Ivoz\Core\Domain\Model\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DtoAssembler
{
    /**
     * @var ContainerInterface
     */
    protected $serviceContainer;

    /**
     * @var array
     */
    protected $customAssemblers;

    public function __construct(
        ContainerInterface $serviceContainer
    ) {
        $this->serviceContainer = $serviceContainer;
        $this->customAssemblers = [];
    }

    public function toDto(EntityInterface $targetEntity, $depth = 0, string $context = null)
    {
        $assembler = $this->getAssembler($targetEntity);
        return $assembler
            ? $assembler->toDto($targetEntity, $depth, $context)
            : $targetEntity->toDto($depth);
    }

    private function getAssembler(EntityInterface $entity)
    {
        $assembler = null;
        $entityClass = EntityClassHelper::getEntityClass($entity);

        if (array_key_exists($entityClass, $this->customAssemblers)) {
            return $this->customAssemblers[$entityClass];
        }

        $assembler = $this->create($entity);
        $this->customAssemblers[$entityClass] = $assembler;

        return $assembler;
    }

    /**
     * @param EntityInterface $targetEntity
     * @return null | CustomDtoAssemblerInterface
     */
    private function create(EntityInterface $targetEntity)
    {
        $entityClass = EntityClassHelper::getEntityClass($targetEntity);
        $serviceClassName =
            str_replace(
                'Domain\\Model',
                'Domain\\Assembler',
                $entityClass
            )
            . 'DtoAssembler';

        $serviceExists = $this->serviceContainer->has($serviceClassName);
        if (!$serviceExists) {
            return null;
        }

        /** @var CustomDtoAssemblerInterface $service */
        $service = $this
            ->serviceContainer
            ->get($serviceClassName);

        return $service;
    }
}
