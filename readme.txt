=== Archive Remote Images ===
Contributors: Kasonzhao, grosbouff, kraoc
Donate link: 
Tags: Archive Remote Images, image archive, grab images, cache Images, auto-save images, media
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 1.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Archive Remote Images allows you to scan a post to fetch remote images; then updates its content automatically.

== Description ==

Archive Remote Images allows you to scan a post to fetch remote images; then updates its content automatically.

A new metabox will appear when editing a post.  
If "Archive Remote Images" is checked, the plugin will grab all remote images and update the content when saving that post.

* Settings page with lot of options (see screenshot #2)
* Several hooks allow advanced users to change the plugin's behaviour.

= Contributors =
[Contributors are listed here](https://github.com/gordielachance/archive-remote-images/contributors)

= Notes =

For feature request and bug reports, [please use the forums](https://wordpress.org/plugins/archive-remote-images#postform).

If you are a plugin developer, [we would like to hear from you](https://github.com/gordielachance/archive-remote-images). Any contribution would be very welcome.

== Installation ==

1. Upload the plugin to your blog and Activate it.
2. Setup the plugin in the Settings menu -> Archive Remote Images.
3. Edit a post with remote images, and check "Archive Remote Images".

== Frequently asked questions ==

Q: Will it archive the images which are already hosted on my blog ?
A: No, only the external images.

Q: Is there hooks I can use to customize the plugin's behaviour ?
A: Yes, there is several hooks you can use.  Search in the code for "apply_filters" and "do_action".  This is for advanced users !


== Screenshots ==

1. Metabox shown in the editor
2. Settings page

== Changelog ==
= 1.0.7 (by Kraoc) =
* Deep clean html content on post load
* Add normalize on document save
= 1.0.6 (by Kraoc) =
* Add default encoding when loading DOM
* removed doctype / html / body from document (only since PHP 5.4).
= 1.0.5 =
* added function is_local_server() to avoid error in get_domain() when used on localhost.
= 1.0.4 =
* Lot of new options in the settings page.
* Localization.
* Also handles the link wrapped around an image, if the link target is pointing to the image file.
* Refactoring of the settings page (uses now Wordpress functions).
* Saves image title if a "title" or "alt" attribute is set on the remote image
* Fixed bug with revisions
* Avoid uploading several times the same image (checking its source, which is saved as "_ari-url" post meta)
* Whole image tag replacement, not only source url replacement.  Can be filtered with hook 'ari_get_new_image_html'.
* Changed regex stuff with DOM parser (more reliable)
* Replaced SQL queries with WP core functions
* Wrapped plugin into a class

=  1.0.3 =
* Update: The read me content
* Bug fix : Fix the default value and auto save option
