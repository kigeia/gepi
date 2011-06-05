<?php
/*
 * $Id$
 *
 * Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
 *
 * This file is part of GEPI.
 *
 * GEPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GEPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GEPI; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

// Initialisations files
require_once("../lib/initialisations.inc.php");

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
    header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
    die();
} else if ($resultat_session == '0') {
    header("Location: ../logout.php?auto=1");
    die();
}

// INSERT INTO droits VALUES ('/gestion/consult_prefs.php', 'V', 'V', 'F', 'F', 'F', 'F', 'F', 'D�finition des pr�f�rences d utilisateurs', '');
if (!checkAccess()) {
    header("Location: ../logout.php?auto=1");
    die();
}


/*
function getPref($login,$item,$default){
	$sql="SELECT value FROM preferences WHERE login='$login' AND name='$item'";
	$res_prefs=mysql_query($sql);

	if(mysql_num_rows($res_prefs)>0){
		$ligne=mysql_fetch_object($res_prefs);
		return $ligne->value;
	}
	else{
		return $default;
	}
}
*/
// Ajout de la possibilit� d'afficher ou pas le menu en barre horizontale
$afficherMenu = isset($_POST["afficher_menu"]) ? $_POST["afficher_menu"] : NULL;
$modifier_le_menu = isset($_POST["modifier_le_menu"]) ? $_POST["modifier_le_menu"] : NULL;
$modifier_entete_prof = isset($_POST['modifier_entete_prof']) ? $_POST['modifier_entete_prof'] : NULL;
$page = isset($_GET['page']) ? $_GET['page'] : (isset($_POST['page']) ? $_POST['page'] : NULL);
$prof = isset($_POST['prof']) ? $_POST['prof'] : NULL;
$enregistrer=isset($_POST['enregistrer']) ? $_POST['enregistrer'] : NULL;
$msg="";

if($_SESSION['statut']!="administrateur"){
	unset($prof);
	$prof = array($_SESSION['login']);
}
// +++++++++++++++++++++ MENU en barre horizontale ++++++++++++++++++++

	// Petite fonction pour d�terminer le checked="checked" des input en tenant compte des deux utilisations (admin et prof)
	function eval_checked($Settings, $yn, $statut, $nom){
		$aff_check = '';
		if ($statut == "professeur") {
			/*
			$req_setting = mysql_fetch_array(mysql_query("SELECT value FROM preferences WHERE login = '".$nom."' AND name = '".$Settings."'"))
								OR DIE ('Erreur requ�te eval_setting (prof) : '.mysql_error());
			*/
			$test=mysql_query("SELECT value FROM preferences WHERE login = '".$nom."' AND name = '".$Settings."'");
			if(mysql_num_rows($test)>0) {
				$req_setting = mysql_fetch_array($test);
			}
		}
		elseif ($statut == "administrateur") {

			$test=mysql_query("SELECT value FROM setting WHERE name = '".$Settings."'");
			if(mysql_num_rows($test)>0) {
				$req_setting = mysql_fetch_array($test);
			}
		}

		if((isset($req_setting["value"]))&&($req_setting["value"]==$yn)) {
			$aff_check = ' checked="checked"';
		}else {
			$aff_check = '';
		}

		return $aff_check;
	} //function eval_checked()

	// On traite si c'est demand�
			$messageMenu = '';
if ($modifier_le_menu == "ok") {
	check_token();

	// On fait la modif demand�e
	// pour l'administrateur g�n�ral
	if ($_SESSION["statut"] == "administrateur"){
		$sql = "UPDATE setting SET value = '".$afficherMenu."' WHERE name = 'utiliserMenuBarre'";
	// ou pour les professeurs
	}elseif ($_SESSION["statut"] == "professeur") {
		// Pour le prof, on v�rifie si ce r�glage existe ou pas
		$query = mysql_query("SELECT value FROM preferences WHERE name = 'utiliserMenuBarre' AND login = '".$_SESSION["login"]."'");
		$verif = mysql_num_rows($query);
		if ($verif == 1) {
			// S'il existe, on le modifie
			$sql = "UPDATE preferences SET value = '".$afficherMenu."' WHERE name = 'utiliserMenuBarre' AND login = '".$_SESSION["login"]."'";
		}else {
			// Sinon, on le cr�e
			$sql = "INSERT INTO preferences SET login = '".$_SESSION["login"]."', name = 'utiliserMenuBarre', value = '".$afficherMenu."'";
		}
	}
		// Dans tous les cas, on envoie la requ�te et on renvoie le message ad�quat.
		$requete = mysql_query($sql);
		if ($requete) {
			$messageMenu = "<p style=\"color: green\">La modification a �t� enregistr�e</p>";
		}else{
			$messageMenu = "<p style=\"color: red\">La modification a �chou�, vous devriez mettre � jour votre base
							 avant de poursuivre</p>";
		}
} // fin du if ($modifier_le_menu...
// +++++++++++++++++++++ FIN -- MENU en barre horizontale -- FIN ++++++++++++++++++++

// ====== hauteur du header ======= //
	$message_header_prof = NULL;

if ($modifier_entete_prof == 'ok') {
	check_token();

	// On traite alors la demande
	$reglage = isset($_POST['header_bas']) ? $_POST['header_bas'] : 'n';

	if (saveSetting('impose_petit_entete_prof', $reglage)) {
		$message_header_prof = '<p style="color: green;">Modification enregistr�e</p>';
	}else{
		$message_header_prof = '<p style="color: red;">Impossible d\'enregistrer la modification</p>';
	}
}



// Tester les valeurs de $page
// Les valeurs autoris�es sont (actuellement): accueil, add_modif_dev, add_modif_conteneur
//if(isset($page)){
if((isset($page))&&($_SESSION['statut']=="administrateur")){
	if(($page!="accueil_simpl")&&($page!="add_modif_dev")&&($page!="add_modif_conteneur")){
		$page=NULL;
		$enregistrer=NULL;
		$msg="La page choisie ne convient pas.";
	}
}

