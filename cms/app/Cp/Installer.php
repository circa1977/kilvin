<?php

namespace Groot\Cp;

use Twig_Loader_Filesystem;
use Twig_Environment;

use DB;
use File;
use Schema;

class Installer {

    public $system_path;
    public $cms_folder;
    public $app_path;
    private $variables = [];

    public $data = [
        'domain'                => '',
        'ip'					=> '',
        'db_connection'			=> 'mysql',
        'db_hostname'			=> '127.0.0.1',
        'db_username'			=> '',
        'db_password'			=> '',
        'db_name'				=> '',
        'site_name'				=> '',
        'site_url'				=> '',
        'site_index'			=> '',
        'cp_url'				=> '',
        'password'				=> '',
        'screen_name'			=> '',
        'email'					=> '',
        'notification_sender_email'		=> '',
        'deft_lang'				=> 'english',
        'template'				=> '01',
        'site_timezone'		    => '',
        'upload_folder'			=> 'uploads/',
        'image_path'			=> '../images/',
        'cp_images'				=> 'cp_images/',
        'photo_path'			=> '../images/member_photos/',
        'photo_url'				=> 'images/member_photos/',
        'theme_folder_path'		=> '../themes/',
    ];

    // --------------------------------------------------------------------

    /**
     *  Constructor
     *
     *  @return void
     */
    public function __construct($cms_folder, $system_path)
    {
        $this->cms_folder = $cms_folder;
        $this->system_path = $system_path;

        $this->variables['version']    = CMS_VERSION;
    }

    // --------------------------------------------------------------------

    /**
     *  Run Installer
     *
     *  @return string
     */
    public function run()
    {
        if (!is_dir($this->system_path)) {
            exit('Unable to find CMS folder.');
        }

        foreach ($_POST as $key => $val) {
            if (isset($this->data[$key])) {
                $this->data[$key] = trim($val);
            }
        }

        $page = (!empty($_GET['page'])) ? $_GET['page'] : 1;

        switch($page) {
            case 2:
                return $this->settingsForm();
            break;

            case 3:
                return $this->performInstall();
            break;

            default:
                return $this->homepage();
            break;
        }
    }

    // --------------------------------------------------------------------

    /**
     *  Homepage of Installer
     *
     *  @return string
     */
    private function homepage()
    {
        $requirements_failure = false;
        $results = $this->installationTests();

        // Show PreFlight Errors Page
        if ($results !== true) {
            $this->variables['errors'] = $results;
            return view('errors', $this->variables);
        }

        // Show Homepage!
        return view('homepage', $this->variables);
    }


    // --------------------------------------------------------------------

