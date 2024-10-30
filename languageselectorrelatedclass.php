<?php
/**
 * @package Language_Selector_Related
 * @version 1.1
 * @author Ruben Vasallo
 *
 * Language Selector Related Class
 */

class Language_Selector_Related_Class {

	public function __construct() {
		add_action('plugins_loaded', array(&$this, 'configure_plugin') );
		add_action('wp_head', array(&$this, 'find_language') );
		add_action('admin_menu', array(&$this, 'admin_menu') );
		add_action('admin_menu', array(&$this, 'admin_posts') );
		add_action('save_post', array(&$this, 'admin_update_posts') );
		add_action('deleted_post', array(&$this, 'admin_delete_related_links_posts') );
		add_action('edit_category_form_fields', array(&$this, 'admin_related_links_tags') );
		add_action('delete_category', array(&$this, 'admin_delete_related_links_tags') );
		add_action('delete_post_tag', array(&$this, 'admin_delete_related_links_tags') );
		add_action('edit_tag_form_fields', array(&$this, 'admin_related_links_tags') );
		add_filter('edited_terms', array(&$this, 'admin_update_tags') );
	}

	/*
	 * Return the num version of db structure of the plugin
	 */
	public function db_version() {
		return '1.0';
	}

	/*
	 * Return the prefix name of the tables for db
	 */
	public function table_name() {
		return 'langselrel';
	}

