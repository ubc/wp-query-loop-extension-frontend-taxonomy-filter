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

$taxonomy_type    = sanitize_title( $attributes['selectedTaxonomyType'] );
$label            = sanitize_text_field( $attributes['label'] );
$accessible_label = sanitize_text_field( $attributes['accessibleLabel'] );
$child_only       = boolval( $attributes['childOnly'] );
$input_type       = sanitize_text_field( $attributes['inputType'] );
$all_tags         = boolval( $attributes['allTags'] );

$selected_dropdown_term_ids = array();

// Build the options to display in the dropdown/checkboxes.
if ( $child_only && 'category' === $taxonomy_type ) {
	if ( ! empty( $attributes['selectedTerms'] ) && is_array( $attributes['selectedTerms'] ) ) {
		foreach ( $attributes['selectedTerms'] as $term_id ) {
			$terms = get_terms(
				array(
					'taxonomy' => 'category',
					'parent'   => $term_id,
				)
			);

			if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $t ) {
					$selected_dropdown_term_ids[] = array(
						'name' => $t->name,
						'id'   => $t->term_id,
					);
				}
			}
		}
	}
} elseif ( $all_tags && 'post_tag' === $taxonomy_type ) {
	$terms = get_terms( array( 'taxonomy' => 'post_tag' ) );
	if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$selected_dropdown_term_ids[] = array(
				'name' => $t->name,
				'id'   => $t->term_id,
			);
		}
	}
} elseif ( ! empty( $attributes['selectedTerms'] ) && is_array( $attributes['selectedTerms'] ) ) {
	foreach ( $attributes['selectedTerms'] as $term_id ) {
		$t = get_term( $term_id );
		if ( $t && ! is_wp_error( $t ) ) {
			$selected_dropdown_term_ids[] = array(
				'name' => $t->name,
				'id'   => $term_id,
			);
		}
	}
}

$identifier    = 'query-' . $block->context['queryId'] . '-term-' . $attributes['instanceId'];
$selected_term = ( 'select' === $input_type ) ? '' : array();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! empty( $_GET[ $identifier ] ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$selected_term_raw = wp_parse_id_list( wp_unslash( $_GET[ $identifier ] ) );

	if ( 'select' === $input_type ) {
		$selected_term = ! empty( $selected_term_raw ) ? $selected_term_raw[0] : '';
	} else {
		$selected_term = $selected_term_raw;
	}
}

// Compute the ARIA label / screen reader legend text.
if ( ! empty( $accessible_label ) ) {
	$computed_label = $accessible_label;
} else {
	$selected_term_names = array();
	if ( ! empty( $attributes['selectedTerms'] ) && is_array( $attributes['selectedTerms'] ) ) {
		foreach ( $attributes['selectedTerms'] as $term_id ) {
			$t = get_term( $term_id );
			if ( $t && ! is_wp_error( $t ) ) {
				$selected_term_names[] = $t->name;
			}
		}
	}

	$terms_string   = wp_sprintf( '%l', $selected_term_names );
	$computed_label = trim( 'Filter by ' . $terms_string );
}

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
	data-wp-interactive="ctlt-query-tax-filter"
	data-wp-watch="callbacks.navigateToDestination"
	filter-id="<?php echo esc_attr( $attributes['instanceId'] ); ?>"
	<?php echo wp_interactivity_data_wp_context( array( 'selectedTerm' => $selected_term ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
>
	<div class="live-region screen-reader-text" aria-live="polite" aria-atomic="true"></div>
	<?php if ( 'select' === $input_type ) : ?>
		<label for="<?php echo esc_attr( $identifier ); ?>" class="screen-reader-text"><?php echo esc_html( $computed_label ); ?></label>
		<select
			data-wp-on--change="actions.onChangeTerm"
			data-wp-bind--value="context.selectedTerm"
			class="wp-query-filter__select"
			id="<?php echo esc_attr( $identifier ); ?>"
			aria-label="<?php echo esc_attr( $computed_label ); ?>"
		>
			<option value=""><?php echo esc_html( $label ); ?></option>
			<?php foreach ( $selected_dropdown_term_ids as $key => $dropdown_term ) : ?>
				<option
					value="<?php echo esc_attr( $dropdown_term['id'] ); ?>"
					<?php selected( $selected_term, $dropdown_term['id'] ); ?>
				>
					<?php echo esc_html( $dropdown_term['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php else : ?>
		<div class="wp-query-filter__checkboxes">
			<fieldset>
				<legend class="wp-query-filter__legend screen-reader-text"><?php echo esc_html( $computed_label ); ?></legend>
				<?php foreach ( $selected_dropdown_term_ids as $key => $dropdown_term ) : ?>
					<label for="<?php echo esc_attr( $identifier . '-checkbox-' . $dropdown_term['id'] ); ?>">
						<input
							id="<?php echo esc_attr( $identifier . '-checkbox-' . $dropdown_term['id'] ); ?>"
							type="checkbox"
							name="<?php echo esc_attr( 'query-' . $attributes['instanceId'] . '-term[]' ); ?>"
							value="<?php echo esc_attr( $dropdown_term['id'] ); ?>"
							data-wp-on--change="actions.onChangeTerm"
							class="wp-query-filter__checkbox"
							<?php checked( in_array( $dropdown_term['id'], $selected_term, true ) ); ?>
						/>
						<?php echo esc_html( $dropdown_term['name'] ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
		</div>
	<?php endif; ?>
</div>
