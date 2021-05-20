<?php

return [
    'groups' => [
        'web' => [
            'middleware' => 'web',
            'prefix' => '',
        ],
        'api' => [
            'middleware' => 'api',
            'prefix' => 'api',
        ],
    ],

    'web' => [
        /** @inject web **/
    ],
    'api' => [
        /** @inject api **/
    ],
];
