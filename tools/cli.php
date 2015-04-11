<?php
if ( php_sapi_name() !== 'cli' || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}

// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 4 ) {
	echo "Missing parameters.\n";
	echo "Handle only many to many relationship.\n";
	echo "script usage: php rpt2p2p.php [domain] [relation] [post_type1] [post_type2] [delete (optionnal)]\n";
	die();
}

// Validate args data
$domain 		= ( isset( $argv[1] ) ) ? $argv[1] : '';
$relation 		= ( isset( $argv[2] ) ) ? $argv[2] : '';
$post_type1 	= ( isset( $argv[3] ) ) ? $argv[3] : '';
$post_type2 	= ( isset( $argv[4] ) ) ? $argv[4] : '';
$delete_table 	= ( isset( $argv[5] ) && 'delete' === $argv[5] ) ? true : false;

// Domain not set ?
if( empty($domain) ) {
	die('Missing domain for allow WP to init to right website. Required for WPMS.');
}

// Fake WordPress, build server array
$_SERVER = array(
	'HTTP_HOST'       => $domain,
	'SERVER_NAME'     => $domain,
	'REQUEST_URI'     => '/',
	'REQUEST_METHOD'  => 'GET',
	'SCRIPT_NAME'     => basename( __FILE__ ),
	'SCRIPT_FILENAME' => basename( __FILE__ ),
	'PHP_SELF'        => basename( __FILE__ )
);

@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

// Try to load WordPress core...
$bootstrap = 'wp-load.php';
while( !is_file( $bootstrap ) ) {
	if( is_dir( '..' ) ) 
		chdir( '..' );
	else
		die( 'EN: Could not find WordPress!' );
}
require_once( $bootstrap );

// Call need class
require_once( dirname( __FILE__ ) . '/../classes/class.import.php' );

// Exec
new RPT_to_P2P( $relation, $post_type1, $post_type2, $delete_table );
