<?php
/**
 * SealedBox route base class.
 *
 *
 *
 * @version  1.0.0
 * @package  SealedBox/Abstracts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract route class.
 *
 * The SealedBox route class handles individual route data.
 *
 * @version 1.0.0
 * @package SealedBox/Abstracts
 */
class SBX_Service_Route extends SBX_Service_Routes {

	/**
	 * Post ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $ID;

	/**
	 * ID of post author.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $post_author = 0;

	/**
	 * The post's local publication time.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $post_date = '0000-00-00 00:00:00';

	/**
	 * The post's GMT publication time.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $post_date_gmt = '0000-00-00 00:00:00';

	/**
	 * The post's status.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $post_status = 'publish';

	/**
	 * The post's password in plain text.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $post_password = '';

	/**
	 * The post's slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $post_name = 'route';

	/**
	 * The post's local modified time.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $post_modified = '0000-00-00 00:00:00';

	/**
	 * The post's GMT modified time.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 * Stores the post object's sanitization level.
	 *
	 * Does not correspond to a DB field.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $filter;

	/**
	 * Stores route data.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	// protected $registered_meta_keys = array();
	/*	'name'               => '',
		'slug'               => '',
		'date_created'       => null,
		'date_modified'      => null,
		'status'             => false,
		'featured'           => false,
		'catalog_visibility' => 'visible',
		'description'        => '',
		'short_description'  => '',
		'sku'                => '',
		'price'              => '',
		'regular_price'      => '',
		'sale_price'         => '',
		'date_on_sale_from'  => null,
		'date_on_sale_to'    => null,
		'total_sales'        => '0',
		'tax_status'         => 'taxable',
		'tax_class'          => '',
		'manage_stock'       => false,
		'stock_quantity'     => null,
		'stock_status'       => 'instock',
		'backorders'         => 'no',
		'low_stock_amount'   => '',
		'sold_individually'  => false,
		'weight'             => '',
		'length'             => '',
		'width'              => '',
		'height'             => '',
		'upsell_ids'         => array(),
		'cross_sell_ids'     => array(),
		'parent_id'          => 0,
		'reviews_allowed'    => true,
		'purchase_note'      => '',
		'attributes'         => array(),
		'default_attributes' => array(),
		'menu_order'         => 0,
		'post_password'      => '',
		'virtual'            => false,
		'downloadable'       => false,
		'service_id'       => array(),
		'tag_ids'            => array(),
		'shipping_class_id'  => 0,
		'downloads'          => array(),
		'image_id'           => '',
		'gallery_image_ids'  => array(),
		'download_limit'     => -1,
		'download_expiry'    => -1,
		'rating_counts'      => array(),
		'average_rating'     => 0,
		'review_count'       => 0,
	); */

	/**
	 * Supported features
	 * such as 'checksum_payloads'.
	 *
	 * @var array
	 */
	protected $supports = array();

	/**
	 * Service instance.
	 *
	 * @var SBX_Abstract_Service $service description.
	 */
	protected $service;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param SBX_Service_Route|object $post    Post object.
     * @param SBX_Abstract_Service     $service Service object.
     */
	protected function __construct( $route, $service ) {
		global $post;
		if ( empty( $post ) ) {
			$post = get_post( $route->ID );
		}
		$this->service = $service;
		foreach ( get_object_vars( $route ) as $key => $value ) {
            if ( isset( $value ) && empty( $this->$key ) || ! empty( $value ) ) {
				$post->$key = $value;
				$this->$key = $value;
            }
		}
    }

	/**
	 * Isset-er.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property Property to check if set.
	 * @return bool
	 */
	public function __isset( $property ) {
		$property = sbx_unprefix( $property );

		if ( in_array( sbx_prefix( $property ), $this->get_registered_meta_keys() ) ) {
			return metadata_exists( 'post', absint( $this->ID ), sbx_prefix( $property ) );
		}

		if ( 'route_type' === $property ) {
			return true;
		}

		if ( 'route_namespace' === $property ) {
			return true;
		}

		$prefixed             = 0 === strpos( $property, 'post_' );
		$property_with_prefix = 'post_' . $property;

		if ( ! $prefixed && null !== $this->$property_with_prefix ) {
			return true;
		}

		$property_method = "get_{$property}";
		$property_class  = get_called_class();

		if ( method_exists( $this, $property_method ) || method_exists( $property_class, $property_method ) ) {
			return true;
		}

		return metadata_exists( 'post', absint( $this->ID ), $property );
	}

