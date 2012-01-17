<?php

#-----------------------------------------------------------------
# Theme Variables
#-----------------------------------------------------------------

// Retrieve theme data from the database to store in an array
//................................................................


if ( ! function_exists( '_theme_data_lookup' ) ) :

	function _theme_data_lookup() {
		global $theme_settings, $design_settings, $theme_data_array;

		// Get settings values (saved and DB)
		$theme_data = $theme_settings->get_objects(array('_plugin_saved', '_plugin'));
		
		// Get design values (saved and DB)
		$design_saved_data = $design_settings->get_objects(array('_plugin_saved'));
		$design_db_data = $design_settings->get_objects(array('_plugin'));
		
		/*
			Design options have several sections, each must be merged individually
			
			Note: using the array casting below may cause some empty values if nothing exists in the item being cast. This shouldn't be
			and issue here since blanks will be ignored. This would only matter if we were populating a field for the admin as it would
			leave an empty option in the field.
		*/
		$design_data['design_setting'] = array_merge((array)$design_saved_data['design_setting'], (array)$design_db_data['design_setting']);
		$design_data['slideshows'] = array_merge((array)$design_saved_data['slideshows'], (array)$design_db_data['slideshows']);
		$design_data['top_graphics'] = array_merge((array)$design_saved_data['top_graphics'], (array)$design_db_data['top_graphics']);
		$design_data['sidebars'] = array_merge((array)$design_saved_data['sidebars'], (array)$design_db_data['sidebars']);
		$design_data['page_headers'] = array_merge((array)$design_saved_data['page_headers'], (array)$design_db_data['page_headers']);
		$design_data['page_footers'] = array_merge((array)$design_saved_data['page_footers'], (array)$design_db_data['page_footers']);
		$design_data['layouts'] = array_merge((array)$design_saved_data['layouts'], (array)$design_db_data['layouts']);

		// combine all option to single array
		$data = array_merge($theme_data, $design_data);
		
		//return $data;
		$theme_data_array = $data;
	}

endif;

// add data lookup to WP init function
add_action('init', '_theme_data_lookup');


// Get theme variables, default action is echo 
//................................................................

//	$option = the option name in the database (can be comma separated array path)
// 	$echo = print the return value (true, false). Default: true
// 	$default = value returned is no value exists in database
if ( ! function_exists( 'theme_var' ) ) :

	function theme_var($option, $act = 'echo', $default = '') {
		global $theme_data_array;
		
		// get the theme data
		$data = $theme_data_array;
			
		// deal with comma separated requests			
		if (strpos($option, ',')) {
			$c = explode(',', str_replace(' ', '', $option));
			foreach($c as $d) $s[] = $d;
		} else {
			$s[] = $option;
		}
		
		// Iterate through the data
		$subdata = $data;
		foreach ($s as $key) {
			$subdata = $subdata[$key];
		}
		if (!is_array($subdata)) $subdata = stripslashes($subdata);

		// return or echo
		switch ($act){
			case "return":
				return $subdata;
				break;
			default:
				echo $subdata;
				break;
		}
	}
	
endif;


// Shortcut for options without echo 
//................................................................

if ( ! function_exists( 'get_theme_var' ) ) :

	function get_theme_var($option, $default = '') {
		return theme_var($option, 'return', $default);
	}
	
endif;


#-----------------------------------------------------------------
# Excerpt Functions
#-----------------------------------------------------------------

// Replace "[...]" in excerpt with "..."
//................................................................
function new_excerpt_more($excerpt) {
	return str_replace('[...]', '...', $excerpt);
}
add_filter('wp_trim_excerpt', 'new_excerpt_more');


// Modify the WordPress excerpt length
//................................................................
//
// We set this pretty high because our "customExcerpt" function 
// uses the WordPress excerpt content as it's source of text
// because it's already stripped of HTML, images and such. 
//
//................................................................
function new_excerpt_length($length) {
	return 250;
}
add_filter('excerpt_length', 'new_excerpt_length');


