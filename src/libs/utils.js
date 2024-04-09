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

	return str.split( ' ' ).splice( 0, length ).join( ' ' ).concat( '...' );
};

const isPlainObject = ( item ) =>
	item && typeof item === 'object' && item.constructor === Object;

/**
 * Recursively clone the passed object.
 *
 * @param {Object} source Source object.
 * @return {Object} Cloned object.
 */
export const deepClone = ( source ) => {
	if ( isPlainObject( source ) ) {
		return Object.fromEntries(
			Object.entries( source ).map( ( [ key, value ] ) => [
				key,
				deepClone( value ),
			] )
		);
	}
	if ( Array.isArray( source ) ) {
		return source.map( ( i ) => deepClone( i ) );
	}
	return source;
};
