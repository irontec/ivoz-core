<?php

namespace Ivoz\Core\Domain\Model\Commandlog;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * @template TKey of array-key
 * @template-extends ObjectRepository<CommandlogInterface>
 * @template-extends Selectable<TKey, CommandlogInterface>
 */
interface CommandlogRepository extends ObjectRepository, Selectable
{

}
