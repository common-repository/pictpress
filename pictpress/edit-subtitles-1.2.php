<?php
chdir('../../../wp-admin');
require_once('../wp-config.php');
require_once('../wp-includes/wp-l10n.php');

$title = __('Edit Subtitles');
$parent_file = 'post.php';

if (file_exists(ABSPATH.'/wp-admin/auth.php'))
	require_once(ABSPATH.'/wp-admin/auth.php');
require(ABSPATH.'/wp-admin/admin-functions.php');
if (function_exists('auth_redirect'))
	auth_redirect();

if (isset($_GET['post'])) $post = (int) $_GET['post'];

get_currentuserinfo();

$author = $wpdb->get_var("SELECT post_author FROM $wpdb->posts
		          WHERE ID = '$post'");
$authordata = get_userdata($author);
if ($user_level < $authordata->user_level)
    die('Forbidden');

if ($post && !isset($_POST['newtitle'])) {

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>WordPress &rsaquo; <?php bloginfo('name') ?> &rsaquo; <?php echo $title; ?></title>
<base href="<?php echo get_settings('siteurl') . "/wp-admin/" ?>">
<link rel="stylesheet" href="wp-admin.css" type="text/css" />
<link rel="shortcut icon" href="../wp-images/wp-favicon.png" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo get_settings('blog_charset'); ?>" />
<?php 

if (get_settings('pp_use_css'))
  echo "<link rel=\"stylesheet\" href=\"../wp-content/plugins/pictpress/admin.css\" type=\"text/css\" />\n";
do_action('admin_head', '');

?>
</head>
<body>
<div id="wphead">
<h1><a href="http://wordpress.org" rel="external" title="<?php _e('Visit WordPress.org') ?>"><?php _e('WordPress') ?></a></h1>
</div>
<?php require('./menu.php'); ?>
<div class="wrap">
<form name="editsubtitles" id="editsubtitles" action="" method="post">
<table width="100%">
<?php

    pp_get_children($post);
    foreach ($pp_children as $url) {
	$id = url_to_postid($url);
	if ($id) {
	    $thumb = pp_get_thumb($url);
	    $title = pp_get_title($id);
	    echo "<tr><td valign=\"top\">$thumb</td><td valign=\"top\">";
	    echo "<input type=\"hidden\" name=\"id[]\" value=\"$id\" />";
	    echo "<input type=\"hidden\" name=\"oldtitle[]\" value=\"$title\" />";
	    echo "<textarea name=\"newtitle[]\" rows=1 cols=50>";
	    echo "$title";
	    echo "</textarea>";
	    echo "</td></tr>\n";
	}
    }

?>
</table>
<input type="submit" name="submit" value="<?php _e('Update Subtitles') ?>" />
</form>
</div>
<?php 

    include('admin-footer.php');
}

if (isset($_POST['id']) && 
    isset($_POST['oldtitle']) && 
    isset($_POST['newtitle'])) {
    $id = $_POST['id'];
    $oldtitle = $_POST['oldtitle'];
    $newtitle = $_POST['newtitle'];
    for ($i = 0; $i < count($newtitle); $i++) {
	if ($newtitle[$i] != $oldtitle[$i]) {
	    $title = $wpdb->escape($newtitle[$i]);
	    $sql = "UPDATE $wpdb->posts SET post_title='$title' 
		    WHERE ID='$id[$i]'";
	    $wpdb->query($sql);
	}
    }

    $location = get_permalink($post);
    header("Location: $location");
}

?>
