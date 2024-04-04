<?php

$mbodyp = "Saviez vous que vous pouviez utiliser votre carte ou application Les Commerces de l'Arche chez des centaines de commerçants. Retrouvez la liste de nos partenaires sur notre site internet ou sur l'application.";

$mtitlep  = "Les Commerces de l'Arche votre carte multi-commerces";

$mlinkp = 'http://www.youfid.fr/';

$mimgp = 'http://business.youfid.fr/img/logo_campagnes.jpg';

$body_html = '

<!DOCTYPE HTML>

<html xmlns="http://www.w3.org/1999/xhtml">

  <head>



  <!-- Define Charset -->

  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />



  <!-- Responsive Meta Tag -->

  <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;" />

  <title>'.$mtitle.'</title>

  <!-- Responsive Styles and Valid Styles -->

  <link href=\'http://fonts.googleapis.com/css?family=Slabo+13px\' rel=\'stylesheet\' type=\'text/css\'>

  <style type=\'text/css\'>

  body {

  width: 100%;

  background-color: #f0f0f0;

  margin: 0;

  padding: 0;

  -webkit-font-smoothing: antialiased;

}

p, h1, h2, h3, h4, img {

  margin-top: 0;

  margin-bottom: 0;

  padding-top: 0;

  padding-bottom: 0;

}

span.preheader {

  display: none;

  font-size: 1px;

}

html {

  width: 100%;

}

table {

  font-size: 14px;

  border: 0;

}



/* ----------- responsivity ----------- */

@media only screen and (max-width: 640px) {

/*------ top header ------ */

.view-online {

  font-size: 12px !important;

}

.main-header {

  line-height: 28px !important;

  font-size: 17px !important;

}

.main-subheader {

  line-height: 28px !important;

}

/*----- main image -------*/

.main-image {

  width: 440px !important;

  height: auto !important;

}

/*-------- container --------*/

.container600 {

  width: 440px !important;

}

.container560 {

  width: 400px !important;

}

/*-------- divider --------*/

.divider {

  width: 440px !important;

  height: 1px !important;

}

/*----- banner -------*/

.banner {

  width: 400px !important;

  height: auto !important;

}

/*-------- secions ----------*/

.section-item {

  width: 400px !important;

}

.section-img {

  width: 400px !important;

  height: auto !important;

}

/*-------- footer ------------*/

.unsubscribe {

  line-height: 26px !important;

  font-size: 13px !important;

}

.copy {

  line-height: 26px !important;

  font-size: 14px !important;

}

.hide-iphone {

  display: none !important;

}

.vertical-spacing {

  width: 400px !important;

}

.footer-item {

  width: 200px !important;

}

}



@media only screen and (max-width: 479px) {

/*------ top header ------ */

.view-online {

  font-size: 12px !important;

}

.main-header {

  line-height: 28px !important;

  font-size: 15px !important;

}

.main-subheader {

  line-height: 28px !important;

}

.logo {

  width: 280px !important;

}

.nav {

  width: 280px !important;

}

/*----- main image -------*/

.main-image {

  width: 280px !important;

  height: auto !important;

}

/*-------- container --------*/

.container600 {

  width: 280px !important;

}

.container560 {

  width: 240px !important;

}

/*-------- divider --------*/

.divider {

  width: 280px !important;

  height: 1px !important;

}

/*----- banner -------*/

.banner {

  width: 240px !important;

  height: auto !important;

}

/*-------- secions ----------*/

.section-item {

  width: 240px !important;

}

.section-img {

  width: 240px !important;

  height: auto !important;

}

/*-------- footer ------------*/

.unsubscribe {

  line-height: 26px !important;

  font-size: 13px !important;

}

.copy {

  line-height: 26px !important;

  font-size: 14px !important;

}

.hide-iphone {

  display: block !important;

}

.vertical-spacing {

  width: 240px !important;

}

.footer-item {

  width: 240px !important;

}

}



  </style>



  </head>



  <body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">



<!--======= preheader ======-->

<span class="preheader">'.$mtitle.'<br/>

    </span>

<!--======= end preheader ======-->



