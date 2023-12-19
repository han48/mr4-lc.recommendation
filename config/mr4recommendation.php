<?php

return [
    'featureWeight' => 1,
    'categoryWeight' => 1,
    'priceWeight' => 1,
    'priceHighRange' => 1000,

    'chunkSize' => 1000,
    'perPage' => 5,

    'output' => storage_path('app/private/recommendation/data'),

    'mapping' => [
        'tables' => [
            // Sample config
            'table_name' => [
                'data_version' => '0.0.1',
                'output' => 'table_name.json',
                // SQL WHERE condition
                'status' => [
                    'status' => 1,
                ],
                // SQL ORDER BY
                'order' => 'id',
                'map' => [
                    'id' => 'id',
                    'price' => 'price',
                    'categories' => ['categories', 'group'], // Array or string column name,
                    'features' => ['color', 'type'], // Array or string column name,
                ],
                'map_type' => [
                    'categories' => 'merge', // String data
                    'features' => 'object', // Object data
                ],
            ],
        ],
    ],
];
