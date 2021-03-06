<?php

return [

// ---------------------------
//  Member List Config
// ---------------------------

"xss_clean_uploads" =>
"Apply XSS Filtering to uploaded files?",

'total_comments' =>
"Total Comments",

'total_entries' =>
"Total Entries",

// ---------------------------
//  Explanatory Blurbs
// ---------------------------

'system_admin_blurb' =>
"Most of the administrative aspects of Kilvin CMS are managed from one of the following five areas:",

'weblog_administration_blurb' =>
"This area enables you to manage your weblogs, custom fields, weblog preferences, etc.",

'members_and_groups_blurb' =>
"This area allows you to manage members, member groups, and membership-related features.",

'notification_messages_blurb' =>
"These templates are used for special purposes such as displaying error messages and sending email notifications.",

'site_preferences_blurb' =>
"This area enables you to manage your Site's preferences for everything from debugging to security to themes.",

'utilities_blurb' =>
"This area contains ancillary utilities that help you manage Kilvin CMS' data.",

'image_preferences_blurb' =>
"File uploading and image resizing are managed here.",

'search' =>
"Search",

'search_preferences' =>
"Search Preferences",

'no_search_results' =>
"No Results Found",

'search_results' =>
"Search Results",


// ---------------------------
//  Extensions Stuff
// ---------------------------

"censor_replacement" =>
"Censoring Replacement Word",

"censor_replacement_info" =>
"If left blank censored words will be replaced with: #",

"censored_wildcards" =>
"Wild cards are allowed.  For example, the wildcard  test* would censor the words test, testing, tester, etc.",

'settings' =>
"Settings",

'documentation' =>
"Documentation",

'status' =>
"Status",

//----------------------------
// Admin Page
//----------------------------

"guest" =>
"Guest",

'wiki_search' =>
'Wiki',

"site_search" =>
"Site",

"searched_in" =>
"Searched In",

"search_terms" =>
"Search Terms",

"screen_name" =>
"Screen Name",

"throttling_cfg" =>
"Throttling Configuration",

"banish_masked_ips" =>
"Deny Access if No IP Address is Present",

"max_page_loads" =>
"Maximum Number of Page Loads",

"max_page_loads_exp" =>
"The total number of times a user is allowed to load any of your web pages (within the time interval below) before being locked out.",

"time_interval" =>
"Time Interval (in seconds)",

"time_interval_exp" =>
"The number of seconds during which the above number of page loads are allowed.",

"lockout_time" =>
"Lockout Time (in seconds)",

"lockout_time_exp" =>
"The length of time a user should be locked out of your site if they exceed the limits.",

"banishment_type" =>
"Action to Take",

"banishment_type_exp" =>
"The action that should take place if a user has exceeded the limits.",

"url_redirect" =>
"URL Redirect",

"404_page" =>
"Send 404 headers",

"show_message" =>
"Show custom message",

"banishment_url" =>
"URL for Redirect",

"banishment_url_exp" =>
"If you chose the URL Redirect option.",

"banishment_message" =>
"Custom Message",

"banishment_message_exp" =>
"If you chose the Custom Message option.",

'ip_search' =>
"IP Address Search",

'ip_search_no_results' =>
"No results for IP Search",

"click" =>
"Click",

"hover" =>
"Hover",

"enable_throttling" =>
"Enable Throttling",

"enable_throttling_explanation" =>
"This feature generates a 404 header and message if a request to your site is made in which the template group does not exist in the URL. It is intended primarily to keep search engine crawlers from repeatedly requesting nonexistent pages.",

"max_caches" =>
"Maximum Number of Cachable URIs",

"max_caches_explanation" =>
"If you cache your pages or your database, this preference limits the total number of cache instances in order to prevent your cache files from taking up too much disk space.  150 is a good number for a small site.  If you have a large site and disk space is not an issue you can set it higher (over 300).  We have an internal limit of 1000 regardless of your preference.",

"standby_recount" =>
"Recounting... please stand by...",

"theme_folder_url" =>
"URL to your \"themes\" folder",

"sql_good_query" =>
"Your query was successful",

"must_submit_number" =>
"You must submit the number of days to filter the pruning routine by.",

"must_submit_group" =>
"You must choose at least one member group",

"must_submit_blog" =>
"You must choose at least one weblog",

"no_members_matched" =>
"There are no member accounts matching the criteria you submitted",

"good_member_pruning" =>
"%x member accounts have been deleted",

"prune_member_confirm_msg" =>
"Are you sure you want to delete the member accounts you specified?",

"member_pruning" =>
"Membership Account Pruning",

"mbr_prune_x_days" =>
"Delete membership accounts that are more than X days old",

"mbr_prune_never_posted" =>
"Only delete users who have never posted entries or comments.",

"mbr_prune_zero_note" =>
"If you submit a zero, member accounts from any date will be deleted.",

"mbr_prune_groups" =>
"Delete only within the selected groups",

"weblog_entry_pruning" =>
"Weblog Entry Pruning",

"weblog_prune_x_days" =>
"Delete weblog entries that are more than X days old",

"weblog_prune_never_posted" =>
"Only delete entries that have no comments",

"prune_entry_confirm_msg" =>
"Are you sure you want to delete the weblog entries you specified?",

"no_entries_matched" =>
"There are no weblog entries matching the criteria you submitted",

"good_entry_pruning" =>
"%x weblog entries have been deleted",

"select_prune_blogs" =>
"Delete only within the selected weblogs",

"comment_pruning" =>
"Comment Pruning",

"comment_prune_x_days" =>
"Delete comments that are more than X days old",

"no_comments_matched" =>
"There are no comments matching the criteria you submitted",

"good_commennt_pruning" =>
"%x comments have been deleted",

"prune_comment_confirm_msg" =>
"Are you sure you want to delete the comments you specified?",

"topic_pruning" =>
"Forum Topic Pruning",

"prune_if_no_posts" =>
"Delete topics only if they do not contain any posts",

"must_select_one" =>
"You must select at least one",

"html_safe" =>
"Allow only safe HTML",

"html_all" =>
"Allow all HTML (not recommended)",

"html_none" =>
"Convert HTML into character entities",

"html_header" =>
"HTML Header",

"page_header" =>
"Page Header",

"page_subheader" =>
"Page Sub-header",

"import_utilities" =>
"Import Utilities",

"html_footer" =>
"HTML Footer",

"breadcrumb_trail" =>
"Breadcrumb Trail",

"breadcrumb_current_page" =>
"Breadcrumb Current Page",

'image_preferences' =>
"Image Preferences",

"image_resizing" =>
"Image Resizing Preferences",

"debugging_preferences" =>
"Debugging Preferences",

"category_trigger_duplication" =>
"A template or template group with this name already exists.",

"invalid_path" =>
"The following path you submitted is not valid:",

"not_writable_path" =>
"The path you submitted is not writeable.  Please make sure the file permissions are set to 777.",

"notification_cfg" =>
"Notification Preferences",

"photo_cfg" =>
"Member Photo Preferences",

"enable_photos" =>
"Enable Member Photos",

"photo_url" =>
"URL to Photos Folder",

"photo_path" =>
"Server Path to Photo Folder",

"photo_max_width" =>
"Photo Maximum Width",

"photo_max_height" =>
"Photo Maximum Height",

"photo_max_kb" =>
"Photo Maximum Size (in Kilobytes)",

"must_be_path" =>
"Note: Must be a full server path, NOT a URL.  Folder permissions must be set to 777.",

"ignore_noncritical" =>
"Ignore non-essential data (recommended)",

"template_rev_msg" =>
"Note: Saving your revisions can use up a lot of database space so you are encouraged to set limits below.",

"max_tmpl_revisions" =>
"Maximum Number of Revisions to Keep",

"max_revisions_exp" =>
"The maximum number of revisions that should be kept for EACH template.  For example, if you set this to 5, only the most recent 5 revisions will be saved for any given template.",

"plugin_no_curl_support" =>
"Your server does not support the Curl library, which is required in order to use this feature.",

"plugins" =>
"Plugins",

"plugin_by_date" =>
"By Date",

"plugin_by_letter" =>
"By Letter",

"plugin_requires" =>
"Requires",

"plugin_zlib_missing" =>
"Zlib library is missing.  Please consult user guide.",

"plugin_can_not_fetch" =>
"Unable to remotely retrieve the plugin",

"plugin_folder_not_writable" =>
"Your plugin folder is not writable.  File permissions must be set to 777 before this action can be performed.",

"plugin_problem_creating_file" =>
"Unable to create a local version of your plugin",

"plugin_version_check" =>
"Check Version",

"plugin_installed" =>
"Plugin(s) Installed",

"plugin_latest" =>
"Latest Plugins",

"plugin_installation" =>
"Plugin Installation",

"plugin_install" =>
"Install",

"plugin_install_status" =>
"Plugin Installation Status",

"plugin_install_success" =>
"The plugin was successfully installed.",

"plugin_install_other" =>
"The plugin file has been stored in your plugins directory.",

"plugin_error_uncompress" =>
"Unable to uncompress ZIP file. The ZIP file has been stored in your plugins directory.",

"plugin_error_no_zlib" =>
"Your server does not have zlib support, so decompression is not possible.  The ZIP file is stored in your plugins directory.",

"plugin_delete_confirm" =>
"Plugin Removal Confirmation",

"plugin_single_confirm" =>
"Are you sure you want to delete this plugin?",

"plugin_multiple_confirm" =>
"Are you sure you want to delete these plugins?",

"plugin_remove" =>
"Remove",

"plugin_removal" =>
"Plugin Removal",

"plugin_removal_status" =>
"Plugin Removal Status",

"plugin_removal_success" =>
"The following plugin was successfully removed:",

"plugin_removal_error" =>
"An error occurred removing the following plugin:",

"auto_assign_cat_parents" =>
"Auto-Assign Category Parents",

"auto_assign_cat_parents_exp" =>
"If set to \"yes\", when new entries are submitted, the parent category will be automatically assigned whenever you choose a child category",

"use_category_name" =>
"Use Category URL Titles In Links?",

"use_category_name_exp" =>
"This preference determines whether the category ID number or the category URL Title is used in category-related links.",

"reserved_category_word" =>
"Category URL Indicator",

"reserved_category_word_exp" =>
"If you set the above preference to \"yes\" you must choose a reserved word.  This word will be used in the URL to indicate to the weblog display engine that you are showing a category.  Note: whatever word you chose CAN NOT be the name of a template group or a template.",

"none" =>
"None",

"cp_image_path" =>
"URL to Control Panel Image Directory",

"auto_close" =>
"Auto",

"manual_close" =>
"Manual",

"new_posts_clear_caches" =>
"Clear all caches when new entries are posted?",

"weblog_cfg" =>
"Global Weblog Preferences",

"cp_cfg" =>
"Control Panel Settings",

"query_cfg" =>
"Query Caching Preferences",

"debug_cfg" =>
"Debugging Preferences",

"word_separator" =>
"Word Separator for URL Titles",

"dash" =>
"Dash",

"underscore" =>
"Underscore",

"site_name" =>
"Name of your site",

"system_admin" =>
"System Administration",

"site_preferences" =>
"Site Preferences",

"is_system_on" =>
"Is system on?",

"is_system_on_explanation" =>
"CMS-wide setting! If system is off, all of your sites are turned off and only SuperAdmins can view your site(s).",

"system_off_msg" =>
"System Off Message",

"offline_template" =>
"System Offline Template",

"offline_template_desc" =>
"This template contains the page that is shown when your site is offline.",

"template_updated" =>
"Template Updated",

"preference_information" =>
"Preference Guide",

"preference" =>
"Preference",

"value" =>
"Value",

"general_cfg" =>
"General Configuration",

"member_cfg" =>
"Membership Preferences",

"separate_emails" =>
"Separate multiple emails with a comma",

"default_member_group" =>
"Default Member Group Assigned to New Members",

"group_assignment_defaults_to_two" =>
"If you require account activation, members will be set to this once they are activated",

"view_email_logs" =>
"Email Console Logs",

"security_cfg" =>
"Security and Session Preferences",

"password_min_length" =>
"Minimum Password Length",

"image_path" =>
"Path to Images Directory",

"cp_url" =>
"URL to your Control Panel index page",

"with_trailing_slash" =>
"With trailing slash",

"site_url" =>
"URL to the root directory of your site",

"url_explanation" =>
"This is the directory containing your site index file.",

"site_index" =>
"Name of your site's index page",

"system_path" =>
"Absolute path to your %x folder",

"safe_mode" =>
"Is your server running PHP in Safe Mode?",

"debug" =>
"Debug Preference",


"site_debug" =>
"CMS Debugging level",

"site_debug_explanation" =>
"Enables the display of error for THIS site ONLY. The Laravel debugging setting can override this and make errors display for ALL sites.",

"show_queries" =>
"Display SQL Queries?",

"show_queries_explanation" =>
"If enabled, Super Admins will see all SQL queries displayed at the bottom of the browser window.  Useful for debugging.",

"debug_zero" =>
"0: No PHP/SQL error messages generated",

"debug_one" =>
"1: PHP/SQL error messages shown only to Super Admins",

"debug_two" =>
"2: PHP/SQL error messages shown to anyone - NOT SECURE",

"deft_lang" =>
"Default Language",

"used_in_meta_tags" =>
"Used in control panel meta tags",

"charset" =>
"Default Character Set",

"localization_cfg" =>
"Localization Settings",

"time_format" =>
"Default Time Formatting",

"united_states" =>
"United States",

"european" =>
"European",

"site_timezone" =>
"Site Timezone",

"cookie_cfg" =>
"Cookie Settings",

"cookie_domain" =>
"Cookie Domain",

"cookie_domain_explanation" =>
"Use .yourdomain.com for site-wide cookies",

"cookie_path" =>
"Cookie Path",

"cookie_path_explain" =>
"Use only if you require a specific server path for cookies",

"enable_image_resizing" =>
"Enable Image Resizing",

"enable_image_resizing_exp" =>
"When enabled, you will be able to create thumbnails when you upload images for placement in your weblog entries.",

"image_resize_protocol" =>
"Image Resizing Protocol",

"image_resize_protocol_exp" =>
"Please check with your hosting provider to verify that your server supports the chosen protocol.",

"image_library_path" =>
"Image Converter Path",

"image_library_path_exp" =>
"If you chose either ImageMagick or NetPBM you must specify the server path to the program.",

"gd" =>
"GD",

"gd2" =>
"GD 2",

"netpbm" =>
"NetPBM",

"imagemagick" =>
"ImageMagik",

"thumbnail_prefix" =>
"Image Thumbnail Suffix",

"thumbnail_prefix_exp" =>
"This suffix will be added to all auto-generated thumbnails.  Example: photo_thumb.jpg",

"email_cfg" =>
"Email Configuration",

"notification_sender_email" =>
"Notifications Email Sender",

'notification_sender_email_explanation' =>
"When notifications are sent automatically by the CMS, this will be the address in the From field.",

"cp_theme" =>
"Default Control Panel Theme",

"template_cfg" =>
"Template Preferences",

"save_tmpl_revisions" =>
"Save Template Revisions",

"censoring_cfg" =>
"Word Censoring",

"enable_censoring" =>
"Enable Word Censoring?",

"censored_words" =>
"Censored Words",

"censored_explanation" =>
"Place each word on a separate line.",

"weblog_administration" =>
"Weblog Administration",

"weblog_management" =>
"Weblog Management",

"field_management" =>
"Custom Weblog Fields",

"file_upload_prefs" =>
"File Upload Preferences",

"categories" =>
"Category Management",

"default_ping_servers" =>
"Default Ping Servers",

"status_management" =>
"Custom Entry Statuses",

"edit_preferences" =>
"Edit Preferences",

"preferences_updated" =>
"Preferences Updated",

"edit_groups" =>
"Edit Groups",

"members_and_groups" =>
"Members and Groups",

"view_members" =>
"View Members",

"member_search" =>
"Member Search",

"user_banning" =>
"User Banning",

"custom_profile_fields" =>
"Custom Profile Fields",

"email_notification_template" =>
"Email Notification Templates",

"member_groups" =>
"Member Groups",

"utilities" =>
"Utilities",

"view_log_files" =>
"View Control Panel Log",

"clear_caching" =>
"Clear Cached Data",

"page_caching" =>
"Page (template) cache files",

"db_caching" =>
"Database cache files",

"all_caching" =>
"All caches",

"cache_deleted" =>
"Cache files have been deleted",

"php_info" =>
"PHP Info",

"sql_info" =>
"SQL Info",

"sql_utilities" =>
"SQL Utilities",

"database_type" =>
"Database Type",

"sql_version" =>
"Database Version",

"database_size" =>
"Database Size",

"database_uptime" =>
"Database Uptime",

"total_queries" =>
"Total server queries since startup",

"sql_status" =>
"Status Info",

"sql_system_vars" =>
"System Variables",

"sql_processlist" =>
"Process List",

"sql_query" =>
"Database Query Form",

"query_result" =>
"Query Result",

"query" =>
"SQL Query",

"total_results" =>
"Total Results: %x",

"total_affected_rows" =>
"Total Affected Rows: ",

"browse" =>
"Browse",

"tables" =>
"tables",

"table_name" =>
"Table Name",

"records" =>
"Records",

"size" =>
"Size",

"type" =>
"Type",

"analize" =>
"Analize Tables",

"optimize" =>
"Optimize SQL Tables",

"repair" =>
"Repair SQL Tables",

"optimize_table" =>
"Optimize selected tables",

"repair_table" =>
"Repair selected tables",

"view_table_sql" =>
"View SQL structure and data",

"backup_tables_file" =>
"Backup selected tables - Text file",

"backup_tables_zip" =>
"Backup selected tables - Zip file",

"backup_tables_gzip" =>
"Backup selected tables - Gzip file",

"select_all" =>
"Select All",

"no_buttons_selected" =>
"You must select the tables in which to perform this action",

"unsupported_compression" =>
"Your PHP installation does not support this compression method",

"backup_info" =>
"Use this form to backup your database.",

"save_as_file" =>
"Save backup to your desktop",

"view_in_browser" =>
"View backup in your browser",

"sql_query_instructions" =>
"Use this form to submit an SQL query",

'sql_query_debug' =>
'Enable MySQL Error Output',

"file_type" =>
"File Type: ",

"plain_text" =>
"Plain text",

"zip" =>
"Zip",

"gzip" =>
"Gzip",

"advanced_users_only" =>
"Advanced Users Only",

"recount_stats" =>
"Recount Statistics",

'stats_weblog_entries' =>
"Weblog Entries",

'stats_members' =>
"Members",

"preference_updated" =>
"Preference Updated",

"click_to_recount" =>
"Click to recount rows %x through %y",

"items_remaining" =>
"Records remaining:",

"recount_completed" =>
"Recount Completed",

"return_to_recount_overview" =>
"Return to Main Recount Page",

"recounting" =>
"Recounting",

"recount_info" =>
"The links below allow you to update various statistics, like how many entries each member has submitted.",

"source" =>
"Source",

"records" =>
"Database Records",

"total_records" =>
"Total Records:",

"recalculate" =>
"Recount Statistics",

"do_recount" =>
"Perform Recount",

"set_recount_prefs" =>
"Recount Preferences",

"recount_instructions" =>
"Total number of database rows processed per batch.",

"recount_instructions_cont" =>
"In order to prevent a server timeout, we recount the statistics in batches.  1000 is a safe number for most servers. If you run a high-performance or dedicated server you can increase the number.",

"exp_members" =>
"Members",

"weblog_entries" =>
"Weblog Entries",

"search_and_replace" =>
"Find and Replace",

"data_pruning" =>
"Data Pruning",

"sandr_instructions" =>
"These forms enable you to search for specific text and replace it with different text",

"search_term" =>
"Search for this text",

"replace_term" =>
"And replace it with this text",

"replace_where" =>
"In what database field do you want the replacement to occur?",

"search_replace_disclaimer" =>
"Depending on the syntax used, this function can produce undesired results.  Consult the manual and backup your database.",

"title" =>
"Title",

"weblog_entry_title" =>
"Weblog Entry Titles",

"weblog_fields" =>
"Weblog Fields:",

"templates" =>
"Templates",

"rows_replaced" =>
"Number of database records in which a replacement occurred:",

"view_database" =>
"Manage Database Tables",

"sql_backup" =>
"Database Backup",

"sql_no_result" =>
"The query you submitted did not produce any results",

"sql_not_allowed" =>
"Sorry, but that is not one of the allowed query types.",

"site_statistics" =>
"Site Statistics",

"please_set_permissions" =>
"Please set the permissions to 666 or 777 on the following directory:",

"core_language_files" =>
"Core language files:",

"plugin_language_files" =>
"Plugin language files:",

"file_saved" =>
"The file has been saved",

"notification_messages" =>
"Notification Messages",

"user_messages_template" =>
"User Message Template",

'template_debugging' =>
"Display Template Debugging?",

"template_debugging_explanation" =>
"If enabled, Super Admins will see a list of details concerning the processing of the page.  Useful for debugging.",

"view_throttle_log" =>
"View Throttle Log",

"no_throttle_logs" =>
"No IPs are currently being throttled by the system.",

'throttling_disabled' =>
"Throttling Disabled",

'hits' =>
"Hits",

'locked_out' =>
"Locked Out",

'last_activity' =>
"Last Activity",

"is_site_on" =>
"Is site on?",

"is_site_on_explanation" =>
"If site is off, only Super Admins will be able to see this site",

'theme_folder_path' =>
"Theme Folder Path",

'site_preferences' =>
"Site Preferences",

'sites_administration' =>
"Sites Administration",

'site_management' =>
"Site Management",

'yes' =>
"Yes",

'no' =>
"No",

"reserved_word" =>
"The field name you have chosen is a reserved word and can not be used.  Please see the user guide for more information.",

"list_edit_warning" =>
"If you have unsaved changes in this page they will be lost when you are transfered to the formatting editor.",

"fmt_has_changed" =>
"Note: You have selected a different field formatting choice than what was previously saved.",

"update_existing_fields" =>
"Update all existing weblog entries with your new formatting choice?",

"display_criteria" =>
"Select display criteria for PUBLISH page",

"limit" =>
"limit",

"orderby_title" =>
"Sort by Title",

"orderby_date" =>
"Sort by Date",

"sort_desc" =>
"Descending Order",

"in" =>
"in",

"sort_asc" =>
"Ascending Order",

"field_label_info" =>
"This is the name that will appear in the PUBLISH page",

"date_field" =>
"Date Field",

"update_publish_cats" =>
"Close Window and Update Categories in PUBLISH Page",

"versioning" =>
"Versioning Preferences",

"enable_versioning" =>
"Enable Entry Versioning",

"clear_versioning_data" =>
"Delete all existing revision data in this weblog",

"enable_qucksave_versioning" =>
"If Enabled, Save Revisions During Quicksave",

"quicksave_note" =>
"If you use the Quick Save feature to save entries while you write, you may not want these to create revisions.",

"max_revisions" =>
"Maximum Number of Recent Revisions per Entry",

"max_revisions_note" =>
"Versioning can use up a lot of database space so it is recommended that you limit the number of revisions.",

"field_populate_manually" =>
"Populate the menu manually",

"field_populate_from_blog" =>
"Populate the menu from another custom field",

"select_weblog_for_field" =>
"Select the field you wish to pre-populate from:",

"field_val" =>
"You must choose a field name from this menu, not a weblog name.",

"weblog_notify" =>
"Enable recipient list below for weblog entry notification?",

"status_created" =>
"Status has been created",

"field_is_hidden" =>
"Show this field by default?",

"hidden_field_blurb" =>
"This preference determines whether the field is visible in the PUBLISH page. If set to \"no\" you will see a link allowing you to open the field. ",

"include_rss_templates" =>
"Include RSS Templates",

"notification_settings" =>
"Notification Preferences",

"comment_notify_authors" =>
"Notify the author of an entry whenever a comment is submitted?",

"comment_notify" =>
"Enable recipient list below for comment notification?",

"update_existing_comments" =>
"Update all existing comments with this expiration setting?",

"category_order_confirm_text" =>
"Are you sure you want to sort this category group alphabetically?",

"category_sort_warning" =>
"If you are using a custom sort order it will be replaced with an alphabetical one.",

"global_sort_order" =>
"Master Sort Order",

"custom" =>
"Custom",

"alpha" =>
"Alphabetical",

"weblog_id" =>
"Weblog ID",

"weblog_short_name" =>
"Short Name",

"group_required" =>
"You must submit a group name.",

"comment_url" =>
"Comment Page URL",

"comment_url_exp" =>
"The URL where the comment page for this weblog is located",

"delete_category_confirmation" =>
"Are you sure you want to delete the following category?",

"category_description" =>
"Category Description",

"category_updated" =>
"Category Updated",

"new_category" =>
"Create a New Category",

"template_creation" =>
"Create New Templates For This Weblog?",

"use_a_theme" =>
"Use one of the default themes",

"duplicate_group" =>
"Duplicate an existing template group",

"template_group_name" =>
"New Template Group Name",

"new_group_instructions" =>
"Field is required if you are creating a new group",

"publish_page_customization" =>
"Publish Page Customization",

"show_url_title" =>
"Display URL Title Field",

"show_author_menu" =>
"Display Author Menu",

"show_status_menu" =>
"Display Status Menu",

"show_options_cluster" =>
"Display Option Buttons",

"show_date_menu" =>
"Display Date Fields",

"show_categories_menu" =>
"Display Category Menu",

"paths" =>
"Path Settings",

"weblog_url_exp" =>
"The URL to this particular weblog",

"search_results_url_exp" =>
"The URL where the search results from this weblog should be pointed to.",

"comment_expiration" =>
"Comment Expiration",

"comment_expiration_desc" =>
"The number of days after an entry is posted during which to allow comments.  Enter 0 (zero) for no expiration.",

"restrict_status_to_group" =>
"Restrict status to members of specific groups",

"no_publishing_groups" =>
"There are no Member Groups available that permit publishing",

"status_updated" =>
"Status has been updated",

"can_edit_status" =>
"Can access status",

"weblog_prefs" =>
"Weblog Preferences",

"weblog_settings" =>
"Weblog Posting Preferences",

"comment_prefs" =>
"Comment Posting Preferences",

"comment_moderate" =>
"Moderate Comments?",

"comment_moderate_exp" =>
"If set to yes, comments will not be visible until a moderator approves them.",

"comment_system_enabled" =>
"Allow comments in this weblog?",

"edit_weblog_prefs" =>
"Edit Weblog Preferences",

"edit_group_prefs" =>
"Edit Group Preferences",

"duplicate_weblog_prefs" =>
"Duplicate existing weblog's preferences",

"do_not_duplicate" =>
'Do Not Duplicate',

"no_weblogs_exist" =>
"There are currently no weblogs",

"create_new_weblog" =>
"Create a New Weblog",

"weblog_base_setup" =>
"Weblog Name",

"default_settings" =>
"Default Field Values",

"short_weblog_name" =>
"Short Name",

"blog_url" =>
"Weblog URL",

"blog_description" =>
"Weblog Description",

"illegal_characters" =>
"The name you submitted may only contain alpha-numeric characters, spaces, underscores, and dashes",

"comment_membership" =>
"Require membership in order to post comments?",

"comment_require_email" =>
"Require email address to post comments?",

"weblog_require_email" =>
"Require email address to post weblog entries?",

"weblog_max_chars" =>
"Maximum number of characters allowed in weblog entries",

"comment_max_chars" =>
"Maximum number of characters allowed in comments",

"comment_timelock" =>
"Comment Re-submission Time Interval",

"comment_timelock_desc" =>
"The number of seconds that must pass before a user can submit another comment.  Leave blank or set to zero for no limit.",

"convert_to_entities" =>
"Convert HTML into character entities",

"allow_safe_html" =>
"Allow only safe HTML",

"allow_all_html" =>
"Allow ALL HTML",

"allow_all_html_not_recommended" =>
"Allow all HTML (not recommended)",

"emails_of_notification_recipients" =>
"Email Address of Notification Recipient(s)",

"auto_link_urls" =>
"Automatically turn URLs and email addresses into links?",

"single_word_no_spaces_with_underscores" =>
"single word, no spaces, underscores allowed",

"full_weblog_name" =>
"Full Weblog Name",

"edit_weblog" =>
"Edit Weblog",

"weblog_name" =>
"Weblog Name",

"new_weblog" =>
"New Weblog",

"weblog_created" =>
"Weblog Created: ",

"weblog_updated" =>
"Weblog Updated: ",

"taken_weblog_name" =>
"This weblog name is already taken.",

"no_weblog_name" =>
"You must give your weblog a 'short' name.",

"no_weblog_title" =>
"You must give your weblog  a 'full' name.",

"invalid_short_name" =>
"Your weblog name must contain only alpha-numeric characters and no spaces.",

"delete_weblog" =>
"Delete Weblog",

"weblog_deleted" =>
"Weblog Deleted:",

"delete_weblog_confirmation" =>
"Are you sure you want to permanently delete this weblog?",

"be_careful" =>
"BE CAREFUL!",

"assign_group_to_weblog" =>
"Note: In order to use your new group, you must assign it to a weblog.",

"click_to_assign_group" =>
"Click here to assign it",

"default" =>
"Default",

"category" =>
"Category",

"default_status" =>
"Default Status",

"default_category" =>
"Default Category",

"allow_comments_default" =>
"Select \"Allow Comments\" button in Publish page by default?",

"no_field_group_selected" =>
"No field group available for this weblog",

"open" =>
"Open",

"closed" =>
"Closed",

"none" =>
"None",

"tag_name" =>
"Tag Name",

"tag_open" =>
"Opening Tag",

"tag_close" =>
"Closing Tag",

"accesskey" =>
"Shortcut",

"tag_order" =>
"Order",

"row" =>
"Row",

"server_name" =>
"Server Name",

"server_url" =>
"Server URL/Path",

"port" =>
"Port",

"protocol" =>
"Protocol",

"is_default" =>
"Default",

"server_order" =>
"Order",

"assign_weblogs" =>
"Choose which weblog(s) you want this group assigned to",

//----------------------------
// Category Administration
//----------------------------

"category_group" =>
"Category Group",

"category_groups" =>
"Category Groups",

"no_category_group_message" =>
"There are currently no categories",

"no_category_message" =>
"There are currently no categories assigned to this group",

"create_new_category_group" =>
"Create a New Category Group",

"edit_category_group" =>
"Edit Category Group",

"name_of_category_group" =>
"Name of category group",

"taken_category_group_name" =>
"This group name is already taken.",

"add_edit_categories" =>
"Add/Edit Categories",

"edit_group_name" =>
"Edit Group",

"delete_group" =>
"Delete Group",

"category_group_created" =>
"Category Group Created: ",

"category_group_updated" =>
"Group Updated: ",

"delete_category_group_confirmation" =>
"Are you sure you want to permanently delete this category group?",

"category_group_deleted" =>
"Category Group Deleted:",

"create_new_category" =>
"Create a New Category",

"add_new_category" =>
"Add a New Category",

"edit_category" =>
"Edit Category",

"delete_category" =>
"Delete Category",

'category_url_title' =>
'Category URL Title',

'category_url_title_is_numeric' =>
'Numbers cannot be used as Category URL Titles',

'unable_to_create_category_url_title' =>
'Unable to create valid Category URL Title for your Category',

'duplicate_category_url_title' =>
'A Category with the submitted Category URL Title already exists in this Category Group',

"category_name" =>
"Category Name",

"category_image" =>
"Category Image URL",

"category_img_blurb" =>
"This is an optional field that enables you to assign an image to your categories.",

"category_parent" =>
"Category Parent",

'can_edit_categories' =>
'Can Edit Categories',

'missing_required_fields' =>
'You Are Missing Required Field(s):',

//----------------------------
// Custom field Administration
//----------------------------

"field_group" =>
"Custom Field Group",

"field_groups" =>
"Field Groups",

"field_group_name" =>
"Field Group Name",

"custom_fields" =>
"Custom Fields",

"no_field_group_message" =>
"There are currently no custom weblog fields",

"create_new_field_group" =>
"Create a New Weblog Field Group",

"new_field_group" =>
"New Field Group",

"add_edit_fields" =>
"Add/Edit Custom Fields",

"edit_field_group_name" =>
"Edit Field Group",

"delete_field_group" =>
"Delete Field Group",

"create_new_field" =>
"Create a New Field",

"edit_field" =>
"Edit Field",

"no_field_groups" =>
"There are no custom fields in this group",

"delete_field" =>
"Delete Field",

"field_deleted" =>
"Custom Field Deleted:",

"create_new_custom_field" =>
"Create a New Custom Field",

"field_label" =>
"Field Label",

"field_name" =>
"Field Name",

"field_name_explanation" =>
"Single word, no spaces, underscores are allowed",

"field_type" =>
"Field Type",

"field_max_length" =>
"Maxlength",

"field_max_length_cont" =>
"If you are using a \"text\" field type",

"textarea_rows" =>
"Textarea Rows",

"textarea_rows_cont" =>
"If you are using a \"textarea\" field type",

"dropdown_sub" =>
"If you are using a \"drop-down\" field type",

"field_list_items" =>
"Select Options",

"field_list_items_cont" =>
"If you chose drop-down menu",

"field_list_instructions" =>
"Put each item on a single line",

"edit_list" =>
"Edit List",

"field_order" =>
"Field Display Order",

"is_field_searchable" =>
"Is field searchable?",

"is_field_required" =>
"Is this a required field?",

"text_input" =>
"Text Input",

"textarea" =>
"Textarea",

"select_list" =>
"Drop-down List",

'site_id_mismatch' =>
'You are not logged into the correct Site to perform this action',

"no_field_name" =>
"You must submit a field name",

"no_field_label" =>
"You must submit a field label",

"invalid_characters" =>
"The field name you submitted contains invalid characters",

"custom_field_empty" =>
"The following field is required:",

"duplicate_field_name" =>
"The field name you chose is already taken",

"taken_field_group_name" =>
"The name you have chosen is already taken",

"field_group_created" =>
"Field Group Created: ",

"field_group_updated" =>
"Field Group Updated: ",

"field_group_deleted" =>
"Field Group Deleted: ",

"field_group" =>
"Field Group",

"delete_field_group_confirmation" =>
"Are you sure you want to permanently delete this custom field group?",

"delete_field_confirmation" =>
"Are you sure you want to permanently delete this custom field?",

"weblog_entries_will_be_deleted" =>
"All weblog entries contained in the above field(s) will be permanently deleted.",


//----------------------------
// Status Administration
//----------------------------

"status_group" =>
"Status Group",

"status_groups" =>
"Status Groups",

"no_status_group_message" =>
"There are currently no custom statuses",

"create_new_status_group" =>
"Create New Status Group",

"edit_status_group" =>
"Edit Status Group",

"name_of_status_group" =>
"Name of Status Group",

"taken_status_group_name" =>
"This status group name is already taken.",

"invalid_status_name" =>
"Status names can only have alpha-numeric characters, as well as spaces, underscores and hyphens.",

"duplicate_status_name" =>
"A status already exists with the same name.",

"status_group_created" =>
"Status Group Created: ",

"new_status" =>
"New Status",

"status_group_updated" =>
"Status Group Updated: ",

"add_edit_statuses" =>
"Add/Edit Statuses",

"edit_status_group_name" =>
"Edit Status Group",

"delete_status_group" =>
"Delete Status Group",

"delete_status_group_confirmation" =>
"Are you sure you want to permanently delete this status group?",

"status_group_deleted" =>
"Status Group Deleted:",

"create_new_status" =>
"Create a New Status",

"status_name" =>
"Status Name",

"status_order" =>
"Status Order",

"change_status_order" =>
"Change Status Order",

"highlight" =>
"Highlight Color (optional)",

"statuses" =>
"Statuses",

"edit_status" =>
"Edit Status",

"delete_status" =>
"Delete Status",

"delete_status_confirmation" =>
"Are you sure you want to delete the following status?",

"edit_file_upload_preferences" =>
"Edit File Upload Preferences",

"new_file_upload_preferences" =>
"New File Upload Preferences",

"file_upload_preferences" =>
"File Upload Preferences",

"no_upload_prefs" =>
"There are currently no file upload preferences",

"create_new_upload_pref" =>
"Create New Upload Destination",

"upload_pref_name" =>
"Descriptive name of upload directory",

"new_file_upload_preferences" =>
"New File Upload Destination",

"server_path" =>
"Server Path to Upload Directory",

"url_to_upload_dir" =>
"URL of Upload Directory",

"allowed_types" =>
"Allowed File Types",

"max_size" =>
"Maximum File Size (in bytes)",

"max_height" =>
"Maximum Image Height (in pixels)",

"max_width" =>
"Maximum Image Width",

"properties" =>
"Image Properties",

"pre_format" =>
"Image Pre Formatting",

"post_format" =>
"Image Post Formatting",

"no_upload_dir_name" =>
"You must submit a name for your upload directory",

"no_upload_dir_path" =>
"You must submit the path to your upload directory",

"no_upload_dir_url" =>
"You must submit the URL to your upload directory",

"duplicate_dir_name" =>
"The name of your directory is already taken",

"delete_upload_preference" =>
"Delete Upload Preference",

"delete_upload_pref_confirmation" =>
"Are you sure you want to permanently delete this preference?",

"upload_pref_deleted" =>
"Upload Preference Deleted:",

"current_upload_prefs" =>
"Current Preferences",

"restrict_to_group" =>
"Restrict file uploading to select member groups",

"restrict_notes_1" =>
"These radio buttons let you to specify which Member Groups are allowed to upload files.",

"restrict_notes_2" =>
"Super Admins can always upload files",

"restrict_notes_3" =>
"Note: File uploading is currently only allowed via the control panel",

"member_group" =>
"Member Group",

"can_upload_files" =>
"Can upload files",

"images_only" =>
"Images only",

"all_filetypes" =>
"All file types",

'file_properties' =>
"File Properties",

'file_pre_format' =>
"File Pre Formatting",

'file_post_format' =>
"File Post Formatting",

'url_title_prefix' =>
"URL Title Prefix",

'live_look_template' =>
'Live Look Template',

'no_live_look_template' =>
'- No Live Look Template -',

'invalid_url_title_prefix' =>
"Invalid URL Title Prefix",

'multiple_category_group_preferences' =>
"Multiple Category Group Preferences",

'integrate_category_groups' =>
"Integrate Category Groups",

'text_direction' =>
"Text Direction",

'ltr' =>
"Left to Right",

"rtl" =>
"Right to Left",

"direction_unavailable" =>
"Text direction is not available for your chosen field type",

'field_instructions' =>
"Field Instructions",

'field_instructions_info' =>
"Instructions for authors on how or what to enter into this custom field when submitting an entry.",

'show_show_all_cluster' =>
"Display 'Show All' Tab",

'unable_to_change_to_date_field_type' =>
"Sorry, you are unable to change an existing field to the 'date' field type.",

'clear_logs' =>
"Clear Logs",

'register_member' =>
"Register Member",

'sort_order' =>
"Sort Order",


];
