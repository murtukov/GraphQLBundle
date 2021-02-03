<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Event;

use Overblog\GraphQLBundle\DependencyInjection\Configuration;
use Symfony\Contracts\EventDispatcher\Event;

class BuildEvent extends Event
{
    public const FILE_BUILD_START   = Configuration::NAME . '.builder.file_build_start';
    public const FILE_BUILD_END     = Configuration::NAME . '.builder.file_build_end';
    public const CLASS_BUILD_END    = Configuration::NAME . '.builder.class_build_end';
    public const TYPE_BUILD_END     = Configuration::NAME . '.builder.type_build_end';
    public const CONFIG_BUILD_END   = Configuration::NAME . '.builder.config_build_end';

    /**
     * @var mixed
     */
    public $part;
    public array $config;
    public array $rootConfig = [];

    public function __construct($part = null, array $config = [])
    {
        $this->part = $part;
        $this->config = $config;
    }

    public function getPart()
    {
        return $this->part;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setData($part, array $config = []): self
    {
        $this->part = $part;
        $this->config = $config;

        return $this;
    }

    /**
     * @return $this
     */
    public function setRootConfig(array $config): self
    {
        $this->rootConfig = $config;

        return $this;
    }

    public function getRootConfig(): array
    {
        return $this->rootConfig;
    }

    /**
     * @param mixed $data
     *
     * @return $this
     */
    public function __invoke($data): self
    {
        return $this->setData($data);
    }
}
