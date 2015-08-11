<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    'users'       => [
        'avatar' => [
            'path'    => 'uploads/images/avatars',
            'default' => 'assets/img/misc/noavatar.png'
        ],
        'model'  => Atorscho\User::class,
        'table'  => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    */

    'groups'      => [
        'default' => 1,
        'perPage' => 10,
        'rules'   => [
            'name'        => [
                'min' => 3,
                'max' => 30
            ],
            'handle'      => [
                'min' => 3,
                'max' => 30
            ],
            'description' => [
                'min' => 0,
                'max' => 255
            ],
            'prefix'      => [
                'min' => 0,
                'max' => 255
            ],
            'suffix'      => [
                'min' => 0,
                'max' => 255
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    'permissions' => [
        'perPage' => 10,
        'rules'   => [
            'name'   => [
                'min' => 3,
                'max' => 30
            ],
            'handle' => [
                'min' => 3,
                'max' => 30
            ]
        ]
    ]

];