    /**
     *  Settings Form
     *
     *  @return string
     */
    function settingsForm($errors = [])
    {
        // ----------------------------------------
        //  Help Them with a few Vars
        // ----------------------------------------

        $host       = ( ! isset($_SERVER['HTTP_HOST'])) ? '' : $_SERVER['HTTP_HOST'];
        $phpself    = ( ! isset($_SERVER['PHP_SELF'])) ? '' : trim($_SERVER['PHP_SELF'], '/');

        $url = (
            isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ?
            'https://' :
            'http://'
            ).
            $host.'/'.$phpself;

        $url = substr($url, 0, -strlen($phpself));

        $cp_url     = ($this->data['cp_url'] == '') ? $url.'admin.php' : $this->data['cp_url'];
        $site_url   = ($this->data['site_url'] == '' OR $this->data['site_url'] == '/') ? $url : $this->data['site_url'];
        $site_index = ''; // For now we assume no index.php in URL

        $db_hostname        = ($this->data['db_hostname'] == '')          ? 'localhost'   : $this->data['db_hostname'];
        $db_username        = ($this->data['db_username'] == '')          ? ''            : $this->data['db_username'];
        $db_password        = ($this->data['db_password'] == '')          ? ''            : $this->data['db_password'];
        $db_name            = ($this->data['db_name'] == '')              ? ''            : $this->data['db_name'];
        $password           = ($this->data['password'] == '')             ? ''            : $this->data['password'];
        $email              = ($this->data['email'] == '')                ? ''            : $this->data['email'];
        $screen_name        = ($this->data['screen_name'] == '')          ? ''            : $this->data['screen_name'];
        $notification_sender_email    = ($this->data['email'] == '')                ? ''            : $this->data['email'];
        $template           = ($this->data['template'] == '')             ? '01'          : $this->data['template'];
        $site_name          = ($this->data['site_name'] == '')            ? ''            : $this->data['site_name'];
        $deft_lang          = ($this->data['deft_lang'] == '')            ? 'english'     : $this->data['deft_lang'];
        $timezone           = ($this->data['site_timezone'] == '')      ? 'UTC'         : $this->data['site_timezone'];

        // ----------------------------------------
        //  Themes
        // ----------------------------------------

        $themes = [];

        if ($fp = @opendir($this->system_path.'resources/site_themes/'))
        {
            while (false !== ($folder = readdir($fp)))
            {
                if (@is_dir($this->system_path.'resources/site_themes/'.$folder) &&
                    $folder !== '.' &&
                    $folder !== '..')
                {
                    $themes[] = $folder;
                }
            }
            closedir($fp);
            sort($themes);
        }

        $theme_options = [];

        if (count($themes) > 0)
        {
            foreach ($themes as $folder)
            {
                if ($folder == 'rss' or $folder == 'search') {
                    continue;
                }

                $theme_options[] = [
                    'value' => $folder,
                    'name'  => ucwords(str_replace('_', ' ', $folder)),
                    'selected' => ($template == $folder) ? 'selected' : ''

                ];
            }
        }

        // ----------------------------------------
        //  Set up Vars
        // ----------------------------------------

        $this->variables['errors']     = $errors;
        $this->variables['themes']     = $theme_options;
        $this->variables['cp_url']     = (!empty($this->data['cp_url'])) ? $this->data['cp_url'] : $cp_url;
        $this->variables['site_url']   = (!empty($this->data['site_url'])) ? $this->data['site_url'] : $site_url;
        $this->variables['site_index'] = (!empty($this->data['site_index'])) ? $this->data['site_index'] : $site_index;

        // Show Settings Form!
        return view('form', array_merge($this->data, $this->variables));
    }


    // --------------------------------------------------------------------

    /**
     *  Existing Installation Form
     *
     *  @return string
     */
    function existingInstallForm()
    {
        $fields = '';
        foreach($_POST as $key => $value)
        {
            $fields .= '<input
                type="hidden"
                name="'.str_replace("'", "&#39;", htmlspecialchars($key)).'"
                value="'.str_replace("'", "&#39;", htmlspecialchars($value)).'">'.PHP_EOL;
        }

        $this->variables['fields'] = $fields;

        return view('existingInstall', array_merge($this->data, $this->variables));
    }

    // --------------------------------------------------------------------

