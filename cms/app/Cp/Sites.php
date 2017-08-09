<?php

namespace Kilvin\Cp;

use Cp;
use DB;
use Site;
use Stats;
use Request;
use Cookie;
use Carbon\Carbon;
use Kilvin\Core\Session;

class Sites
{

    // --------------------------------------------------------------------

    /**
    * Request Handler
    *
    * @return mixed
    */
    public function run()
    {
        $domain_id = Request::input('domain_id');

        if (empty($domain_id) OR ! is_numeric($domain_id)) {
            return $this->listSites();
        }

        $domain = DB::table('domains')
            ->where('domain_id', $domain_id)
            ->first();

        if (empty($domain)) {
            return false;
        }

        if (Session::userdata('group_id') != 1)
        {
            $assigned_sites = Session::userdata('assigned_sites');

            if (!isset($assigned_sites[$domain->site_id])) {
                return false;
            }
        }

        Site::loadDomainPrefs($domain_id);
        Cookie::queue('cp_last_domain_id', $domain_id, 365*24*60);

        return redirect(BASE);
    }

    // ------------------------------------
    //  Sites selection menu
    // ------------------------------------

    public function listSites()
    {
        if (sizeof(Session::userdata('assigned_sites')) == 0) {
            return Cp::unauthorizedAccess();
        }

        Cp::$title  = __('admin.site_management');
        Cp::$crumb  = __('admin.site_management');

        $right_links[] = [
            BASE.'?C=SitesAdministration'.AMP.'M=newSite',
            __('sites.create_new_site')
        ];

        $r = Cp::header(__('sites.choose_a_domain'), $right_links);


        $r .= __('sites.choose_a_domain_details').'<br><br>';
        $r .= Cp::table('tableBorder', '0', '', '100%');

        $i = 0;

        $query = DB::table('sites')
            ->leftJoin('domains', 'sites.site_id', '=', 'domains.site_id')
            ->whereIn('sites.site_id', array_keys(Session::userdata('assigned_sites')))
            ->get();

        $domains = [];

        foreach($query as $row) {
            $domains[$row->site_name][] = $row;
        }

        foreach($domains as $site_name => $site_domains)
        {
            $r .= '<tr>'.PHP_EOL;

            $s = '<p>&nbsp;&nbsp;- <em>No domains for site.</em></p>';

            if (!empty($site_domains[0]->domain)) {
                $s = '';
                foreach($site_domains as $domain) {
                    $s .= '<p>&nbsp;&nbsp;- '.Cp::anchor('/'.BASE."?C=Sites".AMP."domain_id=".$domain->domain_id, $domain->domain).'</p>';
                }
            }

            $r .= Cp::tableCell('', '<strong>'.$site_name.'</strong>'.$s);

            $r .= '</tr>'.PHP_EOL;
        }

        $r .= '</table>'.PHP_EOL;

        $r .= Cp::quickDiv(
            'littlePadding',
            "<a href='/".BASE."?C=SitesAdministration".AMP."M=listSites'><em>&#187;&nbsp;<strong>".__('cp.edit_sites')."</strong></em></a>");

        Cp::$body = $r;
    }
}
