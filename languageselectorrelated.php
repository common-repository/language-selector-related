<?php
/**
 * @package Language_Selector_Related
 * @version 1.1
 * @author Ruben Vasallo
 *
 * Plugin Language Selector Related
 */
/*
Plugin Name: Language Selector Related
Plugin URI:
Description: If you have more blogs in more languages that they are independent between they and would you like to bind each, this is your plugin. This plugin allow relate blogs between they for relate contents in more languages. It add a link in your pages of blog that link between they and adds meta rel="alternate" hreflang="xx" and links in your posts, pages and tags that indicate that they have an other blog in other language.
Author: Rubén Vasallo
Version: 1.1
Author URI: http://rubenvasallo.co.uk/
License: GPL2
*/

if ( ! class_exists('Language_Selector_Related_Class') )
	require_once plugin_dir_path( __FILE__ ).'languageselectorrelatedclass.php';

if ( ! class_exists('Language_Selector_Related_Widget') )
	require_once plugin_dir_path( __FILE__ ).'languageselectorrelatedwidget.php';

$language_selector_related = new Language_Selector_Related_Class();

register_activation_hook( __FILE__, array(&$language_selector_related, 'install') );
?>