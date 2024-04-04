						function checkPassword()
						{
							var old_passval = $("#old_password").val();
							var new_passval = $("#new_password").val();
							var new_pass_bisval = $("#new_password_bis").val();
							
							var old_passlen = old_passval.length;
							var new_passlen = new_passval.length;
							var new_pass_bislen = new_pass_bisval.length;
							
							if (old_passlen < 8)
								$("#old_password").addClass("error");
							else
								$("#old_password").removeClass("error");
									
							if (new_passlen < 8)
								$("#new_password").addClass("error");
							else
								$("#new_password").removeClass("error");
								
							if (new_pass_bislen < 8)
								$("#new_password_bis").addClass("error");
							else
								$("#new_password_bis").removeClass("error");
							
							if (old_passlen < 8 || new_passlen < 8 || new_pass_bislen < 8)
							{
								$("#error_msg").text("Le nombre de caractères pour les champs mot" + 
									" de passe doit etre superieur ou egal a 8...");
								return false;
							}
							
							if (new_passval != new_pass_bisval)
							{
								$("#error_msg").text("Le nouveau mot de passe doit etre identique dans les deux champs");
								return false;
							}
							return true;
						}
				
						$(document).ready(function() 
						{
							$("#change_password_form").submit(function() { return false; });
							
							$("#send").on("click", function(){
								
								var error = false;
								
								var loginval  = $("#login").val();
								var loginlen    = loginval.length;
								
								if(loginlen <= 0) 
								{
									$("#login").addClass("error");
									$("#error_msg").text("Le nom d'utilisateur ne peut être vide...");
									error = true
								}
								else if(loginlen >= 4){
									$("#login").removeClass("error");
								}
								
								
								var is_pass_valid = checkPassword();
								
								if(loginlen >= 4 &&  is_pass_valid == true) {
									$("#error_msg").text("");
									$("#send").replaceWith("<em>Sending...</em>");
									
									$.ajax({
										type: 'POST',
										url: 'lib/change_password.php',
										data: $("#change_password_form").serialize(),
										success: function(data) {
											if(data == "true") {
												$("#change_password_form").fadeOut("fast", function(){
													$(this).before("<p><strong>Success! Le mot de passe a été changé avec succès!</strong></p>");
												});
											}
											
											else {
												$("#change_password_form").fadeOut("fast", function(){
													$(this).before("<p><strong>Erreur!"+ data +"</strong></p>");
													setTimeout("document.location.reload(true)", 3000);
												});
											}
										}
									});
								}
								
								});
								
							$("#send_change_password").on("click", function(){
								
								var error = false;
								
								var loginval  = $("#login").val();
								var loginlen    = loginval.length;
								
								if(loginlen <= 0) 
								{
									$("#login").addClass("error");
									$("#error_msg").text("Le nom d'utilisateur ne peut être vide...");
									error = true
								}
								else if(loginlen >= 4){
									$("#login").removeClass("error");
								}
								
								
								var is_pass_valid = checkPassword();
								
								if(loginlen >= 4 &&  is_pass_valid == true) {
									$("#error_msg").text("");
									$("#send_change_password").replaceWith("<em>Sending...</em>");
									
									$.ajax({
										type: 'POST',
										url: 'lib/change_password.php',
										data: $("#change_password_form").serialize(),
										success: function(data) {
											if(data == "true") {
												$("#change_password_form").fadeOut("fast", function(){
													$(this).before("<p><strong>Success! Le mot de passe a été changé avec succès!</strong></p>");
												});
											}
											
											else {
												$("#change_password_form").fadeOut("fast", function(){
													$(this).before("<p><strong>Erreur!"+ data +"</strong></p>");
													setTimeout("document.location.reload(true)", 3000);
												});
											}
										}
									});
								}
								
								});
						});