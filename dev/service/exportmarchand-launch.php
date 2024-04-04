<?php

if(function_exists('newrelic_set_appname'))
	newrelic_set_appname('youfid_prod');

include '_security.php';

$login = 'youfid';
$password = 'a7ab397c1a4ddae2dd283a47572341a3';

if(!filter_var($_POST['email_to'], FILTER_VALIDATE_EMAIL) or !$_POST['password'])
{
	$infoHeader = str_replace('?error=Votre+login+ou+votre+mot+de+passe+est+incorrec','', $_SERVER['HTTP_REFERER']);
	header("Location: " . $infoHeader . "?error=" . urlencode("Votre login ou votre mot de passe est incorrect"));
	exit;
}

$enter_password = md5($_POST['password']);

if($_POST['login'] != $login or $enter_password != $password)
{
	$infoHeader = str_replace('?error=ddVotre+login+ou+votre+mot+de+passe+est+incorrec','', $_SERVER['HTTP_REFERER']);
	header("Location: " . $infoHeader . "?error=" . urlencode("Votre login ou votre mot de passe est incorrect"));
	exit;
}

# lance la tâche ...
shell_exec('php ' . __DIR__ . '/exportmarchand.php ' . $_POST['email_to'] . ' > /dev/null 2>&1 &');


header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=" . urlencode("Votre demande est en cours de réalisation"));
exit;