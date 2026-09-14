<?php

spl_autoload_register(function ($class) {

    $prefix = 'OnlinePayments\\Sdk\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));

    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    $paths = [
        __DIR__ . '/sdk/src/OnlinePayments/Sdk/' . $relativePath,
        __DIR__ . '/sdk/lib/OnlinePayments/Sdk/' . $relativePath,
    ];

    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});