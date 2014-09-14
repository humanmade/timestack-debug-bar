<?php

/**
 * Include this file directly in your wp-config.php to get more
 * accurate tracking on timestack information.
 */

require_once dirname( __FILE__ ) . '/inc/class-timestack.php';
require_once dirname( __FILE__ ) . '/inc/class-timestack-operation.php';
require_once dirname( __FILE__ ) . '/inc/class-timestack-event.php';

Timestack::get_instance();