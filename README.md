Munee: Optimising Your Assets
=============================

---

What is Munee?
--------------

A PHP5.3 library to easily run all CSS through [lessphp](http://leafo.net/lessphp/)
([LESS](http://lesscss.org/)) and when the project goes live, all CSS and JS are minified and cached
for lightening fast requests from a page.  No need to change how you include your assets in your
templates.  Just drop the `munee` folder into your webroot, paste a couple of `RewriteRule`'s in
your `.htaccess` file and you are on your way.

Why the name Munee?
-------------------

The reason I chose the name Munee is because it sounds like 'Money' which is another word for
'Assets' and what this library optimises.  Also, I needed a top level uniquely named Namespace
for the library.

Requirements
------------
+ PHP5.3+
+ `RewriteEngine` turned on inside a `.htaccess` file (Or in the Apache Config file)

What Happens When?
------------------

**For CSS**

The `RewriteRule`  will always run CSS through the Munee Library because the library
needs to compile the CSS with lessphp and then cache the result. With the newest version of lessphp,
Munee will make sure and rebuild the compiled CSS if any of the files have changed (including any
files you have `@import` within the CSS files themselves - Yay!!).

**For JavaScript**

If you are requesting just one JavaScript file (`<script src="/js/libs/jquery-1.8.1.min.js"></script>`),
the `RewriteRule` will not run it through the Munee Library.  It will just serve the file straight from your web server.
Might as well not have php do more than it has to.  If you are requesting multiple JS files (or minifying),
Munee will put them together into one request and cache the result.  It will also make sure and check the
cache is newer than each of those requested files so you don't have any caching issues.

Note on Caching
---------------

Munee caches asset requests server side and will overwrite that cache if it finds an asset has been
modified.  If you are running the assets through minification, it will set the correct headers
to cache the files client side and then return a `304 Not Modified` if the files have not been
modified since the last request.  This will save you a substantial amount of bandwidth.

[Composer](https://packagist.org/) Installation Instructions
---------------------------------------

1. Add the following to your `require` attribute inside composer.json: `"meenie/munee": "dev-master"`
1. Run `php composer.phar install`
1. Create a php file that is web-accessible (ex: `webroot/munee.php`), include Munee's bootstrap
file and echos out `munee\Dispatcher::run(new munee\Request())`.
1. Open the .htaccess file in your webroot and paste in the following:

```
#### Munee .htaccess Code Start ####
# Only run CSS and LESS through Munee every time if calling a direct file.
RewriteCond %{REQUEST_FILENAME} !-f [OR]
RewriteCond %{REQUEST_URI} \.(css|less)$
RewriteRule ^(minify/)?(.*\.(css|less|js))$ munee.php?minify=$1&files=/$2&type=$3 [L,QSA]
#### Munee .htaccess Code END ####
```

Download Installation Instructions
------------------------------------------

1. [Download the Munee Library](https://github.com/meenie/munee/archive/master.zip)
1. Unzip the file into your webroot
1. Make sure the `cache` folder inside of the top `munee` folder is server writable
1. Open the .htaccess file in your webroot and paste in the following:
    ```
    #### Munee .htaccess Code Start ####
    # Only run CSS and LESS through Munee every time if calling a direct file.
    RewriteCond %{REQUEST_FILENAME} !-f [OR]
    RewriteCond %{REQUEST_URI} \.(css|less)$
    RewriteRule ^(minify/)?(.*\.(css|less|js))$ munee/index.php?minify=$1&files=/$2&type=$3 [L,QSA]
    #### Munee .htaccess Code END ####
    ```

Usage Instructions
------------------

**One Request For All CSS**

Format your style so the href has all css files delimited by a comma (,).

```
<link rel="stylesheet" href="/css/libs/bootstrap.min.css,/css/site.css">
```

**Minify CSS**

Add the word 'minify' before the request to your files. (**Note: only add the word minify once**)

```
<link rel="stylesheet" href="/minify/css/libs/bootstrap.min.css,/css/site.css" />
```

**One Request For All JS**

Format your script tag so the src has all js files delimited by a comma (,).

```
<script src="/js/libs/jquery-1.8.1.min.js,/js/libs/bootstrap.min.js,/js/site.js"></script>
```

**Minify JS**

Add the word 'minify' before the request to your files. (**Note: only add the word minify once**)

```
<script src="/minify/js/libs/jquery-1.8.1.min.js,/js/libs/bootstrap.min.js,/js/site.js"></script>
```

Tips & Tricks
-------------

**Minifying assets without prefixing the URL '/minify'.**

If you want to run your assets through the minifier without having to prefix the URLs with
'/minify' then just add '?minify=true' to the end of the request.

**Minimising JavaScript Errors When Minified**

Make sure and use curly brackets for block statements (`if`, `while`, `switch`, etc) and
terminate lines with a semicolon.  When the JavaScript is minified, it will put all of your code on
one line.  If you have left out some brackets for an `if` statement, it will include the rest of your
code inside that `if` statement and cause a lot of problems.  As long as you follow decent coding
standards, you will not have a problem.