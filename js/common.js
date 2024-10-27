jQuery(document).ready(function($) {
    $("#4blit_connect").click(function() { 
	var apiKey = $("#4blit_api_key").val();
	$("#4blit_connect_result").html('<i>Connecting...please wait !</i>');

        $.post(ajax_object.ajax_url, {
		action: 'connect',
		apiKey: apiKey
	    },function(data) {
    		if (data) {
        	    $("#4blit_connect_result").html(data);
		}
    	    });
    });
});