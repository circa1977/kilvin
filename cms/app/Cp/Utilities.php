<?php

namespace Groot\Cp;

use DB;
use Site;
use Stats;
use Groot\Core\Session;
use Carbon\Carbon;

class Utilities
{
    // ------------------------------------
    //  Delete cache file form
    // ------------------------------------

    function clear_cache_form($message = FALSE)
    {
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

        Cp::$title = __('admin.clear_caching');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')).
					  Cp::breadcrumbItem(__('admin.clear_caching'));

        Cp::$body = Cp::quickDiv('tableHeading', __('admin.clear_caching'));

        if ($message == TRUE)
        {
            Cp::$body  .= Cp::quickDiv('successMessage', __('admin.cache_deleted'));
        }

		Cp::$body .= Cp::div('box');
        Cp::$body .= Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=utilities'.AMP.'P=clear_caching'));

        Cp::$body .= Cp::div('littlePadding');

        if ( ! isset($_POST['type']))
        {
            $_POST['type'] = 'all';
        }

        $selected = ($_POST['type'] == 'page') ? 1 : '';

        Cp::$body .= Cp::input_radio('type', 'page', $selected).__('admin.page_caching').BR;

        $selected = ($_POST['type'] == 'tag') ? 1 : '';

        Cp::$body .= Cp::input_radio('type', 'tag', $selected).__('admin.tag_caching').BR;

        $selected = ($_POST['type'] == 'db') ? 1 : '';

        Cp::$body .= Cp::input_radio('type', 'db', $selected).__('admin.db_caching').BR;

        $selected = ($_POST['type'] == 'all') ? 1 : '';

        Cp::$body .= Cp::input_radio('type', 'all', $selected).__('admin.all_caching');

        Cp::$body .= '</div>'.PHP_EOL;
        Cp::$body .= Cp::quickDiv('littlePadding', BR.Cp::input_submit(__('admin.submit')));
        Cp::$body .= '</form>'.PHP_EOL;
        Cp::$body .= '</div>'.PHP_EOL;
    }



    // ------------------------------------
    //  Delete cache files
    // ------------------------------------

    public function clear_caching()
    {
        if ( ! Request::has('type')) {
			return Utilities::clear_cache_form();
        }

        cms_clear_caching(Request::input('type'));

        return Utilities::clear_cache_form(true);
    }

    // ------------------------------------
    //  JavaScript toggle code
    // ------------------------------------

    function toggle_code()
    {
        ob_start();

        ?>
        <script type="text/javascript">
        <!--

        function toggle(thebutton)
        {
            if (thebutton.checked)
            {
               val = true;
            }
            else
            {
               val = false;
            }

            var len = document.tables.elements.length;

            for (var i = 0; i < len; i++)
            {
                var button = document.tables.elements[i];

                var name_array = button.name.split("[");

                if (name_array[0] == "table")
                {
                    button.checked = val;
                }
            }

            document.tables.toggleflag.checked = val;
        }

        //-->
        </script>
        <?php

        $buffer = ob_get_contents();

        ob_end_clean();

        return $buffer;
    }



    // ------------------------------------
    //  Number format
    // ------------------------------------

    function byte_format($num)
    {
        if ($num >= 1000000000)
        {
            $num = round($num/107374182)/10;
            $unit  = 'GB';
        }
        elseif ($num >= 1000000)
        {
            $num = round($num/104857)/10;
            $unit  = 'MB';
        }
        elseif ($num >= 1000)
        {
            $num = round($num/102)/10;
            $unit  = 'KB';
        }
        else
        {
            $unit = 'Bytes';
        }

        return array(number_format($num, 1), $unit);
    }



    // ------------------------------------
    //  Data pruning
    // ------------------------------------

