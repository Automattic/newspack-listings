/**
 * External dependencies
 */
import { Path, SVG } from '@wordpress/components';

export default ( { size = 48 } ) => (
	<SVG className="newspack-listings__query-logo" height={ size } viewBox="0 0 24 24" width={ size }>
		<Path d="M0 0h24v24H0z" fill="none" />
		<Path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z" />
	</SVG>
);
