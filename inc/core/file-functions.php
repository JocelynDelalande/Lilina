<?php
/**
 * Functions that work with serialized files
 * @author Ryan McCue <cubegames@gmail.com>
 * @package Lilina
 * @version 1.0
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

defined('LILINA_PATH') or die('Restricted access');

/**
 * available_templates() - {{@internal Missing Short Description}}}
 *
 * {{@internal Missing Long Description}}}
 */
function available_templates() {
	//Make sure we open it correctly
	if ($handle = opendir(LILINA_INCPATH . '/templates/')) {
		//Go through all entries
		while (false !== ($dir = readdir($handle))) {
			// just skip the reference to current and parent directory
			if ($dir != '.' && $dir != '..') {
				if (is_dir(LILINA_INCPATH . '/templates/' . $dir)) {
					if(file_exists(LILINA_INCPATH . '/templates/' . $dir . '/style.css')) {
						$list[] = $dir;
					}
				} 
			}
		}
		// ALWAYS remember to close what you opened
		closedir($handle);
	}
	foreach($list as $the_template) {
		$temp_data = implode('', file(LILINA_INCPATH . '/templates/' . $the_template . '/style.css'));
		preg_match("|Name:(.*)|i", $temp_data, $real_name);
		preg_match("|Description:(.*)|i", $temp_data, $desc);
		$templates[]	= array(
								'name' => $the_template,
								'real_name' => trim($real_name[1]),
								'description' => trim($desc[1])
								);
	}
	return $templates;
}

/**
 * available_locales() - {{@internal Missing Short Description}}}
 *
 * {{@internal Missing Long Description}}}
 */
function available_locales() {
	$locale_list = array_map('basename', glob(LILINA_PATH . LANGDIR . '/*.mo'));
	$locale_list = apply_filters('locale_files', $locale_list);
	$locales = array();

	/** Special case for English */
	$locales[]	= array('name' => 'English',
						'file' => '',
						'realname' => 'en');

	foreach($locale_list as $locale) {
		$locale = basename($locale, '.mo');

		if(file_exists( $locale . '.txt' )) {
			$locale_metadata = file_get_contents(LILINA_PATH . LANGDIR . $locale . '.txt');

			preg_match("|Name:(.*)|i", $locale_metadata, $name);

			$locales[$locale] = array(
				'name' => $name,
				'file' => $locale . '.mo',
				'realname' => $locale
			);
		}

		else {
			$locales[$locale] = array(
				'name' => $locale,
				'file' => $locale . '.mo',
				'realname' => $locale
			);
		}
	}
	return $locales;
}

/**
 * Save options to options.data
 */
function save_options() {
	global $options;
	$data = new DataHandler(LILINA_CONTENT_DIR . '/system/config/');
	return $data->save('options.data', serialize($options));
}

/**
 * get_temp_dir() - Get a temporary directory to try writing files to
 *
 * {@internal Missing Long Description}}
 * @author WordPress
 */
function get_temp_dir() {
	if ( defined('LILINA_TEMP_DIR') )
		return trailingslashit(LILINA_TEMP_DIR);

	$temp = LILINA_PATH . '/content/system/temp';
	if ( is_dir($temp) && is_writable($temp) )
		return $temp;

	if  ( function_exists('sys_get_temp_dir') )
		return trailingslashit(sys_get_temp_dir());

	return '/tmp/';
}

/**
 * File validates against allowed set of defined rules.
 *
 * A return value of '1' means that the $file contains either '..' or './'. A
 * return value of '2' means that the $file contains ':' after the first
 * character. A return value of '3' means that the file is not in the allowed
 * files list.
 *
 * @since 1.2.0
 * @author WordPress
 *
 * @param string $file File path.
 * @param array $allowed_files List of allowed files.
 * @return int 0 means nothing is wrong, greater than 0 means something was wrong.
 */
function validate_file( $file, $allowed_files = '' ) {
	if ( false !== strpos( $file, '..' ))
		return 1;

	if ( false !== strpos( $file, './' ))
		return 1;

	if (':' == substr( $file, 1, 1 ))
		return 2;

	if (!empty ( $allowed_files ) && (!in_array( $file, $allowed_files ) ) )
		return 3;

	return 0;
}

/**
 * Delete all cached HTML pages from the CacheHandler class
 *
 * @return bool
 */
function clear_html_cache() {
	$files = glob(get_option('cachedir') . '*.cache');
	foreach($files as $file) {
		if(!unlink($file)) {
			return false;
		}
	}
}
?>