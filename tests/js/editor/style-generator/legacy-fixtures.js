/**
 * Attribute sets that only use attributes that existed before PR #10.
 *
 * The expected CSS for each one was generated with the `main` branch style
 * generator (pre-PR #10) and pinned in style-generator.test.js, so a change to
 * those strings means blocks saved before the PR would get different CSS.
 */
export const LEGACY_CASES = [
	{
		name: 'post-grid with block.json defaults',
		block: 'post-crafts/post-grid',
		attributes: {
			blockId: 'abc12345',
			columns: { value: 3 },
			columnGap: { unit: 'px', value: 20 },
			rowGap: { unit: 'px', value: 20 },
			paginationBorderRadius: { unit: 'px', value: 2 },
			pagination: true,
		},
	},
	{
		name: 'post-grid with responsive values and pagination colors',
		block: 'post-crafts/post-grid',
		attributes: {
			blockId: 'grid0001',
			columns: { value: 4, tablet: { value: 2 }, mobile: { value: 1 } },
			columnGap: {
				unit: 'px',
				value: 30,
				tablet: { unit: 'px', value: 20 },
				mobile: { value: 10 },
			},
			rowGap: { unit: 'em', value: 2, tablet: { value: 1.5 } },
			paginationBorderRadius: {
				unit: '%',
				value: 50,
				mobile: { unit: 'px', value: 4 },
			},
			pagination: true,
			paginationColor: '#ffffff',
			paginationBg: '#111111',
		},
	},
	{
		name: 'post-grid where the gradient overrides the background',
		block: 'post-crafts/post-grid',
		attributes: {
			blockId: 'grad0001',
			columns: { value: 3 },
			columnGap: { unit: 'px', value: 0 },
			rowGap: { unit: 'px', value: 20 },
			paginationBorderRadius: { unit: 'px', value: 2 },
			pagination: true,
			paginationBg: '#111111',
			paginationGradient:
				'linear-gradient(135deg,rgb(6,147,227) 0%,rgb(155,81,224) 100%)',
		},
	},
	{
		name: 'post-grid with pagination off ignores pagination styles',
		block: 'post-crafts/post-grid',
		attributes: {
			blockId: 'nopag001',
			columns: { value: 2 },
			columnGap: { unit: 'px', value: 20 },
			rowGap: { unit: 'px', value: 20 },
			paginationBorderRadius: { unit: 'px', value: 8 },
			pagination: false,
			paginationColor: '#ffffff',
			paginationBg: '#111111',
		},
	},
	{
		name: 'post-list with block.json defaults',
		block: 'post-crafts/post-list',
		attributes: {
			blockId: 'list0001',
			rowGap: { unit: 'px', value: 20 },
			paginationBorderRadius: { unit: 'px', value: 2 },
			pagination: true,
		},
	},
	{
		name: 'post-list with responsive values and pagination colors',
		block: 'post-crafts/post-list',
		attributes: {
			blockId: 'list0002',
			rowGap: {
				unit: 'rem',
				value: 3,
				tablet: { value: 2 },
				mobile: { unit: 'px', value: 12 },
			},
			paginationBorderRadius: {
				unit: 'px',
				value: 6,
				tablet: { value: 4 },
			},
			pagination: true,
			paginationColor: 'red',
			paginationGradient: 'linear-gradient(red, blue)',
		},
	},
];
