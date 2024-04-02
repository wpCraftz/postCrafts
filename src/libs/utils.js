/**
 * Get Substring from a string.
 *
 * @param {string} str    String to get substring from.
 * @param {number} length length of the substring.
 * @return {string} Substring.
 */
export const getSubString = ( str, length ) => {
	if ( str.length <= length ) {
		return str;
	}

	return str.split( ' ' ).splice( 0, 30 ).join( ' ' ).concat( '...' );
};
