<?php

namespace Kilvin\Cp;

use Cp;
use DB;
use Site;
use Stats;
use Schema;
use Request;
use Validator;
use Carbon\Carbon;
use Kilvin\Core\Regex;
use Kilvin\Core\Session;

class PublishAdministration
{
    // Category arrays
    public $categories = [];
    public $cat_update = [];

    public $temp;

    // ------------------------------------
    //  Constructor
    // ------------------------------------

    function __construct()
    {

    }

    // ------------------------------------
    //  Weblog management page
    // ------------------------------------
    // This function displays the "weblog management" page
    // accessed via the "admin" tab
    //-----------------------------------------------------------

    function weblogsOverview($message = '')
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        Cp::$title  = __('admin.weblog_management');
        Cp::$crumb  = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration'));
        Cp::$crumb .= Cp::breadcrumbItem(__('admin.weblog_management'));

        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=new_weblog',
            __('admin.create_new_weblog')
        ];

        $r = Cp::header(__('admin.weblog_management'), $right_links);

        // Fetch weblogs
        $query = DB::table('weblogs')
            ->select('weblog_id', 'blog_name', 'blog_title')
            ->orderBy('blog_title')
            ->get();

        if ($query->count() == 0)
        {
            $r .= Cp::div('box');
            $r .= Cp::quickDiv('littlePadding', Cp::heading(__('admin.no_weblogs_exist'), 5));
            $r .= Cp::quickDiv('littlePadding', Cp::anchor( BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=new_weblog', __('admin.create_new_weblog')));
            $r .= '</div>'.PHP_EOL;

            return Cp::$body = $r;
        }

        if ($message != '')
        {
            $r .= Cp::quickDiv('successMessage', stripslashes($message));
        }

        $r .= Cp::table('tableBorder', '0', '', '100%');

        $r .= '<tr>'.PHP_EOL.
              Cp::td('tableHeadingAlt', '30px').__('admin.weblog_id').'</td>'.PHP_EOL.
              Cp::td('tableHeadingAlt').__('admin.weblog_name').'</td>'.PHP_EOL.
              Cp::td('tableHeadingAlt', '', '4').__('admin.weblog_short_name').'</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $i = 0;

        foreach($query as $row)
        {

            $r .= '<tr>'.PHP_EOL;

            $r .= Cp::tableCell('', Cp::quickSpan('default', $row->weblog_id));

            $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', $row->blog_title).' &nbsp; ');

            $r .= Cp::tableCell('', Cp::quickSpan('default', $row->blog_name).' &nbsp; ');

            $r .= Cp::tableCell('',
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=blog_prefs'.AMP.'weblog_id='.$row->weblog_id,
                                __('admin.edit_preferences')
                              ));

            $r .= Cp::tableCell('',
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=group_prefs'.AMP.'weblog_id='.$row->weblog_id,
                                __('admin.edit_groups')
                              ));

            $r .= Cp::tableCell('',
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=delete_conf'.AMP.'weblog_id='.$row->weblog_id,
                                __('cp.delete')
                              ));

            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        // Assign output data

        Cp::$body = $r;

    }

    //--------------------------------------------------------------

    /**
     * New weblog Form
     *
     * @param array          $variables
     * @param \Dotenv\Loader $loader
     *
     * @return void
     */
    public function newWeblogForm()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        $r = <<<EOT
<script type="text/javascript">

$(function() {
    $('input[name=edit_group_prefs]').click(function(e){
        $('#group_preferences').toggle();
    });
});

</script>
EOT;

        $r .= Cp::formOpen(['action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=create_blog']);

        $r .= Cp::table('tableBorder', '0', '', '100%');
        $r .= '<tr>'.PHP_EOL
            .Cp::td('tableHeading', '', '2').__('admin.create_new_weblog').'</td>'.PHP_EOL
            .'</tr>'.PHP_EOL;


        // Weblog "full name" field
        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::required().NBS.Cp::quickSpan('defaultBold', __('admin.full_weblog_name'))).
              Cp::tableCell('', Cp::input_text('blog_title', '', '20', '100', 'input', '260px')).
              '</tr>'.PHP_EOL;

        // Weblog "short name" field
        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::required().NBS.Cp::quickSpan('defaultBold', __('admin.short_weblog_name')).Cp::quickDiv('', __('admin.single_word_no_spaces_with_underscores')), '40%').
              Cp::tableCell('', Cp::input_text('blog_name', '', '20', '40', 'input', '260px'), '60%').
              '</tr>'.PHP_EOL;

        // Duplicate Preferences Select List
        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.duplicate_weblog_prefs')));

        $w  = Cp::input_select_header('duplicate_weblog_prefs');
        $w .= Cp::input_select_option('', __('admin.do_not_duplicate'));

        $wquery = DB::table('weblogs')
            ->select('weblog_id', 'blog_name', 'blog_title')
            ->orderBy('blog_title')
            ->get();

        foreach($wquery as $row) {
            $w .= Cp::input_select_option($row->weblog_id, $row->blog_title);
        }

        $w .= Cp::input_select_footer();

        $r .= Cp::tableCell('', $w).
              '</tr>'.PHP_EOL;

        // Edit Group Preferences option

        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.edit_group_prefs')), '40%').
              Cp::tableCell('', Cp::input_radio('edit_group_prefs', 'y').
                                                NBS.__('admin.yes').
                                                NBS.
                                                Cp::input_radio('edit_group_prefs', 'n', 1).
                                                NBS.__('admin.no'), '60%').
              '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL.BR;



        // GROUP FIELDS
        $g = '';
        $i = 0;
        $cat_group = '';
        $status_group = '';
        $field_group = '';

        $r .= Cp::div('', '', 'group_preferences', '', 'style="display:none;"');
        $r .= Cp::table('tableBorder', '0', '', '100%');
        $r .= '<tr>'.PHP_EOL.
              Cp::td('tableHeadingAlt', '100%', 2).__('admin.edit_group_prefs').'</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        // Category group select list
        $query = DB::table('category_groups')
            ->orderBy('group_name')
            ->select('group_id', 'group_name')
            ->get();

        $g .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.category_group')), '40%', 'top');

        $g .= Cp::td().
              Cp::input_select_header('cat_group[]', ($query->count() > 0) ? 'y' : '');

        $selected = '';

        $g .= Cp::input_select_option('', __('admin.none'), $selected);

        if ($query->count() > 0)
        {
            foreach ($query as $row)
            {
                $g .= Cp::input_select_option($row->group_id, $row->group_name);
            }
        }

        $g .= Cp::input_select_footer().
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        // Status group select list
        $query = DB::table('status_groups')
            ->orderBy('group_name')
            ->select('group_id', 'group_name')
            ->get();

        $g .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.status_group')));

        $g .= Cp::td().
              Cp::input_select_header('status_group');

        $selected = '';

        $g .= Cp::input_select_option('', __('admin.none'), $selected);

        if ($query->count() > 0)
        {
            foreach ($query as $row)
            {
                $selected = ($status_group == $row->group_id) ? 1 : '';

                $g .= Cp::input_select_option($row->group_id, $row->group_name, $selected);
            }
        }

        $g .= Cp::input_select_footer().
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;


        // Field group select list
        $query = DB::table('field_groups')
            ->orderBy('group_name')
            ->select('group_id', 'group_name')
            ->get();

        $g .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.field_group')));

        $g .= Cp::td().
              Cp::input_select_header('field_group');

        $selected = '';

        $g .= Cp::input_select_option('', __('admin.none'), $selected);

        if ($query->count() > 0)
        {
            foreach ($query as $row)
            {
                $selected = ($field_group == $row->group_id) ? 1 : '';

                $g .= Cp::input_select_option($row->group_id, $row->group_name, $selected);
            }
        }

        $g .= Cp::input_select_footer().
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL.BR.
              '</div>'.PHP_EOL;

        $r .= $g;
        // Table end

        // Submit button
        $r .= Cp::quickDiv('littlePadding', Cp::required(1));
        $r .= Cp::quickDiv('', Cp::input_submit(__('cp.submit')));

        $r .= '</form>'.PHP_EOL;

        // Assign output data
        Cp::$title = __('admin.create_new_weblog');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=blog_list', __('admin.weblog_management'))).
                      Cp::breadcrumbItem(__('admin.new_weblog'));
        Cp::$body  = $r;
    }

    // --------------------------------------------------------------------

    /**
    * Update or Create Weblog Preferences
    *
    * @return  void
    */
    public function updateWeblog()
    {
        if ( ! Session::access('can_admin_weblogs')){
            return Cp::unauthorizedAccess();
        }

        $edit    = (bool) Request::has('weblog_id');
        $return  = (bool) Request::has('return');
        $dupe_id = Request::input('duplicate_weblog_prefs');

        // ------------------------------------
        //  Validation
        // ------------------------------------

        $validator = Validator::make(request()->all(), [
            'blog_name'          => 'required|regex:/^[\pL\pM\pN_]+$/u',
            'blog_title'         => 'required',
            'url_title_prefix'   => 'alpha_dash',
        ]);

        if ($validator->fails()) {
            return Cp::errorMessage(implode(BR, $validator->errors()->all()));
        }

        // Is the weblog name taken?
        $query = DB::table('weblogs')
            ->where('blog_name', Request::input('blog_name'));

        if ($edit === true) {
            $query->where('weblog_id', '!=', Request::input('weblog_id'));
        }

        if ($query->count() > 0) {
            return Cp::errorMessage(__('admin.taken_weblog_name'));
        }

        $data = [];
        if ($edit === true) {
            $data = (array) DB::table('weblogs')
                ->where('weblog_id', Request::input('weblog_id'))
                ->first();
        }

        $fields = [
            'cat_group',
            'status_group',
            'field_group',
            'weblog_id',
            'blog_title',
            'blog_name',
            'blog_description',
            'blog_url',
            'live_look_template',
            'default_status',
            'default_category',
            'enable_versioning',
            'enable_qucksave_versioning',
            'max_revisions',
            'weblog_notify',
            'weblog_notify_emails',
            'show_url_title',
            'show_author_menu',
            'show_status_menu',
            'show_date_menu',
            'show_options_cluster',
            'show_categories_menu',
            'show_show_all_cluster',
            'url_title_prefix'
        ];

        foreach($fields as $field) {
            if (Request::has($field)) {
                $data[$field] = Request::input($field);
            }
        }

        if (isset($data['cat_group']) && is_array($data['cat_group'])) {
            $data['cat_group'] = implode('|', $data['cat_group']);
        }

        $nullable = [
            'cat_group',
            'status_group',
            'field_group'
        ];

        foreach($nullable as $field) {
            if(empty($data[$field])) {
                $data[$field] = null;
            }
        }

        $strings = [
            'blog_description',
            'default_status',
            'weblog_notify_emails',
            'url_title_prefix'
        ];

        foreach($strings as $field) {
            if(empty($data[$field])) {
                $data[$field] = '';
            }
        }

        // Let DB defaults handle these if empty
        $unsettable = [
            'enable_versioning',
            'enable_qucksave_versioning',
            'max_revisions',
            'weblog_notify',
            'show_url_title',
            'show_author_menu',
            'show_status_menu',
            'show_date_menu',
            'show_options_cluster',
            'show_categories_menu',
            'show_show_all_cluster',
        ];

        foreach($unsettable as $field) {
            if(empty($data[$field])) {
                unset($data[$field]);
            }
        }

        // ------------------------------------
        //  Template Error Trapping
        // ------------------------------------

        if ($edit === false) {
            $old_group_id       = Request::input('old_group_id');
            $group_name         = strtolower(Request::input('group_name'));
            $template_theme     = filename_security(Request::input('template_theme'));
        }

        // ------------------------------------
        //  Conversion
        // ------------------------------------

        if (Request::has('weblog_notify_emails') && is_array(Request::input('weblog_notify_emails'))) {
            $data['weblog_notify_emails'] = implode(',', Request::input('weblog_notify_emails'));
        }

        // ------------------------------------
        //  Create Weblog
        // ------------------------------------

        if ($edit === false) {
            // Assign field group if there is only one
            if ( ! isset($data['field_group']) or ! is_numeric($data['field_group'])) {
                $query = DB::table('field_groups')
                        ->select('group_id')
                        ->get();

                if ($query->count() == 1) {
                    $data['field_group'] = $query->first()->group_id;
                }
            }

            // --------------------------------------
            //  Duplicate Preferences
            // --------------------------------------

            if ($dupe_id !== false AND is_numeric($dupe_id))
            {
                $wquery = DB::table('weblogs')
                    ->where('weblog_id', $dupe_id)
                    ->first();

                if ($wquery)
                {
                    $exceptions = [
                        'weblog_id',
                        'blog_name',
                        'blog_title',
                        'total_entries',
                        'last_entry_date',
                    ];

                    foreach($wquery as $key => $val)
                    {
                        // don't duplicate fields that are unique to each weblog
                        if (in_array($key, $exceptions)) {
                            continue;
                        }

                        if (empty($data[$key])) {
                            $data[$key] = $val;
                        }
                    }
                }
            }

            $insert_id = $weblog_id = DB::table('weblogs')->insertGetId($data);

            $success_msg = __('admin.weblog_created');

            $crumb = Cp::breadcrumbItem(__('admin.new_weblog'));
        }

        // ------------------------------------
        //  Updating Weblog
        // ------------------------------------

        if ($edit === true) {
            if (isset($data['clear_versioning_data'])) {
                DB::table('entry_versioning')
                    ->where('weblog_id', $data['weblog_id'])
                    ->delete();
            }

            DB::table('weblogs')
                ->where('weblog_id', $data['weblog_id'])
                ->update($data);

            $weblog_id = $data['weblog_id'];

            $success_msg = __('admin.weblog_updated');

            $crumb = Cp::breadcrumbItem(__('cp.update'));
        }

        // ------------------------------------
        //  Messages and Return
        // ------------------------------------

        Cp::log($success_msg.$data['blog_title']);

        $message = $success_msg.'<strong>'.$data['blog_title'].'</strong>';

        if ($edit === false OR $return === true) {
            return $this->weblogsOverview($message);
        } else {
            return $this->editBlog($message, $weblog_id);
        }
    }

    //--------------------------------------------------------------

    /**
     * Edit Weblog Preferences
     *
     * @param string $message A message of success
     * @param $weblog_id Load this weblog's preferences
     * @return void
     */
    function editBlog($msg='', $weblog_id='')
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        // Default values
        $i            = 0;
        $blog_name    = '';
        $blog_title   = '';
        $cat_group    = '';
        $status_group = '';

        if (empty($weblog_id)) {
            if ( ! $weblog_id = Request::input('weblog_id')) {
                return false;
            }
        }

        $query = DB::table('weblogs')->where('weblog_id', $weblog_id)->first();

        if (!$query) {
            return $this->weblogsOverview();
        }

        foreach ($query as $key => $val) {
            $$key = $val;
        }

        if ($msg != '') {
            Cp::$body .= Cp::quickDiv('box', $msg);
        }

        // New blog so set default
        if (empty($blog_url)) {
           $blog_url = Site::config('site_url');
        }

        //------------------------------------
        // Build the output
        //------------------------------------

        $js = <<<EOT
<script type="text/javascript">

var lastShownObj = '';
var lastShownColor = '';
function showHideMenu(objValue)
{
    if (lastShownObj)
    {
        $('#' + lastShownObj+'_pointer a').first().css('color', lastShownColor);
        $('#' + lastShownObj+'_on').css('display', 'none');

        // document.getElementById(lastShownObj+'_pointer').getElementsByTagName('a')[0].style.color = lastShownColor;
        // document.getElementById(lastShownObj + '_on').style.display = 'none';
    }

    lastShownObj = objValue;
    lastShownColor = $('#' + objValue+'_pointer a').first().css('color');

    $('#' + objValue + '_on').css('display', 'block');
    $('#' + objValue+'_pointer a').first().css('color', '#000');
}

$(function() {
    showHideMenu('weblog');
});

</script>

