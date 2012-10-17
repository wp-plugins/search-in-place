<?php

class CodePeopleSearchInPlace {
		
	
	private $text_domain = 'codepeople_search_in_place';
	private $javascriptVariable;
	
	/*
		Load the language file and initialize the javascript object to pass to the client side
	*/
	function init(){
		// I18n
		load_plugin_textdomain($this->text_domain, false, dirname(plugin_basename(__FILE__)) . '/../languages/');
		$this->javascriptVariables = array(
									'more'  => __('More Results', $this->text_domain),
									'empty' => __('0 results', $this->text_domain),
									'char_number' => get_option('search_in_place_minimum_char_number'),
									'root'	 => get_site_url()
							);
		
		// Fake variables to allow the translation for Poedit application
		$a = __('post', $this->text_domain); 		
		$a = __('page', $this->text_domain); 		
		$a = __('attachment', $this->text_domain);
	} // End init
	
	
	public function javascriptVariables(){
		return $this->javascriptVariables;
	} // End javascritpVariables
	
	/*
		The most important method for search process, populate the list of results.
	*/
	public function populate() {
		global $wp_query, $wpdb;
		
		$counter = 0;
		$limit = get_option('search_in_place_number_of_posts'); // Number of results to display
		$post_list = array();
		
		$wp_query = new WP_Query();
        
		// Get the posts and pages with the search terms
		$s = $_GET['s'];
		$params = array(
          's' => $s,
          'showposts' => $limit,
          'post_type' => 'any',
          'post_status' => 'publish',
        );
		$wp_query->query($params);
		
		// Get the attachments that include the search terms
		$posts = array_merge($wp_query->posts, $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type='attachment' AND post_status='inherit' AND (post_title LIKE '$s%' OR post_content LIKE '$s%' OR post_name LIKE '$s%') AND post_parent IN (SELECT ID FROM $wpdb->posts WHERE post_status='publish') LIMIT $limit", OBJECT));
		
		foreach($posts as $result){
			$counter++;
			
			if($counter > $limit) // Check the limit of search results
				break;
				
			$obj = new stdClass();
			
			// Include the author in search results
			if(get_option('search_in_place_display_author') == 1){
				$author = get_userdata($result->post_author);
				$obj->author = $author->display_name;
			}	
			
			// The link to the item is required
			$obj->link = get_permalink($result->ID);
			
			// Include the thumbnail in search results
			if(get_option('search_in_place_display_thumbnail')){
				if ( function_exists('has_post_thumbnail') && has_post_thumbnail($result->ID) ) {
					// If post thumbnail is used
					$obj->thumbnail = wp_get_attachment_url(get_post_thumbnail_id($result->ID, 'thumbnail'));
				}elseif(function_exists('get_post_image_id')) {
					// Support for WP 2.9 post thumbnails
					$imgID = get_post_image_id($result->ID);
					$img = wp_get_attachment_image_src($imgID, apply_filters('post_image_size', 'thumbnail'));
					$obj->thumbnail = $img[0];
				}
				else {
					// If not post thumbnail, grab the first image from the post
					// Get images for this post
					$imgArr =& get_children('post_type=attachment&post_mime_type=image&post_parent=' . $result->ID );
					
					// If images exist for this page
					if($imgArr) {
						$flag = PHP_INT_MAX;
						
						foreach($imgArr as $img) {
							if($img->menu_order < $flag){
								$flag = $img->menu_order;
								$img_selected = $img;	
							}
						}
						$obj->thumbnail = wp_get_attachment_thumb_url($img_selected->ID);
					}
				}
			}
			
			// Include a post summary in search results, the summary is limited to the number of letters declared in configuration
			if(get_option('search_in_place_display_summary')){
				$length = get_option('search_in_place_summary_char_number');
				if(!empty($result->post_excerpt)){
					$resume = substr(apply_filters("localization", $result->post_excerpt), 0, $length);
				}else{
					$c = strip_tags(apply_filters("localization", $result->post_content));
					$l = strlen($c);
					$p = strpos(strtolower($c), strtolower($s));
					
					$p = ($p !== false && $p-$length/2 > 0) ? $p-$length/2 : 0;
					
					// Start the summary from the begining of word
					if($p > 0){
						if($c[$p] == ' '){
							$p++;
						}elseif($c[$p-1] !== ' '){
							$k = strrpos($c, " ", -1*($l-$p));
							$k = ($k < 0) ? 0 : $k+1;
							$length += $p-$k;
							$p = $k;
						}	
					}
					$resume = substr($c, $p, $length);
				}
				
				// Set the search terms in bold
				$obj->resume = preg_replace('/('.$s.')/i', '<strong>$1</strong>', $resume).'[...]';
			}	
			
			// Include the publication date in search results
			if(get_option('search_in_place_display_date')){
				$obj->date = date_i18n(get_option('search_in_place_date_format'), strtotime($result->post_date));
			}	
			
			// The post title is a required field
			$obj->title = apply_filters("localization", $result->post_title); 
			
			$type = __($result->post_type, $this->text_domain);
			if(!isset($post_list[$type])){
				$post_list[$type] = array();
			}
			$post_list[$type][] = $obj;
			
		}
		
		print json_encode($post_list);die;
	
	} // End populate
	
	/*
		Set a link to plugin settings
	*/
	function settingsLink($links) { 
		$settings_link = '<a href="options-general.php?page=codepeople_search_in_place.php">'.__('Settings').'</a>'; 
		array_unshift($links, $settings_link); 
		return $links; 
	} // End settingsLink
	
	/*
		Set a link to contact page
	*/
	function customizationLink($links) { 
		$settings_link = '<a href="http://wordpress.dwbooster.com/contact-us" target="_blank">'.__('Request custom changes').'</a>'; 
		array_unshift($links, $settings_link); 
		return $links; 
	} // End settingsLink
	
	/**
		Print out the admin page
	*/
	function printAdminPage(){
		if(isset($_POST['search_in_place_submit'])){
			
			echo '<div class="updated"><p><strong>'.__("Settings Updated").'</strong></div>';
			
			$_POST['number_of_posts'] = $_POST['number_of_posts']*1;
			$_POST['minimum_char_number'] = $_POST['minimum_char_number']*1;
			$_POST['summary_char_number'] = $_POST['summary_char_number']*1;
			
			$search_in_place_number_of_posts = (!empty($_POST['number_of_posts']) && is_int($_POST['number_of_posts']) && $_POST['number_of_posts'] > 0) ? $_POST['number_of_posts'] : 10;
			$search_in_place_minimum_char_number = (!empty($_POST['minimum_char_number']) && is_int($_POST['minimum_char_number']) && $_POST['minimum_char_number'] > 0) ? $_POST['minimum_char_number'] : 3;
			$search_in_place_summary_char_number = (!empty($_POST['summary_char_number']) && is_int($_POST['summary_char_number']) && $_POST['summary_char_number'] >= 0) ? $_POST['summary_char_number'] : 20;
			$search_in_place_date_format = $_POST['date_format'];
			$search_in_place_display_thumbnail = (!empty($_POST['thumbnail'])) ? $_POST['thumbnail'] : 0;
			$search_in_place_display_date = (!empty($_POST['date'])) ? $_POST['date'] : 0;
			$search_in_place_display_summary = (!empty($_POST['summary'])) ? $_POST['summary'] : 0;
			$search_in_place_display_author = (!empty($_POST['author'])) ? $_POST['author'] : 0;
			
			
			update_option('search_in_place_number_of_posts', $search_in_place_number_of_posts);
			update_option('search_in_place_minimum_char_number', $search_in_place_minimum_char_number);
			update_option('search_in_place_summary_char_number', $search_in_place_summary_char_number);
			update_option('search_in_place_date_format', $search_in_place_date_format);
			update_option('search_in_place_display_thumbnail', $search_in_place_display_thumbnail);
			update_option('search_in_place_display_date', $search_in_place_display_date);
			update_option('search_in_place_display_summary', $search_in_place_display_summary);
			update_option('search_in_place_display_author', $search_in_place_display_author);
			
			
		}else{
			$search_in_place_number_of_posts = get_option('search_in_place_number_of_posts');
			$search_in_place_minimum_char_number = get_option('search_in_place_minimum_char_number');
			$search_in_place_summary_char_number = get_option('search_in_place_summary_char_number');
			$search_in_place_date_format = get_option('search_in_place_date_format');
			$search_in_place_display_thumbnail = get_option('search_in_place_display_thumbnail');
			$search_in_place_display_date = get_option('search_in_place_display_date');
			$search_in_place_display_summary = get_option('search_in_place_display_summary');
			$search_in_place_display_author = get_option('search_in_place_display_author');
		}
		
		echo '
			<div class="wrap">
				<form method="post" action="'.$_SERVER['REQUEST_URI'].'">
					<h2>Search In Place</h2>
					<div>'.__('For more information go to the <a href="http://wordpress.dwbooster.com/content-tools/search-in-place" target="_blank">Search in Place</a> plugin page').'</div>
					
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="number_of_posts">'.__('Enter the number of posts to display', $this->text_domain).'</label>
								</th>
								<td>
									<input type="text" id="number_of_posts" name="number_of_posts" value="'.$search_in_place_number_of_posts.'" />
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="minimum_char_number">'.__('Enter the minimum of characters number for start the search', $this->text_domain).'</label>
								</th>
								<td>
									<input type="text" id="minimum_char_number" name="minimum_char_number" value="'.$search_in_place_minimum_char_number.'" />
								</td>
							</tr>
						</tbody>
					</table>
					<h3>'.__('Elements to display', $this->text_domain).'</h3>
					<table class="form-table">	
						<tbody>
							<tr valign="top">
								<td>
									<input type="checkbox" checked disabled name="title" id="title"> '.__('Post title', $this->text_domain).' <input type="checkbox" name="thumbnail" id="thumbnail" value="1" '.(($search_in_place_display_thumbnail == 1) ? 'checked' : '').' /> '.__('Post thumbnail', $this->text_domain).' <input type="checkbox" name="author" value="1" id="author" '.(($search_in_place_display_author == 1) ? 'checked' : '').' /> '.__('Post author', $this->text_domain).' <input type="checkbox" name="date" id="date" value="1" '.(($search_in_place_display_date == 1) ? 'checked' : '').' /> '.__('Post date', $this->text_domain).' <input type="checkbox" name="summary" id="summary" value="1" '.(($search_in_place_display_summary == 1) ? 'checked' : '').' /> '.__('Post summary', $this->text_domain).'
								</td>
							</tr>
						</tbody>
					</table>	
					<table class="form-table">	
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="date_format">'.__("Select the date format", $this->text_domain).'</label>
								</th>
								<td>
									<select name="date_format" id="date_format" style="width:135px;">
										<option value="Y-m-d" '.(($search_in_place_date_format == 'Y-m-d') ? 'selected' : '').'>yyyy-mm-dd</option>
										<option value="Y-d-m" '.(($search_in_place_date_format == 'Y-d-m') ? 'selected' : '').'>yyyy-dd-mm</option>
										<option value="m-d-Y" '.(($search_in_place_date_format == 'm-d-Y') ? 'selected' : '').'>mm-dd-yyyy</option>
										<option value="d-m-Y" '.(($search_in_place_date_format == 'd-m-Y') ? 'selected' : '').'>dd-mm-yyyy</option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="summary_char_number">'.__("Enter the number of characters for posts' summaries", $this->text_domain).'</label>
								</th>
								<td>
									<input type="text" id="summary_char_number" name="summary_char_number" value="'.$search_in_place_summary_char_number.'" />
								</td>
							</tr>
						</tbody>
					</table>
					<h3>'.__('The next options are only available for the advanced version of Search in Place', $this->text_domain).'. <a href="http://wordpress.dwbooster.com/content-tools/search-in-place" target="_blank">'.__('Here').'</a></h3>	
					<h3 style="color:#DDD;">'.__('In Search Page', $this->text_domain).'</h3>
					<table class="form-table" style="color:#DDD;">	
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="highlight" style="color:#DDD;">'.__("Highlight the terms in result", $this->text_domain).'</label>
								</th>
								<td>
									<input type="checkbox" name="highlight" id="highlight" value="1" disabled />
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="mark_post_type" style="color:#DDD;">'.__("Identify the posts type in search result", $this->text_domain).'</label>
								</th>
								<td>
									<input type="checkbox" name="mark_post_type" id="mark_post_type" value="1" disabled />
								</td>
							</tr>
						</tbody>
					</table>
					<input type="hidden" name="search_in_place_submit" value="ok" />
					<div class="submit"><input type="submit" class="button-primary" value="'.__('Update Settings', $this->text_domain).'" /></div>
				</form>
			</div>
		';		
	} // End printAdminPage
	
	/*
		Set configuration variables
	*/
	function activePlugin(){
		update_option('search_in_place_number_of_posts', 10);
		update_option('search_in_place_minimum_char_number', 3);
		update_option('search_in_place_summary_char_number', 20);
		update_option('search_in_place_display_thumbnail', 1);
		update_option('search_in_place_display_date', 1);
		update_option('search_in_place_display_summary', 1);
		update_option('search_in_place_display_author', 1);
	} // End activePlugin
	
	/*
		Remove configuration variables
	*/
	function deactivePlugin() {
		delete_option('search_in_place_number_of_posts');
		delete_option('search_in_place_minimum_char_number');
		delete_option('search_in_place_summary_char_number');
		delete_option('search_in_place_display_thumbnail');
		delete_option('search_in_place_display_date');
		delete_option('search_in_place_display_summary');
		delete_option('search_in_place_display_author');
	} // End deactivePlugin
	
} // End SearchInPlace
?>