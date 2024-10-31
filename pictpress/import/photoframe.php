<?php
// Script to import Photoframe picture directory.
//
require_once(dirname(__FILE__) . "/../../../wp-config.php");

$cat = $_GET['cat'];
$path = $_GET['path'];
$author = 2;

if (!file_exists($path) || !is_dir($path)) {
	header("HTTP/1.x 404 Not Found");
	echo "Photoframe directory not found '$path'.";
	exit;
}

// Recursively create directory
//
function createdir ($dir) {
        if (!file_exists($dir)) {
                createdir(dirname($dir));
                mkdir($dir);
        }
}

require("$path/config.php");

$parts = explode(',', $title);
if (count($parts) > 1) {
	// Remove date from title
	//
	array_pop($parts);
	$title = implode(' -', $parts);
}

$date = basename($path);
$yyyy = substr($date, 0, 4);
$mmm  = substr($date, 4, 2);
$dd   = substr($date, 6, 2);

// Create post
//
$postdata['post_title'] = $title;
$postdata['post_author'] = $author;
$postdata['post_status'] = 'publish';
$postdata['post_date'] = "$yyyy-$mmm-$dd 22:00:00";
$postdata['post_date_gmt'] = "$yyyy-$mmm-$dd 20:00:00";
$cats = explode(' ', $cat);
foreach ($cats as $c) {
	$cat_id = $wpdb->get_var("SELECT cat_ID from $wpdb->categories 
			          WHERE cat_name = '$c'");
	if (!$cat_id) {
		header("HTTP/1.x 404 Not Found");
		echo "Category not found '$c'.";
		exit;
	}
	$postdata['post_category'][] = $cat_id;
}
$id = pp_insert_post($postdata);

// Copy images to directory associated with post
//
$base = get_settings('fileupload_realpath');
$newdir = pp_get_image_path($id);
createdir("$base/$newdir");
if (!file_exists("$base/$newdir")) {
        header("HTTP/1.x 404 Not Found");
        echo "File not found '$base/$newdir'.";
        exit;
}
$dir = opendir("$path");
$commentedfiles = array();
while ($file = readdir($dir)) {
    if (eregi("\.jpe?g$", $file) && !eregi("^\.", $file)) {
	copy("$path/$file", "$base/$newdir/$file");
	touch("$base/$newdir/$file", filemtime("$path/$file"));
	$commentfile = "$path/thumb/$file.txt";
	if (file_exists($commentfile)) 
	    $commentedfiles[] = $file;
    }
}

// Update post, so it incorporates the pictures
//
do_action('publish_post', $id);

$file = fopen("$base/rewrite.txt", "a");
$url = get_permalink($id);
fwrite($file, "RewriteRule ^$date(/.*)?\$ $url [R=permanent]\n");

foreach ($commentedfiles as $file) {
        $commentfile = "$path/thumb/$file.txt";
        $slug = preg_replace('/(.+)\..*$/', '$1', $file);
        $slug = sanitize_title($slug);
	$sub_id = pp_get_subpost($id, $slug);
	if (!$sub_id) {
		header("HTTP/1.x 404 Not Found");
		echo "Subpost not found '$slug'.";
		exit;
	}
	$lines = file($commentfile);
	foreach ($lines as $line) {
		$fields = explode('###', rtrim($line));
		$date_gmt = gmdate('Y-m-d H:i:s', $fields[0]);
		$date = gmdate('Y-m-d H:i:s', 
			       $fields[0] + 3600 * get_settings('gmt_offset'));
		$author = $fields[1];
		$comment = $wpdb->escape($fields[2]);
		$approved = 1;
		$wpdb->query("INSERT INTO $wpdb->comments
			(comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_date_gmt, comment_content, comment_approved)  
			VALUES  
			('$sub_id', '$author', '$email', '$url', '$user_ip', '$date', '$date_gmt', '$comment', '$approved')");

	}
}

?>
