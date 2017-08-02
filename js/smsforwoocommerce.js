jQuery(document).ready(function() { 
	var repute_sms = "repute_sms";
	var rpt_content_separator = '<div class="rpt_content_seperator"></div>';
	jQuery(".form-table:first").after('<div class="rpt_content_seperator"></div>');
	jQuery(".form-table").addClass('rpt_sms_section');
	jQuery("#"+ repute_sms +"_view_sms_sent_messages").parent().prev().prev().before(rpt_content_separator);
	jQuery(".reputesms_woo_success_msg").prev().prev().before(rpt_content_separator);
	jQuery(".reputesms_woo_bulk_success_msg").prev().prev().before(rpt_content_separator);
	
	var twiliofromnumber_field_obj = jQuery("#"+ repute_sms +"_twilio_from_number");
	var twilio_parameter = get_parent_html_from_field(twiliofromnumber_field_obj);
	
	if( twiliofromnumber_field_obj.hasClass( "hidetwiliosms" ) )
	{
		hide_sms_previous_heading(twilio_parameter, "hide");
	}
	
	var nexmoapikey_field_obj = jQuery("#"+ repute_sms +"_nexmo_api_key");
	var nexmo_parameter = get_parent_html_from_field(nexmoapikey_field_obj);
	
	if( nexmoapikey_field_obj.hasClass( "hidenexmosms" ) )
	{
		hide_sms_previous_heading(nexmo_parameter, "hide");
	}
	
	var clickatellname_field_obj = jQuery("#"+ repute_sms +"_clickatell_username");
	var clickatell_parameter = get_parent_html_from_field(clickatellname_field_obj);
	
	if( clickatellname_field_obj.hasClass( "hideclickatellsms" ) )
	{
		hide_sms_previous_heading(clickatell_parameter, "hide");
	}
	
	var smsglobalname_field_obj = jQuery("#"+ repute_sms +"_sms_global_username");
	var smsglobal_parameter = get_parent_html_from_field(smsglobalname_field_obj);
	
	if( smsglobalname_field_obj.hasClass( "hidesmsglobalsms" ) )
	{
		hide_sms_previous_heading(smsglobal_parameter, "hide");
	}
	
	
	jQuery("#"+ repute_sms +"_gateway_list").change(function() {
		if(jQuery(this).val()== repute_sms +"_twilio")
		{
			show_sms_previous_heading(twilio_parameter, "fadeIn");
		}else {
			hide_sms_previous_heading(twilio_parameter, "hide");
		}
		
		if(jQuery(this).val()== repute_sms +"_nexmo")
		{
			show_sms_previous_heading(nexmo_parameter, "fadeIn");
		}else {
			hide_sms_previous_heading(nexmo_parameter, "hide");
		}
		
		if(jQuery(this).val()== repute_sms +"_clickatell")
		{
			show_sms_previous_heading(clickatell_parameter, "fadeIn");
		}else {
			hide_sms_previous_heading(clickatell_parameter, "hide");
		}
		
		if(jQuery(this).val()== repute_sms +"_sms_global")
		{
			show_sms_previous_heading(smsglobal_parameter, "fadeIn");
		}else {
			hide_sms_previous_heading(smsglobal_parameter, "hide");
		}
		
	});
	
	var fieldids = "";
	var fieldid = "";
	
	var manual_msg_txtarea = jQuery("#"+ repute_sms +"_manual_message_sms_text");
	var manual_msg_txtarea_label = repute_sms +'_manual_message_sms_text_cntr';
	manual_msg_txtarea.after('<span class="smscounter" id="'+ manual_msg_txtarea_label +'"></span>');
	SMSWooTextLimit( manual_msg_txtarea, manual_msg_txtarea_label );
	manual_msg_txtarea.keyup(function() { 
		SMSWooTextLimit( manual_msg_txtarea, manual_msg_txtarea_label );
	});
	
	var bulk_msg_txtarea = jQuery("#"+ repute_sms +"_bulk_message_sms_text");
	var bulk_msg_txtarea_label = repute_sms +'_bulk_message_sms_text_cntr';
	bulk_msg_txtarea.after('<span class="smscounter" id="'+ bulk_msg_txtarea_label +'"></span>');
	SMSWooTextLimit( bulk_msg_txtarea, bulk_msg_txtarea_label );
	bulk_msg_txtarea.keyup(function() { 
		SMSWooTextLimit( bulk_msg_txtarea, bulk_msg_txtarea_label );
	});
	
	jQuery("#"+ repute_sms +"_view_sms_sent_messages").click(function() { 
		sms_gateway_show_list(0);
	});
	
	
	jQuery("#repute_sms_manual_message_to_number").keypress( function( event ){
		if(event.keyCode=="13") {
			jQuery("#sendmanualmessage").click();
			return false;
		}
	});	
	
	jQuery("#sendmanualmessage").click(function() { 
		var to_number_obj = jQuery("#"+ repute_sms +"_manual_message_to_number");
		var to_text_obj = jQuery("#"+ repute_sms +"_manual_message_sms_text");
		
		var to_number = encodeURIComponent(to_number_obj.val());
		var to_text = encodeURIComponent(to_text_obj.val());
		if(to_number=='' || to_text=='')
		{
			return false;
		}
		jQuery("#loading_check_send_message").css( 'display', 'inline-block' );
		
		jQuery.ajax({
			type:"POST",
			url:ajaxurl,
			data:"action=sms_woo_send_manual_message&to_number="+to_number+"&to_text="+to_text,
			success:function(html){
				jQuery("#loading_check_send_message").hide();
				if(html!="")
				{
					var chkreputesms = html.split("<reputesms>");
					if(chkreputesms[1]!="")
					{
						var chkdata = chkreputesms[1].split("|");
						if(chkdata[0]==1)
						{
							to_number_obj.val('');
							to_text_obj.val('');
							SMSWooTextLimit( manual_msg_txtarea, manual_msg_txtarea_label );
							jQuery(".reputesms_woo_success_msg").html(chkdata[1]);
							jQuery(".reputesms_woo_success_msg").show();
							jQuery(".reputesms_woo_success_msg").delay("4000").fadeOut("300");
						}else {
							jQuery(".reputesms_woo_error_msg").html(chkdata[1]);
							jQuery(".reputesms_woo_error_msg").show();
							jQuery(".reputesms_woo_error_msg").delay("4000").fadeOut("300");
						}
					}
				}
			}
		});
	});
        
        jQuery('.repute_sms_shortcode_add_btn').click(function(){
            var id = jQuery(this).attr('id');
              
          
             var new_id =  id.replace("add_btn", "div"); 
            
            jQuery('#'+new_id).toggle();
            jQuery('.repute_sms_shortcode_div').not('#'+new_id).hide();
           
        });
	
	jQuery("#sendbulkmessage").click(function() { 
		var bulk_sms_txt_obj = jQuery("#"+ repute_sms +"_bulk_message_sms_text");
		var bulk_sms_text = encodeURIComponent(bulk_sms_txt_obj.val());
		if(bulk_sms_text=='') {
			return false;
		}
		
		if( !confirm("Are you sure you want to send notification to ALL Customers?") ) {
			return false;
		}
		
		jQuery("#loading_check_bulk_message").css( 'display', 'inline-block' );
		jQuery.ajax({
			type:"POST",
			url:ajaxurl,
			data:"action=sms_woo_send_bulk_message&bulk_sms_text="+bulk_sms_text,
			success:function(html){
				jQuery("#loading_check_bulk_message").hide();
				if(html!="")
				{
					var chkreputesms = html.split("<reputesms>");
					if(chkreputesms[1]!="")
					{
						var chkdata = chkreputesms[1].split("|");
						if(chkdata[0]==1)
						{
							bulk_sms_txt_obj.val('');
							SMSWooTextLimit( bulk_msg_txtarea, bulk_msg_txtarea_label );
							jQuery(".reputesms_woo_bulk_success_msg").html(chkdata[1]);
							jQuery(".reputesms_woo_bulk_success_msg").show();
							jQuery(".reputesms_woo_bulk_success_msg").delay("4000").fadeOut("300");
						}else {
							jQuery(".reputesms_woo_bulk_error_msg").html(chkdata[1]);
							jQuery(".reputesms_woo_bulk_error_msg").show();
							jQuery(".reputesms_woo_bulk_error_msg").delay("4000").fadeOut("300");
						}
					}
				}
			}
		});
	});
	
});

