<?php
declare(strict_types=1);

namespace ProductManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsScreen
{
    private const OPTION_NAME = 'product_manager_settings';

    public function register(): void
    {
        register_setting(
            'product_manager_settings_group',
            self::OPTION_NAME,
            array(
                'type' => 'array',
                'sanitize_callback' => array( $this, 'sanitize' ),
                'default' => $this->get_default_settings(),
            )
        );

        add_settings_section(
            'product_manager_catalog_section',
            __( 'Catalog Defaults', 'product-manager' ),
            '__return_false',
            'product-manager-settings'
        );

        add_settings_field(
            'catalog_per_page',
            __( 'Products per page', 'product-manager' ),
            array( $this, 'renderPerPageField' ),
            'product-manager-settings',
            'product_manager_catalog_section'
        );

        add_settings_field(
            'catalog_orderby',
            __( 'Default sort field', 'product-manager' ),
            array( $this, 'renderOrderByField' ),
            'product-manager-settings',
            'product_manager_catalog_section'
        );

        add_settings_field(
            'catalog_order',
            __( 'Default sort direction', 'product-manager' ),
            array( $this, 'renderOrderField' ),
            'product-manager-settings',
            'product_manager_catalog_section'
        );
    }

    public function render(): void
    {
        if ( ! current_user_can( 'pm_manage_products' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage product settings.', 'product-manager' ) );
        }

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Settings', 'product-manager' ) . '</h1>';
        echo '<form method="post" action="options.php">';

        settings_fields( 'product_manager_settings_group' );
        do_settings_sections( 'product-manager-settings' );
        submit_button( __( 'Save Settings', 'product-manager' ) );

        echo '</form>';
        echo '</div>';
    }

    public function sanitize( $input ): array
    {
        $input = is_array( $input ) ? $input : array();
        $defaults = $this->get_default_settings();

        $per_page = isset( $input['catalog_per_page'] ) ? absint( $input['catalog_per_page'] ) : $defaults['catalog_per_page'];
        $per_page = max( 1, min( 48, $per_page ) );

        $orderby = isset( $input['catalog_orderby'] ) ? sanitize_key( (string) $input['catalog_orderby'] ) : $defaults['catalog_orderby'];
        $allowed_orderby = array( 'date', 'title', 'modified', 'menu_order' );
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = $defaults['catalog_orderby'];
        }

        $order = isset( $input['catalog_order'] ) ? strtoupper( sanitize_key( (string) $input['catalog_order'] ) ) : $defaults['catalog_order'];
        if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
            $order = $defaults['catalog_order'];
        }

        return array(
            'catalog_per_page' => $per_page,
            'catalog_orderby' => $orderby,
            'catalog_order' => $order,
        );
    }

    public function renderPerPageField(): void
    {
        $settings = $this->get_settings();
        echo '<input type="number" min="1" max="48" name="' . esc_attr( self::OPTION_NAME ) . '[catalog_per_page]" value="' . esc_attr( (string) $settings['catalog_per_page'] ) . '" class="small-text" />';
        echo '<p class="description">' . esc_html__( 'Controls the default number of products shown on the catalog shortcode.', 'product-manager' ) . '</p>';
    }

    public function renderOrderByField(): void
    {
        $settings = $this->get_settings();
        $current_value = (string) $settings['catalog_orderby'];

        echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[catalog_orderby]">';
        echo '<option value="date"' . selected( $current_value, 'date', false ) . '>' . esc_html__( 'Date', 'product-manager' ) . '</option>';
        echo '<option value="title"' . selected( $current_value, 'title', false ) . '>' . esc_html__( 'Title', 'product-manager' ) . '</option>';
        echo '<option value="modified"' . selected( $current_value, 'modified', false ) . '>' . esc_html__( 'Last Modified', 'product-manager' ) . '</option>';
        echo '<option value="menu_order"' . selected( $current_value, 'menu_order', false ) . '>' . esc_html__( 'Menu Order', 'product-manager' ) . '</option>';
        echo '</select>';
    }

    public function renderOrderField(): void
    {
        $settings = $this->get_settings();
        $current_value = (string) $settings['catalog_order'];

        echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[catalog_order]">';
        echo '<option value="DESC"' . selected( $current_value, 'DESC', false ) . '>' . esc_html__( 'Descending', 'product-manager' ) . '</option>';
        echo '<option value="ASC"' . selected( $current_value, 'ASC', false ) . '>' . esc_html__( 'Ascending', 'product-manager' ) . '</option>';
        echo '</select>';
    }

    public function get_settings(): array
    {
        $settings = get_option( self::OPTION_NAME, array() );

        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        return wp_parse_args( $settings, $this->get_default_settings() );
    }

    private function get_default_settings(): array
    {
        return array(
            'catalog_per_page' => 12,
            'catalog_orderby' => 'date',
            'catalog_order' => 'DESC',
        );
    }
}
