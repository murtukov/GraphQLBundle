<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Composer\Autoload\ClassLoader;
use Overblog\GraphQLBundle\Event\SchemaCompiledEvent;
use Overblog\GraphQLBundle\Generator\Processor\GeneratorProcessor;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function array_merge;
use function file_exists;
use function file_put_contents;
use function str_replace;
use function var_export;

/**
 * @final
 */
class TypeGenerator
{
    public const MODE_DRY_RUN = 1;
    public const MODE_MAPPING_ONLY = 2;
    public const MODE_WRITE = 4;
    public const MODE_OVERRIDE = 8;

    public const GRAPHQL_SERVICES = 'services';

    private static bool $classMapLoaded = false;
    private ?string $targetDir;
    protected int $targetDirMask;
    private array $configs;
    private bool $useClassMap;
    private ?string $baseCacheDir;
    private string $classNamespace;
    private TypeBuilder $typeBuilder;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @var iterable<GeneratorProcessor>
     */
    private iterable $processors;

    public function __construct(
        ParameterBagInterface $params,
        TypeBuilder $typeBuilder,
        EventDispatcherInterface $eventDispatcher,
        iterable $processors
    ) {
        $this->typeBuilder = $typeBuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->processors = $processors;

        $this->targetDir = $params->get('overblog_graphql.cache_dir');
        $this->configs = $params->get('overblog_graphql_types.config');
        $this->useClassMap = $params->get('overblog_graphql.use_classloader_listener');
        $this->baseCacheDir = $params->get('kernel.cache_dir');
        $this->classNamespace = $params->get('overblog_graphql.class_namespace');
        $targetDirMask = $params->get('overblog_graphql.cache_dir_permissions');

        if (null === $targetDirMask) {
            // Apply permission 0777 for default cache dir otherwise apply 0775.
            $targetDirMask = null === $this->targetDir ? 0777 : 0775;
        }

        $this->targetDirMask = $targetDirMask;
    }

    public function getBaseCacheDir(): ?string
    {
        return $this->baseCacheDir;
    }

    public function setBaseCacheDir(string $baseCacheDir): void
    {
        $this->baseCacheDir = $baseCacheDir;
    }

    public function getTargetDir(): ?string
    {
        return (null !== $this->targetDir)
            ? $this->targetDir
            : $this->baseCacheDir.'/overblog/graphql-bundle/__definitions__';
    }

    public function setTargetDir(?string $targetDir): self
    {
        $this->targetDir = $targetDir;

        return $this;
    }

    /**
     * Perform configuration preprocessing.
     */
    private function processConfig(array $config): array
    {
        foreach ($this->processors as $processor) {
            $config = $processor->process($config);
        }

        return $config;
    }

    public function compile(int $mode): array
    {
        $cacheDir = $this->getTargetDir();
        $writeMode = $mode & self::MODE_WRITE;

        // Configure write mode
        if ($writeMode && file_exists($cacheDir)) {
            $fs = new Filesystem();
            $fs->remove($cacheDir);
        }

        // Generate classes
        $classes = [];
        foreach ($this->processConfig($this->configs) as $name => $config) {
            $config['config']['name'] ??= $name;
            $config['config']['class_name'] = $config['class_name'];
            $classMap = $this->generateClass($config, $cacheDir, $mode);
            $classes = array_merge($classes, $classMap);
        }

        // Create class map file
        if ($writeMode && $this->useClassMap && count($classes) > 0) {
            $content = "<?php\nreturn ".var_export($classes, true).';';

            // replaced hard-coded absolute paths by __DIR__
            // (see https://github.com/overblog/GraphQLBundle/issues/167)
            $content = str_replace(" => '$cacheDir", " => __DIR__ . '", $content);

            file_put_contents($this->getClassesMap(), $content);

            $this->loadClasses(true);
        }

        $this->eventDispatcher->dispatch(new SchemaCompiledEvent());

        return $classes;
    }

    public function generateClass(array $config, ?string $outputDirectory, int $mode = self::MODE_WRITE): array
    {
        $className = $config['config']['class_name'];
        $path = "$outputDirectory/$className.php";

        if (!($mode & self::MODE_MAPPING_ONLY)) {
            $phpFile = $this->typeBuilder->build($config['config'], $config['type']);

            if ($mode & self::MODE_WRITE) {
                if (($mode & self::MODE_OVERRIDE) || !file_exists($path)) {
                    $phpFile->save($path, $this->targetDirMask);
                }
            }
        }

        return ["$this->classNamespace\\$className" => $path];
    }

    public function loadClasses(bool $forceReload = false): void
    {
        if ($this->useClassMap && (!self::$classMapLoaded || $forceReload)) {
            $classMapFile = $this->getClassesMap();
            $classes = file_exists($classMapFile) ? require $classMapFile : [];

            /** @var ClassLoader $mapClassLoader */
            static $mapClassLoader = null;

            if (null === $mapClassLoader) {
                $mapClassLoader = new ClassLoader();
                $mapClassLoader->setClassMapAuthoritative(true);
            } else {
                $mapClassLoader->unregister();
            }

            $mapClassLoader->addClassMap($classes);
            $mapClassLoader->register();

            self::$classMapLoaded = true;
        }
    }

    private function getClassesMap(): string
    {
        return $this->getTargetDir().'/__classes.map';
    }
}