// Custom Length Excerpts
//................................................................
// 
// Usage:
// echo customExcerpt(get_the_content(), 30);
// echo customExcerpt(get_the_content(), 50);
// echo customExcerpt($your_content, 30);
//
//................................................................
function customExcerpt($excerpt = '', $excerpt_length = 50, $tags = '', $trailing = '...') {
	global $post;
	
	if (has_excerpt()) {
		// see if there is a user created excerpt, if so we use that without any trimming
		return  get_the_excerpt();
	} else {
		// otherwise make a custom excerpt
		$string_check = explode(' ', $excerpt);
		if (count($string_check, COUNT_RECURSIVE) > $excerpt_length) {
			$excerpt = strip_shortcodes( $excerpt );
			$new_excerpt_words = explode(' ', $excerpt, $excerpt_length+1); 
			array_pop($new_excerpt_words);
			$excerpt_text = implode(' ', $new_excerpt_words); 
			$temp_content = strip_tags($excerpt_text, $tags);
			$short_content = preg_replace('`\[[^\]]*\]`','',$temp_content);
			$short_content .= $trailing;
			
			return $short_content;
		} else {
			// no trimming needed, excerpt is too short.
			return $excerpt;
		}
	}
} 


#-----------------------------------------------------------------
# Content Functions
#-----------------------------------------------------------------

// Prepare content for output
//................................................................
if ( ! function_exists( 'prep_content' ) ) :
	
	function prep_content($content, $allowHTML = 1, $allowShortcodes = 0) {
		
		if ($allowHTML) $content = html_entity_decode($content, ENT_QUOTES);
		if ($allowShortcodes) $content = do_shortcode($content);
		
		return $content;
	}

endif;



#-----------------------------------------------------------------
# Menus
#-----------------------------------------------------------------

// Default theme menus
if ( ! function_exists( 'register_theme_menus' ) ) :
	
	function register_theme_menus() {
		global $themeMenus;
		if ( function_exists( 'register_nav_menus' ) ) {	// feature detect instead of version checking
			register_nav_menus( $themeMenus );
		}
	}
	add_action( 'init', 'register_theme_menus' );

endif;

// Menu fallback function. Displays message for menus not set under "Menu > Theme Locations"
if ( ! function_exists( 'no_menu_set' ) ) :

	function no_menu_set($info) {
		global $themeMenus, $theHeader;
		// Display error message if menu location isn't set
		printf(
			'<small style="line-height:2.7;background:#D00;color:#fff;padding:2px 5px;">'. __('Set %s in "Appearance > Menus > Theme Locations"', THEME_NAME) .'</small>', 
			'<strong style="text-decoration:underline;">'.$themeMenus[$info['theme_location']].'</strong>'
		);
	}

endif;



#-----------------------------------------------------------------
# Stylesheet queue
#-----------------------------------------------------------------

/*	Adds the ability to queue style sheets similar to the method
*	used by WordPress. Makes it easier to include CSS files 
*	organized together and load them as needed as opposed to 
*	"wp_enqueue_style" which mixes with all plugins.
*/

// Add CSS files to queue
//................................................................

if ( ! function_exists( 'theme_register_css' ) ) :
function theme_register_css( $handle, $src, $priority = 10, $id = false, $class = false ) {
	global $enqueue_theme_css;
	// add new file to CSS queue
	$enqueue_theme_css[$handle] = array( 'src' => $src, 'priority' => $priority, 'id' => $id, 'class' => $class);
}
endif;