if(isset($enregistrer)) {
	check_token();
	for($i=0;$i<count($prof);$i++){
		//if($page=='accueil_simpl'){
		if(($page=='accueil_simpl')||($_SESSION['statut']=='professeur')){
			//$tab=array('accueil_simpl','accueil_ct','accueil_cn','accueil_bull','accueil_visu','accueil_trombino','accueil_liste_pdf','accueil_aff_txt_icon');
			$tab=array('accueil_simpl','accueil_infobulles','accueil_ct','accueil_cn','accueil_bull','accueil_visu','accueil_trombino','accueil_liste_pdf');

			for($j=0;$j<count($tab);$j++){
				unset($valeur);
				//$valeur=isset($_POST[$tab[$j]]) ? $_POST[$tab[$j]] : NULL;
				$tmp_champ=$tab[$j]."_".$i;
				$valeur=isset($_POST[$tmp_champ]) ? $_POST[$tmp_champ] : NULL;

				$sql="DELETE FROM preferences WHERE login='".$prof[$i]."' AND name='".$tab[$j]."'";
				//echo $sql."<br />\n";
				$res_suppr=mysql_query($sql);

				if(isset($valeur)){
					$sql="INSERT INTO preferences SET login='".$prof[$i]."', name='".$tab[$j]."', value='$valeur'";
					//echo $sql."<br />\n";
					if($res_insert=mysql_query($sql)){
					}
					else{
						$msg.="Erreur lors de l'enregistrement de $tab[$j] pour $prof[$i]<br />\n";
					}
				}
				else{
					$sql="INSERT INTO preferences SET login='".$prof[$i]."', name='".$tab[$j]."', value='n'";
					//echo $sql."<br />\n";
					if($res_insert=mysql_query($sql)){
					}
					else{
						$msg.="Erreur lors de l'enregistrement de $tab[$j] pour $prof[$i]<br />\n";
					}
				}
			}
		}

		if(($page=='add_modif_dev')||($_SESSION['statut']=='professeur')){
			//$tab=array('add_modif_dev_simpl','add_modif_dev_nom_court','add_modif_dev_nom_complet','add_modif_dev_description','add_modif_dev_coef','add_modif_dev_note_autre_que_referentiel','add_modif_dev_date','add_modif_dev_boite');
			$tab=array('add_modif_dev_simpl','add_modif_dev_nom_court','add_modif_dev_nom_complet','add_modif_dev_description','add_modif_dev_coef','add_modif_dev_note_autre_que_referentiel','add_modif_dev_date','add_modif_dev_date_ele_resp','add_modif_dev_boite');
			for($j=0;$j<count($tab);$j++){
				unset($valeur);
				//$valeur=isset($_POST[$tab[$j]]) ? $_POST[$tab[$j]] : NULL;
				$tmp_champ=$tab[$j]."_".$i;
				$valeur=isset($_POST[$tmp_champ]) ? $_POST[$tmp_champ] : NULL;

				$sql="DELETE FROM preferences WHERE login='".$prof[$i]."' AND name='".$tab[$j]."'";
				//echo $sql."<br />\n";
				$res_suppr=mysql_query($sql);

				if(isset($valeur)){
					$sql="INSERT INTO preferences SET login='".$prof[$i]."', name='".$tab[$j]."', value='$valeur'";
					//echo $sql."<br />\n";
					if($res_insert=mysql_query($sql)){
					}
					else{
						$msg.="Erreur lors de l'enregistrement de $tab[$j] pour $prof[$i]<br />\n";
					}
				}
				else{
					$sql="INSERT INTO preferences SET login='".$prof[$i]."', name='".$tab[$j]."', value='n'";
					//echo $sql."<br />\n";
					if($res_insert=mysql_query($sql)){
					}
					else{
						$msg.="Erreur lors de l'enregistrement de $tab[$j] pour $prof[$i]<br />\n";
					}
				}
			}
		}

		if(($page=='add_modif_conteneur')||($_SESSION['statut']=='professeur')){
			$tab=array('add_modif_conteneur_simpl','add_modif_conteneur_nom_court','add_modif_conteneur_nom_complet','add_modif_conteneur_description','add_modif_conteneur_coef','add_modif_conteneur_boite','add_modif_conteneur_aff_display_releve_notes','add_modif_conteneur_aff_display_bull');
			for($j=0;$j<count($tab);$j++){
				unset($valeur);
				//$valeur=isset($_POST[$tab[$j]]) ? $_POST[$tab[$j]] : NULL;
				$tmp_champ=$tab[$j]."_".$i;
				$valeur=isset($_POST[$tmp_champ]) ? $_POST[$tmp_champ] : NULL;

				$sql="DELETE FROM preferences WHERE login='".$prof[$i]."' AND name='".$tab[$j]."'";
				//echo $sql."<br />\n";
				$res_suppr=mysql_query($sql);

				if(isset($valeur)){
					$sql="INSERT INTO preferences SET login='".$prof[$i]."', name='".$tab[$j]."', value='$valeur'";
					//echo $sql."<br />\n";
					if($res_insert=mysql_query($sql)){
					}
					else{
						$msg.="Erreur lors de l'enregistrement de $tab[$j] pour $prof[$i]<br />\n";
					}
				}
				else{
					$sql="INSERT INTO preferences SET login='".$prof[$i]."', name='".$tab[$j]."', value='n'";
					//echo $sql."<br />\n";
					if($res_insert=mysql_query($sql)){
					}
					else{
						$msg.="Erreur lors de l'enregistrement de $tab[$j] pour $prof[$i]<br />\n";
					}
				}
			}
		}

		if ($_SESSION['statut']=='professeur') {
			$aff_quartiles_cn=isset($_POST['aff_quartiles_cn']) ? $_POST['aff_quartiles_cn'] : "n";

			$sql="SELECT * FROM preferences WHERE login='".$_SESSION['login']."' AND name='aff_quartiles_cn';";
			$test=mysql_query($sql);
			if(mysql_num_rows($test)==0) {
				$sql="INSERT INTO preferences SET login='".$_SESSION['login']."', name='aff_quartiles_cn', value='$aff_quartiles_cn';";
				//echo $sql."<br />\n";
				if(!mysql_query($sql)){
					$msg.="Erreur lors de l'enregistrement de aff_quartiles_cn<br />\n";
					//$msg.="Erreur lors de l'enregistrement de l'affichage par d�faut ou non des moyenne, m�diane, quartiles,... sur les carnets de notes.<br />\n";
				}
			}
			else {
				$sql="UPDATE preferences SET value='$aff_quartiles_cn' WHERE login='".$_SESSION['login']."' AND name='aff_quartiles_cn';";
				//echo $sql."<br />\n";
				if(!mysql_query($sql)){
					$msg.="Erreur lors de l'enregistrement de aff_quartiles_cn pour ".$_SESSION['login']."<br />\n";
				}
			}


			$aff_photo_cn=isset($_POST['aff_photo_cn']) ? $_POST['aff_photo_cn'] : "n";

			$sql="SELECT * FROM preferences WHERE login='".$_SESSION['login']."' AND name='aff_photo_cn';";
			$test=mysql_query($sql);
			if(mysql_num_rows($test)==0) {
				$sql="INSERT INTO preferences SET login='".$_SESSION['login']."', name='aff_photo_cn', value='$aff_photo_cn';";
				//echo $sql."<br />\n";
				if(!mysql_query($sql)){
					$msg.="Erreur lors de l'enregistrement de aff_photo_cn<br />\n";
					//$msg.="Erreur lors de l'enregistrement de l'affichage par d�faut ou non des moyenne, m�diane, photo,... sur les carnets de notes.<br />\n";
				}
			}
			else {
				$sql="UPDATE preferences SET value='$aff_photo_cn' WHERE login='".$_SESSION['login']."' AND name='aff_photo_cn';";
				//echo $sql."<br />\n";
				if(!mysql_query($sql)){
					$msg.="Erreur lors de l'enregistrement de aff_photo_cn pour ".$_SESSION['login']."<br />\n";
				}
			}


			$aff_photo_saisie_app=isset($_POST['aff_photo_saisie_app']) ? $_POST['aff_photo_saisie_app'] : "n";

			$sql="SELECT * FROM preferences WHERE login='".$_SESSION['login']."' AND name='aff_photo_saisie_app';";
			$test=mysql_query($sql);
			if(mysql_num_rows($test)==0) {
				$sql="INSERT INTO preferences SET login='".$_SESSION['login']."', name='aff_photo_saisie_app', value='$aff_photo_saisie_app'";
				//echo $sql."<br />\n";
				if(!mysql_query($sql)){
					$msg.="Erreur lors de l'enregistrement de aff_photo_saisie_app<br />\n";
					//$msg.="Erreur lors de l'enregistrement de l'affichage par d�faut ou non des moyenne, m�diane, quartiles,... sur les carnets de notes.<br />\n";
				}
			}
			else {
				$sql="UPDATE preferences SET value='$aff_photo_saisie_app' WHERE login='".$_SESSION['login']."' AND name='aff_photo_saisie_app';";
				//echo $sql."<br />\n";
				if(!mysql_query($sql)){
					$msg.="Erreur lors de l'enregistrement de $tab[$j] pour ".$_SESSION['login']."<br />\n";
				}
			}

		}
	}

	if($msg==""){
		$msg="Enregistrement r�ussi.";
	}

	//unset($page);
}

