<?php
/**
 * @package MyTicket Events Class
 */
/*
Plugin Name: MyTicket Events Class
Plugin URI: https://github.com/runitsolutions/myticket-events
Description: Create event listings, organize events, link events with WooCommerce orders, print PDF invoices.
Author: RunIT Solutions
Author URI: https://runitcr.com/
Version: 2.0.0
*/

if ( ! class_exists( 'MyTicket_Events' ) ) {

	final class MyTicket_Events {

		/**
		 * Instance of the class
		 */
		private static $_instance = null;

		/**
		 * MyTicket_Events Constructor.
		 */
		public function __construct() {

			$this->init_hooks();
		}

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function admin_order_items_headers( $order ){ ?>

			<th class="line_customtitle sortable" data-sort="your-sort-option">
				<?php esc_html_e('Ticket', 'myticket-events'); ?>
			</th>
	
		<?php }

		public function admin_order_item_values( $product, $item, $item_id = null ) {

			//Get what you need from $product, $item or $item_id
			?>
			<td class="line_customtitle">
				<?php if( 'line_item' == $item->get_type() ) $this->show_invoice_button_individual( __( 'View', 'myticket-events' ), $item['order_id'], $item_id, 'create_single', array( 'class="button grant_access order-page invoice"' ) ); ?>
			</td><?php
		}

		/**
		 * Initialize admin.
		 */
		public function admin_init_hooks() {

			add_action( 'admin_init', array( $this, 'admin_pdf_callback' ) );

			//WooCommerce custom admin order item columns
			add_action( 'woocommerce_admin_order_item_headers', array( $this, 'admin_order_items_headers' ) );
			add_action( 'woocommerce_admin_order_item_values', array( $this, 'admin_order_item_values' ), 19, 3 );

			add_action( 'add_meta_boxes', array( $this, 'add_admin_order_pdf_meta_box' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_commerce_metaboxes' ) );
			add_action( 'save_post_product', array( $this, 'save_commerce_metaboxes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_metabox_scripts' ) ); 

			// check for tickets folder permissions
			$uploads = wp_get_upload_dir();
			$ticketDir = $uploads['basedir']."/tickets";
			if ( !wp_mkdir_p( $ticketDir ) ) {
				add_action( 'admin_notices', array( $this, 'permission_notice' ) );
			}
		}

		function woo_custom_redirect_after_purchase() {

			if ( class_exists( 'WooCommerce' ) ){
				global $wp;
				if ( is_checkout() && !empty( $wp->query_vars['order-received'] ) ) {


					$page = get_page_by_path( 'eventcheckout' );

					if( isset( $page ) && !is_null( $page ) ) {
						if( !is_page( 'eventcheckout' ) && !is_admin() /* && $check_date stuff */ ) {
							wp_redirect( get_permalink( $page->ID ), 302 ); exit;   
						}
					}
				}

				if( is_page( 'eventcheckout' ) ) {
					require_once MYTICKET_PATH . 'event-woocommerce/thankyou.php'; exit;
				}
			}
		}

		function permission_notice(){
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php echo  __( 'Please make sure <strong>../wp-content/uploads/tickets</strong> folder exists and has writing permissions. Required to create and store PDF tickets.', 'myticket-events' ); ?></p>
			</div>
			<?php
		}

		function show_account_invoice_button( $actions, $order ) {

			$action = 'create';
			$url = wp_nonce_url( add_query_arg( array(
				'post' => $order->get_id(),
				'action' => 'edit',
				'myticket_action' => $action,
			), admin_url( 'post.php' ) ), $action, 'nonce' );

			$url = apply_filters( 'myticket_pdf_invoice_url', $url, $order_id, $action );
	
			$actions['name'] = array(
				'url'  => $url,
				'name' => __( 'PDF', 'myticket-events' ),
			);
			return $actions;
		}

	function add_admin_order_pdf_meta_box() {
		// Support both traditional shop_order post type and HPOS
		$screen_ids = array( 'shop_order' );
		
		// Check if HPOS is enabled
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$screen_ids[] = wc_get_page_screen_id( 'shop-order' );
		}
		
		foreach ( $screen_ids as $screen ) {
			add_meta_box( 'order_page_myticket_meta', __( 'PDF Ticket', 'myticket-events' ), array(
				$this,
				'display_order_page_pdf_invoice_meta_box',
			), $screen, 'side', 'high' );
		}
	}

	public function display_order_page_pdf_invoice_meta_box( $post ) {

		// Support both traditional posts and HPOS orders
		$order_id = ( $post instanceof \WC_Order ) ? $post->get_id() : $post->ID;
		
		$this->show_invoice_button( __( 'View', 'myticket-events' ), $order_id, 'create', array( 'class="button grant_access order-page invoice"' ) );
		return;
	}

		// is displayed in right metabox under woocommerce orders
		private function show_invoice_button( $title, $order_id, $action, $attributes = array() ) {

			$url = wp_nonce_url( add_query_arg( array(
				'post' => $order_id,
				'action' => 'edit',
				'myticket_action' => $action,
			), admin_url( 'post.php' ) ), $action.$order_id, 'nonce' );

			$url = apply_filters( 'myticket_pdf_invoice_url', $url, $order_id, $action );

			printf( '<a href="%1$s" title="%2$s" %3$s>%4$s</a>', esc_url($url), esc_attr($title), join( ' ', $attributes ), esc_html($title) );
		}

		// is displayed in a separate column in orders item meta table under woocommerce orders 
		private function show_invoice_button_individual( $title, $order_id, $order_item_id, $action, $attributes = array() ) {

			$url = wp_nonce_url( add_query_arg( array(
				'post' => $order_id,
				'item_id' => $order_item_id,
				'action' => 'edit',
				'myticket_action' => $action,
			), admin_url( 'post.php' ) ), $action.$order_id.$order_item_id, 'nonce' );

			$url = apply_filters( 'myticket_pdf_invoice_url', $url, $order_id, $action );

			printf( '<a href="%1$s" title="%2$s" %3$s>%4$s</a>', esc_url($url), esc_attr($title), join( ' ',$attributes ), esc_html($title) ); //
		}

		public function frontend_pdf_callback() {

			if ( ! self::is_pdf_request() ) {
				return;
			}
		
			// verify nonce.
			$action = sanitize_key( $_GET['myticket_action'] );
			if ( 'create' !== $action && 'create_single' !== $action ) {
				return;
			}
		
			$nonce = sanitize_key( $_GET['nonce'] );
			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				// wp_die( 'Invalid request.' );
			}

			// verify woocommerce order.
			$post_id = intval( sanitize_key( $_GET['post'] ) );
			$order = wc_get_order( $post_id );
			if ( ! $order ) {
				wp_die( 'Order not found.' );
			}

			// Get the Order meta data in an unprotected array
			$data  = $order->get_data();

			// Get the Customer ID (User ID)
			$customer_id     = $data['customer_id'];

			if ( $customer_id !==  get_current_user_id() ) {
				wp_die( 'You are not authorized to view this page.' );
			}

			$order_id = intval( sanitize_key( $_GET['post'] ) );
		
			// execute invoice action.
			switch ( $action ) {

				//TODO display cached tickets
				case 'view':
					
					break;
				case 'cancel':
					
					break;
				case 'create':
					self::generate_general_ticket( $order_id, false );
					die;
					break;
				case 'create_single':
					$item_id = intval( $_GET['item_id'] );
					self::generate_general_ticket_single( $order_id, $item_id, false );
					die;
					break;
			}
		}

		/**
		 * Initialize non-admin.
		 */
		private function frontend_init_hooks() {

			add_action( 'init', array( $this, 'frontend_pdf_callback' ) );
			// TODO myacount download button
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'show_account_invoice_button' ), 10, 2 );
			add_action( 'template_redirect', array( $this, 'woo_custom_redirect_after_purchase' ) );
		}
		
		private function init_hooks() {

			if ( is_admin() ) {
				$this->admin_init_hooks();
			} else {
				$this->frontend_init_hooks();
			}

			// generate individual emails if many participants allowed
			if( '1' == get_theme_mod('myticket_participants', '0') ){

				if ( '1' == get_theme_mod('myticket_email_1', '0')  )
					add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_qrcode_email' ), 10, 3 );

				if ( '1' == get_theme_mod('myticket_email_2', '0') )
					add_action( 'woocommerce_order_status_completed', array( $this, 'process_qrcode_email' ), 10, 1 );

				if ( '1' == get_theme_mod('myticket_email_3', '0') )
					add_action( 'woocommerce_payment_complete', array( $this, 'process_qrcode_email' ) );

			}

			// attach general ticket to woocommerce emails
			if( '1' == get_theme_mod('myticket_email_0', '0') ){

				add_filter( 'woocommerce_email_attachments', array( $this, 'attach_tickets_to_email' ), 99, 3 );
			}

			add_shortcode( 'myticket-download-invoice', array( $this, 'download_invoice_shortcode' ) );
			add_shortcode( 'myticket-download-invoice-multi', array( $this, 'download_invoice_shortcode_multi' ) );
		}

		//shortcode for general ticket
		public function download_invoice_shortcode( $atts ) {

			if ( ! isset( $atts['order_id'] ) || 0 === intval( $atts['order_id'] ) ) {
				return;
			}

			$order_id = $atts['order_id'];
			$action = 'create';

			$url = add_query_arg( array(
				'action' => 'edit',
				'post' => $order_id,
				'myticket_action' => $action,
				'nonce' => wp_create_nonce( 'edit' ),
			) );

			$url = apply_filters( 'myticket_pdf_invoice_url', $url, $order_id, $action );

			printf( '<a target="_blank" href="%1$s">%2$s</a>', esc_attr( $url ), esc_html( $atts['title'] ) );
		}

		//shortcode for individual ticket
		public function download_invoice_shortcode_multi( $atts ) {

			if ( ! isset( $atts['order_id'] ) || 0 === intval( $atts['order_id'] ) ) {
				return;
			}

			if ( ! isset( $atts['item_id'] ) || 0 === intval( $atts['item_id'] ) ) {
				return;
			}

			if ( ! isset( $atts['ticket_id'] ) || 0 === intval( $atts['ticket_id'] ) ) {
				return;
			}

			$order_id = $atts['order_id'];
			$action = 'create_single';

			$url = add_query_arg( array(
				'action' => 'edit',
				'ticket_id' => $atts['ticket_id'],
				'item_id' => $atts['item_id'],
				'post' => $order_id,
				'myticket_action' => $action,
				'nonce' => wp_create_nonce( 'edit' ),
			) );

			$url = apply_filters( 'myticket_pdf_invoice_url', $url, $order_id, $action );

			printf( '<a target="_blank" href="%1$s">%2$s</a>', esc_attr( $url ), esc_html( $atts['title'] ) );
		}

		public function attach_tickets_to_email( $attachments, $status, $order ) {

			// only attach to emails with WC_Order object.
			if ( ! $order instanceof WC_Order ) {
				return $attachments;
			}

			$order_id = $order->get_id();
			$ticketPath = self::generate_general_ticket( $order_id, true );
			$attachments[] = $ticketPath;
				
			return $attachments;
		}

		// send out individual emails to participants
	public function process_qrcode_email( $order_id ){
	
		// load email template with proper hierarchy: child theme -> parent theme -> plugin
		$child_template_path = get_stylesheet_directory() . "/" . MYTICKET_SLUG . "/email-individual/index.php";
		$parent_template_path = get_template_directory() . "/" . MYTICKET_SLUG . "/email-individual/index.php";
		$plugin_template_path = MYTICKET_PATH . 'templates/email-individual/index.php';
		
		if ( file_exists($child_template_path) ){
			return include $child_template_path;
		} elseif ( file_exists($parent_template_path) ){
			return include $parent_template_path;
		} else {
			return include $plugin_template_path;
		}
	}

		private static function is_pdf_request() {
			return ( isset( $_GET['post'] ) && isset( $_GET['myticket_action'] ) && isset( $_GET['nonce'] ) );
		}

		/**
		 * Process admin get requests. EX.: Create, View invoices 
		 */
		public function admin_pdf_callback() {

			if ( ! self::is_pdf_request() ) {
				return;
			}

			// pdf request type
			$action = sanitize_key( $_GET['myticket_action'] );

			// nonce
			$nonce = sanitize_key( $_GET['nonce'] );

			// order id
			$order_id = intval( sanitize_key( $_GET['post'] ) );

			// execute invoice action.
			switch ( $action ) {

				// TODO display cached tickets
				case 'view':
					
					break;
				case 'cancel':
					
					break;
				case 'create':

					// verify nonce
					if ( ! wp_verify_nonce( $nonce, $action.$order_id ) ) {
						wp_die( 'Invalid request.' );
					}

					self::generate_general_ticket( $order_id, false );
					die;
					break;
				case 'create_single':

					// order item id
					$item_id = intval( $_GET['item_id'] );

					// verify nonce
					if ( ! wp_verify_nonce( $nonce, $action.$order_id.$item_id ) ) {
						wp_die( 'Invalid request.' );
					}
	
					self::generate_general_ticket_single( $order_id, $item_id, false );
					die;
					break;
			}
		}

	public static function generate_general_ticket( $order_id, $to_file ){

		// Initialize $print variable for the template
		$print = false;

		// load ticket template with proper hierarchy: child theme -> parent theme -> plugin
		$child_template_path = get_stylesheet_directory() . "/" . MYTICKET_SLUG . "/ticket-general/index.php";
		$parent_template_path = get_template_directory() . "/" . MYTICKET_SLUG . "/ticket-general/index.php";
		$plugin_template_path = MYTICKET_PATH . 'templates/ticket-general/index.php';
		
		if ( file_exists($child_template_path) ){
			return include $child_template_path;
		} elseif ( file_exists($parent_template_path) ){
			return include $parent_template_path;
		} else {
			return include $plugin_template_path;
		}
	}

	public static function generate_general_ticket_single( $order_id, $order_item_id, $to_file ){

		// Initialize $print variable for the template
		$print = false;

		// load ticket template with proper hierarchy: child theme -> parent theme -> plugin
		$child_template_path = get_stylesheet_directory() . "/" . MYTICKET_SLUG . "/ticket-individual/index.php";
		$parent_template_path = get_template_directory() . "/" . MYTICKET_SLUG . "/ticket-individual/index.php";
		$plugin_template_path = MYTICKET_PATH . 'templates/ticket-individual/index.php';
		
		if ( file_exists($child_template_path) ){
			return include $child_template_path;
		} elseif ( file_exists($parent_template_path) ){
			return include $parent_template_path;
		} else {
			return include $plugin_template_path;
		}
	}

		/**
		 * Enqueue scripts and styles for metabox
		 */
		public function enqueue_metabox_scripts( $hook ) {
			global $post_type;
			
			if ( 'product' !== $post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
				return;
			}
			
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-ui-timepicker', 'https://cdn.jsdelivr.net/npm/jquery-ui-timepicker-addon@1.6.3/dist/jquery-ui-timepicker-addon.min.js', array( 'jquery-ui-datepicker' ), '1.6.3', true );
			wp_enqueue_style( 'jquery-ui-timepicker', 'https://cdn.jsdelivr.net/npm/jquery-ui-timepicker-addon@1.6.3/dist/jquery-ui-timepicker-addon.min.css', array(), '1.6.3' );
			wp_enqueue_style( 'jquery-ui-core', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css', array(), '1.12.1' );
			
			wp_add_inline_script( 'jquery-ui-timepicker', '
				jQuery(document).ready(function($) {
					$("#myticket_datetime_start, #myticket_datetime_end").datetimepicker({
						dateFormat: "yy-mm-dd",
						timeFormat: "HH:mm",
						separator: " ",
						showTimezone: false
					});
				});
			' );
		}

		/**
		 * Create myticket commerce specific meta box
		 */
		public function add_commerce_metaboxes() {
			add_meta_box(
				'product_metabox',
				esc_html__( 'MyTicket Extra Details', 'myticket-events' ),
				array( $this, 'render_commerce_metabox' ),
				'product',
				'normal',
				'high'
			);
		}

		/**
		 * Render metabox content
		 */
		public function render_commerce_metabox( $post ) {
			wp_nonce_field( 'myticket_metabox_nonce', 'myticket_metabox_nonce' );
			
			$datetime_start = get_post_meta( $post->ID, 'myticket_datetime_start', true );
			$datetime_end = get_post_meta( $post->ID, 'myticket_datetime_end', true );
			$event_length = get_post_meta( $post->ID, 'myticket_event_length', true );
			$title = get_post_meta( $post->ID, 'myticket_title', true );
			$address = get_post_meta( $post->ID, 'myticket_address', true );
			$coordinates = get_post_meta( $post->ID, 'myticket_coordinates', true );
			$link = get_post_meta( $post->ID, 'myticket_link', true );
			
			// Format datetime for display
			$datetime_start_display = $datetime_start ? date( 'Y-m-d H:i', $datetime_start ) : '';
			$datetime_end_display = $datetime_end ? date( 'Y-m-d H:i', $datetime_end ) : '';
			
			?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="myticket_datetime_start"><?php esc_html_e( 'Event Begins', 'myticket-events' ); ?></label>
					</th>
					<td>
						<input type="text" id="myticket_datetime_start" name="myticket_datetime_start" value="<?php echo esc_attr( $datetime_start_display ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Select event date time and zone.', 'myticket-events' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="myticket_datetime_end"><?php esc_html_e( 'Event Ends', 'myticket-events' ); ?></label>
					</th>
					<td>
						<input type="text" id="myticket_datetime_end" name="myticket_datetime_end" value="<?php echo esc_attr( $datetime_end_display ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Select event date time and zone.', 'myticket-events' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="myticket_event_length"><?php esc_html_e( 'Event Length', 'myticket-events' ); ?></label>
					</th>
					<td>
						<select id="myticket_event_length" name="myticket_event_length">
							<option value=""><?php esc_html_e( 'Undefined', 'myticket-events' ); ?></option>
							<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
								<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $event_length, $i ); ?>>
									<?php printf( esc_html__( '%d Day', 'myticket-events' ), $i ); ?><?php if ( $i > 1 ) echo 's'; ?>
								</option>
							<?php endfor; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Allow users to pick specific attendance day in cart calendar. Go to Customizer > MyTicket > Checkout > Enable Calendar', 'myticket-events' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="myticket_title"><?php esc_html_e( 'Location Title', 'myticket-events' ); ?></label>
					</th>
					<td>
						<input type="text" id="myticket_title" name="myticket_title" value="<?php echo esc_attr( $title ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Location title/Venue is used for visual representation only.', 'myticket-events' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="myticket_address"><?php esc_html_e( 'Location Address', 'myticket-events' ); ?></label>
					</th>
					<td>
						<input type="text" id="myticket_address" name="myticket_address" value="<?php echo esc_attr( $address ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Location address is used for visual representation. Map searches are only performed based on location coordinates that can be provided below.', 'myticket-events' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="myticket_coordinates"><?php esc_html_e( 'Location Coordinates', 'myticket-events' ); ?></label>
					</th>
					<td>
						<input type="text" id="myticket_coordinates" name="myticket_coordinates" value="<?php echo esc_attr( $coordinates ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Location latitude and longitude separated by comma. Ex.: 124.34343, -23.3423.', 'myticket-events' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="myticket_link"><?php esc_html_e( 'Custom Link', 'myticket-events' ); ?></label>
					</th>
					<td>
						<input type="url" id="myticket_link" name="myticket_link" value="<?php echo esc_url( $link ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Override default woocommerce product permalink.', 'myticket-events' ); ?></p>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Save metabox data
		 */
		public function save_commerce_metaboxes( $post_id ) {
			// Check nonce
			if ( ! isset( $_POST['myticket_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['myticket_metabox_nonce'], 'myticket_metabox_nonce' ) ) {
				return;
			}
			
			// Check autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			
			// Check permissions
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			
			// Check post type
			if ( 'product' !== get_post_type( $post_id ) ) {
				return;
			}
			
			// Save datetime fields (convert to timestamp)
			if ( isset( $_POST['myticket_datetime_start'] ) && ! empty( $_POST['myticket_datetime_start'] ) ) {
				$datetime_start = strtotime( sanitize_text_field( $_POST['myticket_datetime_start'] ) );
				update_post_meta( $post_id, 'myticket_datetime_start', $datetime_start );
			} else {
				delete_post_meta( $post_id, 'myticket_datetime_start' );
			}
			
			if ( isset( $_POST['myticket_datetime_end'] ) && ! empty( $_POST['myticket_datetime_end'] ) ) {
				$datetime_end = strtotime( sanitize_text_field( $_POST['myticket_datetime_end'] ) );
				update_post_meta( $post_id, 'myticket_datetime_end', $datetime_end );
			} else {
				delete_post_meta( $post_id, 'myticket_datetime_end' );
			}
			
			// Save other fields
			if ( isset( $_POST['myticket_event_length'] ) ) {
				update_post_meta( $post_id, 'myticket_event_length', sanitize_text_field( $_POST['myticket_event_length'] ) );
			} else {
				delete_post_meta( $post_id, 'myticket_event_length' );
			}
			
			if ( isset( $_POST['myticket_title'] ) ) {
				update_post_meta( $post_id, 'myticket_title', sanitize_text_field( $_POST['myticket_title'] ) );
			} else {
				delete_post_meta( $post_id, 'myticket_title' );
			}
			
			if ( isset( $_POST['myticket_address'] ) ) {
				update_post_meta( $post_id, 'myticket_address', sanitize_text_field( $_POST['myticket_address'] ) );
			} else {
				delete_post_meta( $post_id, 'myticket_address' );
			}
			
			if ( isset( $_POST['myticket_coordinates'] ) ) {
				update_post_meta( $post_id, 'myticket_coordinates', sanitize_text_field( $_POST['myticket_coordinates'] ) );
			} else {
				delete_post_meta( $post_id, 'myticket_coordinates' );
			}
			
			if ( isset( $_POST['myticket_link'] ) ) {
				update_post_meta( $post_id, 'myticket_link', esc_url_raw( $_POST['myticket_link'] ) );
			} else {
				delete_post_meta( $post_id, 'myticket_link' );
			}
		}
	}

	new MyTicket_Events;
} ?>