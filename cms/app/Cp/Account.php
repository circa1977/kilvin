<?php

namespace Groot\Cp;

use Cp;
use DB;
use Site;
use Hash;
use Request;
use Carbon\Carbon;
use Groot\Core\ValidateAccount;
use Groot\Core\Session;
use Groot\Models\Member;
use Groot\Core\Localize;

class Account
{
    public $screen_name = '';

    // ------------------------------------
    //  Constructor
    // ------------------------------------

    function __construct()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        // ------------------------------------
        //  Fetch screen name
        // ------------------------------------

        $query = DB::table('members')
            ->where('member_id', $id)
            ->select('screen_name')
            ->first();

        if (!$query)
        {
            return Cp::unauthorizedAccess();
        }

        $this->screen_name = $query->screen_name;
    }

    // --------------------------------------------------------------------

    /**
    * Request Handler
    *
    * @return mixed
    */
    public function run()
    {
        switch(Request::input('M'))
        {
            case 'edit_profile'             : return $this->member_profile_form();
                break;
            case 'update_profile'           : return $this->update_member_profile();
                break;
            case 'email_password_form'       : return $this->email_password_form();
                break;
            case 'update_email_password'    : return $this->update_email_password();
                break;
            case 'notification_settings'     : return $this->notification_settings_form();
                break;
            case 'update_notification_settings' : return $this->update_notification_settings();
                break;
            case 'htmlbuttons'              : return $this->htmlbuttons();
                break;
            case 'update_htmlbuttons'       : return $this->update_htmlbuttons();
                break;
            case 'homepage'                 : return $this->homepage_builder();
                break;
            case 'set_homepage_prefs'       : return $this->set_homepage_prefs();
                break;
            case 'set_homepage_order'       : return $this->set_homepage_order();
                break;
            case 'theme'                    : return $this->theme_builder();
                break;
            case 'save_theme'               : return $this->save_theme();
                break;
            case 'edit_photo'               : return $this->edit_photo();
                break;
            case 'update_photo'             : return $this->upload_photo();
                break;
            case 'notepad'                  : return $this->notepad();
                break;
            case 'notepad_update'           : return $this->notepad_update();
                break;
            case 'administration'           : return $this->administrative_options();
                break;
            case 'administration_update'    : return $this->administration_update();
                break;
            case 'quicklinks'               : return $this->quick_links_form();
                break;
            case 'quicklinks_update'        : return $this->quick_links_update(FALSE);
                break;
            case 'tab_manager'              : return $this->tab_manager();
                break;
            case 'tab_manager_update'       : return $this->quick_links_update(TRUE);
                break;
            case 'bulletin_board'           : return $this->bulletin_board();
                break;
            case 'member_search'            : return $this->member_search();
                break;
            case 'do_member_search'         : return $this->do_member_search();
                break;
            default                         : return $this->account_wrapper();
                break;
        }
    }




    // ------------------------------------
    //  Validate user and get the member ID number
    // ------------------------------------

    function auth_id()
    {
        // Who's profile are we editing?

        $id = ( ! Request::input('id')) ? Session::userdata('member_id') : Request::input('id');

        // Is the user authorized to edit the profile?

        if ($id != Session::userdata('member_id'))
        {
            if ( ! Session::access('can_admin_members'))
            {
                return false;
            }

            // Only Super Admins can view Super Admin profiles
            $group_id = DB::table('members')
                ->where('member_id', $id)
                ->value('group_id');

            if (!$group_id) {
                return false;
            }

            if ($group_id == 1 AND Session::userdata('group_id') != 1) {
                return false;
            }
        }

        if ( ! is_numeric($id)) {
            return false;
        }

        return $id;
    }

    // ------------------------------------
    //  Error Wrapper
    // ------------------------------------

    function _error_message($msg)
    {
        return Cp::errorMessage(__('account.'.$msg));
    }


    // ------------------------------------
    //  Left side menu
    // ------------------------------------

    function nav($path = '', $text = '')
    {
        if ($path == '') {
            return false;
        }

        if ($text == '') {
            return false;
        }

        return Cp::quickDiv('navPad', Cp::anchor(BASE.'?C=account'.AMP.'M='.$path, __($text)));
    }

    // ------------------------------------
    //  "My Account" main page wrapper
    // ------------------------------------

    function account_wrapper($title = '', $crumb = '', $content = '')
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        // Default page title if not supplied

        if ($title == '')
        {
            $title = __('cp.my_account');
        }

        // Default bread crumb if not supplied

        if ($crumb == '')
        {
            if ($id != Session::userdata('member_id'))
            {
                $crumb = __('account.user_account');
            }
            else
            {
                $crumb = __('cp.my_account');
            }
        }

        // Default content if not supplied

        if ($content == '')
        {
            $content .= $this->profile_homepage();
        }

        // Set breadcrumb and title

        Cp::$title = $title;
        Cp::$crumb = $crumb;

        ob_start();
        ?>
        <script type="text/javascript">
        <!--

        function showhide_menu(which)
        {
            head = which + '_h';
            body = which + '_b';

            if (document.getElementById(head).style.display == "block")
            {
                document.getElementById(head).style.display = "none";
                document.getElementById(body).style.display = "block";
            }
            else
            {
                document.getElementById(head).style.display = "block";
                document.getElementById(body).style.display = "none";
            }
        }

        //-->
        </script>

        <?php

        $buffer = ob_get_contents();
        ob_end_clean();
        Cp::$body = $buffer;


        // Build the output

        $expand     = '<img src="'.PATH_CP_IMG.'expand_white.gif" border="0"  width="10" height="10" alt="Expand" />&nbsp;&nbsp;';
        $collapse   = '<img src="'.PATH_CP_IMG.'collapse_white.gif" border="0"  width="10" height="10" alt="Collapse" />&nbsp;&nbsp;';

        Cp::$body  .=  Cp::table('', '0', '', '100%').
        				'<tr class="no-background">'.
                        Cp::td('', '240px', '', '', 'top');

        Cp::$body  .=  Cp::quickDiv('tableHeading', __('account.current_member').NBS.$this->screen_name);


        $prof_state =
            (in_array(Request::input('M'), ['edit_profile', 'edit_photo', 'notification_settings', 'email_password_form'])) ?
            true :
            false;


        Cp::$body  .= '<div id="menu_profile_h" style="display: '.(($prof_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';
        $js = ' onclick="showhide_menu(\'menu_profile\');return false;" ';
        Cp::$body .= Cp::div();
        Cp::$body .= "<div class='tableHeadingAlt pointer' id='prof' ".$js.">";
        Cp::$body .= $expand.__('account.personal_settings');
        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= '</div>'.PHP_EOL;

        Cp::$body .= '<div id="menu_profile_b" style="display: '.(($prof_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';

        $js = ' onclick="showhide_menu(\'menu_profile\');return false;" ';
        Cp::$body .= Cp::div();
        Cp::$body .= "<div class='tableHeadingAlt pointer' id='prof2' ".$js.">";
        Cp::$body .= $collapse.__('account.personal_settings');
        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body  .=  Cp::div('profileMenuInner').
                        $this->nav('email_password_form'.AMP.'id='.$id, 'account.email_and_password').
                        $this->nav('edit_profile'.AMP.'id='.$id, 'account.edit_profile').
                        $this->nav('edit_photo'.AMP.'id='.$id, 'members.edit_photo').
                        $this->nav('notification_settings'.AMP.'id='.$id, 'account.notification_settings').
                    '</div>'.PHP_EOL;
        Cp::$body  .= '</div>'.PHP_EOL;
        Cp::$body  .= '</div>'.PHP_EOL;




        // Customize Control Panel



        $cp_state = (in_array(Request::input('M'), array('homepage', 'set_homepage_order', 'theme', 'tab_manager'))) ? TRUE : false;

        Cp::$body  .= '<div id="menu_cp_h" style="display: '.(($cp_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';
        $js = ' onclick="showhide_menu(\'menu_cp\');return false;" ';
        Cp::$body .= Cp::div();
        Cp::$body .= "<div class='tableHeadingAlt pointer' id='mcp' ".$js.">";
        Cp::$body .= $expand.__('account.customize_cp');
        Cp::$body  .= '</div>'.PHP_EOL;
        Cp::$body  .= '</div>'.PHP_EOL;
        Cp::$body  .= '</div>'.PHP_EOL;

        Cp::$body .= '<div id="menu_cp_b" style="display: '.(($cp_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';

        $js = ' onclick="showhide_menu(\'menu_cp\');return false;" ';
        Cp::$body .= Cp::div();
        Cp::$body .= "<div class='tableHeadingAlt pointer' id='mcp2' ".$js.">";
        Cp::$body .= $collapse.__('account.customize_cp');
        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= Cp::div('profileMenuInner');
        Cp::$body .= $this->nav('homepage'.AMP.'id='.$id, 'account.cp_homepage');
        Cp::$body .= $this->nav('theme'.AMP.'id='.$id, 'account.cp_theme');
        Cp::$body .= $this->nav('tab_manager'.AMP.'id='.$id, 'account.tab_manager');
        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body  .= '</div>'.PHP_EOL;
        Cp::$body  .= '</div>'.PHP_EOL;




        // Extras


        $ex_state = (in_array(Request::input('M'), ['quicklinks', 'notepad'])) ? true : false;

        Cp::$body  .= '<div id="menu_ex_h" style="display: '.(($ex_state == true) ? 'none' : 'block').'; padding:0; margin: 0;">';
        $js = ' onclick="showhide_menu(\'menu_ex\');return false;" ';
        Cp::$body .= Cp::div();
        Cp::$body .= "<div class='tableHeadingAlt pointer' id='exx' ".$js.">";
        Cp::$body .= $expand.__('account.utilities');
        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= '</div>'.PHP_EOL;


        Cp::$body .= '<div id="menu_ex_b" style="display: '.(($ex_state == true) ? 'block' : 'none').'; padding:0; margin: 0;">';
        $js = ' onclick="showhide_menu(\'menu_ex\');return false;" ';
        Cp::$body .= Cp::div();
        Cp::$body .= "<div class='tableHeadingAlt pointer' id='exx2' ".$js.">";
        Cp::$body .= $collapse.__('account.utilities');
        Cp::$body .= '</div>'.PHP_EOL;

        Cp::$body .= Cp::div('profileMenuInner');
        Cp::$body .= $this->nav('quicklinks'.AMP.'id='.$id, 'account.quick_links');
        Cp::$body .= $this->nav('notepad'.AMP.'id='.$id, 'account.notepad');
        Cp::$body .= '</div>'.PHP_EOL;

        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= '</div>'.PHP_EOL;








        if (Session::access('can_admin_members'))
        {
            $ad_state = (in_array(Request::input('M'), array('administration'))) ? TRUE : false;
            Cp::$body  .= '<div id="menu_ad_h" style="display: '.(($ad_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';
            $js = ' onclick="showhide_menu(\'menu_ad\');return false;" ';
            Cp::$body .= Cp::div();
            Cp::$body .= "<div class='tableHeadingAlt pointer' id='adx' ".$js.">";
            Cp::$body .= $expand.__('account.administrative_options');
            Cp::$body .= '</div>'.PHP_EOL;
            Cp::$body .= '</div>'.PHP_EOL;
            Cp::$body .= '</div>'.PHP_EOL;

            Cp::$body .= '<div id="menu_ad_b" style="display: '.(($ad_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';

            $js = ' onclick="showhide_menu(\'menu_ad\');return false;" ';
            Cp::$body .= Cp::div();
            Cp::$body .= "<div class='tableHeadingAlt pointer' id='adx2' ".$js.">";
            Cp::$body .= $collapse.__('account.administrative_options');
            Cp::$body .= '</div>'.PHP_EOL;
            Cp::$body .= Cp::div('profileMenuInner');
            Cp::$body .= $this->nav('administration'.AMP.'id='.$id, 'account.member_preferences');

            if ($id != Session::userdata('member_id'))
            {
                Cp::$body .= Cp::quickDiv('navPad', Cp::anchor(BASE.'?C=communicate'.AMP.'M=email_mbr'.AMP.'mid='.$id, __('account.member_email')));
            }

            if ($id != Session::userdata('member_id') &&    Site::config('req_mbr_activation') == 'email' && Session::access('can_admin_members'))
            {
                $group_id = DB::table('members')
                    ->where('member_id', $id)
                    ->value('group_id');

                if ($group_id == '4')
                {
                    Cp::$body .= Cp::quickDiv('navPad', Cp::anchor(BASE.'?C=Administration'.
                                                                         AMP.'M=members'.
                                                                         AMP.'P=resend_act_email'.
                                                                         AMP.'mid='.$id,
                                                                    __('account.resend_activation_email')));
                }
            }

            if (Session::userdata('group_id') == 1 && $id != Session::userdata('member_id'))
            {
                Cp::$body .= Cp::quickDiv('navPad', Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=login_as_member'.AMP.'mid='.$id, __('account.login_as_member')));
            }

            if (Session::access('can_delete_members'))
            {
                Cp::$body .= Cp::quickDiv('navPad', Cp::anchor(BASE.'?C=Administration'.AMP.'M=members'.AMP.'P=mbr_del_conf'.AMP.'mid='.$id, __('account.delete_member')));
            }

            Cp::$body .= '</div>'.PHP_EOL;
            Cp::$body .= '</div>'.PHP_EOL;
            Cp::$body .= '</div>'.PHP_EOL;
        }

        Cp::$body .=   '</div>'.PHP_EOL;
        Cp::$body .=   '</div>'.PHP_EOL;

        Cp::$body  .=  '</td>'.PHP_EOL.
                        Cp::td('', '8px', '', '', 'top').NBS.'</td>'.PHP_EOL.
                        Cp::td('', '', '', '', 'top').
                        $content.
                        '</td>'.PHP_EOL.
                        '</tr>'.PHP_EOL.
                        '</table>'.PHP_EOL;
    }

    // ------------------------------------
    //  Profile Homepage
    // ------------------------------------

    function profile_homepage()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $query = DB::table('members')
            ->where('member_id', $id)
            ->first();

        if (!$query) {
            return false;
        }

        foreach ($query as $key => $val) {
            $$key = $val;
        }

        $i = 0;

        $r  = Cp::table('tableBorderNoTop', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::th('', '', '2').__('account.member_stats').NBS.$this->screen_name.'</th>'.PHP_EOL;
              '</tr>'.PHP_EOL;

        $fields = array(
                            'cp.email'              => Cp::mailto($email),
                            'account.join_date'         => Localize::createHumanReadableDateTime($join_date),
                            'account.total_entries'     => $total_entries,
                            'account.last_entry_date'   => ($last_entry_date == 0 OR $last_entry_date == '') ? '--' : Localize::createHumanReadableDateTime($last_entry_date),
                            'account.user_ip_address'   => $ip_address
                        );


        foreach ($fields as $key => $val)
        {

            $r .= '<tr>'.PHP_EOL;
            $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __($key)), '50%');
            $r .= Cp::tableCell('', $val, '50%');
            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        return $r;
    }



    // ------------------------------------
    //  Edit Profile Form
    // ------------------------------------

    function member_profile_form()
    {
        $screen_name    = '';
        $email          = '';
        $url            = '';

        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $title = __('account.edit_profile');

        // ------------------------------------
        //  Fetch profile data
        // ------------------------------------

        $query = DB::table('members')
            ->where('member_id', $id)
            ->first();

        foreach ($query as $key => $val)
        {
            $$key = $val;
        }

        // ------------------------------------
        //  Declare form
        // ------------------------------------

        $r  = Cp::formOpen(array('action' => 'C=account'.AMP.'M=update_profile')).
              Cp::input_hidden('id', $id);

        // ------------------------------------
        //  Birthday Year Menu
        // ------------------------------------

        $bd  = Cp::input_select_header('bday_y');
        $bd .= Cp::input_select_option('', __('account.year'), ($bday_y == '') ? 1 : '');

        for ($i = date('Y', Carbon::now()->timestamp); $i > 1904; $i--)
        {
          $bd .= Cp::input_select_option($i, $i, ($bday_y == $i) ? 1 : '');
        }

        $bd .= Cp::input_select_footer();

        // ------------------------------------
        //  Birthday Month Menu
        // ------------------------------------

        $months = array(
                            '01' => 'January',
                            '02' => 'February',
                            '03' => 'March',
                            '04' => 'April',
                            '05' => 'May',
                            '06' => 'June',
                            '07' => 'July',
                            '08' => 'August',
                            '09' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December'
                        );

        $bd .= Cp::input_select_header('bday_m');
        $bd .= Cp::input_select_option('', __('account.month'), ($bday_m == '') ? 1 : '');

        for ($i = 1; $i < 13; $i++)
        {
          if (strlen($i) == 1)
             $i = '0'.$i;

          $bd .= Cp::input_select_option($i, __($months[$i]), ($bday_m == $i) ? 1 : '');
        }

        $bd .= Cp::input_select_footer();

        // ------------------------------------
        //  Birthday Day Menu
        // ------------------------------------

        $bd .= Cp::input_select_header('bday_d');
        $bd .= Cp::input_select_option('', __('account.day'), ($bday_d == '') ? 1 : '');

        for ($i = 31; $i >= 1; $i--)
        {
          $bd .= Cp::input_select_option($i, $i, ($bday_d == $i) ? 1 : '');
        }

        $bd .= Cp::input_select_footer();

        // ------------------------------------
        //  Build Page Output
        // ------------------------------------

        $i = 0;

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('account.profile_updated'));
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::th('', '', '2');

        $r .= __('account.profile_form');

        $r .= '</th>'.PHP_EOL.
              '</tr>'.PHP_EOL;


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('account.birthday')), '25%');
        $r .= Cp::tableCell('', $bd, '75%');
        $r .= '</tr>'.PHP_EOL;

      if ($url == '') {
          $url = 'https://';
        }

        $fields = [
            'url'           => array('i', '75'),
            'location'      => array('i', '50'),
            'occupation'    => array('i', '80'),
            'interests'     => array('i', '75'),
            'bio'           => array('t', '12')
        ];

        foreach ($fields as $key => $val)
        {

            $align = ($val[0] == 'i') ? '' : 'top';

            $r .= '<tr>'.PHP_EOL;
            $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __($key)), '', $align);

            if ($val[0] == 'i')
            {
                $r .= Cp::tableCell('', Cp::input_text($key, $$key, '40', $val[1], 'input', '100%'));
            }
            elseif ($val[0] == 't')
            {
                $r .= Cp::tableCell('', Cp::input_textarea($key, $$key, $val[1], 'textarea', '100%'));
            }
            $r .= '</tr>'.PHP_EOL;
        }

        // ------------------------------------
        //  Extended profile fields
        // ------------------------------------

        $query = DB::table('member_fields')
            ->orderBy('m_field_order');

        if (Session::userdata('group_id') != 1) {
            $query->where('m_field_public', 'y');
        }

        $query = $query->get();

        if ($query->count() > 0)
        {
            $result = DB::table('member_data')
                ->where('member_id', $id)
                ->first();

            if ($result) {
                foreach ($result as $key => $val) {
                    $$key = $val;
                }
            }

            foreach ($query as $row)
            {
                $field_data = (
                    !isset( $result->{'m_field_'.$row->m_field_name} ))
                    ? ''
                    : $result->{'m_field_'.$row->m_field_name};

                $width = '100%';

                $required  = ($row->m_field_required == 'n') ? '' : Cp::required().NBS;

                // Textarea fieled types

                if ($row->m_field_type == 'textarea')
                {
                    $rows = ( ! isset($row->m_field_ta_rows)) ? '10' : $row->m_field_ta_rows;


                    $r .= '<tr>'.PHP_EOL;
                    $r .= Cp::tableCell('', Cp::quickDiv('defaultBold', $required.$row->m_field_label).Cp::quickDiv('default', $required.$row->m_field_description), '', 'top');
                    $r .= Cp::tableCell('', Cp::input_textarea('m_field_'.$row->m_field_name, $field_data, $rows, 'textarea', $width));
                    $r .= '</tr>'.PHP_EOL;
                }
                else
                {
                    // Text input fields

                    if ($row->m_field_type == 'text')
                    {

                        $r .= '<tr>'.PHP_EOL;
                        $r .= Cp::tableCell('', Cp::quickDiv('defaultBold', $required.$row->m_field_label).Cp::quickDiv('default', $required.$row->m_field_description));
                        $r .= Cp::tableCell('', Cp::input_text('m_field_'.$row->m_field_name, $field_data, '20', '100', 'input', $width));
                        $r .= '</tr>'.PHP_EOL;
                    }

                    // Drop-down lists

                    elseif ($row->m_field_type == 'select')
                    {
                        $d = Cp::input_select_header('m_field_'.$row->m_field_name);

                        foreach (explode("\n", trim($row->m_field_list_items)) as $v)
                        {
                            $v = trim($v);

                            $selected = ($field_data == $v) ? 1 : '';

                            $d .= Cp::input_select_option($v, $v, $selected);
                        }

                        $d .= Cp::input_select_footer();


                        $r .= '<tr>'.PHP_EOL;
                        $r .= Cp::tableCell('', Cp::quickDiv('defaultBold', $required.$row->m_field_label).Cp::quickDiv('default', $required.$row->m_field_description));
                        $r .= Cp::tableCell('', $d);
                        $r .= '</tr>'.PHP_EOL;
                    }
                }
            }
        }


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '2');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('cp.update')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        // END CUSTOM FIELDS

        $r .= '</table>'.PHP_EOL;

        $r.=  '</form>'.PHP_EOL;

        return $this->account_wrapper($title, $title, $r);
    }



    // ------------------------------------
    //  Update member profile
    // ------------------------------------

    function update_member_profile()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        // validate for unallowed blank values
        if (empty($_POST)) {
            return Cp::unauthorizedAccess();
        }

        unset($_POST['id']);

        if ($_POST['url'] == 'https://') {
            $_POST['url'] = '';
        }

        $fields = [
            'bday_y',
            'bday_m',
            'bday_d',
            'url',
            'location',
            'occupation',
            'interests',
            'bio'
        ];

        $data = [];

        foreach ($fields as $val)
        {
            if (isset($_POST[$val]))
            {
                $data[$val] = $_POST[$val];
            }

            unset($_POST[$val]);
        }

        if (is_numeric($data['bday_d']) AND is_numeric($data['bday_m']))
        {
            $year = ($data['bday_y'] != '') ? $data['bday_y'] : date('Y');
            $mdays = Carbon::createFromDate($year, $data['bday_m'], 1)->daysInMonth;

            if ($data['bday_d'] > $mdays)
            {
                $data['bday_d'] = $mdays;
            }
        }

        if (count($data) > 0)
        {
            DB::table('members')
                ->where('member_id', $id)
                ->update($data);
        }

        if (count($_POST) > 0) {
            DB::table('member_data')
                ->where('member_id', $id)
                ->update($_POST);
        }

        return redirect('?C=account&M=edit_profile&id='.$id.'&U=1');
    }

    // ------------------------------------
    //  Email notifications form
    // ------------------------------------

    function notification_settings_form()
    {
        $message   = '';


        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $title = __('account.notification_settings');

        $query = DB::table('members')
            ->where('member_id', $id)
            ->first();

        foreach ($query as $key => $val)
        {
            $$key = $val;
        }

        // Build the output

        $r  = Cp::formOpen(array('action' => 'C=account'.AMP.'M=update_notification_settings')).
              Cp::input_hidden('id', $id).
              Cp::input_hidden('current_email', $query->email);

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('account.settings_updated'));
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::th('', '', '2');

        $r .= $title;

        $r .= '</th>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $checkboxes = ['accept_admin_email', 'accept_user_email', 'notify_by_default', 'smart_notifications'];

        foreach ($checkboxes as $val)
        {
            $r .= '<tr>'.PHP_EOL;
            $r .= Cp::td('', '100%', '2');
            $r .= Cp::input_checkbox($val, 'y', ($$val == 'y') ? 1 : '').NBS.__('account.'.$val);
            $r .= '</td>'.PHP_EOL;
            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '2');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('cp.update')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        return $this->account_wrapper($title, $title, $r);
    }

    // ------------------------------------
    //  Update Email Notification Settings
    // ------------------------------------

    function update_notification_settings()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $data = [
            'accept_admin_email'    => (isset($_POST['accept_admin_email'])) ? 'y' : 'n',
            'accept_user_email'     => (isset($_POST['accept_user_email']))  ? 'y' : 'n',
            'notify_by_default'     => (isset($_POST['notify_by_default']))  ? 'y' : 'n',
            'smart_notifications'    => (isset($_POST['smart_notifications']))  ? 'y' : 'n'
        ];

        DB::table('members')->where('member_id', $id)->update($data);

        return redirect('?C=account&M=notification_settings&id='.$id.'&U=1');
    }

    // ------------------------------------
    //  Username/Password form
    // ------------------------------------

    function email_password_form()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $email  = '';
        $message   = '';

        // ------------------------------------
        //  Show "successful update" message
        // ------------------------------------

        if (Request::input('U')) {
            $message = Cp::quickDiv('successMessage', __('account.settings_updated'));
        }

        $title = __('account.email_and_password');

        // ------------------------------------
        //  Fetch screen_name + email
        // ------------------------------------

        $query = DB::table('members')
            ->where('member_id', $id)
            ->select('email', 'screen_name')
            ->first();

        $email          = $query->email;
        $screen_name    = $query->screen_name;

        // ------------------------------------
        //  Build the output
        // ------------------------------------

        $r  = Cp::formOpen(array('action' => 'C=account'.AMP.'M=update_email_password')).
              Cp::input_hidden('id', $id);

        if (Request::input('U'))
        {
            $r .= $message;
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::th('', '', '2');

        $r .= $title;

        $r .= '</th>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('account.screen_name')), '28%');
        $r .= Cp::tableCell('', Cp::input_text('screen_name', $screen_name, '40', '50', 'input', '100%'), '72%');
        $r .= '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('account.email')), '28%');
        $r .= Cp::tableCell('', Cp::input_text('email', $email, '40', '50', 'input', '100%'), '72%');
        $r .= '</tr>'.PHP_EOL;


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '100%', '2');

        $r .= Cp::div('littlePadding')
             .Cp::quickDiv('itemTitle', __('account.password_change'))
             .Cp::quickDiv('littlePadding', Cp::quickDiv('alert', __('account.password_change_exp')))
             .Cp::quickDiv('highlight', __('account.password_change_requires_login'))
             .'</div>'.PHP_EOL;

        $r .= Cp::quickDiv('itemTitle', __('account.new_password'))
             .Cp::input_pass('password', '', '35', '32', 'input', '300px');

        $r .= Cp::div('littlePadding').
              Cp::quickDiv('itemTitle', __('account.new_password_confirm')).
              Cp::input_pass('password_confirm', '', '35', '32', 'input', '300px').
              '</div>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '100%', '2');

        $r .= Cp::div('paddedWrapper').
              Cp::quickDiv('itemTitle', __('account.existing_password')).
              Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('account.existing_password_exp'))).
              Cp::input_pass('current_password', '', '35', '32', 'input', '310px');

        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '2');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('cp.update')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;

        $r .= '</div>'.PHP_EOL;

        $r.=  '</form>'.PHP_EOL;

        return $this->account_wrapper($title, $title, $r);
    }

    // ------------------------------------
    //  Update email and password
    // ------------------------------------

    function update_email_password()
    {
        if (FALSE === ($id = $this->auth_id())) {
            return Cp::unauthorizedAccess();
        }

        if (empty(Request::all())) {
            return Cp::unauthorizedAccess();
        }

        if (!Request::has('email') or !Request::input('screen_name'))  {
            return redirect('?C=account&M=email_password_form&id='.$id);
        }

        // ------------------------------------
        //  Fetch screen_name + email
        // ------------------------------------

        $query = DB::table('members')
            ->where('member_id', $id)
            ->select('email', 'screen_name')
            ->first();

        $current_email          = $query->email;
        $current_screen_name    = $query->screen_name;

        // ------------------------------------
        //  Validate submitted data
        // ------------------------------------

        $VAL = new ValidateAccount(
            [
                'member_id'             => $id,
                'request_type'          => 'update', // new or update
                'require_password'      => true,
                'email'                 => Request::input('email'),
                'current_email'         => $current_email,
                'screen_name'           => Request::input('screen_name'),
                'current_screen_name'   => $current_screen_name,
                'password'              => Request::input('password'),
                'password_confirm'      => Request::input('password_confirm'),
                'current_password'      => Request::input('current_password')
            ]
        );

        $VAL->validateScreenName();
        $VAL->validateEmail();

        if (Request::has('password')) {
            $VAL->validatePassword();
        }

        // ------------------------------------
        //  Display errors
        // ------------------------------------

        if (count($VAL->errors()) > 0) {
            return Cp::errorMessage($VAL->errors());
        }

        // ------------------------------------
        //  Assign the query data
        // ------------------------------------

        $data['screen_name'] = Request::input('screen_name');
        $data['email'] = Request::input('email');

        // Was a password submitted?
        if (Request::has('password')) {
            $data['password'] = Hash::make(Request::input('password'));
        }

        DB::table('members')
            ->where('member_id', $id)
            ->update($data);

        // Write log file
        Cp::log($VAL->log_msg);

        return redirect('?C=account&M=email_password_form&id='.$id.'&U=1');
    }

    // ------------------------------------
    //  Home Page builder
    // ------------------------------------

    public function homepage_builder()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $prefs = [];

        $select = ['recent_entries', 'site_statistics', 'notepad', 'bulletin_board'];

        if (Session::access('can_access_admin') === TRUE)
        {
            $select[] = 'member_search_form';
            $select[] = 'recent_members';
        }

         $query = DB::table('member_homepage')
            ->where('member_id', $id)
            ->first($select);

        foreach ($select as $f)
        {
            $prefs[$f] = 'n';
        }

        foreach ($query as $key => $val)
        {
            $prefs[$key] = $val;
        }

        $r  = Cp::formOpen(array('action' => 'C=account'.AMP.'M=set_homepage_prefs'));
        $r .= Cp::input_hidden('id', $id);

        if (Request::input('U'))
        {
            $r .= Cp::div('');
            $r .= Cp::quickDiv('successMessage', __('account.preferences_updated'));
            $r .= '</div>'.PHP_EOL;
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableQuickHeader('', __('account.homepage_preferences')).
              Cp::tableQuickHeader('', __('account.left_column')).
              Cp::tableQuickHeader('', __('account.right_column')).
              Cp::tableQuickHeader('', __('account.do_not_show')).
              '</tr>'.PHP_EOL;

        $i = 0;

        foreach ($prefs as $key => $val)
        {
            $r .= '<tr>'.PHP_EOL;
            $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('account.'.$key)));
            $r .= Cp::tableCell('', Cp::input_radio($key, 'l', ($val == 'l') ? 1 : ''));
            $r .= Cp::tableCell('', Cp::input_radio($key, 'r', ($val == 'r') ? 1 : ''));
            $r .= Cp::tableCell('', Cp::input_radio($key, 'n', ($val != 'l' && $val != 'r') ? 1 : ''));
            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '4');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('cp.update')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        $title = __('account.customize_homepage');

        $r .= Cp::anchor(
            BASE.'?C=account'.AMP.'M=set_homepage_order'.AMP.'id='.$id,
            '<strong>'.__('account.set_display_order').'</strong>'
        );

        return $this->account_wrapper($title, $title, $r);
    }


    // ------------------------------------
    //  Set Homepage Display Order
    // ------------------------------------

    function set_homepage_order()
    {
        if (false === ($id = $this->auth_id())) {
            return Cp::unauthorizedAccess();
        }

        $opts = [
            'recent_entries',
            'site_statistics',
            'notepad',
            'bulletin_board'
        ];

        if (Session::access('can_access_admin') === true)
        {
            $opts[] = 'recent_members';
            $opts[] = 'member_search_form';
        }

        $prefs = [];

        $query = DB::table('member_homepage')
            ->where('member_id', $id)
            ->first();

        foreach ($query as $key => $val)
        {
            if (in_array($key, $opts))
            {
                if ($val != 'n')
                {
                    $prefs[$key] = $val;
                }
            }
        }

        $title = __('account.customize_homepage');

        $r  = '';

        $r .= Cp::formOpen(array('action' => 'C=account'.AMP.'M=set_homepage_prefs'));
        $r .= Cp::input_hidden('id', $id);
        $r .= Cp::input_hidden('loc', 'set_homepage_order');

        $r .= Cp::table('', '0', '0', '100%').
              '<tr>'.PHP_EOL.
              Cp::td();

        if (isset($_GET['U']))
        {
            if ($_GET['U'] == 2)
            {
                $r .= Cp::div('');
                $r .= Cp::quickDiv('successMessage', __('account.preferences_updated'));
                $r .= '</div>'.PHP_EOL;
            }
            else
            {
                $r .= Cp::div('');
                $r .= Cp::quickDiv('successMessage', __('account.preferences_updated'));
                //$r .= Cp::heading(NBS.__('account.please_update_order'), 5);
                $r .= '</div>'.PHP_EOL;
            }
        }

        $r .= '</td>'.PHP_EOL
             .'</tr>'.PHP_EOL
             .'</table>'.PHP_EOL;

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableQuickHeader('', __('account.set_display_order')).
              Cp::tableQuickHeader('', __('account.left_column')).
              Cp::tableQuickHeader('', __('account.right_column')).
              '</tr>'.PHP_EOL;

        $i = 0;

        foreach ($prefs as $key => $val)
        {
            if (in_array($key, $opts))
            {
                $r .= '<tr>'.PHP_EOL;
                $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('account.'.$key)));

                if ($val == 'l')
                {
                    $r .= Cp::tableCell('', Cp::input_text($key.'_order', $query->{$key.'_order'}, '10', '3', 'input', '50px'));
                    $r .= Cp::tableCell('', NBS);
                }
                elseif ($val == 'r')
                {
                    $r .= Cp::tableCell('', NBS);
                    $r .= Cp::tableCell('', Cp::input_text($key.'_order', $query->{$key.'_order'}, '10', '3', 'input', '50px'));
                }

                $r .= '</tr>'.PHP_EOL;
            }
        }

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '4');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('cp.update')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        return $this->account_wrapper($title, $title, $r);
    }

    // ------------------------------------
    //  Update Homepage Preferences
    // ------------------------------------

    function set_homepage_prefs()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $loc = ( ! isset($_POST['loc'])) ? '' : $_POST['loc'];

        unset($_POST['loc']);
        unset($_POST['id']);

        if (! Session::access('can_access_admin'))
        {
            unset($_POST['recent_members']);
            unset($_POST['member_search_form']);
        }

        $ref = 1;

        $reset = array(
                            'recent_entries_order'              => 0,
                            'recent_members_order'              => 0,
                            'site_statistics_order'             => 0,
                            'member_search_form_order'          => 0,
                            'notepad_order'                     => 0,
                            'bulletin_board_order'              => 0
                        );

        if ($loc == 'set_homepage_order')
        {
            $ref = 2;

            DB::table('member_homepage')
                ->where('member_id', $id)
                ->update($reset);
        }

        DB::table('member_homepage')
                ->where('member_id', $id)
                ->update($_POST);

        // Decide where to redirect based on the value of the submission
        foreach ($reset as $key => $val)
        {
            $key = str_replace('_order', '', $key);

            if (isset($_POST[$key]) AND ($_POST[$key] == 'l' || $_POST[$key] == 'r'))
            {
                return redirect('?C=account&M=set_homepage_order&id='.$id.'&U='.$ref);
            }
        }

        return redirect('?C=account&M=homepage&id='.$id.'&U='.$ref);
    }

    // ------------------------------------
    //  Theme builder
    // ------------------------------------

    // OK, well, the title is misleading.  Eventually, this will be a full-on
    // theme builder.  Right now it just lets users choose from among pre-defined CSS files

    function theme_builder()
    {
        if (FALSE === ($id = $this->auth_id())) {
            return Cp::unauthorizedAccess();
        }

        $title = __('account.cp_theme');

        $r  = Cp::formOpen(array('action' => 'C=account'.AMP.'M=save_theme'));
        $r .= Cp::input_hidden('id', $id);

        $AD = new Administration;

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('account.preferences_updated'));
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::th('', '', '2');

        $r .= $title;

        $r .= '</th>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $theme = (Session::userdata('cp_theme') == '') ? Site::config('cp_theme') : Session::userdata('cp_theme');

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('account.choose_theme')), '50%');
        $r .= Cp::tableCell('', $AD->fetch_themes($theme), '50%');
        $r .= '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '2');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('cp.update')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        return $this->account_wrapper($title, $title, $r);
    }



    // ------------------------------------
    //  Save Theme
    // ------------------------------------

    function save_theme()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        DB::table('members')
            ->where('member_id', $id)
            ->update(['cp_theme' => $_POST['cp_theme']]);

        return redirect('?C=account&M=theme&id='.$id);
    }

    // ------------------------------------
    //  Edit Photo
    // ------------------------------------

    function edit_photo()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        // ------------------------------------
        //  Are photos enabled?
        // ------------------------------------

        if (Site::config('enable_photos') != 'y')
        {
            return Cp::errorMessage(__('account.photos_not_enabled'));
        }

        $query = DB::table('members')
            ->where('member_id', $id)
            ->select('photo_filename', 'photo_width', 'photo_height')
            ->first();

        if ($query->photo_filename == '')
        {
            $cur_photo_url = '';
            $photo_width    = '';
            $photo_height   = '';
        }
        else
        {
            $cur_photo_url = Site::config('photo_url', TRUE).$query->photo_filename;
            $photo_width    = $query->photo_width;
            $photo_height   = $query->photo_height;
        }

        $title = __('members.edit_photo');


        $r  = '<form method="post" action ="'.BASE.'?C=account'.AMP.'M=update_photo'.'" enctype="multipart/form-data" >';
        $r .= Cp::input_hidden('id', $id);

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('account.photo_updated'));
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::th('', '', '2');

        $r .= $title;

        $r .= '</th>'.PHP_EOL.
              '</tr>'.PHP_EOL;


        if ($query->photo_filename != '')
        {
            $photo = '<img src="'.$cur_photo_url.'" border="0" width="'.$photo_width.'" height="'.$photo_height.'" title="'.__('account.my_photo').'" />';
        }
        else
        {
            $photo = Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('members.no_photo_exists')));
        }

        $i = 0;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('members.current_photo')), '35%');
        $r .= Cp::tableCell('', $photo, '65%');
        $r .= '</tr>'.PHP_EOL;

        // ------------------------------------
        //  Set the default image meta values
        // ------------------------------------

        $max_kb = (Site::config('photo_max_kb') == '' OR Site::config('photo_max_kb') == 0) ? 50 : Site::config('photo_max_kb');
        $max_w  = (Site::config('photo_max_width') == '' OR Site::config('photo_max_width') == 0) ? 100 : Site::config('photo_max_width');
        $max_h  = (Site::config('photo_max_height') == '' OR Site::config('photo_max_height') == 0) ? 100 : Site::config('photo_max_height');
        $max_size = str_replace('%x', $max_w, __('members.max_image_size'));
        $max_size = str_replace('%y', $max_h, $max_size);
        $max_size .= ' - '.$max_kb.'KB';



        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('members.upload_photo')), '35%');
        $r .= Cp::tableCell('', '<input type="file" name="userfile" size="20" class="input" />', '65%');
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickDiv('littlePadding', Cp::quickSpan('highlight_alt', $max_size)), '35%');
        $r .= Cp::tableCell('', Cp::quickSpan('highlight_alt', __('members.allowed_image_types')), '65%');
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '2');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('members.upload_photo')).NBS.Cp::input_submit(__('members.remove_photo'), 'remove'));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        return $this->account_wrapper($title, $title, $r);
    }

    // ------------------------------------
    //  Upload Profile Photo
    // ------------------------------------

    function upload_photo()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $edit_image     = 'edit_photo';
        $not_enabled    = 'photos_not_enabled';
        $remove         = 'remove_photo';
        $removed        = 'photo_removed';
        $updated        = 'photo_updated';

        // ------------------------------------
        //  Is this a remove request?
        // ------------------------------------

        if (!Request::has('remove'))
        {
            if (Site::config('enable_photos') == 'n')
            {
                return $this->_error_message($not_enabled);
            }
        }
        else
        {
            $query = DB::table('members')
                ->where('member_id', $id)
                ->select('photo_filename')
                ->first();

            if ($query->photo_filename == '') {
                return redirect('?C=account&M=edit_photo&id='.$id);
            }

            DB::table('members')
                ->where('member_id', $id)
                ->update(
                [
                    'photo_filename' => '',
                    'photo_width' => '',
                    'photo_height' => ''
                ]);

            @unlink(Site::config('photo_path', TRUE).$query->photo_filename);

            return redirect('?C=account&M=edit_photo&id='.$id);
        }

        // ------------------------------------
        //  Is there $_FILES data?
        // ------------------------------------

        if ( ! isset($_FILES['userfile']))
        {
            return redirect('?C=account&M=edit_'.$type.'&id='.$id);
        }

        // ------------------------------------
        //  Check the image size
        // ------------------------------------

        $size = ceil(($_FILES['userfile']['size']/1024));

        $max_size = (Site::config('photo_max_kb') == '' OR Site::config('photo_max_kb') == 0) ? 50 : Site::config('photo_max_kb');
        $max_size = preg_replace("/(\D+)/", "", $max_size);

        if ($size > $max_size)
        {
            return Cp::userError( str_replace('%s', $max_size, __('account.image_max_size_exceeded')));
        }

        // ------------------------------------
        //  Is the upload path valid and writable?
        // ------------------------------------

        $upload_path = Site::config('photo_path', TRUE);

        if ( ! @is_dir($upload_path) OR ! is_writable($upload_path))
        {
            return $this->_error_message('image_assignment_error');
        }

        // ------------------------------------
        //  Set some defaults
        // ------------------------------------

        $filename = $_FILES['userfile']['name'];

        $max_width  = (Site::config('photo_max_width') == '' OR Site::config('photo_max_width') == 0) ? 200 : Site::config('photo_max_width');
        $max_height = (Site::config('photo_max_height') == '' OR Site::config('photo_max_height') == 0) ? 200 : Site::config('photo_max_height');
        $max_kb     = (Site::config('photo_max_kb') == '' OR Site::config('photo_max_kb') == 0) ? 300 : Site::config('photo_max_kb');

        // ------------------------------------
        //  Filename missing extension?
        // ------------------------------------

        if (strpos($filename, '.') === FALSE) {
            return Cp::userError( __('account.invalid_image_type'));
        }

        // ------------------------------------
        //  Is it an allowed image type?
        // ------------------------------------

        $x = explode('.', $filename);
        $extension = '.'.end($x);

        // We'll do a simple extension check now.
        // The file upload class will do a more thorough check later

        $types = array('.jpg', '.jpeg', '.gif', '.png');

        if ( ! in_array(strtolower($extension), $types)) {
            return Cp::userError( __('account.invalid_image_type'));
        }

        // ------------------------------------
        //  Assign the name of the image
        // ------------------------------------

        $new_filename = 'photos_'.$id.strtolower($extension);

        // ------------------------------------
        //  Do they currently have a photo?
        // ------------------------------------

        $query = DB::table('members')
            ->where('member_id', $id)
            ->select('photo_filename')
            ->first();

        $old_filename = ($query->photo_filename == '') ? '' : $query->photo_filename;

        // ------------------------------------
        //  Upload the image
        // ------------------------------------


        // @todo - Use Laravel's approach or a library
        return $this->_error_message('Disabled for the time being, sorry.');


        // ------------------------------------
        //  Do we need to resize?
        // ------------------------------------



        // ------------------------------------
        //  Update DB
        // ------------------------------------

        DB::table('members')
            ->where('member_id', $id)
            ->update(
            [
                'photo_filename' => $new_filename,
                'photo_width' => $width,
                'photo_height' => $height
            ]);

        // ------------------------------------
        //  Success message
        // ------------------------------------

        return redirect('?C=account&M='.$edit_image.'&id='.$id.'&U=1');
    }

    // ------------------------------------
    //  Notepad form
    // ------------------------------------

    function notepad()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $title = __('account.notepad');

        if (Session::userdata('group_id') != 1)
        {
            if ($id != Session::userdata('member_id'))
            {
                return $this->account_wrapper($title, $title, __('account.only_self_notpad_access'));
            }
        }

        $query = DB::table('members')
            ->where('member_id', $id)
            ->select('notepad', 'notepad_size')
            ->first();

        $r  = Cp::formOpen(array('action' => 'C=account'.AMP.'M=notepad_update')).
              Cp::input_hidden('id', $id);

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('account.notepad_updated'));
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::th('', '', '2');

        $r .= $title;

        $r .= '</th>'.PHP_EOL.
              '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '100%', '2');
        $r .= __('account.notepad_blurb');
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '100%', '5');
        $r .= Cp::input_textarea('notepad', $query->notepad, $query->notepad_size, 'textarea', '100%');
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::tableCell('', Cp::quickSpan('defaultBold', __('account.notepad_size')), '20%');
        $r .= Cp::tableCell('', Cp::input_text('notepad_size', $query->notepad_size, '4', '2', 'input', '40px'), '80%');
        $r .= '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '2');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('cp.update')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;

        $r .= '</form>'.PHP_EOL;

        return $this->account_wrapper($title, $title, $r);
    }


    // ------------------------------------
    //  Update notepad
    // ------------------------------------

    function notepad_update()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        if (Session::userdata('group_id') != 1)
        {
            if ($id != Session::userdata('member_id'))
            {
                return false;
            }
        }

        // validate for unallowed blank values
        if (empty($_POST)) {
            return Cp::unauthorizedAccess();
        }

        DB::table('members')
            ->where('member_id', $id)
            ->update(
            [
                'notepad' => $_POST['notepad'],
                'notepad_size' => ( ! is_numeric($_POST['notepad_size'])) ? 18 : $_POST['notepad_size']
            ]);

        return redirect('?C=account&M=notepad&id='.$id.'&U=1');
    }

    // ------------------------------------
    //  Administrative options
    // ------------------------------------

    function administrative_options()
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }

        if (false === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        $title = __('account.administrative_options');

        $query = DB::table('members')
            ->where('member_id', $id)
            ->select('ip_address', 'in_authorlist', 'group_id')
            ->first();

        foreach ($query as $key => $val) {
            $$key = $val;
        }

        $r  = Cp::formOpen(array('action' => 'C=account'.AMP.'M=administration_update')).
              Cp::input_hidden('id', $id);

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('account.administrative_options_updated'));
        }

        $r .= Cp::table('tableBorder', '0', '10', '100%').
              '<tr>'.PHP_EOL.
              Cp::th('', '', '2');

        $r .= $title;

        $r .= '</th>'.PHP_EOL.
              '</tr>'.PHP_EOL;


        // Member groups assignment

        if (Session::access('can_admin_mbr_groups'))
        {
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
                $r .= '<tr>'.PHP_EOL;
                $r .= Cp::tableCell('', Cp::quickDiv('defaultBold', __('account.member_group_assignment')).Cp::quickDiv('littlePadding', Cp::quickDiv('alert', __('account.member_group_warning'))), '50%');

                $menu = Cp::input_select_header('group_id');

                foreach ($query as $row)
                {
                    // If the current user is not a Super Admin
                    // we'll limit the member groups in the list

                    if (Session::userdata('group_id') != 1)
                    {
                        if ($row->group_id == 1)
                        {
                            continue;
                        }
                    }

                    $menu .= Cp::input_select_option($row->group_id, $row->group_name, ($row->group_id == $group_id) ? 1 : '');
                }

                $menu .= Cp::input_select_footer();

                $r .= Cp::tableCell('', $menu, '80%');
                $r .= '</tr>'.PHP_EOL;

            }
        }

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '100%', '2');
        $r .= Cp::input_checkbox('in_authorlist', 'y', ($in_authorlist == 'y') ? 1 : '').NBS.Cp::quickSpan('defaultBold', __('account.include_in_multiauthor_list'));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '2');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('cp.update')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        return $this->account_wrapper($title, $title, $r);
    }



    // ------------------------------------
    //  Update administrative options
    // ------------------------------------

    function administration_update()
    {
        if ( ! Session::access('can_admin_members'))
        {
            return Cp::unauthorizedAccess();
        }

        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        // validate for unallowed blank values
        if (empty($_POST)) {
            return Cp::unauthorizedAccess();
        }

        $data['in_authorlist'] = (Request::input('in_authorlist') == 'y') ? 'y' : 'n';

        if (Request::input('group_id'))
        {
            if ( ! Session::access('can_admin_mbr_groups'))
            {
                return Cp::unauthorizedAccess();
            }

            $data['group_id'] = $_POST['group_id'];


            if ($_POST['group_id'] == '1')
            {
                if (Session::userdata('group_id') != '1')
                {
                    return Cp::unauthorizedAccess();
                }
            }
            else
            {
                if (Session::userdata('member_id') == $id)
                {
                    return Cp::errorMessage(__('account.super_admin_demotion_alert'));
                }
            }
        }

        DB::table('members')
            ->where('member_id', $id)
            ->update($data);

        //  Update Config Values
        $query = DB::table('sites')
            ->select('site_preferences')
            ->first();

        $prefs = unserialize($query->site_preferences);

        foreach($prefs as $key => $value) {
            $prefs[$key] = str_replace('\\', '\\\\', $value);
        }

        DB::table('sites')
            ->update(['site_preferences' => serialize($prefs)]);

        return redirect('?C=account&M=administration&id='.$id.'&U=1');
    }

    // ------------------------------------
    //  Quick links
    // ------------------------------------

    function quick_links_form()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        if (Session::userdata('group_id') != 1)
        {
            if ($id != Session::userdata('member_id'))
            {
                return $this->account_wrapper(__('account.quick_links'), __('account.quick_links'), __('account.only_self_qucklink_access'));
            }
        }

        $r = '';

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('account.quicklinks_updated'));
        }

        $r .= Cp::quickDiv('tableHeading', __('account.quick_links'));

        $r .= Cp::formOpen(array('action' => 'C=account'.AMP.'M=quicklinks_update')).
              Cp::input_hidden('id', $id);

        $r .= Cp::table('tableBorder', '0', '', '100%');

        $r .= '<tr>'.PHP_EOL
             .Cp::td('', '', 3)
             .__('account.quick_link_description').NBS.__('account.quick_link_description_more')
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::quickDiv('defaultBold', __('account.link_title'))).
              Cp::tableCell('', Cp::quickDiv('defaultBold', __('account.link_url'))).
              Cp::tableCell('', Cp::quickDiv('defaultBold', __('account.link_order'))).
              '</tr>'.PHP_EOL;

        $query = DB::table('members')
            ->where('member_id', $id)
            ->select('quick_links')
            ->first();

        $i = 0;

        if ($query->quick_links != '')
        {
            foreach (explode("\n", $query->quick_links) as $row)
            {

                $x = explode('|', $row);

                $title = (isset($x[0])) ? $x[0] : '';
                $link  = (isset($x[1])) ? $x[1] : '';
                $order = (isset($x['2'])) ? $x['2'] : $i;


                $r .= '<tr>'.PHP_EOL.
                      Cp::tableCell('', Cp::input_text('title_'.$i, $title, '20', '40', 'input', '100%'), '40%').
                      Cp::tableCell('', Cp::input_text('link_'.$i,   $link, '20', '120', 'input', '100%'), '55%').
                      Cp::tableCell('', Cp::input_text('order_'.$i, $order, '2', '3', 'input', '30px'), '5%').
                      '</tr>'.PHP_EOL;
            }
        }


        $r .= '<tr>'.PHP_EOL.
              Cp::tableCell('', Cp::input_text('title_'.$i,  '', '20', '40', 'input', '100%'), '40%').
              Cp::tableCell('', Cp::input_text('link_'.$i,  'http://', '20', '120', 'input', '100%'), '60%').
              Cp::tableCell('', Cp::input_text('order_'.$i, $i, '2', '3', 'input', '30px'), '5%').
              '</tr>'.PHP_EOL;



        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '3');
        $r .= Cp::quickDiv('bigPad', Cp::quickSpan('highlight', __('account.quicklinks_delete_instructions')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;


        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::td('', '', '3');
        $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(__('account.submit')));
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $r .= '</table>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        return $this->account_wrapper(__('account.quick_links'), __('account.quick_links'), $r);
    }



    // ------------------------------------
    //  Save quick links (or Tabs)
    // ------------------------------------

    function quick_links_update($tabs = FALSE)
    {
        if (FALSE === ($id = $this->auth_id())) {
            return Cp::unauthorizedAccess();
        }

        if (Session::userdata('group_id') != 1 && $id != Session::userdata('member_id'))
        {
            return false;
        }

        // validate for unallowed blank values
        if (empty($_POST)) {
            return Cp::unauthorizedAccess();
        }

        $safety = [];
        $dups   = false;

        foreach ($_POST as $key => $val)
        {
            if (strstr($key, 'title_') AND $val != '')
            {
                $i = $_POST['order_'.substr($key, 6)];

                if ($i == '' || $i == 0)
                    $_POST['order_'.substr($key, 6)] = 1;


                if ( ! isset($safety[$i]))
                {
                    $safety[$i] = true;
                }
                else
                {
                    $dups = true;
                }
            }
        }

        if ($dups) {
            $i = 1;

            foreach ($_POST as $key => $val) {
                if (strstr($key, 'title_') AND $val != '')
                {
                    $_POST['order_'.substr($key, 6)] = $i;

                    $i++;
                }
            }
        }

        // Compile the data
        $data = [];

        foreach ($_POST as $key => $val)
        {
            if (strstr($key, 'title_') AND $val != '')
            {
                $n = substr($key, 6);

                $i = $_POST['order_'.$n];

                $data[$i] = $i.'|'.$_POST['title_'.$n].'|'.$_POST['link_'.$n].'|'.$_POST['order_'.$n]."\n";
            }
        }

        sort($data, SORT_NUMERIC);

        $str = '';

        foreach ($data as $key => $val)
        {
            $str .= substr(strstr($val, '|'), 1);
        }

        if ($tabs == false)
        {
            DB::table('members')
                ->where('member_id', $id)
                ->update(['quick_links' => trim($str)]);

            $url = '?C=account&M=quicklinks&id='.$id.'&U=1';
        }
        else
        {
            DB::table('members')
                ->where('member_id', $id)
                ->update(['quick_tabs' => trim($str)]);

            $url = '?C=account&M=tab_manager&id='.$id.'&U=1';
        }

        return redirect($url);
    }


    // ------------------------------------
    //  Tab Manager
    // ------------------------------------

    function tab_manager()
    {
        if (FALSE === ($id = $this->auth_id()))
        {
            return Cp::unauthorizedAccess();
        }

        if (Session::userdata('group_id') != 1)
        {
            if ($id != Session::userdata('member_id'))
            {
                return $this->account_wrapper(
                    __('account.tab_manager'),
                    __('account.tab_manager'),
                    __('account.only_self_tab_manager_access'));
            }
        }

        // ------------------------------------
        //  Build the rows of previously saved links
        // ------------------------------------

        $query = DB::table('members')
            ->where('member_id', $id)
            ->select('quick_tabs')
            ->first();

        $i = 0;
        $total_tabs = 0;
        $hidden     = '';
        $current    = '';

        if ($query->quick_tabs == '')
        {
            $tabs_exist = false;
        }
        else
        {
            $tabs_exist = true;

            $xtabs = explode("\n", $query->quick_tabs);

            $total_tabs = count($xtabs);

            foreach ($xtabs as $row)
            {
                $x = explode('|', $row);

                $title = (isset($x[0])) ? $x[0] : '';
                $link  = (isset($x[1])) ? $x[1] : '';
                $order = (isset($x['2'])) ? $x['2'] : $i;

                $i++;

                if (Request::input('link') == '')
                {
                    $current .= '<tr>'.PHP_EOL;

                    $current .= Cp::tableCell('', Cp::input_text('title_'.$i, $title, '20', '40', 'input', '95%'), '70%');
                    $current .= Cp::tableCell('', Cp::input_text('order_'.$i, $order, '2', '3', 'input', '30px'), '30%');

                    $current .= '</tr>'.PHP_EOL;
                }
                else
                {
                    $hidden .= Cp::input_hidden('title_'.$i, $title);
                    $hidden .= Cp::input_hidden('order_'.$i, $order);
                }

                if ($total_tabs <= 1 AND Request::input('link') != '')
                {
                    $hidden .= Cp::input_hidden('order_'.$i, $order);
                }

                $hidden .= Cp::input_hidden('link_'.$i, $link);
            }
        }

        // ------------------------------------
        //  Type of request
        // ------------------------------------

        $new_link = (Request::input('link') == '') ? FALSE : true;

        // ------------------------------------
        //  Create the output
        // ------------------------------------

        $r = '';

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('account.tab_manager_updated'));
        }

        $r .= Cp::formOpen(array('action' => 'C=account'.AMP.'M=tab_manager_update')).
              Cp::input_hidden('id', $id).
              $hidden;

        $r .= Cp::table('tableBorder', '0', '', '100%');

        $r .= '<tr>'.PHP_EOL.
            Cp::th('', '', 3).
            __('account.tab_manager').
            '</th>'.PHP_EOL.
            '</tr>'.PHP_EOL;

        $r .= '<tr>'.PHP_EOL
             .Cp::td('', '', 3)
             .Cp::quickDiv('littlePadding', __('account.tab_manager_description'))
             .'</td>'.PHP_EOL
             .'</tr>'.PHP_EOL;

        if ($new_link == FALSE)
        {
            $r .=
                  '<tr>'.PHP_EOL
                 .Cp::td('', '', 3)
                 .Cp::quickDiv('littlePadding', Cp::quickDiv('highlight_alt', __('account.tab_manager_instructions')))
                 .Cp::quickDiv('littlePadding', Cp::quickDiv('highlight_alt', __('account.tab_manager_description_more')));
        }

        if ($tabs_exist == TRUE AND $new_link == FALSE)
        {
            $r .= Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('account.quicklinks_delete_instructions')));
        }

        $r .= '</td>'.PHP_EOL.'</tr>'.PHP_EOL;


        if ($new_link == FALSE)
        {
            if ($tabs_exist == TRUE)
            {
                $r .= '<tr>'.PHP_EOL.
                      Cp::tableCell('', Cp::quickDiv('defaultBold', __('account.tab_title'))).
                      Cp::tableCell('', Cp::quickDiv('defaultBold', __('account.tab_order')));

                $r .= '</tr>'.PHP_EOL;

                $r .= $current;
            }
        }
        else
        {
            $r .= '</table>'.PHP_EOL;
            $r .= Cp::quickDiv('defaultSmall', NBS);

            $i++;

            $r .= Cp::input_hidden('order_'.$i, $i);

            $r .= Cp::table('tableBorder', '0', '', '100%');
            $r .=   '<tr>'.PHP_EOL.
                    Cp::th('', '', 2).__('account.tab_manager_create_new').'</th>'.PHP_EOL.
                    '</tr>'.PHP_EOL;

            $r .= '<tr>'.PHP_EOL.
                  Cp::tableCell('', Cp::quickDiv('defaultBold', __('account.new_tab_title'))).
                  Cp::tableCell('', Cp::quickSpan('defaultBold', __('account.new_tab_url')).NBS.Cp::quickSpan('default', __('account.can_not_edit'))).
                  '</tr>'.PHP_EOL;

            $newlink = (Request::input('link') != '') ? Request::input('link') : '';

            $newlink = str_replace('--', '=', $newlink);
            $newlink = str_replace('/', '&', $newlink);

            $linktitle = (Request::input('linkt') != '') ? base64_decode(Request::input('linkt')) : '';

            // $linktitle = __('account.tab_manager_newlink_title');

            $r .= '<tr>'.PHP_EOL.
                  Cp::tableCell('', Cp::input_text('title_'.$i, $linktitle, '20', '40', 'input', '100%'), '40%').
                  Cp::tableCell('', Cp::input_text('link_'.$i,  $newlink, '20', '120', 'input', '100%', 'readonly'), '60%').
                  '</tr>'.PHP_EOL;
        }

        if ($new_link == TRUE OR $tabs_exist == TRUE)
        {
            $r .= '<tr>'.PHP_EOL;
            $r .= Cp::td('', '', '2');
            $r .= Cp::quickDiv('buttonWrapper', Cp::input_submit(($new_link == FALSE) ? __('cp.update') : __('account.tab_manager_newlink')));
            $r .= '</td>'.PHP_EOL;
            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        return $this->account_wrapper(__('account.tab_manager'), __('account.tab_manager'), $r);
    }


    // ------------------------------------
    //  Member Mini Search (Ignore List)
    // ------------------------------------

    function member_search($msg = '')
    {
        Cp::$title = __('account.member_search');

        $r = Cp::heading(__('account.member_search'));

        if ($msg != '')
        {
            $r .= Cp::quickDiv('box', Cp::quickDiv('alert', $msg));
        }

        $r .= Cp::div('box');

        $r .= "<form method='post' action='".BASE."?C=account".AMP."M=do_member_search".AMP."Z=1' name='member_search' id='member_search' >\n";

        $r .= Cp::div('littlePadding');
        $r .= Cp::quickDiv('itemTitle', "<label for='screen_name'>".__('account.mbr_screen_name')."</label>");
        $r .= Cp::input_text('screen_name', '', '35');
        $r .= '</div>'.PHP_EOL;

        $r .= Cp::div('littlePadding');
        $r .= Cp::quickDiv('itemTitle', "<label for='screen_name'>".__('account.mbr_email_address')."</label>");
        $r .= Cp::input_text('email', '', '35');
        $r .= '</div>'.PHP_EOL;

        $r .= Cp::div('littlePadding');
        $r .= Cp::quickDiv('itemTitle', "<label for='group_id'>".__('account.mbr_member_group')."</label>");
        $r .= Cp::input_select_header('group_id');
        $r .= Cp::input_select_option('any', __('account.any'));

        $query = DB::table('member_groups')
            ->orderBy('group_name')
            ->select('group_id', 'group_name')
            ->get();

        foreach ($query as $row)
        {
            $r .= Cp::input_select_option($row->group_id, $row->group_name);
        }

        $r .= Cp::input_select_footer();
        $r .= '</div>'.PHP_EOL;

        $r .= '</div>'.PHP_EOL;
        $r .= Cp::quickDiv('paddingTop', Cp::input_submit());
        $r .= '</form>'.PHP_EOL;

        return Cp::$body = $r;
    }


    // ------------------------------------
    //  Do Member Mini Search (Ignore List)
    // ------------------------------------

    function do_member_search()
    {
        $redirect_url = "?C=account&M=member_search&Z=1";

        // ------------------------------------
        //  Parse the $_POST data
        // ------------------------------------

        if ($_POST['screen_name']   == '' &&
            $_POST['email']         == ''
            )
            {
                return redirect($redirect_url);
            }

        $search_query = DB::table('members')
            ->select('member_id', 'screen_name')
            ->join('member_groups', 'member_groups.group_id', '=', 'members.group_id');

        $valid = false;

        foreach ($_POST as $key => $val)
        {
            if ($key == 'XID')
            {
                continue;
            }
            if ($key == 'group_id')
            {
                if ($val != 'any') {
                    $search_query->where('group_id', $_POST['group_id']);
                    $valid = true;
                }
            }
            else
            {
                if ($val != '') {
                    $search_query->where($key, 'LIKE', '%'.$val.'%');
                    $valid = true;
                }
            }
        }

        if ($valid !== true)
        {
            return redirect($redirect_url);
        }

        $query = $query->get();

        if ($query->count() == 0) {
            return $this->member_search(__('account.no_search_results'));
        }

        $r = Cp::table('tableBorder', '0', '10', '100%');
        $r .= '<tr>'.PHP_EOL;
        $r .= Cp::th().__('account.search_results').'</th>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;

        $i = 0;
        foreach($query as $row)
        {
                        $item = '<a href="#" onclick="opener.dynamic_action(\'add\');opener.list_addition(\''.$row->screen_name.'\', \'name\');return false;">'.$row->screen_name.'</a>';
            $r .= '<tr>'.PHP_EOL;
            $r .= Cp::td($style).$item.'</td>'.PHP_EOL;
            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;
        $r .= Cp::quickDiv('defaultCenter', Cp::quickDiv('highlight', __('account.insert_member_instructions')));
        $r .= Cp::div('littlePadding');
        $r .= Cp::div('defaultCenter');
        $r .= Cp::quickDiv('defaultBold', Cp::anchor(BASE."?C=account".AMP."M=member_search".AMP."Z=1", __('account.new_search')));
        $r .= '</div>'.PHP_EOL;
        $r .= Cp::div('defaultCenter');
        $r .= Cp::quickDiv('defaultBold', Cp::anchor('JavaScript:window.close();', __('account.mbr_close_window')));
        $r .= '</div>'.PHP_EOL;
        $r .= '</div>'.PHP_EOL;

        return Cp::$body = $r;
    }

}