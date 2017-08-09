<?php

namespace Groot\Cp;

use Cp;
use DB;
use Site;
use Request;
use Carbon\Carbon;
use Groot\Cp\Logging;
use Groot\Core\Localize;
use Groot\Core\Session;

class Administration
{
	// --------------------------------------------------------------------

    /**
    * Request Handler
    *
    * @return mixed
    */
    public function run()
    {
		// This flag determines if a user can edit categories from the publish page.
		$category_exception =
			(Request::input('M') == 'blog_admin' and
				in_array(
					Request::input('P'),
					['category_editor', 'edit_category',  'update_category', 'del_category_conf',  'del_category', 'category_order']
				)
				and Request::input('Z') == 1
			) ?
			true :
			false;

		if ($category_exception == FALSE AND ! Session::access('can_access_admin'))
		{
			return Cp::unauthorizedAccess();
		}

		switch(Request::input('M'))
		{
			case 'config_mgr' :

				if ( ! Session::access('can_admin_preferences'))
				{
					return Cp::unauthorizedAccess();
				}

				switch(Request::input('P'))
				{
					case 'update_cfg'	: return $this->update_config_prefs();
						break;
					case 'member_cfg'	: return $this->member_config_manager();
						break;
					default				: return $this->config_manager();
						break;
				}

				break;
			case 'members' :

				// Instantiate the member administration class

				$MBR = new Members;

				switch(Request::input('P'))
				{
					case 'view_members'				: return $MBR->view_all_members();
						break;
					case 'mbr_conf'					: return $MBR->member_confirm();
						break;
					case 'mbr_del_conf'				: return $MBR->member_delete_confirm();
						break;
					case 'mbr_delete'				: return $MBR->member_delete();
						break;
					case 'member_reg_form'			: return $MBR->new_member_profile_form();
						break;
					case 'register_member'			: return $MBR->create_member_profile();
						break;
					case 'mbr_group_manager'		: return $MBR->member_group_manager();
						break;
					case 'editMemberGroup'			: return $MBR->editMemberGroup();
						break;
					case 'updateMemberGroup'		: return $MBR->updateMemberGroup();
						break;
					case 'mbr_group_del_conf'		: return $MBR->delete_member_group_conf();
						break;
					case 'delete_mbr_group'			: return $MBR->delete_member_group();
						break;
					case 'member_banning'			: return $MBR->member_banning_forms();
						break;
					case 'save_ban_data'			: return $MBR->update_banning_data();
						break;
					case 'profile_fields'			: return $MBR->custom_profile_fields();
						break;
					case 'edit_field'				: return $MBR->edit_profile_field_form();
						break;
					case 'del_field_conf'			: return $MBR->delete_profile_field_conf();
						break;
					case 'delete_field'				: return $MBR->delete_profile_field();
						break;
					case 'edit_field_order'			: return $MBR->edit_field_order_form();
						break;
					case 'update_field_order'		: return $MBR->update_field_order();
						break;
					case 'update_profile_fields'	: return $MBR->update_profile_fields();
						break;
					case 'member_search'			: return $MBR->member_search_form();
						break;
					case 'do_member_search'			: return $MBR->do_member_search();
						break;
					case 'ip_search'				: return $MBR->ip_search_form();
						break;
					case 'do_ip_search'				: return $MBR->do_ip_search();
						break;
					case 'email_console_logs'		: return $MBR->email_console_logs();
						break;
					case 'view_email'				: return $MBR->view_email();
						break;
					case 'delete_email_console'		: return $MBR->delete_email_console_messages();
						break;
					case 'login_as_member'			: return $MBR->login_as_member();
						break;
					case 'do_login_as_member'		: return $MBR->do_login_as_member();
						break;
					default							: return false;
						break;
					}

				break;
			case 'blog_admin' :

				if ($category_exception == FALSE AND ! Session::access('can_admin_weblogs'))
				{
					return Cp::unauthorizedAccess();
				}

				// Instantiate the publish administration class

				$PA = new PublishAdministration;

				switch(Request::input('P'))
				{
					case 'blog_list'			: return $PA->weblogsOverview();
						break;
					case 'new_weblog'			: return $PA->newWeblogForm();
						break;
					case 'blog_prefs'			: return $PA->editBlog();
						break;
					case 'group_prefs'			: return $PA->edit_group_form();
						break;
					case 'create_blog'			: return $PA->updateWeblog();
						break;
					case 'update_preferences'	: return $PA->updateWeblog();
						break;
					case 'delete_conf'			: return $PA->delete_weblog_conf();
						break;
					case 'delete'				: return $PA->delete_weblog();
						break;
					case 'categories'			: return $PA->category_overview();
						break;
					case 'cat_group_editor'		: return $PA->edit_category_group_form();
						break;
					case 'update_category_group' : return $PA->update_category_group();
						break;
					case 'cat_group_del_conf'	: return $PA->delete_category_group_conf();
						break;
					case 'delete_group'			: return $PA->delete_category_group();
						break;
					case 'category_editor'		: return $PA->category_manager();
						break;
					case 'update_category'		: return $PA->update_category();
						break;
					case 'edit_category'		: return $PA->edit_category_form();
						break;
					case 'category_order'		: return $PA->change_category_order();
						break;
					case 'global_category_order' : return $PA->global_category_order();
						break;
					case 'del_category_conf'	: return $PA->delete_category_confirm();
						break;
					case 'del_category'			: return $PA->delete_category();
						break;
					case 'edit_cat_field'		: return $PA->edit_category_field_form();
						break;
					case 'edit_cat_field_order'	: return $PA->edit_category_field_order_form();
						break;
					case 'statuses'				: return $PA->status_overview();
						break;
					case 'status_group_editor'	: return $PA->edit_status_group_form();
						break;
					case 'update_status_group'	: return $PA->update_status_group();
						break;
					case 'status_group_del_conf': return $PA->delete_status_group_conf();
						break;
					case 'delete_status_group'	: return $PA->delete_status_group();
						break;
					case 'status_editor'		: return $PA->status_manager();
						break;
					case 'update_status'		: return $PA->update_status();
						break;
					case 'edit_status'			: return $PA->edit_status_form();
						break;
					case 'del_status_conf'		: return $PA->delete_status_confirm();
						break;
					case 'del_status'			: return $PA->delete_status();
						break;
					case 'edit_status_order'	: return $PA->edit_status_order();
						break;
					case 'update_status_order'	: return $PA->update_status_order();
						break;
					case 'custom_fields'		: return $PA->field_overview();
						break;
					case 'update_field_group'	: return $PA->update_field_group();
						break;
					case 'del_field_group_conf'	: return $PA->delete_field_group_conf();
						break;
					case 'delete_field_group'	: return $PA->delete_field_group();
						break;
					case 'field_editor'			: return $PA->field_manager();
						break;
					case 'edit_field'			: return $PA->edit_field_form();
						break;
					case 'update_weblog_fields'	: return $PA->update_weblog_fields();
						break;
					case 'field_group_editor'	: return $PA->edit_field_group_form();
						break;
					case 'del_field_conf'		: return $PA->delete_field_conf();
						break;
					case 'delete_field'			: return $PA->delete_field();
						break;
					case 'upload_prefs'			: return $PA->file_upload_preferences();
						break;
					case 'edit_upload_pref'		: return $PA->edit_upload_preferences_form();
						break;
					case 'update_upload_prefs'	: return $PA->update_upload_preferences();
						break;
					case 'del_upload_pref_conf'	: return $PA->delete_upload_preferences_conf();
						break;
					case 'del_upload_pref'		: return $PA->delete_upload_preferences();
						break;
					default						: return false;
						break;
					}

				break;
			case 'utilities' :

				if ( ! Session::access('can_admin_utilities')) {
					return Cp::unauthorizedAccess();
				}

				$utilities = new Utilities;

				switch(Request::input('P'))
				{
					case 'view_logs'			: return (new Logging)->viewLogs();
						break;
					case 'clear_cplogs'		 	: return (new Logging)->clearCpLogs();
						break;
					case 'view_throttle_log'	: return (new Logging)->view_throttle_log();
						break;
					case 'clear_cache_form'		: return $utilities->clear_cache_form();
						break;
					case 'clear_caching'		: return $utilities->clear_caching();
						break;
					case 'recount_stats'		: return $utilities->recount_statistics();
						break;
					case 'recount_prefs'		: return $utilities->recount_preferences_form();
						break;
					case 'set_recount_prefs'	: return $utilities->set_recount_prefs();
						break;
					case 'do_recount'			: return $utilities->do_recount();
						break;
					case 'do_stats_recount'		: return $utilities->do_stats_recount();
						break;
					 case 'prune'				: return $utilities->data_pruning();
						break;
					 case 'member_pruning'		: return $utilities->member_pruning();
						break;
					 case 'prune_member_conf'	: return $utilities->prune_member_confirm();
						break;
					 case 'prune_members'		: return $utilities->prune_members();
						break;
					 case 'entry_pruning'		: return $utilities->entry_pruning();
						break;
					 case 'prune_entry_conf'	: return $utilities->prune_entry_confirm();
						break;
					 case 'prune_entries'		: return $utilities->prune_entries();
						break;
					case 'run_sandr'			: return $utilities->search_and_replace();
						break;
					case 'php_info'			 	: return $utilities->php_info();
						break;
					default					 	: return false;
						break;
					}

				break;
			default	:
				return $this->admin_home_page();
				break;
		}
	}


