<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Generator;

use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\Collection;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Instance;
use Murtukov\PHPCodeGenerator\Literal;
use Overblog\GraphQLBundle\ExpressionLanguage\Expression;
use Overblog\GraphQLBundle\Generator\Event\BuildEvent;
use Overblog\GraphQLBundle\Generator\Exception\GeneratorException;
use Overblog\GraphQLBundle\Generator\TypeBuilder as BaseTypeBuilder;
use Overblog\GraphQLBundle\Validator\InputValidator;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TypeBuilder
{
    private EventDispatcher $eventDispatcher;

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function listen()
    {
        $this->eventDispatcher->addListener(BuildEvent::CONFIG_BUILD_END, function(BuildEvent $event) {
            $array = $event->getPart();
            $config = $event->getConfig();

            // only by input-object types (for class level validation)
            if (isset($config['validation'])) {
                $array->addItem('validation', $this->buildValidationRules($config['validation']));
            }
        });
    }

    /**
     * Builds an arrow function from a string with an expression prefix,
     * otherwise just returns the provided value back untouched.
     *
     * Render example:
     *
     *      fn($value, $context, $info) => $services->getType($value)
     *
     * @param mixed $resolveType
     *
     * @return mixed|ArrowFunction
     */
    protected function buildResolveType($resolveType)
    {
        if ($resolveType instanceof Expression) {
            return ArrowFunction::new()
                ->addArguments('value', 'context', 'info')
                ->setExpression(Literal::new((string) $resolveType));
        }

        return $resolveType;
    }

    /**
     * Builds a closure or a numeric multiline array with Symfony Constraint
     * instances. The array is used by {@see InputValidator} during requests.
     *
     * Render example (array):
     *
     *      [
     *          new NotNull(),
     *          new Length([
     *              'min' => 5,
     *              'max' => 10
     *          ]),
     *          ...
     *      ]
     *
     * Render example (in a closure):
     *
     *      fn() => [
     *          new NotNull(),
     *          new Length([
     *              'min' => 5,
     *              'max' => 10
     *          ]),
     *          ...
     *      ]
     *
     * @throws GeneratorException
     *
     * @return ArrowFunction|Collection
     */
    protected function buildConstraints(array $constraints = [], bool $inClosure = true)
    {
        $result = Collection::numeric()->setMultiline();

        foreach ($constraints as $wrapper) {
            $name = key($wrapper);
            $args = reset($wrapper);

            if (false !== strpos($name, '\\')) {
                // Custom constraint
                $fqcn = ltrim($name, '\\');
                $name = ltrim(strrchr($name, '\\'), '\\');
                $this->file->addUse($fqcn);
            } else {
                // Symfony constraint
                $this->file->addUseGroup(BaseTypeBuilder::CONSTRAINTS_NAMESPACE, $name);
                $fqcn = BaseTypeBuilder::CONSTRAINTS_NAMESPACE."\\$name";
            }

            if (!class_exists($fqcn)) {
                throw new GeneratorException("Constraint class '$fqcn' doesn't exist.");
            }

            $instance = Instance::new($name);

            if (is_array($args)) {
                if (isset($args[0]) && is_array($args[0])) {
                    // Nested instance
                    $instance->addArgument($this->buildConstraints($args, false));
                } else {
                    // Numeric or Assoc array?
                    $instance->addArgument(isset($args[0]) ? $args : Collection::assoc($args));
                }
            } elseif (null !== $args) {
                $instance->addArgument($args);
            }

            $result->push($instance);
        }

        if ($inClosure) {
            return ArrowFunction::new($result);
        }

        return $result; // @phpstan-ignore-line
    }

    /**
     * Checks if given config contains any validation rules.
     */
    private function configContainsValidation(): bool
    {
        $fieldConfig = $this->config['fields'][$this->currentField];

        if (!empty($fieldConfig['validation'])) {
            return true;
        }

        foreach ($fieldConfig['args'] ?? [] as $argConfig) {
            if (!empty($argConfig['validation'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render example:
     *
     *      [
     *          'link' => {@see normalizeLink}
     *          'cascade' => [
     *              'groups' => ['my_group'],
     *          ],
     *          'constraints' => {@see buildConstraints}
     *      ]
     *
     * If only constraints provided, uses {@see buildConstraints} directly.
     *
     * @param array{
     *     constraints: array,
     *     link: string,
     *     cascade: array
     * } $config
     *
     * @throws GeneratorException
     */
    protected function buildValidationRules(array $config): GeneratorInterface
    {
        // Convert to object for better readability
        $c = (object) $config;

        $array = Collection::assoc();

        if (!empty($c->link)) {
            if (false === strpos($c->link, '::')) {
                // e.g. App\Entity\Droid
                $array->addItem('link', $c->link);
            } else {
                // e.g. App\Entity\Droid::$id
                $array->addItem('link', Collection::numeric($this->normalizeLink($c->link)));
            }
        }

        if (isset($c->cascade)) {
            // If there are only constarainst, use short syntax
            if (empty($c->cascade['groups'])) {
                $this->file->addUse(InputValidator::class);

                return Literal::new('InputValidator::CASCADE');
            }
            $array->addItem('cascade', $c->cascade['groups']);
        }

        if (!empty($c->constraints)) {
            // If there are only constarainst, use short syntax
            if (0 === $array->count()) {
                return $this->buildConstraints($c->constraints);
            }
            $array->addItem('constraints', $this->buildConstraints($c->constraints));
        }

        return $array;
    }

    /**
     * Creates and array from a formatted string.
     *
     * Examples:
     *
     *      "App\Entity\User::$firstName"  -> ['App\Entity\User', 'firstName', 'property']
     *      "App\Entity\User::firstName()" -> ['App\Entity\User', 'firstName', 'getter']
     *      "App\Entity\User::firstName"   -> ['App\Entity\User', 'firstName', 'member']
     */
    protected function normalizeLink(string $link): array
    {
        [$fqcn, $classMember] = explode('::', $link);

        if ('$' === $classMember[0]) {
            return [$fqcn, ltrim($classMember, '$'), 'property'];
        } elseif (')' === substr($classMember, -1)) {
            return [$fqcn, rtrim($classMember, '()'), 'getter'];
        } else {
            return [$fqcn, $classMember, 'member'];
        }
    }
}
