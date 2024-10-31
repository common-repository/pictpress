=== PictPress ===
Contributors: Curioso
Donate link: http://www.curioso.org/
Tags: images
Requires at least: 1.2
Tested up to: 2.1
Stable tag: 1.5

Insert a directory of images in a post with on the fly generation of thumbnails.

== Description ==

PictPress is a WordPress 1.2/1.5 plugin that automatically generates a post with thumbnails and picture posts for all images found in a directory. Thumbnails and pictures are resized on the fly and stored in a cache directory. For an example see my post with pictures of a Treble concert.

== Installation ==

Prerequisites:

* PictPress needs either the ImageMagick convert program or the GD2 library to resize images.
* PictPress optionally uses exif_read_data or the rdjpgcom program to read comments from the images.; exif_read_data is also optionally used to read EXIF data like picture date etc.
* You will need some other means to upload images to subdirectories of the WordPress upload directory.

1. Unzip pictpress.zip in the plugin directory of your WordPress site. This creates a pictpress directory plus a pictpress.php file.
1. Go to the Plugin admin page and press the install/upgrade link.
1. Enable the PictPress plugin on the Plugins admin page.
1. Make sure that you have configured a valid destination directory for Uploads under Options >> Miscellaneous and that the web server has write access to the upload directory.

== Usage ==

1. First upload one or more JPEG images to a subdirectory of the WordPress upload directory according to the setings for pp_image_dir (I use %year%/%monthnum%/%postname% myself).
1. Then create a new post that will use this directory (in my case by making sure that date and Post Slug are the same as used for the directory).
1. When pressing Publish, thumbnails for all images will be appended to the post (with a --more-- marker after the first five thumbnails) and for each image a picture post will be generated. The thumbnails have links to the picture posts and the pictures have links to the full resolution images.
1. Titles for the picture posts are either set to the JPEG comment or to the image filename.
1. Captions under the thumbnails are set to the corresponding picture post titles and follow updates being made to the titles.
1. When updating a post, any new images in the image directory are merged with the existing post and the thumbnails are replaced by the new set. This is the way to include new pictures after these have been added to the image directory. Edits to posts between the PictPress comment tags are lost; other edits are kept.
1. Picture posts are suppressed from the post overview pages and in principle should only appear on pages of their own.

== Configuration ==

There are a number of options that can be confgured via the PictPress options form that can be reached from the WordPress options pages.

* Location of image directory
* Location of cache directory for resized images
* URL used for resized images
* Size of thumbnails and images on picture pages
  This is either a single number, which specifies the maximumn of width and height, or a number of the form WxH, where W and H specify an exact width and height. In the latter case the image is cropped in the dimension that is too large.
* Maximum number of thumbnails per page
* Number of thumbnails before more
* Image resize method, ImageMagick or GD2.
* Sorting order for thumbnails.
* Strings for use in title, alt attribute, captions.
* Protect images against referrers from other web sites yes/no.
* Automatically insert CSS header yes/no
* Generate a single post for all pictures yes/no; this switches back to the behaviour of the previous version, see the PictPress 0.91 description.

The strings used for title etc. can contain the following variables:

* %aperture%, EXIF aperture setting
* %comment%, JPEG comment
* %date%, EXIF DateTimeOriginal
* %digitized%, EXIF DateTimeDigitized
* %edited%, edited date
* %exif%, table with all info returned by exif_read_data
* %exposure%, EXIF ExposureTime
* %focallength%, EXIF FocalLength
* %height%, height of image in pixels
* %iso%, EXIF ISOSpeedRatings
* %make%, camera manufactureer
* %model%, camera model
* %modified%, file modified date
* %size%, file size in bytes
* %sizekb%, file size in kilobytes
* %sizemb%, file size in megabytes
* %title%, title of picture post
* %width%, width of image in pixels

== Template Functions ==

The following template functions can be used in index.php/sidebar.php:

* pp_prev_thumb()
  Generate thumbnail for previous picture post if applicable.
* pp_next_thumb()
  Generate thumbnail for next picture post if applcable.
* pp_navigation()
  Generate navigation section with thumbnail for previous picture post and thumbnail for next picture post, if applicable.
* pp_the_parent_title()
  Print the title of the parent post if applicable. Meant to be used before the_title() in the post loop.
* pp_modified_date ($format='', $before='', $after='', $echo = true)
  Print modified time/date of current post.
* pp_edit_subtitles_link($link = 'Edit Subtitles', $before = '', $after = '')
  Print link to edit-subtitles page for current post.

== Content Tags ==

PictPress recognizes a number of HTML comment strings and expands them:

* <!--pp-thumb url--> expands to a thumbnail image pointing to the post with the specified url.
