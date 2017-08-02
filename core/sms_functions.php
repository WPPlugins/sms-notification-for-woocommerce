<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit; //Exit if access directly
	}
	
	if( !class_exists( 'WC_Settings_ReputeSMS' ) )
	{
		return;
	}
	
	if( !function_exists( 'get_repute_sms_woo_setting_fields' ) )
	{	
		function get_repute_sms_woo_setting_fields() {
			global $wc_settings_reputesms, $reputesmsid, $reputesmslabel, $smsforwooplnm;
			
			$sms_text_descritption = "<table class='useshortcodetable'>
										<tbody>
											<tr>
												<td colspan='2'><b>". __('Use Message Shortcodes', REPUTE_SMS_TEXT_DOMAIN) ."</b></td>
											</tr>
											<tr>
												<td>". __('Your Site Name', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{SHOP_NAME}</code></td></tr>
											<tr>
												<td>". __('Order Number', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{ORDER_NUMBER}</code></td>
											</tr>
											<tr>
												<td>". __('Order Status', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{ORDER_STATUS}</code></td>
											</tr>
											<tr>
												<td>". __('Order Amount', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{ORDER_AMOUNT}</code></td>
											</tr>
											<tr>
												<td>". __('Order Date', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{ORDER_DATE}</code></td>
											</tr>
											<tr>
												<td>". __('Order Items', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{ORDER_ITEMS}</code></td>
											</tr>
											<tr>
												<td>". __('First Name', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{BILLING_FNAME}</code></td>
											</tr>
											<tr>
												<td>". __('Last Name', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{BILLING_LNAME}</code></td>
											</tr>
											<tr>
												<td>". __('Billing Email', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{BILLING_EMAIL}</code></td>
											</tr>
											<tr>
												<td>". __('Current Date', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{CURRENT_DATE}</code></td>
											</tr>
											<tr>
												<td>". __('Current Time', REPUTE_SMS_TEXT_DOMAIN) ."&nbsp;</td><td><code>{CURRENT_TIME}</code></td>
											</tr>
										</tbody>
									 </table>";
			$status_lists = $wc_settings_reputesms->get_repute_sms_enable_statuses();
			$total_status_lists = @count($status_lists);
			$status_lists_arr = array();
			$settings_customer_data_arr = array();
			$status_cntr = 0;
			$charactor_script_var = '';
			if($total_status_lists>0)
			{
				foreach ( $status_lists as $slug => $statusname ) {
					if($status_cntr==0) {
						$checkboxgroup = "start";
						$statustitle = __( 'Select status to send notification', REPUTE_SMS_TEXT_DOMAIN );
					}else if(($total_status_lists-1)==$status_cntr && ($total_status_lists>1)) {
						$checkboxgroup = "end";
						$statustitle = "";
					}else {
						$checkboxgroup = "";
						$statustitle = "";
					}
					
					$status_lists_arr[] = array(
											'title' => esc_html($statustitle),
											'name'  => $reputesmsid.'_enable_' . esc_attr( $slug ) . '_sms_notify_status',
											'type'  => 'checkbox',
											'desc'  => __( "Order", REPUTE_SMS_TEXT_DOMAIN ). " ". $statusname,
											'id'    => $reputesmsid.'_enable_' . esc_attr( $slug ) . '_sms_notify_status',
											'checkboxgroup'		=> $checkboxgroup,
											'default' => 'yes',
										);
					
					$settings_customer_data_arr[] = array(
											'title' => __( 'SMS Text for', REPUTE_SMS_TEXT_DOMAIN ) ." ". esc_html($statusname) ." ". __('order', REPUTE_SMS_TEXT_DOMAIN ),
											'name'  => $reputesmsid.'_'. $slug .'_sms_text',
											'type'  => 'textarea',
											'desc_tip'  => __( 'Please enter SMS text to send notification for', REPUTE_SMS_TEXT_DOMAIN ) ." ". $statusname ." ". __( 'order', REPUTE_SMS_TEXT_DOMAIN ).'</script>',
											'id'    => $reputesmsid.'_'. $slug .'_sms_text',
											'default' => 'Hello {BILLING_FNAME}, your order #{ORDER_NUMBER} updated with status:{ORDER_STATUS} by {SHOP_NAME}',
											'css'	=> '',
											'class'	=> 'reputesmstextarea reputesmscustomstatustextarea',
                                                                                       // 'desc' => $wc_settings_reputesms->woocommerce_admin_field_my_button_func(array('name' => $slug)),
                                                                                       'desc' => woocommerce_admin_field_my_button_func(array('name' => $slug)),
											
										);
					
					$status_cntr++;
					
				}
			}
			$get_sms_gateway_list_value = get_option( $reputesmsid. '_gateway_list' );
			$twilio_gateway_class = "hidetwiliosms";
			$nexmo_gateway_class = "hidenexmosms";
			$clickatell_gateway_class = "hideclickatellsms";
			$current_sms_gateway_name = "";
			if( $get_sms_gateway_list_value== $reputesmsid .'_twilio' ) {
				$twilio_gateway_class = "";
				$current_sms_gateway_name = __( 'Twilio', REPUTE_SMS_TEXT_DOMAIN );
			}
			else if( $get_sms_gateway_list_value== $reputesmsid .'_nexmo' ) {
				$nexmo_gateway_class = "";
				$current_sms_gateway_name = __( 'Nexmo', REPUTE_SMS_TEXT_DOMAIN );
			}
			else if( $get_sms_gateway_list_value== $reputesmsid .'_clickatell' ) {
				$clickatell_gateway_class = "";
				$current_sms_gateway_name = __( 'Clickatell', REPUTE_SMS_TEXT_DOMAIN );
			}
			
			$current_sms_gateway_name_label = '';
			if($current_sms_gateway_name!='')
			{
				$current_sms_gateway_name_label = ' ( '. $current_sms_gateway_name .' )';
			}
			
			//POPUP for wordpress Default Funcation
			add_thickbox();
			
			$settings = array(
				array( 'name' => __( 'General Settings', REPUTE_SMS_TEXT_DOMAIN ), 'type' => 'title', 'desc' => '', 'id' => 'wc_'. $reputesmsid .'_main_general_setting_section_title', ),
				
				array(
					'title' => __( 'Opt-in checkbox label', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_opt_in_checkbox_label',
					'type'  => 'text',
					'desc'  => __( 'Shows label on checkout page for buyer.', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_opt_in_checkbox_label',
					'default' => __( 'I want to Receive Order Updates by SMS', REPUTE_SMS_TEXT_DOMAIN ),
					'css'	=> '',
					'class' => 'reputesmsinput',
					'desc_tip' => true,
				),
				
				array(
					'title' => __( 'Opt-in checkbox default', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_opt_in_checkbox_default_value',
					'type'  => 'select',
					'options' => array( "0" => __( 'Unchecked' , REPUTE_SMS_TEXT_DOMAIN ), "1" => __( 'Checked', REPUTE_SMS_TEXT_DOMAIN ), ),
					'desc'  => __( 'Opt-in checkbox set default Checked/Unchecked', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_opt_in_checkbox_default_value',
					'default' => '1',
					'css'	=> '',
					'class' => 'chosen_select reputesmsselect',
					'desc_tip' => true,
				),
				array(
					'title' => __( 'Debug', REPUTE_SMS_TEXT_DOMAIN ), 
					'name'  => $reputesmsid.'_debug_logging',
					'type' => 'checkbox', 
					'desc' => sprintf( __( 'Log data, inside' , REPUTE_SMS_TEXT_DOMAIN ) .'<code>woocommerce/logs/'. $smsforwooplnm .'-%s.txt</code>', sanitize_file_name( wp_hash( $reputesmsid ) ) ),
					'id'  => $reputesmsid.'_debug_logging',
					'default' => 'no',
				),
				
				array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_general_setting_section_title'),
				
				
				array( 'name' => __( 'SMS Settings', REPUTE_SMS_TEXT_DOMAIN ), 'type' => 'title',	'desc' => '', 'id' => 'wc_'. $reputesmsid .'_main_section_title', 'class' => 'testdelta' ),
				
				array(
					'title' => __( 'Select SMS Gateway', REPUTE_SMS_TEXT_DOMAIN ), 
					'type' => 'select',
					'name' => $reputesmsid.'_gateway_list',
					'id' => $reputesmsid.'_gateway_list',
					'css'	=> '',
					'class' => 'chosen_select reputesmsselect',
					'options' => $wc_settings_reputesms->get_repute_sms_gateway_list(),
					'desc' => __( 'Select SMS gateway to send SMS notifications', REPUTE_SMS_TEXT_DOMAIN ),
					'default' => '',
					'desc_tip' => true,
				),
				
				array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_section_title'),
				
				//Twilio Section Start Here
				array( 'name' => __( 'Twilio Settings', REPUTE_SMS_TEXT_DOMAIN ), 'type' => 'title',	'desc' => __( 'Please configure your Twilio account to send SMS. If you don\'t have details with you then get it from', REPUTE_SMS_TEXT_DOMAIN) .' <a href="https://www.twilio.com/login" target="_blank">'. __( 'Twilio', REPUTE_SMS_TEXT_DOMAIN ) .'</a>', 'id' => 'wc_'. $reputesmsid .'_main_twilio_section_title', 'class' => $reputesmsid .'_main_twilio_section_title' ),
				
				
				array(
					'title' => __( 'Twillo Account SID', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_twilio_account_sid',
					'type'  => 'text',
					'desc'  => __( 'Enter Twilio Account SID.', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_twilio_account_sid',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput',
					'desc_tip' => true,
				),
				
				
				array(
					'title' => __( 'Twillo Authentication Token', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_twilio_auth_token',
					'type'  => 'text',
					'desc'  => __( 'Enter Twilio Authentication Token.', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_twilio_auth_token',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput',
					'desc_tip' => true,
				),
				
				array(
					'title' => __( 'Twilio From Number', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_twilio_from_number',
					'type'  => 'text',
					'desc'  => __( 'Enter Twilio From Number.', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_twilio_from_number',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput '. $twilio_gateway_class,
					'desc_tip' => true,
				),
				
				
				array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_twilio_section_title'),
				//Twillio Section End Here
				
				
				//Nexmo Section Start Here
				array( 'name' => __( 'Nexmo Settings', REPUTE_SMS_TEXT_DOMAIN ), 'type' => 'title',	'desc' => __( 'Please configure your Nexmo account to send SMS. If you don\'t have details with you then get it from', REPUTE_SMS_TEXT_DOMAIN ). ' <a href="https://dashboard.nexmo.com/login" target="_blank">'. __( 'Nexmo', REPUTE_SMS_TEXT_DOMAIN ). '</a>', 'id' => 'wc_'. $reputesmsid .'_main_nexmo_section_title', 'class' => $reputesmsid .'_main_nexmo_section_title' ),
				
				
				array(
					'title' => __( 'Nexmo API Key', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_nexmo_api_key',
					'type'  => 'text',
					'desc'  => __( 'Enter Nexmo API Key', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_nexmo_api_key',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput '. $nexmo_gateway_class,
					'desc_tip' => true,
				),
				
				array(
					'title' => __( 'Nexmo API Secret', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_nexmo_api_secret',
					'type'  => 'text',
					'desc'  => __( 'Enter Nexmo API Secret', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_nexmo_api_secret',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput',
					'desc_tip' => true,
				),
				
				array(
					'title' => __( 'Nexmo From Name', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_nexmo_api_from_name',
					'type'  => 'text',
					'desc'  => __( 'This name will be shown as "From" when send SMS. If you will have test account with Nexmo, It will display [Nexmo DEMO] only.', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_nexmo_api_from_name',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput',
					'desc_tip' => true,
				),
				
				array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_nexmo_section_title'),
				//Nexmo Section End Here
				
				
				
				//Clickatell Section Start Here
				array( 'name' => __( 'Clickatell Settings', REPUTE_SMS_TEXT_DOMAIN ), 'type' => 'title',	'desc' => __( 'Please configure your Clickatell account to send SMS. If you don\'t have details with you then get it from', REPUTE_SMS_TEXT_DOMAIN ). ' <a href="https://www.clickatell.com/login/" target="_blank">'. __( 'Clickatell', REPUTE_SMS_TEXT_DOMAIN ). '</a>', 'id' => 'wc_'. $reputesmsid .'_main_clickatell_section_title', 'class' => $reputesmsid .'_main_clickatell_section_title' ),
				
				
				array(
					'title' => __( 'Clickatell Username', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_clickatell_username',
					'type'  => 'text',
					'desc'  => __( 'Enter Clickatell Name', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_clickatell_username',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput '. $clickatell_gateway_class,
					'desc_tip' => true,
				),
				
				array(
					'title' => __( 'Clickatell Password', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_clickatell_password',
					'type'  => 'text',
					'desc'  => __( 'Enter Clickatell Passoword', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_clickatell_password',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput',
					'desc_tip' => true,
				),
				
				array(
					'title' => __( 'Clickatell API ID', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_clickatell_api_id',
					'type'  => 'text',
					'desc'  => __( 'Enter Clickatell API ID', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_clickatell_api_id',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput',
					'desc_tip' => true,
				),
				
				array(
					'title' => __( 'Clickatell From Name', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_clickatell_from_name',
					'type'  => 'text',
					'desc'  => __( 'Enter From Name that will set in message', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_clickatell_from_name',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput',
					'desc_tip' => true,
				),
				
				array(
					'title' => __( 'Clickatell Unicode SMS', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_clickatell_unicode_message',
					'type'  => 'checkbox',
					'desc'  => __( 'Check this if you want to use unicode SMS text', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_clickatell_unicode_message',
					'default' => '',
					'css'	=> '',
					'class' => '',
					//'desc_tip' => true,
				),
				
				array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_clickatell_section_title'),
				//Clickatell Section End Here
				
				array( 'name' => __( 'SMS Notification Settings for Administrator', REPUTE_SMS_TEXT_DOMAIN ), 'type' => 'title', 'desc' => '', 'id' => 'wc_'. $reputesmsid .'_main_admin_notification_setting_section_title', ),
				
				
				array(
					'title' => __( 'Enable / Disable Admin Notification', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_enable_disable_admin_notification',
					'type'  => 'checkbox',
					'id'    => $reputesmsid.'_enable_disable_admin_notification',
					'default' => '',
					'css'	=> '',
					'desc_tip' => true,
				),
				
				
				array(
					'title' => __( 'Enter Admin Phone Number', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_admin_number',
					'type'  => 'text',
					'desc'  => __( 'Enter admin phone number for receiving SMS on customer place orders. ( Including country code. eg.+91XXXXXXXXXX )', REPUTE_SMS_TEXT_DOMAIN ),
					'id'    => $reputesmsid.'_admin_number',
					'default' => '',
					'css'	=> '',
					'class' => 'reputesmsinput',
					'desc_tip' => true,
				),
				
				
				array(
					'title' => __( 'Admin SMS Text', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_admin_sms_text',
					'type'  => 'textarea',
					//'desc'	=> $sms_text_descritption,
					'id'    => $reputesmsid.'_admin_sms_text',
					'default' => '#{ORDER_NUMBER} is updated with Status {ORDER_STATUS} on {CURRENT_DATE} at {SHOP_NAME}',
					'css'	=> '',
					'class'	=> 'reputesmstextarea'
				),
				
				array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_admin_notification_setting_section_title'),
				
				
				array( 'name' => __( 'SMS Notification Settings for Customers', REPUTE_SMS_TEXT_DOMAIN ), 'type' => 'title', 'desc' => '', 'id' => 'wc_'. $reputesmsid .'_main_customer_notification_setting_section_title', ),
				
				$reputesmsid.'_enable_disable_customer_notification' => array(
					'title' => __( 'Enable / Disable Customer Notification', REPUTE_SMS_TEXT_DOMAIN ),
					'name'  => $reputesmsid.'_enable_disable_customer_notification',
					'type'  => 'checkbox',
					'default'=> 'yes',
					'id'    => $reputesmsid.'_enable_disable_customer_notification',
				),
				
			);
				
			$settings = @array_merge($settings,$status_lists_arr);
			
			$settings = @array_merge($settings,$settings_customer_data_arr);
			
			$settings_customer_data = array(
							array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_customer_notification_setting_section_title'),
							
							
							
							array( 'name' => __( 'View SMS History', REPUTE_SMS_TEXT_DOMAIN ), 'type' => 'title', 'desc' => '', 'id' => 'wc_'. $reputesmsid .'_main_view_sent_messages_section_title', 'desc' => '<div id="view-sms-content-main-div">
					<p style="margin:0px;padding:0px;"><span id="view_sms_loader"></span></p><p class="pleasewaittxt">'. __( 'Please wait...', REPUTE_SMS_TEXT_DOMAIN ) .'</p><div id="view-sms-content"></div></div>'. __( 'Please', REPUTE_SMS_TEXT_DOMAIN ) .' <a id="'. $reputesmsid .'_view_sms_sent_messages" href="#TB_inline?&color=cccccc&height=580&width=860&inlineId=view-sms-content-main-div" class="thickbox" title="'. __( 'SMS History', REPUTE_SMS_TEXT_DOMAIN ) .'">'. __( 'Click Here', REPUTE_SMS_TEXT_DOMAIN ) .'</a>&nbsp;'. __( 'To view all sent messages. You will get list of all sent SMS along with its status.', REPUTE_SMS_TEXT_DOMAIN ) .'.'
							),
							
							array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_view_sent_messages_section_title'),
							
							
							
							array( 'name' => __( 'Manual SMS Setting', REPUTE_SMS_TEXT_DOMAIN ).$current_sms_gateway_name_label, 'type' => 'title', 'desc' => repute_sms_woo_manual_message_setting_html(), 'id' => 'wc_'. $reputesmsid .'_main_manual_message_setting_section_title', ),
							
							array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_manual_message_setting_section_title'),
							
							array( 'name' => __( 'Bulk SMS Settings - Notify your all customers', REPUTE_SMS_TEXT_DOMAIN ).$current_sms_gateway_name_label, 'type' => 'title', 'desc' => repute_sms_woo_bulk_message_setting_html(), 'id' => 'wc_'. $reputesmsid .'_main_bulk_message_setting_section_title', ),
							
							array( 'type' => 'sectionend', 'id' => 'wc_'. $reputesmsid .'_main_bulk_message_setting_section_title'),
			);
			
			$settings = @array_merge($settings,$settings_customer_data);
			
			return apply_filters( 'wc_repute_sms_settings', $settings );
		}
	}
	
	if( !function_exists( 'repute_sms_woo_manual_message_setting_html' ) )
	{
		function repute_sms_woo_manual_message_setting_html()
		{
			$html_result = __( 'Send SMS to any defined number even who are not part of your website', REPUTE_SMS_TEXT_DOMAIN ) .'.<br /><strong>'. __( 'Note', REPUTE_SMS_TEXT_DOMAIN ) .':</strong>&nbsp;<span class="note_for_alert">'. __( 'Please do not use shortcode here. It will not parsed with its value to send manual message.', REPUTE_SMS_TEXT_DOMAIN ) .'</span>';
                        $html_result .= '<table class="form-table"><tbody>
                            <tr>
				  <th></th>
				  <td><div class="reputesms_woo_success_msg" style="color: green;"></div><div class="reputesms_woo_error_msg" style="color: red;"></div> </td>
				</tr>
			   <tr valign="top">
				  <th class="titledesc" scope="row"><label for="repute_sms_manual_message_to_number">'. __( 'Enter Number', REPUTE_SMS_TEXT_DOMAIN ) .'</label></th>
				  <td class="forminp forminp-text"><textarea class="reputesmsinput reputesmsinputtextarea" style="" id="repute_sms_manual_message_to_number" name="repute_sms_manual_message_to_number"></textarea></td>
				</tr>
				<tr valign="top">
				  <th class="titledesc" scope="row"><label for="repute_sms_manual_message_sms_text">'. __( 'Enter Notification Text', REPUTE_SMS_TEXT_DOMAIN ) .'</label></th>
				  <td class="forminp forminp-textarea"><textarea class="reputesmstextarea" style="" id="repute_sms_manual_message_sms_text" name="repute_sms_manual_message_sms_text"></textarea></td>
				</tr>
				<tr valign="top">
					<th class="titledesc" scope="row">&nbsp;</th>
					<td class="forminp forminp-textarea"><a class="button-primary" id="sendmanualmessage">'. __( 'Send Manual SMS', REPUTE_SMS_TEXT_DOMAIN ) .'</a><span id="loading_check_send_message" class="loading_check_small send_manual_msg"></span></td>
				</tr>
		    </tbody></table>';
			
			return $html_result;
		}
	}
        
        if( !function_exists( 'woocommerce_admin_field_my_button_func' ) )
	{ 
		function woocommerce_admin_field_my_button_func($status_name)
		{       
                        $output = "<button id='repute_sms_shortcode_add_btn_".$status_name['name']."' class='repute_sms_shortcode_add_btn' type='button' data-id='jhkjkhj'>
                        ".__('Shortcodes',  REPUTE_SMS_TEXT_DOMAIN)." <img src='".REPUTE_SMS_IMAGES_URL."/down-arrow.png' align='absmiddle'>
                        </button>";
                                    $output.= '<div id="repute_sms_shortcode_div_'.$status_name['name'].'" class="repute_sms_shortcode_div" style="display: none;">'.__('Your Site Name',REPUTE_SMS_TEXT_DOMAIN) .'<code> <b>{SHOP_NAME}</b></code>
                                           <br/>
                                '.__('Order Number',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{ORDER_NUMBER}</b></code><br/>
                                '.__('Order Status',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{ORDER_STATUS}</b></code><br/>
                                '.__('Order Amount',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{ORDER_AMOUNT}</b></code><br/>
                                '.__('Order Date',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{ORDER_DATE}</b></code><br/>
                                '.__('Order Items',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{ORDER_ITEMS}</b></code><br/>
                                '.__('First Name',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{BILLING_FNAME}</b></code><br/>
                                '.__('Last Name',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{BILLING_LNAME}</b></code><br/>
                                '.__('Billing Email',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{BILLING_EMAIL}</b></code><br/>
                                '.__('Current Date',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{CURRENT_DATE}</b></code><br/>
                                '.__('Current Time',REPUTE_SMS_TEXT_DOMAIN) .'<code><b>{CURRENT_TIME}</b></code><br/>
                        </div>';
                        return $output;
		}
	}
	
	if( !function_exists( 'repute_sms_woo_bulk_message_setting_html' ) )
	{
		function repute_sms_woo_bulk_message_setting_html()
		{
			$html_result = __( 'Bulk SMS will send SMS to all customers who have purchsed from your shop. It will use their billing phone number to send notification', REPUTE_SMS_TEXT_DOMAIN ) .'<br /><strong>'. __( 'Note', REPUTE_SMS_TEXT_DOMAIN ) .':</strong>&nbsp;<span class="note_for_alert">'. __( 'Please do not use shortcode here. It will not parsed with its value to send bulk SMS.', REPUTE_SMS_TEXT_DOMAIN ) .'</span><table class="form-table"><tbody>
			   
<tr><th></th><td><div class="reputesms_woo_bulk_success_msg" style="color: green;"></div><div class="reputesms_woo_bulk_error_msg" style="color: red;"></div></td></tr>
<tr valign="top">
				  <th class="titledesc" scope="row"><label for="repute_sms_bulk_message_sms_text">'. __( 'Enter notification text for Bulk SMS', REPUTE_SMS_TEXT_DOMAIN ) .'</label></th>
				  <td class="forminp forminp-textarea"><textarea class="reputesmstextarea" style="" id="repute_sms_bulk_message_sms_text" name="repute_sms_bulk_message_sms_text"></textarea></td>
				</tr>
				<tr valign="top">
					<th class="titledesc" scope="row">&nbsp;</th>
					<td class="forminp forminp-textarea"><a class="button-primary" id="sendbulkmessage">'. __( 'Send Bulk SMS', REPUTE_SMS_TEXT_DOMAIN ) .'</a><span id="loading_check_bulk_message" class="loading_check_small send_bulk_msg"></span></td>
				</tr>
		    </tbody></table>';
			
			return $html_result;
		}
	}
	
	if( !function_exists( 'repute_sms_woo_replace_shortcode_variable' ) )
	{
		function repute_sms_woo_replace_shortcode_variable( $content, $order )	{
			if( !$content || !is_object($order))
				return;
			global $wc_settings_reputesms;
			$order_id = $order->id;
			
			$order_custom_fields = get_post_custom($order_id);
			$current_date_time = current_time( 'timestamp' );
			
			if( preg_match("/{SHOP_NAME}/i", $content) )
			{
				$SHOP_NAME = get_option( "blogname" );
				$content = @str_replace( "{SHOP_NAME}", $SHOP_NAME, $content );
			}
			
			if( preg_match("/{ORDER_NUMBER}/i", $content) )
			{
				$ORDER_NUMBER = isset( $order_id ) ? $order_id : "";
				$content = @str_replace( "{ORDER_NUMBER}", $ORDER_NUMBER, $content );
			}
			
			if( preg_match("/{ORDER_DATE}/i", $content) )
			{
				$order_date_format = get_option( "date_format" );
				$ORDER_DATE = date_i18n($order_date_format, strtotime( $order->order_date ) );
				$content = @str_replace( "{ORDER_DATE}", $ORDER_DATE, $content );
			}
			
			if( preg_match("/{ORDER_STATUS}/i", $content) )
			{
				$ORDER_STATUS = @ucfirst($order->status);
				$content = @str_replace( "{ORDER_STATUS}", $ORDER_STATUS, $content );
			}
			
			if( preg_match("/{ORDER_ITEMS}/i", $content) )
			{
				$order_items = $order->get_items( apply_filters( "woocommerce_admin_order_item_types", array( "line_item" ) ) );
				$ORDER_ITEMS = "";
				if( count($order_items) )
				{
					$item_cntr = 0;
					foreach ( $order_items as $order_item ) {
						if($order_item["type"]=="line_item")
						{
							if($item_cntr==0)
								$ORDER_ITEMS = $order_item["name"];
							else 
								$ORDER_ITEMS .= ", ". $order_item["name"];
							$item_cntr++;
						}
					}
				}
				
				$content = @str_replace( "{ORDER_ITEMS}", $ORDER_ITEMS, $content );
			}
			
			if( preg_match("/{BILLING_FNAME}/i", $content) )
			{
				$BILLING_FNAME = $order_custom_fields["_billing_first_name"][0];
				$content = @str_replace( "{BILLING_FNAME}", $BILLING_FNAME, $content );
			}
			
			if( preg_match("/{BILLING_LNAME}/i", $content) )
			{
				$BILLING_LNAME = $order_custom_fields["_billing_last_name"][0];
				$content = @str_replace( "{BILLING_LNAME}", $BILLING_LNAME, $content );
			}
			
			if( preg_match("/{BILLING_EMAIL}/i", $content) )
			{
				$BILLING_EMAIL = $order_custom_fields["_billing_email"][0];
				$content = @str_replace( "{BILLING_EMAIL}", $BILLING_EMAIL, $content );
			}
			
			if( preg_match("/{ORDER_AMOUNT}/i", $content) )
			{
				$ORDER_AMOUNT = $order_custom_fields["_order_total"][0];
				$content = @str_replace( "{ORDER_AMOUNT}", $ORDER_AMOUNT, $content );
			}
			
			if( preg_match("/{CURRENT_DATE}/i", $content) )
			{
				$wp_date_format = get_option( "date_format" );
				$CURRENT_DATE = date_i18n($wp_date_format, $current_date_time );
				$content = @str_replace( "{CURRENT_DATE}", $CURRENT_DATE, $content );
			}
			
			if( preg_match("/{CURRENT_TIME}/i", $content) )
			{
				$wp_time_format = get_option( "time_format" );
				$CURRENT_TIME = date_i18n($wp_time_format, $current_date_time );
				$content = @str_replace( "{CURRENT_TIME}", $CURRENT_TIME, $content );
			}
			
			return $content;
		}
	}
	
	if( !function_exists( 'repute_sms_woo_get_sms_history_list' ) )
	{
		function repute_sms_woo_get_sms_history_list()
		{
			global $wpdb, $wc_settings_reputesms, $reputesmsid, $reputesmslabel;
			
			$RECORDPERPAGE = "10";
			if(isset($_REQUEST['paged']) && $_REQUEST['paged']>0)
			{
				$page = $_REQUEST['paged'];
			}else {
				$page = 0;
			}
			
			$fullresult = $wpdb->get_row( "SELECT count(id) as totalrow FROM ".$wpdb->prefix."repute_sms_log_history" );
			$full_total_log = $fullresult->totalrow;
			
			$total_page = ceil($full_total_log/$RECORDPERPAGE);
			
			$get_sms_logs = $wpdb->get_results(($page*$RECORDPERPAGE),$RECORDPERPAGE);
			$startfrom = $page*$RECORDPERPAGE;
			
			$get_logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix."repute_sms_log_history ORDER BY id DESC LIMIT %d,%d", $startfrom, $RECORDPERPAGE ) );
			
			$added_date_format = get_option( "date_format" );
			$added_time_format = get_option( "time_format" );
			
			$content = "";
			$cntr=$startfrom+1;
			$content = '';
			
			if($full_total_log>0)
			{
				$content .= '<table class="smsstautspaginglink">
								<tr>';
								if($page>0) {
									$content .= '<td>
													<a onclick="sms_gateway_show_list('. ($page-1) .');">'. __( 'Previous', REPUTE_SMS_TEXT_DOMAIN ) .'</a>
												 </td>';
								}
								if($total_page>$page+1) {
									$add_separator = '';
									if($page>0) {
										$add_separator = '<td>&nbsp;|&nbsp;</td>';
									}
									$content .= $add_separator. '
												<td>
													<a onclick="sms_gateway_show_list('. ($page+1) .');">'. __( 'Next', REPUTE_SMS_TEXT_DOMAIN ) .'</a>
												</td>';
								}
				$content .= '</tr>';
				$content .= '</table>';
			}
			
			if( count( $get_logs )>0 )
			{
				$content .= '<table class="data_sms_content">
								<tr>
									<th width="8%">'. __( 'Sr No.', REPUTE_SMS_TEXT_DOMAIN ) .'</th>
									<th width="20%">'. __( 'Date Sent', REPUTE_SMS_TEXT_DOMAIN ) .'</th>
									<th width="10%">'. __( 'Sent to', REPUTE_SMS_TEXT_DOMAIN ) .'</th>
									<th width="35%">'. __( 'Message', REPUTE_SMS_TEXT_DOMAIN ) .'</th>
									<th width="14%">'. __( 'Gateway', REPUTE_SMS_TEXT_DOMAIN ) .'</th>
									<th width="14%">'. __( 'Status', REPUTE_SMS_TEXT_DOMAIN ) .'</th>
								</tr>';
				foreach ($get_logs as $get_log) {
					$sms_histor_id = $get_log->id;
					$sms_gateway = $get_log->sms_gateway;
					if( $sms_gateway=="t" ) { $log_sms_gateway = __( 'Twilio', REPUTE_SMS_TEXT_DOMAIN ); }
					if( $sms_gateway=="c" ) { $log_sms_gateway = __( 'Clickatell', REPUTE_SMS_TEXT_DOMAIN ); }
					if( $sms_gateway=="n" ) { $log_sms_gateway = __( 'Nexmo', REPUTE_SMS_TEXT_DOMAIN ); }
					
					$status = $get_log->delivered_flag;
					$status_delivered = "";
					if( $sms_gateway!="s" )
					{
						if($status>0) 
						{ 
							$status_delivered = '<span class="smstxtstatus">'.__( 'Delivered', REPUTE_SMS_TEXT_DOMAIN ) .'</span>'; 
						} 
						else 
						{ 
							$status_delivered = '<span id="current_sms_status_'. $sms_histor_id .'"><span id="loading_check_status'. $sms_histor_id .'" class="loading_check_small"></span><a class="'. $reputesmsid .'_current_sms_status" id="current_sms_status_a_'. $sms_histor_id .'" onclick="check_sms_current_status('. $sms_histor_id .');"></span>'. __( 'Check', REPUTE_SMS_TEXT_DOMAIN ) .'</a>'; 
						}
					}
					$added_date_time = date_i18n($added_date_format." ".$added_time_format, strtotime( $get_log->added_date_time ) );
					$messagetext = $get_log->messagetext;
					
					$content .= '<tr>
									<td>'. $cntr .'</td>
									<td>'. $added_date_time .'</td>
									<td><span>'. $get_log->to_number .'</span></td>
									<td class="smsmessagebody">'. $messagetext .'</td>
									<td>'. $log_sms_gateway .'</td>
									<td>'. $status_delivered .'</td>
							   ';
					$cntr++;
				}
				$content .= '</table>';
			}else {
				$stylefornotrecord = '';
				if( $full_total_log<1 ) { $stylefornotrecord = 'margin:30px 0;'; }
				$content .= '<table class="nomessagefound_content" style="'. $stylefornotrecord .'">
								<tr>
									<td>'. __( 'No SMS History Found.', REPUTE_SMS_TEXT_DOMAIN ) .'</td>
								</tr>
							</table>';
			}
			
			if($full_total_log>0)
			{
				$content .= '<table class="smsstautspaginglink">
								<tr>';
								if($page>0) {
									$content .= '<td>
													<a onclick="sms_gateway_show_list('. ($page-1) .');">'. __( 'Previous', REPUTE_SMS_TEXT_DOMAIN ) .'</a>
												 </td>';
								}
								if($total_page>$page+1) {
									$add_separator = '';
									if($page>0) {
										$add_separator = '<td>&nbsp;|&nbsp;</td>';
									}
									$content .= $add_separator. '
												<td>
													<a onclick="sms_gateway_show_list('. ($page+1) .');">'. __( 'Next', REPUTE_SMS_TEXT_DOMAIN ) .'</a>
												</td>';
								}
				$content .= '</tr>';
				$content .= '</table>';
			}
			
			return $content;
		}
	}
	
	
?>