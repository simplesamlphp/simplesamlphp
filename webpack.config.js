const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const buildDir = __dirname + '/www/assets/';

const localConfig = {
    css_filename: 'css/[name].css',
    js_filename: 'js/[name].js'
};

module.exports = environment => {
    const env = typeof environment !== 'undefined' ? environment : {};
    const primaryBackground = env.hasOwnProperty('primaryBackground') ? env.primaryBackground : '#b8002c';
    const transitionBackground = env.hasOwnProperty('transitionBackground') ? env.transitionBackground : '#db0100';
    const secondaryBackground = env.hasOwnProperty('secondaryBackground') ? env.secondaryBackground : '#e8410c';
    return {
        entry: {
            bundle: './src/js/bundle',
            logout: './src/js/logout/main',
            stylesheet: './src/js/style'
        },
        output: {
            path: path.resolve(buildDir),
            filename: localConfig['js_filename']
        },
        mode: 'production',
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /\/node_modules\//,
                    use: {
                        loader: 'babel-loader'
                    }
                },
                {
                    test: /\.scss$/,
                    use: ExtractTextPlugin.extract({
                        fallback: 'style-loader',
                        use: [
                            {
                                loader: 'css-loader',
                                options: {
                                    url: false,
                                    sourceMap: true
                                }
                            },
                            {
                                loader: 'sass-loader',
                                options: {
                                    indentedSyntax: false,
                                    sourceMap: true,
                                    data: "$primaryBackground: " + primaryBackground + '; ' +
                                          "$transitionBackground: " + transitionBackground + "; " +
                                          "$secondaryBackground: " + secondaryBackground + ";"
                                }
                            }
                        ]
                    })
                },
                {
                    // expose jquery for use outside webpack bundle
                    test: require.resolve('jquery'),
                    use: [{
                        loader: 'expose-loader',
                        options: 'jQuery'
                    }, {
                        loader: 'expose-loader',
                        options: '$'
                    }]
                }
            ]
        },
        devtool: 'source-map',
        plugins: [
            // Provides jQuery for other JS bundled with Webpack
            new webpack.ProvidePlugin({
                $: 'jquery',
                jQuery: 'jquery'
            }),
            new ExtractTextPlugin({
                filename: localConfig['css_filename'],
                ignoreOrder: true
            }),
            new CopyWebpackPlugin([
                {
                    from: path.resolve(__dirname + '/node_modules/font-awesome/fonts/*'),
                    to: 'fonts/',
                    flatten: true
                }
            ])
        ]
    }
};
