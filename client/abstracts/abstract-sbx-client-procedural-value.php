<?php
/**
 * Sealed Box Client
 *
 * @package  SealedBox/Client
 * @version  3.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Abstract Client Procedural Value class.
 *
 * Derive API parameter values using the whitelist functions and procedural opperations
 * established within the response data of the external endopint OPTIONS request.
 */
abstract class SBX_Abstract_Client_Procedural_Value {

	/**
	 * Format strings for `sprintf()`.
	 *
	 * @var mixed[]
	 */
	const FORMAT = array(
		'ASSIGNMENT' => '%1$s:%2$s',
		'FILTER'     => '%1$s[%2$s]',
		'HOOK'       => '%1$s_%2$s',
	);

	/**
	 * Whitelist functions.
	 *
	 * @var string[]
	 */
	const WHITELIST = array();

	/**
	 * Alternate functions.
	 *
	 * @var mixed[]
	 */
	const ALTERNATE = array();

	/**
	 * Version.
	 *
	 * @var string client version.
	 */
	protected $version = '1.0.0';

	/**
	 * Filter hook.
	 *
	 * @var string
	 */
	protected $hook = '';

	/**
	 * Proccess data.
	 *
	 * @var mixed
	 */
	protected $data = array(
		'input'  => array(),
		'output' => array(),
		'value'  => array(),
		'filter' => array(),
	);

	/**
	 * Process values.
	 *
	 * @var string[][]
	 */
	protected $processing = array(
		'input'  => array(),
		'output' => array(),
	);

	/**
	 * Constructor.
	 *
	 * Add data and filters.
	 *
	 * @param  array       $data    Response to the OPTIONS requests of the external API endpoint.
	 * @param  null|string $suffix  Suffix string appened on the $hook property.
	 */
	public function __construct( array $data, ?string $suffix = null ) {
		$process = array();
		if ( $this->data_exists( $data['data'] ) ) {
			$process = $data['data'];
		}
		$this->update_values( $this->data, $data, $process );
		$this->add_filters( $suffix );
	}

	/**
	 * Get processed value.
	 *
	 * Create and update response values. The values
	 * are produced by filters defined in the response.
	 *
	 * @param  mixed  $input  Value used to process result.
	 * @return mixed  Proccessed results.
	 */
	abstract public function get_value( $input = null );

	/**
	 * Add filters.
	 *
	 * Register filters for proccessing values.
	 *
	 * @param null|string $suffix  Suffix string appened on the $hook property.
	 */
	protected function add_filters( ?string $suffix = null ) {
		$this->hook = trim( sprintf( static::FORMAT['HOOK'], strtolower( static::class ), (string) $suffix ), " \n\r\t\v\0_" ) ;
		foreach( $this->data['filter'] as $filter ) {
			$filter = explode( ',', $filter );
			list( $hook, $callback, $priority, $count ) = $filter;
			if ( 0 === strpos( $hook, $this->hook ) && in_array( $callback, static::WHITELIST ) ) {
				if ( in_array( $callback, static::ALTERNATE ) && function_exists( static::ALTERNATE[ $callback ] ) ) {
					$callback = static::ALTERNATE[ $callback ];
				}
				add_filter( $hook, $callback, $priority, $count );
			}
		}
	}

	/**
	 * Process values.
	 */
	public function process() {
		foreach( $this->processing as $index => &$set ) {
			if ( empty( $this->data[ $index ] ) ) {
				$this->process_value( $index, null, $set );
			} else {
				foreach( explode( ',', $this->data[ $index ] ) as $keys ) {
					$this->map_nested_values( $keys, null, $set );
					$current =& $this->get_nested( $keys, $set );
					$this->process_value( $index, $keys, $current );
				}
			}
		}
	}

	/**
	 * Assign values.
	 *
	 * Evaluate each value and assign values for
	 * items that match key names previously defined
	 * in input values.
	 *
	 * @param string      $index The index to source data.
	 * @param null|string $key   The values key name.
	 * @param mixed       &$set  Target variable to set.
	 */
	protected function process_value( string $index, ?string $key = null, &$set ) {
		$assignment = sprintf( static::FORMAT['ASSIGNMENT'], $index, (string) $key );
		if ( empty( $key ) ) {
			$key = $index;
		}
		if ( isset( $this->data['value'][ $assignment ] ) ) {
			$key = $assignment;
		}
		if ( isset( $this->data['value'][ $key ] ) ) {
			$this->assign_value( $key, $assignment, $set );
		}
	}

