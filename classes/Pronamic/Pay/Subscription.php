<?php

class Pronamic_Pay_Subscription {

	protected $id;

	//////////////////////////////////////////////////

	public $frequency;

	public $interval;

	public $interval_period;

	public $transaction_id;

	public $description;

	public $currency;

	public $amount;

	public $status;

	public $source;

	public $source_id;

	//////////////////////////////////////////////////

	/**
	 * Meta
	 *
	 * @var array
	 */
	public $meta;

	//////////////////////////////////////////////////

	/**
	 * Payments
	 *
	 * @var array
	 */
	public $payments;

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an payment object
	 */
	public function __construct() {
		$this->meta     = array();
		$this->payments = array();
	}

	//////////////////////////////////////////////////

	public function get_id() {
		return $this->id;
	}

	//////////////////////////////////////////////////

	public function get_source() {
		return $this->source;
	}

	public function get_source_id() {
		return $this->source_id;
	}

	//////////////////////////////////////////////////

	public function get_frequency() {
		return $this->frequency;
	}

	public function get_interval() {
		return $this->interval;
	}

	public function get_interval_period() {
		return $this->interval_period;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_currency() {
		return $this->currency;
	}

	public function get_amount() {
		return $this->amount;
	}

	//////////////////////////////////////////////////

	public function add_note( $note ) {
	}

	//////////////////////////////////////////////////

	public function get_transaction_id() {
		return $this->transaction_id;
	}

	public function set_transaction_id( $transaction_id ) {
		$this->transaction_id = $transaction_id;
	}

	//////////////////////////////////////////////////

	public function get_status() {
		return $this->status;
	}

	public function set_status( $status ) {
		$this->status = $status;
	}

	//////////////////////////////////////////////////

	public function get_meta( $key ) {
		$value = null;

		if ( isset( $this->meta[ $key ] ) ) {
			$value = $this->meta[ $key ];
		}

		return $value;
	}

	public function set_meta( $key, $value ) {
		$this->meta[ $key ] = $value;
	}
}