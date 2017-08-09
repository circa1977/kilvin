<?php

namespace Groot\Plugins;

interface PluginInterface
{
    /**
     * Install an addon
     *
     * @param $addon
     * @return mixed
     */
    public function install($addon);

    /**
     * Uninstall an addon
     *
     * @param $addon
     * @return mixed
     */
    public function uninstall($addon);
}
