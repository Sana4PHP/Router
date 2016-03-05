<?php

/**
 * Generates a url from given values
 *
 * @param string $generatorCodeName
 * @param array  $parameters
 *
 * @return string
 * @throws \Sana\Router\Exception\Callback
 *
 * @return string
 */
function url ($generatorCodeName, $parameters = [])
{
    return \Sana\Router\Route::generate($generatorCodeName, $parameters);
}
