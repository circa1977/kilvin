<?php

namespace Kilvin\Core;

use Site;

class Regex
{
    // ------------------------------------
    //  Validate Email Address
    // ------------------------------------

    public static function valid_email($value)
    {
		return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    // ------------------------------------
    //  Prep URL
    // ------------------------------------

    public static function prep_url($str = '')
    {
		if (empty($str)) {
			return '';
		}

		if (substr($str, 0, 7) != 'http://' and substr($str, 0, 8) != 'https://')
		{
			$str = 'https://'.$str;
		}

		return $str;
    }

    // ------------------------------------
    //  Decode query string entities
    // ------------------------------------

    public static function decode_qstr($str)
    {
    	return str_replace(
    		['&#46;','&#63;','&amp;'],
    		['.','?','&'],
    		$str
    	);
    }

    // ------------------------------------
    //  Format HTML so it appears correct in forms
    // ------------------------------------

    public static function form_prep($str = '')
    {
        if (empty($str)) {
            return '';
        }

		$str = htmlspecialchars($str);
		$str = str_replace("'", "&#39;", $str);

        return $str;
    }

    // ------------------------------------
    //  Convert PHP tags to entities
    // ------------------------------------

    public static function encode_php_tags($str)
    {
    	return str_replace(array('<?php', '<?PHP', '<?', '?'.'>'),
    					   array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'),
    					   $str);
	}

    // ------------------------------------
    //  Convert EE Tags
    // ------------------------------------

	public static function encode_ee_tags($str, $convert_curly = false)
	{
		if (empty($str))
		{
			return $str;
		}

		if ($convert_curly === true)
		{
			return str_replace(array('{', '}'), array('&#123;', '&#125;'), $str);
		}

		$str = preg_replace("/\{(\/){0,1}exp:(.+?)\}/", "&#123;\\1exp:\\2&#125;", $str);
		$str = str_replace(array('{exp:', '{/exp'), array('&#123;exp:', '&#123;\exp'), $str);
		$str = preg_replace("/\{embed=(.+?)\}/", "&#123;embed=\\1&#125;", $str);
		$str = preg_replace("/\{path:(.+?)\}/", "&#123;path:\\1&#125;", $str);
		$str = preg_replace("/\{redirect=(.+?)\}/", "&#123;redirect=\\1&#125;", $str);

		return $str;
	}

    // ------------------------------------
    //  Convert single and double quotes to entites
    // ------------------------------------

    public static function convert_quotes($str)
    {
    	return str_replace(array("\'","\""), array("&#39;","&quot;"), $str);
    }

    // ------------------------------------
    //  Trim slashes "/" from front and back of string
    // ------------------------------------

    public static function trim_slashes($str)
    {
        if (substr($str, 0, 1) == '/')
		{
			$str = substr($str, 1);
		}

		if (substr($str, 0, 5) == "&#47;")
		{
			$str = substr($str, 5);
		}

		if (substr($str, -1) == '/')
		{
			$str = substr($str, 0, -1);
		}

		if (substr($str, -5) == "&#47;")
		{
			$str = substr($str, 0, -5);
		}

        return $str;
    }


    // ------------------------------------
    //  Removes double commas from string
    // ------------------------------------

    public static function remove_extra_commas($str)
    {
		// Removes space separated commas as well as leading and trailing commas
		$str =  implode(',', preg_split('/[\s,]+/', $str, -1,  PREG_SPLIT_NO_EMPTY));

        return trim($str);
    }

    // ------------------------------------
    //  Create URL Title
    // ------------------------------------

	public static function create_url_title($str, $lowercase = FALSE)
	{
		if (function_exists('mb_convert_encoding'))
		{
			$str = mb_convert_encoding($str, 'ISO-8859-1', 'auto');
		}
		elseif(function_exists('iconv') AND ($iconvstr = @iconv('', 'ISO-8859-1', $str)) !== FALSE)
		{
			$str = $iconvstr;
		}
		else
		{
			$str = utf8_decode($str);
		}

		if ($lowercase === TRUE)
		{
			$str = strtolower($str);
		}

		$str = preg_replace_callback('/(.)/', function($val) { return Regex::convert_accented_characters($val); }, $str);

		$str = strip_tags($str);

		// Use dash or underscore as separator
		$replace = (Site::config('word_separator') == 'dash') ? '-' : '_';

		$trans = array(
						'&\#\d+?;'				=> '',
						'&\S+?;'				=> '',
						'\s+'					=> $replace,
						'[^a-z0-9\-\._]'		=> '',
						$replace.'+'			=> $replace,
						$replace.'$'			=> $replace,
						'^'.$replace			=> $replace,
						'\.+$'					=> ''
					  );

		foreach ($trans as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);
		}

		$str = trim(stripslashes($str));

		return $str;
	}


	// ------------------------------------
	//  Convert Accented Characters to Unaccented Equivalents
	// ------------------------------------

	public static function convert_accented_characters($match)
	{
		$foreign_characters = [
			'223'	=>	"ss", // ß
			'224'	=>  "a",  '225' =>  "a", '226' => "a", '229' => "a",
			'227'	=>	"ae", '230'	=>	"ae", '228' => "ae",
			'231'	=>	"c",
			'232'	=>	"e",  // è
			'233'	=>	"e",  // é
			'234'	=>	"e",  // ê
			'235'	=>	"e",  // ë
			'236'	=>  "i",  '237' =>  "i", '238' => "i", '239' => "i",
			'241'	=>	"n",
			'242'	=>  "o",  '243' =>  "o", '244' => "o", '245' => "o",
			'246'	=>	"oe", // ö
			'249'	=>  "u",  '250' =>  "u", '251' => "u",
			'252'	=>	"ue", // ü
			'255'	=>	"y",
			'257'	=>	"aa",
			'269'	=>	"ch",
			'275'	=>	"ee",
			'291'	=>	"gj",
			'299'	=>	"ii",
			'311'	=>	"kj",
			'316'	=>	"lj",
			'326'	=>	"nj",
			'353'	=>	"sh",
			'363'	=>	"uu",
			'382'	=>	"zh",
			'256'	=>	"aa",
			'268'	=>	"ch",
			'274'	=>	"ee",
			'290'	=>	"gj",
			'298'	=>	"ii",
			'310'	=>	"kj",
			'315'	=>	"lj",
			'325'	=>	"nj",
			'352'	=>	"sh",
			'362'	=>	"uu",
			'381'	=>	"zh",
		];

    	$ord = ord($match[1]);

		if (isset($foreign_characters[$ord]))
		{
			return $foreign_characters[$ord];
		}
		else
		{
			return $match[1];
		}
	}
}
