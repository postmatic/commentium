<?php
namespace Postmatic\Commentium\Models;

use ReflectionObject;

/**
 * Model a scheduled callback.
 *
 * @since 0.1.0
 *
 */
class Scheduled_Callback {

	/** @var  int */
	protected $id;
	/** @var  int */
	protected $start_timestamp;
	/** @var  string */
	protected $started_on;
	/** @var  int */
	protected $recurrence_seconds;
	/** @var  string */
	protected $next_invocation_on;
	/** @var  string */
	protected $last_invocation_on;
	/** @var  array */
	protected $metadata;

	/**
	 * @since 0.1.0
	 *
	 * @param int $days
	 * @return int seconds
	 */
	public static function days_in_seconds( $days ) {
		return $days * DAY_IN_SECONDS;
	}

	/**
	 * @since 0.1.0
	 *
	 * @param int $seconds
	 * @return int days
	 */
	public static function seconds_in_days( $seconds ) {
		return $seconds / DAY_IN_SECONDS;
	}

	/**
	 *
	 * @since 0.1.0
	 *
	 * @param array $values {
	 *      Scheduled callback fields
	 * @var int $id Optional integer ID. Default null.
	 * @var int $start_timestamp Optional Unix timestamp for callback start time. Default tomorrow midnight.
	 * @var int $recurrence_seconds Optional seconds between callbacks. Default 7 days.
	 * @var array $metadata Optional array of metadata sent with callbacks. Default empty array.
	 * }
	 */
	public function __construct( $values = array() ) {

		$this->start_timestamp = strtotime( 'tomorrow' );
		$this->recurrence_seconds = self::days_in_seconds( 7 );
		$this->metadata = array();

		foreach ( $values as $key => $value ) {
			call_user_func( array( $this, 'set_' . $key ), $value );
		}
	}

	/**
	 * @since 0.1.0
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @since 0.1.0
	 * @param int $id
	 * @return $this
	 */
	public function set_id( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * @since 0.1.0
	 * @return int
	 */
	public function get_start_timestamp() {
		return $this->start_timestamp;
	}

	/**
	 * @since 0.1.0
	 * @param int $start_timestamp
	 * @return $this
	 */
	public function set_start_timestamp( $start_timestamp ) {
		$this->start_timestamp = $start_timestamp;
		return $this;
	}

	/**
	 * @since 0.1.0
	 * @return int
	 */
	public function get_recurrence_seconds() {
		return $this->recurrence_seconds;
	}

	/**
	 * @since 0.1.0
	 * @param int $recurrence_seconds
	 * @return $this
	 */
	public function set_recurrence_seconds( $recurrence_seconds ) {
		$this->recurrence_seconds = $recurrence_seconds;
		return $this;
	}

	/**
	 * @since 0.1.0
	 * @return string
	 */
	public function get_started_on() {
		return $this->started_on;
	}

	/**
	 * @since 0.1.0
	 * @param int|string $started_on
	 * @return $this
	 */
	public function set_started_on( $started_on ) {
		if ( is_string( $started_on ) ) {
			$this->started_on = $started_on;
		} else {
			$this->started_on = date( 'r', $started_on );
		}
		return $this;
	}

	/**
	 * @since 0.1.0
	 * @return string
	 */
	public function get_next_invocation_on() {
		return $this->next_invocation_on;
	}

	/**
	 * @since 0.1.0
	 * @param int|string $next_invocation_on
	 * @return $this
	 */
	public function set_next_invocation_on( $next_invocation_on ) {
		if ( is_string( $next_invocation_on ) ) {
			$this->next_invocation_on = $next_invocation_on;
		} else {
			$this->next_invocation_on = date( 'r', $next_invocation_on );
		}
		return $this;
	}

	/**
	 * @since 0.1.0
	 * @return string
	 */
	public function get_last_invocation_on() {
		return $this->last_invocation_on;
	}

	/**
	 * @since 0.1.0
	 * @param int|string $last_invocation_on
	 * @return $this
	 */
	public function set_last_invocation_on( $last_invocation_on ) {
		if ( is_string( $last_invocation_on ) ) {
			$this->last_invocation_on = $last_invocation_on;
		} else {
			$this->last_invocation_on = date( 'r', $last_invocation_on );
		}
		return $this;
	}

	/**
	 * @since 0.1.0
	 * @return array
	 */
	public function get_metadata() {
		return $this->metadata;
	}

	/**
	 * @since 0.1.0
	 * @param array $metadata
	 * @return $this
	 */
	public function set_metadata( $metadata ) {
		$this->metadata = $metadata;
		return $this;
	}

	/**
	 * @since 0.1.0
	 * @return int
	 */
	public function get_recurrence_days() {
		return self::seconds_in_days( $this->recurrence_seconds );
	}

	/**
	 * @since 0.1.0
	 * @param int $recurrence_days
	 * @return $this
	 */
	public function set_recurrence_days( $recurrence_days ) {
		$this->set_recurrence_seconds( self::days_in_seconds( $recurrence_days ) );
		return $this;
	}

	/**
	 * @since 0.1.0
	 * @return array
	 */
	public function to_array() {
		$reflection = new ReflectionObject( $this );
		$values = array();
		foreach ( $reflection->getProperties() as $property ) {
			$values[$property->getName()] = $this->{$property->getName()};
		}
		return $values;
	}

}