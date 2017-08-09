<?php

namespace Kilvin\Plugins\XmlEncode;

use DB;
use Schema;

class Manager
{
    static $version	= '1.0.0';
    static $name = 'XML Encode';
    static $description = 'Filter to XML Encode strings';
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
    	return true;
    }

    // ------------------------------------
    //  Plugin de-installer
    // ------------------------------------

    function uninstall()
    {
    	return true;
    }
}
