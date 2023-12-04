<?php

namespace Scyzoryck\OpenApiAdapters;

use cebe\openapi\spec\OpenApi;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\PersistentCollection as MongoPersistentCollection;
use Doctrine\ODM\PHPCR\PersistentCollection as PhpcrPersistentCollection;
use Doctrine\ORM\PersistentCollection as OrmPersistentCollection;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Serializer;
use JMS\Serializer\Tests\Fixtures\Enum\Suit;
use Metadata\MetadataFactoryInterface;

class JmsReader
{
    private readonly MetadataFactoryInterface $factory;
    private static \Closure $accessor;
    private array $additionClasses = [];
    private array $alreadyGenerated = [];

    public function __construct(
        Serializer $serializer
    )
    {
        self::$accessor ??= \Closure::bind(static function ($o) {
            return [
                $o->factory,
            ];
        }, null, Serializer::class);

        [$this->factory] = (self::$accessor)($serializer);
    }

    public function read(string...$classes): OpenAPI
    {
        return new OpenApi(['components' => ['schemas' => iterator_to_array($this->generateForClasses(...$classes)),],]);
    }

    private function generateForClasses(string ...$classnames): \Generator
    {
        $this->additionClasses = [];
        foreach ($classnames as $classname) {
            if (in_array($classname, $this->alreadyGenerated, true)) {
                continue;
            }

            $metadata = $this->factory->getMetadataForClass($classname);
            if (enum_exists($classname)) {
                yield $this->formatName($classname) => [
                    'type' => 'string',
                    'enum' => array_map(static fn(object $enum): string => $enum->name, $classname::cases()),
                ];
                continue;
            }

            yield $this->formatName($classname) => [
                'type' => 'object',
                'properties' => iterator_to_array($this->generateProperties(...$metadata->propertyMetadata)),
            ];
            $this->alreadyGenerated[] = $classname;
        }

        if ($this->additionClasses) {
            yield from $this->generateForClasses(...$this->additionClasses);
        }
    }

    private function generateProperties(PropertyMetadata ...$properties): \Generator
    {
        foreach ($properties as $property) {
            yield $property->serializedName => iterator_to_array($this->generateProperty($property));
        }
    }

    private function generateProperty(PropertyMetadata $property)
    {
        $type = $this->unifyType($property->type['name'] ?? '');
        if (class_exists($type)) {
            $this->additionClasses[] = $type;
            yield 'type' => $this->formatName($type);
        } elseif ($property->type['name'] ?? false) {
            yield 'type' => $type;
        }

        if ($type === 'array') {
            $this->additionClasses[] = $property->type['params'][0]['name'];
            yield 'items' => [
                '$ref' => '#/components/schemas/' . $this->formatName($property->type['params'][0]['name']),
            ];
        }

        if ($property->readOnly) {
            yield 'readOnly' => true;
        }
    }

    private function formatName(string $classname): string
    {
        $parts = explode('\\', $classname);
        return end($parts);
    }

    private function unifyType(string $type): string
    {
        return match ($type) {
            'ArrayCollection', ArrayCollection::class, OrmPersistentCollection::class, MongoPersistentCollection::class, PhpcrPersistentCollection::class => 'array',
            default => $type,
        };
    }
}