    /**
     *  Perform the Install
     *
     *  @return string
     */
    public function performInstall()
    {
        // -----------------------------------
        //  Validation
        // ------------------------------------

        $errors = $this->validateSettings();

        if (!empty($errors)) {
            return $this->settingsForm($errors);
        }

        $this->data['site_url'] = rtrim($this->data['site_url'], '/').'/';

        // -----------------------------------
        //  Set up Connection to DB
        // -----------------------------------

        $connections = config('database.connections');

        $connections['mysql'] = [
            'driver' => 'mysql',
            'host' => $this->data['db_hostname'],
            'port' => '3306',
            'database' => $this->data['db_name'],
            'username' => $this->data['db_username'],
            'password' => $this->data['db_password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => 'cms_',
            'strict' => true,
        ];

        config()->set('database.connections', $connections);
        config()->set('database.default', 'mysql');

        require($this->system_path.'resources/language/'.$this->data['deft_lang'].'/email_data.php');

        // -----------------------------------
        //  Test DB Connection
        // -----------------------------------

        try {
            $sites_exists = Schema::hasTable('sites');
        }
        catch(\Exception $e) {
            return $this->settingsForm([$e->getMessage()]);
        }

        // -----------------------------------
        //  Existing Install?
        // -----------------------------------

        if ($sites_exists && ! isset($_POST['install_override']))
        {
            $fields = '';

            foreach($_POST as $key => $value)
            {
                if($key === '_token') {
                    continue;
                }

                $fields .=
                    '<input type="hidden" name="'.
                        str_replace("'", "&#39;", htmlspecialchars($key)).'" value="'.
                        str_replace("'", "&#39;", htmlspecialchars($value)).'" />'.PHP_EOL;
            }

            // Existing Installer
            return $this->existingInstallForm();
        }

        // --------------------------------
        //  Write .env file
        // --------------------------------

        $this->writeEnvFile();

        // -----------------------------------
        //  Migration
        // -----------------------------------

        $this->runMigration();

        // -----------------------------------
        //  Seeder
        // -----------------------------------

        $this->setUsersIpAddress();
        $this->runSeeder();

        // -----------------------------------
        //  Write Config
        // -----------------------------------

        $this->writeConfig();

        // -----------------------------------
        //  Success!
        // -----------------------------------

        return view('success', array_merge($this->data, $this->variables));
    }

    // --------------------------------------------------------------------

    /**
     *  Validate Settings Form
     *
     *  @return array
     */
    public function validateSettings()
    {
         $errors = [];

        // -----------------------------------
        //  Required Fields
        // ------------------------------------

        if (
            $this->data['db_hostname'] == '' or
            $this->data['db_username'] == '' or
            $this->data['db_name']     == '' or
            $this->data['site_name']   == '' or
            $this->data['screen_name'] == '' or
            $this->data['password']    == '' or
            $this->data['email']       == ''
           )
        {
            $errors[] = "You left some form fields empty";
            return $errors;
        }

        // -----------------------------------
        //  Required Fields
        // ------------------------------------

        if (strlen($this->data['screen_name']) < 4)
        {
            $errors[] = "Your screen name must be at least 4 characters in length";
        }

        if (strlen($this->data['password']) < 10)
        {
            $errors[] = "Your password must be at least 10 characters in length.";
        }

        $system_on = config('cms.is_system_on');

        if (!empty($system_on))
        {
            $errors[] = "Your installation lock is set. Locate the <strong>config/cms.php</strong> file and delete its contents";
        }

        // -----------------------------------
        //  Username and Password based off each other?
        // ------------------------------------

        $lowercase_user = strtolower($this->data['email']);
        $lowercase_password = strtolower($this->data['password']);

        if ($lowercase_user == $lowercase_password or $lowercase_user == strrev($lowercase_password))
        {
            $errors[] = "Your password can not be based on your email address.";
        }

        if (strpos($this->data['db_password'], '$') !== FALSE)
        {
            $errors[] = "Your MySQL password can not contain a dollar sign (\$)";
        }

        if ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $this->data['email']))
        {
            $errors[] = "The email address you submitted is not valid";
        }

        $themes_path = $this->system_path.'resources'.DIRECTORY_SEPARATOR.'site_themes'.DIRECTORY_SEPARATOR;

        if ( ! file_exists($themes_path.$this->data['template'].DIRECTORY_SEPARATOR.$this->data['template'].'.php'))
        {
            $errors[] = "Error: Unable to load the theme you have selected.";
        }

