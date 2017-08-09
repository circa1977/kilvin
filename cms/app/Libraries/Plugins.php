<?php

namespace Groot\Libraries;

use DB;
use Carbon\Carbon;
use Groot\Core\Session;

/**
 * Site Data and Functionality
 */
class Plugins
{
    private $plugins;

    // ---------------------------------------------------

    /**
     * The Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->loadPlugins();
    }


    /**
     * List installed plugins
     *
     * @return array
     */
    public function list()
    {
        return $this->plugins;
    }

    // ---------------------------------------------------

    /**
     * Load Plugins
     *
     * @return array
     */
    private function loadPlugins()
    {
        if (!empty($this->plugins)) {
            return;
        }

        // Probably make this Eloquent
        $this->plugins = DB::table('plugins')
            ->orderBy('plugin_name')
            ->get();
    }
}
