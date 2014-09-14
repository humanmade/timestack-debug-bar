<?php

class Timestack_Event extends Timestack_Operation {

	public $id;
	public $time;

	function __construct( $id, $start_time ) {
		parent::__construct( $id, $start_time );
		$this->end_time = microtime(true);
		$this->peak_memory_usage = round( memory_get_peak_usage() / 1024 / 1024, 3 );
		$this->is_open = false;
	}

}