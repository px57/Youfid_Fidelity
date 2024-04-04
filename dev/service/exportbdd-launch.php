<?php

if(function_exists('newrelic_set_appname'))
	newrelic_set_appname('youfid_prod');

//include '_security.php';

if(!filter_var($_POST['email_to'], FILTER_VALIDATE_EMAIL))
{
	$infoHeader = str_replace('?error=Votre+login+ou+votre+mot+de+passe+est+incorrec','', $_SERVER['HTTP_REFERER']);
	header("Location: " . $infoHeader . "?error=" . urlencode("Votre login ou votre mot de passe est incorrect"));
	exit;
}

$database = new PDO('mysql:host=db.youfid.fr;port=3306;dbname=youfid;charset=utf8', 'youfid', 'youfid');


$statement = $database->prepare("
	SELECT login, CONCAT('#31#' , password , '$') AS passwordExpansed
	FROM backoffice_usr
	HAVING
		login = :login AND
		passwordExpansed = :passwordExpansed
	LIMIT 1
");
$statement->execute([
	'login' => $_POST['login'],
	'passwordExpansed' =>  $_POST['password'],
]);
$backoffice_usr = $statement->fetch(PDO::FETCH_OBJ);


if(!$backoffice_usr)
{
	$infoHeader = str_replace('?error=Votre+login+ou+votre+mot+de+passe+est+incorrect','', $_SERVER['HTTP_REFERER']);
	header("Location: " . $infoHeader . "?error=" . urlencode("Votre login ou votre mot de passe est incorrect"));
	exit;
}

$unsubscribe = '0';
if(@$_POST['unsubscribe'] == 'on')
	$unsubscribe = '1';



# lance la tâche ...
shell_exec('php ' . __DIR__ . '/exportbdd.php ' . $_POST['login'] . ' ' . $_POST['email_to'] . ' ' . $unsubscribe . ' > /dev/null 2>&1 &');


header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=" . urlencode("Votre demande est en cours de réalisation"));
exit;