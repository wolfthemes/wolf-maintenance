<?php
/**
 * Plugin Name: Maintenance Page
 * Plugin URI: https://github.com/wolfthemes/wolf-maintenance
 * Description: A plugin to manage your maintenance page.
 * Version: 1.0.7
 * Author: WolfThemes
 * Author URI: http://wolfthemes.com
 * Requires at least: 5.0
 * Tested up to: 5.5
 *
 * Text Domain: wolf-maintenance
 * Domain Path: /languages/
 *
 * @package WolfMaintenance
 * @category Core
 * @author WolfThemes
 *
 * Verified customers who have purchased a premium theme at https://wlfthm.es/tf/
 * will have access to support for this plugin in the forums
 * https://wlfthm.es/help/
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Wolf_Maintenance' ) ) {
	/**
	 * Main Wolf_Maintenance Class
	 *
	 * Contains the main functions for Wolf_Maintenance
	 *
	 * @class Wolf_Maintenance
	 * @version 1.0.7
	 * @since 1.0.0
	 */
	class Wolf_Maintenance {

		var $update_url = 'http://plugins.wolfthemes.com/update';

		/**
		 * Hook into the appropriate actions when the class is constructed.
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'plugin_textdomain' ) );

			add_action( 'template_redirect', array( $this, 'do_redirect' ), 5 );

			add_action( 'admin_menu',  array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_init', array( $this, 'plugin_update' ) );

			add_filter( 'body_class', array( $this, 'add_body_class' ) );

			// Plugin row meta
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_action_links' ) );
		}

		/**
		 * Redirect the page to the URL set in the post meta
		 */
		public function do_redirect() {

			include( 'inc/functions.php' );

			$maintenance_page_id = $this->get_option( 'page_id' );
			$excluded_post_ids = $this->list_to_array( $this->get_option( 'excluded_post_ids' ) );
			$is_admin = current_user_can( 'manage_options' );
			$condition =! in_array( get_the_ID(), $excluded_post_ids ) && ! $is_admin && $maintenance_page_id && ! is_page( $maintenance_page_id );


			if ( apply_filters( 'wolf_maintenance_condition', $condition ) ) {
				wp_safe_redirect( get_permalink( $maintenance_page_id ), 302 );
				exit;
			}
		}

		/**
		 * Redirect the page to the URL set in the post meta
		 */
		public function add_body_class( $classes ) {

			$is_admin = current_user_can( 'manage_options' );
			if ( $this->get_option( 'page_id' ) && ! $is_admin ) {
				$classes[] = 'wolf-maintenance';
			}

			return $classes;
		}

		/**
		 * Add Contextual Menu
		 */
		public function add_menu() {

			add_management_page( esc_html__( 'Maintenance', 'wolf-maintenance' ), esc_html__( 'Maintenance', 'wolf-maintenance' ), 'administrator', 'wolf-maintenance', array( $this, 'maintenance_settings' ) );
		}

		/**
		 * Add Settings
		 */
		public function admin_init() {
			register_setting( 'wolf-maintenance', 'wolf_maintenance_settings', array( &$this, 'settings_validate' ) );
			add_settings_section( 'wolf-maintenance', '', array( $this, 'section_intro' ), 'wolf-maintenance' );

			add_settings_field( 'page_id', esc_html__( 'Choose your maintenance page', 'wolf-maintenance' ), array( $this, 'setting_page_id' ), 'wolf-maintenance', 'wolf-maintenance' );

			add_settings_field( 'excluded_post_ids', esc_html__( 'Choose posts to exlude from the redirection (by IDs).', 'wolf-maintenance' ), array( $this, 'setting_excluded_post_ids' ), 'wolf-maintenance', 'wolf-maintenance' );
		}

		/**
		 * Intro section used for debug
		 */
		public function section_intro() {
			// echo "<pre>";
			// print_r( get_option( 'wolf_maintenance_settings' ) );
			// echo "</pre>";
		}

		/**
		 * Alignment Setting
		 */
		public function setting_page_id() {
			$page_option = array( '' => esc_html__( '- Disabled -', 'wolf-maintenance' ) );
			$pages = get_pages();

			foreach ( $pages as $page ) {

				if ( get_post_field( 'post_parent', $page->ID ) ) {
					$page_option[ absint( $page->ID ) ] = '&nbsp;&nbsp;&nbsp; ' . sanitize_text_field( $page->post_title );
				} else {
					$page_option[ absint( $page->ID ) ] = sanitize_text_field( $page->post_title );
				}
			}
			?>
			<select name="wolf_maintenance_settings[page_id]">
				<?php foreach ( $page_option as $k => $v ) : ?>
					<option <?php selected( $this->get_option( 'page_id' ), absint( $k ) ); ?> value="<?php echo absint( $k ); ?>"><?php echo sanitize_text_field( $v ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		}

		/**
		 * Alignment Setting
		 */
		public function setting_excluded_post_ids() {
			?>
			<input type="text" placeholder="54,87,56" value="<?php echo esc_attr( $this->get_option( 'excluded_post_ids' ) ); ?>" name="wolf_maintenance_settings[excluded_post_ids]">
			<?php
		}

		/**
		 * Validate data
		 */
		public function settings_validate( $input ) {
			$input['page_id'] = absint( $input['page_id'] );
			return $input;
		}

		/**
		 * Get maintenance Option
		 *
		 * @param string $value
		 * @return string|null
		 */
		public function get_option( $value = null ) {

			global $options;

			$wolf_maintenance_settings = get_option( 'wolf_maintenance_settings' );

			if ( isset( $wolf_maintenance_settings[ $value ] ) ) {
				return $wolf_maintenance_settings[ $value ];
			}
		}

		/**
		 * Settings Form
		 */
		public function maintenance_settings() {
			?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"></div>
				<h2><?php esc_html_e( 'Maintenance settings', 'wolf-maintenance' ); ?></h2>
				<?php if ( isset( $_GET['settings-updated']) && $_GET['settings-updated'] ) { ?>
				<div id="setting-error-settings_updated" class="updated settings-error">
					<p><strong><?php esc_html_e( 'Settings saved.', 'wolf-maintenance' ); ?></strong></p>
				</div>
				<?php } ?>
				<form action="options.php" method="post">
					<?php settings_fields( 'wolf-maintenance' ); ?>
					<?php do_settings_sections( 'wolf-maintenance' ); ?>
					<p class="submit"><input name="save" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wolf-maintenance' ); ?>" /></p>
				</form>
			</div>
			<?php
		}

		/**
		 * Loads the plugin text domain for translation
		 */
		public function plugin_textdomain() {

			$domain = 'wolf-maintenance';
			$locale = apply_filters( 'wolf-maintenance', get_locale(), $domain );
			load_textdomain( $domain, WP_LANG_DIR.'/'.$domain.'/'.$domain.'-'.$locale.'.mo' );
			load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Add settings link in plugin page
		 */
		public function settings_action_links( $links ) {
			$setting_link = array(
				'<a href="' . admin_url( 'tools.php?page=wolf-maintenance' ) . '">' . esc_html__( 'Settings', 'wolf-maintenance' ) . '</a>',
			);
			return array_merge( $links, $setting_link );
		}

		/**
		 * Convert list to array
		 *
		 * @param string $list
		 * @return array
		 */
		public function list_to_array( $list, $separator = ',' ) {
			return ( $list ) ? explode( ',', trim( wvc_clean_spaces( wvc_clean_list( $list ) ) ) ) : array();
		}

		/**
		 * Plugin update
		 */
		public function plugin_update() {

			if ( ! class_exists( 'WP_GitHub_Updater' ) ) {
				include_once 'inc/admin/updater.php';
			}

			$repo = 'wolfthemes/wolf-maintenance';

			$config = array(
				'slug' => plugin_basename( __FILE__ ),
				'proper_folder_name' => 'wolf-maintenance',
				'api_url' => 'https://api.github.com/repos/' . $repo . '',
				'raw_url' => 'https://raw.github.com/' . $repo . '/master/',
				'github_url' => 'https://github.com/' . $repo . '',
				'zip_url' => 'https://github.com/' . $repo . '/archive/master.zip',
				'sslverify' => true,
				'requires' => '5.0',
				'tested' => '5.5',
				'readme' => 'README.md',
				'access_token' => '',
			);

			new WP_GitHub_Updater( $config );
		}

	} // end class

	$wolf_maintenance = new Wolf_Maintenance();

} // class_exists check
