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

/**
 * Perform a deep comparison between two values to determine if they are equivalent.
 * Supports primitives, arrays, plain objects, Dates, RegExps, Maps and Sets.
 *
 * @param {*} a First value to compare.
 * @param {*} b Second value to compare.
 * @return {boolean} True if the values are equivalent, else false.
 */
export const isEqual = ( a, b ) => {
	if ( Object.is( a, b ) ) {
		return true;
	}

	if (
		typeof a !== 'object' ||
		typeof b !== 'object' ||
		a === null ||
		b === null ||
		Object.getPrototypeOf( a ) !== Object.getPrototypeOf( b )
	) {
		return false;
	}

	if ( a instanceof Date ) {
		return a.getTime() === b.getTime();
	}

	if ( a instanceof RegExp ) {
		return a.toString() === b.toString();
	}

	if ( a instanceof Map ) {
		if ( a.size !== b.size ) {
			return false;
		}
		for ( const [ key, value ] of a ) {
			if ( ! b.has( key ) || ! isEqual( value, b.get( key ) ) ) {
				return false;
			}
		}
		return true;
	}

	if ( a instanceof Set ) {
		if ( a.size !== b.size ) {
			return false;
		}
		for ( const value of a ) {
			if ( ! b.has( value ) ) {
				return false;
			}
		}
		return true;
	}

	if ( Array.isArray( a ) ) {
		return (
			a.length === b.length &&
			a.every( ( item, index ) => isEqual( item, b[ index ] ) )
		);
	}

	const keysA = Object.keys( a );
	const keysB = Object.keys( b );

	return (
		keysA.length === keysB.length &&
		keysA.every(
			( key ) =>
				Object.prototype.hasOwnProperty.call( b, key ) &&
				isEqual( a[ key ], b[ key ] )
		)
	);
};
