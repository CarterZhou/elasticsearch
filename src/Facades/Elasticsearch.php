<?php

namespace CarterZhou\Elasticsearch\Facades;

use Illuminate\Support\Facades\Facade;

class Elasticsearch extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'elasticsearch';
    }
}
