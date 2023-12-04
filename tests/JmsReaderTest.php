<?php

namespace Scyzoryck\OpenApiAdapters\Tests;

use cebe\openapi\Writer;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Tests\Fixtures\Author;
use JMS\Serializer\Tests\Fixtures\AuthorList;
use JMS\Serializer\Tests\Fixtures\AuthorReadOnly;
use JMS\Serializer\Tests\Fixtures\CircularReferenceParent;
use JMS\Serializer\Tests\Fixtures\Comment;
use JMS\Serializer\Tests\Fixtures\Enum\Suit;
use JMS\Serializer\Tests\Fixtures\ObjectWithEnums;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Scyzoryck\OpenApiAdapters\JmsReader;

class JmsReaderTest extends TestCase
{
    public static function cases(): iterable
    {
        yield ['author', Author::class];
        yield ['author_list', AuthorList::class];
        yield ['author_read_only', AuthorReadOnly::class];
        yield ['circular_reference_parent', CircularReferenceParent::class];
        yield ['comment', Comment::class];
        yield ['suit', Suit::class];
    }

    #[DataProvider('cases')]
    public function testReading(string $filename, string $class)
    {
        $serializer = SerializerBuilder::create()->enableEnumSupport(true)->build();
        $reader = new JmsReader($serializer);

        $openAPI = $reader->read($class);
        $this->assertStringEqualsFile(__DIR__ . '/jms/' . $filename . '.yaml', Writer::writeToYaml($openAPI));
    }
}
