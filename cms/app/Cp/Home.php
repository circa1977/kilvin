<?php

namespace Kilvin\Cp;

use Cp;
use DB;
use Site;
use Request;
use Carbon\Carbon;
use Kilvin\Core\Session;
use Kilvin\Core\Localize;

class Home
{
	public $limit		= 10;  // The number of items to show in the "recent entries"display
	public $methods 	= [];
	public $messages	= [];
	public $stats_ct	= 0;

	public $conn_failure = false;

    // ------------------------------------
    //  Constructor
    // ------------------------------------

    public function __construct()
    {
		// ------------------------------------
		//  Does the install file exist?
		// ------------------------------------

        $install_path = realpath(SYSTEM_PATH.'../public'.DIRECTORY_SEPARATOR.'install.php');

		if (file_exists($install_path)) {
			$this->messages[] = Cp::quickDiv('alert', __('home.install_lock_warning'));
			$this->messages[] = Cp::quickDiv('littlePadding', __('home.install_lock_removal'));
		}

		// Available methods
        $this->methods = [
			'recent_entries',
			'site_statistics',
			'notepad',
			'bulletin_board',
		];

		if (Session::access('can_access_admin') === true) {
			$this->methods[] = 'recent_members';
			$this->methods[] = 'member_search_form';
		}
	}


	// ------------------------------------
    //  Run
    // ------------------------------------

    public function run()
    {
        switch(Request::input('M'))
        {
            case 'notepad_update'		: $this->notepad_update();
				break;
            default	 					: $this->home_page();
				break;
		}
    }

    // ------------------------------------
    //  Control panel home page
    // ------------------------------------

    function home_page()
    {
		Cp::$title = __('cp.homepage');

		// ------------------------------------
		//  Fetch the user display prefs
		// ------------------------------------

		// We'll fill two arrays.  One containing the left side options, the other containing the right side

		$left 	= [];
		$right 	= [];

		$query = (array) DB::table('member_homepage')
			->where('member_id', Session::userdata('member_id'))
			->first();

		if (!empty($query))
		{
			foreach ($query as $key => $val)
			{
				if ($val === 'l')
				{
					$left[$query[$key.'_order'].'_'.$key] = $key;
				}

				if ($val === 'r')
				{
					$right[$query[$key.'_order'].'_'.$key] = $key;
				}
			}
		}

		// ------------------------------------
		//  Sort the arrays
		// ------------------------------------

		ksort($left);
		ksort($right);

		reset($left);
		reset($right);

		// ------------------------------------
		//  Show system messages if they exist
		// ------------------------------------

		if (count($this->messages) > 0)
		{
			Cp::$body	.=	Cp::div('box');
			foreach ($this->messages as $msg)
			{
				Cp::$body .= $msg;
			}

			Cp::$body .=	'</div>'.PHP_EOL;
			Cp::$body .= Cp::quickDiv('defaultSmall', '');
		}

		Cp::$body	.=	Cp::table('', '0', '0', '100%');

		// ------------------------------------
		//  Build the left page display
		// ------------------------------------

        if (count($left) > 0)
        {
			Cp::$body	.=	'<tr>'.PHP_EOL;
			Cp::$body	.=	Cp::td('leftColumn', '50%', '', '', 'top');

        	foreach ($left as $meth)
        	{
        		if (in_array($meth, $this->methods))
        		{
        			Cp::$body .= $this->$meth();
					Cp::$body .= Cp::quickDiv('defaultSmall', '');
        		}
        	}

			Cp::$body	.=	'</td>'.PHP_EOL;
        }

		// ------------------------------------
		//  Build the right page display
		// ------------------------------------

        if (count($right) > 0)
        {
			Cp::$body	.=	Cp::td('rightColumn', '50%', '', '', 'top');

        	foreach ($right as $meth)
        	{
        		if (in_array($meth, $this->methods))
        		{
        			Cp::$body .= $this->$meth();
					Cp::$body .= Cp::quickDiv('defaultSmall', '');
        		}
        	}

			Cp::$body	.=	'</td>'.PHP_EOL;
        }

		Cp::$body	.=	'</tr>'.PHP_EOL;
		Cp::$body	.=	'</table>'.PHP_EOL;
    }

