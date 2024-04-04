function validateEmail(email) { 
	var reg = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return reg.test(email);
}

$(document).ready(function() 
{
	$(".modalbox").fancybox();
	$("#c_add").submit(function() { return false; });
	
	$("#c_send").on("click", function()
	{
		var mailval = $("#c_email").val();
				
		if(!validateEmail(mailval))
			$("#c_email").addClass("error");
		else
			$("#c_email").removeClass("error");
		
					
		if(validateEmail(mailval)) {
			// first we hide the submit btn so the user doesnt click twice
			$("#c_send").replaceWith("<em>sending...</em>");
						
			$.ajax({
				type: 'POST',
				url: 'lib/admin_register_commerciaux.php',
				data: $("#c_add").serialize(),
				success: function(data) 
				{
					if(data == "true") 
					{
						$("#c_add").fadeOut("fast", function(){
							$(this).before("<p><strong>Success! The label has been created successfully.</strong></p>");
							setTimeout("document.location.reload(true)", 2000);
						});
					}
					else
					{
						$("#c_add").fadeOut("fast", function(){
							$(this).before("<p><strong>" + data + "</strong></p>");
							setTimeout("document.location.reload(true)", 2000);
							//setTimeout("$.fancybox.close()", 1000);
							//setTimeout("document.location.reload(true)", 1000);
						});
					}
				}
			});
		}
	});
});