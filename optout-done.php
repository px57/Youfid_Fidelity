<?php

require_once("include/database.class.php");

if(filter_var(@$_GET['email'], FILTER_VALIDATE_EMAIL))

	mysql_connect($host, $username, $password)or die("cannot connect");
	mysql_select_db($db_name)or die("cannot select DB");
	mysql_query("
		UPDATE `mobileuser`
		SET `unsubscribe` = '1'
		WHERE
			`mail` = '" . $_GET['email'] . "'
	");

?><html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body class="container">

		<div class="row">
			<div class="col-md-3"></div>
			<div class="col-md-6">

				<center>

					<br>
					<br>
					<br>
					<br>

					<h1>D&Eacute;SABONNEMENT CONFIRM&Eacute;</h1>

					<hr>

					<h2><?php echo $_GET['email']; ?></h2>

					<hr>

					<p>
						Votre désabonnement a bien été pris en compte.
					</p>

					<br>

					<p>&copy; YouFID.</p>

				</center>

			</div>
		</div>

		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		  ga('create', 'UA-44979007-1', 'youfid.fr');
		  ga('send', 'pageview');

		</script>

	</body>
</html>