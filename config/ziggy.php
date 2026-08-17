<?php

return [
    /*
    | Guests only need routes used by the public portal and authentication
    | screens. Authenticated users continue to receive the complete route map.
    */
    'groups' => [
        'guest' => [
            'public.*',
            'locale.update',
            'login',
            'register',
            'password.*',
            'verification.*',
        ],
    ],
];
