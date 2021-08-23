<?php
/**
 * Plugin Name: Sealed Box Anonymous Service Routes
 * Plugin URI: https://WEBDOGS.COM/
 * Description: Anonymous Public-key Encryption: Sealed boxes are designed to anonymously send messages to a recipient given its public key.
 * Version: 1.0.0
 * Author: WEBDOGS LLC
 * Author URI: https://WEBDOGS.COM
 * Text Domain: sealedbox
 * Domain Path: /i18n/languages/
 *
 * @package SealedBox
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sealed Box identity terms.
 *
 * Identity term keys:
 *  • prefix
 *  • domain
 *  • package
 *  • slug
 *  • meta
 *  • label
 *  • name
 *  • title
 *  • plugin
 *
 * @since   1.0.0
 * @var     array   SEALED_BOX  Identity term strings.
 */
const SEALED_BOX = array(
	'prefix'  => 'sbx',
	'domain'  => 'sealedbox',
	'package' => 'SealedBox',
    'slug'    => 'sealed-box',
	'meta'    => 'sealed_box',
    'label'   => 'Sealed Box',
    'name'    => 'Sealed Box Routes',
    'title'   => 'Anonymous REST API Routes',
    'plugin'  => 'Sealed Box Anonymous Routes',
);

/**
 * Base type.
 *
 * @since   1.0.0
 * @var     array   SEALED_BOX_BASE Type.
 */
const SEALED_BOX_BASE = array(
	'type' => 'basic',
	'name' => 'Basic'
);

/**
 * Sealed Box service types.
 *
 * Built in service slug/name pairs:
 *  - basic: Basic
 *  - redirection: Redirection
 *
 * @since   1.0.0
 * @var     array   SEALED_BOX_BASE_SERVICES  Service type data.
 */
const SEALED_BOX_BUILT_IN_SERVICES = array(
	SEALED_BOX_BASE['type'] => SEALED_BOX_BASE['name'],
	'redirection'           => 'Redirection'
);

/**
 * Avanced public-key cryptography.
 *
 * Sodium provides an API for Curve25519, a state-of-the-art Diffie-Hellman
 * function suitable for a wide variety of applications.
 *
 * ---
 *
 * Sealed boxes
 *
 * (Anonymous Public-key Encryption)
 *
 * Sealed boxes are designed to anonymously send messages to a
 * recipient given its public key.
 *
 * private key. While the recipient can verify the integrity
 * Only the recipient can decrypt these messages, using their
 * of the message, it cannot verify the identity of the sender.
 *
 * secret part is destroyed right after the encryption process.
 * A message is encrypted using an ephemeral key pair, whose
 *
 * the sender cannot decrypt its own message later. And
 * Without knowing the secret key used for a given message,
 * without additional data, a message cannot be correlated
 * with the identity of its sender.
 *
 * ---
 *
 * Sealed Box Encryption
 *
 * Using this will encrypt a message with a user's public key.
 *
 *     ```
 *        sodium_crypto_box_seal( string $message, string $publicke y) : string
 *     ```
 *
 * ---
 *
 * Sealed Box Decryption
 *
 * Opens a sealed box with a keypair from your secret key and public key.
 *
 *     ```
 *        sodium_crypto_box_seal_open( string $message, string $recipient_keypair ) : string
 *     ```
 *
 * ---
 *
 * How to use sodium_crypto_box_seal
 *
 * 2 people exchang a $message. person 1 encrypts it so that
 * only person 2 can decrypt it. It does not allow person 2
 * to know who sent it, as only their public key way used.
 *
 *      ```
 *
 *       ///////////////////////////
 *      // Person 2, box keypair //
 *     ///////////////////////////
 *
 *      $keypair   = sodium_crypto_box_keypair();
 *      $secretkey = sodium_crypto_box_secretkey( $keypair );
 *      $publickey = sodium_crypto_box_publickey( $keypair );
 *
 *      echo $publickey;
 *
 *
 *       //////////////////////////
 *      // Person 1, encrypting //
 *     //////////////////////////
 *
 *      $message   = 'You will ever know who sent this message?';
 *      $encrypted = sodium_crypto_box_seal( $message, $publickey );
 *
 *      echo base64_encode( $encrypted );
 *
 *
 *       //////////////////////////
 *      // Person 2, decrypting //
 *     //////////////////////////
 *
 *      $publickey = sodium_crypto_box_publickey_from_secretkey( $secretkey );
 *      $keypair   = sodium_crypto_box_keypair_from_secretkey_and_publickey( $secretkey, $publickey );
 *
 *      $decrypted = sodium_crypto_box_seal_open( $encrypted, $keypair );
 *
 *      echo $decrypted;
 *
 *
 *      ```
 *
 * @var	array  SEALED_BOX_CRYPT  Crypt function.
 */