<table border="0" width="100%" cellpadding="0" cellspacing="0" bgcolor="f0f0f0">

      <tr>

    <td height="27"></td>

  </tr>



      <!-------------- top header ------------->

      <tr mc:repeatable>

    <td align="center"><table width="600" cellpadding="0" align="center" cellspacing="0" border="0" class="container600">

        <tr>

          <td align="center"><table border="0" align="left" cellpadding="0" cellspacing="0" class="logo">

              <tr>

                <td align="center"><a href="http://youfid.fr" style="display: block; width: 200px; height: 70px; border-style: none !important; border: 0 !important;"><img editable="true" mc:edit="logo" width="200" height="28" border="0" style="display: block; width: 200px; height: 70px" src="https://s3.eu-central-1.amazonaws.com/youfid/logo-mail.png" alt="logo" /></a></td>

              </tr>

            </table>

            <table border="0" align="left" cellpadding="0" cellspacing="0" class="nav">

              <tr>

                <td height="20" width="20"></td>

              </tr>

            </table>

            <table border="0" align="right" cellpadding="0" cellspacing="0" class="nav">

              <tr>

                <td height="5"></td>

              </tr>

              <tr>

                <td align="center" mc:edit="view-online" style="font-size: 13px; font-family: Helvetica, Arial, sans-serif;"><table border="0" cellpadding="0" cellspacing="0" class="date">

                    <tr>

                      <td mc:edit="tel" style="color: #8c8c8c; font-size: 14px; font-family: \'Slabo 13px\', Arial, sans-serif; text-align:right;" class="view-online"><singleline> Vous n\'arrivez pas à lire ce message ?<br>

                          <a href="[[PERMALINK]]" style="color: #34aadc; text-decoration: none;">Voir la version en ligne</a> </singleline></td>

                    </tr>

                  </table></td>

              </tr>

            </table></td>

        </tr>

      </table></td>

  </tr>

      <!-------------- end top header ------------->



      <tr>

    <td height="25"></td>

  </tr>







      <tr mc:repeatable>

    <td><table width="600" cellpadding="0" align="center" cellspacing="0" bgcolor="ffffff" border="0" class="container600">

        <tr>

          <td bgcolor="ffffff" height="45">&nbsp;</td>

        </tr>

      </table></td>

  </tr>



      <!-------------- main text ------------->

      <tr mc:repeatable>

    <td align="center"><table width="600" cellpadding="0" align="center" cellspacing="0" bgcolor="ffffff" border="0" class="container600">

        <tr>

          <td align="center"><table border="0" width="560" align="center" cellpadding="0" cellspacing="0" class="container560">

              <tr>

                <td align="center" mc:edit="main-header" style="color: #596064; font-size: 23px; font-weight: bold; font-family: \'Slabo 13px\', Arial, sans-serif;" class="main-header"><multiline> '.$mtitle.' </multiline></td>

              </tr>

              <tr>

                <td height="25"></td>

              </tr>

              <tr>

                <td align="center" mc:edit="main-subheader" style="color: #8c8c8C; font-size: 15px; font-family: \'Slabo 13px\', Arial, sans-serif; line-height: 36px;" class="main-subheader"><multiline> '.$mbody.' </multiline></td>

              </tr>

            </table></td>

        </tr>

      </table></td>

  </tr>

      <!-------------- end main text ------------->



      <tr mc:repeatable>

    <td><table width="600" cellpadding="0" align="center" cellspacing="0" bgcolor="ffffff" border="0" class="container600">

        <tr>

          <td><img editable="false" mc:edit="divider" src="http://business.youfid.fr/img/divider.png" style="display: block; width: 600px; height: 1px;" width="600" height="1" border="0" alt="divider" class="divider" /></td>

        </tr>

      </table></td>

  </tr>



      <!-------------- divider ------------->

      <trmc:repeatable>

      <!-------------- end divider ------------->



      <!-------------- section ------------->

      <!-------------- end section ------------->



      <!-------------- divider ------------->

      <!-------------- end divider ------------->



      <!------- 2columns ------->

      <!------- end 2columns ------->



      <!-------------- divider ------------->

      <!-------------- end divider ------------->



      <!-------------- banner ------------->

      <!-------------- end banner ------------->



      <!-------------- divider ------------->

      <!-------------- end divider ------------->



      <!-------------- section ------------->

      <!-------------- end section ------------->

      <tr mc:repeatable>

    <td><table width="600" cellpadding="0" align="center" cellspacing="0" bgcolor="ffffff" border="0" class="container600">

        <tr>

          <td bgcolor="ffffff" height="45"></td>

        </tr>

      </table></td>

  </tr>



      <!-------------- section ------------->

    <tr mc:repeatable>

      <td align="center">

        <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="ffffff" class="container600">

          <tr>

            <td>

              <table width="560" align="center" cellpadding="0" cellspacing="0" border="0" class="container560">

                <tr mc:repeatable>

                    <td>

                      <table border="0" width="200" align="left" cellpadding="0" cellspacing="0" class="section-item">

                      <tr>

                        <td>

                          <a href="'.$mlinkp.'" style=" border-style: none !important; border: 0 !important;"><img editable="true" mc:edit="image1" src="https://s3.eu-central-1.amazonaws.com/youfid/logo_campagnes.jpg" style="display: block; width: 200px; height: 193px;" width="200" height="193" border="0" alt="section image" class="section-img"/></a>

                        </td>

                      </tr>

                    </table>



                    <table border="0" width="40" align="left" cellpadding="0" cellspacing="0">

                      <tr><td width="40" height="30"></td></tr>

                    </table>



                    <table border="0" width="320" align="left" cellpadding="0" cellspacing="0" class="section-item">



                      <tr>

                        <td mc:edit="title1" style="color: #4e4e4e; font-size: 16px; font-weight: bold; font-family: \'Slabo 13px\', Arial, sans-serif;" class="main-header">

                              <multiline>

                                '.$mtitlep.'

                              </multiline>

                            </td>

                      </tr>



                      <tr><td height="12"></td></tr>



                      <tr>

                        <td mc:edit="text1" style="color: #8c8c8c; font-size: 15px; font-family: \'Slabo 13px\', Arial, sans-serif; line-height: 36px;">

                          <multiline>

                            '.$mbodyp.'

                          </multiline>

                        </td>

                      </tr>



                      <tr><td height="14"></td></tr>



                      <tr>

                        <td>

                          <a href="'.$mlinkp.'" style="display: block; width: 118px;  border-style: none !important; border: 0 !important;"><img editable="true" mc:edit="readMoreBtn" width="118" height="35" border="0" style="display: block; width: 118px; height: 35px;" src="http://business.youfid.fr/img/readmore-btn.png" alt="read more" /></a>

                        </td>

                      </tr>

                    </table>



                    </td>

                  </tr>

              </table>

            </td>

          </tr>

        </table>

      </td>

    </tr>

                    <tr mc:repeatable>

    <td><table width="600" cellpadding="0" align="center" cellspacing="0" bgcolor="ffffff" border="0" class="container600">

        <tr>

          <td bgcolor="ffffff" height="45"></td>

        </tr>

      </table></td>

  </tr>

    <!-------------- end section ------------->

      <!-------------- footer ------------->



      <tr>

    <td><table width="600" cellpadding="0" align="center" cellspacing="0" bgcolor="34aadc" border="0" class="container600">

        <tr>

          <td height="45"></td>

        </tr>

        <tr>

          <td><table width="560" align="center" cellpadding="0" cellspacing="0" border="0" class="container560">

              <tr mc:repeatable>

                <td><table width="120" align="left" cellpadding="0" cellspacing="0" border="0" class="footer-item">

                    <tr>

                      <td align="center" mc:edit="footer-title1" style="color: #ffffff; font-size: 14px; font-weight: bold; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> APPLICATION </singleline></td>

                    </tr>

                    <tr>

                      <td height="25"></td>

                    </tr>

                    <tr>

                      <td align="center" mc:edit="footer-subtitle1" style="color: #efefef; font-size: 14px; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> <a href="http://itunes.apple.com/fr/app/youfid/id598935698?mt=8" style="color: #efefef; text-decoration: none;">Apple IOS</a> </singleline></td>

                    </tr>

                    <tr>

                      <td height="15"></td>

                    </tr>

                    <tr>

                      <td align="center" mc:edit="footer-subtitle2" style="color: #efefef; font-size: 13px; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> <a href="http://play.google.com/store/apps/details?id=com.fgsecure.youfidcustomer.app&hl=fr" style="color: #efefef; text-decoration: none;">Android</a> </singleline></td>

                    </tr>

                  </table>

                  <table border="0" width="20" align="left" cellpadding="0" cellspacing="0" class="hide-iphone">

                    <tr>

                      <td width="20" height="30"></td>

                    </tr>

                  </table>

                  <table width="120" align="left" cellpadding="0" cellspacing="0" border="0" class="footer-item">

                    <tr>

                      <td align="center" mc:edit="footer-title2" style="color: #ffffff; font-size: 14px; font-weight: bold; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline>COMMERCES</singleline></td>

                    </tr>

                    <tr>

                      <td height="25"></td>

                    </tr>

                    <tr>

                      <td align="center" mc:edit="footer-subtitle3" style="color: #efefef; font-size: 14px; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> <a href="http://youfid.fr/localisations" style="color: #efefef; text-decoration: none;">Localiser</a> </singleline></td>

                    </tr>

                    <tr>

                      <td height="15"></td>

                    </tr>

                    <tr>

                      <td align="center" mc:edit="footer-subtitle4" style="color: #efefef; font-size: 13px; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> <a href="#" style="color: #efefef; text-decoration: none;"></a> </singleline></td>

                    </tr>

                  </table>

                  <table border="0" width="20" align="left" cellpadding="0" cellspacing="0" class="vertical-spacing">

                    <tr>

                      <td width="20" height="30"></td>

                    </tr>

                  </table>

                  <table width="120" align="left" cellpadding="0" cellspacing="0" border="0" class="footer-item">

                    <tr>

                      <td align="center" mc:edit="footer-title3" style="color: #ffffff; font-size: 14px; font-weight: bold; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> FIDELITE </singleline></td>

                    </tr>

                    <tr>

                      <td height="25"></td>

                    </tr>

                    <tr>

                      <td align="center" mc:edit="footer-subtitle5" style="color: #efefef; font-size: 14px; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> <a href="http://www.fideliser.fr/" style="color: #efefef; text-decoration: none;">Devenir partenaire</a> </singleline></td>

                    </tr>

                    <tr>

                      <td height="15"></td>

                    </tr>

                    <tr>

                      <td align="center" mc:edit="footer-subtitle6" style="color: #efefef; font-size: 13px; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> <a href="#" style="color: #efefef; text-decoration: none;"></a> </singleline></td>

                    </tr>

                  </table>

                  <table border="0" width="20" align="left" cellpadding="0" cellspacing="0" class="hide-iphone">

                    <tr>

                      <td width="20" height="30"></td>

                    </tr>

                  </table>

                  <table width="140" align="left" cellpadding="0" cellspacing="0" border="0" class="footer-item">

                    <tr>

                      <td align="center" style="color: #ffffff; font-size: 14px; font-weight: bold; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> <a href="http://youfid.fr/" style="display: block; width: 119px;  border-style: none !important; border: 0 !important;"><img editable="true" mc:edit="logo2" width="119" height="50" border="0" style="display: block; width: 119px; height: 50px;" src="https://s3.eu-central-1.amazonaws.com/youfid/footer-logo.png" alt="mailto logo" /></a> </singleline></td>

                    </tr>

                    <tr>

                      <td height="25"></td>

                    </tr>

                    <tr>

                      <td align="center" mc:edit="footer-title7" style="color: #efefef; font-size: 13px; font-family: \'Slabo 13px\', Arial, sans-serif;"><singleline> <a href="http://youfid.fr/" style="color: #efefef !important; text-decoration: none !important;;">www.youfid.fr</a> </singleline></td>

                    </tr>

                  </table></td>

              </tr>

            </table></td>

        </tr>

        <tr>

          <td height="40"></td>

        </tr>

      </table></td>

  </tr>

      <!-------------- end footer ------------->



      <tr>

    <td><table width="600" cellpadding="0" align="center" cellspacing="0" border="0" class="container600">

        <tr>

          <td><img editable="false" src="http://business.youfid.fr/img/bottom-bg.png" alt="" style="display: block; width: 600px; height: 6px;" width="600" height="6" border="0" class="divider" /></td>

        </tr>

      </table></td>

  </tr>

      <tr>

    <td height="40"></td>

  </tr>

      <tr>

    <td><table width="600" cellpadding="0" align="center" cellspacing="0" border="0" class="container600">

        <tr>

          <td align="center" mc:edit="unsubscribe" style="color: #acacac; font-size: 14px; font-family: \'Slabo 13px\', Arial, sans-serif;" class="unsubscribe"><multiline>Cet email a été envoyé à <a href="mailto:[[EMAIL_TO]]" style="color: #34aadc; text-decoration: none;">[[EMAIL_TO]]</a><br> Si vous souhaitez vous désabonner de notre newsletter, <a href="http://api.youfid.fr/optout-confirm.php?email=[[EMAIL_TO]]" style="color: #34aadc; text-decoration: none;">cliquez-ici</a> </multiline></td>

        </tr>

        <tr>

          <td height="30"></td>

        </tr>

        <tr>

          <td align="center" mc:edit="copy" style="color: #8c8c8c; font-size: 15px; font-family: \'Slabo 13px\', Arial, sans-serif;" class="unsubscribe"><multiline> Copyright © YouFID 2016. </multiline></td>

        </tr>

        <tr>

          <td height="45"></td>

        </tr>

      </table></td>

  </tr>

    </table>

</body>

</html>';

