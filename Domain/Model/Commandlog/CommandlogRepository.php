<?php

namespace Ivoz\Core\Domain\Model\Commandlog;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

interface CommandlogRepository extends ObjectRepository, Selectable
{

}
