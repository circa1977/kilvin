<?php

use Illuminate\Support\HtmlString;

if (! function_exists('remove_double_slashes')) {
    /**
     * Removes double slashes from a string, except those in URL
     *
     * @param  string  $str
     * @return string
     */
    function remove_double_slashes($str)
    {
        $str = str_replace("://", "{:SS}", $str);
        $str = str_replace(":&#47;&#47;", "{:SHSS}", $str);  // Super HTTP slashes saved!
        $str = preg_replace("#/+#", "/", $str);
        $str = preg_replace("/(&#47;)+/", "/", $str);
        $str = str_replace("&#47;/", "/", $str);
        $str = str_replace("{:SHSS}", ":&#47;&#47;", $str);
        $str = str_replace("{:SS}", "://", $str);

        return $str;
    }
}


if (! function_exists('cms_clear_caching')) {
    /**
     * Clears Caching
     *
     * @todo - Figure out how this works.  Hee hee...
     *
     * @param  string  $which
     * @return boolean
     */
    function cms_clear_caching($which)
    {
        $allowed = ['all', 'tag'];

        if (!in_array($which, $allowed)) {
            return false;
        }

        return true;
    }
}

if (! function_exists('filename_security')) {
    /**
     * Cleans out unwanted characters from a filename
     *
     * @param  string  $str
     * @return string
     */
    function filename_security($str)
    {
        $bad = [
            "../",
            "./",
            "<!--",
            "-->",
            "<",
            ">",
            "'",
            '"',
            '&',
            '$',
            '#',
            '{',
            '}',
            '[',
            ']',
            '=',
            ';',
            '?',
            '/',
            "%20",
            "%22",
            "%3c",      // <
            "%253c",    // <
            "%3e",      // >
            "%0e",      // >
            "%28",      // (
            "%29",      // )
            "%2528",    // (
            "%26",      // &
            "%24",      // $
            "%3f",      // ?
            "%3b",      // ;
            "%3d"       // =
        ];


        $str =  stripslashes(str_replace($bad, '', $str));

        return $str;
    }
}
