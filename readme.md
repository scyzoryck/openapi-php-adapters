The library is a bridge between popular PHP libraries for serialisation and validation and OpenAPI documentation. 
It can help you to keep generate OpenAPI always up to date with your code.

The package is in super early stage of development. But if you'd have any feedback, feel free to share it.

## Example
Based on the class used for JMS serializer:
```php
class Author
{
    #[Type(name: 'string')]
    #[SerializedName(name: 'full_name')]
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
```

```yaml
components:
  schemas:
    Author:
      type: object
      properties:
        full_name:
          type: string
```
## Usage

Under the hood package uses `cebe/php-openapi` that can help you to add additional information to your docs or write it into the file. 
```php
        $serializer = SerializerBuilder::create()->build();
        $reader = new JmsReader($serializer);
        $openAPI = $reader->read(Author::class);
        echo Writer::writeToYaml($openAPI);
```

## Features
- JSM Serializer
  - [x] object with properties
  - [x] arrays
  - [x] readonly
  - [x] recursive objects
  - [ ] datetime
  - [x] enums
  - [ ] backed enums
  - [x] ArrayCollections
  - [ ] inlined properties
  - [ ] class naming stategies
  - [ ] xml support
  - [ ] versions
- Symfony Validation
- Symfony Serialisation

## Development
Running unit tests requires installing dependencies from sources:
```shell
composer install --prefer-source
```
After it you can run unit tests with 

```shell
php vendor/bin/phpunit
```
