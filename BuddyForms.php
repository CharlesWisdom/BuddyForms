<?php

/*
 Plugin Name: BuddyForms
 Plugin URI:  http://buddyforms.com
 Description: Form Magic and Collaborative Publishing for WordPress. With Frontend Editing and Drag-and-Drop Form Builder.
 Version: 1.5.3
 Author: Sven Lehnert
 Author URI: https://profiles.wordpress.org/svenl77
 Licence: GPLv3
 Network: false

 *****************************************************************************
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
 *
 ****************************************************************************
 */

// Create a helper function for easy SDK access.
function buddyforms_core_fs() {
	global $buddyforms_core_fs;

	if ( ! isset( $buddyforms_core_fs ) ) {
		// Include Freemius SDK.
		require_once dirname(__FILE__) . '/includes/resources/freemius/start.php';

		$buddyforms_core_fs = fs_dynamic_init( array(
			'id'                => '391',
			'slug'              => 'buddyforms',
			'type'              => 'plugin',
			'public_key'        => 'pk_dea3d8c1c831caf06cfea10c7114c',
			'is_premium'        => true,
			'has_addons'        => true,
			'has_paid_plans'    => true,
			'menu'              => array(
				'slug'       => 'edit.php?post_type=buddyforms',
				'first-path' => 'edit.php?post_type=buddyforms&page=buddyforms_welcome_screen',
				'support'    => false,
				'contact'    => false,
				'addons'    => false,
			),
			// Set the SDK to work in a sandbox mode (for development & testing).
			// IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
//			'secret_key'  => 'sk_Zb!EPD=[JrR!45n03@?w8.Iys1bB*',
		) );
	}

	return $buddyforms_core_fs;
}




class BuddyForms {

	/**
	 * @var string
	 */
	public $version = '1.6 Developer Version';

