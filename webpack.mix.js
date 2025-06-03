const mix = require('laravel-mix');
const fs = require("fs-extra");
const path = require("path");
const cliColor = require("cli-color");
const emojic = require("emojic");
const wpPot = require('wp-pot');
const archiver = require("archiver");
const min = mix.inProduction() ? '.min' : '';


mix.options({
	terser: {
		extractComments: false,
	},
	processCssUrls: false
});


if ((process.env.NODE_ENV === 'development' || process.env.NODE_ENV === 'production')) {

	mix
		.js('src/listing-calendar.js', `assets/listing-calendar.js`).react()


}
