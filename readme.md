# `Sana Router v1.0`

Sana Router is a easy to use, high peformance, customizable and Open Source router for PHP. It supports all HTTP/1.0, HTTP/1.1 and HTTP/2.0 standard methods, but you can use it for other HTTP based protocols, like WebDAV, CalDAV and CardDAV.

### Installation
Just move `Sana` folder to your project, and include `autoload.php`.

### Supported Methods
Sana Router supports All HTTP/1.0, HTTP/1.1 and HTTP/2.0 methods:

- GET
- POST
- HEAD
- OPTIONS
- PUT
- DELETE
- TRACE
- CONNECT
- PATCH
- TRACK
- DEBUG

### Adding New Methods
If you want to add other methods, such as WebDAV, CalDAV and CardDAV methods, you can use `Router::addMethod()`:

```php
    use \Sana\Router\Route;

    Route::addMethod('MKCOL');
```
Note: Methods are case insensitive.

### Routes
Main purpose of using a router, is adding routes and executing one of them accourding to user's request. to add a new route, you can use desired Method as a Router's method.

Each Method has 4 parameters:

1. $pattern: Patterns (or paths) describe which request belongs to this route. For more information, read bottom of this document.
2. $callback: A valid PHP callback that executes only if client's request matches both method and pattern.
3. $generatorCodeName: A unique code name for this pattern (optional).
4. $generatorCallback: A callback to generate a URL which is valid for this $pattern (optional).

```php
    use \Sana\Router\Route;

    // Execute `home` function if client is requested `http(s)://example.com/` by HTTP GET method.
    Router::GET('/', 'home');

    // Execute a closure if client is requested `http(s)://example.com/posts` by HTTP POST method
    Router::POST('/posts', function($parameters){
        echo 'You requested /posts page!';
    });

    // Execute `contactUs` method of $controller object if client requested `http(s)://example.com/contact-us` by HTTP GET
    $controller = new Controller();
    Route::GET('/contact-us', [$controller, 'contactUs']);

    // Execute `thankYou` method of Controller class if client requested  `http(s)://example.com/contact-us` by HTTP POST
    Route::POST('/contact-us', ['Controller', 'thankYOu']);
```
As you see above, we added 4 routes with 4 different callbacks, which 2 of them have same $pattern which are called by different HTTP Methods.

Notes:

1. Methods are case insensitive. It means that you can use `Route::post()` instead of `Route::POST()`
2. You can add new routes by using `Router::defineRoute()` too. Internally, each of above methods, calls `Router::defineRoute()`, which has 5 parameters, first parameter is $method, next 4 parameters are same as above.
3. for $generatorCodeName and $generatorCallback, read bellow.

### Generators
Generators are a very usefull fearure of Sana Router. Generators generate a fully qualified URL to the given pattern/path. You can use these everywhere in your web application by calling `url()`.

Last two parameters of `Router::defineRoute()` or `Router::METHOD()` are for defining a generator. These parameters are optional, but if you want to set an argument, you should set both.

1. Parameter `$generatorCodeName` is a unique code name, which is a string, so you can generate a URL by passing this string to `url()` function. `url()` function is a wrapper for `Route::generate()`.
2. Parameter `$generatorCallback` is a valid PHP callback which calls by `url()` and should return a URL. Sana Router passes second parameter of `url()` to this callback.

##### url() - Router::generate()
`url()` is a wrapper for `Router::generate()`. They are the same. They have 2 parameters:

1. $generatorCodeName: A code name which is defined for a generator with each pattern/path.
2. $parameters: This information will be passed to generator's callback as first argument.

```php
    use \Sana\Router\Route;

    // Execute `home` function if client is requested `http(s)://example.com/` by HTTP GET method.
    Router::GET('/post/[$postId]', 'showPostById', 'post.byID', function($postID){
        return 'http://example.com/post/'.$postID;
    });

    echo url('post.byID', 15); // http://example.com/post/15
    // or Router::generate('post.byID', 15);
