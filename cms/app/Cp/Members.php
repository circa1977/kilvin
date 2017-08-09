<?php

namespace Kilvin\Cp;

use Cp;
use DB;
use Site;
use Hash;
use Stats;
use Cache;
use Schema;
use Request;
use Plugins;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Kilvin\Core\Regex;
use Kilvin\Core\Session;
use Kilvin\Core\Localize;
use Kilvin\Core\ValidateAccount;

class Members
{
    // Default member groups.
    public $default_groups = ['Guests', 'Banned', 'Members', 'Pending', 'Super Admins'];

    public $perpage = 50;  // Number of results on the "View all member" page

    private $no_delete = ['1', '2', '3', '4']; // Member groups that can not be deleted

    public static $group_preferences = [

            'cp_site_cp_access_privs'       => null,  // Site specific

            'cp_site_offline_privs'         =>  null, // Site specific

            'mbr_account_privs' => [
                'include_in_authorlist'     => 'n',
                'can_delete_self'           => 'n',
                'mbr_delete_notify_emails'  => '',
            ],

            'search_privs' => [
                'can_search'                => 'n',
                'search_flood_control'      => '30'
            ],

            'cp_section_access' => [
                'can_access_publish'        => 'n',
                'can_access_edit'           => 'n',
                'can_access_design'         => 'n',
                'can_access_plugins'        => 'n',
                'can_access_admin'          => 'n'
            ],

            'cp_admin_privs' => [
                'can_admin_weblogs'         => 'n',
                'can_edit_categories'       => 'n',
                'can_admin_templates'       => 'n',
                'can_admin_members'         => 'n',
                'can_admin_mbr_groups'      => 'n',
                'can_delete_members'        => 'n',
                'can_ban_users'             => 'n',
                'can_admin_utilities'       => 'n',
                'can_admin_preferences'     => 'n',
                'can_admin_plugins'         => 'n'
            ],

            'cp_weblog_privs' =>
            [
                'can_view_other_entries'   => 'n',
                'can_delete_self_entries'  => 'n',
                'can_edit_other_entries'   => 'n',
                'can_delete_all_entries'   => 'n',
                'can_assign_post_authors'  => 'n',
            ],

            'cp_weblog_post_privs' =>  null,

            'cp_plugin_access_privs'   =>  null,
        ];


    // ------------------------------------
    //  Constructor
    // ------------------------------------

    function __construct()
    {
    }

    // ------------------------------------
    //  View all members
    // ------------------------------------

    function view_all_members($message = '')
    {
        // These variables are only set when one of the pull-down menus is used
        // We use it to construct the SQL query with
        $group_id   = Request::input('group_id');
        $order      = Request::input('order');

        $total_members = DB::table('members')->count();

        // Begin building the page output
        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=member_search',
            __('members.new_member_search')
        ];

        $r  = Cp::header(__('admin.view_members'), $right_links);

        if ($message != '') {
            $r .= Cp::quickDiv('successMessage', $message);
        }

        // Declare the "filtering" form
        $r .= Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=view_members'));

        // Table start
        $r .= Cp::div('box');
        $r .= Cp::table('', '0', '', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('littlePadding', '', '5').PHP_EOL;

        // Member group selection pull-down menu
        $r .= Cp::input_select_header('group_id').
              Cp::input_select_option('', __('admin.member_groups')).
              Cp::input_select_option('', __('cp.all'));

        // Fetch the names of all member groups and write each one in an <option> field
        $query = DB::table('member_groups')
            ->select('group_name', 'group_id')
            ->orderBy('group_name')
            ->get();

        foreach ($query as $row)
        {
            $r .= Cp::input_select_option($row->group_id, $row->group_name, ($group_id == $row->group_id) ? 1 : '');
        }

        $r .= Cp::input_select_footer().
              '&nbsp;';

        // "display order" pull-down menu
        $sel_1  = ($order == 'desc')              ? 1 : '';
        $sel_2  = ($order == 'asc')               ? 1 : '';
        $sel_5  = ($order == 'screen_name')       ? 1 : '';
        $sel_6  = ($order == 'screen_name_desc')  ? 1 : '';
        $sel_7  = ($order == 'email')             ? 1 : '';
        $sel_8  = ($order == 'email_desc')        ? 1 : '';

        $r .= Cp::input_select_header('order').
              Cp::input_select_option('desc',  __('admin.sort_order'), $sel_1).
              Cp::input_select_option('asc',   __('publish.ascending'), $sel_2).
              Cp::input_select_option('desc',  __('publish.descending'), $sel_1).
              Cp::input_select_option('screen_name_asc', __('members.screen_name_asc'), $sel_5).
              Cp::input_select_option('screen_name_desc', __('members.screen_name_desc'), $sel_6).
              Cp::input_select_option('email_asc', __('members.email_asc'), $sel_7).
              Cp::input_select_option('email_desc', __('members.email_desc'), $sel_8).
              Cp::input_select_footer().
              '&nbsp;';


        // Submit button and close filtering form

        $r .= Cp::input_submit(__('cp.submit'), 'submit');

        $r .= '</td>'.PHP_EOL.
              Cp::td('defaultRight', '', 2).
              Cp::heading(__('members.total_members').NBS.$total_members.NBS, 5).
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL;

        $r .= '</div>'.PHP_EOL;



        $r .= '</form>'.PHP_EOL;

        // Build the SQL query as well as the query string for the paginate links

        $pageurl = BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=view_members';

        if ($group_id)
        {
            $total_count = DB::table('members')
                ->where('group_id', $group_id)
                ->count();
        }
        else
        {
            $total_count = $total_members;
        }

        // No result?  Show the "no results" message
        if ($total_count == 0)
        {
            $r .= Cp::quickDiv('', __('members.no_members_matching_that_criteria'));

			Cp::$title = __('admin.view_members');
			Cp::$body  = $r;
			Cp::$crumb = __('admin.view_members');

			return;
        }

        // Get the current row number and add the LIMIT clause to the SQL query

        if ( ! $rownum = Request::input('rownum'))
        {
            $rownum = 0;
        }

        $base_query = DB::table('members')
            ->join('member_groups', 'members.group_id', '=', 'member_groups.group_id')
            ->offset($rownum)
            ->limit($this->perpage)
            ->select(
                'members.member_id',
                'members.screen_name',
                'members.email',
                'members.join_date',
                'members.last_activity',
                'member_groups.group_name',
                'members.member_id');

        if ($group_id) {
            $base_query->where('members.group_id', $group_id);

            $pageurl .= AMP.'group_id='.$group_id;
        }

        if ($order)
        {
            $pageurl .= AMP.'order='.$order;

            switch ($order)
            {
                case 'asc'              : $base_query->orderBy('join_date', 'sac');
                    break;
                case 'desc'             : $base_query->orderBy('join_date', 'desc');
                    break;
                case 'screen_name_asc'  : $base_query->orderBy('screen_name', 'asc');
                    break;
                case 'screen_name_desc' : $base_query->orderBy('screen_name', 'desc');
                    break;
                case 'email_asc'        : $base_query->orderBy('email', 'asc');
                    break;
                case 'email_desc'       : $base_query->orderBy('email', 'desc');
                    break;
                default                 : $base_query->orderBy('join_date', 'desc');
            }
        }
        else
        {
            $base_query->orderBy('join_date', 'desc');
        }

        $query = $base_query->get();

        // "select all" checkbox

        $r .= Cp::toggle();

        Cp::$body_props .= ' onload="magic_check()" ';

        $r .= Cp::magicCheckboxesJavascript();

        // Declare the "delete" form

        $r .= Cp::formOpen(
                                array(
                                        'action'    => 'C=Administration'.AMP.'M=members'.AMP.'P=mbr_conf',
                                        'name'      => 'target',
                                        'id'        => 'target'

                                    )
                            );

        // Build the table heading
        $r .= Cp::table('tableBorder', '0', '', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableCell('tableHeadingAlt', __('account.screen_name')).
              Cp::tableCell('tableHeadingAlt', __('account.email')).
              Cp::tableCell('tableHeadingAlt', __('account.join_date')).
              Cp::tableCell('tableHeadingAlt', __('account.last_activity')).
              Cp::tableCell('tableHeadingAlt', __('admin.member_group')).
              Cp::tableCell('tableHeadingAlt', Cp::input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"")).
              '</tr>'.PHP_EOL;

        // Loop through the query result and write each table row

        $i = 0;

        foreach($query as $row)
        {
            $r .= '<tr>'.PHP_EOL;

            // Screen name

            $r .= Cp::tableCell('', Cp::anchor(
                                                  BASE.'?C=account'.AMP.'id='.$row->member_id,
                                                  '<b>'.$row->screen_name.'</b>'
                                                ));


            // Email

            $r .= Cp::tableCell('',
                                    Cp::mailto($row->email, $row->email)
                                    );

            // Join date

            $r .= Cp::td('').
                  Localize::format('%Y', $row->join_date).'-'.
                  Localize::format('%m', $row->join_date).'-'.
                  Localize::format('%d', $row->join_date).
                  '</td>'.PHP_EOL;

            // Last visit date

            $r .= Cp::td('');

                if (!empty($row->last_activity))
                {
                    $r .= Localize::createHumanReadableDateTime($row->last_activity);
                }
                else
                {
                    $r .= "--";
                }

            $r .= '</td>'.PHP_EOL;

            // Member group
            $r .= Cp::td('');
            $r .= $row->group_name;
            $r .= '</td>'.PHP_EOL;

            // Delete checkbox

            $r .= Cp::tableCell('', Cp::input_checkbox('toggle[]', $row->member_id, '', ' id="delete_box_'.$row->member_id.'"'));

            $r .= '</tr>'.PHP_EOL;

        } // End foreach


        $r .= '</table>'.PHP_EOL;

        $r .= Cp::table('', '0', '', '98%');
        $r .= '<tr>'.PHP_EOL.
              Cp::td();

        // Pass the relevant data to the paginate class so it can display the "next page" links

        $r .=  Cp::div('crumblinks').
               Cp::pager(
                            $pageurl,
                            $total_count,
                            $this->perpage,
                            $rownum,
                            'rownum'
                          ).
              '</div>'.PHP_EOL.
              '</td>'.PHP_EOL.
              Cp::td('defaultRight');

        // Delete button

        $r .= Cp::input_submit(__('cp.submit'));

        $r .= NBS.Cp::input_select_header('action');

        $r .= Cp::input_select_option('delete', __('cp.delete_selected')).
              Cp::input_select_footer().
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        // Table end

        $r .= '</table>'.PHP_EOL.
              '</form>'.PHP_EOL;

        // Set output data

        Cp::$title = __('admin.view_members');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(__('admin.view_members'));
        Cp::$body  = $r;
    }

    // ------------------------------------
    //  Member Action Confirm
    // ------------------------------------

    function member_confirm()
    {
        if (isset($_POST['action']) && $_POST['action'] == 'resend')
        {
            $this->resend_activation_emails();
        }
        else
        {
            $this->member_delete_confirm();
        }
    }


    // ------------------------------------
    //  Delete Member (confirm)
    // ------------------------------------
    // Warning message if you try to delete members
    //-----------------------------------------------------------

    public function member_delete_confirm()
    {
        if ( ! Session::access('can_delete_members')) {
            return Cp::unauthorizedAccess();
        }

        $from_myaccount = false;
        $entries_exit = false;

        $data = Request::all();

        if (Request::input('mid') !== null)
        {
            $from_myaccount = true;
            $data['toggle'] = Request::input('mid');
        }

        if (empty($data['toggle'])) {
            return $this->view_all_members();
        }

        $r = Cp::formOpen(['action' => 'C=Administration'.AMP.'M=members'.AMP.'P=mbr_delete']);

        $i = 0;
        $damned = [];

        foreach ($data as $key => $val)
        {
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                $r .= Cp::input_hidden('delete[]', $val);

                // Is the user trying to delete himself?
                if (Session::userdata('member_id') == $val) {
                    return Cp::errorMessage(__('members.can_not_delete_self'));
                }

                $damned[] = $val;
                $i++;
            }
        }

        $r .= Cp::quickDiv('alertHeading', __('members.delete_member'));
        $r .= Cp::div('box');

        if ($i == 1) {
            $r .= Cp::quickDiv('littlePadding', '<b>'.__('members.delete_member_confirm').'</b>');

            $screen_name = DB::table('members')->where('member_id', $damned[0])->value('screen_name');

            $r .= Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', $screen_name));
        } else {
            $r .= '<b>'.__('members.delete_members_confirm').'</b>';
        }

        $r .= Cp::quickDiv('littlePadding', Cp::quickDiv('alert', __('members.cp.action_can_not_be_undone')));

