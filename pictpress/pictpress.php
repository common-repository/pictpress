<?php
/*
Plugin Name: PictPress
Plugin URI: http://www.curioso.org/pictpress/
Description: Adds fotolog functionality to wordpress posts by adding thumbnails and picture pages automatically to the post for all pictures stored at a post specific location. To install or upgrade <a href="../wp-content/plugins/pictpress/upgrade.php">press here</a> and then enable the plugin. For additional information see the <a href="../wp-content/plugins/pictpress/readme.html">readme file</a>.
Version: 1.0.1
Author: Curioso
Author URI: http://www.curioso.org/
*/ 

// Location of PictPress directory relative to WordPress directory
//
$pp_pictpress_dir = "wp-content/plugins/pictpress";

require_once(ABSPATH . "$pp_pictpress_dir/wp15.php");

// Patterns for thumbnails and picture pages
//
$pp_thumbs_pat = "/<!--pp-thumb-start-->.*<!--pp-thumb-end-->/s";
$pp_pict_pat = "/<!--pp-page-start-->.*<!--pp-page-end-->/s";
$pp_thumbs_gen_pat = "/<!--PictPress generated thumbnails for dir (.*?)-->/s";
$pp_page_gen_pat = "/<!--PictPress generated page for image (.*?)-->/s";
$pp_thumb_href_pat = "/<div class=\"thumbnail\">.*?<div class=\"image\"><a.*?href=\"(.*?)\">/s";

// Div to clear floats
//
$pp_clear = "<div class=\"clear\">&nbsp;</div>";

$pp_children = $pp_flipped_children = array();

