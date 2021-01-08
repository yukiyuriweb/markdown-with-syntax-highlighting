<?php

declare(strict_types=1);

date_default_timezone_set( 'Asia/Tokyo' );

/**
 * Retrieve the comment field key in plugin main file.
 *
 * @param string $plugin
 * @param string $key
 * @return string
 */
function get_comment_field( string $plugin, string $key ): string {
	preg_match( '/' . $key . ':(.*)/', $plugin, $matches );
	return trim( $matches[1] );
}

$package = file_get_contents( __DIR__ . '/package.json' );
$package = json_decode( $package );

$plugin_file_path = __DIR__ . '/' . $package->name . '.php';

if ( ! file_exists( $plugin_file_path ) ) {
	exit( 'Cannot find plugin file. Check name field in package.json or if the plugin file exists.' . PHP_EOL );
}

$plugin = file_get_contents( $plugin_file_path );

if ( get_comment_field( $plugin, 'Version' ) !== $package->version ) {
	exit( 'version in package.json and main plugin file should be the same.' . PHP_EOL );
}

$plugin_site_uri = 'https://wp-plugins.yukiyuriweb.com';

$command = 'yarn run --silent md2html CHANGELOG.md | yarn run --silent html-minifier --collapse-whitespace --remove-comments --remove-optional-tags --remove-redundant-attributes --remove-script-type-attributes --remove-tag-whitespace --use-short-doctype --minify-css true --minify-js true';

$details = array(
	'name'            => get_comment_field( $plugin, 'Plugin Name' ),
	'slug'            => $package->name,
	'version'         => $package->version,
	'download_url'    => "{$plugin_site_uri}/{$package->name}/{$package->name}.{$package->version}.zip",
	'sections'        => array(
		// 'description' => trim( stream_get_contents( STDIN ) ),
		'description' => exec( $command ),
	),
	'homepage'        => get_comment_field( $plugin, 'Plugin URI' ),
	'requres'         => '7.2.0',
	'author'          => get_comment_field( $plugin, 'Author' ),
	'author_homepage' => get_comment_field( $plugin, 'Author URI' ),
	'last_updated'    => date( 'Y-m-d H:i:s' ),
);

echo json_encode( $details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
exit( 0 );
