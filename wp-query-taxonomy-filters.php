<?php
/**
 * Plugin Name:       WP Query Block Extension - Frontend Taxonomy Filters
 * Description:       Add taxonomy filter in the frontend page that filters the posts returned from the query block.
 * Version:           0.1.1
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Author:            CTLT WordPress
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       query-taxonomy-filters
 *
 * @package           query-taxonomy-filters
 */

namespace UBC\CTLT\BLOCKS\QUERY_BLOCK\FILTERS\TAXONOMY;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'init', __NAMESPACE__ . '\\init' );
add_filter( 'pre_render_block', __NAMESPACE__ . '\\pre_render_block', 10, 2 );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type_from_metadata( __DIR__ . '/build' );
}

/**
 * Recursive function to retrieves all inner blocks of a given block with a specific inner block name.
 *
 * @param array  $block The block to search for inner blocks.
 * @param string $inner_block_name The name of the inner block to search for.
 * @return array An array of inner blocks with the specified name.
 */
function get_inner_blocks( $block, $inner_block_name ) {
	if ( ! array_key_exists( 'innerBlocks', $block ) || ! is_array( $block['innerBlocks'] ) ) {
		return array();
	}

	$inner_blocks = array();
	foreach ( $block['innerBlocks'] as $key => $inner_block ) {
		if ( $inner_block_name === $inner_block['blockName'] ) {
			array_push( $inner_blocks, $inner_block );
		} else {
			$inner_blocks = array_merge( $inner_blocks, get_inner_blocks( $inner_block, $inner_block_name ) );
		}
	}

	return $inner_blocks;
}

/**
 * Inject new tax query from the filter based on query ID.
 *
 * @param string|null $pre_render The pre-rendered content. Default null.
 * @param array       $parsed_block The block being rendered.
 *
 * @return string|null The modified pre-rendered block content or the original pre-rendered content if the block name is not 'core/query'.
 */
function pre_render_block( $pre_render, $parsed_block ) {
	if ( 'core/query' !== $parsed_block['blockName'] ) {
		return $pre_render;
	}

	$is_interactive = isset( $parsed_block['attrs']['enhancedPagination'] )
		&& true === $parsed_block['attrs']['enhancedPagination']
		&& isset( $parsed_block['attrs']['queryId'] );

	if ( ! $is_interactive ) {
		return $pre_render;
	}

	// Loop through innerblocks recursively to get all the custom field filters.
	$inner_tax_blocks = get_inner_blocks( $parsed_block, 'ctlt/query-taxonomy-filter' );

	// Creating a hash map. Key is the filter ID and value is the taxonomy type.
	$hash_map = array();
	foreach ( $inner_tax_blocks as $key => $inner_block ) {
		if ( array_key_exists( 'attrs', $inner_block ) &&
			array_key_exists( 'instanceId', $inner_block['attrs'] ) &&
			array_key_exists( 'selectedTaxonomyType', $inner_block['attrs'] )
		) {
			$hash_map[ $inner_block['attrs']['instanceId'] ] = $inner_block['attrs']['selectedTaxonomyType'];
		}
	}

	add_filter(
		'query_loop_block_query_vars',
		function ( $query, $block ) use ( $hash_map ) {
			$query_id        = $block->context['queryId'];
			$term_identifier = 'query-' . $query_id . '-term-';

			if ( ! array_key_exists( 'tax_query', $query ) ) {
				$query['tax_query'] = array();
			}

			// Loop through $_GET.
			foreach ( $_GET as $key => $value ) {
				// Check if the key matches the pattern $category_identifier.

				if ( preg_match( '/^' . $term_identifier . '(?<instance_id>\d+)$/', $key, $matches ) && ! empty( $value ) && array_key_exists( $matches['instance_id'], $hash_map ) ) {
					$terms = explode( ',', $value );

					$new_sub_tax_query = array(
						'taxonomy'         => $hash_map[ absint( $matches['instance_id'] ) ],
						'terms'            => $terms,
						'include_children' => false,
					);

					array_push( $query['tax_query'], $new_sub_tax_query );
				}
			}

			return $query;
		},
		10,
		2
	);

	return $pre_render;
}
