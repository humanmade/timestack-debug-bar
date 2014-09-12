<?php

/**
 * Plugin Name: Timestack Debug Bar
 * Description: Track the timeline of WordPress loading in the Debug Bar
 */

require_once dirname( __FILE__ ) . '/inc/class-timestack.php';
require_once dirname( __FILE__ ) . '/inc/class-timestack-operation.php';
require_once dirname( __FILE__ ) . '/inc/class-timestack-event.php';

Timestack::get_instance();

add_filter( 'debug_bar_panels', 'timestack_add_debug_bar_panel' );

function timestack_add_debug_bar_panel( $panels ) {

	$panels = array();
	require_once dirname( __FILE__ ) . '/inc/class-timestack-debug-bar-panel.php';

	$panels[] = new Timestack_Debug_Bar_Panel();

	return $panels;
}