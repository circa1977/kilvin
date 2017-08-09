<?php

namespace Groot\Cp;

use Cp;
use DB;
use Site;
use Stats;
use Plugins;
use Request;
use Notification;
use Carbon\Carbon;
use Groot\Core\Regex;
use Groot\Core\Session;
use Groot\Core\Localize;
use Groot\Models\Member;
use Groot\Core\JsCalendar;
use Groot\Notifications\NewEntryAdminNotify;

class Publish
{
    public $assign_cat_parent   = true;
    public $direct_return       = false;
    public $categories          = [];
    public $cat_parents         = [];
    public $nest_categories     = 'y';
    public $cat_array           = [];

    public $url_title_error      = false;

    // --------------------------------------------------------------------

    /**
     * Constructor!
     */
    public function __construct() {
    }

    // --------------------------------------------------------------------

    /**
    * Request Handler
    *
    * @return mixed
    */
    public function run()
    {
        $this->assign_cat_parent = (Site::config('auto_assign_cat_parents') == 'n') ? FALSE : true;

        switch (Request::input('M'))
        {
            case 'new_entry'            :
                    return ( ! Request::input('preview')) ? $this->submit_new_entry() : $this->new_entry_form('preview');
                break;
            case 'entry_form'           : return $this->new_entry_form();
                break;
            case 'edit_entry'           : return $this->new_entry_form('edit');
                break;
            case 'view_entry'           : return $this->view_entry();
                break;
            case 'view_entries'         : return $this->edit_entries();
                break;
            case 'multi_edit'           : return $this->multi_edit_form();
                break;
            case 'updateMultipleEntries' : return $this->updateMultipleEntries();
                break;
            case 'entry_category_update': return $this->multi_entry_category_update();
                break;
            case 'delete_conf'          : return $this->delete_entries_confirm();
                break;
            case 'delete_entries'       : return $this->delete_entries();
                break;
            case 'file_upload_form'     : return $this->file_upload_form();
                break;
            case 'upload_file'          : return $this->upload_file();
                break;
            case 'file_browser'         : return $this->file_browser();
                break;
            default  :

                    if (Request::input('C') == 'publish')
                    {
                        $assigned_weblogs = array_keys(Session::userdata('assigned_weblogs'));

                        if (count($assigned_weblogs) == 0)
                        {
                            return Cp::unauthorizedAccess(__('publish.unauthorized_for_any_blogs'));
                        }
                        else
                        {
                            if (count($assigned_weblogs) == 1)
                            {
                                return $this->new_entry_form();
                            }
                            else
                            {
                                return $this->weblog_select_list();
                            }
                        }
                    }
                    else
                    {
                       return $this->edit_entries();
                    }
             break;
        }
    }




    // ------------------------------------
    //  Weblog selection menu
    // ------------------------------------
    // This function shows a list of available weblogs.
    // This list will be displayed when a user clicks the
    // "publish" link when more than one weblog exist.
    //--------------------------------------------

    function weblog_select_list($add='')
    {
        if (Request::input('C') == 'publish')
        {
            $blurb  = __('publish.select_blog_to_post_in');
            $title  = __('publish.publish');
            $action = 'C=publish'.AMP.'M=entry_form';
        }
        else
        {
            $blurb  = __('publish.select_blog_to_edit');
            $title  = __('publish.edit');
            $action = 'C=edit'.AMP.'M=view_entries';
        }

        // ------------------------------------
        //  Fetch the blogs the user is allowed to post in
        // ------------------------------------

        $links = [];

        $i = 0;

        foreach (Session::userdata('assigned_weblogs') as $weblog_id => $weblog_title)
        {
            $links[] = Cp::tableQuickRow(
                '',
                Cp::quickDiv(
                    'defaultBold',
                    Cp::anchor(
                        BASE.'?'.$action.
                            AMP.'weblog_id='.$weblog_id.$add,
                        $weblog_title
                    )
                )
            );
        }

        // If there are no allowed blogs, show a message

        if (count($links) < 1)
        {
            return Cp::unauthorizedAccess(__('publish.unauthorized_for_any_blogs'));
        }

        Cp::$body .= Cp::table('tableBorder', '0', '', '100%')
                     .Cp::tableQuickRow('tableHeading', $blurb);

        foreach ($links as $val)
        {
            Cp::$body .= $val;
        }

        Cp::$body .= '</table>'.PHP_EOL;

        Cp::$title = $title;
        Cp::$crumb = $title;
    }



    // ------------------------------------
    //  Weblog "new entry" form
    // ------------------------------------
    // This function displays the form used to submit, edit, or
    // preview new weblog entries with.
    //--------------------------------------------

