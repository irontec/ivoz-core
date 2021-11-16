<?php

namespace Ivoz\Core\Application;

use Doctrine\Common\Collections\ArrayCollection;
use Ivoz\Core\Domain\Model\EntityInterface;

interface ForeignKeyTransformerInterface
{
    /**
     * @param EntityInterface|DataTransferObjectInterface|null $element
     * @param bool $persist
     */
    public function transform($element, $persist = true);

    /**
     * @param array $elements
     * @return ArrayCollection<array-key, EntityInterface>
     */
    public function transformCollection(array $elements);
}