	// ------------------------------------
	//  Main admin page
	// ------------------------------------

	function admin_home_page()
	{
		if ( ! Session::access('can_access_admin')) {
			return Cp::unauthorizedAccess();
		}

		Cp::$title = __('admin.system_admin');
		Cp::$crumb = __('admin.system_admin');

		$menu = [

			'site_preferences'	=>	[
				'general_cfg'			=> [
					AMP.'M=config_mgr'.AMP.'P=general_cfg',
					'system offline name index site new version auto check rename weblog section urls'
				],
				'localization_cfg' 		=> [
					AMP.'M=config_mgr'.AMP.'P=localization_cfg',
					'localize localization time zone'
				],

				'email_cfg'				=> [
					AMP.'M=config_mgr'.AMP.'P=email_cfg',
					'email SMTP sendmail PHP Mail batch webmaster tell-a-friend contact form'
				],

				'cookie_cfg'			=> [
					AMP.'M=config_mgr'.AMP.'P=cookie_cfg',
					'cookie cookies prefix domain site'
				],

				'space_1'				=> '-',

				'cp_cfg'				=> [
					AMP.'M=config_mgr'.AMP.'P=cp_cfg',
					'control panel display language encoding character publish tab'
				],
				'security_cfg'	 		=> [
					AMP.'M=config_mgr'.AMP.'P=security_cfg',
					'security session sessions cookie deny duplicate require agent ip password length'
				],
				'debugging_preferences'			=> [
					AMP.'M=config_mgr'.AMP.'P=debugging_preferences',
					'output debugging error message force query string HTTP headers redirect redirection'
				],


				'space_2'				=> '-',

				'censoring_cfg'			=> [
					AMP.'M=config_mgr'.AMP.'P=censoring_cfg',
					'censor censoring censored'
				],
			],


			'weblog_administration'	=> [
				'weblog_management'		=>	[
					AMP.'M=blog_admin'.AMP.'P=blog_list',
					'weblog weblogs posting'
				],
				'categories'			=>	[
					AMP.'M=blog_admin'.AMP.'P=categories',
					'category categories'
				],
				'field_management'	 	=>	[
					AMP.'M=blog_admin'.AMP.'P=blog_list'.AMP.'P=custom_fields',
					'custom fields relational date textarea formatting'
				],
				'status_management'		=>	[
					AMP.'M=blog_admin'.AMP.'P=statuses',
					'status statuses open close'
				],
				'weblog_cfg'			=>	[
					AMP.'M=config_mgr'.AMP.'P=weblog_cfg',
					'category URL dynamic caching caches image resizing'
				]
			 ],

			'members_and_groups' 	=> [
				'register_member'		=> [
					AMP.'M=members'.AMP.'P=member_reg_form',
					'register new member'
				],
				'view_members'			=> [
					AMP.'M=members'.AMP.'P=view_members',
					'view members memberlist email url join date'
				],
				'member_groups'		 	=> [
					AMP.'M=members'.AMP.'P=mbr_group_manager',
					'member groups super admin admins superadmin pending guests banned'
				],

				'custom_profile_fields' => [
					AMP.'M=members'.AMP.'P=profile_fields',
					'custom member profile fields '
				],

				'member_cfg'			=> [
					AMP.'M=config_mgr'.AMP.'P=member_cfg',
					'membership members member private message messages messaging photos photo registration activation'
				],

				'space_1'				=> '-',

				'member_search'		 	=> [
					AMP.'M=members'.AMP.'P=member_search',
					'search members'
				],
				'ip_search'		 		=> [
					AMP.'M=members'.AMP.'P=ip_search',
					'ip address search entries'
				],

				'space_2'				=> '-',

				'user_banning'			=> [
					AMP.'M=members'.AMP.'P=member_banning',
					'ban banning users banned'
				],
				'view_email_logs'		=> [
					AMP.'M=members'.AMP.'P=email_console_logs',
					'email console logs message messages'
				]
		 	],

		 	'image_preferences'	=> [

		 		'file_upload_prefs'		=>	[
					AMP.'M=blog_admin'.AMP.'P=upload_prefs',
					'upload uploading paths images files directory'
				],

				'image_resizing'	 			=> [
					AMP.'M=config_mgr'.AMP.'P=image_cfg',
					'image resize resizing thumbnail thumbnails GD netPBM imagemagick magick'
				],
		 	],


			'utilities'				=> [
				'view_log_files'		=>	[
					AMP.'M=utilities'.AMP.'P=view_logs',
					'view CP control panel logs '
				],

				'view_throttle_log'		=>	[
					AMP.'M=utilities'.AMP.'P=view_throttle_log',
					'throttle throttling log'
				],
				'space_1'				=> '-',

				'clear_caching'		 	=>	[
					AMP.'M=utilities'.AMP.'P=clear_cache_form',
					'clear empty cache caches'
				],
			 	'data_pruning'			=>	[
					AMP.'M=utilities'.AMP.'P=prune',
					'prune remove delete member user entry membership'
				],
				'recount_stats'		 	=>	[
					AMP.'M=utilities'.AMP.'P=recount_stats',
					'stats statistics recount redo'
				],
				'php_info'				=>	[
					AMP.'M=utilities'.AMP.'P=php_info',
					'php info information settings paths'
				],
		 	]
		];

		// ----------------------------------------
		//  Set Initial Display + JS
		// ----------------------------------------

		if (!empty($_POST['keywords']))
		{
			Cp::$body_props .= ' onload="showHideMenu(\'search_results\');"';
		}
		else
		{
			if ( Request::input('area') !== null and in_array(Request::input('area'), array_keys($menu)))
			{
				Cp::$body_props .= ' onload="showHideMenu(\''.Request::input('area').'\');"';
			}
			else
			{
				Cp::$body_props .= ' onload="showHideMenu(\'default_menu\');"';
			}
		}

        $js = <<<EOT
<script type="text/javascript">
function showHideMenu(contentId)
{
	$("#menu_contents").html($("#"+contentId).html());
}
</script>
EOT;
        Cp::$body  = $js;
		Cp::$body .= Cp::table('', '0', '', '100%');

		// Various sections of Admin area
		$left_menu = Cp::div('tableHeadingAlt').
			__('admin.system_admin').
			'</div>'.PHP_EOL.
			Cp::div('profileMenuInner');

		// ----------------------------------------
		//  Build Left Menu AND default content, which is also the menu
		// ----------------------------------------

		$content = PHP_EOL.'<ul>'.PHP_EOL;

		foreach($menu as $key => $value)
		{
			$left_menu .= Cp::quickDiv('navPad', Cp::anchor(BASE.'?C=Administration&area='.$key, __('admin.'.$key)));

			$content .= '<li>'.
						Cp::anchor(
							BASE.'?C=Administration&area='.$key,
							__('admin.'.$key)
						).
						'</li>'.PHP_EOL;
		}

		$content .= '</ul>'.PHP_EOL;

		$main_content = Cp::quickDiv('default', '', 'menu_contents').
			"<div id='default_menu' style='display:none;'>".
				Cp::heading(__('admin.system_admin'), 2).
				__('admin.system_admin_blurb').
				$content.
			'</div>'.PHP_EOL;

		// -------------------------------------
		//  Clean up Keywords
		// -------------------------------------

		$keywords = '';

		if (Request::has('keywords'))
		{
			$keywords = Request::input('keywords');

			// Ooooo!
			$question = 'dGhlIGFuc3dlciB0byBsaWZlLCB0aGUgdW5pdmVyc2UsIGFuZCBldmVyeXRoaW5n';

			if (strtolower(Request::input('keywords')) == base64_decode($question))
			{
				return Cp::errorMessage('42');
			}

			$search_terms = preg_split("/\s+/", strtolower( Request::input('keywords')));
			$search_results = '';
		}

		// -------------------------------------
		//  Build Content
		// -------------------------------------

		foreach ($menu as $key => $val)
		{
			$content = PHP_EOL.'<ul>'.PHP_EOL;

			foreach($val as $k => $v)
			{
				// A space between items. Adds clarity
				if (substr($k, 0, 6) == 'space_')
				{
					$content .= '</ul>'.PHP_EOL.PHP_EOL.'<ul>'.PHP_EOL;
					continue;
				}

				$content .= '<li>'.Cp::anchor(BASE.'?C=Administration'.$v[0], __('admin.'.$k)).'</li>'.PHP_EOL;

				// Find areas that match keywords, a bit simplisitic but it works...
				if (!empty($search_terms))
				{
					if (sizeof(array_intersect($search_terms, explode(' ', strtolower($v['1'])))) > 0)
					{
						$search_results .= '<li>'.__('admin.'.$key).' -> '.Cp::anchor(BASE.'?C=Administration'.$v[0], __('admin.'.$k)).'</li>';
					}
				}
			}

			$content .= '</ul>'.PHP_EOL;

			$blurb = ('admin.'.$key.'_blurb' == __('admin.'.$key.'_blurb')) ? '' : __('admin.'.$key.'_blurb');

			$main_content .=  "<div id='".$key."' style='display:none;'>".
								Cp::heading(__('admin.'.$key), 2).
								$blurb.
								$content.
							'</div>'.PHP_EOL;
		}

		// -------------------------------------
		//  Keywords Search
		// -------------------------------------

		if (!empty($search_terms))
		{
			if (strlen($search_results) > 0)
			{
				$search_results = PHP_EOL.'<ul>'.PHP_EOL.$search_results.PHP_EOL.'</ul>';
			}
			else
			{
				$search_results = __('admin.no_search_results');

				if (isset($search_terms[0]) && strtolower($search_terms[0]) === 'mufasa') {
					$search_results .= '<div style="font-size: 4em;">🦁</div>';
				}
			}

			$main_content .=  "<div id='search_results' style='display:none;'>".
								Cp::heading(__('admin.search_results'), 2).
								$search_results.
							  '</div>';
		}

		// -------------------------------------
		//  Display Page
		// -------------------------------------

		$left_menu .= '</div>'.PHP_EOL.BR;

		// Add in the Search Form
		$left_menu .=  Cp::quickDiv('tableHeadingAlt', __('admin.search'))
						.Cp::div('profileMenuInner')
						.	Cp::formOpen(array('action' => 'C=Administration'))
						.		Cp::input_text('keywords', $keywords, '20', '120', 'input', '98%')
						.		Cp::quickDiv('littlePadding', Cp::quickDiv('defaultRight', Cp::input_submit(__('admin.search'))))
						.	'</form>'.PHP_EOL
						.'</div>'.PHP_EOL;

		// Create the Table
		$table_row = [
			'first' 	=> ['valign' => "top", 'width' => "220px", 'text' => $left_menu],
			'second'	=> ['class' => "default", 'width'  => "15px"],
			'third'		=> ['valign' => "top", 'text' => $main_content]
		];

		Cp::$body .= Cp::tableRow($table_row).
					  '</table>'.PHP_EOL;

	}


