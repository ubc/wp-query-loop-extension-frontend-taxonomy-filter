import { FormTokenField } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';

// Folked from https://github.com/WordPress/gutenberg/blob/d623dc1195a4499134f51fc713215174a4e669a6/packages/block-library/src/query/edit/inspector-controls/taxonomy-controls.js#L15
// Helper function to get the term id based on user input in terms `FormTokenField`.
const getTermIdByTermValue = ( terms, termValue, name_field ) => {
	// First we check for exact match by `term.id` or case sensitive `term.name` match.
	const termId =
		termValue?.id || terms.find( ( term ) => term[name_field] === termValue )?.id;
	if ( termId ) {
		return termId;
	}

	/**
	 * Here we make an extra check for entered terms in a non case sensitive way,
	 * to match user expectations, due to `FormTokenField` behaviour that shows
	 * suggestions which are case insensitive.
	 *
	 * Although WP tries to discourage users to add terms with the same name (case insensitive),
	 * it's still possible if you manually change the name, as long as the terms have different slugs.
	 * In this edge case we always apply the first match from the terms list.
	 */
	const termValueLower = termValue.toLocaleLowerCase();
	return terms.find(
		( term ) => term[name_field].toLocaleLowerCase() === termValueLower
	)?.id;
};

//Folked from https://github.com/WordPress/gutenberg/blob/d623dc1195a4499134f51fc713215174a4e669a6/packages/block-library/src/query/edit/inspector-controls/taxonomy-controls.js#L84
export const TaxonomyItem = ( { taxonomy_label, terms, value, onChange, name_field } ) => {

	if ( ! terms?.length ) {
		return null;
	}

	const onTermsChange = ( newTermValues ) => {
		const termIds = new Set();
        const termValues = new Set();
		for ( const termValue of newTermValues ) {
			const termId = getTermIdByTermValue( terms, termValue, name_field );

			if ( termId ) {
				termIds.add( termId );
                // The FormTokenField on change event is doing some strange thing. All the values inside the array other than the last one are returned as object.
                termValues.add( termValue.value ? termValue.value : termValue );
			}
		}
        
		onChange( Array.from( termIds ) );
	};

    // Selects only the existing term ids in proper format to be
	// used in `FormTokenField`. This prevents the component from
	// crashing in the editor, when non existing term ids were provided.
	const inputValue = value
    .map( ( termId ) => terms.find( ( t ) => t.id === termId ) )
    .filter( Boolean )
    .map( ( term ) => ( { id: term.id, value: decodeEntities( term[name_field] ) } ) );

	return (
		<div className="block-library-query-inspector__taxonomy-control">
			<FormTokenField
				label={ taxonomy_label }
				value={ inputValue }
				suggestions={ terms.map( ( t ) => t[name_field] ) }
				onChange={ onTermsChange }
			/>
		</div>
	);
}