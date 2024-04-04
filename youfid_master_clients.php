<?php
	require_once("include/database.class.php");
        require_once("include/session.class.php");
        $session = new Session();
 
	require_once 'dev/service/utils.php';


	$_SESSION['selector_current_location'] = "youfid_master_clients.php";
	if (isset($_POST['shoplist']))
		$_SESSION['selector'] = $_POST['shoplist']; 
	require_once 'header.php';
	
	
	$marchand_id = $_SESSION['selector'];
	$tbl_name="marchand_has_mobileuser";
	$tbl_name1="mobileuser";
	require_once('dev/service/dbLogInfo.php');
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	$sqlGetCustomer = "SELECT * FROM mobileuser";
	$result = mysql_query($sqlGetCustomer);
	if ($marchand_id == 0 ){
?>
   
	<script type="text/javascript" charset="utf-8">
		  $(document).ready(function() {
			  oTable = $('#example').dataTable({ 
			 
			"bJQueryUI": true,
			"sPaginationType": "full_numbers"
			});
			} );
		</script>
 <TABLE cellpadding="0" cellspacing="0" border="0" class="display" id="example" width="100%">
			  <thead>
			  <TR>
			 <TH> Inscription </TH>
			 <TH> Nom </TH>
			 <TH> Prénom</TH>
			 <TH> Email </TH>
			 <TH> Nombre d'utilisations </TH>
			 <TH> Premier Commercant </TH>
			  </TR>
			</thead>
			<tbody>
			<?php
			if(!empty($result)){
				$allNoneSupermarchant = "Select * From marchand where is_supermarchand = 0";
				$resultNo = mysql_query($allNoneSupermarchant);
				$i = 0;
				while ($rowMarchand = mysql_fetch_array($resultNo)) {
					$OkMarchand[$i] = $rowMarchand['id'];
					$i += 1;
				}
			  while($row = mysql_fetch_array($result)){
			  	if ($row['id'] != 1)
			  	{	
					$nbuse = 0;
					if(isset($row['nb_use']) && $row['nb_use']!="") $nbuse = $row['nb_use'];
					
						///	
						echo "<TR>";
						echo "<TD>" . $row['date_inscription']. "</TD>";
						echo "<TD>" . $row['nom']. "</TD>";
						echo "<TD>" . $row['prenom']. "</TD>";
						echo "<TD>" . $row['mail']. "</TD>";
						echo "<TD>" . $nbuse . "</TD>";
						if (!(isset($row['first_merchant'])) || $row['first_merchant'] == ""){
							echo "<TD> N.C. </TD>";
						}
						else {
							echo "<TD>" . $row['first_merchant'] . "</TD>";
						}

						echo "</TR>";
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
			
  <?php 
	}

   else {
   	$sqlGetCustomer = "SELECT * FROM $tbl_name WHERE nb_use > 0 && `marchand_id` = '"
			. mysql_real_escape_string($marchand_id)
			. "'";
		$result = mysql_query($sqlGetCustomer);
		
		
		
      	
?>
  
 Clients
 <script type="text/javascript" charset="utf-8">
		  $(document).ready(function() {
			  oTable = $('#example').dataTable({ 
			 
			"bJQueryUI": true,
			"sPaginationType": "full_numbers"
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
				
                $pts_url = $url_loyalty . "services/mobileuser/appmobiusers";
				$json_pts = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "7e17880d34734a43b83848f76b1452b3", "applicationPublicId":"'. $rowMarchand['application_id'] . '"}';
				$resultPts =  postRequest($pts_url, $json_pts);
				
                $ptsResult = json_decode($resultPts, true);
				$array_mobil = array();
				foreach ($ptsResult["mobileUserApplications"] as $mobil)
				{
					$array_mobil[$mobil["mobileUser"]['publicId']] = $mobil["totalPoints"];
				}
				
			    while($row = mysql_fetch_array($result)){
					$sqlGetUser = "SELECT * FROM $tbl_name1 WHERE `id` = '"
					. mysql_real_escape_string($row['mobileuser_id'])
					. "'";
					$result2 = mysql_query($sqlGetUser);
					while ($row2 = mysql_fetch_array($result2))
					{
						
						echo "<TR>";
						echo "<TD>" . $row2['date_inscription']. "</TD>";
						echo "<TD>" . $row2['nom']. "</TD>";
						echo "<TD>" . $row2['prenom']. "</TD>";
						echo "<TD>" . $row2['mail']. "</TD>";
						echo "<TD>" . $array_mobil[$row2['public_id']] . "</TD>";
						echo "<TD>" . $row['nb_use'] . "</TD>";
						echo "<TD>" . number_format(distance((double)$row2['longitude'], (double)$row2['lattitude'], (double)$rowMarchand['longitude'], (double)$rowMarchand['latittude']), 1) . "</TD>";
						echo "</TR>";
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
 
 <?php 
 }
 require_once 'footer.php'; ?>