// Style sp�cifique pour la page:
$style_specifique="gestion/config_prefs";

// Couleur pour les cases dans lesquelles une modif est faite:
$couleur_modif='orange';

// Message d'alerte pour ne pas quitter par erreur sans valider:
$themessage="Des modifications ont �t� effectu�es. Voulez-vous vraiment quitter sans enregistrer?";


//**************** EN-TETE *****************
$titre_page = "Configuration des interfaces simplifi�es";
require_once("../lib/header.inc");
//**************** FIN EN-TETE *****************

//debug_var();

// Initialisation de la variable utilis�e pour noter si des modifications ont �t� effectu�es dans la page.
echo "<script type='text/javascript'>
	change='no';
</script>\n";

/*
- Choisir la page � afficher
- Choisir les profs? ou juste r�p�ter la ligne de titre?
*/

echo "<form enctype=\"multipart/form-data\" name= \"formulaire\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n";
echo "<div class='norme'><p class=bold>";
echo "<a href='";
if($_SESSION['statut']=='administrateur'){
	echo "index.php#config_prefs";
}
else{
	echo "../accueil.php";
}
echo "' onclick=\"return confirm_abandon (this, change, '$themessage')\"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>\n";

//if(!isset($page)){
if((!isset($page))&&($_SESSION['statut']=="administrateur")){
	echo "</div>\n";

	echo "<p>Cette page permet de configurer l'interface simplifi�e pour:</p>\n";
	echo "<ul>\n";
	echo "<li><a href='".$_SERVER['PHP_SELF']."?page=accueil_simpl'>Page d'accueil simplifi�e pour les ".$gepiSettings['denomination_professeurs']."</a></li>\n";
	echo "<li><a href='".$_SERVER['PHP_SELF']."?page=add_modif_dev'>Page de cr�ation d'�valuation</a></li>\n";
	echo "<li><a href='".$_SERVER['PHP_SELF']."?page=add_modif_conteneur'>Page de cr�ation de ".strtolower(getSettingValue("gepi_denom_boite"))."</a></li>\n";
	echo "</ul>\n";

}
else{
	if($_SESSION['statut']=="administrateur"){
		echo " | <a href='".$_SERVER['PHP_SELF']."' onclick=\"return confirm_abandon (this, change, '$themessage')\">Choix de la page</a>";
	}
	echo "</div>\n";

	echo add_token_field();

	unset($prof);
	$prof=array();
	if($_SESSION['statut']=="administrateur"){

		//$sql="SELECT DISTINCT nom,prenom,login FROM utilisateurs WHERE statut='professeur' ORDER BY nom, prenom";
		$sql="SELECT DISTINCT nom,prenom,login FROM utilisateurs WHERE statut='professeur' AND etat='actif' ORDER BY nom, prenom";
		$res_prof=mysql_query($sql);
		if(mysql_num_rows($res_prof)==0){
			echo "<p>Aucun ".$gepiSettings['denomination_professeur']." n'est encore d�fini.<br />Commencez par cr�er les comptes ".$gepiSettings['denomination_professeurs'].".</p>\n";
			require("../lib/footer.inc.php");
			die();
		}

		$i=0;
		while($lig_prof=mysql_fetch_object($res_prof)){
			$prof[$i]=array();
			$prof[$i]['login']=$lig_prof->login;
			$prof[$i]['nom']=$lig_prof->nom;
			$prof[$i]['prenom']=$lig_prof->prenom;
			$i++;
		}
	}
	else{
		$i=0;
		$prof[$i]['login']=$_SESSION['login'];
		$prof[$i]['nom']=$_SESSION['nom'];
		$prof[$i]['prenom']=$_SESSION['prenom'];
	}

	$nb_profs=count($prof);


	function cellule_checkbox($prof_login,$item,$num,$special){
		echo "<td align='center'";
		echo " id='td_".$item."_".$num."' ";
		//echo " style='text-align:center; ";
		$checked="";
		$coche="";
		$sql="SELECT * FROM preferences WHERE login='$prof_login' AND name='$item'";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)>0){
			$lig_test=mysql_fetch_object($test);
			if($lig_test->value=="y"){
				//echo " style='background-color: lightgreen;'";
				//echo "background-color: lightgreen;";
				echo " class='coche'";
				$checked=" checked";
				$coche="y";
			}
			else{
				//echo " style='background-color: lightgray;'";
				//echo "background-color: lightgray;";
				echo " class='decoche'";
				$coche="n";
			}
		}
		//echo "'";
		echo ">";
		echo "<input type='checkbox' name='$item"."_"."$num' id='$item"."_"."$num' value='y'";

		/*
		// Supprim� apr�s avoir permis l'affichage des tableaux sur une seule page pour l'acc�s prof � ses propres param�trages
		if($special=="y"){
			echo " onchange=\"modif_ligne($num)\"";
		}
		*/

		echo $checked;
		//echo " onchange='changement();'";
		echo " onchange=\"changement_et_couleur('$item"."_"."$num','";
		//if($special=="y"){
		if($special!=''){
			//echo "td_nomprenom_$num";
			//echo "td_nomprenom_".$num."_".$special;
			$chaine_td="td_nomprenom_".$num."_".$special;
			echo $chaine_td;
		}
		echo "');\"";
		echo " />";

		//if($special=="y"){
		if($special!=''){
			if($coche=="y"){
				echo "<script type='text/javascript'>
	//document.getElementById('td_nomprenom_'+$num).style.backgroundColor='lightgreen';
	document.getElementById('$chaine_td').style.backgroundColor='lightgreen';
</script>\n";
			}
			elseif($coche=="n"){
				echo "<script type='text/javascript'>
	//document.getElementById('td_nomprenom_'+$num).style.backgroundColor='lightgray';
	document.getElementById('$chaine_td').style.backgroundColor='lightgray';
</script>\n";
			}
		}

		echo "</td>\n";
	} // FIN function cellule_checkbox


