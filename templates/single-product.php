<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_data = get_query_var( 'product_manager_single_product' );
$pm_product = is_array( $product_data ) ? $product_data : array();

$product_title = trim( (string) ( $pm_product['title'] ?? '' ) );
$product_content = (string) ( $pm_product['content'] ?? '' );
$product_category = trim( (string) ( $pm_product['category'] ?? '' ) );
$product_season = trim( (string) ( $pm_product['season'] ?? '' ) );
$product_sku = trim( (string) ( $pm_product['sku'] ?? '' ) );
$product_price = trim( (string) ( $pm_product['price'] ?? '' ) );
$product_availability = trim( (string) ( $pm_product['availability'] ?? '' ) );
$product_details = trim( (string) ( $pm_product['details'] ?? '' ) );

$company_name = trim( (string) ( $pm_product['company_name'] ?? '' ) );
$company_description = trim( (string) ( $pm_product['company_description'] ?? '' ) );
$company_email = trim( (string) ( $pm_product['company_email'] ?? '' ) );
$company_phone = trim( (string) ( $pm_product['company_phone'] ?? '' ) );
$company_website = trim( (string) ( $pm_product['company_website'] ?? '' ) );
$company_whatsapp = trim( (string) ( $pm_product['company_whatsapp'] ?? '' ) );

$gallery_ids = array();
$thumbnail_id = absint( $pm_product['thumbnail_id'] ?? 0 );
if ( $thumbnail_id > 0 ) {
	$gallery_ids[] = $thumbnail_id;
}

$gallery_meta = $pm_product['gallery'] ?? array();
if ( is_array( $gallery_meta ) && ! empty( $gallery_meta ) ) {
	foreach ( $gallery_meta as $gallery_item ) {
		$attachment_id = absint( $gallery_item );
		if ( $attachment_id > 0 && ! in_array( $attachment_id, $gallery_ids, true ) ) {
			$gallery_ids[] = $attachment_id;
		}
	}
}

$main_image_id = $gallery_ids[0] ?? 0;
$main_image_url = $main_image_id > 0 ? wp_get_attachment_image_url( $main_image_id, 'large' ) : '';
$safe_product_payload = is_array( $pm_product ) ? $pm_product : array();

get_header();
?>
<!-- Product Manager template v1.0.8 -->
<main class="pm-product-single-wrap">
	<article class="pm-product-single">
		<div class="pm-product-single__media">
			<div class="pm-product-single__main-image-wrap">
				<?php if ( ! empty( $main_image_url ) ) : ?>
					<img class="pm-product-single__main-image" src="<?php echo esc_url( $main_image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" />
				<?php else : ?>
					<div class="pm-product-single__image-placeholder"><?php esc_html_e( 'Product image coming soon', 'product-manager' ); ?></div>
				<?php endif; ?>
			</div>

			<?php if ( count( $gallery_ids ) > 1 ) : ?>
				<div class="pm-product-single__thumbs" aria-label="Product gallery">
					<?php foreach ( $gallery_ids as $gallery_image_id ) : ?>
						<?php $thumb_url = wp_get_attachment_image_url( (int) $gallery_image_id, 'thumbnail' ); ?>
						<?php if ( empty( $thumb_url ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<button type="button" class="pm-product-single__thumb <?php echo $gallery_image_id === $main_image_id ? 'is-active' : ''; ?>" data-image-src="<?php echo esc_url( (string) wp_get_attachment_image_url( (int) $gallery_image_id, 'large' ) ); ?>" aria-label="View image <?php echo esc_attr( (string) $gallery_image_id ); ?>">
							<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" />
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="pm-product-single__content">
			<div class="pm-product-single__header-block">
				<?php if ( '' !== $product_category ) : ?>
					<p class="pm-product-single__category"><?php echo esc_html( $product_category ); ?></p>
				<?php endif; ?>
				<h1 class="pm-product-single__title"><?php echo esc_html( $product_title ); ?></h1>
			</div>

			<div class="pm-product-single__info-box">
				<?php if ( '' !== $product_season ) : ?>
					<div class="pm-product-single__meta-item"><span><?php esc_html_e( 'Season', 'product-manager' ); ?>:</span> <?php echo esc_html( $product_season ); ?></div>
				<?php endif; ?>

				<?php if ( '' !== $product_sku ) : ?>
					<div class="pm-product-single__meta-item"><span><?php esc_html_e( 'SKU', 'product-manager' ); ?>:</span> <?php echo esc_html( $product_sku ); ?></div>
				<?php endif; ?>

				<?php if ( '' !== $product_price ) : ?>
					<div class="pm-product-single__meta-item"><span><?php esc_html_e( 'Price', 'product-manager' ); ?>:</span> <?php echo esc_html( $product_price ); ?></div>
				<?php endif; ?>

				<?php if ( '' !== $product_availability ) : ?>
					<div class="pm-product-single__meta-item"><span><?php esc_html_e( 'Availability', 'product-manager' ); ?>:</span> <?php echo esc_html( ucfirst( str_replace( '_', ' ', $product_availability ) ) ); ?></div>
				<?php endif; ?>
			</div>

			<div class="pm-product-single__description">
				<?php echo wp_kses_post( $product_content ); ?>
			</div>

			<?php if ( '' !== $product_details ) : ?>
				<div class="pm-product-single__details">
					<h2><?php esc_html_e( 'Technical Details', 'product-manager' ); ?></h2>
					<div><?php echo wp_kses_post( nl2br( esc_html( $product_details ) ) ); ?></div>
				</div>
			<?php endif; ?>

			<div class="pm-product-single__meta">
				<?php do_action( 'product_manager_single_product_meta', $safe_product_payload ); ?>
			</div>

			<div class="pm-product-single__contact">
				<?php if ( '' !== $company_name ) : ?>
					<h2><?php echo esc_html( $company_name ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== $company_description ) : ?>
					<p><?php echo esc_html( $company_description ); ?></p>
				<?php endif; ?>
				<div class="pm-product-single__contact-links">
					<?php if ( '' !== $company_email ) : ?>
						<a href="mailto:<?php echo esc_attr( $company_email ); ?>"><?php echo esc_html( $company_email ); ?></a>
					<?php endif; ?>
					<?php if ( '' !== $company_phone ) : ?>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $company_phone ) ); ?>"><?php echo esc_html( $company_phone ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( '' !== $company_website ) : ?>
					<a class="pm-product-single__cta" href="<?php echo esc_url( $company_website ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit Website', 'product-manager' ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $company_whatsapp ) : ?>
					<a class="pm-product-single__whatsapp" href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $company_whatsapp ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Contact via WhatsApp', 'product-manager' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</article>
</main>
<script>
(function() {
	var activeThumbs = document.querySelectorAll('.pm-product-single__thumb');
	if (!activeThumbs.length) {
		return;
	}

	var mainImage = document.querySelector('.pm-product-single__main-image');
	if (!mainImage) {
		return;
	}

	activeThumbs.forEach(function(button) {
		button.addEventListener('click', function() {
			var imageSrc = button.getAttribute('data-image-src');
			if (!imageSrc) {
				return;
			}
			mainImage.setAttribute('src', imageSrc);
			activeThumbs.forEach(function(item) { item.classList.remove('is-active'); });
			button.classList.add('is-active');
		});
	});
})();
</script>
<?php
get_footer();
