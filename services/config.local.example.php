<?php

// Copy this file to services/config.local.php on the server and replace the placeholders.
// This file is loaded before services/config.php defines the DB constants, so putenv(...)
// overrides are picked up without editing tracked source files.

putenv('APP_DB_HOST=localhost');
putenv('APP_DB_PORT=3306');
putenv('APP_DB_NAME=dart_club');
putenv('APP_DB_USER=replace_me');
putenv('APP_DB_PASS=replace_me');
putenv('APP_DB_CHARSET=utf8mb4');
