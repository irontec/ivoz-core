<?php

namespace Ivoz\Core\Infrastructure\Application;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\ForeignKeyTransformerInterface;
use Ivoz\Core\Domain\Helper\EntityClassHelper;
use Ivoz\Core\Domain\Model\EntityInterface;

class DoctrineForeignKeyTransformer implements ForeignKeyTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * @param mixed $element
     * @param bool $persist
     *
     * @return ?EntityInterface
     */
    public function transform($element, $persist = true)
    {
        if (is_null($element)) {
            return null;
        }

        if ($element instanceof EntityInterface) {
            if ($persist) {
                $this->em->persist($element);
            }

            return $element;
        }

        $isDto = $element instanceof DataTransferObjectInterface;
        if (!$isDto) {
            throw new \RuntimeException("Error: DataTransferObject was expected");
        }

        $entityClass = EntityClassHelper::getEntityClassByDto($element);
        if (!is_null($element->getId())) {
            return $this->em->getReference(
                $entityClass,
                $element->getId()
            );
        }

        $entity = call_user_func(
            [$entityClass, 'fromDto'],
            $element,
            $this
        );

        if ($persist) {
            $this->em->persist($entity);
        }

        return $entity;
    }

    /**
     * @param array $elements
     * @return ArrayCollection<array-key, EntityInterface>
     */
    public function transformCollection(array $elements)
    {
        if (empty($elements)) {
            return new ArrayCollection();
        }

        $collection = new ArrayCollection();
        foreach ($elements as $element) {
            $collection->add(
                $this->transform($element, false)
            );
        }

        return $collection;
    }
}
