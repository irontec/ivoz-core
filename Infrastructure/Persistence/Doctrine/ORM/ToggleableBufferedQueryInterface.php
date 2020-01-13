<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\ORM;

interface ToggleableBufferedQueryInterface
{
    public function enableBufferedQuery();

    public function disableBufferedQuery();
}
