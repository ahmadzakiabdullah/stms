<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    'description' => env('APP_DESCRIPTION', 'Sports Tournament Management System for Sukan Antara Fakulti UTeM.'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'public_org_slug' => env('PUBLIC_ORG_SLUG'),

    'public_session_slug' => env('PUBLIC_SESSION_SLUG'),

    'csp_report_only' => (bool) env('CSP_REPORT_ONLY', true),

    'production_config_enforce' => (bool) env('PRODUCTION_CONFIG_ENFORCE', false),

    'public_registration' => (bool) env(
        'PUBLIC_REGISTRATION_ENABLED',
        env('APP_ENV', 'production') !== 'production'
    ),

    // Temporary operational switch. Keep enabled for normal production use.
    'email_verification_required' => (bool) env('EMAIL_VERIFICATION_REQUIRED', true),

    'default_org_slug' => env('DEFAULT_ORG_SLUG'),

    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),

    'seed_demo_data' => (bool) env(
        'SEED_DEMO_DATA',
        env('APP_ENV', 'production') !== 'production'
    ),

    'allow_demo_seeding' => (bool) env('ALLOW_DEMO_SEEDING', false),

    'backup' => [
        'enabled' => (bool) env('BACKUP_ENABLED', false),
        'path' => env('BACKUP_PATH') ?: storage_path('app/backups'),
        'source_path' => env('BACKUP_SOURCE_PATH') ?: storage_path('app/public'),
        'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
        'mysqldump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
        'mysql_binary' => env('MYSQL_BINARY', 'mysql'),
    ],

    'health' => [
        'token' => env('HEALTH_ENDPOINT_TOKEN'),
        'monitor_enabled' => (bool) env('HEALTH_MONITOR_ENABLED', false),
        'max_pending_jobs' => (int) env('HEALTH_MAX_PENDING_JOBS', 100),
        'max_failed_jobs' => (int) env('HEALTH_MAX_FAILED_JOBS', 0),
        'min_disk_free_mb' => (int) env('HEALTH_MIN_DISK_FREE_MB', 1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'supported_locales' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('APP_SUPPORTED_LOCALES', 'en,ms'))
    ))),

    'locale_labels' => [
        'en' => 'English',
        'ms' => 'Bahasa Malaysia',
    ],

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