function get_parent_html_from_field(obj)
{
	return obj.parent().parent().parent().parent();
}

function hide_sms_previous_heading(obj, flag)
{
	if(flag=="hide") {
		obj.prev().hide();
		obj.prev().prev().hide();
		obj.hide();
	}else {
		obj.prev().fadeOut();
		obj.prev().prev().fadeOut();
		obj.fadeOut();
	}
}

function show_sms_previous_heading(obj, flag)
{
	obj.prev().fadeIn();
	obj.prev().prev().fadeIn();
	obj.fadeIn();
}

function sms_gateway_show_list(paged)
{
	jQuery("#view-sms-content").empty();
	jQuery("#view_sms_loader").show();
	jQuery(".pleasewaittxt").show();
	jQuery.ajax({
		type:"POST",
		url:ajaxurl,
		data:"action=view_sms_sent_messages&paged="+paged,
		success:function(html){
			jQuery("#view_sms_loader").hide();
			jQuery(".pleasewaittxt").hide();
			jQuery("#view-sms-content").html(html);
		}
	});
}

function check_sms_current_status(id)
{
	jQuery("#loading_check_status" + id).css('display','inline-block');
	jQuery("a#current_sms_status_a_"+ id).css('display','none');
	jQuery.ajax({
		type:"POST",
		url:ajaxurl,
		data:"action=check_sms_current_status&sms_history_id="+id,
		success:function(html){
			jQuery("#loading_check_status" + id).hide();
			jQuery("#current_sms_status_"+ id).html(html);
			jQuery("#current_sms_status_a_"+ id).html('');
		}
	});
}

function SMSWooTextLimit(limitField, limitCount)
{
	var limitNum = 160;
	if(limitField && document.getElementById(limitCount))
	{
		if (limitField.val().length > limitNum) 
		{
			limitField.val(limitField.val().substring(0, limitNum));
		}
		else
		{
			document.getElementById(limitCount).innerHTML = limitNum - limitField.val().length;
		}
	}
}