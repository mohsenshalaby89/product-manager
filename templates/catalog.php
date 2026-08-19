<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pm-catalog">
	<form class="pm-catalog__filters" method="get" action="<?php echo esc_url( $catalog_action_url ); ?>">
		<div class="pm-catalog__filter">
			<label class="pm-catalog__label" for="pm-catalog-search"><?php esc_html_e( 'Search products', 'product-manager' ); ?></label>
			<input
				id="pm-catalog-search"
				class="pm-catalog__input"
				type="search"
				name="pm_search"
				value="<?php echo esc_attr( $filters['search'] ); ?>"
			/>
		</div>

		<div class="pm-catalog__filter">
			<label class="pm-catalog__label" for="pm-catalog-category"><?php esc_html_e( 'Category', 'product-manager' ); ?></label>
			<select id="pm-catalog-category" class="pm-catalog__select" name="pm_category">
				<option value=""><?php esc_html_e( 'All Products', 'product-manager' ); ?></option>
				<?php foreach ( $categories as $category_option ) : ?>
					<option value="<?php echo esc_attr( $category_option['slug'] ); ?>" <?php selected( $filters['category'], $category_option['slug'] ); ?>>
						<?php echo esc_html( $category_option['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="pm-catalog__actions">
			<button class="pm-catalog__button" type="submit"><?php esc_html_e( 'Filter', 'product-manager' ); ?></button>
			<?php if ( $filters['has_active_filters'] ) : ?>
				<a class="pm-catalog__reset" href="<?php echo esc_url( $catalog_action_url ); ?>"><?php esc_html_e( 'Reset', 'product-manager' ); ?></a>
			<?php endif; ?>
		</div>
	</form>

	<?php if ( ! empty( $products ) ) : ?>
		<div class="pm-catalog__grid">
			<?php foreach ( $products as $product ) : ?>
				<?php include $product_card_template; ?>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="pm-catalog__empty">
			<p><?php esc_html_e( 'No products matched your current filters.', 'product-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $pagination_links ) ) : ?>
		<nav class="pm-catalog__pagination" aria-label="<?php esc_attr_e( 'Products pagination', 'product-manager' ); ?>">
			<?php foreach ( $pagination_links as $pagination_link ) : ?>
				<?php echo wp_kses_post( $pagination_link ); ?>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>
</div>
