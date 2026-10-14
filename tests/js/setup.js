/**
 * Runs after the test framework is installed, before each test file.
 */

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { TextEncoder, TextDecoder } from 'util';

// jsdom (Jest 29) lacks these; @wordpress/core-data needs them at import time.
if ( typeof global.TextEncoder === 'undefined' ) {
	global.TextEncoder = TextEncoder;
	global.TextDecoder = TextDecoder;
}
