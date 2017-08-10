<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class CmsSeeder extends Seeder
{
	public $data; // Data coming in from installer
	public $theme_path;
	public $system_path;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	    // ------------------------------------
	    //  Encrypt password and Unique ID
	    // ------------------------------------

	    $unique_id = Uuid::uuid4();
		$password  = Hash::make($this->data['password']);

    	$now	= Carbon::now()->toDateTimeString();

	    $themes_path = $this->system_path.'resources'.DIRECTORY_SEPARATOR.'site_themes'.DIRECTORY_SEPARATOR;

	    // -----------------------------------
		//  Default Site!
		// -----------------------------------

		DB::table('sites')
			->insert([
				'site_id'         => 1,
				'site_name'       => $this->data['site_name'],
				'site_handle'     => 'default_site'
			]);

	    // -----------------------------------
		//  Default Domain!
		// -----------------------------------

		$domain = parse_url($this->data['site_url'], PHP_URL_HOST);

		DB::table('domains')
			->insert([
				'site_id'       => 1,
				'domain'      	=> $domain,
				'site_url'      => $this->data['site_url'],
				'cms_path'		=> '',
				'public_path'	=> ''
			]);

		// -----------------------------------
		//  Site - Template Group Templates
		// -----------------------------------

		require $this->theme_path;

		foreach ($template_matrix as $template)
		{
			$name = $template[0];

			DB::table('templates')
				->insert(
					[
						'folder' => '/',
						'template_name'  => $name,
						'template_type'  => $template[1],
						'template_data'  => $name(),
						'updated_at'     => $now,
						'last_author_id' => 1
					]);
		}

		unset($template_matrix);

		// -----------------------------------
		//  RSS/ATOM Templates
		// -----------------------------------

		require $themes_path.'rss/rss.php';

		DB::table('templates')
			->insert(
				[
					'folder' => '/',
					'template_name'  => 'atom',
					'template_type'  => 'atom',
					'template_data'  => atom(),
					'updated_at'     => $now,
					'last_author_id' => 1
				]);

		DB::table('templates')
			->insert(
				[
					'folder' => '/',
					'template_name'  => 'rss',
					'template_type'  => 'rss',
					'template_data'  => rss_2(),
					'updated_at'     => $now,
					'last_author_id' => 1
				]);

		unset($template_matrix);

		// -----------------------------------
		//  Search Templates
		// -----------------------------------

		require $themes_path.'search/search.php';

		foreach ($template_matrix as $template)
		{
			$name = $template[0];

			DB::table('templates')
				->insert(
					[
						'folder'    => '/search',
						'template_name'  => ($name == 'search_index') ? 'index' : $name,
						'template_type'  => $template[1],
						'template_data'  => $name(),
						'updated_at'     => $now,
						'last_author_id' => 1
					]);
		}

		// --------------------------------------------------------------------
		//  Default Weblog - Preferences, Fields, Statuses, Categories
		// --------------------------------------------------------------------

		DB::table('weblogs')
			->insert([
				'weblog_id' 		   	=> '1',
				'cat_group' 		   	=> '1',
				'blog_name'		 	    => 'default_site',
				'blog_title' 		    => 'Default Site Weblog',
				'blog_url' 			    => $this->data['site_url'].$this->data['site_index'].'/site/index/',
				'comment_url' 		    => $this->data['site_url'].$this->data['site_index'].'/site/comments/',
				'total_entries' 	    => 1,
				'last_entry_date'       => $now,
				'status_group'          => 1,
				'default_status' 		     => 'open',
				'field_group' 		         => 1,
				'allow_comments_default'     => 'y',
				'comment_max_chars'          => 5000,
				'comment_require_email'      => 'y',
				'comment_require_membership' => 'n',
			]);

		// Custom Fields
		DB::table('field_groups')
			->insert([
				'group_id'   => 1,
				'group_name' => 'Default Field Group'
			]);

		$fields = [
			[1, 'excerpt', 'Excerpt', 'Excerpts are optional hand-crafted summaries of your content.', 3, 'n'],
			[2, 'body', 'Body', '', 10, 'n'],
			[3, 'extended', 'Extended', 'Excerpts are optional hand-crafted summaries of your content.', 12 , 'y']
		];

		foreach($fields as $key => $field) {

			DB::table('weblog_fields')
				->insert([
					'field_id'      	 => $field[0],
					'group_id'      	 => 1,
					'field_name'    	 => $field[1],
					'field_label'		 => $field[2],
					'field_instructions' => $field[3],
					'field_ta_rows'		 => $field[4],
					'field_type'         => 'textarea',
					'field_search'		 => 'y',
					'field_is_hidden'    => $field[5]
				]);
		}

		// Custom statuses
		DB::table('status_groups')
			->insert([
				'group_id'   => 1,
				'group_name' => 'Default Status Group'
			]);

		DB::table('statuses')
			->insert([
				'group_id'     => 1,
				'status'	   => 'open',
				'status_order' => 1
			]);

		DB::table('statuses')
			->insert([
				'group_id'     => 1,
				'status' 	   => 'closed',
				'status_order' => 2
			]);


		// Member groups - Super Admins
		DB::table('member_groups')
			->insert(
			[
				'group_id'					 => 1,
				'group_name'				 => 'Super Admins',
				'group_description' 		 => ''
			]);

		// SuperAdmin has no group preferences for they are AS GODS
		$prefs = [ ];

		foreach($prefs as $handle => $value) {
			DB::table('member_group_preferences')
				->insert([
					'group_id'	=> 1
					'handle' 	=> $handle,
					'value'  	=> $value
				]);
		}

		// Member groups - Banned
		DB::table('member_groups')
			->insert(
			[
				'group_id'					 => 2,
				'group_name'				 => 'Banned',
				'group_description' 		 => ''
			]
		);

		$prefs = [
			'is_locked'					 => 'y',
			'can_view_offline_system'	 => 'n',
			'can_access_cp'				 => 'n',
			'can_access_publish'		 => 'n',
			'can_access_edit'			 => 'n',
			'can_access_design'			 => 'n',
			'can_access_plugins'		 => 'n',
			'can_access_admin'			 => 'n',
			'can_admin_weblogs'			 => 'n',
			'can_admin_members'			 => 'n',
			'can_delete_members'		 => 'n',
			'can_admin_mbr_groups'		 => 'n',
			'can_ban_users'				 => 'n',
			'can_admin_utilities'		 => 'n',
			'can_admin_preferences'		 => 'n',
			'can_admin_plugins'			 => 'n',
			'can_admin_templates'		 => 'n',
			'can_edit_categories'		 => 'n',
			'can_view_other_entries'	 => 'n',
			'can_edit_other_entries'	 => 'n',
			'can_assign_post_authors'	 => 'n',
			'can_delete_self_entries'	 => 'n',
			'can_delete_all_entries'	 => 'n',
			'can_view_other_comments'	 => 'n',
			'can_edit_own_comments'		 => 'n',
			'can_delete_own_comments'	 => 'n',
			'can_edit_all_comments'		 => 'n',
			'can_delete_all_comments'	 => 'n',
			'can_moderate_comments'		 => 'n',
			'can_delete_self'			 => 'n',
			'mbr_delete_notify_emails'	 => '',
			'can_post_comments'			 => 'n',
			'exclude_from_moderation'	 => 'n',
			'can_search'				 => 'n',
			'search_flood_control'		 => 60,
			'can_send_bulletins'			 => 'n',
			'include_in_authorlist'			 => 'n',
			'include_in_memberlist'			 => 'n',
			'can_access_cp_site_id_1'	 	 => 'n',
			'can_access_offline_site_id_1' 	 => 'n',
		];

		foreach($prefs as $handle => $value) {
			DB::table('member_group_preferences')
				->insert([
					'group_id'	=> 2
					'handle' 	=> $handle,
					'value'  	=> $value
				]);
		}

		// Member Group - Guests
		DB::table('member_groups')
			->insert(
			[
				'group_id'					 => 3,
				'group_name'				 => 'Guests',
				'group_description' 		 => ''
			]
		);

		$prefs = [
			'is_locked'					 => 'y',
			'can_view_offline_system'	 => 'n',
			'can_access_cp'				 => 'n',
			'can_access_publish'		 => 'n',
			'can_access_edit'			 => 'n',
			'can_access_design'			 => 'n',
			'can_access_plugins'		 => 'n',
			'can_access_admin'			 => 'n',
			'can_admin_weblogs'			 => 'n',
			'can_admin_members'			 => 'n',
			'can_delete_members'		 => 'n',
			'can_admin_mbr_groups'		 => 'n',
			'can_ban_users'				 => 'n',
			'can_admin_utilities'		 => 'n',
			'can_admin_preferences'		 => 'n',
			'can_admin_plugins'			 => 'n',
			'can_admin_templates'		 => 'n',
			'can_edit_categories'		 => 'n',
			'can_view_other_entries'	 => 'n',
			'can_edit_other_entries'	 => 'n',
			'can_assign_post_authors'	 => 'n',
			'can_delete_self_entries'	 => 'n',
			'can_delete_all_entries'	 => 'n',
			'can_view_other_comments'	 => 'n',
			'can_edit_own_comments'		 => 'n',
			'can_delete_own_comments'	 => 'n',
			'can_edit_all_comments'		 => 'n',
			'can_delete_all_comments'	 => 'n',
			'can_moderate_comments'		 => 'n',
			'can_delete_self'			 => 'n',
			'mbr_delete_notify_emails'	 => '',
			'can_post_comments'			 => 'y',
			'exclude_from_moderation'	 => 'n',
			'can_search'				 => 'y',
			'search_flood_control'		 => 15,
			'can_send_bulletins'			 => 'n',
			'include_in_authorlist'			 => 'n',
			'include_in_memberlist'			 => 'y',
			'can_access_cp_site_id_1'	 	 => 'n',
			'can_access_offline_site_id_1' 	 => 'n',
		];

		foreach($prefs as $handle => $value) {
			DB::table('member_group_preferences')
				->insert([
					'group_id'	=> 3
					'handle' 	=> $handle,
					'value'  	=> $value
				]);
		}


		// Member Group - Pending
		DB::table('member_groups')
			->insert(
			[
				'group_id'					 => 4,
				'group_name'				 => 'Pending',
				'group_description' 		 => ''
			]);

		$prefs = [
			'is_locked'					 => 'y',
			'can_view_offline_system'	 => 'n',
			'can_access_cp'				 => 'y',
			'can_access_publish'		 => 'n',
			'can_access_edit'			 => 'n',
			'can_access_design'			 => 'n',
			'can_access_plugins'		 => 'n',
			'can_access_admin'			 => 'n',
			'can_admin_weblogs'			 => 'n',
			'can_admin_members'			 => 'n',
			'can_delete_members'		 => 'n',
			'can_admin_mbr_groups'		 => 'n',
			'can_ban_users'				 => 'n',
			'can_admin_utilities'		 => 'n',
			'can_admin_preferences'		 => 'n',
			'can_admin_plugins'			 => 'n',
			'can_admin_templates'		 => 'n',
			'can_edit_categories'		 => 'n',
			'can_view_other_entries'	 => 'n',
			'can_edit_other_entries'	 => 'n',
			'can_assign_post_authors'	 => 'n',
			'can_delete_self_entries'	 => 'n',
			'can_delete_all_entries'	 => 'n',
			'can_view_other_comments'	 => 'n',
			'can_edit_own_comments'		 => 'n',
			'can_delete_own_comments'	 => 'n',
			'can_edit_all_comments'		 => 'n',
			'can_delete_all_comments'	 => 'n',
			'can_moderate_comments'		 => 'n',
			'can_delete_self'			 => 'n',
			'mbr_delete_notify_emails'	 => '',
			'can_post_comments'			 => 'y',
			'exclude_from_moderation'	 => 'n',
			'can_search'				 => 'y',
			'search_flood_control'		 => 15,
			'can_send_bulletins'			 => 'n',
			'include_in_authorlist'			 => 'n',
			'include_in_memberlist'			 => 'y',
			'can_access_cp_site_id_1'	 	 => 'n',
			'can_access_offline_site_id_1' 	 => 'n',
		];

		foreach($prefs as $handle => $value) {
			DB::table('member_group_preferences')
				->insert([
					'group_id'	=> 4
					'handle' 	=> $handle,
					'value'  	=> $value
				]);
		}

		// Member Group - Members
		DB::table('member_groups')
			->insert(
			[
				'group_id'					 => 5,
				'group_name'				 => 'Members',
				'group_description' 		 => ''
			]);

		$prefs = [
			'is_locked'					 => 'y',
			'can_view_offline_system'	 => 'n',
			'can_access_cp'				 => 'y',
			'can_access_publish'		 => 'n',
			'can_access_edit'			 => 'n',
			'can_access_design'			 => 'n',
			'can_access_plugins'		 => 'n',
			'can_access_admin'			 => 'n',
			'can_admin_weblogs'			 => 'n',
			'can_admin_members'			 => 'n',
			'can_delete_members'		 => 'n',
			'can_admin_mbr_groups'		 => 'n',
			'can_ban_users'				 => 'n',
			'can_admin_utilities'		 => 'n',
			'can_admin_preferences'		 => 'n',
			'can_admin_plugins'			 => 'n',
			'can_admin_templates'		 => 'n',
			'can_edit_categories'		 => 'n',
			'can_view_other_entries'	 => 'n',
			'can_edit_other_entries'	 => 'n',
			'can_assign_post_authors'	 => 'n',
			'can_delete_self_entries'	 => 'n',
			'can_delete_all_entries'	 => 'n',
			'can_view_other_comments'	 => 'n',
			'can_edit_own_comments'		 => 'n',
			'can_delete_own_comments'	 => 'n',
			'can_edit_all_comments'		 => 'n',
			'can_delete_all_comments'	 => 'n',
			'can_moderate_comments'		 => 'n',
			'can_delete_self'			 => 'n',
			'mbr_delete_notify_emails'	 => '',
			'can_post_comments'			 => 'y',
			'exclude_from_moderation'	 => 'n',
			'can_search'				 => 'y',
			'search_flood_control'		 => 10,
			'can_send_bulletins'		 => 'n',
			'include_in_authorlist'		 => 'n',
			'include_in_memberlist'		 => 'y',
			'can_access_cp_site_id_1'	 	 => 'n',
			'can_access_offline_site_id_1' 	 => 'n',
		];

		foreach($prefs as $handle => $value) {
			DB::table('member_group_preferences')
				->insert([
					'group_id'	=> 5
					'handle' 	=> $handle,
					'value'  	=> $value
				]);
		}

		// --------------------------------------------------------------------
		//  Default SuperAdmin User!
		// --------------------------------------------------------------------

		DB::table('members')
			->insert(
			[
				'member_id'    		=> 1,
				'group_id'     		=> 1,
				'password'     		=> $password,
				'unique_id'	   		=> $unique_id,
				'email'		   		=> $this->data['email'],
				'screen_name'  		=> $this->data['screen_name'],
				'join_date'			=> $now,
				'ip_address'		=> $this->data['ip'],
				'total_entries'		=> 1,
				'last_entry_date' 	=> $now,
				'quick_links'   	=> '',
				'language'      	=> $this->data['deft_lang']
			]);

		DB::table('member_homepage')
			->insert(
				[
					'member_id' => 1,
					'recent_entries_order' => 1,
					'recent_comments_order' => 1,
					'site_statistics_order' => 2,
					'notepad_order' => 2,

				]
			);

		DB::table('member_data')->insert(['member_id' => 1]);

		// --------------------------------------------------------------------
		//  System Stats
		// --------------------------------------------------------------------

		DB::table('stats')
			->insert(
				[
					'total_members' => 1,
					'total_entries' => 1,
					'last_entry_date' => $now,
					'recent_member' => $this->data['screen_name'],
					'recent_member_id' => 1,
					'last_cache_clear' => $now
				]
			);

		// --------------------------------------------------------------------
		//  Default Categories
		// --------------------------------------------------------------------

		DB::table('category_groups')
			->insert(
				[
					'group_id' => 1,
					'group_name' => 'Default Category Group'
				]
			);

		$categories = [
			'Music', 'Travel', 'Photography', 'Learning', 'Outdoors'
		];

		foreach($categories as $key => $category) {
			DB::table('categories')
				->insert(
					[
						'category_id'	 		=> $key + 1,
						'group_id'   			=> 1,
						'parent_id'	 			=> 0,
						'category_name' 	    => $category,
						'category_url_title'	=> $category,
						'category_description'  => '',
						'category_order'		=> $key + 1
					]
				);
		}

		DB::table('cms_weblog_entry_categories')
			->insert(
				[
					'entry_id' 		=> 1,
					'category_id'   => 4
				]
			);

		// --------------------------------------------------------------------
		//  First Weblog Entry! Yay!!
		// --------------------------------------------------------------------

		$body = <<<ENTRY
Thank you for choosing Kilvin CMS!

This entry contains helpful resources to help you get the most from Kilvin CMS and the Kilvin Community.


<h3>Community Technical Support:</h3>

Community technical support is handled through our Slack Channel.
Our community is full of knowledgeable and helpful people that will often reply quickly to your technical questions.
Please review the <a href="https://arliden.com/docs/support.html">Support</a> section
of our User Guide before posting in Slack.


<h3>Premium Support:</h3>

With our <a href="https://arliden.com/premium-support">support subscriptions</a>
you can receive premium support for Kilvin CMS from the maintainers of the code.

Get help on how to best begin your development process, how to organise your team of developers
working on the same project for maximum productivity, and answers to prompt, in-depth
answers to your technical questions from the experts.

Please review our <a href="https://arliden.com/premium-support">Premium Support</a> page for additional information.


<h3>Learning and support resources:</h3>

<a href="https://arliden.com/getting_started.html">Getting Started Guide</a>
<a href="https://arliden.com/quick_start.html">Quick Start Tutorial</a>
<a href="https://arliden.com/docs/">Kilvin CMS - Documentation</a>
<a href="https://arliden.com/faq/">Kilvin CMS - FAQ</a>


Love Kilvin CMS? Please tell your friends and professionals associates.

Enjoy!

<strong>The Kilvin CMS Team</strong>
ENTRY;

		DB::table('weblog_entries')
			->insert(
			[
				'entry_id' 		=> 1,
				'weblog_id'		=> 1,
				'author_id'		=> 1,
				'entry_date'	=> $now,
				'updated_at'	=> $now,
				'url_title' 	=> 'getting-started',
				'status'		=> 'open'
			]);

		DB::table('weblog_entry_data')
			->insert(
			[
				'entry_id' 		 => 1,
				'weblog_id'		 => 1,
				'title'			 => 'Getting Started with Kilvin CMS',
				'field_excerpt'  => '',
				'field_body'	 => $body,
				'field_extended' => ''
			]);

		// --------------------------------------------------------------------
		//  Upload Prefs
		// --------------------------------------------------------------------

		if (@realpath(str_replace('../', './', $this->data['image_path'])) !== FALSE)
		{
			$this->data['image_path'] = str_replace('../', './', $this->data['image_path']);
			$this->data['image_path'] = str_replace("\\", "/", realpath($this->data['image_path'])).'/';
		}

		DB::table('upload_prefs')
			->insert(
			[
				'id'			=> 1,
				'name'			=> 'Mail Upload Directory',
				'server_path'	=> $this->data['image_path'].$this->data['upload_folder'],
				'url'			=> $this->data['site_url'].'images/'.$this->data['upload_folder'],
				'allowed_types'	=> 'all',
				'properties'	=> 'style="border: 0;" alt="Image"'
			]);

		// --------------------------------------------------------------------
		//  Comments plugin
		// --------------------------------------------------------------------

		DB::table('plugins')
			->insert(
				[
					'plugin_name' => 'Comments',
					'plugin_version' => '1.0.0',
					'has_cp' => 'y'
				]
			);

		// --------------------------------------------------------------------
		//  Members plugin
		// --------------------------------------------------------------------

		DB::table('plugins')
			->insert(
				[
					'plugin_name' => 'Members',
					'plugin_version' => '1.0.0',
					'has_cp' => 'n'
				]
			);

		// --------------------------------------------------------------------
		//  Query, RSS, Stats plugins
		// --------------------------------------------------------------------

		DB::table('plugins')
			->insert(
				[
					'plugin_name' => 'Query',
					'plugin_version' => '1.0.0',
					'has_cp' => 'n'
				]
			);


		DB::table('plugins')
			->insert(
				[
					'plugin_name' => 'Rss',
					'plugin_version' => '1.0.0',
					'has_cp' => 'n'
				]
			);

		DB::table('plugins')
			->insert(
				[
					'plugin_name' => 'Stats',
					'plugin_version' => '1.0.0',
					'has_cp' => 'n'
				]
			);

		// --------------------------------------------------------------------
		//  Weblogs plugin
		// --------------------------------------------------------------------

		DB::table('plugins')
			->insert(
				[
					'plugin_name' => 'Weblogs',
					'plugin_version' => '1.0.0',
					'has_cp' => 'n'
				]
			);

		// --------------------------------------------------------------------
		//  Search plugin
		// --------------------------------------------------------------------

		DB::table('plugins')
			->insert(
				[
					'plugin_name' => 'Search',
					'plugin_version' => '1.0.0',
					'has_cp' => 'n'
				]
			);
    }
}
