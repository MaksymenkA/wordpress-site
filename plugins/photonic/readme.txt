=== Photonic Gallery & Lightbox for Flickr, SmugMug, Google Photos & Others ===
Contributors: sayontan
Donate link: https://aquoid.com/plugins/photonic/
Tags: flickr, google photos, smugmug, zenfolio, instagram, gallery, lightbox, responsive, gestures, colorbox, fancybox, lightcase, lightgallery, magnific, photoswipe, prettyphoto, swipebox, strip, slideshow, deeplinking, social
Text Domain: photonic
Requires at least: 4.9
Tested up to: 5.4.1
Requires PHP: 5.6
Stable tag: trunk
License: GPLv3 or later

Galleries on steroids! A stylish lightbox & gallery plugin for WP, Flickr, SmugMug, Google Photos, Zenfolio & Instagram photos and videos.

== Description ==

Photonic takes the WordPress gallery and super-charges it with a lot of added functionality. It adds support for several new sources and parameters to enhance the content and look-and-feel of your galleries. It supports <a href='https://flickr.com'>Flickr</a> photos, Albums (Photosets), Galleries and Collections, along with <a href='https://photos.google.com/'>Google Photos</a> photos and albums, <a href='https://smugmug.com'>SmugMug</a> folders, albums and images, <a href='https://zenfolio.com'>Zenfolio</a> photos, Photosets and Groups, and <a href='https://instagram.com'>Instagram</a> photos. You can also set up authentication so that visitors can see private and protected photos from each provider.

When used without the Gutenberg editor Photonic by default overrides the <code>gallery</code> shortcode. In case you happen to be using a theme or plugin that already overrides the <code>gallery</code> shortcode, Photonic provides you with the option to use your own shortcode for Photonic galleries. This lets your plugins coexist. Bear in mind that if you deactivate Photonic you will have to remove all instances of this custom shortcode, something that is not required if you stick to the <code>gallery</code> shortcode.

When used with Gutenberg Photonic creates no shortcodes, rather it creates blocks. If some of your posts were written with Gutenberg and some without, Photonic supports both scenarios.

= Lightboxes =

Photonic has built-in support for commonly used lightbox scripts such as:

*	<a href='http://www.jacklmoore.com/colorbox/'>Colorbox</a>
*	<a href='http://fancybox.net/'>Fancybox</a>
*	<a href='http://fancyapps.com/fancybox/'>Fancybox2</a> - not GPL, so the script is not included with the plugin. See the <a href='https://aquoid.com/plugins/photonic/third-party-lightboxes/'>Lightboxes</a> page for details
*	<a href='https://fancyapps.com/fancybox/3/'>Fancybox3</a>
*	<a href='https://noelboss.github.io/featherlight/'>Featherlight</a>
*	<a href='https://osvaldas.info/image-lightbox-responsive-touch-friendly'>Image Lightbox</a>
*	<a href='http://cornel.bopp-art.com/lightcase/'>Lightcase</a>
*	<a href='https://sachinchoolur.github.io/lightGallery/'>Lightgallery</a>
*	<a href='http://dimsemenov.com/plugins/magnific-popup/'>Magnific Popup</a>
*	<a href='http://photoswipe.com/'>PhotoSwipe</a>
*	<a href='http://www.no-margin-for-errors.com/projects/prettyphoto-jquery-lightbox-clone/'>PrettyPhoto</a>
*	<a href='http://brutaldesign.github.io/swipebox/'>Swipebox</a>
*	<a href='http://www.stripjs.com'>Strip</a> - not GPL, so the script is not included with the plugin. See the <a href='https://aquoid.com/plugins/photonic/third-party-lightboxes/'>Lightboxes</a> page for details
*	Thickbox

Non-GPL alternatives like <a href='http://fancyapps.com/fancybox/'>Fancybox2</a> and <a href='http://www.stripjs.com'>Strip</a> are supported, provided you install the scripts yourself. With the exception of Thickbox the lightboxes have been adapted to become touch and gesture-friendly. See the <a href='https://aquoid.com/plugins/photonic/third-party-lightboxes/'>Lightboxes</a> page for details.

= Flickr Support =

The following Flickr concepts are supported in Photonic:

*	<a href='https://aquoid.com/plugins/photonic/flickr/flickr-photos/'>Photos</a>
*	<a href='https://aquoid.com/plugins/photonic/flickr/flickr-photosets/'>PhotoSets (Albums)</a>
*	<a href='https://aquoid.com/plugins/photonic/flickr/flickr-galleries/'>Galleries</a>
*	<a href='https://aquoid.com/plugins/photonic/flickr/flickr-collections/'>Collections</a>
*	<a href='https://aquoid.com/plugins/photonic/flickr/flickr-photo/'>Single Photo</a>
*	<a href='https://aquoid.com/plugins/photonic/flickr/flickr-authentication/'>Authentication</a>

