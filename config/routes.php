<?php
/**
 * Router configuration
 *
 * @author      veelasky <veelasky@gmail.com>
 */

return [
    'groups' => [
        'web' => [
            'middleware' => 'web',
            'prefix' => ''
        ],
        'api' => [
            'middleware' => 'api',
            'prefix' => 'api'
        ]
    ],

    'web' => [

    ],
    'api' => [

    ]
];