<?php

namespace Team7745\OzonApi\Facades;

use Illuminate\Support\Facades\Facade;

class OzonApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ozon-api';
    }
}
