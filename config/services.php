<?php

$dashboardPrefix = trim((string) env('DASHBOARD_PREFIX', 'admin'), '/');

return [

    /*
    |--------------------------------------------------------------------------
    | Third-party credentials & app integration
    |--------------------------------------------------------------------------
    |
    | Grouped by area: mail transports (Laravel expects these top-level keys),
    | notifications, OAuth, then SaaS toggles and SPA routing.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Mail — keys consumed by Illuminate\Mail\MailManager
    |--------------------------------------------------------------------------
    */
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth — social login (keys used by SocialAuthController)
    |--------------------------------------------------------------------------
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', env('APP_URL').'/auth/github/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SaaS — auth modes, OTP channel, avatar storage, phone defaults
    |--------------------------------------------------------------------------
    |
    | login_methods: password_only | otp_only | both
    | otp_deliver_to: email | phone | both
    |
    */
    'saas' => [
        'auth' => [
            'login_methods' => env('SAAS_AUTH_LOGIN_METHODS', 'both'),
            'otp_deliver_to' => env('SAAS_AUTH_OTP_DELIVER_TO', 'email'),
            'default_phone_region' => env('SAAS_DEFAULT_PHONE_REGION', 'VN'),
        ],
        'avatar_disk' => env('FILESYSTEM_AVATAR_DISK', 'public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SPA — dashboard / settings URL segment (auth pages stay at root)
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'prefix' => $dashboardPrefix !== '' ? $dashboardPrefix : 'admin',
    ],

];
