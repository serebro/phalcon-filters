<?php

namespace Serebro\Phalcon\Filter;

class GPlusId {

    protected static $patterns = [
        ['i' => 1, 'p' => '/plus\.google\.com\/.?\/?.?\/?([0-9]*)/i'],
        ['i' => 1, 'p' => '/([0-9]*)/']
    ];

	public function filter($value) {
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