EOT;
        Cp::$body .= $js;

        // Third table cell contains are preferences in hidden <div>'s
        $r  = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_preferences'));
        $r .= Cp::input_hidden('weblog_id', $weblog_id);

        $r .= Cp::quickDiv('none', '', 'menu_contents');

        // ------------------------------------
        //  General settings
        // ------------------------------------

        $r .= '<div id="weblog_on" style="display: none; padding:0; margin: 0;">';
        $r .= Cp::table('tableBorder', '0', '', '100%');
        $r .= '<tr>'.PHP_EOL;

        $r .= "<td class='tableHeadingAlt' id='weblog2' colspan='2'>";
        $r .= NBS.__('admin.weblog_base_setup').'</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        // Weblog "full name" field
        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::required().NBS.Cp::quickSpan('defaultBold', __('admin.full_weblog_name')), '50%').
              Cp::tableCell('', Cp::input_text('blog_title', $blog_title, '20', '100', 'input', '260px'), '50%').
              '</tr>'.PHP_EOL;

        // Weblog "short name" field
        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::required().NBS.Cp::quickSpan('defaultBold', __('admin.short_weblog_name')).'&nbsp;'.'-'.'&nbsp;'.__('admin.single_word_no_spaces_with_underscores'), '50%').
              Cp::tableCell('', Cp::input_text('blog_name', $blog_name, '20', '40', 'input', '260px'), '50%').
              '</tr>'.PHP_EOL;

        // Weblog descriptions field
        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.blog_description')), '50%').
              Cp::tableCell('', Cp::input_text('blog_description', $blog_description, '50', '225', 'input', '100%'), '50%').
              '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL.'</div>'.PHP_EOL;

        // ------------------------------------
        //  Paths
        // ------------------------------------

        $r .= '<div id="paths_on" style="display: none; padding:0; margin: 0;">';
        $r .= Cp::table('tableBorder', '0', '', '100%');
        $r .= '<tr>'.PHP_EOL;

        $r .= "<td class='tableHeadingAlt' id='paths2' colspan='2'>";
        $r .= NBS.__('admin.paths').'</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        // Weblog URL field
        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell(
                '',
                Cp::quickSpan(
                    'defaultBold',
                    __('admin.blog_url')
                ).
                Cp::quickDiv('default', __('admin.weblog_url_exp')),
                '50%').
              Cp::tableCell('', Cp::input_text('blog_url', $blog_url, '50', '80', 'input', '100%'), '50%').
              '</tr>'.PHP_EOL;

        // Live Look Template
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.live_look_template')))
             .Cp::td('', '50%')
             .Cp::input_select_header('live_look_template')
             .Cp::input_select_option('0', __('admin.no_live_look_template'), ($live_look_template == 0) ? '1' : 0);

        $tquery = DB::table('templates AS t')
            ->join('sites', 'sites.site_id', '=', 't.site_id')
            ->orderBy('t.template_name')
            ->select('t.folder', 't.template_id', 't.template_name', 'sites.site_name')
            ->get();

        foreach ($tquery as $template)
        {
            $r .= Cp::input_select_option(
                $template->template_id,
                $template->site_name.': '.$template->folder.'/'.$template->template_name,
                (($template->template_id == $live_look_template) ? 1 : ''));
        }

        $r .= Cp::input_select_footer()
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;

        $r .= '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL;

        $r .= '</div>'.PHP_EOL;


        // ------------------------------------
        //  Administrative settings
        // ------------------------------------

        $r .= '<div id="defaults_on" style="display: none; padding:0; margin: 0;">';
        $r .= Cp::table('tableBorder', '0', '', '100%');
        $r .= '<tr>'.PHP_EOL;

        $r .= "<td class='tableHeadingAlt' id='defaults2' colspan='2'>";
        $r .= NBS.__('admin.default_settings').'</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;


        // Default status menu
        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.default_status')), '50%').
              Cp::td('', '50%').
              Cp::input_select_header('default_status');

        $query = DB::table('statuses')
            ->where('group_id', $status_group)
            ->orderBy('status')
            ->get();

        if ($query->count() == 0) {
            $selected = ($default_status == 'open') ? 1 : '';

            $r .= Cp::input_select_option('open', __('admin.open'), $selected);

            $selected = ($default_status == 'closed') ? 1 : '';

            $r .= Cp::input_select_option('closed', __('admin.closed'), $selected);
        } else {
            foreach ($query as $row) {
                $selected = ($default_status == $row->status) ? 1 : '';

                $status_name = ($row->status == 'open' OR $row->status == 'closed') ? __($row->status) : $row->status;

                $r .= Cp::input_select_option($row->status, $status_name, $selected);
            }
        }

        $r .= Cp::input_select_footer().
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        // Default category menu
        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.default_category')), '50%');

        $r .= Cp::td('', '50%').
              Cp::input_select_header('default_category');

        $selected = '';

        $r .= Cp::input_select_option('', __('admin.none'), $selected);

        $query = DB::table('categories')
            ->join('category_groups', 'category_groups.group_id', '=', 'categories.group_id')
            ->whereIn('categories.group_id', explode('|', $cat_group))
            ->select(
                'categories.category_id',
                'categories.category_name',
                'category_groups.group_name'
            )
            ->orderBy('category_groups.group_name')
            ->orderBy('categories.category_name')
            ->get();

        foreach ($query as $row)
        {
            $row->display_name = $row->group_name.': '.$row->category_name;

            $selected = ($default_category == $row->category_id) ? 1 : '';

            $r .= Cp::input_select_option($row->category_id, $row->display_name, $selected);
        }

        $r .= Cp::input_select_footer().
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;
        $r .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Versioning settings
        // ------------------------------------

        $r .= '<div id="versioning_on" style="display: none; padding:0; margin: 0;">';
        $r .= Cp::table('tableBorder', '0', '', '100%');
        $r .= '<tr>'.PHP_EOL;

        $r .= "<td class='tableHeadingAlt' id='versioning2' colspan='2'>";
        $r .= NBS.__('admin.versioning').'</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;


        // Enable Versioning?
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.enable_versioning')), '50%')
             .Cp::td('', '50%');

              $selected = ($enable_versioning == 'y') ? 1 : '';

        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('enable_versioning', 'y', $selected).'&nbsp;';

              $selected = ($enable_versioning == 'n') ? 1 : '';

        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('enable_versioning', 'n', $selected)
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;


        // Enable Quicksave versioning
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.enable_qucksave_versioning')).BR.__('admin.quicksave_note'), '50%')
             .Cp::td('', '50%');

              $selected = ($enable_qucksave_versioning == 'y') ? 1 : '';

        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('enable_qucksave_versioning', 'y', $selected).'&nbsp;';

              $selected = ($enable_qucksave_versioning == 'n') ? 1 : '';

        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('enable_qucksave_versioning', 'n', $selected)
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;

        // Max Revisions
        $x = Cp::quickDiv('littlePadding', Cp::input_checkbox('clear_versioning_data', 'y', 0).' '.Cp::quickSpan('highlight', __('admin.clear_versioning_data')));

        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.max_revisions')).BR.__('admin.max_revisions_note'), '50%').
              Cp::tableCell('', Cp::input_text('max_revisions', $max_revisions, '30', '4', 'input', '100%').$x, '50%').
              '</tr>'.PHP_EOL;


        $r .= '</table>'.PHP_EOL;
        $r .= '</div>'.PHP_EOL;


        // ------------------------------------
        //  Notifications
        // ------------------------------------

        $r .= '<div id="not_on" style="display: none; padding:0; margin: 0;">';
        $r .= Cp::table('tableBorder', '0', '', '100%');
        $r .= '<tr>'.PHP_EOL;

        $r .= "<td class='tableHeadingAlt' id='not2' colspan='2'>";
        $r .= NBS.__('admin.notification_settings').'</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.weblog_notify')), '50%')
             .Cp::td('', '50%');

        $selected = ($weblog_notify == 'y') ? 1 : '';

        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('weblog_notify', 'y', $selected).'&nbsp;';

        $selected = ($weblog_notify == 'n') ? 1 : '';

        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('weblog_notify', 'n', $selected)
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;

        $users = DB::table('members')
            ->distinct()
            ->select('members.screen_name', 'members.member_id', 'members.email')
            ->leftJoin('member_group_preferences', function ($join) use ($weblog_id) {
                $join->on('member_group_preferences.group_id', '=', 'members.group_id')
                     ->where('member_group_preferences.handle', 'weblog_id_'.$weblog_id);
            })
            ->where('members.group_id', 1)
            ->orWhere('member_group_preferences.value', 'y')
            ->get();

        $weblog_notify_emails = explode(',', $weblog_notify_emails);

        $s = '<select name="weblog_notify_emails[]" multiple="multiple" size="8" style="width:100%">'.PHP_EOL;

        foreach($users as $row) {

            $selected = (in_array($row->member_id, $weblog_notify_emails)) ? 'selected="selected"' : '';

            $s .= '<option value="'.$row->member_id.'" '.$selected.'>'.$row->screen_name.' &lt;'.$row->email.'&gt;</option>'.PHP_EOL;
        }

        $s .= '</select>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell(
                '',
                Cp::quickSpan('defaultBold', __('admin.emails_of_notification_recipients')), '50%', 'top').
              Cp::tableCell('', $s, '50%').
              '</tr>'.PHP_EOL;


        $r .= '</table>'.PHP_EOL;
        $r .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Publish Page customization
        // ------------------------------------

        $r .= '<div id="cust_on" style="display: none; padding:0; margin: 0;">';
        $r .= Cp::table('tableBorder', '0', '', '100%');
        $r .= '<tr>'.PHP_EOL;

        $r .= "<td class='tableHeadingAlt' id='cust2' colspan='2'>";
        $r .= NBS.__('admin.publish_page_customization').'</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        // show_url_title
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.show_url_title')), '50%')
             .Cp::td('', '50%');
        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('show_url_title', 'y', ($show_url_title == 'y') ? 1 : '').'&nbsp;';
        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('show_url_title', 'n', ($show_url_title == 'n') ? 1 : '')
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;


        // show_author_menu
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.show_author_menu')), '50%')
             .Cp::td('', '50%');
        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('show_author_menu', 'y', ($show_author_menu == 'y') ? 1 : '').'&nbsp;';
        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('show_author_menu', 'n', ($show_author_menu == 'n') ? 1 : '')
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;


        // show_status_menu
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.show_status_menu')), '50%')
             .Cp::td('', '50%');
        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('show_status_menu', 'y', ($show_status_menu == 'y') ? 1 : '').'&nbsp;';
        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('show_status_menu', 'n', ($show_status_menu == 'n') ? 1 : '')
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;


        // show_date_menu
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.show_date_menu')), '50%')
             .Cp::td('', '50%');
        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('show_date_menu', 'y', ($show_date_menu == 'y') ? 1 : '').'&nbsp;';
        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('show_date_menu', 'n', ($show_date_menu == 'n') ? 1 : '')
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;

        // show_options_cluster
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.show_options_cluster')), '50%')
             .Cp::td('', '50%');
        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('show_options_cluster', 'y', ($show_options_cluster == 'y') ? 1 : '').'&nbsp;';
        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('show_options_cluster', 'n', ($show_options_cluster == 'n') ? 1 : '')
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;

        // show_categories_menu
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.show_categories_menu')), '50%')
             .Cp::td('', '50%');
        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('show_categories_menu', 'y', ($show_categories_menu == 'y') ? 1 : '').'&nbsp;';
        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('show_categories_menu', 'n', ($show_categories_menu == 'n') ? 1 : '')
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;


        // Show All Cluster
        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.show_show_all_cluster')), '50%')
             .Cp::td('', '50%');
        $r .= Cp::qlabel(__('admin.yes'))
             .Cp::input_radio('show_show_all_cluster', 'y', ($show_show_all_cluster == 'y') ? 1 : '').'&nbsp;';
        $r .= Cp::qlabel(__('admin.no'))
             .Cp::input_radio('show_show_all_cluster', 'n', ($show_show_all_cluster == 'n') ? 1 : '')
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;

        // url_title_prefix


        $r .= '<tr>'.PHP_EOL
             .Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.url_title_prefix')).'&nbsp;'.'-'.'&nbsp;'.__('admin.single_word_no_spaces_with_underscores'))
             .Cp::td('', '50%')
             .Cp::input_text('url_title_prefix', $url_title_prefix, '50', '255', 'input', '100%')
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;
        $r .= '</div>'.PHP_EOL;


        // BOTTOM SECTION OF PAGE


        // Text: * Indicates required fields

        $r .= Cp::div('littlePadding');

        $r .= Cp::quickDiv('littlePadding', Cp::required(1));

        // "Submit" button

        $r .= Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.update')).NBS.Cp::input_submit(__('cp.update_and_return'),'return'));

        $r.= '</div>'.PHP_EOL.'</form>'.PHP_EOL;

        // ------------------------------------
        //  Create Our All Encompassing Table of Weblog Goodness
        // ------------------------------------

        Cp::$body .= Cp::table('', '0', '', '100%');

        // List of our various preference areas begins here

        $areas = [
            "weblog"       => "admin.weblog_base_setup",
            "paths"        => "admin.paths",
            "not"          => "admin.notification_settings",
            "defaults"     => "admin.default_settings",
            "cust"         => "admin.publish_page_customization",
            "versioning"   => "admin.versioning"
        ];

        $menu = '';

        foreach($areas as $area => $area_lang) {
            $menu .= Cp::quickDiv('navPad', ' <span id="'.$area.'_pointer">&#8226; '.Cp::anchor("#", __($area_lang), 'onclick="showHideMenu(\''.$area.'\');"').'</span>');
        }

        $first_text =   Cp::div('tableHeadingAlt')
                        .   $blog_title
                        .'</div>'.PHP_EOL
                        .Cp::div('profileMenuInner')
                        .   $menu
                        .'</div>'.PHP_EOL;

        // Create the Table
        $table_row = array( 'first'     => array('valign' => "top", 'width' => "220px", 'text' => $first_text),
                            'second'    => array('class' => "default", 'width'  => "8px"),
                            'third'     => array('valign' => "top", 'text' => $r));

        Cp::$body .= Cp::tableRow($table_row).
                      '</table>'.PHP_EOL;


        Cp::$title = __('admin.edit_weblog_prefs');

        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=blog_list', __('admin.weblog_management'))).
                      Cp::breadcrumbItem(__('admin.edit_weblog_prefs'));
    }



    // ------------------------------------
    //  Weblog group preferences form
    // ------------------------------------
    // This function displays the form used to edit the various
    // preferences and group assignements for a given weblog
    //-----------------------------------------------------------

    function edit_group_form()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        // Set default values

        $i = 0;

        // If we don't have the $weblog_id variable, bail out.
        if ( ! $weblog_id = Request::input('weblog_id')) {
            return false;
        }

        $query = DB::table('weblogs')
            ->where('weblog_id', $weblog_id)
            ->first();

        foreach ($query as $key => $val)
        {
            $$key = $val;
        }

        // Build the output

        Cp::$body .= Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_preferences'));
        Cp::$body .= Cp::input_hidden('weblog_id', $weblog_id);
        Cp::$body .= Cp::input_hidden('blog_name',  $blog_name);
        Cp::$body .= Cp::input_hidden('blog_title', $blog_title);
         Cp::$body .= Cp::input_hidden('return', '1');

        Cp::$body .= Cp::table('tableBorder', '0', '', '100%');
        Cp::$body .= '<tr>'.PHP_EOL.
              Cp::td('tableHeading', '100%').__('admin.edit_group_prefs').'</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        Cp::$body .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickDiv('littlePadding', Cp::quickSpan('defaultBold', $blog_title)), '50%').
              '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL;

        Cp::$body .= Cp::table('tableBorder', '0', '', '100%');
        Cp::$body .= '<tr>'.PHP_EOL;
        Cp::$body .= Cp::tableCell('tableHeadingAlt', __('admin.preference'));
        Cp::$body .= Cp::tableCell('tableHeadingAlt', __('admin.value'));
        Cp::$body .= '</tr>'.PHP_EOL;


        // GROUP FIELDS

        $g = '';

        // Category group select list


        $query = DB::table('category_groups')
            ->orderBy('group_name')
            ->select('group_id', 'group_name')
            ->get();

        $g .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.category_group')), '40%', 'top');

        $g .= Cp::td().
              Cp::input_select_header('cat_group[]', ($query->count() > 0) ? 'y' : '');

        $selected = (empty($cat_group)) ? 1 : '';

        $g .= Cp::input_select_option('', __('admin.none'), $selected);

        if ($query->count() > 0)
        {
            $cat_group = explode('|', $cat_group);

            foreach ($query as $row)
            {
                $selected = (in_array($row->group_id, $cat_group)) ? 1 : '';

                $g .= Cp::input_select_option($row->group_id, $row->group_name, $selected);
            }
        }

        $g .= Cp::input_select_footer().
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        // Status group select list


        $query = DB::table('status_groups')
            ->orderBy('group_name')
            ->select('group_id', 'group_name')
            ->get();

        $g .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.status_group')));

        $g .= Cp::td().
              Cp::input_select_header('status_group');

        $selected = '';

        $g .= Cp::input_select_option('', __('admin.none'), $selected);

        if ($query->count() > 0)
        {
            foreach ($query as $row)
            {
                $selected = ($status_group == $row->group_id) ? 1 : '';

                $g .= Cp::input_select_option($row->group_id, $row->group_name, $selected);
            }
        }

        $g .= Cp::input_select_footer().
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;


        // Field group select list


        $query = DB::table('field_groups')
            ->orderBy('group_name')
            ->select('group_id', 'group_name')
            ->get();

        $g .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.field_group')));

        $g .= Cp::td().
              Cp::input_select_header('field_group');

        $selected = '';

        $g .= Cp::input_select_option('', __('admin.none'), $selected);

        if ($query->count() > 0) {
            foreach ($query as $row) {
                $selected = ($field_group == $row->group_id) ? 1 : '';

                $g .= Cp::input_select_option($row->group_id, $row->group_name, $selected);
            }
        }

        $g .= Cp::input_select_footer().
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;


        Cp::$body .= $g;

        // BOTTOM SECTION OF PAGE
        // Table end
        Cp::$body .= '</table>'.PHP_EOL;

        Cp::$body .= Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.update')));

        Cp::$body .= '</form>'.PHP_EOL;

        Cp::$title = __('admin.edit_group_prefs');

        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=blog_list', __('admin.weblog_management'))).
                      Cp::breadcrumbItem(__('admin.edit_group_prefs'));
    }

    // ------------------------------------
    //  Delete weblog confirm
    // ------------------------------------
    // Warning message shown when you try to delete a weblog
    //-----------------------------------------------------------

    function delete_weblog_conf()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $weblog_id = Request::input('weblog_id')) {
            return false;
        }

        $query = DB::table('weblogs')
            ->select('blog_title')
            ->where('weblog_id', $weblog_id)
            ->first();

        Cp::$title = __('admin.delete_weblog');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=blog_list', __('admin.weblog_administration'))).
                      Cp::breadcrumbItem(__('admin.delete_weblog'));

        Cp::$body = Cp::deleteConfirmation(
        [
                'url'       => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=delete'.AMP.'weblog_id='.$weblog_id,
                'heading'   => 'delete_weblog',
                'message'   => 'delete_weblog_confirmation',
                'item'      => $query->blog_title,
                'extra'     => '',
                'hidden'    => array('weblog_id' => $weblog_id)
            ]
        );
    }

    // ------------------------------------
    //  Delete weblog
    // ------------------------------------
    // This function deletes a given weblog
    //-----------------------------------------------------------

    function delete_weblog()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $weblog_id = Request::input('weblog_id')) {
            return false;
        }

        if ( ! is_numeric($weblog_id)) {
            return false;
        }

        $blog_title = DB::table('weblogs')
            ->where('weblog_id', $weblog_id)
            ->value('blog_title');

        if (empty($blog_title)) {
            return false;
        }

        Cp::log(__('admin.weblog_deleted').NBS.$blog_title);

        $query = DB::table('weblog_entries')
            ->where('weblog_id', $weblog_id)
            ->select('entry_id', 'author_id')
            ->get();

        $entries = [];
        $authors = [];

        if ($query->count() > 0)
        {
            foreach ($query as $row)
            {
                $entries[] = $row->entry_id;
                $authors[] = $row->author_id;
            }
        }

        $authors = array_unique($authors);

        DB::table('weblog_entry_data')->where('weblog_id', $weblog_id)->delete();
        DB::table('weblog_entries')->where('weblog_id', $weblog_id)->delete();
        DB::table('weblogs')->where('weblog_id', $weblog_id)->delete();

        // ------------------------------------
        //  Clear catagories
        // ------------------------------------

        if (!empty($entries)) {
            DB::table('weblog_entry_categories')->whereIn('entry_id', $entries)->delete();
        }

        // ------------------------------------
        //  Update author stats
        // ------------------------------------

        foreach ($authors as $author_id)
        {
            $total_entries = DB::table('weblog_entries')->where('author_id', $author_id)->count();

            DB::table('members')
                ->where('member_id', $author_id)
                ->update(['total_entries' => $total_entries]);
        }

        // ------------------------------------
        //  McFly, update the stats!
        // ------------------------------------

        Stats::update_weblog_stats();

        return $this->weblogsOverview(__('admin.weblog_deleted').NBS.'<b>'.$blog_title.'</b>');
    }

