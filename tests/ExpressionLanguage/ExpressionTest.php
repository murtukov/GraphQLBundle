<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Generator;
use Overblog\GraphQLBundle\ExpressionLanguage\Expression;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    /**
     * @test
     * @dataProvider expressionProvider
     */
    public function expressionContainsVar(Expression $expression, bool $expectedResult): void
    {
        $result = $expression->containsVar('validator');

        $this->assertEquals($result, $expectedResult);
    }

    public function expressionProvider(): Generator
    {
        $el = new ExpressionLanguage();

        yield [new Expression($el, "test('default', 15.6, validator)"), true];
        yield [new Expression($el, "validator('default', 15.6)"), false];
        yield [new Expression($el, "validator('default', validator(), 15.6)"), false];
        yield [new Expression($el, "validator('default', validator(), 15.6)"), false];
        yield [new Expression($el, 'validator'), true];
    }
}