	/**
	 * Set existing property.
	 *
	 * Encapsulation can be easily violated.
	 * Prevent the addition of properties beyond
	 * the class definition.
	 *
	 * @since 1.0.0
	 * @param   string $property   Name of property to set.
	 * @param   mixed  $value      Value to set.
	 * @return  void
	 * @throws  Exception When property does not exists.
	 */
	public function __set( $property, $value ): void {
		if ( property_exists( get_called_class(), $property ) ) {
			$this->$property = $value;
		} else {
			// throw new Exception( "Property {$property} does not exists.", 1 );
		}
    }

	/**
	 * Getter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property Property to get.
	 * @return mixed
	 */
	public function __get( $property ) {
		$property = sbx_unprefix( $property );

		if ( in_array( sbx_prefix( $property ), $this->get_registered_meta_keys() ) ) {
			return get_registered_metadata( 'post', absint( $this->ID ), sbx_prefix( $property ) );
		}

		if ( 'route_type' === $property ) {
			return sbx_get_route_type( $this->ID );
		}

		if ( 'route_namespace' === $property ) {
			return sbx_get_route_namespace( $this->ID );
		}

		$value                = null;
		$prefixed             = 0 === strpos( $property, 'post_' );
		$property_with_prefix = 'post_' . $property;

		$property_method = "get_{$property}";
		$property_class  = get_called_class();
		$filter_class    = strtolower( $property_class );

		if ( method_exists( $this, $property_method ) || method_exists( $property_class, $property_method )) {
			$value = $this->$property_method();

		} elseif ( property_exists( $this, $property ) && null !== $this->$property ) {
			$value = get_post_meta( absint( $this->ID ), $property, true );

		} elseif ( ! $prefixed && property_exists( $this, $property_with_prefix ) || property_exists( $this, $property_class ) ) {
			$value = $this->$property_with_prefix;
		}

		if ( $this->filter ) {
			$value = sanitize_post_field( $property, $value, absint( $this->ID ), $this->filter );
		}

		/**
		 * Filter the value, allowing adjustments to be made before returning.
		 *
		 * @since 1.0.0
		 * @param   mixed   $value      Value from data property or method.
		 * @param   string  $property   Name used to store the value.
		 * @param   object  $this       The instance of the calling class.
		 */
		$value = apply_filters( "{$filter_class}\\filter\\{$property}", $value, $property, $this );

		return $value;
	}

	/**
	 * {@Missing Summary}
	 *
	 * @since 1.0.0
	 *
	 * @param string $filter Filter.
	 * @return array|bool|object|SBX_Service_Route
	 */
	public function filter( $filter ) {
		if ( $this->filter === $filter ) {
			return $this;
		}

		if ( 'raw' === $filter ) {
			return self::get_route( $this->ID );
		}

		return sanitize_post( $this, $filter );
	}

	/**
	 * Get route id.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_id( $context = 'view' ) {
		return $this->ID;
	}

	/**
	 * Get internal type. Should return string and *should be overridden* by child classes.
	 *
	 * The route_type property is deprecated but is used here for BW compatibility with child classes which may be defining route_type and not have a get_type method.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_route_type();
	}

	/**
	 * Get route name.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->post_name;
	}

	/**
	 * Get route slug.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->post_name;
	}

	/**
	 * Get route created date.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return SBX_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->post_date_created;
	}

	/**
	 * Get route modified date.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return SBX_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->post_date_modified;
	}

	/**
	 * Get route status.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->post_status;
	}

	/**
	 * Get route description.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->description;
	}

	/**
	 * Get route short description.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_short_description( $context = 'view' ) {
		return $this->short_description;
	}

	/**
	 * Get post password.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_password( $context = 'view' ) {
		return $this->post_password;
	}

	/**
	 * Get service type slug.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_route_type( $context = 'view' ) {
		return sbx_get_term_nicename( $this->route_type );
	}

	/**
	 * Get namespace slug.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_namespace( $context = 'view' ) {
		return sbx_get_term_nicename( $this->route_namespace );
	}

	/**
	 * Get service type id.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_type_id( $context = 'view' ) {
		return sbx_get_route_type_id( $this->ID );
	}

	/**
	 * Get namespace id.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_namespace_id( $context = 'view' ) {
		return sbx_get_route_namespace_id( $this->ID );
	}

	/**
	 * Get tag IDs.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_tag_ids( $context = 'view' ) {
		return $this->tag_ids;
	}

	/**
	 * Get the service type API route.
	 *
	 * @return string
	 */
	public function get_rest_api_service_route( $context = 'view' ) {
		return site_url( '/wp-json/' . $this->get_namespace() . '/v2/service/' . $this->get_route_type() . '.' . $this->get_name() );
	}

