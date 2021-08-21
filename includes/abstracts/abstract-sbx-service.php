<?php
/**
 * Abstract Route Service Type
 *
 *
 *
 * @package  SealedBox/Service/Abstract
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default post meta registration arguments for Service Routes.
 *
 * @since   1.0.0
 * @var     string[]   SEALED_BOX_DEFAULT_POST_META_SETTINGS Default registration arguments.
 */
const SEALED_BOX_DEFAULT_POST_META_SETTINGS = array(
	'object_subtype'    => 'sbx_service_route',
	'description'       => '',
	'type'              => 'string',
	'single'            => true,
	'show_in_rest'      => false,
	'auth_callback'     => array(
		'SBX_Abstract_Service',
		'auth_callback'
	),
	'sanitize_callback' => 'sbx_clean',
);

/**
 * SBX_Abstract_Service Class.
 */
abstract class SBX_Abstract_Service {

	/** @var string $name service slug */
	protected $name = '';

	/** @var string $version service version. */
	protected $version = '';

	/** @var array $settings description */
	protected $settings = array();

	/**
	 * Get class instance
	 */
	public static function get_instance() {
		$classname = get_called_class();
		if ( null === $classname::$instance ) {
			$classname::$instance = new $classname();
		}

		return $classname::$instance;
	}
	public static function auth_callback() {
		return current_user_can( 'manage_options' );
	}

	private function __construct() {

		$this->define_settings();

		// fillters
		add_filter( 'sealed_box_service_route_data_meta_box_tab_settings', array( $this, 'get_data_meta_box_tab_settings' ), 10, 1 );
		add_filter( 'sealed_box_service_route_registered_meta_keys', array( $this, 'get_post_meta_keys' ), 10, 1 );
		add_filter( 'sealed_box_service_route_post_meta_settings', array( $this, 'get_post_meta_settings' ), 10, 1 );
		add_filter( 'sealed_box_' . $this->get_name() . '_service_route_registered_meta_keys', array( $this, 'get_post_meta_keys' ), 10, 1 );

		// actions
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 1 );
		add_action( 'sealed_box_after_register_post_type', array( $this, 'register_post_meta' ), 1 );
		add_action( 'perform_sealed_box_' . $this->get_name() . '_service', array( $this, 'perform_service' ), 10, 4 );

    }

    /**
     * Define service settings.
     *
     * @since   1.0.0
     * @return  void
     */
    protected function define_settings() {}

    /**
     * Add meta box.
     *
     * @since 1.0.0
     * @return  void
     */
    public function add_meta_box( $post ) {}

    /**
     * Process the payload thourgh this service type.
     *
     * @since   1.0.0
	 * @param   mixed             $payload
	 * @param   string            $cipher_text
	 * @param   integer           $route_id
     * @return  void
     */
    abstract public function perform_service( $payload, $cipher_text, $route_id, $rest_route );

	/**
	 * Return service name.
	 *
	 * @return string
	 */
	final public function get_name() {
		return $this->name;
	}

	/**
	 * Return service version.
	 *
	 * @return string
	 */
	final public function get_version() {
		return $this->version;
	}

	/**
	 * Return service information.
	 *
	 * @return string
	 */
	public function get_info() {
		return $this->settings['info'];
	}

	/**
	 * Return array of tabs to show.
	 *
	 * @return mixed[]
	 */
	public function get_data_meta_box_tab_settings( $settings = array() ) {
		return wp_parse_args( $this->settings['data_meta_box_tab_settings'], $settings );
	}

	/**
	 * Return array of route post meta settings.
	 *
	 * @return mixed[]
	 */
	public function get_post_meta_settings( $settings = array() ) {
		return wp_parse_args( $this->settings['post_meta_settings'], $settings );
	}

	/**
	 * Return array of route post meta keys.
	 *
	 * @return mixed[]
	 */
	public function get_post_meta_keys( $post_meta_keys = array() ) {
		return array_keys( $this->get_post_meta_settings( array_flip( $post_meta_keys ) ) );
	}

	/**
	 * Register REST route post meta.
	 *
	 * @since 1.0.0
	 */
	public function register_post_meta() {
		foreach ( $this->settings['post_meta_settings'] as $meta_key => $params ) {
			register_post_meta( 'sbx_service_route', sbx_prefix( $meta_key ), $params );
		}
    }

	/**
	 * Construct route.
	 *
	 * @param SBX_Service_Route|int $route Route instance or ID.
	 * @return SBX_Service_Routes
	 */
	final public function construct_route( $route = 0 ) {
        return new class( $route, $this ) extends SBX_Service_Route {

			/**
			 * Initialize route.
			 *
			 * @param SBX_Service_Route|int $route Route instance or ID.
			 * @param SBX_Abstract_Service  $service Service instance.
			 */
			public function __construct( $route = 0, SBX_Abstract_Service $service ) {
				parent::__construct( $route, $service );
				$this->service    = $service;
				$this->supports[] = 'feature';
				// $this->route;
				// $route_class = SBX_Service_Routes::get_classname_from_route_type( $this->service->get_name() );
			}

			/**
			 * Get internal type.
			 *
			 * @return string
			 */
			public function get_type( $context = 'view' ) {
				return $this->service->get_name();
			}
        };
    }
}