const SEALED_BOX_CRYPT = array(
	'type' => 'curve25519',
	'name' => 'Curve25519',
);

/**
 * Sealed Box plugin file.
 *
 * The full path and filename of this file.
 *
 * @var	string  SBX_PLUGIN_FILE  Plugin path/filename string.
 */
const SBX_PLUGIN_FILE    = __FILE__;
const SBX_ABSPATH        = __DIR__ . '/';

/**
 * Sealed Box plugin version.
 *
 * @var	string  SEALED_BOX_VERSION  Version string.
 */
const SEALED_BOX_VERSION = '1.0.0';

/**
 * Sealed Box singelton class.
 *
 * @version 1.0.0
 */
class SealedBox {

    /**
     * Plugin version.
	 *
     * @var string $version
     */
    public $version = SEALED_BOX_VERSION;

    /**
     * Array of services.
     *
     * @var SBX_Services[]
     */
    protected $services;

    /**
     * Post types object.
     *
     * @var SBX_Post_Types
     */
    protected $post_types;
    /**
     * SealedBox singelton class instance.
     *
     * Return the class instance from a staic variable. The class instance
     * is constructed to the staic variable durring the inital method call.
     *
     * @return SealedBox
     */
    public static function get_instance(): SealedBox {
        static $sealed_box = null;

        if ( null === $sealed_box ) {
            $sealed_box = new SealedBox();
        }

        return $sealed_box;
    }

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'sealed_box' ), $this->version );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'sealed_box' ), $this->version );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'services' ), true ) ) {
			return $this->services;
		}
	}

    /**
     * SealedBox constructor.
	 *
	 * @since 1.0.0
     */
    private function __construct() {
		$this->includes();
		$this->init_hooks();
    }

	/**
	 * When WP has loaded all plugins, trigger the `sealed_box_loaded` hook.
	 *
	 * This ensures `sealed_box_loaded` is called only after all other plugins
	 * are loaded, to avoid issues caused by plugin directory naming changing
	 * the load order.
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded() {
		// Load class instances.
		$this->services = new SBX_Services();
		$this->post_types = new SBX_Post_Types();
		do_action( 'sealed_box_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -1 );
        add_action( 'init', array( $this, 'init' ), 10 );

        if ( is_admin() ) {
			add_action( 'admin_footer', 'sbx_print_js', 25 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_register_scripts' ), 1, 1 );
        }
    }

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
        include_once SBX_ABSPATH . 'includes/class-sbx-autoloader.php';

		/**
		 * Abstract classes.
		 */
        include_once SBX_ABSPATH . 'includes/abstracts/abstract-sbx-service.php';
        include_once SBX_ABSPATH . 'includes/abstracts/abstract-sbx-service-routes.php';
        include_once SBX_ABSPATH . 'includes/abstracts/abstract-sbx-service-route.php';

		/**
		 * Core classes and functions.
		 */
        include_once SBX_ABSPATH . 'includes/sbx-core-functions.php';
        include_once SBX_ABSPATH . 'includes/class-sbx-services.php';
        include_once SBX_ABSPATH . 'includes/class-sbx-rest-route.php';
        include_once SBX_ABSPATH . 'includes/class-sbx-post-types.php';
        include_once SBX_ABSPATH . 'client/sbx-client-functions.php';

        if ( is_admin() ) {
            /**
             * Admin classes and functions.
             */
            include_once SBX_ABSPATH . 'includes/admin/sbx-meta-box-functions.php';
            include_once SBX_ABSPATH . 'includes/admin/class-sbx-admin-post-types.php';
            include_once SBX_ABSPATH . 'includes/admin/class-sbx-admin-taxonomies.php';
        }
    }

	/**
	 * Init SealedBox when WordPress Initialises.
	 */
	public function init() {

		// Before init action.
		do_action( 'before_sealed_box_init' );

		// $this->route = new SBX_REST_Routes();

		// Init action.
		do_action( 'sealed_box_init' );
	}

	/**
	 * Enqueue Scripts
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function admin_register_scripts( $hook ) {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style( 'jquery-ui-style', plugins_url( 'assets/css/jquery-ui/jquery-ui.min.css', SBX_PLUGIN_FILE ), array(), $this->version );

		wp_register_script( 'sbx-term-input', plugins_url( 'assets/js/sbx-term-input' . $suffix . '.js', SBX_PLUGIN_FILE ), array( 'jquery', 'inline-edit-post' ), $this->version, true );
		wp_register_script( 'jquery-tiptip', plugins_url( 'assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', SBX_PLUGIN_FILE ), array( 'jquery' ), $this->version, true );
	}
}

/**
 * Sealed Box class function.
 *
 * @since 1.0.0
 */
function SealedBox(): SealedBox {
    return SealedBox::get_instance();
}
SealedBox();