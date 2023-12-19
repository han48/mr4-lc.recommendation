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
            'wines' => [
                'data_version' => '0.0.1',
                'output' => 'wines.json',
                'status' => [
                    'status' => 2,
                ],
                'order' => 'wine_code',
                'map' => [
                    'id' => 'wine_code',
                    'price' => 'base_price',
                    'categories' => ['national', 'region'],
                    'features' => ['harvest_year', 'variety', 'alcohol_concentration', 'capacity', 'capacity_unit', 'category', 'taste', 'bottle_status', 'rating', 'average_holder_duration', 'average_score'],
                ],
                'map_type' => [
                    'categories' => 'merge',
                    'features' => 'object',
                ],
            ],

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
