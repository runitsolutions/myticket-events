<?php
/**
 * Custom template tags for this plugin.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package myticket
 */

add_action('wp_ajax_nopriv_myticket_events_get_reservations', 'myticket_events_get_reservations');
add_action('wp_ajax_myticket_events_get_reservations', 'myticket_events_get_reservations');
if ( ! function_exists( 'myticket_events_get_reservations' ) ) {
    function myticket_events_get_reservations() {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'myticket_events_ajax' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            return;
        }

        $id          = (isset($_POST['id'])) ? absint($_POST['id']) : 0;
        $user_id     = (isset($_POST['user_id'])) ? sanitize_text_field($_POST['user_id']) : '';
        $time        = (isset($_POST['time'])) ? sanitize_text_field($_POST['time']) : '';
        $render_type = (isset($_POST['render_type'])) ? absint($_POST['render_type']) : 0;
        $zone_id     = (isset($_POST['zone_id'])) ? sanitize_text_field($_POST['zone_id']) : '';

        // Validate product ID
        if ( $id <= 0 || ! get_post( $id ) ) {
            wp_send_json_error( array( 'message' => 'Invalid product ID' ) );
            return;
        }

        $output = [];
        $output['success'] = true;

        // retrieve all reservations
        $reservations = json_decode(get_option('myticket_'.$id, '[]'), true);

        // seats mode.
        if($render_type == 1){

            // return all reservations
            $output['data'] = json_decode(get_option('myticket_'.$id, '[]'));
        }

        // zones mode. split data for large layout types
        if($render_type == 0){

            // return reservations partially and provide individual zone statistics
            $reservations_current_zone = [];
            $reservations_all_zones = [];

            $clear_time = get_option("myticket_res_time", 0);
            // get cached data
            if(time() - $clear_time < 3) {

                $output['data'] = json_decode(get_option('myticket_data_'.$id.'_'.$zone_id, '[]'));
                $output['data_zones'] = json_decode(get_option('myticket_data_zones_'.$id.'_'.$zone_id, '[]'));
                $output['cached'] = true;

            // get latest data
            }else{

                // retrieve only selected zone
                foreach($reservations as $key => $val){

                    $_zone_id = explode("_" ,$key)[0];
                    
                    // count zone reservations
                    if(!isset($reservations_all_zones[$_zone_id])){

                        $reservations_all_zones[$_zone_id] = [];
                        $reservations_all_zones[$_zone_id]['count'] = 1;
                    }else{
                        $reservations_all_zones[$_zone_id]['count'] += 1;
                    }

                    // retrieve only selected zone if ( intval($zone_id)>0 ) 
                    if ( $_zone_id == $zone_id ){ $reservations_current_zone[$key] = $val; }
                }

                update_option('myticket_res_time', time());
  
                update_option('myticket_data_'.$id.'_'.$zone_id, json_encode($reservations_current_zone));
                update_option('myticket_data_zones_'.$id.'_'.$zone_id, json_encode($reservations_all_zones));

                $output['data'] = $reservations_current_zone;
                $output['data_zones'] = $reservations_all_zones;
                $output['cached'] = false;
            }
        }

        // clear unsuccessful reservations
        $reservations = myticket_events_clear_reservations($reservations, $user_id);
        update_option('myticket_'.$id, json_encode($reservations));

        echo json_encode($output);
        
        wp_reset_postdata();
        wp_die();
    }
}

