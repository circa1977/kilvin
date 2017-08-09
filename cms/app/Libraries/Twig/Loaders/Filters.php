<?php

namespace Kilvin\Libraries\Twig\Loaders;

use Twig_SimpleFilter;

/**
 * Extension to expose defined filters to the Twig templates.
 *
 * See the `extensions.php` config file, specifically the `filters` key
 * to configure those that are loaded.
 */
class Filters extends Loader
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'Cms_Twig_Extension_Loader_Filters';
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        $load    = config('twig.filters', []);
        $filters = [];

        foreach ($load as $method => $callable) {
            list($method, $callable, $options) = $this->parseCallable($method, $callable);

            $filter = new Twig_SimpleFilter(
                $method,
                function () use ($callable) {
                    return call_user_func_array($callable, func_get_args());
                },
                $options
            );

            $filters[] = $filter;
        }

        return $filters;
    }
}