For demos of Flickr support visit the <a href='https://aquoid.com/plugins/photonic/flickr/'>Flickr page</a>.

= Google Photos Support =

The following Google Photos concepts are supported in Photonic:

*	<a href='https://aquoid.com/plugins/photonic/google-photos/photos/'>Photos</a>
*	<a href='https://aquoid.com/plugins/photonic/google-photos/albums/'>Albums</a>

For demos of Google Photos support visit the <a href='https://aquoid.com/plugins/photonic/google-photos/'>Google Photos page</a>. Note that the Google has released the API in a <a href='https://developers.google.com/photos/library/guides/api-limits-quotas'>"Developer preview" mode</a>, so there are some restrictions around usage.

= SmugMug Support =

The following SmugMug concepts are supported in Photonic:

*	<a href='https://aquoid.com/plugins/photonic/smugmug/smugmug-tree/'>User Tree</a>
*	<a href='https://aquoid.com/plugins/photonic/smugmug/smugmug-photos/'>Photos</a>
*	<a href='https://aquoid.com/plugins/photonic/smugmug/smugmug-albums/'>Albums</a>
*	<a href='https://aquoid.com/plugins/photonic/smugmug/folders/'>Folders</a>

For demos of SmugMug support visit the <a href='https://aquoid.com/plugins/photonic/smugmug/'>SmugMug page</a>.

= Zenfolio Support =

The following Zenfolio concepts are supported in Photonic:

*	<a href='https://aquoid.com/plugins/photonic/zenfolio/photos/'>Photos</a>
*	<a href='https://aquoid.com/plugins/photonic/zenfolio/photosets/'>PhotoSets (Galleries and Collections)</a>
*	<a href='https://aquoid.com/plugins/photonic/zenfolio/groups/'>Groups</a>
*	<a href='https://aquoid.com/plugins/photonic/zenfolio/group-hierarchy/'>Group Hierarchies</a>

For demos of Zenfolio support visit the <a href='https://aquoid.com/plugins/photonic/zenfolio/'>Zenfolio page</a>. Note that authenticated content in Zenfolio is not currently supported.

= Instagram Support =

Instagram photos <a href='https://aquoid.com/plugins/photonic/instagram/'>are supported in Photonic</a>. You can display your own feed, and photos based on tags, and location-based searches. Note that while code has been written to support tags and locations, Instagram hasn't approved Photonic's access to others' photos. This means that currently only displaying one's own feed will work.

= Enhanced Native Galleries =

Your existing gallery insertions are left intact. However you can add a <code>style</code> parameter to a native gallery to open it up to Photonic. The <code>style</code> parameter can take any of the values documented on the <a href='https://aquoid.com/plugins/photonic/layouts/'>Layouts</a> page.

= Video Support =

Photonic provides gallery and lightbox support for <a href='https://aquoid.com/plugins/photonic/videos/'>videos as well</a>. Videos of the following sorts are supported:

*	External videos from YouTube or Vimeo can be opened in any of the lightboxes apart from Image Lightbox or Thickbox
*	Self-hosted or external videos in MP4 formats can be opened in any of the lightboxes apart from Image Lightbox, PrettyPhoto, Strip or Thickbox
*	Videos hosted by external service providers (Flickr, Google etc.) can be opened as a part of a gallery in any of the lightboxes apart from Image Lightbox, PrettyPhoto, Strip or Thickbox. Some lightboxes have issues with specific features. Please refer to the <a href=''>Lightboxes</a> documentation for more.

= Deep-Linking and Social Sharing =

Photonic provides deep-linking support for non-WP images, and by extension, supports social sharing to Facebook, Twitter, Google+ and Pinterest.

= Beautiful Layouts =

Photonic displays your galleries either as a grid of square thumbnails (the default), or circular thumbnails (like Jetpack), or random tiles in a justified grid, or a masonry layout, or a random mosaic (a much improved variant of the Jetpack Tiled Gallery layout), or a slideshow. See the <a href='https://aquoid.com/plugins/photonic/layouts/'>Layouts</a> page for details.

= Interactive Editing =

The WordPress editor shows up with a button that says "Add / Edit Photonic Gallery". Clicking on it will show you the different types of galleries you can work with.

= Gutenberg Support =

