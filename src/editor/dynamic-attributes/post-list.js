const postList = ( attributes ) => {
	const styleAttributes = {
		rowGap: {
			value: attributes.rowGap,
			responsive: true,
			function: 'range',
			selector: `.pcrafts-block-${ attributes.blockId } .pcrafts-list-items-wrapper .post-list { margin-bottom: $value$unit; }`,
		},
	};

	return styleAttributes;
};

export default postList;
