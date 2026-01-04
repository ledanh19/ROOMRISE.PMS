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

    'channex' => [ //todo: move into .env
        'api_key' => env('CHANNEX_API_KEY', 'ufCDlEHG1uC4EKLlUWkoa5Hirhy06liJQpZ+vG9/JbK2wCK88E8sRaQPkQNcy8uO'),
        'base_url' => env('CHANNEX_BASE_URL', 'https://app.channex.io/api/v1'),
        'iframe_base_url' => env('CHANNEX_IFRAME_BASE_URL', 'https://app.channex.io'),
        'callback_url' => env('CHANNEX_URL_CALLBACK')
    ],

    'pancake' => [
        'partner_id' => env('PARTNER_ID'),
        'api_key' => env('PANCAKE_API_KEY'),
        'shop_id' => env('PANCAKE_SHOP_ID'),
        'pancake_crm_table' => env('PANCAKE_CRM_TABLE'),
        'customer_type_id' => env('PANCAKE_CUSTOMER_TYPE_ID', 'KH MỚI-493f-8076-1a62-2801-2d24-389b-85c2'),
        'status_new' => env('PANCAKE_STATUS_NEW', 'b908c3fa-bdfa-4e6a-af05-e06f912a25e2'),
        'status_modified' => env('PANCAKE_STATUS_MODIFIED', 'b908c3fa-bdfa-4e6a-af05-e06f912a25e2'),
        'status_cancelled' => env('PANCAKE_STATUS_CANCELLED', 'b908c3fa-bdfa-4e6a-af05-e06f912a25e2'),
        'status_standby' => env('PANCAKE_STATUS_STANDBY', 'b908c3fa-bdfa-4e6a-af05-e06f912a25e2'),
        'source_booking' => env('PANCAKE_SOURCE_BOOKING', ''),
        'source_expedia' => env('PANCAKE_SOURCE_EXPEDIA', ''),
        'source_agoda' => env('PANCAKE_SOURCE_AGODA', ''),
        'source_airbnb' => env('PANCAKE_SOURCE_AIRBNB', ''),
        'source_ctrip' => env('PANCAKE_SOURCE_CTRIP', ''),
    ],
];