With effect from version 2.10 Photonic is Gutenberg-capable. There are some steps you might need to take to migrate your existing galleries. Please refer to the <a href='https://aquoid.com/plugins/photonic/gutenberg-support/'>documentation</a>.

== Installation ==

You can install the plugin through the WordPress installer under <strong>Plugins &rarr; Add New</strong> by searching for "Photonic", or by uploading the file downloaded from here.

Alternatively you can download the file from here, unzip it and move the unzipped contents to the <code>wp-content/plugins</code> folder of your WordPress installation. You will then be able to activate the plugin.

Once you have activated the plugin, refer to <em>Photonic &rarr; Getting Started</em> for a list of capabilities and documentation.

== Screenshots ==

For the plugin in action see the <a href='https://aquoid.com/plugins/photonic/'>plugin page</a>.

1.	If you are using Gutenberg look for the "Photonic" block
2.	If you are not using Gutenberg insert the shortcode for Photonic through the Media Uploader by clicking "Add Media", or click on "Add / Edit Photonic Gallery".
3.	Clicking on the Gutenberg block, or on "Add / Edit Photonic Gallery" shows up an interactive flow for you to build out your gallery.
4.	The interactive flow shows you the options available and helps construct the gallery easily.
5.	If you click on "Add Media" you will see a new tab for "Photonic". This is not available for Gutenberg.
6.	Clicking on the "Photonic" tab will show you new tabs, one for each type of gallery. Fill out what you need and click "Insert into post".
7.	The gallery placeholder shows up in the "Visual Editor" or in the Gutenberg editor. Each provider's placeholder is designated by its logo.
8.	Clicking on the placeholder lets you edit the attributes of the shortcode if Gutenberg is not being used and the interactive workflow is disabled.
9.	An example of the "Random Justified Gallery" layout.

== Frequently Asked Questions ==

= If I disable the plugin what happens to the galleries? =

Obviously, your galleries will not show. If you are using Gutenberg you don't have to worry about anything. If you are not using Gutenberg and you are using the native <code>gallery</code> shortcode, you will not see any empty shortcode tags on your site. But if you are not using Gutenberg and you are using a custom shortcode that shortcode tag will now show up.

= When I click on a gallery in the Visual Editor nothing happens. Is the plugin working? =

Yes, the plugin is working. Unfortunately the integration of Photonic with the visual editor is complex, and there is a likelihood of conflicts with other TinyMCE-specific plugins. If you come across such a conflict, please report it on the support forum. In the meanwhile you can disable the visual editing capability of Photonic (<em>Photonic &rarr; Settings &rarr; Generic Options &rarr; Generic Settings &rarr; Disable shortcode editing in Visual Editor</em>) and you should still be able to edit the gallery shortcode directly through the text editor.

= My gallery layout seems to get messed up with random text showing up at various places. Are you sure the plugin is working? =

Yes, the plugin is working. The issue you are facing is that you have another plugin (typically some sort of a lightbox plugin) that is modifying the markup generated by Photonic.

Of course, it would be easiest if you were to disable that plugin. If a lightbox is all you need, Photonic's lightbox can be used to display regular photos as well, from your admin dashboard under <em>Photonic &rarr; Settings &rarr; Generic Options &rarr; Generic Settings &rarr; Photonic Lightbox for non-Photonic Images</em>. This will ensure consistency across Photonic and non-Photonic images.

However, if you really wanted to keep that plugin, Photonic offers a way out there too! For each provider (e.g. Flickr, Google Photos etc.) go to the Settings page, e.g. <em>Photonic &rarr; Settings &rarr; SmugMug &rarr; SmugMug Settings &rarr; Disable lightbox linking</em> and set that option.

= My Instagram gallery is not working. Are you really sure the plugin is working? =

It's complicated. Photonic's code works for Instagram's new Graph API, as can be seen from the demo URL. Photonic's Application Key was approved by Facebook for usage. However, Photonic's developer needs to be verified as an individual by Facebook, and that step is pending at the time of the release of version 2.32, due to Facebook redirecting some resources to work on COVID-19 support. The latest status may be found <a href='https://wordpress.org/support/topic/potential-disruption-for-instagram-galleries/'>here</a>.

= Your link above says that all steps for Facebook's approval are complete, but my Instagram gallery is not working. Surely the plugin is not working? =

Yes, the plugin is working. You simply need to re-authenticate with new API, from <em>Photonic &rarr; Authentication</em>

= Why is the Google Photos setup process so painful? =

Blame Big G here! Google Photos' API has several shortcomings, primary among them being the high number of API calls required to fetch a gallery. This would cause API keys to routine hit their limits if they were being used for too many galleries. This makes it impossible for developers to authenticate users using their API keys without signing up for the Google Partners program. Unfortunately Photonic's design is not one of the use cases supported by the Partners program.

