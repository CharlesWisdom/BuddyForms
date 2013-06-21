<?php

function buddyforms_wp_before_admin_bar_render(){
	global $wp_admin_bar, $buddyforms;

	foreach ($buddyforms['buddyforms'] as $key => $buddyform) {
		
		if(isset($buddyform['admin_bar'][0])){
		
			$wp_admin_bar->add_menu( array(
				'parent' 	=> 'my-account',
				'id'		=> 'my-account-'.$buddyform['slug'],
				'title'		=> __($buddyform['name'], 'buddypress'),
				'href'		=> trailingslashit(bp_loggedin_user_domain() . $buddyform['slug'])
			));
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'my-account-'.$buddyform['slug'],
				'id'		=> 'my-account-'.$buddyform['slug'].'-view',
				'title'		=> __('View my '.$buddyform['name'],'buddypress'),
				'href'		=> trailingslashit(bp_loggedin_user_domain() . $buddyform['slug'])
			)); 

			 $wp_admin_bar->add_menu( array(
				'parent'	=> 'my-account-'.$buddyform['slug'],
				'id'		=> 'my-account-'.$buddyform['slug'].'-new',
				'title'		=> __('New '.$buddyform['singular_name'],'buddypress'),
				'href'		=> trailingslashit(bp_loggedin_user_domain() . $buddyform['slug']).'/create'
			));  

		}
	}
}
add_action('wp_before_admin_bar_render', 'buddyforms_wp_before_admin_bar_render',1,2);



// Register a URL that will set this variable to true
add_action( 'init', 'buddyforms_edit_init' );
function buddyforms_edit_init() {
    add_rewrite_rule( 'buddyform_edit', 'index.php?buddyform_edit=true' );
}

// WordPress has a whitelist of variables it allows, so we must put it on that list
add_action( 'query_vars', 'buddyforms_edit_query_vars' );
function buddyforms_edit_query_vars( $query_vars ){
    $query_vars[] = 'buddyform_edit';
    return $query_vars;
}

// If this is done, we can access it later
// This example checks very early in the process:
// If the variable is set, we include our page and stop execution after it
add_action( 'parse_request', 'buddyforms_edit_parse_request' );
function buddyforms_edit_parse_request( &$wp ){
    if ( array_key_exists( 'buddyform_edit', $wp->query_vars ) ) {
        	get_template_part('header');
			do_shortcode('[buddyforms_form]');
			get_template_part('footer');
        exit();
    }
}

// Add a button to the content editor, next to the media button 
// This button will show a popup that contains inline content 
add_action('media_buttons_context', 'add_my_custom_button');

// Action to add a custom button to the content editor
function add_my_custom_button($context) {
  if (!is_admin())
  	return $context;
  
  // Path to my icon
  $img = plugins_url( 'admin/img/icon-buddyformsc-16.png' , __FILE__ );
  
  // The ID of the container I want to show in the popup
  $container_id = 'popup_container';
  
  // Our popup's title
  $title = 'BuddyForms Shortcode Generator!';

  // Append the icon <a href="#" class="button insert-media add_media" data-editor="content" title="Add Media"><span class="wp-media-buttons-icon"></span> Add Media</a>
  $context .= "<a class='button thickbox' data-editor='content'  title='{$title}'
    href='#TB_inline?width=400&inlineId={$container_id}'>
    <img src='{$img}' /></span> Add Form</a>";
  
  return $context;
}


// Add some content to the bottom of the page for the BuddyForms Shortcodes
// This will be shown in the thickbox of the post edit screen
add_action('admin_footer', 'add_inline_popup_content');
function add_inline_popup_content() {
global $buddyforms;
	if (!is_admin())
		return; ?>
		
	<div id="popup_container" style="display:none;">
	<h2></h2>
	<?php 
  
  	// Get all post types
    $args=array(
		'public' => true,
		'show_ui' => true
    ); 
    $output = 'names'; // names or objects, note names is the default
    $operator = 'and'; // 'and' or 'or'
    $post_types = get_post_types($args,$output,$operator); 
   	$post_types_none['none'] = 'none';
	$post_types = array_merge($post_types_none,$post_types);
	
  
  	$form = new Form("buddyforms_add_form");
	$form->configure(array(
		"prevent" => array("bootstrap", "jQuery"),
		"action" => $_SERVER['REQUEST_URI'],
		"view" => new View_Inline
	));
	$the_forms[] = 'Select the form to use';
	
	foreach ($buddyforms['buddyforms'] as $key => $buddyform) {
		$the_forms[] = $buddyform['slug'];
	}
	$form->addElement( new Element_Select("Select the form to use", "buddyforms_add_form", $the_forms, array('class' => 'buddyforms_add_form')));
	$form->addElement( new Element_Select("Select the post type", "buddyforms_posttype", $post_types, array('class' => 'buddyforms_posttype')));
	$form->render();
  ?>
  <a href="#" class="buddyforms-button-insert">Insert into page</a>
</div>
<?php
}

