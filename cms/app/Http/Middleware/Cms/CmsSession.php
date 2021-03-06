<?php

namespace Kilvin\Http\Middleware\Cms;

use Site;
use Request;
use Closure;
use Carbon\Carbon;
use Kilvin\Core\Session;
use Illuminate\Http\Response;
use Illuminate\Container\Container;
use Illuminate\Routing\RouteDependencyResolverTrait;

class CmsSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        // ----------------------------------------------
        //  Instantiate Kilvin Session Data
        // ----------------------------------------------

        Session::boot();

        if (defined('REQUEST') && REQUEST === 'INSTALL') {
            return $next($request);
        }

        // ----------------------------------------------
        //  If Site Debug is 1 and User is SuperAdmin, Debugging On
        //  - App debug value overrides Site debugging value
        // ----------------------------------------------

        if (config('app.debug') == true or (Site::config('site_debug') == 1 and Session::userdata('group_id') == 1))
        {
            error_reporting(E_ALL);
        }

        // ----------------------------------------------
        //  Is the system turned on?
        //  - Note: super-admins can always view the system
        // ----------------------------------------------

        if (Session::userdata('group_id') != 1 and REQUEST == 'SITE') {
            if (Site::config('is_site_on') != 'y') {
                $viewable_sites = Session::userdata('offline_sites');
                if (!in_array(Site::config('site_id'), $viewable_sites)) {
                    exit(view('offline'));;
                }
            }
        }

        // ----------------------------------------------
        //  Done for Now
        // ----------------------------------------------

        return $next($request);
    }
}