// set seat reservations immediately before checkout
add_action('wp_ajax_nopriv_myticket_events_set_reservations', 'myticket_events_set_reservations');
add_action('wp_ajax_myticket_events_set_reservations', 'myticket_events_set_reservations');
if ( ! function_exists( 'myticket_events_set_reservations' ) ) {
    function myticket_events_set_reservations() {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'myticket_events_ajax' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            return;
        }

        $id                       = (isset($_POST['id'])) ? absint($_POST['id']) : 0;
        $user_id                  = (isset($_POST['user_id'])) ? sanitize_text_field($_POST['user_id']) : '';
        $time                     = (isset($_POST['time'])) ? sanitize_text_field($_POST['time']) : '';

        // Validate product ID
        if ( $id <= 0 || ! get_post( $id ) ) {
            wp_send_json_error( array( 'message' => 'Invalid product ID' ) );
            return;
        }

        // Validate tickets array exists
        if ( ! isset( $_POST['tickets'] ) || ! is_array( $_POST['tickets'] ) ) {
            wp_send_json_error( array( 'message' => 'Invalid tickets data' ) );
            return;
        }

        $output = [];
        $tickets = [];

        // clear cart to make sure tickets not added twice
        if ( class_exists( 'WooCommerce' ) ) {
            WC()->cart->empty_cart(true);
            WC()->session->set('cart', array());
        }

        $reservations = json_decode(get_option("myticket_".$id, '[]'), true);

        // clear unsuccessful reservations
        $reservations = myticket_events_clear_reservations($reservations, $user_id);

        // add reservation for current user
        foreach ($_POST['tickets'] as $key => $value){

            $zone_id = sanitize_text_field($_POST['tickets'][$key]['zone_id']);
            $ticket_id = sanitize_text_field($_POST['tickets'][$key]['ticket_id']);
            $ticket_type = sanitize_text_field($_POST['tickets'][$key]['ticket_type']);
            // $zone_text = sanitize_text_field($_POST['tickets'][$key]['zone_text']);
            // $ticket_text = sanitize_text_field($_POST['tickets'][$key]['ticket_text']);
            // $ticket_row = sanitize_text_field($_POST['tickets'][$key]['ticket_row']);
            // $ticket_price = sanitize_text_field($_POST['tickets'][$key]['ticket_price']);

            $allow = true;

            // check if not reserved by others
            if(isset($reservations[$zone_id."_".$ticket_id])){
                if($reservations[$zone_id."_".$ticket_id]["user"] != $user_id){

                    // report frontend if seats already taken
                    $output['notreserved'][] = $zone_id."_".$ticket_id;
                    $allow = false;
                }
            }

            // type 1 = reserved | type 2 = reserved + checkout | type 3 = booked
            if($allow){

                // track successfull reservations
                $output['reserved'][] = $zone_id."_".$ticket_id;
                // $temp = array("type"=>1, "user"=>$user_id, "time"=>time()); // "zone_text"=>$zone_text, "ticket_text"=>$ticket_text, "ticket_row"=>$ticket_row, "ticket_price"=>$ticket_price
                $temp = [];

                // add custom fields and additional data
                foreach($_POST['tickets'][$key] as $k => $v){

                    // $temp[$k] = sanitize_option($v);
                    $temp[$k] = myticket_events_recursive_sanitize_text_field($v);
                    // $temp[$k] = sanitize_text_field($_POST['tickets'][$key][$k]);
                    // $kv = []; $kv[$k] = $v;
                    // array_push($temp, $kv);
                }

                $temp["tt"] = $ticket_type;
                $temp["type"] = 1;
                $temp["user"] = $user_id;
                $temp["time"] = time();

                $reservations[$zone_id."_".$ticket_id] = $temp;
            }
        }

        update_option("myticket_".$id, json_encode($reservations));

        $output['success'] = true;
        $output['reservations'] = $reservations;

        echo json_encode($output);
        
        wp_reset_postdata();
        wp_die();
    }
}

// clear unsuccessful reservations and bookings
if ( ! function_exists( 'myticket_events_clear_reservations' ) ) {
    function myticket_events_clear_reservations($reservations, $user_id) {

        // allow execution every 10 seconds to prevent server overload. Useful when many users calling this script 
        $clear_time = get_option("myticket_clear_time", 0);
        if(time() - $clear_time < 10) return $reservations;
        update_option("myticket_clear_time", time());

        // remove previous reservations for current user
        foreach ($reservations as $key => $value) {

            // clear not booked reservations older than 20 mins | Ex.: abandoned cart case
            if($reservations[$key]['time']<time()-(get_theme_mod('myticket_res_time', 20)*60) && $reservations[$key]['type'] < 3){
                $reservations[$key]['type'] = 0;
            }

            // clear not booked reservations older than 1 day | Ex.: abandoned cart case
            if($reservations[$key]['time']<time()-86400 && $reservations[$key]['type'] < 3){
                unset($reservations[$key]);

                if($reservations[$key]['user']==$user_id){
                    unset($reservations[$key]); 
                }
            }
        }

        return $reservations;
    }
}

