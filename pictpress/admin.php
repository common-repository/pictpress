function pp_upload_tabs ($tabs) {
	// 0 => tab display name, 1 => required cap, 2 => function that produces tab content,
	// 3 => total number objects OR array(total, objects per page), 4 => add_query_args
	$tabs['pictpress'] = array('PictPress', 'upload_files', 'pp_upload_tab_pictpress', 0);
	return $tabs;
}
add_filter('wp_upload_tabs', 'pp_upload_tabs');
