<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Expression as OriginalExpression;
use Symfony\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\Token;

/**
 * A modified class representing an expression. Unline the original
 * Expression class, this one can compile as well as evaluate itself.
 */
class Expression
{
    private ExpressionLanguage $expressionLanguage;
    private OriginalExpression $expression;

    /**
     * @param string|OriginalExpression $expression
     */
    public function __construct(ExpressionLanguage $expressionLanguage, $expression)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->expression = is_string($expression) ? new OriginalExpression($expression) : $expression;
    }

    public function compile(): string
    {
        return $this->expressionLanguage->compile(
            $this->expression,
            ExpressionLanguage::KNOWN_NAMES
        );
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return mixed
     */
    public function evaluate(array $values = [])
    {
        return $this->expressionLanguage->evaluate($this->expression, $values);
    }

    /**
     * Checks if expression string containst specific variable.
     *
     * Argument can be either an Expression object or a string with or
     * without a prefix
     *
     * @param string $name - name of the searched variable (needle)
     *
     * @throws SyntaxError
     */
    public function containsVar(string $name): bool
    {
        $stream = (new Lexer())->tokenize((string) $this->expression);
        $current = &$stream->current;

        while (!$stream->isEOF()) {
            if ($name === $current->value && Token::NAME_TYPE === $current->type) {
                // Also check that it's not a function's name
                $stream->next();
                if ('(' !== $current->value) {
                    $contained = true;
                    break;
                }
                continue;
            }

            $stream->next();
        }

        return $contained ?? false;
    }

    /**
     * Compiles the expression.
     *
     * @return string The expression
     */
    public function __toString(): string
    {
        return $this->compile();
    }
}