// admin mode. Update bookings
add_action('wp_ajax_nopriv_myticket_events_set_booking', 'myticket_events_set_booking');
add_action('wp_ajax_myticket_events_set_booking', 'myticket_events_set_booking');
if ( ! function_exists( 'myticket_events_set_booking' ) ) {
    function myticket_events_set_booking() {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'myticket_events_ajax' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            return;
        }

        $user = wp_get_current_user();
        if ( !in_array( 'administrator', (array) $user->roles ) ) {
            wp_send_json_error( array( 'message' => 'Not an admin' ) );
            return;
        }

        $id                       = (isset($_POST['id'])) ? absint($_POST['id']) : 0;
        $user_id                  = (isset($_POST['user_id'])) ? sanitize_text_field($_POST['user_id']) : '';
        $seat_id                  = (isset($_POST['seat_id'])) ? sanitize_text_field($_POST['seat_id']) : '';
        $zone_id                  = (isset($_POST['zone_id'])) ? sanitize_text_field($_POST['zone_id']) : '';
        $time                     = (isset($_POST['time'])) ? sanitize_text_field($_POST['time']) : '';
        $baction                  = (isset($_POST['baction'])) ? sanitize_text_field($_POST['baction']) : '';

        // Validate product ID
        if ( $id <= 0 || ! get_post( $id ) ) {
            wp_send_json_error( array( 'message' => 'Invalid product ID' ) );
            return;
        }

        // Validate action
        if ( ! in_array( $baction, array( 'clear', 'book' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid action' ) );
            return;
        }

        $output = [];
        $tickets = [];

        // clear cart to make sure tickets not added twice
        if ( class_exists( 'WooCommerce' ) ) {
            WC()->cart->empty_cart(true);
            WC()->session->set('cart', array());
        }

        $reservations = json_decode(get_option("myticket_".$id, '[]'), true);

        switch($baction){

            // clear reservation for selected seat
            case 'clear':

                $output['bookings'] = get_option("myticket_".$id, '[]');
                unset($reservations[$seat_id]); 

            break;
            // mark seat as reserved by admin
            case 'book':

                $output['reserved'][] = $seat_id;

                $zone_text = $ticket_text = $ticket_row = $ticket_price = "-";
                $temp = array("type"=>3, "user"=>"admin", "time"=>time(), "zone_text"=>$zone_text, "zone_id"=>$zone_id, "ticket_text"=>$ticket_text, "ticket_row"=>$ticket_row, "ticket_price"=>$ticket_price);
                $reservations[$seat_id] = $temp;
            break;
        }

        update_option("myticket_".$id, json_encode($reservations));

        $output['success'] = true;
        $output['reservations'] = $reservations;

        echo json_encode($output);
        
        wp_reset_postdata();
        wp_die();
    }
}

// retrieve stadium from templates directory
add_action('wp_ajax_nopriv_myticket_events_get_templates', 'myticket_events_get_templates');
add_action('wp_ajax_myticket_events_get_templates', 'myticket_events_get_templates');
if ( ! function_exists( 'myticket_events_get_templates' ) ) {
    function myticket_events_get_templates() {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'myticket_events_ajax' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            return;
        }

        $layout_slug = (isset($_POST['layout'])) ? sanitize_file_name($_POST['layout']) : '';
        $table_slug  = (isset($_POST['table'])) ? sanitize_file_name($_POST['table']) : '';

        $output = array();

        // Validate and sanitize file paths to prevent directory traversal (WordPress best practices)
        if ( ! empty( $layout_slug ) ) {
            $layout_slug = preg_replace( '/[^a-zA-Z0-9_-]/', '', $layout_slug ); // Remove dangerous characters
            $allowed_dir = trailingslashit( get_template_directory() . '/' . MYTICKET_SLUG . '/layouts/' );
            $fl = $allowed_dir . $layout_slug . '.json';
            
            // Normalize paths using WordPress function for cross-platform compatibility
            $fl_normalized = wp_normalize_path( $fl );
            $allowed_dir_normalized = wp_normalize_path( $allowed_dir );
            
            // Verify file is within allowed directory
            $real_path = realpath( $fl_normalized );
            $allowed_dir_real = realpath( $allowed_dir_normalized );
            
            if ( $real_path && $allowed_dir_real && 0 === stripos( wp_normalize_path( $real_path ), $allowed_dir_real ) && file_exists( $fl_normalized ) ) {
                $layout_content = file_get_contents( $fl_normalized );
                if ( $layout_content !== false ) {
                    $output['layout'] = $layout_content;
                }
            }
        }

        if ( ! empty( $table_slug ) ) {
            $table_slug = preg_replace( '/[^a-zA-Z0-9_-]/', '', $table_slug ); // Remove dangerous characters
            $allowed_dir = trailingslashit( get_template_directory() . '/' . MYTICKET_SLUG . '/layouts/' );
            $fl = $allowed_dir . $table_slug . '.json';
            
            // Normalize paths using WordPress function for cross-platform compatibility
            $fl_normalized = wp_normalize_path( $fl );
            $allowed_dir_normalized = wp_normalize_path( $allowed_dir );
            
            // Verify file is within allowed directory
            $real_path = realpath( $fl_normalized );
            $allowed_dir_real = realpath( $allowed_dir_normalized );
            
            if ( $real_path && $allowed_dir_real && 0 === stripos( wp_normalize_path( $real_path ), $allowed_dir_real ) && file_exists( $fl_normalized ) ) {
                $table_content = file_get_contents( $fl_normalized );
                if ( $table_content !== false ) {
                    $output['table'] = $table_content;
                }
            }
        }

        $output['success'] = true;

        wp_send_json_success( $output );
    }
}

/**
 * Recursive sanitation for an array
 * 
 * @param $array
 *
 * @return mixed
 */
function myticket_events_recursive_sanitize_text_field($array) {
    
    foreach ( $array as $key => &$value ) {
        if ( is_array( $value ) ) {
            $value = myticket_events_recursive_sanitize_text_field($value);
        }
        else {
            $value = sanitize_text_field( $value );
        }
    }

    return $array;
}