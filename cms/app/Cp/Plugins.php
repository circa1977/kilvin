<?php

namespace Kilvin\Cp;

use Cp;
use DB;
use Request;
use Kilvin\Core\Session;
use Kilvin\Exceptions\CmsFatalException;

class Plugins
{
    public $result;

    // ------------------------------------
    //  Constructor
    // ------------------------------------

    public function __construct()
    {

    }

    // --------------------------------------------------------------------

    /**
    * Request Handler
    *
    * @return mixed
    */
    public function run()
    {
        if (Request::input('action') === null && Request::input('plugin') === null) {
            return $this->homepage();
        }

        switch(Request::input('action'))
        {
            case 'install'	 :   return $this->plugin_installer();
                break;
            case 'uninstall' :   return $this->plugin_installer(false);
                break;
            default     	 :   return $this->plugin_handler();
                break;
        }
    }

    // --------------------------------------------------------------------

    /**
    * Plugins Homepage
    *
    * @param string $message
    * @return string
    */
    public function homepage($message = '')
    {
        if ( ! Session::access('can_access_plugins')) {
            return Cp::unauthorizedAccess();
        }

		// ------------------------------------
		//  Assing page title
		// ------------------------------------

        $title = __('cp.plugins');

        Cp::$title = $title;
        Cp::$crumb = $title;

        // ------------------------------------
        //  Fetch all plugin names from "plugins" folder
        // ------------------------------------

        $plugins = [];

        if ($fp = @opendir(CMS_PATH_PLUGINS))
        {
            while (false !== ($file = readdir($fp)))
            {
            	if ( is_dir(CMS_PATH_PLUGINS.$file) && ! preg_match("/[^A-Za-z0-9]/", $file))
            	{
                    $plugins[] = ucfirst($file);
                }
            }

            closedir($fp);
        }

        if($fp = @opendir(CMS_PATH_THIRD_PARTY))
        {
            while (false !== ($file = readdir($fp)))
            {
                if ( is_dir(CMS_PATH_THIRD_PARTY.$file) && ! preg_match("/[^A-Za-z0-9]/", $file))
                {
                    $plugins[] = $file;
                }
            }

            closedir($fp);
        }

        sort($plugins);

        // ------------------------------------
        //  Fetch allowed Plugins for a particular user
        // ------------------------------------

        // Assigned plugins is plugin_id => plugin_name
        $plugin_ids = array_keys(Session::userdata('assigned_plugins'));

        if (empty($plugin_ids)) {
            return Cp::$body = Cp::quickDiv('', __('plugins.plugin_no_access'));
        }

        $allowed_plugins = DB::table('plugins')
            ->whereIn('plugin_id', $plugin_ids)
            ->orderBy('plugin_name')
            ->pluck('plugin_name')
            ->all();

        if (sizeof($allowed_plugins) == 0 and ! Session::access('can_admin_plugins')) {
            return Cp::$body = Cp::quickDiv('', __('plugins.plugin_no_access'));
        }

        // ------------------------------------
        //  Fetch the installed plugins from DB
        // ------------------------------------

        $query = DB::table('plugins')
        	->orderBy('plugin_name')
        	->get();

        $installed_plugins = [];

        foreach ($query as $row) {
            $installed_plugins[$row->plugin_name] = $row;
        }

        // ------------------------------------
        //  Build page output
        // ------------------------------------

        $r = '';

        if ($message != '') {
        	$r .= Cp::quickDiv('successMessage', $message);
        }

        $r .= Cp::table('tableBorderNoTop', '0', '0', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableCell(
                'tableHeading',
                [
					NBS,
                    __('plugins.plugin_name'),
                    __('plugins.plugin_description'),
                    __('plugins.plugin_version'),
                    __('plugins.plugin_status'),
                    __('plugins.plugin_action')
                ]).
              '</tr>'.PHP_EOL;


        $i = 0;
		$n = 1;

        foreach ($plugins as $plugin)
        {
			if (!Session::access('can_admin_plugins') && !in_array($plugin, $allowed_plugins)) {
				continue;
			}

            $manager = $this->loadManager($plugin);

            $r .= '<tr>'.PHP_EOL;

            $r .= Cp::tableCell('', Cp::quickSpan('', $n++), '1%');

            $name = $manager->name();

            if (isset($installed_plugins[$plugin]) AND $manager->hasCp() == 'y') {
				$name = Cp::anchor(BASE.'?C=plugins'.AMP.'plugin='.$plugin, $manager->name());
            }

            $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', $name), '29%');

            // Plugin Description
            $r .= Cp::tableCell('', $manager->description(), '36%');

            // Plugin Version
            $r .= Cp::tableCell('', $manager->version(), '10%');


            // Plugin Status
            $status = ( ! isset($installed_plugins[$plugin]) ) ? 'not_installed' : 'installed';

			$in_status = str_replace(" ", "&nbsp;", __('plugins.'.$status));

            $show_status = ($status == 'not_installed') ?
                Cp::quickSpan('highlight', $in_status) :
                Cp::quickSpan('highlight_alt', $in_status);

            $r .= Cp::tableCell('', $show_status, '12%');

            // Plugin Action
            $action = ($status == 'not_installed') ? 'install' : 'uninstall';

            $show_action =
                (Session::access('can_admin_plugins')) ?
                Cp::anchor(BASE.'?C=plugins'.AMP.'action='.$action.AMP.'plugin='.$plugin, __('plugins.'.$action)) :
                '--';

            $r .= Cp::tableCell('', $show_action, '10%');

            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        Cp::$body  = $r;
    }

    // --------------------------------------------------------------------

    /**
    * Load a Plugin's Manager
    *
    * @param string $plugin
    * @return object
    */
    public function loadManager($plugin)
    {
        $plugin = filename_security($plugin);

        $class = '\\Kilvin\\Plugins\\'.$plugin.'\\Manager';
        $third_paty_path = CMS_PATH_THIRD_PARTY.$plugin.DIRECTORY_SEPARATOR.'Manager.php';

        // Not native?
        if ( ! class_exists($class)) {
            if ( ! is_file($third_paty_path)) {
                throw new CmsFatalException(__('plugins.plugin_cannot_be_found'));
            }

            require $third_paty_path;

            if ( ! class_exists($class)) {
                throw new CmsFatalException(__('plugins.plugin_cannot_be_found'));
            }
        }

        return new $class;
    }

    // --------------------------------------------------------------------

    /**
    * Load a Plugin's Control Panel class
    *
    * @param string $plugin
    * @return object
    */
    public function loadControlPanel($plugin)
    {
        $plugin = filename_security($plugin);

        $class = '\\Kilvin\\Plugins\\'.$plugin.'\\ControlPanel';
        $third_paty_path = CMS_PATH_THIRD_PARTY.$plugin.DIRECTORY_SEPARATOR.'ControlPanel.php';

        // Not native?
        if ( ! class_exists($class)) {
            if ( ! is_file($third_paty_path)) {
                throw new CmsFatalException(__('plugins.plugin_cannot_be_found'));
            }

            require $third_paty_path;

            if ( ! class_exists($class)) {
                throw new CmsFatalException(__('plugins.plugin_cannot_be_found'));
            }
        }

        return new $class;
    }

    // --------------------------------------------------------------------

    /**
    * Load Plugin's CP Pages
    *
    * @param string $plugin
    * @return object
    */
    function plugin_handler()
    {
        if ( ! Session::access('can_access_plugins')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $plugin = Request::input('plugin')) {
            return false;
        }

        // @todo - Check that it is installed first?

        if (Session::userdata('group_id') != 1)
        {
            $access = false;
            // Session::userdata('assigned_plugins')

			if ($access == false) {
				return Cp::unauthorizedAccess();
			}
		}

        return $this->loadControlPanel($plugin);
    }

    // --------------------------------------------------------------------

    /**
    * Plugin Installer and Uninstaller
    *
    * @param bool $install
    * @return string
    */
    function plugin_installer($install=true)
    {
        if ( ! Session::access('can_admin_plugins')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $plugin = Request::input('plugin')) {
            return false;
        }

        // Will check that the Plugin exists and if not throws an error
        $manager = $this->loadManager($plugin);

        $query_count = DB::table('plugins')
        	->where('plugin_name', $plugin)
        	->count();

        if ($query_count == 0 && $install === false) {
        	throw new CmsFatalException(__('plugins.plugin_is_not_installed'));
        }

        if ($query_count > 0 && $install === true) {
        	throw new CmsFatalException(__('plugins.plugin_is_already_installed'));
        }

        if($install === false) {
			if ( ! Request::input('confirmed')) {
				return $this->uninstall_confirm($plugin);
			}

        	$method = 'uninstall';
        	$error = 'plugin_uninstall_error';
        }

        if ($install === true) {
        	$method = 'install';
        	$error  = 'plugin_install_error';
        }

        // Run Manager's install or uninstall method
		$manager->$method();

		// Run universal install queries
		if ($install === true) {
			DB::table('plugins')
				->insert(
					[
						'plugin_name' => $plugin,
						'plugin_version' => $manager->version(),
						'has_cp' => $manager->hasCp()
                    ]
				);
		}

		// Run universal uinstall queries
		if ($install === false) {
			 DB::table('plugins')->where('plugin_name', ucfirst($plugin))->delete();
		}

        $line = (stristr($method, 'uninstall')) ? __('plugins.plugin_has_been_uninstalled') : __('plugins.plugin_has_been_installed');

        $message = $line.$manager->name();

        return $this->homepage($message);
    }

    // ------------------------------------
    //  De-install Confirm
    // ------------------------------------

    function uninstall_confirm($plugin = '')
    {
        if ( ! Session::access('can_admin_plugins')) {
            return Cp::unauthorizedAccess();
        }

        if ($plugin == '') {
            return Cp::unauthorizedAccess();
        }

        Cp::$title	= __('plugins.uninstall_plugin');
		Cp::$crumb	= __('plugins.uninstall_plugin');

        Cp::$body	= Cp::formOpen(
            ['action' => 'C=plugins'.AMP.'action=uninstall'.AMP.'plugin='.$plugin],
            ['confirmed' => '1']
		);

        $MOD = $this->loadManager($plugin);
		$name = $MOD->name();

		Cp::$body .= Cp::quickDiv('alertHeading', __('plugins.uninstall_plugin'));
		Cp::$body .= Cp::div('box');
		Cp::$body .= Cp::quickDiv('defaultBold', __('plugins.uninstall_plugin_confirm'));
		Cp::$body .= Cp::quickDiv('defaultBold', BR.$name);
		Cp::$body .= Cp::quickDiv('alert', BR.__('plugins.data_will_be_lost')).BR;
		Cp::$body .= '</div>'.PHP_EOL;

		Cp::$body .= Cp::quickDiv('paddingTop', Cp::input_submit(__('plugins.uninstall_plugin')));
		Cp::$body .= '</form>'.PHP_EOL;
    }
}