	// ------------------------------------
	//  Configuratin Menu data
	// ------------------------------------

	function config_data()
	{
		return [

			'general_cfg' =>	[
				'is_system_on'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'is_site_on'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'site_index'				=> '',
				'site_url'					=> '',
				'cp_url'					=> '',
				'theme_folder_url'			=> '',
				'theme_folder_path'			=> '',
				'notification_sender_email'	=> '',
				'max_caches'				=> '',
				'site_debug'				=> ['s', ['0' => 'debug_zero', '1' => 'debug_one', '2' => 'debug_two']],
			],

			'localization_cfg'	=>	[
				'site_timezone'			=> array('f', 'timezone'),
				'time_format'			=> array('s', array('us' => 'united_states', 'eu' => 'european')),
				'deft_lang'				=> array('f', 'language_menu'),
			],

			'cookie_cfg' => [
				'cookie_domain'				=> '',
				'cookie_path'				=> '',
			],

			'cp_cfg' =>	[
				'cp_theme'					=> array('f', 'theme_menu'),
			],

			'debugging_preferences'	=>	[
				'show_queries'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'template_debugging'		=> array('r', array('y' => 'yes', 'n' => 'no'))
			],

			'weblog_cfg' =>	[
				'use_category_name'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'reserved_category_word'	=> '',
				'auto_assign_cat_parents'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'new_posts_clear_caches'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'word_separator'			=> array('s', array('dash' => 'dash', 'underscore' => 'underscore')),
			],

			'image_cfg' =>	[
				'enable_image_resizing' 	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'image_resize_protocol'		=> ['s', ['gd' => 'gd', 'gd2' => 'gd2', 'imagemagick' => 'imagemagick', 'netpbm' => 'netpbm']],
				'image_library_path'		=> '',
				'thumbnail_prefix'			=> '',
				'xss_clean_uploads'			=> array('r', array('y' => 'yes', 'n' => 'no')),
			],

			'security_cfg' =>	[
				'password_min_length'		=> '',
				'enable_throttling'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'banish_masked_ips'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'max_page_loads'			=> '',
				'time_interval'				=> '',
				'lockout_time'				=> '',
				'banishment_type'			=> array('s', array('404' => '404', 'redirect' => 'url_redirect', 'message' => 'show_message')),
				'banishment_url'			=> '',
				'banishment_message'		=> ''
			],


			'template_cfg' => [
				'save_tmpl_revisions' 		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'max_tmpl_revisions'		=> '',
			],

			'censoring_cfg' => [
				'enable_censoring' 			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'censor_replacement'		=> '',
				'censored_words'			=> array('t', array('rows' => '20', 'kill_pipes' => TRUE)),
			],
		];
	}


