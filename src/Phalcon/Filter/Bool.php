<?php

namespace Serebro\Phalcon\Filter;

class Bool
{

    public function filter($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
