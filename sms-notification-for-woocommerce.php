<?php @error_reporting(E_ERROR | E_WARNING | E_PARSE);
/*
Plugin Name: SMS Notification for WooCommerce
Description: Extension for Woocommerce plugin to send Order SMS Notification.
Version: 1.0
Plugin URI: https://wordpress.org/plugins/sms-notification-for-woocommerce
Author: Repute Infosystems
Author URI: https://profiles.wordpress.org/reputeinfosystems
Text Domain: Repute-SMS
*/


if ( ! defined( "ABSPATH" ) ) exit; // Exit if accessed directly

define("REPUTE_SMS_TEXT_DOMAIN","Repute-SMS");

$plugin_dir_name = dirname(plugin_basename( __FILE__ ));

define("REPUTE_SMS_GATEEWAY_DIR", WP_PLUGIN_DIR."/".$plugin_dir_name);
define("REPUTE_SMS_GATEEWAY_URL", WP_PLUGIN_URL."/".$plugin_dir_name);
define('REPUTE_SMS_IMAGES_URL',REPUTE_SMS_GATEEWAY_URL . '/images');
define('REPUTE_SMS_IMAGES_DIR', REPUTE_SMS_GATEEWAY_DIR . '/images');

global $repute_sms_gateway_plugin_version, $repute_sms_gateway_db_version, $wc_settings_reputesms, $reputesmsid, $reputesmslabel, $smsforwooplnm;

$repute_sms_gateway_plugin_version= "1.0";
$repute_sms_gateway_db_version = "1.0";

if ( ! class_exists( "WC_Repute_SMS_Installer" ) )
{
	class WC_Repute_SMS_Installer {
	
		public static function init() {
		
			register_activation_hook( REPUTE_SMS_GATEEWAY_DIR, array('Repute_SMS', 'repute_sms_install') );
			
		}
	
		public static function repute_sms_payment_gateway_active_check() {
			
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			return is_plugin_active( 'woocommerce/woocommerce.php' );
			
		}
	
	}
}

/**
 * WC Checking Available plugin active Detection
 */
if ( ! function_exists( "is_repute_sms_woocommerce_active" ) ) {
	function is_repute_sms_woocommerce_active() {
		return WC_Repute_SMS_Installer::repute_sms_payment_gateway_active_check();
	}
}

if(is_repute_sms_woocommerce_active()) {
	$wc_settings_reputesms = new WC_Settings_ReputeSMS();
	require_once( REPUTE_SMS_GATEEWAY_DIR.'/core/sms_functions.php' );
}

/**
 *  Define Code Styling Localisation
 */
load_plugin_textdomain( REPUTE_SMS_TEXT_DOMAIN, false, dirname(plugin_basename( __FILE__ ))."/languages/");

class WC_Settings_ReputeSMS 
{
	/**
	 * Constructor.
	*/
	
	var $reputesmsid;
	var $reputesmslabel;
	var $smsforwooplnm;
	
	public function __construct() {
		global $reputesmsid, $reputesmslabel,$smsforwooplnm;
		$this->reputesmsid = "repute_sms";
		$this->smsforwooplnm = "smsforwoo";
		$this->reputesmslabel = __( "SMS Notification", REPUTE_SMS_TEXT_DOMAIN );
		$this->reputesms_log = get_option( $this->reputesmsid ."_debug_logging" );
		$this->request_timeout = 30;
		$this->set_auto_deliver_hours = 24;
		$this->log = "";
		
		$smsforwooplnm = $this->smsforwooplnm;
		$reputesmsid = $this->reputesmsid;
		$reputesmslabel = $this->reputesmslabel;
		
		if(is_repute_sms_woocommerce_active()) {
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_repute_sms_settings_heading' ), 50 );
			
			add_action( 'woocommerce_settings_tabs_'. $this->reputesmsid,  array( &$this, 'repute_sms_settings_tab_output' ) );
			add_action( 'woocommerce_update_options_'. $this->reputesmsid, array( &$this, 'update_settings' ) );
			
			add_action('admin_enqueue_scripts', array( &$this, 'repute_sms_set_js'), 11);
			add_action('admin_enqueue_scripts', array( &$this, 'repute_sms_set_css'), 11);

			/* Add the field to the checkout */
			add_action( 'woocommerce_after_order_notes', array( &$this, 'repute_sms_checkout_fields' ), 10, 1 );
			
			/**
			* Update the order meta with field value
			*/
			add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'repute_sms_checkout_fields_update' ) );
			
			add_action( 'init', array( &$this, 'load_repute_sms_status_actions' ) );
			
