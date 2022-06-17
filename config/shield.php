<?php

/**
 * Copyright (c) Vincent Klaiber.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://github.com/vinkla/laravel-shield
 */

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Basic Auth Credentials
    |--------------------------------------------------------------------------
    |
    | The array of users with hashed username and password credentials which are
    | used when logging in with HTTP basic authentication.
    |
    */

    'users' => [
        'default' => [
            env('SHIELD_USER'),
            env('SHIELD_PASSWORD'),
        ],
        'nemdeling' => [
            'nemdeling',
            '$2y$10$4xICn9w1Asg.I0k.Ba8mZOLQTiYd4MM8R3T/g8Bbsxzu0tI8lcm2m'
        ]
    ],

];