    function data_pruning()
    {
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

		$r  = Cp::tableOpen(array('class' => 'tableBorder', 'width' => '100%'));

		$r .= Cp::tableRow(array(
									array(
											'text'		=> __('admin.data_pruning'),
											'class'		=> 'tableHeading',
										)
									)
							);

		$r .= Cp::tableRow(array(
									array(
											'text'	=> Cp::quickDiv('defaultBold', Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=member_pruning', __('admin.member_pruning'))),
										)
									)
							);


		$r .= Cp::tableRow(array(
									array(
											'text'	=> Cp::quickDiv('defaultBold', Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=entry_pruning', __('admin.weblog_entry_pruning'))),
										)
									)
							);

 		$r .= '</table>'.PHP_EOL;
 		
 		Cp::$title = __('admin.utilities');
		Cp::$body  = $r;
		Cp::$crumb = Cp::anchor(
        		BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')
        	).
        	Cp::breadcrumbItem(__('admin.data_pruning'));
		
		return;
    }



    // ------------------------------------
    //  Membership pruning
    // ------------------------------------

    function member_pruning()
    {
        if ( ! Session::access('can_admin_utilities')) {
            return Cp::unauthorizedAccess();
        }

		$r = '';

        if (Request::input('update') !== null) {
        	$r .= Cp::quickDiv('successMessage', str_replace('%x', Request::input('update'), __('admin.good_member_pruning')));
        }

		$r .= Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=utilities'.AMP.'P=prune_member_conf'));

		$r .= Cp::tableOpen(array('class' => 'tableBorder', 'width' => '100%'));

		$r .= Cp::tableRow(array(
									array(
											'text'		=> __('admin.member_pruning'),
											'class'		=> 'tableHeading',
											'colspan'	=> '2'
										)
									)
							);

			$data  = Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.mbr_prune_x_days')));
			$data .= Cp::quickDiv('littlePadding', Cp::quickDiv('highlight', __('admin.mbr_prune_zero_note')));


			$r .= Cp::tableRow(array(
										array(
												'text'	=> $data,
												'width'	=> '65%'
											),
										array(
												'text'	=> Cp::input_text('days_ago', '365', '10', '4', 'input', '40px'),
												'width'	=> '35%'
											)
										)
								);



		$r .= Cp::tableRow(array(
									array(
											'text'		=> Cp::quickDiv('littlePadding', Cp::input_checkbox('post_filter', 'y', 1).NBS.__('admin.mbr_prune_never_posted')),
											'colspan'	=> '2'
										)
									)
							);

 		$r .= '</table>'.PHP_EOL;

		$query = DB::table('member_groups')
			->select('group_id', 'group_name')
			->where('group_id', '!=', 1)
			->orderBy('group_name')
			->get();

		$r .= Cp::quickDiv('tableHeading', __('admin.mbr_prune_groups'));

		$r .= Cp::tableOpen(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= Cp::tableRow(array(
									array(
											'text' 	=> __('admin.must_select_one'),
											'class'	=> 'tableHeadingAlt'
										)
								)
							);

		$i = 0;
		foreach ($query as $row)
		{
			// Translate groups if needed

            $group_name = $row->group_name;

            if (in_array($group_name, array('Guests', 'Banned', 'Members', 'Pending', 'Super Admins')))
            {
                $group_name = __(strtolower(str_replace(" ", "_", $group_name)));
            }

            $group_name = str_replace(' ', NBS, $group_name);

			// ------------------------------------
			//  Write group rows
			// ------------------------------------


			$r .= Cp::tableRow(array(
										array(
												'text' 	=> Cp::quickDiv('defaultBold', Cp::input_checkbox('group_'.$row->group_id, 'y').NBS.$group_name),
												'class'	=> $class,
												'width'	=> '50%'
											)
									)
								);
			}

 		$r .= '</table>'.PHP_EOL;

 		$r .= Cp::quickDiv('paddingTop', Cp::input_submit());
 		$r .= '</form>'.PHP_EOL;

        $c = Cp::anchor(BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')).
			 Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=prune', __('admin.data_pruning'))).
			 Cp::breadcrumbItem(__('admin.member_pruning'));
			 
		Cp::$title = __('admin.member_pruning');
		Cp::$body  = $r;
		Cp::$crumb = $c;
		return;
    }



    // ------------------------------------
    //  Prune Member Confirmation
    // ------------------------------------

    function prune_member_confirm()
    {
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

		// ------------------------------------
		//  Did they submit the number of day?
		// ------------------------------------

		if ( ! is_numeric($_POST['days_ago']))
		{
			return Cp::errorMessage(__('admin.must_submit_number'));
		}

		// ------------------------------------
		//  Did they submit member groups?
		// ------------------------------------

		$groups = false;

		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 6) == 'group_')
			{
				$groups = true;
				break;
			}
		}

		if ($groups == FALSE)
		{
			return Cp::errorMessage(__('admin.must_submit_group'));
		}

		$r = Cp::deleteConfirmation(
										array(
												'url'			=> 'C=Administration'.AMP.'M=utilities'.AMP.'P=prune_members',
												'heading'		=> 'member_pruning',
												'message'		=> 'prune_member_confirm_msg',
												'hidden'		=> $_POST
											)
										);

        $c = Cp::anchor(BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')).
			 Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=prune', __('admin.data_pruning'))).
			 Cp::breadcrumbItem(__('admin.member_pruning'));
			 
		Cp::$title = __('admin.member_pruning');
		Cp::$body  = $r;
		Cp::$crumb = $c;
		return;
    }


    // ------------------------------------
    //  Prune Member Data
    // ------------------------------------

    function prune_members()
    {
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

		// ------------------------------------
		//  Did they submit the number of day?
		// ------------------------------------

		if ( ! is_numeric($_POST['days_ago']))
		{
			return Cp::errorMessage(__('admin.must_submit_number'), 2);
		}

		// ------------------------------------
		//  Assign the member groups
		// ------------------------------------

		$groups = [];

		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 6) == 'group_')
			{
				if (substr($key, 6) != 1)
				{
					$groups[] = substr($key, 6);
				}
			}
		}

