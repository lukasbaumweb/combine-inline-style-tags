<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://baum-lukas.de
 * @since             1.0.0
 * @package           Combine_Inline_Style_Tags
 *
 * @wordpress-plugin
 * Plugin Name:       Combine Inline Style Tags
 * Plugin URI:        https://baum-lukas.de/combine-inline-style-tags
 * Description:       Move annoying inline style tag into a separate CSS file thus it can be processed by caching plugins 
 * Version:           1.0.0
 * Author:            Lukas Baum
 * Author URI:        https://baum-lukas.de/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       combine-inline-style-tags
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('COMBINE_INLINE_STYLE_TAGS_VERSION', '1.0.0');

/**
 * Begins execution of the plugin.
 *  
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_combine_inline_style_tags()
{
	/**
	 * Code snippet for extracting, removing inline style tags from final html output and moving these styles into a single css file
	 * 
	 * @ Some parts of the Code is from https://wordpress.org/support/topic/wp-fastest-cache-not-reflecting-changes-made-with-add_filter-in-functions-php/
	 */

	ob_start();

	define('LBA_CIST_STYLESHEET_NAME', 'cist.css');

	add_action('shutdown', function () {
		$file_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . LBA_CIST_STYLESHEET_NAME;
		$final = '';

		// We'll need to get the number of ob levels we're in, so that we can iterate over each, collecting
		// that buffer's output into the final output.
		$levels = ob_get_level();

		for ($i = 0; $i < $levels; $i++) {
			$final .= ob_get_clean();
		}

		// shutdown action gets called two times. this ignores the second one
		if ($final == 0) return;

		// delete file before insert new content. Note: Plain overwriting file contents does not work
		file_put_contents($file_path, '');

		$style_tag_pattern = '/<style((.|\n|\r)*?)<\/style>/'; // for capturing inline styles with content
		$html_tag_pattern = '/<[^>]*>/'; // for removing style tag leaving its contents 

		// get all inline styles
		preg_match_all($style_tag_pattern, $final, $matches, PREG_PATTERN_ORDER);

		$combined_css = '';
		for ($i = 0; $i < count($matches[0]); $i++) {
			$combined_css .= preg_replace($html_tag_pattern, '', $matches[0][$i]);
		}

		// write cleaned inline styles to single css file
		file_put_contents($file_path, $combined_css, FILE_APPEND);

		// Remove style tags
		$final = preg_replace($style_tag_pattern, '', $final);

		// echo processed html
		echo $final;
	}, 0);

	wp_register_style("lba-fix-inline-styles", plugin_dir_url(__FILE__) . "dist/" . LBA_CIST_STYLESHEET_NAME, array(), "1.0.0", 'all');
	wp_enqueue_style("lba-fix-inline-styles");
}
run_combine_inline_style_tags();
