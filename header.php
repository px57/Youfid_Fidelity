<?php
	require_once('popup-labelform.php');
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" type="image/ico" href="http://www.datatables.net/favicon.ico" />
		<link rel="stylesheet" type="text/css" media="all" href="static/fancybox/jquery.fancybox.css">
		<!-- Date Picker -->
		<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>

		<title>YouFid - BackOffice</title>
		<style type="text/css" title="currentStyle">
		
			@import "./static/css/change_password.css";
			@import "./static/css/merchant_push.css";
		
			/*@import "./css/demo_page.css";*/
			@import "./css/jquery.dataTables.css";
			@import "./css/time.css";
			@import "./css/promo.css";
			@import "./css/navstyle.css";
			
			@import "./static/css/header_content.css";
			@import "./static/css/new_merchant.css";
			@import "./static/css/youfid_master_stats.css";
			@import "./static/css/widgets/client_merchant_switch.css";
			@import "./static/css/widgets/on_off_switch.css";
			
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
	
	
		
	<!-- DANS LA BALISE BODY::SCRIPT POUR LE FORMULAIRE DE CATEGORIE -->
	<body onload="javascript:fg_hideform('fg_formContainer','fg_backgroundpopup');">
		<div id="general" class="align-center ui-corner-all shadow">
            <div id="header" class="ui-corner-top">
                <div id="title">
					<?php 
						
						/// Role Information
						$role_marchand = "marchands";
					
						/// Verification du type de header a inclure
						if ($_SESSION['role'] == $role_marchand)
							require_once "header_content_merchant.php";
						else if ($_SESSION['role'] == "admin_4g")
							require_once "header_content_youfid_admin.php";
						else
							require_once "header_content_other.php"
						 
					?>
				</div>
            </div>
            	<?php require_once "nav.php"; ?>
            <div id="main">
				<div class="clear"></div>
				<div id="content">
