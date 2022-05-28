const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
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
            bundle: './resources/js/bundle/main',
            logout: './resources/js/logout/main',
            post: './resources/js/post/main',
            stylesheet: './resources/js/style'
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
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: ['@babel/preset-env']
                        }
                    }
                },
                {
                    test: /\.scss$/,
                    use: [
                        'style-loader',
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: {
                                url: false
                            }
                        },
                        {
                            loader: 'sass-loader',
                            options: {
                                sassOptions: {
                                    indentedSyntax: false
                                },
                                additionalData: "$primaryBackground: " + primaryBackground + '; ' +
                                      "$transitionBackground: " + transitionBackground + "; " +
                                      "$secondaryBackground: " + secondaryBackground + ";"
                            }
                        }
                    ]
                }
            ]
        },
        devtool: 'source-map',
        plugins: [
            new MiniCssExtractPlugin({
                filename: localConfig['css_filename'],
                ignoreOrder: true
            }),
            new CopyWebpackPlugin({
                patterns: [
                    {
                        from: path.resolve(__dirname + '/node_modules/\@fortawesome/fontawesome-free/webfonts/fa-solid*'),
                        to: 'fonts/[name][ext]'
                    }
                ]
            })
        ]
    }
};
