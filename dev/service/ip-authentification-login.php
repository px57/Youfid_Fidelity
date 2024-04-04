<?php

if(function_exists('newrelic_set_appname'))
	newrelic_set_appname('youfid_prod');

include '_security.php';

?><!DOCTYPE html  PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="fr">
	<head>
	    <meta charset="UTF-8">
		<title>YouFid Authentification</title>
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>
			body{
				background: #DDD;
				margin: 8px;
			}

			#general{
				border: #CCC 1px solid;
			    border-top: none;
			    width: 850px;
			    float: none;
			    margin: auto;
			    background: #FFF;
			    border-radius: 6px;
			}

			.no-ma{
				margin: 0;
			}

			header{
				border: none;
			    background-color: #4179AF;
			    margin-bottom: 0px;
			    border-top-left-radius: 6px;
			    border-top-right-radius: 6px;
			}

			.ui-corner-all{
				border-radius: 6px;
			}

			.shadow{
			    -moz-box-shadow: 0 0 5px #888;
    			-webkit-box-shadow: 0 0 5px #888;
    			box-shadow: 0 0 5px #888;
			}

			#title{
			    font-weight: bold;
			    text-align: center;
			    font-size: 40px;
			    width: auto;
			    padding: 20px;
			    color: #FFF;
			}

			.login-box{
				padding: 0 0 10px;
				width: 30%;
				margin:3% auto;
				border-radius: 6px;
				border: 1px solid #404040;
				background: #c7bed0 50% 50% repeat-x;
				color: #000;
				box-shadow: 0 -1px 3px #888888;
			}

			.header-info{
				padding: 5px;
				border: 1px solid #764475;
				border-top-left-radius: 6px;
				border-top-right-radius: 6px;
				background: #3A173A;
				color: #FFFFFF;
				font-weight: bold;
				margin: 0;
				outline: 0;
				line-height: 1.3;
			}

			input[type="text"],
			input[type="password"]{
				width: 200px;
				padding: 4px;
				border: 1px solid #6EA7D1;
				background: #DFEFFC 50% 50% repeat-x;
				border-radius: 5px;
			}

			footer{
				border-top: 1px solid #F3F3F3;
				min-height: 50px;
				background-color: #4179AF;
				color: #FFF;
				border-bottom-right-radius: 6px;
				border-bottom-left-radius: 6px;
			}

		</style>
	</head>
	<body>
		<div id="general" class="align-center ui-corner-all shadow">
			<header class="ui-corner-top">
				<div id="title">
					YouFid - IP Register
				</div>
			</header>
			<main>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12" style="margin-top: 3%;">
							<h2 class="text-center no-ma">Merci de renseigner vos identifiants de magasin</h2>

							<?php if(isset($_GET['error'])): ?>
								<div class="alert text-center"><?php echo urldecode(@$_GET['error']) ?></div>
							<?php endif; ?>

						</div>
					</div>
					<div class="login-box">
						<div class="header-info text-center">
							Log In
						</div>
						<div style="margin: 10px 30px;">
							<form action="ip-authentification-check.php" method="post">
								<label for="login" class="text-left">Votre nom de magasin : </label>
								<input type="text" name="login" id="login" placeholder="Votre magasin">
								<br><br>
								<label for="password" class="text-left">Votre mot de passe : </label>
								<input type="password" name="password" id="password" placeholder="Mot de passe">
								<br><br>
								<input type="submit" value="Envoyer" class="btn btn-primary">
							</form>
						</div>
					</div>

				</div>
			</main>
			<footer>
				<div class="text-center" style="padding: 10px;">
					@YouFID
				</div>
			</footer>
		</div>
	</body>
</html>