<html>
	<?php
		require_once("include/database.class.php");
	        require_once("include/session.class.php");
        	$session = new Session();
 
		
		/// Redirection vers la page index.php si non log
		if (!isset($_SESSION['login']))
			header("location:index.php");
		
		/// Gestion du selector
		if (isset($_POST['shoplist']))
		{
			$_SESSION['selector'] = $_POST['shoplist'];
			unset($_POST['shoplist']);
		}
		$_SESSION['selector_current_location'] = "youfid_master_stats.php";
		require_once("header.php");
		
		/// Si != ALL redirection vers la page de stat du marchand(envois du POST???)
		//echo($_SESSION['selector']);
		
		//echo($_SESSION['selector']);
		
		if ($_SESSION['selector'] != 0)
		{
			//header("location:youfid_master_stats_marchand.php");
			//die();
			echo "<script> window.location.replace('youfid_master_stats_marchand.php') </script>";
		}
	?>
	
  	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
  	
  	<!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript" src="jquery-1.6.2.min.js"></script>
    
    <script type="text/javascript">
    
	    // Load the Visualization API and the piechart package.
	    google.load('visualization', '1', {'packages':['corechart']});
	    // Load the Visualization API and the table package.
	    google.load('visualization', '1', {packages:['table']});
	      
	    // Set a callback to run when the Google Visualization API is loaded.
	    google.setOnLoadCallback(drawChartFranchise);
	    google.setOnLoadCallback(drawChartCategorie);
	    google.setOnLoadCallback(drawTable);
	      
	    function drawChartFranchise() {
	      var jsonData = $.ajax({
	          url: 'lib/pie_chart_franchise_data.php',
	          dataType:"json",
	          async: false
	          }).responseText;
	          
	      // Create our data table out of JSON data loaded from server.
	      var data = new google.visualization.DataTable(jsonData);
	
	      // Instantiate and draw our chart, passing in some options.
	      var chart = new google.visualization.PieChart(document.getElementById('chart_div_franchise'));
	      chart.draw(data, {width: 450, height: 340, backgroundColor: { fill:'transparent' }});
	    }
	    
	    function drawChartCategorie() {
	      var jsonData = $.ajax({
	          url: 'lib/pie_chart_categorie_data.php',
	          dataType:"json",
	          async: false
	          }).responseText;
	          
	      // Create our data table out of JSON data loaded from server.
	      var data = new google.visualization.DataTable(jsonData);
	
	      // Instantiate and draw our chart, passing in some options.
	      var chart = new google.visualization.PieChart(document.getElementById('chart_div_categorie'));
	      chart.draw(data, {width: 450, height: 340, backgroundColor: { fill:'transparent' }});
	    }
	    
	    function drawTable() {
	    	var jsonData = $.ajax({
	          url: 'lib/table_merchant_data.php',
	          dataType:"json",
	          async: false
	          }).responseText;
	    	
	        // Create our data table out of JSON data loaded from server.
	      	var data = new google.visualization.DataTable(jsonData);
	
	        var table = new google.visualization.Table(document.getElementById('stats_table'));
	        table.draw(data);
      	}
    </script>
	
	
	
	<!-- Stats FORM -->
	<div id="stats_content" style="margin:10px 30px">
		
		<h2>Statistiques</h2>
		<form id="stats_form" method="post">
			<div class="clear"></div>
			<div class="stats_content_left left">
				<label>Du </label>
				<input id="date_start" name="date_start" placeholder="Date de début"/>
				<label> au </label>
				<input id="date_end" name="date_end" placeholder="Date de fin"/></br>
				<div id="button_holder">
					<input type="button" onclick="onClickCalculer()" value="Calculer!"></button><span id="calculer_result"></span></br>
				</div>
			</div>
			<div class="stats_content_right left">
				<label for="filterlist">Filtrer selon:</label></br>
				<select id="filterlist" name="filterlist">
					<?php require_once("lib/spinner_list_stats_filter.php") ?>
				</select>
			</div>
			<div class="clear"></div>
		</form>
		
		<hr />
		<div id="stats_detail">
			<div class="clear"></div>
			<!-- Pie charts -->
			<div class="pie_content_left left">
				<h3>Franchises</h3>
				<div id="chart_div_franchise"></div>
			</div>
			<div class="pie_content_right left">
				<h3>Catégories</h3>
				<div id="chart_div_categorie"></div>
			</div>
			<!--------------->	
			<div class="clear"></div>
		</div>
		<hr />
		<!-- Table Chart -->
		<div id="stats_table_content">
			<h3>Marchands</h3>
			<div id="stats_table"></div>
		</div>
		<form id="export_form" method="post" action="youfid_master_stats_export.php">
			<div id="button_holder">
				<button>Exporter</button></br>
			</div>
		</form>
	</div>
	
	<!-- Script Gestion formulaire -->
	<script type="text/javascript" src="static/js/youfid_master_stats.js" charset="utf-8"></script>
	
	<?php
		require_once("footer.php"); 
	?>
</html>
