<?php declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Validator\Mapping;

use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphQLBundle\Validator\Mapping\MetadataFactory;
use Overblog\GraphQLBundle\Validator\Mapping\ObjectMetadata;
use Overblog\GraphQLBundle\Validator\ValidationNode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\NoSuchMetadataException;

class MetadataFactoryTest extends TestCase
{
    public function testMetadataFactoryHasObject()
    {
        $metadataFactory = new MetadataFactory();

        $type = new ObjectType(['name' => 'testType']);
        $validationNode = new ValidationNode($type);
        $objectMetadata = new ObjectMetadata($validationNode);

        $metadataFactory->addMetadata($objectMetadata);

        $hasMetadata = $metadataFactory->hasMetadataFor($validationNode);
        $metadata = $metadataFactory->getMetadataFor($validationNode);

        $this->assertTrue($hasMetadata);
        $this->assertSame($objectMetadata, $metadata);
    }

    public function testMetadataFactoryHasNoObject()
    {
        $metadataFactory = new MetadataFactory();

        $object = new \stdClass();

        $hasMetadata = $metadataFactory->hasMetadataFor($object);
        $this->assertFalse($hasMetadata);

        $this->expectException(NoSuchMetadataException::class);
        $metadataFactory->getMetadataFor($object);
    }
}
