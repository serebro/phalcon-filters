<?php

namespace Serebro\Phalcon\Filter;

/*
	Название:	 	PHP класс для фильтрации HTML кода
	Описание:		http://savvateev.org/blog/36/
	Автор: 			Олег Савватеев (http://savvateev.org)
	Лицензия:		MIT License
	Версия:			1.0.1 от 11.04.2011
*/

/**
 * Class Html
 *
 * Using:
 *
 *      $filter = new \Phalcon\Filter();
 *      $htmlFilter = new \Serebro\Phalcon\Filter\Html();
 *      $htmlFilter->delInvalidTags(true)->setTags([
 *          'h1' => ['id', 'class'],
 *          'h2' => ['id', 'class'],
 *          'h3' => ['id', 'class'],
 *          'h4' => ['id', 'class'],
 *          'h5' => ['id', 'class'],
 *          'h6' => ['id', 'class'],
 *          'p' => ['id', 'class'],
 *          'b' => ['class'],
 *          'span' => ['id', 'class'],
 *          'a' => ['id', 'class', 'href'],
 *          'img' => ['id', 'class', 'src', 'alt', false],
 *          'br' => [false],
 *          'hr' => [false],
 *          'pre' => ['id', 'class'],
 *          'code' => ['id', 'class'],
 *          'ul' => ['id', 'class'],
 *          'ol' => ['id', 'class'],
 *          'li' => ['id', 'class'],
 *          'table' => ['id', 'class'],
 *          'tr' => ['id', 'class'],
 *          'td' => ['id', 'class'],
 *          'th' => ['id', 'class'],
 *          'thead' => ['id', 'class'],
 *          'tbody' => ['id', 'class'],
 *          'tfoot' => ['id', 'class'],
 *          'cut' => ['text', false],
 *          'video' => []
 *      ]);
 *      $filter->add('html', $htmlFilter);
 *
 * @package Serebro\Phalcon\Filter
 */
class Html
{

    private $tags = [];

    private $del_tags = false;

    /**
     * Этот метод устанавливает тэги для фильтрации
     * @param array $arr
     * @return $this
     */
    public function setTags(array $arr)
    {
        if (!is_array($arr)) {
            $arr = [];
        }
        foreach ($arr as $key => $value) {
            for ($i = 0; $i < count($value); $i++) {
                $arr[$key][$i] = strtolower($arr[$key][$i]);
            }
        }
        $this->tags = array_change_key_case($arr);

        return $this;
    }

    /**
     * Этот метод устанавливает способ обработки недопустимых тэгов. По умолчанию тэги экранируются
     * @param bool $value
     * @return $this
     */
    public function delInvalidTags($value)
    {
        $this->del_tags = !!$value;

        return $this;
    }

