# Image helpers plugin for Craft CMS 3.x

THIS PLUGIN IS IN BETA AND SHOULD NOT BE USED IN PRODUCTION

## Installation

```
composer require craftsnippets/craft-image-helpers
```

## Usage

### craft.imageHelpers.picture(image, transform, htmlAttributes)

This function generates `<picture>` HTML element from image asset object. 

If [imager](https://plugins.craftcms.com/imager) or [imager-x](https://plugins.craftcms.com/imager-x) is installed, plugin by default will use either of them for image transforms. Otherwise, native Craft image transforms will be used.

First parameter of function should contain image object (not query object - so for asset field of entry, it would be `entry.assetField.one()` instead of `entry.assetField`).

Second parameter is array containing image transform settings. These settings can be identical to one used by native Craft image transforms. Transform settings are actually optional - you can use `picture()` function without providing them. This would make sense if you just wanted to make use of webp creation functionality, which is described below.

Third, optional parameter of function can contain array of HTML attributes that will be set on `<img>` element within `<picture>`. These settings need to be in same format as accepted by [tag()](https://docs.craftcms.com/v3/dev/functions.html#tag) or [attr()](https://docs.craftcms.com/v3/dev/functions.html#attr) Twig function. More info about these functions can be found in [this article](http://craftsnippets.com/articles/using-attr-function-to-render-html-attributes-in-craft-cms).

If image is missing (image object equals `null`), placeholder will be generated based on `width` and `height` settings of transform. If either `width` or `height` are missing, placeholder will take form of square based on `width`/`height`. Placeholder is inline SVG picture and has specific CSS class which differentiates it from other non-placeholder images and allows you to style it.

Additional webp version of image will be automatically created as one of pictures element `<source>`, assuming that:

* We didn't disabled that behaviour in plugin settings file using `useWebp` setting.
* Image is not in SVG format.
* We didn't disabled this behaviour for specific picture adding `useWebp` set to `false` in transform setting. 
* Our server supports webp images.
* We don't set image transform to webp already (this would create two identical webp variants).
* Our source image is not webp, without format set in transform settings (this also would create two identical webp variants).

`<source>` containing webp version will have `type` attribute set to `image/webp`, so browsers that do not support webp will be able to use `<source>` with other format. That would be one that was set in transform settings, or format was not specified - source containing image in original format.

If you use imager-x (or imager), you might set additional `filenamePattern ` parameter in transform settings. This will allow you to set transfrom filename pattern. More info in [imager-x docs](https://imager-x.spacecat.ninja/configuration.html#filenamepattern-bool).


### craft.imageHelpers.pictureMedia(image, transforms, commonSettings, htmlAttributes)

This function will generate `<picture>` element with multiple sources, each based on specific image transform and each displayed for specific media query. `transforms` array need to be structured like this:

```
{
	'(max-width: 600px)': {
		width: 100,
		height: 200,
		mode: 'crop',
	},
	'(max-width: 999px)': {
		width: 400,
		height: 500,
		mode: 'fit',
	},
	'(min-width: 1000px)': null,
}
```

This will render `<picture>` like (webp version generation disabled for simplicity sake):

```
<picture>
<source type="image/jpeg" srcset="http://test/web/img/_100x200_crop_center-center_none/x.jpg" media="(max-width: 600px)">
<source type="image/jpeg" srcset="http://test/web/img/_400x500_fit_center-center_none/x.jpg" media="(max-width: 999px)">
<source srcset="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%220%22%20height%3D%220%22%2F%3E" media="(min-width: 1000px)">
<img class="zzz" src="http://test/web/img/_100x200_crop_center-center_none/x.jpg">
</picture>
```

Few things to note here:

* Array keys are media query strings that will used in each `<source>` `media` attribute.
* Array values are transform settings for each source.
* If array value is set to null, this means that for specific breakpoint `<source>` will contain only transparent pixel. This is usefull if we do not want to display picture on specific breakpoint - if we just hidden it with CSS, browser would still download it.
* Picture element has `<img>` tag inside which serves as fallback for browsers that do not support `<picture>` (see support info on [caniuse](https://caniuse.com/#feat=picture)). First image transform will be used for fallback image.

Third parameter of function - `commonSettings` contains transform settings that are common for all transforms. It can be used to avoid setting same settings for every transform.

For example, this code:

```
{% set sources = {
    '(min-width: 1000px)': {
        width: 500,
        height: 200,
    },
    '(max-width: 999px)': {
        width: 200,
        height: 200,
    },    
} %}
{% set common = {
    format: 'png',
    mode: 'stretch',
} %}

{{craft.imageHelpers.pictureMedia(glob.pole.one(), sources, common,{
    class: 'some-class',
})}}
```

Will generate `<picture>` with each source in png format and transform mode `stretch`. Note that settings of individual transforms will overwrite any conflicting settings from `commonSettings`.


Other than that, `pictureMedia()` behaves in same way as `picture()` - it can generate placeholders or webp versions of images.

### craft.imageHelpers.pictureMin(image, transforms, commonSettings, htmlAttributes)

This function behaves in same way as `pictureMedia()`, except we do not use media query strings as transforms array keys. We use number of minimum screen width. Which internally will be transformed toproper breakpoints. So, for example:

```
{
	300: {
		width: 100,
		height: 200,
		mode: 'crop',
	},
	600: {
		width: 400,
		height: 500,
		mode: 'fit',
	},
}
```

This will generate `<picture>` element like this:

```
<picture>
<source type="image/jpeg" srcset="http://test/web/img/_100x200_crop_center-center_none/x.jpg" media="(min-width: 600px)">
<source type="image/jpeg" srcset="http://test/web/img/_400x500_fit_center-center_none/x.jpg" media="(min-width: 300px)">
<img class="zzz" src="http://test/web/img/_100x200_crop_center-center_none/xt.jpg">
</picture>
```	

Note, that browser will use first `<source>` which media query fits. So if you are on screen of width 1024px, and first source would be one with media query `(min-width: 300px)`, it would be used - even if there is other, with setting `(min-width: 600px)`. Thats why sources in this function are sorted from ones with largest min-width, to smallest.

### craft.imageHelpers.pictureMax(image, transforms, commonSettings, htmlAttributes)

Same as `pictureMin()`, except we use `max-width` media query. Sources will be sorted from smallest to largest `max-width` value.

### craft.imageHelpers.placeholder(transform)

This function will generate placeholder based on inline SVG image. You can pass into it image transform settings array - `width` and `height` values from this array will be used.

If only width or only height is provided, image will take form of square.

## Settings

Place these settings in `config/image.helpers.php`:

* `useWebp` - if webp version of image should automatically be generated. Default: `true`.
* `useImager` - if imager or imager-x should be used for transforms (assuming one of these plugins is installed). Default: `true`.
* `usePlaceholders` - if placeholder should be generated if image is missing (image object equals `null`). Default: `true`.
* `placeholderClass` - css class added to `<picture>` element if placeholder image is displayed. Default: `is-placeholder`.

