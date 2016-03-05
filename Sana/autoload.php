<?php

require_once __DIR__ . '/functions.php';

spl_autoload_register(function ($className)
{
    if (substr($className, 0, 5) === 'Sana\\')
    {
        require_once __DIR__ . '/../' . str_replace('\\', '/', $className) . '.php';
    }
});