function pp_get_children ($id) {
	global $wpdb, $pp_thumbs_pat, $pp_thumb_href_pat,
	       $pp_parent_id, $pp_children, $pp_flipped_children;

	if ($pp_parent_id == $id)
		return;
	$pp_parent_id = $id;
	$pp_children = $pp_flipped_children = array();
	$content = $wpdb->get_var("SELECT post_content
				   FROM $wpdb->posts
				   WHERE ID = $id");
	if (preg_match($pp_thumbs_pat, $content, $matches)) {
		$content = $matches[0];
		if (preg_match_all($pp_thumb_href_pat, $content, $matches)) {
			$pp_children = $matches[1];
			$pp_flipped_children = array_flip($pp_children);
		}
	}
}

//////////////////////
// Template functions
//////////////////////

// Template function to print parent link and title
//
function pp_the_parent_title () {
	global $post, $wpdb;

	$p = $post;
	$title = '';
	while ($p->post_parent != 0) {
		$r = get_permalink($p->post_parent);
		$p = $wpdb->get_row("SELECT post_parent, post_title 
				     FROM $wpdb->posts 
				     WHERE ID = $p->post_parent");
		$p->post_title = stripslashes($p->post_title);
		$title = "<a href=\"$r\">$p->post_title</a> &raquo; $title";
	}
	echo $title;
}
	
// Generate thumbnail for previous page
//
function pp_prev_thumb() {
	global $wpdb, $posts, $pp_clear;
	global $pp_children, $pp_flipped_children;

	if (!is_single()) return;

	$post = $posts[0];
	if ($post->post_parent == 0) {
		$id = $wpdb->get_var("SELECT ID FROM $wpdb->posts 
				      WHERE post_date < '$post->post_date' AND 
				            post_status = 'publish' AND
					    post_parent = 0
				      ORDER BY post_date DESC LIMIT 1");
		if (!$id) return;
		$r = get_permalink($id);
	} else {
		pp_get_children($post->post_parent);
		$i = $pp_flipped_children[get_permalink($post->ID)];
		if (!$i) return;
		$r = $pp_children[--$i];
	}
	echo "<li class=\"prev\"><a href=\"$r\">&laquo;</a> ";
	echo pp_get_thumb($r);
	echo "$pp_clear</li>\n";
}

// Generate thumbnail for next page
//
function pp_next_thumb() {
	global $wpdb, $posts, $pp_clear;
	global $pp_children, $pp_flipped_children;

	if (!is_single()) return;

	$post = $posts[0];
	if ($post->post_parent == 0) {
		$id = $wpdb->get_var("SELECT ID FROM $wpdb->posts 
				      WHERE post_date > '$post->post_date' AND 
				            post_status = 'publish' AND
					    post_parent = 0
				      ORDER BY post_date ASC LIMIT 1");
		if (!$id) return;
		$r = get_permalink($id);
	} else {
		pp_get_children($post->post_parent);
		$i = $pp_flipped_children[get_permalink($post->ID)];
		if (++$i >= count($pp_children))
			return;
		$r = $pp_children[$i];
	}
	echo "<li class=\"next\"><a href=\"$r\">&raquo</a> ";
	echo pp_get_thumb($r);
	echo "$pp_clear</li>\n";
}

// Generate navigation thumbnails
//
function pp_navigation() {
	global $posts;
	if (!is_single())
		return;
	$nav = __('Navigation');
	echo "<li id=\"navigation\">$nav<ul>";
	pp_next_thumb();
	pp_prev_thumb();
	echo "</ul></li>\n";
}

// Print post last modified date/time
//
function pp_modified_date ($d='', $before='', $after='', $echo = true) {
        global $post;
        if ($d == "")
                $d = get_settings('date_format');
        $date = mysql2date($d, $post->post_date);
        $modified = mysql2date($d, $post->post_modified);
        $the_date = '';
        if ($date != $modified) {
                $the_date .= $before;
                $the_date .= mysql2date($d, $post->post_modified);
                $the_date .= $after;
        }
        $the_date = apply_filters('the_date', $the_date);
        if ($echo) {
                echo $the_date;
        } else {
                return $the_date;
        }
}

// Link to edit subtitles
//
function pp_edit_subtitles_link($link = 'Edit Subtitles', $before = '', $after = '') {
    global $user_level, $post, $pp_thumbs_gen_pat, $pp_page_gen_pat, $pp_wp15;

    if (!preg_match($pp_thumbs_gen_pat, $post->post_content) ||
        preg_match($pp_page_gen_pat, $post->post_content))
	return;

    get_currentuserinfo();

    if ($user_level > 0) {
        $authordata = get_userdata($post->post_author);
        if ($user_level < $authordata->user_level) {
            return;
        }
    } else {
        return;
    }

    if ($pp_wp15)
	$location = get_settings('siteurl') . 
	    "/wp-content/plugins/pictpress/edit-subtitles.php?post=$post->ID";
    else
	$location = get_settings('siteurl') . 
	    "/wp-content/plugins/pictpress/edit-subtitles-1.2.php?post=$post->ID";
    echo "$before <a href=\"$location\">$link</a> $after";
}

//////////////////////
// Internal functions
//////////////////////

// Get upload directory
//
function pp_get_upload_dir () {
	if ($path = get_settings('upload_path'))
		return ABSPATH . "/$path";
	if ($path = get_settings('fileupload_realpath'))
		return $path;
	return ABSPATH . '/wp-content';
}

// Get image directory
//
function pp_get_image_path($post_ID, $pict_date = 0) {
	global $wpdb;
        $post = $wpdb->get_row("SELECT post_date, post_name 
                                FROM $wpdb->posts WHERE ID = $post_ID");

        $rewritecode = array(
                '%year%',
                '%monthnum%',
                '%day%',
                '%postname%',
                '%post_id%'
        );
	$unixtime = $pict_date ? $pict_date : strtotime($post->post_date);
        $rewritereplace = array(
                date('Y', $unixtime),
                date('m', $unixtime),
                date('d', $unixtime),
                $post->post_name,
                $post_ID
        );
	$perm = str_replace($rewritecode, $rewritereplace, 
			    get_settings('pp_image_dir')); 
	return $perm;
}

// Get subpost with image
//
function pp_get_subpost($parent, $slug) {
	global $wpdb;

	$sql = "SELECT ID FROM $wpdb->posts
		WHERE post_parent = '$parent' AND post_name ='$slug'";
	return $wpdb->get_var($sql);
}

// Insert new post in database
//
function pp_insert_post($postarr = array()) {
        global $wpdb, $post_default_category;
        
        // export array as variables
        extract($postarr);
        
        // Do some escapes for safety
        $post_title = $wpdb->escape($post_title);
        $post_name = sanitize_title($post_title);
        $post_excerpt = $wpdb->escape($post_excerpt);
        $post_content = $wpdb->escape($post_content);
        $post_author = (int) $post_author;
        
        // Make sure we set a valid category
        if (0 == count($post_category) || !is_array($post_category)) {
                $post_category = array($post_default_category);
        }

        $post_cat = $post_category[0];

        if (empty($post_date))
                $post_date = current_time('mysql');
        // Make sure we have a good gmt date:
        if (empty($post_date_gmt)) 
                $post_date_gmt = get_gmt_from_date($post_date);
 	if (empty($comment_status))
		$comment_status = get_settings('default_comment_status');
	if (empty($ping_status))
		$ping_status = get_settings('default_ping_status');
	
	$sql = "INSERT INTO $wpdb->posts 
		(post_author, post_date, post_date_gmt, post_modified, post_modified_gmt, post_content, post_title, post_excerpt, post_category, post_status, post_name, comment_status, ping_status) 
		VALUES ('$post_author', '$post_date', '$post_date_gmt', '$post_date', '$post_date_gmt', '$post_content', '$post_title', '$post_excerpt', '$post_cat', '$post_status', '$post_name', '$comment_status', '$ping_status')";

        $result = $wpdb->query($sql);
        $post_ID = $wpdb->insert_id;

        wp_set_post_cats('',$post_ID,$post_category);

        // Return insert_id if we got a good result, otherwise return zero.
        return $result ? $post_ID : 0;
}

// Create picture post
//
function pp_make_picture_post ($postdata, $image) {
	global $pp_pict_pat, $wpdb;

	$name = basename($image->name);
	$slug = preg_replace('/(.+)\..*$/', '$1', $name);
	$slug = sanitize_title($slug);
	$post_ID = $postdata['ID'];
	$post_category = $postdata['post_category'];

	// Check if there is an existing post with this image
	//
	$image->id = $id = pp_get_subpost($post_ID, $slug);
	if ($id) {
		$postdata = wp_get_single_post($id, ARRAY_A);
		$content = $postdata['content'];

		// If existing picture, replace it
		// else append to end of content
		//
		if (preg_match($pp_pict_pat, $content))
			$content = preg_replace($pp_pict_pat,
					$image->GetPicture(),
					$content);
		else
			$content .= $image->GetPicture();
		$content = $wpdb->escape($content);
		if (get_settings('pp_use_picture_time')) {
			$post_date = gmdate('Y-m-d H:i:s', 
					    $image->time + 3600 *
					    get_settings('gmt_offset'));
			$post_date_gmt = gmdate('Y-m-d H:i:s', $image->time);
		} else {
			$post_date = $postdata['post_date'];
			$post_date_gmt = $postdata['post_date_gmt'];
		}
		$result = $wpdb->query("
			UPDATE $wpdb->posts SET
				post_content = '$content',
				post_date = '$post_date',
				post_date_gmt = '$post_date_gmt'
			WHERE ID = $id");
		if (!get_settings('pp_image_cat'))
		        wp_set_post_cats('', $id, $post_category);
	} else {
		// Create new post
		//
		$postdata['post_parent'] = $post_ID;
		$postdata['post_title'] = $slug;
		if (get_settings('pp_use_picture_time')) {
			$postdata['post_date'] = gmdate('Y-m-d H:i:s', 
						$image->time + 3600 *
						get_settings('gmt_offset'));
			$postdata['post_date_gmt'] = gmdate('Y-m-d H:i:s', 
							    $image->time);
		}
		$postdata['post_content'] = $image->GetPicture();
		$cat = get_settings('pp_image_cat');
		if ($cat)
			$postdata['post_category'] = array($cat);
		$image->id = $id = pp_insert_post($postdata);
		$title = $wpdb->escape($image->GetText('pp_image_title'));
		// Fill in parent, title
		//
		$result = $wpdb->query("
			UPDATE $wpdb->posts SET
				post_parent = $post_ID,
				post_title = '$title'
			WHERE ID = $id ");
	}
	return $id;
}

// Create picture page
//
function pp_make_picture_page ($pages, $image) {
	global $pp_pict_pat;

	$numpages = count($pages);
	$name = basename($image->name);

	// Check if there is an existing page with this image
	//
	$page = '';
	for ($p = 1; $p < $numpages; $p++) {
		if (preg_match("/<!--pp-page-start-->.*$name.*<!--pp-page-end-->/s", $pages[$p])) {
			$page = $pages[$p];
			break;
		}
	}
	if ($page) {
		// If existing page, replace picture
		//
		$page = preg_replace($pp_pict_pat,
				     $image->GetPicture(),
				     $page);
	} else {
		// If not, generate a picture page
		//
		$page  = "<h4 class=\"caption\">";
		$page .= $image->GetText('pp_image_title');
		$page .= "</h4>";
		$page .= $image->GetPicture();
	}
	return $page;
}

// Add pictures to post, to be called by do_action('publish_post',...)
//
function pp_add_pictures ($post_ID) {
        global $pp_pictpress_dir, $wpdb, $pp_wp15;
	global $pp_clear, $pp_pict_pat, $pp_thumbs_pat;

	$postdata = wp_get_single_post($post_ID, ARRAY_A);

	// No recursive posts
	//
	if ($postdata['post_parent'] != 0)
		return;

	include_once (ABSPATH . "$pp_pictpress_dir/class.image.php");
	include_once (ABSPATH . "$pp_pictpress_dir/class.imagedir.php");

	$path = pp_get_image_path($post_ID);
	$dirname = pp_get_upload_dir() . "/$path";

        if (file_exists($dirname) && is_dir($dirname))
		$dir = new ImageDir($path);

	// Remove previous thumbnails
	//
	$content = preg_replace($pp_thumbs_pat, "", $postdata['post_content']);

	$pages = explode('<!--nextpage-->', $content);
	$numpages = count($pages);

	// Copy all pages that contain no PictPress images
	//
	for ($p = 0; $p < $numpages; $p++) {
		if ($p == 0 || !preg_match($pp_pict_pat, $pages[$p])) {
			$newpages[] = $pages[$p];
		}
	}

	// Determine number of first page after thumbnails
	//
	if ($dir) {
		$pp_max_thumbs = get_settings('pp_max_thumbs');
		if ($dir->count < get_settings('pp_min_thumbs')) {
			$no_thumbs = 1;
		} else if ($pp_max_thumbs) {
			$first = count($newpages) + ceil($dir->count / $pp_max_thumbs);
		} else {
			$first = count($newpages) + 1;
		}
	}

	// Delete children if necessary
	//
	if ($no_thumbs || get_settings('pp_single_post'))
		pp_delete_children($post_ID);
	else
		pp_check_deleted($post_ID);

	// Create thumbnails
	//
	$thumbs = "<!--pp-thumb-start-->";
	if (!$dir) {
		$thumbs .= "<!--PictPress found no dir $dirname-->";
	} else if ($dir->count == 0) {
		$thumbs .= "<!--PictPress found no images in dir $dirname-->";
	} else {
		$thumbs .= "<!--PictPress generated thumbnails for dir $dirname-->";
		$pp_more_thumbs    = get_settings('pp_more_thumbs');
		$pp_thumbs_per_row = get_settings('pp_thumbs_per_row');
		$pp_single_post    = get_settings('pp_single_post');

		// $pp_tag indicates new way of including thumbnails in parent post
		//
		if ($pp_single_post)
			$pp_tag = '';
		else if (get_settings('pp_thumb_text') == 'content')
		        $pp_tag = '%pp-thumb-content%';
		else
			$pp_tag = '';

		$thumbs .= "$pp_clear" . $pp_tag;
		for ($i = 0; $i < $dir->count; $i++) {
			if (!$pp_tag) {
				if ($pp_more_thumbs && $i == $pp_more_thumbs) {
					$thumbs .= "<!--more-->";
				}
				if ($i) {
					if ($pp_max_thumbs) {
						$j = $i % $pp_max_thumbs;
						if ($j == 0) {
							$thumbs .= "$pp_clear<!--nextpage-->$pp_clear";
						} else if ($pp_thumbs_per_row && 
					   		($j % $pp_thumbs_per_row) == 0) {
							$thumbs .= "$pp_clear";
						}
					} else if ($pp_thumbs_per_row && ($i % $pp_thumbs_per_row) == 0) {
						$thumbs .= "$pp_clear";
					}
				}
			}
			$image = $dir->GetImage($i);
			if ($i == 0) {
				$pict_date = $image->time + 
					     3600 * get_settings('gmt_offset');
			}

			if ($no_thumbs) {
				$thumbs .= $image->GetPicture();
			} else {
				if ($pp_single_post) {
					$image->id = $first + $i;
					$newpages[] = 
						pp_make_picture_page($pages, $image);
					$href = pp_get_link($post_ID, $image->id);
				} else {
					$image->id = pp_make_picture_post($postdata, $image); 
					$href = get_permalink($image->id);
				}
				if (!$pp_tag)
					$thumbs .= $image->GetThumbnail($href) . ' ';
			}
		}
		$thumbs .= "$pp_clear";
	}
	$thumbs .= "<!--pp-thumb-end-->";

	// Append thumbnails to end of first page.
	//
	$newpages[0] .= $thumbs;
	$content = $wpdb->escape(implode("<!--nextpage-->", $newpages));

	// Update database
	if ($pict_date && get_settings('pp_use_picture_time') &&
	    $path == pp_get_image_path($post_ID, $pict_date)) {
		// If we use picture times and the path to the image directory
		// will not change due to a different date, 
		// copy the first picture date to the post date.
		$post_date = gmdate('Y-m-d H:i:s', $pict_date);
		$post_date_gmt = gmdate('Y-m-d H:i:s', $pict_date -
					3600 * get_settings('gmt_offset'));
		$result = $wpdb->query("
			UPDATE $wpdb->posts SET
				post_content = '$content',
				post_date = '$post_date',
				post_date_gmt = '$post_date_gmt'
			WHERE ID = $post_ID ");
	} else {
		$result = $wpdb->query("
			UPDATE $wpdb->posts SET
				post_content = '$content'
			WHERE ID = $post_ID ");
	}

	// Generate excerpt if none there
	//
	//if (!$postdata['post_excerpt']) {
		//$excerpt = explode('<!--more-->', $content);
		//if ($excerpt[0] != $content) {
			//$wpdb->query("UPDATE $wpdb->posts
				      //SET post_excerpt = '$excerpt[0]'
				      //WHERE ID = $post_ID");
		//}
	//}

	// Let user edit subtitles if necessary and possible
	//
	if ($dir && !$no_thumbs && !get_settings('pp_single_post') &&
	    !headers_sent()) {
		if ($pp_wp15)
		    $location = get_settings('siteurl') . 
			"/$pp_pictpress_dir/edit-subtitles.php?post=$post_ID";
		else
		    $location = get_settings('siteurl') . 
			"/$pp_pictpress_dir/edit-subtitles-1.2.php?post=$post_ID";
		wp_redirect($location);
	}
	return $post_ID;
}

// Add CSS header (to be called from wp_head)
//
function pp_css_head ($arg) {
	global $pp_pictpress_dir;

	$site = get_settings('siteurl');
	echo "<style type=\"text/css\">";
	echo "@import url( $site/$pp_pictpress_dir/pictpress.css );";
        echo "</style>";

	return $arg;
}

// Get subtitle for page $p
//
function pp_get_subtitle ($p) {
	global $pages;

	if (preg_match("'<h4.*?>(.*?)</h4>'s", $pages[$p-1], $matches))
		return $matches[1];
	else
		return "Page $p";
}

// Get title for post $id
//
function pp_get_title ($id) {
	global $wpdb, $post;

	if ($id) {
		$sql = "SELECT post_title FROM $wpdb->posts WHERE ID = '$id'";
		$sub = $wpdb->get_var($sql);
	} else {
		$sub = $post->post_title;
	}
	return $sub ? stripslashes($sub) : $id;
}

// Get link for page $p
//
function pp_get_link ($post_ID, $p) {
	global $querystring_equal, $querystring_separator;

	if ('' == get_settings('permalink_structure')) {
		return get_permalink($post_ID).$querystring_separator.'page'.$querystring_equal.$p;
	} else {
		return get_permalink($post_ID).$p.'/';
	}

}

// Generate thumbnails with text from child posts
//
function pp_get_thumb_content () {
	global $wpdb, $post, $pp_page_gen_pat, $pp_pict_pat;

	$children = $wpdb->get_row("SELECT id, post_content
				    FROM $wpdb->posts 
				    WHERE post_parent = $post->ID
				    ORDER BY post_date ASC");

	$html = '';

        foreach ($children as $child) {
	 	if (preg_match($pp_page_gen_pat, $child->post_content, $m)) {
			include_once (ABSPATH . "$pp_pictpress_dir/class.image.php");
			$image = new Image($m[1], $child->id);
			$href = get_permalink($child->id);
			$html .= "<div class=\"pp-thumb-text\">";
			$html .= "<div class=\"image\">";
			$html .= $image->GetImgRef($href, get_settings('pp_thumb_size'));
			$html .= "</div>";
			$html .= preg_replace($pp_pict_pat, '', $child->post_content);
			$html .= "</div>";             
		}
	}

	return $html;
}

// Generate thumbnail for picture post
//
function pp_get_thumb ($url) {
	global $wpdb, $pp_page_gen_pat, $pp_pictpress_dir, $pp_children;
	$id = url_to_postid ($url);
	if (!$id)
		return "<!--PictPress could not find post '$url'-->";
	$postdata = wp_get_single_post($id, ARRAY_A);
	$parent = $postdata['post_parent'];
	$title = stripslashes($postdata['post_title']);
	// If main post, generate thumbnail for first child
	if ($parent == 0) {
		pp_get_children($id);
                if (count($pp_children) > 0) {
                        $postdata = wp_get_single_post(
					url_to_postid($pp_children[0]), 
					ARRAY_A);
                }
	}
	$content = $postdata['post_content'];
	if (preg_match($pp_page_gen_pat, $content, $m)) {
		include_once (ABSPATH . "$pp_pictpress_dir/class.image.php");
		$image = new Image($m[1], $id);
		$r = get_permalink($id);
		return pp_expand_subtitles($image->GetThumbnail($r));
	} else {
		// No picture found, just return title + link
		return "<a href=\"$url\">$title</a>";
	}
}

// Expand subtitles in content (to be called as filter in the_content)
//
function pp_expand_subtitles ($content) {
	// Old style formats
	// These interfere with strip_tags in RSS feed generation
	//
	$content = preg_replace_callback(
			"/<!--subtitle *([0-9]*)-->/", 
			create_function('$matches', 'return pp_get_subtitle($matches[1]);'),
			$content);
	$content = preg_replace_callback(
		        "/<!--pp-title *([0-9]*)-->/",
		        create_function('$matches', 'return pp_get_title($matches[1]);'),
			$content);
	$content = preg_replace_callback(
		        "/<!--pp-thumb +(.+?)-->/",
		        create_function('$matches', 'return pp_get_thumb($matches[1]);'),
			$content);
	// New style formats
	//
	$content = preg_replace_callback(
			"/%pp-subtitle *([0-9]*)%/", 
			create_function('$matches', 'return pp_get_subtitle($matches[1]);'),
			$content);
	$content = preg_replace_callback(
		        "/%pp-title *([0-9]*)%/",
		        create_function('$matches', 'return pp_get_title($matches[1]);'),
			$content);
	$content = preg_replace_callback(
		        "/%pp-archives-date *(.*?)%/",
		        create_function('$matches', 'return pp_archives_date($matches[1]);'),
			$content);
	$content = preg_replace_callback(
		        "/%pp-archives-cat *(.*?)%/",
		        create_function('$matches', 'return pp_archives_cat($matches[1]);'),
			$content);
	$content = preg_replace_callback(
		        "/%pp-thumb-content%/",
		        create_function('$matches', 'return pp_get_thumb_content();'),
			$content);
	// Remove all comments if in silent mode.
	// This should prevent problems with wpautop, markdown, textile, ...
	// Note that comments can still be seen in the edit post form.
	//
	if (get_settings('pp_silent')) 
		$content = preg_replace('/<!--.*?-->/', '', $content);
	return $content;
}

// Delete all posts with parent $id
//
function pp_delete_children ($id) {
	global $wpdb;
	global $post_id;

	if (!$id) $id = $post_id; // Bug in post.php when calling do_action

	$children = $wpdb->get_col("SELECT ID FROM $wpdb->posts
		                    WHERE post_parent = $id");
	if ($children) {
		foreach ($children as $child) {
			wp_delete_post($child);
			pp_delete_children($child);
		}
	}

	return $id;
}

// Check children with deleted image and delete them
//
function pp_check_deleted ($id) {
	global $wpdb, $pp_page_gen_pat;

	$children = $wpdb->get_col("SELECT ID FROM $wpdb->posts
		                    WHERE post_parent = $id");
	if ($children) {
		$dir = pp_get_upload_dir();
		foreach ($children as $child) {
			$content = $wpdb->get_var("SELECT post_content 
						   FROM $wpdb->posts 
						   WHERE ID = $child");
			if (preg_match($pp_page_gen_pat, $content, $m) &&
			    !file_exists("$dir/" . $m[1])) {
				wp_delete_post($child);
				pp_delete_children($child);
			}
		}
	}
}

// Query filter
//
function pp_posts_where_filter ($where) {
	if (strpos($where, 'DAYOFMONTH') === false &&
	    strpos($where, 'post_name') === false &&
	    strpos($where, 'post_parent') === false &&
	    strpos($where, "post_type = 'attachment'") === false &&
	    strpos($where, 'attachment_id') === false &&
	    strpos($where, 'ID') === false &&
	    strpos($where, 'LIKE') === false)
		return "$where AND post_parent = 0";
	else
		return $where;
}

// Filter out all child posts
//
function pp_filter_children () {
	global $posts;

	if (is_page() || is_single() || is_day() || is_search())
		return;

	$newsposts = array();

	if ($posts) {
		foreach ($posts as $post) 
			if ($post->post_parent == 0)
				$newposts[] = $post;

		if ($newposts)
			$posts = $newposts;
	}
}

// Filter to add parent to title
//
function pp_add_parent_title ($title) {
	global $posts, $wpdb;

	$post = $posts[0];
	while ($post->post_parent != 0) {
		$post = $wpdb->get_row("SELECT post_parent, post_title 
					FROM $wpdb->posts 
					WHERE ID = $post->post_parent");
		$parent_title = stripslashes($post->post_title);
		$title = "$parent_title &raquo; $title";
	}
	return $title;
}
	
// Action for add_menu
//
function pp_add_options_page () {
    add_options_page('PictPress Options', 'PictPress', 8, 
    		     'pictpress/options.php');
}

// Add hidden form field to pass post_parent when editing post
//
function pp_add_post_parent () {
    global $post_parent;
    if ($post_parent)
        echo "<input name=\"parent_id\" type=\"hidden\" value=\"$post_parent\" />";
}

// Priority must be below generic_ping priority, because
// generic_ping does not return its argument, so we would
// not have the post_ID we need.
//
add_action("publish_post", "pp_add_pictures", 5);
add_action("delete_post", "pp_delete_children");
add_action("posts_where", "pp_posts_where_filter");
add_action("wp_head", "pp_filter_children");
add_filter("single_post_title", "pp_add_parent_title");
add_filter("the_content", "pp_expand_subtitles", 5);
add_filter("the_excerpt", "pp_expand_subtitles", 5);
add_filter("the_excerpt_rss", "pp_expand_subtitles", 5);
add_action('admin_menu', 'pp_add_options_page');
if (get_settings('pp_use_css')) {
	add_action("wp_head", "pp_css_head");
	add_action("admin_head", "pp_css_head");
}
add_action('edit_form_advanced', 'pp_add_post_parent');
add_action('pp_the_parent_title', 'pp_the_parent_title');
add_action('pp_prev_thumb', 'pp_prev_thumb');
add_action('pp_next_thumb', 'pp_next_thumb');
add_action('pp_navigation', 'pp_navigation');
add_action('pp_modified_date', 'pp_modified_date');
add_action('pp_edit_subtitles_link', 'pp_edit_subtitles_link');

//echo "PictPress loaded\n";

?>
