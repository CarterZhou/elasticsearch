# Elasticsearch

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

## Installation

Via Composer

``` bash
$ composer require carterzhou/elasticsearch
```

## Usage

Firstly, create an instance of this class. Here we use dependency injection to let Laravel create and inject an instance for us.

```php
use CarterZhou\Elasticsearch\Elasticsearch;

class TestController extends Controller
{
    protected $elasticsearchClient;

    /**
     * TestController constructor.
     * @param Elasticsearch $client
     */
    public function __construct(Elasticsearch $client)
    {
        $this->elasticsearchClient = $client;
    }
}
```
- Doing simple search. Then we can ```search``` method to grab data from Elasticsearch. Notice that we can chain ```must``` method to add filtering conditions (similar to Eloquent ```where``` method).

```php
$url = $this->elasticsearchClient->getHost() . '/indices-2019.01.28';

$this->elasticsearchClient
    ->must('request', 'one-of-urls')
    ->must('request', 'field1 field2')
    ->setSize(500);

$this->elasticsearchClient->search($url);

if ($this->elasticsearchClient->hasDocuments()) {
    foreach ($this->elasticsearchClient->getDocuments() as $document) {
        // Process your document here...
    }
}
```

- Search for all documents. You can also communicate with Elasticsearch multiple times to get documents if total of matching documents exceeds the size you set.

```php
$url = $this->elasticsearchClient->getHost() . '/indices-2019.01.28';

$this->elasticsearchClient
    ->must('request', 'one-of-urls')
    ->must('request', 'field1 field2')
    ->setSize(500);

do {
    $this->elasticsearchClient->search($url);

    if ($this->elasticsearchClient->hasDocuments()) {

        foreach ($this->elasticsearchClient->getDocuments() as $redirect) {
            // Process your document here...
        }
    }
} while ($this->elasticsearchClient->hasMoreDocuments());
```

Notice that we use a ```do while``` loop here because a search will be performed at least once. You don't have to manually set "from" because the ```search``` method will calculate and maintain properties including "from" under the hood.

Warning: you should not use ```search``` method if total of matching documents is over 10000, because by default the result window is 10000 by using "from" to do query. In such case, please use ```scroll``` method instead.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/carterzhou/elasticsearch.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/carterzhou/elasticsearch.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/carterzhou/elasticsearch/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/carterzhou/elasticsearch
[link-downloads]: https://packagist.org/packages/carterzhou/elasticsearch
[link-travis]: https://travis-ci.org/carterzhou/elasticsearch
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/carterzhou
[link-contributors]: ../../contributors