/*
	echo "<style type='text/css'>
	table.contenu {
		border: 1px solid black;
		border-collapse: collapse;
	}

	.contenu th {
		font-weight:bold;
		text-align: center;
		background-color: white;
		border: 1px solid black;
	}

	.contenu td {
		vertical-align: middle;
		text-align: center;
		border: 1px solid black;
	}

	.contenu tr.entete {
		background-color: white;
	}

	.contenu .coche {
		background-color: lightgreen;
	}

	.contenu .decoche {
		background-color: lightgray;
	}
</style>\n";
*/

	echo "<p align='center'><input type=\"submit\" name='enregistrer' value=\"Valider\" style=\"font-variant: small-caps;\" /></p>\n";

	//if($page=="accueil_simpl"){
	if(($page=="accueil_simpl")||($_SESSION['statut']=='professeur')){
		echo "<p>Param�trage de la page d'<b>accueil</b> simplifi�e pour les ".$gepiSettings['denomination_professeurs'].".</p>\n";

		//$tabchamps=array('accueil_simpl','accueil_ct','accueil_trombino','accueil_cn','accueil_bull','accueil_visu','accueil_liste_pdf');
		//accueil_aff_txt_icon
		$tabchamps=array('accueil_simpl','accueil_infobulles','accueil_ct','accueil_trombino','accueil_cn','accueil_bull','accueil_visu','accueil_liste_pdf');

		//echo "<table border='1'>\n";
		echo "<table class='contenu' border='1' summary='Pr�f�rences professeurs'>\n";

		// 1�re ligne
		//$lignes_entete="<tr style='background-color: white;'>\n";
		$lignes_entete="<tr class='entete'>\n";
		if($_SESSION['statut']!='professeur'){
			$lignes_entete.="<th rowspan='3'>".$gepiSettings['denomination_professeur']."</th>\n";
		}
		else{
			$lignes_entete.="<th rowspan='2'>".$gepiSettings['denomination_professeur']."</th>\n";
		}
		$lignes_entete.="<th rowspan='2'>Utiliser l'interface simplifi�e</th>\n";
		$lignes_entete.="<th rowspan='2'>Afficher les infobulles</th>\n";
		$lignes_entete.="<th colspan='6'>Afficher les liens pour</th>\n";
		if($_SESSION['statut']!='professeur') {$lignes_entete.="<th rowspan='3'>Tout cocher / d�cocher</th>\n";}
		$lignes_entete.="</tr>\n";

		// 2�me ligne
		//$lignes_entete.="<tr style='background-color: white;'>\n";
		$lignes_entete.="<tr class='entete'>\n";
		$lignes_entete.="<th>le Cahier de textes</th>\n";
		$lignes_entete.="<th>le Trombinoscope</th>\n";
		$lignes_entete.="<th>le Carnet de notes</th>\n";
		$lignes_entete.="<th>les notes et appr�ciations des Bulletins</th>\n";
		$lignes_entete.="<th>la Visualisation des graphes et bulletins simplifi�s</th>\n";
		$lignes_entete.="<th>les Listes PDF des �l�ves</th>\n";
		$lignes_entete.="</tr>\n";

		// 3�me ligne
		if($_SESSION['statut']!='professeur'){
			//$lignes_entete.="<tr style='background-color: white;'>\n";
			$lignes_entete.="<tr class='entete'>\n";
			for($i=0;$i<count($tabchamps);$i++){
				$lignes_entete.="<th>";
				$lignes_entete.="<a href='javascript:modif_coche(\"$tabchamps[$i]\",true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
				$lignes_entete.="<a href='javascript:modif_coche(\"$tabchamps[$i]\",false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
				$lignes_entete.="</th>\n";
			}
			$lignes_entete.="</tr>\n";
		}

		//$i=0;
		//while($lig_prof=mysql_fetch_object($res_prof)){
		for($i=0;$i<count($prof);$i++){
			if($i-ceil($i/10)*10==0){
				echo $lignes_entete;
			}

			echo "<tr>\n";

			//echo "<td id='td_nomprenom_".$i."'>";
			echo "<td id='td_nomprenom_".$i."_accueil_simpl'>";
			//echo strtoupper($lig_prof->nom)." ".ucfirst(strtolower($lig_prof->prenom));
			echo strtoupper($prof[$i]['nom'])." ".ucfirst(strtolower($prof[$i]['prenom']));
			//echo "<input type='hidden' name='prof[$i]' value='$lig_prof->login' />";
			echo "<input type='hidden' name='prof[$i]' value='".$prof[$i]['login']."' />";
			echo "</td>\n";

			/*
			cellule_checkbox($prof[$i]['login'],'accueil_simpl',$i,'y');

			cellule_checkbox($prof[$i]['login'],'accueil_ct',$i,'');
			cellule_checkbox($prof[$i]['login'],'accueil_trombino',$i,'');
			cellule_checkbox($prof[$i]['login'],'accueil_cn',$i,'');
			cellule_checkbox($prof[$i]['login'],'accueil_bull',$i,'');
			cellule_checkbox($prof[$i]['login'],'accueil_visu',$i,'');
			cellule_checkbox($prof[$i]['login'],'accueil_liste_pdf',$i,'');
			*/

			$j=0;
			//cellule_checkbox($prof[$i]['login'],$tabchamps[$j],$i,'y');
			cellule_checkbox($prof[$i]['login'],$tabchamps[$j],$i,'accueil_simpl');
			//for($j=0;$j<count($tabchamps);$j++){
			for($j=1;$j<count($tabchamps);$j++){
				cellule_checkbox($prof[$i]['login'],$tabchamps[$j],$i,'');
			}

			if($_SESSION['statut']!='professeur') {
				echo "<th>";
				echo "<a href='javascript:coche_ligne($i,true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
				echo "<a href='javascript:coche_ligne($i,false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
				echo "</th>\n";
			}

			echo "</tr>\n";
			//$i++;
		}

		echo "</table>\n";
	}







	if($_SESSION['statut']=='professeur') {
		echo "<p><br /></p>\n";
		echo "<p><b>Param�tres du carnet de notes&nbsp;:</b></p>\n";

		$sql="SELECT * FROM preferences WHERE login='".$_SESSION['login']."' AND name='aff_quartiles_cn'";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)==0) {
			$aff_quartiles_cn="n";
		}
		else {
			$lig_test=mysql_fetch_object($test);
			$aff_quartiles_cn=$lig_test->value;
		}
		echo "<p>\n";
		echo "<input type='checkbox' name='aff_quartiles_cn' id='aff_quartiles_cn' value='y' ";
		if($aff_quartiles_cn=='y') {echo 'checked';}
		echo "/><label for='aff_quartiles_cn'> Afficher par d�faut, les moyenne, m�diane, quartiles, min, max sur les carnets de notes.</label>\n";
		echo "</p>\n";

		$sql="SELECT * FROM preferences WHERE login='".$_SESSION['login']."' AND name='aff_photo_cn'";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)==0) {
			$aff_photo_cn="n";
		}
		else {
			$lig_test=mysql_fetch_object($test);
			$aff_photo_cn=$lig_test->value;
		}
		echo "<p>\n";
		echo "<input type='checkbox' name='aff_photo_cn' id='aff_photo_cn' value='y' ";
		if($aff_photo_cn=='y') {echo 'checked';}
		echo "/><label for='aff_photo_cn'> Afficher par d�faut la photo des �l�ves sur les carnets de notes.</label>\n";
		echo "</p>\n";
	}



	if(($page=="add_modif_dev")||($_SESSION['statut']=='professeur')){
		echo "<p>Param�trage de la page de <b>cr�ation d'�valuation</b> pour les ".$gepiSettings['denomination_professeurs']."</p>\n";

		if(getSettingValue("note_autre_que_sur_referentiel")=="V") {
			//$tabchamps=array( 'add_modif_dev_simpl','add_modif_dev_nom_court','add_modif_dev_nom_complet','add_modif_dev_description','add_modif_dev_coef','add_modif_dev_note_autre_que_referentiel','add_modif_dev_date','add_modif_dev_boite');
			$tabchamps=array( 'add_modif_dev_simpl','add_modif_dev_nom_court','add_modif_dev_nom_complet','add_modif_dev_description','add_modif_dev_coef','add_modif_dev_note_autre_que_referentiel','add_modif_dev_date','add_modif_dev_date_ele_resp','add_modif_dev_boite');
		} else {
			//$tabchamps=array( 'add_modif_dev_simpl','add_modif_dev_nom_court','add_modif_dev_nom_complet','add_modif_dev_description','add_modif_dev_coef','add_modif_dev_date','add_modif_dev_boite');	
			$tabchamps=array( 'add_modif_dev_simpl','add_modif_dev_nom_court','add_modif_dev_nom_complet','add_modif_dev_description','add_modif_dev_coef','add_modif_dev_date','add_modif_dev_date_ele_resp','add_modif_dev_boite');	
		}
		//echo "<table border='1'>\n";
		echo "<table class='contenu' border='1' summary='Pr�f�rences professeurs'>\n";

		// 1�re ligne
		//$lignes_entete.="<tr style='background-color: white;'>\n";
		$lignes_entete="<tr class='entete'>\n";
		if($_SESSION['statut']!='professeur'){
			$lignes_entete.="<th rowspan='3'>".$gepiSettings['denomination_professeur']."</th>\n";
		}
		else{
			$lignes_entete.="<th rowspan='2'>".$gepiSettings['denomination_professeur']."</th>\n";
		}
		$lignes_entete.="<th rowspan='2'>Utiliser l'interface simplifi�e</th>\n";
		if(getSettingValue("note_autre_que_sur_referentiel")=="V") {
			$lignes_entete.="<th colspan='8'>Afficher les champs</th>\n";
		} else {
			$lignes_entete.="<th colspan='7'>Afficher les champs</th>\n";
		}
		if($_SESSION['statut']!='professeur') {$lignes_entete.="<th rowspan='3'>Tout cocher / d�cocher</th>\n";}
		$lignes_entete.="</tr>\n";

		// 2�me ligne
		//$lignes_entete.="<tr style='background-color: white;'>\n";
		$lignes_entete.="<tr class='entete'>\n";
		$lignes_entete.="<th>Nom court</th>\n";
		$lignes_entete.="<th>Nom complet</th>\n";
		$lignes_entete.="<th>Description</th>\n";
		$lignes_entete.="<th>Coefficient</th>\n";
		if(getSettingValue("note_autre_que_sur_referentiel")=="V") {
			$lignes_entete.="<th>Note autre que sur le referentiel</th>\n";
		}
		$lignes_entete.="<th>Date</th>\n";
		$lignes_entete.="<th>Date ele/resp</th>\n";
		$lignes_entete.="<th>".ucfirst(strtolower(getSettingValue("gepi_denom_boite")))."</th>\n";
		$lignes_entete.="</tr>\n";

		// 3�me ligne
		if($_SESSION['statut']!='professeur'){
			//$lignes_entete.="<tr style='background-color: white;'>\n";
			$lignes_entete.="<tr class='entete'>\n";
			for($i=0;$i<count($tabchamps);$i++){
				$lignes_entete.="<th>";
				$lignes_entete.="<a href='javascript:modif_coche(\"$tabchamps[$i]\",true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
				$lignes_entete.="<a href='javascript:modif_coche(\"$tabchamps[$i]\",false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
				$lignes_entete.="</th>\n";
			}
			$lignes_entete.="</tr>\n";
		}

		//$i=0;
		//while($lig_prof=mysql_fetch_object($res_prof)){
		for($i=0;$i<count($prof);$i++){
			if($i-ceil($i/10)*10==0){
				echo $lignes_entete;
			}

			echo "<tr>\n";

			//echo "<td>";
			//echo "<td id='td_nomprenom_".$i."'>";
			echo "<td id='td_nomprenom_".$i."_add_modif_dev'>";
			//echo strtoupper($lig_prof->nom)." ".ucfirst(strtolower($lig_prof->prenom));
			echo strtoupper($prof[$i]['nom'])." ".ucfirst(strtolower($prof[$i]['prenom']));
			//echo "<input type='hidden' name='prof[$i]' value='$lig_prof->login' />";
			echo "<input type='hidden' name='prof[$i]' value='".$prof[$i]['login']."' />";
			echo "</td>\n";

			$j=0;
			//cellule_checkbox($prof[$i]['login'],$tabchamps[$j],$i,'y');
			cellule_checkbox($prof[$i]['login'],$tabchamps[$j],$i,'add_modif_dev');
			//for($j=0;$j<count($tabchamps);$j++){
			for($j=1;$j<count($tabchamps);$j++){
				cellule_checkbox($prof[$i]['login'],$tabchamps[$j],$i,'');
			}

			if($_SESSION['statut']!='professeur') {
				echo "<th>";
				echo "<a href='javascript:coche_ligne($i,true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
				echo "<a href='javascript:coche_ligne($i,false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
				echo "</th>\n";
			}

			echo "</tr>\n";
			//$i++;
		}

		echo "</table>\n";
	}


	if(($page=="add_modif_conteneur")||($_SESSION['statut']=='professeur')){
		echo "<p>Param�trage de la page de <b>cr�ation de ".ucfirst(strtolower(getSettingValue("gepi_denom_boite")))."</b> pour les ".$gepiSettings['denomination_professeurs']."</p>\n";

		$tabchamps=array('add_modif_conteneur_simpl','add_modif_conteneur_nom_court','add_modif_conteneur_nom_complet','add_modif_conteneur_description','add_modif_conteneur_coef','add_modif_conteneur_boite','add_modif_conteneur_aff_display_releve_notes','add_modif_conteneur_aff_display_bull');

		//echo "<table border='1'>\n";
		echo "<table class='contenu' border='1' summary='Pr�f�rences professeurs'>\n";

		// 1�re ligne
		//$lignes_entete.="<tr style='background-color: white;'>\n";
		$lignes_entete="<tr class='entete'>\n";
		if($_SESSION['statut']!='professeur'){
			$lignes_entete.="<th rowspan='3'>".$gepiSettings['denomination_professeur']."</th>\n";
		}
		else{
			$lignes_entete.="<th rowspan='2'>".$gepiSettings['denomination_professeur']."</th>\n";
		}
		$lignes_entete.="<th rowspan='2'>Utiliser l'interface simplifi�e</th>\n";
		$lignes_entete.="<th colspan='7'>Afficher les champs</th>\n";
		if($_SESSION['statut']!='professeur') {$lignes_entete.="<th rowspan='3'>Tout cocher / d�cocher</th>\n";}
		$lignes_entete.="</tr>\n";

		// 2�me ligne
		//$lignes_entete.="<tr style='background-color: white;'>\n";
		$lignes_entete.="<tr class='entete'>\n";
		$lignes_entete.="<th>Nom court</th>\n";
		$lignes_entete.="<th>Nom complet</th>\n";
		$lignes_entete.="<th>Description</th>\n";
		$lignes_entete.="<th>Coefficient</th>\n";
		$lignes_entete.="<th>".ucfirst(strtolower(getSettingValue("gepi_denom_boite")))."</th>\n";
		$lignes_entete.="<th>Afficher sur le relev� de notes</th>\n";
		$lignes_entete.="<th>Afficher sur le bulletin</th>\n";
		$lignes_entete.="</tr>\n";

		// 3�me ligne
		if($_SESSION['statut']!='professeur'){
			//$lignes_entete.="<tr style='background-color: white;'>\n";
			$lignes_entete.="<tr class='entete'>\n";
			for($i=0;$i<count($tabchamps);$i++){
				$lignes_entete.="<th>";
				$lignes_entete.="<a href='javascript:modif_coche(\"$tabchamps[$i]\",true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
				$lignes_entete.="<a href='javascript:modif_coche(\"$tabchamps[$i]\",false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
				$lignes_entete.="</th>\n";
			}
			$lignes_entete.="</tr>\n";
		}

		//$i=0;
		//while($lig_prof=mysql_fetch_object($res_prof)){
		for($i=0;$i<count($prof);$i++){
			if($i-ceil($i/10)*10==0){
				echo $lignes_entete;
			}

			echo "<tr>\n";

			//echo "<td>";
			//echo "<td id='td_nomprenom_".$i."'>";
			echo "<td id='td_nomprenom_".$i."_add_modif_conteneur'>";
			//echo strtoupper($lig_prof->nom)." ".ucfirst(strtolower($lig_prof->prenom));
			echo strtoupper($prof[$i]['nom'])." ".ucfirst(strtolower($prof[$i]['prenom']));
			//echo "<input type='hidden' name='prof[$i]' value='$lig_prof->login' />";
			echo "<input type='hidden' name='prof[$i]' value='".$prof[$i]['login']."' />";
			echo "</td>\n";

			$j=0;
			//cellule_checkbox($prof[$i]['login'],$tabchamps[$j],$i,'y');
			cellule_checkbox($prof[$i]['login'],$tabchamps[$j],$i,'add_modif_conteneur');
			//for($j=0;$j<count($tabchamps);$j++){
			for($j=1;$j<count($tabchamps);$j++){
				cellule_checkbox($prof[$i]['login'],$tabchamps[$j],$i,'');
			}

			if($_SESSION['statut']!='professeur') {
				echo "<th>";
				echo "<a href='javascript:coche_ligne($i,true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
				echo "<a href='javascript:coche_ligne($i,false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
				echo "</th>\n";
			}
			echo "</tr>\n";
			//$i++;
		}

		echo "</table>\n";
	}





	if($_SESSION['statut']=='professeur') {
		echo "<p><br /></p>\n";
		echo "<p><b>Param�tres de saisie des appr�ciations&nbsp;:</b></p>\n";

		$sql="SELECT * FROM preferences WHERE login='".$_SESSION['login']."' AND name='aff_photo_saisie_app'";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)==0) {
			$aff_photo_saisie_app="n";
		}
		else {
			$lig_test=mysql_fetch_object($test);
			$aff_photo_saisie_app=$lig_test->value;
		}

		echo "<p>\n";
		echo "<input type='checkbox' name='aff_photo_saisie_app' id='aff_photo_saisie_app' value='y' ";
		if($aff_photo_saisie_app=='y') {echo 'checked';}
		echo "/><label for='aff_photo_saisie_app'> Afficher par d�faut les photos des �l�ves lors de la saisie des appr�ciations sur les bulletins.</label>\n";
		echo "</p>\n";
	}





	// La page n'est consid�r�e que pour l'admin pour r�duire la longueur de la liste
	if($_SESSION['statut']=='administrateur'){
		echo "<input type=\"hidden\" name='page' value=\"$page\" />\n";
	}

	echo "<p align='center'><input type=\"submit\" name='enregistrer' value=\"Valider\" style=\"font-variant: small-caps;\" /></p>\n";

	echo "<script type='text/javascript' language='javascript'>
	function modif_coche(item,statut){
		// statut: true ou false
		for(k=0;k<$nb_profs;k++){
			if(document.getElementById(item+'_'+k)){
				document.getElementById(item+'_'+k).checked=statut;

				document.getElementById('td_'+item+'_'+k).style.backgroundColor='$couleur_modif';
			}
		}
		changement();
	}

	tab_item=new Array('accueil_simpl','accueil_infobulles','accueil_ct','accueil_cn','accueil_bull','accueil_visu','accueil_trombino','accueil_liste_pdf','add_modif_conteneur_simpl','add_modif_conteneur_nom_court','add_modif_conteneur_nom_complet','add_modif_conteneur_description','add_modif_conteneur_coef','add_modif_conteneur_boite','add_modif_conteneur_aff_display_releve_notes','add_modif_conteneur_aff_display_bull','add_modif_dev_simpl','add_modif_dev_nom_court','add_modif_dev_nom_complet','add_modif_dev_description','add_modif_dev_coef','add_modif_dev_note_autre_que_referentiel','add_modif_dev_date','add_modif_dev_date_ele_resp','add_modif_dev_boite');
	function coche_ligne(ligne,statut){
		// statut: true ou false
		for(k=0;k<tab_item.length;k++){
			if(document.getElementById(tab_item[k]+'_'+ligne)){
				document.getElementById(tab_item[k]+'_'+ligne).checked=statut;

				document.getElementById('td_'+tab_item[k]+'_'+ligne).style.backgroundColor='$couleur_modif';
			}
		}
		changement();
	}

	function changement_et_couleur(id,special){
		if(document.getElementById(id)){
			document.getElementById('td_'+id).style.backgroundColor='$couleur_modif';
		}

		if(special!=''){
			document.getElementById(special).style.backgroundColor='$couleur_modif';
		}

		changement();
	}