	// ------------------------------------
	//  Configuration sub-text
	// ------------------------------------

	// Secondary lines of text used in configuration pages
	// This text appears below any given preference defenition

	function subtext()
	{
		return [
			'site_url'					=> array('url_explanation'),
			'is_site_on'		    	=> array('is_site_on_explanation'),
			'is_system_on'		    	=> array('is_system_on_explanation'),
			'site_debug'				=> array('site_debug_explanation'),
			'show_queries'				=> array('show_queries_explanation'),
			'template_debugging'		=> array('template_debugging_explanation'),
			'max_caches'				=> array('max_caches_explanation'),
			'default_member_group' 		=> array('group_assignment_defaults_to_two'),
			'notification_sender_email' => array('notification_sender_email_explanation'),
			'cookie_domain'				=> array('cookie_domain_explanation'),
			'cookie_path'				=> array('cookie_path_explain'),
			'censored_words'			=> array('censored_explanation', 'censored_wildcards'),
			'censor_replacement'		=> array('censor_replacement_info'),
			'enable_image_resizing'		=> array('enable_image_resizing_exp'),
			'image_resize_protocol'		=> array('image_resize_protocol_exp'),
			'image_library_path'		=> array('image_library_path_exp'),
			'thumbnail_prefix'			=> array('thumbnail_prefix_exp'),
			'use_category_name'			=> array('use_category_name_exp'),
			'reserved_category_word'	=> array('reserved_category_word_exp'),
			'auto_assign_cat_parents'	=> array('auto_assign_cat_parents_exp'),
			'save_tmpl_revisions'		=> array('template_rev_msg'),
			'max_tmpl_revisions'		=> array('max_revisions_exp'),
			'max_page_loads'			=> array('max_page_loads_exp'),
			'time_interval'				=> array('time_interval_exp'),
			'lockout_time'				=> array('lockout_time_exp'),
			'banishment_type'			=> array('banishment_type_exp'),
			'banishment_url'			=> array('banishment_url_exp'),
			'banishment_message'		=> array('banishment_message_exp'),
		];
	}


