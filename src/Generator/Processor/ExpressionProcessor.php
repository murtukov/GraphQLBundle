<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Processor;

use Overblog\GraphQLBundle\ExpressionLanguage\Expression;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use function array_map;
use function is_array;
use function is_string;
use function strpos;
use function substr;

final class ExpressionProcessor
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    public function process(array $configs): array
    {
        return array_map(function ($v) {
            if (is_array($v)) {
                return $this->process($v);
            } elseif (is_string($v) && 0 === strpos($v, ExpressionLanguage::EXPRESSION_TRIGGER)) {
                return new Expression($this->expressionLanguage, substr($v, 2));
            }

            return $v;
        }, $configs);
    }
}
