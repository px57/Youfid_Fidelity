<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<?php
		require_once("include/database.class.php");
		require_once("include/session.class.php");
		$session = new Session();
		
		require_once("lib/Logger.class.php");
		require_once("lib/db_functions.php");
		
		if (!isset($logger))
			$logger = new Logger('./logs/');

		require_once('dev/service/dbLogInfo.php');

		mysql_connect("$host", "$username", "$password")or die("cannot connect");
		mysql_select_db("$db_name")or die("cannot select DB");
		
		function doLog($message) {
			global $logger;

			if (isset($logger))
				$logger->log('debug', 'validate_physique', $message, Logger::GRAN_MONTH);
		}

		/// table name
		$tbl_mbuser="mobileuser";
		$mbuser = FALSE;
		
		////////////////////////////////////////
		/// Methods
		
		function get_mobi_user($qrcode)
		{
			global $tbl_mbuser;
			
			$query = "SELECT * FROM $tbl_mbuser WHERE `qr_code`='"
				. mysql_real_escape_string($qrcode)
				. "'";
				
			$result = mysql_query($query);
			
			doLog($query);
			//echo($query);
			
			if ($result == FALSE || !mysql_num_rows($result))
				return FALSE;
			
			return mysql_fetch_array($result);
		}
		
		function valid_acount($qr_code)
		{
			global $tbl_mbuser;
			
			$query = "UPDATE $tbl_mbuser SET `status`='1' WHERE `qr_code`='"
				. mysql_real_escape_string($qr_code)
				. "'";
				
			$result = mysql_query($query);
		}
		
		/// GET
		if (isset($_GET['qr_code']) && !empty($_GET['qr_code']))
			$mbuser = get_mobi_user($_GET['qr_code']);
		
		/// Le compte n'existe pas ou a déja ete validé
		// || $mbuser['status'] == 1)
		/*if ($mbuser == FALSE)
		{
			header("location:http://www.youfid.fr");
		}*/
		
		/*if ($mbuser && $mbuser['status'] == 0)
		{
			valid_acount($_GET['qr_code']);
			header("location:http://www.youfid.fr");
		}*/
		/// ELSE on charge la page de confimation des physiques
		if (isset($_GET['qr_code']))
			$_SESSION['qr_code'] = $_GET['qr_code'];
		
	?>

	<head>
			<meta http-equiv="content-type" content="text/html; charset=utf-8" />
			<link rel="shortcut icon" type="image/ico" href="http://www.datatables.net/favicon.ico" />
	
			<title>YouFid - Activation d'un compte client</title>
			<style type="text/css" title="currentStyle">
				
				@import "./static/css/change_password.css";
			
				@import "./static/css/jquery-tooltip/tooltipsy.css";
				@import "./static/css/styles.css";
				@import "./static/css/easyslider.screen.css";
				@import "./static/css/flexigrid/flexigrid.css";
				@import "./static/css/jquery-ui/custom/jquery-ui-1.8.24.custom.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.accordion.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.all.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.autocomplete.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.base.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.button.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.core.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.datepicker.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.dialog.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.progressbar.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.resizable.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.selectable.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.slider.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.tabs.css";
				@import "./static/css/jquery-ui/custom/jquery.ui.theme.css";
			</style>
			<script type="text/javascript" src="./static/js/jquery/jquery-1.8.2.min.js"></script>
			<script type="text/javascript" src="./static/js/jquery/jquery-ui-1.8.24.custom.min.js"></script>
			<script type="text/javascript" language="javascript" src="./js/jquery-ui-timepicker-addon-0.6.2.js"></script>
			<script type="text/javascript" src="./static/js/jquery/jquery.cookie.js"></script>
			<script type="text/javascript" language="javascript" src="./js/disapear2.js"></script>
			<!--<script type="text/javascript" language="javascript" src="./js/jquery-1.7.2.min.js"></script>-->
			<script type="text/javascript" language="javascript" src="./js/jquery.dataTables.js"></script>
			<script type="text/javascript" src="./static/js/jquery-tooltip/tooltipsy.min.js"></script>
			<script type="text/javascript" src="./static/js/easySlider1.7.js"></script>
			<!--script type="text/javascript" src="./static/js/flexigrid/flexigrid.js"></script-->
		</head>
		
		<body>
			<div id="general" class="align-center ui-corner-all shadow">
	            <div id="header" class="ui-corner-top">
	                <div id="title">
						<img height="100%" width="20%" src=<?php echo('static/logos/logo.png'); ?>></img>
					</div>
	            </div>
	            <div id="main">
					<div class="clear"></div>
					<div id="content">
						
						<div id="change_password_content left" style="margin:10px 30px">
							
							<h2>Activation de votre compte YouFid</h2>
							
							<br />
							<div >
								<p>Veuillez cliquer sur le bouton "Valider" pour proc&eacute;der a l'activation de votre compte YouFid</p>
							</div>
							
							<form id="change_password_form" name="change_password_form" action="#" method="post">
								<button style="margin-top: 10px; margin-bottom: 10px; height: 25px;" id="send">Valider</button>
							</form>
							
							<br/>
							<a href="http://www.youfid.fr">Se rendre sur le site de YouFid</a>
							
						</div>
		
		<script>
			
			$(document).ready(function() 
			{
				$("#change_password_form").submit(function() { return false; });
								
				$("#send").on("click", function()
				{
					var error = true;
					
					if (error == true)
					{
						$("#error_msg").text("");
						$("#send").replaceWith("<em>Sending...</em>");
										
						$.ajax({
							type: 'POST',
							url: 'lib/validate_other.php',
							data: $("#change_password_form").serialize(),
							success: function(data) 
							{
								data = data.trim();
								console.log("DATA", data);
								console.log("DATA TYPE", typeof(data));
								if(data == 'true') 
								{
									$("#change_password_form").fadeOut("fast", function(){
										$(this).before("<p><strong>Bienvenue chez YouFID, Votre compte est activ&eacute;!<br/> T&eacute;l&eacute;chargez gratuitement l’application YouFID depuis votre mobile ou munissez-vous d’une carte YouFID chez un commer&ccedil;ant partenaire.<br/>Vous pouvez d&egrave;s &agrave; pr&eacute;sent cumuler des points et b&eacute;n&eacute;ficier de promotions sp&eacute;ciales aupr&egrave;s de vos commerces favoris.</strong></p>");
									});
								}												
								else 
								{
									$("#change_password_form").fadeOut("fast", function()
									{
										$(this).before("<p><strong>Une erreur s'est produite. Si le problème persiste, contactez un administrateur YouFid.</strong></p>");
										//setTimeout("document.location.reload(true)", 3000);
									});
								}
							}
						});
					}
				});
			});
			
		</script>
		
		<?php
			require_once("footer.php"); 
		?>

</html>
