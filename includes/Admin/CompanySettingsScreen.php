<?php
declare(strict_types=1);

namespace ProductManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ProductManager\Company\CompanySettingsService;

final class CompanySettingsScreen
{
    private CompanySettingsService $companySettingsService;

    public function __construct( CompanySettingsService $companySettingsService )
    {
        $this->companySettingsService = $companySettingsService;
    }

    public function render(): void
    {
        if ( ! current_user_can( 'pm_manage_products' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage company settings.', 'product-manager' ) );
        }

        $settings = $this->companySettingsService->get_settings();
        $logo_id = absint( $settings['company_logo_id'] );

        echo '<div class="wrap product-manager-company-settings">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Company Settings', 'product-manager' ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'product_manager_save_company_settings', 'product_manager_company_settings_nonce' );
        echo '<input type="hidden" name="action" value="product_manager_save_company_settings" />';

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="company_name">' . esc_html__( 'Company Name', 'product-manager' ) . '</label></th><td><input type="text" id="company_name" name="company_name" value="' . esc_attr( $settings['company_name'] ) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="company_description">' . esc_html__( 'Company Description', 'product-manager' ) . '</label></th><td><textarea id="company_description" name="company_description" rows="5" class="large-text">' . esc_textarea( $settings['company_description'] ) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="company_email">' . esc_html__( 'Email', 'product-manager' ) . '</label></th><td><input type="email" id="company_email" name="company_email" value="' . esc_attr( $settings['company_email'] ) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="company_phone">' . esc_html__( 'Phone', 'product-manager' ) . '</label></th><td><input type="text" id="company_phone" name="company_phone" value="' . esc_attr( $settings['company_phone'] ) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="company_whatsapp">' . esc_html__( 'WhatsApp', 'product-manager' ) . '</label></th><td><input type="text" id="company_whatsapp" name="company_whatsapp" value="' . esc_attr( $settings['company_whatsapp'] ) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="company_website">' . esc_html__( 'Website', 'product-manager' ) . '</label></th><td><input type="url" id="company_website" name="company_website" value="' . esc_attr( $settings['company_website'] ) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label>' . esc_html__( 'Company Logo', 'product-manager' ) . '</label></th><td>';
        echo '<input type="hidden" id="company_logo_id" name="company_logo_id" value="' . esc_attr( (string) $logo_id ) . '" />';
        echo '<button type="button" class="button" data-product-manager-media data-target="company_logo_id" data-preview="company-logo-preview">' . esc_html__( 'Select logo', 'product-manager' ) . '</button>';
        echo '<div id="company-logo-preview" class="product-manager-company-logo-preview" style="margin-top:12px;">';
        if ( $logo_id > 0 ) {
            $logo_html = wp_get_attachment_image( $logo_id, array( 150, 150 ), false, array( 'style' => 'max-width:150px;height:auto;' ) );
            if ( $logo_html ) {
                echo $logo_html;
            }
        }
        echo '</div>';
        echo '</td></tr>';
        echo '</table>';

        submit_button( __( 'Save Company Settings', 'product-manager' ) );
        echo '</form>';
        echo '</div>';
    }
}
