<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator;

use Overblog\GraphQLBundle\Validator\Mapping\MetadataFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ValidatorFactory
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class ValidatorFactory
{
    private $defaultValidator;
    private $defaultTranslator;
    private $constraintValidatorFactory;

    public function __construct(?ValidatorInterface $validator, ConstraintValidatorFactoryInterface $constraintValidatorFactory, ?TranslatorInterface $translator)
    {
        $this->defaultValidator = $validator;
        $this->defaultTranslator = $translator;
        $this->constraintValidatorFactory = $constraintValidatorFactory;
    }

    public function createValidator(MetadataFactory $metadataFactory): ValidatorInterface
    {
        $builder = Validation::createValidatorBuilder()
            ->setMetadataFactory($metadataFactory)
            ->setConstraintValidatorFactory($this->constraintValidatorFactory)
        ;

        if (null !== $this->defaultTranslator) {
            $builder
                ->setTranslator($this->defaultTranslator)
                ->setTranslationDomain('validators');
        }

        return $builder->getValidator();
    }
}