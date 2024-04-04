<?php

if(function_exists('newrelic_set_appname'))
	newrelic_set_appname('youfid_prod');

//include '_security.php';

?><!DOCTYPE html  PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="fr">
	<head>
	    <meta charset="UTF-8">
		<title>YouFid - Export BDD</title>
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>

			body{
				background-color: black;
				margin-top:50px;
			}

			h1{
				font-family : "Helvetica Neue",Helvetica,Arial,sans-serif;
				margin-top: 0rem;
				margin-bottom: 3rem;
			}

            .active_case {
                background-color: #0bb8bf !important;
                border-color: #0bb8bf !important;
            }
            .white_text {
                color: white !important;
                font-weight: bold;
            }
            .blue_button {
                background-color: #0bb8bf !important;
                border-color: #0bb8bf !important;
                font-weight: bold;
            }
            .error_log {
                background-color: #ea007d !important;
                color: white;
                font-weight: bold;
                padding: 10px;
                border-radius: 10px;
            }
            .show_description {
                max-height: 100px;
                overflow: hidden;
            }
            .gray_background {
                background-color: #eaeaea;
            }
            .login-part {
                background-color: white;
                border-radius: 10px;
                padding-bottom: 40px;
            }
            .pagination span a{
                text-decoration: none;
                color: black;
                border: solid 2px #eaeaea;
                background-color: #eaeaea;
                padding: 10px;
                border-radius: 80px;
            }

            .pagination span a:hover{
                text-decoration: none;
                color: black;
                border: solid 2px #b6b6b6;
                background-color: #b6b6b6;
                padding: 10px;
                border-radius: 80px;
                font-weight: bold;
            }
            .pagination .current{
                text-decoration: none;
                color: white;
                border: solid 2px #eaeaea;
                background-color: #0bb8bf;
                padding: 10px;
                border-radius: 80px;
            }
            .nomapa {
                margin: 0px;
                padding: 0px;
            }
        </style>
    </head>
	<body>
        <div class="col-md-offset-4 col-md-4 login-part">
            <form action="exportbdd-launch.php" method="post">
	            <div class="text-center">
	                <img src="https://i.vimeocdn.com/portrait/11825666_300x300" alt="Logo" class="img img-responsive"  style="margin:0 auto">
	            </div>
	            <h1 class="text-center">Outil d'export de base de données supermarchand</h1>
	            <?php if(isset($_GET['error'])): ?>
					<div class="alert text-center"><?php echo urldecode(@$_GET['error']) ?></div>
				<?php endif; ?>
                <div class="form-group">
                    <label for="login" class="text-left">Votre nom de magasin : </label>
                    <input type="text" name="login" id="login" placeholder="Votre magasin" class="form-control">
                </div>
                <div class="form-group">
                    <label for="password" class="text-left">Votre mot de passe : </label>
                    <input type="password" name="password" id="password" placeholder="Mot de passe" class="form-control">
                </div>
                <div class="form-group">
                    <label for="email" class="text-left">Boite mail sur laquelle recevoir l'export de la base : </label>
                    <input type="email" name="email_to" id="email" placeholder="Email" class="form-control">
                </div>
                <div class="form-inline">
                    <label for="unsubscribe" class="text-left">Uniquement les opt-in : </label>
                    <input type="checkbox" name="unsubscribe" id="unsubscribe" class="checkbox">
                </div>
                <br>
                <input type="submit" value="Envoyer" class="btn btn-primary">
			</form>
        </div>
    </body>

</html>