	// ------------------------------------
	//  Configuration manager
	// ------------------------------------

	// This function displays the various Preferences pages

	function config_manager($f_data = '', $subtext = '', $return_loc = '')
	{
		if ( ! Session::access('can_admin_preferences')) {
			return Cp::unauthorizedAccess();
		}

		if ( ! $type = Request::input('P')) {
			return false;
		}

		if ($f_data == '')
		{
			// No funny business with the URL

			$allowed = [
				'general_cfg',
				'localization_cfg',
				'cookie_cfg',
				'cp_cfg',
				'weblog_cfg',
				'member_cfg',
				'debugging_preferences',
				'debug_cfg',
				'db_cfg',
				'security_cfg',
				'email_cfg',
				'image_cfg',
				'template_cfg',
				'censoring_cfg',
				'tracking_cfg',
			];

			if (!in_array($type, $allowed)) {
				return redirect('?');
			}

			$f_data = $this->config_data();
		}

		if ($subtext == '') {
			$subtext = $this->subtext();
		}

		// ------------------------------------
		//  Build the output
		// ------------------------------------

		Cp::$body	 =	'';

		if (Request::input('U')) {
			Cp::$body .= Cp::quickDiv('successMessage', __('admin.preferences_updated'));
		}

		if ($return_loc == '') {

			$return_loc = BASE.'?C=Administration'.AMP.'M=config_mgr'.AMP.'P='.$type.AMP.'U=1';

			if ($type === 'template_cfg') {
				$return_loc = 'templates_manager';
			}
		}

		Cp::$body	.=	Cp::formOpen(
			[
				'action' => 'C=Administration'.AMP.'M=config_mgr'.AMP.'P=update_cfg'
			],
			[
				'return_location' => $return_loc
			]
		);

		Cp::$body	.=	Cp::table('tableBorder', '0', '', '100%');
		Cp::$body	.=	'<tr>'.PHP_EOL;
		Cp::$body	.=	Cp::td('tableHeading', '', '2');
		Cp::$body	.=	__('admin.'.$type);
		Cp::$body	.=	'</td>'.PHP_EOL;
		Cp::$body	.=	'</tr>'.PHP_EOL;

		$i = 0;

		// ------------------------------------
		//  Blast through the array
		// ------------------------------------

		foreach ($f_data[$type] as $key => $val)
		{
			Cp::$body	.=	'<tr>'.PHP_EOL;

			// If the form type is a textarea, we'll align the text at the top, otherwise, we'll center it

			if (is_array($val) AND $val[0] == 't')
			{
				Cp::$body .= Cp::td('', '50%', '', '', 'top');
			}
			else
			{
				Cp::$body .= Cp::td('', '50%', '');
			}

			// ------------------------------------
			//  Preference heading
			// ------------------------------------

			Cp::$body .= Cp::div('defaultBold');

			$label = ( ! is_array($val)) ? $key : '';

			Cp::$body .= __('admin.'.$key);

			Cp::$body .= '</div>'.PHP_EOL;

			// ------------------------------------
			//  Preference sub-heading
			// ------------------------------------

			if (isset($subtext[$key]))
			{
				foreach ($subtext[$key] as $sub)
				{
					Cp::$body .= Cp::quickDiv('subtext', __('admin.'.$sub));
				}
			}

			Cp::$body .= '</td>'.PHP_EOL;

			// ------------------------------------
			//  Preference value
			// ------------------------------------

			Cp::$body .= Cp::td('', '50%', '');

				if (is_array($val))
				{
					// ------------------------------------
					//  Drop-down menus
					// ------------------------------------

					if ($val[0] == 's')
					{
						Cp::$body .= Cp::input_select_header($key);

						foreach ($val[1] as $k => $v)
						{
							$selected = ($k == Site::config($key)) ? 1 : '';

							Cp::$body .= Cp::input_select_option($k, __('admin.'.$v), $selected);
						}

						Cp::$body .= Cp::input_select_footer();

					}
					elseif ($val[0] == 'r')
					{
						// ------------------------------------
						//  Radio buttons
						// ------------------------------------

						foreach ($val[1] as $k => $v)
						{
							// little cheat for some values popped into a build update
							if (Site::config($key) === FALSE)
							{
								$selected = (isset($val['2']) && $k == $val['2']) ? 1 : '';
							}
							else
							{
								$selected = ($k == Site::config($key)) ? 1 : '';
							}

							Cp::$body .= __('admin.'.$v).'&nbsp;';
							Cp::$body .= Cp::input_radio($key, $k, $selected).'&nbsp;';
						}
					}
					elseif ($val[0] == 't')
					{
						// ------------------------------------
						//  Textarea fields
						// ------------------------------------

						// The "kill_pipes" index instructs us to
						// turn pipes into newlines

						if (isset($val[1]['kill_pipes']) AND $val[1]['kill_pipes'] === TRUE)
						{
							$text	= '';

							foreach (explode('|', Site::config($key)) as $exp)
							{
								$text .= $exp.PHP_EOL;
							}
						}
						else
						{
							$text = stripslashes(Site::config($key));
						}

						$rows = (isset($val[1]['rows'])) ? $val[1]['rows'] : '20';

						$text = str_replace("\\'", "'", $text);

						Cp::$body .= Cp::input_textarea($key, $text, $rows);

					}
					elseif ($val[0] == 'f')
					{
						// ------------------------------------
						//  Function calls
						// ------------------------------------

						switch ($val[1])
						{
							case 'language_menu'		: 	Cp::$body .= $this->availableLanguages(Site::config($key));
								break;
							case 'theme_menu'			: 	Cp::$body .= $this->fetch_themes(Site::config($key));
								break;
							case 'timezone'				: 	Cp::$body .= Localize::timezoneMenu(Site::config($key));
								break;
						}
					}
				}
				else
				{
					// ------------------------------------
					//  Text input fields
					// ------------------------------------

					$item = str_replace("\\'", "'", Site::config($key));

					Cp::$body .= Cp::input_text($key, $item, '20', '120', 'input', '100%');
				}

			Cp::$body .= '</td>'.PHP_EOL;
			Cp::$body .= '</tr>'.PHP_EOL;
		}

		Cp::$body .= '</table>'.PHP_EOL;

		Cp::$body .= Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.update')));

		Cp::$body .= '</form>'.PHP_EOL;

		Cp::$title  = __('admin.'.$type);

		if (Request::input('P') == 'weblog_cfg')
		{
			Cp::$crumb  = Cp::anchor(BASE.'?C=Administration'.AMP.'area=weblog_administration', __('admin.weblog_administration'));
			Cp::$crumb .= Cp::breadcrumbItem(__('admin.'.$type));
		}
		elseif(Request::input('P') != 'template_cfg')
		{
			Cp::$crumb  = Cp::anchor(BASE.'?C=Administration'.AMP.'area=site_preferences', __('admin.site_preferences'));
			Cp::$crumb .= Cp::breadcrumbItem(__('admin.'.$type));
		}
		else
		{
			Cp::$crumb .= __('admin.'.$type);
		}
	}


