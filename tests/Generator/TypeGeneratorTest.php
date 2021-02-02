<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Generator;

use Generator;
use Overblog\GraphQLBundle\Event\SchemaCompiledEvent;
use Overblog\GraphQLBundle\Generator\TypeBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TypeGeneratorTest extends TestCase
{
    /**
     * @dataProvider getPermissionsProvider
     */
    public function testCacheDirPermissions(int $expectedMask, ?string $cacheDir, ?int $cacheDirMask): void
    {
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $mask = $this->getTypeGenerator($dispatcher, $cacheDir, $cacheDirMask)->getCacheDirMask();

        $this->assertSame($expectedMask, $mask);
    }

    public function testCompiledEvent(): void
    {
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(new SchemaCompiledEvent()));

        $this->getTypeGenerator($dispatcher)->compile(TypeGenerator::MODE_DRY_RUN);
    }

    private function getTypeGenerator(
        EventDispatcherInterface $dispatcher,
        ?string $cacheDir = null,
        ?int $cacheDirMask = null
    ): TypeGenerator {
        $typeBuilder = $this->createMock(TypeBuilder::class);

        $paramBag = new ParameterBag();

        $paramBag->set('overblog_graphql_types.config', []);
        $paramBag->set('overblog_graphql.cache_dir', $cacheDir);
        $paramBag->set('overblog_graphql.use_classloader_listener', true);
        $paramBag->set('overblog_graphql.class_namespace', '');
        $paramBag->set('overblog_graphql.cache_dir_permissions', $cacheDirMask);
        $paramBag->set('kernel.cache_dir', '');

        return new TypeGenerator($paramBag, $typeBuilder, $dispatcher, []);
    }

    public function getPermissionsProvider(): Generator
    {
        // default permission when using default cache dir
        yield [0777, null, null];
        // default with custom cache dir path
        yield [0775, '/src', null];
        // custom permissions
        yield [0755, null, 0755];
    }
}
