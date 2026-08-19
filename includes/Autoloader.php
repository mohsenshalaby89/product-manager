<?php
declare(strict_types=1);

namespace ProductManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    public static function autoload( string $class ): void
    {
        $prefix = 'ProductManager\\';

        if ( 0 !== strpos( $class, $prefix ) ) {
            return;
        }

        $relative_class = substr( $class, strlen( $prefix ) );
        $path = dirname( __DIR__ ) . '/includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}
