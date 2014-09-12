<?php

class Timestack {

	private static $instance;
	public $stack;
	private $start_time;

	public static function get_instance() {

		if ( !isset( self::$instance ) )
			self::$instance = new static();

		return self::$instance;
	}

	function __construct() {

		global $hm_time_stack_start;

		if( !empty( $hm_time_stack_start ) )
			$this->start_time = $hm_time_stack_start;
		else
			$this->start_time = microtime( true );
		
		$this->setup_hooks();

		$this->stack = new Timestack_Operation( 'wp' );
		$this->stack->start_time = $this->start_time;
		
	}

	public function start_operation( $id, $vars = array() ) {

		if ( ! $this->stack )
			return;

		$operation = new Timestack_Operation( $id );
		$operation->vars = (array) $vars;

		$this->stack->add_operation( $operation );

	}

	public function end_operation() {
		if ( ! $this->stack )
			return;

		$this->stack->force_end_operation();
		//$this->stack->end_operation();
	}

	public function add_event( $id, $label = '' ) {

		$event = new Timestack_Event( $id, $label );
		$this->stack->add_event( $event );
	}

	private function setup_hooks() {

		$t = $this;

		// global adding from actions
		add_action( 'start_operation', function( $id, $args = array() ) use ( $t ) {

			$t->start_operation( $id, $args );

		}, 10, 2 );

		add_action( 'end_operation', function() use ( $t ) {

			$t->stack->end_operation();

		}, 10, 2 );

		add_action( 'add_event', function( $id, $label = '' ) use ( $t ) {

			$t->add_event( $id, $label );

		}, 10, 1 );

		add_action( 'log', function( $data ) {
			if ( is_scalar( $data ) )
				do_action( 'add_event', $data );
			else
				do_action( 'add_event', print_r( $data, true ) );
		} );



		add_action( 'parse_query', function( $wp_query ) use ( $t ) {

			$query = is_string( $wp_query->query ) ? $wp_query->query : json_encode( $wp_query->query );
			global $wp_the_query;

			if ( $wp_the_query == $wp_query ) {
				$name = 'Main WP Query';
			}

			else {
				$trace = debug_backtrace();

				if ( isset( $trace[6]['function'] ) && isset( $trace[7]['file'] ) )
					$name = $trace[6]['function'] . ' - ' . $trace[7]['file'] . '[' . $trace[7]['line'] . ']';
				else
					$name = 'WP_Query';
			}

			$t->start_operation( $name, $wp_query );

			$wp_query->query_vars['suppress_filters'] = false;
		}, 1 );

		add_action( 'the_posts', function( $posts, $wp_query ) use ( $t ) {
			$t->stack->end_operation();
			return $posts;
		}, 99, 2 );

		add_action( 'shutdown', function() use ( $t ) {

			$t->add_event( 'shutdown' );

		} );

		add_action( 'loop_start', function( $wp_query ) use ( $t ) {

			$query = is_string( $wp_query->query ) ? $wp_query->query : json_encode( $wp_query->query );
			$t->add_event( 'the_loop::' . spl_object_hash( $wp_query ), 'Loop Start' );

		}, 1 );

		add_action( 'all', function( $tag ) {

			if ( in_array( $tag, array( 'start_operation', 'end_operation' ) ) )
				return;
			
			$backtrace = debug_backtrace();

			if ( $backtrace[3]['function'] === 'do_action' ) {
				$this->add_event( 'Action Fired: ' . $tag );	
			}

			
			//$this->start_operation( 'hook: ' . $tag );

			//add_action( $tag, $_f = function() use ( &$_f, $tag ) {
				//$this->end_operation( 'hook: ' . $tag );
				//remove_action( $tag, $_f );
			//}, 99999 );
		}, 1 );

		add_action( 'loop_end', function( $wp_query ) use ( $t ) {

			$query = is_string( $wp_query->query ) ? $wp_query->query : json_encode( $wp_query->query );
			$t->add_event( 'the_loop::' . spl_object_hash( $wp_query ), 'Loop End' );

		}, 1 );

		add_action( 'get_sidebar', function( $name ) use ( $t ) {

			$t->add_event( 'get_sidebar', 'get_sidebar - ' . $name );

		}, 1 );

		// hooks for remote rewuest, (but hacky)
		add_filter( 'https_ssl_verify', function( $var ) {

			do_action( 'start_operation', 'Remote Request' );
			return $var;

		} );

		add_action( 'http_api_debug', function( $response, $type, $class, $args, $url ) use ( $t ) {
			$t->end_operation( 'Remote Request', array( 'url' => $url ) );
		}, 10, 5 );


		add_action( 'plugins_loaded', function() use ( $t ) {
			$t->add_event( 'loaded plugins' );
		}, 9999999 );
	}

	public function get_start_time() {
		return $this->start_time;
	}
}