";

	/*
	echo "
	function modif_ligne(num){";

	$liste_champs="";
	for($k=0;$k<count($tabchamps);$k++){
		if($k>0){$liste_champs.=", ";}
		$liste_champs.="'$tabchamps[$k]'";
	}

		echo "
		tabchamps=Array($liste_champs);
		for(k=0;k<tabchamps.length;k++){
			item=tabchamps[k];
			if(document.getElementById('td_'+item+'_'+num)){
				document.getElementById('td_'+item+'_'+num).style.backgroundColor='orange';
			}
		}
		changement();
	}
";
	*/
	echo "</script>\n";


	echo "<p><i>Remarques:</i></p>\n";
	echo "<ul>\n";
	echo "<li>La prise en compte des champs choisis est conditionn�e par le fait d'avoir coch� ou non la colonne 'Utiliser l'interface simplifi�e' pour l'utilisateur consid�r�.</li>\n";
	echo "<li>Les champs non propos�s dans les interfaces simplifi�es restent accessibles aux utilisateurs en cliquant sur les liens 'Interface compl�te' propos�s dans les pages d'interfaces simplifi�es .</li>\n";
	echo "</ul>\n";
	//}
}

echo "</form>\n";

	// On ajoute le r�glage pour le menu en barre horizontale
	$aff = "non";
