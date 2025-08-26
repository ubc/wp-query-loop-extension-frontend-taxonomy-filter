<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 * @package query-taxonomy-filters
 */

$taxonomy_type = sanitize_title( $attributes['selectedTaxonomyType'] );
$label         = sanitize_text_field( $attributes['label'] );
$child_only    = boolval( $attributes['childOnly'] );
$input_type    = sanitize_text_field( $attributes['inputType'] );
$all_tags      = boolval( $attributes['allTags'] );

if ( true === $child_only && 'category' === $taxonomy_type ) {
	$selected_dropdown_term_ids = array();

	array_map(
		function ( $term_id ) use ( &$selected_dropdown_term_ids ) {
			$terms = get_terms(
				array(
					'taxonomy' => 'category',
					'parent'   => $term_id,
				)
			);

			$terms = array_map(
				function ( $term ) {
					return array(
						'name' => $term->name,
						'id'   => $term->term_id,
					);
				},
				$terms
			);

			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$selected_dropdown_term_ids = array_merge( $selected_dropdown_term_ids, $terms );
			}
		},
		$attributes['selectedTerms']
	);
} elseif ( true === $all_tags && 'post_tag' === $taxonomy_type ) {
	$terms = get_terms(
		array(
			'taxonomy' => 'post_tag',
		)
	);

	$selected_dropdown_term_ids = array_map(
		function ( $term ) {
			return array(
				'name' => $term->name,
				'id'   => $term->term_id,
			);
		},
		$terms
	);
} else {
	$selected_dropdown_term_ids = array_map(
		function ( $term_id ) {
			return array(
				'name' => get_term( $term_id )->name,
				'id'   => $term_id,
			);
		},
		$attributes['selectedTerms']
	);
}

$identifier = 'query-' . $block->context['queryId'] . '-term-' . $attributes['instanceId'];

if ( isset( $_GET[ $identifier ] ) && ! empty( $_GET[ $identifier ] ) ) {
	$selected_term = explode( ',', wp_unslash( $_GET[ $identifier ] ) );
} else {
	$selected_term = array();
}

$selected_term = array_map( 'absint', $selected_term );

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
	data-wp-interactive="ctlt-query-tax-filter"
	data-wp-watch="callbacks.navigateToDestination"
	filter-id="<?php echo esc_attr( $attributes['instanceId'] ); ?>"
	<?php echo wp_interactivity_data_wp_context( array( 'selectedTerm' => $selected_term ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
>
	<?php if ( 'select' === $input_type ) : ?>
		<select
			data-wp-on--change="actions.onChangeTerm"
			data-wp-bind--value="context.selectedTerm"
			class="wp-query-filter__select"
		>
			<option value=""><?php echo esc_html( $label ); ?></option>
			<?php foreach ( $selected_dropdown_term_ids as $key => $dropdown_term ) : ?>
				<option
					value="<?php echo esc_attr( $dropdown_term['id'] ); ?>"
				>
					<?php echo esc_html( $dropdown_term['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php else : ?>
		<div class="wp-query-filter__checkboxes">
			<fieldset>
				<legend class="wp-query-filter__legend visually-hidden">Taxonomy Filter, available terms in below list.</legend>
				<?php foreach ( $selected_dropdown_term_ids as $key => $dropdown_term ) : ?>
					<label>
						<input
							type="checkbox"
							name="<?php echo esc_attr( 'query-' . $attributes['instanceId'] . '-term[]' ); ?>"
							value="<?php echo esc_attr( $dropdown_term['id'] ); ?>"
							data-wp-on--change="actions.onChangeTerm"
							class="wp-query-filter__checkbox"
							<?php checked( in_array( $dropdown_term['id'], $selected_term ) ); ?>
						/>
						<?php echo esc_html( $dropdown_term['name'] ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
		</div>
	<?php endif; ?>
</div>
