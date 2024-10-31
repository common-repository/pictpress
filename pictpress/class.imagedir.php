<?PHP

require_once ("class.image.php");

// Sort compare function for images
//
function pp_image_compare ($a, $b) {
    $pp_sort_key = get_settings('pp_sort_key');
    if (!$pp_sort_key)
        $pp_sort_key = 'time';
    $pp_sort_order = get_settings('pp_sort_order');
    if (!$pp_sort_order)
        $pp_sort_order = 1;

    if ($a->$pp_sort_key > $b->$pp_sort_key)
	return $pp_sort_order;

    if ($a->$pp_sort_key < $b->$pp_sort_key)
	return -1 * $pp_sort_order;

    return 0;
}

class ImageDir {

    var $name;
    var $list = array();
    var $count = 0;

    function ImageDir ($dirname) {
	$base = pp_get_upload_dir();
        $this->name = $dirname;
        $dir = opendir("$base/$dirname");
        while ($file = readdir($dir)) {
            if (eregi("\.jpe?g$", $file) && !eregi("^\.", $file)) {
                $this->list[] = new Image ("$dirname/$file");
		$this->count++;
            }
        }
        usort($this->list, "pp_image_compare");
   }

    // Return Image number $i
    //
    function GetImage ($i) {
        return $this->list[$i];
    }

} // ImageDir
?>
