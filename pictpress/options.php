<?php
//require_once('admin.php');

//$title = __('PictPress Options');
//$parent_file = 'options-general.php';

//include('admin-header.php');

function text_option($option, $size=80) {
    echo "<input type=\"text\" name=\"$option\" id=\"$option\" value=\"";
    echo htmlspecialchars(get_option($option), ENT_QUOTES);
    echo "\" size=\"$size\" />\n";
}

function number_option($option, $size=8) {
    echo "<input type=\"text\" name=\"$option\" id=\"$option\" value=\"";
    echo htmlspecialchars(get_option($option), ENT_QUOTES);
    echo "\" size=\"$size\" />\n";
}

function checkbox_option($option) {
    echo "<input type=\"checkbox\" name=\"$option\" id=\"$option\" value=\"1\"";
    if (get_option($option)) echo ' checked="checked"';
    echo " />\n";
}

function select_option($option, $values) {
    echo "<select name=\"$option\" id=\"$option\">";
    foreach ($values as $value) {
    	if (get_settings($option) == $value)
	    echo "<option value=\"$value\" selected=\"selected\">";
	else
	    echo "<option value=\"$value\">";
	_e($value);
	echo "</option>";
    }
    echo "</select>";
}

?>

<div class="wrap"> 
<h2><?php _e('PictPress Options') ?></h2> 
<form name="form1" method="post" action="options.php"> 
	<input type="hidden" name="action" value="update" /> 
	<input type="hidden" name="page_options" value="'pp_image_dir','pp_cache_dir','pp_resize_url','pp_thumb_size','pp_image_size','pp_full_image','pp_thumbs_per_row','pp_min_thumbs','pp_max_thumbs','pp_more_thumbs','pp_image_title','pp_thumb_alt','pp_thumb_title','pp_thumb_caption','pp_image_caption','pp_single_post','pp_thumb_text','pp_read_comments','pp_sort_key','pp_resize_method','pp_use_picture_time','pp_image_cat','pp_protect_images','pp_use_css','pp_silent','pp_rebuild','pp_uninstall' " /> 

    <fieldset class="options"> 
    <legend><?php _e('Directory Structure') ?></legend> 
    <table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
	<tr valign="top"> 
	<th width="33%" scope="row"><?php _e('Image directory:') ?></th> 
	<td>
	<?php text_option('pp_image_dir') ?><br />
	Location of image directory relative to the upload directory
	(<?php echo pp_get_upload_dir(); ?>).
	The same variables can be used as in permalink specifications: 
	%year%, %monthnum%, %day%, %postname%, %post_id%
	</td> 
	</tr> 
	<tr valign="top"> 
	<th width="33%" scope="row"><?php _e('Cache directory:') ?></th>
	<td>
	<?php text_option('pp_cache_dir') ?><br />
	Location of resized image cache directory relative to the 
	upload directory 
	(<?php echo pp_get_upload_dir(); ?>).
	</td> 
	</tr> 
	<tr valign="top"> 
	<th width="33%" scope="row"><?php _e('Resize URL:') ?></th>
	<td>
	<?php text_option('pp_resize_url') ?><br />
	URL used for retrieving a resized image. You can use it to define a nice URL for resized images, e.g. resize/%size%/%path%, but then you will also have to provide a RewriteRule in .htaccess to the resize.php script.
	</td> 
	</tr> 
    </table> 
    </fieldset> 

    <fieldset class="options"> 
    <legend><?php _e('Picture Sizes') ?></legend> 
    <table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
	<tr valign="top"> 
	<th width="33%" scope="row"><?php _e('Thumbnail size:') ?></th> 
	<td>
	<?php number_option('pp_thumb_size') ?><br />
	Either a single number, which specifies a maximum for both width and height in pixels, or WxH, which specifies exact width and height in pixels.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row"><?php _e('Picture size:') ?> </th>
	<td>
	<?php number_option('pp_image_size') ?><br />
	Either a single number, which specifies a maximum for both width and height in pixels, or WxH, which specifies exact width and height in pixels.
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Click through to full size image:') ?></th>
	<td>
	<?php checkbox_option('pp_full_image') ?><br />
	</td>
	</tr> 
    </table> 
    </fieldset> 

    <fieldset class="options"> 
    <legend><?php _e('Layout') ?></legend> 
    <table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
	<tr valign="top"> 
	<th width="33%" scope="row"><?php _e('Thumbnails per row:') ?></th> 
	<td>
	<?php number_option('pp_thumbs_per_row') ?><br />
	</td>
	</tr>
	<tr valign="top">
	<th scope="row"><?php _e('Minimum number of pictures to create thumbnail post:') ?> </th>
	<td>
	<?php number_option('pp_min_thumbs') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Maximum number of thumbnails per page:') ?></th>
	<td>
	<?php number_option('pp_max_thumbs') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Insert --more-- string after:') ?></th>
	<td>
	<?php number_option('pp_more_thumbs'); _e('thumbnails') ?><br />
	</td>
	</tr> 
    </table> 
    </fieldset> 

    <fieldset class="options"> 
    <legend><?php _e('Text Strings') ?></legend> 
    <table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
	<tr valign="top"> 
	<th width="33%" scope="row"><?php _e('Picture title:') ?></th> 
	<td>
	<?php text_option('pp_image_title') ?><br />
	</td>
	</tr>
	<tr valign="top">
	<th scope="row"><?php _e('Image alt:') ?> </th>
	<td>
	<?php text_option('pp_thumb_alt') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Image link title:') ?></th>
	<td>
	<?php text_option('pp_thumb_title') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Thumbnail caption:') ?></th>
	<td>
	<?php text_option('pp_thumb_caption') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Picture caption:') ?> </th>
	<td>
	<?php text_option('pp_image_caption') ?><br />
	</td>
	</tr> 
    </table> 
    </fieldset> 

    <fieldset class="options"> 
    <legend><?php _e('Procesing Options') ?></legend> 
    <table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
	<tr valign="top"> 
	<th width="33%" scope="row"><?php _e('Create single post:') ?></th> 
	<td>
	<?php checkbox_option('pp_single_post') ?><br />
	If ticked all pictures are added as pages to a single post.<br />
	Else for each picture a child post is created and a thumbnail is added to the parent post,
	together with the <?php select_option('pp_thumb_text', array('content', 'title')) ?> of the child post.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row"><?php _e('Use picture comments:') ?> </th>
	<td>
	<?php checkbox_option('pp_read_comments') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Sort pictures on:') ?></th>
	<td>
	<?php select_option('pp_sort_key', 
			    array('comment', 'modified', 'name', 'time')) ?>
	<?php select_option('pp_sort_order', array('1', '-1')) ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('For resizing use:') ?></th>
	<td>
	<?php select_option('pp_resize_method', 
			    array('GD2', 'ImageMagick')) ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Use picture time as post time:') ?> </th>
	<td>
	<?php checkbox_option('pp_use_picture_time') ?><br />
	</td>
	</tr> 
    </table> 
    </fieldset> 

    <fieldset class="options"> 
    <legend><?php _e('Miscellaneous') ?></legend> 
    <table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
	<tr valign="top"> 
	<th width="33%" scope="row"><?php _e('Picture post category:') ?></th> 
	<td>
	<?php number_option('pp_image_cat') ?><br />
	</td>
	</tr>
	<tr valign="top">
	<th scope="row"><?php _e('Protect images:') ?> </th>
	<td>
	<?php checkbox_option('pp_protect_images') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Add header for PictPress CSS file:') ?></th>
	<td>
	<?php checkbox_option('pp_use_css') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Silent:') ?> </th>
	<td>
	<?php checkbox_option('pp_silent') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Rebuild:') ?></th>
	<td>
	<?php checkbox_option('pp_rebuild') ?><br />
	</td>
	</tr> 
	<tr valign="top">
	<th scope="row"><?php _e('Uninstall:') ?> </th>
	<td>
	<?php checkbox_option('pp_uninstall') ?><br />
	</td>
	</tr> 
    </table> 
    </fieldset> 

    <p class="submit">
    <input type="submit" name="Submit" value="<?php _e('Update Options') ?>" />
    </p>
</form> 
</div> 
