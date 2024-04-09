const postGrid = ( attributes ) => {
	const styleAttributes = {
		columns: {
			value: attributes.columns,
			responsive: true,
			function: 'range',
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-grid-items-wrapper { grid-template-columns: repeat($value, 1fr); }`,
		},
		columnGap: {
			value: attributes.columnGap,
			responsive: true,
			function: 'range',
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-grid-items-wrapper { column-gap: $value$unit; }`,
		},
		rowGap: {
			value: attributes.rowGap,
			responsive: true,
			function: 'range',
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-grid-items-wrapper { row-gap: $value$unit; }`,
		},
	};

	return styleAttributes;
};

export default postGrid;
