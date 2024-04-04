<!-- Scripts for label Popup -->
<!--<script type="text/javascript" src="scripts/jquery.min.js"></script>-->
<script type="text/javascript" src="static/fancybox/jquery.fancybox.js?v=2.0.6"></script>
<script type="text/javascript" src="static/js/add_commerciaux_popup.js"></script>
<!----------------------------->

<div class="myContent">
	
		<?php
			/// Il faudra changer le logo par le logo youfid ainsi que changer le path
			$logoPath = "static/logos/logo.png";
			if(isset($_SESSION['logopath']) && !empty($_SESSION['logopath']))
			{
				$logoPath = $_SESSION['logopath'];
			}
			
			$shop_criteria = "Rechercher";
			if(isset($_POST['shop_find_criteria']) && !empty($_POST['shop_find_criteria']))
			{
				$shop_criteria = $_POST['shop_find_criteria'];
				//unset($_POST['shop_find_criteria']);
			}
			//onChange="combo(this, 'theinput')"
		?>
	
        <div class="clear"></div>
        
        <div class="gauche left">
        	
        	<form method="post" action="<?php echo($_SESSION['selector_current_location']) ?>">
	        	<select name="shoplist"	onchange="form.submit()">
					<?php 
						if ($_SESSION['role'] == "youfid_commerciaux" && ($_SESSION['selector_current_location'] == "commerciaux_moncompte.php"))
							echo("<option value=0>NEW</option>");
						else if ($_SESSION['role'] == "youfid_master" && ($_SESSION['selector_current_location'] == "youfid_master_moncompte.php"))
							echo("<option value=0>NEW</option>");
						else
							echo("<option value=0>Tous les marchands</option>");
						
						require_once("lib/spinner_list_shop.php")
					?>
				</select>
				<div style="margin-top: 2px;">
					<input title="Expression a rechercher" id="shop_search_box" style="width: 60%; margin-left: -5px" type="text" name="shop_find_criteria" placeholder=<?php echo($shop_criteria)?> />
					<button  style="background:transparent; border-color: transparent; " type="submit" title="Lancer la recherche" ><img width="21" height="21" src="static/images/icone_loupe.png"/></button>
					<a href="<?php echo($_SESSION['selector_current_location']) ?>" title="Réinitialiser la recherche"><img class="icons" width="21" height="21" src="static/images/red_cross.png"/></a><br>
					<?php 
						
						$nb_result = "";
						if(isset($_SESSION['shop_find_nbresult']) && !empty($_SESSION['shop_find_nbresult']))
						{
							$nb_result = $_SESSION['shop_find_nbresult'];
							unset($_SESSION['shop_find_nbresult']);
						}
					
						echo($nb_result); 
					?>  
				</div>
			</form>
			 
        </div>
        <div class="milieu left">
         	<img height="100%" width="60%" src=<?php echo($logoPath); ?>></img>
        </div>
        <div class="droite left">
            <?php echo("Log: " . $_SESSION['role']) ?>
            <form method="GET" action="lib/logout.php">
            	<input type="submit" value="Logout"/>
            </form>
            <?php
            	if ($_SESSION['role'] == "youfid_master")
            		echo('<a class="modalbox" href="#header_inline" title="Ajouter un nouveau commercial"><button >Ajouter un nouveau commercial</button></a><br>');
            ?>
        </div>
        <div class="clear"></div>
        
		<!-- POPUP hidden inline form -->
		<div id="header_inline">
			<h2>Ajouter un nouveau compte commercial</h2>
						
			<form id="c_add" name="c_add" action="#" method="post">
				<label for="c_email">Entrez l'addresse email du commercial:</label>
				<input id="c_email" name="c_email" class="txt">
								
				<button id="c_send">Créer le nouveau compte</button>
			</form>
		</div>
		<!----------------------->
</div>
