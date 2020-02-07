<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\ORM;

use Doctrine\ORM\Query as DoctrineQuery;

class Query
{
    const HYDRATE_OBJECT = DoctrineQuery::HYDRATE_OBJECT;

    /**
     * Very simple object hydrator (optimized for performance).
     */
    const HYDRATE_SIMPLEOBJECT = DoctrineQuery::HYDRATE_SIMPLEOBJECT;

    const HYDRATE_DTO = 101;

    const HINT_INCLUDE_META_COLUMNS = DoctrineQuery::HINT_INCLUDE_META_COLUMNS;
}
