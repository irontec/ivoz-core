<?php

namespace Ivoz\Core\Domain\Model\Changelog;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * @template TKey of array-key
 * @template-extends ObjectRepository<ChangelogInterface>
 * @template-extends Selectable<TKey, ChangelogInterface>
 */
interface ChangelogRepository extends ObjectRepository, Selectable
{

}
