<?php
// resize.php?size=n&path=xxx
//
// Resize image to max nxn pixels caching results
// If n==0, return original image
// Path is relative to WordPress file upload directory

require_once(dirname(__FILE__) . "/../../../wp-config.php");

$path = $_GET['path'];
$size = $_GET['size'];

if (preg_match('/^(\d+)[xX](\d+)$/', $size, $m)) {
} else if (!is_numeric($size) || 
           ($size == 0 && !get_settings('pp_full_image'))) {
	header("HTTP/1.x 404 Not Found");
	echo "Invalid size argument '$size'.";
	exit;
} else {
    // Limit $size to sensible values
    if ($size > 1000) $size = 1000;
    if ($size != 0 && $size < 16) $size = 16;
}


if (get_magic_quotes_gpc())
	$path = stripslashes($path);
$base = pp_get_upload_dir();
$image = "$base/$path";

if (strpos('/../', $image) !== false || !file_exists($image)) {
	header("HTTP/1.x 404 Not Found");
	echo "File not found '$image'.";
	exit;
}

$referrer = $_SERVER['HTTP_REFERER'];

function valid_referrer($referrer) {

	if (empty($referrer))
		return 1;

	if (strpos($referrer, get_settings('home')) === 0)
		return 1;

	if (strpos($referrer, get_settings('siteurl')) === 0)
		return 1;

	return 0;
}

if (get_settings('pp_protect_images') && !valid_referrer($referrer)) {
	header("HTTP/1.x 403 Forbidden");
	echo "Invalid referrer '$referrer'.";
	exit;
}

$imagemtime = filemtime($image);

// Recursively create directory
//
function createdir ($dir) {
	if (!file_exists($dir)) {
		createdir(dirname($dir));
		mkdir($dir);
		if (!file_exists($dir)) {
			header("HTTP/1.x 404 Not Found");
			echo "Could not create cache directory '$dir'.";
			exit;
		}
	}
}

if ($size != 0) {
	$cache = "$base/" . str_replace(array('%size%', '%path%'),
					array("$size", "$path"), 
					get_settings('pp_cache_dir'));
	$dir = dirname($cache);
	createdir($dir);
}

// Resize image
//
function resize ($image, $cache, $newsize) {
    if (get_settings('pp_resize_method') == "GD2") {
	$src = imagecreatefromjpeg($image);
	$sx = imagesx($src);
	$sy = imagesy($src);
	if (preg_match('/^(\d+)[xX](\d+)$/', $newsize, $m)) {
	    $w = $m[1];
	    $h = $m[2];
	    $dst = imagecreatetruecolor($w, $h);
	    $xo = $yo = 0;
	    $rw = $sx / $w;
	    $rh = $sy / $h;
	    if ($rw < $rh) {
		$sy = floor($h * $rw);
		$yo = floor((imagesy($src) - $sy) / 2);
	    } else {
		$sx = floor($w * $rh);
		$xo = floor((imagesx($src) - $sx) / 2);
	    }
	    imagecopyresampled($dst, $src, 0, 0, $xo, $yo, 
					   $w, $h, $sx, $sy);
	    imagejpeg($dst, $cache, 80);
	    imagedestroy($dst);
	} else {
	    $scale = $newsize / ($sx > $sy ? $sx : $sy);
	    $w = floor($scale * $sx);
	    $h = floor($scale * $sy);
	    $dst = imagecreatetruecolor($w, $h);
	    imagecopyresampled($dst, $src, 0, 0, 0, 0, 
					   $w, $h, $sx, $sy);
	    imagejpeg($dst, $cache, 80);
	    imagedestroy($dst);
	}
	imagedestroy($src);
    } else {
	$c = escapeshellarg($cache);
	$f = escapeshellarg($image);
	if (preg_match('/^(\d+)[xX](\d+)$/', $newsize, $m)) {
	    $w = $m[1];
	    $h = $m[2];
	    list($sx, $sy) = getimagesize("$image");
	    $rw = $sx / $w;
	    $rh = $sy / $h;
	    if ($rw < $rh) {
		$ww = $w;
		$hh = floor($sy / $rw);
	    } else {
		$ww = floor($sx / $rh);
		$hh = $h;
	    }
	    exec("convert -resize ${ww}x${hh} -gravity center -crop ${w}x${h}+0+0 $f $c");
	} else {
	    $x = $newsize . 'x' . $newsize;
	    exec("convert -size $x -resize $x $f $c");
	}
    }
}

$last_modified = gmdate('D, d M Y H:i:s', $imagemtime) . ' GMT';
$etag = '"' . md5($last_modified) . '"';

header("Content-Type: image/jpeg");
header("Content-Disposition: inline");
header("Last-Modified: $last_modified");
header("ETag: $etag");
// Set expiry to one day
header("Expires: " . gmdate("D, d M Y H:i:s", time() + 24*3600) . " GMT");

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) 
    $c_last_modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
else 
    $c_last_modified = false;
if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) 
    $c_etag = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']);
else 
    $c_etag = false;

if (($c_last_modified && $c_etag) ?
		(($c_last_modified == $last_modified) && ($c_etag == $etag)) :
		(($c_last_modified == $last_modified) || ($c_etag == $etag))) {
    if (preg_match('/cgi/', php_sapi_name())) {
	header('HTTP/1.1 304 Not Modified');
	echo "\r\n\r\n";
    } else {
	header('HTTP/1.x 304 Not Modified');
    }
} else if ($size == 0) {
    readfile($image);
} else {
    if (!file_exists($cache) || $imagemtime > filemtime($cache)) {
	resize($image, $cache, $size);
    }
    if (file_exists($cache)) {
	readfile($cache);
    } else {
	header("HTTP/1.x 404 Not Found");
	echo "File not found '$image'.";
	exit;
    }
}

?>
