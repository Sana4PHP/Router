<?php

namespace Sana\Router;

/**
 * Class Route
 * Sets allowed routes and executes one of them according to client's request
 * Each specified callback should be one of these types:
 * 1. array('className', 'methodName')
 * 2. array(object, 'methodName')
 * 3. 'functionName'
 * 4. Closure (AKA Anonymous function)
 * Note that callbacks should be callable, also, they are not checked to be callable until execution (higher performance)
 *
 * @method static void PUT(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void GET(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void POST(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void HEAD(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void TRACE(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void PATCH(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void TRACK(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void DEBUG(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void DELETE(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void CONNECT(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 * @method static void OPTIONS(string $pattern, callback $callback, string $generatorCodeName = null, string $generatorCallback = null)
 *
 * ------------- PHP 5.5 ------------
 *
 * @package Sana
 */
class Route
{
    /**
     * @var array
     */
    protected static $routes = ['GET' => [], 'POST' => [], 'HEAD' => [], 'OPTIONS' => [], 'PUT' => [], 'DELETE' => [], 'TRACE' => [], 'CONNECT' => [], 'PATCH' => [], 'TRACK' => [], 'DEBUG' => []];
    /**
     * @var array
     */
    protected static $generators = [];
    /**
     * @var array
     */
    protected static $parameterTypes = ['alphanumeric' => '[a-zA-Z0-9]', 'alphabet' => '[a-zA-Z]', 'decimal' => '[0-9]', 'lowercase' => '[a-z]', 'uppercase' => '[A-Z]', 'word' => '[a-zA-Z0-9_-]', 'hex' => '[0-9a-fA-F]', 'binary' => '[01]', 'any' => '[^/]'];

    /**
     * Magic method to call self::setRoute()
     *
     * @param string $name      Method type
     * @param array  $arguments [$pattern, $callback, $generatorCodeName = null, $generatorCallback = null]
     *
     * @return void
     */
    public function __call ($name, $arguments)
    {
        array_unshift($arguments, $name);

        call_user_func_array([self::class, 'defineRoute'], $arguments);
    }

    /**
     * Magic method to call self::setRoute()
     *
     * @param string $name      Method type
     * @param array  $arguments [$pattern, $callback, $generatorCodeName = null, $generatorCallback = null]
     *
     * @return void
     */
    public static function __callStatic ($name, $arguments)
    {
        array_unshift($arguments, $name);

        call_user_func_array([self::class, 'defineRoute'], $arguments);
    }

    /**
     * Adds a new method to handle by Sana Route
     * You can add new methods if they are not supported yet.
     * It is useful for adding support for WebDAV, CalDAV, CardDAV or HTTP > version 2.0 methods (Server software MUST support these protocols)
     *
     * @param string $method
     *
     * @return void
     */
    public static function newMethod ($method)
    {
        if (!isset(self::$routes[$method]))
        {
            self::$routes[$method] = [];
        }
    }

    /**
     * Defines a new route
     *
     * @param string   $method
     * @param string   $pattern
     * @param callable $callback
     * @param null     $generatorCodeName
     * @param null     $generatorCallback
     *
     * @throws Exception\Method
     * @throws Exception\Pattern
     * @throws Exception\Generator
     *
     * @return void
     */
    public static function defineRoute ($method, $pattern, $callback, $generatorCodeName = null, $generatorCallback = null)
    {
        $method = strtoupper($method);

        if (!isset(self::$routes[$method]))
        {
            throw new Exception\Method('Requested method is not supported: ' . $method);
        }

        if (($generatorCodeName === null) xor ($generatorCallback === null))
        {
            throw new Exception\Generator('Can not set only $generatorCodeName or $generatorCallback and leave the other one unset');
        }

        if (!strlen($pattern))
        {
            throw new Exception\Pattern('Pattern can not be empty');
        }

        self::$routes[$method][$pattern] = $callback;

        if ($generatorCodeName !== null)
        {
            $generatorCodeName                    = strtolower($generatorCodeName);
            self::$generators[$generatorCodeName] = $generatorCallback;
        }
    }

    /**
     * @param string $type
     * @param string $regex
     *
     * @throws \Sana\Router\Exception\ParameterType
     *
     * @return void
     */
    public static function newParameterType ($type, $regex)
    {
        if (isset(self::$parameterTypes[$type]))
        {
            throw new Exception\ParameterType('Duplicate parameter type: ' . $type);
        }

        self::$parameterTypes[$type] = $regex;
    }

