<html>
	<?php
		require_once("include/database.class.php");
                require_once("include/session.class.php");
                $session = new Session();
 
		
		/// Redirection vers la page index.php si non log
		if (!isset($_SESSION['login']))
			header("location:index.php");
		
		$_SESSION['selector_current_location'] = "marchand_stats.php";
		require_once("header.php");
	?>

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
	
	<!-- Stats FORM -->
	<div id="stats_content" style="margin:10px 30px">
		<h2>Statistiques</h2>
		
		<form id="stats_form" method="post" action="youfid_master_stats_marchand_export.php">
			<div class="clear"></div>
			<div class="stats_content_left left">
				<label>Du </label>
				<input id="date_start" name="date_start" placeholder="Date de début"/>
				<label> au </label>
				<input id="date_end" name="date_end" placeholder="Date de fin"/></br>
				
				<div id="checkbox_holder">
					<input type="checkbox" name="is_app" checked="checked"/><label>Clients application mobile</label></br>
					<input type="checkbox" name="is_physique"/><label>Clients carte physique</label></br>
				</div>
				
				<div id="button_holder">
					<span style="color: red;" id="calculer_result"></span></br>
					<input type="button" onclick="onClickCalculerMarchand()" value="Calculer!"></button>
					<!--<input type="submit"  value="Exporter"></br>-->
					<input type="button" onclick="onClickExportMarchand()" value="Exporter"></button><span id="calculer_result"></span></br>
				</div>
			</div>
			<div class="stats_content_right left">
				<label for="filterlist">Filtrer selon:</label></br>
				<select id="filterlist" name="filterlist">
					<?php require_once("lib/spinner_list_stats_merchant_filter.php") ?>
				</select>
			</div>
			<div class="clear"></div>
		</form>
	</div>
	
	<!-- Script Gestion formulaire -->
	<script type="text/javascript" src="static/js/youfid_master_stats.js" charset="utf-8"></script>
	
	<?php
		require_once("footer.php"); 
	?>
</html>