    // ------------------------------------
    //  Recent entries
    // ------------------------------------

    function recent_entries()
    {
		$query = DB::table('weblog_entries')
			->join('weblogs', 'weblogs.weblog_id', '=', 'weblog_entries.weblog_id')
			->join('weblog_entry_data', 'weblog_entry_data.entry_id', '=', 'weblog_entries.entry_id')
			->select(
				'weblog_entries.weblog_id',
				'weblog_entries.author_id',
				'weblog_entries.entry_id',
				'weblog_entry_data.title');

		if (Session::userdata('group_id') != 1)
		{
			if ( ! Session::access('can_view_other_entries') AND
				 ! Session::access('can_edit_other_entries') AND
				 ! Session::access('can_delete_all_entries'))
			{

				$query->where('weblog_entries.author_id', Session::userdata('member_id'));
			}

			$allowed_blogs = array_keys(Session::userdata('assigned_weblogs'));

			// If the user is not assigned a weblog we want the
			// query to return false, so we'll use a dummy ID number

			if (count($allowed_blogs) == 0)
			{
				$query->where('weblog_entries.weblog_id', 0);
			}
			else
			{
				$query->whereIn('weblog_entries.weblog_id', $allowed_blogs);
			}
		}

        $query = $query->orderBy('entry_date')
        	->limit($this->limit)
        	->get();

		// ------------------------------------
		//  Table Header
		// ------------------------------------

        $r  = Cp::table('tableBorder', '0', '0', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableCell('tableHeading', __('home.most_recent_entries')).
              '</tr>'.PHP_EOL;

		// ------------------------------------
		//  Table Rows
		// ------------------------------------

        if ($query->count() == 0)
        {
			$r .= Cp::tableQuickRow('',
				[
					__('no_entries')
				]
			);
        }
        else
        {
			foreach ($query as $row)
			{
				$which = 'view_entry';

				if ($row->author_id == Session::userdata('member_id'))
				{
					$which = 'edit_entry';
				}
				else
				{
					if (Session::access('can_edit_other_entries'))
					{
						$which = 'edit_entry';
					}
				}

				$r .= Cp::tableQuickRow(
					'',
					Cp::quickSpan(
						'defaultBold',
						Cp::anchor(
							BASE.'?C=edit'.
								AMP.'M='.$which.
								AMP.'weblog_id='.$row->weblog_id.
								AMP.'entry_id='.$row->entry_id,
							$row->title)
					)
				);
			}
        }

        $r .= '</table>'.PHP_EOL;

    	return $r;
	}

    // ------------------------------------
    //  Recent members
    // ------------------------------------

    function recent_members()
    {
    	$query = DB::table('members')
    		->select('member_id', 'screen_name', 'group_id', 'join_date')
    		->orderBy('join_date', 'desc')
    		->limit(10)
    		->get();

		// ------------------------------------
		//  Define alternating style
		// ------------------------------------

		$i = 0;


		// ------------------------------------
		//  Table Header
		// ------------------------------------

        $r  = Cp::table('tableBorder', '0', '0', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableCell('tableHeading',
                                array(
                                		__('home.recent_members'),
                                		__('home.join_date')
                                	 )
                                ).
              '</tr>'.PHP_EOL;

		// ------------------------------------
		//  Table Rows
		// ------------------------------------

		foreach ($query as $row)
		{
			$name = $row->screen_name;

			$r .= Cp::tableQuickRow('',
									array(

										Cp::quickSpan('defaultBold', Cp::anchor(BASE.'?C=account'.AMP.'id='.$row->member_id, $name)),
										Localize::createHumanReadableDateTime($row->join_date)
										  )
									);
		}

        $r .= '</table>'.PHP_EOL;

		return $r;
	}



    // ------------------------------------
    //  Site statistics
    // ------------------------------------

    function site_statistics()
    {
		// ------------------------------------
		//  Fetch stats
		// ------------------------------------

        $stats = DB::table('stats')
        	->where('site_id', Site::config('site_id'))
        	->select('total_entries')
        	->first();

		// ------------------------------------
		//  Define alternating style
		// ------------------------------------

		$i = 0;


		// ------------------------------------
		//  Table Header
		// ------------------------------------

        $r  = Cp::table('tableBorder', '0', '0', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableCell('tableHeading',
                                array(
                                        __('home.site_statistics'),
                                        __('home.value')
                                     )
                                ).
              '</tr>'.PHP_EOL;


		if (Session::userdata('group_id') == 1)
		{
			$r .= $this->system_status();
			$r .= $this->system_version();
		}


		$r .= $this->total_weblog_entries($stats);

		if (Session::userdata('group_id') == 1)
		{
			$r .= $this->total_members();
		}

        $r .= '</table>'.PHP_EOL;

		return $r;
	}


	// ------------------------------------
	//  Version Data
	// ------------------------------------

	function system_version()
	{
  		return Cp::tableQuickRow('',
								array(
										Cp::quickSpan('defaultBold', __('cp.cms_version')),
										CMS_VERSION
									  )
								);
	}

	// ------------------------------------
	//  Total Members
	// ------------------------------------

	function total_members()
	{
    		$count = DB::table('members')->count();

		return Cp::tableQuickRow('',
								array(
										Cp::quickSpan('defaultBold', __('home.total_members')),
										$count
									  )
								);
	}

	// ------------------------------------
	//  Total Weblog Entries
	// ------------------------------------

	function total_weblog_entries($stats)
	{
  		return Cp::tableQuickRow('',
								array(
										Cp::quickSpan('defaultBold', __('home.total_entries')),
										$stats->total_entries
									  )
								);
	}



	// ------------------------------------
	//  System status
	// ------------------------------------

	function system_status()
	{
  		$r = Cp::tableQuickRow('',
								array(
										Cp::quickSpan('defaultBold', __('home.system_status')),
										(Site::config('is_system_on') == 'y') ? Cp::quickDiv('highlight_alt_bold', __('home.online')) : Cp::quickDiv('highlight_bold', __('home.offline'))
									  )
								);


		$r .= Cp::tableQuickRow('',
								array(
										Cp::quickSpan('defaultBold', __('home.site_status')),
										(Site::config('is_site_on') == 'y' && Site::config('is_system_on') == 'y') ? Cp::quickDiv('highlight_alt_bold', __('home.online')) : Cp::quickDiv('highlight_bold', __('home.offline'))
									  )
								);

		return $r;
	}


    // ------------------------------------
    //  Member search form
    // ------------------------------------

    function member_search_form()
    {
        $r = Cp::formOpen(array('action' => 'C=Administration'.AMP.'M=members'.AMP.'P=do_member_search'));

        $r .= Cp::div('box');

		$r .= Cp::heading(__('home.member_search') ,5);

		$r .= Cp::quickDiv('littlePadding', __('home.search_instructions', 'keywords'));

		$r .= Cp::quickDiv('littlePadding', Cp::input_text('keywords', '', '35', '100', 'input', '100%'));

        $r .= Cp::input_select_header('criteria');
        $r .= Cp::input_select_option('screen_name', 	__('home.search_by'));
		$r .= Cp::input_select_option('screen_name', 	__('members.screen_name'));
		$r .= Cp::input_select_option('email',		__('members.email_address'));
		$r .= Cp::input_select_option('url', 			__('members.url'));
		$r .= Cp::input_select_option('ip_address', 	__('members.ip_address'));

		$query = DB::table('member_fields')
			->orderBy('m_field_label')
			->select('m_field_label', 'm_field_name')
			->get();

		if ($query->count() > 0)
		{
			$r .= Cp::input_select_option('screen_name', '---');

			foreach($query as $row)
			{
				$r .= Cp::input_select_option('m_field_'.$row->m_field_name, $row->m_field_label);
			}
		}

        $r .= Cp::input_select_footer();

        // Member group select list
        $query = DB::table('member_groups')
        	->select('group_id', 'group_name')
        	->orderBy('group_name');

		if (Session::userdata('group_id') != '1')
		{
			$query = $query->where('group_id', '!=', 1);
        }

        $query = $query->get();

        $r.= Cp::input_select_header('group_id');

        $r.= Cp::input_select_option('any', __('members.member_group'));
        $r.= Cp::input_select_option('any', __('cp.any'));

        foreach ($query as $row)
        {
            $r .= Cp::input_select_option($row->group_id, $row->group_name);
        }

        $r .= Cp::input_select_footer();

        $r .= NBS.__('home.exact_match').NBS.Cp::input_checkbox('exact_match', 'y').NBS;

        $r.= Cp::input_submit(__('cp.submit'));

        // END select list

        $r.= '</div>'.PHP_EOL;

        $r.= '</form>'.PHP_EOL;

        return $r;
	}

    // ------------------------------------
    //  Validating members
    // ------------------------------------

    function validating_members()
    {
  		return Cp::heading('validating_members', 5);
	}

    // ------------------------------------
    //  Bulletin Board
    // ------------------------------------

    function bulletin_board()
    {

        $query = DB::table('member_bulletin_board AS b')
        	->select('m.screen_name', 'b.bulletin_message', 'b.bulletin_date')
        	->join('members AS m', 'b.sender_id', '=', 'm.member_id')
        	->where('b.bulletin_group', Session::userdata('group_id'))
        	->where('bulletin_date', '<', Carbon::now()->timestamp)
        	->where(function($q) {
        		$q->where('b.bulletin_expires', '>', Carbon::now()->timestamp)
        		  ->orWhere('b.bulletin_expires', 0);
        	})
        	->orderBy('b.bulletin_date', 'desc')
        	->limit(2);

        $r  = Cp::table('tableBorder', '0', '0', '100%').
              '<tr>'.PHP_EOL.
              Cp::tableCell('tableHeading', __('home.bulletin_board')).
              '</tr>'.PHP_EOL;

        $i = 0;

        if ($query->count() == 0)
        {
        	$r .= Cp::tableQuickRow( '',
									array(
											__('home.no_bulletins')
										  )
									);
        }
        else
		{
			foreach($query as $row)
			{
				$r .= Cp::tableQuickRow( '',
										array(
												Cp::quickDiv('littlePadding', Cp::quickSpan('defaultBold', __('home.bulletin_sender')).':'.NBS.$row->screen_name).
												Cp::quickDiv('littlePadding', Cp::quickSpan('defaultBold', __('home.bulletin_date')).':'.NBS.Localize::createHumanReadableDateTime($row->bulletin_date)).
												Cp::quickDiv('littlePadding', Cp::input_textarea('notepad', $row->bulletin_message, 10, 'textarea', '100%', "readonly='readonly'"))
											  )
										);
			}
		}

        return $r.'</table>'.PHP_EOL;
	}

	// ------------------------------------
    //  Notepad
    // ------------------------------------

    function notepad()
    {
        $query = DB::table('members')
        	->where('member_id', Session::userdata('member_id'))
        	->select('notepad', 'notepad_size')
        	->first();

		return
			 Cp::formOpen(array('action' => 'C=home'.AMP.'M=notepad_update'))
			.Cp::quickDiv('tableHeading', __('home.notepad'))
			.Cp::input_textarea('notepad', $query->notepad, 10, 'textarea', '100%')
			.Cp::quickDiv('littlePadding', Cp::input_submit(__('cp.update')))
			.'</form>'.PHP_EOL;
	}


    // ------------------------------------
    //  Update notepad
    // ------------------------------------

    function notepad_update()
    {
        DB::table('members')
        	->where('member_id', Session::userdata('member_id'))
        	->update(['notepad' => Request::input('notepad')]);

        return redirect('?');
    }
}
