<?php

namespace Groot\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Groot\Http\Middleware\TrimStrings::class,
        \Groot\Http\Middleware\Cms\HtmlPurify::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \Groot\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Groot\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Groot\Http\Middleware\Cms\CmsSession::class,
            \Groot\Http\Middleware\Cms\LoadPlugins::class, // After all other boots and checks
            \Groot\Http\Middleware\Cms\Cleanup::class
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
       // 'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
       // 'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
       // 'can' => \Illuminate\Auth\Middleware\Authorize::class,
       // 'guest' => \Groot\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        // @todo - Use CMS' settings for throttling of requests (probably our own custom middlware)

        // ----------------------------------------------
        // Throttle Check
        // ----------------------------------------------

        // if (Site::config('enable_throttling') == 'y' AND REQUEST == 'SITE')
        // {
        //     $THR = new Throttling;
        //     $THR->throttle_ip_check();
        //     $THR->throttle_check();
        //     $THR->throttle_update();
        // }

    ];
}
