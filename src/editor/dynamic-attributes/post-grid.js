const postGrid = ( attributes ) => {
	const styleAttributes = {
		columns: {
			responsive: true,
			function: 'range',
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-grid-items-wrapper { grid-template-columns: repeat($value, 1fr); }`,
		},
		columnGap: {
			responsive: true,
			function: 'range',
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-grid-items-wrapper { column-gap: $value$unit; }`,
		},
		rowGap: {
			responsive: true,
			function: 'range',
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-grid-items-wrapper { row-gap: $value$unit; }`,
		},
		paginationBorderRadius: {
			responsive: true,
			function: 'range',
			condition: attributes.pagination === true,
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-pages li { border-radius: $value$unit; }`,
		},
		paginationColor: {
			function: 'color',
			condition: attributes.pagination === true,
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-pages li { color: $value; }`,
		},
		paginationBg: {
			function: 'color',
			condition:
				attributes.pagination === true &&
				typeof attributes.paginationGradient === 'undefined',
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-pages li { background-color: $value; }`,
		},
		paginationGradient: {
			function: 'color',
			condition: attributes.pagination === true,
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-pages li { background: $value; }`,
		},
	};

	return styleAttributes;
};

export default postGrid;
