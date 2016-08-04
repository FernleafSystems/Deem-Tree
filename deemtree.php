<?php

/**
 * Class ICWP_DeemTree
 *
 * @package Deemtree
 * @author Paul Goodchild <paul@icontrolwp.com>
 * @version 1.0.0
 */
class ICWP_DeemTree {

	/**
	 * @var ICWP_DeemTree
	 */
	public static $oInstance;

	/**
	 * @var string
	 */
	private static $sRootFile;

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
	 */
	public function __construct( $sRootFile ) {
		self::$sRootFile = $sRootFile;

		$this->init();

		// Register hooks
		$this->registerHooks();
	}

	protected function init() {
		$this->sNamespace = 'deemtree';
		$this->sHumanName = 'Deemtree';
		$this->sVersion = '1.0.0';
		$this->sOptionsStorageKey = $this->sNamespace.'_options';
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
	 * @return string The domain name for this Deemtree account
	 */
	public function getDeemtreeIframeDomain() {
		return $this->getDeemtreeOption( 'domain_name' );
	}

	/**
	 * @return string
	 */
	public function getDeemtreeIframeWidth() {
		$sWidth = $this->getDeemtreeOption( 'iframe_width' );
		return empty( $sWidth ) ? '100%' : $sWidth;
	}

	/**
	 * @return string
	 */
	public function getDeemtreeIframeHeight() {
		$sHeight = $this->getDeemtreeOption( 'iframe_height' );
		return empty( $sHeight ) ? '800px' : $sHeight;
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
		$sDeemtreeDomain = $this->getDeemtreeIframeDomain();
		if ( empty( $sDeemtreeDomain ) ) {
			$sFrame = 'Error: You must set your Deemtree domain in Settings > Business > Site';
		}
		else {
			$sFrame = sprintf(
				'<iframe src="https://%s.deemtree.com" width="%s" height="%s" frameBorder="0" ></iframe>',
				$this->getDeemtreeIframeDomain(),
				$this->getDeemtreeIframeWidth(),
				$this->getDeemtreeIframeHeight()
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
		require( $this->getRootDir().'views'.DIRECTORY_SEPARATOR.'options.php' );
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
		add_settings_section(
			'deemtree_settings_dimensions',
			'Deemtree iFrame Dimensions',
			array( $this, 'admin_section_deemtree_settings_dimensions' ),
			$this->getNamespace()
		);
		add_settings_field(
			'deemtree_domain_name',
			'Domain Name',
			array( $this, 'admin_option_domain_name' ),
			$this->getNamespace(),
			'deemtree_settings'
		);
		add_settings_field(
			'deemtree_iframe_width',
			'iFrame Width',
			array( $this, 'admin_option_iframe_width' ),
			$this->getNamespace(),
			'deemtree_settings_dimensions'
		);
		add_settings_field(
			'deemtree_iframe_height',
			'iFrame Height',
			array( $this, 'admin_option_iframe_height' ),
			$this->getNamespace(),
			'deemtree_settings_dimensions'
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

		if ( !empty( $aInput ) ) {

			$sDomainName = '';
			if ( !empty( $aInput['domain_name'] ) ) {
				// Remove padded whitespace
				$sDomainName = strtolower( trim( sanitize_text_field( $aInput['domain_name'] ) ) );
				if ( !preg_match( '#[-a-z0-9]+$#', $sDomainName ) ) {
					$sDomainName = '';
					add_settings_error( 'domain_name', $this->getNamespace() . '_domain_name_error', "Please enter a valid domain name", 'error' );
				}
			}
			$aOptions[ 'domain_name' ] = $sDomainName;

			$sIframeHeight = '';
			if ( !empty( $aInput['iframe_height'] ) ) {
				// Remove padded whitespace
				$sIframeHeight = strtolower( trim( sanitize_text_field( $aInput['iframe_height'] ) ) );
				if ( !preg_match( '#[0-9]+(px|%){1}$#', $sIframeHeight ) ) {
					$sIframeHeight = '';
					add_settings_error( 'iframe_height', $this->getNamespace() . '_iframe_width_error', "Please enter a valid iFrame Height", 'error' );
				}
			}
			$aOptions[ 'iframe_height' ] = $sIframeHeight;

			$sIframeWidth = '';
			if ( !empty( $aInput['iframe_width'] ) ) {
				// Remove padded whitespace
				$sIframeWidth = strtolower( trim( sanitize_text_field( $aInput['iframe_width'] ) ) );
				if ( !preg_match( '#[0-9]+(px|%){1}$#', $sIframeWidth ) ) {
					$sIframeWidth = '';
					add_settings_error( 'iframe_width', $this->getNamespace() . '_iframe_width_error', "Please enter a valid iFrame Width", 'error' );
				}
			}
			$aOptions[ 'iframe_width' ] = $sIframeWidth;

		}
		return $aOptions;
	}

	/**
	 */
	public function admin_option_domain_name() {
		echo sprintf(
			'<code>https://<input type="text" name="%s" size="20" value="%s">.deemtree.com</code>',
			sprintf( '%s[domain_name]', $this->getOptionsKey() ),
			$this->getDeemtreeOption( 'domain_name' )
		);
	}

	/**
	 */
	public function admin_option_iframe_width() {
		echo sprintf(
			'<input type="text" name="%s" size="10" value="%s">',
			sprintf( '%s[iframe_width]', $this->getOptionsKey() ),
			$this->getDeemtreeIframeWidth()
			).'<div>Use "px" or "%". Leaving blank will default to "100%"</div>';
	}

	/**
	 */
	public function admin_option_iframe_height() {
		echo sprintf(
			'<input type="text" name="%s" size="10" value="%s">',
			sprintf( '%s[iframe_height]', $this->getOptionsKey() ),
			$this->getDeemtreeIframeHeight()
			)
			.'<div>Use "px" or "%". Leaving blank will default to "800px"</div>';
	}

	/**
	 * Output the description for the Tracking Code settings section
	 */
	public function admin_section_deemtree_settings() {
		echo '<p>You can find your domain name under <a href="http://app.deemtree.com/Home/Update#/business" target="_blank">Settings &rarr; Business &rarr; Site</a> in your Deemtree account.</p>';
	}

	/**
	 * Output the description for the Tracking Code settings section
	 */
	public function admin_section_deemtree_settings_dimensions() {
		echo '<p>You can set the dimensions of your Deemtree display. Leave blank to use default settings.</p>';
	}

	/**
	 * Load stylesheet for the admin options page
	 *
	 * @uses wp_enqueue_style()
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style(
			sprintf( "%s_admin_css".rand(), $this->getNamespace() ),
			$this->getPluginUrl().'css/admin.css',
			array(),
			rand()
		);
	}

	/**
	 * @return string
	 */
	public function getPluginUrl() {
		return plugins_url( '/', $this->getRootFile() );
	}

	/**
	 * Get the root directory for the plugin with the trailing slash
	 *
	 * @return string
	 */
	public function getRootDir() {
		return dirname( $this->getRootFile() ).DIRECTORY_SEPARATOR;
	}

	/**
	 * @return string
	 */
	public function getRootFile() {
		if ( !isset( self::$sRootFile ) ) {
			self::$sRootFile = __FILE__;
		}
		return self::$sRootFile;
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
	 * @return ICWP_DeemTree|null
	 */
	static function GetInstance( $sRootFile ) {
		if ( !isset( self::$oInstance ) ) {
			try {
				self::$oInstance = new self( $sRootFile );
			}
			catch( Exception $oE ) {
				return null;
			}
		}
		return self::$oInstance;
	}
}