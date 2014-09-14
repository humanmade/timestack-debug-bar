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

		$this->start_time = microtime( true );
		
		$this->stack = new Timestack_Operation( 'wp', $this->start_time );

		if ( function_exists( 'add_action' ) ) {
			$this->setup_hooks();
		} else {
			$this->start_operation( 'Bootstrap' );

			$this->add_action_pre_load( 'muplugins_loaded', array( $this, 'setup_hooks' ), 1 );
		}

		$this->track_all_actions();
	}

	public function start_operation( $id, $vars = array() ) {

		if ( ! $this->stack )
			return;

		$operation = new Timestack_Operation( $id, $this->start_time );
		$operation->vars = (array) $vars;

		$this->stack->add_operation( $operation );

	}

	public function end_operation() {
		if ( ! $this->stack )
			return;

		$this->stack->end_operation();
	}

	public function add_event( $id ) {

		$event = new Timestack_Event( $id, $this->start_time );
		$this->stack->add_event( $event );
	}

	public function setup_hooks() {

		$t = $this;

		add_action( 'parse_query', function( $wp_query ) use ( $t ) {

			global $wp_the_query;

			if ( $wp_the_query == $wp_query ) {
				$name = 'Main WP Query';
			} else {
				$name = 'WP_Query';
			}

			$t->start_operation( $name, $wp_query );

			$wp_query->query_vars['suppress_filters'] = false;
		}, 1 );

		add_action( 'the_posts', function( $posts, $wp_query ) use ( $t ) {
			$t->stack->end_operation();
			return $posts;
		}, 99, 2 );


		add_action( 'loop_start', function( $wp_query ) use ( $t ) {

			$this->start_operation( 'loop_start' );

		}, 1 );

		add_action( 'loop_end', function( $wp_query ) use ( $t ) {

			$this->end_operation();

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
	}

	public function track_all_actions() {
		$this->add_action_pre_load( 'all', function( $tag ) {

			if ( empty( $this->_ended_bootstrap ) ) {
				$this->end_operation();
				$this->_ended_bootstrap = true;
			}

			if ( in_array( $tag, array( 'start_operation', 'end_operation' ) ) )
				return;
			
			$backtrace = debug_backtrace();

			if ( $backtrace[3]['function'] === 'do_action' ) {
				$this->add_event( 'Action Fired: ' . $tag );	

				$this->start_operation( $tag );
				add_action( $tag, $_ = function() use ( $tag, &$_ ) {

					remove_action( $tag, $_, 999999 );
				 	$t = $this->stack->end_operation();

				 	if ( $t !== $tag ) {
				 		// var_dump($tag);
				 		// var_dump($t);
				 		// print_r($this->stack);
				 		// exit;
				 	}
				}, 999999 );
			}

		}, 0 );

		// global adding from actions
		$this->add_action_pre_load( 'start_operation', function( $id, $args = array() ) {

			$this->start_operation( $id, $args );

		}, 10, 2 );

		$this->add_action_pre_load( 'end_operation', function() {

			$this->stack->end_operation();

		}, 10, 2 );

		$this->add_action_pre_load( 'add_event', function( $id ) {

			$this->add_event( $id, $label );

		}, 10, 1 );
	}

	function add_action_pre_load( $tag, $function, $priority = 10, $args = 1 ) {
		global $wp_filter;

		if ( $wp_filter === null ) {
			$wp_filter = null;
		}

		$wp_filter[$tag][$priority][] = array('function' => $function, 'accepted_args' => $args);
	}

	public function get_start_time() {
		return $this->start_time;
	}
}