//=====================================================================
//  CATEGORY ADMINISTRATION FUNCTIONS
//=====================================================================


    // ------------------------------------
    //  Category overview page
    // ------------------------------------
    // This function displays the "categories" page, accessed
    // via the "admin" tab
    //-----------------------------------------------------------

    function category_overview($message = '')
    {
        if ( ! Session::access('can_edit_categories'))
        {
            return Cp::unauthorizedAccess();
        }

        Cp::$title  = __('admin.category_groups');
        Cp::$crumb  = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                       Cp::breadcrumbItem(__('admin.category_groups'));

        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=cat_group_editor',
            __('admin.create_new_category_group')
        ];

        $r = Cp::header(__('admin.categories'), $right_links);

        // Fetch category groups
        $query = DB::table('category_groups')
            ->orderBy('group_name')
            ->select('group_id', 'group_name')
            ->get();

        if ($query->count() == 0)
        {
            $r .= stripslashes($message);
            $r .= Cp::div('box');
            $r .= Cp::quickDiv('littlePadding', Cp::heading(__('admin.no_category_group_message'), 5));
            $r .= Cp::quickDiv('littlePadding',
                Cp::anchor(
                    BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=cat_group_editor',
                    __('admin.create_new_category_group')));
            $r .= '</div>'.PHP_EOL;

            return Cp::$body = $r;
        }

        if ($message != '') {
            $r .= $message;
        }

        $i = 0;

        $r .= Cp::table('tableBorder', '0', '', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeading').'</td>'.PHP_EOL.
              Cp::td('tableHeading', '', '4').
              __('admin.category_groups').
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        foreach($query as $row)
        {
            // It is not efficient to put this query in the loop.
            // Originally I did it with a join above, but there is a bug on OS X Server
            // that I couldn't find a work-around for.  So... query in the loop it is.
            $count = DB::table('categories')
                ->where('group_id', $row->group_id)
                ->count();


            $r .= '<tr>'.PHP_EOL.
                  Cp::td('', '5%').
                  Cp::quickSpan('defaultBold', $row->group_id).
                  '</td>'.PHP_EOL.
                  Cp::td('', '30%').
                  Cp::quickSpan('defaultBold', $row->group_name).
                  '</td>'.PHP_EOL;

            $r .= Cp::tableCell('',
                  '('.$count.')'.'&nbsp;'.
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$row->group_id,
                                __('admin.add_edit_categories')
                              ));


            $r .= Cp::tableCell('',
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=cat_group_editor'.AMP.'group_id='.$row->group_id,
                                __('admin.edit_group_name')
                              ));



            $r .= Cp::tableCell('',
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=cat_group_del_conf'.AMP.'group_id='.$row->group_id,
                                __('admin.delete_group')
                              )).
                  '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;
        Cp::$body = $r;
    }


    // ------------------------------------
    //  Category group form
    // ------------------------------------
    // This function shows the form used to define a new category
    // group or edit an existing one
    //-----------------------------------------------------------

    function edit_category_group_form()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        // Set default values
        $edit       = false;
        $group_id   = '';
        $group_name = '';
        $can_edit   = [];
        $can_delete = [];

        // If we have the group_id variable, it's an edit request, so fetch the category data
        if ($group_id = Request::input('group_id')) {
            $edit = true;

            if ( ! is_numeric($group_id)) {
                return false;
            }

            $query = DB::table('category_groups')
                ->where('group_id', $group_id)
                ->first();

            if (empty($query)) {
                return $this->category_overview();
            }

            foreach ($query as $key => $val) {
                $$key = $val;
            }
        }

        // ------------------------------------
        //  Opening Outpu
        // ------------------------------------

        $title = ($edit == false) ? __('admin.create_new_category_group') : __('admin.edit_category_group');

        // Build our output
        $r = Cp::formOpen(
            [
                'action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_category_group'
            ]
        );

        if ($edit == true) {
            $r .= Cp::input_hidden('group_id', $group_id);
        }

        $r .= Cp::quickDiv('tableHeading', $title);

        $r .= Cp::div('box').
              Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.name_of_category_group'))).
              Cp::quickDiv('littlePadding', Cp::input_text('group_name', $group_name, '20', '50', 'input', '300px'));

        $r .= '</div>'.PHP_EOL; // main box

        $r .= Cp::div('paddingTop');

        if ($edit == FALSE)
            $r .= Cp::input_submit(__('cp.submit'));
        else
            $r .= Cp::input_submit(__('cp.update'));

        $r .= '</div>'.PHP_EOL;

        $r .= '</form>'.PHP_EOL;

        Cp::$title = $title;
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=categories', __('admin.category_groups'))).
                      Cp::breadcrumbItem($title);
        Cp::$body  = $r;
    }


    // ------------------------------------
    //  Create/update category group
    // ------------------------------------
    // This function receives the submission from the group
    // form and stores it in the database
    //-----------------------------------------------------------

    function update_category_group()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        $edit = (bool) request()->has('group_id');

        if (! request()->has('group_name')) {
            return $this->edit_category_group_form();
        }

        $group_id   = request()->input('group_id');
        $group_name = request()->input('group_name');

        // check for bad characters in group name

        if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $group_name)) {
            return Cp::errorMessage(__('admin.illegal_characters'));
        }

        // Is the group name taken?
        $query = DB::table('category_groups')
            ->where('group_name', $group_name);

        if ($edit === true) {
            $query->where('group_id', '!=', $group_id);
        }

        if ($query->count() > 0) {
            return Cp::errorMessage(__('admin.taken_category_group_name'));
        }

        // Construct the query based on whether we are updating or inserting
        if ($edit === false)
        {
            $data['site_id'] = Site::config('site_id');

            DB::table('category_groups')->insert(['group_name' => $group_name]);

            $success_msg = __('admin.category_group_created');

            Cp::log(__('admin.category_group_created').$group_name);
        }
        else
        {
            DB::table('category_groups')
                ->where('group_id', $group_id)
                ->update(['group_name' => $group_name]);

            $success_msg = __('admin.category_group_updated');

            Cp::log(__('admin.category_group_updated').$group_name);
        }

        $message  = Cp::div('successMessage');
        $message .= $success_msg.$group_name;

        if ($edit === false)
        {
            $query = DB::table('weblogs')
                ->select('weblog_id')
                ->get();

            if ($query->count() > 0)
            {
                $message .= Cp::quickDiv('littlePadding', Cp::quickDiv('alert', __('admin.assign_group_to_weblog')));

                if ($query->count() == 1)
                {
                    $link = 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=group_prefs'.AMP.'weblog_id='.$query->first()->weblog_id;
                }
                else
                {
                    $link = 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=blog_list';
                }

                $message .= Cp::quickDiv('littlePadding', Cp::anchor(BASE.'?'.$link, __('admin.click_to_assign_group')));
            }
        }

        $message .= '</div>'.PHP_EOL;

        return $this->category_overview($message);
    }


    // ------------------------------------
    //  Delete category group confirm
    // ------------------------------------
    // Warning message if you try to delete a category group
    //-----------------------------------------------------------

    function delete_category_group_conf()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id)) {
            return false;
        }

        $group_name = DB::table('category_groups')->where('group_id', $group_id)->value('group_name');

        if(empty($group_name)) {
            return false;
        }

        Cp::$title = __('admin.delete_group');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=categories', __('admin.category_groups'))).
                      Cp::breadcrumbItem(__('admin.delete_group'));

        Cp::$body = Cp::deleteConfirmation(
            [
                'url'       => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=delete_group'.AMP.'group_id='.$group_id,
                'heading'   => 'delete_group',
                'message'   => 'delete_cat_group_confirmation',
                'item'      => $group_name,
                'extra'     => '',
                'hidden'    => ['group_id' => $group_id]
            ]
        );
    }

    // ------------------------------------
    //  Delete category group
    // ------------------------------------
    // This function deletes the category group and all
    // associated catetgories
    //-----------------------------------------------------------

    function delete_category_group()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === false OR ! is_numeric($group_id)) {
            return false;
        }

        $query = DB::table('category_groups')
            ->where('group_id', $group_id)
            ->select('group_id', 'group_name')
            ->first();

        if (!$query) {
            return false;
        }

        $name = $query->group_name;
        $group_id = $query->group_id;

        // ------------------------------------
        //  Delete from weblog_entry_categories
        // ------------------------------------

        $cat_ids = DB::table('categories')
            ->where('group_id', $group_id)
            ->pluck('category_id')
            ->all();

        if (!empty($cat_ids)) {
            DB::table('weblog_entry_categories')
                ->whereIn('category_id', $cat_ids)
                ->delete();
        }

        DB::table('category_groups')
            ->where('group_id', $group_id)
            ->delete();

        DB::table('categories')
            ->where('group_id', $group_id)
            ->delete();

        $message = Cp::quickDiv('successMessage', __('admin.category_group_deleted').NBS.'<b>'.$name.'</b>');

        Cp::log(__('admin.category_group_deleted').'&nbsp;'.$name);

        cms_clear_caching('all');

        return $this->category_overview($message);
    }

    // ------------------------------------
    //  Category tree
    // ------------------------------------
    // This function (and the next) create a hierarchical tree
    // of categories.
    //-----------------------------------------------------------

    function category_tree($type = 'text', $group_id = '', $p_id = '', $sort_order = 'a')
    {
        // Fetch category group ID number

        if ($group_id == '') {
            if (($group_id = Request::input('group_id')) === FALSE) {
                return false;
            }
        }

        if ( ! is_numeric($group_id)) {
            return false;
        }

        // Fetch category groups
        $query = DB::table('categories')
            ->where('group_id', $group_id)
            ->select('category_id', 'category_name', 'parent_id')
            ->orderBy('parent_id')
            ->orderBy(($sort_order == 'a') ? 'category_name' : 'category_order')
            ->get();

        if ($query->count() == 0) {
            return false;
        }

        // Assign the query result to a multi-dimensional array
        foreach($query as $row) {
            $cat_array[$row->category_id]  = [$row->parent_id, $row->category_name];
        }

        if ($type == 'data')  {
            return $cat_array;
        }

        $up     = '<img src="'.PATH_CP_IMG.'arrow_up.png" border="0"  width="16" height="16" alt="" title="" />';
        $down   = '<img src="'.PATH_CP_IMG.'arrow_down.png" border="0"  width="16" height="16" alt="" title="" />';

        // Build our output...
        $can_delete = true;
        if (Request::input('Z') == 1)
        {
            if (Session::access('can_edit_categories'))
            {
                $can_delete = true;
            }
            else
            {
                $can_delete = false;
            }
        }


        $zurl  = (Request::input('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= (Request::input('cat_group') !== null) ? AMP.'cat_group='.Request::input('cat_group') : '';
        $zurl .= (Request::input('integrated') !== null) ? AMP.'integrated='.Request::input('integrated') : '';

        foreach($cat_array as $key => $val)
        {
            if (0 == $val[0])
            {
                if ($type == 'table')
                {
                    if ($can_delete == TRUE)
                        $delete = Cp::anchor(
                            BASE.'?C=Administration'.
                                AMP.'M=blog_admin'.
                                AMP.'P=del_category_conf'.
                                AMP.'category_id='.$key.
                                $zurl,
                            __('cp.delete'));
                    else {
                        $delete = __('cp.delete');
                    }

                    $this->categories[] =
                        Cp::tableQuickRow(
                            '',
                            [
                                $key,
                                Cp::anchor(
                                    BASE.'?C=Administration'.
                                        AMP.'M=blog_admin'.
                                        AMP.'P=category_order'.
                                        AMP.'category_id='.$key.
                                        AMP.'group_id='.$group_id.
                                        AMP.'order=up'.$zurl,
                                    $up).
                                NBS.
                                Cp::anchor(
                                    BASE.'?C=Administration'.
                                        AMP.'M=blog_admin'.
                                        AMP.'P=category_order'.
                                        AMP.'category_id='.$key.
                                        AMP.'group_id='.$group_id.
                                        AMP.'order=down'.$zurl,
                                    $down),
                                Cp::quickDiv('defaultBold', NBS.$val[1]),
                                Cp::anchor(
                                    BASE.'?C=Administration'.
                                        AMP.'M=blog_admin'.
                                        AMP.'P=edit_category'.
                                        AMP.'category_id='.$key.
                                        AMP.'group_id='.$group_id.$zurl,
                                    __('cp.edit')),
                                $delete
                            ]
                        );
                }
                else
                {
                    $this->categories[] = Cp::input_select_option($key, $val[1], ($key == $p_id) ? '1' : '');
                }

                $this->category_subtree($key, $cat_array, $group_id, $depth=0, $type, $p_id);

            }
        }
    }




    // ------------------------------------
    //  Category sub-tree
    // ------------------------------------

    function category_subtree($cat_id, $cat_array, $group_id, $depth, $type, $p_id)
    {
        if ($type == 'table')
        {
            $spcr = '<span style="display:inline-block; margin-left:10px;"></span>';
            $indent = $spcr.'<img src="'.PATH_CP_IMG.'category_indent.png" border="0" width="12" height="12" title="indent" style="vertical-align:top; display:inline-block;"  />';
        }
        else
        {
            $spcr = '&nbsp;';
            $indent = $spcr.$spcr.$spcr.$spcr;
        }

        $up   = '<img src="'.PATH_CP_IMG.'arrow_up.png" border="0"  width="16" height="16" alt="" title="" />';
        $down = '<img src="'.PATH_CP_IMG.'arrow_down.png" border="0"  width="16" height="16" alt="" title="" />';


        if ($depth == 0)
        {
            $depth = 1;
        }
        else
        {
            $indent = str_repeat($spcr, $depth+1).$indent;
            $depth = ($type == 'table') ? $depth + 1 : $depth + 4;
        }

        $can_delete = true;
        if (Request::input('Z') == 1)
        {
            if (Session::access('can_edit_categories'))
            {
                $can_delete = true;
            }
            else
            {
                $can_delete = false;
            }
        }
        $zurl = (Request::input('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= (Request::input('cat_group') !== null) ? AMP.'cat_group='.Request::input('cat_group') : '';
        $zurl .= (Request::input('integrated') !== null) ? AMP.'integrated='.Request::input('integrated') : '';

        foreach ($cat_array as $key => $val)
        {
            if ($cat_id == $val[0])
            {
                $pre = ($depth > 2) ? "&nbsp;" : '';

                if ($type == 'table')
                {
                    if ($can_delete == true)
                        $delete = Cp::anchor(
                            BASE.'?C=Administration'.
                                AMP.'M=blog_admin'.
                                AMP.'P=del_category_conf'.
                                AMP.'category_id='.$key.$zurl,
                            __('cp.delete'));
                    else {
                        $delete = __('cp.delete');
                    }

                    $this->categories[] =

                    Cp::tableQuickRow(
                        '',
                        [
                            $key,
                            Cp::anchor(
                                BASE.'?C=Administration'.
                                    AMP.'M=blog_admin'.
                                    AMP.'P=category_order'.
                                    AMP.'category_id='.$key.
                                    AMP.'group_id='.$group_id.
                                    AMP.'order=up'.$zurl,
                                $up).
                        NBS.
                            Cp::anchor(
                                BASE.'?C=Administration'.
                                    AMP.'M=blog_admin'.
                                    AMP.'P=category_order'.
                                    AMP.'category_id='.$key.
                                    AMP.'group_id='.$group_id.
                                    AMP.'order=down'.$zurl,
                                $down),
                            Cp::quickDiv('defaultBold', $pre.$indent.NBS.$val[1]),
                            Cp::anchor(
                                BASE.'?C=Administration'.
                                    AMP.'M=blog_admin'.
                                    AMP.'P=edit_category'.
                                    AMP.'category_id='.$key.
                                    AMP.'group_id='.$group_id.$zurl,
                                __('cp.edit')),
                            $delete
                        ]
                    );
                }
                else
                {
                    $this->categories[] = Cp::input_select_option($key, $pre.$indent.NBS.$val[1], ($key == $p_id) ? '1' : '');
                }

                $this->category_subtree($key, $cat_array, $group_id, $depth, $type, $p_id);
            }
        }
    }


    // ------------------------------------
    //  Change Category Order
    // ------------------------------------

    function change_category_order()
    {
        if (! Session::access('can_edit_categories'))
        {
            return Cp::unauthorizedAccess();
        }

        // Fetch required globals

        foreach (['category_id', 'group_id', 'order'] as $val)
        {
            if ( ! isset($_GET[$val]))
            {
                return false;
            }

            $$val = $_GET[$val];
        }

        $zurl = (Request::input('Z') == 1) ? '&Z=1' : '';
        $zurl .= (Request::input('cat_group') !== null) ? '&cat_group='.Request::input('cat_group') : '';
        $zurl .= (Request::input('integrated') !== null) ? '&integrated='.Request::input('integrated') : '';

        // Return Location
        $return = '?C=Administration&M=blog_admin&P=category_editor&group_id='.$group_id.$zurl;

        // Fetch the parent ID
        $parent_id = DB::table('categories')
            ->where('category_id', $category_id)
            ->value('parent_id');

        // Is the requested category already at the beginning/end of the list?

        $dir = ($order == 'up') ? 'asc' : 'desc';

        $query = DB::table('categories')
            ->select('category_id')
            ->where('group_id', $group_id)
            ->where('parent_id', $parent_id)
            ->orderBy('category_order', $dir)
            ->first();

        if ($query->category_id == $category_id) {
            return redirect($return);
        }

        // Fetch all the categories in the parent
        $query = DB::table('categories')
            ->select('category_id', 'category_order')
            ->where('group_id', $group_id)
            ->where('parent_id', $parent_id)
            ->orderBy('category_order', 'asc')
            ->get();

        // If there is only one category, there is nothing to re-order
        if ($query->count() <= 1) {
            return redirect($return);
        }

        // Assign category ID numbers in an array except the category being shifted.
        // We will also set the position number of the category being shifted, which
        // we'll use in array_shift()

        $flag   = '';
        $i      = 1;
        $cats   = [];

        foreach ($query as $row)
        {
            if ($category_id == $row->category_id)
            {
                $flag = ($order == 'down') ? $i+1 : $i-1;
            }
            else
            {
                $cats[] = $row->category_id;
            }

            $i++;
        }

        array_splice($cats, ($flag -1), 0, $category_id);

        // Update the category order for all the categories within the given parent
        $i = 1;

        foreach ($cats as $val) {
            DB::table('categories')
                ->where('category_id', $val)
                ->update(['category_order' => $i]);

            $i++;
        }

        // Switch to custom order
        DB::table('category_groups')
            ->where('group_id', $group_id)
            ->update(['sort_order' => 'c']);

        return redirect($return);
    }

    // ------------------------------------
    //  Category management page
    // ------------------------------------
    // This function shows the list of current categories, as
    // well as the form used to submit a new category
    //-----------------------------------------------------------

    function category_manager($group_id = '', $update = FALSE)
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if ($group_id == '')
        {
            if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
            {
                return false;
            }
        }

        $zurl  = (Request::input('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= (Request::input('cat_group') !== null) ? AMP.'cat_group='.Request::input('cat_group') : '';
        $zurl .= (Request::input('integrated') !== null) ? AMP.'integrated='.Request::input('integrated') : '';

        $query = DB::table('category_groups')
            ->where('group_id', $group_id)
            ->select('group_name', 'sort_order')
            ->first();

        $group_name = $query->group_name;
        $sort_order = $query->sort_order;

        $r = '';
        $r .= Cp::quickDiv('tableHeading', $group_name);

        if ($update != FALSE)
        {
            $r .= Cp::quickDiv('successMessage', __('admin.category_updated'));
        }

        // Fetch the category tree

        $this->category_tree('table', $group_id, '', $sort_order);

        if (count($this->categories) == 0)
        {
            $r .= Cp::quickDiv('box', Cp::quickDiv('highlight', __('admin.no_category_message')));
        }
        else
        {
            $r .= Cp::table('tableBorder', '0', '0').
                  '<tr>'.PHP_EOL.
                  Cp::tableCell('tableHeadingAlt', 'ID', '2%').
                  Cp::tableCell('tableHeadingAlt', __('admin.order'), '8%').
                  Cp::tableCell('tableHeadingAlt', __('admin.category_name'), '50%').
                  Cp::tableCell('tableHeadingAlt', __('cp.edit'), '20%').
                  Cp::tableCell('tableHeadingAlt', __('cp.delete'), '20%');
            $r .= '</tr>'.PHP_EOL;

            foreach ($this->categories as $val)
            {
                $prefix = (strlen($val[0]) == 1) ? NBS : NBS;
                $r .= $val;
            }

            $r .= '</table>'.PHP_EOL;

            $r .= Cp::quickDiv('defaultSmall', '');

            // Category order

            if (Request::input('Z') == null)
            {
                $r .= Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=global_category_order'.AMP.'group_id='.$group_id.$zurl));
                $r .= Cp::div('box box320');
                $r .= Cp::quickDiv('defaultBold', __('admin.global_sort_order'));
                $r .= Cp::div('littlePadding');
                $r .= Cp::input_radio('sort_order', 'a', ($sort_order == 'a') ? 1 : '').NBS.__('admin.alpha').NBS.Cp::input_radio('sort_order', 'c', ($sort_order != 'a') ? 1 : '').NBS.__('admin.custom');
                $r .= NBS.Cp::input_submit(__('cp.update'));
                $r .= '</div>'.PHP_EOL;
                $r .= '</div>'.PHP_EOL;
                $r .= '</form>'.PHP_EOL;
            }
        }

        // Build category tree for javascript replacement

        if (Request::input('Z') == 1)
        {
            $PUB = new Publish;
            $PUB->category_tree((Request::input('cat_group') !== null) ? Request::input('cat_group') : Request::input('group_id'), 'new', '', '', (Request::input('integrated') == 'y') ? 'y' : 'n');

            $cm = "";
            foreach ($PUB->categories as $val)
            {
                $cm .= $val;
            }
            $cm = preg_replace("/(\r\n)|(\r)|(\n)/", '', $cm);

            Cp::$extra_header = '
            <script type="text/javascript">

                function update_cats()
                {
                    var str = "'.$cm.'";
                    opener.swap_categories(str);
                    window.close();
                }

            </script>';

            // $r .= Cp::quickDiv('littlePadding', Cp::quickDiv('defaultCenter', '<a href="javascript:update_cats();"><b>'.__('admin.update_publish_cats').'</b></a>'));

            $r .= '<form>';
            $r .= Cp::quickDiv('littlePadding', Cp::quickDiv('defaultCenter', '<input type="submit" value="'.NBS.__('admin.update_publish_cats').NBS.'" onclick="update_cats();"/>'  ));
            $r .= '</form>';
        }


       // Assign output data

        Cp::$title = __('admin.categories');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=categories', __('admin.category_groups'))).
                      Cp::breadcrumbItem(__('admin.categories'));


        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=edit_category'.AMP.'group_id='.$group_id,
            __('admin.new_category')
        ];

        $r = Cp::header(__('admin.categories'), $right_links).$r;

        Cp::$body  = $r;
    }


    // ------------------------------------
    //  Set Global Category Order
    // ------------------------------------

    function global_category_order()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return false;
        }

        $order = ($_POST['sort_order'] == 'a') ? 'a' : 'c';

        $query = DB::table('sort_order')->select('sort_order')->where('group_id', $group_id);

        if ($order == 'a')
        {
            if ( ! isset($_POST['override']))
            {
                return $this->global_category_order_confirm();
            }
            else
            {
                $this->reorder_cats_alphabetically();
            }
        }

        DB::table('category_groups')
            ->where('group_id', $group_id)
            ->update(['sort_order' => $order]);

        return redirect('?C=Administration&M=blog_admin&P=category_editor&group_id='.$group_id);
    }

    // ------------------------------------
    //  Category order change confirm
    // ------------------------------------

    function global_category_order_confirm()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return false;
        }

        Cp::$title = __('admin.global_sort_order');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=categories', __('admin.category_groups'))).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$group_id, __('admin.categories'))).
                      Cp::breadcrumbItem(__('admin.global_sort_order'));

        Cp::$body = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=global_category_order'.AMP.'group_id='.$group_id))
                    .Cp::input_hidden('sort_order', $_POST['sort_order'])
                    .Cp::input_hidden('override', 1)
                    .Cp::quickDiv('tableHeading', __('admin.global_sort_order'))
                    .Cp::div('box')
                    .Cp::quickDiv('defaultBold', __('admin.category_order_confirm_text'))
                    .Cp::quickDiv('alert', BR.__('admin.category_sort_warning').BR.BR)
                    .'</div>'.PHP_EOL
                    .Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.update')))
                    .'</form>'.PHP_EOL;
    }


    // ------------------------------------
    //  Re-order Categories Alphabetically
    // ------------------------------------

    function reorder_cats_alphabetically()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return false;
        }

        $data = $this->process_category_group($group_id);

        if (count($data) == 0)
        {
            return false;
        }

        foreach($data as $cat_id => $cat_data)
        {
            DB::table('categories')
                ->where('category_id', $cat_id)
                ->update(['category_order' => $cat_data[1]]);
        }

        return true;
    }


    // ------------------------------------
    //  Process nested category group
    // ------------------------------------

    function process_category_group($group_id)
    {
        $query = DB::table('categories')
            ->where('group_id', $group_id)
            ->orderBy('parent_id')
            ->orderBy('category_name')
            ->select('category_name', 'category_id', 'parent_id')
            ->get();

        if ($query->count() == 0)
        {
            return false;
        }

        foreach($query as $row)
        {
            $this->cat_update[$row->category_id]  = array($row->parent_id, '1', $row->category_name);
        }

        $order = 0;

        foreach($this->cat_update as $key => $val)
        {
            if (0 == $val[0])
            {
                $order++;
                $this->cat_update[$key]['1'] = $order;
                $this->process_subcategories($key);  // Sends parent_id
            }
        }

        return $this->cat_update;
    }

    // ------------------------------------
    //  Process Subcategories
    // ------------------------------------

    function process_subcategories($parent_id)
    {
        $order = 0;

        foreach($this->cat_update as $key => $val)
        {
            if ($parent_id == $val[0])
            {
                $order++;
                $this->cat_update[$key]['1'] = $order;
                $this->process_subcategories($key);
            }
        }
    }



    // ------------------------------------
    //  New / Edit category form
    // ------------------------------------
    // This function displays an existing category in a form
    // so that it can be edited.
    //-----------------------------------------------------------

    function edit_category_form()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return Cp::unauthorizedAccess();
        }

        $cat_id = Request::input('category_id');

        // Get the category sort order for the parent select field later on

        $sort_order = DB::table('category_groups')
            ->where('group_id', $group_id)
            ->value('sort_order');

        $default = ['category_name', 'category_url_title', 'category_description', 'category_image', 'category_id', 'parent_id'];

        if ($cat_id)
        {
            $query = DB::table('categories')
                ->where('category_id', $cat_id)
                ->select(
                    'category_id',
                    'category_name',
                    'category_url_title',
                    'category_description',
                    'category_image',
                    'group_id',
                    'parent_id')
                ->first();

            if (!$query) {
                return Cp::unauthorizedAccess();
            }

            foreach ($default as $val) {
                $$val = $query->$val;
            }
        }
        else
        {
            foreach ($default as $val) {
                $$val = '';
            }
        }

        // Build our output

        $title = ( ! $cat_id) ? 'new_category' : 'edit_category';

        $zurl  = (Request::input('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= (Request::input('cat_group') !== null) ? AMP.'cat_group='.Request::input('cat_group') : '';
        $zurl .= (Request::input('integrated') !== null) ? AMP.'integrated='.Request::input('integrated') : '';

        Cp::$title = __($title);

        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor( BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=categories', __('admin.category_groups'))).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$group_id, __('admin.categories'))).
                      Cp::breadcrumbItem(__($title));

        $word_separator = Site::config('word_separator') != "dash" ? '_' : '-';

        // ------------------------------------
        //  Create Foreign Character Conversion JS
        // ------------------------------------

        $foreign_characters = [
            '223'   =>  "ss", // ß
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
        ];

        $foreign_replace = '';

        foreach($foreign_characters as $old => $new)
        {
            $foreign_replace .= "if (c == '$old') {NewTextTemp += '$new'; continue;}\n\t\t\t\t";
        }

        $r = <<<SCRIPPITYDOO
        <script type="text/javascript">
        <!--
        // ------------------------------------
        //  Live URL Title Function
        // ------------------------------------

        function liveUrlTitle()
        {
            var NewText = document.getElementById("category_name").value;

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

            document.getElementById("category_url_title").value = NewText;

        }
        -->
        </script>
SCRIPPITYDOO;

        $r .= Cp::quickDiv('tableHeading', __($title));

        $r .= Cp::formOpen(array('id' => 'category_form', 'action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_category'.$zurl)).
              Cp::input_hidden('group_id', $group_id);

        if ($cat_id)
        {
            $r .= Cp::input_hidden('category_id', $cat_id);
        }

        $r .= Cp::div('box');
        $r .= Cp::div('littlePadding').
              Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', Cp::required().NBS.__('admin.category_name'))).
              Cp::input_text('category_name', $category_name, '20', '100', 'input', '400px', (( ! $cat_id) ? 'onkeyup="liveUrlTitle();"' : ''), TRUE).
              '</div>'.PHP_EOL;

        $r .= Cp::div('littlePadding').
              Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.category_url_title'))).
              Cp::input_text('category_url_title', $category_url_title, '20', '75', 'input', '400px', '', TRUE).
              '</div>'.PHP_EOL;

        $r .= Cp::div('littlePadding').
              Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.category_description'))).
              Cp::input_textarea('category_description', $category_description, 4, 'textarea', '400px').
              '</div>'.PHP_EOL;

        $r .= Cp::div('littlePadding').
              Cp::quickDiv('defaultBold', __('admin.category_image')).
              Cp::quickDiv('littlePadding', Cp::quickDiv('', __('admin.category_img_blurb'))).
              Cp::input_text('category_image', $category_image, '40', '120', 'input', '400px').
              '</div>'.PHP_EOL;

        $r .= Cp::div('littlePadding').
              Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.category_parent'))).
              Cp::input_select_header('parent_id').
              Cp::input_select_option('0', __('admin.none'));

        $this->category_tree('list', $group_id, $parent_id, $sort_order);

        foreach ($this->categories as $val)
        {
            $prefix = (strlen($val[0]) == 1) ? NBS : NBS;
            $r .= $val;
        }

        $r .= Cp::input_select_footer().
              '</div>'.PHP_EOL;
        $r .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Submit Button
        // ------------------------------------

        $r .= Cp::div('paddingTop');
        $r .= ( ! $cat_id) ? Cp::input_submit(__('cp.submit')) : Cp::input_submit(__('cp.update'));
        $r .= '</div>'.PHP_EOL;

        $r .= '</form>'.PHP_EOL;

        Cp::$body = $r;
    }



    // ------------------------------------
    //  Category submission handler
    // ------------------------------------
    // This function receives the category information after
    // being submitted from the form (new or edit) and stores
    // the info in the database.
    //-----------------------------------------------------------

    function update_category()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return Cp::unauthorizedAccess();
        }

        $edit = Request::has('category_id');

        // ------------------------------------
        //  Validation
        // ------------------------------------

        $validator = Validator::make(request()->all(), [
            'category_url_title' => 'regex:/^[\pL\pM\pN_]+$/u',
            'category_name'      => 'required',
            'parent_id'          => 'numeric',
            'group_id'           => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return Cp::errorMessage(implode(BR, $validator->errors()->all()));
        }

        $data = Request::only(
            [
                'category_name',
                'category_url_title',
                'category_description',
                'category_image',
                'parent_id',
                'group_id',
                'category_id'
            ]
        );

        if(is_null($data['category_description'])) {
            $data['category_description'] = '';
        }

        if(empty($data['parent_id'])) {
            $data['parent_id'] = 0;
        }

        // ------------------------------------
        //  Create Category URL Title
        // ------------------------------------

        if (empty($data['category_url_title'])) {
            $data['category_url_title'] = Regex::create_url_title($data['category_name'], true);

            // Integer? Not allowed, so we show an error.
            if (is_numeric($data['category_url_title'])) {
                return Cp::errorMessage(__('admin.category_url_title_is_numeric'));
            }

            if (trim($data['category_url_title']) == '') {
                return Cp::errorMessage(__('admin.unable_to_create_category_url_title'));
            }
        }

        // ------------------------------------
        //  Cat URL Title must be unique within the group
        // ------------------------------------

        $query = DB::table('categories')
            ->where('category_url_title', $data['category_url_title'])
            ->where('group_id', $group_id);

        if ($edit === true) {
            $query->where('category_id', '!=', $data['category_id']);
        }

        $query = $query->get();

        if ($query->count() > 0) {
            return Cp::errorMessage(__('admin.duplicate_category_url_title'));
        }

        // ------------------------------------
        //  Finish data prep for insertion
        // ------------------------------------

        $data['category_name'] = str_replace(['<','>'], ['&lt;','&gt;'], $data['category_name']);

        // ------------------------------------
        //  Insert
        // ------------------------------------

        if ($edit == FALSE)
        {
            $data['category_order'] = 0; // Temp
            $field_cat_id = DB::table('categories')->insertGetId($data);

            $update = false;

            // ------------------------------------
            //  Re-order categories
            // ------------------------------------

            // When a new category is inserted we need to assign it an order.
            // Since the list of categories might have a custom order, all we
            // can really do is position the new category alphabetically.

            // First we'll fetch all the categories alphabetically and assign
            // the position of our new category
            $query = DB::table('categories')
                ->where('group_id', $group_id)
                ->where('parent_id', $_POST['parent_id'])
                ->orderBy('category_name', 'asc')
                ->select('category_id', 'category_name')
                ->get();

            $position = 0;
            $cat_id = '';

            foreach ($query as $row) {
                if ($data['category_name'] == $row->category_name) {
                    $cat_id = $row->category_id;
                    break;
                }

                $position++;
            }

            // Next we'll fetch the list of categories ordered by the custom order
            // and create an array with the category ID numbers
            $cat_array = DB::table('categories')
                ->where('group_id', $group_id)
                ->where('parent_id', $data['parent_id'])
                ->where('category_id', '!=', $cat_id)
                ->orderBy('category_order')
                ->pluck('category_id')
                ->all();

            // Now we'll splice in our new category to the array.
            // Thus, we now have an array in the proper order, with the new
            // category added in alphabetically

            array_splice($cat_array, $position, 0, $cat_id);

            // Lastly, update the whole list

            $i = 1;
            foreach ($cat_array as $val)
            {
                DB::table('categories')
                    ->where('category_id', $val)
                    ->update(['category_order' => $i]);

                $i++;
            }
        }
        else
        {

            if ($data['category_id'] == $data['parent_id']) {
                $data['parent_id'] = 0;
            }

            // ------------------------------------
            //  Check for parent becoming child of its child...oy!
            // ------------------------------------

            $query = DB::table('categories')
                ->where('category_id', Request::input('category_id'))
                ->select('parent_id', 'group_id')
                ->first();

            if (Request::input('parent_id') !== 0 && $query && $query->parent_id !== Request::input('parent_id'))
            {
                $children  = [];
                $cat_array = $this->category_tree('data', $query->group_id);

                foreach($cat_array as $key => $values)
                {
                    if ($values['0'] == Request::input('category_id'))
                    {
                        $children[] = $key;
                    }
                }

                if (sizeof($children) > 0)
                {
                    if (($key = array_search(Request::input('parent_id'), $children)) !== FALSE)
                    {
                        DB::table('categories')
                            ->where('category_id', $children[$key])
                            ->update(['parent_id' => $query->parent_id]);
                    }
                    // ------------------------------------
                    //  Find All Descendants
                    // ------------------------------------
                    else
                    {
                        while(sizeof($children) > 0)
                        {
                            $now = array_shift($children);

                            foreach($cat_array as $key => $values)
                            {
                                if ($values[0] == $now)
                                {
                                    if ($key == Request::input('parent_id'))
                                    {
                                        DB::table('categories')
                                            ->where('category_id', $key)
                                            ->update(['parent_id' => $query->parent_id]);
                                        break 2;
                                    }

                                    $children[] = $key;
                                }
                            }
                        }
                    }
                }
            }

            DB::table('categories')
                ->where('category_id', Request::input('category_id'))
                ->where('group_id', Request::input('group_id'))
                ->update(
                    [
                        'category_name'         => Request::input('category_name'),
                        'category_url_title'    => Request::input('category_url_title'),
                        'category_description'  => Request::input('category_description'),
                        'category_image'        => Request::input('category_image'),
                        'parent_id'             => Request::input('parent_id')
                    ]
                );

            $update = true;

            // need this later for custom fields
            $field_cat_id = Request::input('category_id');
        }

        return $this->category_manager($group_id, $update);
    }


    // ------------------------------------
    //  Delete category confirm
    // ------------------------------------

    function delete_category_confirm()
    {
        if ( ! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $cat_id = Request::input('category_id')) {
            return false;
        }

        $query = DB::table('categories')
            ->where('category_id', $cat_id)
            ->select('category_name', 'group_id')
            ->first();

        if (!$query)
        {
            return false;
        }

        // ------------------------------------
        //  Check privileges
        // ------------------------------------

        if (Request::input('Z') == 1 and Session::userdata('group_id') != 1 and ! Session::access('can_edit_categories'))
        {
            return Cp::unauthorizedAccess();
        }

        Cp::$title = __('admin.delete_category');

        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor( BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=categories', __('admin.category_groups'))).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=category_editor'.AMP.'group_id='.$query->group_id, __('admin.categories'))).
                      Cp::breadcrumbItem(__('admin.delete_category'));

        $zurl = (Request::input('Z') == 1) ? AMP.'Z=1' : '';
        $zurl .= (Request::input('cat_group') !== null) ? AMP.'cat_group='.Request::input('cat_group') : '';
        $zurl .= (Request::input('integrated') !== null) ? AMP.'integrated='.Request::input('integrated') : '';

        Cp::$body = Cp::deleteConfirmation(
            [
                'url'       => 'C=Administration'.
                    AMP.'M=blog_admin'.
                    AMP.'P=del_category'.
                    AMP.'group_id='.$query->group_id.
                    AMP.'category_id='.$cat_id.
                    $zurl,
                'heading'   => 'delete_category',
                'message'   => 'delete_category_confirmation',
                'item'      => $query->category_name,
                'extra'     => '',
                'hidden'    => ''
            ]
        );
    }

    // ------------------------------------
    //  Delete category
    // ------------------------------------
    // Deletes a cateogory and removes it from all weblog entries
    //-----------------------------------------------------------

    function delete_category()
    {
        if (! Session::access('can_edit_categories')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $cat_id = Request::input('category_id'))
        {
            return false;
        }

        if ( ! is_numeric($cat_id))
        {
            return false;
        }

        $query = DB::table('categories')
            ->select('group_id')
            ->where('category_id', $cat_id)
            ->first();

        if (!$query)
        {
            return false;
        }

        $group_id = $query->group_id;

        DB::table('weblog_entry_categories')->where('category_id', $cat_id)->delete();
        DB::table('categories')->where('parent_id', $cat_id)->where('group_id', $group_id)->update(['parent_id' => 0]);
        DB::table('categories')->where('category_id', $cat_id)->where('group_id', $group_id)->delete();

        $this->category_manager($group_id);
    }

