<?php

namespace Serebro\Phalcon\Filter;

class Language
{

    protected $supported_languages = [];


    /**
     * @param array $supported_languages
     */
    public function __construct(array $supported_languages)
    {
        $this->setSupportedLanguages($supported_languages);
    }

    public function filter($value)
    {
        $value = strtolower($value);
        $languages = implode('|', $this->supported_languages);

        return preg_replace('/[^' . $languages . ']/i', '', $value);
    }

    /**
     * @return array
     */
    public function getSupportedLanguages()
    {
        return $this->supported_languages;
    }

    /**
     * @param array $supported_languages
     * @return $this
     */
    public function setSupportedLanguages(array $supported_languages)
    {
        $this->supported_languages = array_map('strtolower', $supported_languages);

        return $this;
    }
}