		// ------------------------------------
		//  Did they submit member groups?
		// ------------------------------------

		if (empty($groups)) {
			return Cp::errorMessage(__('admin.must_submit_group'), 2);
		}

		// ------------------------------------
		//  Fetch the member IDs
		// ------------------------------------

		$query = DB::table('members AS m')
			->select('m.member_id')
			->where('m.group_id', '!=', 1)
			->whereIn('m.group_id', $groups);

		if (!empty($_POST['days_ago'] > 0)) {
			$query->where('join_date', '<', Carbon::now()->subDays((int) $_POST['days_ago'])->timestamp);
		}

		if (isset($_POST['post_filter']))
		{
			$query->leftJoin('weblog_entries', 'weblog_entries.author_id', '=', 'm.member_id')
				->whereNull('weblog_entries.author_id');
		}

		$query = $query->get();

		if ($query->count() == 0) {
			return Cp::errorMessage(__('admin.no_members_matched'), 2);
		}


		$total = 0;
		foreach ($query as $row)
		{
			$deletes = [
				'members' => 'member_id',
				'member_data' => 'member_id',
				'member_homepage' => 'member_id'
			];

			foreach($deletes as $table => $field) {
				DB::table($table)->where($field, $row->member_id)->delete();
			}

			$total++;
        }

        // Update global stats
		Stats::update_member_stats();

