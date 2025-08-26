/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */

import { TaxonomyItem } from './components';
import { useEntityRecords } from '@wordpress/core-data';
import { useInstanceId } from '@wordpress/compose';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @param {Object}   props               Properties passed to the function.
 * @param {Object}   props.attributes    Available block attributes.
 * @param {Function} props.setAttributes Function that updates individual attributes.
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();

	const { selectedTerms, instanceId, selectedTaxonomyType, label, childOnly, inputType, allTags } = attributes;

	const query = {
		per_page: -1,
		hideEmpty: true,
		context: 'view',
	};

	const inputTypes = [
		{ label: 'Select (Single)', value: 'select' },
		{ label: 'Checkboxes (Multiple)', value: 'checkboxes' },
	];

	const { records: categories } = useEntityRecords(
		'taxonomy',
		'category',
		query
	);

	const { records: tags } = useEntityRecords(
		'taxonomy',
		'post_tag',
		query
	);

	// If childOnly is true, get the child terms of the selected terms
	// SelectedTerms is an array of term IDs, so we need to get the child terms of each term in the array
	const childTerms = useSelect(
		(select) => {
			const childTerms = [];

			if (childOnly) {
				for (const termId of selectedTerms) {
					const query = { parent: termId, per_page: -1 };
					const fetchedTerms = select('core').getEntityRecords('taxonomy', selectedTaxonomyType, query);
					if (Array.isArray(fetchedTerms)) {
						childTerms.push(...fetchedTerms);
					}
				}
			} else {
				const query = { per_page: -1, include: selectedTerms };
				const fetchedTerms = select('core').getEntityRecords('taxonomy', selectedTaxonomyType, query);
				if (Array.isArray(fetchedTerms)) {
					childTerms.push(...fetchedTerms);
				}
			}
			return childTerms;
		},
		[selectedTerms, childOnly, selectedTaxonomyType]
	);

	const newInstanceId = useInstanceId(Edit);

	if (null === categories || null === tags) {
		return;
	}

	if ('' === selectedTaxonomyType) {
		setAttributes({ selectedTaxonomyType: 'category' });
	}

	if (null === instanceId) {
		setAttributes({ instanceId: newInstanceId });
	}

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title="Settings" initialOpen={true}>
					<SelectControl
						label="Taxonomy Type"
						value={selectedTaxonomyType}
						options={[
							{ label: 'Category', value: 'category' },
							{ label: 'Tag', value: 'post_tag' }
						]}
						onChange={(newType) => {
							setAttributes({
								selectedTaxonomyType: newType,
								selectedTerms: []
							})
						}}
						__nextHasNoMarginBottom
					/>
					{'post_tag' === selectedTaxonomyType ? (
						<ToggleControl
							label="Include All Tags"
							checked={allTags}
							onChange={() => {
								setAttributes({ allTags: !allTags });
							}}
						/>
					) : ''}
					{'post_tag' === selectedTaxonomyType && allTags ? '' : (
						<TaxonomyItem
							key={selectedTaxonomyType}
							taxonomy_label={'post_tag' === selectedTaxonomyType ? 'Tags' : 'Categories'}
							name_field='name'
							terms={'post_tag' === selectedTaxonomyType ? tags : categories}
							value={selectedTerms}
							onChange={terms => {
								setAttributes({
									selectedTerms: terms
								});
							}}
						/>
					)}
					{'category' === selectedTaxonomyType ? (
						<ToggleControl
							label="Child Categories Only"
							help={
								childOnly
									? 'The filter dropdown will include only child categories from selected categories.'
									: 'The filter dropdown will include only selected categories.'
							}
							checked={childOnly}
							onChange={(newChildOnly) => {
								setAttributes({
									childOnly: newChildOnly
								})
							}}
						/>
					) : null}
					<TextControl
						label="Default Label"
						value={label}
						onChange={(newLabel) => {
							setAttributes({
								label: newLabel
							})
						}}
					/>
					<SelectControl
						label="Input Type"
						value={inputType}
						options={inputTypes}
						onChange={(newInputType) => {
							setAttributes({ inputType: newInputType });
						}}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				{(() => {
					switch (inputType) {
						case 'select':
							return (
								<select className='wp-query-filter__select'>
									<option>{label}</option>
								</select>
							);
						case 'checkboxes':
							return (
								<div className='wp-query-filter__checkboxes'>
									<ul>
										{childTerms.map(term => (
											<li key={term.id}>
												<label>
													<input type='checkbox' />
													{term.name}
												</label>
											</li>
										))}
									</ul>
								</div>
							);
						default:
							return null;
					}
				})()}
			</div>
		</Fragment>
	);
}
