const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        'chatbot': './src/chatbot/index.js',
        'admin': './src/admin/index.js',
    },
    output: {
        ...defaultConfig.output,
        filename: '[name].js',
        path: __dirname + '/assets/js/build',
    },
}; 