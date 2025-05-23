module.exports = {
    plugins: [
        require('autoprefixer')({
            overrideBrowserslist: [
                "> 1%",
                "last 2 versions",
                "not ie <= 8"
            ]
        }),
        require('./convert-color-to-uppercase'),
        // stylelint: {
        // },
    ]
}
