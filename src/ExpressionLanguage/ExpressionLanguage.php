<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use function array_merge;

class ExpressionLanguage extends BaseExpressionLanguage
{
    // TODO (murtukov): make names conditional
    public const KNOWN_NAMES = ['value', 'args', 'context', 'info', 'object', 'validator', 'errors', 'childrenComplexity', 'typeName', 'fieldName'];
    public const EXPRESSION_TRIGGER = '@=';

    public array $globalNames = [];

    public function addGlobalName(string $index, string $name): void
    {
        $this->globalNames[$index] = $name;
    }

    /**
     * @param string|Expression $expression
     * @param array             $names
     *
     * @return string
     */
    public function compile($expression, $names = [])
    {
        return parent::compile($expression, array_merge($names, $this->globalNames));
    }
}