    /**
     * Finds proper route for the client's request and executes it
     * Note that routes are checked one by one, in order that they are added, so you are responsible for conflicting patterns
     *
     * @throws Exception\Callback
     * @throws Exception\Error404
     * @throws Exception\Error501
     *
     * @return string Response
     */
    public static function execute ()
    {
        $method  = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $request = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        // If app is installed in a subdirectory, we ommit it from beginning of $request
        if (isset($_SERVER['SCRIPT_NAME']))
        {
            $scriptPath = dirname($_SERVER['SCRIPT_NAME']);

            if (stripos($request, $scriptPath) === 0)
            {
                $request = substr($request, strlen($scriptPath));
            }
        }

        if($request{0} !== '/')
        {
            $request = '/' . $request;
        }

        if (!isset(self::$routes[$method]))
        {
            throw new Exception\Error501('Error 501: Not Implemented');
        }

        foreach (self::$routes[$method] as $pattern => $callback)
        {
            $pattern = self::convertToRegex($pattern);

            if (preg_match($pattern, $request, $requestParameters))
            {
                foreach ($requestParameters as $key => $value)
                {
                    if (is_numeric($key))
                    {
                        unset($requestParameters[$key]);
                    }
                }

                if (is_array($callback) && is_string($callback[0]))
                {
                    $callback[0] = str_replace('.', '\\', $callback[0]);
                }

                if (!is_callable($callback))
                {
                    throw new Exception\Callback('Callback is not callable');
                }

                if (is_array($callback) && is_string($callback[0]))
                {
                    // callback is a ['ClassName', 'methodName'] array
                    $callback = [new $callback[0]($requestParameters), $callback[1]];
                }

                return $callback($requestParameters);
            }
        }

        var_dump($_SERVER);

        throw new Exception\Error404('Error 404: Not Found');
    }

    /**
     * Converts a Route pattern to a RegEx pattern for processing
     *
     * @param $routePattern
     *
     * @throws Exception\Pattern
     *
     * @return string RegEx pattern
     */
    protected static function convertToRegex ($routePattern)
    {
        if (substr($routePattern, -1, 1) == '?')
        {
            $routePattern = '#^' . preg_quote(substr($routePattern, 0, -1), '#') . '(?:\?.*)?$#';
        }
        else
        {
            $routePattern = '#^' . preg_quote($routePattern, '#') . '$#';
        }

        $parameterTypes = implode('|', array_keys(self::$parameterTypes));

        return preg_replace_callback("#\\\\\\[ *(?<prefix>[^$]*)? *\\\\\\$(?<name>[a-zA-Z0-9]*) *(?:\\\\\\:(?<type>{$parameterTypes}))? *(?<optional>\\\\\\?)?(?<postfix>[^$]*)? *\\\\\\]#", [self::class, 'convertPatternParameterToRegexCallback'], $routePattern);
    }

    /**
     * Converts a Route pattern's parameter to regex
     *
     * @param array $matches
     *
     * @throws Exception\Pattern
     *
     * @return string
     */
    protected static function convertPatternParameterToRegexCallback (array $matches)
    {
        $prefix  = $matches['prefix'];
        $postfix = $matches['postfix'];
        $name    = $matches['name'];
        //$postfix = $matches['postfix'];
        $type     = !empty($matches['type']) ? $matches['type'] : 'any';
        $optional = isset($matches['optional']) ? '?' : '';
        $repeats  = isset($matches['optional']) ? '*' : '+';

        if (!isset(self::$parameterTypes[$type]))
        {
            throw new Exception\Pattern('Parameter type is not supported: ' . $type);
        }

        $parameterType = self::$parameterTypes[$type];

        return "(?:{$prefix}(?<{$name}>{$parameterType}+){$postfix}){$optional}";
    }

    /**
     * Generates a fully qualified URL by calling $generatorCallback
     *
     * @param string $generatorCodeName
     * @param array  $parameters
     *
     * @throws Exception\Callback
     * @throws Exception\Generator
     *
     * @return string
     */
    public static function generate ($generatorCodeName, $parameters = null)
    {
        if (!isset(self::$generators[$generatorCodeName]))
        {
            return '';
        }

        $callback = self::$generators[$generatorCodeName];

        if (!is_callable($callback))
        {
            throw new Exception\Generator('Generator is not callable');
        }

        if (is_array($callback) && is_string($callback[0]))
        {
            // callback is a ['ClassName', 'methodName'], so we create an instance of class and then we call the method
            $callback = [new $callback[0]($parameters), $callback[1]];
        }

        return (string)$callback($parameters);
    }
}

