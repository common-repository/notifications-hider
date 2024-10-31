<?php
/**
 * Plugin Name: Notifications Hider
 * Description: A plugin to hide WordPress notifications from unauthorized users.
 * Version: 1.0.0
 * Text Domain: notifications-hider
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Kantari Samy
 */

namespace NHider;

// security check.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notification Hider
 */
class NotificationsHider {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * Instance
	 *
	 * @var NotificationsHider
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'hide_notifications' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_head', array( $this, 'load_custom_admin_style' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_codemirror_assets' ) );

		// add link to plugin page.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_page_link' ) );
	}

	/**
	 * Add link to plugin page.
	 *
	 * @param array $links Array of links.
	 * @return array
	 */
	public function add_plugin_page_link( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=NHider' ) ) . '">' . esc_html__( 'Settings', 'notifications-hider' ) . '</a>';
		return $links;
	}

	/**
	 * Enqueue CodeMirror assets.
	 *
	 * @return void
	 */
	public function enqueue_codemirror_assets() {

		// get current screen.
		$screen = get_current_screen();

		// if not on the notification hider page, return.
		if ( ! isset( $screen->id ) || 'settings_page_NHider' !== $screen->id ) {
			return;
		}

		// lib codemirror css.
		wp_enqueue_style( 'wp-codemirror' );

		// lib codemirror js.
		wp_enqueue_script( 'wp-codemirror' );

		// custom js.
		wp_enqueue_script(
			'NHider-script',
			plugins_url( 'js/notifications-hider.js', __FILE__ ),
			array( 'wp-codemirror' ),
			$this->version,
			true
		);
	}


	/**
	 * Check if the current user is authorized to see the notifications.
	 *
	 * @return boolean
	 */
	private function is_authorized() {
		$current_user = wp_get_current_user();
		$options      = get_option( 'NHider__options', array() );

		// if $options is empty, return true.
		if ( empty( $options ) ) {
			return true;
		}

		// if the option doesn't exist and the current user is an administrator, return true.
		if ( empty( $options['allowed_admins'] ) && in_array( 'administrator', $current_user->roles, true ) ) {
			return true;
		}

		// array_map int cast $allowed_admins.
		$allowed_admins = array_map( 'intval', $options['allowed_admins'] );

		// if the option exists and the current user is in the allowed_admins array, return true.
		if ( $options['allowed_admins'] && in_array( $current_user->ID, $allowed_admins, true ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Load custom CSS
	 */
	public function load_custom_admin_style() {

		if ( $this->is_authorized() ) {
			return;
		}

		$options = get_option( 'NHider__options' );
		if ( isset( $options['custom_css'] ) ) {
			echo '<style type="text/css">' . wp_kses_post( $options['custom_css'] ) . '</style>';
		}
	}

	/**
	 * Get instance
	 *
	 * @return NotificationsHider
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new NotificationsHider();
		}

		return self::$instance;
	}

	/**
	 * Hide notifications
	 *
	 * @return void
	 */
	public function hide_notifications() {
		if ( $this->is_authorized() ) {
			return;
		}

		$this->remove_notifications();
	}

	/**
	 * Remove notifications
	 *
	 * @return void
	 */
	private function remove_notifications() {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page(
			'Notifications Hider Settings',
			'Notifications Hider',
			'manage_options',
			'NHider',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Settings page
	 *
	 * @return void
	 */
	public function settings_page() {
		require_once plugin_dir_path( __FILE__ ) . 'settings-form.php';
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'NHider__options_group', 'NHider__options' );

		add_settings_section(
			'NHider__admin_section',
			esc_html__( 'Administrators Access Settings', 'notifications-hider' ),
			null,
			'NHider'
		);

		add_settings_field(
			'NHider__admin_section___field',
			esc_html__( 'Allowed Administrators', 'notifications-hider' ),
			array( $this, 'admin_field_callback' ),
			'NHider',
			'NHider__admin_section'
		);

		add_settings_field(
			'NHider__admin_section___custom_css',
			esc_html__( 'Doesn\'t a plugin use the classic way of adding a notice? Use display none in custom css', 'notifications-hider' ),
			array( $this, 'custom_css_render' ),
			'NHider',
			'NHider__admin_section'
		);
	}

	/**
	 * Callback for the custom CSS field.
	 *
	 * @return void
	 */
	public function custom_css_render() {
		$options = get_option( 'NHider__options' );
		$css     = isset( $options['custom_css'] ) ? $options['custom_css'] : '';
		?>
		<textarea id='NHider-custom-css' cols='40' rows='5' name='NHider__options[custom_css]'><?php echo wp_kses_post( $css ); ?></textarea>
		<?php
	}

	/**
	 * Callback for the admin field.
	 *
	 * @return void
	 */
	public function admin_field_callback() {
		$options = get_option( 'NHider__options' );

		$allowed_admins = isset( $options['allowed_admins'] ) ? $options['allowed_admins'] : array();
		$admins         = get_users( array( 'role' => 'administrator' ) );

		// array_map int cast $allowed_admins.
		$allowed_admins = array_map( 'intval', $allowed_admins );

		echo '<fieldset>';
		foreach ( $admins as $admin ) {
			echo '<label>';
			echo '<input type="checkbox" name="NHider__options[allowed_admins][]" value="' . esc_attr( $admin->ID ) . '" ' . checked( in_array( $admin->ID, $allowed_admins, true ), true, false ) . '>';
			echo esc_html( $admin->display_name );
			echo '</label><br>';
		}
		echo '</fieldset>';
	}
}

// Init Singleton.
NotificationsHider::get_instance();
