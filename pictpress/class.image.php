<?PHP
class Image {

    var $name;
    var $exif;
    var $time;
    var $id;
    var $width;
    var $height;
    var $modified;
    var $comment;

    function Image ($filename, $id='') {
        $path = pp_get_upload_dir() . "/$filename";
        $this->name = $filename;
	$this->modified = $this->time = filemtime("$path");
	list($this->width, $this->height) = getimagesize("$path");
	$this->id = $id;

	if (function_exists('exif_read_data')) {
            $this->exif = exif_read_data ("$path", 0, true);
            $datetime = $this->exif['EXIF']['DateTimeOriginal'];
            if ($datetime) {
                $yyyy = substr($datetime, 0, 4);
                $mmm  = substr($datetime, 5, 2);
                $dd   = substr($datetime, 8, 2);
                $hh   = substr($datetime, 11, 2);
                $mm   = substr($datetime, 14, 2);
                $ss   = substr($datetime, 17, 2);
                $this->time = mktime($hh, $mm, $ss, $mmm, $dd, $yyyy, 0);
            }
	}

	$comment = $this->exif['COMMENT'];
        if (!$comment && get_settings('pp_read_comments')) {
            exec("rdjpgcom ".escapeshellarg("$path"), $comment);
	}
	if ($comment) {
	    $comment = trim(implode("", $comment));
	    $comment = htmlentities(strip_tags(strtr($comment, "\r\n", "  ")));
	    if ($comment == 'AppleMark')
		$comment = '';
        }
        if (!$comment) {
	    $comment = preg_replace('/(.+)\..*$/', '$1', 
				basename($this->name));
        }
	$this->comment = $comment;
    }

    // Get URL to resized image
    //
    function GetURL ($size = 0) {
	if ($size == 0 && !get_settings('pp_full_image'))
		return '';
	$path = $this->name;
	$url = get_settings('pp_resize_url');
	if (preg_match('/\?.*%path%/', $url))
		$path = rawurlencode($path);
	else {
		$path = urlencode($path);
		$path = str_replace('%2F', '/', $path);
	}
	return str_replace(array('%size%', '%path%'),
		           array("$size", "$path"),
		           $url);
    }

    // Get expanded text
    //
    function GetText ($option) {
	$text = get_settings($option);
	if (strpos($text, '%exif%') !== false) {
	    $exif = '<table>';
	    $exif .= "<tr><th>Section</th><th>Name</th><th>Value</th></tr>\n";
            foreach ($this->exif as $key => $section) {
                foreach ($section as $name => $val) {
		    $exif .= "<tr><td>$key</td><td>$name</td><td>$val</td></tr>\n";
	        }
	    }
	    $exif .= '</table>';
	}
	if (get_settings('pp_single_post')) 
	    $title = "%pp-subtitle $this->id%";
	else
	    $title = "%pp-title $this->id%";
	$aperture = $this->exif['EXIF']['FNumber'];
	if ($aperture) {
		preg_match("|(.*)/(.*)|", $aperture, $matches);
		if ($matches[2])
			$aperture = 'f/'.$matches[1]/$matches[2];
		else
			$aperture = 'f/'.$aperture;
	}
	return str_replace(
	   array('%aperture%',
		 '%comment%', 
		 '%date%', 
		 '%digitized%', 
		 '%edited%', 
		 '%exif%', 
		 '%exposure%', 
		 '%focallength%', 
		 '%height%',
		 '%iso%',
		 '%make%',
		 '%model%',
		 '%modified%',
		 '%size%',
		 '%sizekb%',
		 '%sizemb%',
		 '%title%',
		 '%width%'),
	   array($aperture,
		 $this->comment, 
		 date("j-M-y H:i:s", $this->time),
		 $this->exif['EXIF']['DateTimeDigitized'],
		 $this->exif['IFD0']['DateTime'],
		 $exif, 
		 $this->exif['EXIF']['ExposureTime'],
		 $this->exif['EXIF']['FocalLength'],
		 $this->height, 
		 $this->exif['EXIF']['ISOSpeedRatings'],
		 $this->exif['IFD0']['Make'],
		 $this->exif['IFD0']['Model'],
		 date("j-M-y H:i:s", $this->modified),
		 $this->exif['FILE']['FileSize'],
		 rtrim(substr($this->exif['FILE']['FileSize'] / 1024, 0, 4), 
		       '.'),
		 substr($this->exif['FILE']['FileSize'] / (1024*1024), 0, 4),
		 $title,
		 $this->width),
	   $text);
    }

    // Return HTML for popup
    //
    function GetPopup ($href) {
	$size = get_settings('pp_pict_size');
	if ($size == 0) {
	    $w = $this->width;
	    $h = $this->height;
	} else if ($this->width < $this->height) {
	    $w = intval($this->width * $size / $this->height);
	    $h = $size;
	} else {
	    $w = $size;
	    $h = intval($this->height * $size / $this->width);
	}
	return "onclick=\"window.open('$href','popup','width=$w,height=$h,scrollbars=no,resizable=no,toolbar=no,directories=no,location=no,menubar=no,status=no,left=0,top=0'); return false\"";
    }

    // Return HTML for img reference
    //
    function GetImgRef ($href, $size='140') {
	if (preg_match('/^(\d+)[xX](\d+)$/', $size, $m)) {
	    $w = $m[1];
	    $h = $m[2];
	} else if ($size == 0 || 
	           ($size >= $this->width && $size >= $this->height)) {
	    $w = $this->width;
	    $h = $this->height;
	    $size = 0;
	} else if ($this->width < $this->height) {
	    $w = intval($this->width * $size / $this->height);
	    $h = $size;
	} else {
	    $w = $size;
	    $h = intval($this->height * $size / $this->width);
	}
	$a = $this->GetText('pp_thumb_alt');
	$t = $this->GetText('pp_thumb_title');
	$u = $this->GetURL($size);
	if ($href)
		$html  = "<a title=\"$t\" href=\"$href\">";
	$html .= "<img src=\"$u\" width=\"$w\" height=\"$h\" alt=\"$a\" />";
	if ($href)
		$html .= "</a>";
	return $html;
    }

    // Return HTML for thumbnail
    //
    function GetThumbnail ($href) {
	$html  = "<div class=\"thumbnail\">";
	$html .= "<div class=\"image\">";
	$html .= $this->GetImgRef($href, get_settings('pp_thumb_size'));
	$html .= "</div>";
	$html .= "<div class=\"caption\">";
	$html .= $this->GetText('pp_thumb_caption');
	$html .= "</div>";
	$html .= "</div>";
	return $html;
    }

    // Return HTML for picture page
    //
    function GetPicture () {
	$html  = "<!--pp-page-start-->";
	$html .= "<!--PictPress generated page for image $this->name-->";
	$html .= "<div class=\"picture\">";
	$html .= "<div class=\"image\">";
	$html .= $this->GetImgRef($this->GetURL(),
				     get_settings('pp_image_size'));
	$html .= "</div>";
	$html .= "<div class=\"caption\">";
	$html .= $this->GetText('pp_image_caption');
	$html .= "</div>";
	$html .= "</div>";
	$html .= "<!--pp-page-end-->";

	return $html;
    }

} // Image
?>
