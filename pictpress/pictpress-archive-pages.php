<?php
/*
Plugin Name: PictPress Archive Pages
Plugin URI: http://www.curioso.org/pictpress/
Description: Addition to the PictPress plugin to create pages with archives by date or archives by category.
Version: 1.0 beta
Author: Curioso
Author URI: http://www.curioso.org/
*/ 

// List posts
//
function pp_list_posts ($posts) {
    global $wpdb;

    $prev_date = '';
    foreach ($posts as $post) {
	$date = mysql2date(get_settings('date_format'), $post->date);
	if ($date != $prev_date) {
	    if ($prev_date)
		$t .= "</ul></li>\n";
	    $url = get_day_link($post->year, $post->month, $post->day);
	    $t .= "<li><a href=\"$url\" title=\"$date\">$date</a><ul>";
	    $prev_date = $date;
	}
	$t .= "<li>";
	$t .= mysql2date(get_settings('time_format'), $post->date);
	$perm = get_permalink($post->post_id);
	$title = stripslashes($post->title);
	$t .= " <a href=\"$perm\" rel=\"bookmark\" title=\"$title\">";
	$t .= "$title</a>";

	$count = count(explode(' ', strip_tags($post->content)));
	if ($count == 1)
	    $t .= ", ".__('1 word');
	else if ($count != 0)
	    $t .= ", ".str_replace('%', "$count", __('% words'));

	$count = preg_match_all("|<img|", $post->content, $m);
	if ($count == 1)
	    $t .= ", ".__('1 picture');
	else if ($count != 0)
	    $t .= ", ".str_replace('%', "$count", __('% pictures'));

	$count = $wpdb->get_var("SELECT COUNT(comment_ID) 
				 FROM $wpdb->comments 
				 WHERE comment_post_ID = $post->post_id AND 
				       comment_approved = '1';");
	if ($count == 1)
	    $t .= ", ".__('1 comment');
	else if ($count != 0)
	    $t .= ", ".str_replace('%', "$count", __('% comments'));

	$t .= "</li>\n";
    }
    if ($prev_date)
	$t .= "</ul></li>\n";
    return $t;
}

// Archives by date
//
function pp_archives_date () {
    global $wpdb, $month;

    $t = '';
    $now = current_time('mysql');
    $dates = $wpdb->get_results(
		    "SELECT DISTINCT YEAR(post_date) AS year, 
				     MONTH(post_date) AS month, 
				     count(ID) as posts 
		     FROM $wpdb->posts WHERE post_date < '$now' AND 
					     post_status = 'publish' AND 
					     post_parent = 0 AND
					     post_password='' 
		     GROUP BY YEAR(post_date), MONTH(post_date) 
		     ORDER BY post_date DESC");
    if ($dates) {
	$t .= "<div class=\"archives\">\n";
    	foreach ($dates as $date) {
	    $url = get_month_link($date->year, $date->month);
	    $text = $month[zeroise($date->month,2)]." $date->year";

	    $posts = $wpdb->get_results(
	            "SELECT ID AS post_id,
			    post_title AS title,
		            post_content AS content,
			    post_date AS date,
			    YEAR(post_date) AS year, 
			    MONTH(post_date) AS month, 
			    DAYOFMONTH(post_date) AS day 
		     FROM $wpdb->posts 
		     WHERE year = $date->year AND 
			   month = $date->month AND
			   post_date < '$now' AND
		           post_status = 'publish' AND 
			   post_parent = 0 AND
			   post_password = '' 
		     ORDER BY date DESC");
	    $count = count($posts);
	    $t .= "<h3><a href=\"$url\" title=\"$text\">$text</a> ($count)</h3>\n";
	    $t .= "<ul>\n";
	    $t .= pp_list_posts($posts);
	    $t .= "</ul>\n";
	}
	$t .= "</div>\n";
    }
    return $t;
}

// Archives by category
//
function pp_archives_cat($parent = 0) {
    global $wpdb, $month, $pp_thumb_href_pat;

    $t = '';
    $now = current_time('mysql');
    $cats = $wpdb->get_results(
                    "SELECT cat_ID, 
		            cat_name, 
			    category_nicename,
			    category_parent,
		            COUNT($wpdb->post2cat.post_id) AS cat_count
		     FROM $wpdb->categories 
		     LEFT JOIN $wpdb->post2cat ON (cat_ID = category_id)
		     LEFT JOIN $wpdb->posts ON (ID = post_id)
		     WHERE cat_ID > 0 AND 
			   category_parent = '$parent'
		     GROUP BY cat_ID HAVING cat_count > 0
		     ORDER BY cat_name ASC");

    if ($cats) {
        if ($parent == 0)
	    $t .= "<div class=\"archives\">\n";
    	foreach ($cats as $cat) {
	    $url = get_category_link(0, $cat->cat_ID, $cat->category_nicename);
	    $text = htmlspecialchars($cat->cat_name);

	    $posts = $wpdb->get_results(
	            "SELECT post_title AS title,
		            post_content AS content,
			    post_date AS date,
			    YEAR(post_date) AS year, 
			    MONTH(post_date) AS month, 
			    DAYOFMONTH(post_date) AS day, 
			    ID AS post_id 
		     FROM $wpdb->posts JOIN $wpdb->post2cat
		     WHERE post_status = 'publish' AND 
			   post_parent = 0 AND
			   post_password = '' AND
			   post_date < '$now' AND
			   $wpdb->post2cat.post_id = ID AND
			   $wpdb->post2cat.category_id = $cat->cat_ID
		     ORDER BY date DESC");
	    $count = count($posts);
	    $t .= $parent == 0 ? "<h3>" : "<li>";
	    $t .= "<a href=\"$url\" title=\"$text\">$text</a> ($count)";
	    $t .= $parent == 0 ? "</h3>\n" : "\n";
	    $t .= "<ul>\n";
	    $subcats = pp_archives_cat($cat->cat_ID);
	    if ($subcats) {
	        $t .= "<li>".__("General")."\n<ul>\n";
		$t .= pp_list_posts($posts);
		$t .= "</ul>\n</li>\n";
		$t .= $subcats;
	    } else {
		$t .= pp_list_posts($posts);
	    }
	    $t .= "</ul>\n";
	    $t .= $parent == 0 ? "\n" : "</li>\n";
	}
	$t .= $parent == 0 ? "</div>\n" : "";

    }
    return $t;
}

// Expand archive tags
//
function pp_archive_tags ($content) {
	$content = preg_replace_callback(
		        "/{pp-archives-date *(.*?)}/",
		        create_function('$matches',
				'return pp_archives_date($matches[1]);'),
			$content);
	$content = preg_replace_callback(
		        "/{pp-archives-cat *(.*?)}/",
		        create_function('$matches',
				'return pp_archives_cat($matches[1]);'),
			$content);
	return $content;
}

add_filter("the_content", "pp_archive_tags", 5);
?>
