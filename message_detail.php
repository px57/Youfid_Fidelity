<?php
		require_once("include/database.class.php");
                require_once("include/session.class.php");
                $session = new Session();
 
		
		/// Redirection vers la page index.php si non log
		if (!isset($_SESSION['login']))
			header("location:index.php");
		
		$_SESSION['selector_current_location'] = "youfid_master_validationmes.php";
		require_once("header.php");
		$sqlGetMes = "SELECT * FROM message WHERE `id` = '"
			. mysql_real_escape_string($_GET['id'])
			. "'";
		$result = mysql_query($sqlGetMes);
		$row = mysql_fetch_array($result);
		$getDate = "SELECT * FROM message_has_mobileuser WHERE message_id = '" . $row['id'] ."'";
		$resultDate = mysql_query($getDate);
		$rowDate = mysql_fetch_array($resultDate);
		$getMarchand = "Select * From marchand WHERE id ='" . $row['marchand_id'] . "'";
		$resultMarchand = mysql_query($getMarchand);
		$rowMarchand = mysql_fetch_array($resultMarchand);
?>
<script>
	function validate_message()
	{
		document.getElementById('message_content').value = document.getElementById('mailtext').value;
	}
	</script>
</head>
<div class="container">
  <div class="row">
    <div class="col-xs-12 col-md-12"> <br/>
      <br/>
      <TABLE cellpadding="0" class="table" cellspacing="0" border="0"  width="90%">
        <thead>
          <TR>
            <TH> <?php echo $rowDate['date_creation'] ?></TH>
            <TH> <?php echo $rowMarchand['name'] ?></TH>
            <TH> <?php echo $row['type'] ?></TH>
            <TH> <?php echo $row['message'] ?></TH>
          </TR>
          <TR>
            <TH> <?php echo "                                                  Du $row[start_date] au $row[finish_date]";?></TH>
          </TR>
        </thead>
      </TABLE>
      <br/>
      <br/>
      <br/>
      <br/>
      <textarea class="form-control mailtext" id="mailtext" rows="20"> <?php echo $row['detail'] ?></textarea>
      <br/>
      <br/>
      <br/>
      <br/>
      <?php if ($row['type'] == 'promo'){ ?>
      <form method="post" action="lib/validate_message.php" class="inlineform">
        <input class="form-control" type="hidden" name="id" value="<?php echo $row['id']  ?>">
        <input type="hidden" id="message_content" name="message_content" value="value" >
        <input type='submit' value='Valider' class="btn btn-default" onclick='validate_message()'>
      </form>
      <FORM class="inlineform" METHOD='LINK' ACTION='youfid_master_validationmes.php'>
        <INPUT TYPE='submit' VALUE='Retour' class="btn btn-default">
      </FORM>
      <?php }
				elseif ($row['type'] == 'email') {?>
      <form method="post" action="lib/send_mail.php" class="inlineform">
        <input type="hidden" name="id" value="<?php echo $row['id']  ?>">
        <input type='submit' value='Valider' class="btn btn-default">
      </form>
      <FORM class="inlineform" METHOD='LINK' ACTION='youfid_master_validationmes.php'>
        <INPUT TYPE='submit' class="btn btn-default" VALUE='Retour'>
      </FORM>
      <?php
				}?>
    </div>
  </div>
</div>
<?php require_once 'footer.php'; ?>
<?
		require_once("footer.php"); 
	?>