	// ------------------------------------
	//  Member Config Page
	// ------------------------------------

	function member_config_manager()
	{
		if ( ! Session::access('can_admin_preferences')) {
			return Cp::unauthorizedAccess();
		}

		if ( ! $type = Request::input('P')) {
			return false;
		}

		$f_data = [
			'general_cfg'		=>
			[
				'default_member_group'	=> ['f', 'member_groups'],
				'enable_photos'			=> ['r', ['y' => 'yes', 'n' => 'no']],
				'photo_url'				=> '',
				'photo_path'			=> '',
				'photo_max_width'		=> '',
				'photo_max_height'		=> '',
				'photo_max_kb'			=> ''
			]
		];


		$subtext = [
			'default_member_group' 		=> ['group_assignment_defaults_to_two'],
			'photo_path'				=> ['must_be_path']
		];

		if (Request::input('U'))
		{
			Cp::$body .= Cp::quickDiv('successMessage', __('admin.preferences_updated'));
		}

		$r = Cp::formOpen(
			[
				'action' => 'C=Administration'.AMP.'M=config_mgr'.AMP.'P=update_cfg'
			],
			[
				'return_location' => BASE.'?C=Administration'.AMP.'M=config_mgr'.AMP.'P='.$type.AMP.'U=1'
			]
		);

		$r .= Cp::quickDiv('default', '', 'menu_contents');

		$i = 0;

		// ------------------------------------
		//  Blast through the array
		// ------------------------------------

		foreach ($f_data as $menu_head => $menu_array)
		{
			$r .= '<div id="'.$menu_head.'" style="display: block; padding:0; margin: 0;">';
			$r .= Cp::table('tableBorder', '0', '', '100%');
			$r .= '<tr>'.PHP_EOL;

			$r .= "<td class='tableHeadingAlt' id='".$menu_head."2' colspan='2'>";
			$r .= NBS.__('admin.'.$menu_head).'</td>'.PHP_EOL;
			$r .= '</tr>'.PHP_EOL;


			foreach ($menu_array as $key => $val)
			{
				$r	.=	'<tr>'.PHP_EOL;

				// If the form type is a textarea, we'll align the text at the top, otherwise, we'll center it

				if (is_array($val) AND $val[0] == 't')
				{
					$r .= Cp::td('', '50%', '', '', 'top');
				}
				else
				{
					$r .= Cp::td('', '50%', '');
				}

				// ------------------------------------
				//  Preference heading
				// ------------------------------------

				$r .= Cp::div('defaultBold');

				$label = ( ! is_array($val)) ? $key : '';

				$r .= __('admin.'.$key);

				$r .= '</div>'.PHP_EOL;

				// ------------------------------------
				//  Preference sub-heading
				// ------------------------------------

				if (isset($subtext[$key]))
				{
					foreach ($subtext[$key] as $sub)
					{
						$r .= Cp::quickDiv('subtext', __('admin.'.$sub));
					}
				}

				$r .= '</td>'.PHP_EOL;
				$r .= Cp::td('', '50%', '');

					if (is_array($val))
					{
						// ------------------------------------
						//  Drop-down menus
						// ------------------------------------

						if ($val[0] == 's')
						{
							$r .= Cp::input_select_header($key);

							foreach ($val[1] as $k => $v)
							{
								$selected = ($k == Site::config($key)) ? 1 : '';

								$r .= Cp::input_select_option($k, ( ! __('admin.'.$v) ? $v : __('admin.'.$v)), $selected);
							}

							$r .= Cp::input_select_footer();

						}
						elseif ($val[0] == 'r')
						{
							// ------------------------------------
							//  Radio buttons
							// ------------------------------------

							foreach ($val[1] as $k => $v)
							{
								$selected = ($k == Site::config($key)) ? 1 : '';

								$r .= __('admin.'.$v).'&nbsp;';
								$r .= Cp::input_radio($key, $k, $selected).'&nbsp;';
							}
						}
						elseif ($val[0] == 'f')
						{
							// ------------------------------------
							//  Function calls
							// ------------------------------------

							switch ($val[1])
							{
								case 'member_groups'		:	$r .= $this->fetch_member_groups();
									break;
							}
						}

					}
					else
					{
						// ------------------------------------
						//  Text input fields
						// ------------------------------------

						$item = str_replace("\\'", "'", Site::config($key));

						$r .= Cp::input_text($key, $item, '20', '120', 'input', '100%');
					}

				$r .= '</td>'.PHP_EOL;
			}

			$r .= '</tr>'.PHP_EOL;
			$r .= '</table>'.PHP_EOL;
			$r .= '</div>'.PHP_EOL;
		}

		$r .= Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.update')));

		$r .= '</form>'.PHP_EOL;


		// ------------------------------------
        //  Create Our All Encompassing Table of Member Goodness
        // ------------------------------------

        Cp::$body .= Cp::table('', '0', '', '100%');

		$menu  = '';

		foreach ($f_data as $menu_head => $menu_array)
		{
			$menu .= Cp::quickDiv('navPad', ' <span id="'.$menu_head.'_pointer">&#8226; '.Cp::anchor("#", __('admin.'.$menu_head), 'onclick="showHideMenu(\''.$menu_head.'\');return false;"').'</span>');
		}

		$first_text = 	Cp::div('tableHeadingAlt')
						.	__('admin.'.$type)
						.'</div>'.PHP_EOL
						.Cp::div('profileMenuInner')
						.	$menu
						.'</div>'.PHP_EOL;

		// Create the Table
		$table_row = [
			'first' 	=> ['valign' => "top", 'width' => "220px", 'text' => $first_text],
			'second'	=> ['class' => "default", 'width'  => "15px"],
			'third'		=> ['valign' => "top", 'text' => $r]
		];

		Cp::$body .= Cp::tableRow($table_row).
					  '</table>'.PHP_EOL;

		Cp::$title = __('admin.admin.'.$type);
		Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=members_and_groups', __('admin.members_and_groups')).
					  Cp::breadcrumbItem(__('admin.'.$type));
	}

