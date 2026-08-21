<?php

return [
    /*
    | The guest group remains available for explicit uses, while the main
    | application shell renders the complete route map because Inertia login
    | redirects may happen without a full document reload.
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
