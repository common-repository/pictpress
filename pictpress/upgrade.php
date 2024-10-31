<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
        <title>PictPress &rsaquo; Installation</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<body>
<h1 id="logo"><a href="http://curioso.org/2004/09/pictpress"><span>PictPress</span></a></h1>
<?php
require_once("../../../wp-config.php");
$pp_uninstall = get_settings('pp_uninstall');
if (isset($_GET['step']))
        $step = $_GET['step'];
else
        $step = 0;

switch($step) {

        case 0:
		if ($pp_uninstall) {
?>
<p>Ready to uninstall PictPress, <a href="upgrade.php?step=1">press here</a>.
</p>
<?php		} else { ?>
<p>Welcome to PictPress. 
Have you looked at the <a href="readme.html">readme</a>? 
If you&#8217;re all ready, <a href="upgrade.php?step=1">press here</a>
to install or upgrade.</p>
<p>To rebuild all picture posts after a change in the settings, first enable the pp_rebuild option and then <a href="upgrade.php?step=1">press here</a>.</p>
<p>To uninstall, first enable the pp_uninstall option and then <a href="upgrade.php?step=1">press here</a>.</p>
<?php
		}
		break;

        case 1:
?>
<h2>Step 1</h2>
<?php if ($pp_uninstall) { ?>
<p>We will delete the PictPress options from the database.
<?php } else { ?>
<p>We will add the PictPress options to the database.
<?php } ?>
</p>
<?php
if ($tableoptiongroups) {
	$pp_group_id = $wpdb->get_var("SELECT group_id FROM $tableoptiongroups
				       WHERE group_name = 'PictPress'");
	if ($pp_uninstall && $pp_group_id) {
		$wpdb->query("DELETE FROM $tableoptiongroups
			      WHERE group_name = 'PictPress'");
	} else if (!$pp_uninstall && !$pp_group_id) {
		$wpdb->query(
			"INSERT INTO $tableoptiongroups 
			 (group_name, group_desc) 
			 VALUES 
			 ('PictPress', 'Settings used by PictPress plugin.')"
		);
		$pp_group_id = $wpdb->insert_id;
	}
}
$pp_option_seq = 0;

function pp_do_option ($name, $type, $width, $value, $desc) {
	global $wpdb, $tableoptions, $tableoptiongroup_options,
               $pp_group_id, $pp_option_seq, $pp_uninstall;

	$pp_option_seq++;

	$id = $wpdb->get_var("SELECT option_id FROM $tableoptions
			      WHERE option_name = '$name'");
	if ($pp_uninstall && $id) {
		$wpdb->query("DELETE FROM $tableoptions
			      WHERE option_name = '$name'");
		if ($tableoptiongroup_options)
			$wpdb->query("DELETE FROM $tableoptiongroup_options
				      WHERE option_id = $id");
	} else if (!$pp_uninstall && !$id) {
		$wpdb->query("INSERT INTO $tableoptions 
			      (option_name, option_value, option_description,
			       option_type, option_admin_level, option_width) 
			      VALUES 
			      ('$name', '$value', '$desc', $type, 8, $width)"
			    );
		$id = $wpdb->insert_id;

		if ($tableoptiongroup_options)
			$wpdb->query("INSERT INTO $tableoptiongroup_options 
				      (group_id, option_id, seq) 
				      VALUES
				      ($pp_group_id, $id, $pp_option_seq)"
				    );
	}
}

pp_do_option ('pp_image_dir', 3, 40, '%year%/%monthnum%/%postname%', 
               'Location of image directory relative to the upload directory. The same variables can be used as in permalink specifications: %year%, %monthnum%, %day%, %postname%, %post_id%'
              );
pp_do_option ('pp_cache_dir', 3, 40, 'cache/%size%/%path%',    
               'Location of resized image cache directory relative to the upload directory.' 
              );
$siteurl = $wpdb->get_var("SELECT option_value FROM $tableoptions
                           WHERE option_name = 'siteurl'");
pp_do_option ('pp_resize_url', 3, 40, 
	       $siteurl .
               "/wp-content/plugins/pictpress/resize.php?size=%size%&path=%path%",
               'URL used for retrieving a resized image. You can use it to define a nice URL for resized images, e.g. similar to pp_cache_dir, but then you will also have to provide a RewriteRule in .htaccess to the resize.php script.'
              );
pp_do_option ('pp_single_post', 2, 10, '0',    
               'Use single post for all pictures if set. If not set, a separate post will be generated for each picture'
              );
pp_do_option ('pp_thumb_text', 3, 40, 'title',
               'Text from child post added to thumbnail, can be "content", "text".' 
              );
pp_do_option ('pp_read_comments', 2, 10, '0',    
               'Read JPEG comments using rdjpgcom and use this as title'
              );
pp_do_option ('pp_sort_key', 3, 40, 'time',
               'Key used for sorting images, can be "name", "comment", "time", "modified".' 
              );
pp_do_option ('pp_sort_order', 1, 10, '1',    
               'Order for sorting images, 1 for normal, -1 for reverse.'
              );
pp_do_option ('pp_resize_method', 3, 40, 'ImageMagick',
               'Image resize method, "GD2" or "ImageMagick".' 
              );
pp_do_option ('pp_thumb_size', 1, 10, '92',    
               'Max horizontal/vertical size for thumbnails.'
              );
pp_do_option ('pp_image_size', 1, 10, '450',    
               'Max horizontal/vertical size for images on picture pages.'
              );
pp_do_option ('pp_full_image', 2, 10, '1',    
               'Provide link to full image from picture pages.'
              );
pp_do_option ('pp_image_title', 3, 40, '%comment%',    
               'Title used for picture pages/posts.'
              );
pp_do_option ('pp_thumb_alt', 3, 40, '%comment%',    
               'Alt attribute of thumbnail image.'
              );
pp_do_option ('pp_thumb_title', 3, 40, '%date% %comment%',    
               'Title attribute of thumbnail image.'
              );
pp_do_option ('pp_thumb_caption', 3, 40, '%title%',    
               'Caption put below thumbnail.'
              );
pp_do_option ('pp_image_caption', 3, 40, '%make% %model% %exposure% %aperture% %width%x%height% pixels %sizekb% KiB',    
               'Caption put below image on picture pages/posts.'
              );
pp_do_option ('pp_image_cat', 1, 10, '0',    
               'Category to be added for picture posts, 0 is none.'
              );
pp_do_option ('pp_thumbs_per_row', 1, 10, '0',    
               'Number of thumbnails per row. Set to 0 for no limit.'
              );
pp_do_option ('pp_min_thumbs', 1, 10, '2',    
               'Min number of thumbnails per page. Below this number pictures are put in the main post instead of in separate pages or posts.'
              );
pp_do_option ('pp_max_thumbs', 1, 10, '0',    
               'Max number of thumbnails per page. Set to 0 for no limit.'
              );
pp_do_option ('pp_more_thumbs', 1, 10, '4',    
               'Number of thumnails before more. Insert more-tag after so many thumbnails. Set to 0 for no more-tag'
              );
pp_do_option ('pp_use_picture_time', 2, 10, '1',    
               'Use picture time as publishing date for picture posts.'
              );
pp_do_option ('pp_protect_images', 2, 10, '0',    
               'Protect images agains access from other web sites (bandwidth stealing)'
              );
pp_do_option ('pp_use_css', 2, 10, '1',    
               'Add header for PictPress CSS file.'
              );
pp_do_option ('pp_rebuild', 2, 10, '0',    
               'Rebuilt all picture posts in upgrade script.'
              );
pp_do_option ('pp_uninstall', 2, 10, '0',    
               'Uninstall PictPress posts in upgrade script.'
              );
pp_do_option ('pp_silent', 2, 10, '0',    
               'Remove all comments in content filter.'
              );

if (!$pp_uninstall && get_settings('pp_rebuild')) {
	echo("<p>Rebuilding all picture posts.</p>\n");
	// Update old picture posts that have no image category set
	//
	$pictposts = $wpdb->get_col("SELECT ID FROM $tableposts
				     WHERE post_parent <> 0");
	if ($pictposts) {
		echo("<p>Updating categories.</p>\n");
		$cat = $wpdb->get_var("SELECT option_value FROM $tableoptions
				       WHERE option_name = 'pp_image_cat'");
		if ($cat) {
			foreach ($pictposts as $id) {
				$wpdb->query("DELETE FROM $tablepost2cat
					      WHERE post_id = $id");
				$wpdb->query("INSERT INTO $tablepost2cat 
					      (post_id, category_id)
					      VALUES ($id, $cat)");
			}
		} else {
			foreach ($pictposts as $id) {
				$wpdb->query("DELETE FROM $tablepost2cat
					      WHERE post_id = $id");
				$p = $wpdb->get_var("SELECT post_parent 
						     FROM $tableposts
						     WHERE ID = '$id'");
				$cats = $wpdb->get_col("SELECT category_id
							FROM $tablepost2cat
							WHERE post_id = $p");
				if ($cats)
					foreach ($cats as $cat)
						$wpdb->query(
						   "INSERT INTO $tablepost2cat 
					            (post_id, category_id)
					            VALUES ($id, $cat)");
			}
		}
	}
}
echo("<p>Done!</p>\n");
if (!$pp_uninstall) {
	echo("<p>You can now ");
	echo("<a href=\"../../../wp-admin/options.php?option_group_id=$pp_group_id\">");
	echo("edit the options</a>.</p>\n");
}
break;

        case -1:
?>
<h2>Step -1</h2>
<p>Uninstall PictPress.
</p>
<?php
break;
}
?>
</body>
</html>