	// ------------------------------------
	//  Fetch Member groups
	// ------------------------------------

	function fetch_member_groups()
	{
    	$query = DB::table('member_groups')
    		->select('group_id', 'group_name')
    		->where('group_id', '!=', 1)
    		->orderBy('group_name')
    		->get();

		$r = Cp::input_select_header('default_member_group');

		foreach ($query as $row)
		{
			$group_name = $row->group_name;

			$selected = ($row->group_id == Site::config('default_member_group')) ? 1 : '';

			$r .= Cp::input_select_option($row->group_id, $group_name, $selected);
		}

		$r .= Cp::input_select_footer();

		return $r;
	}



	// ------------------------------------
	//  Update general preferences
	// ------------------------------------

	function update_config_prefs()
	{
		if ( ! Session::access('can_admin_preferences')) {
			return Cp::unauthorizedAccess();
		}

		// @todo - Probably bogus, just set a default
		$loc = Request::input('return_location');

		// We'll format censored words if they happen to cross our path
		if (Request::has('censored_words')) {
			$censored_words = Request::input('censored_words');
			$censored_words = str_replace(PHP_EOL, '|', $censored_words);
			$censored_words = preg_replace("#\s+#", "", $censored_words);
		}

		// Category trigger cannot match a folder name or a template
		if (Request::has('reserved_category_word') and Request::input('reserved_category_word') != Site::config('reserved_category_word'))
		{
			$count = DB::table('templates')
				->where(function ($query) {
	                $query->where('template_name', Request::input('reserved_category_word'))
	                	->orWhere('folder', Request::input('reserved_category_word'));
	            })
				->count();

			if ($count > 0)
			{
				$msg  = Cp::quickDiv('littlePadding', __('admin.category_trigger_duplication'));
				$msg .= Cp::quickDiv('highlight', htmlentities(Request::input('reserved_category_word')));

				return Cp::errorMessage($msg);
			}
		}

		// ------------------------------------
		//  Do path checks if needed
		// ------------------------------------

		$paths = ['photo_path'];

		foreach ($paths as $val)
		{
			if (Request::has($val))
			{
				$fp = Request::input($val);

				if (substr($fp, -1) != '/' && substr($fp, -1) != '\\')
				{
					$fp .= '/';
				}

				if ( ! @is_dir($fp))
				{
					$msg  = Cp::quickDiv('littlePadding', __('admin.invalid_path'));
					$msg .= Cp::quickDiv('highlight', $fp);

					return Cp::errorMessage($msg);
				}

				if ( ! @is_writable($fp))
				{
					$msg  = Cp::quickDiv('littlePadding', __('admin.not_writable_path'));
					$msg .= Cp::quickDiv('highlight', $fp);

					return Cp::errorMessage($msg);
				}
			}
		}

		// ------------------------------------
		//  Preferences Stored in Database For Site
		// ------------------------------------

		$query = DB::table('sites')
			->where('site_id', Site::config('site_id'))
			->first();

		foreach(['site', 'weblog', 'template', 'member'] as $type)
		{
			$prefs	 = unserialize($query->{$type.'_preferences'});

			$changes = 'n';

			foreach(Site::divination($type) as $value)
			{
				if (Request::has($value))
				{
					$changes = 'y';

					$prefs[$value] = str_replace('\\\\', '/',  Request::input($value));
				}
			}

			if ($changes == 'y')
			{
				DB::table('sites')
					->where('site_id', Site::config('site_id'))
					->update(
						[
							$type.'_preferences' => serialize($prefs)
						]);
			}
		}

		// ------------------------------------
		//  Certain Preferences in config/cms.php
		// ------------------------------------

		$this->update_config_file();

		// ------------------------------------
		//  Redirect
		// ------------------------------------

		if ($loc === 'templates_manager') {
			return redirect('?C=templates');
		}

		return redirect($loc);
	}