	/**
	 * Map and filter value assignment.
	 *
	 * Assign values from previously evaluated key names.
	 *
	 * @param string $key        Value key.
	 * @param string $assignment Value assignment key.
	 * @param mixed  &$set       Processing value to have value assigned.
	 */
	protected function assign_value( string $key, string $assignment, &$set ) {
		$filter = sprintf( static::FORMAT['FILTER'], $this->hook, $assignment );
		if ( $key === $assignment ) {
			$keys  = explode( ',', $this->data['value'][ $key ] );
			$value = array_map( array( $this, 'map_processing_values' ), $keys );
			$set   = apply_filters( $filter, ...$value );
		} else {
			$value = array();
			$key   = $this->map_nested_values( $key, $this->data['value'], $value );
			$set   = apply_filters( $filter, $value[ $key ] );
		}
	}

	/**
	 * Map values from previously evaluated key names.
	 *
	 * @param	string[]|string	$key Key to value.
	 * @return	mixed Mapped value.
	 */
	protected function map_processing_values( $keys ) {
		$key = current( explode( '.', $keys ) );
		if ( array_key_exists( $key, $this->processing ) ) {
			return $this->get_nested( $keys, $this->processing );
		}
		if ( is_array( $this->processing[ 'input' ] ) && array_key_exists( $key, $this->processing[ 'input' ] ) ) {
			return $this->get_nested( $keys, $this->processing[ 'input' ] );
		}
	}

	/**
	 * Check array for procedural value data.
	 *
	 * @param  array &$data	Array to check.
	 * @return bool Truthiness.
	 */
	protected static function data_exists( ?array &$data ) : bool {
		return isset( $data, $data['for'] ) && 'procedural-value' === $data['for'];
	}

    /**
     * Retrieve reference to nested value.
	 *
     * Access values in a multi-dimentional associative
     * array using a delimited path structure.
     *
     * @param  string       $keys    Keys to nested value.
     * @param  mixed|array  &$values Array containing nested value.
	 * @return mixed Reference to nested value.
     */
    public static function &get_nested( string $keys, &$values ) {
		foreach ( explode( '.', $keys ) as $key ) {
            if ( is_array( $values ) && array_key_exists( $key, $values ) ) {
                $values =& $values[ $key ];
            } else {
                $values = '';
				break;
            }
        }
        return $values;
    }

	/**
	 * Expand key and set the value to nested array.
	 *
     * @param  string $keys  Keys to nested value.
	 * @param  mixed  $value Value to be nested.
	 * @param  mixed  &$set  Reference to target data set.
	 * @return string Base key string.
	 */
	protected static function map_nested_values( $keys, $value, &$set ) {
		$map = array_reverse( explode( '.', $keys ) );
		$key = array_pop( $map );
		if ( ! empty( $map ) ) {
			if ( ! isset( $set ) ) {
				$set = array();
			}
			if ( is_array( $set ) && ! array_key_exists( $key, $set ) ) {
				$set[ $key ] = array();
			}
			$nested = array_reduce( $map, function( $value, $key ) {
				return array( $key => $value );
			}, $value );
			$value = array_merge_recursive( (array) $set[ $key ], $nested );
		}
		$set[ $key ] = $value;
		return $key;
	}

	/**
	 * Combine user values with known values and fill in defaults when needed.
	 *
	 * The pairs should be considered to be all of the values which are
	 * supported by the caller and given as a list. The returned values will
	 * only contain the values in the $set list.
	 *
	 * If the $values list has unsupported values, then will be ignored and
	 * removed from the final returned list.
	 *
	 * @param array      &$set   Entire list of supported values and their defaults.
	 * @param array[] ...$values User defined values.
	 */
	protected static function update_values( &$set, array ...$values ) {
		foreach ( $values as $value ) {
			$value = (array) $value;
			foreach ( array_keys( $set ) as $key ) {
				if ( array_key_exists( $key, $value ) ) {
					$set[ $key ] = $value[ $key ];
				}
			}
		}
	}
}
