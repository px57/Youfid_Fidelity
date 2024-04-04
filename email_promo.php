<?php
	require_once "dev/service/utils.php";
	require_once("include/database.class.php");
        require_once("include/session.class.php");
        $session = new Session();
 

	$_SESSION['selector_current_location'] = "histo_promo.php";
	require_once 'header.php';
	if (isset($_POST['shoplist']))
			$_SESSION['selector'] = $_POST['shoplist']; 
	$marchand_id = $_SESSION['selector'];
	$tbl_name="message";
	require_once('dev/service/dbLogInfo.php');
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	if ($marchand_id == 0) {
		$sqlGetMes = "SELECT * FROM $tbl_name WHERE `type` = 'promo'";
	}
	else {
	$sqlGetMes = "SELECT * FROM $tbl_name WHERE `marchand_id` = '"
			. mysql_real_escape_string($marchand_id)
			. "'AND `type` = 'promo'";
	}	
	$result = mysql_query($sqlGetMes);
	
	?>
	
	<form class="inlineform" action="lib/calc_promo.php" method="post" accept-charset="UTF-8">
			<br/> <br/> <br/>
			Objet :
			<br/> <br/>
			<textarea class="mailtext" rows="1" name="objet" > </textarea>
			<br/> <br/> <br/>
			Message :
			<br/> <br/>
			<textarea class="mailtext"  rows="20" name="mail" > </textarea>
			<br/> <br/> <br/>
		    <input type='submit' name='email' value='Envoyer à <?php echo count($_SESSION['nb_client']) ?> clients'>  </form>       <FORM class="inlineform" METHOD='LINK' ACTION='youfid_master_promos.php'><INPUT TYPE='submit' VALUE='Retour'></FORM>
	
<?php require_once 'footer.php'; ?>
	