if ($_SESSION["statut"] == "administrateur") {
	$aff = "oui";
}elseif($_SESSION["statut"] == "professeur" AND getSettingValue("utiliserMenuBarre") == "yes") {
	$aff = "oui";
}else {
	$aff = "non";
}
// On affiche si c'est autoris�
if ($aff == "oui") {
	echo '
		<form name="change_menu" method="post" action="./config_prefs.php">
';

	echo add_token_field();

	echo '
	<fieldset id="afficherBarreMenu" style="border: 1px solid grey;">
		<legend style="border: 1px solid grey;">G�rer la barre horizontale du menu</legend>
			<input type="hidden" name="modifier_le_menu" value="ok" />
		<p>
			<label for="visibleMenu">Rendre visible la barre de menu horizontale sous l\'en-t�te.</label>
			<input type="radio" id="visibleMenu" name="afficher_menu" value="yes"'.eval_checked("utiliserMenuBarre", "yes", $_SESSION["statut"], $_SESSION["login"]).' onclick="document.change_menu.submit();" />
		</p>
		<p>
			<label for="invisibleMenu">Ne pas utiliser la barre de menu horizontale.</label>
			<input type="radio" id="invisibleMenu" name="afficher_menu" value="no"'.eval_checked("utiliserMenuBarre", "no", $_SESSION["statut"], $_SESSION["login"]).' onclick="document.change_menu.submit();" />
		</p>
	</fieldset>
		</form>
		'.$messageMenu
		;
} // fin du if ($aff == "oui")

echo '<br />' . "\n";

if ($_SESSION["statut"] == 'administrateur') {
	// On propose de pouvoir obliger tous les professeurs � avoir un header court
	echo '
		<form name="change_header_prof" method="post" action="config_prefs.php">
';

	echo add_token_field();

	echo '

			<fieldset style="border: 1px solid grey;">
				<legend style="border: 1px solid grey;">G�rer la hauteur de l\'ent�te pour les professeurs</legend>
				<input type="hidden" name="modifier_entete_prof" value="ok" />
				<p>
					<label for="headerBas">Imposer une ent�te basse</label>
					<input type="radio" id="headerBas" name="header_bas" value="y"'.eval_checked("impose_petit_entete_prof", "y", "administrateur", $_SESSION["login"]).' onclick="document.change_header_prof.submit();" />
				</p>
				<p>
					<label for="headerNormal">Ne rien imposer</label>
					<input type="radio" id="headerNormal" name="header_bas" value="n"'.eval_checked("impose_petit_entete_prof", "n", "administrateur", $_SESSION["login"]).' onclick="document.change_header_prof.submit();" />
				</p>
				' . $message_header_prof . '
			</fieldset>
		</form>';
}

echo "<br />\n";
require("../lib/footer.inc.php");
?>