        // ------------------------------------
        //  Do the users being deleted have entries assigned to them?
        // ------------------------------------

        $count = DB::table('weblog_entries')
            ->whereIn('author_id', $damned)
            ->count();

        if ($count > 0)
        {
            $entries_exit = true;
            $r .= Cp::input_hidden('entries_exit', 'yes');
        }

       // ------------------------------------
        //  If so, fetch the member names for reassigment
        // ------------------------------------

        if ($entries_exit == TRUE)
        {
            $group_ids = DB::table('members')
                ->whereIn('member_id', $damned)
                ->pluck('group_id')
                ->all();

            $group_ids = array_unique($group_ids);

            // Find Valid Member Replacements
            $query = DB::table('members')
                ->select('members.member_id', 'screen_name')
                ->leftJoin('member_groups', 'member_groups.group_id', '=', 'members.group_id')
                ->whereIn('member_groups.group_id', $group_ids)
                ->whereNotIn('members.member_id', $damned)
                ->where(function($q) {
                    $q->where('members.in_authorlist', 'y')->orWhere('member_groups.include_in_authorlist', 'y');
                })
                ->orderBy('screen_name', 'asc')
                ->get();

            if ($query->count() == 0)
            {
                $query = DB::table('members')
                    ->select('member_id', 'screen_name')
                    ->where('group_id', 1)
                    ->whereNotIn('member_id', $damned)
                    ->orderBy('screen_name', 'asc')
                    ->get();
            }

            $r .= Cp::div('littlePadding');
            $r .= Cp::div('defaultBold');
            $r .= ($i == 1) ? __('members.heir_to_member_entries') : __('members.heir_to_members_entries');
            $r .= '</div>'.PHP_EOL;

            $r .= Cp::div('littlePadding');
            $r .= Cp::input_select_header('heir');

            foreach($query as $row)
            {
                $r .= Cp::input_select_option($row->member_id, $row->screen_name);
            }

            $r .= Cp::input_select_footer();
            $r .= '</div>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
        }

        $r .= Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.delete'))).
              '</div>'.PHP_EOL.
              '</form>'.PHP_EOL;


