export const range = ( selector, { value, unit } = {}, fallbackUnit ) => {
	// Optional attributes (no default) may hold only a unit or a device value.
	if ( value === undefined || value === null || value === '' ) {
		return '';
	}

	let dynamicCSS = selector;
	dynamicCSS = dynamicCSS.replace( '$value', value );

	const unitValue = unit || fallbackUnit;

	if ( unitValue ) {
		dynamicCSS = dynamicCSS.replace( '$unit', unitValue );
	}

	return dynamicCSS;
};

export const color = ( selector, value ) => {
	return selector.replace( '$value', value );
};