As a net result, to prevent users from getting locked out using Photonic's API key, it is an unfortunate requirement that users use their own key. And this is where Google makes things needlessly complicated. Photonic's documentation is very comprehensive with instructions on how to authenticate, but that doesn't change the fact that Google's process is convoluted.

= After all the pain I got Google Photos working yesterday, but today my galleries don't show up. Why? =

Check if you have a caching plugin active. If so, exclude the page with Google Photos from the cache. Google Photos' URLs are short-lived, hence cannot be cached. This problem does not occur for other sources.

= What about other photo-sharing platforms? =

Suggestions are welcome for other photo-sharing platforms.

= What about other JS libraries? =

Currently Colorbox, Fancybox, Fancybox3, Featherlight, Image Lightbox, Lightcase, Lightgallery, PrettyPhoto and Swipebox are supported. Inbuilt support is also present for the non-GPL Fancybox2 and Strip. Both lighter and heavier alternatives are being considered. If you have specific suggestions please feel free to contact the plugin author.

Note that there have been slight modifications to some of the above scripts to make them interact seamlessly with JQuery tooltips.

= Are there any known issues? =

As explained in the "Description", the Instagram API has been coded to support hashtags and locations, but Instagram itself has not provided Photonic access to those features.

Also, the TinyMCE integration for the plugin is complex, predominantly since Photonic doesn't rely on a separate shortcode. This can cause potential conflicts with other plugins. If such a situation arises, please report it in the <a href='https://wordpress.org/support/plugin/photonic/'>Support Forum</a>, and disable the visual editor capability for the shortcode by specific post-types (<em>Photonic &rarr; Settings &rarr; Generic Options &rarr; Generic Settings &rarr; Disable Visual Editing for specific post types</em>). If that doesn't work, you can go thermonuclear on Photonic's visual editing (<em>Photonic &rarr; Settings &rarr; Generic Options &rarr; Generic Settings &rarr; Disable shortcode editing in Visual Editor</em>) - you will still be able to edit the shortcode using the Text Editor in WordPress.

The "Mosaic" layout may sometimes show 1px wide gaps betweeen images if you set the padding between images to 0. This happens due to rounding errors in the height and width calculations. To avoid this, it is recommended that you use a padding &gt; 0 between images for this layout.

Apart from these, while the plugin can handle pretty much whatever you throw at it, Lubarsky's Law of Cybernetic Entomology states:
<blockquote>There is always one more bug.</blockquote>

Bug reports are welcome, and handled enthusiastically.

= Are translations supported? =

Yes, but only for the plugin front-end and the interactive workflow. The admin panel is not translated at this point. Also note that any strings included in the third-party JS scripts are not translated.

== Upgrade Notice ==

= 2.45 =

Version 2.40 is a major update where the PHP code has been modernised to use namespaces. <strong>Only update if you are on PHP 5.6 or higher, or the plugin will break.</strong>

= 2.33 =

Version 2.32 eliminates support for versions of PHP older than 5.6. <strong>Only update if you are on PHP 5.6 or higher, or the plugin will break.</strong>

This version also switches the Instagram module from the legacy API to the new API, <strong>because the legacy API is not going to be supported from 30th March 2020.</strong> At the time of releasing this version Photonic has been partially approved for Instagram use by Facebook. Facebook has paused approvals in the wake of COVID-19. See <a href='https://wordpress.org/support/topic/potential-disruption-for-instagram-galleries/'>here for the latest status</a>.

= 2.10 =

Version 2.10 introduces Gutenberg support. Please read <a href='https://aquoid.com/plugins/photonic/gutenberg-support/'>the documentation</a> before you embark on using it. If you encounter any issues with Gutenberg support, please log a ticket on the <a href='https://wordpress.org/support/plugin/photonic/'>support forum</a>.

= 2.00 =

Version 2.00 is a major release, providing users with the ability to use an interactive workflow to insert / edit galleries, moving away from the traditional shortcode editor. If you encounter any issues with the interactive workflow, please log a ticket on the <a href='https://wordpress.org/support/plugin/photonic/'>support forum</a>.

= 1.57 =

Version 1.57 was a major release for the SmugMug API. If you were on older versions of the plugin and using SmugMug, please read <a href='https://aquoid.com/2016/12/smugmug-access-in-photonic-1-57/'>this article</a>.

== Changelog ==

= 2.45 =

*	Fixed an issue where titles of SmugMug folders, Flickr collections and Zenfolio groups were not showing up on pages in some cases

