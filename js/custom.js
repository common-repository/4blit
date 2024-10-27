jQuery(document).ready(function($) {
    $("#4blit_verify").click(function() { 
	$("#4blit_verify_result").html('<i>Connecting...please wait !</i>');

	var apiKey = $("#4blit_api_key").val();

	jQuery.ajax({
	    method: "POST",
	    dataType : "json",
	    url : "https://www.4blitsiena.com/rest/verify",
	    data: {key: apiKey},
	    success: function(response) {
		$("#4blit_verify_result").html(response.message);
            },
	    error: function(request, status, error) {
		$("#4blit_verify_result").html('<b>Error '+error+'</b>');
	    }
        });
    });
});