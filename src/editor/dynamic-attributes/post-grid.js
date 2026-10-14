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
		// Card typography/colors. The doubled `.pcrafts-block` class outranks the base card styles.
		categoryColor: {
			function: 'color',
			selector: `.pcrafts-block.pcrafts-block-${ attributes.blockId } .cat-links a { color: $value; }`,
		},
		titleColor: {
			function: 'color',
			selector: `.pcrafts-block.pcrafts-block-${ attributes.blockId } .entry-title a { color: $value; }`,
		},
		titleHoverColor: {
			function: 'color',
			selector: `.pcrafts-block.pcrafts-block-${ attributes.blockId } .entry-title a:hover, .pcrafts-block.pcrafts-block-${ attributes.blockId } article.post-grid:hover .entry-title a { color: $value; }`,
		},
		metaColor: {
			function: 'color',
			selector: `.pcrafts-block.pcrafts-block-${ attributes.blockId } .entry-meta, .pcrafts-block.pcrafts-block-${ attributes.blockId } .entry-meta a { color: $value; }`,
		},
		excerptColor: {
			function: 'color',
			selector: `.pcrafts-block.pcrafts-block-${ attributes.blockId } .entry-summary, .pcrafts-block.pcrafts-block-${ attributes.blockId } .entry-summary p { color: $value; }`,
		},
		titleFontSize: {
			responsive: true,
			function: 'range',
			selector: `.pcrafts-block.pcrafts-block-${ attributes.blockId } .entry-title { font-size: $value$unit; line-height: 1.25; }`,
		},
	};

	return styleAttributes;
};

export default postGrid;
