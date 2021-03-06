<?php
/**
 * Ajax call back function to add a form element
 *
 * @package BuddyForms
 * @since 0.1-beta
 */
function buddyforms_add_form() {
	global $buddyforms;

	if ( ! is_array( $buddyforms ) ) {
		$buddyforms = Array();
	}

	if ( empty( $_POST['create_new_form_name'] ) ) {
		return;
	}
	if ( empty( $_POST['create_new_form_singular_name'] ) ) {
		return;
	}
	if ( empty( $_POST['create_new_form_attached_page'] ) && empty( $_POST['create_new_page'] ) ) {
		return;
	}
	if ( empty( $_POST['create_new_form_post_type'] ) ) {
		return;
	}

	if ( ! empty( $_POST['create_new_page'] ) ) {
		// Create post object
		$mew_post = array(
			'post_title'   => wp_strip_all_tags( $_POST['create_new_page'] ),
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'page'
		);

		// Insert the post into the database
		$_POST['create_new_form_attached_page'] = wp_insert_post( $mew_post );
	}

	$bf_forms_args = array(
		'post_title'  => $_POST['create_new_form_name'],
		'post_type'   => 'buddyforms',
		'post_status' => 'publish',
	);

	// Insert the new form
	$post_id  = wp_insert_post( $bf_forms_args, true );
	$the_post = get_post( $post_id );

	$options = Array(
		'slug'          => $the_post->post_name,
		'id'            => $the_post->ID,
		'name'          => $_POST['create_new_form_name'],
		'singular_name' => $_POST['create_new_form_singular_name'],
		'attached_page' => $_POST['create_new_form_attached_page'],
		'post_type'     => $_POST['create_new_form_post_type'],
	);

	if ( ! empty( $_POST['create_new_form_status'] ) ) {
		$options = array_merge( $options, Array( 'status' => $_POST['create_new_form_status'] ) );
	}

	if ( ! empty( $_POST['create_new_form_comment_status'] ) ) {
		$options = array_merge( $options, Array( 'comment_status' => $_POST['create_new_form_comment_status'] ) );
	}

	$field_id = $mod5 = substr( md5( time() * rand() ), 0, 10 );

	$options['form_fields'][ $field_id ]['name'] = 'Title';
	$options['form_fields'][ $field_id ]['slug'] = 'editpost_title';
	$options['form_fields'][ $field_id ]['type'] = 'Title';

	$field_id = $mod5 = substr( md5( time() * rand() ), 0, 10 );

	$options['form_fields'][ $field_id ]['name'] = 'Content';
	$options['form_fields'][ $field_id ]['slug'] = 'editpost_content';
	$options['form_fields'][ $field_id ]['type'] = 'Content';


	update_post_meta( $post_id, '_buddyforms_options', $options );

	if ( $post_id ) {
		buddyforms_attached_page_rewrite_rules( true );
		echo sanitize_title( $_POST['create_new_form_name'] );
	} else {
		echo 'Error Saving the Form';
	}

	die();

}

add_action( 'wp_ajax_buddyforms_add_form', 'buddyforms_add_form' );

/**
 * Get all taxonomies
 *
 * @package BuddyForms
 * @since 0.1-beta
 */
function buddyforms_taxonomies( $buddyform ) {

	$post_type = $buddyform['post_type'];

	$taxonomies = get_object_taxonomies( $post_type );

	return $taxonomies;
}

function buddyforms_update_taxonomy_default() {

	if ( ! isset( $_POST['taxonomy'] ) ) {
		echo 'false';
	}

	$taxonomy = $_POST['taxonomy'];

	$args = array(
		'orderby'    => 'name',
		'order'      => 'ASC',
		'hide_empty' => false,
		'fields'     => 'id=>name',
	);

	$terms = get_terms( $taxonomy, $args );

	$tmp = '<option value="none">none</option>';
	foreach ( $terms as $key => $term_name ) {
		$tmp .= '<option value="' . $key . '">' . $term_name . '</option>';
	}

	echo $tmp;

	die();

}
add_action( 'wp_ajax_buddyforms_update_taxonomy_default', 'buddyforms_update_taxonomy_default' );

