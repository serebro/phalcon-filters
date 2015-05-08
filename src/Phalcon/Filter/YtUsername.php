<?php

namespace Serebro\Phalcon\Filter;


class YtUsername
{

    protected static $patterns = [
        ['i' => 5, 'p' => '#((http|https):\/\/|)(www\.|)youtube\.com\/(user\/)([a-zA-Z0-9_-]{1,})#'],
        ['i' => 1, 'p' => '#([a-zA-Z0-9_-]{1,})#']
    ];

    public function filter($value)
    {
        if (preg_match(self::$patterns[0]['p'], $value, $m)) {
            $value = $m[self::$patterns[0]['i']];
        } elseif (preg_match(self::$patterns[1]['p'], $value, $m)) {
            $value = $m[self::$patterns[1]['i']];
        } else {
            $value = '';
        }

        return $value;
    }
}
