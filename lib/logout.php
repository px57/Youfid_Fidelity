<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
        require_once(dirname(__FILE__) . "/../include/session.class.php");
        $session = new Session();
 
	
	///////////////////////////////////////////////////////////////////
	/// Reinitialise les variables $_POST et redirige vers index.php
	
//	if (isset($_SESSION))
//		unset($_SESSION);

	session_destroy();
	//$_SESSION = array();
	header("location:../index.php");
?>
