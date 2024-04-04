<html>
	<?php
		require_once("include/database.class.php");
	        require_once("include/session.class.php");
        	$session = new Session();
 
		
		/// Redirection vers la page index.php si non log
		if (!isset($_SESSION['login']))
			header("location:index.php");
		if (isset($_POST['shoplist']))
			$_SESSION['selector'] = $_POST['shoplist']; 
		$_SESSION['selector_current_location'] = "youfid_master_validationmes.php";
		require_once("header.php");
		$marchand_id = $_SESSION['selector'];
		if ($marchand_id == 0){
			$sqlGetMes = "SELECT * FROM message WHERE `is_validated` = '"
				. mysql_real_escape_string('0')
				. "'";
		}
		else {
			$sqlGetMes = "SELECT * FROM message WHERE `is_validated` = '"
				. mysql_real_escape_string('0')
				. "' && marchand_id = '"
				. mysql_real_escape_string($marchand_id)
				. "'";
		}
		$result = mysql_query($sqlGetMes);
		
		$sqlgetMarchand = "SELECT * FROM marchand LIMIT 1";
		$resultmarchand = mysql_query($sqlgetMarchand);
		$rowMarchand =mysql_fetch_array($resultmarchand);
	?>
	
	<script type="text/javascript" charset="utf-8">
		  $(document).ready(function() {
			  oTable = $('#example').dataTable({ 
			  "aaSorting": [[ 0, "desc" ]],
			"bJQueryUI": true,
			"sPaginationType": "full_numbers"
			});
			} );
		</script>
	
	
	<form method="post" action="lib/updatepush.php" class="inlineform">
	A partir de <select name='nonlu'><?php $i = 1; while ($i <= 10) {if ($rowMarchand['push_non_lu'] == $i){echo "<option selected> $i";} else{echo "<option> $i";}   $i += 1;}    ?></select> promos non lues, envoyer un push toutes les <select name="every"><?php $i = 1; while ($i <= 10) {if ($rowMarchand['push_nouvelle_promo'] == $i){echo "<option selected> $i";} else{echo "<option> $i";}   $i += 1;}    ?></select> nouvelles promos.
	<br />
	<input type='submit' name='submit1' value='Valider'>
	<br /><br />
	<form>
	 <TABLE cellpadding="0" cellspacing="0" border="0" class="display" id="example" width="100%">
			  <thead>
			  <TR>
			 <TH> Date de soumission </TH>
			 <TH> Marchand </TH>
			 <TH> Type </TH>
			 <TH> Titre </TH>
			 <TH> Details</TH>
			 <TH> Supprimer</TH>
			  </TR>
			</thead>
			<tbody>
			<?php
			if(!empty($result)){
				while($row = mysql_fetch_array($result)){
						echo "<TR>";
						
						$getMarchand = "Select * From marchand WHERE id ='" . $row['marchand_id'] . "'";
						$resultMarchand = mysql_query($getMarchand);
						$rowMarchand = mysql_fetch_array($resultMarchand);
						
						if ($row['message'] == "A proximité")
						{
							/*$today =  date('Y-m-d H:i:s');
							echo "<TH>" . $today . "</TH>";*/
							echo "<TH>" . $row['start_date']. "</TH>";
						}
						else
						{
							$getDate = "SELECT * FROM message_has_mobileuser WHERE message_id = '" . $row['id'] ."'";
							$resultDate = mysql_query($getDate);
							$rowDate = mysql_fetch_array($resultDate);
							//echo "<TH>" . $rowDate['date_creation']. "</TH>";
							$newDate = date("Y-m-d", strtotime($rowDate['date_creation']));
							echo "<TH>" . $newDate. "</TH>";
						}
							
						echo "<TD>" . $rowMarchand['name']. "</TD>";
						echo "<TD>" . $row['type']. "</TD>";
						echo "<TD>" . $row['message']. "</TD>";
						echo "<TD>  <a href='message_detail.php?id=" .  $row['id'] . "'> Détails </a></TD>";
						echo "<TD> <a href='message_suppress.php?id=" .  $row['id'] . "'> Supprimer </a></TD>";
						echo "</TR>";
			  }
			}
			?>
			
			</tbody>
			</TABLE>
	
	
	
	
	
	<?php
		require_once("footer.php"); 
	?>
</html>
