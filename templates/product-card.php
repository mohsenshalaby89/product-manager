<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$season_text = trim( (string) ( $product['season'] ?? '' ) );
$details_text = trim( (string) ( $product['details'] ?? '' ) );
$availability_text = trim( (string) ( $product['availability'] ?? '' ) );
$price_text = trim( (string) ( $product['price'] ?? '' ) );

$info_rows = array();
if ( '' !== $season_text ) {
	$info_rows[] = $season_text;
}
if ( '' !== $details_text ) {
	$info_rows[] = $details_text;
}
if ( '' !== $availability_text ) {
	$availability_label = ucfirst( str_replace( '_', ' ', $availability_text ) );
	$info_rows[] = $availability_label;
}
if ( '' !== $price_text ) {
	$info_rows[] = $price_text;
}
if ( empty( $info_rows ) ) {
	$info_rows[] = __( 'As per request', 'product-manager' );
}
?>
<article class="pm-product-card">
	<a class="pm-product-card__image-link" href="<?php echo esc_url( $product['url'] ); ?>" aria-label="<?php echo esc_attr( $product['title'] ); ?>">
		<?php if ( ! empty( $product['thumbnail_id'] ) ) : ?>
			<?php echo wp_kses_post( wp_get_attachment_image( (int) $product['thumbnail_id'], 'medium_large', false, array( 'class' => 'pm-product-card__image' ) ) ); ?>
		<?php else : ?>
			<span class="pm-product-card__image-placeholder"><?php esc_html_e( 'No image available', 'product-manager' ); ?></span>
		<?php endif; ?>
	</a>

	<div class="pm-product-card__content">
		<h2 class="pm-product-card__title">
			<a href="<?php echo esc_url( $product['url'] ); ?>"><?php echo esc_html( $product['title'] ); ?></a>
		</h2>

		<div class="pm-product-card__info-list">
			<?php foreach ( array_slice( $info_rows, 0, 3 ) as $info_row ) : ?>
				<div class="pm-product-card__info-row"><?php echo esc_html( $info_row ); ?></div>
			<?php endforeach; ?>
		</div>
	</div>
</article>
