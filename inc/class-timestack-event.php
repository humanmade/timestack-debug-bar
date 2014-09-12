<?php

class Timestack_Event extends Timestack_Operation {

	public $id;
	public $time;

	function __construct( $id, $label = '' ) {

		$this->id = $id;
		$this->time = microtime(true) - Timestack::get_instance()->get_start_time();
		$this->start_time = microtime(true);
		$this->end_time = microtime(true);
		$this->children = array();
		$this->label = $label;
		$this->peak_memory_usage = round( memory_get_peak_usage() / 1024 / 1024, 3 );
	}

}