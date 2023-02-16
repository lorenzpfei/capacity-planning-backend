<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'provider' => [
        'task' => env('TASK_PROVIDER'),
        'tracking' => env('TRACKING_PROVIDER'),
        'login' => env('LOGIN_PROVIDER')
    ],

    'asana' => [
        'client_id' => env('ASANA_CLIENT_ID'),
        'client_secret' => env('ASANA_CLIENT_SECRET'),
        'redirect' => env('ASANA_REDIRECT_URI'),
        'optfields' => env('ASANA_OPTFIELDS')
    ],

    'azure' => [
        'client_id' => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'redirect' => env('AZURE_REDIRECT_URI'),
        'tenant' => env('AZURE_TENANT_ID')
    ],

    'everhour' => [
        'api_key' => env('EVERHOUR_API_KEY'),
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL'),
    ],
];