	/**
	 * Initiate the class
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	public function __construct() {

		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );

		$this->load_constants();

		add_action( 'init', array( $this, 'init_hook' ), 1, 1 );
		add_action( 'init', array( $this, 'includes' ), 4, 1 );
		add_action( 'init', array( $this, 'buddyforms_update_db_check' ), 10 );


		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'buddyforms_admin_style' ), 1, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'buddyforms_admin_js' ), 2, 1 );
		add_action( 'admin_footer', array( $this, 'buddyforms_admin_js_footer' ), 2, 1 );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

		add_action( 'template_redirect', array( $this, 'buddyform_front_js_loader' ), 2, 1 );

		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );

	}

	/**
	 * Defines constants needed throughout the plugin.
	 *
	 * These constants can be overridden in bp-custom.php or wp-config.php.
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	public function load_constants() {

		define( 'BUDDYFORMS_VERSION', $this->version );

		// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
		define( 'BUDDYFORMS_STORE_URL', 'https://themekraft.com/' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

		// the name of your product. This should match the download name in EDD exactly
		define( 'BUDDYFORMS_EDD_ITEM_NAME', 'BuddyForms' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file


		if ( ! defined( 'BUDDYFORMS_PLUGIN_URL' ) ) {
			define( 'BUDDYFORMS_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
		}

		if ( ! defined( 'BUDDYFORMS_INSTALL_PATH' ) ) {
			define( 'BUDDYFORMS_INSTALL_PATH', dirname( __FILE__ ) . '/' );
		}

		if ( ! defined( 'BUDDYFORMS_INCLUDES_PATH' ) ) {
			define( 'BUDDYFORMS_INCLUDES_PATH', BUDDYFORMS_INSTALL_PATH . 'includes/' );
		}

		if ( ! defined( 'BUDDYFORMS_TEMPLATE_PATH' ) ) {
			define( 'BUDDYFORMS_TEMPLATE_PATH', BUDDYFORMS_INSTALL_PATH . 'templates/' );
		}

	}

	/**
	 * Defines buddyforms_init action
	 *
	 * This action fires on WP's init action and provides a way for the rest of WP,
	 * as well as other dependent plugins, to hook into the loading process in an
	 * orderly fashion.
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	public function init_hook() {
		global $buddyforms;
		$this->set_globals();
		do_action( 'buddyforms_init' );
	}

	/**
	 * Setup all globals
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	static function set_globals() {
		global $buddyforms;

		$buddyforms = get_option( 'buddyforms_forms' );
		$buddyforms = apply_filters( 'buddyforms_set_globals', $buddyforms );

		return $buddyforms;
	}

	/**
	 * Include files needed by BuddyForms
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	public function includes() {

		if ( ! function_exists( 'PFBC_Load' ) ) {
			require_once( BUDDYFORMS_INCLUDES_PATH . '/resources/pfbc/Form.php' );
		}

		require_once( BUDDYFORMS_INCLUDES_PATH . 'functions.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'the-content.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'rewrite-roles.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'shortcodes.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'wp-mail.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'revisions.php' );

		require_once( BUDDYFORMS_INCLUDES_PATH . 'form/form.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'form/form-preview.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'form/form-render.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'form/form-ajax.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'form/form-elements.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'form/form-control.php' );
		require_once( BUDDYFORMS_INCLUDES_PATH . 'form/form-validation.php' );

		if ( is_admin() ) {

			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/admin-ajax.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/welcome-screen.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/register-post-types.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/submissions.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/settings.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/add-ons.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/contact-us.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/functions.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-metabox.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-wizard.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/mce-editor-button.php' );

			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/form-builder-elements.php' );

			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/meta-boxes/metabox-mail-notification.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/meta-boxes/metabox-permissions.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/meta-boxes/metabox-shortcodes.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/meta-boxes/metabox-select-form.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/meta-boxes/metabox-form-elements.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/meta-boxes/metabox-form-setup.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/meta-boxes/metabox-form-header.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/meta-boxes/metabox-form-footer.php' );
			require_once( BUDDYFORMS_INCLUDES_PATH . '/admin/form-builder/meta-boxes/metabox-default-sidebar.php' );

		}


	}

	/**
	 * Load the textdomain for the plugin
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'buddyforms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Enqueue the needed CSS for the admin screen
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	function buddyforms_admin_style( $hook_suffix ) {
		global $post;
		if (
			( isset( $post ) && $post->post_type == 'buddyforms' && isset( $_GET['action'] ) && $_GET['action'] == 'edit'
			  || isset( $post ) && $post->post_type == 'buddyforms' && $hook_suffix == 'post-new.php' )
			//|| isset($_GET['post_type']) && $_GET['post_type'] == 'buddyforms'
			|| $hook_suffix == 'buddyforms_page_bf_add_ons'
			|| $hook_suffix == 'buddyforms_page_bf_settings'
			|| $hook_suffix == 'buddyforms_page_buddyforms_submissions'
			|| $hook_suffix == 'buddyforms_page_buddyforms-pricing'
		) {

			if ( is_rtl() ) {
				wp_enqueue_style( 'style-rtl', plugins_url( 'assets/admin/css/admin-rtl.css', __FILE__ ) );
			}

			wp_enqueue_style( 'bootstrapcss', plugins_url( 'assets/admin/css/bootstrap.css', __FILE__ ) );
			wp_enqueue_style( 'buddyforms_admin_css', plugins_url( 'assets/admin/css/admin.css', __FILE__ ) );
			wp_enqueue_style ( 'wp-jquery-ui-dialog' );

		} else {
			wp_enqueue_style( 'admin_post_metabox', plugins_url( 'assets/admin/css/admin-post-metabox.css', __FILE__ ) );
		}
		// load the tk_icons everywhere
		wp_enqueue_style( 'tk_icons', plugins_url( '/assets/resources/tk_icons/style.css', __FILE__ ) );

	}

	/**
	 * Enqueue the needed JS for the admin screen
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	function buddyforms_admin_js( $hook_suffix ) {
		global $post;

		if (
			( isset( $post ) && $post->post_type == 'buddyforms' && isset( $_GET['action'] ) && $_GET['action'] == 'edit'
			  || isset( $post ) && $post->post_type == 'buddyforms' && $hook_suffix == 'post-new.php' )
			//|| isset($_GET['post_type']) && $_GET['post_type'] == 'buddyforms'
			|| $hook_suffix == 'buddyforms-page-bf-add_ons'
			|| $hook_suffix == 'buddyforms-page-bf-settings'
			|| $hook_suffix == 'buddyforms-page-bf-submissions'
			|| $hook_suffix == 'buddyforms_page_buddyforms-pricing'
		) {
			wp_register_script( 'buddyforms-admin-js', plugins_url( 'assets/admin/js/admin.js', __FILE__ ) );
			wp_register_script( 'buddyforms-admin-slugifies-js', plugins_url( 'assets/admin/js/slugifies.js', __FILE__ ) );
			wp_register_script( 'buddyforms-admin-wizard-js', plugins_url( 'assets/admin/js/wizard.js', __FILE__ ) );
			wp_register_script( 'buddyforms-admin-deprecated-js', plugins_url( 'assets/admin/js/deprecated.js', __FILE__ ) );
			wp_register_script( 'buddyforms-admin-conditionals-js', plugins_url( 'assets/admin/js/conditionals.js', __FILE__ ) );
			wp_register_script( 'buddyforms-admin-formbuilder-js', plugins_url( 'assets/admin/js/formbuilder.js', __FILE__ ) );

			$admin_text_array = array(
				'check'   => __( 'Check all', 'buddyforms' ),
				'uncheck' => __( 'Uncheck all', 'buddyforms' )
			);
			wp_localize_script( 'buddyforms-admin-js', 'admin_text', $admin_text_array );
			wp_enqueue_script( 'buddyforms-admin-js' );
			wp_enqueue_script( 'buddyforms-admin-slugifies-js' );
			wp_enqueue_script( 'buddyforms-admin-wizard-js' );
			wp_enqueue_script( 'buddyforms-admin-deprecated-js' );
			wp_enqueue_script( 'buddyforms-admin-formbuilder-js' );
			wp_enqueue_script( 'buddyforms-admin-conditionals-js' );

			wp_enqueue_script( 'buddyforms-jquery-steps-js', plugins_url( 'assets/resources/jquery-steps/jquery.steps.min.js', __FILE__ ), array( 'jquery' ), '' );
			wp_enqueue_script( 'bootstrapjs', plugins_url( 'assets/admin/js/bootstrap.js', __FILE__ ), array( 'jquery' ) );

			wp_enqueue_script( 'jQuery' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-accordion' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_script( 'jquery-ui-tabs' );

		}
			wp_enqueue_script('tinymce');
			wp_enqueue_script( 'buddyforms_admin_all_js', plugins_url( 'assets/admin/js/admin-all.js', __FILE__ ), array( 'jquery' ) );

			wp_enqueue_script( 'buddyforms-select2-js', plugins_url( 'assets/resources/select2/select2.min.js', __FILE__ ), array( 'jquery' ), '3.5.2' );
			wp_enqueue_style( 'buddyforms-select2-css', plugins_url( 'assets/resources/select2/select2.css', __FILE__ ) );




		wp_enqueue_media();
			wp_enqueue_script( 'media-uploader-js', plugins_url( 'assets/js/media-uploader.js', __FILE__ ), array( 'jquery' ) );


	}

	/**
	 * Enqueue the needed JS for the admin screen
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	function buddyforms_admin_js_footer() {
		global $post, $hook_suffix;

		if (
		( isset( $post )
		  && $post->post_type == 'buddyforms'
		  && isset( $_GET['action'] ) && $_GET['action'] == 'edit'
		  || isset( $post ) && $post->post_type == 'buddyforms'
		  || $hook_suffix == 'buddyforms_page_buddyforms-pricing'
		)
		) {
			?>
			<script>!function (e, o, n) {
					window.HSCW = o, window.HS = n, n.beacon = n.beacon || {};
					var t = n.beacon;
					t.userConfig = {}, t.readyQueue = [], t.config = function (e) {
						this.userConfig = e
					}, t.ready = function (e) {
						this.readyQueue.push(e)
					}, o.config = {
						docs: {enabled: !0, baseUrl: "http://buddyforms.helpscoutdocs.com/"},
						contact: {enabled: !0, formId: "44c14297-6391-11e5-8846-0e599dc12a51"}
					};
					var r = e.getElementsByTagName("script")[0], c = e.createElement("script");
					c.type = "text/javascript", c.async = !0, c.src = "https://djtflbt20bdde.cloudfront.net/", r.parentNode.insertBefore(c, r)
				}(document, window.HSCW || {}, window.HS || {});</script>
			<?php
		}

	}

	/**
	 * Enqueue the needed JS for the form in the frontend
	 *
	 * @package buddyforms
	 * @since 0.1-beta
	 */
	function buddyform_front_js_loader() {
		global $post, $wp_query, $buddyforms;

		$found = false;

		// check the post content for the short code
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'buddyforms_form' ) ) {
			$found = true;
		}

		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'buddyforms_list_all' ) ) {
			$found = true;
		}

		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'buddyforms_the_loop' ) ) {
			$found = true;
		}

		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'bf' ) ) {
			$found = true;
		}

		if ( isset( $wp_query->query['bf_action'] ) ) {
			$found = true;
		}

		if ( $post->ID == get_option('buddyforms_preview_page', true) ) {
			$found = true;
		}

		$found = apply_filters( 'buddyforms_front_js_css_loader', $found );

		if ( $found ) {
			BuddyForms::buddyform_front_js_css();
		}

	}

	function buddyform_front_js_css() {
		global $wp_scripts;

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

		do_action( 'buddyforms_front_js_css_enqueue' );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), $jquery_version );

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-widgets' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		wp_enqueue_script( 'jquery-validation', plugins_url( 'assets/resources/jquery.validate.min.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'jquery-garlicjs', plugins_url( 'assets/resources/garlicjs/garlic.js', __FILE__ ), array( 'jquery' ) );


		wp_enqueue_script( 'buddyforms-select2-js', plugins_url( 'assets/resources/select2/select2.min.js', __FILE__ ), array( 'jquery' ), '3.5.2' );
		wp_enqueue_style( 'buddyforms-select2-css', plugins_url( 'assets/resources/select2/select2.css', __FILE__ ) );

		wp_enqueue_script( 'buddyforms-jquery-ui-timepicker-addon-js', plugins_url( 'assets/resources/jquery-ui-timepicker-addon/jquery-ui-timepicker-addon.js', __FILE__ ), array(
			'jquery-ui-core',
			'jquery-ui-datepicker',
			'jquery-ui-slider'
		) );
		wp_enqueue_style( 'buddyforms-jquery-ui-timepicker-addon-css', plugins_url( 'assets/resources/jquery-ui-timepicker-addon/jquery-ui-timepicker-addon.css', __FILE__ ) );

		wp_enqueue_script( 'buddyforms-js', plugins_url( 'assets/js/buddyforms.js', __FILE__ ), array(
			'jquery-ui-core',
			'jquery-ui-datepicker',
			'jquery-ui-slider'
		) );

		wp_enqueue_media();
		wp_enqueue_script( 'media-uploader-js', plugins_url( 'assets/js/media-uploader.js', __FILE__ ), array( 'jquery' ) );

		wp_enqueue_style( 'buddyforms-the-loop-css', plugins_url( 'assets/css/the-loop.css', __FILE__ ) );
		wp_enqueue_style( 'buddyforms-the-form-css', plugins_url( 'assets/css/the-form.css', __FILE__ ) );

		// load dashicons
		wp_enqueue_style( 'dashicons' );

		add_action('wp_head', 'buddyforms_jquery_validation');

	}

	function buddyforms_update_db_check() {
		$buddyforms_old = get_option( 'buddyforms_options' );

		if ( ! $buddyforms_old ) {
			return;
		}

		update_option( 'buddyforms_options_old', $buddyforms_old );

		foreach ( $buddyforms_old['buddyforms'] as $key => $form ) {
			$bf_forms_args = array(
				'post_title'  => $form['name'],
				'post_type'   => 'buddyforms',
				'post_status' => 'publish',
			);

			// Insert the new form
			$post_id    = wp_insert_post( $bf_forms_args, true );
			$form['id'] = $post_id;

			update_post_meta( $post_id, '_buddyforms_options', $form );

			// Update the option _buddyforms_forms used to reduce queries
			$buddyforms_forms = get_option( 'buddyforms_forms' );

			$buddyforms_forms[ $form['slug'] ] = $form;
			update_option( 'buddyforms_forms', $buddyforms_forms );

		}

		update_option( 'buddyforms_version', BUDDYFORMS_VERSION );

		delete_option( 'buddyforms_options' );

		buddyforms_attached_page_rewrite_rules( true );
	}


	/**
	 * Change the admin footer text on BuddyForms admin pages.
	 *
	 * @since  1.6
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		global $post;

		if ( ! current_user_can( 'manage_options' ) ) {
			return $footer_text;
		}

		$current_screen = get_current_screen();

		if ( ! isset( $current_screen->id ) ) {
			return $footer_text;
		}

		if ( $current_screen->id == 'edit-buddyforms'
		     || $current_screen->id == 'buddyforms'
		     || $current_screen->id == 'buddyforms_page_buddyforms_submissions'
		     || $current_screen->id == 'buddyforms_page_buddyforms_settings'
		     || $current_screen->id == 'buddyforms_page_bf_add_ons'
		) {

			// Change the footer text
			$footer_text = sprintf( __( 'If you like <strong>BuddyForms</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733;%s rating. A huge thank you from BuddyForms in advance!', 'buddyforms' ), '<a href="https://wordpress.org/support/view/plugin-reviews/buddyforms?filter=5#postform" target="_blank" class="wc-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'woocommerce' ) . '">', '</a>' );
		}

		return $footer_text;
	}


	function plugin_activation(){

		$title = apply_filters( 'buddyforms_preview_page_title', 'BuddyForms Preview Page' );
		$preview_page = get_page_by_title( $title );
		if( !$preview_page ) {
			// Create preview page object
			$preview_post = array(
				'post_title' => $title,
				'post_content' => 'This is a preview of how this form will appear on your website',
				'post_status' => 'draft',
				'post_type' => 'page'
			);

			// Insert the page into the database
			$page_id = wp_insert_post( $preview_post );
		}else{
			$page_id = $preview_page->ID;
		}

		update_option( 'buddyforms_preview_page', $page_id );


		set_transient( '_buddyforms_welcome_screen_activation_redirect', true, 30 );

	}

	function plugin_deactivation(){
		$buddyforms_preview_page = get_option('buddyforms_preview_page', true);

		wp_delete_post( $buddyforms_preview_page, true );

		delete_option( 'buddyforms_preview_page' );
	}
}
if(PHP_VERSION < 5.3){
	function bf_php_version_admin_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php _e( 'PHP Version Update Required!', 'buddyforms' ); ?></p>
			<p><?php _e( 'You are using PHP Version ' . PHP_VERSION, 'buddyforms' )  ; ?></p>
			<p><?php _e( 'Please make sure you have at least php version 5.3 installed.', 'buddyforms' ); ?></p>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'bf_php_version_admin_notice' );
} else {
	$GLOBALS['buddyforms_new'] = new BuddyForms();
	// Init Freemius.
	buddyforms_core_fs();
}

