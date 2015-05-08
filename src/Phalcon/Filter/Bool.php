<?php

namespace Serebro\Phalcon\Filter;

class Bool
{

    public function filter($value)
    {
        if (is_string($value)) {
            $value = strtolower($value);
        }

        return $value === true || $value === 'true' || $value === '1' || $value === 1 || $value === 'y' || $value === 'yes' || $value === 'on';
    }
}
