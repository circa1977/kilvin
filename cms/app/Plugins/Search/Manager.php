<?php

namespace Groot\Plugins\Search;

use DB;
use Schema;

class Manager
{
    static $version	= '1.0.0';
    static $name = 'Search';
    static $description = 'Search within weblog entries';
    static $developer = 'Paul Burdick';
    static $developer_url = 'https://arliden.com';
    static $documentat_url = 'https://arliden.com';
    static $has_cp = 'n';

	function name()
    {
        return self::$name;
    }

    public function description()
    {
        return self::$description;
    }

    function version()
    {
        return self::$version;
    }

    function developer()
    {
        return self::$developer;
    }

    function developerUrl()
    {
        return self::$developer_url;
    }

    function documentationUrl()
    {
        return self::$documentation_url;
    }

    public function hasCp()
    {
        return static::$has_cp;
    }

    // ------------------------------------
    //  Plugin installer
    // ------------------------------------

    function install()
    {
        Schema::create('search', function($table) {
        	$table->increments('search_id'); // unique hash
        	$table->string('search_hash', 32); // unique hash
        	$table->integer('site_id')->unsigned()->default(1);
        	$table->integer('search_date');
        	$table->string('keywords', 60);
        	$table->integer('member_id')->unsigned();
        	$table->string('ip_address', 45);
        	$table->integer('total_results')->unsigned()->default(0);
        	$table->tinyInteger('per_page')->unsigned();
        	$table->text('custom_fields');
        	$table->string('result_page', 70);
        });

        return true;
    }


    // ------------------------------------
    //  Plugin de-installer
    // ------------------------------------

    function uninstall()
    {
        Schema::dropTable('search');

        return true;
    }
}
