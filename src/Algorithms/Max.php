<?php

/**
 * max function
 * 
 * max :: [a, b] -> b
 * @package bingo-functional
 * @author Lochemem Bruno Michael
 * @license Apache-2.0
 */

namespace Chemem\Bingo\Functional\Algorithms;

const max = 'Chemem\\Bingo\\Functional\\Algorithms\\max';

function max(array $collection) : int
{
    $maxVal = isArrayOf($collection) == 'integer' ? 
        fold(function ($acc, $val) { return $val > $acc ? $val : $acc; }, $collection, 0) :
        identity(0);
        
    return $maxVal;
}