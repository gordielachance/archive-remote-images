=== Archive Remote Images ===
Contributors: Kasonzhao, grosbouff
Donate link: 
Tags: Archive Remote Images, image archive, Cache Images, auto save images.
Requires at least: 3.0
Tested up to: 3.9.1
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Advanced remote images grabber, automatically save the remote img to local, saves them directly into your blog media directory and attaches to the appointed post.

== Description ==

Advanced remote images grabber, automatically save the remote picture to the local, saves them directly into your blog media directory, and attaches to the appointed post.
Totaly auto save image in your post/page content. 
Just simple setting, it will help you transfer the image from remote website into your local, you can choose for single preference, if some post need not transfer the images. inspired from Cache Images.

Following links can help you:  
   <ul style="margin-left:40px;"> 
   <li><a href="http://www.lookingimage.com/wordpress-plugin/wordpress-archive-remote-images/" target="_blank">Details and video tutorial (FAQ .etc)</a></li>
   <li><a href="http://www.lookingimage.com/forums/discussion/" target="_blank">Support forum</a></li>
   <li><a href="http://lookingimage.com/" target="_blank">Author home page</a></li>
   <li><a href="http://www.lookingimage.com/wordpress-themes/" target="_blank">Free WordPress themes</a></li>
   <li><a href="http://www.lookingimage.com/wordpress-plugin/" target="_blank">Other plugins from lookingimage.com</a></li>
   </ul>



== Installation ==

1. Upload folder 'archive-remote-images' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. After activate it, you can set the in the setting menu -> Archive Remote Images

== Frequently asked questions ==

Q: Can I set whether archive needed for single post?
A: Yes, you can, use the option box in new/edit post page, check the screenshot.
Q: Will it archive again the images which already host from my website?
A: No, only the external images.


== Screenshots ==

1. /screenshot-1.jpg

2. /screenshot-2.jpg

== Changelog ==

=  1.0.4 =
*   Cleaned settings page (uses now Wordpress functions); and moved under the >Media section.
*   Saves image title if a "title" or "alt" attribute is set on the remote image
*   Fixed bug with revisions
*   Avoid uploading several times the same image (checking its source, which is saved as "_ari-url" post meta)
*   Whole image tag replacement, not only source url replacement.  Can be filtered with hook 'ari_get_attachment_html'.
*   Changed regex stuff with DOM parser (more reliable)
*   Replaced SQL queries with WP core functions
*   Wrapped plugin into a class

=  1.0.3 =
*   Update: The read me content
*   Bug fix : Fix the default value and auto save option