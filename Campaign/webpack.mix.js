let mix = require('laravel-mix').webpackConfig({
    resolve: {
        symlinks: false,
    },
    watchOptions: {
        ignored: [ /node_modules([\\]+|\/)+(?!remp)/ ]
    }
}).version();

require('laravel-mix-polyfill');

if (process.env.REMP_TARGET === 'lib') {
    // we're not using mix.extract() due to issues with splitting of banner.js + vue.js; basically we need not to have manifest.js
    mix
        .options({
            publicPath: "public/assets/lib/",
            resourceRoot: "/assets/lib/"
        })
        .js("resources/assets/js/banner.js", "js/banner.js")
        .js("resources/assets/js/remplib.js", "js/remplib.js")
        .js("resources/assets/js/bannerSelector.js", "js/bannerSelector.js")
        .polyfill({
            enabled: true,
            useBuiltIns: "usage",
            targets: {"ie": 11},
            debug: false,
        });
} else {
    mix
        .options({
            publicPath: "public/assets/vendor/",
            resourceRoot: "/assets/vendor/"
        })
        .js("resources/assets/js/app.js", "js/app.js")
        .js("resources/assets/js/banner.js", "js/banner.js")
        .sass("resources/assets/sass/vendor.scss", "css/vendor.css")
        .sass("resources/assets/sass/app.scss", "css/app.css")
        .extract()
        .autoload({
            "jquery": ['$', 'jQuery'],
            "node-waves": ["Waves"],
            "autosize": ["autosize"],
            "vue": ["Vue"],
            "moment": ["Moment"]
        });
}