// Print CSS files
if ( ! function_exists( 'print_theme_style_sheets' ) ) :
	function print_theme_style_sheets() {
		global $enqueue_theme_css, $skinCssPosition;
		
		$skin = (!$skinCssPosition) ? 'last' : $skinCssPosition; // skin CSS file added: 'first', 'last' or 'none' to exclude based on global variable
		
		$styleSheet = $enqueue_theme_css;
		$cssFiles = array(); // new array to move values to before sorting
	
		// add skin CSS file (set to add before other CSS files)
		if ($skin == 'first') {
			theme_skin();
		}
	
		// put all the items in the new array with ID based on priority
		if (is_array($styleSheet) && count($styleSheet) > 0) {
			$n = 0;
			foreach ((array) $styleSheet as $handle => $css) {
				$id = ($id) ? ' id="'.$css['id'].'"' : '';
				$class = ($class) ? ' class="'.$css['class'].'"' : '';
				$cssFiles[$css['priority'].'.'.$n] = '<link rel="stylesheet" type="text/css" href="'. $css['src'] .'"'. $id . $class .' />';
				$n++;
			}
			ksort($cssFiles); // sort the items for priority
			
			// print each CSS file
			foreach ($cssFiles as $style_sheet) {
				echo $style_sheet ."\n";
			}
		}

		// add skin CSS file last (default)
		if ($skin == 'last') {
			theme_skin();
		}
	}
	
	// Add print function to hook 
	add_action('wp_head','print_theme_style_sheets', 1);
endif;


#-----------------------------------------------------------------
# Miscelanious
#-----------------------------------------------------------------

// Test for a script already enqueue 
//................................................................
if ( ! function_exists( 'is_enqueued_script' ) ) :
	
	function is_enqueued_script( $script ) {
		return isset( $GLOBALS['wp_scripts']->registered[ $script ] );
	}
	
endif;



// Add gravatars to WP admin options 
//................................................................
if ( ! function_exists( 'theme_gravatar' ) ) :
	
	function theme_gravatar( $avatar_defaults ) {
		
		$themeAvatar_1 = THEME_URL . 'assets/images/icons/avatar-1.png';
		$avatar_defaults[$themeAvatar_1] = 'Theme Avatar 1';
		$themeAvatar_2 = THEME_URL . 'assets/images/icons/avatar-2.png';
		$avatar_defaults[$themeAvatar_2] = 'Theme Avatar 2';
		return $avatar_defaults;
		
	}
	
	add_filter( 'avatar_defaults', 'theme_gravatar' );
	
endif;


// Simple string encode/decode functions
//................................................................

$strEncOffset = 14; // set to a unique number for offset

if ( ! function_exists( 'strEnc' ) ) :
	function strEnc($s) {
		global $strEncOffset;
		
		for( $i = 0; $i < strlen($s); $i++ )
			$r[] = ord($s[$i]) + $strEncOffset;
		return implode('.', $r);
	}
endif;

if ( ! function_exists( 'strDec' ) ) :
	function strDec($s) {
		global $strEncOffset;
		
		$s = explode(".", $s);
		for( $i = 0; $i < count($s); $i++ )
			$s[$i] = chr($s[$i] - $strEncOffset);
		return implode('', $s);
	}
endif;



// WordPress Auto Paragraphs (wpautop)
//................................................................


if ( ! function_exists( 'wpautop_control_filter' ) ) :
	function wpautop_control_filter($content) {
		global $post;
		
		// Get wpautop setting
		$remove_filter = wpautop_filter();
		
		// turn on/off
		if ( $remove_filter ) {
		  remove_filter('the_content', 'wpautop');
		  remove_filter('the_excerpt', 'wpautop');
		}
		
		return $content;
	}
	
	add_filter('the_content', 'wpautop_control_filter', 9);
endif;

if ( ! function_exists( 'wpautop_filter' ) ) :
	function wpautop_filter($id = '') {
		global $post;
		
		// Get the page/post meta setting
		$post_wpautop_value = strtolower(get_meta('wpautop', $id)); //get_post_meta($post->ID, 'wpautop', true);
		
		// Global default setting
		$default_wpautop_value = get_theme_var('options,wpautop',1); //intval( get_option('wpautop_on_by_default', '1') );
		
		$remove_filter = false; // to match the WP default
		
		// check if set at page level
		if ( in_array($post_wpautop_value, array('true', 'on', 'yes')) ) {
			$remove_filter = false;
		} elseif ( in_array($post_wpautop_value, array('false', 'off', 'no')) ) {
			$remove_filter = true;
		} else {
			// page/post level setting not found, use global setting
			$remove_filter = ! $default_wpautop_value;
		}
		
		return $remove_filter;
	}