//=====================================================================
//  STATUS ADMINISTRATION FUNCTIONS
//=====================================================================



    // ------------------------------------
    //  Status overview page
    // ------------------------------------
    // This function show the list of current status groups.
    // It is accessed by clicking "Custom entry statuses"
    // in the "admin" tab
    //-----------------------------------------------------------

    function status_overview($message = '')
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        Cp::$title  = __('admin.status_groups');
        Cp::$crumb  = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                       Cp::breadcrumbItem(__('admin.status_groups'));

        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=status_group_editor',
            __('admin.create_new_status_group')
        ];

        $r = Cp::header(__('admin.status_groups'), $right_links);

        // Fetch category groups
        $query = DB::table('status_groups')
            ->groupBy('status_groups.group_id')
            ->orderBy('status_groups.group_name')
            ->select(
                'status_groups.group_id',
                'status_groups.group_name'
            )->get();

        if ($query->count() == 0)
        {
            if ($message != '') {
                Cp::$body .= Cp::quickDiv('successMessage', $message);
            }

            $r .= Cp::div('box');
            $r .= Cp::quickDiv('littlePadding', Cp::heading(__('admin.no_status_group_message'), 5));
            $r .= Cp::quickDiv('littlePadding', Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=status_group_editor', __('admin.create_new_status_group')));
            $r .= '</div>'.PHP_EOL;

            return Cp::$body = $r;
        }

        if ($message != '') {
            $r .= Cp::quickDiv('successMessage', $message);
        }

        $r .= Cp::table('tableBorder', '0', '', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeading', '', '4').
              __('admin.status_groups').
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;


        $i = 0;

        foreach($query as $row)
        {

            $r .= '<tr>'.PHP_EOL;

            $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', $row->group_name));

            $field_count = $query = DB::table('statuses')
                ->where('statuses.group_id', $row->group_id)
                ->count();

            $r .= Cp::tableCell('',
                  '('.$field_count.')'.'&nbsp;'.
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=status_editor'.AMP.'group_id='.$row->group_id,
                                __('admin.add_edit_statuses')
                              ));

            $r .= Cp::tableCell('',
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=status_group_editor'.AMP.'group_id='.$row->group_id,
                                __('admin.edit_status_group_name')
                              ));


            $r .= Cp::tableCell('',
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=status_group_del_conf'.AMP.'group_id='.$row->group_id,
                                __('admin.delete_status_group')
                              ));

            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        Cp::$body  = $r;
    }



    // ------------------------------------
    //  New/edit status group form
    // ------------------------------------
    // This function lets you create or edit a status group
    //-----------------------------------------------------------

    function edit_status_group_form()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }



        // Set default values

        $edit       = false;
        $group_id   = '';
        $group_name = '';

        // If we have the group_id variable it's an edit request, so fetch the status data

        if ($group_id = Request::input('group_id'))
        {
            $edit = true;

            if ( ! is_numeric($group_id))
            {
                return false;
            }

            $query = DB::table('status_groups')
                ->where('group_id', $group_id)
                ->first();

            foreach ($query as $key => $val)
            {
                $$key = $val;
            }
        }


        if ($edit == FALSE)
            $title = __('admin.create_new_status_group');
        else
            $title = __('admin.edit_status_group');

        // Build our output

        $r  = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_status_group'));

        if ($edit == TRUE)
            $r .= Cp::input_hidden('group_id', $group_id);


        $r .= Cp::quickDiv('tableHeading', $title);

        $r .= Cp::div('box').
              Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.name_of_status_group'))).
              Cp::quickDiv('littlePadding', Cp::input_text('group_name', $group_name, '20', '50', 'input', '260px'));

        $r .= '</div>'.PHP_EOL;

        $r .= Cp::div('paddingTop');
        if ($edit == FALSE)
            $r .= Cp::input_submit(__('cp.submit'));
        else
            $r .= Cp::input_submit(__('cp.update'));

        $r .= '</div>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        Cp::$title = $title;
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=statuses', __('admin.status_groups'))).
                      Cp::breadcrumbItem($title);
        Cp::$body  = $r;
    }


    // ------------------------------------
    //  Status group submission handler
    // ------------------------------------
    // This function receives the submitted status group data
    // and puts it in the database
    //-----------------------------------------------------------

    function update_status_group()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        $edit = Request::has('group_id');

        // ------------------------------------
        //  Validation
        // ------------------------------------

        $validator = Validator::make(request()->all(), [
            'group_name'      => 'required|regex:#^[a-zA-Z0-9_\-/\s]+$#i',
            'group_id'        => 'integer'
        ]);

        if ($validator->fails()) {
            return Cp::errorMessage(implode(BR, $validator->errors()->all()));
        }

        $data = Request::only(
            [
                'group_id',
                'group_name'
            ]
        );

        // Group Name taken?
        $query = DB::table('status_groups')
            ->where('group_name', $data['group_name']);

        if ($edit === true) {
            $query->where('group_id', '!=', $data['group_id']);
        }

        if ($query->count() > 0) {
            return Cp::errorMessage(__('admin.taken_status_group_name'));
        }

        // ------------------------------------
        //  Insert/Update
        // ------------------------------------

        if ($edit == FALSE)
        {
            $group_id = DB::table('status_groups')->insertGetId($data);

            $success_msg = __('admin.status_group_created');

            $crumb = Cp::breadcrumbItem(__('admin.new_status'));

            Cp::log(__('admin.status_group_created').'&nbsp;'.$data['group_name']);
        }
        else
        {
            DB::table('status_groups')
                ->where('group_id', $data['group_id'])
                ->update($data);

            $success_msg = __('admin.status_group_updated');

            $crumb = Cp::breadcrumbItem(__('cp.update'));
        }


        $message = Cp::quickDiv('successMessage', $success_msg.'<b>'.$data['group_name'].'</b>');

        if ($edit === false)
        {
            $query = DB::table('weblogs')
                ->select('weblog_id')
                ->get();

            if ($query->count() > 0)
            {
                $message .= Cp::div('littlePadding').Cp::span('alert').__('admin.assign_group_to_weblog').'</span>'.PHP_EOL.'&nbsp;';

                if ($query->count() == 1)
                {
                    $link = 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=group_prefs'.AMP.'weblog_id='.$query->weblog_id;
                }
                else
                {
                    $link = 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=blog_list';
                }

                $message .= Cp::anchor(BASE.'?'.$link, __('admin.click_to_assign_group')).'</div>'.PHP_EOL;
            }
        }

        return $this->status_overview($message);
    }

    // ------------------------------------
    //  Delete status group confirm
    // ------------------------------------
    // Warning message shown when you try to delete a status group
    //-----------------------------------------------------------

    function delete_status_group_conf()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id)) {
            return false;
        }

        $group_name = DB::table('status_groups')->where('group_id', $group_id)->value('group_name');

        if (empty($group_name)) {
            return false;
        }

        Cp::$title = __('admin.delete_group');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=statuses', __('admin.status_groups'))).
                      Cp::breadcrumbItem(__('admin.delete_group'));


        Cp::$body = Cp::deleteConfirmation(
            [
                'url'       => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=delete_status_group'.AMP.'group_id='.$group_id,
                'heading'   => 'delete_group',
                'message'   => 'delete_status_group_confirmation',
                'item'      => $group_name,
                'extra'     => '',
                'hidden'    => ['group_id' => $group_id]
            ]
        );
    }

    // ------------------------------------
    //  Delete status group
    // ------------------------------------
    // This function nukes the status group and associated statuses
    //-----------------------------------------------------------

    function delete_status_group()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id)) {
            return false;
        }

        $group_name = DB::table('status_groups')->where('group_id', $group_id)->value('group_name');

        if (empty($group_name)) {
            return false;
        }

        DB::table('status_groups')->where('group_id', $group_id)->delete();
        DB::table('statuses')->where('group_id', $group_id)->delete();

        Cp::log(__('admin.status_group_deleted').'&nbsp;'.$group_name);

        $message = __('admin.status_group_deleted').'&nbsp;'.'<b>'.$group_name.'</b>';

        return $this->status_overview($message);
    }

    // ------------------------------------
    //  Status manager
    // ------------------------------------
    // This function lets you create/edit statuses
    //-----------------------------------------------------------

    function status_manager($group_id = '', $update = false)
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if ($group_id == '') {
            if (($group_id = Request::input('group_id')) === FALSE) {
                return false;
            }
        }

        if ( ! is_numeric($group_id)) {
            return false;
        }

        $i = 0;

        $r = '';

        if ($update === true)
        {
            if (!Request::has('status_id'))
            {
                $r .= Cp::quickDiv('successMessage', __('admin.status_created'));
            }
            else
            {
                $r .= Cp::quickDiv('successMessage', __('admin.status_updated'));
            }
        }

        $r .= Cp::table('', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('', '55%', '', '', 'top');


        $query = DB::table('status_groups')
            ->select('group_name')
            ->where('group_id', $group_id)
            ->first();

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeading', '', '3').
              __('admin.status_group').':'.'&nbsp;'.$query->group_name.
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $query = DB::table('statuses')
            ->where('group_id', $group_id)
            ->orderBy('status_order')
            ->select('status_id', 'status')
            ->get();

        $total = $query->count() + 1;

        if ($query->count() > 0)
        {
            foreach ($query as $row)
            {

                $del =
                    ($row->status != 'open' AND $row->status != 'closed')
                    ?
                    Cp::anchor(
                        BASE.'?C=Administration'.
                            AMP.'M=blog_admin'.
                            AMP.'P=del_status_conf'.
                            AMP.'status_id='.$row->status_id,
                        __('cp.delete')
                    )
                    :
                    '--';

                $status_name = ($row->status == 'open' OR $row->status == 'closed') ? __($row->status) : $row->status;

                $r .= '<tr>'.PHP_EOL.
                      Cp::tableCell('', Cp::quickSpan('defaultBold', $status_name)).
                      Cp::tableCell('', Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=edit_status'.AMP.'status_id='.$row->status_id, __('cp.edit'))).
                      Cp::tableCell('', $del).
                      '</tr>'.PHP_EOL;
            }
        }
        else
        {
            $r .= '<tr>'.PHP_EOL.
                      Cp::tableCell('', '<em>No statuses yet.</em>').
                  '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        $r .= Cp::quickDiv('littlePadding', Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=edit_status_order'.AMP.'group_id='.$group_id, __('admin.change_status_order')));

        $r .= '</td>'.PHP_EOL.
              Cp::td('rightCel', '45%', '', '', 'top');

        // Build the right side output

        $r .= Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_status'.AMP.'group_id='.$group_id)).
              Cp::input_hidden('group_id', $group_id);

        $r .= Cp::quickDiv('tableHeading', __('admin.create_new_status'));

        $r .= Cp::div('box');

        $r .= Cp::quickDiv('', Cp::quickDiv('littlePadding', __('admin.status_name')).Cp::input_text('status', '', '30', '60', 'input', '260px'));

        $r .= Cp::quickDiv('',  Cp::quickDiv('littlePadding', __('admin.status_order')).Cp::input_text('status_order', $total, '20', '3', 'input', '50px'));

        $r .= '</div>'.PHP_EOL;


        if (Session::userdata('group_id') == 1)
        {
            $query = DB::table('member_group_preferences')
                ->join('member_groups', 'member_groups.group_id', '=', 'member_group_preferences.group_id')
                ->whereNotIn('member_groups.group_id', [1,2])
                ->where('member_group_preferences.value', 'y')
                ->where('member_group_preferences.handle', 'can_access_publish')
                ->orderBy('member_groups.group_name')
                ->select('member_groups.group_id', 'member_groups.group_name')
                ->get();

            $table_end = true;

            if ($query->count() == 0) {
                $table_end = false;
            }
            else
            {
                $r .= Cp::quickDiv('paddingTop', Cp::heading(__('admin.restrict_status_to_group'), 5));

                $r .= Cp::table('tableBorder', '0', '', '100%').
                      '<tr>'.PHP_EOL.
                      Cp::td('tableHeading', '', '').
                      __('admin.member_group').
                      '</td>'.PHP_EOL.
                      Cp::td('tableHeading', '', '').
                      __('admin.can_edit_status').
                      '</td>'.PHP_EOL.
                      '</tr>'.PHP_EOL;

                $i = 0;

                $group = [];

                foreach ($query as $row)
                {
                    $r .= '<tr>'.PHP_EOL.
                          Cp::td('', '50%').
                          $row->group_name.
                          '</td>'.PHP_EOL.
                          Cp::td('', '50%');

                    $selected = ( ! isset($group[$row->group_id])) ? 1 : '';

                    $r .= Cp::qlabel(__('admin.yes')).NBS.
                          Cp::input_radio('access_'.$row->group_id, 'y', $selected).'&nbsp;';

                    $selected = (isset($group[$row->group_id])) ? 1 : '';

                    $r .= Cp::qlabel(__('admin.no')).NBS.
                          Cp::input_radio('access_'.$row->group_id, 'n', $selected).'&nbsp;';

                    $r .= '</td>'.PHP_EOL
                         .'</tr>'.PHP_EOL;
                }
            }
        }

        if ($table_end == TRUE) {
            $r .= '</table>'.PHP_EOL;
        }

        $r .= Cp::quickDiv('paddingTop', Cp::input_submit(__('cp.submit')));

        $r .= '</form>'.PHP_EOL;

        $r .= '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL;


        Cp::$title = __('admin.statuses');

        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=statuses', __('admin.status_groups'))).
                      Cp::breadcrumbItem(__('admin.statuses'));

        Cp::$body  = $r;
    }



    // ------------------------------------
    //  Status submission handler
    // ------------------------------------
    // This function recieves the submitted status data and
    // inserts it in the database.
    //-----------------------------------------------------------

    function update_status()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        $edit = Request::has('status_id');

        // ------------------------------------
        //  Validation
        // ------------------------------------

        $validator = Validator::make(request()->all(), [
            'status'       => 'required|regex:#^([-a-z0-9_\+ ])+$#i',
            'status_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return Cp::errorMessage(implode(BR, $validator->errors()->all()));
        }

        $data = Request::only(
            [
                'group_id',
                'status',
                'status_id',
                'status_order',
            ]
        );

        if (empty($data['status_order'])) {
            $data['status_order'] = 0;
        }

        if ($edit === false)
        {
            $count = DB::table('statuses')
                ->where('status', $data['status'])
                ->where('group_id', $data['group_id'])
                ->count();

            if ($count > 0) {
                return Cp::errorMessage(__('admin.duplicate_status_name'));
            }

            $status_id = DB::table('statuses')->insertGetId($data);
        }

        if ($edit === true)
        {
            $status_id = $data['status_id'];

            $count = DB::table('statuses')
                ->where('status', $data['status'])
                ->where('group_id', $data['group_id'])
                ->where('status_id', '!=', $data['status_id'])
                ->count();

            if ($count > 0) {
                return Cp::errorMessage(__('admin.duplicate_status_name'));
            }

            DB::table('statuses')
                ->where('status_id', $data['status_id'])
                ->where('group_id', Request::input('group_id'))
                ->update($data);

            DB::table('status_no_access')->where('status_id', $data['status_id'])->delete();

            // If the status name has changed, we need to update weblog entries with the new status.
            if (Request::has('old_status') && Request::input('old_status') != $data['status'])
            {
                $query = DB::table('weblogs')
                    ->where('status_group', $data['group_id'])
                    ->get();

                foreach ($query as $row)
                {
                    DB::table('weblog_entries')
                        ->where('status', $data['old_status'])
                        ->where('weblog_id', $row->weblog_id)
                        ->update(['status' => $data['status']]);
                }
            }
        }


        // Set access privs
        foreach (Request::all() as $key => $val)
        {
            if (substr($key, 0, 7) == 'access_' AND $val == 'n')
            {
                DB::table('status_no_access')
                    ->insert(
                        [
                            'status_id' => $status_id,
                            'member_group' => substr($key, 7)
                        ]);
            }
        }

        return $this->status_manager($data['group_id'], true);
    }

    // ------------------------------------
    //  Edit status form
    // ------------------------------------

    function edit_status_form()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        if (($status_id = Request::input('status_id')) === FALSE OR ! is_numeric($status_id))
        {
            return false;
        }

        $query = DB::table('statuses')->where('status_id', $status_id)->first();

        $group_id       = $query->group_id;
        $status         = $query->status;
        $status_order   = $query->status_order;
        $status_id      = $query->status_id;

        // Build our output
        $r  = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_status')).
              Cp::input_hidden('status_id', $status_id).
              Cp::input_hidden('old_status',  $status).
              Cp::input_hidden('group_id',  $group_id);

        $r .= Cp::quickDiv('tableHeading', __('admin.edit_status'));
        $r .= Cp::div('box');

        if ($status == 'open' OR $status == 'closed')
        {
            $r .= Cp::input_hidden('status', $status);

            $r .= Cp::quickDiv(
                    'littlePadding',
                    Cp::quickSpan('defaultBold', __('admin.status_name').':').
                        NBS.
                        __($status));
        }
        else
        {
            $r .= Cp::quickDiv('', Cp::quickDiv('littlePadding', __('admin.status_name')).Cp::input_text('status', $status, '30', '60', 'input', '260px'));
        }

        $r .= Cp::quickDiv('', Cp::quickDiv('littlePadding', __('admin.status_order')).Cp::input_text('status_order', $status_order, '20', '3', 'input', '50px'));

        $r .= '</div>'.PHP_EOL;

        if (Session::userdata('group_id') == 1)
        {
            $query = DB::table('member_groups')
                ->whereNotIn('group_id', [1,2,3,4])
                ->orderBy('group_name')
                ->select('group_id', 'group_name')
                ->get();

            $table_end = true;

            if ($query->count() == 0)
            {
                $table_end = false;
            }
            else
            {
                $r .= Cp::quickDiv('paddingTop', Cp::heading(__('admin.restrict_status_to_group'), 5));

                $r .= Cp::table('tableBorder', '0', '', '100%').
                      '<tr>'.PHP_EOL.
                      Cp::td('tableHeadingAlt', '', '').
                      __('admin.member_group').
                      '</td>'.PHP_EOL.
                      Cp::td('tableHeadingAlt', '', '').
                      __('admin.can_edit_status').
                      '</td>'.PHP_EOL.
                      '</tr>'.PHP_EOL;

                    $i = 0;

                $group = [];

                $result = DB::table('status_no_access')
                    ->select('member_group')
                    ->where('status_id', $status_id)
                    ->get();

                if ($result->count() != 0)
                {
                    foreach($result as $row)
                    {
                        $group[$row->member_group] = true;
                    }
                }

                foreach ($query as $row)
                {

                        $r .= '<tr>'.PHP_EOL.
                              Cp::td('', '50%').
                              $row->group_name.
                              '</td>'.PHP_EOL.
                              Cp::td('', '50%');

                        $selected = ( ! isset($group[$row->group_id])) ? 1 : '';

                        $r .= Cp::qlabel(__('admin.yes')).NBS.
                              Cp::input_radio('access_'.$row->group_id, 'y', $selected).'&nbsp;';

                        $selected = (isset($group[$row->group_id])) ? 1 : '';

                        $r .= Cp::qlabel(__('admin.no')).NBS.
                              Cp::input_radio('access_'.$row->group_id, 'n', $selected).'&nbsp;';

                        $r .= '</td>'.PHP_EOL
                             .'</tr>'.PHP_EOL;
                }

            }
        }

        if ($table_end == TRUE)
            $r .= '</table>'.PHP_EOL;

        $r .= Cp::quickDiv('paddingTop', Cp::input_submit(__('cp.update')));
        $r .= '</form>'.PHP_EOL;

        Cp::$title = __('admin.edit_status');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=statuses', __('admin.status_groups'))).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=status_editor'.AMP.'group_id='.$group_id, __('admin.statuses'))).
                      Cp::breadcrumbItem(__('admin.edit_status'));

        Cp::$body  = $r;
    }


    // ------------------------------------
    //  Delete status confirm
    // ------------------------------------

    function delete_status_confirm()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        if (($status_id = Request::input('status_id')) === FALSE OR ! is_numeric($status_id))
        {
            return false;
        }

        $query = DB::table('statuses')->where('status_id', $status_id)->first();

        Cp::$title = __('admin.delete_status');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=status_editor'.AMP.'group_id='.$query->group_id, __('admin.status_groups'))).
                      Cp::breadcrumbItem(__('admin.delete_status'));


        Cp::$body = Cp::deleteConfirmation(
                                        array(
                                                'url'       => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=del_status'.AMP.'status_id='.$status_id,
                                                'heading'   => 'delete_status',
                                                'message'   => 'delete_status_confirmation',
                                                'item'      => $query->status,
                                                'extra'     => '',
                                                'hidden'    => ''
                                            )
                                        );
    }


    // ------------------------------------
    //  Delete status
    // ------------------------------------

    function delete_status()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        if (($status_id = Request::input('status_id')) === FALSE OR ! is_numeric($status_id))
        {
            return false;
        }

        $query = DB::table('statuses')->where('status_id', $status_id)->first();

        if (!$query)
        {
            return $this->status_overview();
        }

        $group_id = $query->group_id;
        $status   = $query->status;

        $query = DB::table('weblogs')
            ->select('weblog_id')
            ->where('status_group', $group_id)
            ->get();

        if ($query->count() > 0)
        {
            foreach($query as $row) {
                DB::table('weblog_entries')
                    ->where('status', $status)
                    ->where('weblog_id', $row->weblog_id)
                    ->update(['status' => 'closed']);
            }
        }

        if ($status != 'open' AND $status != 'closed')
        {
            DB::table('statuses')
                ->where('status_id', $status_id)
                ->where('group_id', $group_id)
                ->delete();
        }

        $this->status_manager($group_id);
    }

    // ------------------------------------
    //  Edit status order
    // ------------------------------------

    function edit_status_order()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return false;
        }

        $query = DB::table('statuses')
            ->where('group_id', $group_id)
            ->orderBy('status_order')
            ->get();

        if ($query->count() == 0) {
            return false;
        }

        $r  = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_status_order'));
        $r .= Cp::input_hidden('group_id', $group_id);

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeading', '', '2').
              __('admin.change_status_order').
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        foreach ($query as $row)
        {
            $status_name = ($row->status == 'open' OR $row->status == 'closed') ? __($row->status) : $row->status;

            $r .= '<tr>'.PHP_EOL;
            $r .= Cp::tableCell('', $status_name);
            $r .= Cp::tableCell('', Cp::input_text('status_'.$row->status_id, $row->status_order, '4', '3', 'input', '30px'));
            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        $r .= Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.update')));

        $r .= '</form>'.PHP_EOL;

        Cp::$title = __('admin.change_status_order');


        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=statuses', __('admin.status_groups'))).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=status_editor'.AMP.'group_id='.$group_id, __('admin.statuses'))).
                      Cp::breadcrumbItem(__('admin.change_status_order'));


        Cp::$body  = $r;

    }


    // ------------------------------------
    //  Update status order
    // ------------------------------------

    function update_status_order()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $group_id = Request::input('group_id')) {
            return false;
        }

        foreach (Request::all() as $key => $val)
        {
            if (!preg_match('/^status\_([0-9]+)$/', $key, $match)) {
                continue;
            }

            DB::table('statuses')
                ->where('status_id', $match[1])
                ->update(['status_order' => $val]);
        }

        return $this->status_manager($group_id);
    }


