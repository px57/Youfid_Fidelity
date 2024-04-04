/// On verifie d'abord la presence d'un logo et on genere la miniature
if (logo_path != "")
{
	$('#divdisplay2').fadeIn(); 
	$('#divdisplay2').html('<img src="' + logo_path + '" width=200px height=200px border=3>');
}

function display_url_picture(current)
{
	var url = current.value;
								
	$('#divdisplay2').fadeIn(); 
	$('#divdisplay2').html('<img src="' + url + '" width=200px height=200px border=3>'); 
}
							
function display_path_picture(input)
{
	if (input.files && input.files[0]) {
	    var reader = new FileReader();
							
	    reader.onload = function (e) {
		    /*$('#blah')
		    .attr('src', e.target.result)
		    .width(100)
		    .height(100);*/
								                        
		   	$('#divdisplay2').fadeIn(); 
			$('#divdisplay2').html('<img src="' + e.target.result + '" width=200px height=200px border=3>');   
		};
							
		reader.readAsDataURL(input.files[0]);						            
	}
}
