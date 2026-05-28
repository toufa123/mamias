<?php

return [
    'db_container' => env('DOCKER_DB_CONTAINER', 'mamias_db'),

    'backup_container' => env('DOCKER_BACKUP_CONTAINER', 'mamias_db_backup'),
];
