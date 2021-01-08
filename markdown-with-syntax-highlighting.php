<?php
/**
 * Plugin Name:     Markdown with Syntax Highlighting
 * Plugin URI:      https://wp-plugins.yukiyuriweb.com/markdown-with-syntax-highlighting
 * Description:     Enable markdown format in posts with server-side syntax highlighting.
 * Author:          YUKiYURi WEB
 * Author URI:      https://yukiyuriweb.com
 * Text Domain:     github-style-syntax-highlighting
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Markdown_With_Syntax_Highlighting
 */

declare(strict_types=1);

namespace MarkdownWithSyntaxHighlighting;

use Puc_v4_Factory;
use Parsedown;
use Highlight\Highlighter;

defined( 'ABSPATH' ) || exit;

// define( 'PLUGIN_VERSION', '0.1.0' );
// define( 'MINIMUM_PHP_VERSION', '7.2.27' );
// define( 'MINIMUM_WP_VERSION', '5.6' );

require_once __DIR__ . '/vendor/autoload.php';

/**
 * DB に保存する直前に、Parsedown でパースして Highlighter でハイライト用の
 * マークアップを追加した結果を post_content_filtered に格納する.
 */
add_filter(
	'wp_insert_post_data',
	/**
	 * Filters slashed post data just before it is inserted into the database.
	 *
	 * @since 2.7.0
	 *
	 * @param array $data    An array of slashed post data.
	 * @param array $postarr An array of sanitized, but otherwise unmodified post data.
	 */
	function( array $data, array $dataarr ) {
		// 投稿以外は何もしない.
		if ( 'post' !== $data['post_type'] ) {
			return $data;
		}

		/**
		 * マークダウンをパースする.
		 *
		 * @see https://cofus.work/blog/wordpress-markdown/
		 */
		$post_content = Parsedown::instance()->setMarkupEscaped( false )->setBreaksEnabled( true )->text( htmlspecialchars_decode( $dataarr['post_content'], ENT_QUOTES ) );

		$post_content = str_replace( '<a ', '<a target="_blank" rel="noopener" ', $post_content );

		/**
		 * ショートコードなどで Double quotation があると &quot; に置換されるため
		 * それを double quotation に戻す.
		 */
		$post_content = str_replace( '&quot;', '', $post_content );

		/**
		 * 引き続きパースしたコンテンツにハイライト用のマークアップを加える.
		 *
		 * @see https://webnetforce.net/amo-syntax-highlight-with-php/
		 */
		$highlighter = new Highlighter();

		$data['post_content_filtered'] = preg_replace_callback(
			'/<pre><code(.*?)>(.+?)<\/code><\/pre>/s',
			function( $matches ) use ( $highlighter ) {
				$input         = html_entity_decode( $matches[2] );
				$has_classname = preg_match( '/class=\"language-(.*)\"/', $matches[1], $classname );
				if ( $has_classname ) {
					$output = $highlighter->highlight( $classname[1], $input )->value;
					$html   = '<pre><code class="hljs ' . $classname[1] . '">' . $output . '</code></pre>';
				} else {
					$highlighter->setAutodetectLanguages(
						array(
							'html',
							'css',
							'php',
							'shell',
							'javascript',
							'python',
							'ruby',
							'perl',
						)
					);
					$output = $highlighter->highlightAuto( $input )->value;
					$html   = '<pre><code class="hljs ' . $highlighter->highlightAuto( $input )->language . '">' . $output . '</code></pre>';
				}
				return $html;
			},
			$post_content
		);

		return $data;
	},
	~PHP_INT_MAX,
	2
);


/**
 * 投稿の場合に post_content_filtered に保存された HTML を返す.
 */
add_filter(
	'the_content',
	function( string $content ) {
		global $post;

		if ( $post && 'post' === $post->post_type ) {
			return $post->post_content_filtered;
		}

		return $content;
	},
	~PHP_INT_MAX
);
