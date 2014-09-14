<?php

class Timestack_Operation {

	public $start_time;
	public $end_time;
	public $duration = '';
	public $id;
	public $label;
	public $is_open;
	private $open_operation;
	public $children = array();
	public $peak_memory_usage;
	public $start_memory_usage;
	public $end_memory_usage;
	public $start_query_count;
	public $end_query_count;
	public $query_count;
	public $vars = array();
	public $time = 0;

	public function __construct( $id, $start_offset ) {

		$this->backtrace = array();
		$this->children = array();
		$this->id = $id;
		$this->start_time = microtime(true);

		$this->time = microtime(true) - $start_offset;

		$this->is_open = true;
		$this->start_memory_usage = memory_get_usage();

		global $wpdb;

		if ( ! defined( 'SAVEQUERIES' ) )
			define( 'SAVEQUERIES', true );

		if ( $wpdb ) {
			$this->start_query_count = count( $wpdb->queries );	
		} else {
			$this->start_query_count = 0;
		}
		
	}

	public function end() {
		$this->end_time = microtime(true);
		$this->end_memory_usage = memory_get_usage();
		$this->peak_memory_usage = $this->end_memory_usage - $this->start_memory_usage;
		$this->duration = $this->end_time - $this->start_time;
		$this->is_open = false;

		global $wpdb;

		if ( $wpdb ) {
			$this->end_query_count = count( $wpdb->queries );	
		} else {
			$this->end_query_count = 0;
		}

		$this->query_count = $this->end_query_count - $this->start_query_count;

		if ( ! empty( $wpdb->queries ) )
			$this->queries = array_slice( $wpdb->queries, $this->start_query_count );

		else
			$this->queries = array();
	}

	public function add_operation( $operation ) {

		if ( ! empty( $this->open_operation ) ) {
			$this->open_operation->add_operation( $operation );
		}

		else {
			$this->children[] = $operation;

			$this->open_operation = $operation;
		}
	}

	public function add_event( $event ) {

		if ( ! empty( $this->open_operation ) ) {
			$this->open_operation->add_event( $event );
		} else {
			$this->children[] = $event;
		}
	}

	public function end_operation() {

		if ( ! empty( $this->open_operation ) ) {

			$id = $this->open_operation->end_operation();

			if ( ! $this->open_operation->is_open ) {
				$this->open_operation = null;
			}
		} else {
			$this->end();
			$id = $this->id;
		}

		return $id;
	}

	public function force_end_operation() {

		if ( $this->open_operation ) {
			$this->open_operation->force_end_operation();
		}
		$this->end();
	}

	public function get_child_operation_by_id( $id ) {

		$return = null;

		foreach ( $this->children as $child ) {
			if ( $operation = $child->get_child_operation_by_id( $id ) ) {
				$return = $operation;
				break;
			}
		}

		if ( $this->is_open && $this->id == $id )
			$return = $this;

		return $return;
	}

	public function archive() {

		$archive = array(
			'id' => $this->id,
			'open' => $this->is_open
		);

		if ( $this->children ) {
			foreach( $this->children as $c ) {
				$archive['children'][] = $c->archive();
			}
		}

		return $archive;
	}

	public function get_vars() {
		$archive = array();

		if ( $this->queries ) {
			//$archive['queries']		= ! empty( $this->queries ) ? $this->queries : array();
			$archive['query_time']	= $this->get_query_time();	
		}
		
		$archive['Memory Usage'] = number_format( $this->peak_memory_usage / 1024 / 1024, 2 ) . ' mb';
		$archive['Backtrace'] = $this->backtrace;
		
		$archive = array_merge( $archive, $this->vars );

		return $archive;
	}

	private function get_query_time() {

		$query_time = 0;

		if ( !empty( $this->queries ) )
			foreach ( (array) $this->queries as $q )
				$query_time += $q[1];

	}

	public function get_label() {
		return $this->label ? $this->label : $this->id;
	}


}