<?php
require_once("include/database.class.php");
require_once("include/session.class.php");

// Start the session
echo "Session start";
$session = new Session();
               // 

$_SESSION['login'] = "antoine";
$_SESSION['page'] = "home";


echo "Page : " . $_SESSION['page'];

?>