//=====================================================================
//  CUSTOM FIELD FUNCTIONS
//=====================================================================




    // ------------------------------------
    //  Custom field overview page
    // ------------------------------------
    // This function show the "Custom weblog fields" page,
    // accessed via the "admin" tab
    //-----------------------------------------------------------

    function field_overview($message = '')
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        // Fetch field groups
        $query = DB::table('field_groups')
            ->groupBy('field_groups.group_id')
            ->orderBy('field_groups.group_name')
            ->select(
                'field_groups.group_id',
                'field_groups.group_name'
            )->get();

        if ($query->count() == 0)
        {
			$r = Cp::heading(__('admin.field_groups')).
				Cp::quickDiv('successMessage', $message).
				Cp::quickDiv('littlePadding', __('admin.no_field_group_message')).
				Cp::quickDiv('itmeWrapper',
					Cp::anchor(
						BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=field_group_editor',
						__('admin.create_new_field_group')
					 )
				);

			Cp::$title = __('admin.admin').Cp::breadcrumbItem(__('admin.field_groups'));
			Cp::$body  = $r;
			Cp::$crumb = __('admin.field_groups');

			return;

        }



        $r = '';

        if ($message != '') {
            $r .= Cp::quickDiv('successMessage', $message);
        }

        $r .= Cp::table('tableBorder', '0', '', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeading', '', '4').
              __('admin.field_group').
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $i = 0;

        foreach($query as $row)
        {
            $field_count = DB::table('weblog_fields')
                ->where('weblog_fields.group_id', $row->group_id)
                ->count();


            $r .= '<tr>'.PHP_EOL.
                  Cp::tableCell('', Cp::quickSpan('defaultBold', $row->group_name));

            $r .= Cp::tableCell('',
                  '('.$field_count.')'.'&nbsp;'.
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=field_editor'.AMP.'group_id='.$row->group_id,
                                __('admin.add_edit_fields')
                               ));

            $r .= Cp::tableCell('',
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=field_group_editor'.AMP.'group_id='.$row->group_id,
                                __('admin.edit_field_group_name')
                               ));

            $r .= Cp::tableCell('',
                  Cp::anchor(
                                BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=del_field_group_conf'.AMP.'group_id='.$row->group_id,
                                __('admin.delete_field_group')
                               ));

            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        Cp::$title  = __('admin.field_groups');
        Cp::$crumb  = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                       Cp::breadcrumbItem(__('admin.field_groups'));

        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=field_group_editor',
            __('admin.create_new_field_group')
        ];

        $r = Cp::header(__('admin.field_groups'), $right_links).$r;

        Cp::$body = $r;
    }



    // ------------------------------------
    //  New/edit field group form
    // ------------------------------------
    // This function lets you create/edit a custom field group
    //-----------------------------------------------------------

    function edit_field_group_form()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        // Set default values

        $edit       = false;
        $group_id   = '';
        $group_name = '';

        // If we have the group_id variable it's an edit request, so fetch the field data

        if ($group_id = Request::input('group_id'))
        {
            $edit = true;

            if ( ! is_numeric($group_id)) {
                return false;
            }

            $query = DB::table('field_groups')
                ->where('group_id', $group_id)
                ->select('group_id', 'group_name')
                ->first();

            foreach ($query as $key => $val) {
                $$key = $val;
            }
        }

        if ($edit == FALSE) {
            $title = __('admin.new_field_group');
        }
        else {
            $title = __('admin.edit_field_group_name');
        }

        // Build our output
        $r = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_field_group'));

        if ($edit == TRUE) {
            $r .= Cp::input_hidden('group_id', $group_id);
        }

        $r .= Cp::quickDiv('tableHeading', $title);

        $r .= Cp::div('box');
        $r .= Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.field_group_name')));
        $r .= Cp::input_text('group_name', $group_name, '20', '50', 'input', '300px');
        $r .= '<br><br>';
        $r .= '</div>'.PHP_EOL;

        $r .= Cp::div('paddingTop');

        $r .= Cp::input_submit(($edit == FALSE) ? __('cp.submit') : __('cp.update'));

        $r .= '</form>'.PHP_EOL;

        Cp::$title = $title;
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=custom_fields', __('admin.field_groups'))).
                      Cp::breadcrumbItem($title);
        Cp::$body  = $r;
    }

    // ------------------------------------
    //  Field group submission handler
    // ------------------------------------
    // This function receives the submitted group data and puts
    // it in the database
    //-----------------------------------------------------------

    function update_field_group()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        $edit = Request::has('group_id');

        // ------------------------------------
        //  Validation
        // ------------------------------------

        $validator = Validator::make(request()->all(), [
            'group_name'       => 'required|regex:#^[a-zA-Z0-9_\-/\s]+$#i',
            'group_id'         => 'numeric'
        ]);

        if ($validator->fails()) {
            return Cp::errorMessage(implode(BR, $validator->errors()->all()));
        }

        $data = Request::only(
            [
                'group_name',
                'group_id'
            ]
        );

        $query = DB::table('field_groups')
            ->where('group_name', $data['group_name']);

        if ($edit === true) {
            $query->where('group_id', '!=', $data['group_id']);
        }

        if ($query->count() > 0) {
            return Cp::errorMessage(__('admin.taken_field_group_name'));
        }

        // ------------------------------------
        //  Create!
        // ------------------------------------

        if ($edit === false)
        {
            DB::table('field_groups')->insert($data);

            $success_msg = __('admin.field_group_created');

            $crumb = Cp::breadcrumbItem(__('admin.new_field_group'));

            Cp::log(__('admin.field_group_created').'&nbsp;'.$data['group_name']);
        }

        // ------------------------------------
        //  Update!
        // ------------------------------------

        if ($edit === true) {
            DB::table('field_groups')->where('group_id', $data['group_id'])->update($data);

            $success_msg = __('admin.field_group_updated');

            $crumb = Cp::breadcrumbItem(__('cp.update'));
        }

        $message = $success_msg.' '. $data['group_name'];

        // ------------------------------------
        //  Message
        // ------------------------------------

        if ($edit === false)
        {
            $query = DB::table('weblogs')
                ->select('weblog_id')
                ->get();

            if ($query->count() > 0)
            {
                $message .= Cp::div('littlePadding').Cp::quickSpan('highlight', __('admin.assign_group_to_weblog')).'&nbsp;';

                if ($query->count() == 1)
                {
                    $link = 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=group_prefs'.AMP.'weblog_id='.$query->weblog_id;
                }
                else
                {
                    $link = 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=blog_list';
                }

                $message .= Cp::anchor(BASE.'?'.$link, __('admin.click_to_assign_group'));

                $message .= '</div>'.PHP_EOL;
            }
        }

        return $this->field_overview($message);
    }

    // ------------------------------------
    //  Delete field group confirm
    // ------------------------------------
    // Warning message if you try to delete a field group
    //-----------------------------------------------------------

    function delete_field_group_conf()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return false;
        }

        $group_name = DB::table('field_groups')
            ->where('group_id', $group_id)
            ->value('group_name');

        if ( ! $group_name) {
            return false;
        }

        Cp::$title = __('admin.delete_group');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=custom_fields', __('admin.field_groups'))).
                      Cp::breadcrumbItem(__('admin.delete_group'));

        Cp::$body = Cp::deleteConfirmation(
            [
                'url'       => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=delete_field_group'.AMP.'group_id='.$group_id,
                'heading'   => 'delete_field_group',
                'message'   => 'delete_field_group_confirmation',
                'item'      => $group_name,
                'extra'     => '',
                'hidden'    => ['group_id' => $group_id]
            ]
        );
    }

    // ------------------------------------
    //  Delete field group
    // ------------------------------------

    function delete_field_group()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
        {
            return false;
        }

        $name = DB::table('field_groups')->where('group_id', $group_id)->value('group_name');

        $query = DB::table('weblog_fields')
            ->where('group_id', $group_id)
            ->select('field_id', 'field_type', 'field_name')
            ->get();

        foreach ($query as $row)
        {
            Schema::table('weblog_entry_data', function($table) use ($row) {
                $table->dropColumn('field_'.$row->field_name);
            });
        }

        DB::table('field_groups')->where('group_id', $group_id)->delete();
        DB::table('weblog_fields')->where('group_id', $group_id)->delete();

        Cp::log(__('admin.field_group_deleted').$name);

        $message = __('admin.field_group_deleted').'<b>'.$name.'</b>';

        cms_clear_caching('all');

        return $this->field_overview($message);
    }

    // ------------------------------------
    //  Field manager
    // ------------------------------------
    // This function show a list of current fields and the
    // form that allows you to create a new field.
    //-----------------------------------------------------------

    function field_manager($group_id = '', $msg = FALSE)
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

         $message = ($msg == true) ? __('admin.preferences_updated') : '';

        if ($group_id == '')
        {
            if (($group_id = Request::input('group_id')) === FALSE OR ! is_numeric($group_id))
            {
                return false;
            }
        }
        elseif ( ! is_numeric($group_id))
        {
            return false;
        }

        // Fetch the name of the field group

        $query = DB::table('field_groups')->select('group_name')->where('group_id', $group_id)->first();

        $r  = Cp::quickDiv('tableHeading', __('admin.field_group').':'.'&nbsp;'.$query->group_name);

        if ($message != '')
        {
            $r .= Cp::quickDiv('successMessage', $message);
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeadingAlt', '40%', '1').__('admin.field_label').'</td>'.PHP_EOL.
              Cp::td('tableHeadingAlt', '20%', '1').__('admin.field_name').'</td>'.PHP_EOL.
              Cp::td('tableHeadingAlt', '40%', '2').__('admin.field_type').'</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $query = DB::table('weblog_fields')
            ->where('group_id', $group_id)
            ->orderBy('field_label')
            ->select(
                'field_id',
                'field_name',
                'field_label',
                'field_type'
            )->get();


        if ($query->count() == 0)
        {
            $r .= '<tr>'.PHP_EOL.
                  Cp::td('', '', 4).
                  '<b>'.__('admin.no_field_groups').'</br>'.
                  '</td>'.PHP_EOL.
                  '</tr>'.PHP_EOL;
        }

        $i = 0;

        if ($query->count() > 0)
        {
            foreach ($query as $row)
            {

                $r .= '<tr>'.PHP_EOL;

                $r .= Cp::tableCell(
                    '',
                    Cp::quickDiv(
                        'defaultBold',
                        Cp::anchor(
                            BASE.'?C=Administration'.
                                AMP.'M=blog_admin'.
                                AMP.'P=edit_field'.
                                AMP.'field_id='.$row->field_id,
                            $row->field_label
                        )
                    )
                );

                $r .= Cp::tableCell('', $row->field_name);

                $field_type = (__($row->field_type) === FALSE) ? '' : __($row->field_type);

                switch ($row->field_type)
                {
                    case 'text' :  $field_type = __('admin.text_input');
                        break;
                    case 'textarea' :  $field_type = __('admin.textarea');
                        break;
                    case 'select' :  $field_type = __('admin.select_list');
                        break;
                    case 'date' :  $field_type = __('admin.date_field');
                        break;
                }

                $r .= Cp::tableCell('', $field_type);
                $r .= Cp::tableCell('', Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=del_field_conf'.AMP.'field_id='.$row->field_id, __('cp.delete')));
                $r .= '</tr>'.PHP_EOL;
            }
        }

        $r .= '</table>'.PHP_EOL;

        Cp::$title = __('admin.custom_fields');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=custom_fields', __('admin.field_groups'))).
                      Cp::breadcrumbItem(__('admin.custom_fields'));

        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=edit_field'.AMP.'group_id='.$group_id,
            __('admin.create_new_custom_field')
        ];

        $r = Cp::header(__('admin.custom_fields'), $right_links).$r;

        Cp::$body  = $r;
    }

    // ------------------------------------
    //  Edit field form
    // ------------------------------------
    // This function lets you edit an existing custom field
    //-----------------------------------------------------------

    function edit_field_form()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        $field_id = Request::input('field_id');

        $type = ($field_id) ? 'edit' : 'new';

        $total_fields = '';

        if ($type == 'new')
        {
            $total_fields = 1 + DB::table('weblog_fields')->count();
        }

        $query = DB::table('weblog_fields AS f')
            ->join('field_groups AS g', 'f.group_id', '=', 'g.group_id')
            ->where('f.field_id', $field_id)
            ->select(
                'f.*',
                'g.group_name'
            )
            ->first();

        $data = [];

        $field_id           = $data['field_id'] = '';
        $field_name         = $data['field_name'] = '';
        $site_id            = $data['site_id'] = Site::config('site_id');
        $field_label        = $data['field_label'] = '';
        $field_type         = $data['field_type'] = '';
        $field_maxl         = $data['field_maxl'] = '';
        $field_ta_rows      = $data['field_ta_rows'] = '';
        $field_text_direction = $data['field_text_direction'] = '';
        $field_required     = $data['field_required']  = '';
        $group_id           = $data['group_id'] = '';
        $group_name         = $data['group_name'] = '';
        $field_instructions = $data['field_instructions'] = '';
        $field_list_items   = $data['field_list_items'] ='';
        $field_pre_populate = $data['field_pre_populate'] = '';
        $field_pre_blog_id  = $data['field_pre_blog_id'] = '';
        $field_pre_field_name = $data['field_pre_field_name'] = '';
        $field_search       = $data['field_search'] = '';
        $field_is_hidden    = $data['field_is_hidden'] ='';

        if ($query)
        {
            foreach ($query as $key => $val) {
                $data[$key] = $val;
                $$key = $val;
            }
        }

        if ($group_id == '') {
            $group_id = Request::input('group_id');
        }

        // Adjust $group_name for new custom fields
        // as we display this later

        if ($group_name == '')
        {
            $query = DB::table('field_groups')
                ->select('group_name')
                ->where('group_id', $group_id)
                ->first();

            if ($query)
            {
                $group_name = $query->group_name;
            }
        }

        // JavaScript Stuff
        $val = __('admin.field_val');

        $r = '';

        ob_start();
        ?>
        <script type="text/javascript">
        <!--

        function showhide_element(id)
        {
            if (id == 'text')
            {
                document.getElementById('text_block').style.display = "block";
                document.getElementById('textarea_block').style.display = "none";
                document.getElementById('select_block').style.display = "none";
                document.getElementById('pre_populate').style.display = "none";
                document.getElementById('date_block').style.display = "none";
                document.getElementById('rel_block').style.display = "none";
                document.getElementById('relationship_type').style.display = "none";
                document.getElementById('formatting_block').style.display = "block";
                document.getElementById('formatting_unavailable').style.display = "none";
                document.getElementById('direction_available').style.display = "block";
                document.getElementById('direction_unavailable').style.display = "none";
            }
            else if (id == 'textarea')
            {
                document.getElementById('textarea_block').style.display = "block";
                document.getElementById('text_block').style.display = "none";
                document.getElementById('select_block').style.display = "none";
                document.getElementById('pre_populate').style.display = "none";
                document.getElementById('date_block').style.display = "none";
                document.getElementById('rel_block').style.display = "none";
                document.getElementById('relationship_type').style.display = "none";
                document.getElementById('formatting_block').style.display = "block";
                document.getElementById('formatting_unavailable').style.display = "none";
                document.getElementById('direction_available').style.display = "block";
                document.getElementById('direction_unavailable').style.display = "none";
            }
            else if (id == 'select')
            {
                document.getElementById('select_block').style.display = "block";
                document.getElementById('pre_populate').style.display = "block";
                document.getElementById('text_block').style.display = "none";
                document.getElementById('textarea_block').style.display = "none";
                document.getElementById('date_block').style.display = "none";
                document.getElementById('rel_block').style.display = "none";
                document.getElementById('relationship_type').style.display = "none";
                document.getElementById('formatting_block').style.display = "block";
                document.getElementById('formatting_unavailable').style.display = "none";
                document.getElementById('direction_available').style.display = "block";
                document.getElementById('direction_unavailable').style.display = "none";
            }
            else if (id == 'date')
            {
                document.getElementById('date_block').style.display = "block";
                document.getElementById('select_block').style.display = "none";
                document.getElementById('pre_populate').style.display = "none";
                document.getElementById('text_block').style.display = "none";
                document.getElementById('textarea_block').style.display = "none";
                document.getElementById('rel_block').style.display = "none";
                document.getElementById('relationship_type').style.display = "none";
                document.getElementById('formatting_block').style.display = "none";
                document.getElementById('formatting_unavailable').style.display = "block";
                document.getElementById('direction_available').style.display = "none";
                document.getElementById('direction_unavailable').style.display = "block";
            }
        }

        function pre_populate(id)
        {
            if (id == 'n')
            {
                document.getElementById('populate_block_man').style.display = "block";
                document.getElementById('populate_block_blog').style.display = "none";
            }
            else
            {
                document.getElementById('populate_block_blog').style.display = "block";
                document.getElementById('populate_block_man').style.display = "none";
            }
        }


        function relationship_type(id)
        {
            if (id == 'blog')
            {
                document.getElementById('related_block_blog').style.display = "block";
                document.getElementById('sortorder_block').style.display = "block";
            }
            else
            {
                document.getElementById('related_block_blog').style.display = "none";
            }
        }

        function validate(id)
        {
          if (id == "")
          {
            alert("<?php echo __('admin.field_val'); ?>");
            return false;
          }
        }

        -->
        </script>
        <?php

        $js = ob_get_contents();
        ob_end_clean();

        $r .= $js;
        $r .= PHP_EOL.PHP_EOL;
        $typopts  = '';

        // Form declaration

        $r .= Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_weblog_fields', 'name' => 'field_form'));
        $r .= Cp::input_hidden('group_id', $group_id);
        $r .= Cp::input_hidden('field_id', $field_id);
        $r .= Cp::input_hidden('site_id', Site::config('site_id'));

        $title = ($type == 'edit') ? 'edit_field' : 'create_new_custom_field';

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeading', '', '2').__($title).NBS."(".__('admin.field_group').": {$group_name})".'</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $i = 0;

        // ------------------------------------
        //  Field Label
        // ------------------------------------

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', Cp::required().NBS.__('admin.field_label')).Cp::quickDiv('', __('admin.field_label_info')), '50%');
        $r .= Cp::tableCell('', Cp::input_text('field_label', $field_label, '20', '60', 'input', '260px'), '50%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Field name
        // ------------------------------------

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', Cp::required().NBS.__('admin.field_name')).Cp::quickDiv('littlePadding', __('admin.field_name_explanation')), '50%');
        $r .= Cp::tableCell('', Cp::input_text('field_name', $field_name, '20', '60', 'input', '260px'), '50%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Field Instructions
        // ------------------------------------

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.field_instructions')).Cp::quickDiv('', __('admin.field_instructions_info')), '50%', 'top');
        $r .= Cp::tableCell('', Cp::input_textarea('field_instructions', $field_instructions, '6', 'textarea', '99%'), '50%', 'top');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Field type
        // ------------------------------------

        $sel_1 = ''; $sel_2 = ''; $sel_3 = ''; $sel_4 = ''; $sel_5 = '';
        $text_js = ($type == 'edit') ? 'none' : 'block';
        $textarea_js = 'none';
        $select_js = 'none';
        $select_opt_js = 'none';
        $date_js = 'none';
        $rel_js = 'none';
        $rel_type_js = 'none';

        switch ($field_type)
        {
            case 'text'     : $sel_1 = 1; $text_js = 'block';
                break;
            case 'textarea' : $sel_2 = 1; $textarea_js = 'block';
                break;
            case 'select'   : $sel_3 = 1; $select_js = 'block'; $select_opt_js = 'block';
                break;
            case 'date'     : $sel_4 = 1; $date_js = 'block';
                break;
        }

        // ------------------------------------
        //  Create the pull-down menu
        // ------------------------------------

        $typemenu = "<select name='field_type' class='select' onchange='showhide_element(this.options[this.selectedIndex].value);' >".PHP_EOL;
        $typemenu .= Cp::input_select_option('text',      __('admin.text_input'),  $sel_1)
                    .Cp::input_select_option('textarea',  __('admin.textarea'),    $sel_2)
                    .Cp::input_select_option('select',    __('admin.select_list'), $sel_3)
                    .Cp::input_select_option('date',      __('admin.date_field'),  $sel_4);
        $typemenu .= Cp::input_select_footer();

        // ------------------------------------
        //  Create the "populate" radio buttons
        // ------------------------------------

        if ($field_pre_populate == '')
            $field_pre_populate = 'n';

        $typemenu .= '<div id="pre_populate" style="display: '.$select_opt_js.'; padding:0; margin:5px 0 0 0;">';
        $typemenu .= Cp::quickDiv('default',Cp::input_radio('field_pre_populate', 'n', ($field_pre_populate == 'n') ? 1 : 0, " onclick=\"pre_populate('n');\"").' '.__('admin.field_populate_manually'));
        $typemenu .= Cp::quickDiv('default',Cp::input_radio('field_pre_populate', 'y', ($field_pre_populate == 'y') ? 1 : 0, " onclick=\"pre_populate('y');\"").' '.__('admin.field_populate_from_blog'));
        $typemenu .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Select List Field
        // ------------------------------------

        $typopts .= '<div id="select_block" style="display: '.$select_js.'; padding:0; margin:5px 0 0 0;">';

        // ------------------------------------
        //  Populate Manually
        // ------------------------------------

        $man_populate_js = ($field_pre_populate == 'n') ? 'block' : 'none';
        $typopts .= '<div id="populate_block_man" style="display: '.$man_populate_js.'; padding:0; margin:5px 0 0 0;">';
        $typopts .= Cp::quickDiv('defaultBold', __('admin.field_list_items')).Cp::quickDiv('default', __('admin.field_list_instructions')).Cp::input_textarea('field_list_items', $field_list_items, 10, 'textarea', '400px');
        $typopts .= '</div>'.PHP_EOL;


        // ------------------------------------
        //  Populate via an existing field
        // ------------------------------------

        $blog_populate_js = ($field_pre_populate == 'y') ? 'block' : 'none';
        $typopts .= '<div id="populate_block_blog" style="display: '.$blog_populate_js.'; padding:0; margin:5px 0 0 0;">';

        $query = DB::table('weblogs')
            ->orderBy('blog_title', 'asc')
            ->select('weblog_id', 'blog_title', 'field_group')
            ->get();

        // Create the drop-down menu
        $typopts .= Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.select_weblog_for_field')));
        $typopts .= "<select name='field_pre_populate_id' class='select' onchange='validate(this.options[this.selectedIndex].value);' >".PHP_EOL;

        foreach ($query as $row)
        {
            // Fetch the field names
            $rez = DB::table('weblog_fields')
                ->where('group_id', $row->field_group)
                ->orderBy('field_label', 'asc')
                ->select('field_id', 'field_name', 'field_label')
                ->get();

            $typopts .= Cp::input_select_option('', $row->blog_title);

            foreach ($rez as $frow)
            {
                $sel = ($field_pre_blog_id == $row->weblog_id AND $field_pre_field_name == $frow->field_name) ? 1 : 0;

                $typopts .= Cp::input_select_option(
                    $row->weblog_id.'_'.$frow->field_id,
                    NBS.'-'.NBS.$frow->field_label,
                    $sel);
            }
        }
        $typopts .= Cp::input_select_footer();
        $typopts .= '</div>'.PHP_EOL;

        $typopts .= '</div>'.PHP_EOL;


        // ------------------------------------
        //  Date type
        // ------------------------------------

        $typopts .= '<div id="date_block" style="display: '.$date_js.'; padding:0; margin:0;">';
        $typopts .= NBS;
        $typopts .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Max-length Field
        // ------------------------------------

        if ($type != 'edit') {
            $field_maxl = 128;
        }

        $z  = '<div id="text_block" style="display: '.$text_js.'; padding:0; margin:5px 0 0 0;">';
        $z .= Cp::quickDiv('littlePadding', NBS.Cp::input_text('field_maxl', $field_maxl, '4', '3', 'input', '30px').NBS.__('admin.field_max_length'));
        $z .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Textarea Row Field
        // ------------------------------------

        if ($type != 'edit')
            $field_ta_rows = 6;

        $z .= '<div id="textarea_block" style="display: '.$textarea_js.'; padding:0; margin:5px 0 0 0;">';
        $z .= Cp::quickDiv('littlePadding', NBS.Cp::input_text('field_ta_rows', $field_ta_rows, '4', '3', 'input', '30px').NBS.__('admin.textarea_rows'));
        $z .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Generate the above items
        // ------------------------------------


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickDiv('littlePadding', Cp::quickSpan('defaultBold', __('admin.field_type'))).$typemenu.$z, '50%', 'top');
        $r .= Cp::tableCell('', $typopts, '50%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Text Direction
        // ------------------------------------

        if ($field_text_direction == '') {
            $field_text_direction = 'ltr';
        }

        $direction_available   = (in_array($field_type, ['text', 'textarea', 'select', ''])) ? 'block' : 'none';
        $direction_unavailable = (in_array($field_type, ['text', 'textarea', 'select', ''])) ? 'none' : 'block';

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.text_direction')), '50%');
        $r .= Cp::tableCell('',
                                        '<div id="direction_available" style="display: '.$direction_available.'; padding:0; margin:0 0 0 0;">'.
                                        __('admin.ltr').'&nbsp;'.
                                        Cp::input_radio('field_text_direction', 'ltr', ($field_text_direction == 'ltr') ? 1 : '').
                                        '&nbsp;'.
                                        __('admin.rtl').'&nbsp;'.
                                        Cp::input_radio('field_text_direction', 'rtl', ($field_text_direction == 'rtl') ? 1 : '').
                                        '</div>'.PHP_EOL.

                                        '<div id="direction_unavailable" style="display: '.$direction_unavailable.'; padding:0; margin:0 0 0 0;">'.
                                        Cp::quickDiv('highlight', __('admin.direction_unavailable')).
                                        '</div>'.PHP_EOL,
                                        '50%');
        $r .= '</tr>'.PHP_EOL;


        // ------------------------------------
        //  Is field required?
        // ------------------------------------

        if ($field_required == '') $field_required = 'n';


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.is_field_required')), '50%');
        $r .= Cp::tableCell('', __('admin.yes').'&nbsp;'.Cp::input_radio('field_required', 'y', ($field_required == 'y') ? 1 : '').'&nbsp;'.__('admin.no').'&nbsp;'.Cp::input_radio('field_required', 'n', ($field_required == 'n') ? 1 : ''), '50%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Is field searchable?
        // ------------------------------------
        if ($field_search == '') $field_search = 'n';


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.is_field_searchable')), '50%');
        $r .= Cp::tableCell('', __('admin.yes').'&nbsp;'.Cp::input_radio('field_search', 'y', ($field_search == 'y') ? 1 : '').'&nbsp;'.__('admin.no').'&nbsp;'.Cp::input_radio('field_search', 'n', ($field_search == 'n') ? 1 : ''), '50%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Is field hidden?
        // ------------------------------------

        if ($field_is_hidden == '')
            $field_is_hidden = 'n';


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickDiv('defaultBold', __('admin.field_is_hidden')).Cp::quickDiv('littlePadding', __('admin.hidden_field_blurb')), '50%');
        $r .= Cp::tableCell('', __('admin.yes').'&nbsp;'.Cp::input_radio('field_is_hidden', 'n', ($field_is_hidden == 'n') ? 1 : '').'&nbsp;'.__('admin.no').'&nbsp;'.Cp::input_radio('field_is_hidden', 'y', ($field_is_hidden == 'y') ? 1 : ''), '50%');
        $r .= '</tr>'.PHP_EOL;


        // ------------------------------------
        //  Field order
        // ------------------------------------

        $r .= '</table>'.PHP_EOL;

        $r .= Cp::div('littlePadding');
        $r .= Cp::quickDiv('littlePadding', Cp::required(1));

        if ($type == 'edit') {
            $r .= Cp::input_submit(__('cp.update'));
        }
        else {
            $r .= Cp::input_submit(__('cp.submit'));
        }

        $r .= '</div>'.PHP_EOL;


        $r .= '</form>'.PHP_EOL;

        Cp::$title = __('admin.custom_fields');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=custom_fields', __('admin.field_groups'))).
                      Cp::breadcrumbItem(__('admin.custom_fields'));
        Cp::$body  = $r;

    }

    // ------------------------------------
    //  Create/update custom fields
    // ------------------------------------

    function update_weblog_fields()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        $edit = Request::has('field_id');

        // ------------------------------------
        //  Validation
        // ------------------------------------

        $validator = Validator::make(request()->all(), [
            'field_name'       => 'regex:/^[\pL\pM\pN_]+$/u',
            'field_label'      => 'required|not_in:'.implode(',',Cp::unavailableFieldNames()),
            'group_id'         => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return Cp::errorMessage(implode(BR, $validator->errors()->all()));
        }

        $data = Request::only(
            [
                'field_label',
                'field_name',
                'field_instructions',
                'field_type',
                'field_pre_populate',
                'field_maxl',
                'field_ta_rows',
                'field_list_items',
                'field_pre_populate_id',
                'field_text_direction',
                'field_required',
                'field_search',
                'field_is_hidden',
                'group_id',
                'field_id'
            ]
        );

        $stringable = [
            'field_instructions',
            'field_list_items'
        ];

        foreach($stringable as $field) {
            if(empty($data[$field])) {
                $data[$field] = '';
            }
        }

        // Let DB defaults handle these if empty
        $unsettable = [
            'field_pre_populate',
            'field_maxl',
            'field_ta_rows',
            'field_pre_populate_id',
            'field_text_direction',
            'field_required',
            'field_is_hidden',
        ];

        foreach($unsettable as $field) {
            if(empty($data[$field])) {
                unset($data[$field]);
            }
        }


        $group_id = Request::input('group_id');

        // ------------------------------------
        //  Field Name Taken?
        // ------------------------------------

        $query = DB::table('weblog_fields')
            ->where('field_name', $_POST['field_name']);

        if ($edit === true) {
            $query->where('group_id', '!=', $group_id);
        }

        if ($query->count() > 0) {
            return Cp::errorMessage(__('admin.duplicate_field_name'));
        }

        // ------------------------------------
        //  Data ch-ch-changes
        // ------------------------------------

        if (!empty($data['field_list_items'])) {
            $data['field_list_items'] = Regex::convert_quotes($data['field_list_items']);
        }

        if ($data['field_pre_populate'] == 'y') {
            $x = explode('_', $data['field_pre_populate_id']);

            $_POST['field_pre_blog_id']    = $x[0];
            $_POST['field_pre_field_name'] = $x[1];
        }

        unset($data['field_pre_populate_id']);

        if ( ! in_array($data['field_type'], ['text', 'textarea', 'select'])) {
            unset($data['field_text_direction']);
        }

        // ------------------------------------
        //  Updating!
        // ------------------------------------

        if ($edit === true)
        {
            if ( ! is_numeric($data['field_id'])) {
                return false;
            }

            unset($data['group_id']);

            $query = DB::table('weblog_fields')
                ->select('field_type', 'field_name')
                ->where('field_id', $data['field_id'])
                ->first();

            if ($query->field_type != $data['field_type'] && $data['field_type'] == 'date') {
                return Cp::errorMessage(__('admin.unable_to_change_to_date_field_type'));
            }

            // Ch-ch-ch-changing
            if ($query->field_type != $data['field_type'])
            {
                switch($data['field_type'])
                {
                    case 'date' :
                        Schema::table('weblog_entry_data', function($table) use ($query)
                        {
                            $table->timestamp('field_'.$query->field_name)->nullable(true)->change();
                        });
                    break;
                    default     :
                        Schema::table('weblog_entry_data', function($table) use ($query)
                        {
                            $table->text('field_'.$query->field_name)->nullable(true)->change();
                        });
                    break;
                }
            }

            DB::table('weblog_fields')
                ->where('field_id', $data['field_id'])
                ->where('group_id', $group_id)
                ->update($data);
        }

        // ------------------------------------
        //  Creation
        // ------------------------------------

        if ($edit !== true)
        {
            unset($data['field_id']);

            $insert_id = DB::table('weblog_fields')->insertGetId($data);

            if ($data['field_type'] == 'date')
            {
                Schema::table('weblog_entry_data', function($table) use ($data)
                {
                    $table->timestamp('field_'.$data['field_name'])->nullable(true);
                });
            }
            else
            {
                Schema::table('weblog_entry_data', function($table) use ($data)
                {
                    $table->text('field_'.$data['field_name'])->nullable(true);
                });
            }
       }

        cms_clear_caching('all');

        return $this->field_manager($group_id, $edit);
    }

    // ------------------------------------
    //  Delete field confirm
    // ------------------------------------
    // Warning message if you try to delete a custom field
    //-----------------------------------------------------------

    function delete_field_conf()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $field_id = Request::input('field_id')) {
            return false;
        }

        $query = DB::table('weblog_fields')
            ->select('field_label')
            ->where('field_id', $field_id)
            ->first();

        Cp::$title = __('admin.delete_field');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=custom_fields', __('admin.field_groups'))).
                      Cp::breadcrumbItem(__('admin.delete_field'));

        Cp::$body = Cp::deleteConfirmation(
                                        array(
                                                'url'       => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=delete_field'.AMP.'field_id='.$field_id,
                                                'heading'   => 'delete_field',
                                                'message'   => 'delete_field_confirmation',
                                                'item'      => $query->field_label,
                                                'extra'     => '',
                                                'hidden'    => array('field_id' => $field_id)
                                            )
                                        );
    }



    // ------------------------------------
    //  Delete field
    // ------------------------------------
    // This function alters the "weblog_data" table, dropping
    // the fields
    //-----------------------------------------------------------

    function delete_field()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        if ( ! $field_id = Request::input('field_id'))
        {
            return false;
        }

        if ( ! is_numeric($field_id))
        {
            return false;
        }

        $query = DB::table('weblog_fields')
            ->where('field_id', $field_id)
            ->select('group_id', 'field_type', 'field_label', 'field_name')
            ->first();

        $group_id = $query->group_id;
        $field_label = $query->field_label;
        $field_type = $query->field_type;
        $field_name = $query->field_name;

        Schema::table('weblog_entry_data', function($table) use ($field_name)
        {
            if (!Schema::hasColumn('weblog_entry_data', 'field_'.$field_name)) {
                return;
            }

            $table->dropColumn('field_'.$field_name);
        });

        DB::table('weblog_fields')
            ->where('field_id', $field_id)
            ->delete();

        Cp::log(__('admin.field_deleted').'&nbsp;'.$field_label);

        cms_clear_caching('all');

        return $this->field_manager($group_id);
    }

    // ------------------------------------
    //  File Upload Preferences Page
    // ------------------------------------

    function file_upload_preferences($update = '')
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=edit_upload_pref',
            __('admin.create_new_upload_pref')
        ];

        $r = Cp::header(__('admin.file_upload_preferences'), $right_links);

        if ($update != '')
        {
            $r .= Cp::quickDiv('successMessage', __('admin.preferences_updated'));
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeading', '', '3').
              __('admin.current_upload_prefs').
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $query = DB::table('upload_prefs')
            ->orderBy('name')
            ->get();

        if ($query->count() == 0)
        {
            $r .= '<tr>'.PHP_EOL.
                  Cp::td('', '', '3').
                  '<b>'.__('admin.no_upload_prefs').'</b>'.
                  '</td>'.PHP_EOL.
                  '</tr>'.PHP_EOL;
        }

        $i = 0;

        if ($query->count() > 0)
        {
            foreach ($query as $row)
            {
                $r .= '<tr>'.PHP_EOL;
                $r .= Cp::tableCell('', '&nbsp;'.Cp::quickSpan('defaultBold', $row->name), '40%');
                $r .= Cp::tableCell('', Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=edit_upload_pref'.AMP.'id='.$row->id, __('cp.edit')), '30%');
                $r .= Cp::tableCell('', Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=del_upload_pref_conf'.AMP.'id='.$row->id, __('cp.delete')), '30%');
                $r .= '</tr>'.PHP_EOL;
            }
        }

        $r .= '</table>'.PHP_EOL;

        Cp::$title  = __('admin.file_upload_preferences');
        Cp::$crumb  = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                       Cp::breadcrumbItem(__('admin.file_upload_preferences'));

        Cp::$body   = $r;
    }

    // ------------------------------------
    //  New/Edit Upload Preferences form
    // ------------------------------------

    function edit_upload_preferences_form()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        $id = Request::input('id');

        $type = ($id !== FALSE) ? 'edit' : 'new';

        $query = DB::table('upload_prefs')
            ->where('id', $id)
            ->first();

        if (!$query) {
            if ($id != '') {
                return Cp::unauthorizedAccess();
            }

            $site_id = Site::config('site_id');
            $name = '';
            $server_path = '';
            $url = '';
            $allowed_types = 'img';
            $max_size = '';
            $max_width = '';
            $max_height = '';
            $properties = '';
            $pre_format = '';
            $post_format = '';
            $file_properties = '';
            $file_pre_format = '';
            $file_post_format = '';
        } else {
            foreach ($query as $key => $val)
            {
                $$key = $val;
            }
        }

        // Form declaration
        $r  = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=update_upload_prefs'));
        $r .= Cp::input_hidden('id', $id);
        $r .= Cp::input_hidden('cur_name', $name);

        $r .= Cp::table('tableBorder', '0', '', '100%').
              Cp::td('tableHeading', '', '2');

        if ($type == 'edit')
            $r .= __('admin.edit_file_upload_preferences');
        else
            $r .= __('admin.new_file_upload_preferences');

            $r .= '</td>'.PHP_EOL.
                  '</tr>'.PHP_EOL;

        $i = 0;


        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', Cp::required().NBS.__('admin.upload_pref_name')),
                                    Cp::input_text('name', $name, '50', '50', 'input', '100%')
                                      )
                                );

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', Cp::required().NBS.__('admin.server_path')),
                                    Cp::input_text('server_path', $server_path, '50', '100', 'input', '100%')
                                      )
                                );

        if ($url == '') {
            $url = 'https://';
        }

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', Cp::required().NBS.__('admin.url_to_upload_dir')),
                                    Cp::input_text('url', $url, '50', '100', 'input', '100%')
                                      )
                                );


        if ($allowed_types == '')
            $allowed_types = 'img';

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', Cp::required().NBS.__('admin.allowed_types')),
                                    Cp::input_radio('allowed_types', 'img', ($allowed_types == 'img') ? 1 : '').NBS.__('admin.images_only')
                                    .NBS.Cp::input_radio('allowed_types', 'all', ($allowed_types == 'all') ? 1 : '').NBS.__('admin.all_filetypes')
                                      )
                                );

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', __('admin.max_size')),
                                    Cp::input_text('max_size', $max_size, '15', '16', 'input', '90px')
                                      )
                                );

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', __('admin.max_height')),
                                    Cp::input_text('max_height', $max_height, '10', '6', 'input', '60px')
                                      )
                                );

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', __('admin.max_width')),
                                    Cp::input_text('max_width', $max_width, '10', '6', 'input', '60px')
                                      )
                                );

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', __('admin.properties')),
                                    Cp::input_text('properties', $properties, '50', '120', 'input', '100%')
                                      )
                                );

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', __('admin.pre_format')),
                                    Cp::input_text('pre_format', $pre_format, '50', '120', 'input', '100%')
                                      )
                                );

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', __('admin.post_format')),
                                    Cp::input_text('post_format', $post_format, '50', '120', 'input', '100%')
                                      )
                                );


        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', __('admin.file_properties')),
                                    Cp::input_text('file_properties', $file_properties, '50', '120', 'input', '100%')
                                      )
                                );

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', __('admin.file_pre_format')),
                                    Cp::input_text('file_pre_format', $file_pre_format, '50', '120', 'input', '100%')
                                      )
                                );

        $r .= Cp::tableQuickRow('',
                                array(

                                    Cp::quickSpan('defaultBold', __('admin.file_post_format')),
                                    Cp::input_text('file_post_format', $file_post_format, '50', '120', 'input', '100%')
                                      )
                                );

        $r .= '</table>'.PHP_EOL;

        $r .= Cp::quickDiv('paddingTop', Cp::heading(__('admin.restrict_to_group'), 5).__('admin.restrict_notes_1').Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('admin.restrict_notes_2'))));

        $query = DB::table('member_groups')
            ->whereNotIn('group_id',  [1,2,3,4])
            ->select('group_id', 'group_name')
            ->orderBy('group_name')
            ->get();

        if ($query->count() > 0)
        {
            $r .= Cp::table('tableBorder', '0', '', '100%').
                  '<tr>'.PHP_EOL.
                  Cp::td('tableHeading', '', '').
                  __('admin.member_group').
                  '</td>'.PHP_EOL.
                  Cp::td('tableHeading', '', '').
                  __('admin.can_upload_files').
                  '</td>'.PHP_EOL.
                  '</tr>'.PHP_EOL;

            $i = 0;

            $group = [];

            $result = DB::table('upload_no_access');

            if ($id != '') {
                $result->where('upload_id', $id);
            }

            $result = $result->get();

            if ($result->count() != 0)
            {
                foreach($result as $row)
                {
                    $group[$row->member_group] = true;
                }
            }

            foreach ($query as $row)
            {

                    $r .= '<tr>'.PHP_EOL.
                          Cp::td('', '50%').
                          $row->group_name.
                          '</td>'.PHP_EOL.
                          Cp::td('', '50%');

                    $selected = ( ! isset($group[$row->group_id])) ? 1 : '';

                    $r .= Cp::qlabel(__('admin.yes')).NBS.
                          Cp::input_radio('access_'.$row->group_id, 'y', $selected).'&nbsp;';

                    $selected = (isset($group[$row->group_id])) ? 1 : '';

                    $r .= Cp::qlabel(__('admin.no')).NBS.
                          Cp::input_radio('access_'.$row->group_id, 'n', $selected).'&nbsp;';

                    $r .= '</td>'.PHP_EOL
                         .'</tr>'.PHP_EOL;
            }
            $r .= '</table>'.PHP_EOL;
        }

        $r .= Cp::div('littlePadding')
             .Cp::quickDiv('littlePadding', Cp::required(1));

        if ($type == 'edit') {
            $r .= Cp::input_submit(__('cp.update'));
        }
        else {
            $r .= Cp::input_submit(__('cp.submit'));
        }

        $r .= '</div>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        $lang_line = ($type == 'edit') ? 'edit_file_upload_preferences' : 'create_new_upload_pref';

        Cp::$title = __($lang_line);
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=upload_prefs', __('admin.file_upload_prefs'))).
                      Cp::breadcrumbItem(__($lang_line));
        Cp::$body  = $r;
    }




    // ------------------------------------
    //  Update upload preferences
    // ------------------------------------

    function update_upload_preferences()
    {
        if ( ! Session::access('can_admin_weblogs'))
        {
            return Cp::unauthorizedAccess();
        }

        // If the $id variable is present we are editing an
        // existing field, otherwise we are creating a new one

        $edit = (isset($_POST['id']) AND $_POST['id'] != '' && is_numeric($_POST['id'])) ? TRUE : false;

        // Check for required fields

        $error = [];

        if ($_POST['name'] == '')
        {
            $error[] = __('admin.no_upload_dir_name');
        }

        if ($_POST['server_path'] == '')
        {
            $error[] = __('admin.no_upload_dir_path');
        }

        if ($_POST['url'] == '' OR $_POST['url'] == 'http://')
        {
            $error[] = __('admin.no_upload_dir_url');
        }

        if (substr($_POST['server_path'], -1) != '/' AND substr($_POST['server_path'], -1) != '\\')
        {
            $_POST['server_path'] .= '/';
        }

        $_POST['url'] = rtrim($_POST['url'], '/').'/';

        // Is the name taken?
        $count = DB::table('upload_prefs')
            ->where('name', $_POST['name'])
            ->count();

        if (($edit == FALSE || ($edit == TRUE && strtolower($_POST['name']) != strtolower($_POST['cur_name']))) && $count > 0)
        {
            $error[] = __('admin.duplicate_dir_name');
        }

        // Are there errors to display?
        if (count($error) > 0) {
            $str = '';

            foreach ($error as $msg) {
                $str .= $msg.BR;
            }

            return Cp::errorMessage($str);
        }

        $id = Request::input('id');

        unset($_POST['id']);
        unset($_POST['cur_name']);

        $data = [];
        $no_access = [];

        DB::table('upload_no_access')->where('upload_id', $id)->delete();

        foreach ($_POST as $key => $val)
        {
            if (substr($key, 0, 7) == 'access_')
            {
                if ($val == 'n')
                {
                    $no_access[] = substr($key, 7);
                }
            }
            else
            {
                $data[$key] = $val;
            }
        }

        // Construct the query based on whether we are updating or inserting

        if ($edit === TRUE)
        {
            DB::table('upload_prefs')->where('id', $id)->update($data);
        }
        else
        {
            $data['site_id'] = Site::config('site_id');

            $id = DB::table('upload_prefs')->insertGetId($data);
        }

        if (sizeof($no_access) > 0)
        {
            foreach($no_access as $member_group)
            {
                DB::table('upload_no_access')
                    ->insert(
                    [
                        'upload_id' => $id,
                        'upload_loc' => 'cp',
                        'member_group' => $member_group
                    ]);
            }
        }

        return $this->file_upload_preferences(1);
    }

    // ------------------------------------
    //  Upload preferences delete confirm
    // ------------------------------------

    function delete_upload_preferences_conf()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $id = Request::input('id')) {
            return false;
        }

        if ( ! is_numeric($id)) {
            return false;
        }

        $query = DB::table('upload_prefs')->select('name')->where('id', $id)->first();

        Cp::$title = __('admin.delete_upload_preference');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=blog_admin'.AMP.'P=upload_prefs', __('admin.file_upload_prefs'))).
                      Cp::breadcrumbItem(__('admin.delete_upload_preference'));

        Cp::$body = Cp::deleteConfirmation(
                                        array(
                                                'url'       => 'C=Administration'.AMP.'M=blog_admin'.AMP.'P=del_upload_pref'.AMP.'id='.$id,
                                                'heading'   => 'delete_upload_preference',
                                                'message'   => 'delete_upload_pref_confirmation',
                                                'item'      => $query->name,
                                                'extra'     => '',
                                                'hidden'    => array('id', $id)
                                            )
                                        );
    }



    // ------------------------------------
    //  Delete upload preferences
    // ------------------------------------

    function delete_upload_preferences()
    {
        if ( ! Session::access('can_admin_weblogs')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! $id = Request::input('id')) {
            return false;
        }

        if ( ! is_numeric($id)) {
            return false;
        }

        DB::table('upload_no_access')->where('upload_id', $id)->delete();

        $name = DB::table('upload_prefs')->where('id', $id)->value('name');

        DB::table('upload_prefs')->where('id', $id)->delete();

        Cp::log(__('admin.upload_pref_deleted').'&nbsp;'.$name);

        return $this->file_upload_preferences();
    }
}
