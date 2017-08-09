<?php

namespace Kilvin\Libraries;

use DB;
use Cache;
use Plugins;
use Carbon\Carbon;
use Kilvin\Core\Session;
use Kilvin\Exceptions\CmsFailureException;

/**
 * Site Data and Functionality
 */
class Site
{
    private $config = [];

    // seven special TLDs for cookie domains
    private $special_tlds = [
        'com', 'edu', 'net', 'org', 'gov', 'mil', 'int'
    ];

    // --------------------------------------------------------------------

    /**
     * Set a Config value
     *
     * @param   string
     * @param   string
     * @return  void
     */
    public function setConfig($which, $value)
    {
        if ( ! isset($this->config[$which])) {
            return;
        }

        $this->config[$which] = $value;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch config value
     *
     * @param   string
     * @param   boolean
     * @return  mixed
     */
    public function config($which = '', $add_slash = false)
    {
       if ($which == '') {
            return null;
        }

        if ( ! isset($this->config[$which])) {
            return null;
        }

        $pref = $this->config[$which];

        if (is_string($pref)) {
            if ($add_slash !== false) {
                $pref = rtrim($pref, '/').'/';
            }

            $pref = str_replace('\\\\', '\\', $pref);
        }

        return $pref;
    }

    // --------------------------------------------------------------------

    /**
     * Determine Domain and Site based off request host + uri
     *
     * @return  void
     */
    public function loadDomainMagically()
    {
        $host       = request()->getHost();
        $http_host  = request()->getHttpHost(); // includes port
        $uri        = request()->getRequestUri(); // Hey, maybe there's a folder!

        try {
            $query = DB::table('domains')
                ->select('domain_id')
                ->where('site_url', 'LIKE', '%'.$host.'%')
                ->get();
        } catch (\InvalidArgumentException $e) {
            throw new CmsFailureException('Unable to Load CMS. Database is either not up or credentials are invalid.');
        }

        if ($query->count() == 0) {
            abort(500, ' Unable to Load Site Preferences; No Preferences Found');
        }

        if ($query->count() == 1) {
            $this->loadDomainPrefs($query->first()->domain_id);
        }

        // @todo - We have two matches? Figure out which one is the best based off domain + uri
        $this->loadDomainPrefs($query->first()->domain_id);
    }

    // --------------------------------------------------------------------

    /**
     * Load Domain Preferences
     *
     * @param   integer
     * @return  void
     */
    public function loadDomainPrefs($domain_id = 1)
    {
        $query = DB::table('domains')
            ->join('sites', 'sites.site_id', '=', 'domains.site_id')
            ->where('domain_id', $domain_id)
            ->first();

        if (!$query) {
            abort(500, 'Unable to Load Site Preferences. No Domain Found.');
        }

        $this->parseDomainPrefs($query);
    }

    // --------------------------------------------------------------------

    /**
     * Parse Domain Preferences from Query Result
     *
     * @param   object
     * @return  void
     */

    public function parseDomainPrefs($query)
    {
        // ------------------------------------
        //  Reset Preferences
        // ------------------------------------

        $this->config = $cms_config = config('cms');

        // ------------------------------------
        //  Fold in the Preferences in the Database
        // ------------------------------------

        foreach($query as $name => $data)
        {
            if (substr($name, -12) == '_preferences')
            {
                if ( ! is_string($data) OR substr($data, 0, 2) != 'a:')
                {
                    exit("Site Error: Unable to Load Site Preferences; Invalid Preference Data");
                }

                // Any values in cms.php take precedence over those in the database, so it goes second in array_merge()
                $this->config = array_merge(unserialize($data), $this->config, $cms_config);
            }
            else
            {
                $this->config[$name] = $data;
            }
        }

        $cms_path    = $query->cms_path ?? SYSTEM_PATH;
        $public_path = $query->public_path ?? realpath(SYSTEM_PATH.'../public');

        // Paths and URLs!
        $this->config = str_replace('{SITE_URL}', $query->site_url, $this->config);
        $this->config = str_replace('{CMS_PATH}', rtrim($cms_path, '/').'/', $this->config);
        $this->config = str_replace('{PUBLIC_PATH}', rtrim($cms_path, '/').'/', $this->config);

        // ------------------------------------
        //  Few More Variables
        // ------------------------------------

        $this->config['site_id']  = (int) $query->site_id;
        $this->config['site_short_name']  = $this->config['site_handle'] = $query->site_handle;

        // If we just reloaded, then we reset a few things automatically
        if ($this->config('show_queries') == 'y' or REQUEST == 'CP') {
            DB::enableQueryLog();
        }
    }

    // ------------------------------------------------

    /**
     * List all plugins
     *
     * @return array
     */
    public static function pluginsList()
    {
        return Plugins::list();
    }

    // ------------------------------------------------

    /**
     * List all sites
     *
     * @return array
     */
    public static function sitesList()
    {
        $storeTime = Carbon::now()->addMinutes(1);

        $query = static function()
        {
            return DB::table('sites')
                ->select('site_id', 'site_name')
                ->orderBy('site_name')
                ->get();
        };

        // File and database storage stores do not support tags
        // And Laravel throws an exception if you even try ::rolls eyes::
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags('sites')->remember('cms.libraries.site.sitesList', $storeTime, $query);
        }

        return Cache::remember('cms.libraries.site.sitesList', $storeTime, $query);
    }

    // --------------------------------------------------------------------

    /**
     * Return preferences located in sites table's fields
     *
     * @param   string
     * @return  array
     */
    public function divination($which)
    {
        $site_default = [
            'site_debug',
            'is_site_on',
            'cp_url',
            'encryption_type',
            'site_index',
            'site_url',
            'theme_folder_url',
            'theme_folder_path',
            'notification_sender_email',
            'max_caches',
            'show_queries',
            'template_debugging',
            'include_seconds',
            'cookie_domain',
            'cookie_path',
            'xss_clean_uploads',
            'deft_lang',
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
            'max_logged_searches'
        ];

        $member_default = [
            'password_min_length',
            'default_member_group',
            'enable_photos',
            'photo_url',
            'photo_path',
            'photo_max_width',
            'photo_max_height',
            'photo_max_kb'
        ];

        $template_default = [
            'save_tmpl_revisions',
            'max_tmpl_revisions'
        ];

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

        $name = $which.'_default';

        if(!isset(${$name})) {
            return [];
        }

        return ${$name};
    }

    // ------------------------------------------------

    /**
     * All the Data for All Sites
     *
     * @return array
     */
    public static function sitesData()
    {
        $storeTime = Carbon::now()->addMinutes(1);

        $query = static function()
        {
            return DB::table('sites')
                ->orderBy('site_name')
                ->get();
        };

        // File and database storage stores do not support tags
        // And Laravel throws an exception if you even try ::rolls eyes::
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags('sites')->remember('cms.libraries.site.sitesData', $storeTime, $query);
        }

        return Cache::remember('cms.libraries.site.sitesData', $storeTime, $query);
    }

    // ------------------------------------------------

    /**
     * Flush all Site Caches
     *
     * @return void
     */
    public static function flushSiteCache()
    {
        // File and database storage stores do not support tags
        // And Laravel throws an exception if you even try ::rolls eyes::
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags('sites')->flush();
            return;
        }

        Cache::forget('cms.libraries.site.sitesList');
    }
}
