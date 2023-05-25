<?php

namespace Doofinder\WP;

use Doofinder\WP\Update_On_Save_Index;
use Doofinder\WP\Log;
use Doofinder\WP\Post;
use Doofinder\WP\Multilanguage\Multilanguage;
use Doofinder\WP\Settings\Accessors;

class Update_On_Save {

    /**
    * Creates the database table for storing update on save information if it doesn't exist.
    */
    public static function create_update_on_save_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'doofinder_update_on_save';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'");

        if ($table_exists === NULL) {
            // The table does not exist, we create it
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                post_id INT NOT NULL,
                type_post VARCHAR(255),
                type_action VARCHAR(255),
                PRIMARY KEY (post_id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        update_option( 'doofinder_update_on_save_last_exec', date('Y-m-d H:i:s') );
	}

    /**
     * Register hooks and actions.
     */
    public static function register_hooks() {
        add_action('wp_insert_post', function ($post_id, \WP_Post $post, $updated) {
            self::add_item_to_update($post_id, $post, $updated);
        }, 99, 3);
    }

    /**
     * Determines if the current post should be indexed and adds it to the update queue.
     *
     * @param int $post_id The ID of the post.
     * @param WP_Post $post The post object.
     */
	public static function add_item_to_update($post_id, $post, $updated) {

        $log = new Log( 'update_on_save.txt' );
        $update_on_save_index = new Update_On_Save_Index();

        if (Settings::is_update_on_save_enabled()) {
            $doofinder_post = new Post($post);
            $log->log( 'Add this item to update on save: ' . print_r($doofinder_post, true)); 

            self::add_item_to_db($doofinder_post, $post_id, $post->post_status, $post->post_type);

            if(self::allow_process_items()) {
                $log->log( 'We can send the data. '); 
                $update_on_save_index->launch_doofinder_update_on_save();
                self::clean_update_on_save_db();
            }
        }
	}

    /**
     * Adds the item to the update queue in the database.
     *
     * @param Post $doofinder_post The Doofinder post object.
     * @param int $post_id The ID of the post.
     * @param string $status The status of the post.
     * @param string $type The type of the post.
     */
    public static function add_item_to_db($doofinder_post, $post_id, $status, $type) {
        $log = new Log( 'update_on_save.txt' );
        if ( $status === 'auto-draft' || $type === "revision") {
            # If it is draft or revision we don't need to do anything with it because we don't have to index it.
            $log->log( 'It is not necessary to save it as it is a draft. '); 
        } elseif ( $doofinder_post->is_indexable()) {
            # If the status of the post is still indexable after the changes we do an update.
            $log->log( 'The item will be saved with the update action. '); 
            self::add_to_update_on_save_db($post_id, $type, "update");
        } else {
            # If the status of the post is no longer indexable we have to delete it.
            $log->log( 'The item will be saved with the delete action. '); 
            self::add_to_update_on_save_db($post_id, $type, "delete");
        }
    }

    /**
     * Adds an item to the update queue in the database.
     *
     * @param int $post_id The ID of the post.
     * @param string $post_type The type of the post.
     * @param string $action The action to perform on the post (update/delete).
     */
	public static function add_to_update_on_save_db($post_id, $post_type, $action) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'doofinder_update_on_save';

        $resultado = $wpdb->get_var("SELECT * FROM $table_name WHERE post_id = $post_id" );

        if ($resultado != null) {       
            $wpdb->update( $table_name,
                array(
                    'post_id' => $post_id,
                    'type_post' => $post_type,
                    'type_action' => $action
                ),
                array( 'id' => $resultado )
            );
        } else {
            $wpdb->insert( $table_name,
                array(
                    'post_id' => $post_id,
                    'type_post' => $post_type,
                    'type_action' => $action
                )
            );
        }
	}

    /**
     * Checks if processing of items is allowed based on the configured update frequency.
     *
     * @return bool True if processing is allowed, false otherwise.
     */
    public static function allow_process_items() {

        $log = new Log( 'update_on_save.txt' );
        $language = Multilanguage::instance();
        $current_language = $language->get_active_language();
        $update_on_save_option = Accessors::get_update_on_save($current_language);
        
        $last_exec = get_option('doofinder_update_on_save_last_exec');

        $log->log( 'The last execution was: ' . $last_exec ); 

        switch ($update_on_save_option) {
            case "each_15_minutes":
                $delay = 15;
                break;
            case "each_30_minutes":
                $delay = 30;
                break;
            case "hourly":
                $delay = 60;
                break;
            case "every_3_hours":
                $delay = 180;
                break;
            case "every_6_hours":
                $delay = 360;
                break;
            case "every_12_hours":
                $delay = 720;
                break;
            default:
                $delay = 15;
                break;
        }

        $log->log( 'The established delay is:  ' . $last_exec ); 

        $last_exec_ts = strtotime($last_exec);

        $diff_min = (time() - $last_exec_ts) / 60;

        $log->log( 'The difference is:  ' . $diff_min ); 

        if ($diff_min >= $delay) {
            return true;
        }

        return false;
    }

    /**
     * Cleans the update on save database table by deleting all entries.
     *
     * @return bool True if the database table was cleaned successfully, false otherwise.
     */
    public static function clean_update_on_save_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'doofinder_update_on_save';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        $log = new Log( 'update_on_save.txt' );
        $log->log( 'Cleaned database' ); 
	}

    /**
     * Deletes the update on save database table.
     *
     * @return bool True if the database table was deleted successfully, false otherwise.
     */
    public static function delete_update_on_save_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'doofinder_update_on_save';

        $wpdb->query( "DROP TABLE $table_name" );

        $log = new Log( 'update_on_save.txt' );
        $log->log( 'Deleted database' ); 
	}
}
