<?php
/*
 * Main Class to handle preview emails*/
if ( ! class_exists( 'aks_WooCommercePreviewEmails' ) ):
	class aks_WooCommercePreviewEmails {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;
		private $plugin_url, $choose_email, $orderID, $recipient;
		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public $emails = null, $notice_message = null, $notice_class = null;

		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			$this->plugin_url = plugins_url( '', WOO_PREVIEW_EMAILS_FILE );
			add_action( 'init', array( $this, 'load' ), 999 );
			//add_action( 'admin_init', array( $this, 'generate_result' ), 20 );
			add_action( 'admin_menu', array( $this, 'menu_page' ), 90 );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ), 10, 1 );
			add_action( 'wp_ajax_woo_preview_orders_search', array( $this, 'woo_preview_orders_search' ) );
			add_action( 'wp_ajax_previewemail', array( $this, 'wordimpress_preview_woo_emails' ) );
			add_filter('woocommerce_email_settings', array( $this, 'add_preview_email_links'));
		}

		
		/*Ajax Callback to Search Orders*/
		public function woo_preview_orders_search() {

			$q = filter_input( INPUT_GET, 'q' );

			$args     = array(
				'post_type'      => 'shop_order',
				'posts_per_page' => 20,
				'post_status'    => array_keys( wc_get_order_statuses() ),
				'post__in'       => array( $q )
			);
			$response = array();
			$orders   = new WP_Query( $args );

			while ( $orders->have_posts() ):
				$orders->the_post();
				$id         = get_the_id();
				$response[] = array( 'id' => $id, 'text' => '#order :' . $id );
			endwhile;

			wp_reset_postdata();

			wp_send_json( $response );
		}




		/*Ajax Callback to email preview */
		function wordimpress_preview_woo_emails()
		{
			if (is_admin()) {
		
				$files   = scandir(get_stylesheet_directory() . '/woocommerce/emails');
				$afiles  = scandir(WP_PLUGIN_DIR . '/woocommerce/templates/emails');
				if ($files) {
					$files    =    array_merge($files, $afiles);
				} else {
					$files    =    $afiles;
				}
				$exclude = array(
					'.',
					'..',
					'email-header.php',
					'email-footer.php',
					'email-styles.php',
					'email-order-items.php',
					'email-addresses.php',
					'email-customer-details.php',
					'woo-preview-emails.php',
					'plain'
				);
				$list    = array_diff($files, $exclude);
		
				if ($list) {
							$woocommerce_orders = new WP_Query(array(
								'post_type' => 'shop_order',
								'posts_per_page' => -1,
								'order' => 'ASC',
								'post_status' => array('wc-completed', 'wc-processing')
							));
				
							$order_drop_down_array = array();
							$order_drop_down_array_type = array();
							if ($woocommerce_orders->have_posts()) {
								while ($woocommerce_orders->have_posts()) {
									$woocommerce_orders->the_post();
									$order_drop_down_array[get_the_ID()] = '#' . get_the_ID() . ' - ' . wc_get_order(get_the_ID())->get_order_number() . ' - ' . wc_get_order(get_the_ID())->get_status();
									if (!in_array(wc_get_order(get_the_ID())->get_status(), $order_drop_down_array_type)) {
										$order_drop_down_array_type[] = wc_get_order(get_the_ID())->get_status();
									}
								}
							}
		
		
							?>
							<!-- If you delete the viewport meta tag, the ground will open and swallow you. -->
							<meta name="viewport" content="width=device-width" />
				
							<style>
								@import url(http://fonts.googleapis.com/css?family=Lato:400,900);
				
								<?php
									/*  This is normally not needed because 
									*  WooCommerce inserts it into your templates
									*  automatically. It's here so the styles
									*  get applied to the preview correctly.
									*/
				
									wc_get_template('emails/email-styles.php');
				
									/* Custom styles can be added here
									* NOTE: Don't add inline comments in your styles, 
									* they will break the template.
									*/
									$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
									if (strpos($url, 'admin-ajax.php') !== false) {
									?>#template_container {
										max-width: 640px;
									}
				
									#template-selector form,
									#template-selector a.logo,
									#template-selector .template-row,
									#template-selector .order-row {
										display: block;
										margin: 0.75em 0;
									}
				
									#template-selector {
										background: #333;
										color: white;
										text-align: center;
										padding: 0 2rem 1rem 2rem;
										font-family: 'Lato', sans-serif;
										font-weight: 400;
										border: 4px solid #5D5D5D;
										border-width: 0 0 4px 0;
									}
				
									#template-selector a.logo {
										display: inline-block;
										position: relative;
										top: 1.5em;
										margin: 1em 0 2em;
									}
				
									#template-selector a.logo img {
										max-height: 5em;
									}
				
									#template-selector a.logo p {
										display: none;
										float: left;
										position: absolute;
										width: 16em;
										top: 4.5em;
										padding: 2em;
										left: -8em;
										background: white;
										opacity: 0;
										border: 2px solid #777;
										border-radius: 4px;
										font-size: 0.9em;
										line-height: 1.8;
										transition: all 500ms ease-in-out;
									}
				
									#template-selector a.logo:hover p {
										display: block;
										opacity: 1;
									}
				
									#template-selector a.logo p:after,
									#template-selector a.logo p:before {
										bottom: 100%;
										left: 50%;
										border: solid transparent;
										content: " ";
										height: 0;
										width: 0;
										position: absolute;
										pointer-events: none;
									}
				
									#template-selector a.logo p:after {
										border-color: rgba(255, 255, 255, 0);
										border-bottom-color: #ffffff;
										border-width: 8px;
										margin-left: -8px;
									}
				
									#template-selector a.logo p:before {
										border-color: rgba(119, 119, 119, 0);
										border-bottom-color: #777;
										border-width: 9px;
										margin-left: -9px;
									}
				
									#template-selector a.logo:hover p {
										display: block;
									}
				
									#template-selector span {
										font-weight: 900;
										display: inline-block;
										margin: 0 1rem;
									}
				
									#template-selector select,
									#template-selector input {
										background: #e3e3e3;
										font-family: 'Lato', sans-serif;
										color: #333;
										padding: 0.5rem 1rem;
										border: 0px;
									}
				
									#template-selector #order,
									#template-selector .choose-order {
										display: none;
									}
				
									@media screen and (min-width: 1100px) {
				
										#template-selector .template-row,
										#template-selector .order-row {
											display: inline-block;
										}
				
										#template-selector form {
											display: inline-block;
											line-height: 3;
										}
				
										#template-selector a.logo p {
											width: 16em;
											top: 4.5em;
											left: 0.25em;
										}
				
										#template-selector a.logo p:after,
										#template-selector a.logo p:before {
											left: 10%;
										}
									}
				
									<?php } ?>
							</style>
				
							<div id="template-selector">
								<form method="get" action="<?php echo site_url(); ?>/wp-admin/admin-ajax.php">
									<div class="template-row">
										<input id="setorder" type="hidden" name="order" value="">
										<input type="hidden" name="action" value="previewemail">
										<span class="choose-email">Choose your email template: </span>
										<select name="file" id="email-select">
											<?php
											foreach ($list as $item) { ?>
												<option value="<?php echo $item; ?>"><?php echo str_replace('.php', '', $item); ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="order-row">
										<span class="choose-order-type">Status: </span>
										<select id="order-type" onchange="process1(this)" name="order-type">
											<?php foreach ($order_drop_down_array_type as $order_statuses) { ?>
												<option value="<?php echo $order_statuses; ?>" <?php selected(((isset($_GET['order-type'])) ? $_GET['order-type'] : key($order_drop_down_array_type)), $order_statuses); ?>><?php echo $order_statuses; ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="order-row">
										<span class="choose-order">order: </span>
										<select id="order" onchange="process1(this)" name="order">
											<?php foreach ($order_drop_down_array as $order_id => $order_name) { ?>
												<option value="<?php echo $order_id; ?>" <?php selected(((isset($_GET['order'])) ? $_GET['order'] : key($order_drop_down_array)), $order_id); ?>><?php echo $order_name; ?></option>
											<?php } ?>
										</select>
									</div>
									<input type="submit" value="Go">
								</form>
							</div>
							<?php
		
							global $order, $billing_email;
				
							reset($order_drop_down_array);
				
							$order_number = isset($_GET['order']) ? $_GET['order'] : key($order_drop_down_array);
				
							$order = new WC_Order($order_number);
				
							$emails = new WC_Emails();
				
							$user_id = (int) $order->post->post_author;
				
							$user_details = get_user_by('id', $user_id);
		
							// Load the email header on files that don't include it
							// if (in_array($_GET['file'], array('email-customer-details.php', 'email-order-details.php'))) {
							// 	wc_get_template('emails/email-header.php', array(
							// 		'order' => $order,
							// 		'email_heading' => $email_heading
							// 	));
							// }
		
							do_action('woocommerce_email_before_order_table', $order, false, false);
			
							wc_get_template('emails/' . $_GET['file'], array(
								'order' => $order,
								'email_heading' => '',
								'sent_to_admin' => false,
								'plain_text' => false,
								'email' => $user_details->user_email,
								'user_login' => $user_details->user_login,
								'blogname' => get_bloginfo('name'),
								'customer_note' => $order->customer_note,
								'partial_refund' => ''
							));
		
		
							/* This makes sure the JS is
							* only loaded on the preview page
							* don't remove it.
							*/
							// Load colours
							$base = get_option('woocommerce_email_base_color');
				
							$base_lighter_40 = wc_hex_lighter($base, 40);
			
							// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline.
							$template_footer = "border-top:0;-webkit-border-radius:6px;";
			
							$credit = "border:0;color: $base_lighter_40;font-family: Arial;	font-size:12px;line-height:125%;	text-align:center;	";
							$url = "http" . ($_SERVER['SERVER_PORT'] == 443 ? "s" : "") . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
							if (strpos($url, 'admin-ajax.php') !== false) {
										?>
										<!-- We need jQuery for some of the preview functionality -->
										<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
										<script language="javascript">
											//This sets the order value for the query string
											function process1(showed) {
												document.getElementById("setorder").value = showed.value;
												jQuery("#ordernum").attr("value", getQueryVariable("order"));
											}
											// This shows the order field
											// conditionally based on the select field
											jQuery(document).ready(function($) {
												$("#email-select").change(function() {
													$("#email-select option:selected").each(function() {
														// if(($(this).attr("value")=="customer-completed-order.php") || ($(this).attr("value")=="admin-cancelled-order.php") || ($(this).attr("value")=="admin-new-order.php") ||($(this).attr("value")=="customer-completed-order") || ($(this).attr("value")=="customer-invoice.php")){
														$("#order").show()
														$(".choose-order").show();
														// } else {
														// 	$("#order").hide()
														// 	$(".choose-order").hide();
														// }
						
													});
												}).change();
											});
						
											//This gets the info from the query string
											function getUrlVars() {
												var vars = [],
													hash;
												var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
												for (var i = 0; i < hashes.length; i++) {
													hash = hashes[i].split(' = ');
													vars.push(hash[0]);
													vars[hash[0]] = hash[1];
												}
												return vars;
						
											}
											var order = getUrlVars()["order"];
											var file = getUrlVars()["file"];
						
											// This populates the fields 
											// from the data in the query string
											jQuery('form input#order').val(decodeURI(order));
											jQuery('select#email-select').val(decodeURI(file));
										</script>
							<?php }
		
						// Everything below here will be output into the email directly
		
				}
			}
			wp_die();
		}
		


		/**
		 * load woo preview scripts
		 *
		 * @param  [type] $hook [admin page suffix]
		 */
		public function load_scripts( $hook ) {

			// if ( $hook != 'woocommerce_page_aks-woocommerce-preview-emails' ) {
			// 	return;
			// }
			// wp_register_style( 'woo-preview-email-select2-css', $this->plugin_url . '/assets/css/select2.min.css' );
			// wp_register_script( 'woo-preview-email-select2-js', $this->plugin_url . '/assets/js/select2.min.js', array( 'jquery' ), '', true );

			// wp_enqueue_style( 'woo-preview-email-select2-css' );
			// wp_enqueue_script( 'woo-preview-email-select2-js' );
		}

		public function load() {

			$page = filter_input( INPUT_GET, 'page' );

			if ( class_exists( 'WC_Emails' ) && $page == 'aks-woocommerce-preview-emails' ) {

				$wc_emails = WC_Emails::instance();
				$emails    = $wc_emails->get_emails();
				if ( ! empty( $emails ) ) {
					//Filtering out booking emails becuase it won't work from this plugin
					//Buy PRO version if you need this capability
					$unset_booking_emails = array(
						'WC_Email_New_Booking',
						'WC_Email_Booking_Reminder',
						'WC_Email_Booking_Confirmed',
						'WC_Email_Booking_Notification',
						'WC_Email_Booking_Cancelled',
						'WC_Email_Admin_Booking_Cancelled',
						'WC_Email_Booking_Pending_Confirmation'
					);

					//Filtering out subscription emails becuase it won't work from this plugin
					//Buy PRO version if you need this capability
					$unset_subscription_emails = array(
						'WCS_Email_New_Renewal_Order',
						'WCS_Email_New_Switch_Order',
						'WCS_Email_Processing_Renewal_Order',
						'WCS_Email_Completed_Renewal_Order',
						'WCS_Email_Completed_Switch_Order',
						'WCS_Email_Customer_Renewal_Invoice',
						'WCS_Email_Cancelled_Subscription',
						'WCS_Email_Expired_Subscription',
						'WCS_Email_On_Hold_Subscription'
					);

					//Filtering out membership emails becuase it won't work from this plugin
					//Buy PRO version if you need this capability
					$unset_membership_emails = array(
						'WC_Memberships_User_Membership_Note_Email',
						'WC_Memberships_User_Membership_Ending_Soon_Email',
						'WC_Memberships_User_Membership_Ended_Email',
						'WC_Memberships_User_Membership_Renewal_Reminder_Email',
					);

					$unset_booking_emails      = apply_filters( 'woo_preview_emails_unset_booking_emails', $unset_booking_emails );
					$unset_subscription_emails = apply_filters( 'woo_preview_emails_unset_subscription_emails', $unset_subscription_emails );
					$unset_membership_emails   = apply_filters( 'woo_preview_emails_unset_memebership_emails', $unset_membership_emails );

					if ( ! empty( $unset_booking_emails ) ) {
						foreach ( $unset_booking_emails as $unset_booking_email ) {
							if ( isset( $emails[ $unset_booking_email ] ) ) {
								unset( $emails[ $unset_booking_email ] );
							}
						}
					}

					if ( ! empty( $unset_subscription_emails ) ) {
						foreach ( $unset_subscription_emails as $unset_subscription_email ) {
							if ( isset( $emails[ $unset_subscription_email ] ) ) {
								unset( $emails[ $unset_subscription_email ] );
							}
						}
					}

					if ( ! empty( $unset_membership_emails ) ) {
						foreach ( $unset_membership_emails as $unset_membership_email ) {
							if ( isset( $emails[ $unset_membership_email ] ) ) {
								unset( $emails[ $unset_membership_email ] );
							}
						}
					}

					$this->emails = $emails;
				}
			}

		}

		public function adminNotices() {
			echo "<div class=\"$this->notice_class\"><p>$this->notice_message</p></div>";
		}

		public function menu_page() {
			//moved into submenu
			add_submenu_page( 'woocommerce', 'WooCommerce Preview Emails', __( 'aks - Preview Emails', 'woo-preview-emails' ), apply_filters( 'woo_preview_emails_min_capability', 'manage_options' ), 'aks-woocommerce-preview-emails', array( $this, 'generate_page' ) );
		}


		/*
		*    Extend WC_Email_Setting
		*    in order to add our own
		*    links to the preview
		*/

		function add_preview_email_links($settings)
		{
			$updated_settings = array();
			foreach ($settings as $section) {
				// at the bottom of the General Options section

				if (
					isset($section['id']) && 'email_recipient_options' == $section['id'] &&

					isset($section['type']) && 'sectionend' == $section['type']
				) {
					$updated_settings[] = array(
						'title' => __('Preview Email Templates', 'previewemail'),
						'type'  => 'title',
						'desc'  => __('<a href="' . site_url() . '/wp-admin/admin-ajax.php?action=previewemail&file=customer-new-account.php" target="_blank">Click Here to preview all of your Email Templates with Orders</a>.', 'previewemail'),
						'id'    => 'email_preview_links'
					);
				}
				$updated_settings[] = $section;
			}

			return $updated_settings;
		}

		public function generate_page() {
			?>
            <div class="wrap">
                <h2>Woo Preview Emails</h2>
				<?php $this->generate_form(); ?>
            </div>
			<?php
		}

		public function generate_form() {
			$this->choose_email = isset( $_POST['choose_email'] ) ? $_POST['choose_email'] : '';
			$this->orderID      = isset( $_POST['orderID'] ) ? $_POST['orderID'] : '';
			$recipient_email    = isset( $_POST['email'] ) ? $_POST['email'] : '';

	
			if ( is_admin()  ) {
				require_once WOO_PREVIEW_EMAILS_DIR . '/views/form.php';
			} 
		}

		public function generate_result() {

			if ( is_admin() && isset( $_POST['preview_email'] ) && wp_verify_nonce( $_POST['preview_email'], 'woocommerce_preview_email' ) ):
				$condition = false;
			    WC()->payment_gateways();
				WC()->shipping();
				if ( isset( $_POST['choose_email'] ) && ( $_POST['choose_email'] == 'WC_Email_Customer_New_Account' || $_POST['choose_email'] == 'WC_Email_Customer_Reset_Password' ) ) {
					$condition = true;
				} elseif ( ( ( isset( $_POST['orderID'] ) && ! empty( $_POST['orderID'] ) ) || ( isset( $_POST['search_order'] ) && ! empty( $_POST['search_order'] ) ) ) && ( isset( $_POST['choose_email'] ) && ! empty( $_POST['choose_email'] ) ) ) {
					$condition = true;
				}

				if ( $condition == true ) {
					$this->plugin_url = plugins_url( '', WOO_PREVIEW_EMAILS_FILE );

					/*Load the styles and scripts*/
					require_once WOO_PREVIEW_EMAILS_DIR . '/views/result/style.php';
					require_once WOO_PREVIEW_EMAILS_DIR . '/views/result/scripts.php';

					/*Make Sure serached order is selected */
					$orderID         = absint( ! empty( $_POST['search_order'] ) ? $_POST['search_order'] : $_POST['orderID'] );
					$index           = esc_attr( $_POST['choose_email'] );
					$recipient_email = $_POST['email'];

					if ( is_email( $recipient_email ) ) {
						$this->recipient = $_POST['email'];
					} else {
						$this->recipient = '';
					}

					$current_email = $this->emails[ $index ];
					/*The Woo Way to Do Things Need Exception Handling Edge Cases*/
					add_filter( 'woocommerce_email_recipient_' . $current_email->id, array( $this, 'no_recipient' ) );

					$additional_data = apply_filters( 'woo_preview_additional_orderID', false, $index, $orderID, $current_email );
					if ( $additional_data ) {
						do_action( 'woo_preview_additional_order_trigger', $current_email, $additional_data );
					} else {
						if ( $index === 'WC_Email_Customer_Note' ) {
							/* customer note needs to be added*/
							$customer_note = 'This is some customer note , just some dummy text nothing to see here';
							$args          = array(
								'order_id'      => $orderID,
								'customer_note' => $customer_note
							);
							$current_email->trigger( $args );

						} else if ( $index === 'WC_Email_Customer_New_Account' ) {
							$user_id = get_current_user_id();
							$current_email->trigger( $user_id );
						} else if ( strpos( $index, 'WCS_Email' ) === 0 && class_exists( 'WC_Subscription' ) && is_subclass_of( $current_email, 'WC_Email' ) ) {
							/* Get the subscriptions for the selected order */
							$order_subscriptions = wcs_get_subscriptions_for_order( $orderID );
							if ( ! empty( $order_subscriptions ) && $current_email->id != 'customer_payment_retry' && $current_email->id != 'payment_retry' ) {
								/* Pick the first one as an example */
								$subscription = array_pop( $order_subscriptions );
								$current_email->trigger( $subscription );

							} else {
								$current_email->trigger( $orderID, wc_get_order( $orderID ) );
							}
						} else {
							$current_email->trigger( $orderID );
						}
					}

					$content = $current_email->get_content_html();
					$content = apply_filters( 'woocommerce_mail_content', $current_email->style_inline( $content ) );
					echo $content;
					/*This ends the content for email to be previewed*/
					/*Loading Toolbar to display for multiple email templates*/

					/*The Woo Way to Do Things Need Exception Handling Edge Cases*/
					remove_filter( 'woocommerce_email_recipient_' . $current_email->id, array( $this, 'no_recipient' ) );
					?>
                    <div id="tool-options">
                        <div id="tool-wrap">
                            <p>
                                <strong>Currently Viewing Template File: </strong><br/>
								<?php echo wc_locate_template( $current_email->template_html ); ?>
                            </p>
                            <p class="description">
                                <strong> Descripton: </strong>
								<?php echo $current_email->description; ?>
                            </p>
							<?php $this->generate_form(); ?>
                            <!-- admin url was broken -->
                            <a class="button" href="<?php echo admin_url( 'admin.php?page=aks-woocommerce-preview-emails' ); ?>"><?php _e( 'Back to Admin Area', 'woo-preview-emails' ); ?></a>
                        </div>
                    </div>
                    <div class="menu-toggle-wrapper">
                        <a href="#" id="show_menu" class="show_menu">Show Menu</a>
                    </div>
					<?php
					die;
				} else {
					$this->notice_message = 'Please specify both Order and Email';
					$this->notice_class   = 'error';
					add_action( 'admin_notices', array( $this, 'adminNotices' ) );
				}
			endif;
		}

		public function no_recipient( $recipient ) {

			if ( $this->recipient != '' ) {
				$recipient = $this->recipient;
			} else {
				$recipient = '';
			}

			return $recipient;
		}
	}

	add_action( 'plugins_loaded', array( 'aks_WooCommercePreviewEmails', 'get_instance' ) );

endif;
