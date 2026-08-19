<?php
declare(strict_types=1);

namespace ProductManager\Company;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CompanySettingsService
{
    private const OPTION_NAME = 'product_manager_company_settings';

    public function get_default_settings(): array
    {
        return array(
            'company_name' => '',
            'company_description' => '',
            'company_email' => '',
            'company_phone' => '',
            'company_whatsapp' => '',
            'company_website' => '',
            'company_logo_id' => 0,
        );
    }

    public function get_settings(): array
    {
        $settings = get_option( self::OPTION_NAME, array() );

        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        return wp_parse_args( $settings, $this->get_default_settings() );
    }

    public function save_settings( array $input ): bool
    {
        $settings = $this->get_default_settings();

        foreach ( $settings as $key => $default_value ) {
            if ( ! array_key_exists( $key, $input ) ) {
                continue;
            }

            $value = $input[ $key ];

            if ( 'company_logo_id' === $key ) {
                $settings[ $key ] = absint( $value );
                continue;
            }

            if ( 'company_description' === $key ) {
                $settings[ $key ] = sanitize_textarea_field( wp_unslash( (string) $value ) );
                continue;
            }

            $settings[ $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
        }

        return update_option( self::OPTION_NAME, $settings, false );
    }

    public function get_contact_email(): string
    {
        $settings = $this->get_settings();

        return is_email( $settings['company_email'] ) ? $settings['company_email'] : '';
    }

    public function get_contact_phone(): string
    {
        $settings = $this->get_settings();

        return sanitize_text_field( $settings['company_phone'] );
    }

    public function get_whatsapp_number(): string
    {
        $settings = $this->get_settings();

        return sanitize_text_field( $settings['company_whatsapp'] );
    }

    public function get_company_name(): string
    {
        $settings = $this->get_settings();

        return sanitize_text_field( $settings['company_name'] );
    }

    public function get_company_description(): string
    {
        $settings = $this->get_settings();

        return sanitize_textarea_field( $settings['company_description'] );
    }

    public function get_logo_id(): int
    {
        $settings = $this->get_settings();

        return absint( $settings['company_logo_id'] );
    }
}
