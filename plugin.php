<?php
/*
Plugin Name: Deemtree, Voucher Marketing Made Easy
Plugin URI: http://www.deemtree.com/?utm_source=wp-plugin&utm_medium=link&utm_campaign=plugins
Description:
Version: 1.0.0
Author: Deemtree
Author URI: http://www.deemtree.com
License: GPLv3
*/

// Include constants file
require_once( dirname( __FILE__ ) . '/lib/constants.php' );

class WP_DeemTree {

	/**
	 * @var string
	 */
	protected $sNamespace;

	/**
	 * @var string
	 */
	protected $sHumanName;

	/**
	 * @var string
	 */
	protected $sVersion;

	/**
	 * @var string
	 */
	protected $sOptionsStorageKey;

	/**
	 * @var array
	 */
	protected $aOptions;

	/**
	 * Instantiate a new instance
	 *
	 * @uses get_option()
	 */
	public function __construct() {

		$this->init();

		// Load all library files used by this plugin
		$libs = glob( WP_DEEMTREE_DIRNAME . '/lib/*.php' );
		foreach( $libs as $lib ) {
			include_once( $lib );
		}

		// Register hooks
		$this->registerHooks();
	}

	protected function init() {
		$this->sNamespace = 'deemtree';
		$this->sHumanName = "Deemtree";
		$this->sVersion = '1.0.0';
		$this->sOptionsStorageKey = $this->sNamespace."_options";
	}

	/**
	 * Sets default options upon activation
	 *
	 * Hook into register_activation_hook action
	 *
	 * @uses update_option()
	 */
	public function activate() {
		// Set default options
		if ( ! isset( $this->aOptions['domain_name'] ) ) {
			$this->aOptions['domain_name'] = '';
		}

		// Redirect to settings page
//		$this->options['do_redirect'] = true;

		// Save options
		update_option( $this->getOptionsKey(), $this->getOptions() );

		// Redirect to settings page
//		wp_redirect( $this->getSettingsPath() );
	}

	/**
	 * Clean up after deactivation
	 *
	 * Hook into register_deactivation_hook action
	 */
	public function deactivate() {
		// Deactivation stuff here...
	}

	/**
	 * Add various hooks and actions here
	 *
	 * @uses add_action()
	 */
	private function registerHooks() {
		// Activation and deactivation
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Options page for configuration
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Register admin settings
		add_action( 'admin_init', array( $this, 'admin_register_settings' ) );

		// Register activation redirect
//		add_action( 'admin_init', array( $this, 'do_activation_redirect' ) );

		// Add settings link on plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

		// Place tracking code in the footer
		add_shortcode( 'DEEMTREE', array( $this, 'printDeemtreeIframe' ) );
	}

	/**
	 * Lookup an option from the options array
	 *
	 * @param string $sOptionKey The name of the option you wish to retrieve
	 * @return mixed Returns the option value or NULL if the option is not set or empty
	 */
	public function getDeemtreeOption( $sOptionKey ) {
		$aOptions = $this->getOptions();
		if ( isset( $aOptions[ $sOptionKey ] ) ) {
			return $aOptions[ $sOptionKey ];
		}
		else {
			return null;
		}
	}

	/**
	 * Deletes an option from the options array
	 *
	 * @param string $sOptionKey The name of the option you wish to delete
	 * @uses update_option()
	 */
	public function deleteDeemtreeOption( $sOptionKey ) {
		unset( $this->aOptions[ $sOptionKey ] );
		update_option( $this->getOptionsKey(), $this->aOptions );
	}

	/**
	 * Look up the account ID from the options array
	 *
	 * @return string The domain name for this Deemtree account
	 */
	public function getDeemtreeDomain() {
		return $this->getDeemtreeOption( 'domain_name' );
	}

	/**
	 */
	public function printDeemtreeIframe() {
		echo $this->getDeemtreeIframe();
	}

	/**
	 * @return string
	 */
	public function getDeemtreeIframe() {
		$sDeemtreeDomain = $this->getDeemtreeDomain();
		if ( empty( $sDeemtreeDomain ) ) {
			$sFrame = 'Error: You must set your Deemtree domain in Settings -> Deemtree';
		}
		else {
			$sFrame = sprintf(
				'<iframe src="https://%s.deemtree.com" width="%s" height="%s" frameBorder="0" ></iframe>',
				$this->getDeemtreeDomain(),
				'100%',
				'800px'
			);
		}
		return $sFrame;
	}

	/**
	 * Performs a redirect to the settings page if the flag is set.
	 * To be called on admin_init action.
	 *
	 * @uses wp_redirect()
	 */
	public function do_activation_redirect() {
		if ( $this->getDeemtreeOption( 'do_redirect' ) ) {
			// Prevent future redirecting
			$this->deleteDeemtreeOption( 'do_redirect' );

			// Only redirect if it's a single activation
			if( ! isset( $_GET['activate-multi'] ) ) {
				wp_redirect( $this->getSettingsPath() );
			}
		}
	}