```


### Patterns/Paths
Patterns/Paths are URL request which are called by clients.

Each patterns starts with a slash `/` which is HTTP's standard. If you want to point to `http://example.com/`, you need to use `/` as pattern and if you want to point to `http://example.com/contact-us`, you need to use `/contact-us` as pattern.

`/` and `/contact-us` patterns are static patterns, which means that their callbacks are called if client requests exactly same urls. but Sana Router supports dynamic patterns too.

Each dynamic part of URL should be defined inside of `[` and `]`. Here is the syntax:

`[ PREFIX $parameterName : TYPE POSTFIX ? ]`

1. `[` : Defines start of a parameter/dynamic part of URL.
2. `PREFIX` : Is a string, which can be anything (optional - usefull for optional parameters).
3. `$parameterMame` : A name for this parameter which is prefixed with `$`. Name and value of this parameter will be passed to the callback.
4. `: TYPE` : Defines type of this parameter (see bellow).
5. `POSTFIX` : Just like PREFIX, can be anything (optional - usefull for optional parameters).
6. `?` : Makes this parameter as optional.
7. `]` : Defines end of the parameter difinition.

As you can see, only `[`, `$parameterName` and `]` are required to define a parameter inside a pattern.

Note that all spaces between each part of a parameter are optional. You can add as many spaces as you want to keep patterns readable.

##### Parameter Types
In current version of Sana Router, there are following types are defined by default:

* `alphanumeric` : [a-zA-Z0-9]
* `alphabet` : [a-zA-Z]
* `decimal` : [0-9]
* `lowercase` : [a-z]
* `uppercase` : [A-Z]
* `word` : [a-zA-Z0-9_-]
* `hex` : [0-9a-fA-F]
* `binary` : [01]
* `any` : [^/]

##### Defining New Parameter Types
If you want to add new parameter types, you can use `Router::newParameterType()` and pass two arguments to that:

1. `$type` : Name of the new type
2. `$regex` : A valid regular expression character set, like `[0-8]` which matches octal numbers.

Note that Sana Router will add a `*` or `+` at the end of $regex, to define optional/required parameters, so take care of this when defining new types.

### Exceptions
Here is the hierarchy of exceptions:

+ Throwable (Only PHP 7.0+)
+ + \Exception
+ + + \Sana\Exception\Base
+ + + + \Sana\Router\Exception\Base
+ + + + + \Sana\Router\Exception\Callback
+ + + + + \Sana\Router\Exception\Error404
+ + + + + \Sana\Router\Exception\Error501
+ + + + + \Sana\Router\Exception\Generator
+ + + + + \Sana\Router\Exception\Method
+ + + + + \Sana\Router\Exception\ParameterType
+ + + + + \Sana\Router\Exception\Pattern

Note that:

1. `\Throwable` Is the base class for all PHP 7.0+ exceptions.
2. `\Exception` Is the base class for all PHP 5.x exceptions and extends `\Throwable` in PHP 7.
3. `\Sana\Exception\Base` Is the base class for all Sana libraries exceptions, which extends `\Exception`.
4. `\Sana\Router\Exception\Base` Is the base class for all Sana Router library exceptions, and extends `\Sana\Exception\Base`.
5. All other exceptions, in Sana Router library are extended from `\Sana\Router\Exception\Base`


# License
Sana Router is a BSD licensed library which means that you can use this library in any free or commercial, open source or close source software, as long as all conditions of the license are met.

**Free Software, Hell Yeah!**

Here is the license:

Copyright (c) 2016, Masoud Alimadadi.
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. All advertising materials mentioning features or use of this software
   must display the following acknowledgement:
   This product includes software developed by the Masoud Alimadadi <masoud@alimadadi.info>
4. Neither the name of the Sana Router nor the
   names of its contributors may be used to endorse or promote products
   derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY MASOUD ALIMADADI ''AS IS'' AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL MASOUD ALIMADADI BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

