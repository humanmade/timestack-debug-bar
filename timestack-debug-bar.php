<?php

/**
 * Plugin Name: Timestack Debug Bar
 * Description: Track the timeline of WordPress loading in the Debug Bar
 */

add_filter( 'debug_bar_panels', 'timestack_add_debug_bar_panel' );

function timestack_add_debug_bar_panel( $panels ) {

	require_once dirname( __FILE__ ) . '/inc/class-timestack-debug-bar-panel-flamegraph.php';

	$panels[] = new Timestack_Debug_Bar_Panel_Flamegraph();

	return $panels;
}