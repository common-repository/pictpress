<?php

// Some WP 1.5 functions

$pp_wp15 = false;

if(!isset($wpdb->posts))
{
	$wpdb->posts               = $table_prefix . 'posts';
	$wpdb->users               = $table_prefix . 'users';
	$wpdb->categories          = $table_prefix . 'categories';
	$wpdb->post2cat            = $table_prefix . 'post2cat';
	$wpdb->comments            = $table_prefix . 'comments';
	$wpdb->links               = $table_prefix . 'links';
	$wpdb->linkcategories      = $table_prefix . 'linkcategories';
	$wpdb->options             = $table_prefix . 'options';
	$wpdb->optiontypes         = $table_prefix . 'optiontypes';
	$wpdb->optionvalues        = $table_prefix . 'optionvalues';
	$wpdb->postmeta            = $table_prefix . 'postmeta';
}

if (!function_exists('wp_get_single_post')) {
function wp_get_single_post($postid = 0, $mode = OBJECT) {
        global $wpdb;
        
        $sql = "SELECT * FROM $wpdb->posts WHERE ID=$postid";
        $result = $wpdb->get_row($sql, $mode);

        // Set categories
        $result['post_category'] = wp_get_post_cats('',$postid);
        
        return $result;
}
} else $pp_wp15 = true;

if (!function_exists('wp_get_post_cats')) {
function wp_get_post_cats($blogid = '1', $post_ID = 0) {
        global $wpdb;

        $sql = "SELECT category_id
                FROM $wpdb->post2cat
                WHERE post_id = $post_ID
                ORDER BY category_id";

        $result = $wpdb->get_col($sql);

        return array_unique($result);
}
}

if (!function_exists('wp_set_post_cats')) {
function wp_set_post_cats($blogid = '1', $post_ID = 0, $post_categories = array()) {
        global $wpdb;
        // If $post_categories isn't already an array, make it one:
        if (!is_array($post_categories)) {
                if (!$post_categories) { 
                        $post_categories = 1;
                }
                $post_categories = array($post_categories);
        }

        $post_categories = array_unique($post_categories);

        // First the old categories
        $old_categories = $wpdb->get_col("
                SELECT category_id
                FROM $wpdb->post2cat
                WHERE post_id = $post_ID");

        if (!$old_categories) {
                $old_categories = array();
        } else {
                $old_categories = array_unique($old_categories);
        }

        // Delete any? 
        $delete_cats = array_diff($old_categories,$post_categories);
        
        if ($delete_cats) {
                foreach ($delete_cats as $del) {
                        $wpdb->query(" 
                                DELETE FROM $wpdb->post2cat
                                WHERE category_id = $del 
                                        AND post_id = $post_ID
                                ");
                }
        }

        // Add any?
        $add_cats = array_diff($post_categories, $old_categories);

        if ($add_cats) {
                foreach ($add_cats as $new_cat) {
                        $wpdb->query("
                                INSERT INTO $wpdb->post2cat (post_id, category_id)
                                VALUES ($post_ID, $new_cat)");

		}
        }
}       // wp_set_post_cats()
}

if (!function_exists('wp_delete_post')) {
function wp_delete_post($postid = 0) {
	global $wpdb;

	$result = $wpdb->query("DELETE FROM $wpdb->posts WHERE ID = $postid");

	if (!$result)
		return $result;

	$wpdb->query("DELETE FROM $wpdb->comments WHERE comment_post_ID = $postid");

	$wpdb->query("DELETE FROM $wpdb->post2cat WHERE post_id = $postid");

	$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = $postid");
	
	return $result;
}
}

if (!function_exists('is_day')) {
function is_day() {
	global $day;

	return !empty($day);
}
}

if (!function_exists('is_search')) {
function is_search() {
	global $s;

	return !empty($s);
}
}

if (!function_exists('is_page')) {
function is_page() {
	global $static, $pagename, $page_id;

	return !(empty($static) && empty($pagename) && empty($page_id));
}
}

if (!function_exists('is_single')) {
function is_single() {
	global $name, $p;
	global $year, $monthnum, $day, $hour, $minute, $second;

	return !(empty($name) && (empty($p) || $p == 'all') &&
		 !($year && $monthnum && $day && $hour && $minute && $second)) &&
	       !is_page();
}
}

?>