	/**
	 * Define the admin menu options for this plugin
	 *
	 * @uses add_options_page()
	 */
	public function admin_menu() {
		add_options_page(
			'Deemtree Settings',
			$this->getHumanName(),
			$this->getBasePermissions(),
			$this->getNamespace(),
			array( $this, 'admin_options_page' )
		);

		// Add admin scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * The admin section options page rendering method
	 *
	 * @uses current_user_can()
	 * @uses wp_die()
	 */
	public function admin_options_page() {
		// Ensure the user has sufficient permissions
		if ( ! current_user_can( $this->getBasePermissions() ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$sSettingsNamespace = $this->getNamespace();
		$sSettingsOptionKey = $this->getOptionsKey();
		require( WP_DEEMTREE_DIRNAME . DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'options.php' );
	}

	/**
	 * Add links on the plugin page
	 *
	 * @param array $aLinks An array of existing action links
	 * @return array Returns the new array of links
	 */
	public function plugin_action_links( $aLinks ) {
		// Ensure the user has sufficient permissions
		if ( current_user_can( $this->getBasePermissions() ) )  {
			$settings_link = sprintf( '<a href="%s">%s</a>', $this->getSettingsPath(), __( 'Settings' ) );
			array_unshift( $aLinks, $settings_link );
		}
		return $aLinks;
	}

	/**
	 * Register all the settings for the options page (Settings API)
	 *
	 * @uses register_setting()
	 * @uses add_settings_section()
	 * @uses add_settings_field()
	 */
	public function admin_register_settings() {
		add_settings_section(
			'deemtree_settings',
			'Deemtree Domain',
			array( $this, 'admin_section_deemtree_settings' ),
			$this->getNamespace()
		);
		add_settings_field(
			'deemtree_domain_name',
			'Domain Name',
			array( $this, 'admin_option_domain_name' ),
			$this->getNamespace(),
			'deemtree_settings'
		);

		// We only register a single option since we use the validate_settings() to
		// create the array of options later.
		register_setting(
			$this->getOptionsKey(),
			$this->getOptionsKey(),
			array( $this, 'validate_settings' )
		);
	}

	/**
	 * Validates user supplied settings and sanitizes the input
	 *
	 * @param array $aInput
	 * @return array Returns the set of sanitized options to save to the database
	 */
	public function validate_settings( $aInput ) {
		$aOptions = $this->getOptions();
		if ( !empty( $aInput ) && !empty( $aInput['domain_name'] ) ) {
			// Remove padded whitespace
			$sDomainName = strtolower( trim( $aInput['domain_name'] ) );
			if ( preg_match( '#[-a-z0-9]+$#', $sDomainName ) ) {
				$aOptions['domain_name'] = $sDomainName;
			}
			else {
				add_settings_error( 'domain_name', $this->getNamespace() . '_domain_name_error', "Please enter a valid domain name", 'error' );
			}
		}
		return $aOptions;
	}

	/**
	 * Output the input for the account ID option
	 */
	public function admin_option_domain_name() {
		echo sprintf(
			'https://<input type="text" name="%s" size="20" value="%s">.deemtree.com',
			sprintf( '%s[domain_name]', $this->getOptionsKey() ),
			$this->getDeemtreeOption( 'domain_name' )
		);
	}

	/**
	 * Output the description for the Tracking Code settings section
	 */
	public function admin_section_deemtree_settings() {
		echo '<p>You can find your domain name under <a href="http://app.deemtree.com/" target="_blank">Settings &rarr; Setup</a> in your Deemtree account.</p>';
	}

	/**
	 * Load stylesheet for the admin options page
	 *
	 * @uses wp_enqueue_style()
	 */
	function admin_enqueue_scripts() {
		wp_enqueue_style(
			sprintf( "%s_admin_css", $this->getNamespace() ),
			WP_DEEMTREE_URLPATH . "/css/admin.css"
		);
	}

	/**
	 * @return string
	 */
	public function getBasePermissions() {
		return 'manage_options';
	}

	/**
	 * @return string
	 */
	public function getNamespace() {
		return $this->sNamespace;
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		if ( !isset( $this->aOptions ) ) {
			$this->aOptions = get_option( $this->getOptionsKey() );
			if ( empty( $this->aOptions ) || !is_array( $this->aOptions ) ) {
				$this->aOptions = array();
			}
		}
		return $this->aOptions;
	}

	/**
	 * @return string
	 */
	public function getOptionsKey() {
		return $this->sOptionsStorageKey;
	}

	/**
	 * @return string
	 */
	public function getHumanName() {
		return $this->sHumanName;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->sVersion;
	}

	/**
	 * @return string
	 */
	public function getSettingsPath() {
		return 'options-general.php?page=' . $this->getNamespace();
	}

	/**
	 * Initialization function to hook into the WordPress init action
	 *
	 * Instantiates the class on a global variable and sets the class, actions
	 * etc. up for use.
	 */
	static function instance() {
		global $WP_DeemTree;

		// Only instantiate the Class if it hasn't been already
		if( ! isset( $WP_DeemTree ) ) $WP_DeemTree = new WP_DeemTree();
	}
}

if( !isset( $WP_DeemTree ) ) {
	WP_DeemTree::instance();
}