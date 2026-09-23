<?php

return [
    'modal' => [
        'form' => [
            'file' => [
                'placeholder' => 'Upload a CSV or XLSX file',
                'rules' => [
                    'duplicate_columns' => '{0} The file contains multiple empty column headers (often from trailing blank columns in Excel exports). Please remove them.|{1,*} The file must not contain duplicate column headers: :columns.',
                ],
            ],
        ],
        'actions' => [
            'download_example' => [
                'label' => 'Download example CSV file',
            ],
            'download_example_xlsx' => [
                'label' => 'Download example XLSX file',
            ],
        ],
    ],
    'example_csv' => [
        'file_name' => ':importer-example.csv',
    ],
    'example_xlsx' => [
        'file_name' => ':importer-example.xlsx',
    ],
];
