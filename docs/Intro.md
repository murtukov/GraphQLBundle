Introduction
======================

This bundle integrates the [`webonyx/graphql-php`](https://github.com/webonyx/graphql-php) library into your Symfony 
app, therefore it is essential to get familiar with its [documentation](https://webonyx.github.io/graphql-php/) first. 

GraphQLBundle introduces a number of additional features such as [Validation](), [Access Control](), 
[Type Inheritance](), [Profiler]() and many others, that are not built into the underlying library. With GraphQLBundle 
you can define your GraphQL types in different ways and file formats, such as YAML, PHP Annotations/Attributes or GraphQL SDL 
(schema definition language).

The core task of this bundle is to generate PHP classes that are compatible with `webonyx/graphql-php`'s [type system](https://webonyx.github.io/graphql-php/type-system/#type-system), 
since it relies on this library. Whichever format you choose to configure your types - YAML, annotations, attributes or 
GraphQL SDL - they will be eventually used to generate PHP classes, that will be your actuall types. Of course, you can 
also write this PHP classes manually - and it may be required in some cases - but using one of the mentioned formats 
greatly reduces boilerplate.

> Note: All generated classes are Symfony services.

For example if you want to create an [Object Type](https://webonyx.github.io/graphql-php/type-system/object-types/) `Query` 
with only 1 field `echo`, you could do it with YAML, which is the default type format:
```yaml
Query:
    type: object
    config:
        fields:
            echo:
                type: String
                resolve: "Hello, World"
```
...and this config will be used to generate the following PHP class, that `webonyx/graphql-php` can _understand_:
```php
final class QueryType extends ObjectType implements GeneratedTypeInterface
{
    public const NAME = 'Query';
    
    public function __construct(GraphQLServices $services)
    {
        $config = [
            'name' => self::NAME,
            'fields' => fn() => [
                'post' => [
                    'type' => Type::string(),
                    'resolve' => fn() => "Hello, World!",
                ],
            ],
        ];

        parent::__construct($services->processConfig($config));
    }
}
```
`$services` is a special variable passed to each of your generated GraphQL types. It is a container that provides necessary 
services, references to other types or user-defined resolver callbacks. The good thing is, you don't need to worry about 
these classes, as they are generated automatically (unless explicitely disabled), but knowing them will help you understand 
the general concept of this bundle.

Todo:
- Expression language in the config
- Lazy loading
- Creating types directly

Resolvers
---------
In the example above our resolver simply returns a hardcoded string:
```yaml
resolve: "Hello, World!"
```
But in most of the cases this is not enough. We need a way to specify an external resolver function to retrieve data. 
For this purpose you can use [Expression Language](https://symfony.com/doc/current/components/expression_language.html), 
for example:
```yaml
resolve: "@=query('find_users', args)"
```
Expressions always start with `@=` prefix to distinguish them from normal strings. This bundle ships with a number of
[predefined expression functions](https://github.com/overblog/GraphQLBundle/blob/master/docs/definitions/expression-language.md#contents).
In this example we are using the function `query`, that was created specially to be used with the `resolve` option and 
we pass to it 2 parameters: a string alias of your resolver service and request arguments. `args` is a special variable 
also [registered by the bundle](https://github.com/overblog/GraphQLBundle/blob/master/docs/definitions/expression-language.md#registered-variables).

Now the generated class will look like this:
```php
final class QueryType extends ObjectType implements GeneratedTypeInterface
{
    public const NAME = 'Query';
    
    public function __construct(ConfigProcessor $configProcessor, GlobalVariables $globalVariables = null)
    {
        $configLoader = fn() => [
            'name' => self::NAME,
            'fields' => fn() => [
                'post' => [
                    'type' => Type::string(),
                    'resolve' => function ($value, $args, $context, $info) use ($globalVariables) {
                        return $globalVariables->get('resolverResolver')->resolve(["find_гыукы", [$args]]);	
                    },
                ],
            ],
        ];
        $config = $configProcessor->process(LazyConfig::create($configLoader, $globalVariables))->load();
        parent::__construct($config);
    }
}
```

Expression Language uses a syntax similar to JavaScript. For more details see the [official documentation](https://symfony.com/doc/current/components/expression_language/syntax.html).

Default folder
--------------
All generated classes are stored by default in the Symfony's cache directory: 
```
%kernel.cache_dir%/overblog/graphql-bundle/__definitions__
```
which usually is:
```
var/cache/{env}/overblog/graphql-bundle/__definitions__
```
where `{env}` is the current enviroment (prod, dev or test).

It is recommended to change this directory to the one, that will be committed. Here is an example of how to do it:

- First set the `cache_dir` option:
```yaml
overblog_graphql:
    definitions:
        cache_dir: "src/GraphQL/Definitions"
```
- Add an entry under `autoload.psr-4` in the `composer.json`
```json
{
    "autoload": {
        "psr-4": {
            "Overblog\\GraphQLBundle\\__DEFINITIONS__\\": "src/GraphQL/Definitions"
        }
    }
}
```
- And dump the autoloader:
```
composer dump-autoload
```


Available type formats
---------------------
There are several ways to define your GraphQL types: you can use YAML, Annotations or GraphQL SDL, each of which has
its pros and cons.

- Write about differences in readability
- Then write about additional features, that each way has (builders, validation, argument transformer)


and since all of them are used to generate
