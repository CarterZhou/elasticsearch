# Laravel fluent APIs to work with Elasticsearch

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

## Installation

Via Composer

``` bash
$ composer require carterzhou/elasticsearch
```

## Usage

Firstly, create an instance of this class. Here we use dependency injection to let Laravel create and inject an instance for us.

```php
use CarterZhou\Elasticsearch\Client;

class TestController extends Controller
{
    protected $client;

    /**
     * TestController constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
```
- Doing simple search

Then we can use ```search``` method to grab data from Elasticsearch. Notice that we can chain ```match``` method to add filtering conditions (similar to Eloquent ```where``` method).

```php
$url = $this->client->getHost() . '/indices-2019.01.28';

$this->client
    ->match('request', 'one-of-urls')
    ->match('request', 'field1 field2')
    ->setSize(500);

$this->client->search($url);

if ($this->client->hasDocuments()) {
    foreach ($this->client->getDocuments() as $document) {
        // Process your document here...
    }
}
```

- Search for all documents. You can also communicate with Elasticsearch multiple times to get documents if total of matching documents exceeds the size you set.

```php
$url = $this->client->getHost() . '/indices-2019.01.28';

$this->client
    ->match('request', 'one-of-urls')
    ->match('request', 'field1 field2')
    ->setSize(500);

do {
    $this->client->search($url);

    if ($this->client->hasDocuments()) {

        foreach ($this->client->getDocuments() as $redirect) {
            // Process your document here...
        }
    }
} while ($this->client->hasMoreDocuments());
```

Notice that we use a ```do while``` loop here because a search will be performed at least once. You don't have to manually set "from" because the ```search``` method will calculate and maintain properties including "from" under the hood.

Warning: you should not use ```search``` method if total of matching documents is over 10000, because by default the result window is 10000 by using "from" to do query. In such case, please use ```scroll``` method instead.

- Use scrolling

As stated above, do not use ```search``` method to loop through large result sets because normally you are not allowed to do so. To address such need, you can use ```scroll``` method like so

```php
$url = $this->client->getHost() . '/logstash*';

$this->client->matchAll()->setSize(500);

$this->client->scroll($url);

do {
    foreach ($this->client->getDocuments() as $document) {
        // Process your document here...
    }

    $this->client->scroll($url);

} while ($this->client->hasDocuments());
```

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/carterzhou/elasticsearch.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/carterzhou/elasticsearch.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/carterzhou/elasticsearch/master.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/carterzhou/elasticsearch
[link-downloads]: https://packagist.org/packages/carterzhou/elasticsearch
[link-travis]: https://travis-ci.org/carterzhou/elasticsearch
[link-author]: https://github.com/carterzhou
[link-contributors]: ../../contributors
