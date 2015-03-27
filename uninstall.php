<?php 
/*
	Remove configuration variables
*/
function search_in_place_remove_configuration_variables()
{
	delete_option('search_in_place_number_of_posts');
	delete_option('search_in_place_minimum_char_number');
	delete_option('search_in_place_summary_char_number');
	delete_option('search_in_place_display_thumbnail');
	delete_option('search_in_place_display_date');
	delete_option('search_in_place_display_summary');
	delete_option('search_in_place_display_author');
} // End search_in_place_remove_configuration_variables

function search_in_place_deactivePlugin() {
	global $wpdb;
	if (function_exists('is_multisite') && is_multisite()) {
		$old_blog = $wpdb->blogid;
		// Get all blog ids
		$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		foreach ($blogids as $blog_id) {
			switch_to_blog($blog_id);
			search_in_place_remove_configuration_variables();
		}
		switch_to_blog($old_blog);
		return;
	}
	search_in_place_remove_configuration_variables();
	
} // End search_in_place_deactivePlugin
if(WP_UNINSTALL_PLUGIN){
search_in_place_deactivePlugin();
}
?>