    /**
     * Этот метод фильтрует код
     * @param string $html
     * @return string
     */
    public function filter($html)
    {
        $open_tags_stack = [];
        $code = false;

        //Разбиваем полученный код на учатки простого текста и теги
        $seg = [];
        while (preg_match('/<[^<>]+>/siu', $html, $matches, PREG_OFFSET_CAPTURE)) {
            if ($matches[0][1]) {
                $seg[] = ['seg_type' => 'text', 'value' => substr($html, 0, $matches[0][1])];
            }
            $seg[] = ['seg_type' => 'tag', 'value' => $matches[0][0]];
            $html = substr($html, $matches[0][1] + strlen($matches[0][0]));
        }
        if ($html != '') {
            $seg[] = ['seg_type' => 'text', 'value' => $html];
        }

        //Обрабатываем полученные участки
        for ($i = 0; $i < count($seg); $i++) {

            //Если участок является простым текстом экранируем в нем спец. символы HTML
            if ($seg[$i]['seg_type'] == 'text') {
                $seg[$i]['value'] = htmlentities($seg[$i]['value'], ENT_QUOTES, 'UTF-8');
            } //Если участок является тэгом...
            elseif ($seg[$i]['seg_type'] == 'tag') {

                //находим тип тэга(открывающий/закрывающий), имя тэга, строку атрибутов
                preg_match('#^<\s*(/)?\s*([a-z0-9]+)(.*?)>$#siu', $seg[$i]['value'], $matches);
                $matches[1] ? $seg[$i]['tag_type'] = 'close' : $seg[$i]['tag_type'] = 'open';
                $seg[$i]['tag_name'] = strtolower($matches[2]);

                if (($seg[$i]['tag_name'] == 'code') && ($seg[$i]['tag_type'] == 'close')) {
                    $code = false;
                }

                //Если этот тэг находится внутри конструкции <code></code> рассматриваем его не как тэг, а как простой текст
                if ($code) {
                    $seg[$i]['seg_type'] = 'text';
                    $i--;
                    continue;
                }

                //если тэг открывающий
                if ($seg[$i]['tag_type'] == 'open') {

                    //если тэг недопустимый экранируем/удаляем его
                    if (!array_key_exists($seg[$i]['tag_name'], $this->tags)) {
                        if ($this->del_tags) {
                            $seg[$i]['action'] = 'del';
                        } else {
                            $seg[$i]['seg_type'] = 'text';
                            $i--;
                            continue;
                        }
                    } //если допустимый
                    else {

                        //находим атрибуты и оставляем только допустимые
                        preg_match_all('#([a-z]+)\s*=\s*([\'\"])\s*(.*?)\s*\2#siu', $matches[3], $attr_m,
                            PREG_SET_ORDER);
                        $attr = [];
                        foreach ($attr_m as $arr) {
                            if (in_array(strtolower($arr[1]), $this->tags[$seg[$i]['tag_name']])) {
                                $attr[strtolower($arr[1])] = htmlentities($arr[3], ENT_QUOTES, 'UTF-8');
                            }
                        }
                        $seg[$i]['attr'] = $attr;

                        if ($seg[$i]['tag_name'] == 'code') {
                            $code = true;
                        }

                        //если тэг требует закрывающего тэга заносим в стек открывающих тэгов
                        if (!count($this->tags[$seg[$i]['tag_name']]) || ($this->tags[$seg[$i]['tag_name']][count($this->tags[$seg[$i]['tag_name']]) - 1] != false)) {
                            array_push($open_tags_stack, $seg[$i]['tag_name']);
                        }
                    }
                } //если тэг закрывающий
                else {

                    //если тэг допустимый...
                    if (array_key_exists($seg[$i]['tag_name'],
                            $this->tags) && (!count($this->tags[$seg[$i]['tag_name']]) || ($this->tags[$seg[$i]['tag_name']][count($this->tags[$seg[$i]['tag_name']]) - 1] != false))
                    ) {

                        if ($seg[$i]['tag_name'] == 'code') {
                            $code = false;
                        }

                        //если стек открывающих тэгов пуст экранируем/удаляем этот тэг
                        //...или в нем нет тэга с таким именем
                        if ((count($open_tags_stack) == 0) || (!in_array($seg[$i]['tag_name'], $open_tags_stack))) {
                            if ($this->del_tags) {
                                $seg[$i]['action'] = 'del';
                            } else {
                                $seg[$i]['seg_type'] = 'text';
                                $i--;
                                continue;
                            }
                        } //в противном случае...
                        else {

                            //если этот тэг не соответствует последнему из стека открывающих тэгов добавляем правильный закрывающий тэг
                            $tn = array_pop($open_tags_stack);
                            if ($seg[$i]['tag_name'] != $tn) {
                                array_splice($seg, $i, 0, [
                                    [
                                        'seg_type' => 'tag',
                                        'tag_type' => 'close',
                                        'tag_name' => $tn,
                                        'action' => 'add'
                                    ]
                                ]);
                            }
                        }
                    } //если тэг недопустимый удаляем его
                    else {
                        if ($this->del_tags) {
                            $seg[$i]['action'] = 'del';
                        } else {
                            $seg[$i]['seg_type'] = 'text';
                            $i--;
                            continue;
                        }
                    }
                }
            }
        }

        //Закрываем оставшиеся в стеке тэги
        foreach (array_reverse($open_tags_stack) as $value) {
            array_push($seg, ['seg_type' => 'tag', 'tag_type' => 'close', 'tag_name' => $value, 'action' => 'add']);
        }

        //Собираем профильтрованный код и возвращаем его
        $filtered_HTML = '';
        foreach ($seg as $segment) {
            if ($segment['seg_type'] == 'text') {
                $filtered_HTML .= $segment['value'];
            } elseif (($segment['seg_type'] == 'tag') && (!isset($segment['action']) || $segment['action'] != 'del')) {
                if ($segment['tag_type'] == 'open') {
                    $filtered_HTML .= '<' . $segment['tag_name'];
                    if (is_array($segment['attr'])) {
                        foreach ($segment['attr'] as $attr_key => $attr_val) {
                            $filtered_HTML .= ' ' . $attr_key . '="' . $attr_val . '"';
                        }
                    }
                    if (count($this->tags[$segment['tag_name']]) && ($this->tags[$segment['tag_name']][count($this->tags[$segment['tag_name']]) - 1] == false)) {
                        $filtered_HTML .= " /";
                    }
                    $filtered_HTML .= '>';
                } elseif ($segment['tag_type'] == 'close') {
                    $filtered_HTML .= '</' . $segment['tag_name'] . '>';
                }
            }
        }

        return $filtered_HTML;
    }
}