	/**
	 * Get generated public key for post.
	 *
	 * @since 1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string|null
	 */
	public function get_public_key( $context = 'view' ) {

		if ( 'publish' !== $this->post_status || '' === $this->post_password ) {

			return null;
		}

		$author = get_userdata( $this->post_author );

		if ( ! $author || ! user_can( $author, 'manage_options' ) ) {

			return null;
		}

		/*

		Advanced Public-key Cryptography
		Sodium provides an API for Curve25519, a state-of-the-art
		Diffie-Hellman function suitable for a wide variety
		of applications.


		Sealed boxes
		(Anonymous Public-key Encryption)
		Sealed boxes are designed to anonymously send messages to a
		recipient given its public key.

		Only the recipient can decrypt these messages, using their
		private key. While the recipient can verify the integrity
		of the message, it cannot verify the identity of the sender.

		A message is encrypted using an ephemeral key pair, whose
		secret part is destroyed right after the encryption process.

		Without knowing the secret key used for a given message,
		the sender cannot decrypt its own message later. And
		without additional data, a message cannot be correlated
		with the identity of its sender.


		Sealed Box Encryption using
		This will encrypt a message with a user's public key.

			```sodium_crypto_box_seal(string $message, string $publickey) : string```


		Sealed Box Decryption
		Opens a sealed box with a keypair from your secret key and public key.

			```sodium_crypto_box_seal_open(string $message, string $recipient_keypair) : string```


		How to use sodium_crypto_box_seal
		2 people exchang a $message. person 1 encrypts it so that
		only person 2 can decrypt it. It does not allow person 2
		to know who sent it, as only their public key way used.

			```

		  ///////////////////////////
		 // Person 2, box keypair //
		///////////////////////////

			$keypair   = sodium_crypto_box_keypair();
			$secretkey = sodium_crypto_box_secretkey($keypair);
			$publickey = sodium_crypto_box_publickey($keypair);

			echo $publickey;


		  //////////////////////////
		 // Person 1, encrypting //
		//////////////////////////

			$message   = 'You will never know who sent this message?';
			$encrypted = sodium_crypto_box_seal($message, $publickey);

			echo base64_encode( $encrypted );


		  //////////////////////////
		 // Person 2, decrypting //
		//////////////////////////

			$publickey = sodium_crypto_box_publickey_from_secretkey($secretkey);
			$keypair   = sodium_crypto_box_keypair_from_secretkey_and_publickey($secretkey, $publickey);

			$decrypted = sodium_crypto_box_seal_open($encrypted, $keypair);

			echo $decrypted;


			```

		*/


		return rtrim(
			base64_encode(
				sodium_crypto_box_publickey(
					sodium_crypto_box_seed_keypair(
						$this->hash( $this->post_password, $this->post_modified_gmt, $author->user_login, $author->user_email, $this->post_name, SEALED_BOX_CRYPT['name'] )
			) ) ), '=' );
	}


	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting route data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

	/**
	 * Set route name.
	 *
	 * @since 1.0.0
	 * @param string $name Route name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Set route slug.
	 *
	 * @since 1.0.0
	 * @param string $slug Route slug.
	 */
	public function set_slug( $slug ) {
		$this->post_name = $slug;
	}

	/**
	 * Set route created date.
	 *
	 * @since 1.0.0
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->date_created = ( new DateTime( $date ) )->format( DATE_ATOM );
	}

	/**
	 * Set route modified date.
	 *
	 * @since 1.0.0
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->date_modified = ( new DateTime( $date ) )->format( DATE_ATOM );
	}

	/**
	 * Set route status.
	 *
	 * @since 1.0.0
	 * @param string $status Route status.
	 */
	public function set_status( $status ) {
		$this->post_status = $status;
	}

	/**
	 * Set route description.
	 *
	 * @since 1.0.0
	 * @param string $description Route description.
	 */
	public function set_description( $description ) {
		$this->description = $description;
	}

	/**
	 * Set route short description.
	 *
	 * @since 1.0.0
	 * @param string $short_description Route short description.
	 */
	public function set_short_description( $short_description ) {
		$this->short_description = $short_description;
	}

	/**
	 * Set post password.
	 *
	 * @since 1.0.0
	 * @param string $post_password Post password.
	 */
	public function set_post_password( $post_password ) {
		$this->post_password = $post_password;
	}

	/**
	 * Set the route service type.
	 *
	 * @since 1.0.0
	 * @param int $term_id Single terms ID.
	 */
	public function set_type_id( $term_id ) {
		$this->type_id = intval( $term_id );
	}

