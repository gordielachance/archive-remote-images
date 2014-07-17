=== Archive Remote Images ===
Contributors: Kasonzhao, grosbouff
Donate link: 
Tags: Archive Remote Images, image archive, Cache Images, auto save images.
Requires at least: 3.0
Tested up to: 3.9.1
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Archive Remote Images scans a post for remote images and allows you to fetch them; then it updates the post content.

== Description ==

Archive Remote Images scans a post for remote images and allows you to fetch them; then it updates the post content.

A new metabox will appear when editing a post.  
If "Archive Remote Images" is checked, the plugin will grab all remote images and update the content when saving that post.

* If the images is wrapped inside a link poiting to that same image, the link will be updated too.
* Settings page.
* Several hooks allow advanced users to change the plugin's behaviour.

= Contributors =
[Contributors are listed here](https://github.com/gordielachance/archive-remote-images/contributors)

= Notes =

For feature request and bug reports, [please use the forums](https://wordpress.org/plugins/archive-remote-images#postform).

If you are a plugin developer, [we would like to hear from you](https://github.com/gordielachance/archive-remote-images). Any contribution would be very welcome.

== Installation ==

1. Upload the plugin to your blog and Activate it.
2. Setup the plugin in the Media menu -> Archive Remote Images.
3. Edit a post with remote images, and check "Archive Remote Images".

== Frequently asked questions ==

Q: Will it archive the images which are already hosted on my blog ?
A: No, only the external images.

Q: Is there hooks I can use to customize the plugin's behaviour ?
A: Yes, there is several hooks you can use.  Search in the code for "apply_filters" and "do_action".  This is for advanced users !


== Screenshots ==

1. /screenshot-1.jpg

2. /screenshot-2.jpg

== Changelog ==

=  1.0.4 =
* Localization.
* If the remote image is wrapped into a link pointing to the same remote file, replace that link.  Can be filtered with hook 'ari_get_new_link_html'.
* Cleaned settings page (uses now Wordpress functions); and moved under the >Media section.
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