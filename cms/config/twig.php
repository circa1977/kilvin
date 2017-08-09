<?php


/**
 * Configuration options for Twig Service in CMS
 * - Mostly based off the TwigBridge library at https://github.com/rcrowe/TwigBridge/
 * - Our implementation has a number of CMS and Laravel modifications + restrictions
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Accepts all Twig environment configuration options
    |--------------------------------------------------------------------------
    |
    | http://twig.sensiolabs.org/doc/api.html#environment-options
    |
    */
    'environment' => [

        // The base template class to use for generated templates.
        'base_template_class' => 'Groot\Libraries\Twig\Template',

        // Set to false to disable caching.
        'cache' => true,

        // When developing with Twig, it's useful to recompile the template
        // whenever the source code changes. If you don't provide a value
        // for the auto_reload option, it will be determined automatically based on the debug value.
        'auto_reload' => true,

        // If set to false, Twig will silently ignore invalid variables
        // (variables and or attributes/methods that do not exist) and
        // replace them with a null value. When set to true, Twig throws an exception instead.
        'strict_variables' => true,

        // If set to true, auto-escaping will be enabled by default for all templates.
        // default: 'html'
        'autoescape' => 'html',

        // A flag that indicates which optimizations to apply
        // (default to -1 -- all optimizations are enabled; set it to 0 to disable)
        'optimizations' => -1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global variables
    |--------------------------------------------------------------------------
    |
    | These will always be passed in and can be accessed as Twig variables.
    | NOTE: these will be overwritten if you pass data into the view with the same key.
    |
    */
    'globals' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | Extensions
    |--------------------------------------------------------------------------
    |
    | `Twig_Extension_Debug` is enabled automatically if app.debug is TRUE.
    |
    */
    'extensions' => [

        // Easily disable all Facades, Filters, and Functions by commenting these out
        'Groot\Libraries\Twig\Loaders\Facades',
        'Groot\Libraries\Twig\Loaders\Filters',
        'Groot\Libraries\Twig\Loaders\Functions',

        // Feel free to add your own!
        // 'External\Plugins\Hope\Twig\FunExtension'
    ],

    /*
    |--------------------------------------------------------------------------
    | Facades
    |--------------------------------------------------------------------------
    |
    | Available facades. Access like `{{ Config.get('foo.bar') }}`.
    |
    | Each facade can take an optional array of options. To mark the whole facade
    | as safe you can set the option `'is_safe' => true`. Setting the facade as
    | safe means that any HTML returned will not be escaped.
    |
    | It is advisable to not set the whole facade as safe and instead mark the
    | each appropriate method as safe for security reasons. You can do that with
    | the following syntax:
    |
    | <code>
    |     'Form' => [
    |         'is_safe' => [
    |             'open'
    |         ]
    |     ]
    | </code>
    |
    | The values of the `is_safe` array must match the called method on the facade
    | in order to be marked as safe.
    |
    */
    'facades' => [
        'Site' => ['is_safe' => ['config']], // Site.config() includes all site related config variables
        'Auth',
        'Config',
        'Request',
        'Session',
        'Str',
        'URL',

        // Do not remove as it will disable all 'Plugins.' functionality
        'Plugins' => ['callback' => 'PluginVariable']
    ],

    /*
    |--------------------------------------------------------------------------
    | Functions
    |--------------------------------------------------------------------------
    |
    | Available functions. Access like `{{ secure_url(...) }}`.
    |
    | Each function can take an optional array of options. These options are
    | passed directly to `Twig_Function`.
    |
    | So for example, to mark a function as safe you can do the following:
    |
    | <code>
    |     'link_to' => ['is_safe' => ['html']]
    | </code>
    |
    | The options array also takes a `callback` that allows you to name the
    | function differently in your Twig templates than what it's actually called.
    |
    | <code>
    |     'token'  => ['callback' => 'csrf_token'],
    |     'user'   => ['callback' => 'Auth@user'],
    |     'butter' => ['callback' => function() { return 'Yes!';}]
    | </code>
    |
    */
    'functions' => [
        'elixir',
        'head',
        'last',
        'mix',
        'csrfToken' => ['callback' => 'csrf_token'],
        'secureUrl' => ['is_safe' => ['html'], 'callback' => 'secure_url'],
        'csrfField' => ['is_safe' => ['html'], 'callback' => 'csrf_field'],
        'ceil',
        'floor',
        'round',
        'pi'
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    |
    | Available filters. Access like `{{ variable|filter }}`.
    |
    | Each filter can take an optional array of options. These options are
    | passed directly to `Twig_SimpleFilter`.
    |
    | So for example, to mark a filter as safe you can do the following:
    |
    | <code>
    |     'studly_case' => ['is_safe' => ['html'] ]
    | </code>
    |
    | The options array also takes a `callback` that allows you to name the
    | filter differently in your Twig templates than what is actually called.
    |
    | <code>
    |     'snake' => ['callback' => 'snake_case']
    | </code>
    |
    */
    'filters' => [
        'daysAgo' => ['callback' => '\Groot\Core\Localize@daysAgo'],
        'relativeDate' => ['callback' => '\Groot\Core\Localize@relativeDate']

    ]
];
