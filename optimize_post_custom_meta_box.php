<?php
/*
Plugin Name: Optimize post custom meta box
Plugin URI:
Description: Optimize wordpress post custom meta box query
Author: Antonio Alarcon
Version: 1.0
Author URI:
*/

//Optimize post custom meta box ACTIVATION
if ( ! function_exists( 'optimize_post_custom_meta_box_activation' ) )
{

    /**
     * Gallery To Slideshow Activation
     *
     * @package Gallery To Slideshow
     * @since 1.0
     *
     */
    function optimize_post_custom_meta_box_activation()
    {
        // check compatibility
        if ( version_compare( get_bloginfo( 'version' ), '4.0' ) >= 0 )
            deactivate_plugins( basename( __FILE__ ) );
    }

    register_activation_hook( __FILE__, 'optimize_post_custom_meta_box_activation' );
}

function optimize_post_custom_meta_box_remove_post_meta_box() {
    global $post_type;

    if ( is_admin() && post_type_supports( $post_type, 'custom-fields' ) ) {
        remove_meta_box( 'postcustom', 'post', 'normal' );
        add_meta_box( 'optimize_post_custom_meta_box_postcustom', __('Custom Fields'), 'optimize_post_custom_meta_box_admin', null, 'normal', 'core' );
    }
}

add_action( 'add_meta_boxes', 'optimize_post_custom_meta_box_remove_post_meta_box' );

function optimize_post_custom_meta_box_admin( $post ) {
    ?>
    <div id="postcustom">
        <div id="postcustomstuff">
            <div id="ajax-response"></div>
            <?php
            $metadata = has_meta($post->ID);
            foreach ( $metadata as $key => $value ) {
                if ( is_protected_meta( $metadata[ $key ][ 'meta_key' ], 'post' ) || ! current_user_can( 'edit_post_meta', $post->ID, $metadata[ $key ][ 'meta_key' ] ) )
                    unset( $metadata[ $key ] );
            }
            list_meta( $metadata );
            optimize_post_custom_meta_box_meta_form( $post ); ?>
            <p><?php _e('Custom fields can be used to add extra metadata to a post that you can <a href="https://codex.wordpress.org/Using_Custom_Fields" target="_blank">use in your theme</a>.'); ?></p>
        </div>
    </div>
    <?php
}

function optimize_post_custom_meta_box_meta_form( $post = null ) {
    global $wpdb;
    $post = get_post( $post );

    if ( false === ( $keys = get_transient( 'optimize_post_custom_meta_box_meta_keys' ) ) ) {
        $limit = apply_filters( 'postmeta_form_limit', 30 );
        $sql = "SELECT DISTINCT meta_key
			FROM $wpdb->postmeta
			WHERE meta_key NOT BETWEEN '_' AND '_z' 
    	    HAVING meta_key NOT LIKE %s
			ORDER BY meta_key
			LIMIT %d";
        $keys = $wpdb->get_col( $wpdb->prepare( $sql, $wpdb->esc_like( '_' ) . '%', $limit ) );

        set_transient( 'optimize_post_custom_meta_box_meta_keys', $keys, 60 * 60 );
    }

    if ( $keys ) {
        natcasesort( $keys );
        $meta_key_input_id = 'metakeyselect';
    } else {
        $meta_key_input_id = 'metakeyinput';
    }
    ?>
    <p><strong><?php _e( 'Add New Custom Field:' ) ?></strong></p>
    <table id="newmeta">
        <thead>
        <tr>
            <th class="left"><label for="<?php echo $meta_key_input_id; ?>"><?php _ex( 'Name', 'meta name' ) ?></label></th>
            <th><label for="metavalue"><?php _e( 'Value' ) ?></label></th>
        </tr>
        </thead>

        <tbody>
        <tr>
            <td id="newmetaleft" class="left">
                <?php if ( $keys ) { ?>
                    <select id="metakeyselect" name="metakeyselect">
                        <option value="#NONE#"><?php _e( '&mdash; Select &mdash;' ); ?></option>
                        <?php

                        foreach ( $keys as $key ) {
                            if ( is_protected_meta( $key, 'post' ) || ! current_user_can( 'add_post_meta', $post->ID, $key ) )
                                continue;
                            echo "\n<option value='" . esc_attr($key) . "'>" . esc_html($key) . "</option>";
                        }
                        ?>
                    </select>
                    <input class="hide-if-js" type="text" id="metakeyinput" name="metakeyinput" value="" />
                    <a href="#postcustomstuff" class="hide-if-no-js" onclick="jQuery('#metakeyinput, #metakeyselect, #enternew, #cancelnew').toggle();return false;">
                        <span id="enternew"><?php _e('Enter new'); ?></span>
                        <span id="cancelnew" class="hidden"><?php _e('Cancel'); ?></span></a>
                <?php } else { ?>
                    <input type="text" id="metakeyinput" name="metakeyinput" value="" />
                <?php } ?>
            </td>
            <td><textarea id="metavalue" name="metavalue" rows="2" cols="25"></textarea></td>
        </tr>

        <tr><td colspan="2">
                <div class="submit">
                    <?php submit_button( __( 'Add Custom Field' ), 'secondary', 'addmeta', false, array( 'id' => 'newmeta-submit', 'data-wp-lists' => 'add:the-list:newmeta' ) ); ?>
                </div>
                <?php wp_nonce_field( 'add-meta', '_ajax_nonce-add-meta', false ); ?>
            </td></tr>
        </tbody>
    </table>
    <?php

}

function optimize_post_custom_meta_box_delete_meta_keys_transient() {
    delete_transient( 'optimize_post_custom_meta_box_meta_keys' );
}
add_action( 'update_post_meta', 'optimize_post_custom_meta_box_delete_meta_keys_transient' );
add_action( 'delete_post_meta', 'optimize_post_custom_meta_box_delete_meta_keys_transient' );