	/*
	 * Create or update structure db necessary for run plugin
	 */
	public function install() {
		global $wpdb;
		$installed_ver = get_option( 'langselrel_db_version');

		$table_name_posts = $wpdb->prefix . $this->table_name() . '_posts';
		$table_name_terms = $wpdb->prefix . $this->table_name() . '_terms';

		if( false === $installed_ver ) {
			$sql = "CREATE TABLE IF NOT EXISTS $table_name_posts (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				id_post BIGINT(20) NOT NULL,
				uri VARCHAR(250) DEFAULT '' NOT NULL,
				language VARCHAR(3) NULL,
				country VARCHAR(3) NULL,
				UNIQUE KEY id (id)
			);";
			$wpdb->query( $sql );

			$sql = "CREATE TABLE IF NOT EXISTS $table_name_terms (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				term_id BIGINT(20) NOT NULL,
				uri VARCHAR(250) DEFAULT '' NOT NULL,
				language VARCHAR(3) NULL,
				country VARCHAR(3) NULL,
				UNIQUE KEY id (id)
			);";
			$wpdb->query( $sql );

		} elseif( $installed_ver != $this->db_version() ) {
			$sql = "ALTER TABLE $table_name_posts
				CHANGE id id BIGINT(20) NOT NULL AUTO_INCREMENT,
				CHANGE id_post id_post BIGINT(20) NOT NULL,
				CHANGE uri uri VARCHAR(250) DEFAULT '' NOT NULL,
				CHANGE language language VARCHAR(3) NULL,
				CHANGE country country VARCHAR(3) NULL
			;";
			$wpdb->query( $sql );

			$sql = "ALTER TABLE $table_name_terms
				CHANGE id id BIGINT(20) NOT NULL AUTO_INCREMENT,
				CHANGE term_id term_id BIGINT(20) NOT NULL,
				CHANGE uri uri VARCHAR(250) DEFAULT '' NOT NULL,
				CHANGE language language VARCHAR(3) NULL,
				CHANGE country country VARCHAR(3) NULL
			;";
			$wpdb->query($sql);
		}

		if( false === $installed_ver ) {
			add_option('langselrel_db_version', $this->db_version() );
		} else {
			update_option('langselrel_db_version', $this->db_version() );
		}
	}

	/*
	 * Update db structure if is necessary and active the translations
	 */
	public function configure_plugin() {
		if ( get_option('langselrel_db_version') != $this->db_version() ) {
			$this->install();
		}
        load_plugin_textdomain('language-selector-related', false, 'language-selector-related/languages');
	}

	/*
	 * Find a language related for object called
	 */
	public function find_language( $print_a_element = false ) {
		$list_links = array();
		global $wp_query;
		$obj = $wp_query->get_queried_object();
		if ( empty( $print_a_element ) ){
				$print_a_element = false;
		}

		if ( is_home() ){
			// For home
			$list_links = $this->admin_get_related_links( 'tag', 0 );
		}elseif (is_singular()){
			// For posts or pages
			$list_links = $this->admin_get_related_links( 'post', $obj->ID );
		}elseif ( is_category() || is_tag() ) {
			// For categories or tags
			$list_links = $this->admin_get_related_links( 'tag', $obj->term_id );
		}
		if ( true == $print_a_element ){
			echo "<ul>\n";
		}elseif ( ! empty($list_links) ){
			echo "\n<!-- Language Selector Related 1.0 by Ruben Vasallo -->\n";
		}
		foreach ($list_links as $link){
			$uri = $link['uri'];
            if ( '--' != $link['language']){
                $hreflang = $link['language'];
                if ( !empty($link['country']) ){
                    $hreflang .= "_".$link['country'];
                }
            } else {
                $hreflang = 'x-default';
            }
			if ( true == $print_a_element ){
                if ( '--' != $link['language'] ){
                    echo "<li><a rel=\"alternate\" hreflang=\"$hreflang\" href=\"$uri\">".$link['language']."</a></li>\n";
                }
			} else {
				echo "<link rel=\"alternate\" hreflang=\"$hreflang\" href=\"$uri\" />\n";
			}
		}
		if ( true == $print_a_element ){
			echo "</ul>\n";
		}elseif ( ! empty($list_links) ){
			echo "<!-- /Language Selector Related -->\n";
		}
	}

	/*
	 * (Admin) Acttion for show related links form for home
	 */
	public function admin_menu() {
		$file = __FILE__;

		add_submenu_page('options-general.php', 'Language Selector Relate - Configure home related links', 'Language Selector Relate', 'manage_options', $file, array(&$this, 'admin_related_links_home'));
	}

	/*
	 * (Admin) List form with all related links for look in home
	 */
	public function admin_related_links_home() {
		if( isset($_POST['uri'])){
			$this->admin_update_tags( 0 );
		}
?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2><?php _e('Home Related links', 'language-selector-related');?></h2>
<?php
            $this->admin_related_links_by_type('home');
?>
		</div>
<?php
	}

	/*
	 * (Admin) Action for show related links form for posts and pages
	 */
	public function admin_posts() {
		$post_types = get_post_types('','names');
		foreach ($post_types as $post_type) {
			if($post_type == 'post' || $post_type == 'page'){
				add_meta_box('related_links_posts', __('Language Selector Relate - Configure related links', 'language-selector-related'), array(&$this, 'admin_related_links_posts'),$post_type);
			}
		}
	}

	/*
	 * (Admin) Show related links form for current post
	 */
	public function admin_related_links_posts() {
		global $post;
		$post_id = $post->ID;
?>
		<h2><?php _e('Related links', 'language-selector-related');?></h2>
<?php
		$this->admin_related_links_by_type('post', $post_id);
	}

	/*
	 * (Admin) Show related links form for current tag or category
	 */
	public function admin_related_links_tags() {
		global $tag;
		$this->admin_related_links_by_type('tag', $tag->term_id);
	}

	/*
	 * List form with all related links depending page type send
	 */
	public function admin_related_links_by_type( $page_type, $id = '' ) {
		$list_links = array();
		if ( 'post' == $page_type ){
			if ( ! empty( $id ) ){
				$list_links = $this->admin_get_related_links( 'post', $id );
			}
		} elseif ( 'tag' == $page_type ) {
			if ( ! empty( $id ) ){
				$list_links = $this->admin_get_related_links( 'tag', $id );
			}
?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="category_theme"><?php _e('Language related links', 'language-selector-related');?></label></th>
			<td>
<?php
		} else {
			$list_links = $this->admin_get_related_links( 'tag', 0 );
?>
			<form method="POST" action="">
<?php
		}
?>
				<p><?php _e('Adds links and select the languages and countries for these for show in head web and widget selected zone (only if the widget is active. Only show language in the widget link)', 'language-selector-related');?></p>
				<p style="color:#f00"><?php _e("Remember fields URL and Language are required. If you no complete this fields of a row, this row aren't saved.", 'language-selector-related');?></p>
				<p><button type="button" class="button" onclick="action_rel_link_list('',true);" title="Add Related link"><?php _e('Add Related link', 'language-selector-related');?></button></p>
<?php
        $stylesColum = array();
        if ( 'post' != $page_type ){
            $stylesColum[0] = ' style="display:inline-block;width:30%"';
            $stylesColum[1] = ' style="display:inline-block;width:25%"';
            $stylesColum[2] = ' style="display:inline-block;width:25%"';
            $stylesColum[3] = ' style="display:inline-block;width:10%"';
        }
?>
				<table id="rel_link_list">
					<tbody>
						<tr valign="top">
							<th scope="row"<?php echo $stylesColum[0];?>><?php _e('URL', 'language-selector-related');?></th>
							<th scope="row"<?php echo $stylesColum[1];?>><?php _e('Language', 'language-selector-related');?></th>
							<th scope="row"<?php echo $stylesColum[2];?>><?php _e('Country', 'language-selector-related');?></th>
							<th scope="row"<?php echo $stylesColum[3];?>>&nbsp;</th>
						</tr>
<?php
		$num_links = count($list_links);
		$count_links = 0;
		if ( 0 < $num_links ){
			foreach ( $list_links AS $uri ){
				$langact = $countryact = '';
				if( ! empty( $uri['language'] ) ){
					$langact = $uri['language'];
				}
				if( ! empty( $uri['country'] ) ){
					$countryact = $uri['country'];
				}
?>
						<tr id="rel_link_<?php echo $count_links;?>" valign="top">
							<td<?php echo $stylesColum[0];?>><input type="text" id="uri[]" name="uri[]" style="width:100%" value="<?php echo ( ! empty( $uri['uri'] ) )?$uri['uri']:'http://';?>" <?php echo ( 'post' != $page_type )?'class="regular-text"':'';?>></td>
							<td<?php echo $stylesColum[1];?>><?php $this->get_language_select( $langact ); ?></td>
							<td<?php echo $stylesColum[2];?>><?php $this->get_countries_select( $countryact ); ?></td>
							<td<?php echo $stylesColum[3];?>><button type="button" class="button" onclick="action_rel_link_list('<?php echo $count_links;?>',false);" title="<?php _e('Delete link','language-selector-related');?>">X</button></td>
						</tr>
<?php
					$count_links++;
			}
		} else {
?>
						<tr id="rel_link_<?php echo $count_links;?>" valign="top">
							<td<?php echo $stylesColum[0];?>><input type="text" id="uri[]" name="uri[]" style="width:100%" value="http://" <?php echo ( 'post' != $page_type )?'class="regular-text"':'';?>></td>
							<td<?php echo $stylesColum[1];?>><?php $this->get_language_select(); ?></td>
							<td<?php echo $stylesColum[2];?>><?php $this->get_countries_select(); ?></td>
                            <td<?php echo $stylesColum[3];?>><button type="button" class="button" onclick="action_rel_link_list('<?php echo $count_links;?>',false)" title="<?php _e('Delete link','language-selector-related');?>">X</button></td>
						</tr>
<?php
			$count_links++;
		}
?>
					</tbody>
				</table>
				<script type="text/javascript">
					var last_id_link = <?php echo $count_links;?>;
					function action_rel_link_list(id,add){
						if (add){
							res ='<tr id="rel_link_' + last_id_link + '" valign="top">';
							res +='     <td<?php echo $stylesColum[0];?>><input type="text" id="uri[]" name="uri[]" style="width:100%" value="http://" <?php echo ( 'post' != $page_type )?'class="regular-text"':'';?>></td>';
							res +='     <td<?php echo $stylesColum[1];?>>' + "<?php $this->get_language_select( '', true ); ?></td>";
							res +='     <td<?php echo $stylesColum[2];?>>' + "<?php $this->get_countries_select( '', true ); ?></td>";
							res +='     <td<?php echo $stylesColum[3];?>><button type="button" class="button" onclick="action_rel_link_list(\'' + last_id_link + '\',false);" title="<?php _e('Delete link','language-selector-related');?>">X</button></td>';
							res +='</tr>';
							jQuery('#rel_link_list tr:last-child').before( res );
							last_id_link++;
						} else {
							jQuery('#rel_link_'+id).html('');
							jQuery('#rel_link_'+id).attr('id','');
						}
						return false;
					}
				</script>
<?php
		if ( 'tag' == $page_type ) {
?>
			</td>
		</tr>
<?php
		} elseif ( 'home' == $page_type ){
?>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes','language-selector-related');?>"></p>
			</form>
<?php
		}
	}

	/*
	 * Get related links by BD
	 */
	public function admin_get_related_links( $type_element, $id ){
		global $wpdb;

		$list_links = array();
		if ( -1 < $id ){
			if ( 'post' == $type_element ){
					$table_name = $wpdb->prefix . $this->table_name() . '_posts';
					$id_table = 'id_post';
			}else{
				$table_name = $wpdb->prefix . $this->table_name() . '_terms';
				$id_table = 'term_id';
			}
			$sql = "SELECT uri, language, country FROM $table_name WHERE $id_table = $id ORDER BY language;";
			$res = $wpdb->get_results( $sql );
			foreach ($res AS $uri){
				$link = array();
				$link['uri'] = $uri->uri;
				$link['language'] = $uri->language;
				$link['country'] = $uri->country;
				$list_links[] = $link;
			}
		}

		return $list_links;
	}
	/*
	 * Proceses POST form from tags panel
	 */
	public function admin_update_tags( $id_tag = -1 ){
		$uris = $languages = $countries = array();
		if ( -1 == $id_tag && isset( $_POST['tag_ID'] ) ){
			$id_tag = $_POST['tag_ID'];
		}
		if ( -1 < $id_tag ){
			if ( isset($_POST['uri']) && ! empty($_POST['uri']) ){
				$uris = $_POST['uri'];
			}
			if ( isset($_POST['language']) && ! empty($_POST['language']) ){
				$languages = $_POST['language'];
			}
			if ( isset($_POST['language']) && ! empty($_POST['language']) ){
				$countries = $_POST['country'];
			}
			$this->admin_update_related_links($id_tag, 'tag', $uris, $languages, $countries);
		}
	}
	/*
	 * (Admin) Delete related links form for current post
	 */
	public function admin_delete_related_links_tags() {
		if ( isset( $_REQUEST['tag_ID'] ) ) {
			$this->clear_related_links( (int) $_REQUEST['tag_ID'], 'tag' );
		}
	}

	/*
	 * (Admin) Proceses POST form from posts
	 */
	public function admin_update_posts(){
		$uris = $languages = $countries = array();
		if ( isset($_POST['post_ID'])){
			$id = $_POST['post_ID'];
			if ( isset($_POST['uri']) && ! empty($_POST['uri']) ){
				$uris = $_POST['uri'];
			}
			if ( isset($_POST['language']) && ! empty($_POST['language']) ){
				$languages = $_POST['language'];
			}
			if ( isset($_POST['language']) && ! empty($_POST['language']) ){
				$countries = $_POST['country'];
			}
			$this->admin_update_related_links($id, 'post', $uris, $languages, $countries);
		}
	}

	/*
	 * (Admin) Delete related links form for current post
	 */
	public function admin_delete_related_links_posts() {
		global $post_id;
		$this->clear_related_links( $post_id, 'post' );
	}

	/*
	 * (Admin) Update related links for elements posted
	 */
	public function admin_update_related_links($id, $type_element, $uris, $languages, $countries){
		if ( -1 < $id ){
			$this->clear_related_links( $id, $type_element );

			$list_links = array();
			for ( $i = 0; $i < count($uris); $i++){
				if ( ! empty( $uris[$i] ) ) {
					if ( isset( $languages[$i] ) && ! empty( $languages[$i] ) ){
						$link = array();
						$link['uri'] = $uris[$i];
						$link['language'] = $languages[$i];
                        $link['country'] = null;
                        if ( isset( $countries[$i] ) && ! empty( $countries[$i] ) ){
                            $link['country'] = $countries[$i];
                        }
						$list_links[] = $link;
					}
				}
			}
			$this->insert_related_links( $id, $type_element, $list_links );
		}
	}

	private function clear_related_links( $id, $type_element ) {
		global $wpdb;

		if ( 'post' == $type_element ){
			$table_name = $wpdb->prefix . $this->table_name() . '_posts';
			$id_table = 'id_post';
		} else {
			$table_name = $wpdb->prefix . $this->table_name() . '_terms';
			$id_table = 'term_id';
		}

		$sql = "DELETE FROM $table_name WHERE $id_table = '$id';";
		$wpdb->query( $sql );
	}

	private function insert_related_links( $id, $type_element, $list_links ) {
		global $wpdb;

		if ( 'post' == $type_element ){
			$table_name = $wpdb->prefix . $this->table_name() . '_posts';
			$id_table = 'id_post';
		}else{
			$table_name = $wpdb->prefix . $this->table_name() . '_terms';
			$id_table = 'term_id';
		}

		foreach ($list_links as $value) {
			if ( ! empty( $value['uri'] ) && ! empty( $value['language'] ) ){
                $sql = $wpdb->prepare(
                    "INSERT INTO $table_name ($id_table, uri, language, country)
                    VALUES (%d, %s, %s, %s );",
                    array(
                        $id,
                        $value['uri'],
                        $value['language'],
                        $value['country']
                    )
                );
				$wpdb->query( $sql );
			}
		}
	}

	private function get_language_select( $active = '', $javascript_format = false ){
		$languages = array(
            '--' => 'x-default',
			'om' => '(Afan) Oromo',
			'ab' => 'Abkhazian',
			'aa' => 'Afar',
			'af' => 'Afrikaans',
			'ak' => 'Akan',
			'sq' => 'Albanian',
			'am' => 'Amharic',
			'ar' => 'Arabic',
			'an' => 'Aragonese',
			'hy' => 'Armenian',
			'as' => 'Assamese',
			'av' => 'Avaric',
			'ae' => 'Avestan',
			'ay' => 'Aymara',
			'az' => 'Azerbaijani',
			'bm' => 'Bambara',
			'ba' => 'Bashkir',
			'eu' => 'Basque',
			'be' => 'Belarusian',
			'bn' => 'Bengali; Bangla',
			'bh' => 'Bihari',
			'bi' => 'Bislama',
			'bs' => 'Bosnian',
			'br' => 'Breton',
			'bg' => 'Bulgarian',
			'my' => 'Burmese',
			'ca' => 'Catalan',
			'km' => 'Central Khmer; Cambodian',
			'ch' => 'Chamorro',
			'ce' => 'Chechen',
			'ny' => 'Chichewa; Nyanja',
			'zh' => 'Chinese',
			'cu' => 'Church Slavic',
			'cv' => 'Chuvash',
			'kw' => 'Cornish',
			'co' => 'Corsican',
			'cr' => 'Cree',
			'hr' => 'Croatian',
			'cs' => 'Czech',
			'da' => 'Danish',
			'dv' => 'Divehi; Maldivian',
			'nl' => 'Dutch',
			'dz' => 'Dzongkha; Bhutani',
			'en' => 'English',
			'eo' => 'Esperanto',
			'et' => 'Estonian',
			'fo' => 'Faroese',
			'fj' => 'Fijian; Fiji',
			'fi' => 'Finnish',
			'fr' => 'French',
			'ff' => 'Fulah',
			'gl' => 'Galician',
			'lg' => 'Ganda',
			'ka' => 'Georgian',
			'de' => 'German',
			'el' => 'Greek',
			'gn' => 'Guarani',
			'gu' => 'Gujarati',
			'ht' => 'Haitian; Haitian Creole',
			'ha' => 'Hausa',
			'he' => 'Hebrew (formerly iw)',
			'hz' => 'Herero',
			'hi' => 'Hindi',
			'ho' => 'Hiri Motu',
			'hu' => 'Hungarian',
			'is' => 'Icelandic',
			'io' => 'Ido',
			'ig' => 'Igbo',
			'id' => 'Indonesian (formerly in)',
			'ia' => 'Interlingua',
			'ie' => 'Interlingue; Occidental',
			'iu' => 'Inuktitut',
			'ik' => 'Inupiak; Inupiaq',
			'ga' => 'Irish',
			'it' => 'Italian',
			'ja' => 'Japanese',
			'jv' => 'Javanese',
			'kl' => 'Kalaallisut; Greenlandic',
			'kn' => 'Kannada',
			'kr' => 'Kanuri',
			'ks' => 'Kashmiri',
			'kk' => 'Kazakh',
			'ki' => 'Kikuyu; Gikuyu',
			'rw' => 'Kinyarwanda',
			'ky' => 'Kirghiz',
			'kv' => 'Komi',
			'kg' => 'Kongo',
			'ko' => 'Korean',
			'kj' => 'Kuanyama; Kwanyama',
			'ku' => 'Kurdish',
			'lo' => 'Lao; Laotian',
			'la' => 'Latin',
			'lv' => 'Latvian; Lettish',
			'lb' => 'Letzeburgesch; Luxembourgish',
			'li' => 'Limburgish; Limburger; Limburgan',
			'ln' => 'Lingala',
			'lt' => 'Lithuanian',
			'lu' => 'Luba-Katanga',
			'mk' => 'Macedonian',
			'mg' => 'Malagasy',
			'ms' => 'Malay',
			'ml' => 'Malayalam',
			'mt' => 'Maltese',
			'gv' => 'Manx',
			'mi' => 'Maori',
			'mr' => 'Marathi',
			'mh' => 'Marshallese',
			'mo' => 'Moldavian',
			'mn' => 'Mongolian',
			'na' => 'Nauru',
			'nv' => 'Navajo; Navaho',
			'nd' => 'Ndebele, North',
			'nr' => 'Ndebele, South',
			'ng' => 'Ndonga',
			'ne' => 'Nepali',
			'se' => 'Northern Sami',
			'no' => 'Norwegian',
			'nb' => 'Norwegian Bokmål',
			'nn' => 'Norwegian Nynorsk',
			'oc' => 'Occitan; Provençal',
			'oj' => 'Ojibwa',
			'or' => 'Oriya',
			'os' => 'Ossetian; Ossetic',
			'pi' => 'Pali',
			'pa' => 'Panjabi; Punjabi',
			'ps' => 'Pashto; Pushto',
			'fa' => 'Persian',
			'pl' => 'Polish',
			'pt' => 'Portuguese',
			'qu' => 'Quechua',
			'ro' => 'Romanian',
			'rm' => 'Romansh',
			'rn' => 'Rundi; Kirundi',
			'ru' => 'Russian',
			'sm' => 'Samoan',
			'sg' => 'Sango; Sangro',
			'sa' => 'Sanskrit',
			'sc' => 'Sardinian',
			'gd' => 'Scottish Gaelic',
			'sr' => 'Serbian',
			'st' => 'Sesotho; Sotho, Southern',
			'sn' => 'Shona',
			'ii' => 'Sichuan Yi; Nuosu',
			'sd' => 'Sindhi',
			'si' => 'Sinhala; Sinhalese',
			'sk' => 'Slovak',
			'sl' => 'Slovenian',
			'so' => 'Somali',
			'es' => 'Spanish',
			'su' => 'Sundanese',
			'sw' => 'Swahili',
			'ss' => 'Swati; Siswati',
			'sv' => 'Swedish',
			'tl' => 'Tagalog',
			'ty' => 'Tahitian',
			'tg' => 'Tajik',
			'ta' => 'Tamil',
			'tt' => 'Tatar',
			'te' => 'Telugu',
			'th' => 'Thai',
			'bo' => 'Tibetan',
			'ti' => 'Tigrinya',
			'to' => 'Tonga',
			'ts' => 'Tsonga',
			'tn' => 'Tswana; Setswana',
			'tr' => 'Turkish',
			'tk' => 'Turkmen',
			'tw' => 'Twi',
			'ug' => 'Uighur',
			'uk' => 'Ukrainian',
			'ur' => 'Urdu',
			'uz' => 'Uzbek',
			've' => 'Venda',
			'vi' => 'Vietnamese',
			'vo' => 'Volapük; Volapuk',
			'wa' => 'Walloon',
			'cy' => 'Welsh',
			'fy' => 'Western Frisian',
			'wo' => 'Wolof',
			'xh' => 'Xhosa',
			'yi' => 'Yiddish (formerly ji)',
			'yo' => 'Yoruba',
			'za' => 'Zhuang',
			'zu' => 'Zulu',
			'ee' => 'Éwé'
		);

?>          <select name='language[]' style='width:100%'><?php echo ($javascript_format)?' \n\ ':'';?>
                <option value=''><?php _e('Select language for this URL', 'language-selector-related');?></option><?php echo ($javascript_format)?' \n\ ':'';?>
<?php
		foreach ($languages as $code => $value){
?>              <option value='<?php echo $code;?>' <?php echo ( $code == $active )?'selected=\'selected\'':'';?>><?php echo $value;?></option><?php echo ($javascript_format)?' \n\ ':'';?>
<?php
		}
?>          </select><?php echo ($javascript_format)?' \n\ ':'';?>
<?php
	}

	private function get_countries_select( $active = '', $javascript_format = false ){
		$countries = array(
			'AX' => 'Aaland Islands',
			'AF' => 'Afghanistan',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'GB' => 'Britain (United Kingdom)',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos (Keeling) Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CD' => 'Congo (Dem. Rep.)',
			'CG' => 'Congo (Rep.)',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'CI' => 'Côte d\'Ivoire',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FO' => 'Faeroe Islands',
			'FK' => 'Falkland Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern and Antarctic Lands',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island and McDonald Islands',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KP' => 'Korea (North)',
			'KR' => 'Korea (South)',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Laos',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia',
			'MD' => 'Moldova',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar (Burma)',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestine',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russia',
			'RW' => 'Rwanda',
			'AS' => 'Samoa (American)',
			'WS' => 'Samoa (Western)',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome and Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia and the South Sandwich Islands',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SH' => 'St Helena',
			'KN' => 'St Kitts and Nevis',
			'LC' => 'St Lucia',
			'PM' => 'St Pierre and Miquelon',
			'VC' => 'St Vincent and the Grenadines',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syria',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UM' => 'US minor outlying islands',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'US' => 'United States',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VA' => 'Vatican City',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam',
			'VG' => 'Virgin Islands (UK)',
			'VI' => 'Virgin Islands (US)',
			'WF' => 'Wallis and Futuna',
			'EH' => 'Western Sahara',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe'
		);
?>          <select name='country[]' style='width:100%'><?php echo ($javascript_format)?' \n\ ':'';?>
                <option value=''><?php _e('Select country for this URL', 'language-selector-related');?></option><?php echo ($javascript_format)?' \n\ ':'';?>
<?php
		foreach ($countries as $code => $value) {
?>              <option value='<?php echo $code;?>'<?php echo ( $code == $active )?' selected=\'selected\'':'';?>><?php echo $value;?></option><?php echo ($javascript_format)?' \n\ ':'';?>
<?php
		}
?>          </select><?php echo ($javascript_format)?' \n\ ':'';?>
<?php
	}
}
?>