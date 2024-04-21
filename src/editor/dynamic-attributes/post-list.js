const postList = ( attributes ) => {
	const styleAttributes = {
		rowGap: {
			function: 'range',
			responsive: true,
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-list-items-wrapper .post-list { margin-bottom: $value$unit; }`,
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

export default postList;
