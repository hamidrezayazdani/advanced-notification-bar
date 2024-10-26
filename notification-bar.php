<?php
/**
 * Plugin Name: Advanced Notification Bar
 * Plugin URI: https://github.com/hamidrezayazdani
 * Description: A customizable notification bar with scheduling and visibility controls
 * Version: 1.0.0
 * Author: HamidReza Yazdani
 * Author URI: https://github.com/hamidrezayazdani
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: adv-notification-bar
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin class
class Advanced_Notification_Bar {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		// Initialize plugin
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_head', array( $this, 'display_notification_bar' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Add AJAX handlers
		add_action( 'wp_ajax_search_posts', array( $this, 'ajax_search_posts' ) );
		add_action( 'wp_ajax_search_pages', array( $this, 'ajax_search_pages' ) );
	}

	public function init() {
		load_plugin_textdomain( 'adv-notification-bar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Notification Bar', 'adv-notification-bar' ),
			__( 'Notification Bar', 'adv-notification-bar' ),
			'manage_options',
			'notification-bar-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-megaphone',
		);
	}

	public function register_settings() {
		register_setting( 'notification_bar_options', 'notification_bar_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
		) );

		add_settings_section(
			'notification_bar_main',
			__( 'Notification Bar Settings', 'adv-notification-bar' ),
			array( $this, 'settings_section_callback' ),
			'notification-bar-settings',
		);

		$this->add_settings_fields();
	}

	private function add_settings_fields() {
		$fields = array(
			'message'      => array(
				'title'    => __( 'Notification Message', 'adv-notification-bar' ),
				'callback' => 'message_field_callback',
			),
			'visibility'   => array(
				'title'    => __( 'Visibility Settings', 'adv-notification-bar' ),
				'callback' => 'visibility_field_callback',
			),
			'schedule'     => array(
				'title'    => __( 'Schedule', 'adv-notification-bar' ),
				'callback' => 'schedule_field_callback',
			),
			'design'       => array(
				'title'    => __( 'Design Options', 'adv-notification-bar' ),
				'callback' => 'design_field_callback',
			),
			'close_button' => array(
				'title'    => __( 'Close Button', 'adv-notification-bar' ),
				'callback' => 'close_button_field_callback',
			)
		);

		foreach ( $fields as $id => $field ) {
			add_settings_field(
				$id,
				$field['title'],
				array( $this, $field['callback'] ),
				'notification-bar-settings',
				'notification_bar_main',
			);
		}
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
				<?php
				settings_fields( 'notification_bar_options' );
				do_settings_sections( 'notification-bar-settings' );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['message']           = wp_kses_post( $input['message'] );
		$sanitized['visibility_type']   = sanitize_text_field( $input['visibility_type'] );
		$sanitized['selected_posts']    = isset( $input['selected_posts'] ) ? array_map( 'absint', $input['selected_posts'] ) : array();
		$sanitized['selected_pages']    = isset( $input['selected_pages'] ) ? array_map( 'absint', $input['selected_pages'] ) : array();
		$sanitized['start_date']        = sanitize_text_field( $input['start_date'] );
		$sanitized['end_date']          = sanitize_text_field( $input['end_date'] );
		$sanitized['bg_color']          = sanitize_hex_color( $input['bg_color'] );
		$sanitized['text_color']        = sanitize_hex_color( $input['text_color'] );
		$sanitized['font_size']         = absint( $input['font_size'] );
		$sanitized['show_close_button'] = isset( $input['show_close_button'] ) ? 1 : 0;

		return $sanitized;
	}


	public function settings_section_callback() {
		echo '<p>' . esc_html__( 'Configure your notification bar settings below.', 'adv-notification-bar' ) . '</p>';
	}

	public function message_field_callback() {
		$options = get_option( 'notification_bar_settings' );
		?>
        <div class="notification-field">
            <textarea name="notification_bar_settings[message]" rows="3" class="large-text"><?php
	            echo esc_textarea( $options['message'] ?? '' );
	            ?></textarea>
            <p class="description"><?php esc_html_e( 'Enter the message to display in the notification bar.', 'adv-notification-bar' ); ?></p>
        </div>
		<?php
	}

	public function visibility_field_callback() {
		$options         = get_option( 'notification_bar_settings' );
		$visibility_type = $options['visibility_type'] ?? 'site-wide';
		?>
        <div class="notification-field visibility-settings">
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="notification_bar_settings[visibility_type]" value="site-wide"
						<?php checked( $visibility_type, 'site-wide' ); ?>>
					<?php esc_html_e( 'Site-wide', 'adv-notification-bar' ); ?>
                </label>

                <label class="radio-label">
                    <input type="radio" name="notification_bar_settings[visibility_type]" value="homepage"
						<?php checked( $visibility_type, 'homepage' ); ?>>
					<?php esc_html_e( 'Homepage Only', 'adv-notification-bar' ); ?>
                </label>

                <label class="radio-label">
                    <input type="radio" name="notification_bar_settings[visibility_type]" value="specific-posts"
						<?php checked( $visibility_type, 'specific-posts' ); ?>>
					<?php esc_html_e( 'Specific Posts', 'adv-notification-bar' ); ?>
                </label>

                <label class="radio-label">
                    <input type="radio" name="notification_bar_settings[visibility_type]" value="specific-pages"
						<?php checked( $visibility_type, 'specific-pages' ); ?>>
					<?php esc_html_e( 'Specific Pages', 'adv-notification-bar' ); ?>
                </label>
            </div>

            <div id="posts-select" class="select2-container" style="display: none;">
                <select name="notification_bar_settings[selected_posts][]" class="posts-select2" multiple="multiple">
					<?php
					if ( ! empty( $options['selected_posts'] ) ) {
						foreach ( $options['selected_posts'] as $post_id ) {
							$post = get_post( $post_id );
							if ( $post ) {
								printf(
									'<option value="%d" selected="selected">%s</option>',
									$post_id,
									esc_html( $post->post_title )
								);
							}
						}
					}
					?>
                </select>
            </div>

            <div id="pages-select" class="select2-container" style="display: none;">
                <select name="notification_bar_settings[selected_pages][]" class="pages-select2" multiple="multiple">
					<?php
					if ( ! empty( $options['selected_pages'] ) ) {
						foreach ( $options['selected_pages'] as $page_id ) {
							$page = get_post( $page_id );
							if ( $page ) {
								printf(
									'<option value="%d" selected="selected">%s</option>',
									$page_id,
									esc_html( $page->post_title )
								);
							}
						}
					}
					?>
                </select>
            </div>
        </div>
		<?php
	}

	public function schedule_field_callback() {
		$options = get_option( 'notification_bar_settings' );
		?>
        <label><?php esc_html_e( 'Start Date:', 'adv-notification-bar' ); ?></label>
        <input type="datetime-local" name="notification_bar_settings[start_date]"
               value="<?php echo esc_attr( $options['start_date'] ?? '' ); ?>"><br><br>

        <label><?php esc_html_e( 'End Date:', 'adv-notification-bar' ); ?></label>
        <input type="datetime-local" name="notification_bar_settings[end_date]"
               value="<?php echo esc_attr( $options['end_date'] ?? '' ); ?>">
		<?php
	}


	public function design_field_callback() {
		$options = get_option( 'notification_bar_settings' );
		?>
        <label><?php esc_html_e( 'Background Color:', 'adv-notification-bar' ); ?></label>
        <input type="color" name="notification_bar_settings[bg_color]"
               value="<?php echo esc_attr( $options['bg_color'] ?? '#000000' ); ?>"><br><br>

        <label><?php esc_html_e( 'Text Color:', 'adv-notification-bar' ); ?></label>
        <input type="color" name="notification_bar_settings[text_color]"
               value="<?php echo esc_attr( $options['text_color'] ?? '#ffffff' ); ?>"><br><br>

        <label><?php esc_html_e( 'Font Size (px):', 'adv-notification-bar' ); ?></label>
        <input type="number" name="notification_bar_settings[font_size]" min="10" max="32"
               value="<?php echo esc_attr( $options['font_size'] ?? '16' ); ?>">
		<?php
	}

	public function close_button_field_callback() {
		$options           = get_option( 'notification_bar_settings' );
		$show_close_button = $options['show_close_button'] ?? 0;
		?>
        <div class="notification-field">
            <label class="toggle-switch">
                <input type="checkbox" name="notification_bar_settings[show_close_button]" value="1"
					<?php checked( $show_close_button, 1 ); ?>>
                <span class="slider round"></span>
            </label>
            <span class="toggle-label"><?php esc_html_e( 'Show close button', 'adv-notification-bar' ); ?></span>
        </div>
		<?php
	}

	public function ajax_search_posts() {
		check_ajax_referer( 'notification_bar_search', 'nonce' );

		$search = sanitize_text_field( $_GET['search'] );

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			's'              => $search,
			'posts_per_page' => 20,
		);

		$posts   = get_posts( $args );
		$results = array();

		foreach ( $posts as $post ) {
			$results[] = array(
				'id'   => $post->ID,
				'text' => $post->post_title
			);
		}

		wp_send_json( array( 'results' => $results ) );
	}

	public function ajax_search_pages() {
		check_ajax_referer( 'notification_bar_search', 'nonce' );

		$search = sanitize_text_field( $_GET['search'] );

		$args = array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			's'              => $search,
			'posts_per_page' => 20,
		);

		$pages   = get_posts( $args );
		$results = array();

		foreach ( $pages as $page ) {
			$results[] = array(
				'id'   => $page->ID,
				'text' => $page->post_title,
			);
		}

		wp_send_json( array( 'results' => $results ) );
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_notification-bar-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'select2',
			'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
			array(),
			'4.0.13',
		);

		wp_enqueue_style(
			'advanced-notification-bar-admin',
			plugins_url( 'assets/css/admin.css', __FILE__ ),
			array( 'select2' ),
			'1.0.0',
		);

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script(
			'select2',
			'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
			array( 'jquery' ),
			'4.0.13',
			true,
		);

		wp_enqueue_script(
			'advanced-notification-bar-admin',
			plugins_url( 'assets/js/admin.js', __FILE__ ),
			array( 'jquery', 'select2' ),
			'1.0.0',
			true,
		);

		wp_localize_script( 'advanced-notification-bar-admin', 'notificationBar', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'notification_bar_search' ),
		) );
	}

	public function enqueue_frontend_assets() {
		if ( $this->should_display_notification() ) {
			wp_enqueue_style(
				'advanced-notification-bar',
				plugins_url( 'assets/css/frontend.css', __FILE__ ),
				array(),
				'1.0.0',
			);

			wp_enqueue_script(
				'advanced-notification-bar',
				plugins_url( 'assets/js/frontend.js', __FILE__ ),
				array( 'jquery' ),
				'1.0.0',
				true,
			);
		}
	}

	public function should_display_notification() {
		$options = get_option( 'notification_bar_settings' );

		if ( empty( $options['message'] ) ) {
			return false;
		}

		// Check scheduling
		$current_time = current_time( 'timestamp' );
		$start_time   = strtotime( $options['start_date'] ?? '' );
		$end_time     = strtotime( $options['end_date'] ?? '' );

		if ( $start_time && $current_time < $start_time ) {
			return false;
		}
		if ( $end_time && $current_time > $end_time ) {
			return false;
		}

		// Check visibility settings
		$visibility_type = $options['visibility_type'] ?? 'site-wide';

		switch ( $visibility_type ) {
			case 'site-wide':
				return true;

			case 'homepage':
				return is_front_page();

			case 'specific-posts':
				if ( ! is_single() ) {
					return false;
				}

				$selected_posts = $options['selected_posts'] ?? array();

				return in_array( get_the_ID(), $selected_posts );

			case 'specific-pages':
				if ( ! is_page() ) {
					return false;
				}

				$selected_pages = $options['selected_pages'] ?? array();

				return in_array( get_the_ID(), $selected_pages );

			default:
				return false;
		}
	}

	public function display_notification_bar() {
		if ( ! $this->should_display_notification() ) {
			return;
		}

		$options           = get_option( 'notification_bar_settings' );
		$show_close_button = $options['show_close_button'] ?? 0;
		$bg_color          = esc_attr( $options['bg_color'] ?? '#000000' );
		$text_color        = esc_attr( $options['text_color'] ?? '#ffffff' );
		$font_size         = absint( $options['font_size'] ?? 16 );

		?>
        <style>
          .advanced-notification-bar {
            background-color: <?php echo $bg_color; ?>;
            color: <?php echo $text_color; ?>;
            font-size: <?php echo $font_size; ?>px;
          }
        </style>
        <div class="advanced-notification-bar" role="alert">
            <div class="notification-content">
				<?php echo wp_kses_post( $options['message'] ); ?>
            </div>
			<?php if ( $show_close_button ): ?>
                <button class="close-button" aria-label="<?php esc_attr_e( 'Close notification', 'adv-notification-bar' ); ?>">
                    ×
                </button>
			<?php endif; ?>
        </div>
		<?php
	}
}

// Initialize the plugin
function advanced_notification_bar_init() {
	Advanced_Notification_Bar::get_instance();
}

add_action( 'plugins_loaded', 'advanced_notification_bar_init' );

/**
 * Activation hook
 */
function advanced_notification_bar_activate() {
	// Set default options
	$default_options = array(
		'message'           => '',
		'visibility_type'   => 'site-wide',
		'selected_posts'    => array(),
		'selected_pages'    => array(),
		'bg_color'          => '#000000',
		'text_color'        => '#ffffff',
		'font_size'         => 16,
		'show_close_button' => 1,
	);

	add_option( 'notification_bar_settings', $default_options );
}

register_activation_hook( __FILE__, 'advanced_notification_bar_activate' );

function advanced_notification_bar_uninstall() {
	delete_option( 'notification_bar_settings' );
}

register_uninstall_hook( __FILE__, 'advanced_notification_bar_uninstall' );