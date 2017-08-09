<?php

namespace Groot\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Stats Functionality
 */
class Template extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cms.template';
    }
}
