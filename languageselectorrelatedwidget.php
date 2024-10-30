<?php
/**
 * @package Language_Selector_Related
 * @version 1.1
 * @author Ruben Vasallo
 *
 * Language Selector Related Class for manage widget
 */

class Language_Selector_Related_Widget extends WP_Widget {

	function language_selector_related_widget() {
		parent::__construct(
			false,
			__('Language Selector Related Widget', 'language-selector-related'),
			array( 'description' => __('widget that show related languages links', 'language-selector-related'))
			);
	}

	function widget ( $args, $instance ){
		$language_selector_related = new Language_Selector_Related_Class();
		extract( $args );
		if ( isset($instance) && !empty($instance['title']) ){
			$title = apply_filters( 'widget_title', $instance['title'] );
		}

		echo $before_widget;
		if ( ! empty( $title ) )
				echo $before_title . $title . $after_title;
		$language_selector_related->find_language(true);
		echo $after_widget;
	}

	function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __('Other languages', 'language-selector-related');
		}
?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo 'Title:'; ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}
}

function languageSelectorRealted_register_widgets() {
	register_widget( 'language_selector_related_widget' );
}

add_action( 'widgets_init', 'languageSelectorRealted_register_widgets');
?>