        Cp::$title = __('members.delete_member');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(__('members.delete_member'));
        Cp::$body  = $r;
    }


    // ------------------------------------
    //  Login as Member - SuperAdmins only!
    // ------------------------------------

    function login_as_member()
    {
        if (Session::userdata('group_id') != 1)
        {
            return Cp::unauthorizedAccess();
        }

        if (($id = Request::input('mid')) === FALSE)
        {
            return Cp::unauthorizedAccess();
        }

        if (Session::userdata('member_id') == $id)
        {
            return Cp::unauthorizedAccess();
        }

        // ------------------------------------
        //  Fetch member data
        // ------------------------------------

        // @todo - Can Access CP? That is now a member_group_preferences value
        $query = DB::table('members')
            ->select('account.screen_name', 'member_groups.can_access_cp')
            ->join('member_groups', 'member_groups.group_id', '=', 'members.group_id')
            ->where('member_id', $id)
            ->first();

        if (!$query){
            return Cp::unauthorizedAccess();
        }

        Cp::$title = __('members.login_as_member');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(__('members.login_as_member'));


        // ------------------------------------
        //  Create Our Little Redirect Form
        // ------------------------------------

        $r  = Cp::formOpen(
                              array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=do_login_as_member'),
                              array('mid' => $id)
                              );

        $r .= Cp::quickDiv('default', '', 'menu_contents');

        $r .= Cp::table('tableBorder', '0', '', '100%');

        $r .= '<tr>'.PHP_EOL.
              Cp::td('tableHeadingAlt', '', '2').__('members.login_as_member').
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL.
              Cp::td('').
              Cp::quickDiv('alert', __('members.cp.action_can_not_be_undone')).
              Cp::quickDiv('littlePadding', str_replace('%screen_name%', $query->screen_name, __('members.login_as_member_description'))).
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL.
              Cp::td('');

        $r .= Cp::quickDiv('',
                        Cp::input_radio('return_destination', 'site', 1).'&nbsp;'.
                        __('members.site_homepage')
                        );

        if ($query->can_access_cp == 'y')
        {
            $r .= Cp::quickDiv('',
                            Cp::input_radio('return_destination', 'cp').'&nbsp;'.
                            __('members.control_panel')
                  );
        }

        $r .= Cp::quickDiv('',
                        Cp::input_radio('return_destination', 'other', '').'&nbsp;'.
                        __('members.other').NBS.':'.NBS.Cp::input_text('other_url', Site::config('site_url'), '30', '80', 'input', '500px')
                        );

        $r .= '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '<tr>'.PHP_EOL.
              Cp::td('').
              Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.submit'), 'submit')).
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL.
              '</div>'.PHP_EOL;

        Cp::$body = $r;
    }


    // ------------------------------------
    //  Login as Member - SuperAdmins only!
    // ------------------------------------

    function do_login_as_member()
    {
        // You lack the power, mortal!
        if (Session::userdata('group_id') != 1) {
            return Cp::unauthorizedAccess();
        }

        // Give me something to do here...
        if (($id = Request::input('mid')) === null) {
            return Cp::unauthorizedAccess();
        }

        // ------------------------------------
        //  Determine Return Path
        // ------------------------------------

        $return_path = Site::config('site_url');

        if (isset($_POST['return_destination']))
        {
            if ($_POST['return_destination'] == 'cp')
            {
                $return_path = Site::config('cp_url', FALSE);
            }
            elseif ($_POST['return_destination'] == 'other' && isset($_POST['other_url']) && stristr($_POST['other_url'], 'http'))
            {
                $return_path = strip_tags($_POST['other_url']);
            }
        }

        // ------------------------------------
        //  Log Them In and Boot up new Session Data
        // ------------------------------------

        // Already logged in as that member
        if (Session::userdata('member_id') == $id) {
            return redirect($return_path);
        }

        Auth::loginUsingId($id);

        Session::boot();

        // ------------------------------------
        //  Determine Redirect Path
        // ------------------------------------

        return redirect($return_path);
    }

    // ------------------------------------
    //  Delete Members
    // ------------------------------------

    function member_delete()
    {

        if ( ! Session::access('can_delete_members'))
        {
            return Cp::unauthorizedAccess();
        }

        if ( ! Request::input('delete'))
        {
            return $this->view_all_members();
        }

        // ------------------------------------
        //  Fetch member ID numbers and build the query
        // ------------------------------------

        $mids = [];

        foreach ($_POST as $key => $val) {
            if (strstr($key, 'delete') AND ! is_array($val) AND $val != '') {
                $mids[] = $val;
            }
        }

        // SAFETY CHECK
        // Let's fetch the Member Group ID of each member being deleted
        // If there is a Super Admin in the bunch we'll run a few more safeties

        $super_admins = 0;

        $query = DB::table('members')
            ->select('group_id')
            ->whereIn('member_id', $mids)
            ->get();

        foreach ($query as $row)
        {
            if ($query->group_id == 1)
            {
                $super_admins++;
            }
        }

        if ($super_admins > 0)
        {
            // You must be a Super Admin to delete a Super Admin

            if (Session::userdata('group_id') != 1)
            {
                return Cp::errorMessage(__('members.must_be_superadmin_to_delete_one'));
            }

            // You can't detete the only Super Admin
            $total_count = DB::table('members')
                ->where('group_id', 1)
                ->count();

            if ($super_admins >= $total_count)
            {
                return Cp::errorMessage(__('members.can_not_delete_super_admin'));
            }
        }

        // If we got this far we're clear to delete the members

        $deletes = [
                'members' => 'member_id',
                'member_data' => 'member_id',
                'member_homepage' => 'member_id'
        ];

        foreach($deletes as $table => $field) {
            DB::table($table)->whereIn($field, $mids)->delete();
        }

        // ------------------------------------
        //  Reassign Entires to Heir
        // ------------------------------------

        $heir_id = Request::input('heir');
        $entries_exit = Request::input('entries_exit');

        if ($heir_id !== FALSE && is_numeric($heir_id))
        {
            if ($entries_exit == 'yes')
            {
                DB::table('weblog_entries')
                    ->whereIn('author_id', $mids)
                    ->update(['author_id' => $heir_id]);

                $query = DB::table('weblog_entries')
                    ->where('author_id', $heir_id)
                    ->select(DB::raw('COUNT(entry_id) AS count, MAX(entry_date) AS entry_date'))
                    ->first();

                DB::table('members')
                    ->where('member_id', $heir_id)
                    ->update(['total_entries' => $query->count, 'last_entry_date' => $query->entry_date]);
            }
        }

        // Update global stats

        Stats::update_member_stats();

        $message = (count($ids) == 1) ? __('members.member_deleted') :
                                        __('members.members_deleted');

        return $this->view_all_members($message);
    }



    // ------------------------------------
    //  Member group overview
    // ------------------------------------

    function member_group_manager($message = '')
    {
        $row_limit = 20;
        $paginate = '';

        if ( ! Session::access('can_admin_mbr_groups'))
        {
            return Cp::unauthorizedAccess();
        }

        $query = DB::table('member_groups')
                ->orderBy('group_name');

        $count_query = clone $query;
        $count = $count_query->count();

        if ($count > $row_limit)
        {
            $row_count = ( ! Request::input('row')) ? 0 : Request::input('row');

            $paginate = Cp::pager(  BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=mbr_group_manager',
                                      $count,
                                      $row_limit,
                                      $row_count,
                                      'row'
                                    );

            $query->offset($row_count)->limit($row_limit);
        }

        $query = $query->get();


        Cp::$title  = __('admin.member_groups');
        Cp::$crumb  = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                       Cp::breadcrumbItem(__('admin.member_groups'));

        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=editMemberGroup',
            __('members.create_new_member_group')
        ];

        Cp::$body = Cp::header(__('admin.member_groups'), $right_links);

        if ($message != '') {
            Cp::$body .= Cp::quickDiv('successMessage', $message);
        }

        Cp::$body .= Cp::table('tableBorder', '0', '', '100%').
                      '<tr>'.PHP_EOL.
                      Cp::tableCell(
                        'tableHeadingAlt',
                        [
                            __('members.group_id'),
                            __('members.group_name'),
                            __('cp.edit'),
                            __('members.member_count'),
                            __('cp.delete')
                        ]).
                      '</tr>'.PHP_EOL;


        $i = 0;

        foreach($query as $row)
        {
            Cp::$body .= '<tr>'.PHP_EOL;
            Cp::$body .= Cp::tableCell('', $row->group_id, '5%');

            $title = $row->group_name;

            Cp::$body .= Cp::tableCell('', Cp::quickSpan('defaultBold', $title), '35%');

            Cp::$body .= Cp::tableCell('', Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=editMemberGroup'.AMP.'group_id='.$row->group_id, __('cp.edit')), '20%');

            $group_id = $row->group_id;
            $total_count = DB::table('members')
                ->where('group_id', $group_id)
                ->count();

            Cp::$body .= Cp::tableCell(
                '',
                '('.$total_count.')'.
                NBS.
                Cp::anchor(
                    BASE.'?C=Administration'.
                        AMP.'M=members'.
                        AMP.'P=view_members'.
                        AMP.'group_id='.$row->group_id,
                    __('cp.view')
                ),
                '15%');

            $delete = ( ! in_array($row->group_id, $this->no_delete)) ?
                Cp::anchor(
                    BASE.'?C=Administration'.
                        AMP.'M=members'.
                        AMP.'P=mbr_group_del_conf'.
                        AMP.'group_id='.$row->group_id, __('cp.delete')) :
                '--';

            Cp::$body .= Cp::tableCell('',  $delete, '10%');

            Cp::$body .= '</tr>'.PHP_EOL;
        }

        Cp::$body .= '</table>'.PHP_EOL;

        if ($paginate != '')
        {
            Cp::$body .= Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', $paginate));
        }

        Cp::$body .= Cp::formOpen(['action' => 'C=Administration'.AMP.'M=members'.AMP.'P=edit_mbr_group']);

        Cp::$body .= Cp::div('box');
        Cp::$body .= NBS.__('members.create_group_based_on_existing');
        Cp::$body .= Cp::input_select_header('clone_id');

        foreach($query as $row)
        {
            Cp::$body .= Cp::input_select_option($row->group_id, $row->group_name);
        }

        Cp::$body .= Cp::input_select_footer();
        Cp::$body .= '&nbsp;'.Cp::input_submit();
        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= '</form>'.PHP_EOL;
    }

    // ------------------------------------
    //  Edit/Create a member group form
    // ------------------------------------

    function editMemberGroup($msg='', $group_id = null)
    {
        // ------------------------------------
        //  Only super admins can administrate member groups
        // ------------------------------------

        if (Session::userdata('group_id') != 1) {
            return Cp::unauthorizedAccess(__('members.only_superadmins_can_admin_groups'));
        }

        $clone_id = Request::input('clone_id');

        // Incoming from a continue editing button?
        if (empty($group_id)) {
            $group_id = Request::input('group_id');
        }

        $id = (!empty($clone_id)) ? $clone_id : $group_id;

        // ------------------------------------
        //  Fetch the Group's Data
        // ------------------------------------

        if (!empty($id)) {
            $group_data = (array) DB::table('member_groups')->where('group_id', $id)->first();
            $preferences = DB::table('member_group_preferences')->where('group_id', $id)->get();

            foreach($preferences as $row) {
                $group_data[$row->handle] = $row->value;
            }
        }

        if(empty($group_data['is_locked'])) {
            $group_data['is_locked'] = 'y';
        }

        // ------------------------------------
        //  Group title
        // ------------------------------------

        $group_name       = ($group_id == '') ? '' : $group_data['group_name'];
        $group_description = ($group_id == '') ? '' : $group_data['group_description'];

        if ($msg != '') {
            Cp::$body .= Cp::quickDiv('successMessage', $msg);
        }

        Cp::$body_props .= ' onload="showHideMenu(\'group_name\');"';

        // ------------------------------------
        //  Declare form and page heading
        // ------------------------------------

        $js = <<<EOT
<script type="text/javascript">
    var lastShownObj = '';
    var lastShownColor = '';
    function showHideMenu(objValue)
    {
        if (lastShownObj != '') {
            $('#' + lastShownObj+'_pointer a').first().removeAttr('style');

            $('#' + lastShownObj + '_on').css('display', 'none');
        }

        lastShownObj = objValue;
        lastShownColor = $('#' + lastShownObj+'_pointer a').first().css('color');

        $('#' + objValue + '_on').css('display', 'block');
        lastShownColor = $('#' + lastShownObj+'_pointer a').first().css('color', '#000')
    }
</script>
EOT;

        Cp::$body .= $js;

        $r  = Cp::formOpen(
            [
                'action' => 'C=Administration'.AMP.'M=members'.AMP.'P=updateMemberGroup'
            ]
        );

        if ($clone_id != '')
        {
            $group_name = '';
            $group_description = '';
            $r .= Cp::input_hidden('clone_id', $clone_id);
        }

        $r .= Cp::input_hidden('group_id', $group_id);

        // ------------------------------------
        //  Group name form field
        // ------------------------------------

        $r .= '<div id="group_name_on" style="display: none; padding:0; margin: 0;">'.
              Cp::table('tableBorder', '0', '', '100%').
              '<tr>'.PHP_EOL.
              "<td class='tableHeadingAlt' colspan='2'>".
              NBS.__('members.group_name').
              '</tr>'.PHP_EOL.
              '<tr>'.PHP_EOL.
              Cp::td('', '40%').
              Cp::quickDiv('defaultBold', __('members.group_name')).
              '</td>'.PHP_EOL.
              Cp::td('', '60%').
              Cp::input_text('group_name', $group_name, '50', '70', 'input', '100%').
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '<tr>'.PHP_EOL.
              Cp::td('', '40%', '', '', 'top').
              Cp::quickDiv('defaultBold', __('members.group_description')).
              '</td>'.PHP_EOL.
              Cp::td('', '60%').
              Cp::input_textarea('group_description', $group_description, 10).
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL.
              Cp::quickDiv('defaultSmall', '');

        // ------------------------------------
        //  Top section of page
        // ------------------------------------

        if ($group_id == 1)
        {
            $r .= Cp::quickDiv('box', __('members.super_admin_edit_note'));
        }
        else
        {
            $r .= Cp::quickDiv('box', Cp::quickSpan('alert', __('members.warning')).'&nbsp;'.__('members.be_careful_assigning_groups'));
        }

        $r .= Cp::quickDiv('defaultSmall', '');

        $r .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Group lock
        // ------------------------------------

        $r .= '<div id="group_lock_on" style="display: none; padding:0; margin: 0;">';

        $r .= Cp::table('tableBorder', '0', '', '100%');

        $r .= '<tr>'.PHP_EOL.
              Cp::td('tableHeadingAlt', '', '2').__('members.group_lock').
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL.
              Cp::td('', '60%').
              Cp::quickDiv('alert', __('members.enable_lock')).
              Cp::quickDiv('littlePadding', __('members.lock_description')).
              '</td>'.PHP_EOL.
              Cp::td('', '40%');

        $selected = ($group_data['is_locked'] == 'y') ? true : false;

        $r .= __('members.locked').NBS.
              Cp::input_radio('is_locked', 'y', $selected).'&nbsp;';

        $selected = ($group_data['is_locked'] == 'n') ? true : false;

        $r .= __('members.unlocked').NBS.
              Cp::input_radio('is_locked', 'n', $selected).'&nbsp;';

        $r .= '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL.
              '</table>'.PHP_EOL.
              '</div>'.PHP_EOL;

        // ------------------------------------
        //  Fetch the names and IDs of all weblogs
        // ------------------------------------

        $blog_names = [];
        $blog_ids   = [];

        $query = DB::table('weblogs')
            ->orderBy('blog_title')
            ->select('weblog_id', 'site_id', 'blog_title')
            ->get();

        if ($id != 1)
        {
            foreach($query as $row)
            {
                $field = 'weblog_id_'.$row->weblog_id;

                $status = (isset($group_data[$field]) && $group_data[$field] == 'y') ? 'y' : 'n';

                $blog_names[$field] = $row->blog_title;
                $group_data[$field] = $status;
            }
        }

        // ------------------------------------
        //  Fetch the names and IDs of all plugins
        // ------------------------------------

        $plugins    = Plugins::list();
        $plugin_names = [];
        $plugin_ids   = [];

        if ($id != 1)
        {
            foreach(Plugins::list() as $plugin)
            {
                $name = __(strtolower($plugin->plugin_name . '_plugin_name'));
                $name = str_replace('_', ' ', $name);
                $name = ($name == '' ? $plugin->plugin_name : $name);

                $field = 'plugin_name_'.$plugin->plugin_name;

                $status = (isset($group_data[$field]) && $group_data[$field] == 'y') ? 'y' : 'n';

                $plugin_names[$field] = $name;
                $group_data[$field] = $status;
            }
        }

        // ------------------------------------
        //  Fetch the names and IDs of all Sites
        // ------------------------------------

        $site_cp_names = [];
        $site_offline_names = [];
        $site_ids   = []; // Figure out where I am storing these

        if ($id != 1) {
            foreach(Site::sitesList() as $site) {

                $field = 'can_access_offline_site_id_'.$site->site_id;
                $site_offline_names[$field] = $site->site_name;
                $status = (isset($group_data[$field]) && $group_data[$field] == 'y') ? 'y' : 'n';
                $group_data[$field] = $status;

                $field = 'can_access_cp_site_id_'.$site->site_id;
                $site_cp_names[$field] = $site->site_name;
                $status = (isset($group_data[$field]) && $group_data[$field] == 'y') ? 'y' : 'n';
                $group_data[$field] = $status;
            }
        }

        // ------------------------------------
        //  Assign clusters of member groups
        //  - The associative value (y/n) is the default setting
        // ------------------------------------

        $G = static::$group_preferences;

        $G['cp_site_cp_access_privs']  = $site_cp_names;
        $G['cp_site_offline_privs']    = $site_offline_names;
        $G['cp_weblog_post_privs']     = $blog_names;
        $G['cp_plugin_access_privs']   = $plugin_names;

        // ------------------------------------
        //  Super Admin Group cannot be edited
        // ------------------------------------

        if ($group_id == 1) {
            $G = ['mbr_account_privs' => ['include_in_authorlist' => 'n']];
        }

        // ------------------------------------
        //  Assign items we want to highlight
        // ------------------------------------

        $alert = [
            'can_view_offline_system',
            'can_access_cp',
            'can_admin_weblogs',
            'can_admin_templates',
            'can_delete_members',
            'can_admin_mbr_groups',
            'can_ban_users',
            'can_admin_members',
            'can_admin_preferences',
            'can_admin_plugins',
            'can_admin_utilities',
            'can_edit_categories',
            'can_delete_self'
        ];

        // ------------------------------------
        //  Items that should be shown in an input box
        // ------------------------------------

        $tbox = [
            'search_flood_control',
            'mbr_delete_notify_emails'
        ];

        // ------------------------------------
        //  Render the group matrix
        // ------------------------------------

        $special = ['cp_plugin_access_privs', 'cp_site_offline_privs', 'cp_site_cp_access_privs'];

        foreach ($G as $g_key => $g_val)
        {
            // ------------------------------------
            //  Start the Table
            // ------------------------------------

            $r .= '<div id="'.$g_key.'_on" style="display: none; padding:0; margin: 0;">';
            $r .= Cp::table('tableBorder', '0', '', '100%');
            $r .= '<tr>'.PHP_EOL;

            $r .= "<td class='tableHeadingAlt' id='".$g_key."2' colspan='2'>";
            $r .= NBS.__($g_key);
            $r .= '</tr>'.PHP_EOL;

            $i = 0;

            foreach($g_val as $key => $val)
            {
                if ( !in_array($g_key, $special) && ! isset($group_data[$key])) {
                    $group_data[$key] = $val;
                }

                $line = __($key);

                if (substr($key, 0, strlen('weblog_id_')) == 'weblog_id_')
                {
                    $line = __('members.can_post_in').Cp::quickSpan('alert', $blog_names[$key]);
                }

                if (substr($key, 0, strlen('plugin_name_')) == 'plugin_name_')
                {

                    $line = __('members.can_access_plugin').Cp::quickSpan('alert', $plugin_names[$key]);
                }

                if (substr($key, 0, strlen('can_access_offline_site_id_')) == 'can_access_offline_site_id_')
                {
                    $line = __('members.can_access_offline_site').Cp::quickSpan('alert', $site_offline_names[$key]);
                }

                if (substr($key, 0, strlen('can_access_cp_site_id_')) == 'can_access_cp_site_id_')
                {
                    $line = __('members.can_access_cp').Cp::quickSpan('alert', $site_cp_names[$key]);
                }

                $mark = (in_array($key, $alert)) ?  Cp::quickSpan('alert', $line) : Cp::quickSpan('defaultBold', $line);

                $r .= '<tr>'.PHP_EOL.
                      Cp::td('', '60%').
                      $mark;

                $r .= '</td>'.PHP_EOL.
                      Cp::td('', '40%');

                if (in_array($key, $tbox))
                {
                    $width = ($key == 'mbr_delete_notify_emails') ? '100%' : '100px';
                    $length = ($key == 'mbr_delete_notify_emails') ? '255' : '5';
                    $r .= Cp::input_text($key, $group_data[$key], '15', $length, 'input', $width);
                }
                else
                {
                    $r .= __('cp.yes').NBS.
                          Cp::input_radio($key, 'y', ($group_data[$key] == 'y') ? 1 : '').'&nbsp;';

                    $r .= __('cp.no').NBS.
                          Cp::input_radio($key, 'n', ($group_data[$key] == 'n') ? 1 : '').'&nbsp;';
                }

                $r .= '</td>'.PHP_EOL;
                $r .= '</tr>'.PHP_EOL;
            }

            $r .= '</table>'.PHP_EOL;
            $r .= '</div>'.PHP_EOL;
        }

        // ------------------------------------
        //  Submit button
        // ------------------------------------

        if (empty($group_id))
        {
            $r .= Cp::quickDiv(
                'paddingTop',
                Cp::input_submit(__('cp.submit'))
                .NBS.
                Cp::input_submit(__('cp.submit_and_return'),'return')
            );
        }
        else
        {
            $r .= Cp::quickDiv(
                'paddingTop',
                Cp::input_submit(__('members.update')).
                NBS.
                Cp::input_submit(__('members.update_and_return'), 'return')
            );
        }

        $r .= '</form>'.PHP_EOL;

        // ------------------------------------
        //  Create Our All Encompassing Table of Weblog Goodness
        // ------------------------------------

        Cp::$body .= Cp::table('', '0', '', '100%');

        $menu  = '';
        $menu .= Cp::quickDiv('navPad',
                ' <span id="group_name_pointer">&#8226; '.
                    Cp::anchor("#", __('members.group_name'),'onclick="showHideMenu(\'group_name\');"').
                '</span>');

        if ($group_id != 1)
        {
            $menu .= Cp::quickDiv('navPad',
                ' <span id="group_lock_pointer">&#8226; '.
                    Cp::anchor("#", __('members.security_lock'), 'onclick="showHideMenu(\'group_lock\');"').
                '</span>');
        }

        // Sites Access
        if ($group_id != 1)
        {
            $menu .= Cp::quickDiv('navPad',
                ' <span id="cp_site_cp_access_privs_pointer">&#8226; '.
                    Cp::anchor("#", __('members.cp_site_cp_access_privs'), 'onclick="showHideMenu(\'cp_site_cp_access_privs\');"').
                    '</span>'
                );
        }

        // Sites Access
        if ($group_id != 1)
        {
            $menu .= Cp::quickDiv('navPad',
                ' <span id="cp_site_offline_privs_pointer">&#8226; '.
                    Cp::anchor("#", __('members.cp_site_offline_privs'), 'onclick="showHideMenu(\'cp_site_offline_privs\');"').
                    '</span>'
                );
        }


        foreach ($G as $g_key => $g_val)
        {
            if (in_array($g_key, $special)) {
                continue;
            }

            $menu .= Cp::quickDiv(
                'navPad',
                ' <span id="'.$g_key.'_pointer">&#8226; '.
                    Cp::anchor(
                        "#",
                        __($g_key),
                        'onclick="showHideMenu(\''.$g_key.'\');"'
                    ).
                '</span>');
        }

        // Plugins
        if ($group_id != 1)
        {
            $menu .= Cp::quickDiv('navPad',
                ' <span id="cp_plugin_access_privs_pointer">&#8226; '.
                    Cp::anchor("#", __('members.cp_plugin_access_privs'), 'onclick="showHideMenu(\'cp_plugin_access_privs\');"').
                    '</span>'
                );
        }

        // ------------------------------------
        //  Compile all of it into output
        // ------------------------------------

        $title = (!empty($id)) ? __('members.edit_member_group') : __('members.create_member_group');

        $first_text =   Cp::div('tableHeadingAlt').
                              $title.
                          '</div>'.PHP_EOL.
                        Cp::div('profileMenuInner', '', 'membersMenu').
                          $menu.
                        '</div>'.PHP_EOL;

        // Create the Table
        $table_row = [
            'first'     => ['valign' => "top", 'width' => "220px", 'text' => $first_text],
            'second'    => ['class' => "default", 'width'  => "8px"],
            'third'     => ['valign' => "top", 'text' => $r]
        ];

        Cp::$body .= Cp::tableRow($table_row).
                      '</table>'.PHP_EOL;

        Cp::$title = $title;

        if ($group_id != '')
        {
            Cp::$crumb =
                Cp::anchor(
                    BASE.'?C=Administration'.
                        AMP.'area=members_and_groups',
                    __('admin.members_and_groups')
                ).
                Cp::breadcrumbItem(
                    Cp::anchor(
                        BASE.'?C=Administration'.
                            AMP.'M=members'.
                            AMP.'P=mbr_group_manager',
                        __('admin.member_groups')
                    )
                ).
                Cp::breadcrumbItem(
                    Cp::anchor(
                        BASE.'?C=Administration'.
                            AMP.'M=members'.
                            AMP.'P=edit_mbr_group'.
                            AMP.'group_id='.$group_data['group_id'],
                        $title
                    )
                ).
                Cp::breadcrumbItem($group_data['group_name']);
        }
        else
        {
            Cp::$crumb =
                Cp::anchor(
                    BASE.'?C=Administration'.
                        AMP.'area=members_and_groups',
                    __('admin.members_and_groups')
                ).
                Cp::breadcrumbItem(
                    Cp::anchor(
                        BASE.'?C=Administration'.
                            AMP.'M=members'.
                            AMP.'P=mbr_group_manager',
                        __('admin.member_groups')
                    )
                ).
                Cp::breadcrumbItem($title);
        }
    }

    // ------------------------------------
    //  Create/update a member group
    // ------------------------------------

    function updateMemberGroup()
    {
        // ------------------------------------
        //  Only super admins can administrate member groups
        // ------------------------------------

        if (Session::userdata('group_id') != 1) {
            return Cp::unauthorizedAccess(__('members.only_superadmins_can_admin_groups'));
        }

        $edit = (bool) Request::has('group_id');

        $group_id = Request::input('group_id');
        $clone_id = Request::input('clone_id');

        unset($_POST['group_id']);
        unset($_POST['clone_id']);

        // No group name
        if ( ! Request::input('group_name')) {
            return Cp::errorMessage(__('members.missing_group_name'));
        }

        $return = (Request::has('return'));

        $site_ids     = [];
        $plugin_ids   = [];
        $weblog_ids   = [];
        $template_ids = [];

        // ------------------------------------
        //  Remove and Store Weblog and Template Permissions
        // ------------------------------------

        $data = [
            'group_name'        => Request::input('group_name'),
            'group_description' => Request::input('group_description'),
        ];

        $duplicate = DB::table('member_groups')
            ->where('group_name', $data['group_name']);

        if (!empty($group_id)) {
            $duplicate->where('group_id', '!=', $group_id);
        }

        if($duplicate->count() > 0) {
            return Cp::errorMessage(__('members.duplicate_group_name'));
        }

        // ------------------------------------
        //  Preferences
        // ------------------------------------

        $preferences['group_id']  = $group_id;
        $preferences['is_locked'] = Request::input('is_locked');

        foreach(static::$group_preferences as $group => $prefs) {
            foreach((array) $prefs as $key => $default) {
                if (Request::has($key)) {
                    $preferences[$key] = Request::get($key);
                }
            }
        }

        foreach (Request::all() as $key => $val)
        {
            if (substr($key, 0, strlen('weblog_id_')) == 'weblog_id_') {
                $preferences[$key] = ($val == 'y') ? 'y' : 'n';
            } elseif (substr($key, 0, strlen('plugin_name_')) == 'plugin_name_') {
                $preferences[$key] = ($val == 'y') ? 'y' : 'n';
            } elseif (substr($key, 0, strlen('can_access_offline_site_id_')) == 'can_access_offline_site_id_') {
                $preferences[$key] = ($val == 'y') ? 'y' : 'n';
            } elseif (substr($key, 0, strlen('can_access_cp_site_id_')) == 'can_access_cp_site_id_') {
                $preferences[$key] = ($val == 'y') ? 'y' : 'n';
            } else {
                continue;
            }
        }

        if ($edit === false)
        {
            $group_id = DB::table('member_groups')->insertGetId($data);

            foreach($preferences as $handle => $value) {
                $prefs =
                [
                    'group_id'  => $data['group_id'],
                    'handle'    => $handle,
                    'value'     => $value
                ];

                DB::table('member_group_preferences')->insert($prefs);
            }

            $uploads = DB::table('upload_prefs')
                ->select('id')
                ->get();

            foreach($uploads as $yeeha)
            {
                DB::table('upload_no_access')
                    ->insert(
                    [
                        'upload_id'    => $yeeha->id,
                        'upload_loc'   => 'cp',
                        'member_group' => $group_id
                    ]);
            }

            $message = __('members.member_group_created').'&nbsp;'.$_POST['group_name'];
        }
        else
        {
            DB::table('member_groups')
                ->where('group_id', $data['group_id'])
                ->update($data);

            DB::table('member_group_preferences')
                ->where('group_id', $data['group_id'])
                ->delete();

            foreach($preferences as $handle => $value) {
                $prefs =
                [
                    'group_id'  => $data['group_id'],
                    'handle'    => $handle,
                    'value'     => $value
                ];

                DB::table('member_group_preferences')->insert($prefs);
            }

            $message = __('members.member_group_updated').'&nbsp;'.$_POST['group_name'];
        }

        // Update CP log
        Cp::log($message);

        $this->clearMemberGroupCache($data['group_id']);

        if ($return == true) {
            return $this->member_group_manager($message);
        }

        return $this->editMemberGroup($message, $group_id);
    }

    // ------------------------------------
    //  Delete member group confirm
    // ------------------------------------
    // Warning message shown when you try to delete a group
    //-----------------------------------------------------------

    function delete_member_group_conf()
    {
        // ------------------------------------
        //  Only super admins can delete member groups
        // ------------------------------------

        if (Session::userdata('group_id') != 1)
        {
            return Cp::unauthorizedAccess(__('members.only_superadmins_can_admin_groups'));
        }


        if ( ! $group_id = Request::input('group_id'))
        {
            return false;
        }

        // You can't delete these groups

        if (in_array($group_id, $this->no_delete))
        {
            return Cp::unauthorizedAccess();
        }

        // Are there any members that are assigned to this group?
        $count = DB::table('members')
                ->where('group_id', $group_id)
                ->count();

        $members_exist = (!empty($count)) ? true : false;

        $group_name = DB::table('member_groups')
            ->where('group_id', $group_id)
            ->value('group_name');

        Cp::$title = __('members.delete_member_group');

        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=group_manager', __('admin.member_groups'))).
                      Cp::breadcrumbItem(__('members.delete_member_group'));


        Cp::$body = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=delete_mbr_group'.AMP.'group_id='.$group_id))
                    .Cp::input_hidden('group_id', $group_id);

        Cp::$body .= ($members_exist === TRUE) ? Cp::input_hidden('reassign', 'y') : Cp::input_hidden('reassign', 'n');


        Cp::$body .= Cp::heading(Cp::quickSpan('alert', __('members.delete_member_group')))
                     .Cp::div('box')
                     .Cp::quickDiv('littlePadding', '<b>'.__('members.delete_member_group_confirm').'</b>')
                     .Cp::quickDiv('littlePadding', '<i>'.$group_name.'</i>')
                     .Cp::quickDiv('alert', BR.__('members.cp.action_can_not_be_undone').BR.BR);

        if ($members_exist === TRUE)
        {
            Cp::$body .= Cp::quickDiv('defaultBold', str_replace('%x', $count, __('members.member_assignment_warning')));

            Cp::$body .= Cp::div('littlePadding');
            Cp::$body .= Cp::input_select_header('new_group_id');

            $query = DB::table('member_groups')
                ->select('group_name', 'group_id')
                ->orderBy('group_name')
                ->get();

            foreach ($query as $row)
            {
                Cp::$body .= Cp::input_select_option($row->group_id, $row->group_name, '');
            }

            Cp::$body .= Cp::input_select_footer();
            Cp::$body .= '</div>'.PHP_EOL;
        }

        Cp::$body .= Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.delete')))
                    .'</div>'.PHP_EOL
                    .'</form>'.PHP_EOL;
    }


    // ------------------------------------
    //  Delete Member Group
    // ------------------------------------

    function delete_member_group()
    {
        // ------------------------------------
        //  Only super admins can delete member groups
        // ------------------------------------

        if (Session::userdata('group_id') != 1) {
            return Cp::unauthorizedAccess(__('members.only_superadmins_can_admin_groups'));
        }

        if ( ! $group_id = Request::input('group_id')) {
            return false;
        }

        if (in_array($group_id, $this->no_delete)) {
            return Cp::unauthorizedAccess();
        }

        if (Request::input('reassign') == 'y' AND Request::input('new_group_id') !== null)
        {
            DB::table('members')
                ->where('group_id', $group_id)
                ->update(['group_id' => Request::input('new_group_id')]);
        }

        DB::table('members_groups')
            ->where('group_id', $group_id)
            ->delete();

        DB::table('members_group_preferences')
            ->where('group_id', $group_id)
            ->delete();

        $this->clearMemberGroupCache($group_id);

        return $this->member_group_manager(__('members.member_group_deleted'));
    }

    // ------------------------------------
    //  Create a member profile form
    // ------------------------------------

    function new_member_profile_form()
    {
        if ( ! Session::access('can_admin_members')) {
            return Cp::unauthorizedAccess();
        }

        Cp::$body_props = " onload=\"document.forms[0].email.focus();\"";

        $title = __('members.register_member');

        // Build the output
        $r  = Cp::formOpen(['action' => 'C=Administration'.AMP.'M=members'.AMP.'P=register_member']);

        $r .= Cp::quickDiv('tableHeading', $title);
        $r .= Cp::div('box');
        $r .= Cp::itemgroup(
            Cp::required().NBS.__('account.email'),
            Cp::input_text('email', '', '35', '32', 'input', '300px')
        );

        $r .= Cp::itemgroup(
            Cp::required().NBS.__('account.password'),
            Cp::input_pass('password', '', '35', '32', 'input', '300px')
        );

        $r .= Cp::itemgroup(
            Cp::required().NBS.__('account.password_confirm'),
            Cp::input_pass('password_confirm', '', '35', '32', 'input', '300px')
        );

        $r .= Cp::itemgroup(
            Cp::required().NBS.__('account.screen_name'),
            Cp::input_text('screen_name', '', '40', '50', 'input', '300px')
        );

        $r .= '</td>'.PHP_EOL.
              Cp::td('', '45%', '', '', 'top');

        $r .= Cp::itemgroup(
            Cp::required().NBS.__('account.email'),
            Cp::input_text('email', '', '35', '100', 'input', '300px')
        );

        // Member groups assignment
        if (Session::access('can_admin_mbr_groups')) {
            $query = DB::table('member_groups')
                ->select('group_id', 'group_name')
                ->orderBy('group_name');

            if (Session::userdata('group_id') != 1)
            {
                $query->where('is_locked', 'n');
            }

            $query = $query->get();

            if ($query->count() > 0)
            {
                $r .= Cp::quickDiv(
                    'paddingTop',
                    Cp::quickDiv('defaultBold', __('account.member_group_assignment'))
                );

                $r .= Cp::input_select_header('group_id');

                foreach ($query as $row)
                {
                    $selected = ($row->group_id == 5) ? 1 : '';

                    // Only SuperAdmins can assigned SuperAdmins
                    if ($row->group_id == 1 AND Session::userdata('group_id') != 1) {
                        continue;
                    }

                    $r .= Cp::input_select_option($row->group_id, $row->group_name, $selected);
                }

                $r .= Cp::input_select_footer();
            }
        }

        $r .= '</div>'.PHP_EOL;

        // Submit button

        $r .= Cp::itemgroup( '',
                                Cp::required(1).'<br><br>'.Cp::input_submit(__('cp.submit'))
                              );
        $r .= '</form>'.PHP_EOL;


        Cp::$title = $title;
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem($title);
        Cp::$body  = $r;
    }

    // ------------------------------------
    //  Create a member profile
    // ------------------------------------

    public function create_member_profile()
    {
        if ( ! Session::access('can_admin_members')) {
            return Cp::unauthorizedAccess();
        }

        $data = [];

        if (Request::has('group_id')) {
            if ( ! Session::access('can_admin_mbr_groups')) {
                return Cp::unauthorizedAccess();
            }

            $data['group_id'] = Request::input('group_id');
        }

        // ------------------------------------
        //  Instantiate validation class
        // ------------------------------------

        $VAL = new ValidateAccount(
            [
                'request_type'          => 'new', // new or update
                'require_password'      => false,
                'screen_name'           => Request::input('screen_name'),
                'password'              => Request::input('password'),
                'password_confirm'      => Request::input('password_confirm'),
                'email'                 => Request::input('email'),
            ]
        );

        $VAL->validateScreenName();
        $VAL->validateEmail();
        $VAL->validatePassword();

        // ------------------------------------
        //  Display error is there are any
        // ------------------------------------

        if (count($VAL->errors()) > 0) {
            return Cp::errorMessage($VAL->errors());
        }

        // Assign the query data
        $data['password']    = Hash::make(Request::input('password'));
        $data['ip_address']  = Request::ip();
        $data['unique_id']   = Uuid::uuid4();
        $data['join_date']   = Carbon::now();
        $data['email']       = Request::input('email');
        $data['screen_name'] = Request::input('screen_name');

        // Was a member group ID submitted?
        $data['group_id'] = ( ! Request::input('group_id')) ? 2 : Request::input('group_id');

        // Create records
        $member_id = DB::table('members')->insertGetId($data);
        DB::table('member_data')->insert(['member_id' => $member_id]);
        DB::table('member_homepage')->insert(['member_id' => $member_id]);

        $message = __('members.new_member_added');

        // Write log file
        Cp::log($message.' '.$data['email']);

        // Update global stat
        Stats::update_member_stats();

        // Build success message
        return $this->view_all_members($message.' <b>'.stripslashes($data['screen_name']).'</b>');
    }

    // ------------------------------------
    //  Member banning forms
    // ------------------------------------

    function member_banning_forms()
    {
        if ( ! Session::access('can_ban_users')) {
            return Cp::unauthorizedAccess();
        }

        $banned_ips   = Site::config('banned_ips');
        $banned_emails  = Site::config('banned_emails');
        $banned_screen_names = Site::config('banned_screen_names');

        $out        = '';
        $ips        = '';
        $email      = '';
        $users      = '';
        $screens    = '';

        if ($banned_ips != '') {
            foreach (explode('|', $banned_ips) as $val) {
                $ips .= $val.PHP_EOL;
            }
        }

        if ($banned_emails != '')
        {
            foreach (explode('|', $banned_emails) as $val)
            {
                $email .= $val.PHP_EOL;
            }
        }

        if ($banned_screen_names != '')
        {
            foreach (explode('|', $banned_screen_names) as $val)
            {
                $screens .= $val.PHP_EOL;
            }
        }

        $r  = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=save_ban_data')).
              Cp::quickDiv('tableHeading', __('members.user_banning'));

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('members.ban_preferences_updated'));
        }

        $r .=   Cp::table('', '', '', '100%', '').
                '<tr>'.PHP_EOL.
                Cp::td('', '48%', '', '', 'top');


        $r .=   Cp::div('box').
                Cp::heading(__('members.ip_address_banning'), 5).
                Cp::quickDiv('littlePadding', Cp::quickSpan('highlight', __('members.ip_banning_instructions'))).
                Cp::quickDiv('littlePadding', __('members.ip_banning_instructions_cont')).
                Cp::input_textarea('banned_ips', stripslashes($ips), '22', 'textarea', '100%').BR.BR;

        $r .=   Cp::heading(BR.__('members.ban_options'), 5);

        $selected = (Site::config('ban_action') == 'restrict') ? 1 : '';

        $r .=   Cp::div('littlePadding').
                Cp::input_radio('ban_action', 'restrict', $selected).NBS. __('members.restrict_to_viewing').BR.
                '</div>'.PHP_EOL;

        $selected    = (Site::config('ban_action') == 'message') ? 1 : '';

        $r .=   Cp::div('littlePadding').
                Cp::input_radio('ban_action', 'message', $selected).NBS.__('members.show_this_message').BR.
                Cp::input_text('ban_message', Site::config('ban_message'), '50', '100', 'input', '100%').
                '</div>'.PHP_EOL;

        $selected    = (Site::config('ban_action') == 'bounce') ? 1 : '';
        $destination = (Site::config('ban_destination') == '') ? 'https://' : Site::config('ban_destination');

        $r .=   Cp::div('littlePadding').
                Cp::input_radio('ban_action', 'bounce', $selected).NBS.__('members.send_to_site').BR.
                Cp::input_text('ban_destination', $destination, '50', '70', 'input', '100%').
                '</div>'.PHP_EOL;

        $r .=   Cp::div().BR.
                Cp::input_submit(__('members.update')).BR.BR.BR.
                '</div>'.PHP_EOL.
                '</div>'.PHP_EOL;

        $r .=   '</td>'.PHP_EOL.
                Cp::td('', '4%', '', '', 'top').NBS.
                '</td>'.PHP_EOL.
                Cp::td('', '48%', '', '', 'top');

        $r .=   Cp::div('box').
                Cp::heading(__('members.email_address_banning'), 5).
                Cp::quickDiv('littlePadding', Cp::quickSpan('highlight', __('members.email_banning_instructions'))).
                Cp::quickDiv('littlePadding', __('members.email_banning_instructions_cont')).
                Cp::input_textarea('banned_emails', stripslashes($email), '9', 'textarea', '100%').
                '</div>'.PHP_EOL;

        $r .= Cp::quickDiv('defaultSmall', NBS);

        $r .=   Cp::div('box').
                Cp::heading(__('members.screen_name_banning'), 5).
                Cp::quickDiv('littlePadding', Cp::quickSpan('highlight', __('members.screen_name_banning_instructions'))).
                Cp::input_textarea('banned_screen_names', stripslashes($screens), '9', 'textarea', '100%').
                '</div>'.PHP_EOL;

        $r .=   '</td>'.PHP_EOL.
                '</tr>'.PHP_EOL.
                '</table>'.PHP_EOL;

        $r .= '</form>'.PHP_EOL;

        Cp::$title = __('members.user_banning');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(__('members.user_banning'));
        Cp::$body  = $r;
    }


    // ------------------------------------
    //  Update banning data
    // ------------------------------------

    function update_banning_data()
    {
        if ( ! Session::access('can_ban_users'))
        {
            return Cp::unauthorizedAccess();
        }

        if (empty($_POST))
        {
            return Cp::unauthorizedAccess();
        }

        foreach ($_POST as $key => $val)
        {
            $_POST[$key] = stripslashes($val);
        }

        $banned_ips             = str_replace(PHP_EOL, '|', $_POST['banned_ips']);
        $banned_emails          = str_replace(PHP_EOL, '|', $_POST['banned_emails']);
        $banned_screen_names    = str_replace(PHP_EOL, '|', $_POST['banned_screen_names']);

        $destination = ($_POST['ban_destination'] == 'https://') ? '' : $_POST['ban_destination'];

        $data = array(
                        'banned_ips'            => $banned_ips,
                        'banned_emails'         => $banned_emails,
                        'banned_emails'         => $banned_emails,
                        'banned_screen_names'   => $banned_screen_names,
                        'ban_action'            => $_POST['ban_action'],
                        'ban_message'           => $_POST['ban_message'],
                        'ban_destination'       => $destination
                     );

        // ------------------------------------
        //  Preferences Stored in Database For Site
        // ------------------------------------

        $query = DB::table('sites')
            ->select('site_id', 'site_preferences')
            ->get();

        foreach($query AS $row)
        {
            $prefs = array_merge(unserialize($row->site_preferences), $data);

            DB::table('sites')
                ->update(['site_preferences' => serialize($prefs)]);
        }

        $override = (Request::input('class_override') != '') ? '&class_override='.Request::input('class_override') : '';

        return redirect('?C=Administration&M=members&P=member_banning&U=1'.$override);
    }

    // ------------------------------------
    //  Custom profile fields
    // ------------------------------------
    // This function show a list of current member fields and the
    // form that allows you to create a new field.
    //-----------------------------------------------------------

    function custom_profile_fields($group_id = '')
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }

        // Fetch language file
        // There are some lines in the publish administration language file
        // that we need.

        Cp::$title  = __('members.custom_member_fields');
        Cp::$crumb  = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                       Cp::breadcrumbItem(__('members.custom_member_fields'));

        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=edit_field',
            __('members.create_new_profile_field')
        ];

        $r  = Cp::header(__('members.custom_member_fields'), $right_links);

        // Build the output
        if (Request::input('U')) {
            $r .= Cp::quickDiv('successMessage', __('members.field_updated'));
        }

        $query = DB::table('member_fields')
            ->select('m_field_id', 'm_field_order', 'm_field_label')
            ->orderBy('m_field_order')
            ->get();

        if ($query->count() == 0)
        {
            Cp::$body  = Cp::div('box');
            Cp::$body .= Cp::quickDiv('littlePadding', Cp::heading(__('members.no_custom_profile_fields'), 5));
                Cp::$body .= Cp::quickDiv('littlePadding', Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=edit_field', __('members.create_new_profile_field')));
            Cp::$body .= '</div>'.PHP_EOL;

            return;
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeadingAlt', '', '3').
              __('members.current_fields').
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $i = 0;

        foreach ($query as $row)
        {
            $r .= '<tr>'.PHP_EOL;
            $r .= Cp::tableCell('', $row->m_field_order.'&nbsp;'.Cp::quickSpan('defaultBold', $row->m_field_label), '40%');
            $r .= Cp::tableCell('', Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=edit_field'.AMP.'m_field_id='.$row->m_field_id, __('cp.edit')), '30%');
            $r .= Cp::tableCell('', Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=del_field_conf'.AMP.'m_field_id='.$row->m_field_id, __('cp.delete')), '30%');
            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        $r .= Cp::quickDiv('paddedWrapper', Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=edit_field_order', __('members.edit_field_order')));

        Cp::$body   = $r;
    }




    // ------------------------------------
    //  Edit field form
    // ------------------------------------
    // This function lets you edit an existing custom field
    //-----------------------------------------------------------

    function edit_profile_field_form()
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }

        $type = ($m_field_id = Request::input('m_field_id')) ? 'edit' : 'new';

        // Fetch language file
        // There are some lines in the publish administration language file
        // that we need.

        $total_fields = '';

        if ($type == 'new')
        {
            $total_fields = DB::table('member_fields')->count() + 1;
        }

        $query = DB::table('member_fields')
            ->where('m_field_id', $m_field_id)
            ->first();

        if (!$query) {

            $m_field_name='';
            $m_field_label='';
            $m_field_description='';
            $m_field_type='text';
            $m_field_list_items='';
            $m_field_ta_rows=8;
            $m_field_maxl='';
            $m_field_width='';
            $m_field_search='y';
            $m_field_required='n';
            $m_field_public='y';
            $m_field_reg='n';
            $m_field_order='';
        } else {
            foreach ($query as $key => $val) {
                $$key = $val;
            }
        }

        $r = <<<EOT

        <script type="text/javascript">
        <!--

        function showhide_element(id)
        {
            if (id == 'text')
            {
                document.getElementById('text_block').style.display = "block";
                document.getElementById('textarea_block').style.display = "none";
                document.getElementById('select_block').style.display = "none";
            }
            else if (id == 'textarea')
            {
                document.getElementById('textarea_block').style.display = "block";
                document.getElementById('text_block').style.display = "none";
                document.getElementById('select_block').style.display = "none";
            }
            else
            {
                document.getElementById('select_block').style.display = "block";
                document.getElementById('text_block').style.display = "none";
                document.getElementById('textarea_block').style.display = "none";
            }
        }

        -->
        </script>
EOT;

        $title = ($type == 'edit') ? 'members.edit_member_field' : 'members.create_member_field';

        $i = 0;

        // Form declaration

        $r .= Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=update_profile_fields'.AMP.'U=1'));
        $r .= Cp::input_hidden('m_field_id', $m_field_id);
        $r .= Cp::input_hidden('cur_field_name', $m_field_name);

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::td('tableHeading', '', '2').__($title).'</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;


        // ------------------------------------
        //  Field name
        // ------------------------------------

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', Cp::required().NBS.__('members.fieldname')).Cp::quickDiv('littlePadding', __('members.fieldname_cont')), '40%');
        $r .= Cp::tableCell('', Cp::input_text('m_field_name', $m_field_name, '50', '60', 'input', '300px'), '60%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Field label
        // ------------------------------------

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', Cp::required().NBS.__('members.fieldlabel')).Cp::quickDiv('littlePadding', __('members.for_profile_page')), '40%');
        $r .= Cp::tableCell('', Cp::input_text('m_field_label', $m_field_label, '50', '60', 'input', '300px'), '60%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Field Description
        // ------------------------------------

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('members.field_description')).Cp::quickDiv('littlePadding', __('members.field_description_info')), '40%');
        $r .= Cp::tableCell('', Cp::input_textarea('m_field_description', $m_field_description, '4', 'textarea', '100%'), '60%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Field order
        // ------------------------------------

        if ($type == 'new')
            $m_field_order = $total_fields;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('admin.field_order')), '40%');
        $r .= Cp::tableCell('', Cp::input_text('m_field_order', $m_field_order, '4', '3', 'input', '30px'), '60%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Field type
        // ------------------------------------

        $sel_1 = ''; $sel_2 = ''; $sel_3 = '';
        $text_js = ($type == 'edit') ? 'none' : 'block';
        $textarea_js = 'none';
        $select_js = 'none';
        $select_opt_js = 'none';

        switch ($m_field_type)
        {
            case 'text'     : $sel_1 = 1; $text_js = 'block';
                break;
            case 'textarea' : $sel_2 = 1; $textarea_js = 'block';
                break;
            case 'select'   : $sel_3 = 1; $select_js = 'block'; $select_opt_js = 'block';
                break;
        }

        // ------------------------------------
        //  Create the pull-down menu
        // ------------------------------------

        $typemenu = "<select name='m_field_type' class='select' onchange='showhide_element(this.options[this.selectedIndex].value);' >".PHP_EOL;
        $typemenu .= Cp::input_select_option('text',      __('admin.text_input'), $sel_1)
                    .Cp::input_select_option('textarea',  __('admin.textarea'),   $sel_2)
                    .Cp::input_select_option('select',    __('admin.select_list'), $sel_3)
                    .Cp::input_select_footer();


        // ------------------------------------
        //  Field width
        // ------------------------------------

        if ($m_field_width == '') {
            $m_field_width = '100%';
        }

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('members.field_width')).Cp::quickDiv('littlePadding', __('members.field_width_cont')), '40%');
        $r .= Cp::tableCell('', Cp::input_text('m_field_width', $m_field_width, '8', '6', 'input', '60px'), '60%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Max-length Field
        // ------------------------------------

        if ($m_field_maxl == '') $m_field_maxl = '100';

        $typopts  = '<div id="text_block" style="display: '.$text_js.'; padding:0; margin:5px 0 0 0;">';
        $typopts .= Cp::quickDiv('defaultBold', __('members.max_length')).Cp::quickDiv('littlePadding', Cp::input_text('m_field_maxl', $m_field_maxl, '4', '3', 'input', '30px'));
        $typopts .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Textarea Row Field
        // ------------------------------------

        if ($m_field_ta_rows == '') $m_field_ta_rows = '10';

        $typopts .= '<div id="textarea_block" style="display: '.$textarea_js.'; padding:0; margin:5px 0 0 0;">';
        $typopts .= Cp::quickDiv('defaultBold', __('members.text_area_rows')).Cp::quickDiv('littlePadding', Cp::input_text('m_field_ta_rows', $m_field_ta_rows, '4', '3', 'input', '30px'));
        $typopts .= '</div>'.PHP_EOL;

        // ------------------------------------
        //  Select List Field
        // ------------------------------------

        $typopts .= '<div id="select_block" style="display: '.$select_js.'; padding:0; margin:5px 0 0 0;">';
        $typopts .= Cp::quickDiv('defaultBold', __('members.pull_down_items')).Cp::quickDiv('default', __('members.field_list_instructions')).Cp::input_textarea('m_field_list_items', $m_field_list_items, 10, 'textarea', '400px');
        $typopts .= '</div>'.PHP_EOL;


        // ------------------------------------
        //  Generate the above items
        // ------------------------------------

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickDiv('littlePadding', Cp::quickSpan('defaultBold', __('admin.field_type'))).$typemenu, '50%', 'top');
        $r .= Cp::tableCell('', $typopts, '50%', 'top');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Is field required?
        // ------------------------------------

        if ($m_field_required == '') $m_field_required = 'n';

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('members.is_field_required')), '40%');
        $r .= Cp::tableCell('', __('cp.yes').'&nbsp;'.Cp::input_radio('m_field_required', 'y', ($m_field_required == 'y') ? 1 : '').'&nbsp;'.__('cp.no').'&nbsp;'.Cp::input_radio('m_field_required', 'n', ($m_field_required == 'n') ? 1 : ''), '60%');
        $r .= '</tr>'.PHP_EOL;


        // ------------------------------------
        //  Is field public?
        // ------------------------------------

        if ($m_field_public == '') $m_field_public = 'y';

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('members.is_field_public')).Cp::quickDiv('littlePadding', __('members.is_field_public_cont')), '40%');
        $r .= Cp::tableCell('', __('cp.yes').'&nbsp;'.Cp::input_radio('m_field_public', 'y', ($m_field_public == 'y') ? 1 : '').'&nbsp;'.__('cp.no').'&nbsp;'.Cp::input_radio('m_field_public', 'n', ($m_field_public == 'n') ? 1 : ''), '60%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Is field visible in reg page?
        // ------------------------------------

        if ($m_field_reg == '') $m_field_reg = 'n';

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('members.is_field_reg')).Cp::quickDiv('littlePadding', __('members.is_field_public_cont')), '40%');
        $r .= Cp::tableCell('', __('cp.yes').'&nbsp;'.Cp::input_radio('m_field_reg', 'y', ($m_field_reg == 'y') ? 1 : '').'&nbsp;'.__('cp.no').'&nbsp;'.Cp::input_radio('m_field_reg', 'n', ($m_field_reg == 'n') ? 1 : ''), '60%');
        $r .= '</tr>'.PHP_EOL;


        $r .= '</table>'.PHP_EOL;

        $r .= Cp::div('littlePadding');
        $r .= Cp::required(1).BR.BR;

        if ($type == 'edit')
            $r .= Cp::input_submit(__('members.update'));
        else
            $r .= Cp::input_submit(__('cp.submit'));

        $r .= '</div>'.PHP_EOL;

        $r .= '</form>'.PHP_EOL;

        Cp::$title = __('members.edit_member_field');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=profile_fields', __('members.custom_member_fields'))).
                      Cp::breadcrumbItem(__('members.edit_member_field'));
        Cp::$body  = $r;
    }

    // ------------------------------------
    //  Create/update custom fields
    // ------------------------------------
    // This function alters the "member_data" table, adding
    // the new custom fields.
    //-----------------------------------------------------------

    function update_profile_fields()
    {
        if ( ! Session::access('can_admin_members')) {
            return Cp::unauthorizedAccess();
        }

        $fields = [
            "m_field_id",
            "cur_field_name",
            "m_field_name",
            "m_field_label",
            "m_field_description",
            "m_field_order",
            "m_field_width",
            "m_field_type",
            "m_field_maxl",
            "m_field_ta_rows",
            "m_field_list_items",
            "m_field_required",
            "m_field_public",
            "m_field_reg"
        ];


        $input = request()->only($fields);

        // If the $m_field_id variable is present we are editing existing
        $edit = (bool) request()->has('m_field_id');

        // Check for required fields
        if (empty($input['m_field_name'])) {
            $errors[] = __('members.no_field_name');
        }

        if (empty($input['m_field_label'])) {
            $errors[] = __('members.no_field_label');
        }

        // Is the field one of the reserved words?
        if (in_array($input['m_field_name'], Cp::unavailableFieldNames())) {
            $errors[] = __('members.reserved_word');
        }

        // Does field name have invalid characters?
        if ( ! preg_match("#^[a-z0-9\_]+$#i", $input['m_field_name'])) {
            $errors[] = __('members.invalid_characters');
        }

        // Is the field name taken?
        $field_count = DB::table('member_fields')
            ->where('m_field_name', $input['m_field_name'])
            ->count();

        if ($field_count > 0) {
            if ($edit === false) {
                $errors[] = __('members.duplicate_field_name');
            }

            if ($edit === true && $input['m_field_name'] != $input['cur_field_name']) {
                $errors[] = __('members.duplicate_field_name');
            }
        }

        // Are there errors to display?
        if (!empty($errors)) {
            return Cp::errorMessage(implode("\n", $errors));
        }

        if (!empty($input['m_field_list_items'])) {
            $input['m_field_list_items'] = Regex::convert_quotes($input['m_field_list_items']);
        }

        $n = 100;

        $f_type = 'text';

        if ($input['m_field_type'] == 'text') {
            if ( !empty($input['m_field_maxl']) && is_numeric($input['m_field_maxl'])) {
                $n = '100';
            }

            $f_type = 'string';
        }

        if ($edit === true) {

            if ($input['cur_field_name'] !== $input['m_field_name']) {

                Schema::table('member_data', function ($table) use ($input) {
                    $table->renameColumn('m_field_'.$input['cur_field_name'], 'm_field_'.$input['m_field_name']);
                });
            }

            // ALTER
            Schema::table('member_data', function($table) use ($input, $f_type, $n)
            {
                if ($f_type == 'string') {
                    $table->string('m_field_'.$input['m_field_name'], $n)->change();
                } else {
                    $table->text('m_field_'.$input['m_field_name'])->change();
                }
            });

            unset($input['cur_field_name']);

            DB::table('member_fields')
                ->where('m_field_id', $input['m_field_id'])
                ->update($input);
        }

        if ($edit === false) {
            if (empty($input['m_field_order'])) {
                $total = DB::table('member_fields')->count() + 1;

                $input['m_field_order'] = $total;
            }

            unset($input['m_field_id']); // insure empty
            unset($input['cur_field_name']);

            $field_id = DB::table('member_fields')->insertGetId($input);

            // Add Field
            Schema::table('member_data', function($table) use ($input, $f_type, $n)
            {
                if ($f_type == 'string') {
                    $table->string('m_field_'.$input['m_field_name'], $n);
                } else {
                    $table->text('m_field_'.$input['m_field_name']);
                }
            });
        }

        // Insure every member has member data row?
        $query = DB::table('members')
            ->leftJoin('member_data', 'members.member_id', '=', 'member_data.member_id')
            ->whereNull('member_data.member_id')
            ->select('members.member_id')
            ->get();

        foreach ($query as $row)
        {
            DB::table('member_data')->insert(['member_id' => $row->member_id]);
        }

        return $this->custom_profile_fields();
    }


    // ------------------------------------
    //  Delete field confirm
    // ------------------------------------
    // Warning message if you try to delete a custom profile field
    //-----------------------------------------------------------

    function delete_profile_field_conf()
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }

        if ( ! $m_field_id = Request::input('m_field_id'))
        {
            return false;
        }

        $query = DB::table('member_fields')
            ->where('m_field_id', $m_field_id)
            ->select('m_field_label')
            ->first();

        Cp::$title = __('members.delete_field');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=profile_fields', __('members.custom_member_fields'))).
                      Cp::breadcrumbItem(__('members.edit_member_field'));

        Cp::$body = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=delete_field'.AMP.'m_field_id='.$m_field_id))
                    .Cp::input_hidden('m_field_id', $m_field_id)
                    .Cp::quickDiv('alertHeading', __('members.delete_field'))
                    .Cp::div('box')
                    .Cp::quickDiv('littlePadding', '<b>'.__('members.delete_field_confirmation').'</b>')
                    .Cp::quickDiv('littlePadding', '<i>'.$query->m_field_label.'</i>')
                    .Cp::quickDiv('alert', BR.__('members.cp.action_can_not_be_undone'))
                    .Cp::quickDiv('littlePadding', BR.Cp::input_submit(__('cp.delete')))
                    .'</div>'.PHP_EOL
                    .'</form>'.PHP_EOL;
    }

    // ------------------------------------
    //  Delete member profile field
    // ------------------------------------

    function delete_profile_field()
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }

        if ( ! $m_field_id = Request::input('m_field_id'))
        {
            return false;
        }

        $query = DB::table('member_fields')
            ->where('m_field_id', $m_field_id)
            ->select('m_field_name', 'm_field_label', 'm_field_id')
            ->first();

        if (!$query) {
            return false;
        }

        // Drop Column
        Schema::table('member_data', function($table) use ($query)
        {
            $table->dropColumn('m_field_'.$query->m_field_name);
        });

        DB::table('member_fields')->where('m_field_id', $query->m_field_id)->delete();

        Cp::log(__('members.profile_field_deleted').'&nbsp;'.$query->m_field_label);

        return $this->custom_profile_fields();
    }

    // ------------------------------------
    //  Edit field order
    // ------------------------------------

    function edit_field_order_form()
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }

        $query = DB::table('member_fields')
            ->orderBy('m_field_order')
            ->select('m_field_label', 'm_field_name', 'm_field_order')
            ->get();

        if ($query->count() == 0) {
            return false;
        }

        $r  = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=update_field_order'));

        $r .= Cp::table('tableBorder', '0', '10', '100%');

        $r .= Cp::td('tableHeading', '', '3').
        __('members.edit_field_order').
        '</td>'.PHP_EOL.
        '</tr>'.PHP_EOL;

        foreach ($query as $row)
        {
            $r .= '<tr>'.PHP_EOL;
            $r .= Cp::tableCell('', $row->m_field_label);
            $r .= Cp::tableCell('', Cp::input_text($row->m_field_name, $row->m_field_order, '4', '3', 'input', '30px'));
            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        $r .= Cp::quickDiv('paddingTop', Cp::input_submit(__('members.update')));

        $r .= '</form>'.PHP_EOL;

        Cp::$title = __('members.edit_field_order');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=profile_fields', __('members.custom_member_fields'))).
                      Cp::breadcrumbItem(__('members.edit_field_order'));

        Cp::$body  = $r;
    }




    // ------------------------------------
    //  Update field order
    // ------------------------------------
    // This function receives the field order submission
    //-----------------------------------------------------------

    function update_field_order()
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }

        foreach ($_POST as $key => $val)
        {
            DB::table('member_fields')
                ->where('m_field_name' , $key)
                ->update(['m_field_order' => $val]);
        }

        return $this->custom_profile_fields();
    }


    // ------------------------------------
    //  Member search form
    // ------------------------------------

    function member_search_form($message = '')
    {
        Cp::$body  = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=do_member_search'));

        Cp::$body .= Cp::quickDiv('tableHeading', __('members.member_search'));

        if ($message != '') {
            Cp::$body .= Cp::quickDiv('successMessage', $message);
        }

        Cp::$body .= Cp::div('box');

        Cp::$body .= Cp::itemgroup(
                                        __('account.email'),
                                        Cp::input_text('email', '', '35', '100', 'input', '300px')
                                     );

        Cp::$body .= Cp::itemgroup(
                                        __('account.screen_name'),
                                        Cp::input_text('screen_name', '', '35', '100', 'input', '300px')
                                     );

        Cp::$body .= Cp::itemgroup(
                                        __('account.url'),
                                        Cp::input_text('url', '', '35', '100', 'input', '300px')
                                     );

        Cp::$body .= Cp::itemgroup(
                                        __('account.ip_address'),
                                        Cp::input_text('ip_address', '', '35', '100', 'input', '300px')
                                     );

        Cp::$body .= Cp::itemgroup(
                                        Cp::quickDiv('defaultBold', __('admin.member_group'))
                                     );

        // Member group select list
        $query = DB::table('member_groups')
                ->select('group_id', 'group_name')
                ->orderBy('group_name')
                ->get();

        Cp::$body .= Cp::input_select_header('group_id');

        Cp::$body .= Cp::input_select_option('any', __('members.any'));

        foreach ($query as $row)
        {
            Cp::$body.= Cp::input_select_option($row->group_id, $row->group_name);
        }

        Cp::$body .= Cp::input_select_footer();

        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= Cp::quickDiv('paddingTop', Cp::input_submit(__('cp.submit')));

        Cp::$body .= '</form>'.PHP_EOL;

        Cp::$title = __('members.member_search');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(__('members.member_search'));
    }

    // ------------------------------------
    //  Member search
    // ------------------------------------

    function do_member_search()
    {
        $pageurl = BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=do_member_search';

        $custom = false;

        // ------------------------------------
        //  Homepage source?
        // ------------------------------------

        // Since we allow a simplified member search field to be displayed
        // on the Control Panel homepage, we need to set the proper POST variable

        if (isset($_POST['criteria']))
        {
            if ($_POST['keywords'] == '')
            {
                return redirect('?');
            }

            if (substr($_POST['criteria'], 0, 11) == 'm_field_' && is_numeric(substr($_POST['criteria'], 11)))
            {
                $custom = true;
            }

            $_POST[$_POST['criteria']] = $_POST['keywords'];

            unset($_POST['keywords']);
            unset($_POST['criteria']);
        }
        // Done...

        // ------------------------------------
        //  Parse the GET or POST request
        // ------------------------------------

        if ($Q = Request::input('Q')) {
            $Q = base64_decode(urldecode($Q));
        } else {
            foreach (array('screen_name', 'email', 'url', 'ip_address') as $pval) {
                if ( ! isset($_POST[$pval])) {
                    $_POST[$pval] = '';
                }
            }

            if ($_POST['screen_name']   == '' &&
                $_POST['email']         == '' &&
                $_POST['url']           == '' &&
                $_POST['ip_address']    == '' &&
                $custom === false
            )
            {
                return redirect('?C=Administration&M=members&P=member_search');
            }

            $search_query = DB::table('members')
                ->select('member_id', 'screen_name', 'email', 'join_date', 'ip_address', 'group_name')
                ->join('member_groups', 'member_groups.group_id', '=', 'members.group_id');

            foreach ($_POST as $key => $val)
            {
                if ($key == 'group_id') {
                    if ($val != 'any') {
                        $search_query->where('member_groups.group_id', $_POST['group_id']);
                    }
                }
                elseif ($key != 'exact_match' && $val != '') {
                    if (isset($_POST['exact_match'])) {
                        $search_query->where('members.'.$key, '=', $val);
                    } else {
                        $search_query->where('members.'.$key, 'LIKE', '%'.$val.'%');
                    }
                }
            }
        }

        $pageurl .= AMP.'Q='.urlencode(base64_encode($Q));

        if ($custom === TRUE) {
            $query->join('member_data', 'member_data.member_id', '=', 'members.member_id');
        }

        // No result?  Show the "no results" message
        $query = clone $search_query;
        $total_count = $query->count();

        if ($total_count == 0)  {
            return $this->member_search_form(Cp::quickDiv('littlePadding', Cp::quickDiv('alert', __('members.no_search_results'))));
        }

        // Get the current row number and add the LIMIT clause to the SQL query
        if ( ! $rownum = Request::input('rownum')) {
            $rownum = 0;
        }

        $search_query->offset($rownum)->limit($this->perpage);

        // Run the query
        $query = clone $search_query;
        $query = $query->get();

        // Build the table heading
        $right_links[] = [
            BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=member_search',
            __('members.new_member_search')
        ];

        $r  = Cp::header(__('admin.view_members'), $right_links);

        // "select all" checkbox
        $r .= Cp::toggle();

        Cp::$body_props .= ' onload="magic_check()" ';

        $r .= Cp::magicCheckboxesJavascript();

        // Declare the "delete" form

        $r .= Cp::formOpen(
                                array(
                                        'action' => 'C=Administration'.AMP.'M=members'.AMP.'P=mbr_del_conf',
                                        'name'  => 'target',
                                        'id'    => 'target'
                                    )
                            );

        $r .= Cp::table('tableBorder', '0', '', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableCell('tableHeadingAlt', __('account.screen_name')).
              Cp::tableCell('tableHeadingAlt', __('account.email')).
              Cp::tableCell('tableHeadingAlt', __('account.ip_address')).
              Cp::tableCell('tableHeadingAlt', __('account.join_date')).
              Cp::tableCell('tableHeadingAlt', __('admin.member_group')).
              Cp::tableCell('tableHeadingAlt', Cp::input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"")).
              '</tr>'.PHP_EOL;


        // Loop through the query result and write each table row

        $i = 0;

        foreach($query as $row)
        {
            $r .= '<tr>'.PHP_EOL;

            // Screen name
            $r .= Cp::tableCell('', Cp::anchor(
                                                  BASE.'?C=account'.AMP.'id='.$row->member_id,
                                                  '<b>'.$row->screen_name.'</b>'
                                                ));

            // Email

            $r .= Cp::tableCell('',
                                    Cp::mailto($row->email, $row->email)
                                    );
            // IP Address

            $r .= Cp::td('');
            $r .= $row->ip_address;
            $r .= '</td>'.PHP_EOL;

            // Join date

            $r .= Cp::td('').
                  Localize::format('%Y', $row->join_date).'-'.
                  Localize::format('%m', $row->join_date).'-'.
                  Localize::format('%d', $row->join_date).
                  '</td>'.PHP_EOL;

            // Member group

            $r .= Cp::td('');

            $r .= $row->group_name;

            $r .= '</td>'.PHP_EOL;

            // Delete checkbox

            $r .= Cp::tableCell('', Cp::input_checkbox('toggle[]', $row->member_id, '', " id='delete_box_".$row->member_id."'"));

            $r .= '</tr>'.PHP_EOL;

        } // End foreach


        $r .= '</table>'.PHP_EOL;

        $r .= Cp::table('', '0', '', '98%');
        $r .= '<tr>'.PHP_EOL.
              Cp::td();

        // Pass the relevant data to the paginate class so it can display the "next page" links

        $r .=  Cp::div('crumblinks').
               Cp::pager(
                            $pageurl,
                            $total_count,
                            $this->perpage,
                            $rownum,
                            'rownum'
                          ).
              '</div>'.PHP_EOL.
              '</td>'.PHP_EOL.
              Cp::td('defaultRight');

        // Delete button

        $r .= Cp::input_submit(__('cp.delete')).
              '</td>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        // Table end

        $r .= '</table>'.PHP_EOL.
              '</form>'.PHP_EOL;

        Cp::$title = __('members.member_search');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(__('members.member_search'));
        Cp::$body  = $r;
    }


    // ------------------------------------
    //  IP Search Form
    // ------------------------------------

    function ip_search_form($message = '')
    {
        if ( ! Session::access('can_admin_members')) {
            return Cp::unauthorizedAccess();
        }

        $ip = (Request::input('ip_address') !== null) ? str_replace('_', '.',Request::input('ip_address')) : '';

        Cp::$body  = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=do_ip_search'));

        Cp::$body .= Cp::quickDiv('tableHeading', __('admin.ip_search'));

        if ($message != '') {
            Cp::$body .= Cp::quickDiv('successMessage', $message);
        }

        Cp::$body .= Cp::div('box');

        if (Request::input('error') == 2)
        {
            Cp::$body .= Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('members.ip_search_no_results')));
        }
        elseif (Request::input('error') == 1)
        {
            Cp::$body .= Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('members.ip_search_too_short')));
        }


        Cp::$body .= Cp::quickDiv('littlePadding', __('members.ip_search_instructions'));

        Cp::$body .= Cp::itemgroup(
                                        __('cp.ip_address'),
                                        Cp::input_text('ip_address', $ip, '35', '100', 'input', '300px')
                                     );

        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= Cp::quickDiv('paddingTop', Cp::input_submit(__('cp.submit')));

        Cp::$body .= '</form>'.PHP_EOL;

        Cp::$title = __('members.member_search');

        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(__('admin.ip_search'));
    }


    // ------------------------------------
    //  IP Search
    // ------------------------------------

    function do_ip_search($message = '')
    {
        if ( ! Session::access('can_admin_members')) {
            return Cp::unauthorizedAccess();
        }

        $ip = str_replace('_', '.', Request::input('ip_address'));
        $url_ip = str_replace('.', '_', $ip);

        if ($ip == '') {
            return redirect('?C=Administration&M=members&P=ip_search');
        }

        if (strlen($ip) < 3) {
            return redirect('?C=Administration&M=members&P=ip_search&error=1&ip_address='.$url_ip);
        }

        // ------------------------------------
        //  Set some defaults for pagination
        // ------------------------------------

        $w_page = (Request::input('w_page') == null) ? 0 : Request::input('w_page');
        $m_page = (Request::input('m_page') == null) ? 0 : Request::input('m_page');
        $c_page = (Request::input('c_page') == null) ? 0 : Request::input('c_page');
        $t_page = (Request::input('t_page') == null) ? 0 : Request::input('t_page');
        $p_page = (Request::input('p_page') == null) ? 0 : Request::input('p_page');

        $page_url = BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=do_ip_search'.AMP.'ip_address='.$url_ip;

        $r = '';

        // ------------------------------------
        //  Find Member Accounts with IP
        // ------------------------------------

        $base_query = DB::table('members')
            ->where('ip_address', 'LIKE', '%'.$ip.'%')
            ->orderBy('screen_name', 'desc');

        // Run the query the first time to get total for pagination
        $count_query = clone $base_query;
        $total = $count_query->count();

        if ($total > 0)
        {
            if ($total > 10) {
                $base_query->offset($m_page)->limit(10);
            }

            $base_query->select('member_id', 'screen_name', 'ip_address', 'email', 'join_date');

            // Run the full query
            $query = $base_query->get();

            $r .= Cp::tableOpen(array('class' => 'tableBorder', 'width' => '100%'));
            $r .= Cp::tableRow(array(array('text' => __('members.member_accounts'),'class'   => 'tableHeading', 'colspan' => '4' )));
            $r .= Cp::tableRow([
                    ['text' => __('account.screen_name'), 'class' => 'tableHeadingAlt', 'width' => '50%'],
                    ['text' => __('account.email'), 'class' => 'tableHeadingAlt', 'width' => '30%'],
                    ['text' => __('cp.ip_address'), 'class' => 'tableHeadingAlt', 'width' => '20%']
                ]
            );
            $i = 0;
            foreach($query as $row)
            {
                                $r .= Cp::tableRow(array(
                                            array('text' => Cp::anchor(BASE.'?C=account'.AMP.'id='.$row->member_id, '<b>'.$row->screen_name.'</b>')),
                                            array('text' => Cp::mailto($row->email, $row->email)),
                                            array('text' => $row->ip_address)
                                            )
                                    );
            } // End foreach

            $r .= '</table>'.PHP_EOL;

            if ($total > 10)
            {
                $r .=  Cp::div('crumblinks').
                       Cp::pager(
                            $page_url.AMP.'w_page='.$w_page.AMP.'c_page='.$c_page,
                            $total,
                            10,
                            $m_page,
                            'm_page'
                        ).
                      '</div>'.PHP_EOL;
            }
        }

        // ------------------------------------
        //  Find Weblog Entries with IP
        // ------------------------------------

        $base_query = DB::table('weblog_entries as t')
            ->join('weblog_entry_data as d', 'd.entry_id', '=', 't.entry_id')
            ->join('members as m', 'm.member_id', '=', 't.author_id')
            ->orderBy('entry_id', 'desc')
            ->where('t.ip_address', 'LIKE', '%'.$ip.'%');

        // Run the query the first time to get total for pagination

        $total_query = clone($base_query);
        $total = $total_query->count();

        if ($total > 0)
        {
            if ($total > 10) {
                $base_query->offset($w_page)->limit(10);
            }

            // Run the full query
            $base_query->select(
                's.site_name', 't.entry_id', 't.weblog_id', 'd.title',
                't.ip_address', 'm.member_id', 'm.screen_name', 'm.email'
            );

            $query = $base_query->get();

            $r .= Cp::quickDiv('defaultSmall', BR);
            $r .= Cp::tableOpen(array('class' => 'tableBorder', 'width' => '100%'));
            $r .= Cp::tableRow(
                [
                    [
                        'text' => __('members.weblog_entries'),
                        'class'   => 'tableHeading',
                        'colspan' => '5'
                    ]
                ]
            );

            $r .= Cp::tableRow([
                    ['text' => __('members.title'), 'class' => 'tableHeadingAlt', 'width' => '40%'],
                    ['text' => __('members.site'), 'class' => 'tableHeadingAlt', 'width' => '15%'],
                    ['text' => __('account.screen_name'), 'class' => 'tableHeadingAlt', 'width' => '15%'],
                    ['text' => __('account.email'), 'class' => 'tableHeadingAlt', 'width' => '20%'],
                    ['text' => __('cp.ip_address'), 'class' => 'tableHeadingAlt', 'width' => '10%']
                ]
            );

            $i = 0;
            foreach($query as $row)
            {
                $r .= Cp::tableRow([
                    ['text' => Cp::anchor(BASE.'?C=edit'.AMP.'M=view_entry'.AMP.'weblog_id='.$row->weblog_id.AMP.'entry_id='.$row->entry_id, '<b>'.$row->title.'</b>')],
                    ['text' => $row->site_name],
                    ['text' => Cp::anchor(BASE.'?C=account'.AMP.'id='.$row->member_id, '<b>'.$row->screen_name.'</b>')],
                    ['text' => Cp::mailto($row->email, $row->email)],
                    ['text' => $row->ip_address]
                    ]
                );

            } // End foreach

            $r .= '</table>'.PHP_EOL;

            if ($total > 10)
            {
                $r .=  Cp::div('crumblinks').
                       Cp::pager(
                            $page_url.AMP.'m_page='.$m_page.AMP.'c_page='.$c_page,
                            $total,
                            10,
                            $w_page,
                            'w_page'
                          ).
                      '</div>'.PHP_EOL;
            }
        }

        // ------------------------------------
        //  Were there results?
        // ------------------------------------

        if ($r == '') {
            return redirect('?C=Administration&M=members&P=ip_search&error=2&ip_address='.$url_ip);
        }


        Cp::$body  = $r;
        Cp::$title = __('admin.ip_search');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(__('admin.ip_search'));
    }

    // ------------------------------------
    //  View Email Console Logs
    // ------------------------------------

    function email_console_logs($message = '')
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }


        // ------------------------------------
        //  Define base variables
        // ------------------------------------

        $i = 0;


        $row_limit  = 100;
        $paginate   = '';
        $row_count  = 0;

        Cp::$title = __('members.email_console_log');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
                      Cp::breadcrumbItem(__('members.email_console_log'));

        Cp::$body  = Cp::quickDiv('tableHeading', __('members.email_console_log'));

        if ($message != '') {
            Cp::$body .= Cp::quickDiv('successMessage', $message);
        }


        // ------------------------------------
        //  Run Query
        // ------------------------------------

        $query = DB::table('email_console_cache')
            ->orderBy('cache_id', 'desc')
            ->select('cache_id', 'member_id', 'member_name', 'recipient_name', 'cache_date', 'subject');

        $total_query = clone $query;

        if ($total_query->count() == 0)
        {
            if ($message == '') {
                Cp::$body  .=  Cp::quickDiv('box', Cp::quickDiv('highlight', __('members.no_cached_email')));
            }

            return;
        }

        // ------------------------------------
        //  Do we need pagination?
        // ------------------------------------

        if ($total_query->count() > $row_limit)
        {
            $row_count = ( ! Request::input('row')) ? 0 : Request::input('row');

            $url = BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=email_console_logs';

            $paginate = Cp::pager(  $url,
                                      $total_query->count(),
                                      $row_limit,
                                      $row_count,
                                      'row'
                                    );

            $query->offset($row_count)->$limit($row_limit);

            $query = $query->get();
        }

        Cp::$body .= Cp::toggle();

        Cp::$body_props .= ' onload="magic_check()" ';

        Cp::$body .= Cp::magicCheckboxesJavascript();

        Cp::$body .= Cp::formOpen(
                                        array(
                                            'action' => 'C=Administration'.AMP.'M=members'.AMP.'P=delete_email_console',
                                            'name'  => 'target',
                                            'id'    => 'target'
                                            )
                                    );

        Cp::$body .= Cp::table('tableBorder', '0', '0', '100%').
                      '<tr>'.PHP_EOL.
                      Cp::tableCell('tableHeadingAlt',
                                        array(
                                                NBS,
                                                __('members.email_title'),
                                                __('members.from'),
                                                __('members.to'),
                                                __('members.date'),
                                                Cp::input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS
                                              )
                                            ).
              '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Table Rows
        // ------------------------------------

        $row_count++;

        foreach ($query as $row)
        {
            Cp::$body  .=  Cp::tableQuickRow('',
                                    array(
                                            $row_count,

                                            Cp::anchorpop(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=view_email'.AMP.'id='.$row->cache_id.AMP.'Z=1', '<b>'.$row->subject.'</b>', '600', '580'),

                                            Cp::quickSpan('defaultBold', $row->member_name),

                                            Cp::quickSpan('defaultBold', $row->recipient_name),

                                            Localize::createHumanReadableDateTime($row->cache_date),

                                            Cp::input_checkbox('toggle[]', $row->cache_id, '', " id='delete_box_".$row->cache_id."'")

                                          )
                                    );
            $row_count++;
        }

        Cp::$body .= '</table>'.PHP_EOL;


        if ($paginate != '')
        {
            Cp::$body .= Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', $paginate));
        }

        Cp::$body .= Cp::quickDiv('paddingTop', Cp::input_submit(__('cp.delete')));

        Cp::$body .= '</form>'.PHP_EOL;
    }



    // ------------------------------------
    //  View Email
    // ------------------------------------

    function view_email()
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }

        $id = Request::input('id');

        // ------------------------------------
        //  Run Query
        // ------------------------------------

        $query = DB::table('email_console_cache')
            ->where('cache_id', $id)
            ->select('cache_id', 'member_id', 'member_name', 'recipient_name', 'cache_date', 'subject', 'message', 'ip_address')
            ->first();

        if (!$query)
        {
            Cp::$body .= Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('members.no_cached_email')));

            return;
        }

        // ------------------------------------
        //  Render output
        // ------------------------------------

        Cp::$body .= Cp::heading(BR.$query->subject);

        // ------------------------------------
        //  Table
        // ------------------------------------

        Cp::$body .= '<div class="email-message">'.$query->message.'</div>';
        Cp::$body  .= Cp::quickDiv('', BR);
        Cp::$body  .= Cp::table('tableBorderNoBottom', '0', '10', '100%');
        Cp::$body  .= '<tr>'.PHP_EOL;
        Cp::$body  .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('members.from')));
        Cp::$body  .= Cp::tableCell('', Cp::quickSpan('defaultBold', $query->member_name));
        Cp::$body  .= Cp::tableCell('', Cp::quickSpan('defaultBold', $query->ip_address));
        Cp::$body  .= '</tr>'.PHP_EOL;
        Cp::$body  .= '<tr>'.PHP_EOL;
        Cp::$body  .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('members.to')));
        Cp::$body  .= Cp::tableCell('', Cp::quickSpan('defaultBold', $query->recipient_name));
        Cp::$body  .= Cp::tableCell('', Cp::quickSpan('defaultBold', Cp::mailto($query->recipient)));
        Cp::$body  .= '</tr>'.PHP_EOL;
        Cp::$body  .= '</table>'.PHP_EOL;
    }


    // ------------------------------------
    //  Delete Emails
    // ------------------------------------

    function delete_email_console_messages()
    {
        if ( ! Session::access('can_admin_members')) {
            return Cp::unauthorizedAccess();
        }

        if ( ! Request::input('toggle')) {
            return $this->email_console_logs();
        }

        $ids = [];

        foreach ($_POST as $key => $val)
        {
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                $ids[] = $val;
            }
        }

        $query = DB::table('email_console_cache')
            ->whereIn('cache_id', $ids)
            ->delete();

        return $this->email_console_logs(__('members.email_deleted'));
    }

    // ------------------------------------------------------

    /**
     * Clear out caches related to Member Groups
     *
     * @param integer  $group_id The group we are loading this for
     * @return void
     */
    private function clearMemberGroupCache($group_id = null)
    {
        $tags = (is_null($group_id)) ? ['member_groups'] : 'member_group'.$group_id;

        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags($tags)->flush();
        }

        if (!is_null($group_id)) {

            // Session::fetchSpecialPreferencesCache()
            $keys[] = 'cms.member_group:'.$group_id.'.specialPreferences';
        }

        foreach($keys as $key) {
            Cache::forget($key);
        }
    }
}
