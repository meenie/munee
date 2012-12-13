Munee: Optimising Your Assets
=============================

---

Features
--------

+ On-the-fly LESS Compiling
+ On-the-fly Image Resizing/Manipulation
+ Smart Asset Caching - Client Side & Server Side
+ Combine CSS or JS into one request
+ Minifying and Gzip Server Response

What is Munee?
--------------

A PHP5.3 library to easily run all CSS through [lessphp](http://leafo.net/lessphp/) ([LESS](http://lesscss.org/)), resize/manipulate images on the fly, minify CSS and JS, and cache assets locally and remotely for lightening fast requests. No need to change how you include your assets in your templates. Just follow the couple of installation instructions below and you are ready to go!

Why the name Munee?
-------------------

The reason I chose the name Munee is because it sounds like 'Money' which is another word for 'Assets' and what this library optimises.  Also, I needed a top level uniquely named Namespace for the library.

Requirements
------------

+ PHP5.3+
+ `RewriteEngine` turned on inside a `.htaccess` file (Or in the Apache Config file) **Optional**

Note on Caching
---------------

Munee caches asset requests server side and returns a `304 Not Modified` on subsequent requests if the asset hasn't been modified. If the asset has been modified, it will overwrite that cache and tell the browser they must revalidate it's cache so the new file can be shown. To accomplish this without the browser's caching engine trying to be smart, Munee sets the `Cache-Control` header to `no-cache`.  If you run into any problems, please [submit an issue](https://github.com/meenie/munee/issues).

[Composer](https://packagist.org/) Installation Instructions
------------------------------------------------------------

1. Create a file called: `composer.json` and add the following:

        {
            "require": {
                "meenie/munee": "*"
            }
        }
1. Run `curl -s http://getcomposer.org/installer | php`
1. Run `php composer.phar install`
1. Make sure the `cache` folder inside `vendor/meenie/munee` is writable
1. Create a file called `munee.php` that is web accessible and paste in the following (**Update the correct path to the `autoload.php` file**):

        // Include the composer autoload file
        require 'vendor/autoload.php';
        // Echo out the response
        echo \munee\Dispatcher::run(new munee\Request());

1. Open the .htaccess file in your webroot and paste in the following: **Optional**

        #### Munee .htaccess Code Start ####
        RewriteRule ^(.*\.(?:css|less|js|jpg|png|gif|jpeg))$ munee.php?files=/$1 [L,QSA]
        #### Munee .htaccess Code End ####

Usage Instructions
------------------

### Handling CSS ###

All CSS is automatically compiled through LESS and cached, there is nothing extra that you need to do.  Any changes you make to your CSS, even LESS files you have `@import` will automatically recreate the cache invalidate the client side cache and force the browser to download the newest version.

**One Request For All CSS**

Format your style so the href has all CSS files delimited by a comma (,).

    <link rel="stylesheet" href="/css/libs/bootstrap.min.css,/css/site.css">

**Minify CSS**

To minify your CSS, add the query string parameter `minify` and set it to `true`

    <link rel="stylesheet" href="/css/libs/bootstrap.min.css,/css/site.css?minify=true">

### Handling/Resizing Images ###

Using Munee, you resize/crop/stretch/fill images on-the-fly using a set of parameters.  The resulting image will be cached and served up on subsequent requests.  If the source image is modified, it will recreate the cache and invalidate the client side cache and force the browser to download the newest version.

**Security**

When using an on-the-fly image resizing tool like this, there is an inherent risk that someone will try and exploit it to try and resize a photo a thousand different ways to take down your server. Munee has a couple of ways to minimise this risk a great deal.  One is it checks that the referer is set and is coming from the same domain that the image is located on.  Another is it only allows 3 resizes of an image within a 5 minute time span.  In a later version, these can be modified or turned off for development purposes.

**Resize Parameters** - Parameters can either be in long form or use their shortened form.  The value for an alias must be wrapped in square brackets `[]`. There is no need to put any characters between each parameter, although you can if you want.

+ `height` (or `h`) - The max height you want the image. Value must be an integer.
+ `width` (or `w`) - The max width you want the image. Value must be an integer.
+ `exact` (or `e`) - Crop the image to the exact `height` and `width`. Value can be `true` or `false`.
+ `stretch` (or `s`) - Stretch the image to match the exact `height` and `width`. Value can be `true` or `false`.
+ `fill` (or `f`) - Draw a background the exact size of the `height` and `width` and centre the image in the middle. (If you do not want the image to be stretched, then do not use the `stretch` parameter along with `fill`). Value can be `true` or `false`.
+ `fillColour` (or `fc`) - The colour of the background. Default is `FFFFFF` (white).  This can be any hex colour (i.e. `FF0000`)
+ `quality` (or `q`) - JPEG compression value. Default is: `75` - It will only work for JPEG's.

**Examples**

Resize an image to a specific width and keep it's correct aspect ratio. **Note:** This is using the long form of the parameters.

    <img src="/img/my-image.jpg?resize=width[250]">

Resize an image and keep it's correct aspect ratio but neither width or height can be bigger than specified. 

    <img src="/img/my-image.jpg?resize=width[100]-height[50]">

Crop an image to an exact size.  If the image is smaller than the provided dimensions, it will not stretch or fill the image out to match the height and width. **Note:** This is using the shortened form of the parameters.

    <img src="/img/my-image.jpg?resize=w[100]h[85]e[true]">

Crop an image and stretch it to an exact size.

    <img src="/img/my-image.jpg?resize=w[200]h[300]e[true]s[true]">

Resize an image and put it on dark grey background the exact size of the dimensions.

    <img src="/img/my-image.jpg?resize=w[500]h[500]f[true]fc[444444]">

### Handling JavaScript ###

All JavaScript is served through Munee so that it can handle the client side caching.

**One Request For All JS**

Format your script tag so the src has all js files delimited by a comma (,).

    <script src="/js/libs/jquery-1.8.1.min.js,/js/libs/bootstrap.min.js,/js/site.js"></script>

**Minify JS**

To minify your JS, add the query string parameter `minify` and set it to `true`

    <script src="/js/libs/jquery-1.8.1.min.js,/js/libs/bootstrap.min.js,/js/site.js?minify=true"></script>

Tips & Tricks
-------------

**Minimising JavaScript Errors When Minified**

Make sure and use curly brackets for block statements (`if`, `while`, `switch`, etc) and terminate lines with a semicolon.  When the JavaScript is minified, it will put all of your code on one line.  If you have left out some brackets for an `if` statement, it will include the rest of your code inside that `if` statement and cause a lot of problems.  As long as you follow decent coding standards, you will not have a problem.

**Using Munee without the `.htaccess` file**

If you would like to use Munee without having to use a `.htaccess` you will need to change how your assets are added in your template.  So instead of doing this:

    <link rel="stylesheet" href="/css/libs/bootstrap.min.css,/css/site.css?minify=true">

You will need to do this:

    <link rel="stylesheet" href="/path/to/munee.php?files=/css/libs/bootstrap.min.css,/css/site.css&minify=true">
