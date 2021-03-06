<?php
function bf_mail_notification_screen() {
	global $post, $buddyform;

	$buddyform = get_post_meta( $post->ID, '_buddyforms_options', true );

//	echo '<pre>';
//	print_r($buddyform);
//	echo '</pre>';

	$form_setup   = array();

	//$form_setup[] = new Element_HTML( '<a class="button-primary btn btn-primary" href="#" id="mail_notification_add_new">' . __( 'Create New Mail Notification', 'buddyforms' ) . '</a>' );

	$form_setup[] = new Element_HTML( '
		<h4>Mail Notifications</h4>
		<p>Each time this form gets submitted sent out a mail notification.</p>
		<a class="button-primary btn btn-primary" href="#" id="mail_notification_add_new">' . __( 'Create New Mail Notification', 'buddyforms' ) . '</a><br><br><br>' );

	foreach ( $form_setup as $key => $field ) {
		echo $field->getLabel();
		echo $field->getShortDesc();
		echo $field->render();
	}

	echo '<ul>';
	if ( isset( $buddyform['mail_submissions'] ) ) {
		foreach ( $buddyform['mail_submissions'] as $key => $mail_submission ) {
			buddyforms_mail_notification_form( $key, $mail_submission );
		}
	} else {
		echo '<div id="no-trigger-mailcontainer">' . __('No Mail Notification Trigger so far.') . '</div>';
	}
	echo '<div id="mailcontainer"></div>';
	echo '<ul>';
	echo '<hr>';
}

function bf_post_status_mail_notification_screen() {
	global $post;

	$buddyform = get_post_meta( $post->ID, '_buddyforms_options', true );

	if(!isset($buddyform['post_type']) || $buddyform['post_type'] == 'buddyforms_submissions'){
		return;
	}

	 $form_setup   = array();

	$form_setup[] = new Element_HTML( '<br><div class="trigger-select">' );
	$form_setup[] = new Element_Select( '<h4>' . __( "Post Status Change Mail Notifications", 'buddyforms' ) . '</h4><p>' . __( 'Forms can send different email notifications triggered by post status changes. For example, automatically notify post authors when their post is published! ', 'buddyforms' ) . '</p>', "buddyforms_notification_trigger", bf_get_post_status_array(), array(
		'class'     => 'post_status_mail_notification_trigger',
		'shortDesc' => ''
	) );

	$form_setup[] = new Element_HTML( '<a class="button-primary btn btn-primary" href="#" id="post_status_mail_notification_add_new">' . __( 'Create New Post Status Change Mail Notification', 'buddyforms' ) . '</a></div><br>' );


	foreach ( $form_setup as $key => $field ) {
		echo $field->getLabel();
		echo $field->getShortDesc();
		echo $field->render();
	}

	echo '<ul>';
	if ( isset( $buddyform['mail_notification'] ) ) {
		foreach ( $buddyform['mail_notification'] as $key => $value ) {
			buddyforms_new_post_status_mail_notification_form( $buddyform['mail_notification'][ $key ]['mail_trigger'] );
		}
	} else {
		echo '<div id="no-trigger-post-status-mail-container">' . __('No Post Status Mail Notification Trigger so far.') . '</div>';
	}
	echo '<div id="post-status-mail-container"></div>';
	echo '<ul>';
	echo '<hr>';

}


function buddyforms_mail_notification_form( $trigger, $mail_submission ) {
	global $post, $buddyform;

//	echo '<pre>';
//	print_r($mail_submission);
//	echo '</pre>';


	$shortDesc = "
    <br>
    <h4>User Shortcodes</h4>
    <p>You can use any form element slug as shortcode [FIELD SLUG].</p>";

	$form_setup[] = new Element_Hidden( "buddyforms_options[mail_submissions][" . $trigger . "][mail_trigger_id]", $trigger, array( 'class' => 'trigger' . $trigger ) );


	$form_setup[] = new Element_Textbox( '<b>' . __( "Label", 'buddyforms' ) . '</b>', "buddyforms_options[mail_submissions][" . $trigger . "][mail_from_name]", array(
		'value'     => isset( $buddyform['mail_submissions'][ $trigger ]['mail_from_name'] ) ? $buddyform['mail_submissions'][ $trigger ]['mail_from_name'] : 'eMail Notification',
		'required'  => 1,
		'shortDesc' => 'the senders name'
	) );
	$form_setup[] = new Element_Textbox( '<b>' . __( "To", 'buddyforms' ) . '</b>', "buddyforms_options[mail_submissions][" . $trigger . "][mail_to_address]", array(
		"class" => "bf-mail-field",
		'value' => isset( $buddyform['mail_submissions'][ $trigger ]['mail_to_address'] ) ? $buddyform['mail_submissions'][ $trigger ]['mail_to_address'] : ''
	) );
	$form_setup[] = new Element_Email( '<b>' . __( "From", 'buddyforms' ) . '</b>', "buddyforms_options[mail_submissions][" . $trigger . "][mail_from]", array(
		'value'     => isset( $buddyform['mail_submissions'][ $trigger ]['mail_from'] ) ? $buddyform['mail_submissions'][ $trigger ]['mail_from'] : get_option('admin_email'),
		'required'  => 1,
		'shortDesc' => 'the senders email'
	) );

	$element = new Element_Checkbox( '<b>' . __( 'Sent mail to', 'buddyforms' ) . '</b>', "buddyforms_options[mail_submissions][" . $trigger . "][mail_to]", array(
		'admin'  => 'Admin',
		'cc'  => 'CC',
		'bcc' => 'BCC'
	), array(
		'value'  => isset( $buddyform['mail_submissions'][ $trigger ]['mail_to'] ) ? $buddyform['mail_submissions'][ $trigger ]['mail_to'] : '',
		'id'     => 'mail_submissions' . $trigger,
		'class'  => 'mail_to_checkbox bf_sent_mail_to_multi_checkbox'
	) );

	$mail_to_cc = isset( $buddyform['mail_submissions'][$trigger]['mail_to'] ) && in_array( 'cc', $buddyform['mail_submissions'][$trigger]['mail_to'] ) ? '' : 'hidden';

	$form_setup[] = $element;

	$form_setup[] = new Element_Email( '<b>' . __( "CC", 'buddyforms' ) . '</b>', "buddyforms_options[mail_submissions][" . $trigger . "][mail_to_cc_address]", array(
		'id'    => 'mail_submissions' . $trigger . '-1',
		"class" => 'mail_submissions' . $trigger . '-1 ' . $mail_to_cc,
		'value' => isset( $buddyform['mail_submissions'][ $trigger ]['mail_to_cc_address'] ) ? $buddyform['mail_submissions'][ $trigger ]['mail_to_cc_address'] : ''
	) );

	$mail_to_bcc = isset( $buddyform['mail_submissions'][$trigger]['mail_to'] ) && in_array( 'bcc', $buddyform['mail_submissions'][$trigger]['mail_to'] ) ? '' : 'hidden';

	$form_setup[] = new Element_Email( '<b>' . __( "BCC", 'buddyforms' ) . '</b>', "buddyforms_options[mail_submissions][" . $trigger . "][mail_to_bcc_address]", array(
		'id'    => 'mail_submissions' . $trigger . '-2',
		"class" => 'mail_submissions' . $trigger . '-2 ' . $mail_to_bcc,
		'value' => isset( $buddyform['mail_submissions'][ $trigger ]['mail_to_bcc_address'] ) ? $buddyform['mail_submissions'][ $trigger ]['mail_to_bcc_address'] : ''
	) );


	$form_setup[] = new Element_Textbox( '<b>' . __( "Subject", 'buddyforms' ) . '</b>', "buddyforms_options[mail_submissions][" . $trigger . "][mail_subject]", array(
		"class"    => "bf-mail-field",
		'value'    => isset( $buddyform['mail_submissions'][ $trigger ]['mail_subject'] ) ? $buddyform['mail_submissions'][ $trigger ]['mail_subject'] : 'Form Submission Notification',
		'required' => 1
	) );

	ob_start();
	$settings = array(
		'textarea_name' => 'buddyforms_options[mail_submissions][' . $trigger . '][mail_body]',
		'wpautop'       => true,
		'media_buttons' => false,
		'tinymce'       => true,
		'quicktags'     => true,
		'textarea_rows' => 18
	);
	wp_editor( isset( $buddyform['mail_submissions'][ $trigger ]['mail_body'] ) ? $buddyform['mail_submissions'][ $trigger ]['mail_body'] : '', "bf_mail_body" . $trigger, $settings );
	$wp_editor    = ob_get_clean();
	$wp_editor    = '<div style="margin-left: -10px; margin-right: -10px"class="bf_field_group bf_form_content"><label for="form_title"><b>' . __( 'Content', 'buddyforms' ) . '</b><div class="bf_inputs">' . $wp_editor . '</div></div>';
	$form_setup[] = new Element_HTML( $wp_editor . $shortDesc );
	?>

	<li id="trigger<?php echo $trigger ?>" class="bf_trigger_list_item <?php echo $trigger ?>">
		<div class="accordion_fields">
			<div class="accordion-group">
				<div class="accordion-heading-options">
					<table class="wp-list-table widefat fixed posts">
						<tbody>
						<tr>
							<td class="field_order ui-sortable-handle">
								<span class="circle">1</span>
							</td>
							<td class="field_label">
								<strong>
									<a class="bf_edit_field row-title accordion-toggle collapsed" data-toggle="collapse"
									   data-parent="#accordion_text" href="#accordion_<?php echo $trigger ?>"
									   title="Edit this Field" href="#"><?php echo $buddyform['mail_submissions'][ $trigger ]['mail_subject'] ?></a>
								</strong>

							</td>
							<td class="field_delete">
                                <span><a class="accordion-toggle collapsed" data-toggle="collapse"
                                         data-parent="#accordion_text" href="#accordion_<?php echo $trigger ?>"
                                         title="Edit this Field" href="javascript:;">Edit</a> | </span>
                                <span><a class="bf_delete_trigger" id="<?php echo $trigger ?>" title="Delete this Field"
                                         href="javascript:;">Delete</a></span>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<div id="accordion_<?php echo $trigger ?>" class="accordion-body <?php if (defined('DOING_AJAX') && DOING_AJAX) { echo 'in'; } ?> collapse">
					<div class="accordion-inner">
						<?php buddyforms_display_field_group_table( $form_setup, $trigger ) ?>
					</div>
				</div>
			</div>
		</div>
	</li>

	<?php
	return $trigger;
}

function buddyforms_new_post_status_mail_notification_form( $trigger ) {
	global $post;


	if ( isset( $post->ID ) ) {
		$buddyform = get_post_meta( $post->ID, '_buddyforms_options', true );
	}

	$shortDesc = "
    <br>
    <h4>User Shortcodes</h4>
    <ul>
        <li><p><b>[user_login] </b>Username</p></li>
        <li><p><b>[user_nicename] </b>user_nicename is a url-sanitized version of user_login. For example, if a user’s login is user@example.com, their user_nicename will be userexample-com.</p></li>
        <li><p><b>[user_email]</b> user email</p></li>
        <li><p><b>[first_name]</b> user first name</p></li>
        <li><p><b>[last_name] </b> user last name</p></li>
    </ul>
    <h4>Published Post Shortcodes</h4>
    <ul>
        <li><p><b>[published_post_link_html]</b> the published post link in html</p></li>
        <li><p><b>[published_post_link_plain]</b> the published post link in plain</p></li>
        <li><p><b>[published_post_title]</b> the published post title</p></li>
    </ul>
    <h4>Site Shortcodes</h4>
    <ul>
        <li><p><b>[site_name]</b> the site name </p></li>
        <li><p><b>[site_url]</b> the site url</p></li>
        <li><p><b>[site_url_html]</b> the site url in html</p></li>
    </ul>
        ";

	$form_setup[] = new Element_Hidden( "buddyforms_options[mail_notification][" . $trigger . "][mail_trigger]", $trigger, array( 'class' => 'trigger' . $trigger ) );


	$form_setup[] = new Element_Textbox( '<b>' . __( "Name", 'buddyforms' ) . '</b>', "buddyforms_options[mail_notification][" . $trigger . "][mail_from_name]", array(
		'value'     => isset( $buddyform['mail_notification'][ $trigger ]['mail_from_name'] ) ? $buddyform['mail_notification'][ $trigger ]['mail_from_name'] : '',
		'required'  => 1,
		'shortDesc' => 'the senders name'
	) );
	$form_setup[] = new Element_Email( '<b>' . __( "Email", 'buddyforms' ) . '</b>', "buddyforms_options[mail_notification][" . $trigger . "][mail_from]", array(
		'value'     => isset( $buddyform['mail_notification'][ $trigger ]['mail_from'] ) ? $buddyform['mail_notification'][ $trigger ]['mail_from'] : '',
		'required'  => 1,
		'shortDesc' => 'the senders email'
	) );

	$form_setup[] = new Element_Checkbox( '<b>' . __( 'Sent mail to', 'buddyforms' ) . '</b>', "buddyforms_options[mail_notification][" . $trigger . "][mail_to]", array(
		'author' => 'The Post Author',
		'admin'  => 'Admin E-mail Address from Settings/General'
	), array(
		'value'  => isset( $buddyform['mail_notification'][ $trigger ]['mail_to'] ) ? $buddyform['mail_notification'][ $trigger ]['mail_to'] : '',
		'inline' => 0
	) );
	$form_setup[] = new Element_Textbox( '<b>' . __( "Add mail to addresses separated with ','", 'buddyforms' ) . '</b>', "buddyforms_options[mail_notification][" . $trigger . "][mail_to_address]", array(
		"class" => "bf-mail-field",
		'value' => isset( $buddyform['mail_notification'][ $trigger ]['mail_to_address'] ) ? $buddyform['mail_notification'][ $trigger ]['mail_to_address'] : ''
	) );

	$form_setup[] = new Element_Textbox( '<b>' . __( "Subject", 'buddyforms' ) . '</b>', "buddyforms_options[mail_notification][" . $trigger . "][mail_subject]", array(
		"class"    => "bf-mail-field",
		'value'    => isset( $buddyform['mail_notification'][ $trigger ]['mail_subject'] ) ? $buddyform['mail_notification'][ $trigger ]['mail_subject'] : '',
		'required' => 1
	) );

	ob_start();
	$settings = array(
		'textarea_name' => 'buddyforms_options[mail_notification][' . $trigger . '][mail_body]',
		'wpautop'       => true,
		'media_buttons' => false,
		'tinymce'       => true,
		'quicktags'     => true,
		'textarea_rows' => 18
	);
	wp_editor( isset( $buddyform['mail_notification'][ $trigger ]['mail_body'] ) ? $buddyform['mail_notification'][ $trigger ]['mail_body'] : '', "bf_mail_body".$trigger, $settings );
	$wp_editor    = ob_get_clean();
	$wp_editor    = '<div class="bf_field_group bf_form_content"><label><h2>' . __( 'Content', 'buddyforms' ) . '</h2></label><div class="bf_inputs">' . $wp_editor . '</div></div>';
	$form_setup[] = new Element_HTML( $wp_editor . $shortDesc );
	?>

	<li id="trigger<?php echo $trigger ?>" class="bf_trigger_list_item <?php echo $trigger ?>">
		<div class="accordion_fields">
			<div class="accordion-group">
				<div class="accordion-heading-options">
					<table class="wp-list-table widefat fixed posts">
						<tbody>
						<tr>
							<td class="field_order ui-sortable-handle">
								<span class="circle">1</span>
							</td>
							<td class="field_label">
								<strong>
									<a class="bf_edit_field row-title accordion-toggle collapsed" data-toggle="collapse"
									   data-parent="#accordion_text" href="#accordion_<?php echo $trigger ?>"
									   title="Edit this Field" href="#"><?php echo $trigger ?></a>
								</strong>

							</td>
							<td class="field_delete">
                                <span><a class="accordion-toggle collapsed" data-toggle="collapse"
                                         data-parent="#accordion_text" href="#accordion_<?php echo $trigger ?>"
                                         title="Edit this Field" href="javascript:;">Edit</a> | </span>
                                <span><a class="bf_delete_trigger" id="<?php echo $trigger ?>" title="Delete this Field"
                                         href="javascript:;">Delete</a></span>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<div id="accordion_<?php echo $trigger ?>" class="accordion-body collapse">
					<div class="accordion-inner">
						<?php buddyforms_display_field_group_table( $form_setup, $trigger ) ?>
					</div>
				</div>
			</div>
		</div>
	</li>

	<?php
}

function buddyforms_new_mail_notification() {

	ob_start();
		$trigger_id = buddyforms_mail_notification_form();
	$trigger_html = ob_get_clean();

	$json['trigger_id'] = $trigger_id;
	$json['html'] = $trigger_html;

	echo json_encode($json);
	die();
}

add_action( 'wp_ajax_buddyforms_new_mail_notification', 'buddyforms_new_mail_notification' );


function buddyforms_new_post_status_mail_notification() {

	$trigger = $_POST['trigger'];

	if ( isset( $trigger, $buddyform['mail_notification'][ $trigger ] ) ) {
		return false;
	}

	buddyforms_new_post_status_mail_notification_form( $trigger );
	die();
}

add_action( 'wp_ajax_buddyforms_new_post_status_mail_notification', 'buddyforms_new_post_status_mail_notification' );


function buddyforms_form_setup_nav_li_notification(){ ?>
	<li><a
		href="#notification"
		data-toggle="tab"><?php _e( 'Notifications', 'buddyforms' ); ?></a>
	</li><?php
}
add_action('buddyforms_form_setup_nav_li_last', 'buddyforms_form_setup_nav_li_notification');

function buddyforms_form_setup_tab_pane_notification(){ ?>
	<div class="tab-pane fade in" id="notification">
		<div class="buddyforms_accordion_notification">
			<div class="hidden bf-hidden"><?php wp_editor('dummy', 'dummy'); ?></div>


			<?php  bf_mail_notification_screen() ?>

			<?php  bf_post_status_mail_notification_screen() ?>



		</div>
	</div><?php
}
add_action('buddyforms_form_setup_tab_pane_last', 'buddyforms_form_setup_tab_pane_notification');
