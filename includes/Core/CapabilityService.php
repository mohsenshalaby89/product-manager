<?php
declare(strict_types=1);

namespace ProductManager\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CapabilityService
{
    private const CAPABILITY_VERSION = 3;

    public function ensure_capabilities(): void
    {
        $version = (int) get_option( 'product_manager_capability_version', 0 );

        $this->assign_capabilities();
        $this->register_product_manager_role();

        if ( $version >= self::CAPABILITY_VERSION ) {
            return;
        }

        update_option( 'product_manager_capability_version', self::CAPABILITY_VERSION );
    }

    public function get_capabilities(): array
    {
        return array(
            'pm_manage_products',
            'pm_edit_products',
            'pm_edit_others_products',
            'pm_publish_products',
            'pm_delete_products',
            'pm_manage_categories',
        );
    }

    public function get_product_manager_role_capabilities(): array
    {
        $caps = array(
            'read' => true,
            'upload_files' => true,
        );

        foreach ( $this->get_capabilities() as $capability ) {
            $caps[ $capability ] = true;
        }

        return $caps;
    }

    private function assign_capabilities(): void
    {
        $roles_to_update = array( 'administrator', 'editor', 'product_manager' );

        foreach ( $roles_to_update as $role_name ) {
            $role = get_role( $role_name );

            if ( ! $role instanceof \WP_Role ) {
                continue;
            }

            foreach ( $this->get_capabilities() as $capability ) {
                $role->add_cap( $capability );
            }
        }
    }

    private function register_product_manager_role(): void
    {
        $existing_role = get_role( 'product_manager' );

        if ( $existing_role instanceof \WP_Role ) {
            foreach ( $this->get_product_manager_role_capabilities() as $capability => $granted ) {
                if ( $granted ) {
                    $existing_role->add_cap( $capability );
                }
            }

            return;
        }

        add_role(
            'product_manager',
            __( 'Product Manager', 'product-manager' ),
            $this->get_product_manager_role_capabilities()
        );
    }
}
