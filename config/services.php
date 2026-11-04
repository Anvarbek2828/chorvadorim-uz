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



    'openai' => [

        'api_key' => env('OPENAI_API_KEY'),

    ],



    'telegram' => [

        'bot_token' => env('TELEGRAM_BOT_TOKEN'),

        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    ],

    // Obuna kunlik ishi: zaxira URL tokeni (/cron/obuna/{token}); bo'sh bo'lsa URL 404.
    // auto_run=false — har so'rovdan keyin avtomatik ishga tushmaydi (testlar uchun)
    'obuna' => [
        'cron_token' => env('OBUNA_CRON_TOKEN'),
        'auto_run' => env('OBUNA_AUTO_RUN', true),
    ],



    'slack' => [

        'notifications' => [

            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),

            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),

        ],

    ],



];

