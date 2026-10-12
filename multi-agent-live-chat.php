<?php
/**
 * Plugin Name: Multi Agent Live Chat
 * Description: Add a responsive, multi-agent chat launcher for WhatsApp, Viber, and Messenger.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/digammavauf/multi-agent-live-chat
 * Author: John Philip Tena
 * Author URI: https://www.facebook.com/johnphiliptena
 * License: GPL-2.0-or-later
 * Text Domain: multi-agent-live-chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Multi_Agent_Live_Chat {
	const OPTION = 'wla_settings';
	const PAGE   = 'multi-agent-live-chat';
	const MAX_AGENTS = 25;
	const GITHUB_REPO = 'digammavauf/multi-agent-live-chat';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_button' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_github_release' ) );
		add_filter( 'plugins_api', array( $this, 'github_plugin_information' ), 20, 3 );
	}

	public static function defaults() {
		return array(
			'platform' => 'whatsapp',
			'agents' => array(),
			'position' => 'left',
			'text' => __( 'Send us a message', 'multi-agent-live-chat' ),
			'delete_on_uninstall' => false,
		);
	}

	private function clean_text( $value ) {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	private function settings() {
		$settings = wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
		$legacy_platform = isset( $settings['platform'] ) ? $settings['platform'] : 'whatsapp';
		// Keep installations made with version 1.0.0 working after the settings format changes.
		if ( ! is_array( $settings['agents'] ) ) {
			$settings['agents'] = array();
		}
		if ( empty( $settings['agents'] ) ) {
			$legacy = get_option( self::OPTION, array() );
			if ( is_array( $legacy ) && isset( $legacy['mobile'] ) && is_scalar( $legacy['mobile'] ) && '' !== (string) $legacy['mobile'] ) {
				$settings['agents'] = array( array(
					'name' => isset( $legacy['name'] ) ? $this->clean_text( $legacy['name'] ) : '',
					'contact' => $legacy['mobile'],
					'platform' => 'whatsapp',
				) );
			}
		}
		$normalized_agents = array();
		foreach ( $settings['agents'] as $agent ) {
			if ( ! is_array( $agent ) ) {
				continue;
			}
			$platform = isset( $agent['platform'] ) && in_array( $agent['platform'], array( 'whatsapp', 'viber', 'messenger' ), true ) ? $agent['platform'] : ( in_array( $legacy_platform, array( 'whatsapp', 'viber', 'messenger' ), true ) ? $legacy_platform : 'whatsapp' );
			$visibility = isset( $agent['visibility'] ) && in_array( $agent['visibility'], array( 'all', 'desktop', 'mobile' ), true ) ? $agent['visibility'] : 'all';
			$contact = isset( $agent['contact'] ) ? $agent['contact'] : ( isset( $agent['mobile'] ) ? $agent['mobile'] : '' );
			$normalized_agents[] = array(
				'name' => isset( $agent['name'] ) ? $this->clean_text( $agent['name'] ) : '',
				'contact' => is_scalar( $contact ) ? trim( (string) $contact ) : '',
				'platform' => $platform,
				'visibility' => $visibility,
			);
		}
		$settings['agents'] = $normalized_agents;
		$settings['text'] = $this->clean_text( $settings['text'] );
		$settings['delete_on_uninstall'] = ! empty( $settings['delete_on_uninstall'] );
		return $settings;
	}

	public function add_plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Settings', 'multi-agent-live-chat' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function add_settings_page() {
		add_options_page(
			__( 'Multi Agent Live Chat', 'multi-agent-live-chat' ),
			__( 'Multi Agent Live Chat', 'multi-agent-live-chat' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'wla_settings_group', self::OPTION, array(
			'type' => 'array',
			'show_in_rest' => false,
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
		) );
	}

	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$settings = self::defaults();
		$settings['position'] = isset( $input['position'] ) && in_array( $input['position'], array( 'left', 'right' ), true ) ? $input['position'] : 'left';
		$settings['text'] = isset( $input['text'] ) ? $this->clean_text( $input['text'] ) : self::defaults()['text'];
		$settings['delete_on_uninstall'] = isset( $input['delete_on_uninstall'] ) && '1' === (string) $input['delete_on_uninstall'];
		if ( isset( $input['agents'] ) && is_array( $input['agents'] ) ) {
			$processed_agents = 0;
			foreach ( $input['agents'] as $agent ) {
				if ( $processed_agents >= self::MAX_AGENTS ) {
					add_settings_error( self::OPTION, 'wla_agent_limit', sprintf( __( 'Only the first %d agents were saved.', 'multi-agent-live-chat' ), self::MAX_AGENTS ), 'warning' );
					break;
				}
				++$processed_agents;
				if ( ! is_array( $agent ) ) {
					continue;
				}
				$name = isset( $agent['name'] ) ? $this->clean_text( $agent['name'] ) : '';
				$platform = isset( $agent['platform'] ) && in_array( $agent['platform'], array( 'whatsapp', 'viber', 'messenger' ), true ) ? $agent['platform'] : 'whatsapp';
				$visibility = isset( $agent['visibility'] ) && in_array( $agent['visibility'], array( 'all', 'desktop', 'mobile' ), true ) ? $agent['visibility'] : 'all';
				$contact = isset( $agent['contact'] ) ? $agent['contact'] : ( isset( $agent['mobile'] ) ? $agent['mobile'] : '' );
				$contact = is_scalar( $contact ) ? trim( (string) $contact ) : '';
				$raw_contact = $contact;
				if ( 'messenger' === $platform ) {
					$contact = $this->clean_text( $contact );
					$contact = preg_replace( '#^(https?://)?(www\.)?m\.me/#i', '', $contact );
					$contact = trim( $contact, " /\t\n\r\0\x0B" );
					$contact = preg_match( '/^[A-Za-z0-9.]{3,50}$/', $contact ) ? $contact : '';
				} elseif ( 'viber' === $platform ) {
					if ( preg_match( '#^https?://#i', $contact ) ) {
						$viber_url = wp_parse_url( $contact );
						$viber_host = isset( $viber_url['host'] ) ? strtolower( $viber_url['host'] ) : '';
						$viber_path = isset( $viber_url['path'] ) ? trim( $viber_url['path'], '/' ) : '';
						$contact = ( isset( $viber_url['scheme'] ) && 'https' === strtolower( $viber_url['scheme'] ) && in_array( $viber_host, array( 'viber.me', 'www.viber.me' ), true ) && preg_match( '/^\d{7,15}$/', $viber_path ) && empty( $viber_url['user'] ) && empty( $viber_url['pass'] ) && empty( $viber_url['port'] ) ) ? esc_url_raw( $contact ) : '';
					} else {
						$contact = preg_replace( '/\D+/', '', $contact );
						if ( strlen( $contact ) < 7 || strlen( $contact ) > 15 ) {
							$contact = '';
						}
					}
				} else {
					$contact = preg_replace( '/\D+/', '', $contact );
					if ( strlen( $contact ) < 7 || strlen( $contact ) > 15 ) {
						$contact = '';
					}
				}
				if ( '' !== $raw_contact && '' === $contact ) {
					add_settings_error( self::OPTION, 'wla_invalid_contact', __( 'One or more agent contacts were invalid and were not saved. Check the platform-specific format and try again.', 'multi-agent-live-chat' ), 'warning' );
					continue;
				}
				if ( '' === $name && '' === $contact ) {
					continue;
				}
				$settings['agents'][] = array( 'name' => $name, 'contact' => $contact, 'platform' => $platform, 'visibility' => $visibility );
			}
		}
		return $settings;
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_' . self::PAGE === $hook ) {
			wp_enqueue_style( 'malc-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.css', array(), '1.0.0' );
			wp_enqueue_script( 'malc-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.js', array(), '1.0.0', true );
		}
	}

	private function is_valid_agent_contact( $agent ) {
		$contact = isset( $agent['contact'] ) ? $agent['contact'] : '';
		$platform = isset( $agent['platform'] ) ? $agent['platform'] : 'whatsapp';
		if ( 'messenger' === $platform ) {
			return (bool) preg_match( '/^[A-Za-z0-9.]{3,50}$/', (string) $contact );
		}
		if ( 'viber' === $platform && preg_match( '#^https://#i', (string) $contact ) ) {
			$viber_url = wp_parse_url( $contact );
			$viber_path = isset( $viber_url['path'] ) ? trim( $viber_url['path'], '/' ) : '';
			return isset( $viber_url['host'] ) && in_array( strtolower( $viber_url['host'] ), array( 'viber.me', 'www.viber.me' ), true ) && (bool) preg_match( '/^\d{7,15}$/', $viber_path ) && empty( $viber_url['user'] ) && empty( $viber_url['pass'] ) && empty( $viber_url['port'] );
		}
		$digits = preg_replace( '/\D+/', '', (string) $contact );
		return strlen( $digits ) >= 7 && strlen( $digits ) <= 15;
	}

	private function active_agents() {
		$settings = $this->settings();
		return array_values( array_filter( $settings['agents'], array( $this, 'is_valid_agent_contact' ) ) );
	}

	public function enqueue_assets() {
		if ( empty( $this->active_agents() ) ) {
			return;
		}
		wp_enqueue_style( 'malc-contact-button', plugin_dir_url( __FILE__ ) . 'assets/contact-button.css', array(), '1.0.0' );
		wp_enqueue_script( 'malc-contact-button', plugin_dir_url( __FILE__ ) . 'assets/contact-button.js', array(), '1.0.0', true );
	}

	private function github_release() {
		$release = get_transient( 'malc_github_latest_release' );
		if ( false !== $release ) {
			return $release;
		}
		$response = wp_remote_get( 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest', array(
			'timeout' => 5,
			'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'Multi-Agent-Live-Chat-WordPress-Updater' ),
		) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( 'malc_github_latest_release', array(), HOUR_IN_SECONDS );
			return array();
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['tag_name'] ) || empty( $data['html_url'] ) ) {
			set_transient( 'malc_github_latest_release', array(), HOUR_IN_SECONDS );
			return array();
		}
		$version = ltrim( sanitize_text_field( $data['tag_name'] ), 'vV' );
		if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
			set_transient( 'malc_github_latest_release', array(), HOUR_IN_SECONDS );
			return array();
		}
		$release = array(
			'version' => $version,
			'url' => esc_url_raw( $data['html_url'] ),
			'package' => 'https://github.com/' . self::GITHUB_REPO . '/releases/latest/download/multi-agent-live-chat.zip',
			'body' => isset( $data['body'] ) ? wp_kses_post( $data['body'] ) : '',
		);
		set_transient( 'malc_github_latest_release', $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}

	public function check_github_release( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}
		$release = $this->github_release();
		$plugin = plugin_basename( __FILE__ );
		if ( ! empty( $release['version'] ) && isset( $transient->checked[ $plugin ] ) && version_compare( $release['version'], $transient->checked[ $plugin ], '>' ) ) {
			$transient->response[ $plugin ] = (object) array(
				'slug' => 'multi-agent-live-chat',
				'plugin' => $plugin,
				'new_version' => $release['version'],
				'url' => $release['url'],
				'package' => $release['package'],
			);
		}
		return $transient;
	}

	public function github_plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'multi-agent-live-chat' !== $args->slug ) {
			return $result;
		}
		$release = $this->github_release();
		if ( empty( $release['version'] ) ) {
			return $result;
		}
		return (object) array(
			'name' => 'Multi Agent Live Chat',
			'slug' => 'multi-agent-live-chat',
			'version' => $release['version'],
			'author' => '<a href="https://www.facebook.com/johnphiliptena">John Philip Tena</a>',
			'homepage' => $release['url'],
			'download_link' => $release['package'],
			'sections' => array(
				'description' => 'A responsive agent picker for WhatsApp, Viber, and Messenger. Conversations open in the selected service.',
				'changelog' => $release['body'],
			),
			'requires' => '5.8',
			'requires_php' => '7.4',
		);
	}

	private function contact_url( $contact, $platform, $text ) {
		if ( 'messenger' === $platform ) {
			return 'https://m.me/' . rawurlencode( $contact );
		}
		if ( 'viber' === $platform ) {
			if ( preg_match( '#^https://(www\.)?viber\.me/#i', $contact ) ) {
				return add_query_arg( 'draft', $text, $contact );
			}
			return 'https://viber.me/' . rawurlencode( $contact ) . '?draft=' . rawurlencode( $text );
		}
		return 'https://wa.me/' . rawurlencode( $contact ) . '?text=' . rawurlencode( $text );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = $this->settings();
		$agents = ! empty( $settings['agents'] ) ? $settings['agents'] : array( array( 'name' => '', 'contact' => '', 'platform' => 'whatsapp', 'visibility' => 'all' ) );
		?>
		<div class="wrap wla-admin">
			<h1><?php esc_html_e( 'Multi Agent Live Chat', 'multi-agent-live-chat' ); ?></h1>
			<p><?php esc_html_e( 'Give visitors a clear way to contact the right person through WhatsApp, Viber, or Messenger.', 'multi-agent-live-chat' ); ?></p>
			<?php settings_errors( self::OPTION ); ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'wla_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="wla-position"><?php esc_html_e( 'Button position', 'multi-agent-live-chat' ); ?></label></th><td>
						<select id="wla-position" name="<?php echo esc_attr( self::OPTION ); ?>[position]">
							<option value="left" <?php selected( $settings['position'], 'left' ); ?>><?php esc_html_e( 'Bottom left', 'multi-agent-live-chat' ); ?></option>
							<option value="right" <?php selected( $settings['position'], 'right' ); ?>><?php esc_html_e( 'Bottom right', 'multi-agent-live-chat' ); ?></option>
						</select>
					</td></tr>
					<tr><th scope="row"><label for="wla-text"><?php esc_html_e( 'Startup text', 'multi-agent-live-chat' ); ?></label></th><td>
						<input class="regular-text" id="wla-text" type="text" name="<?php echo esc_attr( self::OPTION ); ?>[text]" value="<?php echo esc_attr( $settings['text'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Shown on the button; WhatsApp and Viber also prefill it in the visitor’s message.', 'multi-agent-live-chat' ); ?></p>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Uninstall data', 'multi-agent-live-chat' ); ?></th><td>
						<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[delete_on_uninstall]" value="1" <?php checked( $settings['delete_on_uninstall'] ); ?> /> <?php esc_html_e( 'Delete this plugin’s saved settings when the plugin is deleted', 'multi-agent-live-chat' ); ?></label>
						<p class="description"><?php esc_html_e( 'Off by default, so reinstalling the plugin will keep your agent settings.', 'multi-agent-live-chat' ); ?></p>
					</td></tr>
				</table>
				<h2><?php esc_html_e( 'Agents', 'multi-agent-live-chat' ); ?></h2>
			<p><?php esc_html_e( 'Choose a service and where each agent is available. Contacts without a valid username or phone number are omitted from the visitor list. Viber Business chat links can be copied from Share Business; personal Viber numbers do not create public viber.me chat links.', 'multi-agent-live-chat' ); ?></p>
			<div id="wla-agents" data-max-agents="<?php echo esc_attr( self::MAX_AGENTS ); ?>">
				<?php foreach ( $agents as $index => $agent ) : ?>
					<?php $this->render_agent_row( $index, $agent ); ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button" id="wla-add-agent"><?php esc_html_e( 'Add agent', 'multi-agent-live-chat' ); ?></button>
			<p class="description wla-agent-limit" aria-live="polite"><?php echo esc_html( sprintf( __( 'You can configure up to %d agents.', 'multi-agent-live-chat' ), self::MAX_AGENTS ) ); ?></p>
			<template id="wla-agent-template"><?php $this->render_agent_row( '__INDEX__', array( 'name' => '', 'contact' => '', 'platform' => 'whatsapp', 'visibility' => 'all' ) ); ?></template>
			<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	private function render_agent_row( $index, $agent ) {
		$prefix = self::OPTION . '[agents][' . $index . ']';
		$contact = isset( $agent['contact'] ) ? $agent['contact'] : ( isset( $agent['mobile'] ) ? $agent['mobile'] : '' );
		$platform = isset( $agent['platform'] ) ? $agent['platform'] : 'whatsapp';
		$visibility = isset( $agent['visibility'] ) ? $agent['visibility'] : 'all';
		$is_messenger = 'messenger' === $platform;
		$is_viber = 'viber' === $platform;
		?>
		<div class="wla-agent-row">
			<label><?php esc_html_e( 'Service', 'multi-agent-live-chat' ); ?>
				<select class="wla-agent-platform" name="<?php echo esc_attr( $prefix ); ?>[platform]">
					<option value="whatsapp" <?php selected( $platform, 'whatsapp' ); ?>>WhatsApp</option>
					<option value="viber" <?php selected( $platform, 'viber' ); ?>>Viber</option>
					<option value="messenger" <?php selected( $platform, 'messenger' ); ?>>Messenger</option>
				</select>
			</label>
			<label><?php esc_html_e( 'Show on', 'multi-agent-live-chat' ); ?>
				<select name="<?php echo esc_attr( $prefix ); ?>[visibility]">
					<option value="all" <?php selected( $visibility, 'all' ); ?>><?php esc_html_e( 'Desktop and mobile', 'multi-agent-live-chat' ); ?></option>
					<option value="desktop" <?php selected( $visibility, 'desktop' ); ?>><?php esc_html_e( 'Desktop only', 'multi-agent-live-chat' ); ?></option>
					<option value="mobile" <?php selected( $visibility, 'mobile' ); ?>><?php esc_html_e( 'Mobile only', 'multi-agent-live-chat' ); ?></option>
				</select>
			</label>
			<label><?php esc_html_e( 'Name', 'multi-agent-live-chat' ); ?>
				<input type="text" class="regular-text" name="<?php echo esc_attr( $prefix ); ?>[name]" value="<?php echo esc_attr( $agent['name'] ); ?>" placeholder="<?php esc_attr_e( 'Agent name', 'multi-agent-live-chat' ); ?>" />
			</label>
			<label class="wla-contact-label"><?php echo $is_messenger ? esc_html__( 'Messenger username', 'multi-agent-live-chat' ) : ( $is_viber ? esc_html__( 'Viber chat link or business number', 'multi-agent-live-chat' ) : esc_html__( 'Mobile number', 'multi-agent-live-chat' ) ); ?>
				<input type="<?php echo ( $is_messenger || $is_viber ) ? 'text' : 'tel'; ?>" class="regular-text wla-agent-contact" name="<?php echo esc_attr( $prefix ); ?>[contact]" value="<?php echo esc_attr( $contact ); ?>" placeholder="<?php echo $is_messenger ? 'yourpage' : ( $is_viber ? 'https://viber.me/15551234567' : '15551234567' ); ?>" />
			</label>
			<button type="button" class="button-link-delete wla-remove-agent"><?php esc_html_e( 'Remove', 'multi-agent-live-chat' ); ?></button>
		</div>
		<?php
	}

	public function render_button() {
		$settings = $this->settings();
		$agents = $this->active_agents();
		if ( empty( $agents ) ) {
			return;
		}
		$position_class = 'wla-position-' . $settings['position'];
		?>
		<div class="wla-contact <?php echo esc_attr( $position_class . ' wla-platform-mixed' ); ?>">
			<div class="wla-agent-panel" id="wla-agent-panel" hidden>
				<div class="wla-agent-panel__heading">
					<strong><?php echo esc_html( $settings['text'] ); ?></strong>
					<button type="button" class="wla-panel-close" aria-label="<?php esc_attr_e( 'Close agent list', 'multi-agent-live-chat' ); ?>">&times;</button>
				</div>
				<div class="wla-agent-list">
					<?php foreach ( $agents as $agent ) :
						$contact = isset( $agent['contact'] ) ? $agent['contact'] : $agent['mobile'];
						$platform = isset( $agent['platform'] ) ? $agent['platform'] : ( isset( $settings['platform'] ) ? $settings['platform'] : 'whatsapp' );
						$visibility = isset( $agent['visibility'] ) ? $agent['visibility'] : 'all';
						$service = 'viber' === $platform ? 'Viber' : ( 'messenger' === $platform ? 'Messenger' : 'WhatsApp' );
						$url = $this->contact_url( $contact, $platform, $settings['text'] );
						$agent_name = $agent['name'] ? $agent['name'] : $service;
						?>
						<a class="wla-agent-link wla-agent-link--show-<?php echo esc_attr( $visibility ); ?>" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="wla-agent-link__avatar wla-agent-link__avatar--<?php echo esc_attr( $platform ); ?>" aria-hidden="true"><?php echo esc_html( strtoupper( substr( $agent_name, 0, 1 ) ) ); ?></span>
							<span><strong><?php echo esc_html( $agent_name ); ?></strong><small><?php echo esc_html( sprintf( __( 'Chat on %s', 'multi-agent-live-chat' ), $service ) ); ?></small></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
			<button type="button" class="wla-contact-button" aria-expanded="false" aria-controls="wla-agent-panel">
				<span class="wla-contact-button__icon" aria-hidden="true">
					<svg viewBox="0 0 32 32" focusable="false"><path d="M16 3C8.8 3 3 8.2 3 14.7c0 3.1 1.4 5.9 3.8 7.9.3.2.4.5.4.9v3.1c0 .5.5.8.9.6l3.5-1.6c.3-.1.5-.2.8-.1 1.1.3 2.3.4 3.6.4 7.2 0 13-5.2 13-11.7S23.2 3 16 3Zm-1.4 15.1-3.4-3.6-6.4 3.6 7.1-7.5 3.5 3.6 6.4-3.6-7.2 7.5Z"/></svg>
				</span>
				<span class="wla-contact-button__copy"><span class="wla-contact-button__text"><?php echo esc_html( $settings['text'] ); ?></span></span>
			</button>
		</div>
		<?php
	}
}

new Multi_Agent_Live_Chat();
