const path = require('path');
const webpack = require('webpack');

module.exports = {
    entry: './dndupload/dndupload.ts',
    mode: 'development',
    devtool: false,
    module: {
        rules: [
            {
                test: /\.ts$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
        ],
    },
    resolve: {
        extensions: ['.ts', '.js'],
    },
    output: {
        filename: 'dndupload.js',
        path: path.resolve(__dirname, '../amd/src'),
        library: {
            type: 'amd',
        },
    },
    externals: [
        /^core\/.+/,
        /^core_form\/.+/,
        'jquery',
    ],
    // Inject the "Ignore Me" banner
    plugins: [
        new webpack.BannerPlugin({
            raw: true,
            entryOnly: true,
            banner: '/* eslint-disable */\n// This file is generated from TypeScript. Do not edit directly.'
        })
    ]
};