	// ------------------------------------
	//  Update config file
	// ------------------------------------

	function update_config_file()
	{
		$allowed = [
			'is_system_on',
			'disable_events'
		];

		$data = [];

		foreach($allowed as $value) {
			if (Request::has($value)) {
				$data[$value] = Request::input($value);
			}
		}

		if (empty($data)) {
			return;
		}

		$config = array_merge(config('cms'), $data);

		// --------------------------------
        // Write config/cms.php file
        // --------------------------------

        $contents  = '<?php'.PHP_EOL."return [".PHP_EOL;

        foreach ($config as $key => $val)
        {
            $val = str_replace("\"", "\\\"", $val);
            $contents .= "\t'".$key."' => \"".$val."\",".PHP_EOL;
        }
        $contents .= "];".PHP_EOL;

        $cfile = SYSTEM_PATH.'config/cms.php';

        file_put_contents($cfile, $contents);
	}


	// ------------------------------------
	//  Fetch Control Panel Themes
	// ------------------------------------

	function fetch_themes($default = '')
	{
		$source_dir = PATH_CP_THEME;

		$filelist = [];

		if ( ! $fp = @opendir($source_dir))
		{
			return '';
		}

		while (false !== ($file = readdir($fp)))
		{
			$filelist[count($filelist)] = $file;
		}

		closedir($fp);
		sort($filelist);

		$r = Cp::input_select_header('cp_theme');

		for ($i =0; $i < sizeof($filelist); $i++)
		{
			if ( is_dir(PATH_CP_THEME.$filelist[$i]) && ! preg_match("/[^a-z\_\-0-9]/", $filelist[$i]))
			{
				$selected = ($filelist[$i] == $default) ? 1 : '';

				$name = ucwords(str_replace("_", " ", $filelist[$i]));

				$r .= Cp::input_select_option($filelist[$i], $name, $selected);
			}
		}

		$r .= Cp::input_select_footer();

		return $r;
	}

	// ------------------------------------
    //  Fetch names of installed language packs
    // ------------------------------------

    private function availableLanguages($default)
    {
        $source_dir = resource_path('lang');

        $dirs = [];

        if ($fp = @opendir($source_dir))
        {
            while (FALSE !== ($file = readdir($fp)))
            {
                if (is_dir($source_dir.$file) && substr($file, 0, 1) != ".")
                {
                    $dirs[] = $file;
                }
            }
            closedir($fp);
        }

        sort($dirs);

        $r  = "<div class='default'>";
        $r .= "<select name='deft_lang' class='select'>\n";

        foreach ($dirs as $dir)
        {
            $selected = ($dir == $default) ? " selected='selected'" : '';
            $r .= "<option value='{$dir}'{$selected}>".ucfirst($dir)."</option>\n";
        }

        $r .= "</select>";
        $r .= "</div>";

        return $r;
    }

}

