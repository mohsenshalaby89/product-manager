<?php
declare(strict_types=1);

namespace ProductManager\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivator
{
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