	/**
	 * Set the route namespace.
	 *
	 * @since 1.0.0
	 * @param int $term_id Single terms ID.
	 */
	public function set_namespace_id( $term_id ) {
		$this->namespace_id = intval( $term_id );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Return the service instance.
	 *
	 * @since 1.0.0
	 * @return SBX_Abstract_Service
	 */
	public function get_service() {
		return $this->service;
	}

	/**
	 * Return array of route post meta settings.
	 *
	 * @return mixed[]
	 */
	public function get_registered_meta_keys() {
		return array_map( 'sbx_prefix', $this->service->get_post_meta_keys() );
		// return array_map( 'sbx_prefix', apply_filters( 'sealed_box_' . $this->get_type() . '_service_route_registered_meta_keys', array() ) );
	}

	/**
	 * Return array of route post meta settings.
	 *
	 * @return mixed[]
	 */
	public function get_registered_metadata() {
		$data = wp_list_pluck( get_registered_metadata( 'post', absint( $this->ID ) ), 0 );
		$keys = array_flip( $this->get_registered_meta_keys() );
		$data = array_intersect_key( $data, $keys );
		$keys = array_map( 'sbx_unprefix', array_keys( $data ) );
		return array_combine( $keys, $data );
    }

	/**
	 * Encrypt data.
	 *
	 * @since 1.0.0
	 * @param  mixed $payload
	 * @return string|null
	 */
	public function encrypt_payload( $payload ) {
		return rawurlencode(
			base64_encode(
				sodium_crypto_box_seal(
					maybe_serialize( $payload ),
					base64_decode( $this->get_public_key() )
				)
			)
		);
	}

	/**
	 * Decrypt data.
	 *
	 * @since 1.0.0
	 * @param  string $cypher_text
	 * @return string|null
	 */
	public function decrypt_payload( string $cypher_text ) {

		if ( 'publish' !== $this->post_status || '' === $this->post_password ) {

			return null;
		}

		$author = get_userdata( $this->post_author );

		if ( ! $author || ! user_can( $author, 'manage_options' ) ) {

			return null;
		}

		return maybe_unserialize(
			sodium_crypto_box_seal_open(
				base64_decode( rawurldecode( $cypher_text ) ),
				sodium_crypto_box_seed_keypair(
					$this->hash(
						$this->post_password,
						$this->post_modified_gmt,
						$author->user_login,
						$author->user_email,
						$this->post_name,
						SEALED_BOX_CRYPT['name']
					)
				)
			)
		);
	}

	/**
	 * Route hash.
	 *
	 * @param  string[]    ...$entropy
	 * @return string
	 **/
	protected function hash( ...$entropy ) {
		return md5( implode( '', $entropy ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Check if a route supports a given feature.
	 *
	 * Route classes should override this to declare support (or lack of support) for a feature.
	 *
	 * @param  string $feature string The name of a feature to test support for.
	 * @return bool True if the route supports the feature, false otherwise.
	 * @since 1.0.0
	 */
	public function supports( $feature ) {
		return apply_filters( 'sealed_box_route_supports', in_array( $feature, $this->supports, true ), $feature, $this );
	}

	/**
	 * Returns whether or not the route post exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return false !== $this->get_status();
	}

	/**
	 * Checks the route type.
	 *
	 * Backwards compatibility with downloadable/virtual.
	 *
	 * @param  string|array $type Array or string of types.
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( $this->get_type() === $type || ( is_array( $type ) && in_array( $this->get_type(), $type, true ) ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the route's title. For routes this is the route name.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'sealed_box_route_title', $this->get_name(), $this );
	}

	/**
	 * Convert object to detail array.
	 *
	 * @since 1.0.0
	 *
	 * @return array Object as array.
	 */
	public function to_detail() {
		$post = $this->to_array();
		foreach ( array_keys( $post ) as $key ) {
			if ( 0 === strpos( $key, 'post_' ) || in_array( $key, array( 'ID', 'filter' ) ) ) {
				unset( $post[ $key ] );
			}
		}
		return $post;
	}

	/**
	 * Convert object to array.
	 *
	 * @since 1.0.0
	 *
	 * @return array Object as array.
	 */
	public function to_array() {
		$post = get_object_vars( $this );

		foreach ( array( 'type', 'namespace'/* , 'public_key' */ ) as $key ) {
			if ( isset( $this->$key ) ) {
				$post[ $key ] = $this->$key;
			}
		}

		return  array( 'registered_metadata' => $this->get_registered_metadata() ) + $post;
	}
}