endif;


// Exclude posts and pages from search
//................................................................
if (!is_admin()) {
	
	// filter search results
	if ( ! function_exists( 'filter_search_exclude' ) ) :
		function filter_search_exclude($where = '') {
			global $wpdb;
			
			// Meta values to look up
			$meta_key = 'search-exclude';
			$meta_value = '1';
			
			// Query DB for meta setting 'search-exclude = "Yes"'
			$search_exclude_ids = $wpdb->get_col($wpdb->prepare("
			SELECT      post_id
			FROM        $wpdb->postmeta
			WHERE       meta_key = %s
			AND			meta_value = %s
			ORDER BY    post_id ASC",
					 $meta_key,$meta_value)); 
						
			if ( is_search() && $search_exclude_ids) {
				
				$exclude = $search_exclude_ids;
	
				for($x=0; $x < count($exclude); $x++){
				  $where .= " AND ID != ".$exclude[$x];
				}
			}
			return $where;
		}
		add_filter('posts_where', 'filter_search_exclude');
	endif;

}


// Filter WP navigation menus by class name as function
// Run shortcodes for menu titles and URLs for special features
//................................................................
if ( ! function_exists( 'filter_nav_menu_items' ) ) :
	
	// Filter mneus by specific class names used to call a function as the test for include/exclude
	function filter_nav_menu_items($sorted_menu_items, $args){

		foreach ($sorted_menu_items as $nav_item) {	

			// Shortcodes in titles and URLs
			//................................................................
			
				// Shortcodes to item titles
				$nav_item->title = do_shortcode($nav_item->title);
	
				// Shortcodes to URLs
				if (strpos($nav_item->url,"((") && strpos($nav_item->url,"))")) {
					// Change any "((" to "[" and "))" to "]" - WP menus don't allow [ or ] in URL
					$URL = str_replace('((', '[', str_replace('))', ']', $nav_item->url));
					$nav_item->url = do_shortcode($URL);					
				}
			

			// Filter items by class name (to call function)
			//................................................................

				// Check for classes that trigger functions
				for ($i=0; $i<count($nav_item->classes); $i++) {
	
					$item = 'include';
					
					$class = $nav_item->classes[$i];
					
					// Users can spefy to test for a false value such as "if (!is_home())" with a "-" before the class name.
					if (substr($class, 0, 1) == '-') {
						$conditon =  false;
						$class = substr($class, 1, strlen($class)); // get rid of that "-" at the start for the function test.
					} else {
						$conditon =  true;
					}
					
					// All special classes should start with "function-"
					if (substr($class, 0, 9) == 'function-') {
						
						// get rid of the prefix "function-" at the start of the class
						$class = substr($class, 9, strlen($class)); 
	
						// change "is-home" into "is_home" (WP menus won't allow "_" in class names
						$class = str_replace('-', '_', $class);
						
						// See if a function exists by this name
						if ( function_exists($class)) {
							if ( call_user_func($class) != $conditon ) {
								$item = 'exclude';
								break; // go to the next class for this item :)
							}
						}
												
					}
			}
			
			// add the item to the correct list (keep it or exclude it)
			if ($item == 'exclude') {
				$excluded_items[]=$nav_item; 
			} else {
				$included_items[]=$nav_item;
			}

		}
		
		return $included_items;
	
	}
	
	add_filter( 'wp_nav_menu_objects', 'filter_nav_menu_items', 10, 3);

endif;


// Check if BuddyPress is active 
//................................................................

if ( ! function_exists( 'bp_plugin_is_active' ) ) :
	function bp_plugin_is_active() {
		//check if the function "bp_include" exists (this is a shortcut to checking if BP is loaded)
		return ( function_exists( 'bp_include' ) ) ? true : false;
	}
endif;
?>