<html>
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

					<h1>CONFIRMEZ VOTRE D&Eacute;SABONNEMENT</h1>

					<hr>

					<h2><?php echo $_GET['email']; ?></h2>

					<hr>

					<p>
						Vous êtes sur le point de vous désabonner de nos communications email.
					</p>

					<br>

					<p>Etes-vous sûr de vouloir vous désabonner ?</p>

					<br>
					<br>
					<br>

					<a href="optout-done.php?email=<?php echo $_GET['email']; ?>" style="padding: 10px; background: black; color: white; font-size: 20px;">&nbsp; CONFIRMER MON D&Eacute;SABONNEMENT &nbsp;</a>

					<br>
					<br>
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