<?php
function bf_mail_notification_screen() {
    global $post;

    $buddyform = get_post_meta($post->ID, '_buddyforms_options', true);

    echo '<h2>' . __(' Mail Notification Settings for "', 'buddyforms') . $buddyform['name'] . '"</h2>';
    echo '<p>' . __('Every form can have different mail notification depends on the post status change. You can create a mail notification for each individual post status. Use the select box and choose the post status you want to create mail notifications for.', 'buddyforms') . '</p><br>';

    if (isset($buddyform['mail_notification'])) { ?>
        <div class="panel-mail-notifications" id="accordion" role="tablist" aria-multiselectable="true">
            <?php
            foreach ($buddyform['mail_notification'] as $key => $value) {
                buddyforms_new_notification_trigger_form($buddyform['mail_notification'][$key]['mail_trigger']);
            }
            echo '<div id="mailcontainer"></div>';
            ?>
        </div>
        <?php
    } else {
        echo '<div id="mailcontainer"></div>';
    }

    echo '<hr>';

    $form_setup = array();
    $form_setup[] = new Element_HTML('<div class="trigger-select">');
    $form_setup[] = new Element_Select('<b>' . __("Create new Mail Notification", 'buddyforms') . '</b><br><br>', "buddyforms_notification_trigger", bf_get_post_status_array(), array('class' => 'buddyforms_notification_trigger', 'shortDesc' => ''));
    $form_setup[] = new Element_HTML('<a class="button-primary btn btn-primary" href="#" id="mail_notification_add_new">' . __('Create Trigger', 'buddyforms') . '</a></div>');

    $form_setup[] = new Element_HTML('<br>
    <div class="help-trigger">
        <b>' . __( 'Post Status', 'buddyforms') . '</b>

        <ul>
            <li><b>publish</b> <small>' . __('(post or page is visible in the frontend)' , 'buddyforms') . '</small></li>
            <li><b>pending</b> <small>' . __('(post or page is in review process)'    , 'buddyforms') . '</small></li>
            <li><b>draft</b> <small>' .   __('(post or page is not visible in the frontend for public)'   , 'buddyforms') . '</small></li>
            <li><b>future</b> <small>' .  __('(post or page is scheduled to publish in the future)'    , 'buddyforms') . '</small></li>
            <li><b>private</b> <small>' . __('(not visible to users who are not logged in)'   , 'buddyforms') . '</small></li>
            <li><b>trash</b> <small>' .   __('(post is in trash)', 'buddyforms') . '</small></li>
        </ul>

    </div>');

    foreach($form_setup as $key => $field){
        echo $field->getLabel();
        echo $field->getShortDesc();
        echo $field->render();
    }





}


function buddyforms_new_notification_trigger_form($trigger){
    global $post;

    $buddyform = get_post_meta($post->ID, '_buddyforms_options', true);

    $shortDesc = "
    <br>
    <h4>User Shortcodes</h4>
    <ul>
        <li><p><b>[user_login] </b>Username</p></li>
        <li><p><b>[user_nicename] </b>Username Sanitized</p><p><small> user_nicename is url sanitized version of user_login. In general, if you don't use any special characters in your login, then your nicename will always be the same as login. But if you enter email address in the login field during registration, then you will see the difference.
            For instance, if your login is user@example.com then you will have userexample-com nicename and it will be used in author's urls (like author's archive, post permalink, etc).
        </small></p></li>
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


    $form_setup[] = new Element_Hidden("buddyforms_options[mail_notification][" . $trigger . "][mail_trigger]", $trigger);


    $form_setup[] = new Element_Textbox(__("Name", 'buddyforms'), "buddyforms_options[mail_notification][" . $trigger . "][mail_from_name]", array('value' => $buddyform['mail_notification'][$trigger]['mail_from_name'], 'required' => 1, 'shortDesc' => 'the senders name'));
    $form_setup[] = new Element_HTML('<br><br>');
    $form_setup[] = new Element_Email(__("Email", 'buddyforms'), "buddyforms_options[mail_notification][" . $trigger . "][mail_from]", array('value' => $buddyform['mail_notification'][$trigger]['mail_from'], 'required' => 1,  'shortDesc' => 'the senders email'));
    $form_setup[] = new Element_HTML('<br><br>');

    $form_setup[] = new Element_Checkbox(__('Sent mail to', 'buddyforms'), "buddyforms_options[mail_notification][" . $trigger . "][mail_to]", array('author' => 'The Post Author', 'admin' => 'Admin E-mail Address from Settings/General'), array('value' => $buddyform['mail_notification'][$trigger]['mail_to'], 'inline' => 1));
    $form_setup[] = new Element_HTML('<br><br>');
    $form_setup[] = new Element_Textbox(__("Add mail to addresses separated with ','", 'buddyforms'), "buddyforms_options[mail_notification][" . $trigger . "][mail_to_address]", array("class" => "bf-mail-field", 'value' => $buddyform['mail_notification'][$trigger]['mail_to_address']));
    $form_setup[] = new Element_HTML('<br><br>');
    $form_setup[] = new Element_Textbox(__("Subject", 'buddyforms'), "buddyforms_options[mail_notification][" . $trigger . "][mail_subject]", array("class" => "bf-mail-field", 'value' => $buddyform['mail_notification'][$trigger]['mail_subject'], 'required' => 1));
    $form_setup[] = new Element_HTML('<br><br>');

    ob_start();
    $settings = array('wpautop' => true, 'media_buttons' => false, 'wpautop' => true, 'tinymce' => true, 'quicktags' => true, 'textarea_rows' => 18);

    wp_editor($buddyform['mail_notification'][$trigger]['mail_body'], "buddyforms_options[mail_notification][" . $trigger . "][mail_body]", $settings);

    $wp_editor = ob_get_contents();
    ob_clean();

    $wp_editor = '<div class="bf_field_group bf_form_content"><label>' . __('Content', 'buddyforms') . ':</label><div class="bf_inputs">' . $wp_editor . '</div></div>';
    $form_setup[] = new Element_HTML($wp_editor);
    $form_setup[] = new Element_HTML('<br><br>');
    $form_setup[] = new Element_HTML($shortDesc);
    ?>
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="heading<?php echo $trigger ?>">
            <h4 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $trigger ?>" aria-expanded="true" aria-controls="collapse<?php echo $trigger ?>">
                    <table class="wp-list-table widefat fixed posts">
                        <tbody><tr>
                            <td class="field_order ui-sortable-handle">
                                <span class="circle">1</span>
                            </td>
                            <td class="field_label">
                                <strong>
                                    <a class="bf_edit_field row-title accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion_text" href="#accordion_<?php echo $trigger ?>" title="Edit this Field" href="#"><?php echo $trigger ?></a>
                                </strong>

                            </td>
                            <td class="field_delete">
                                <span><a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion_text" href="#accordion_<?php echo $trigger ?>" title="Edit this Field" href="javascript:;">Edit</a> | </span>
                                <span><a class="bf__delete_field" title="Delete this Field" href="javascript:;">Delete</a></span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </a>
            </h4>
        </div>
        <div id="collapse<?php echo $trigger ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading<?php echo $trigger ?>">
            <div class="panel-body">
                <?php
                foreach($form_setup as $key => $field){
                    echo '<div class="buddyforms_field_label">' . $field->getLabel() . '</div>';
                    echo '<div class="buddyforms_field_description">' . $field->getShortDesc() . '</div>';
                    echo '<div class="buddyforms_form_field">' . $field->render() . '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}


function buddyforms_new_mail_notification(){

    $trigger = $_POST['trigger'];

    if (isset($trigger, $buddyform['mail_notification'][$trigger]))
        return false;

    buddyforms_new_notification_trigger_form($trigger);
    die();
}

add_action('wp_ajax_buddyforms_new_mail_notification', 'buddyforms_new_mail_notification');