			/**
			* Display field value on the order edit page
			*/
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( &$this, 'repute_sms_checkout_field_display_admin_order_meta' ), 10, 1 );
			
			//view sent message function 
			add_action( 'wp_ajax_view_sms_sent_messages', array( &$this, 'get_sms_gateway_sent_messages' ) );
			
			//Check Single Message Status
			add_action( 'wp_ajax_check_sms_current_status', array( &$this, 'check_sms_current_status' ) );
			
			//Send Manual Message Action
			add_action( 'wp_ajax_sms_woo_send_manual_message', array( &$this, 'sms_woo_send_manual_message' ) );
			
			//Send Bulk Message Action
			add_action( 'wp_ajax_sms_woo_send_bulk_message', array( &$this, 'sms_woo_send_bulk_messages' ) );
			
			add_action( 'admin_footer', array( &$this, 'smsforwoo_footer_link' ) );
			
		}
	}
	
	public function repute_sms_install()
	{
		global $wpdb, $repute_sms_gateway_db_version, $repute_sms_gateway_plugin_version;
		
		$reputesmsgatewaydbversioncheck = get_option( $this->reputesmsid .'_gateway_db_version');
		
		if( ! empty( $reputesmsgatewaydbversioncheck ) )
			return;
			
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		$charset_collate = '';

        if( $wpdb->has_cap( 'collation' ) ){

            if( !empty($wpdb->charset) )

                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

            if( !empty($wpdb->collate) )

                $charset_collate .= " COLLATE $wpdb->collate";
        }
		
		$sql = "CREATE TABLE `". $wpdb->prefix . $this->reputesmsid ."_log_history` (
					`id` INT( 11 ) NOT NULL AUTO_INCREMENT,
					`sms_gateway` VARCHAR( 10 ) NOT NULL,
					`messageid` TEXT NOT NULL,
					`messagetext` TEXT NOT NULL,
					`to_number` VARCHAR( 50 ) NOT NULL,
					`delivered_flag` INT( 3 ) NOT NULL,
					`added_date_time` DATETIME NOT NULL,
					PRIMARY KEY ( `id` )
				) {$charset_collate};";
		dbDelta($sql);
		
		update_option( $this->reputesmsid .'_gateway_db_version', $repute_sms_gateway_db_version);
		update_option( $this->reputesmsid .'_gateway_plugin_version', $repute_sms_gateway_plugin_version);

	}
	
	public function load_repute_sms_status_actions()
	{
		global $wc_settings_reputesms;
		$this->log = new WC_Logger();
		
		$reputesmsgatewaydbversion = get_option('repute_sms_gateway_db_version');
		
		if($reputesmsgatewaydbversion == "" || !isset($reputesmsgatewaydbversion))
			$wc_settings_reputesms->repute_sms_install();
		
		$status_lists = $wc_settings_reputesms->get_repute_sms_enable_statuses();
		$total_status_lists = @count($status_lists);
		if($total_status_lists>0)
		{
			global $woocommerce;
			if (version_compare($woocommerce->version, '2.2', '>=')) 
			{
				
				foreach ( $status_lists as $slug => $status ) {
					add_action( 'woocommerce_order_status_'. strtolower( esc_attr( str_replace( "wc-", "", $slug ) ) ), array( &$this, 'check_repute_sms_to_send_customer_payment_complete' ) );
				}
			}else {
				foreach ( $status_lists as $slug => $status ) {
					add_action( 'woocommerce_order_status_'. strtolower( esc_attr( $slug ) ), array( &$this, 'check_repute_sms_to_send_customer_payment_complete' ) );
				}
			}
		}
	}
	
	public function add_repute_sms_settings_heading( $pages ) {
		$pages[$this->reputesmsid] = $this->reputesmslabel;
		return $pages;
	}
	
	public function repute_sms_set_js() {
		if(isset($_REQUEST["page"]) && $_REQUEST["page"] != "" && ( $_REQUEST["page"] == "wc-settings" || $_REQUEST["page"] == "woocommerce_settings" ) && (isset($_REQUEST["tab"]) && $_REQUEST["tab"]==$this->reputesmsid )) 
		{
                    global $repute_sms_gateway_plugin_version;
			wp_register_script( "smsforwoocommerce-js", REPUTE_SMS_GATEEWAY_URL . "/js/smsforwoocommerce.js", array("jquery") ,$repute_sms_gateway_plugin_version);
			wp_enqueue_script( "smsforwoocommerce-js" );
		}
	}
	
	public function repute_sms_set_css() {
		if(isset($_REQUEST["page"]) && $_REQUEST["page"] != "" && ( $_REQUEST["page"] == "wc-settings" || $_REQUEST["page"] == "woocommerce_settings" ) && (isset($_REQUEST["tab"]) && $_REQUEST["tab"]==$this->reputesmsid )) 
		{
                    global $repute_sms_gateway_plugin_version;
			wp_register_style("smsforwoocommerce-css", REPUTE_SMS_GATEEWAY_URL . "/css/smsforwoocommerce.css",array(), $repute_sms_gateway_plugin_version );
			wp_enqueue_style("smsforwoocommerce-css");
		}
	}
	
	function repute_sms_settings_tab_output() {
           
        echo $sms_banner_div = "<div class='rpt_banner_div'>
            
            <table><tbody>
            
                <tr><td style='text-align: center;'><span class='rpt_banner_heading_text'>".__('Other Useful Plugins', REPUTE_SMS_TEXT_DOMAIN)."</span><hr><br/></td></tr>

                <tr><td><a href='https://www.armemberplugin.com/product.php?rdt=t10' target='blank'><img src='".REPUTE_SMS_IMAGES_URL."/armember.png' class='rpt_sms_banner_img'></a><br/></td></tr>

                <tr><td><a href='http://arprice.arformsplugin.com/premium/product.php?rdt=t9' target='blank'><img src='".REPUTE_SMS_IMAGES_URL."/arprice.png' class='rpt_sms_banner_img'></a><br/></td></tr>

                <tr><td><a href='http://arformsplugin.com/premium/product.php?rdt=t1' target='blank'><img src='".REPUTE_SMS_IMAGES_URL."/arforms.png' class='rpt_sms_banner_img'></a><br/></td></tr>

                <tr><td><a href='http://arsocial.arformsplugin.com/premium/upgrade_to_premium.php?rdt=t6' target='blank'><img src='".REPUTE_SMS_IMAGES_URL."/arsocial.png' class='rpt_sms_banner_img'></a><br/></td></tr>
                
            </tbody></table>
              
            </div>"; 
        
           woocommerce_admin_fields( get_repute_sms_woo_setting_fields() );
	}
	
	function update_settings() {
		woocommerce_update_options( get_repute_sms_woo_setting_fields() );
	}
	
	//Get All SMS List
	function get_repute_sms_gateway_list()
	{
		$list = array( 
					'' => __( 'Please Select SMS Gateway', REPUTE_SMS_TEXT_DOMAIN ), 
					$this->reputesmsid .'_nexmo' => __( 'Nexmo', REPUTE_SMS_TEXT_DOMAIN ),
					$this->reputesmsid .'_twilio' => __( 'Twilio', REPUTE_SMS_TEXT_DOMAIN ), 
					$this->reputesmsid .'_clickatell' => __( 'Clickatell', REPUTE_SMS_TEXT_DOMAIN ), 
				);
		return $list;
	}
	
	function get_repute_sms_enable_statuses()
	{
		global $woocommerce;
		if (version_compare($woocommerce->version, '2.2', '>=')) 
		{
			$statuses = wc_get_order_statuses();
		}else {
			$statuses = array();
			$term_statuses = (array) get_terms( 'shop_order_status', array( 'hide_empty' => 0, 'orderby' => 'id' ) );
			if(count($term_statuses)>0)
			{
				foreach( $term_statuses as $term_status )
				{
					$statuses[$term_status->slug] = $term_status->name;
				}
			}
		}
		
		return $statuses;
	}
	
	//Code for show Custom Fields
	function repute_sms_checkout_fields( $checkout )
	{
		$check_sms_gateway_enabled = get_option( $this->reputesmsid .'_gateway_list' );
		$enable_disable_customer_notification = get_option( $this->reputesmsid ."_enable_disable_customer_notification" );
		if($check_sms_gateway_enabled!="" && $enable_disable_customer_notification=="yes")
		{
			$opt_in_checkbox_label = get_option( $this->reputesmsid.'_opt_in_checkbox_label' );
			if(!$opt_in_checkbox_label)
				$opt_in_checkbox_label = __('I want to Receive Order Updates by SMS', REPUTE_SMS_TEXT_DOMAIN );
				
			$opt_in_checkbox_default_value = get_option( $this->reputesmsid.'_opt_in_checkbox_default_value' );
			
			
			echo '<div id="'. $this->reputesmsid .'_send_me_sms_order_status_updates_heading"><h2>' . __("Get SMS Updates?", REPUTE_SMS_TEXT_DOMAIN ) . '</h2>';
				woocommerce_form_field( 
					$this->reputesmsid .'_send_me_sms_order_status_updates',
					array(
						'type' => 'checkbox',
						'class' => array('form-row-wide'),
						'label' => $opt_in_checkbox_label,
						'default' => $opt_in_checkbox_default_value,
						),
						$checkout->get_value( $this->reputesmsid .'_send_me_sms_order_status_updates' )
				);
				 
			echo '</div>';
		}
	}
	 
	//Code for update Custom Fields
	function repute_sms_checkout_fields_update( $order_id ) {
		if ( ! empty( $_POST[$this->reputesmsid .'_send_me_sms_order_status_updates'] ) ) {
			update_post_meta( $order_id, $this->reputesmsid .'_send_me_sms_order_status_updates', sanitize_text_field( $_POST[ $this->reputesmsid .'_send_me_sms_order_status_updates' ] ) );
		}
	}
	
	//Code for Shows that, is user have subscribe sms gateway or not
	
	function repute_sms_checkout_field_display_admin_order_meta( $order ) {
		$chk_user_subscribe_sms = get_post_meta( $order->id, $this->reputesmsid .'_send_me_sms_order_status_updates', true );
		if( $chk_user_subscribe_sms ) {
			$chk_user_subscribe_sms_text = __( 'Yes', REPUTE_SMS_TEXT_DOMAIN );
		}else {
			$chk_user_subscribe_sms_text = __( 'No', REPUTE_SMS_TEXT_DOMAIN );
		}
		echo '<p><strong>'.__( 'Enable', REPUTE_SMS_TEXT_DOMAIN ) .' '. $this->reputesmslabel .':</strong> ' . $chk_user_subscribe_sms_text . '</p>';
	}
	
	//Code for call this function for payment is done
	function check_repute_sms_to_send_customer_payment_complete( $order_id )
	{
		global $wc_settings_reputesms;
		$check_sms_gateway_enabled = get_option( $this->reputesmsid ."_gateway_list" );
		if($check_sms_gateway_enabled=="") {
			return;
		}
		
		$check_order_sms_gateway_enabled = get_post_meta( $order_id, $this->reputesmsid .'_send_me_sms_order_status_updates', true );
		
		if(!$check_order_sms_gateway_enabled) {
			return;
		}
		
		$order = new WC_Order( $order_id );
		$user_id = $order->user_id;
		
		$order_status = $wc_settings_reputesms->repute_sms_new_order_status( $order );
		$chk_sms_setting_status_enable = get_option( $this->reputesmsid ."_enable_". $order_status ."_sms_notify_status" );
		
		//Checking for status is enabled from sms settings
		if($chk_sms_setting_status_enable=="yes")
		{
			//Start Condition For Twilio SMS
			if( $check_sms_gateway_enabled==$this->reputesmsid .'_twilio' )
			{
				if( $this->reputesms_log=="yes" )
					$this->log->add( $this->reputesmsid, PHP_EOL .'***** '. $this->reputesmslabel .' Order #'. $order->id .' Log Start for Twilio *****');
				
				$wc_settings_reputesms->SendSMSFromTwilio( $order );
				
				if( $this->reputesms_log=="yes" )
					$this->log->add( $this->reputesmsid, PHP_EOL .'***** '. $this->reputesmslabel .' Order #'. $order->id .' Log End *****');
			}
			//End Condition For Twilio SMS
			
			//Start Condition For Clickatell SMS
			if( $check_sms_gateway_enabled==$this->reputesmsid .'_clickatell' )
			{
				if( $this->reputesms_log=="yes" )
					$this->log->add( $this->reputesmsid, PHP_EOL .'***** '. $this->reputesmslabel .' Order #'. $order->id .' Log Start for Clickatell *****');
				
				$wc_settings_reputesms->SendSMSFromClickatell( $order );
				
				if( $this->reputesms_log=="yes" )
					$this->log->add( $this->reputesmsid, PHP_EOL .'***** '. $this->reputesmslabel .' Order #'. $order->id .' Log End *****');
			}
			//End Condition For Clickatell SMS
			
			//Start Condition For Nexmo SMS
			if( $check_sms_gateway_enabled==$this->reputesmsid .'_nexmo' )
			{
				if( $this->reputesms_log=="yes" )
					$this->log->add( $this->reputesmsid, PHP_EOL .'***** '. $this->reputesmslabel .' Order #'. $order->id .' Log Start for Nexmo *****');
				$wc_settings_reputesms->SendSMSFromNexmo( $order );
					
				if( $this->reputesms_log=="yes" )
					$this->log->add( $this->reputesmsid, PHP_EOL .'***** '. $this->reputesmslabel .' Order #'. $order->id .' Log End *****');
			}
			//End Condition For Nexmo SMS
			
		}
	}
	
	function get_customer_sms_text( $order ) {
		global $wc_settings_reputesms;
		$order_status = $wc_settings_reputesms->repute_sms_new_order_status( $order );
		$customer_sms_text_default = stripslashes_deep(get_option( $this->reputesmsid ."_". $order_status ."_sms_text" ));
		$customer_sms_text = repute_sms_woo_replace_shortcode_variable( $customer_sms_text_default, $order);
		
		return $customer_sms_text;
	}
	
	function get_admin_sms_text( $order ) {
		global $wc_settings_reputesms;
		$admin_sms_text_default = stripslashes_deep(get_option( $this->reputesmsid ."_admin_sms_text" ));
		$admin_sms_text = repute_sms_woo_replace_shortcode_variable( $admin_sms_text_default, $order);
		
		return $admin_sms_text;
	}
	
	function get_message_text_URLs( $message ) {
		$regex = '/(https?|ftp|file)\:\/\/[^\" ,\n]+/i';
		preg_match_all($regex, $message, $matches);
		return ($matches[0]);
	}
	
	function AddMessageHistory($sms_gateway, $messageid, $messagetext, $to_number)
	{
		global $wpdb, $wc_settings_reputesms;
		$added_date_time = current_time( 'mysql' );
		$res = $wpdb->insert(
				$wpdb->prefix. $this->reputesmsid .'_log_history',
				//$wpdb->prefix."repute_sms_log_history",
					array( 
						'sms_gateway' => $sms_gateway,
						'messageid' => maybe_serialize( $messageid ),
						'messagetext' => $messagetext,
						'to_number' => $to_number,
						'added_date_time' => $added_date_time,
					), array( '%s', '%s', '%s', '%s', '%s' )
				);
	}
	
	function load_smstwilio_library()
	{
		if( !class_exists(Services_Twilio))
		{
			$lib = REPUTE_SMS_GATEEWAY_DIR . '/core/libs/twilio/Services/Twilio.php';
			include($lib);
		}
	}
	
	function SendSMSFromTwilio( $order )
	{
		global $wc_settings_reputesms;
		
		$order_id = $order->id;
		$order_status = $wc_settings_reputesms->repute_sms_new_order_status( $order );
		
		$chk_twilio_from_number = get_option( $this->reputesmsid ."_twilio_from_number" );
		$chk_twilio_account_sid = get_option( $this->reputesmsid ."_twilio_account_sid" );
		$chk_twilio_auth_token = get_option( $this->reputesmsid ."_twilio_auth_token" );
		
		if($chk_twilio_from_number!="" && $chk_twilio_account_sid!="" && $chk_twilio_auth_token!="")
		{
			if( $this->reputesms_log=="yes" )
				$this->log->add( $this->reputesmsid, PHP_EOL.'-> Order Status: '. $order_status .' Twilio From Number, AccountSID and AuthToken Set.');
			
			$customer_sms_text = $wc_settings_reputesms->get_customer_sms_text( $order );
			$admin_sms_text = $wc_settings_reputesms->get_admin_sms_text( $order );
			
			$wc_settings_reputesms->load_smstwilio_library();
			$ReputeSMSTwilioService = new Services_Twilio($chk_twilio_account_sid, $chk_twilio_auth_token);
			
			//Send SMS to ADMIN
			$chk_admin_enabled_notification = get_option( $this->reputesmsid ."_enable_disable_admin_notification" );
			$chk_repute_sms_admin_number = get_option( $this->reputesmsid ."_admin_number" );
			if($chk_admin_enabled_notification=="yes" && $chk_repute_sms_admin_number!="")
			{	
				try {
					$sms = $ReputeSMSTwilioService->account->messages->sendMessage(
								$chk_twilio_from_number, 
								$chk_repute_sms_admin_number, 
								$admin_sms_text
							);
					if($sms->sid!="")
					{
						$messageid = array( "msgid" => $sms->sid );
						$sms_gateway = 't';
						$wc_settings_reputesms->AddMessageHistory($sms_gateway, $messageid, $admin_sms_text, $chk_repute_sms_admin_number);
					}
					
					if( $this->reputesms_log=="yes" )
						$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Sent to Admin.'. PHP_EOL . 'From Number:'. $chk_twilio_from_number .', To Number:'. $chk_repute_sms_admin_number .', SMS Text:'. $admin_sms_text);
						
				} catch (Services_Twilio_RestException $e) {
					if( $this->reputesms_log=="yes" )
						$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Not Sent to Admin due to '. $e->getMessage(). PHP_EOL . 'From Number:'. $chk_twilio_from_number .', To Number:'. $chk_repute_sms_admin_number .', SMS Text:'. $admin_sms_text );
				}
			}
			
			$chk_customer_enabled_notification = get_option( $this->reputesmsid ."_enable_disable_customer_notification" );
			$chk_repute_sms_customer_phone = $wc_settings_reputesms->repute_sms_woo_get_customer_nubmer( $order_id ); //This value from WooCommerce Ordered.
			
			if($chk_customer_enabled_notification=="yes" && $chk_repute_sms_customer_phone!="")
			{
				try {
					$sms = $ReputeSMSTwilioService->account->messages->sendMessage(
								$chk_twilio_from_number, 
								$chk_repute_sms_customer_phone, 
								$customer_sms_text
						   );
					if($sms->sid!="" && is_object($sms))
					{
						$messageid = array( "msgid" => $sms->sid );
						$sms_gateway = 't';
						$wc_settings_reputesms->AddMessageHistory($sms_gateway, $messageid, $customer_sms_text, $chk_repute_sms_customer_phone);
					}
					if( $this->reputesms_log=="yes" )
						$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Sent to Customer.'. PHP_EOL . 'From Number:'. $chk_twilio_from_number .', To Number:'. $chk_repute_sms_customer_phone .', SMS Text:'. $customer_sms_text);
					
				} catch (Services_Twilio_RestException $e) {
					if( $this->reputesms_log=="yes" )
						$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Not Sent to Customer due to '. $e->getMessage(). PHP_EOL . 'From Number:'. $chk_twilio_from_number .', To Number:'. $chk_repute_sms_customer_phone .', SMS Text:'. $customer_sms_text );
				}
			}
			
		}
	
	}
	
	function SendSMSFromClickatell( $order )
	{
		global $wc_settings_reputesms;
		
		$order_id = $order->id;
		$order_status = $wc_settings_reputesms->repute_sms_new_order_status( $order );
		
		$chk_clickatell_username = get_option( $this->reputesmsid ."_clickatell_username" );
		$chk_clickatell_password = get_option( $this->reputesmsid ."_clickatell_password" );
		$chk_clickatell_api_id = get_option( $this->reputesmsid ."_clickatell_api_id" );
		$chk_clickatell_from_name = get_option( $this->reputesmsid ."_clickatell_from_name" );
		$chk_clickatell_unicode_message = get_option( $this->reputesmsid ."_clickatell_unicode_message" );
		$notsent = '1';
		if( $chk_clickatell_username!="" && $chk_clickatell_password!="" && $chk_clickatell_api_id!="" )
		{
			if( $this->reputesms_log=="yes" )
				$this->log->add( $this->reputesmsid, PHP_EOL.'-> Order Status: '. $order_status .' Clickatell Username, Password, Api ID Set.');
			
			$customer_sms_text = $wc_settings_reputesms->get_customer_sms_text( $order );
			$admin_sms_text = $wc_settings_reputesms->get_admin_sms_text( $order );
			
			$packet["body"]["user"] = $chk_clickatell_username;
			$packet["body"]["password"] = $chk_clickatell_password;
			$packet["body"]["api_id"] = $chk_clickatell_api_id;
			$packet["body"]["from"] = $chk_clickatell_from_name;
			if($chk_clickatell_unicode_message=="yes") {
				$packet["body"]["unicode"] = "true";
			}
			
			$clickatell_url = "http://api.clickatell.com/http/";
			$clickatell_api_url = $clickatell_url ."sendmsg";
			$clickatell_auth_url = $clickatell_url ."auth?api_id=". $chk_clickatell_api_id ."&user=". $chk_clickatell_username ."&password=". $chk_clickatell_password;
			
			//Send SMS to ADMIN
			$chk_admin_enabled_notification = get_option( $this->reputesmsid ."_enable_disable_admin_notification" );
			$chk_repute_sms_admin_number = get_option( $this->reputesmsid ."_admin_number" );
			if( $chk_admin_enabled_notification=="yes" && $chk_repute_sms_admin_number!="" )
			{
				$packet["body"]["to"] = $chk_repute_sms_admin_number;
				$packet["body"]["text"] = $admin_sms_text;
				
				$sms = wp_remote_post( $clickatell_api_url, $packet );
				
				if($sms['body']!="")
				{
					$sms_check = explode( "ID:", $sms['body'] );
					if( count($sms_check)>1 )
					{
						$notsent = '0';
						$messageid = array( "msgid" => trim( $sms_check[1] ) );
						$sms_gateway = 'c';
						$wc_settings_reputesms->AddMessageHistory($sms_gateway, $messageid, $admin_sms_text, $chk_repute_sms_admin_number);

						if( $this->reputesms_log=="yes" )
							$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Sent to Admin.'. PHP_EOL . 'From Name:'. $chk_clickatell_from_name .', To Number:'. $chk_repute_sms_admin_number .', SMS Text:'. $admin_sms_text);
					}
				}
				if( $notsent=='1' )
				{
					if( $this->reputesms_log=="yes" )
						$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Not Sent to Admin due to '. $sms['body']. PHP_EOL . 'From Name:'. $chk_clickatell_from_name .', To Number:'. $chk_repute_sms_admin_number .', SMS Text:'. $admin_sms_text );
				}
			}
			
			//Send SMS to Customer
			$chk_customer_enabled_notification = get_option( $this->reputesmsid ."_enable_disable_customer_notification" );
			$chk_repute_sms_customer_phone = $wc_settings_reputesms->repute_sms_woo_get_customer_nubmer( $order_id ); //This value from WooCommerce Ordered.
			if($chk_customer_enabled_notification=="yes" && $chk_repute_sms_customer_phone!="")
			{
				$packet["body"]["to"] = $chk_repute_sms_customer_phone;
				$packet["body"]["text"] = $customer_sms_text;
				
				$sms = wp_remote_post( $clickatell_api_url, $packet );
				if($sms['body']!="")
				{
					$sms_check = explode( "ID:", $sms['body'] );
					if( count($sms_check)>1 )
					{
						$notsent = '0';
						$messageid = array( "msgid" => trim( $sms_check[1] ) );
						$sms_gateway = 'c';
						$wc_settings_reputesms->AddMessageHistory($sms_gateway, $messageid, $customer_sms_text, $chk_repute_sms_customer_phone);
						
						if( $this->reputesms_log=="yes" )
						$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Sent to Customer.'. PHP_EOL . 'From Name:'. $chk_clickatell_from_name .', To Number:'. $chk_repute_sms_customer_phone .', SMS Text:'. $customer_sms_text);
					}
				}
				if( $notsent=='1' )
				{
					if( $this->reputesms_log=="yes" )
						$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Not Sent to Customer '. $sms['body'] . PHP_EOL . 'From Name:'. $chk_clickatell_from_name .', To Number:'. $chk_repute_sms_customer_phone .', SMS Text:'. $customer_sms_text );
				}
			}
		}
	}
	
	function load_smsnexmo_library()
	{
		if( !class_exists(NexmoMessage))
		{
			$lib = REPUTE_SMS_GATEEWAY_DIR . '/core/libs/nexmo/NexmoMessage.php';
			include($lib);
		}
	}
	
	function SendSMSFromNexmo( $order )
	{
		global $wc_settings_reputesms;
		
		$order_id = $order->id;
		$order_status = $wc_settings_reputesms->repute_sms_new_order_status( $order );
		
		$chk_nexmo_api_key = get_option( $this->reputesmsid ."_nexmo_api_key" );
		$chk_nexmo_api_secret = get_option( $this->reputesmsid ."_nexmo_api_secret" );
		$chk_nexmo_api_from_name = get_option( $this->reputesmsid ."_nexmo_api_from_name" );
		
		if( $chk_nexmo_api_key!="" && $chk_nexmo_api_secret!="" )
		{
			if( $this->reputesms_log=="yes" )
				$this->log->add( $this->reputesmsid, PHP_EOL.'-> Order Status: '. $order_status .' Nexmo API Key and Secret Set.');
			
			
			$customer_sms_text = $wc_settings_reputesms->get_customer_sms_text( $order );
			$admin_sms_text = $wc_settings_reputesms->get_admin_sms_text( $order );
			
			$wc_settings_reputesms->load_smsnexmo_library();
			
			// Step 1: Declare new NexmoMessage.
			$ReputeSMSNexmoService = new NexmoMessage($chk_nexmo_api_key, $chk_nexmo_api_secret);
			
			//Send SMS to ADMIN
			$chk_admin_enabled_notification = get_option( $this->reputesmsid ."_enable_disable_admin_notification" );
			$chk_repute_sms_admin_number = get_option( $this->reputesmsid ."_admin_number" );
			if( $chk_admin_enabled_notification=="yes" && $chk_repute_sms_admin_number!="" )
			{
				// Step 2: Use sendText( $to, $from, $message ) method to send a message. 
				$info = $ReputeSMSNexmoService->sendText( $chk_repute_sms_admin_number, $chk_nexmo_api_from_name, $admin_sms_text );
				
				if( $info->messages[0]->messageid!="" && is_object($info) )
				{
					$messageid = array( "msgid" => $info->messages[0]->messageid );
					$sms_gateway = 'n';
					$wc_settings_reputesms->AddMessageHistory($sms_gateway, $messageid, $admin_sms_text, $chk_repute_sms_admin_number);
				}

				// Step 3: Display an overview of the message
				$sms = @strip_tags( $ReputeSMSNexmoService->displayOverview($info), "<td>" );
				$sms = trim( str_replace( array( "<td>", "</td>", "<td colspan=\"2\">", "  " ), " ", $sms ) );
				
				$notsent = '1';
				if( $info->messages[0]->messageid!="" && is_object($info) )
				{
					$sms_check = explode( " OK ", $sms );
					if( count($sms_check)>1 )
					{
						$notsent = '0';
						if( $this->reputesms_log=="yes" )
							$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Sent to Admin.'. PHP_EOL . 'From Name:'. $chk_nexmo_api_from_name .', To Number:'. $chk_repute_sms_admin_number .', SMS Text:'. $admin_sms_text . PHP_EOL . 'Nexmo Return:'. $sms);
					}
				}
				if( $notsent=='1' )
				{
					if( $this->reputesms_log=="yes" )
						$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Not Sent to Admin due to '. $sms['body']. PHP_EOL . 'From Name:'. $chk_nexmo_api_from_name .', To Number:'. $chk_repute_sms_admin_number .', SMS Text:'. $admin_sms_text . PHP_EOL . 'Nexmo Return:'. $sms );
				}
			}
			
			//Send SMS to Customer
			$chk_customer_enabled_notification = get_option( $this->reputesmsid ."_enable_disable_customer_notification" );
			$chk_repute_sms_customer_phone = $wc_settings_reputesms->repute_sms_woo_get_customer_nubmer( $order_id ); //This value from WooCommerce Ordered.
			if($chk_customer_enabled_notification=="yes" && $chk_repute_sms_customer_phone!="")
			{
				// Step 2: Use sendText( $to, $from, $message ) method to send a message. 
				$info = $ReputeSMSNexmoService->sendText( $chk_repute_sms_customer_phone, $chk_nexmo_api_from_name, $customer_sms_text );
				
				if( $info->messages[0]->messageid!="" && is_object($info) )
				{
					$messageid = array( "msgid" => $info->messages[0]->messageid );
					$sms_gateway = 'n';
					$wc_settings_reputesms->AddMessageHistory($sms_gateway, $messageid, $customer_sms_text, $chk_repute_sms_customer_phone);
				}

				// Step 3: Display an overview of the message
				$sms = @strip_tags( $ReputeSMSNexmoService->displayOverview($info), "<td>" );
				$sms = trim( str_replace( array( "<td>", "</td>", "<td colspan=\"2\">", "  " ), " ", $sms ) );
				
				$notsent = '1';
				if( $info->messages[0]->messageid!="" && is_object($info) )
				{
					$sms_check = explode( " OK ", $sms );
					if( count($sms_check)>1 )
					{
						$notsent = '0';
						if( $this->reputesms_log=="yes" )
							$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Sent to Customer.'. PHP_EOL . 'From Name:'. $chk_nexmo_api_from_name .', To Number:'. $chk_repute_sms_customer_phone .', SMS Text:'. $customer_sms_text . PHP_EOL . 'Nexmo Return:'. $sms );
					}
				}
				if( $notsent=='1' )
				{
					if( $this->reputesms_log=="yes" )
						$this->log->add( $this->reputesmsid, PHP_EOL.'-> SMS Not Sent to Customer '. $sms['body'] . PHP_EOL . 'From Name:'. $chk_nexmo_api_from_name .', To Number:'. $chk_repute_sms_customer_phone .', SMS Text:'. $customer_sms_text . PHP_EOL . 'Nexmo Return:'. $sms  );
				}
			}
		}
	}
	
	function get_sms_gateway_sent_messages()
	{
		$list = repute_sms_woo_get_sms_history_list();
		echo $list;
		die();
	}
	
	function check_sms_current_status()
	{	
		global $wpdb, $wc_settings_reputesms;
		$current_time = current_time( 'timestamp' );
		
		if(isset($_REQUEST['sms_history_id']) && $_REQUEST['sms_history_id']>0)
		{
			$id = $_REQUEST['sms_history_id'];
		}else {
			return;
		}
		
		$get_log = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix . $this->reputesmsid ."_log_history WHERE id=%d", $id ) );
		$get_status="-";

		if( count($get_log)>0 )
		{
			$get_log = $get_log[0];
			
			$messageid_arr = maybe_unserialize( $get_log->messageid );
			$messageid = trim( $messageid_arr["msgid"] );
			$sms_sent_time = $get_log->added_date_time;
			
			$consider_deliver_max_time = (($current_time - strtotime($sms_sent_time))/60/60);
			if( $consider_deliver_max_time>=$this->set_auto_deliver_hours )
			{
				$res = $wpdb->update(
								$wpdb->prefix. $this->reputesmsid ."_log_history",
								array( 'delivered_flag' => 1, ), array( 'id' => $id ), array( '%d' ), array( '%d' ) 
							  );
				echo  $get_status = '<span class="smstxtstatus">'. __( 'Delivered', REPUTE_SMS_TEXT_DOMAIN ) .'</span>';
				die();
			}
			if( $get_log->sms_gateway=="t" )
			{
				$chk_twilio_account_sid = get_option( $this->reputesmsid ."_twilio_account_sid" );
				$chk_twilio_auth_token = get_option( $this->reputesmsid ."_twilio_auth_token" );
				
				if($chk_twilio_account_sid!="" && $chk_twilio_auth_token!="")
				{
					$wc_settings_reputesms->load_smstwilio_library();
					$ReputeSMSTwilioService = new Services_Twilio($chk_twilio_account_sid, $chk_twilio_auth_token);
				
					$chk_sms = $ReputeSMSTwilioService->account->sms_messages->get($messageid);
					$chk_status = $chk_sms->status;
					
					if( $chk_status=="delivered" )
					{
						$get_status = '<span class="smstxtstatus">'. __( 'Delivered', REPUTE_SMS_TEXT_DOMAIN ) .'</span>';
						$res = $wpdb->update(
										$wpdb->prefix . $this->reputesmsid ."_log_history",
										array( 'delivered_flag' => 1, ), array( 'id' => $id ), array( '%d' ), array( '%d' ) 
									  );
					}
				}
			}
			else if( $get_log->sms_gateway=="c" )
			{
				$chk_clickatell_username = get_option( $this->reputesmsid ."_clickatell_username" );
				$chk_clickatell_password = get_option( $this->reputesmsid ."_clickatell_password" );
				$chk_clickatell_api_id = get_option( $this->reputesmsid ."_clickatell_api_id" );
				
				if( $chk_clickatell_username!="" && $chk_clickatell_password!="" && $chk_clickatell_api_id!="" )
				{
					$clickatell_url = "http://api.clickatell.com/http/";
					$clickatell_auth_url = $clickatell_url ."auth?api_id=". $chk_clickatell_api_id ."&user=". $chk_clickatell_username ."&password=". $chk_clickatell_password;
					
					$clickatell_auth_token = wp_remote_get( $clickatell_auth_url, array( 'timeout' => $this->request_timeout ) );
					$session_id = "";
					if($clickatell_auth_token["body"]!="")
					{
						$check_session = explode( "OK:", $clickatell_auth_token["body"] );
						$session_id = trim( $check_session[1] );
					}
				
					$url = "https://api.clickatell.com/http/querymsg?session_id=". $session_id ."&apimsgid=". $messageid;
					$chk_sms = wp_remote_get( $url, array( 'timeout' => $this->request_timeout ) );
					if($chk_sms!="")
					{
						$explodesms = explode( "Status:", $chk_sms["body"] );
						if( count($explodesms>1) )
						{
							$chk_status = trim( $explodesms[1] );
							if( $chk_status=="004" )
							{
								$get_status = '<span class="smstxtstatus">'. __( 'Delivered', REPUTE_SMS_TEXT_DOMAIN ) .'</span>';
								$res = $wpdb->update(
												$wpdb->prefix . $this->reputesmsid ."_log_history",
												array( 'delivered_flag' => 1, ), array( 'id' => $id ), array( '%d' ), array( '%d' ) 
											  );
							}
						}
					}
				}
			}
			else if( $get_log->sms_gateway=="n" )
			{
				$chk_nexmo_api_key = get_option( $this->reputesmsid ."_nexmo_api_key" );
				$chk_nexmo_api_secret = get_option( $this->reputesmsid ."_nexmo_api_secret" );
				if( $chk_nexmo_api_key!="" && $chk_nexmo_api_secret!="" )
				{
					$url = "https://rest.nexmo.com/search/messages/". $chk_nexmo_api_key ."/". $chk_nexmo_api_secret."?ids=".$messageid;
					$chk_sms = wp_remote_get( $url, array( 'timeout' => $this->request_timeout ) );
					$chk_status = $chk_sms->items[0]->final-status;
					if( $chk_status=="DELIVRD" )
					{
						$get_status = '<span class="smstxtstatus">'. __( 'Delivered', REPUTE_SMS_TEXT_DOMAIN ) .'</span>';
						$res = $wpdb->update(
										$wpdb->prefix . $this->reputesmsid ."_log_history",
										array( 'delivered_flag' => 1, ), array( 'id' => $id ), array( '%d' ), array( '%d' ) 
									  );
					}
				}
				
			}
		}
		
		echo $get_status;
		die();
	}
	
	function smsforwoo_footer_link(){
		if( isset( $_REQUEST['tab'] ) && $_REQUEST['tab']==$this->reputesmsid )
	  		echo "<div class='smsforwoo_footer'><a href='". REPUTE_SMS_GATEEWAY_URL ."/documentation/index.html' target='_blank'>". __( 'SMS for WooCommerce Documentation', REPUTE_SMS_TEXT_DOMAIN ) ."</a></div>";
	}
	
	function sms_woo_send_custom_message($to_number="", $to_text="", $flag=false)
	{
		global $wpdb, $wc_settings_reputesms;
		if($to_number=="" && $to_text=="")
		{
			
		}
		$actual_error = '';
		$check_sms_gateway_enabled = get_option( $this->reputesmsid .'_gateway_list' );
		$sent_from_gateway = "";
		if($check_sms_gateway_enabled!="")
		{
			if( $check_sms_gateway_enabled== $this->reputesmsid .'_twilio' ) {
				$chk_twilio_from_number = get_option( $this->reputesmsid ."_twilio_from_number" );
				$chk_twilio_account_sid = get_option( $this->reputesmsid ."_twilio_account_sid" );
				$chk_twilio_auth_token = get_option( $this->reputesmsid ."_twilio_auth_token" );
				
				if($chk_twilio_from_number!="" && $chk_twilio_account_sid!="" && $chk_twilio_auth_token!="")
				{
					$wc_settings_reputesms->load_smstwilio_library();
					$ReputeSMSTwilioService = new Services_Twilio($chk_twilio_account_sid, $chk_twilio_auth_token);
					try {
						$sms = $ReputeSMSTwilioService->account->messages->sendMessage(
									$chk_twilio_from_number,
									$to_number,
									$to_text
								);
						if($sms->sid!="")
						{
							$sent_from_gateway = "<reputesms>1|". __( 'Message sent successfully from Twilio.', REPUTE_SMS_TEXT_DOMAIN )."<reputesms>";
							if($flag==true) { return 1; }
						}		
					} catch (Services_Twilio_RestException $e) {
                                            $actual_error =$e->getMessage();
					}
				}
				
			}
			else if( $check_sms_gateway_enabled== $this->reputesmsid .'_clickatell' ) {
				$chk_clickatell_username = get_option( $this->reputesmsid ."_clickatell_username" );
				$chk_clickatell_password = get_option( $this->reputesmsid ."_clickatell_password" );
				$chk_clickatell_api_id = get_option( $this->reputesmsid ."_clickatell_api_id" );
				$chk_clickatell_from_name = get_option( $this->reputesmsid ."_clickatell_from_name" );
				$chk_clickatell_unicode_message = get_option( $this->reputesmsid ."_clickatell_unicode_message" );
				
				if( $chk_clickatell_username!="" && $chk_clickatell_password!="" && $chk_clickatell_api_id!="" )
				{
					$packet["body"]["user"] = $chk_clickatell_username;
					$packet["body"]["password"] = $chk_clickatell_password;
					$packet["body"]["api_id"] = $chk_clickatell_api_id;
					$packet["body"]["from"] = $chk_clickatell_from_name;
					if($chk_clickatell_unicode_message=="yes") {
						$packet["body"]["unicode"] = "true";
					}
					
					$clickatell_url = "http://api.clickatell.com/http/";
					$clickatell_api_url = $clickatell_url ."sendmsg";
					
					$packet["body"]["to"] = $to_number;
					$packet["body"]["text"] = $to_text;
					
					$sms = wp_remote_post( $clickatell_api_url, $packet );
					$notsent = '1';
					if($sms['body']!="")
					{
						$sms_check = explode( "ID:", $sms['body'] );
						if( count($sms_check)>1 )
						{
							$sent_from_gateway = "<reputesms>1|". __( 'Message sent successfully from Clickatell.', REPUTE_SMS_TEXT_DOMAIN )."<reputesms>";
							if($flag==true) { return 1; }
                                                }else{
                                                   $actual_error =  $sms['body'];
                                                }
					}
				}
			}
			else if( $check_sms_gateway_enabled== $this->reputesmsid .'_nexmo' ) {
				$chk_nexmo_api_key = get_option( $this->reputesmsid ."_nexmo_api_key" );
				$chk_nexmo_api_secret = get_option( $this->reputesmsid ."_nexmo_api_secret" );
				$chk_nexmo_api_from_name = get_option( $this->reputesmsid ."_nexmo_api_from_name" );
				
				if( $chk_nexmo_api_key!="" && $chk_nexmo_api_secret!="" )
				{
					$wc_settings_reputesms->load_smsnexmo_library();
			
					// Step 1: Declare new NexmoMessage.
					$ReputeSMSNexmoService = new NexmoMessage($chk_nexmo_api_key, $chk_nexmo_api_secret);
					$info = $ReputeSMSNexmoService->sendText( $to_number, $chk_nexmo_api_from_name, $to_text );
					if( $info->messages[0]->messageid!="" && is_object($info) )
					{
						$sent_from_gateway = "<reputesms>1|". __( 'Message sent successfully from Nexmo.', REPUTE_SMS_TEXT_DOMAIN )."<reputesms>";
                                                if($flag==true) { return 1; }
                                        }else{
                                             $actual_error = $info->messages[0]->errortext;
                                        }
				}
			}
		}
		if($sent_from_gateway!="" && $flag==false)	{
			echo $sent_from_gateway;
			die();
		}
		
		if($flag==false)
		{
                        if(isset($actual_error)){
                            echo "<reputesms>2|". $actual_error ."<reputesms>";
                        }else{
                               echo "<reputesms>2|". __( 'Message not sent successfully.', REPUTE_SMS_TEXT_DOMAIN )."<reputesms>";
                        }
			
			die();
		}
	}
	
	function sms_woo_send_manual_message()
	{
		global $wpdb, $wc_settings_reputesms;
		$to_number = isset( $_REQUEST['to_number'] ) ? sanitize_text_field($_REQUEST['to_number']) : '';
                
		$to_text = isset( $_REQUEST['to_text'] ) ? $_REQUEST['to_text'] : '';
		
		$wc_settings_reputesms->sms_woo_send_custom_message($to_number, $to_text, false);
		die();
	}
	
	function sms_woo_send_bulk_messages()
	{
		global $wpdb, $wc_settings_reputesms;
		
		$bulk_sms_text = isset( $_REQUEST['bulk_sms_text'] ) ? $_REQUEST['bulk_sms_text'] : '';
		
		if($bulk_sms_text=='')
			die();
			
		$checked_data = "";
		$check_sms_gateway_enabled = get_option( $this->reputesmsid .'_gateway_list' );
		if($check_sms_gateway_enabled!="")
		{
			$type = 'shop_order';
			
			$args=array( 'post_type' => $type, 'posts_per_page' => -1, 'post_status' =>'any');
			
			$get_order = new WP_Query($args);
			$customer_numbers = array();
			if( $get_order->have_posts() ) {
				while ($get_order->have_posts()) : $get_order->the_post();
					$order_id = get_the_ID();
					$phone_number = $wc_settings_reputesms->repute_sms_woo_get_customer_nubmer( $order_id );
					$check_order_sms_gateway_enabled = get_post_meta( $order_id, $this->reputesmsid .'_send_me_sms_order_status_updates', true );
					if( $phone_number!="" && $check_order_sms_gateway_enabled>0 )
					{
						$customer_numbers[$order_id] = $phone_number;
					}
					
				endwhile;
			}
			$customer_numbers = array_unique($customer_numbers);
			wp_reset_query();
			
			$total_customer_number = count($customer_numbers);
			if($total_customer_number>0)
			{
				foreach($customer_numbers as $customer_number)
				{
					$sendmsg = $wc_settings_reputesms->sms_woo_send_custom_message($customer_number,$bulk_sms_text,true);
					if( $checked_data=="" && $sendmsg!="" )
					{
						$checked_data="1";
					}
				}
			}
		}
		if($checked_data!="")
		{
			echo "<reputesms>1|". __( 'Bulk SMS sent successfully.', REPUTE_SMS_TEXT_DOMAIN )."<reputesms>";
		}else {
			echo "<reputesms>2|". __( 'Bulk SMS not sent successfully.', REPUTE_SMS_TEXT_DOMAIN )."<reputesms>";
		}
		die();
	}
	
	function repute_sms_woo_get_customer_nubmer( $order_id )
	{
		return get_post_meta( $order_id, "_billing_phone", true );
	}

	function repute_sms_new_order_status( $order )
	{
		global $woocommerce, $wpdb, $wc_settings_reputesms;
		if (version_compare($woocommerce->version, '2.2', '>=')) 
		{
			$order_status = $order->post_status;
		}else {
			$order_status = $order->status;
		}
		return $order_status;
	}

	
}

function ReputeSMSGatewayUninstall() 
{
	global $wpdb, $reputesmsid;
	if($reputesmsid=="") { $reputesmsid = "repute_sms"; }
	
	if ( is_multisite() ) 
	{
		
		$blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
		if ($blogs) {
			foreach($blogs as $blog) {
				switch_to_blog($blog['blog_id']);
				
				$wpdb->query('DROP TABLE IF EXISTS '. $wpdb->prefix.  $reputesmsid .'_log_history');
				
				//Delete SMSWooCommerce options
				$wpdb->query('DELETE FROM '. $wpdb->prefix .'options WHERE option_name LIKE "'. $reputesmsid .'_%"');
				
			}
			restore_current_blog();
		}	
	}
	else
	{
		$wpdb->query('DROP TABLE IF EXISTS '. $wpdb->prefix.  $reputesmsid .'_log_history');
		
		//Delete SMSWooCommerce options
		$wpdb->query('DELETE FROM '. $wpdb->prefix .'options WHERE option_name LIKE "'. $reputesmsid .'_%"');
	
	}
	
}
register_uninstall_hook( __FILE__, 'ReputeSMSGatewayUninstall' );

?>