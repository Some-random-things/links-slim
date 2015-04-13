<?php
/**
 * Created by PhpStorm.
 * User: ibziy_000
 * Date: 13.04.2015
 * Time: 0:21
 */

class Word {

    public $word, $partOfSpeechLong, $partOfSpeechShort;

    function __construct($word, $partOfSpeech, $shortHandle)
    {
        $this->word = $word;
        $this->partOfSpeechLong = $partOfSpeech;
        $this->partOfSpeechShort = $shortHandle;
    }
}