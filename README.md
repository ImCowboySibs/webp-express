# WebP Express

Serve autogenerated WebP images instead of jpeg/png to browsers that supports WebP.

The plugin is available on the Wordpress codex ([here](https://wordpress.org/plugins/webp-express/)), and developed on github ([here](https://github.com/rosell-dk/webp-express/)).

## Description
Almost 4 out of 5 mobile users use a browser that is able to display webp images. Yet, on most websites, they are served jpeg images, which are typically double the size of webp images for a given quality. What a waste of bandwidth! This plugin was created to help remedy that situation. With little effort, Wordpress admins can have their site serving autogenerated webp images to browsers that supports it, while still serving jpeg and png files to browsers that does not support webp.

### The image converter
The plugin uses the [WebP Convert](https://github.com/rosell-dk/webp-convert) library to convert images to webp. *WebP Convert* is able to convert images using multiple methods. There are the "local" conversion methods: `cwebp`, `gd`, `imagick`. If none of these works on your host, there are the cloud alternatives: `ewww` (paid) or connecting to a Wordpress site where you got WebP Express installed and you enabled the "web service" functionality.

### The "Serving webp to browsers that supports it" part.

The plugin supports different ways of delivering webps to browsers that supports it:

1. By routing jpeg/png images to the corresponding webp - or to the image converter if the image hasn't been converted yet.
2. By altering the HTML, replacing image tags with *picture* tags. Missing webps are auto generated upon visit.
3. By altering the HTML, replacing image URLs so all points to webp. The replacements only being made for browsers that supports webp. Again, missing webps are auto generated upon visit.
4. In combination with *Cache Enabler*, the same as above can be achieved, but with page caching.
5. You can also deliver webp to *all* browsers and add the [webpjs](http://webpjs.appspot.com) javascript, which provides webp support for browsers that doesn't support webp natively. You currently have to add the javascript yourself, but I expect to add an option for it in the next release.

The plugin builds on [WebPConvert](https://github.com/rosell-dk/webp-convert) and its "WebP On Demand" solution described [here](https://github.com/rosell-dk/webp-convert/blob/master/docs/webp-on-demand/webp-on-demand.md)

### Benefits
- Much faster load time for images in browsers that supports webp. The converted images are typically *less than half the size* (for jpeg), while maintaining the same quality. Bear in mind that for most web sites, images are responsible for the largest part of the waiting time.
- Better user experience (whether performance goes from terrible to bad, or from good to impressive, it is a benefit)
- Better ranking in Google searches (performance is taken into account by Google)
- Less bandwidth consumption - makes a huge difference in the parts of the world where the internet is slow and costly (you know, ~80% of the world population lives under these circumstances).
- Currently ~73% of all traffic, and ~79% of mobile browsing traffic are done with browsers supporting webp. With Mozilla and Microsoft [finally on board](https://medium.com/@richard_90141/webp-image-support-an-8-year-saga-7aa2bedb8d02), these numbers are bound to increase. Check current numbers on  [caniuse.com](https://caniuse.com/webp)).


## Installation

1. Upload the plugin files to the `/wp-content/plugins/webp-express` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure it (the plugin doesn't do anything until configured)
4. Verify that it works

### Configuring
You configure the plugin in *Settings > WebP Express*.

#### Conversion methods
WebP Express has a bunch of methods available for converting images: Executing cwebp binary, Gd extension, Imagick extension, ewww cloud converter and remote WebP express. Each requires *something*. In many cases, one of the conversion methods will be available. You can quickly identify which converters are working - there is a green icon next to them. Hovering conversion methods that are not working will show you what is wrong.

In case no conversion methods are working out of the box, you have several options:
- You can install this plugin on another website, which supports a local conversion method and connect to that using the "Remote WebP Express" conversion method
- You can [purchase a key](https://ewww.io/plans/) for the ewww cloud converter. They do not charge credits for webp conversions, so all you ever have to pay is the one dollar start-up fee :)
- You can set up [webp-convert-cloud-service](https://github.com/rosell-dk/webp-convert-cloud-service) on another server and connect to that. Its open source.
- You can try to meet the server requirements of cwebp, gd, imagick or gmagick. Check out [this wiki page](https://github.com/rosell-dk/webp-convert/wiki/Meeting-the-requirements-of-the-converters) on how to do that

### The auto quality
If your server has imagick og gmagick installed, the plugin will be able to detect the quality of a jpeg, and use the same quality for the converted webp. You can tell if it does, by looking at the quality option. If it allows you to select "auto" quality, it is available, otherwise it is not, and you will only have the option to set a specific quality for all conversions. *Auto* should be chosen, if available, as this ensures that each conversion are converted with an appropriate quality. Say you have a jpeg with low quality (say 30). The best result, is achieved by converting it to the same quality. Converting it with high quality (say 80), will not get you better quality, only a larger file.

If you do not the "auto" option available:
- Install imagick or gmagick, if you can
- Use "Remote WebP Express" converter to connect to a site, that *does* have the auto option available
- If you have cwebp converter available, you can configure it to aim for a certain reduction, rather than using the quality parameter. Set this to for example 50%, or even 45%.

### Verifying that it works.
Once, you have a converter, that works, when you click the "test"-button, you are ready to test the whole stack, and the rewrite rules. To do this, first make sure to select something other than "Do not convert any images!" in *Image types to convert*. Next, click "Save settings". This will save settings, as well as update the *.htaccess*.

If you are working in a browser that supports webp (ie Google Chrome), you will see a link "Convert test image (show debug)" after a successful save. Click that to test if it works. The screen should show a textual report of the conversion process. If it shows an image, it means that the *.htaccess* redirection isn't working. It may be that your server just needs some time. Some servers has set up caching. It could also be that your images are handled by nginx.

Note that the plugin does not change any HTML. In the HTML the image src is still set to ie "example.jpg". To verify that the plugin is working (without clicking the test button), do the following:

- Open the page in Google Chrome
- Right-click the page and choose "Inspect"
- Click the "Network" tab
- Reload the page
- Find a jpeg or png image in the list. In the "type" column, it should say "webp"

In order to test that the image is not being reconverted every time, look at the Response headers of the image. There should be a "X-WebP-Convert-Status" header. It should say "Serving existing converted image" the first time, but "Serving existing converted image" on subsequent requests (WebP-Express is based upon [WebP Convert](https://github.com/rosell-dk/webp-convert)).

You can also append `?debug` after any image url, in order to run a conversion, and see the conversion report. Also, if you append `?reconvert` after an image url, you will force a reconversion of the image.

### Notes

*Note:*
The redirect rules created in *.htaccess* are pointing to a PHP script. If you happen to change the url path of your plugins, the rules will have to be updated. The *.htaccess* also passes the path to wp-content (relative to document root) to the script, so the script knows where to find its configuration and where to store converted images. So again, if you move the wp-content folder, or perhaps moves Wordpress to a subfolder, the rules will have to be updated. As moving these things around is a rare situation, WebP Express are not using any resources monitoring this. However, it will do the check when you visit the settings page.

*Note:*
Do not simply remove the plugin without deactivating it first. Deactivation takes care of removing the rules in the *.htaccess* file. With the rules there, but converter gone, your Google Chrome visitors will not see any jpeg images.

*Note:*
The plugin has not been tested in multisite configurations.


## Limitations

* The plugin does not work on Microsoft IIS server, nor in WAMP
* The plugin has not been tested with multisite installation

## Frequently Asked Questions

### How do I verify that the plugin is working?
See the "Verifying that it works section"

### No conversions methods are working out of the box
Don't fret - you have options!

- If you a controlling another WordPress site (where the local conversion methods DO work), you can set up WebP Express there, and then connect to it by configuring the “Remote WebP Express” conversion method.
- You can also setup the ewww conversion method. To use it, you need to purchase an api key. They do not charge credits for webp conversions, so all you ever have to pay is the one dollar start-up fee 🙂 (unless they change their pricing – I have no control over that). You can buy an api key here: https://ewww.io/plans/
- If you are up to it, you can try to get one of the local converters working. Check out [this page](https://github.com/rosell-dk/webp-convert/wiki/Meeting-the-requirements-of-the-converters) on the webp-convert wiki
- Finally, if you have access to a server and are comfortable with installing projects with composer, you can install [webp-convert-cloud-service](https://github.com/rosell-dk/webp-convert-cloud-service). It's open source.

### It doesn't work - Although test conversions work, it still serves jpeg images.
Actually, you might be mistaking, so first, make sure that you didn't make the very common mistake of thinking that something with the URL *example.com/image.jpg* must be a jpeg image. The plugin serves webp images on same URL as the original (unconverted) images, so do not let appearances fool you! Confused? See next FAQ item.

Assuming that you have inspected the *content type* header, and it doesn't show "image/webp", please make sure that:
1) You tested with a browser that supports webp (such as Chrome)
2) The image URL you are looking at are not pointing to another server (such as gravatar.com)

Assuming that all above is in place, please look at the response headers to see if there is a *X-WebP-Convert-Status* header. If there isn't, well, then it seems that the problem is that the image request isn't handed over to WebP Express. Reasons for that can be:

- You are on NGINX (or an Apache/Nginx combination). NGINX requires special attention, please look at that FAQ item
- You are on WAMP. Please look at that FAQ item

I shall write more on this FAQ item... Stay tuned.

### How can a webp image be served on an URL ending with "jpg"?
Easy enough. Browsers looks at the *content type* header rather than the URL to determine what it is that it gets. So, although it can be confusing that the resource at *example.com/image.jpg* is a webp image, rest assured that the browsers are not confused. To determine if the plugin is working, you must therefore examine the *content type* response header rather than the URL. See the "How do I verify that the plugin is working?" Faq item.

I am btw considering making an option to have the plugin redirect to the webp instead of serving immediately. That would remove the apparent mismatch between file extension and content type header. However, the cost of doing that will be an extra request for each image, which means extra time and worse performance. I believe you'd be ill advised to use that option, so I guess I will not implement it. But perhaps you have good reasons to use it? If you do, please let me know!

### I am on NGINX / OpenResty
It is possible to make WebP Express work on NGINX, but it requires manually inserting redirection rules in the NGINX configuration file (nginx.conf or the configuration file for the site, found in `/etc/nginx/sites-available`). On most setups the following rules should work:

```
if ($http_accept ~* "webp"){
  rewrite ^/(.*).(jpe?g|png)$ /wp-content/plugins/webp-express/wod/webp-on-demand.php?wp-content=wp-content break;
}
```

If it doesn't work, try this instead:

```
if ($http_accept ~* "webp"){
  rewrite ^/(.*).(jpe?g|png)$ /wp-content/plugins/webp-express/wod/webp-on-demand.php?xsource=x$request_filename&wp-content=wp-content break;
}
```

*Beware:* If you copy the code above, you might get an html-encoded ampersand before "wp-content"

The `wp-content` argument must point to the wp-content folder (relative to document root). In most installations, it is 'wp-content'.

Note that the rules above redirects every image request to the PHP script. To get better performance, you can add a rule that redirects jpeg/png requests directly to existing webp images. There are some rules for that [here](https://www.keycdn.com/blog/convert-to-webp-the-successor-of-jpeg#rewriterules-for-nginx-nginx-conf), but they need to be modified because WebP Express stores the webp files in a different location (`wp-content/webp-express/webp-images/doc-root`).

Discussion on this topic [here](https://wordpress.org/support/topic/nginx-rewrite-rules-4/)

### I am on a WAMP stack
It has been reported that WebP Express *almost* works on WAMP stack (Windows, Apache, MySQL, PHP). I'd love to debug this, but do not own a Windows server or access to one... Can you help?

### I am using Jetpack
If you install Jetpack and enable the "Speed up image load times" then Jetpack will alter the HTML such that images are pointed to their CDN.

Ie:
`<img src="https://example.com/wp-content/uploads/2018/09/architecture.jpg">`

becomes:
`<img src="https://i0.wp.com/example.com/wp-content/uploads/2018/09/architecture.jpg">`

Jetpack automatically serves webp files to browsers that supports it using same mechanism as the standard WebP Express configuration: If the "Accept" header contains "image/webp", a webp is served (keeping original file extension, but setting the "content-type" header to "image/webp"), otherwise a jpg is served.

As images are no longer pointed to your original server, the .htaccess rules created by WebP Express will not have any effect.

So if you are using Jetpack you don't really need WebP Express?
Well, there is no point in having the "Speed up image load times" enabled together with WebP Express.

But there is a case for using WebP Express rather than Jetpacks "Speed up image load times" feature:

Jetpack has the same drawback as the *Varied image responses* operation mode: If a user downloads the file, there will be a mismatch between the file extension and the image type (the file is ie called "logo.jpg", but it is really a webp image). I don't think that is a big issue, but for those who do, WebP Express might still be for you, even though you have Jetpack. And that is because WebP Express can be set up just to generate webp's, without doing the internal redirection to webp (will be possible from version 0.10.0). You can then for example use the [Cache Enabler](https://wordpress.org/plugins/cache-enabler/) plugin, which is able to generate and cache two versions of each page. One for browsers that accepts webp and one for those that don't. In the HTML for webp-enabled browsers, the images points directly to the webp files.

Pro Jetpack:
- It is a free CDN which serves webp out of the box.
- It optimizes jpegs and pngs as well (but note that only about 1 out of 5 users gets these, as webp is widely supported now)

Con Jetpack:
- It is a big plugin to install if you are only after the CDN
- It requires that you create an account on Wordpress.com

Pro WebP Express:
- You have control over quality and metadata
- It is a small plugin and care has been taken to add only very little overhead
- Plays well together with Cache Enabler. By not redirecting jpg to webp, there is no need to do any special configuration on the CDN and no issue with misleading file extension, if user downloads a file.

Con WebP Express:
- If you are using a CDN and you are redirecting jpg to webp, you must configure the CDN to forward the Accept header. It is not possible on all CDNs.

### Why do I not see the option to set WebP quality to auto?
The option will only display, if your system is able to detect jpeg qualities. To make your server capable to do that, install *Imagick extension* (PECL >= 2.2.2) or enable exec() calls and install either *Imagick* or *Gmagick*.

If you have the *Imagick*, the *Imagick binary* or the *Remote WebP Express* conversion method working, but don't have the global "auto" option, you will have the auto option available in options of the individual converter.

Note: If you experience that the general auto option doesn't show, even though the above-mentioned requirements should be in order, check out [this support-thread](https://wordpress.org/support/topic/still-no-auto-option/).

### How do I configure my CDN ("Varied image responses" mode)?
In *Varied image responses* operation mode, the response *varies* depending on whether the browser supports webp or not (which browsers signals in the *Accept* header). Some CDN's support this out of the box, others requires some configuration and others doesn't support it at all.

For a CDN to cooperate, it needs to
1) forward the *Accept* header and
2) Honour the Vary:Accept response header.

You can also make it "work" on some CDN's by bypassing cache for images. But I rather suggest that you try out the *No varied image responses* mode (see next FAQ item)

#### Status of some CDN's

- *KeyCDN*: Does not support varied responses. I have added a feature request [here](https://community.keycdn.com/t/support-vary-accept-header-for-conditional-webp/1864). You can give it a +1 if you like!
- *Cloudflare*: See the "I am on Cloudflare" item
- *Cloudfront*: Works, but needs to be configured to forward the accept header. Go to *Distribution settings*, find the *Behavior tab*, select the Behavior and click the Edit button. Choose *Whitelist* from *Forward Headers* and then add the "Accept" header to the whitelist.

I shall add more to the list. You are welcome to help out [here](https://wordpress.org/support/topic/which-cdns-works-in-standard-mode/).

### How do I make it work with CDN? ("No varied image responses" mode)
In *No varied image responses* mode, there is no trickery with varied responses, so no special attention is required *on the CDN*.

However, there are other pitfalls.

The thing is that, unless you have the whole site on a CDN, you are probably using a plugin that *alters the HTML* in order to point your static assets to the CDN. If you have enabled the "Alter HTML" in WebP Express, it means that you now have *two alterations* on the image URLs!

How will that play out?

Well, if *WebP Express* gets to alter the HTML *after* the image URLs have been altered to point to a CDN, we have trouble. WebP Express does not alter external images but the URLs are now external.

However, if *WebP Express* gets to alter the HTML *before* the other plugin, things will work fine.

So it is important that *WebP Express* gets there first.

*The good news is that WebP Express does get there first on all the plugins I have tested.*

But what can you do if it doesn't?

Firstly, you have an option in WebP Express to select between:
1. Use content filtering hooks (the_content, the_excerpt, etc)
2. The complete page (using output buffering)

The content filtering hooks gets to process the content before output buffering does. So in case output buffering isn't early enough for you, choose the content filtering hooks.

There is a risk that you CDN plugin also uses content filtering hooks. I haven't encountered any though. But if there is any out there that does, chances are that they get to process the content before WebP Express, because I have set the priority of these hooks quite high (10000). The reasoning behind this is to that we want to replace images that might be inserted using the same hook (for example, a theme might use *the_content* filter to insert the featured image). If you do encounter a plugin for changing URLs for CDN which uses the content filtering hooks, you are currently out of luck. Let me know, so I can fix that (ie. by making the priority configurable)

Here are a list of some plugins for CDN and when they process the HTML:

| Plugin            | Method             | Hook(s)                                          | Priority
| ----------------- | ------------------ | ------------------------------------------------ | ---------------
| BunnyCDN          | Output buffering   | template_redirect                                | default (10)
| CDN enabler       | Output buffering   | template_redirect                                | default (10)
| Jetpack           | content filtering  | the_content, etc                                 | the_content: 10
| W3 Total Cache    | Output buffering   | no hooks. Buffering is started before hooks      |
| WP Fastest Cache  | Output buffering   | no hooks. Buffering is started before hooks      |
| WP Super Cache    | Output buffering   | init                                             | default (10)


With output buffering the plugin that starts the output buffering first gets to process the output last. So WebP Express starts as late as possible, which is on the `template_redirect` hook, with priority 10000 (higher means later). This is later than the `init` hook, which is again later than the `no hooks`.


### I am on Cloudflare
Without configuration, Cloudflare will not maintain separate caches for jpegs and webp; all browsers will get jpeg. To make Cloudflare cache not only by URL, but also by header, you need to use the [Custom Cache Key](https://support.cloudflare.com/hc/en-us/articles/115004290387) page rule, and add *Header content*  to make separate caches depending on the *Accept* request header.

However, the *Custom Cache Key* rule currently requires an *Enterprise* account. And if you already have that, you may as well go with the *Polish* feature, which starts at the “Pro” level plan. With the *Polish* feature, you will not need WebP Express.

To make *WebP Express* work on a free Cloudflare account, you have the following choices:

1. You can configure the CDN not to cache jpeg images by adding the following page rule: If rule matches: `example.com/*.jpg`, set: *Cache level* to: *Bypass*

2. You can set up another CDN (on another provider), which you just use for handling the images. You need to configure that CDN to forward the *Accept header*. You also need to install a Wordpress plugin that points images to that CDN.

3. You can switch operation mode to "Just convert" and use either *Cache Enabler* or *ShortPixel* to modify the HTML. See the following FAQ items

### WebP Express / ShortPixel setup
Here is a recipe for using WebP Express together with ShortPixel, such that WebP Express generates the webp's, and ShortPixel only is used to create `<picture>` tags, when it detects a webp image in the same folder as an original.

The reason for doing this could be:
1. You are using a CDN which cannot be configured to work in the "Varied image responses" mode.
2. You could tweak your CDN to work in the "Varied image responses" mode, but you would have to do it by using the entire Accept header as key. Doing that would increase the risk of cache MISS, and you therefore decided that do not want to do that.
3. You think it is problematic that when a user saves an image, it has the jpg extension, even though it is a webp image.

You need:
1 x WebP Express
1 x ShortPixel

*1. Setup WebP Express*
If you do not want to use serve varied images:
- Open WebP Express options
- Switch to *No varied image responses* mode.
- Set *File extension* to "Set to .webp"
- Make sure the *Auto convert* option is enabled

If you want to *ShortPixel* to create <picture> tags but still want the magic to work on other images (such as images are referenced from CSS or javascript):
- Open WebP Express options
- Switch to *Varied image responses* mode.
- Set *Destination folder* to "Mingled"
- Set *File extension* to "Set to .webp"

*2. Setup ShortPixel*
- Install [ShortPixel](https://wordpress.org/plugins/shortpixel-image-optimiser/) the usual way
- Get an API key and enter it on the options page.
- In *Advanced*, enable the following options:
    - *Also create WebP versions of the images, for free.*
    - *Deliver the WebP versions of the images in the front-end*
    - *Altering the page code, using the <PICTURE> tag syntax*
- As there is a limit to how many images you can convert freely with *ShortPixel*, you should disable the following options (also on the *Advanced* screen):
    - *Automatically optimize images added by users in front end.*
    - *Automatically optimize Media Library items after they are uploaded (recommended).*

*3. Visit a page*
As there are presumably no webps generated yet, ShortPixel will not generate `<picture>` tags on the first visit. However, the images that are referenced causes the WebP Express *Auto convert* feature to kick in and generate webp images for each image on that page.

*4. Visit the page again*
As *WebP Express* have generated webps in the same folder as the originals, *ShortPixel* detects these, and you should see `<picture>` tags which references the webp's.

*ShortPixel or Cache Enabler ?*
Cache Enabler has the advantage over ShortPixel that the HTML structure remains the same. With ShortPixel, image tags are wrapped in a `<picture>` tag structure, and by doing that, there is a risk of breaking styles.

Further, Cache Enabler *caches* the HTML. This is good for performance. However, this also locks you to using that plugin for caching. With ShortPixel, you can keep using your favourite caching plugin.

Cache Enabler will not work if you are caching HTML on a CDN, because the HTML varies depending on the *Accept* header and it doesn't signal this with a Vary:Accept header. You could however add that manually. ShortPixel does not have that issue, as the HTML is the same for all.

### WebP Express / Cache Enabler setup
The WebP Express / Cache Enabler setup is quite potent and very CDN-friendly. *Cache Enabler* is used for generating and caching two versions of the HTML (one for webp-enabled browsers and one for webp-disabled browsers)

The reason for doing this could be:
1. You are using a CDN which cannot be configured to work in the "Varied image responses" mode.
2. You could tweak your CDN to work in the "Varied image responses" mode, but you would have to do it by using the entire Accept header as key. Doing that would increase the risk of cache MISS, and you therefore decided that do not want to do that.
3. You think it is problematic that when a user saves an image, it has the jpg extension, even though it is a webp image.

You need:
1 x WebP Express
1 x Cache Enabler

*1. Setup WebP Express*
If you do not want to use serve varied images:
- Open WebP Express options
- Switch to *No varied image responses* mode.
- Set *File extension* to "Set to .webp"
- Make sure the *Auto convert* option is enabled

If you want to *Cache Enabler* to create <picture> tags but still want the magic to work on other images (such as images are referenced from CSS or javascript):
- Open WebP Express options
- Switch to *Varied image responses* mode.
- Set *Destination folder* to "Mingled"
- Set *File extension* to "Set to .webp"

*2. Setup Cache Enabler*
- Open the options
- Enable of the *Create an additional cached version for WebP image support* option

*3. Let rise in a warm place until doubled*
*WebP Express* creates *webp* images on need basis. It needs page visits in order to do the conversions . Bulk conversion is on the roadmap, but until then, you need to visit all pages of relevance. You can either do it manually, let your visitors do it (that is: wait a bit), or, if you are on linux, you can use `wget` to grab your website:

```
wget -e robots=off -r -np -w 2 http://www.example.com
```

**flags:**
`-e robots=off` makes wget ignore rules in robots.txt
`-np` (no-parent) makes wget stay within the boundaries (doesn't go into parent folders)
`w 2` Waits two seconds between each request, in order not to stress the server

*4. Clear the Cache Enabler cache.*
Click the "Clear Cache" button in the top right corner in order to clear the Cache Enabler cache.

*5. Inspect the HTML*
When visiting a page with images on, different HTML will be served to browsers, depending on whether they support webp or not.

In a webp-enabled browser, the HTML may look like this: `<img src="image.webp">`, while in a non-webp enabled browser, it looks like this: `<img src="image.jpg">`


*6. Optionally add Cache Enabler rewrite rules in your .htaccess*
*Cache Enabler* provides some rewrite rules that redirects to the cached file directly in the `.htaccess`, bypassing PHP entirely. Their plugin doesn't do that for you, so you will have to do it manually in order to get the best performance. The rules are in the "Advanced configuration" section on [this page](https://www.keycdn.com/support/wordpress-cache-enabler-plugin).

*ShortPixel or Cache Enabler ?*
Cache Enabler has the advantage over ShortPixel that the HTML structure remains the same. With ShortPixel, image tags are wrapped in a `<picture>` tag structure, and by doing that, there is a risk of breaking styles.

Further, Cache Enabler *caches* the HTML. This is good for performance. However, this also locks you to using that plugin for caching. With ShortPixel, you can keep using your favourite caching plugin.

Cache Enabler will not work if you are caching HTML on a CDN, because the HTML varies depending on the *Accept* header and it doesn't signal this with a Vary:Accept header. You could however add that manually. ShortPixel does not have that issue, as the HTML is the same for all.

### Does it work with lazy loaded images?
No plugins/frameworks has yet been discovered, which does not work with *WebP Express*.

The most common way of lazy-loading is by setting a *data-src* attribute on the image and let javascript use that value for setten the *src* attribute. That method works, as the image request, seen from the server side, is indistinguishable from any other image request. It could however be that some obscure lazy load implementation would load the image with an XHR request. In that case, the *Accept* header will not contain 'image/webp', but '*/*', and a jpeg will be served, even though the browser supports webp.

The following lazy load plugins/frameworks has been tested and works with *WebP Express*:
- [BJ Lazy Load](https://da.wordpress.org/plugins/bj-lazy-load/)
- [Owl Carousel 2](https://owlcarousel2.github.io/OwlCarousel2/)

### When is feature X coming? / Roadmap
No schedule. I move forward as time allows. I currently spend a lot of time answering questions in the support forum. If someone would be nice and help out answering questions here, it would allow me to spend that time developing. Also, donations would allow me to turn down some of the more boring requests from my customers, and speed things up here.

Here are my current plans ahead: The 0.11 release will add ability to alter HTML (both picture tag syntax, as ShortPixel does and replace image URLs as Cache Enabler does). The 0.12 release will allow webp for all browsers! - using [this wonderful javascript library](https://webpjs.appspot.com/). The 0.13 release will probably add a some diagnose tool – this should release some time spend in the forum. 0.14 could be focused on PNG. 0.15 might be displaying rules for NGINX. 0.16 might be supporting Save-Data header (send extra compressed images to clients who wants to use as little bandwidth as possible). 0.17 might be multisite support. 0.18 might be a file manager-like interface for inspecting generated webp files. 0.19 might be WAMP support. The current milestones, their subtasks and their progress can be viewed here: https://github.com/rosell-dk/webp-express/milestones

If you wish to affect priorities, it is certainly possible. You can try to argue your case in the forum or you can simply let the money do the talking. By donating as little as a cup of coffee on [ko-fi.com/rosell](https://ko-fi.com/rosell), you can leave a wish. I shall take these wishes into account when prioritizing between new features.


## Changes in 0.9.0
- Optionally make .htaccess redirect directly to existing webp (improves performance)
- Optionally do not send filename from .htaccess to the PHP in Querystring, but use other means (improves security and reduces risks of problems due to firewall rules)
- Fixed some bugs

For more info, see the closed issues on the 0.9.0 milestone on the github repository: https://github.com/rosell-dk/webp-express/issues?q=is%3Aclosed+milestone%3A0.9.0

## Supporting WebP Express
Bread on the table don't come for free, even though this plugin does, and always will. I enjoy developing this, and supporting you guys, but I kind of need the bread too. Please make it possible for me to continue putting effort into this plugin:

- [Buy me a Coffee](https://ko-fi.com/rosell)
- [Become a backer](https://github.com/rosell-dk/webp-express/blob/master/BACKERS.md)
