const HtmlWebpackPlugin = require('html-webpack-plugin'); //installed via npm
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const CleanWebpackPlugin = require('clean-webpack-plugin');
const webpack = require('webpack'); //to access built-in plugins
// const BrowserSyncPlugin = require('browser-sync-webpack-plugin');
const path = require('path');

const extractSass = new ExtractTextPlugin({
    filename: "[name].css"
});

const MiniCssExtractPlugin = require("mini-css-extract-plugin");

function includeTemplate(template, filename) {
    return new HtmlWebpackPlugin({
        template: template,
        filename: filename,
        inject: false
    })
}

const resourcesFolder = path.dirname(__dirname);
const distFolder = path.resolve(resourcesFolder, '..', 'Public', 'dist');

module.exports = {
    mode: 'development',
    entry: {
        'font-awesome': 'font-awesome/scss/font-awesome.scss',
        'app': './src/app.js'
    },
    output: {
        filename: '[name].js',
        path: distFolder
    },
    plugins: [
        new CleanWebpackPlugin(),
        includeTemplate('src/index.html', 'index.html'),
        extractSass,
        new MiniCssExtractPlugin({
            // Options similar to the same options in webpackOptions.output
            // both options are optional
            filename: "[name].css",
            chunkFilename: "[id].css"
        })
    ],
    module: {
        rules: [
            {
                test: /\.html$/,
                use: [
                    {
                        loader: 'html-loader',
                        options: {
                            minimize: true,
                            interpolate: true
                        }
                    }
                ]
            },
            {
                // Exposes jQuery for use outside Webpack build
                test: require.resolve('jquery'),
                use: [{
                    loader: 'expose-loader',
                    options: 'jQuery'
                }, {
                    loader: 'expose-loader',
                    options: '$'
                }]
            },
            {
                test: /\.(png|jpg|gif)$/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: '[name].[ext]',
                            publicPath: './images/',
                            outputPath: './images/'
                        }
                    }
                ]
            },
            {
                test: /\.(scss)$/,
                use: extractSass.extract({
                    use: [{
                        loader: "css-loader",
                        options: {
                            // minimize: true
                        }
                    }, {
                        loader: "sass-loader"
                    }],
                    // use style-loader in development
                    fallback: "style-loader"
                })
            },
            {
                test: /\.woff2?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
                use: 'url-loader?limit=10000',
            },
            {
                test: /\.(ttf|eot|svg)(\?[\s\S]+)?$/,
                use: 'file-loader',
            },
            {
                test: /\.(jpe?g|png|gif|svg)$/i,
                use: [
                    'file-loader?name=images/[name].[ext]',
                    'image-webpack-loader?bypassOnDebug'
                ]
            },
            // font-awesome
            {
                test: /font-awesome\.config\.js/,
                use: [
                    { loader: 'style-loader' },
                    { loader: 'font-awesome-loader' }
                ]
            },
        ]
    }
};