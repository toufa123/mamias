<?php

return [
    'modal' => [
        'form' => [
            'file' => [
                'rules' => [
                    'duplicate_columns' => '{0} The file contains multiple empty column headers (often from trailing blank columns in Excel exports). Please remove them.|{1,*} The file must not contain duplicate column headers: :columns.',
                ],
            ],
        ],
    ],
];
