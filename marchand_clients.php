<?php
	require_once 'dev/service/utils.php';
	//// Login ////
	$login_url = $url_loyalty . 'services/user/login';
	$json_login = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "login" : "youfid", "password":"youfid"}';
	$result =  postRequest($login_url, $json_login);
	$loginResult = json_decode($result, true);
	///////////
	require_once("include/database.class.php");
        require_once("include/session.class.php");
        $session = new Session();
	
	/// Redirection vers la page index.php si non log^M
        if (!isset($_SESSION['login']))
        	header("location:index.php");
 
	$_SESSION['selector_current_location'] = "marchand_clients.php";
	
	require_once 'header.php';
	$marchand_id = $_SESSION['selector'];
	$tbl_name="marchand_has_mobileuser";
	$tbl_name1="mobileuser";
	require_once('dev/service/dbLogInfo.php');
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	$sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `marchand_id` = '"
			. mysql_real_escape_string($marchand_id)
			. "'";
		$result = mysql_query($sqlGetCustomer);
?>
  
 <script type="text/javascript" charset="utf-8">
		  $(document).ready(function() {
			  oTable = $('#example').dataTable({ 
			 
			"bJQueryUI": true,
			"sPaginationType": "full_numbers",
			});
			} );
		</script>

 
 <TABLE cellpadding="0" cellspacing="0" border="0" class="display" id="example" width="100%">
			  <thead>
			  <TR>
			  <TH> Inscription </TH>
			 <TH> Nom </TH>
			  <TH> Prénom </TH>
			  <TH> Email </TH>
			 <TH> Points </TH>
			 <TH> Nombre d'utilisations </TH>
			 <TH> Distance </TH>
			  </TR>
			</thead>
			<tbody>
			<?php
			if(!empty($result)){
				// Get app id
				$sqlGetMarchand = "SELECT * FROM marchand WHERE `id` = '"
				. mysql_real_escape_string($marchand_id) 
				. "'";
				$resultMarchand = mysql_query($sqlGetMarchand);
				$rowMarchand = mysql_fetch_array($resultMarchand);
				///////////////
					
				
			  while($row = mysql_fetch_array($result)){
					$sqlGetUser = "SELECT * FROM $tbl_name1 WHERE `id` = '"
					. mysql_real_escape_string($row['mobileuser_id'])
					. "'";
					$result2 = mysql_query($sqlGetUser);
					while ($row2 = mysql_fetch_array($result2))
					{
						$pts_url = $url_loyalty . 'services/mobileuser/mobiuserapp';
						$json_pts = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "'
						. $loginResult['wsAccess']['wsAccessToken']  . '", "mobileUserPublicId":"' 
						.  $row2['public_id'] . '", "applicationPublicId":"'. $rowMarchand['application_id'] . '"}';
						$resultPts =  postRequest($pts_url, $json_pts);
						$ptsResult = json_decode($resultPts, true);
						//echo($resultPts);
						if ($row['nb_use'] > 0) {
							echo "<TR>";
							echo "<TD>" . $row2['date_inscription']. "</TD>";
							echo "<TD>" . $row2['nom']. "</TD>";
							echo "<TD>" . $row2['prenom']. "</TD>";
							echo "<TD>" . $row2['mail']. "</TD>";
							echo "<TD>" . $ptsResult['mobileUserApplication']['totalPoints'] . "</TD>";
							echo "<TD>" . $row['nb_use'] . "</TD>";
							echo "<TD>" . number_format(distance((double)$row2['longitude'], (double)$row2['lattitude'], (double)$rowMarchand['longitude'], (double)$rowMarchand['latittude']), 1) . "</TD>";
							echo "</TR>";
						}
					}			
			  }
			}
			?>
			</tbody>
			</TABLE>
			
			<form id="export_form" method="post" action="lib/client_export.php">
				<div id="button_holder">
					<button>Exporter</button></br>
				</div>
			</form>
 
 <?php require_once 'footer.php'; ?>
