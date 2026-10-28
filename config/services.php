<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Google, Stripe, etc. This file provides the de facto location for
    | this type of information, allowing packages to have a conventional
    | location to find your various credentials.
    |
    */

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Drive / OAuth2
    |--------------------------------------------------------------------------
    |
    | Credentials for Google Drive integration using OAuth2 Web Application.
    | The refresh_token is stored in the SystemSetting database table
    | after admin completes the one-time authorization flow.
    |
    */

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/admin/google-drive/callback'),
        'folder_id'     => env('GOOGLE_DRIVE_FOLDER_ID'),
        'api_key'       => env('GOOGLE_API_KEY'),
        'enabled'       => env('GOOGLE_DRIVE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Groq API
    |--------------------------------------------------------------------------
    */

    'groq' => [
        'key' => env('GROQ_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hyperledger Fabric
    |--------------------------------------------------------------------------
    */

    'fabric' => [
        'peer_endpoint'  => env('FABRIC_PEER_ENDPOINT', 'localhost:7051'),
        'channel'        => env('FABRIC_CHANNEL', 'documentchannel'),
        'chaincode'      => env('FABRIC_CHAINCODE', 'documentCC'),
        'msp_id'         => env('FABRIC_MSP_ID', 'Org1MSP'),
        'tls_cert'       => env('FABRIC_TLS_CERT'),
        'msp_cert'       => env('FABRIC_MSP_CERT'),
        'msp_key'        => env('FABRIC_MSP_KEY'),
        'api_url'        => env('FABRIC_API_URL'),
        'api_url_verify' => env('FABRIC_API_URL_VERIFY'),
    ],

];
