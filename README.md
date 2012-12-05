Munee: Optimising Your Assets
=============================

---

Features
--------

+ On the fly LESS Compiler
+ On-the-fly Image Resizing/Manipulation
+ Smart Asset Caching - Client Side & Server Side
+ Combine CSS or JS into one request
+ Minifying and Gzip

What is Munee?
--------------

A PHP5.3 library to easily run all CSS through [lessphp](http://leafo.net/lessphp/) ([LESS](http://lesscss.org/)),
resize/manipulate images on the fly, minify CSS and JS, and cache assets locally and remotely for lightening fast
requests. No need to change how you include your assets in your templates. Just follow the couple of installation
instructions below and you are ready to go!

Why the name Munee?
-------------------

The reason I chose the name Munee is because it sounds like 'Money' which is another word for
'Assets' and what this library optimises.  Also, I needed a top level uniquely named Namespace
for the library.

Requirements
------------

+ PHP5.3+
+ `RewriteEngine` turned on inside a `.htaccess` file (Or in the Apache Config file) **Optional**

Note on Caching
---------------

Munee caches asset requests server side and returns a `304 Not Modified` on subsequent requests if the asset hasn't
been modified. If the asset has been modified, it will overwrite that cache and tell the browser they must revalidate
it's cache so the new file can be shown.

[Composer](https://packagist.org/) Installation Instructions
------------------------------------------------------------

1. Create a file called: `composer`.json and add the following:

        {
            "require": {
                "meenie/munee": "*"
            }
        }

2. Run `curl -s http://getcomposer.org/installer | php`
2. Run `php composer.phar install`
3. Make sure the `cache` folder inside `vendor/meenie/munee` is writable
4. Create a file called `munee.php` that is web accessible and paste in the following (**Update to the correct path**):

        // Include the composer autoload file
        require 'vendor/autoload.php';

        // Echo out the response
        echo \munee\Dispatcher::run(new munee\Request());

5. Open the .htaccess file in your webroot and paste in the following:

        #### Munee .htaccess Code Start ####
        RewriteRule ^(.*\.(css|less|js|jpg|png|gif|jpeg))$ munee.php?files=/$1&ext=$2 [L,QSA]
        #### Munee .htaccess Code END ####

Usage Instructions
------------------

**One Request For All CSS**

Format your style so the href has all css files delimited by a comma (,).

    <link rel="stylesheet" href="/css/libs/bootstrap.min.css,/css/site.css">

**Minify CSS**

Add the word 'minify' before the request to your files.

    <link rel="stylesheet" href="/css/libs/bootstrap.min.css,/css/site.css?minify=true" />

**One Request For All JS**

Format your script tag so the src has all js files delimited by a comma (,).

    <script src="/js/libs/jquery-1.8.1.min.js,/js/libs/bootstrap.min.js,/js/site.js"></script>

**Minify JS**

Add the word 'minify' before the request to your files.

    <script src="/js/libs/jquery-1.8.1.min.js,/js/libs/bootstrap.min.js,/js/site.js?minify=true"></script>

Tips & Tricks
-------------

**Minimising JavaScript Errors When Minified**

Make sure and use curly brackets for block statements (`if`, `while`, `switch`, etc) and
terminate lines with a semicolon.  When the JavaScript is minified, it will put all of your code on
one line.  If you have left out some brackets for an `if` statement, it will include the rest of your
code inside that `if` statement and cause a lot of problems.  As long as you follow decent coding
standards, you will not have a problem.