/**
 * Smoke test: @wordpress/element + @wordpress/components render under Jest with
 * Testing Library, the same way the editor components in src/components do.
 */

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

const Counter = () => {
	const [ count, setCount ] = useState( 0 );

	return (
		<Button variant="primary" onClick={ () => setCount( count + 1 ) }>
			{ `Clicked ${ count }` }
		</Button>
	);
};

describe( 'WordPress components in Jest', () => {
	it( 'renders and handles user events', async () => {
		const user = userEvent.setup();
		render( <Counter /> );

		await user.click( screen.getByRole( 'button', { name: 'Clicked 0' } ) );

		expect(
			screen.getByRole( 'button', { name: 'Clicked 1' } )
		).toBeInTheDocument();
	} );
} );
