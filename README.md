Munee: Optimising Your Assets
=============================

---

What is Munee?
--------------

I've created this library to easily run all CSS through [lessphp](http://leafo.net/lessphp/)
([LESS](http://lesscss.org/)) and when the project goes live, all CSS and JS are minified and cached
for lightening fast requests from a page.  One thing to note, if you are requesting just one JS file,
it will not run it through the Munee Library.  It will just serve that straight up from Apache
(Might as well not have to do more than you need to).  If you are requesting multiple JS files it
will put them together into one request and cache the result.  It will also make sure and check the
cache is newer than each of those requested files so you don't have any caching issues.  As for CSS,
it will always run them through the Munee Library because it needs to compile them with lessphp and
then cache the result. With the newest version of lessphp, it will make sure and rebuild the
compiled CSS if any of the files have changed (including any files you have included within the CSS
files themselves - yay!).

Why the name Munee?
-------------------

The reason I chose the name Munee is because it sounds like 'Money' which is another word for
'Assets' and what this library deals with.  Also, I needed a top level uniquely named Namespace
for the library.

Note on Caching
---------------

Munee caches asset request server side and will overwrite that cache if it finds an asset has been
modified.  If you are running the assets through minification, it will set the correct headers
to cache the files client side and then return a `304 Not Modified` if the files have not been
modified since the last request.

Installation Instructions
-------------------------

+ Unzip the folder into your webroot
+ Make sure the `cache` folder inside of the top `munee` folder is server writable
+ Open the .htaccess file in your webroot and paste in the following:

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