		return redirect('?C=Administration&M=utilities&P=member_pruning&update='.$total);
	}


    // ------------------------------------
    //  Weblog Entry pruning
    // ------------------------------------

    function entry_pruning()
    {
        if ( ! Session::access('can_admin_utilities')) {
            return Cp::unauthorizedAccess();
        }

		$r = '';

        if (Request::input('update') !== null) {
        	$r .= Cp::quickDiv('successMessage', str_replace('%x', Request::input('update'), __('admin.good_entry_pruning')));
        }

		$r .= Cp::formOpen(['action' => 'C=Administration'.AMP.'M=utilities'.AMP.'P=prune_entry_conf']);

		$r .= Cp::tableOpen(['class' => 'tableBorder', 'width' => '100%']);

		$r .= Cp::tableRow(
			[
				[
					'text'		=> __('admin.weblog_entry_pruning'),
					'class'		=> 'tableHeading',
					'colspan'	=> '2'
				]
			]
		);

			$data  = Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.weblog_prune_x_days')));

			$r .= Cp::tableRow(
				[
					[
						'text'	=> $data,
						'width'	=> '50%'
					],
					[
						'text'	=> Cp::input_text('days_ago', '365', '10', '4', 'input', '40px'),
						'width'	=> '50%'
					]
				]
			);

 		$r .= '</table>'.PHP_EOL;

		$query = DB::table('weblogs')
			->orderBy('blog_title')
			->select('weblog_id', 'blog_title')
			->get();

		$r .= Cp::quickDiv('tableHeading', __('admin.select_prune_blogs'));

		$r .= Cp::tableOpen(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= Cp::tableRow(array(
									array(
											'text' 	=> __('admin.must_select_one'),
											'class'	=> 'tableHeadingAlt'
										)
								)
							);


		$i = 0;
		foreach ($query as $row)
		{

			$r .= Cp::tableRow(array(
										array(
												'text' 	=> Cp::quickDiv('defaultBold', Cp::input_checkbox('blog_'.$row->weblog_id, 'y').NBS.$row->blog_title),
												'class'	=> $class,
												'width'	=> '50%'
											)
									)
								);
			}

 		$r .= '</table>'.PHP_EOL;

 		$r .= Cp::quickDiv('paddingTop', Cp::input_submit());
 		$r .= '</form>'.PHP_EOL;

        $c = Cp::anchor(BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')).
			 Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=prune', __('admin.data_pruning'))).
			 Cp::breadcrumbItem(__('admin.weblog_entry_pruning'));
			 
		Cp::$title = __('admin.weblog_entry_pruning');
		Cp::$body  = $r;
		Cp::$crumb = $c;
		return;
    }



	// ------------------------------------
	//  Weblog Entry Pruning Confirmation
	// ------------------------------------

	function prune_entry_confirm()
	{
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

		// ------------------------------------
		//  Did they submit the number of day?
		// ------------------------------------

		if ( ! is_numeric($_POST['days_ago']))
		{
			return Cp::errorMessage(__('admin.must_submit_number'));
		}

		// ------------------------------------
		//  Did they submit blog IDs?
		// ------------------------------------

		$blogs = false;

		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'blog_')
			{
				$blogs = true;
				break;
			}
		}


		if ($blogs == FALSE)
		{
			return Cp::errorMessage(__('admin.must_submit_blog'));
		}

		$r = Cp::deleteConfirmation(
										array(
												'url'			=> 'C=Administration'.AMP.'M=utilities'.AMP.'P=prune_entries',
												'heading'		=> 'weblog_entry_pruning',
												'message'		=> 'prune_entry_confirm_msg',
												'hidden'		=> $_POST
											)
										);

        $c = Cp::anchor(BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')).
			 Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=prune', __('admin.data_pruning'))).
			 Cp::breadcrumbItem(__('admin.weblog_entry_pruning'));
			 
		Cp::$title = __('admin.weblog_entry_pruning');
		Cp::$body  = $r;
		Cp::$crumb = $c;
		return;
	}



	// ------------------------------------
	//  Prune Entries
	// ------------------------------------

	function prune_entries()
	{
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

		// ------------------------------------
		//  Did they submit the number of day?
		// ------------------------------------

		if ( ! is_numeric($_POST['days_ago']))
		{
			return Cp::errorMessage(__('admin.must_submit_number'));
		}

		// ------------------------------------
		//  Did they submit blog IDs?
		// ------------------------------------

		$blog_ids = [];

		foreach ($_POST as $key => $val) {
			if (substr($key, 0, 5) == 'blog_') {
				$blog_ids[] = substr($key, 5);
			}
		}

		if (empty($blog_ids)) {
			return Cp::errorMessage(__('admin.must_submit_blog'), 2);
		}

		$days_ago = ($_POST['days_ago'] > 0) ? (Carbon::now()->timestamp - (60*60*24*$_POST['days_ago'])) : '';

		// ------------------------------------
		//  Fetch the entry IDs
		// ------------------------------------

		$query = DB::table('weblog_entries AS t')
			->select('t.entry_id')
			->whereIn('t.weblog_id', $blog_ids);

		if ($days_ago != '') {
			$query->where('t.entry_date', '<', $days_ago);
		}

		$query = $query->get();

		if ($query->count() == 0) {
			return Cp::errorMessage(__('admin.no_entries_matched'), 2);
		}

		$total = 0;
		foreach ($query as $row)
		{
			DB::table('weblog_entries')->where('entry_id', $row->entry_id)->delete();
			DB::table('weblog_entry_data')->where('entry_id', $row->entry_id)->delete();
			DB::table('weblog_entry_categories')->where('entry_id', $row->entry_id)->delete();

			$total++;
        }

        // Update global stats
		Stats::update_member_stats();

		foreach ($blog_ids as $id) {
			Stats::update_weblog_stats($id);
		}

		return redirect('?C=Administration&M=utilities&P=entry_pruning&update='.$total);
	}

    // ------------------------------------
    //  Recalculate Statistics - Main Page
    // ------------------------------------

    function recount_statistics()
    {
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

        $sources = array('members', 'weblog_entries');

        Cp::$title = __('admin.recount_stats');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')).
			 		  Cp::breadcrumbItem(__('admin.recount_stats'));

		$right_links[] = [
			BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=recount_prefs',
			__('admin.set_recount_prefs')
		];

		$r  = Cp::header(Cp::$title, $right_links);

        $r .= Cp::quickDiv('tableHeading', __('admin.recalculate'));

        $r .= Cp::quickDiv('box', __('admin.recount_info'));

        $r .= Cp::table('tableBorder', '0', '', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableCell('tableHeadingAlt',
                                array(
                                        __('admin.source'),
                                        __('admin.records'),
                                        __('cp.action')
                                     )
                                ).
                '</tr>'.PHP_EOL;

        $i = 0;

        foreach ($sources as $val)
        {
			$source_count = DB::table($val)->count();


			$r .= '<tr>'.PHP_EOL;

			// Table name
			$r .= Cp::tableCell('', Cp::quickDiv('defaultBold', __('admin.stats_'.$val)), '20%');

			// Table rows
			$r .= Cp::tableCell('', $source_count, '20%');

			// Action
			$r .= Cp::tableCell('', Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=do_recount'.AMP.'TBL='.$val, __('admin.do_recount')), '20%');
        }


		$r .= '<tr>'.PHP_EOL;

		// Table name
		$r .= Cp::tableCell('', Cp::quickDiv('defaultBold', __('admin.site_statistics')), '20%');

		// Table rows
		$r .= Cp::tableCell('', '4', '20%');

		// Action
		$r .= Cp::tableCell('', Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=do_stats_recount', __('admin.do_recount')), '20%');

        $r .= '</table>'.PHP_EOL;

        Cp::$body = $r;
    }


    // ------------------------------------
    //  Recount preferences form
    // ------------------------------------

    function recount_preferences_form()
    {
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

        $recount_batch_total = Site::config('recount_batch_total');

        Cp::$title = __('admin.utilities');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')).
			 		  Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=recount_stats', __('admin.recount_stats'))).
			 		  Cp::breadcrumbItem(__('admin.set_recount_prefs'));

        $r = Cp::quickDiv('tableHeading', __('admin.set_recount_prefs'));

        if (Request::input('U'))
        {
            $r .= Cp::quickDiv('successMessage', __('admin.preference_updated'));
        }

        $r .= Cp::formOpen(
								array('action' => 'C=Administration'.AMP.'M=utilities'.AMP.'P=set_recount_prefs'),
								array('return_location' => BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=recount_prefs'.AMP.'U=1')
							);

        $r .= Cp::div('box');

        $r .= Cp::quickDiv('littlePadding', Cp::quickDiv('defaultBold', __('admin.recount_instructions')));

        $r .= Cp::quickDiv('littlePadding', __('admin.recount_instructions_cont'));

        $r .= Cp::input_text('recount_batch_total', $recount_batch_total, '7', '5', 'input', '60px');

        $r .= Cp::quickDiv('paddingTop', Cp::input_submit(__('admin.update')));

        $r .= '</div>'.PHP_EOL;
        $r .= '</form>'.PHP_EOL;

        Cp::$body = $r;
    }



    // ------------------------------------
    //  Update recount preferences
    // ------------------------------------

    function set_recount_prefs()
    {
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

        $total = Request::input('recount_batch_total');

        if ($total == '' || ! is_numeric($total))
        {
            return Utilities::recount_preferences_form();
        }

        $this->update_config_prefs(array('recount_batch_total' => $total));
    }


    // ------------------------------------
    //  Do General Statistics Recount
    // ------------------------------------

    function do_stats_recount()
    {
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

        $original_site_id = Site::config('site_id');

        $query = DB::table('sites')
        	->select('site_id')
        	->get();

        foreach($query as $row)
		{
			Site::setConfig('site_id', $row->site_id);

			Stats::update_member_stats();
			Stats::update_weblog_stats();
		}

		Site::setConfig('site_id', $original_site_id);

        Cp::$title = __('admin.utilities');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')).
			 		  Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=recount_stats', __('admin.recalculate'))).
			 		  Cp::breadcrumbItem(__('admin.recounting'));

		Cp::$body  = Cp::quickDiv('tableHeading', __('admin.site_statistics'));
		Cp::$body .= Cp::div('successMessage');
		Cp::$body .= __('admin.recount_completed');
		Cp::$body .= Cp::quickDiv('littlePadding', Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=recount_stats', __('admin.return_to_recount_overview')));
		Cp::$body .= '</div>'.PHP_EOL;
	}


    // ------------------------------------
    //  Do member/weblog recount
    // ------------------------------------

    function do_recount()
    {
        if ( ! Session::access('can_admin_utilities'))
        {
            return Cp::unauthorizedAccess();
        }

        if ( ! $table = Request::input('TBL'))
        {
            return false;
        }

        $sources = array('members', 'weblog_entries');

        if ( ! in_array($table, $sources))
        {
            return false;
        }

   		if ( ! isset($_GET['T']))
   		{
        	$num_rows = false;
        }
        else
        {
        	$num_rows = $_GET['T'];
			settype($num_rows, 'integer');
        }

        $batch = Site::config('recount_batch_total');

		if ($table == 'members')
		{
			$total_rows = DB::table('members')->count();

			if ($num_rows !== false)
			{
				$query = DB::table('members')
					->select('member_id')
					->orderBy('member_id')
					->offset($num_rows)
					->limit($batch)
					->get();

				foreach ($query as $row)
				{
					$total_entries = DB::table('weblog_entries')
						->where('author_id', $row->member_id)
						->count();

					DB::table('members')
						->where('member_id', $row->member_id)
						->update(
						[
							'total_entries' => $total_entries
						]
					);
				}
			}
		}
		elseif ($table == 'weblog_entries')
		{
			$total_rows = DB::table('weblog_entries')->count();
		}

        Cp::$title = __('admin.utilities');
        Cp::$crumb = Cp::anchor(BASE.'?C=Administration'.AMP.'area=utilities', __('admin.utilities')).
			 		  Cp::breadcrumbItem(Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=recount_stats', __('admin.recalculate'))).
			 		  Cp::breadcrumbItem(__('admin.recounting'));


        $r = <<<EOT

        <script type="text/javascript">
        <!--

        function standby()
        {
			if (document.getElementById('batchlink').style.display == "block")
			{
				document.getElementById('batchlink').style.display = "none";
				document.getElementById('wait').style.display = "block";
        	}
        }

		-->
		</script>
EOT;

		$r .= PHP_EOL.PHP_EOL;

        $r .= Cp::quickDiv('tableHeading', __('admin.recalculate'));
        $r .= Cp::div('successMessage');

		if ($num_rows === FALSE) {
			$total_done = 0;
		}
		else {
			$total_done = $num_rows + $batch;
		}


        if ($total_done >= $total_rows)
        {
            $r .= __('admin.recount_completed');
            $r .= Cp::quickDiv('littlePadding', Cp::anchor(BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=recount_stats', __('admin.return_to_recount_overview')));
        }
        else
        {
			$r .= Cp::quickDiv('littlePadding', __('admin.total_records').NBS.$total_rows);
			$r .= Cp::quickDiv('itemWRapper', __('admin.items_remaining').NBS.($total_rows - $total_done));

            $line = __('admin.click_to_recount');

        	$to = (($total_done + $batch) >= $total_rows) ? $total_rows : ($total_done + $batch);

            $line = str_replace("%x", $total_done, $line);
            $line = str_replace("%y", $to, $line);

            $link = "<a href='".BASE.'?C=Administration'.AMP.'M=utilities'.AMP.'P=do_recount'.AMP.'TBL='.$table.AMP.'T='.$total_done."'  onclick='standby();'><b>".$line."</b></a>";
			$r .= '<div id="batchlink" style="display: block; padding:0; margin:0;">';
            $r .= Cp::quickDiv('littlePadding', BR.$link);
			$r .= '</div>'.PHP_EOL;


			$r .= '<div id="wait" style="display: none; padding:0; margin:0;">';
			$r .= Cp::quickDiv('successMessage', BR.__('admin.standby_recount'));
			$r .= '</div>'.PHP_EOL;

        }

		$r .= '</div>'.PHP_EOL;

        Cp::$body = $r;
   }



    // ------------------------------------
    //  PHP INFO
    // ------------------------------------

    function php_info()
    {
        phpinfo();
        exit;
    }
}
