<?php

spl_autoload_register( 'autoloader_doofinder_wp_api_classes' );

function autoloader_doofinder_wp_api_classes( $className ) {
	$libraryPrefix    = 'Doofinder\\WP\\Api\\';
	$libraryDirectory = __DIR__ . '/src/';

	$len = strlen( $libraryPrefix );

	// Binary safe comparison of $len first characters
	if ( strncmp( $libraryPrefix, $className, $len ) !== 0 ) {
		return;
	}

	$classPath = str_replace( '\\', '/', substr( $className, $len ) ) . '.php';
	$file      = $libraryDirectory . $classPath;

	if ( file_exists( $file ) ) {
		require $file;
	}
}
