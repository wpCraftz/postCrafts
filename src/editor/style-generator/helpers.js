export const range = ( selector, { value, unit }, fallbackUnit ) => {
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
