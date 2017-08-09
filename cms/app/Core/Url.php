<?php

namespace Kilvin\Core;

use DB;
use Site;
use Schema;
use Illuminate\Http\Request;

class Url
{
    public static $URI			= '';       // The full URI query string: /weblog/entry/124/
    public static $QSTR     	= '';       // Only the query segment of the URI: 124
    public static $Pages_QSTR	= '';		// For a Pages request, this contains the Entry ID for the Page

    public static $make_safe = ['RET', 'XSS', 'URI', 'ACT'];

    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    // ------------------------------------
    //  Convert programatic characters to entities
    // ------------------------------------

	public static function sanitize($str)
	{
		$bad	= ['$', 	'(', 		')',	 	'%28', 		'%29'];
		$good	= ['&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;'];

		return str_replace($bad, $good, $str);
	}

    // ------------------------------------
    //  Parse URI segments
    // ------------------------------------

    public static function parseUri($uri = '')
    {
        if ($uri != '')
        {
        	// Don't use a reference on this or it messes up the CSS files
            $uri = static::sanitize(Regex::trim_slashes($uri));

            if ($uri != '')
            {
                $x = 0;

                $ex = explode("/", $uri);

                // ------------------------------------
                //  Maximum Number of Segments Check
                // - If the URL contains more than 10 segments, error out
                // ------------------------------------

                if (count($ex) > 10) {
                	exit("Error: The URL contains too many segments.");
                }

                // ------------------------------------
                //  Parse URI segments
                // ------------------------------------

                $n = 1;

                $uri = '';

                for ($i = $x; $i < count($ex); $i++)
                {
					// nothing naughty
					if (strpos($ex[$i], '=') !== FALSE && preg_match('#.*(\042|\047).+\s*=.*#i', $ex[$i]))
					{
						$ex[$i] = str_replace(array('"', "'", ' ', '='), '', $ex[$i]);
					}

                    $uri .= $ex[$i].'/';

                    $n++;
                }

                $uri = substr($uri, 0, -1);

                // Reassign the full URI
                static::$URI = '/'.$uri.'/';
            }
        }
    }

    // ------------------------------------
    //  Parse out the Url::$QSTR variable
    // ------------------------------------

	public static function parseQueryString()
	{
		if ( ! request()->segment(2))
		{
			$QSTR = 'index';
		}
		elseif ( ! request()->segment(3))
		{
			$QSTR = request()->segment(2);
		}
		else
		{
			$QSTR = preg_replace("|".'/'.preg_quote(request()->segment(1)).'/'.preg_quote(request()->segment(2))."|", '', static::$URI);
		}

		static::$QSTR = Regex::trim_slashes($QSTR);
	}


    // ------------------------------------
    //  Fetch a URI segment
    // ------------------------------------

    function fetch_uri_segment($n = '')
    {
        return $this->request->segment($n);
    }
}
