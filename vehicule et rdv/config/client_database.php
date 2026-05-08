<?php

return [
    'host' => getenv('SMART_GARAGE_CLIENT_DB_HOST') ?: 'localhost',
    'dbname' => getenv('SMART_GARAGE_CLIENT_DB_NAME') ?: 'garage1',
    'username' => getenv('SMART_GARAGE_CLIENT_DB_USER') ?: 'root',
    'password' => getenv('SMART_GARAGE_CLIENT_DB_PASS') ?: '',
    'charset' => getenv('SMART_GARAGE_CLIENT_DB_CHARSET') ?: 'utf8mb4',
    'table' => getenv('SMART_GARAGE_CLIENT_TABLE') ?: 'user',
];