    function new_entry_form($which = 'new', $submission_error = '', $entry_id='', $hidden = array())
    {
        $title                      = '';
        $url_title                  = '';
        $url_title_prefix           = '';
        $status                     = '';
        $expiration_date            = '';
        $entry_date                 = '';
        $sticky                     = '';
        $field_data                 = '';
        $preview_text               = '';
        $catlist                    = '';
        $author_id                  = '';
        $version_id                 = Request::input('version_id');
        $version_num                = Request::input('version_num');
        $weblog_id                  = '';

        $publish_tabs = [
            'form'      => __('publish.publish_form'),
            'date'      => __('publish.date'),
            'cat'       => __('publish.categories'),
            'options'   => __('publish.options'),
            'revisions' => __('publish.revisions'),
            'show_all'  => __('publish.show_all'),
        ];

        // ------------------------------------
        //  We need to first determine which weblog to post the entry into.
        // ------------------------------------

        $assigned_weblogs = array_keys(Session::userdata('assigned_weblogs'));

        // if it's an edit, we just need the entry id and can figure out the rest
        if (Request::input('entry_id') !== null and is_numeric(Request::input('entry_id')) and $weblog_id == '')
        {
            $query = DB::table('weblog_entries')
                ->select('weblog_id')
                ->where('entry_id', Request::input('entry_id'))
                ->first();

            if ($query) {
                $weblog_id = $query->weblog_id;
            }
        }

        if (empty($weblog_id)) {

            if (is_numeric(Request::input('weblog_id'))) {

                $weblog_id = Request::input('weblog_id');
            }
            elseif (sizeof($assigned_weblogs) == 1)
            {
                $weblog_id = $assigned_weblogs[0];
            }
        }

        if ( empty($weblog_id) OR ! is_numeric($weblog_id)) {
            return false;
        }

        // ------------------------------------
        //  Security check
        // ------------------------------------


        if ( ! in_array($weblog_id, $assigned_weblogs)) {
            return Cp::unauthorizedAccess(__('publish.unauthorized_for_this_blog'));
        }

        // ------------------------------------
        //  If Still Set, Show All Goes at the End
        // ------------------------------------

        if (isset($publish_tabs['show_all']))
        {
            unset($publish_tabs['show_all']);
            $publish_tabs['show_all'] = __('publish.show_all');
        }

        // ------------------------------------
        //  Fetch weblog preferences
        // ------------------------------------

        $query = DB::table('weblogs')->where('weblog_id', $weblog_id)->first();

        if (!$query)
        {
            return Cp::errorMessage(__('publish.no_weblog_exits'));
        }

        foreach ($query as $key => $val) {
            $$key = $val;
        }

        // ------------------------------------
        //  Fetch Revision if Necessary
        // ------------------------------------

        $show_revision_cluster = ($enable_versioning == 'y') ? 'y' : 'n';

        if ($which == 'new')
        {
            $versioning_enabled = ($enable_versioning == 'y') ? 'y' : 'n';
        }
        else
        {
            $versioning_enabled = (isset($_POST['versioning_enabled'])) ? 'y' : 'n';
        }

        if (is_numeric($version_id))
        {
            $entry_id = Request::input('entry_id');

            $revquery = DB::table('entry_versioning')
                ->select('version_data')
                ->where('entry_id', $entry_id)
                ->where('version_id', $version_id)
                ->first();

            if ($revquery)
            {
                $_POST = unserialize($revquery->version_data);
                $_POST['entry_id'] = $entry_id;
                $which = 'preview';
            }
            unset($revquery);
        }

        // ------------------------------------
        //  Insane Idea to Have Defaults and Prefixes
        // ------------------------------------

        if ($which == 'edit')
        {
            $url_title_prefix = '';
        }
        elseif ($which == 'new')
        {
            $title      = '';
            $url_title  = $url_title_prefix;
        }

        // --------------------------------------------------------------------
        // The $which variable determines what the page should show:
        //  If $which = 'new' we'll show a blank "new entry" page
        //  If $which = "preview", the user has clicked the "preview" button.
        //  If $which = "edit", we are editing an already existing entry.
        //  If $which = 'save', like a preview, but also an edit.
        // --------------------------------------------------------------------

        if ($which == 'edit')
        {
            if ( ! $entry_id = Request::input('entry_id')) {
                return false;
            }

            // Fetch the weblog data
            $result = DB::table('weblog_entries')
                ->join('weblog_entry_data', 'weblog_entry_data.entry_id', '=', 'weblog_entries.entry_id')
                ->where('weblog_entries.entry_id', $entry_id)
                ->where('weblog_entries.weblog_id', $weblog_id)
                ->select('weblog_entries.*', 'weblog_entry_data.*')
                ->first();

            if (!$result) {
                return Cp::errorMessage(__('publish.no_weblog_exits'));
            }

            if ($result->author_id != Session::userdata('member_id')) {
                if ( ! Session::access('can_edit_other_entries')) {
                    return Cp::unauthorizedAccess();
                }
            }

            foreach ($result as $key => $val)  {
                $$key = $val;
            }
        }

        // ------------------------------------
        //  Assign page title based on type of request
        // ------------------------------------

        switch ($which)
        {
            case 'edit'     :  Cp::$title = __('publish.edit_entry');
                break;
            case 'save'     :  Cp::$title = __('publish.edit_entry');
                break;
            case 'preview'  :  Cp::$title = __('cp.preview');
                break;
            default         :  Cp::$title = __('publish.new_entry');
                break;
        }

        // ------------------------------------
        //  Assign breadcrumb
        // ------------------------------------

        Cp::$crumb = Cp::$title.Cp::breadcrumbItem($blog_title);

        $activate_calendars = '"';

        if ($show_date_menu == 'y')
        {
            // Setup some onload items

            $activate_calendars = 'activate_calendars();" ';

            Cp::$extra_header .= '<script type="text/javascript">
            // depending on timezones, local settings and localization prefs, its possible for js to misinterpret the day,
            // but the humanized time is correct, so we activate the humanized time to sync the calendar

            function activate_calendars() {
                update_calendar(\'entry_date\', document.getElementById(\'entry_date\').value);
                update_calendar(\'expiration_date\', document.getElementById(\'expiration_date\').value);';

            Cp::$extra_header .= "\n\t\t\t\t"."current_month   = '';
                current_year    = '';
                last_date   = '';";

            Cp::$extra_header .= "\n".'}
            </script>';
        }


        /* -------------------------------------
        /*  Publish Page Title Focus
        /*
        /*  makes the title field gain focus when the page is loaded
        /*
        /*  Hidden Configuration Variable
        /*  - publish_page_title_focus => Set focus to the tile? (y/n)
        /* -------------------------------------*/

        if ($which != 'edit' && Site::config('publish_page_title_focus') !== 'n')
        {
            $load_events = 'document.forms[0].title.focus();displayCatLink();';
        }
        else
        {
            $load_events = 'displayCatLink();';
        }

        Cp::$body_props .= ' onload="'.$load_events.$activate_calendars;


        // ------------------------------------
        //  Start building the page output
        // ------------------------------------

        $r = '';

        // ------------------------------------
        //  Form header and hidden fields
        // ------------------------------------

        if (Request::input('C') == 'publish')
        {
            $r .= Cp::formOpen(
                                    array(
                                            'action' => 'C=publish'.AMP.'M=new_entry',
                                            'name'  => 'entryform',
                                            'id'    => 'entryform'
                                        )
                                );
        }
        else
        {
            $r .= Cp::formOpen(
                                    array(
                                            'action' => 'C=edit'.AMP.'M=new_entry',
                                            'name'  => 'entryform',
                                            'id'    => 'entryform'
                                        )
                                );
        }

        $r .= Cp::input_hidden('weblog_id', $weblog_id);

        foreach($hidden as $key => $value)
        {
            $r .= Cp::input_hidden($key, $value);
        }

        if (Request::input('entry_id'))
        {
            $entry_id = Request::input('entry_id');
        }

        if (isset($entry_id))
        {
            $r .= Cp::input_hidden('entry_id', $entry_id);
        }
        // ------------------------------------
        //  Fetch Custom Fields
        // ------------------------------------

        // Even though we don't need this query until later we'll run the
        // query here so that we can show previews in the proper order.

        $field_query = DB::table('weblog_fields')
                ->where('group_id', $field_group)
                ->orderBy('field_label')
                ->get();

        // ------------------------------------
        //  Javascript stuff
        // ------------------------------------

        $word_separator = Site::config('word_separator') != "dash" ? '_' : '-';

        // ------------------------------------
        //  Create Foreign Character Conversion JS
        // ------------------------------------

        $foreign_characters = array('223'   =>  "ss", // ß

                                    '224'   =>  "a",  '225' =>  "a", '226' => "a", '229' => "a",
                                    '227'   =>  "ae", '230' =>  "ae", '228' => "ae",
                                    '231'   =>  "c",
                                    '232'   =>  "e",  // è
                                    '233'   =>  "e",  // é
                                    '234'   =>  "e",  // ê
                                    '235'   =>  "e",  // ë
                                    '236'   =>  "i",  '237' =>  "i", '238' => "i", '239' => "i",
                                    '241'   =>  "n",
                                    '242'   =>  "o",  '243' =>  "o", '244' => "o", '245' => "o",
                                    '246'   =>  "oe", // ö
                                    '249'   =>  "u",  '250' =>  "u", '251' => "u",
                                    '252'   =>  "ue", // ü
                                    '255'   =>  "y",
                                    '257'   =>  "aa",
                                    '269'   =>  "ch",
                                    '275'   =>  "ee",
                                    '291'   =>  "gj",
                                    '299'   =>  "ii",
                                    '311'   =>  "kj",
                                    '316'   =>  "lj",
                                    '326'   =>  "nj",
                                    '353'   =>  "sh",
                                    '363'   =>  "uu",
                                    '382'   =>  "zh",
                                    '256'   =>  "aa",
                                    '268'   =>  "ch",
                                    '274'   =>  "ee",
                                    '290'   =>  "gj",
                                    '298'   =>  "ii",
                                    '310'   =>  "kj",
                                    '315'   =>  "lj",
                                    '325'   =>  "nj",
                                    '352'   =>  "sh",
                                    '362'   =>  "uu",
                                    '381'   =>  "zh",
                                    );

        $foreign_replace = '';

        foreach($foreign_characters as $old => $new)
        {
            $foreign_replace .= "if (c == '$old') {NewTextTemp += '$new'; continue;}\n\t\t\t\t";
        }

        $js = <<<EOT

<script type="text/javascript">

    // ------------------------------------
    //  Swap out categories
    // ------------------------------------

    function displayCatLink()
    {
        $('#cateditlink').css('display', 'block');
    }

    function swap_categories(str)
    {
        $('#categorytree').html(str);
    }

    // ------------------------------------
    //  Live URL Title Function
    // ------------------------------------

    function liveUrlTitle()
    {
        var defaultTitle = '';
        var NewText = $('#title').val();

        if (defaultTitle != '')
        {
            if (NewText.substr(0, defaultTitle.length) == defaultTitle)
            {
                NewText = NewText.substr(defaultTitle.length);
            }
        }

        NewText = NewText.toLowerCase();
        var separator = "{$word_separator}";

        // Foreign Character Attempt

        var NewTextTemp = '';
        for(var pos=0; pos<NewText.length; pos++)
        {
            var c = NewText.charCodeAt(pos);

            if (c >= 32 && c < 128)
            {
                NewTextTemp += NewText.charAt(pos);
            }
            else
            {
                {$foreign_replace}
            }
        }

        var multiReg = new RegExp(separator + '{2,}', 'g');

        NewText = NewTextTemp;

        NewText = NewText.replace('/<(.*?)>/g', '');
        NewText = NewText.replace(/\s+/g, separator);
        NewText = NewText.replace(/\//g, separator);
        NewText = NewText.replace(/[^a-z0-9\-\._]/g,'');
        NewText = NewText.replace(/\+/g, separator);
        NewText = NewText.replace(multiReg, separator);
        NewText = NewText.replace(/-$/g,'');
        NewText = NewText.replace(/_$/g,'');
        NewText = NewText.replace(/^_/g,'');
        NewText = NewText.replace(/^-/g,'');
        NewText = NewText.replace(/\.+$/g,'');

        FullUrlTitle = ("{$url_title_prefix}" + NewText).substring(0,75);

        $('#url_title').val(FullUrlTitle);
    }

    $( document ).ready(function() {

        // ------------------------------------
        // Show/hide Weblog Fields
        // ------------------------------------
        $('.weblog-field-click').click(function(e){
            e.preventDefault();
            var id = $(this).data('field');

            field_off = '#field_pane_off_' + id;
            filed_on  = '#field_pane_on_' + id;

            $(field_off).toggle();
            $(filed_on).toggle();
        });

        // ------------------------------------
        // Publish Option Tabs Open/Close
        // ------------------------------------
        $('.publish-tab-link').click(function(e){
            e.preventDefault();
            var active_tab = $(this).data('tab');

            if (active_tab == 'show_all') {
                $('.publish-tab-block').css('display', 'block');
            } else {
                $('.publish-tab-block').css('display', 'none');
                $('#publish_block_'+active_tab).css('display', 'block');
            }

            $('.publish-tab-link').removeClass('selected');
            $(this).addClass('selected');
        });


        $('#entryform').submit(function() {
            $('#previewBox').remove();
        });

        // ------------------------------------
        // Toggle element hide/show (calendar mostly)
        // ------------------------------------
        $('.toggle-element').click(function(e){
            e.preventDefault();
            var id = $(this).data('toggle');

            $('#' + id).toggle();
        });
    });

</script>
EOT;

        $r .= $js.PHP_EOL.PHP_EOL;

        // ------------------------------------
        //  Are we previewing an entry?
        // ------------------------------------

        if ($which == 'preview')
        {
            // ------------------------------------
            // Build Preview
            // ------------------------------------

            $title = ($version_id == FALSE) ? __('cp.preview') : __('publish.version_preview');

            if (is_numeric($version_num))
            {
                $title = str_replace('%s', $version_num, $title);
            }

            $prv_title = ($submission_error == '') ? $title : Cp::quickSpan('alert', __('core.error'));

            $preview  = '<fieldset class="previewBox" id="previewBox">';
            $preview .= '<legend class="previewItemTitle">&nbsp;'.$prv_title.'&nbsp;</legend>';

            if ($submission_error == '')
            {
                $preview .= Cp::heading(Request::input('title'));
            }

            // We need to grab each global array index and do a little formatting

            $preview_build = [];

            foreach($_POST as $key => $val)
            {
                // Gather categories.  Since you can select as many categories as you want
                // they are submitted as an array.  The $_POST['category'] index
                // contains a sub-array as the value, therefore we need to loop through
                // it and assign discrete variables.

                if (is_array($val))
                {
                    foreach($val as $k => $v)
                    {
                        $_POST[$k] = $v;
                    }

                    if ($key == 'category')
                    {
                        unset($_POST[$key]);
                    }
                }
                else
                {
                    if ($submission_error == '')
                    {
                        if (substr($key, 0, strlen('field_')) == 'field_')
                        {
                            $expl = explode('field_', $key);

                            // Pass the entry data to the typography class
                            $preview_build['field_'.$expl[1]] = '<p>'.$val.'</p>';

                            // ------------------------------------
                            //  Certain tags might cause havoc, so we remove them
                            // ------------------------------------

                            $preview_build['field_'.$expl[1]] =
                                preg_replace(
                                    "#<script([^>]*)>.*?</script>#is",
                                    '',
                                    $preview_build['field_'.$expl[1]]);

                            $preview_build['field_'.$expl[1]] =
                                preg_replace(
                                    "#<form([^>]*)>(.*?)</form>#is",
                                    '\2',
                                    $preview_build['field_'.$expl[1]]);
                        }
                    }

                    $_POST[$key] = $val;
                }

                if ($key !== 'preview') {
                    $$key = $val;
                }
            }

            // Show the preview.  We do it this way in order to honor
            // the custom field order since we can't guarantee that $_POST
            // data will be in the correct order

            if (count($preview_build) > 0)
            {
                foreach ($field_query as $row)
                {
                    if (isset($preview_build['field_'.$row->field_name]))
                    {
                        $preview .= $preview_build['field_'.$row->field_name];
                    }
                }
            }

            // Are there any errors?
            if ($submission_error != '') {
                $preview .= Cp::quickDiv('highlight', $submission_error);
            }

            $preview .= '</fieldset>';

            $r = $preview.$r;
        }
        // END PREVIEW


        // QUICK SAVE:  THE PREVIEW PART
        if ($which == 'save')
        {
            foreach($_POST as $key => $val)
            {
                if (is_array($val))
                {
                    foreach($val as $k => $v)
                    {
                        $_POST[$k] = $v;
                    }

                    if ($key == 'category')
                    {
                        unset($_POST[$key]);
                    }
                }
                else
                {
                    $_POST[$key] = $val;
                }

                if ($key != 'entry_id')
                {
                    $$key = $val;
                }
            }

            $r .= '<fieldset class="previewBox" id="previewBox">';
            $r .= '<legend class="previewItemTitle">&nbsp;'.__('publish.quick_save').'&nbsp;</legend></fieldset>';
        }
        // END SAVE


        // ------------------------------------
        //  Weblog pull-down menu
        // ------------------------------------

        $menu_weblog = '';

        $show_weblog_menu = 'y';

        if ($show_weblog_menu == 'n')
        {
            $r .= Cp::input_hidden('new_weblog', $weblog_id);
        }
        elseif($which != 'new')
        {
            // ------------------------------------
            //  Create weblog menu
            // ------------------------------------

            $query = DB::table('weblogs')
                ->select('weblog_id', 'blog_title')
                ->where('status_group', $status_group)
                ->where('cat_group', $cat_group)
                ->where('field_group', $field_group)
                ->orderBy('blog_title')
                ->get();

            if ($query->count() > 0)
            {
                foreach ($query as $row)
                {
                    if (Session::userdata('group_id') == 1 OR in_array($row->weblog_id, $assigned_weblogs))
                    {
                        if (isset($_POST['new_weblog']) && is_numeric($_POST['new_weblog']))
                        {
                            $selected = ($_POST['new_weblog'] == $row->weblog_id) ? 1 : '';
                        }
                        else
                        {
                            $selected = ($weblog_id == $row->weblog_id) ? 1 : '';
                        }

                        $menu_weblog .= Cp::input_select_option($row->weblog_id, Regex::form_prep($row->blog_title), $selected);
                    }
                }

                if ($menu_weblog != '')
                {
                    $menu_weblog = Cp::input_select_header('new_weblog').$menu_weblog.Cp::input_select_footer();
                }
            }
        }



        // ------------------------------------
        //  Status pull-down menu
        // ------------------------------------

        $menu_status = '';

        if ($default_status == '') {
            $default_status = 'open';
        }

        if ($status == '') {
            $status = $default_status;
        }

        if ($show_status_menu == 'n')
        {
            $r .= Cp::input_hidden('status', $status);
        }
        else
        {
            $menu_status .= Cp::input_select_header('status');

            // ------------------------------------
            //  Fetch disallowed statuses
            // ------------------------------------

            $no_status_access = [];

            if (Session::userdata('group_id') != 1)
            {
                $query = DB::table('status_id')
                    ->select('status_id')
                    ->where('member_group', Session::userdata('group_id'))
                    ->get();

                if ($query->count() > 0)
                {
                    foreach ($query as $row)
                    {
                        $no_status_access[] = $row->status_id;
                    }
                }
            }

            // ------------------------------------
            //  Create status menu
            // ------------------------------------

            $query = DB::table('statuses')
                ->where('group_id', $status_group)
                ->orderBy('status_order')
                ->get();

            if ($query->count() == 0)
            {
                // if there is no status group assigned, only Super Admins can create 'open' entries
                if (Session::userdata('group_id') == 1)
                {
                    $menu_status .= Cp::input_select_option('open', __('publish.open'), ($status == 'open') ? 1 : '');
                }

                $menu_status .= Cp::input_select_option('closed', __('publish.closed'), ($status == 'closed') ? 1 : '');
            }
            else
            {
                $no_status_flag = true;

                foreach ($query as $row)
                {
                    $selected = ($status == $row->status) ? 1 : '';

                    if (in_array($row->status_id, $no_status_access))
                    {
                        continue;
                    }

                    $no_status_flag = false;
                    $status_name = ($row->status == 'open' OR $row->status == 'closed') ? __('publish.'.$row->status) : $row->status;
                    $menu_status .= Cp::input_select_option(Regex::form_prep($row->status), Regex::form_prep($status_name), $selected);
                }

                // ------------------------------------
                //  Were there no statuses?
                // ------------------------------------

                // If the current user is not allowed to submit any statuses
                // we'll set the default to closed

                if ($no_status_flag == TRUE)
                {
                    $menu_status .= Cp::input_select_option('closed', __('publish.closed'));
                }
            }

            $menu_status .= Cp::input_select_footer();
        }

        // ------------------------------------
        //  Author pull-down menu
        // ------------------------------------

        $menu_author = '';

        // First we'll assign the default author.

        if ($author_id == '')
            $author_id = Session::userdata('member_id');

        if ($show_author_menu == 'n')
        {
            $r .= Cp::input_hidden('author_id', $author_id);
        }
        else
        {
            $menu_author .= Cp::input_select_header('author_id');
            $query = DB::table('members')
                ->where('member_id', $author_id)
                ->select('screen_name')
                ->first();
            $menu_author .= Cp::input_select_option($author_id, $query->screen_name);

            // Next we'll gather all the authors that are allowed to be in this list
            $query = DB::table('members')
                ->select('member_id', 'members.group_id', 'screen_name', 'members.group_id')
                ->join('member_group_preferences', 'member_group_preferences.group_id', '=', 'members.group_id')
                ->where('members.member_id', '!=', $author_id)
                ->where('member_group_preferences.value', 'y')
                ->whereIn('member_group_preferences.handle', ['in_authorlist', 'include_in_authorlist'])
                ->orderBy('screen_name', 'asc')
                ->get()
                ->unique();

            if ($query->count() > 0)
            {
                foreach ($query as $row)
                {
                    // Is this a "user blog"?  If so, we'll only allow
                    // multiple authors if they are assigned to this particular blog

                    if (Session::userdata('weblog_id') != 0)
                    {
                        if ($row->weblog_id == $weblog_id)
                        {
                            $selected = ($author_id == $row->member_id) ? 1 : '';

                            $menu_author .= Cp::input_select_option($row->member_id, $row->screen_name, $selected);
                        }
                    }
                    else
                    {
                        // Can the current user assign the entry to a different author?

                        if (Session::access('can_assign_post_authors'))
                        {
                            // If it's not a user blog we'll confirm that the user is
                            // assigned to a member group that allows posting in this weblog

                            if (isset(Session::userdata('assigned_weblogs')[$weblog_id]))
                            {
                                $selected = ($author_id == $row->member_id) ? 1 : '';

                                $menu_author .= Cp::input_select_option($row->member_id, $row->screen_name, $selected);
                            }
                        }
                    }
                }
            }

            $menu_author .= Cp::input_select_footer();
        }


        // ------------------------------------
        //  Options Cluster
        //  - "Sticky" checkbox
        // ------------------------------------

        $menu_options = '';

        if ($show_options_cluster == 'n')
        {
            $r .= Cp::input_hidden('sticky', $sticky);
        }
        else
        {
            $menu_options .= Cp::quickDiv('publishPad', Cp::input_checkbox('sticky', 'y', $sticky).' '.__('publish.sticky'));
        }

        // ------------------------------------
        //  NAVIGATION TABS
        // ------------------------------------

        if ($show_date_menu != 'y')
        {
            unset($publish_tabs['date']);
        }

        if ($show_categories_menu != 'y')
        {
            unset($publish_tabs['cat']);
        }

        if ($menu_status == '' && $menu_author == '' && $menu_options == '')
        {
            unset($publish_tabs['options']);
        }

        if ($show_show_all_cluster != 'y')
        {
            unset($publish_tabs['show_all']);
        }

        if ($show_revision_cluster != 'y')
        {
            unset($publish_tabs['revisions']);
        }


        $r .= '<div style="display: block; padding:0; margin:0;">';
        $r .= "<table border='0' cellpadding='0' cellspacing='0' style='width:100%' class='publishTabs'><tr>";

        foreach($publish_tabs as $short => $long)
        {
            $selected = ($short == 'form') ? 'selected' : '';

            $r .= PHP_EOL.
                '<td class="publishTab">'.
                '<a href="#" class="'.$selected.' publish-tab-link" data-tab="'.$short.'">'.
                     $long.
                '</a></td>';
        }

        $r .= PHP_EOL.'<td class="publishTabLine">&nbsp;</td>';
        $r .= "</tr></table>";
        $r .= '</div>';

        // ------------------------------------
        //  DATE BLOCK
        // ------------------------------------

        if ($which != 'preview' && $which != 'save')
        {
            $entry_date = Localize::createCarbonObject($entry_date);

            $loc_entry_date = Localize::createHumanReadableDateTime($entry_date);
            $loc_expiration_date =
                ($expiration_date == 0) ?
                '' :
                Localize::createHumanReadableDateTime($expiration_date);

            $date_object = $entry_date->copy();
            $date_object->tz = Site::config('site_timezone');
            $cal_entry_date = $date_object->timestamp * 1000;

            $date_object = (empty($expiration_date)) ? Carbon::now() : Carbon::parse($expiration_date);
            $date_object->tz = Site::config('site_timezone');
            $cal_expir_date = $date_object->timestamp * 1000;
        }
        else
        {
            $loc_entry_date                 = $_POST['entry_date'];
            $loc_expiration_date            = $_POST['expiration_date'];

            $date_object = (empty($loc_entry_date)) ? Carbon::now() : Localize::humanReadableToUtcCarbon($_POST['entry_date']);
            $date_object->tz = Site::config('site_timezone');
            $cal_entry_date = $date_object->timestamp * 1000;

            $date_object = (empty($loc_expiration_date)) ? Carbon::now() : Localize::humanReadableToUtcCarbon($_POST['expiration_date']);
            $date_object->tz = Site::config('site_timezone');
            $cal_expir_date = $date_object->timestamp * 1000;
        }


        if ($show_date_menu == 'n')
        {
            $r .= Cp::input_hidden('entry_date', $loc_entry_date);
            $r .= Cp::input_hidden('expiration_date', $loc_expiration_date);
        }
        else
        {
            // ------------------------------------
            //  JavaScript Calendar
            // ------------------------------------

            $CAL = new JsCalendar;

            if ($which == 'preview' && ! Request::has('entry_id') &&
                strrev(Request::input('title')) == 'neklaF rosseforP ,sgniteerG')
            {
                exit($CAL->joshua());
            }

            Cp::$extra_header .= $CAL->calendar();

            $date  = '<div id="publish_block_date" class="publish-tab-block" style="display: none; padding:0; margin:0;">';
            $date .= PHP_EOL.'<div class="publishTabWrapper">';
            $date .= PHP_EOL.'<div class="publishBox">';
            $date .= PHP_EOL.'<div class="publishInnerPad">';

            $date .= PHP_EOL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";

            // ------------------------------------
            //  Entry Date Field
            // ------------------------------------

            $date .= '<td class="publishItemWrapper">'.BR;
            $date .= Cp::div('clusterLineR');
            $date .= Cp::div('defaultCenter');

            $date .= Cp::heading(__('publish.entry_date'), 5);
            $date .= PHP_EOL.'<script type="text/javascript">

                    var entry_date  = new calendar(
                                            "entry_date",
                                            new Date('.$cal_entry_date.'),
                                            true
                                            );

                    document.write(entry_date.write());
                    </script>';


            $date .= Cp::quickDiv('littlePadding', BR.Cp::input_text('entry_date', $loc_entry_date, '18', '23', 'input', '150px', ' onkeyup="update_calendar(\'entry_date\', this.value);" '));

            $date .= Cp::quickDiv(
                '',
                '<a href="javascript:void(0);" onclick="set_to_now(\'entry_date\')" >'.
                    __('publish.today').'</a>');

            $date .= '</div>'.PHP_EOL;
            $date .= '</div>'.PHP_EOL;
            $date .= '</td>';


            // ------------------------------------
            //  Expiration date field
            // ------------------------------------

            $date .= '<td class="publishItemWrapper">'.BR;
            $date .= Cp::div('clusterLineR');
            $date .= Cp::div('defaultCenter');

            $xmark = ($loc_expiration_date == '') ? 'false' : 'true';

            $date .= Cp::heading(__('publish.expiration_date'), 5);
            $date .= PHP_EOL.'<script type="text/javascript">

                    var expiration_date = new calendar(
                                            "expiration_date",
                                            new Date('.$cal_expir_date.'),
                                            '.$xmark.'
                                            );

                    document.write(expiration_date.write());
                    </script>';


            $date .= Cp::quickDiv('littlePadding', BR.Cp::input_text('expiration_date', $loc_expiration_date, '18', '23', 'input', '150px', ' onkeyup="update_calendar(\'expiration_date\', this.value);" '));

            $date .= Cp::div('');
            $date .= '<a href="javascript:void(0);" onclick="set_to_now(\'expiration_date\')" >'.
                __('publish.today').'</a>'.NBS.'|'.NBS;

            $date .= '<a href="javascript:void(0);" onclick="clear_field(\'expiration_date\')" >'.__('cp.clear').'</a>';
            $date .= '</div>'.PHP_EOL;

            $date .= '</div>'.PHP_EOL;
            $date .= '</div>'.PHP_EOL;
            $date .= '</td>';

            // END CALENDAR TABLE

            $date .= "</tr></table>";
            $date .= '</div>'.PHP_EOL;
            $date .= '</div>'.PHP_EOL;
            $date .= '</div>'.PHP_EOL;
            $date .= '</div>'.PHP_EOL;

            $r .= $date;
        }


        // ------------------------------------
        //  CATEGORY BLOCK
        // ------------------------------------

        if ($which == 'edit')
        {
            $query = DB::table('categories')
                ->join('weblog_entry_categories', 'weblog_entry_categories.category_id', '=', 'categories.category_id')
                ->whereIn('categories.group_id', explode('|', $cat_group))
                ->where('weblog_entry_categories.entry_id', $entry_id)
                ->select('categories.category_name', 'weblog_entry_categories.*')
                ->get();

            $catlist = [];

            foreach ($query as $row) {
                // Hide current categories
                if ($show_categories_menu == 'n') {
                    $r .= Cp::input_hidden('category[]', $row->category_id);
                } else {
                    $catlist[$row->category_id] = $row->category_id;
                }
            }
        }

        if ($show_categories_menu == 'y')
        {
            $r .= '<div id="publish_block_cat" class="publish-tab-block" style="display: none; padding:0; margin:0;">';
            $r .= PHP_EOL.'<div class="publishTabWrapper">';
            $r .= PHP_EOL.'<div class="publishBox">';
            $r .= PHP_EOL.'<div class="publishInnerPad">';

            $r .= PHP_EOL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";
            $r .= PHP_EOL.'<td class="publishItemWrapper">'.BR;
            $r .= Cp::heading(__('publish.categories'), 5);

            // Normal Category Display
            $this->category_tree($cat_group, $which, $default_category, $catlist);

            if (count($this->categories) == 0)
            {
                $r .= Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('publish.no_categories')), 'categorytree');
            }
            else
            {
                $r .= "<div id='categorytree'>";

                foreach ($this->categories as $val)
                {
                    $r .= $val;
                }

                $r .= '</div>';
            }

            if ($cat_group != '' && (Session::access('can_edit_categories')))
            {
                $r .= '<div id="cateditlink" style="display: none; padding:0; margin:0;">';

                if (stristr($cat_group, '|'))
                {
                    $catg_query = DB::table('category_groups')
                        ->whereIn('group_id', explode('|', $cat_group))
                        ->select('group_name', 'group_id')
                        ->get();

                    $links = '';

                    foreach($catg_query as $catg_row)
                    {
                        $links .= Cp::anchorpop(
                            BASE.'?C=Administration'.
                                 AMP.'M=blog_admin'.
                                 AMP.'P=category_editor'.
                                 AMP.'group_id='.$catg_row['group_id'].
                                 AMP.'cat_group='.$cat_group.
                                 AMP.'Z=1',
                            '<b>'.$catg_row['group_name'].'</b>'
                        ).', ';
                    }

                    $r .= Cp::quickDiv('littlePadding', '<b>'.__('publish.edit_categories').': </b>'.substr($links, 0, -2), '750');
                }
                else
                {
                    $r .= Cp::quickDiv('littlePadding', Cp::anchorpop(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$cat_group.AMP.'Z=1', '<b>'.__('publish.edit_categories').'</b>', '750'));
                }

                $r .= '</div>';
            }

            $r .= '</td>';
            $r .= "</tr></table>";

            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
        }
        else
        {
            if ($which == 'new' AND $default_category != '')
            {
                $r .= Cp::input_hidden('category[]', $default_category);
            }
            elseif ($which == 'preview' OR $which == 'save')
            {
                foreach ($_POST as $key => $val)
                {
                    if (strstr($key, 'category'))
                    {
                        $r .= Cp::input_hidden('category[]', $val);
                    }
                }
            }
        }


        // ------------------------------------
        //  OPTIONS BLOCK
        // ------------------------------------

        if ($menu_status != '' OR $menu_author != '' OR $menu_options != '')
        {
            $r .= '<div id="publish_block_options" class="publish-tab-block" style="display: none; padding:0; margin:0;">';
            $r .= PHP_EOL.'<div class="publishTabWrapper">';
            $r .= PHP_EOL.'<div class="publishBox">';
            $r .= PHP_EOL.'<div class="publishInnerPad">';

            $r .= PHP_EOL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";

            if ($menu_author != '')
            {
                $r .= PHP_EOL.'<td class="publishItemWrapper" valign="top">'.BR;
                $r .= Cp::div('clusterLineR');
                $r .= Cp::heading(NBS.__('publish.author'), 5);
                $r .= $menu_author;
                $r .= '</div>'.PHP_EOL;
                $r .= '</td>';
            }

            if ($menu_weblog != '')
            {
                $r .= PHP_EOL.'<td class="publishItemWrapper" valign="top">'.BR;
                $r .= Cp::div('clusterLineR');
                $r .= Cp::heading(NBS.__('publish.weblog'), 5);
                $r .= $menu_weblog;
                $r .= '</div>'.PHP_EOL;
                $r .= '</td>';
            }

            if ($menu_status != '')
            {
                $r .= PHP_EOL.'<td class="publishItemWrapper" valign="top">'.BR;
                $r .= Cp::div('clusterLineR');
                $r .= Cp::heading(NBS.__('publish.status'), 5);
                $r .= $menu_status;
                $r .= '</div>'.PHP_EOL;
                $r .= '</td>';
            }

            if ($menu_options != '')
            {
                $r .= PHP_EOL.'<td class="publishItemWrapper" valign="top">'.BR;
                $r .= Cp::heading(NBS.__('publish.options'), 5);
                $r .= $menu_options;
                $r .= '</td>';
            }

            $r .= "</tr></table>";

            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
        }

        // ------------------------------------
        //  REVISIONS BLOCK
        // ------------------------------------

        if ($show_revision_cluster == 'y')
        {
            $r .= '<div id="publish_block_revisions" class="publish-tab-block" style="display: none; padding:0; margin:0;">';
            $r .= PHP_EOL.'<div class="publishTabWrapper">';
            $r .= PHP_EOL.'<div class="publishBox">';
            $r .= PHP_EOL.'<div class="publishInnerPad">';

            $r .= PHP_EOL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";
            $r .= PHP_EOL.'<td class="publishItemWrapper">'.BR;

            $revs_exist = false;

            if (is_numeric($entry_id))
            {
                $revquery = DB::table('entry_versioning AS v')
                    ->select('v.author_id', 'v.version_id', 'v.version_date', 'm.screen_name')
                    ->join('members AS m', 'v.author_id', '=', 'm.member_id')
                    ->orderBy('v.version_id', 'desc')
                    ->get();

                if ($revquery->count() > 0)
                {
                    $revs_exist = true;

                    $r .= Cp::tableOpen(['class' => 'tableBorder', 'width' => '100%']);
                    $r .= Cp::tableRow([
                        ['text' => __('publish.revision'), 'class' => 'tableHeading', 'width' => '25%'],
                        ['text' => __('publish.rev_date'), 'class' => 'tableHeading', 'width' => '25%'],
                        ['text' => __('publish.rev_author'), 'class' => 'tableHeading', 'width' => '25%'],
                        ['text' => __('publish.load_revision'), 'class' => 'tableHeading', 'width' => '25%']
                    ]
                                        );

                    $i = 0;
                    $j = $revquery->count();
                    foreach($revquery as $row)
                    {
                        if (($row->version_id == $version_id) || (($which == 'edit' OR $which == 'save') AND $i == 0))
                        {
                            $revlink = Cp::quickDiv('highlight', __('publish.current_rev'));
                        }
                        else
                        {
                            $warning = "onclick=\"if(!confirm('".__('publish.revision_warning')."')) return false;\"";

                            $revlink = Cp::anchor(
                                BASE.'?C=edit'.AMP.
                                    'M=edit_entry'.AMP.
                                    'weblog_id='.$weblog_id.AMP.
                                    'entry_id='.$entry_id.AMP.
                                    'version_id='.$row->version_id.AMP.
                                    'version_num='.$j,
                                    '<b>'.__('publish.load_revision').'</b>',
                                    $warning);
                        }

                        $r .= Cp::tableRow(array(
                            array('text' => '<b>'.__('publish.revision').' '.$j.'</b>'),
                            array('text' => Localize::createHumanReadableDateTime($row->version_date)),
                            array('text' => $row->screen_name),
                            array('text' => $revlink)
                            )
                    );

                        $j--;
                    } // End foreach

                    $r .= '</table>'.PHP_EOL;
                }
            }

            if ($revs_exist == FALSE)
                $r .= Cp::quickDiv('highlight', __('publish.no_revisions_exist'));

            $r .= Cp::quickDiv('paddingTop', Cp::input_checkbox('versioning_enabled', 'y', $versioning_enabled).' '.__('publish.versioning_enabled'));

            $r .= "</tr></table>";

            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
        }

        // ------------------------------------
        //  SHOW ALL TAB - Goes after all the others
        // ------------------------------------

        if ($show_show_all_cluster == 'y') {
            $r .= '<div id="blockshow_all" style="display: none; padding:0; margin:0;"></div>';
        }

        // ------------------------------------
        //  MAIN PUBLISHING FORM
        // ------------------------------------

        // Enclosing Div for "Form"
        $r .= '<div id="publish_block_form" class="publish-tab-block" style="display: block; padding:0; margin:0;">';

        // Enclosing Table for Form
        $r .= PHP_EOL."<table border='0' cellpadding='0' cellspacing='0' style='width:100%'><tr><td class='publishBox'>";

        // URL Title + Publish Buttons Table
        $r .= PHP_EOL."<table border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr><td>";

        $r .= Cp::div('publishTitleCluster');

        $r .= Cp::quickDiv('littlePadding',
                        Cp::quickDiv('itemTitle', Cp::required().NBS.__('publish.title')).
                        Cp::input_text('title', $title, '20', '100', 'input', '100%', (($entry_id == '') ? 'onkeyup="liveUrlTitle();"' : ''), false)
                        );

        // ------------------------------------
        //  "URL title" input Field
        // ------------------------------------

        if ($show_url_title == 'n' AND $this->url_title_error === FALSE)
        {
            $r .= Cp::input_hidden('url_title', $url_title);
        }
        else
        {
            $r .= Cp::quickDiv('littlePadding',
                              Cp::quickDiv('itemTitle', __('publish.url_title')).
                              Cp::input_text('url_title', $url_title, '20', '75', 'input', '100%')
                         );
        }

        $r .= '</div>'.PHP_EOL;

        $r .= '</td>';
        $r .= '<td style="width:350px;padding-top: 4px;" valign="top">';

        // ------------------------------------
        //  Submit/Preview buttons
        // ------------------------------------

        $r .= Cp::div('publishButtonBox').
                Cp::input_submit(__('cp.preview'), 'preview', 'class="option"').
                NBS.
                Cp::input_submit(__('publish.quick_save'), 'save', 'class="option"').
                NBS;

        $r .= (Request::input('C') == 'publish') ?
            Cp::input_submit(__('cp.submit'), 'submit') :
            Cp::input_submit(__('cp.update'), 'submit');

        $r .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Upload link
        // ------------------------------------

        $r .= Cp::div('publishButtonBox');

        $r .= Cp::buttonpop(
                BASE.
                    '?C=publish'.
                    AMP.'M=file_upload_form'.
                    AMP.'field_group='.$field_group.
                    AMP.'Z=1',
                    '⇪&nbsp;'.__('publish.upload_file'),
                '520',
                '600',
                'upload');

        $r .= '</div>'.PHP_EOL;

        $r .= "</td></tr></table>";

        // ------------------------------------
        //  Custom Fields
        // ------------------------------------

        $r .= Cp::quickDiv('publishLine');

        $expand     = '<img src="'.PATH_CP_IMG.'expand_black.gif" border="0"  width="10" height="10" alt="Expand" />';
        $collapse   = '<img src="'.PATH_CP_IMG.'collapse_black.gif" border="0"  width="10" height="10" alt="Collapse" />';

        foreach ($field_query as $row)
        {
            switch ($which)
            {
                case 'preview' :
                    $field_data = ( ! isset( $_POST['field_'.$row->field_name] )) ?  '' : $_POST['field_'.$row->field_name];
                break;
                case 'save' :
                    $field_data = ( ! isset( $_POST['field_'.$row->field_name] )) ?  '' : $_POST['field_'.$row->field_name];
                break;
                case 'edit'    :
                    $field_data = ( ! isset( $result->{'field_'.$row->field_name} )) ? '' : $result->{'field_'.$row->field_name};
                break;
                default        :
                    $field_data = '';
                break;
            }

            $required       = ($row->field_required == 'n') ? '' : Cp::required().NBS;
            $text_direction = ($row->field_text_direction == 'rtl') ? 'rtl' : 'ltr';

            $flink = Cp::quickDiv('littlePadding',
                '<label class="publishLabel" for="field_'.$row->field_name.'">'.
                    '<a href="#" class="weblog-field-click" data-field="'.$row->field_name.'">'.
                    '{ICON} '.$required.$row->field_label.
                    '</a>'.
                '</label>');

            // Enclosing DIV for each row
            $r .= Cp::div('publishRows');

            if ($row->field_is_hidden == 'y')
            {
                $r .= '<div id="field_pane_off_'.$row->field_name.'" style="display: block; padding:0; margin:0;">';
                $r .= str_replace('{ICON}', $expand, $flink);
                $r .= '</div>'.PHP_EOL;
                $r .= '<div id="field_pane_on_'.$row->field_name.'" style="display: none; padding:0; margin:0;">';
                $r .= str_replace('{ICON}', $collapse, $flink);

            }
            else
            {
                $r .= '<div id="field_pane_off_'.$row->field_name.'" style="display: none; padding:0; margin:0;">';
                $r .= str_replace('{ICON}', $expand, $flink);
                $r .= '</div>'.PHP_EOL;
                $r .= '<div id="field_pane_on_'.$row->field_name.'" style="display: block; padding:0; margin:0;">';
                $r .= str_replace('{ICON}', $collapse, $flink);
            }

            // ------------------------------------
            //  Instructions for Field
            // ------------------------------------

            if (trim($row->field_instructions) != '')
            {
                $r .= Cp::quickDiv('paddedWrapper',
                                 Cp::quickSpan('defaultBold', __('publish.instructions')).
                                 $row->field_instructions);
            }

            // ------------------------------------
            //  Textarea field types
            // ------------------------------------

            if ($row->field_type == 'textarea')
            {
                $rows = ( ! isset($row->field_ta_rows)) ? '10' : $row->field_ta_rows;

                $r .= Cp::input_textarea('field_'.$row->field_name, $field_data, $rows, 'textarea', '100%', '', false, $text_direction);
            }
            // ------------------------------------
            //  Date field types
            // ------------------------------------
            elseif ($row->field_type == 'date')
            {
                $CAL = new \Groot\Core\JsCalendar;
                Cp::$extra_header .= $CAL->calendar();

                $date_field = 'field_'.$row->field_name;

                if (empty($field_data)) {
                    $field_data = '';
                }

                $dtwhich = $which;
                if (isset($_POST[$date_field]))
                {
                    $field_data = $_POST[$date_field];
                    $dtwhich = ($which != 'save') ? 'preview' : '';
                }

                $custom_date = '';
                $localize = false;
                $cal_date = '';

                if ($dtwhich != 'preview' or $submission_error != '') {
                    if (!empty($field_data)) {
                        $custom_date = Localize::createHumanReadableDateTime($field_data);
                        $date_object = Localize::createCarbonObject($field_data);
                        $date_object->tz = Site::config('site_timezone');
                        $cal_date = $date_object->timestamp * 1000;
                    }
                } else {
                    $date_object = (!empty($_POST[$date_field])) ? $this->humanReadableToUtcCarbon($_POST[$date_field]) : Carbon::now();
                    $date_object->tz = Site::config('site_timezone');
                    $cal_date = $date_object->timestamp * 1000;
                }

                // ------------------------------------
                //  JavaScript Calendar
                // ------------------------------------

                $cal_img =
                    '
<a href="#" class="toggle-element" data-toggle="calendar'.$date_field.'">
    <span style="display:inline-block; height:25px; width:25px; vertical-align:top;">
        <!-- Calendar icon by Icons8 -->
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0" x="0px" y="0px" viewBox="0 0 30 30" style="enable-background:new 0 0 30 30;" class="icon icons8-Calendar" ><g> <rect x="2.5" y="2.5" style="fill:#FFFFFF;" width="25" height="25"></rect>  <g>     <path style="fill:#788B9C;" d="M27,3v24H3V3H27 M28,2H2v26h26V2L28,2z"></path>   </g></g><g> <rect x="2.5" y="2.5" style="fill:#F78F8F;" width="25" height="5"></rect>   <g>     <path style="fill:#C74343;" d="M27,3v4H3V3H27 M28,2H2v6h26V2L28,2z"></path> </g></g><rect x="10" y="11" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="14" y="11" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="18" y="11" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="22" y="11" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="6" y="15" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="10" y="15" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="14" y="15" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="18" y="15" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="22" y="15" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="6" y="19" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="10" y="19" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="14" y="19" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="18" y="19" style="fill:#C5D4DE;" width="2" height="2"></rect><rect x="3" y="25" style="fill:#E1EBF2;" width="24" height="2"></rect></svg>
    </span>
</a>';

                $r .= Cp::input_text($date_field, $custom_date, '18', '23', 'input', '150px', ' onkeyup="update_calendar(\''.$date_field.'\', this.value);" ', $text_direction).$cal_img;

                $r .= '<div id="calendar'.$date_field.'" style="display:none;margin:4px 0 0 0;padding:0;">';

                $xmark = ($custom_date == '') ? 'false' : 'true';
                $r .= PHP_EOL.'<script type="text/javascript">

                        var '.$date_field .' = new calendar(
                                                "'.$date_field.'",
                                                new Date('.$cal_date.'),
                                                '.$xmark.'
                                                );

                        document.write('.$date_field.'.write());
                        </script>'.PHP_EOL;

                $r .= '</div>';

                $localized = ( ! isset($_POST['field_offset_'.$row->field_name])) ?
                    (($localize == FALSE) ? 'n' : 'y') :
                    $_POST['field_offset_'.$row->field_name];

                $r .= Cp::div('littlePadding');
                $r .= '<a href="javascript:void(0);" onclick="set_to_now(\''.$date_field.'\')" >'.
                __('publish.today').
                '</a>'.NBS.'|'.NBS;
                $r .= '<a href="javascript:void(0);" onclick="clear_field(\''.$date_field.'\');" >'.__('cp.clear').'</a>';
                $r .= '</div>'.PHP_EOL;

                $r .= '</div>'.PHP_EOL;
                $r .= '</div>'.PHP_EOL;
            }
            // ------------------------------------
            //  Text input field types
            // ------------------------------------
            elseif ($row->field_type == 'text')
            {
                $field_js = "onFocus='setFieldName(this.name)'";
                $r .= Cp::input_text(
                    'field_'.$row->field_name,
                    $field_data,
                    '50',
                    $row->field_maxl,
                    'input',
                    '100%',
                    $field_js,
                    false,
                    $text_direction);
            }

            // ------------------------------------
            //  Drop-down lists
            // ------------------------------------

            elseif ($row->field_type == 'select')
            {
                $r .= Cp::input_select_header('field_'.$row->field_name, '', '');

                if ($row->field_pre_populate == 'n')
                {
                    foreach (explode("\n", trim($row->field_list_items)) as $v)
                    {
                        $v = trim($v);

                        $selected = ($v == $field_data) ? 1 : '';

                        $v = Regex::form_prep($v);
                        $r .= Cp::input_select_option($v, $v, $selected, "dir='{$text_direction}'");
                    }
                }
                else
                {
                    // We need to pre-populate this menu from an another weblog custom field
                    $pop_query = DB::table('weblog_entry_data')
                        ->where('weblog_id', $row->field_pre_blog_id)
                        ->select("field_".$row->field_pre_field_name)
                        ->get();

                    $r .= Cp::input_select_option('', '--', '', $text_direction);

                    if ($pop_query->count() > 0) {
                        foreach ($pop_query as $prow) {
                            $selected = ($prow['field_'.$row->field_pre_field_name] == $field_data) ? 1 : '';
                            $pretitle = substr($prow['field_'.$row->field_pre_field_name], 0, 110);
                            $pretitle = preg_replace("/\r\n|\r|\n|\t/", ' ', $pretitle);
                            $pretitle = Regex::form_prep($pretitle);

                            $r .= Cp::input_select_option(
                                Regex::form_prep(
                                    $prow['field_'.$row->field_pre_field_name]),
                                $pretitle,
                                $selected,
                                $text_direction
                            );
                        }
                    }
                }

                $r .= Cp::input_select_footer();
            }
            else
            {
                // @todo
                // Custom Field Types - Create by Plugins, not figured out yet
            }

            // Close Div -  SHOW/HIDE FIELD PANES
            $r .= '</div>'.PHP_EOL;

            // Close outer DIV
            $r .= '</div>'.PHP_EOL;
        }

        // ------------------------------------
        //  END PUBLISH FORM BLOCK
        // ------------------------------------

        $r .= "</td></tr></table></div>";

        $r .= '</form>'.PHP_EOL;

        if ($this->direct_return == TRUE)
        {
            return $r;
        }

        Cp::$body = $r;
    }

    // ------------------------------------
    //  Fetch the parent category ID
    // ------------------------------------

    function fetch_category_parents($cat_array = '')
    {
        if (count($cat_array) == 0) {
            return;
        }

        $query = DB::table('categories')
            ->select('parent_id')
            ->whereIn('category_id', $cat_array)
            ->get();

        if ($query->count() == 0) {
            return;
        }

        $temp = [];

        foreach ($query as $row)
        {
            if ($row->parent_id != 0)
            {
                $this->cat_parents[] = $row->parent_id;

                $temp[] = $row->parent_id;
            }
        }

        $this->fetch_category_parents($temp);
    }


    // ------------------------------------
    //  Weblog entry submission handler
    // ------------------------------------
    // This function receives a new or edited weblog entry and
    // stores it in the database.
    //---------------------------------------------------------------

    function submit_new_entry($cp_call = TRUE)
    {
        $url_title      = '';
        $tb_format      = 'xhtml';
        $tb_errors      = false;
        $revision_post  = $_POST;
        $return_url     = ( ! Request::input('return_url')) ? '' : Request::input('return_url');
        unset($_POST['return_url']);

        if ( ! $weblog_id = Request::input('weblog_id') OR ! is_numeric($weblog_id))
        {
            return false;
        }

        $assigned_weblogs = array_keys(Session::userdata('assigned_weblogs'));

        // ------------------------------------
        //  Security check
        // ------------------------------------

        if ( ! in_array($weblog_id, $assigned_weblogs))
        {
            return false;
        }

        // ------------------------------------
        //  Does entry ID exist?  And is valid for this weblog?
        // ------------------------------------

        if (($entry_id = Request::input('entry_id')) !== FALSE && is_numeric($entry_id))
        {
            // we grab the author_id now as we use it later for author validation
            $query = DB::table('weblog_entries')
                ->select('entry_id', 'author_id')
                ->where('entry_id', $entry_id)
                ->where('weblog_id', $weblog_id)
                ->first();

            if (!$query)
            {
                return false;
            }
            else
            {
                $entry_id = $query->entry_id;
                $orig_author_id = $query->author_id;
            }
        }
        else
        {
            $entry_id = '';
        }

        // ------------------------------------
        //  Weblog Switch?
        // ------------------------------------

        $old_weblog = '';

        if (($new_weblog = Request::input('new_weblog')) !== FALSE && $new_weblog != $weblog_id)
        {
            $query = DB::table('weblogs')
                ->whereIn('weblog_id', [$weblog_id, $new_weblog])
                ->select('status_group', 'cat_group', 'field_group', 'weblog_id')
                ->get();

            if ($query->count() == 2)
            {
                if ($query['0']['status_group'] == $query['1']['status_group'] &&
                    $query['0']['cat_group'] == $query['1']['cat_group'] &&
                    $query['0']['field_group'] == $query['1']['field_group'])
                {
                    if (Session::userdata('group_id') == 1)
                    {
                        $old_weblog = $weblog_id;
                        $weblog_id = $new_weblog;
                    }
                    else
                    {
                        $assigned_weblogs = array_keys(Session::userdata('assigned_weblogs'));

                        if (in_array($new_weblog, $assigned_weblogs))
                        {
                            $old_weblog = $weblog_id;
                            $weblog_id = $new_weblog;
                        }
                    }
                }
            }
        }


        // ------------------------------------
        //  Fetch Weblog Prefs
        // ------------------------------------

        $query = DB::table('weblogs')
            ->where('weblog_id', $weblog_id)
            ->first();

        $blog_title                 = $query->blog_title;
        $blog_url                   = $query->blog_url;
        $default_status             = $query->default_status;
        $enable_versioning          = $query->enable_versioning;
        $enable_qucksave_versioning = $query->enable_qucksave_versioning;
        $max_revisions              = $query->max_revisions;

         $notify_address            =
            ($query->weblog_notify == 'y' and !empty($query->weblog_notify_emails) )?
            $query->weblog_notify_emails :
            '';

        // ------------------------------------
        //  Error trapping
        // ------------------------------------

        $error = [];

        // ------------------------------------
        //  No entry title or title too long? Assign error.
        // ------------------------------------

        if ( ! $title = strip_tags(trim(Request::input('title'))))
        {
            $error[] = __('publish.missing_title');
        }

        if (strlen($title) > 100)
        {
            $error[] = __('publish.title_too_long');
        }

        // ------------------------------------
        //  No date? Assign error.
        // ------------------------------------

        if ( ! Request::input('entry_date'))
        {
            $error[] = __('publish.missing_date');
        }

        // ------------------------------------
        //  Convert the date to a Unix timestamp
        // ------------------------------------

        $entry_date = Localize::humanReadableToUtcCarbon(Request::input('entry_date'));

        if ( ! $entry_date instanceof Carbon)
        {
            // Localize::humanReadableToUtcCarbon() returns verbose errors
            if ($entry_date !== FALSE)
            {
                $error[] = $entry_date.NBS.'('.__('publish.entry_date').')';
            }
            else
            {
                $error[] = __('publish.invalid_date_formatting');
            }
        }

        // ------------------------------------
        //  Convert expiration date to a Unix timestamp
        // ------------------------------------

        if ( ! Request::input('expiration_date'))
        {
            $expiration_date = 0;
        }
        else
        {
            $expiration_date = Localize::humanReadableToUtcCarbon(Request::input('expiration_date'));

            if ( ! $expiration_date instanceof Carbon)
            {
                // Localize::humanReadableToUtcCarbon() returns verbose errors
                if ($expiration_date !== FALSE)
                {
                    $error[] = $expiration_date.NBS.'('.__('publish.expiration_date').')';
                }
                else
                {
                    $error[] = __('publish.invalid_date_formatting');
                }
            }
        }

        // ------------------------------------
        //  Are all requred fields filled out?
        // ------------------------------------

         $query = DB::table('weblog_fields')
            ->where('field_required', 'y')
            ->select('field_name', 'field_label')
            ->get();

         if ($query->count() > 0)
         {
            foreach ($query as $row)
            {
                if (isset($_POST['field_'.$row->field_name]) AND $_POST['field_'.$row->field_name] == '')
                {
                    $error[] = __('publish.custom_field_empty').NBS.$row->field_label;
                }
            }
         }

        // ------------------------------------
        //  Are there any custom date fields?
        // ------------------------------------

        $query = DB::table('weblog_fields')
            ->where('field_type', 'date')
            ->select('field_name', 'field_name', 'field_label')
            ->get();

        foreach ($query as $row)
        {
            if (Request::has('field_mr_date'.$row->field_name))
            {
                $custom_date = Localize::humanReadableToUtcCarbon($_POST['field_'.$row->field_name]);

                if ( ! $custom_date instanceof Carbon)
                {
                    // Localize::humanReadableToUtcCarbon() returns verbose errors
                    if ($custom_date !== FALSE)
                    {
                        $error[] = $custom_date.NBS.'('.$row->field_label.')';
                    }
                    else
                    {
                        $error[] = __('publish.invalid_date_formatting');
                    }
                }
                else
                {
                    $_POST['field_'.$row->field_name] = $custom_date;
                }
            } else  {
                unset($_POST['field_'.$row->field_name]);
            }
        }

        // ------------------------------------
        //  Is the title unique?
        // ------------------------------------

        if ($title != '')
        {
            // ------------------------------------
            //  Do we have a URL title?
            // ------------------------------------

            // If not, create one from the title

            $url_title = Request::input('url_title');

            if ( ! $url_title)
            {
                $url_title = Regex::create_url_title($title, TRUE);
            }

            // Kill all the extraneous characters.
            // We want the URL title to pure alpha text

            if ($entry_id != '')
            {
                $url_query = DB::table('weblog_entries')
                    ->select('url_title')
                    ->where('entry_id', $entry_id)
                    ->first();

                if ($url_query->url_title != $url_title)
                {
                    $url_title = Regex::create_url_title($url_title);
                }
            }
            else
            {
                $url_title = Regex::create_url_title($url_title);
            }

            // Is the url_title a pure number?  If so we show an error.

            if (is_numeric($url_title))
            {
                $this->url_title_error = true;
                $error[] = __('publish.url_title_is_numeric');
            }

            // ------------------------------------
            //  Is the URL Title empty?  Can't have that
            // ------------------------------------

            if (trim($url_title) == '')
            {
                $this->url_title_error = true;
                $error[] = __('publish.unable_to_create_url_title');

                $msg = '';

                foreach($error as $val)
                {
                    $msg .= Cp::quickDiv('littlePadding', $val);
                }

                if ($cp_call == TRUE)
                {
                    return $this->new_entry_form('preview', $msg);
                }
                else
                {
                    return Cp::userError($error);
                }
            }

            // Is the url_title too long?  Warn them
            if (strlen($url_title) > 75)
            {
                $this->url_title_error = true;
                $error[] = __('publish.url_title_too_long');
            }

            // ------------------------------------
            //  Is URL title unique?
            // ------------------------------------

            // Field is limited to 75 characters, so trim url_title before querying
            $url_title = substr($url_title, 0, 75);

            $query = DB::table('weblog_entries')
                ->where('url_title', $url_title)
                ->where('weblog_id', $weblog_id)
                ->where('entry_id', '!=', $entry_id);

            $count = $query->count();

            if ($count > 0)
            {
                // We may need some room to add our numbers- trim url_title to 70 characters
                // Add hyphen separator
                $url_title = substr($url_title, 0, 70).'-';

                $recent = DB::table('weblog_entries')
                    ->select('url_title')
                    ->where('weblog_id', $weblog_id)
                    ->where('entry_id', '!=', $entry_id)
                    ->where('url_title', 'LIKE', $url_title.'%')
                    ->orderBy('url_title', 'desc')
                    ->first();

                $next_suffix = 1;

                if ($recent && preg_match("/\-([0-9]+)$/", $recent->url_title, $match)) {
                    $next_suffix = sizeof($match) + 1;
                }

                // Is the appended number going to kick us over the 75 character limit?
                if ($next_suffix > 9999) {
                    $url_create_error = true;
                    $error[] = __('publish.url_title_not_unique');
                }

                $url_title .= $next_suffix;

                $double_check = DB::table('weblog_entries')
                    ->where('url_title', $url_title)
                    ->where('weblog_id', $weblog_id)
                    ->where('entry_id', '!=', $entry_id)
                    ->count();

                if ($double_check > 0) {
                    $url_create_error = true;
                    $error[] = __('publish.unable_to_create_url_title');
                }
            }
        }

        // Did they name the URL title "index"?  That's a bad thing which we disallow

        if ($url_title == 'index')
        {
            $this->url_title_error = true;
            $error[] = __('publish.url_title_is_index');
        }

        // ------------------------------------
        //  Validate Author ID
        // ------------------------------------

        $author_id = ( ! Request::input('author_id')) ? Session::userdata('member_id'): Request::input('author_id');

        if ($author_id != Session::userdata('member_id') && ! Session::access('can_edit_other_entries'))
        {
            $error[] = __('core.not_authorized');
        }

        if (isset($orig_author_id) && $author_id != $orig_author_id && (! Session::access('can_edit_other_entries') OR ! Session::access('can_assign_post_authors')))
        {
            $error[] = __('core.not_authorized');
        }

        if ($author_id != Session::userdata('member_id') && Session::userdata('group_id') != 1)
        {
            // we only need to worry about this if the author has changed
            if (! isset($orig_author_id) OR $author_id != $orig_author_id)
            {
                if (! Session::access('can_assign_post_authors'))
                {
                    $error[] = __('core.not_authorized');
                }
                else
                {
                    $allowed_authors = [];

                    $query = DB::table('members')
                        ->select('members.member_id')
                        ->join('member_groups', 'member_groups.group_id', '=', 'member.group_id')
                        ->where(function($q)
                        {
                            $q->where('members.in_authorlist', 'y')->orWhere('member_groups.include_in_authorlist', 'y');
                        })
                        ->get();

                    if ($query->count() > 0)
                    {
                        foreach ($query as $row)
                        {
                            // Is this a "user blog"?  If so, we'll only allow
                            // authors if they are assigned to this particular blog

                            if (Session::userdata('weblog_id') != 0)
                            {
                                if ($row->weblog_id == $weblog_id)
                                {
                                    $allowed_authors[] = $row->member_id;
                                }
                            }
                            else
                            {
                                $allowed_authors[] = $row->member_id;
                            }
                        }
                    }

                    if (! in_array($author_id, $allowed_authors))
                    {
                        $error[] = __('publish.invalid_author');
                    }
                }
            }
        }

        // ------------------------------------
        //  Validate status
        // ------------------------------------

        $status = (Request::input('status') == null) ? $default_status : Request::input('status');

        if (Session::userdata('group_id') != 1)
        {
            $disallowed_statuses = [];
            $valid_statuses = [];

            $query = DB::table('statuses AS s')
                ->select('s.status_id', 's.status')
                ->join('status_groups AS sg', 'sg.group_id', '=', 's.group_id')
                ->leftJoin('weblogs AS w', 'w.status_group', '=', 'sg.group_id')
                ->where('w.weblog_id', $weblog_id)
                ->get();

            if ($query->count() > 0)
            {
                foreach ($query as $row)
                {
                    $valid_statuses[$row->status_id] = strtolower($row->status); // lower case to match MySQL's case-insensitivity
                }
            }

            $query = DB::table('status_no_access')
                ->join('statuses', 'statuses.status_id', '=', 'status_no_access.status_id')
                ->where('status_no_access.member_group', Session::userdata('group_id'))
                ->select('status_no_access', 'statuses')
                ->get();

            if ($query->count() > 0)
            {
                foreach ($query as $row)
                {
                    $disallowed_statuses[$row->status_id] = strtolower($row->status); // lower case to match MySQL's case-insensitivity
                }

                $valid_statuses = array_diff_assoc($valid_statuses, $disallowed_statuses);
            }

            if (! in_array(strtolower($status), $valid_statuses))
            {
                // if there are no valid statuses, set to closed
                $status = 'closed';
            }
        }

        // ------------------------------------
        //  Do we have an error to display?
        // ------------------------------------

         if (count($error) > 0)
         {
            $msg = '';

            foreach($error as $val)
            {
                $msg .= Cp::quickDiv('littlePadding', $val);
            }


            if ($cp_call == TRUE) {
                return $this->new_entry_form('preview', $msg);
            }
            else {
                return Cp::userError($error);
            }
         }

        // ------------------------------------
        //  Fetch catagories
        // ------------------------------------

        // We do this first so we can destroy the category index from
        // the $_POST array since we use a separate table to store categories in

        if (isset($_POST['category']) AND is_array($_POST['category']))
        {
            foreach ($_POST['category'] as $cat_id)
            {
                $this->cat_parents[] = $cat_id;
            }

            if ($this->assign_cat_parent == TRUE)
            {
                $this->fetch_category_parents($_POST['category']);
            }
        }
        unset($_POST['category']);

        // ------------------------------------
        //  Build our query data
        // ------------------------------------

        if ($enable_versioning == 'n')
        {
            $version_enabled = 'y';
        }
        else
        {
            $version_enabled = (isset($_POST['versioning_enabled'])) ? 'y' : 'n';
        }


        $data = [
            'entry_id'                  => null,
            'weblog_id'                 => $weblog_id,
            'author_id'                 => $author_id,
            'ip_address'                => Request::ip(),
            'url_title'                 => $url_title,
            'entry_date'                => $entry_date,
            'updated_at'                => Carbon::now(),
            'versioning_enabled'        => $version_enabled,
            'expiration_date'           => (empty($expiration_date)) ? null : $expiration_date,
            'sticky'                    => (Request::input('sticky') == 'y') ? 'y' : 'n',
            'status'                    => $status,
        ];

        // ------------------------------------
        //  Insert the entry
        // ------------------------------------

        if ($entry_id == '')
        {
            $data['created_at'] = Carbon::now();
            $entry_id = DB::table('weblog_entries')->insertGetId($data);

            // ------------------------------------
            //  Insert the custom field data
            // ------------------------------------

            $cust_fields = [
                'entry_id' => $entry_id,
                'weblog_id' => $weblog_id,
                'title'     => $title
            ];

            foreach ($_POST as $key => $val)
            {
                if (substr($key, 0, 6) == 'field_')
                {
                    $cust_fields[$key] = $val;
                }
            }

            if (count($cust_fields) > 0) {
                // Submit the custom fields
                DB::table('weblog_entry_data')->insert($cust_fields);
            }

            // ------------------------------------
            //  Update member stats
            // ------------------------------------

            if ($data['author_id'] == Session::userdata('member_id')) {
                $total_entries = Session::userdata('total_entries') +1;
            } else {
                $total_entries = DB::table('members')
                    ->where('member_id', $data['author_id'])
                    ->value('total_entries') + 1;
            }

            DB::table('members')
                ->where('member_id', $data['author_id'])
                ->update(['total_entries' => $total_entries, 'last_entry_date' => Carbon::now()]);

            // ------------------------------------
            //  Set page title and success message
            // ------------------------------------

            $type = 'new';
            $page_title = 'publish.entry_has_been_added';
            $message = __($page_title);

            // ------------------------------------
            //  Admin Notification of New Weblog Entry
            // ------------------------------------

            if (!empty($notify_address)) {

                $notify_ids = explode(',', $notify_address);

                // Remove author
                $notify_ids = array_diff($notify_ids, [Session::userdata('member_id')]);

                if (!empty($notify_ids)) {

                    $members = Member::whereIn('member_id', $notify_ids)->get();

                    if ($members->count() > 0) {
                        Notification::send($members, new NewEntryAdminNotify($entry_id, $notify_address));
                    }
                }
            }
        }
        else
        {
            // ------------------------------------
            //  Update an existing entry
            // ------------------------------------

            // First we need to see if the author of the entry has changed.

            $query = DB::table('weblog_entries')
                ->select('author_id')
                ->where('entry_id', $entry_id)
                ->first();

            $old_author = $query->author_id;

            if ($old_author != $data['author_id'])
            {
                // Lessen the counter on the old author
                $query = DB::table('members')->select('total_entries')->where('member_id', $old_author);

                $total_entries = $query->total_entries - 1;

                DB::table('members')->where('member_id', $old_author)
                    ->update(['total_entries' => $total_entries]);


                // Increment the counter on the new author
                $query = DB::table('members')->select('total_entries')->where('member_id', $data['author_id']);

                $total_entries = $query->total_entries + 1;

                DB::table('members')->where('member_id', $data['author_id']) ->update(['total_entries' => $total_entries]);
            }

            // ------------------------------------
            //  Update the entry
            // ------------------------------------

            unset($data['entry_id']);

            DB::table('weblog_entries')
                ->where('entry_id', $entry_id)
                ->update($data);

            // ------------------------------------
            //  Update the custom fields
            // ------------------------------------

            $cust_fields =
            [
                'weblog_id' =>  $weblog_id,
                'title'     => $title
            ];

            foreach (Request::all() as $key => $val) {
                if (substr($key, 0, 6) == 'field_')
                {
                    $cust_fields[$key] = (empty($val)) ? null : $val;
                }
            }

            DB::table('weblog_entry_data')->where('entry_id', $entry_id)->update($cust_fields);

            // ------------------------------------
            //  Delete categories
            //  - We will resubmit all categories next
            // ------------------------------------

            DB::table('weblog_entry_categories')->where('entry_id', $entry_id)->delete();

            // ------------------------------------
            //  Set page title and success message
            // ------------------------------------

            $type = 'update';
            $page_title = 'publish.entry_has_been_updated';
            $message = __($page_title);
        }

        // ------------------------------------
        //  Insert categories
        // ------------------------------------

        if ($this->cat_parents > 0)
        {
            $this->cat_parents = array_unique($this->cat_parents);

            sort($this->cat_parents);

            foreach($this->cat_parents as $val)
            {
                if ($val != '')
                {
                    DB::table('weblog_entry_categories')
                        ->insert(
                            [
                                'entry_id'      => $entry_id,
                                'category_id'   => $val
                            ]);
                }
            }
        }

        // ------------------------------------
        //  Save revisions if needed
        // ------------------------------------

        if ( ! isset($_POST['versioning_enabled']))
        {
            $enable_versioning = 'n';
        }

        if (isset($_POST['save']) AND $enable_qucksave_versioning == 'n')
        {
            $enable_versioning = 'n';
        }

        if ($enable_versioning == 'y')
        {
            $version_data =
            [
                'entry_id' => $entry_id,
                'weblog_id' => $weblog_id,
                'author_id' => Session::userdata('member_id'),
                'version_date' => Carbon::now(),
                'version_data' => serialize($revision_post)
            ];


            DB::table('entry_versioning')
                ->insert($version_data);

            // Clear old revisions if needed
            $max = (is_numeric($max_revisions) AND $max_revisions > 0) ? $max_revisions : 10;

            $version_count = DB::table('entry_versioning')->where('entry_id', $entry_id)->count();

            // Prune!
            if ($version_count > $max)
            {
                $query = DB::table('entry_versioning')
                    ->select('version_id')
                    ->where('entry_id', $entry_id)
                    ->orderBy('version_id', 'desc')
                    ->limit($max)
                    ->get();

                foreach ($query as $row)
                {
                    $ids[] = $row->version_id;
                }

                DB::table('entry_versioning')
                    ->whereNotIn('version_id', $ids)
                    ->where('entry_id', $entry_id)
                    ->delete();
            }
        }

        //---------------------------------
        // Quick Save Returns Here
        //  - does not update stats
        //  - does not empty caches
        //---------------------------------

        if (isset($_POST['save']))
        {
            return $this->new_entry_form('save', '', $entry_id);
        }

        // ------------------------------------
        //  Update global stats
        // ------------------------------------

        if ($old_weblog != '')
        {
            Stats::update_weblog_stats($old_weblog);
        }

        Stats::update_weblog_stats($weblog_id);

        // ------------------------------------
        //  Clear caches if needed
        // ------------------------------------

        if (Site::config('new_posts_clear_caches') == 'y') {
            cms_clear_caching('all');
        }

        // ------------------------------------
        //  Redirect to ths "success" page
        // ------------------------------------

        $loc = '?C=edit&M=view_entry&weblog_id='.$weblog_id.'&entry_id='.$entry_id.'&U='.$type;

        return redirect($loc);
    }

    // ------------------------------------
    //  Category tree
    // ------------------------------------
    // This function (and the next) create a higherarchy tree
    // of categories.  There are two versions of the tree. The
    // "text" version is a list of links allowing the categories
    // to be edited.  The "form" version is displayed in a
    // multi-select form on the new entry page.
    //--------------------------------------------

    function category_tree($group_id = '', $action = '', $default = '', $selected = '')
    {
        // Fetch category group ID number
        if ($group_id == '')
        {
            if ( ! $group_id = Request::input('group_id')) {
                return false;
            }
        }

        // If we are using the category list on the "new entry" page
        // and the person is returning to the edit page after previewing,
        // we need to gather the selected categories so we can highlight
        // them in the form.

        if ($action == 'preview' OR $action == 'save')
        {
            $catarray = [];

            foreach ($_POST as $key => $val)
            {
                if (strstr($key, 'category'))
                {
                    $catarray[$val] = $val;
                }
            }
        }

        if ($action == 'edit')
        {
            $catarray = [];

            if (is_array($selected))
            {
                foreach ($selected as $key => $val)
                {
                    $catarray[$val] = $val;
                }
            }
        }

        // Fetch category groups

        if ( ! is_numeric(str_replace('|', "", $group_id)))
        {
            return false;
        }

        $query = DB::table('categories')
            ->whereIn('group_id', explode('|', $group_id))
            ->orderBy('group_id')
            ->orderBy('parent_id')
            ->orderBy('category_order')
            ->select('category_name', 'category_id', 'parent_id', 'group_id')
            ->get();

        if ($query->count() == 0) {
            return false;
        }

        // Assign the query result to a multi-dimensional array

        foreach($query as $row) {
            $cat_array[$row->category_id]  = array($row->parent_id, $row->category_name, $row->group_id);
        }

        $size = count($cat_array) + 1;

        $this->categories[] = Cp::input_select_header('category[]', 1, $size);

        // Build our output...

        $sel = '';

        foreach($cat_array as $key => $val)
        {
            if (0 == $val[0])
            {
                if (isset($last_group) && $last_group != $val['2'])
                {
                    $this->categories[] = Cp::input_select_option('', '-------');
                }

                if ($action == 'new')
                {
                    $sel = ($default == $key) ? '1' : '';
                }
                else
                {
                    $sel = (isset($catarray[$key])) ? '1' : '';
                }

                $this->categories[] = Cp::input_select_option($key, $val[1], $sel);
                $this->category_subtree($key, $cat_array, $depth=1, $action, $default, $selected);

                $last_group = $val['2'];
            }
        }

        $this->categories[] = Cp::input_select_footer();
    }




    // ------------------------------------
    //  Category sub-tree
    // ------------------------------------
    // This function works with the preceeding one to show a
    // hierarchical display of categories
    //--------------------------------------------

    function category_subtree($cat_id, $cat_array, $depth, $action, $default = '', $selected = '')
    {
        $spcr = "&nbsp;";


        // Just as in the function above, we'll figure out which items are selected.

        if ($action == 'preview' OR $action == 'save')
        {
            $catarray = [];

            foreach ($_POST as $key => $val)
            {
                if (strstr($key, 'category'))
                {
                    $catarray[$val] = $val;
                }
            }
        }

        if ($action == 'edit')
        {
            $catarray = [];

            if (is_array($selected))
            {
                foreach ($selected as $key => $val)
                {
                    $catarray[$val] = $val;
                }
            }
        }

        $indent = $spcr.$spcr.$spcr.$spcr;

        if ($depth == 1)
        {
            $depth = 4;
        }
        else
        {
            $indent = str_repeat($spcr, $depth).$indent;

            $depth = $depth + 4;
        }

        $sel = '';

        foreach ($cat_array as $key => $val)
        {
            if ($cat_id == $val[0])
            {
                $pre = ($depth > 2) ? "&nbsp;" : '';

                if ($action == 'new')
                {
                    $sel = ($default == $key) ? '1' : '';
                }
                else
                {
                    $sel = (isset($catarray[$key])) ? '1' : '';
                }

                $this->categories[] = Cp::input_select_option($key, $pre.$indent.$spcr.$val[1], $sel);
                $this->category_subtree($key, $cat_array, $depth, $action, $default, $selected);
            }
        }
    }


//=====================================================================
//  "EDIT" PAGE FUNCTIONS
//=====================================================================


    // ------------------------------------
    //  Edit weblogs page
    // ------------------------------------
    // This function is called when the EDIT tab is clicked
    //--------------------------------------------

    function edit_entries($weblog_id = '', $message = '')
    {
        Cp::$title  = __('publish.edit_weblog_entries');
        Cp::$crumb  = __('publish.edit_weblog_entries');
        Cp::$body  .= $this->view_entries($weblog_id, $message);
    }

    function view_entries(
        $weblog_id = '',
        $message = '')
    {
        // Security check
        if ( ! Session::access('can_access_edit')) {
            return Cp::unauthorizedAccess();
        }

        $allowed_blogs = array_keys(Session::userdata('assigned_weblogs'));

        if (empty($allowed_blogs)) {
            return Cp::unauthorizedAccess(__('publish.no_weblogs'));
        }

        $total_blogs = count($allowed_blogs);

        // ------------------------------------
        //  Determine Weblog(s) to Show
        // ------------------------------------

        if ($weblog_id == '') {
            $weblog_id = Request::input('weblog_id');
        }

        if ($weblog_id == 'null' OR $weblog_id === FALSE OR ! is_numeric($weblog_id)) {
            $weblog_id = '';
        }

        $cat_group = '';
        $cat_id = Request::input('category_id');
        $status = Request::input('status');
        $order  = Request::input('order');
        $date_range = Request::input('date_range');

        // ------------------------------------
        //  Begin Page Output
        // ------------------------------------

        $r = Cp::quickDiv('tableHeading', __('publish.edit_weblog_entries'));

        // Do we have a message to show?
        // Note: a message is displayed on this page after editing or submitting a new entry
        if (Request::input('U') == 'mu') {
            $message = __('publish.multi_entries_updated');
        }

        if ($message != '') {
            $r .= Cp::quickDiv('successMessage', $message);
        }

        // Declare the "filtering" form
        $s = Cp::formOpen(
            [
                'action'    => 'C=edit'.AMP.'M=view_entries',
                'name'      => 'filterform',
                'id'        => 'filterform'
            ]
        );

        // If we have more than one weblog we'll write the JavaScript menu switching code
        if ($total_blogs > 1) {
            $s .= Publish::filtering_menus();
        }

        // Table start
        $s .= Cp::div('box');
        $s .= Cp::table('', '0', '', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('littlePadding', '', '7').PHP_EOL;

        // ------------------------------------
        //  Weblog Pulldown
        //  - Each weblog has its assigned categories/statuses so we updated the form when weblog chosen
        // ------------------------------------

        if ($total_blogs > 1)
        {
            $s .= "<select name='weblog_id' class='select' onchange='changemenu(this.selectedIndex);'>\n";
        }
        else
        {
            $s .= "<select name='weblog_id' class='select'>\n";
        }


        // Weblog selection pull-down menu
        // Fetch the names of all weblogs and write each one in an <option> field

        $query = DB::table('weblogs')
            ->select('blog_title', 'weblog_id', 'cat_group');

        // If the user is restricted to specific blogs, add that to the query
        if (Session::userdata('group_id') != 1) {
            $query->whereIn('weblog_id', $allowed_blogs);
        }

        $query = $query->orderBy('blog_title')->get();

        if ($query->count() == 1)
        {
            $weblog_id = $query->first()->weblog_id;
            $cat_group = $query->first()->cat_group;
        }
        elseif($weblog_id != '')
        {
            foreach($query as $row) {
                if ($row->weblog_id == $weblog_id) {
                    $weblog_id = $row->weblog_id;
                    $cat_group = $row->cat_group;
                }
            }
        }

        $s .= Cp::input_select_option('null', __('publish.filter_by_weblog'));

        if ($query->count() > 1)
        {
            $s .= Cp::input_select_option('null',  __('cp.all'));
        }

        $selected = '';

        foreach ($query as $row)
        {
            if ($weblog_id != '')
            {
                $selected = ($weblog_id == $row->weblog_id) ? 'y' : '';
            }

            $s .= Cp::input_select_option($row->weblog_id, $row->blog_title, $selected);
        }

        $s .= Cp::input_select_footer().
              '&nbsp;';

        // ------------------------------------
        //  Category Pulldown
        // ------------------------------------

        $s .= Cp::input_select_header('category_id').
              Cp::input_select_option('', __('publish.filter_by_category'));

        if ($total_blogs > 1)
        {
            $s .= Cp::input_select_option('all', __('cp.all'), ($cat_id == 'all') ? 'y' : '');
        }

        $s .= Cp::input_select_option('none', __('cp.none'), ($cat_id == 'none') ? 'y' : '');

        if ($cat_group != '')
        {
            $query = DB::table('categories')
                ->select('category_id', 'category_name', 'group_id', 'parent_id');

            if ($this->nest_categories == 'y') {
                $query->orderBy('group_id')->orderBy('parent_id');
            }

            $query = $query->orderBy('category_name')->get();

            $categories = [];

            if ($query->count() > 0) {
                foreach ($query as $row) {
                    $categories[] = [$row->group_id, $row->category_id, $row->category_name, $row->parent_id];
                }

                if ($this->nest_categories == 'y') {
                    $this->cat_array = [];

                    foreach($categories as $key => $val)
                    {
                        if (0 == $val['3'])
                        {
                            $this->cat_array[] = array($val[0], $val[1], $val['2']);
                            $this->category_edit_subtree($val[1], $categories, $depth=1);
                        }
                    }
                } else {
                    $this->cat_array = $categories;
                }
            }

            foreach($this->cat_array as $key => $val) {
                if ( ! in_array($val[0], explode('|',$cat_group))) {
                    unset($this->cat_array[$key]);
                }
            }

            foreach ($this->cat_array as $ckey => $cat)
            {
                if ($ckey-1 < 0 OR ! isset($this->cat_array[$ckey-1]))
                {
                    $s .= Cp::input_select_option('', '-------');
                }

                $s .= Cp::input_select_option($cat['1'], str_replace('!-!', '&nbsp;', $cat['2']), (($cat_id == $cat['1']) ? 'y' : ''));

                if (isset($this->cat_array[$ckey+1]) && $this->cat_array[$ckey+1]['0'] != $cat['0'])
                {
                    $s .= Cp::input_select_option('', '-------');
                }
            }
        }

        $s .= Cp::input_select_footer().
              '&nbsp;';

        // ------------------------------------
        //  Status Pulldown
        // ------------------------------------

        $s .= Cp::input_select_header('status').
              Cp::input_select_option('', __('publish.filter_by_status')).
              Cp::input_select_option('all', __('cp.all'), ($status == 'all') ? 1 : '');

        if ($weblog_id != '')
        {
            $rez = DB::table('weblogs')
                ->select('status_group')
                ->where('weblog_id', $weblog_id)
                ->first();

            $query = DB::table('statuses')
                ->select('status')
                ->where('group_id', $rez->status_group)
                ->orderBy('status_order')
                ->get();

            if ($query->count() > 0)
            {
                foreach ($query as $row)
                {
                    $selected = ($status == $row->status) ? 1 : '';
                    $status_name = ($row->status == 'closed' OR $row->status == 'open') ?  __('publish.'.$row->status) : $row->status;
                    $s .= Cp::input_select_option($row->status, $status_name, $selected);
                }
            }
        }
        else
        {
             $s .= Cp::input_select_option('open', __('publish.open'), ($status == 'open') ? 1 : '');
             $s .= Cp::input_select_option('closed', __('publish.closed'), ($status == 'closed') ? 1 : '');
        }

        $s .= Cp::input_select_footer().
              '&nbsp;';

        // ------------------------------------
        //  Date Range Pulldown
        // ------------------------------------

        $sel_1 = ($date_range == '1')   ? 1 : '';
        $sel_2 = ($date_range == '7')   ? 1 : '';
        $sel_3 = ($date_range == '31')  ? 1 : '';
        $sel_4 = ($date_range == '182') ? 1 : '';
        $sel_5 = ($date_range == '365') ? 1 : '';

        $s .= Cp::input_select_header('date_range').
              Cp::input_select_option('', __('publish.date_range')).
              Cp::input_select_option('1', __('publish.today'), $sel_1).
              Cp::input_select_option('7', __('publish.past_week'), $sel_2).
              Cp::input_select_option('31', __('publish.past_month'), $sel_3).
              Cp::input_select_option('182', __('publish.past_six_months'), $sel_4).
              Cp::input_select_option('365', __('publish.past_year'), $sel_5).
              Cp::input_select_option('', __('publish.any_date')).
              Cp::input_select_footer().
              '&nbsp;';

        // ------------------------------------
        //  Order By Pulldown
        // ------------------------------------

        $sel_1 = ($order == 'desc')  ? 1 : '';
        $sel_2 = ($order == 'asc')   ? 1 : '';
        $sel_3 = ($order == 'alpha') ? 1 : '';

        $s .= Cp::input_select_header('order').
              Cp::input_select_option('desc', __('publish.order'), $sel_1).
              Cp::input_select_option('asc', __('publish.ascending'), $sel_2).
              Cp::input_select_option('desc', __('publish.descending'), $sel_1).
              Cp::input_select_option('alpha', __('publish.alpha'), $sel_3).
              Cp::input_select_footer().
              '&nbsp;';

        // ------------------------------------
        //  Per Page Pulldown
        // ------------------------------------

        if ( ! ($perpage = Request::input('perpage')) && session()->has('perpage')) {
            $perpage = session('perpage');
        }

        if (empty($perpage)) {
            $perpage = 50;
        }

        session('perpage', $perpage);

        $s .= Cp::input_select_header('perpage').
              Cp::input_select_option('25', '25 '.__('publish.results'), ($perpage == 25)  ? 1 : '').
              Cp::input_select_option('50', '50 '.__('publish.results'), ($perpage == 50)  ? 1 : '').
              Cp::input_select_option('75', '75 '.__('publish.results'), ($perpage == 75)  ? 1 : '').
              Cp::input_select_option('100', '100 '.__('publish.results'), ($perpage == 100)  ? 1 : '').
              Cp::input_select_option('150', '150 '.__('publish.results'), ($perpage == 150)  ? 1 : '').
              Cp::input_select_footer().
              '&nbsp;';

        $s .= '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        // ------------------------------------
        //  New Row! Keywords!
        // ------------------------------------

        $s .= '<tr>'.PHP_EOL.
              Cp::td('littlePadding', '', '7').PHP_EOL;

        $keywords = '';

        // Form Keywords
        if (Request::has('keywords')) {
            $keywords = Request::input('keywords');
        }

        // Pagination Keywords
        if (Request::has('pkeywords')) {
            $keywords = trim(base64_decode(Request::input('pkeywords')));
        }

        // IP Search! WHEE!
        if (substr(strtolower($keywords), 0, 3) == 'ip:')
        {
            $keywords = str_replace('_','.',$keywords);
        }

        $exact_match = (Request::input('exact_match') != '') ? Request::input('exact_match') : '';

        $s .= Cp::div('default').__('publish.keywords').NBS;
        $s .= Cp::input_text('keywords', $keywords, '40', '200', 'input', '200px').NBS;
        $s .= Cp::input_checkbox('exact_match', 'yes', $exact_match).NBS.__('publish.exact_match').NBS;

        $search_in = (Request::input('search_in') != '') ? Request::input('search_in') : 'title';

        $s .= Cp::input_select_header('search_in').
              Cp::input_select_option('title', __('publish.title_only'), ($search_in == 'title') ? 1 : '').
              Cp::input_select_option('body', __('publish.title_and_body'), ($search_in == 'body') ? 1 : '').
              Cp::input_select_footer().
              '&nbsp;';

        // ------------------------------------
        //  Submit! Submit!
        // ------------------------------------

        $s .= Cp::input_submit(__('publish.search'), 'submit');
        $s .= '</div>'.PHP_EOL;

        $s .= '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL;
        $s .= '</div>'.PHP_EOL;
        $s .= '</form>'.PHP_EOL;


        $r .= $s;

        // ------------------------------------
        //  Fetch the searchable fields
        // ------------------------------------

        $fields = [];

        $query = DB::table('weblogs');

        if ($weblog_id != '') {
            $query->where('weblog_id', $weblog_id);
        }

        $field_groups = $query->pluck('field_group')->all();

        if (!empty($field_groups)) {
            $fields = DB::table('weblog_fields')
                ->whereIn('group_id', $field_groups)
                ->whereIn('field_type', ['text', 'textarea', 'select'])
                ->pluck('field_name')
                ->all();
        }

        // ------------------------------------
        //  Build the main query
        // ------------------------------------

        $pageurl = BASE.'?C=edit'.AMP.'M=view_entries';

        $search_query = DB::table('weblog_entries')
            ->join('weblogs', 'weblog_entries.weblog_id', '=', 'weblogs.weblog_id')
            ->join('weblog_entry_data', 'weblog_entries.entry_id', '=', 'weblog_entry_data.entry_id')
            ->leftJoin('members', 'members.member_id', '=', 'weblog_entries.author_id')
            ->select('weblog_entries.entry_id');

        // ---------------------------------------
        //  JOINS
        // ---------------------------------------

        if ($cat_id == 'none' OR ($cat_id != "" && is_numeric($cat_id)))
        {
            $search_query->leftJoin('weblog_entry_categories', 'weblog_entries.entry_id', '=', 'weblog_entry_categories.entry_id')
                  ->leftJoin('categories', 'weblog_entry_categories.category_id', '=', 'categories.category_id');
        }

        // ---------------------------------------
        //  Limit to weblogs assigned to user
        // ---------------------------------------

        $search_query->whereIn('weblog_entries.weblog_id', $allowed_blogs);

        if ( ! Session::access('can_edit_other_entries') AND ! Session::access('can_view_other_entries')) {
            $search_query->where('weblog_entries.author_id', Session::userdata('member_id'));
        }

        // ---------------------------------------
        //  Exact Values
        // ---------------------------------------

        if ($weblog_id) {
            $pageurl .= AMP.'weblog_id='.$weblog_id;

            $search_query->where('weblog_entries.weblog_id', $weblog_id);
        }

        if ($date_range) {
            $pageurl .= AMP.'date_range='.$date_range;

            $search_query->where('weblog_entries.entry_date', '>', Carbon::now()->subDays($date_range));
        }

        if (is_numeric($cat_id)) {
            $pageurl .= AMP.'category_id='.$cat_id;

            $search_query->where('weblog_entry_categories.category_id', $cat_id);
        }

        if ($cat_id == 'none') {
            $pageurl .= AMP.'category_id='.$cat_id;

            $search_query->whereNull('weblog_entry_categories.entry_id');
        }

        if ($status && $status != 'all') {
            $pageurl .= AMP.'status='.$status;

            $search_query->where('weblog_entries.status', $status);
        }

        // ---------------------------------------
        //  Keywords
        // ---------------------------------------

        if ($keywords != '')
        {
            $search_query = $this->editKeywordsSearch($search_query, $keywords, $search_in, $exact_match, $fields);

            $pageurl .= AMP.'pkeywords='.base64_encode($keywords);

            if ($exact_match == 'yes')
            {
                $pageurl .= AMP.'exact_match=yes';
            }

            $pageurl .= AMP.'search_in='.$search_in;
        }

        // ---------------------------------------
        //  Order By!
        // ---------------------------------------

        if ($order) {
            $pageurl .= AMP.'order='.$order;

            switch ($order)
            {
                case 'asc'   : $search_query->orderBy('entry_date', 'asc');
                    break;
                case 'desc'  :  $search_query->orderBy('entry_date', 'desc');
                    break;
                case 'alpha' :  $search_query->orderBy('title', 'asc');
                    break;
                default      :  $search_query->orderBy('entry_date', 'desc');
            }
        } else {
             $search_query->orderBy('entry_date', 'desc');
        }

        // ------------------------------------
        //  Are there results?
        // ------------------------------------

        $total_query = clone $search_query;

        $total_count = $total_query->count();

        if ($total_count == 0)
        {
            $r .= Cp::quickDiv('highlight', BR.__('publish.no_entries_matching_that_criteria'));

            Cp::$title = __('cp.edit').Cp::breadcrumbItem(__('publish.edit_weblog_entries'));
			Cp::$body  = $r;
			Cp::$crumb = __('publish.edit_weblog_entries');
			return;
        }

        // Get the current row number and add the LIMIT clause to the SQL query
        if ( ! $rownum = Request::input('rownum')) {
            $rownum = 0;
        }

        // ------------------------------------
        //  Run the query again, fetching ID numbers
        // ------------------------------------

        $query = clone $search_query;
        $query = $query->offset($rownum)->limit($perpage)->get();

        $pageurl .= AMP.'perpage='.$perpage;

        $entry_ids = $query->pluck('entry_id')->all();

        // ------------------------------------
        //  Fetch the weblog information we need later
        // ------------------------------------

        $w_array = DB::table('weblogs')
            ->pluck('blog_name', 'weblog_id')->all();

        // "select all" checkbox
        $r .= Cp::toggle();

        Cp::$body_props .= ' onload="magic_check()" ';

        $r .= Cp::magicCheckboxesJavascript();

        // Build the item headings
        // Declare the "multi edit actions" form
        $r .= Cp::formOpen(
           [
                'action' => 'C=edit'.AMP.'M=multi_edit',
                'name'  => 'target',
                'id'    => 'target'
            ]
        );

        // ------------------------------------
        //  Build the output table
        // ------------------------------------

        $o  = Cp::table('tableBorderNoTop row-hover', '0', '', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableCell('tableHeadingAlt', '#').
              Cp::tableCell('tableHeadingAlt', __('publish.title')).
              Cp::tableCell('tableHeadingAlt', __('publish.view')).
              Cp::tableCell('tableHeadingAlt', __('publish.author')).
              Cp::tableCell('tableHeadingAlt', __('publish.date')).
              Cp::tableCell('tableHeadingAlt', __('publish.weblog')).
              Cp::tableCell('tableHeadingAlt', __('publish.status'));

        $o .= Cp::tableCell('tableHeadingAlt', Cp::input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"")).
              '</tr>'.PHP_EOL;

        $r .= $o;

        // ------------------------------------
        //  Build and run the full SQL query
        // ------------------------------------

        $query = DB::table('weblog_entries')
            ->leftJoin('weblogs', 'weblog_entries.weblog_id', '=', 'weblogs.weblog_id')
            ->leftJoin('weblog_entry_data', 'weblog_entries.entry_id', '=', 'weblog_entry_data.entry_id')
            ->leftJoin('members', 'members.member_id', '=', 'weblog_entries.author_id')
            ->select(
                'weblog_entries.entry_id',
                'weblog_entries.weblog_id',
                'weblog_entry_data.title',
                'weblog_entries.author_id',
                'weblog_entries.status',
                'weblog_entries.entry_date',
                'weblogs.live_look_template',
                'members.email',
                'members.screen_name')
            ->whereIn('weblog_entries.entry_id', $entry_ids);

        if ($cat_id != 'none' and $cat_id != '') {
            $query->innerJoin('weblog_entry_categories', 'weblog_entries.entry_id', '=', 'weblog_entry_categories.entry_id')
                  ->innerJoin('categories', 'weblog_entry_categories.category_id', '=', 'categories.category_id');
        }

        $query = $query->get();

        // load the site's templates
        $templates = [];

        $tquery = DB::table('templates')
        	->join('sites', 'sites.site_id', '=', 'templates.site_id')
            ->select('templates.folder', 'templates.template_name', 'templates.template_id', 'sites.site_name')
            ->orderBy('templates.folder')
            ->orderBy('templates.template_name')
            ->get();


        foreach ($tquery as $row) {
            $templates[$row->template_id] = $row->site_name.': '.$row->folder.'/'.$row->template_name;
        }

        // Loop through the main query result and write each table row

        $i = 0;

        foreach($query as $row)
        {
            $tr  = '<tr>'.PHP_EOL;

            // Entry ID number
            $tr .= Cp::tableCell('', $row->entry_id);

            // Weblog entry title (view entry)
            $tr .= Cp::tableCell('',
                                    Cp::anchor(
                                                  BASE.'?C=edit'.AMP.'M=edit_entry'.AMP.'weblog_id='.$row->weblog_id.AMP.'entry_id='.$row->entry_id,
                                                  '<b>'.$row->title.'</b>'
                                                )
                                    );
            // Edit entry
            $show_link = true;

            if ($row->live_look_template != 0 && isset($templates[$row->live_look_template]))
            {
                $view_link = Cp::anchor(
                                    $templates[$row->live_look_template].'/'.$row->entry_id,
                                    __('publish.live_look'), '', TRUE);
            }
            else
            {
                if (($row->author_id != Session::userdata('member_id')) && ! Session::access('can_edit_other_entries'))
                {
                    $show_link = false;
                }

                $view_url  = BASE.'?C=edit'.AMP.'M=view_entry'.AMP.'weblog_id='.$row->weblog_id.AMP.'entry_id='.$row->entry_id;

                $view_link = ($show_link == FALSE) ? '--' : Cp::anchor($view_url, __('publish.view'));
            }


            $tr .= Cp::tableCell('', $view_link);

            // Username
            $name = Cp::anchor('mailto:'.$row->email, $row->screen_name, 'title="Send an email to '.$row->screen_name.'"');

            $tr .= Cp::tableCell('', $name);
            $tr .= Cp::td().
                Cp::quickDiv(
                    'noWrap',
                    Localize::createHumanReadableDateTime($row->entry_date, true, true)
                ).
                '</td>'.PHP_EOL;

            // Weblog
            $tr .= Cp::tableCell('', (isset($w_array[$row->weblog_id])) ? Cp::quickDiv('noWrap', $w_array[$row->weblog_id]) : '');

            // Status
            $tr .= Cp::td();
            $tr .= $row->status;
            $tr .= '</td>'.PHP_EOL;

            // Delete checkbox
            $tr .= Cp::tableCell('', Cp::input_checkbox('toggle[]', $row->entry_id, '' , ' id="delete_box_'.$row->entry_id.'"'));

            $tr .= '</tr>'.PHP_EOL;
            $r .= $tr;

        } // End foreach

        $r .= '</table>'.PHP_EOL;

        $r .= Cp::table('', '0', '', '100%');
        $r .= '<tr>'.PHP_EOL.
              Cp::td();

        // Pass the relevant data to the paginate class
        $r .=  Cp::div('crumblinks').
               Cp::pager(
                            $pageurl,
                            $total_count,
                            $perpage,
                            $rownum,
                            'rownum'
                          ).
              '</div>'.PHP_EOL.
              '</td>'.PHP_EOL.
              Cp::td('defaultRight');

        $r .= Cp::input_hidden('pageurl', base64_encode($pageurl));

        // Delete button
        $r .= Cp::div('littlePadding');

        $r .= Cp::input_submit(__('cp.submit'));

        $r .= NBS.Cp::input_select_header('action').
              Cp::input_select_option('edit', __('publish.edit_selected')).
              Cp::input_select_option('delete', __('publish.delete_selected')).
              Cp::input_select_option('edit', '------').
              Cp::input_select_option('add_categories', __('publish.add_categories')).
              Cp::input_select_option('remove_categories', __('publish.remove_categories')).
              Cp::input_select_footer();

        $r .= '</div>'.PHP_EOL;

        $r .= '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL.
              '</form>'.PHP_EOL;

        // Set output data
        return $r;
    }

    // --------------------------------------------------------------------

    /**
     * Keywords search for Edit Page
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $keywords
     * @param string $search_in title/body/everywhere
     * @param string $exact_match  yes/no
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function editKeywordsSearch($query, $keywords, $search_in, $exact_match, $fields)
    {
        return $query->where(function($q) use ($keywords, $search_in, $exact_match, $fields)
        {
            if ($exact_match != 'yes')
            {
                $q->where('weblog_entry_data.title', 'LIKE', '%'.$keywords.'%');
            }
            else
            {
                $q->where('weblog_entry_data.title', 'LIKE', '% '.$keywords.' %')
                  ->orWhere('weblog_entry_data.title', 'LIKE', $keywords.' %')
                  ->orWhere('weblog_entry_data.title', 'LIKE', '% '.$keywords);
            }

            if ($search_in == 'body' OR $search_in == 'everywhere')
            {
                foreach ($fields as $val)
                {
                    if ($exact_match != 'yes')
                    {
                        $q->orWhere('weblog_entry_data.field_'.$val, 'LIKE', '%'.$keywords.'%');
                    }
                    else
                    {
                        $q->where('weblog_entry_data.field_'.$val, 'LIKE', '% '.$keywords.' %')
                          ->orWhere('weblog_entry_data.field_'.$val, 'LIKE', $keywords.' %')
                          ->orWhere('weblog_entry_data.field_'.$val, 'LIKE', '% '.$keywords);
                    }
                }
            }
        });
    }

    // ------------------------------------
    //  Category Sub-tree
    // ------------------------------------
    function category_edit_subtree($cat_id, $categories, $depth)
    {
        $spcr = '!-!';

        $indent = $spcr.$spcr.$spcr.$spcr;

        if ($depth == 1)
        {
            $depth = 4;
        }
        else
        {
            $indent = str_repeat($spcr, $depth).$indent;

            $depth = $depth + 4;
        }

        $sel = '';

        foreach ($categories as $key => $val)
        {
            if ($cat_id == $val['3'])
            {
                $pre = ($depth > 2) ? $spcr : '';

                $this->cat_array[] = array($val[0], $val[1], $pre.$indent.$spcr.$val['2']);

                $this->category_edit_subtree($val[1], $categories, $depth);
            }
        }
    }


    // ------------------------------------
    //  JavaScript filtering code
    // ------------------------------------
    // This function writes some JavaScript functions that
    // are used to switch the various pull-down menus in the
    // EDIT page
    //--------------------------------------------

    function filtering_menus()
    {
        // In order to build our filtering options we need to gather
        // all the weblogs, categories and custom statuses

        $blog_array   = [];
        $cat_array    = [];
        $status_array = [];

        $allowed_blogs = array_keys(Session::userdata('assigned_weblogs'));

        if (count($allowed_blogs) > 0)
        {
            // Fetch weblog titles

            $query = DB::table('weblogs')
                ->select('blog_title', 'weblog_id', 'cat_group', 'status_group');

            if (Session::userdata('group_id') != 1)
            {
                $query->whereIn('weblog_id', $allowed_blogs);
            }

            $query = $query->orderBy('blog_title')
                ->get();

            foreach ($query as $row)
            {
                $blog_array[$row->weblog_id] = [$row->blog_title, $row->cat_group, $row->status_group];
            }
        }

        $query = DB::table('categories')
            ->select('category_id', 'category_name', 'group_id', 'parent_id');

        if ($this->nest_categories == 'y') {
            $query->orderBy('group_id')
                ->orderBy('parent_id');
        }

        $query = $query->orderBy('category_name')->get();

        $categories = [];

        if ($query->count() > 0)
        {
            foreach ($query as $row)
            {
                $categories[] = [$row->group_id, $row->category_id, $row->category_name, $row->parent_id];
            }

            if ($this->nest_categories == 'y')
            {
                foreach($categories as $key => $val)
                {
                    if (0 == $val['3'])
                    {
                        $this->cat_array[] = array($val[0], $val[1], $val['2']);
                        $this->category_edit_subtree($val[1], $categories, $depth=1);
                    }
                }
            }
            else
            {
                $this->cat_array = $categories;
            }
        }

        $query = DB::table('statuses')
            ->orderBy('status_order')
            ->select('group_id', 'status')
            ->get();

        foreach ($query as $row)
        {
            $status_array[]  = array($row->group_id, $row->status);
        }

        // Build the JavaScript needed for the dynamic pull-down menus
        // We'll use output buffering since we'll need to return it
        // and we break in and out of php

        ob_start();

?>

<script type="text/javascript">
<!--

var firstcategory = 1;
var firststatus = 1;

function changemenu(index)
{

  var categories = new Array();
  var statuses   = new Array();

  var i = firstcategory;
  var j = firststatus;

  var blogs = document.filterform.weblog_id.options[index].value;

    with(document.filterform.cat_id)
    {
        if (blogs == "null")
        {
            categories[i] = new Option("<?php echo __('cp.all'); ?>", ""); i++;
            categories[i] = new Option("<?php echo __('cp.none'); ?>", "none"); i++;

            statuses[j] = new Option("<?php echo __('cp.all'); ?>", ""); j++;
            statuses[j] = new Option("<?php echo __('cp.open'); ?>", "open"); j++;
            statuses[j] = new Option("<?php echo __('cp.closed'); ?>", "closed"); j++;
        }

       <?php

        foreach ($blog_array as $key => $val)
        {

        ?>

        if (blogs == "<?php echo $key ?>")
        {
            categories[i] = new Option("<?php echo __('cp.all'); ?>", ""); i++;
            categories[i] = new Option("<?php echo __('cp.none'); ?>", "none"); i++; <?php echo "\n";

            if (count($this->cat_array) > 0)
            {
                $last_group = 0;

                foreach ($this->cat_array as $k => $v)
                {
                    if (in_array($v[0], explode('|', $val[1])))
                    {

                        if ($last_group == 0 OR $last_group != $v[0])
                        {?>
            categories[i] = new Option("-------", ""); i++; <?php echo "\n";
                            $last_group = $v[0];
                        }

            // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page
            ?>
            categories[i] = new Option("<?php echo addslashes($v['2']);?>", "<?php echo $v['1'];?>"); i++; <?php echo "\n";
                    }
                }
            }

            ?>

            statuses[j] = new Option("<?php echo __('cp.all'); ?>", ""); j++;
            <?php

            if (count($status_array) > 0)
            {
                foreach ($status_array as $k => $v)
                {
                    if ($v[0] == $val[2])
                    {

                    $status_name = ($v[1] == 'closed' OR $v[1] == 'open') ?  __('cp.'.$v[1]) : $v[1];
            ?>
            statuses[j] = new Option("<?php echo $status_name; ?>", "<?php echo $v['1']; ?>"); j++; <?php
                    }
                }
            }

            ?>

        } // END if blogs

        <?php

        } // END OUTER FOREACH

        ?>

        spaceString = eval("/!-!/g");

        with (document.filterform.cat_id)
        {
            for (i = length-1; i >= firstcategory; i--)
                options[i] = null;

            for (i = firstcategory; i < categories.length; i++)
            {
                options[i] = categories[i];
                options[i].text = options[i].text.replace(spaceString, String.fromCharCode(160));
            }

            options[0].selected = true;
        }

        with (document.filterform.status)
        {
            for (i = length-1; i >= firststatus; i--)
                options[i] = null;

            for (i = firststatus;i < statuses.length; i++)
                options[i] = statuses[i];

            options[0].selected = true;
        }
    }
}

//--></script>

<?php

        $javascript = ob_get_contents();

        ob_end_clean();

        return $javascript;

    }


    // ------------------------------------
    //  Multi Edit Form
    // ------------------------------------

    function multi_edit_form()
    {
        if ( ! Session::access('can_access_edit')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! in_array(Request::input('action'), ['edit', 'delete', 'add_categories', 'remove_categories'])) {
            return Cp::unauthorizedAccess();
        }

        if ( ! Request::has('toggle')) {
            return $this->edit_entries();
        }

        if (Request::input('action') == 'delete') {
            return $this->delete_entries_confirm();
        }

        // ------------------------------------
        //  Fetch the entry IDs
        // ------------------------------------

        foreach (Request::input('toggle') as $key => $val) {
            if (!empty($val) && is_numeric($val)) {
                $entry_ids[] = $val;
            }
        }

        if (empty($entry_ids)) {
            return Cp::unauthorizedAccess(__('publish.unauthorized_to_edit'));
        }

        // ------------------------------------
        //  Build and run the query
        // ------------------------------------

        $base_query = DB::table('weblog_entries AS t')
            ->join('weblog_entry_data AS d', 'd.entry_id', '=', 't.entry_id')
            ->join('weblogs AS w', 'w.weblog_id', '=', 't.weblog_id')
            ->select('t.entry_id',
                't.weblog_id',
                't.author_id',
                'd.title',
                't.url_title',
                't.entry_date',
                't.status',
                't.sticky',
                'w.show_options_cluster')
            ->whereIn('t.weblog_id', array_keys(Session::userdata('assigned_weblogs')))
            ->orderBy('entry_date', 'asc');

        $query = clone $base_query;
        $query = $query->whereIn('t.entry_id', $entry_ids)->get();

        // ------------------------------------
        //  Security check...
        // ------------------------------------

        // Before we show anything we have to make sure that the user is allowed to
        // access the blog the entry is in, and if the user is trying
        // to edit an entry authored by someone else they are allowed to

        $weblog_ids     = [];
        $disallowed_ids = [];
        $assigned_weblogs = array_keys(Session::userdata('assigned_weblogs'));

        foreach ($query as $row)
        {
            if (! Session::access('can_edit_other_entries') && $row->author_id != Session::userdata('member_id'))
            {
               $disallowed_ids = $row->entry_id;
            } else {
                $weblog_ids[] = $row->weblog_id;
            }
        }

        // ------------------------------------
        //  Are there disallowed posts?
        //  - If so, we have to remove them....
        // ------------------------------------

        if (count($disallowed_ids) > 0)
        {
            $disallowed_ids = array_unique($disallowed_ids);

            $new_ids = array_diff($entry_ids, $disallowed_ids);

            // After removing the disallowed entry IDs are there any left?
            if (count($new_ids) == 0) {
                return Cp::unauthorizedAccess(__('publish.unauthorized_to_edit'));
            }

            // Run the query one more time with the proper IDs.
            $query = clone $base_query;
            $query = $query->whereIn('t.entry_id', $new_ids)->get();
        }

        // ------------------------------------
        //  Adding/Removing of Categories Breaks Off to Their Own Function
        // ------------------------------------

        if (Request::input('action') == 'add_categories') {
            return $this->multi_categories_edit('add', $query);
        } elseif (Request::input('action') == 'remove_categories') {
            return $this->multi_categories_edit('remove', $query);
        }

        // ------------------------------------
        //  Fetch the status details for weblogs
        // ------------------------------------

        $weblog_query = DB::table('weblogs')
            ->select('weblog_id', 'status_group', 'default_status')
            ->whereIn('weblog_id', $weblog_ids)
            ->get();

        // ------------------------------------
        //  Fetch disallowed statuses
        // ------------------------------------

        $no_status_access = [];

        if (Session::userdata('group_id') != 1) {
            $result = DB::table('status_id')
                ->select('status_id')
                ->where('member_group', Session::userdata('group_id'))
                ->get();

            if ($result->count() > 0) {
                foreach ($result as $row) {
                    $no_status_access[] = $row->status_id;
                }
            }
        }

        // ------------------------------------
        //  Build the output
        // ------------------------------------

        $r  = Cp::formOpen(array('action' => 'C=edit'.AMP.'M=updateMultipleEntries'));
        $r .= '<div class="tableHeading">'.__('publish.multi_entry_editor').'</div>';

        if (isset($_POST['pageurl']))
        {
            $r .= Cp::input_hidden('redirect', $_POST['pageurl']);
        }

        foreach ($query as $row)
        {
            $r .= Cp::input_hidden('entry_id['.$row->entry_id.']', $row->entry_id);
            $r .= Cp::input_hidden('weblog_id['.$row->entry_id.']', $row->weblog_id);

            $r .= PHP_EOL.'<div class="publishTabWrapper">';
            $r .= PHP_EOL.'<div class="publishBox">';

            $r .= PHP_EOL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";

            $r .= PHP_EOL.'<td class="publishItemWrapper" valign="top" style="width:45%;">'.BR;
            $r .= Cp::div('clusterLineR');

            $r .= Cp::heading(__('publish.title'), 5).
                  Cp::input_text('title['.$row->entry_id.']', $row->title, '20', '100', 'input', '95%', 'onkeyup="liveUrlTitle();"');

            $r .= Cp::quickDiv('defaultSmall', NBS);

            $r .= Cp::heading(__('publish.url_title'), 5).
                  Cp::input_text('url_title['.$row->entry_id.']', $row->url_title, '20', '75', 'input', '95%');

            $r .= '</div>'.PHP_EOL;
            $r .= '</td>';

            // ------------------------------------
            //  Status pull-down menu
            // ------------------------------------

            $status_queries = [];
            $status_menu = '';

            foreach ($weblog_query as $weblog_row)
            {
                if ($weblog_row->weblog_id != $row->weblog_id) {
                    continue;
                }

                $status_query = DB::table('statuses')
                    ->where('group_id', $weblog_row->status_group)
                    ->orderBy('status_order')
                    ->get();

                $menu_status = '';

                if ($status_query->count() == 0)
                {
                    // No status group assigned, only Super Admins can create 'open' entries
                    if (Session::userdata('group_id') == 1)
                    {
                        $menu_status .= Cp::input_select_option('open', __('cp.open'), ($row->status == 'open') ? 1 : '');
                    }

                    $menu_status .= Cp::input_select_option('closed', __('cp.closed'), ($row->status == 'closed') ? 1 : '');
                }
                else
                {
                    $no_status_flag = true;

                    foreach ($status_query as $status_row)
                    {
                        $selected = ($row->status == $status_row->status) ? 1 : '';

                        if (in_array($status_row->status_id, $no_status_access))
                        {
                            continue;
                        }

                        $no_status_flag = false;

                        $status_name =
                            ($status_row->status == 'open' OR $status_row->status == 'closed') ?
                            __('publish.'.$status_row->status) :
                            Regex::form_prep($status_row->status);

                        $menu_status .= Cp::input_select_option(Regex::form_prep($status_row->status), $status_name, $selected);
                    }

                    // ------------------------------------
                    //  No Statuses? Default is Closed
                    // ------------------------------------

                    if ($no_status_flag == TRUE) {
                        $menu_status .= Cp::input_select_option('closed', __('cp.closed'));
                    }
                }

                $status_menu = $menu_status;
            }

            $r .= PHP_EOL.'<td class="publishItemWrapper" valign="top" style="width:25%;">'.BR;
            $r .= Cp::div('clusterLineR');
            $r .= Cp::heading(__('publish.entry_status'), 5);
            $r .= Cp::input_select_header('status['.$row->entry_id.']');
            $r .= $status_menu;
            $r .= Cp::input_select_footer();

            $r .= Cp::div('paddingTop');
            $r .= Cp::heading(__('publish.entry_date'), 5);
            $r .= Cp::input_text('entry_date['.$row->entry_id.']', Localize::createHumanReadableDateTime($row->entry_date), '18', '23', 'input', '150px');
            $r .= '</div>'.PHP_EOL;

            $r .= '</div>'.PHP_EOL;
            $r .= '</td>';

            $r .= PHP_EOL.'<td class="publishItemWrapper" valign="top" style="width:30%;">'.BR;

            if ($row->show_options_cluster == 'n')
            {
                $r .= Cp::input_hidden('sticky['.$row->entry_id.']', $row->sticky);
            }
            else
            {
                $r .= Cp::heading(NBS.__('publish.options'), 5);
                $r .= Cp::quickDiv('publishPad', Cp::input_checkbox('sticky['.$row->entry_id.']', 'y', $row->sticky).' '.__('publish.sticky'));
            }

            $r .= '</td>';

            $r .= "</tr></table>";

            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
        }

        $r .= Cp::quickDiv('paddingTop', Cp::input_submit(__('cp.update'))).
              '</form>'.PHP_EOL;

        Cp::$title = __('publish.multi_entry_editor');
        Cp::$crumb = __('publish.multi_entry_editor');
        Cp::$body  = $r;
    }


    // ------------------------------------
    //  Update Multi Entries
    // ------------------------------------

    function updateMultipleEntries()
    {
        if ( ! Session::access('can_access_edit')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! is_array(Request::input('entry_id')) or ! is_array(Request::input('weblog_id'))) {
            return Cp::unauthorizedAccess();
        }

        $titles      = Request::input('title');
        $url_titles  = Request::input('url_title');
        $entry_dates = Request::input('entry_date');
        $statuses    = Request::input('status');
        $stickys     = Request::input('sticky');
        $weblog_ids  = Request::input('weblog_id');

        foreach (Request::input('entry_id') as $id)
        {
            $weblog_id = $weblog_ids[$id];

            $data = [
                'title'             => strip_tags($_POST['title'][$id]),
                'url_title'         => $url_titles[$id],
                'entry_date'        => $entry_dates[$id],
                'status'            => $statuses[$id],
                'sticky'            => (isset($stickys[$id]) AND $stickys[$id] == 'y') ? 'y' : 'n',
            ];

            $error = [];

            // ------------------------------------
            //  No entry title? Assign error.
            // ------------------------------------

            if ($data['title'] == '') {
                $error[] = __('publish.missing_title');
            }

            // ------------------------------------
            //  Is the title unique?
            // ------------------------------------

            if ($data['title'] != '')
            {
                // ------------------------------------
                //  Do we have a URL title?
                // ------------------------------------

                // If not, create one from the title
                if ($data['url_title'] == '') {
                    $data['url_title'] = Regex::create_url_title($data['title'], TRUE);
                }

                // Kill all the extraneous characters.
                // We want the URL title to pure alpha text
                $data['url_title'] = Regex::create_url_title($data['url_title']);

                // Is the url_title a pure number?  If so we show an error.
                if (is_numeric($data['url_title'])) {
                    $error[] = __('publish.url_title_is_numeric');
                }

                // Field is limited to 75 characters, so trim url_title before unique work below
                $data['url_title'] = substr($data['url_title'], 0, 70);

                // ------------------------------------
                //  Is URL title unique?
                // ------------------------------------

                $unique = false;
                $i = 0;

                while ($unique == false)
                {
                    $temp = ($i == 0) ? $data['url_title'] : $data['url_title'].'-'.$i;
                    $i++;

                    $unique_query = DB::table('weblog_entries')
                        ->where('url_title', $temp)
                        ->where('weblog_id', $weblog_id);

                    if ($id != '') {
                        $unique_query->where('entry_id', '!=', $id);
                    }

                     if ($unique_query->count() == 0) {
                        $unique = true;
                     }

                     // Safety
                     if ($i >= 50) {
                        $error[] = __('publish.url_title_not_unique');
                        break;
                     }
                }

                $data['url_title'] = $temp;
            }

            // ------------------------------------
            //  No date? Assign error.
            // ------------------------------------

            if ($data['entry_date'] == '') {
                $error[] = __('publish.missing_date');
            }

            // ------------------------------------
            //  Convert the date to a Unix timestamp
            // ------------------------------------

            $data['entry_date'] = Localize::humanReadableToUtcCarbon($data['entry_date']);

            if ( ! $data['entry_date'] instanceof Carbon) {
                $error[] = __('publish.invalid_date_formatting');
            }

            // ------------------------------------
            //  Do we have an error to display?
            // ------------------------------------

             if (count($error) > 0)
             {
                $msg = '';

                foreach($error as $val)
                {
                    $msg .= Cp::quickDiv('littlePadding', $val);
                }

                return Cp::errorMessage($msg);
             }

            // ------------------------------------
            //  Update the entry
            // ------------------------------------

             DB::table('weblog_entry_data')
                ->where('entry_id', $id)
                ->update(['title' => $data['title']]);

            unset($data['title']);

            DB::table('weblog_entries')
                ->where('entry_id', $id)
                ->update($data);
        }

        // ------------------------------------
        //  Clear caches if needed
        // ------------------------------------

        if (Site::config('new_posts_clear_caches') == 'y') {
            cms_clear_caching('all');
        }

        if (Request::has('redirect') && ($redirect = base64_decode(Request::input('redirect'))) !== FALSE) {
            return redirect($redirect);
        } else {
            return redirect('?C=edit&U=mu');
        }
    }

    // ------------------------------------
    //  Multi Categories Edit Form
    // ------------------------------------

    function multi_categories_edit($type, $query)
    {
        if ( ! Session::access('can_access_edit'))
        {
            return Cp::unauthorizedAccess();
        }

        if ($query->count() == 0)
        {
            return Cp::unauthorizedAccess(__('publish.unauthorized_to_edit'));
        }

        // ------------------------------------
        //  Fetch the cat_group
        // ------------------------------------

        $sql = "SELECT DISTINCT cat_group FROM weblogs WHERE weblog_id IN(";

        $weblog_ids = [];
        $entry_ids  = [];

        foreach ($query as $row)
        {
            $weblog_ids[] = $row->weblog_id;
            $entry_ids[] = $row->entry_id;
        }

        $group_query = DB::table('weblogs')
            ->whereIn('weblog_id', $weblog_ids)
            ->distinct()
            ->select('cat_group')
            ->get();

        $valid = 'n';

        if ($group_query->count() > 0)
        {
            $valid = 'y';
            $last  = explode('|', $group_query->last()->cat_group);

            foreach($group_query as $row) {
                $valid_cats = array_intersect($last, explode('|', $row->cat_group));

                if (sizeof($valid_cats) == 0) {
                    $valid = 'n';
                    break;
                }
            }
        }

        if ($valid == 'n') {
            return Cp::userError( __('publish.no_category_group_match'));
        }

        $this->category_tree(($cat_group = implode('|', $valid_cats)));

        if (count($this->categories) == 0) {
            $cats = Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('publish.no_categories')), 'categorytree');
        } else {
            $cats = "<div id='categorytree'>";

            foreach ($this->categories as $val)
            {
                $cats .= $val;
            }

            $cats .= '</div>';
        }

        if (Session::access('can_edit_categories'))
        {
            $cats .= '<div id="cateditlink" style="padding:0; margin:0;display:none;">';

            if (stristr($cat_group, '|'))
            {
                $catq_query = DB::table('category_groups')
                    ->where('group_id', explode('|', $cat_group))
                    ->select('group_name', 'group_id')
                    ->get();

                $links = '';

                foreach($catg_query as $catg_row)
                {
                    $links .= Cp::anchorpop(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$catg_row['group_id'].AMP.'cat_group='.$cat_group.AMP.'Z=1', '<b>'.$catg_row['group_name'].'</b>').', ';
                }

                $cats .= Cp::quickDiv('littlePadding', '<b>'.__('publish.edit_categories').': </b>'.substr($links, 0, -2), '750');
            }
            else
            {
                $cats .= Cp::quickDiv('littlePadding', Cp::anchorpop(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$cat_group.AMP.'Z=1', '<b>'.__('publish.edit_categories').'</b>', '750'));
            }

            $cats .= '</div>';
        }

        // ------------------------------------
        //  Build the output
        // ------------------------------------

        $r  = Cp::formOpen(
                                array(
                                        'action'    => 'C=edit'.AMP.'M=entry_category_update',
                                        'name'      => 'entryform',
                                        'id'        => 'entryform'
                                     ),
                                array(
                                        'entry_ids' => implode('|', $entry_ids),
                                        'type'      => ($type == 'add') ? 'add' : 'remove'
                                     )
                            );

        $r .= <<<EOT

<script type="text/javascript">

    // ------------------------------------
    // Swap out categories
    // - This is used by the "edit categories" feature
    // ------------------------------------

    function set_catlink()
    {
        $('#cateditlink').css('display', 'block');
    }

    function swap_categories(str)
    {
        document.getElementById('categorytree').innerHTML = str;
    }
</script>
EOT;

        $r .= '<div class="tableHeading">'.__('publish.multi_entry_category_editor').'</div>';

        $r .= PHP_EOL.'<div class="publishTabWrapper">';
        $r .= PHP_EOL.'<div class="publishBox">';

        $r .= Cp::heading(($type == 'add') ? __('publish.add_categories') : __('publish.remove_categories'), 5);

        $r .= PHP_EOL."<table class='clusterBox' border='0' cellpadding='0' cellspacing='0' style='width:99%'><tr>";
        $r .= PHP_EOL.'<td class="publishItemWrapper" valign="top" style="width:45%;">'.BR;
        $r .= $cats;
        $r .= '</td>';
        $r .= "</tr></table>";

        $r .= '</div>'.PHP_EOL;
        $r .= '</div>'.PHP_EOL;

        $r .= Cp::quickDiv('paddingTop', Cp::input_submit(__('cp.update'))).
              '</form>'.PHP_EOL;

        Cp::$body_props .= ' onload="displayCatLink();" ';
        Cp::$title = __('publish.multi_entry_category_editor');
        Cp::$crumb = __('publish.multi_entry_category_editor');
        Cp::$body  = $r;
    }

    // ------------------------------------
    //  Update Multiple Entries with Categories
    // ------------------------------------

    function multi_entry_category_update()
    {
        if ( ! Session::access('can_access_edit')) {
            return Cp::unauthorizedAccess();
        }

        if (!Request::has('entry_ids') or !Request::has('type')) {
            return Cp::unauthorizedAccess(__('publish.unauthorized_to_edit'));
        }

        if (!Request::has('category') or ! is_array($_POST['category']) OR sizeof($_POST['category']) == 0)
        {
            return Cp::userError( __('publish.no_categories_selected'));
        }

        // ------------------------------------
        //  Fetch categories
        // ------------------------------------

        $this->cat_parents = Request::input('category');

        if ($this->assign_cat_parent == true)
        {
            $this->fetch_category_parents(Request::input('category'));
        }

        $this->cat_parents = array_unique($this->cat_parents);

        sort($this->cat_parents);

        $entry_ids = [];

        foreach (explode('|', Request::input('entry_ids')) as $entry_id)
        {
            $entry_ids[] = $entry_id;
        }

        // ------------------------------------
        //  Get Category Group IDs
        // ------------------------------------

        $query = DB::table('weblogs')
            ->select('weblogs.cat_group')
            ->join('weblog_entries', 'weblog_entries.weblog_id', '=', 'weblogs.weblog_id')
            ->whereIn('weblog_entries.entry_id', $entry_ids)
            ->get();

        $valid = 'n';

        if ($query->count() > 0)
        {
            $valid = 'y';
            $last  = explode('|', $query->last()->cat_group);

            foreach($query as $row)
            {
                $valid_cats = array_intersect($last, explode('|', $row->cat_group));

                if (sizeof($valid_cats) == 0)
                {
                    $valid = 'n';
                    break;
                }
            }
        }

        if ($valid == 'n') {
            return Cp::userError(__('publish.no_category_group_match'));
        }

        // ------------------------------------
        //  Remove Cats, Then Add Back In
        // ------------------------------------

        $valid_cat_ids = DB::table('categories')
            ->where('group_id', $valid_cats)
            ->whereIn('category_id', $this->cat_parents)
            ->pluck('category_id')
            ->all();

        if (!empty($valid_cat_ids)) {
            DB::table('weblog_entry_categories')
                ->whereIn('category_id', $valid_cat_ids)
                ->whereIn('entry_id', $entry_ids)
                ->delete();
        }

        if (Request::input('type') == 'add') {

            $insert_cats = array_intersect($this->cat_parents, $valid_cat_ids);

            // How brutish...
            foreach($entry_ids as $id)
            {
                foreach($insert_cats as $val)
                {
                    DB::table('weblog_entry_categories')
                        ->insert(
                        [
                            'entry_id'     => $id,
                            'category_id'  => $val
                        ]);
                }
            }
        }

        // ------------------------------------
        //  Clear caches if needed
        // ------------------------------------

        if (Site::config('new_posts_clear_caches') == 'y') {
            cms_clear_caching('all');
        }

        return $this->edit_entries('', __('publish.multi_entries_updated'));
    }

    // ------------------------------------
    //  View weblog entry
    // ------------------------------------
    // This function displays an individual weblog entry
    //--------------------------------------------

    function view_entry()
    {
        if ( ! $entry_id = Request::input('entry_id')) {
            return false;
        }

        if ( ! $weblog_id = Request::input('weblog_id')) {
            return false;
        }

        $assigned_weblogs = array_keys(Session::userdata('assigned_weblogs'));

        if ( ! in_array($weblog_id, $assigned_weblogs)) {
            return Cp::unauthorizedAccess(__('publish.unauthorized_for_this_blog'));
        }

        // ------------------------------------
        //  View Entry Output
        // ------------------------------------

        $query = DB::table('weblogs')
            ->where('weblog_id', $weblog_id)
            ->select('field_group')
            ->first();

        if (!$query) {
            return false;
        }

        foreach($query as $key => $val) {
            ${$key} = $val;
        }

        $message = '';

        if ($U = Request::input('U'))
        {
            $message = ($U == 'new') ? __('publish.entry_has_been_added') : __('publish.entry_has_been_updated');
        }

        $query = DB::table('weblog_fields')
            ->select('field_name', 'field_type')
            ->where('group_id', $field_group)
            ->orderBy('field_label')
            ->get();

        $fields = [];

        foreach ($query as $row)
        {
            $fields['field_'.$row->field_name] = $row->field_type;
        }

        $result = DB::table('weblog_entries')
            ->join('weblog_entry_data', 'weblog_entry_data.entry_id', '=', 'weblog_entries.entry_id')
            ->join('weblogs', 'weblogs.weblog_id', '=', 'weblog_entries.weblog_id')
            ->select('weblog_entries.*', 'weblog_entry_data.*', 'weblogs.*')
            ->where('weblog_entries.entry_id', $entry_id)
            ->first();

        $show_edit_link = true;

        if ($result->author_id != Session::userdata('member_id'))
        {
            if ( ! Session::access('can_view_other_entries'))
            {
                return Cp::unauthorizedAccess();
            }

            if ( ! Session::access('can_edit_other_entries'))
            {
                $show_edit_link = false;
            }
        }

        $r = '';

        if ($message != '') {
            $r .= Cp::quickDiv('successMessage', $message);
        }

        if ($result)
        {
            $r .= Cp::quickDiv('tableHeading', $result->title);
            $r .= Cp::div('box');

            foreach ($fields as $key => $val)
            {
                if (isset($result->{$key}) and $result->{$key} != '')
                {
                    $expl = explode('field_', $key);

                    if ($val == 'date')
                    {
                        if (!empty($result->{$key}))
                        {
                            $date = $result->{$key};
                            $r .= Localize::createHumanReadableDateTime($date);
                        }
                    }
                    else
                    {
                        $r .= $result->{$key};
                    }
                }
            }

            $r .= '</div>'.PHP_EOL;
        }

        if ($show_edit_link)
        {
            $r .= Cp::quickDiv('paddingTop', Cp::quickDiv('defaultBold', Cp::anchor(
                                BASE.'?C=edit'.AMP.'M=edit_entry'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$entry_id,
                                __('publish.edit_this_entry')
                              )));
        }

        if ($result->live_look_template != 0)
        {
            $res = DB::table('templates')
                ->where('templates.template_id', $result->live_look_template)
                ->select('folder', 'template_name')
                ->first();

            if ($res)
            {
                $r .= Cp::quickDiv('littlePadding',
                        Cp::quickDiv('defaultBold',
                            Cp::anchor(
                                '/'.$res->folder.'/'.$res->template_name.'/'.$entry_id,
                                __('publish.live_look'), '', TRUE)
                        )
                    );
            }
        }

        Cp::$title = __('publish.view_entry');
		Cp::$body  = $r;
		Cp::$crumb = __('publish.view_entry');
    }

    // ------------------------------------
    //  Delete Entries (confirm)
    // ------------------------------------
    // Warning message if you try to delete an entry
    //--------------------------------------------

    function delete_entries_confirm()
    {
        if ( ! Session::access('can_delete_self_entries') AND
             ! Session::access('can_delete_all_entries'))
        {
            return Cp::unauthorizedAccess();
        }

        if ( ! Request::has('toggle')){
            return $this->edit_entries();
        }

        $r  = Cp::formOpen(['action' => 'C=edit'.AMP.'M=delete_entries']);

        $i = 0;
        foreach (Request::input('toggle') as $key => $val)
        {
            if (!empty($val))
            {
                $r .= Cp::input_hidden('delete[]', $val);
                $i++;
            }
        }

        $r .= Cp::quickDiv('alertHeading', __('publish.delete_confirm'));
        $r .= Cp::div('box');

        if ($i == 1) {
            $r .= Cp::quickDiv('defaultBold', __('publish.delete_entry_confirm'));
        }
        else{
            $r .= Cp::quickDiv('defaultBold', __('publish.delete_entries_confirm'));
        }

        // if it's just one entry, let's be kind and show a title
        if ($i == 1) {
            $query = DB::table('weblog_entry_data')
                ->where('entry_id', $_POST['toggle'][0])
                ->first(['title']);

            if ($query)
            {
                $r .= '<br>'.
                      Cp::quickDiv(
                        'defaultBold',
                        str_replace(
                            '%title',
                            $query->title,
                            __('publish.entry_title_with_title')
                        )
                      );
            }
        }

        $r .= '<br>'.
              Cp::quickDiv('alert', __('publish.action_can_not_be_undone')).
              '<br>'.
              Cp::input_submit(__('cp.delete')).
              '</div>'.PHP_EOL.
              '</form>'.PHP_EOL;

        Cp::$title = __('publish.delete_confirm');
        Cp::$crumb = __('publish.delete_confirm');
        Cp::$body  = $r;
    }

    // ------------------------------------
    //  Delete Entries
    // ------------------------------------
    // Kill the specified entries
    //--------------------------------------------

    function delete_entries()
    {
        if ( ! Session::access('can_delete_self_entries') AND
             ! Session::access('can_delete_all_entries'))
        {
            return Cp::unauthorizedAccess();
        }

        if ( ! Request::has('delete') && is_array(Request::input('delete'))) {
            return $this->edit_entries();
        }

        $ids = Request::input('delete');

        $query = DB::table('weblog_entries')
            ->whereIn('entry_id', $ids)
            ->select('weblog_id', 'author_id', 'entry_id')
            ->get();

        $allowed_blogs = array_keys(Session::userdata('assigned_weblogs'));

        foreach ($query as $row)
        {
            if (Session::userdata('group_id') != 1)
            {
                if ( ! in_array($row->weblog_id, $allowed_blogs))
                {
                    return $this->edit_entries();
                }
            }

            if ($row->author_id == Session::userdata('member_id'))
            {
                if ( ! Session::access('can_delete_self_entries'))
                {
                    return Cp::unauthorizedAccess(__('publish.unauthorized_to_delete_self'));
                }
            }
            else
            {
                if ( ! Session::access('can_delete_all_entries'))
                {
                    return Cp::unauthorizedAccess(__('publish.unauthorized_to_delete_others'));
                }
            }
        }

        $entry_ids = [];

        foreach ($query as $row)
        {
            $entry_ids[] = $row->entry_id;
            $weblog_id = $row->weblog_id;

            DB::table('weblog_entries')->where('entry_id', $row->entry_id)->delete();
            DB::table('weblog_entry_data')->where('entry_id', $row->entry_id)->delete();
            DB::table('weblog_entry_categories')->where('entry_id', $row->entry_id)->delete();

            $tot = DB::table('members')
                ->where('member_id', $row->author_id)
                ->value('total_entries');

            if ($tot > 0) {
                $tot -= 1;
            }

            DB::table('members')
                ->where('member_id', $row->author_id)
                ->update(['total_entries' => $tot]);

            // Update statistics
            Stats::update_weblog_stats($row->weblog_id);
        }

        // ------------------------------------
        //  Clear caches
        // ------------------------------------

        cms_clear_caching('all');

        // ------------------------------------
        //  Return success message
        // ------------------------------------

        $message = __('publish.entries_deleted');

        return $this->edit_entries('', $message);
    }

    // ------------------------------------
    //  File upload form
    // ------------------------------------

    function file_upload_form()
    {
        Cp::$title = __('publish.file_upload');

        Cp::$body .= Cp::quickDiv('tableHeading', __('publish.file_upload'));

        Cp::$body .= Cp::div('box').BR;


        if (Session::userdata('group_id') != 1) {
            $ids = DB::table('upload_no_access')
                ->where('member_group', Session::userdata('group_id'))
                ->pluck('upload_id')
                ->all();
        }

        $query = DB::table('upload_prefs')
            ->select('id', 'name')
            ->orderBy('name');

        if ( ! empty($ids)) {
            $query->whereNotIn('id', $ids);
        }

        $query = $query->get();

        if ($query->count() == 0) {
            return Cp::unauthorizedAccess();
        }

        Cp::$body .= "<form method=\"post\" action=\"".BASE.'?C=publish'.AMP.'M=upload_file'.AMP.'Z=1'."\" enctype=\"multipart/form-data\">\n";

        Cp::$body .= Cp::input_hidden('field_group', Request::input('field_group'));

        Cp::$body .= Cp::quickDiv('', "<input type=\"file\" name=\"userfile\" size=\"20\" />".BR.BR);

        Cp::$body .= Cp::quickDiv('littlePadding', __('publish.select_destination_dir'));

        Cp::$body .= Cp::input_select_header('destination');

        foreach ($query as $row)
        {
            Cp::$body .= Cp::input_select_option($row->id, $row->name);
        }

        Cp::$body .= Cp::input_select_footer();


        Cp::$body .= Cp::quickDiv('', BR.Cp::input_submit(__('publish.upload')).'<br><br>');

        Cp::$body .= '</form>'.PHP_EOL;

        Cp::$body .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  File Browser
        // ------------------------------------

        Cp::$body .= Cp::quickDiv('', BR.BR);

        Cp::$body .= Cp::quickDiv('tableHeading', __('filebrowser.file_browser'));
        Cp::$body .= Cp::div('box');

        Cp::$body .= '<form method="post" action="'.BASE.'?C=publish'.AMP.'M=file_browser'.AMP.'Z=1'."\" enctype=\"multipart/form-data\">\n";

        Cp::$body .= Cp::input_hidden('field_group', Request::input('field_group'));

        Cp::$body .= Cp::quickDiv('paddingTop', __('publish.select_destination_dir'));

        Cp::$body .= Cp::input_select_header('directory');

        foreach ($query as $row)
        {
            Cp::$body .= Cp::input_select_option($row->id, $row->name);
        }

        Cp::$body .= Cp::input_select_footer();


        Cp::$body .= Cp::quickDiv('', BR.Cp::input_submit(__('publish.view')));

        Cp::$body .= '</form>'.PHP_EOL;
        Cp::$body .= BR.BR.'</div>'.PHP_EOL;

        Cp::$body .= Cp::quickDiv('littlePadding', BR.'<div align="center"><a href="JavaScript:window.close();">'.__('cp.close_window').'</a></div>');
    }


    // ------------------------------------
    //  Upload File
    // ------------------------------------

    function upload_file()
    {
        return Cp::errorMessage('Disabled for the time being, sorry');
    }

    // ------------------------------------
    //  File Browser
    // ------------------------------------

    function file_browser()
    {
        $id = Request::input('directory');
        $field_group = Request::input('field_group');

        Cp::$title = __('filebrowser.file_browser');

        $r  = Cp::quickDiv('tableHeading', __('filebrowser.file_browser'));
        $r .= Cp::quickDiv('box', 'Disabled for the time being, sorry');

        $query = DB::table('upload_prefs')->where('id', $id);

        if ($query->count() == 0) {
            return;
        }

        if (Session::userdata('group_id') != 1)
        {
            $safety_count = DB::table('upload_no_access')
                ->where('upload_id', $query->id)
                ->where('upload_loc', 'cp')
                ->where('member_group', Session::userdata('group_id'))
                ->count();

            if ($safety_count != 0) {
                return Cp::unauthorizedAccess();
            }
        }

        Cp::$body = $r;
    }
}