add_action('admin_footer',  'add_mce_popup');

function add_mce_popup(){ ?>
   <script>

jQuery(document).ready(function (){
    jQuery('.buddyforms-button-insert').on('click',function(event){  
    	var form = jQuery('.buddyforms_add_form').val();
    	var posttype = jQuery('.buddyforms_posttype').val();
		window.send_to_editor('[buddyforms_form form_slug="'+form +'" post_type="'+posttype+'"]');
        });
});

</script>
<?php
}


// add_action( 'wp_ajax_buddyforms_form_ajax', 'buddyforms_form_ajax' );
// add_action( 'wp_ajax_nopriv_buddyforms_form_ajax', 'buddyforms_form_ajax' );
function buddyforms_form_ajax() {
	
	$args = array(
		'posttype' => 'post',
		'the_post' => 0,
		'post_id' => $_POST['post_id']
		);
		
	buddyforms_create_edit_form( $args ); 
	die();
}

/**
 * If single and if the post type is selected for BuddyPress and if there is post meta to display. 
 * Hook the post meta to the right places.
 * 
 * This function is an example how you can hook fields into templates in your BuddyForms extension
 * of course you can also use get_post_meta(sanitize_title('name'))
 *
 * @package BuddyForms
 * @since 0.2-beta
*/
function buddyforms_form_display_element_frontend(){
	global $buddyforms, $post, $bp;
	
	if(!is_single($post))
		return;
					
	if (!isset($buddyforms['buddyforms']))
		return;

	$post_type = get_post_type($post);
	
	foreach ($buddyforms['buddyforms'] as $key => $buddyform) {
		if($buddyforms['buddyforms'][$key]['post_type'] != 'none' &&  $buddyforms['buddyforms'][$key]['post_type'] == $post_type)
			$form = $buddyforms['buddyforms'][$key]['slug'];
	}
	
	if(!isset($form))
		return;
		
	if (!empty($buddyforms['buddyforms'][$form]['form_fields'])) {
			
		foreach ($buddyforms['buddyforms'][$form]['form_fields'] as $key => $customfield) :
			if(isset($customfield['slug'])){
				$slug = $customfield['slug'];
			} else {
				$slug = sanitize_title($customfield['name']);
			}
			
			$customfield_value = get_post_meta($post->ID, $slug, true);
			
			if ($customfield_value != '' && $customfield['display'] != 'no') :
				
				$post_meta_tmp = '<div class="post_meta ' . $slug . '">';
				
				if($customfield['display_name'])
					$post_meta_tmp .= '<lable>' . $customfield['name'] . '</lable>';
				
				
				$meta_tmp = "<p>". $customfield_value ."</p>";
				
				if(is_array($customfield_value))
					$meta_tmp = "<p>". implode(',' , $customfield_value)."</p>";
			
				switch ($customfield['type']) {
					case 'Taxonomy':
						$meta_tmp = "<p>". get_the_term_list( $post->ID, $customfield['taxonomy'] )."</p>";
						break;
					case 'Link':
						$meta_tmp = "<p><a href='" . $customfield_value . "' " . $customfield['name'] . ">" . $customfield_value . " </a></p>";
						break;
					default:
						 apply_filters('buddyforms_form_element_display_frontend',$customfield,$post_type);
						break;
				}
				
				$post_meta_tmp .= $meta_tmp;
				
				$post_meta_tmp .= '</div>';
				apply_filters('buddyforms_form_element_display_frontend_before_hook',$post_meta_tmp);
				add_action($customfield['display'], create_function('', 'echo "' . addcslashes($post_meta_tmp, '"') . '";'));
			endif;
		endforeach;
	}
}
add_action('wp_head','buddyforms_form_display_element_frontend');

/**
 * Get the BuddyForms template directory.
 *
 * @package BuddyForms
 * @since 0.1 beta
 *
 * @uses apply_filters()
 * @return string
 */
function buddyforms_get_template_directory() {
	return apply_filters('buddyforms_get_template_directory', constant('BUDDYFORMS_TEMPLATE_PATH'));
}

/**
 * Locate a template
 *
 * @package BuddyForms
 * @since 0.1 beta
 */
function buddyforms_locate_template($file) {
	if (locate_template(array($file), false)) {
		locate_template(array($file), true);
	} else {
		include (BUDDYFORMS_TEMPLATE_PATH . $file);
	}
}
?>