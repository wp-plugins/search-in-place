<?php 
/*  
Plugin Name: Search In Place
Plugin URI: http://wordpress.dwbooster.com/content-tools/search-in-place
Version: 1.0.3
Author: <a href="http://www.codepeople.net">CodePeople</a>
Description: Search in Place improves blog search by displaying query results in real time. Search in place displays a list with results dynamically as you enter the search criteria. Search in place groups search results by their type, labeling them as post, page, or attachment. To get started: 1) Click the "Activate" link to the left of this description.
*/

include 'php/searchinplace.clss.php';

	//Initialize the admin panel 
	if (!function_exists("CodePeopleSearchInPlace_admin")) { 
		function CodePeopleSearchInPlace_admin() { 
			global $codepeople_search_in_place_obj; 
			if (!isset($codepeople_search_in_place_obj)) { 
				return; 
			} 
			if (function_exists('add_options_page')) { 
				add_options_page('Search In Place', 'Search In Place', 9, basename(__FILE__), array(&$codepeople_search_in_place_obj, 'printAdminPage')); 
			} 
		}    
	}
	
	// Initialize the public website code
	if(!function_exists("CodePeopleSearchInPlace")){	
		function CodePeopleSearchInPlace(){
			global $codepeople_search_in_place_obj;
			
			if (is_admin ())
				return false;

			wp_enqueue_style('codepeople-search-in-place-style', plugin_dir_url(__FILE__).'css/codepeople_shearch_in_place.css');
			wp_enqueue_script('codepeople-search-in-place', plugin_dir_url(__FILE__).'js/codepeople_shearch_in_place.js', array('jquery'));
			wp_localize_script('codepeople-search-in-place', 'codepeople_search_in_place', $codepeople_search_in_place_obj->javascriptVariables());
		}
	}	

$codepeople_search_in_place_obj = new CodePeopleSearchInPlace();
$codepeople_search_in_place_obj->init();

// Plugin activation and deactivation
register_activation_hook(__FILE__, array(&$codepeople_search_in_place_obj, 'activePlugin'));
register_deactivation_hook(__FILE__, array(&$codepeople_search_in_place_obj, 'deactivePlugin'));

$plugin = plugin_basename(__FILE__);
add_filter('plugin_action_links_'.$plugin, array(&$codepeople_search_in_place_obj, 'customizationLink'));
add_filter('plugin_action_links_'.$plugin, array(&$codepeople_search_in_place_obj, 'settingsLink'));

add_action('init', 'CodePeopleSearchInPlace');
add_action('admin_menu', 'CodePeopleSearchInPlace_admin');
add_action('wp_ajax_nopriv_search_in_place', array(&$codepeople_search_in_place_obj, 'populate'));
add_action('wp_ajax_search_in_place', array(&$codepeople_search_in_place_obj, 'populate'));
?>