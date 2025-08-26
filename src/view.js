/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

let didRunInitially = false;

const updateURLParameter = ( url, urlParameters ) => {
	const newUrl = new URL(url);

	urlParameters.forEach( urlParameter => {
		newUrl.searchParams.set(urlParameter.identifier, urlParameter.value);
	});

	return newUrl;
};

store( 'ctlt-query-tax-filter', {
	actions: {
		onChangeTerm: ( event ) => {
			event.preventDefault();

			const context = getContext();
			
			// Check the element tag, if it's a checkbox, get the all the checked values.
			if ( event.target.tagName === 'INPUT' && event.target.type === 'checkbox' ) {
				// Get the name of the checkbox
				const checkboxName = event.target.name;
				// Get all the checked values
				const checkedValues = document.querySelectorAll( `input[name="${checkboxName}"]:checked` );
				// Add the checked values to the selectedTerms array
				context.selectedTerm = Array.from( checkedValues ).map( checkbox => checkbox.value );
			} else {
				context.selectedTerm = event.target.value;
			}
		},
	},
	callbacks: {
		*navigateToDestination() {
			const { ref } = getElement();
			const { selectedTerm } = getContext();

			if ( ! didRunInitially ) {
				didRunInitially = true;
				return; // Skip the first run on node creation
			}

			if ( null === ref ) {
				return;
			}

			const queryRef = ref.closest(
				'.wp-block-query[data-wp-router-region]'
				);

			const { actions } = yield import(
				'@wordpress/interactivity-router'
			);

			let navigateTo = updateURLParameter(
				window.location,
				[
					{ identifier: queryRef.getAttribute( 'data-wp-router-region' ) + '-term-' + ref.getAttribute( 'filter-id' ), value: Array.isArray( selectedTerm ) ? selectedTerm.join( ',' ) : selectedTerm },
					{ identifier: queryRef.getAttribute( 'data-wp-router-region' ) + '-page', value: '1' },
				]
			);

			yield actions.navigate( navigateTo );

		},
	  },
	
} );
