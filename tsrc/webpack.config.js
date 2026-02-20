const path = require('path');
const webpack = require('webpack');

const sharedConfig = {
    entry: './dndupload/dndupload.ts',
    module: {
        rules: [
            {
                test: /\.ts$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
        ],
    },
    externals: [
        /^core\/.+/,
        /^core_form\/.+/,
        /^core_courseformat\/.+/,
        'jquery',
    ],
    plugins: [
        new webpack.BannerPlugin({
            raw: true,
            entryOnly: true,
            banner: '/* eslint-disable */\n// This file is generated from TypeScript. Do not edit directly.'
        })
    ]
}

const prodConfig = {
    ...sharedConfig,
    mode: 'production',
    devtool: 'source-map',
    resolve: {
        extensions: ['.ts', '.js'],
    },
    output: {
        filename: 'dndupload.min.js',
        path: path.resolve(__dirname, '../amd/build'),
        library: {
            type: 'amd',
        },
    }
};

const devConfig = {
    ...sharedConfig,
    mode: 'development',
    devtool: false,
    resolve: {
        extensions: ['.ts', '.js'],
    },
    output: {
        filename: 'dndupload.js',
        path: path.resolve(__dirname, '../amd/src'),
        library: {
            type: 'amd',
        },
    }
};

module.exports = [devConfig, prodConfig]