        return $errors;
    }

    // --------------------------------------------------------------------

    /**
     *  Set User's IP Address
     *
     *  @return void
     */
    private function setUsersIpAddress()
    {
        $CIP = ( ! isset($_SERVER['HTTP_CLIENT_IP']))       ? FALSE : $_SERVER['HTTP_CLIENT_IP'];
        $FOR = ( ! isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? FALSE : $_SERVER['HTTP_X_FORWARDED_FOR'];
        $RMT = ( ! isset($_SERVER['REMOTE_ADDR']))          ? FALSE : $_SERVER['REMOTE_ADDR'];

        if ($CIP)
        {
            $cip = explode('.', $CIP);

            $this->data['ip'] = ($cip['0'] != current(explode('.', $RMT))) ? implode('.', array_reverse($cip)) : $CIP;
        }
        elseif ($FOR)
        {
            $this->data['ip'] = (strstr($FOR, ',')) ? end(explode(',', $FOR)) : $FOR;
        }
        else {
            $this->data['ip'] = $RMT;
        }
    }

    // --------------------------------------------------------------------

    /**
     *  Run the Migration
     *
     *  @return void
     */
    public function runMigration()
    {
        require_once SYSTEM_PATH.'database/migrations/2017_06_12_000000_create_cms_tables.php';

        $class = new \CreateCmsTables;

        $class->down(); // Kill existing tables
        $class->up();   // Put in Fresh tables
    }

    // --------------------------------------------------------------------

    /**
     *  Run the Seeder
     *
     *  @return void
     */
    public function runSeeder()
    {
        require_once SYSTEM_PATH.'database/seeds/CmsSeeder.php';

        $class = new \CmsSeeder;
        $class->data = $this->data;
        $class->system_path = $this->system_path;
        $themes_path = $this->system_path.'resources'.DIRECTORY_SEPARATOR.'site_themes'.DIRECTORY_SEPARATOR;
        $class->theme_path = $themes_path.$this->data['template'].DIRECTORY_SEPARATOR.$this->data['template'].'.php';
        $class->run();
    }

    // --------------------------------------------------------------------

    /**
     *  Write the Config File
     *
     *  @return void
     */
    public function writeConfig()
    {
        if (@realpath(str_replace('../', './', $this->data['photo_path'])) !== FALSE)
        {
            $this->data['photo_path'] = str_replace('../', './', $this->data['photo_path']);
            $this->data['photo_path'] = str_replace("\\", "/", realpath($this->data['photo_path'])).'/';
        }

        if (@realpath(str_replace('../', './', $this->data['theme_folder_path'])) !== FALSE)
        {
            $this->data['theme_folder_path'] = str_replace('../', './', $this->data['theme_folder_path']);
            $this->data['theme_folder_path'] = str_replace("\\", "/", realpath($this->data['theme_folder_path'])).'/';
        }

        $config = [
            'installed_version'             =>  CMS_VERSION, // What version is installed in DB/config, opposed to version of files
            'site_debug'                    =>  1,
            'cp_url'                        =>  $this->data['cp_url'],
            'site_index'                    =>  $this->data['site_index'],
            'site_name'                     =>  $this->data['site_name'],
            'site_url'                      =>  $this->data['site_url'],
            'theme_folder_url'              =>  $this->data['site_url'].'themes/',
            'notification_sender_email'     =>  $this->data['email'],
            'max_caches'                    => '150',
            'show_queries'                  =>  'n',
            'template_debugging'            =>  'n',
            'include_seconds'               =>  'n',
            'cookie_domain'                 =>  '',
            'cookie_path'                   =>  '',
            'allow_email_change'            =>  'y',
            'allow_multi_emails'            =>  'n',
            'xss_clean_uploads'             =>  'y',
            'deft_lang'                     =>  $this->data['deft_lang'],
            'is_system_on'                  =>  'y',
            'time_format'                   =>  'us',
            'site_timezone'                 =>  $this->data['site_timezone'],
            'cp_theme'                      =>  'default',
            'un_min_len'                    =>  '5',
            'password_min_length'           =>  '10',
            'default_member_group'          =>  '5',
            'enable_photos'                 => 'y',
            'photo_url'                     => $this->data['site_url'].$this->data['photo_url'],
            'photo_path'                    => $this->data['photo_path'],
            'photo_max_width'               => '100',
            'photo_max_height'              => '100',
            'photo_max_kb'                  => '50',
            'save_tmpl_revisions'           =>  'n',
            'max_tmpl_revisions'            =>  '5',
            'enable_censoring'              =>  'n',
            'censored_words'                =>  '',
            'censor_replacement'            =>  '',
            'banned_ips'                    =>  '',
            'banned_emails'                 =>  '',
            'banned_screen_names'           =>  '',
            'ban_action'                    =>  'restrict',
            'ban_message'                   =>  'This site is currently unavailable',
            'ban_destination'               =>  'https://google.com/',
            'recount_batch_total'           =>  '1000',
            'enable_image_resizing'         =>  'y',
            'image_resize_protocol'         =>  'gd2',
            'image_library_path'            =>  '',
            'thumbnail_prefix'              =>  'thumb',
            'word_separator'                =>  'underscore',
            'use_category_name'             =>  'n',
            'reserved_category_word'        =>  'category',
            'new_posts_clear_caches'        =>  'y',
            'auto_assign_cat_parents'       =>  'y',
            'enable_throttling'             => 'n',
            'banish_masked_ips'             => 'y',
            'max_page_loads'                => '10',
            'time_interval'                 => '8',
            'lockout_time'                  => '30',
            'banishment_type'               => 'message',
            'banishment_url'                => '',
            'banishment_message'            => 'You have exceeded the allowed page load frequency.',
            'disable_events'            => 'n',
            'is_site_on'                    => 'y',
            'theme_folder_path'             => $this->data['theme_folder_path'],
        ];

    // --------------------------------------------------------------------
    //  Writes Sites Database
    // --------------------------------------------------------------------

        // ---------------------------------------
        //  Default Administration Prefs
        // ---------------------------------------

        $admin_default = [
            'site_index',
            'site_name',
            'site_url',
            'site_debug',
            'is_site_on',
            'cp_url',
            'theme_folder_url',
            'notification_sender_email',
            'max_caches',
            'show_queries',
            'template_debugging',
            'include_seconds',
            'cookie_domain',
            'cookie_path',
            'allow_email_change',
            'allow_multi_emails',
            'xss_clean_uploads',
            'deft_lang',
            'send_headers',
            'time_format',
            'site_timezone',
            'cp_theme',
            'enable_censoring',
            'censored_words',
            'censor_replacement',
            'banned_ips',
            'banned_emails',
            'banned_screen_names',
            'ban_action',
            'ban_message',
            'ban_destination',
            'recount_batch_total',
            'enable_throttling',
            'banish_masked_ips',
            'max_page_loads',
            'time_interval',
            'lockout_time',
            'banishment_type',
            'banishment_url',
            'banishment_message',
            'theme_folder_path',
        ];

        $site_prefs = [];

        foreach($admin_default as $value)
        {
            $site_prefs[$value] = $config[$value];
        }

        DB::table('sites')
            ->where('site_id', 1)
            ->update(
            [
                'site_preferences' => serialize($site_prefs)
            ]);

        // ------------------------------------
        //  Default Members Prefs
        // ------------------------------------

        $member_default = [
            'un_min_len',
            'password_min_length',
            'default_member_group',
            'enable_photos',
            'photo_url',
            'photo_path',
            'photo_max_width',
            'photo_max_height',
            'photo_max_kb',
        ];

        $site_prefs = [];

        foreach($member_default as $value)
        {
            $site_prefs[$value] = $config[$value];
        }

        DB::table('sites')
            ->where('site_id', 1)
            ->update(
            [
                'member_preferences' => serialize($site_prefs)
            ]);

        // ------------------------------------
        //  Default Templates Prefs
        // ------------------------------------

        $template_default = [
            'save_tmpl_revisions',
            'max_tmpl_revisions'
        ];

        $site_prefs = [];

        foreach($template_default as $value)
        {
            $site_prefs[$value] = $config[$value];
        }

        DB::table('sites')
            ->where('site_id', 1)
            ->update(
            [
                'template_preferences' => serialize($site_prefs)
            ]);

        // ------------------------------------
        //  Default Weblogs Prefs
        // ------------------------------------

        $weblog_default = [
            'enable_image_resizing',
            'image_resize_protocol',
            'image_library_path',
            'thumbnail_prefix',
            'word_separator',
            'use_category_name',
            'reserved_category_word',
            'new_posts_clear_caches',
            'auto_assign_cat_parents'
        ];

        $site_prefs = [];

        foreach($weblog_default as $value)
        {
            $site_prefs[$value] = $config[$value];
        }

        DB::table('sites')
            ->where('site_id', 1)
            ->update(
            [
                'weblog_preferences' => serialize($site_prefs)
            ]);

        // ------------------------------------
        //  Remove Site Prefs from Config
        // ------------------------------------

        foreach([$admin_default, $member_default, $template_default, $weblog_default] as $value) {
            unset($config[$value]);
        }

        // --------------------------------
        // Write config/cms.php file
        // --------------------------------

        $contents  = '<?php'.PHP_EOL."return [".PHP_EOL;

        foreach ($config as $key => $val)
        {
            $val = str_replace("\"", "\\\"", $val);
            $contents .= "\t'".$key."' => \"".$val."\",".PHP_EOL;
        }
        $contents .= "];".PHP_EOL;

        $cfile = $this->system_path.'config/cms.php';

        file_put_contents($cfile, $contents);
    }

    // --------------------------------------------------------------------

    /**
     *  Write the .env file
     *
     *  @return void
     */
    private function writeEnvFile()
    {
        $new = [
            'DB_CONNECTION' => $this->data['db_connection'],
            'DB_HOST'       => $this->data['db_hostname'],
            'DB_PASSWORD'   => $this->data['db_password'],
            'DB_USERNAME'   => $this->data['db_username'],
            'DB_DATABASE'   => $this->data['db_name'],
        ];

        $encryption_key = $this->generateEncryptionKey();

        $new['APP_KEY'] = $encryption_key;

        $this->writeNewEnvironmentFileWith($new);

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateEncryptionKey()
    {
        return 'base64:'.base64_encode(random_bytes(
            config('app.cipher') == 'AES-128-CBC' ? 16 : 32
        ));
    }

    // --------------------------------------------------------------------

    /**
     * Write a new environment file with the given key.
     *
     * @param  array  $new
     * @return void
     */
    protected function writeNewEnvironmentFileWith($new)
    {
        // The example is what starts us off
        $string = file_get_contents(app()->environmentFilePath().'.example');

        foreach($new as $key => $value) {
            $string = preg_replace(
                $this->keyReplacementPattern($key),
                $key.'='.$this->protectEnvValue($value),
                $string
            );
        }

        file_put_contents(app()->environmentFilePath(), $string);
    }

    // --------------------------------------------------------------------

    /**
     * Protect an .env value if it has special chars
     *
     * @return string
     */
    protected function protectEnvValue($value)
    {
        // Todo!
        return $value;
    }

    // --------------------------------------------------------------------

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern($key)
    {
        return "/^".$key."\=.*$/m";
    }

    // --------------------------------------------------------------------

    /**
     *  Create Cache Directories
     *
     *  @return void
     */
    public function createCacheDirectories()
    {
        $cache_path = $this->system_path.'/cache/';
        $cache_dirs = ['page_cache', 'tag_cache'];
        $errors = [];

        foreach ($cache_dirs as $dir)
        {
            if ( ! @is_dir($cache_path.$dir))
            {
                if ( ! @mkdir($cache_path.$dir, 0777))
                {
                    $errors[] = $dir;

                    continue;
                }

                @chmod($cache_path.$dir, 0777);
            }
       }
    }

    // -----------------------------------------
    //  Fetch names of installed languages
    // -----------------------------------------

    private function language_pack_names($default)
    {
        $source_dir = './'.trim($this->data['system_dir']).'/language/';

        $filelist = [];

        if ($fp = @opendir($source_dir))
        {
            while (false !== ($file = readdir($fp)))
            {
                $filelist[count($filelist)] = $file;
            }
        }

        closedir($fp);

        sort($filelist);

        $r  = "<div class='default'>";
        $r .= "<select name='deft_lang' class='select'>\n";

        $skip = ['.php', '.html', '.DS_Store', '.'];

        for ($i =0; $i < sizeof($filelist); $i++)
        {
            foreach($skip as $a)
            {
                if (stristr($filelist[$i], $a))
                {
                    continue(2);
                }
            }

            $selected = ($filelist[$i] == $default) ? " selected='selected'" : '';

            $r .= "<option value='{$filelist[$i]}'{$selected}>".ucfirst($filelist[$i])."</option>\n";
        }

        $r .= "</select>";
        $r .= "</div>";

        return $r;
    }

    // --------------------------------------------------------------------

    /**
     *  Run System Tests Prior to Installation
     *
     *  @return boolean|array
     */
    private function installationTests()
    {
        $errors = [];

        if (version_compare(phpversion(), '7.1') == -1)
        {
            $errors[] =
                [
                    'Unsupported PHP version',
                    sprintf(
                        'In order to install Groot CMS, your server must be running PHP version 7.1 or newer.
                         Your server is running PHP version <em>%s</em>',
                        phpversion()
                    )
                ];
        }

        if (! file_exists($this->system_path.'config/cms.php'))
        {
            $errors[] = ["Unable to locate the file 'config/cms.php'.", "Please make sure you have uploaded all components of this software."];
        }

        if (! file_exists($this->system_path.'resources/language/'.$this->data['deft_lang'].'/email_data.php'))
        {
            $errors[] = ["Unable to locate the file containing your email templates.", "Make sure you have uploaded all components of this software."];
        }

        // ---------------------------------------
        //  Writeable Files + Directories
        // ---------------------------------------

        $writable_things = [
                                $this->system_path.'config/cms.php',
                                // $this->system_path.'../.env',
                                $this->system_path.'templates/',
                                $this->system_path.'storage/',
                                './images/uploads',
                            ];


        $not_writable = [];

        foreach ($writable_things as $val)
        {
            if (!is_writable($val))
            {
                $not_writable[] = $val;
            }
        }

        if (count($not_writable) > 0)
        {
            $title =  "Error: Incorrect Permissions";

            $d = (count($not_writable) > 1) ? 'directories or files' : 'directory or file';

            $message = "The following {$d} cannot be written to:<p>";

            foreach ($not_writable as $bad)
            {
                $message .= '<em>'.$bad.'</em><br >';
            }

            $message .= '
            </p><p>In order to install Groot CMS, the file permissions on the above must be set as indicated in the instructions.
            If you are not sure how to set permissions, <a href="#">click here</a>.</p>';

            $errors[] = [$title, $message];
        }

        // ---------------------------------------
        //  Config File Exists, Not Already Installed
        // ---------------------------------------

        if (file_exists($this->system_path.'config/cms.php'))
        {
            $is_system_on = config('cms.is_system_on');

            if (!empty($is_system_on))
            {
                $errors[]  = [
                    "Warning: Your installation is locked!",
                    "<p>There already appears to be an instance of Groot CMS installed!
                    If you wish to continue, you must locate the file <strong>config/cms.php</strong> and delete its contents.</strong>"
                ];
            }
        }

        return (empty($errors)) ? true : $errors;
    }
}