function buddyforms_form_template(){
	global $post, $buddyform;


	$post->post_type = 'buddyforms';


	switch( $_POST['template'] ){
		case 'contact' :
			$buddyform =  json_decode( '{"form_fields":{"92f6e0cb6b":{"type":"user_first","slug":"user_first","name":"First Name","description":""},"8ead289ca0":{"type":"user_last","slug":"user_last","name":"Last Name","description":""},"87e0afb2d7":{"type":"user_email","slug":"user_email","name":"eMail","description":""},"210ef7d8a8":{"type":"subject","slug":"subject","name":"Subject","description":"","validation_error_message":"This field is required.","validation_minlength":"0","validation_maxlength":"0"},"0a256db3cb":{"type":"message","slug":"message","name":"Message","description":"","validation_error_message":"This field is required.","validation_minlength":"0","validation_maxlength":"0"}},"form_type":"contact","after_submit":"display_message","after_submission_page":"none","after_submission_url":"","after_submit_message_text":"Your Message has been Submitted Successfully","post_type":"buddyforms_submissions","status":"publish","comment_status":"open","singular_name":"","attached_page":"none","edit_link":"all","list_posts_option":"list_all_form","list_posts_style":"list","mail_submissions":{"01bd6766e5":{"mail_trigger_id":"01bd6766e5","mail_from_name":"eMail Notification","mail_to_address":"[user_email]","mail_from":"mail@sven-lehnert.de","mail_to_cc_address":"","mail_to_bcc_address":"","mail_subject":"Form Submission Notification","mail_body":"Hi [user_first],\r\n\r\nthanks for getting in contact with us.\r\n\r\nYour Message has been Submitted Successfully. We will get in contact with you shortly.\r\n\r\nYour Message Text:\r\n\r\n[subject]\r\n[message]\r\n\r\n\r\n"}},"public_submit":["public_submit"],"name":"Contact Form","slug":"contact-form"}', true );
			break;
		case 'registration' :
			$buddyform =  json_decode( '{"form_fields":{"5bf35b20e3":{"name":"Subject","description":"","type":"title","slug":"editpost_title","validation_error_message":"This field is required.","validation_minlength":"0","validation_maxlength":"","custom_class":""},"a6aa43b61d":{"name":"Content","description":"","type":"content","slug":"editpost_content","validation_error_message":"This field is required.","validation_minlength":"0","validation_maxlength":"0","custom_class":""}},"name":"Post","slug":"post","after_submit":"display_message","after_submit_message_text":"The [form_singular_name] [post_title] has been successfully updated!<br>1. [post_link]<br>2. [edit_link]","post_type":"post","status":"publish","comment_status":"open","edit_link":"all","singular_name":"","attached_page":"2","list_posts_option":"list_all_form","list_posts_style":"list"}', true );
			break;
		case 'post' :
			$buddyform =  json_decode( '{"form_fields":{"12232eecf8":{"type":"title","slug":"editpost_title","name":"Title","description":"Please add a meaningful title","validation_error_message":"This field is required.","validation_minlength":"0","validation_maxlength":"","custom_class":""},"b353f21f01":{"type":"content","slug":"editpost_content","name":"Content","description":"Please add your content","validation_error_message":"This field is required.","validation_minlength":"0","validation_maxlength":"0","custom_class":""},"c8edc4dbc5":{"type":"taxonomy","name":"Category","description":"Please select at least one category","taxonomy":"category","taxonomy_order":"ASC","multiple":["multiple"],"show_option_none":["show_select_option"],"validation_error_message":"This field is required.","slug":"category","custom_class":""}},"form_type":"post","after_submit":"display_message","after_submission_page":"none","after_submission_url":"","after_submit_message_text":"Your Message has been Submitted Successfully","post_type":"post","status":"publish","comment_status":"open","singular_name":"Post","attached_page":"2","admin_bar":["Admin Bar"],"edit_link":"all","list_posts_option":"list_all_form","list_posts_style":"list","mail_notification":{"publish":{"mail_trigger":"publish","mail_from_name":"Post Published","mail_from":"mail@sven-lehnert.de","mail_to":["author","admin"],"mail_to_address":"","mail_subject":"Post Published","mail_body":"Hi [user_nicename],\r\n\r\nyour post [published_post_title] on the site [site_name] has been published.\r\n\r\nYou can visit the post here: [published_post_link_html]\r\n\r\nYour [site_name] Team"}},"name":"Posts","slug":"posts"}', true );
			break;
		default :
			$buddyform = json_decode( apply_filters('buddyforms_templates_json', $buddyform ) );
			break;
	}

	ob_start();
		buddyforms_metabox_form_elements( $post, $buddyform );
	$formbuilder = ob_get_clean();

	$json['formbuilder'] = $formbuilder;


	ob_start();
		buddyforms_mail_notification_form( $buddyform['form_slug'] );
	$mail_notification = ob_get_clean();

	$json['mail_notification'] = $mail_notification;

	unset($buddyform['form_fields']);

	$json['form_setup'] = $buddyform;


	echo json_encode( $json );

	die();

}
add_action( 'wp_ajax_buddyforms_form_template', 'buddyforms_form_template' );


function buddyforms_new_page(){

	if(!is_admin())
		return;

	// Check if a title is entered
	if(empty($_POST['page_name'])){
		$json['error'] = 'Please enter a name';
		echo json_encode( $json ); 	die();
	}

	// Create post object
	$new_page = array(
		'post_title'    => wp_strip_all_tags( $_POST['page_name'] ),
		'post_content'  => '',
		'post_status'   => 'publish',
		'post_type'     => 'page'
	);

	// Insert the post into the database
	$new_page = wp_insert_post( $new_page );

	// Check if page creation worked successfully
	if(is_wp_error($new_page)){
		$json['error'] = $new_page;
	} else {
		$json['id'] = $new_page;
		$json['name'] = wp_strip_all_tags( $_POST['page_name'] );
	}

	echo json_encode( $json ); 	die();

}
add_action( 'wp_ajax_buddyforms_new_page', 'buddyforms_new_page' );