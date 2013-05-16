Munee: Standalone PHP 5.3 Asset Optimisation &amp; Manipulation
===============================================================

#####On-The-Fly Image Resizing, On-the-fly LESS, SASS, CoffeeScript Compiling, CSS &amp; JavaScript Combining/Minifying, and Smart Client Side and Server Side Caching

[![Build Status](https://secure.travis-ci.org/meenie/munee.png?branch=master)](http://travis-ci.org/meenie/munee)
[![Flatter this](http://api.flattr.com/button/flattr-badge-large.png)](http://flattr.com/thing/1191331/)

---

Features
--------

+ On-the-fly LESS, SCSS, CoffeeScript Compiling
+ On-the-fly Image Resizing/Manipulation
+ Smart Asset Caching - Client Side & Server Side
+ Combine CSS or JS into one request
+ Minifying and Gzip Server Response

What is Munee?
--------------

A PHP5.3 library to easily on-the-fly compile LESS, SCSS (SASS is not supported!), or CoffeeScript, resize/manipulate images on-the-fly, minify CSS and JS, and cache assets locally and remotely for lightening fast requests. No need to change how you include your assets in your templates. Just follow the couple of installation instructions below and you are ready to go!

Why the name Munee?
-------------------

The reason I chose the name Munee is because it sounds like 'Money' which is another word for 'Assets' and what this library optimises.  Also, I needed a top level uniquely named Namespace for the library.

Requirements
------------

+ PHP5.3+
+ `RewriteEngine` turned on inside a `.htaccess` file (Or in the Apache Config file) **Optional**

Note on Caching
---------------

Munee caches asset requests server side and returns a `304 Not Modified` on subsequent requests if the asset hasn't been modified. If the asset has been modified, it will overwrite that cache and tell the browser they must revalidate it's cache so the new file can be shown. To accomplish this without the browser's caching engine trying to be smart, Munee sets the `Cache-Control` header to `must-revalidate`.  If you run into any problems, please [submit an issue](https://github.com/meenie/munee/issues).

[Composer](https://packagist.org/) Installation Instructions
------------------------------------------------------------

### Step 1: Download/Install Munee using composer

Add `meenie/Munee` to your `composer.json` file:

```js
{
    "require": {
        "meenie/Munee": "*"
    }
}
```

If you haven't already, download Composer

```bash
$ curl -s http://getcomposer.org/installer | php
```

Now install Munee

```bash
$ php composer.phar install
```

Make sure the `cache` folder inside `vendor/meenie/Munee` is writable

### Step 2: Use Munee in your library

Create a file called `munee.php` that is web accessible and paste in the following

```php
<?php
// Include the composer autoload file
require 'vendor/autoload.php';
// Echo out the response
echo \Munee\Dispatcher::run(new Munee\Request());
```

**Note: Update the correct path to the `autoload.php` file**

### Step 3: Create A `RewriteRule` rule - [Optional](#tips--tricks)

Open the `.htaccess` file in your webroot and paste in the following: 

```bash
#### Munee .htaccess Code Start ####
RewriteRule ^(.*\.(?:css|less|scss|js|coffee|jpg|png|gif|jpeg))$ munee.php?files=/$1 [L,QSA,NC]
#### Munee .htaccess Code End ####
```

Usage Instructions
------------------

### Handling CSS ###

All LESS & SCSS (SASS is not supported!) files are automatically compiled into CSS and cached, there is nothing extra that you need to do.  Any changes you make to your CSS, even LESS/SCSS files you have `@import`ed will automatically recreate the cache, invalidate the client side cache, and force the browser to download the newest version.

If you would like to run **all css** through the `LESS` compiler, then you will need to pass the `lessifyAllCss` parameter into the `Request` class with the value of `true` when you instantiate it:

```php
echo \Munee\Dispatcher::run(new Munee\Request(array('css' => array('lessifyAllCss' => true))));
```

If you would like to run **all css** through the `scssphp` compiler, then you will need to pass the `scssifyAllCss` parameter into the `Request` class with the value of `true` when you instantiate it:

```php
echo \Munee\Dispatcher::run(new Munee\Request(array('css' => array('scssifyAllCss' => true))));
```

**One Request For All CSS**

Format your style so the href has all CSS files delimited by a comma (,).

```html
<link rel="stylesheet" href="/css/libs/bootstrap.min.css,/css/site.less">
```

**Minify CSS**

To minify your CSS, add the query string parameter `minify` and set it to `true`

```html
<link rel="stylesheet" href="/css/libs/bootstrap.min.css,/css/site.scss?minify=true">
```

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

```html
<img src="/img/my-image.jpg?resize=width[250]">
```

Resize an image and keep it's correct aspect ratio but neither width or height can be bigger than specified. 

```html
<img src="/img/my-image.jpg?resize=width[100]-height[50]">
```

Crop an image to an exact size.  If the image is smaller than the provided dimensions, it will not stretch or fill the image out to match the height and width. **Note:** This is using the shortened form of the parameters.

```html
<img src="/img/my-image.jpg?resize=w[100]h[85]e[true]">
```

Crop an image and stretch it to an exact size.

```html
<img src="/img/my-image.jpg?resize=w[200]h[300]e[true]s[true]">
```

Resize an image and put it on dark grey background the exact size of the dimensions.

```html
<img src="/img/my-image.jpg?resize=w[500]h[500]f[true]fc[444444]">
```

### Handling JavaScript ###

All JavaScript is served through Munee so that it can handle the client side caching.

**One Request For All JS**

Format your script tag so the src has all js files delimited by a comma (,).

```html
<script src="/js/libs/jquery-1.8.1.min.js,/js/libs/bootstrap.min.js,/js/site.js"></script>
```

**Minify JS**

To minify your JS, add the query string parameter `minify` and set it to `true`

```html
<script src="/js/libs/jquery-1.8.1.min.js,/js/libs/bootstrap.min.js,/js/site.js?minify=true"></script>
```

**CoffeeScript**

CoffeeScript can also be automatically compiled if included in your html.  When requested, Munee will compile it, cache it, and set the `Content-Type` headers to `text/javascript`

```html
<script src="/js/libs/jquery-1.8.1.min.js,/js/libs/bootstrap.min.js,/js/site.coffee?minify=true"></script>
```

Update 1.5.0 Important Note
---------------------------

I have changed the vendor to be uppercase so you will need to update how you instantiate the Munee library.  Please follow step 2 of the installion instructions.

Update 1.3.0 Important Note
---------------------------

In this and future versions of Munee, the way CSS is run through the LESS compiler has changed.  By default, only `.less` files will be compiled and you will have to set a special parameter to have all CSS (`.css`) files run through the compiler as well. [See here](#handling-css) for more instructions. The reason behind this change is technically `.less` files should have only valid LESS in them and `.css` should only have valid CSS in them.

Tips & Tricks
-------------

**Resizing Images In Emails**

If you want to resize images through Munee in your emails, you will need to turn off one of the security features in Munee.  This is the Referrer check.  To get it working, you can pass in the following option when you instantiate Munee:

```php
echo \Munee\Dispatcher::run(new \Munee\Request(array(
    'image' => array(
        'checkReferrer' => false
    )
)));
```

**Minimising JavaScript Errors When Minified**

Make sure and use curly brackets for block statements (`if`, `while`, `switch`, etc) and terminate lines with a semicolon.  When the JavaScript is minified, it will put all of your code on one line.  If you have left out some brackets for an `if` statement, it will include the rest of your code inside that `if` statement and cause a lot of problems.  As long as you follow decent coding standards, you will not have a problem.

**Using Munee without the `.htaccess` file**

If you would like to use Munee without having to use a `.htaccess` you will need to change how your assets are added in your template.  So instead of doing this:

```html
<link rel="stylesheet" href="/css/libs/bootstrap.min.css,/css/site.css?minify=true">
```

You will need to do this:

```html
<link rel="stylesheet" href="/path/to/munee.php?files=/css/libs/bootstrap.min.css,/css/site.css&minify=true">
```

**Preventing Munee From Setting Headers**

If for some reason you would like to prevent Munee from setting any headers, you can pass a second argument in the Dispatcher::run() function with `array('setHeaders' => false)`.

```php
$content = \Munee\Dispatcher::run(new \Munee\Request(array('files' => '/css/site.css')), array('setHeaders' => false));
```

Known Issues
------------

Munee will *not* work with PHP 5.3.1 as there is an issue with running protected methods in the wrong context.  Please update your version of PHP as I will not be fixing this. - [Read More](https://github.com/meenie/munee/issues/15)
