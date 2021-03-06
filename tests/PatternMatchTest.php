<?php

namespace Chemem\Bingo\Functional\Tests;

use Chemem\Bingo\Functional\Algorithms as A;
use Chemem\Bingo\Functional\Functors\Monads\IO;
use Chemem\Bingo\Functional\Functors\Monads\State;
use Chemem\Bingo\Functional\PatternMatching as PM;
use PHPUnit\Framework\TestCase;

class PatternMatchTest extends TestCase
{
    public function testGetNumConditionsFunctionOutputsArrayOfArities()
    {
        $numConditions = PM\getNumConditions(['(a:b:_)', '(a:_)', '_']);

        $this->assertEquals(
            $numConditions,
            [
                '(a:b:_)' => 2,
                '(a:_)'   => 1,
                '_'       => 0,
            ]
        );
    }

    public function testMatchFunctionComputesMatches()
    {
        $match = PM\match(
            [
                '(dividend:divisor:_)' => function (int $dividend, int $divisor) {
                    return $dividend / $divisor;
                },
                '(dividend:_)' => function (int $dividend) {
                    return $dividend / 2;
                },
                '_' => function () {
                    return 1;
                },
            ]
        );

        $result = $match([10, 5]);

        $this->assertEquals($result, 2);
    }

    public function testEvalStringPatternEvaluatesStrings()
    {
        $strings = A\partialLeft(
            PM\evalStringPattern,
            [
                '"foo"' => function () {
                    return 'foo';
                },
                '"bar"' => function () {
                    return 'bar';
                },
                '_' => function () {
                    return 'undefined';
                },
            ]
        );

        $this->assertEquals($strings('foo'), 'foo');
        $this->assertEquals($strings('baz'), 'undefined');
    }

    public function testEvalStringPatternEvaluatesNumbers()
    {
        $numbers = A\partialLeft(
            PM\evalStringPattern,
            [
                '"1"' => function () {
                    return 'first';
                },
                '"2"' => function () {
                    return 'second';
                },
                '_' => function () {
                    return 'undefined';
                },
            ]
        );

        $this->assertEquals($numbers(1), 'first');
        $this->assertEquals($numbers(24), 'undefined');
        $this->assertEquals('undefined', $numbers(''));
    }

    public function testArrayPatternEvaluatesArrayPatterns()
    {
        $patterns = A\partialLeft(
            PM\evalArrayPattern,
            [
                '["foo", "bar", baz]' => function ($baz) {
                    return \strtoupper($baz);
                },
                '["foo", "bar"]' => function () {
                    return 'foo-bar';
                },
                '_' => function () {
                    return 'undefined';
                },
            ]
        );

        $this->assertEquals($patterns(['foo', 'bar']), 'foo-bar');
        $this->assertEquals($patterns(['foo', 'bar', 'cat']), 'CAT');
        $this->assertEquals($patterns([]), 'undefined');
    }

    public function testPatternMatchFunctionPerformsSingleValueSensitiveMatch()
    {
        $pattern = PM\patternMatch(
            [
                '"foo"' => function () {
                    $val = \strtoupper('FOO');

                    return $val;
                },
                '"12"' => function () {
                    return 12 * 12;
                },
                '_' => function () {
                    return 'undefined';
                },
            ],
            'foo'
        );

        $this->assertEquals($pattern, 'FOO');
    }

    public function testPatternMatchFunctionPerformsMultipleValueSensitiveMatch()
    {
        $pttn = A\partial(PM\patternMatch, [
            '[_, "book"]' => function () {
                return 'FP in PHP';
            },
            '["hello", name]' => function (string $name) {
                return A\concat(' ', 'Hello', $name);
            },
            '[a, (x:xs), b]' => function () {
                return 'multiple';
            },
            '_' => function () {
                return 'undefined';
            }
        ]);
        
        $this->assertEquals($pttn(['api', 'book']), 'FP in PHP');
        $this->assertEquals($pttn(['hello', 'World']), 'Hello World');
        $this->assertEquals($pttn([3, [5, 7], 9]), 'multiple');
        $this->assertEquals($pttn(['pennies']), 'undefined');
    }

    public function testEvalObjectPatternMatchesObjects()
    {
        $evalObject = PM\evalObjectPattern(
            [
                IO::class => function () {
                    return 'IO monad';
                },
                State::class => function () {
                    return 'State monad';
                },
                '_' => function () {
                    return 'NaN';
                },
            ],
            IO::of(function () {
                return 12;
            })
        );

        $this->assertEquals('IO monad', $evalObject);
    }

    public function testLetInDestructuresByPatternMatching()
    {
        $list = \range(1, 10);
        $let  = PM\letIn('[a, b, c, _]', $list);
        $_in  = $let(['c'], function (int $c) {
            return $c * 10;
        });

        $this->assertInstanceOf(\Closure::class, $let);
        $this->assertEquals(30, $_in);
    }

    public function testLetInFunctionAcceptsWildcardParameters()
    {
        $let    = PM\letIn('[a, _, (x:xs)]', [1, 'foo', [3, 9]]);
        $in     = $let(['x', 'xs'], function (int $fst, array $snd) {
            return A\head($snd) / $fst;
        });

        $this->assertEquals(3, $in);
        $this->assertInternalType('integer', $in);
    }
}
