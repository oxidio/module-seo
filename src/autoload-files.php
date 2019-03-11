<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

call_user_func(function(...$files) {
    foreach ($files as $file) {
        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            require_once $file;
        }
    }
}, __DIR__ . '/Oxidio/constants.php', __DIR__ . '/Oxidio/functions.php');
