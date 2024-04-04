<?php

	require_once "dev/service/utils.php";
	require_once("include/database.class.php");
        require_once("include/session.class.php");
        $session = new Session();
 

	$_SESSION['selector_current_location'] = "histo_promo.php";
	if (isset($_POST['shoplist']))
			$_SESSION['selector'] = $_POST['shoplist']; 
	require_once 'header.php';
	
	$marchand_id = $_SESSION['selector'];
	$tbl_name="message";
	require_once('dev/service/dbLogInfo.php');
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	if ($marchand_id == 0) {
		$sqlGetMes = "SELECT * FROM $tbl_name WHERE `type` = 'promo' ORDER BY `start_date` ASC";
	}
	else {
		$sqlGetMes = "SELECT * FROM $tbl_name WHERE `marchand_id` = '"
			. mysql_real_escape_string($marchand_id)
			. "'AND `type` = 'promo' ORDER BY `start_date` ASC";
	}	
	$result = mysql_query($sqlGetMes);
	
	
?>

	<script type="text/javascript" charset="utf-8">
		  $(document).ready(function() {
			  oTable = $('#example').dataTable({ 
			 "aaSorting": [[ 0, "desc" ]],
			"bJQueryUI": true
			
			 
			});
			} );
		</script>
  <TABLE cellpadding="0" cellspacing="0" border="0" class="display" id="example" width="100%">
			  <thead>
			  <TR>
			  	 <TH> ID </TH>
			 <TH> Titre </TH>
			 <TH> Date de Début </TH>
			 <TH> Date de Fin </TH>
			 <TH> Contenu </TH>
			  </TR>
			</thead>
			<tbody>
			<?php
			if(!empty($result)){
			/*  $bigtab = array();
			  while($row = mysql_fetch_array($result)){
			  		$data = array();
			  		$data['message'] = $row['message'];
			  		$data['start_date'] = $row['start_date'];
					$data['finish_date'] = $row['finish_date'];
					$data['detail'] = $row['detail'];
					array_push($bigtab, $data);
			  }*/
			while($row = mysql_fetch_array($result)){
						echo "<TR>";
						echo "<TD> $row[id] </TD>";
						echo "<TD> $row[message] </TD>";
						echo "<TD> $row[start_date] </TD>";
						echo "<TD> $row[finish_date] </TD>";
						echo "<TD> $row[detail] </TD>";
						echo "</TR>";
			  }
			}
			?>
			
			</tbody>
			</TABLE>
			
		<BR/>	<BR/>
 <?php 
 if ($_SESSION['role'] == "youfid_master"){
	echo "<FORM METHOD='LINK' ACTION='youfid_master_promos.php'>
		<INPUT TYPE='submit' VALUE='Retour'>
		</FORM>	";				
}
else {
	echo "<FORM METHOD='LINK' ACTION='marchand_promos.php'>
		<INPUT TYPE='submit' VALUE='Retour'>
		</FORM>	";				

}
 
 
 require_once 'footer.php'; ?>
