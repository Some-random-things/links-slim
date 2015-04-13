<?php
/**
 * Created by PhpStorm.
 * User: ibziy_000
 * Date: 13.04.2015
 * Time: 0:23
 */

class Link {

    public $wordLeft, $wordRight;
    public $count;

    function __construct($wordLeft, $wordRight, $count)
    {
        $this->wordLeft = $wordLeft;
        $this->wordRight = $wordRight;
        $this->count = $count;
    }

}