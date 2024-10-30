<?php
/**
 * @package Language_Selector_Related
 * @package Language_Selector_Related
 * @version 1.1
 * @author Ruben Vasallo
 *
 * Script for uninstall Language Selector Related plugin and clean db options
 */

if ( !defined('WP_UNINSTALL_PLUGIN') )
	exit();

if ( ! class_exists('Language_Selector_Related_Class') )
	require_once plugin_dir_path( __FILE__ ).'languageselectorrelatedclass.php';

global $wpdb;
$language_selector_related = new Language_Selector_Related_Class();

$table_name_posts = $wpdb->prefix . $language_selector_related->table_name() . '_posts';
$table_name_terms = $wpdb->prefix . $language_selector_related->table_name() . '_terms';

$sql = "DROP TABLE $table_name_posts;";
$wpdb->query( $sql );
$sql = "DROP TABLE $table_name_terms;";
$wpdb->query( $sql );

delete_option('langselrel_db_version');
?>