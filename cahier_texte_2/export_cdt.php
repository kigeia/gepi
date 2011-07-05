<?php
/*
* @version: $Id$
*
* Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Gabriel Fischer
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

// Initialisation des feuilles de style apr�s modification pour am�liorer l'accessibilit�
$accessibilite="y";

// Initialisations files
require_once("../lib/initialisations.inc.php");
require_once("../lib/transform_functions.php");

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
	header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
	die();
} else if ($resultat_session == '0') {
	header("Location: ../logout.php?auto=1");
	die();
}

$sql="SELECT 1=1 FROM droits WHERE id='/cahier_texte_2/export_cdt.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/cahier_texte_2/export_cdt.php',
administrateur='V',
professeur='V',
cpe='V',
scolarite='V',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Export de CDT',
statut='';";
$insert=mysql_query($sql);
}

if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

//On v�rifie si le module est activ�
if (getSettingValue("active_cahiers_texte")!='y') {
	die("Le module n'est pas activ�.");
}

//$display_date_debut=isset($_POST['display_date_debut']) ? $_POST['display_date_debut'] : NULL;
//$display_date_fin=isset($_POST['display_date_fin']) ? $_POST['display_date_fin'] : NULL;

//=======================
//Configuration du calendrier
include("../lib/calendrier/calendrier.class.php");
//$cal1 = new Calendrier("form_choix_edit", "display_date_debut");
//$cal2 = new Calendrier("form_choix_edit", "display_date_fin");
$cal1 = new Calendrier("formulaire", "display_date_debut");
$cal2 = new Calendrier("formulaire", "display_date_fin");
$cal3 = new Calendrier("formulaire", "date2_acces");
//=======================

//=======================
// Pour �viter de refaire le choix des dates en revenant ici, on utilise la SESSION...
$annee = strftime("%Y");
$mois = strftime("%m");
$jour = strftime("%d");
$heure = strftime("%H");
$minute = strftime("%M");

if($mois>7) {$date_debut_tmp="01/09/$annee";} else {$date_debut_tmp="01/09/".($annee-1);}

//$display_date_debut=isset($_POST['display_date_debut']) ? $_POST['display_date_debut'] : (isset($_SESSION['display_date_debut']) ? $_SESSION['display_date_debut'] : $jour."/".$mois."/".$annee);
$display_date_debut=isset($_POST['display_date_debut']) ? $_POST['display_date_debut'] : (isset($_SESSION['display_date_debut']) ? $_SESSION['display_date_debut'] : $date_debut_tmp);

$display_date_fin=isset($_POST['display_date_fin']) ? $_POST['display_date_fin'] : (isset($_SESSION['display_date_fin']) ? $_SESSION['display_date_fin'] : $jour."/".$mois."/".$annee);
//=======================

//$id_groupe=isset($_POST['id_groupe']) ? $_POST['id_groupe'] : (isset($_GET['id_groupe']) ? $_GET['id_groupe'] : NULL);
$id_groupe=isset($_POST['id_groupe']) ? $_POST['id_groupe'] : NULL;

if(isset($_GET['id_groupe'])) {
	$id_groupe=array();
	$id_groupe[0]=$_GET['id_groupe'];
}

$id_classe=isset($_POST['id_classe']) ? $_POST['id_classe'] : (isset($_GET['id_classe']) ? $_GET['id_classe'] : NULL);
$login_prof=isset($_POST['login_prof']) ? $_POST['login_prof'] : (isset($_GET['login_prof']) ? $_GET['login_prof'] : NULL);

$action=isset($_POST['action']) ? $_POST['action'] : "export_zip";

$tab_fichiers_a_zipper=array();

//**************** EN-TETE *****************
$titre_page = "Cahier de textes - Export";
require_once("../lib/header.inc");
//**************** FIN EN-TETE *************

//debug_var();

echo "<p class='bold'>";
if($_SESSION['statut']=='professeur') {
	echo "<a href='index.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
}
else {
	// Modifier par la suite le chemin de retour selon les statuts...
	echo "<a href='../accueil.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
}

// Cr�ation d'un espace entre le bandeau et le reste 
//echo "<p></p>\n";

//On v�rifie si le module est activ�
if (getSettingValue("active_cahiers_texte")!='y') {
	echo "</p>\n";

	echo "<p class='grand centre_texte'>Le cahier de textes n'est pas accessible pour le moment.</p>\n";
	require("../lib/footer.inc.php");
	die();
}

if(!isset($id_groupe)) {

	if($_SESSION['statut']!='professeur') {

		// Pour les non-professeurs, on choisit d'abord les classes ou le login_prof
		if((!isset($id_classe))&&(!isset($login_prof))) {
			echo "</p>\n";
	
			echo "<p class='bold'>Choix des classes&nbsp;:</p>\n";
		
			// Liste des classes avec �l�ve:
			$sql="SELECT DISTINCT c.* FROM j_eleves_classes jec, classes c WHERE (c.id=jec.id_classe) ORDER BY c.classe;";
			$call_classes=mysql_query($sql);
		
			$nb_classes=mysql_num_rows($call_classes);
			if($nb_classes==0){
				echo "<p>Aucune classe avec �l�ve affect� n'a �t� trouv�e.</p>\n";
				require("../lib/footer.inc.php");
				die();
			}
		
			echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='formulaire'>\n";
			// Affichage sur 3 colonnes
			$nb_classes_par_colonne=round($nb_classes/3);
		
			echo "<table width='100%' summary='Choix des classes'>\n";
			echo "<tr valign='top' align='center'>\n";
		
			$cpt = 0;
		
			echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
			echo "<td align='left'>\n";
		
			while($lig_clas=mysql_fetch_object($call_classes)) {
	
				//affichage 3 colonnes
				if(($cpt>0)&&(round($cpt/$nb_classes_par_colonne)==$cpt/$nb_classes_par_colonne)){
					echo "</td>\n";
					echo "<td align='left'>\n";
				}
		
				echo "<label id='label_tab_id_classe_$cpt' for='tab_id_classe_$cpt' style='cursor: pointer;'><input type='checkbox' name='id_classe[]' id='tab_id_classe_$cpt' value='$lig_clas->id' onchange='change_style_classe($cpt)' /> $lig_clas->classe</label>";
				echo "<br />\n";
				$cpt++;
			}
		
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
		
			echo "<p><a href='#' onClick='ModifCase(true)'>Tout cocher</a> / <a href='#' onClick='ModifCase(false)'>Tout d�cocher</a></p>\n";
		
			echo "<p><input type='submit' value='Valider' /></p>\n";
			echo "</form>\n";

			//+++++++++++++++++++++++++++

			$sql="SELECT DISTINCT u.login, u.nom, u.prenom FROM j_groupes_professeurs jgp, utilisateurs u WHERE u.login=jgp.login ORDER BY u.nom, u.prenom;";
			$res_prof=mysql_query($sql);
			$nb_profs=mysql_num_rows($res_prof);
			if($nb_profs==0){
				echo "<p>Aucun professeur assurant un enseignement n'a �t� trouv�.</p>\n";
				require("../lib/footer.inc.php");
				die();
			}
	
			//echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='formulaire'>\n";
			echo "<p>Ou choisissez un professeur&nbsp;:</p>\n";
	
			$nb_prof_par_colonne=round($nb_profs/3);
		
			echo "<table width='100%' summary='Choix du professeur'>\n";
			echo "<tr valign='top' align='center'>\n";
		
			$cpt = 0;
		
			echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
			echo "<td align='left'>\n";
			while($lig_prof=mysql_fetch_object($res_prof)) {
				//affichage 3 colonnes
				if(($cpt>0)&&(round($cpt/$nb_prof_par_colonne)==$cpt/$nb_prof_par_colonne)){
					echo "</td>\n";
					echo "<td align='left'>\n";
				}
		
				//echo "<label id='label_tab_prof_$cpt' for='tab_prof_$cpt' style='cursor: pointer;'><input type='radio' name='login_prof' id='tab_prof_$cpt' value='$lig_prof->login' onchange='change_style_prof($cpt)' /> $lig_prof->nom $lig_prof->prenom</label>";
				echo "<a href='".$_SERVER['PHP_SELF']."?login_prof=$lig_prof->login'>$lig_prof->nom $lig_prof->prenom</a>";
				echo "<br />\n";
				$cpt++;
			}
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
	
			//echo "<p><input type='submit' value='Valider' /></p>\n";
			//echo "</form>\n";
	
	
			echo "<script type='text/javascript'>
	function ModifCase(mode) {
		for (var k=0;k<$cpt;k++) {
			if(document.getElementById('tab_id_classe_'+k)){
				document.getElementById('tab_id_classe_'+k).checked = mode;
				change_style_classe(k);
			}
		}
	}

	function change_style_classe(num) {
		if(document.getElementById('tab_id_classe_'+num)) {
			if(document.getElementById('tab_id_classe_'+num).checked) {
				document.getElementById('label_tab_id_classe_'+num).style.fontWeight='bold';
			}
			else {
				document.getElementById('label_tab_id_classe_'+num).style.fontWeight='normal';
			}
		}
	}

	</script>\n";
		}
		else {
			// On passe au choix des groupes
			echo " | <a href='".$_SERVER['PHP_SELF']."'>Retour au choix des classes ou du professeur</a>";
			echo "</p>\n";

			echo "<p class='bold'>Choix des mati�res/enseignements&nbsp;:</p>\n";
			echo "<blockquote>\n";
	
			//echo "<p></p>\n";
			if(isset($login_prof)) {
	
				echo "<p class='bold'>Enseignements de ".civ_nom_prenom($login_prof)."&nbsp;:</p>\n";
				echo "<blockquote>\n";
	
				echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='formulaire'>\n";
	
				echo "<input type='hidden' name='login_prof' value='$login_prof' />\n";
	
				$cpt=0;
				$groups=get_groups_for_prof($login_prof);
				echo "<table class='boireaus' summary='Choix des enseignements'>\n";
				echo "<tr>\n";
				echo "<th>\n";
				echo "<a href='#' onClick='tout_cocher(true);return false;'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a> / <a href='#' onClick='tout_cocher(false);return false;'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
				echo "</th>\n";
				echo "<th>Enseignement</th>\n";
				echo "<th>Description</th>\n";
				echo "<th>Classes</th>\n";
				echo "</tr>\n";
				$alt=1;
				foreach($groups as $current_group) {
					$alt=$alt*(-1);
					echo "<tr class='lig$alt white_hover'>\n";
					echo "<td>\n";
					echo "<input type='checkbox' name='id_groupe[]' id='id_groupe_$cpt' value='".$current_group['id']."' onchange='change_style_groupe($cpt)' />\n";
					echo "</td>\n";
					echo "<td>\n";
					echo "<label id='label_groupe_$cpt' for='id_groupe_$cpt'> ".$current_group['name']."</label>\n";
					echo "</td>\n";
					echo "<td>\n";
					echo "<label for='id_groupe_$cpt'>";
					echo $current_group['description'];
					echo "</label>";
					echo "</td>\n";
					echo "<td>\n";
					echo $current_group['classlist_string'];
					echo "</td>\n";
					echo "</tr>\n";
					$cpt++;
				}
				echo "</table>\n";
			
				//echo "<input type='hidden' name='choix_enseignements' value='y' />\n";
			
				//echo "<p style='color:red'>A FAIRE: Ajouter le choix Du/Au � ce niveau</p>\n";
				echo "<p>";
				echo "<label for='choix_periode_dates' style='cursor: pointer;'> \nExporter le(s) cahier(s) de textes de la date : </label>";
			
				echo "<input type='text' name = 'display_date_debut' id = 'display_date_debut' size='10' value = \"".$display_date_debut."\" onfocus=\"document.getElementById('choix_periode_dates').checked=true;\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />";
				echo "<label for='choix_periode_dates' style='cursor: pointer;'><a href=\"#calend\" onClick=\"".$cal1->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";
			
				echo "&nbsp;� la date : </label>";
				echo "<input type='text' name = 'display_date_fin' id = 'display_date_fin' size='10' value = \"".$display_date_fin."\" onfocus=\"document.getElementById('choix_periode_dates').checked=true;\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />";
				echo "<label for='choix_periode_dates' style='cursor: pointer;'><a href=\"#calend\" onClick=\"".$cal2->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";
				echo "<br />\n";
				echo " (<i>Veillez � respecter le format jj/mm/aaaa</i>)</label>\n";
				echo "</p>\n";

				echo "<p><b>Action � r�aliser&nbsp;:</b><br />\n";
				echo "<input type='radio' name='action' id='action_export_zip' value='export_zip' checked onchange='modif_param_affichage()' /><label for='action_export_zip'> G�n�rer un export de cahier(s) de textes et le zipper</label><br />\n";
				echo "<input type='radio' name='action' id='action_acces' value='acces' onchange='modif_param_affichage()' /><label for='action_acces'> Mettre en place un acc�s sans authentification aux cahier(s) de textes choisis<br />(<i>pour par exemple, permettre � un inspecteur de consulter les cahiers de textes d'un professeur lors d'une inspection</i>)</label>";
				echo "</p>\n";

				echo "<div id='div_param_action_acces' style='margin-left: 3em;'>\n";
				echo "<p class='bold'>Param�tres de l'acc�s&nbsp;</p>\n";
				echo "<blockquote>\n";
				echo "<p>Description du motif de l'ouverture d'acc�s&nbsp;:<br />";
				echo "<textarea name='description_acces' cols='50' rows='4'></textarea>";
				echo "</p>";
				echo "<p>Date � laquelle vous souhaitez supprimer l'acc�s&nbsp;: ";
				echo "<input type='text' name='date2_acces' id='date2_acces' size='10' value=\"".$display_date_fin."\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />";
				echo "<a href=\"#calend\" onClick=\"".$cal3->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";
				echo "<br />\n";
				echo "(<i>la suppression n'est pas automatique � la date indiqu�e, mais fixer une date peut aider � savoir si l'acc�s doit �tre conserv� ou non</i>)</p>\n";

				echo "</blockquote>\n";
				echo "</div>\n";

				echo "<p><input type='submit' value='Valider' /></p>\n";
				echo "</form>\n";

				echo "<script type='text/javascript'>

	document.getElementById('div_param_action_acces').style.display='none';

	function modif_param_affichage() {
		if(document.getElementById('action_export_zip').checked==true) {
			document.getElementById('div_param_action_acces').style.display='none';
		}
		else {
			document.getElementById('div_param_action_acces').style.display='';
		}
	}

		function tout_cocher(mode) {
			for (var k=0;k<$cpt;k++) {
				if(document.getElementById('id_groupe_'+k)){
					document.getElementById('id_groupe_'+k).checked = mode;
					change_style_groupe(k);
				}
			}
		}
	
		function change_style_groupe(num) {
			//if(document.getElementById('id_groupe_'+num)) {
			if((document.getElementById('id_groupe_'+num))&&(document.getElementById('label_groupe_'+num))) {
				if(document.getElementById('id_groupe_'+num).checked) {
					document.getElementById('label_groupe_'+num).style.fontWeight='bold';
				}
				else {
					document.getElementById('label_groupe_'+num).style.fontWeight='normal';
				}
			}
		}
	
	</script>\n";
	
			}
			elseif(isset($id_classe)) {
	
				echo "<p class='bold'>Choix des mati�res/enseignements&nbsp;:</p>\n";
				echo "<blockquote>\n";
	
				echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='formulaire'>\n";
	
				$cpt=0;
				for($i=0;$i<count($id_classe);$i++) {
	
					$classe=get_class_from_id($id_classe[$i]);
					echo "<p class='bold'>".$classe."</p>\n";
	
					echo "<input type='hidden' name='id_classe[]' value='$id_classe[$i]' />\n";
	
					$groups=get_groups_for_class($id_classe[$i]);
					echo "<table class='boireaus' summary=\"Choix des enseignements de $classe\">\n";
					echo "<tr>\n";
					echo "<th>\n";
					echo "<a href='#' onClick='tout_cocher(true);return false;'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a> / <a href='#' onClick='tout_cocher(false);return false;'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
					echo "</th>\n";
					echo "<th>Enseignement</th>\n";
					echo "<th>Description</th>\n";
					echo "<th>Classes</th>\n";
					echo "</tr>\n";
					$alt=1;
					foreach($groups as $current_group) {
						$alt=$alt*(-1);
						echo "<tr class='lig$alt white_hover'>\n";
						echo "<td>\n";
						echo "<input type='checkbox' name='id_groupe[]' id='id_groupe_$cpt' value='".$current_group['id']."' onchange='change_style_groupe($cpt)' />\n";
						echo "</td>\n";
						echo "<td>\n";
						echo "<label id='label_groupe_$cpt' for='id_groupe_$cpt'> ".$current_group['name']."</label>\n";
						echo "</td>\n";
						echo "<td>\n";
						echo "<label for='id_groupe_$cpt'>";
						echo $current_group['description'];
						echo "</label>";
						echo "</td>\n";
						echo "<td>\n";
						echo $current_group['classlist_string'];
						echo "</td>\n";
						echo "</tr>\n";
						$cpt++;
					}
					echo "</table>\n";
	
				}

				echo "</blockquote>\n";
	
				//echo "<input type='hidden' name='choix_enseignements' value='y' />\n";
			
				//echo "<p style='color:red'>A FAIRE: Ajouter le choix Du/Au � ce niveau</p>\n";

				echo "<p>";
				echo "<label for='choix_periode_dates' style='cursor: pointer;'> \nExporter le(s) cahier(s) de textes de la date : </label>";
			
				echo "<input type='text' name = 'display_date_debut' id = 'display_date_debut2' size='10' value = \"".$display_date_debut."\" onfocus=\"document.getElementById('choix_periode_dates').checked=true;\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />";
				echo "<label for='choix_periode_dates' style='cursor: pointer;'><a href=\"#calend\" onClick=\"".$cal1->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";
			
				echo "&nbsp;� la date : </label>";
				echo "<input type='text' name = 'display_date_fin' id = 'display_date_fin2' size='10' value = \"".$display_date_fin."\" onfocus=\"document.getElementById('choix_periode_dates').checked=true;\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />";
				echo "<label for='choix_periode_dates' style='cursor: pointer;'><a href=\"#calend\" onClick=\"".$cal2->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";
				echo "<br />\n";
				echo " (<i>Veillez � respecter le format jj/mm/aaaa</i>)</label>\n";
				echo "</p>\n";
	
				echo "<p><b>Action � r�aliser&nbsp;:</b><br />\n";
				echo "<input type='radio' name='action' id='action_export_zip' value='export_zip' checked onchange='modif_param_affichage()' /><label for='action_export_zip'> G�n�rer un export de cahier(s) de textes et le zipper</label><br />\n";
				echo "<input type='radio' name='action' id='action_acces' value='acces' onchange='modif_param_affichage()' /><label for='action_acces'> Mettre en place un acc�s sans authentification aux cahier(s) de textes choisis<br />(<i>pour par exemple, permettre � un inspecteur de consulter les cahiers de textes d'un professeur lors d'une inspection</i>)</label>";
				echo "</p>\n";

				echo "<div id='div_param_action_acces' style='margin-left: 3em;'>\n";
				echo "<p class='bold'>Param�tres de l'acc�s&nbsp;</p>\n";
				echo "<blockquote>\n";
				echo "<p>Description du motif de l'ouverture d'acc�s&nbsp;:<br />";
				echo "<textarea name='description_acces' cols='50' rows='4'></textarea>";
				echo "</p>";
				echo "<p>Date � laquelle vous souhaitez supprimer l'acc�s&nbsp;: ";
				echo "<input type='text' name='date2_acces' size='10' value=\"".$display_date_fin."\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />";
				echo "<a href=\"#calend\" onClick=\"".$cal3->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";
				echo "<br />\n";
				echo "(<i>la suppression n'est pas automatique � la date indiqu�e, mais fixer une date peut aider � savoir si l'acc�s doit �tre conserv� ou non</i>)</p>\n";

				echo "</blockquote>\n";
				echo "</div>\n";

				echo "<p><input type='submit' value='Valider' /></p>\n";
				echo "</form>\n";

				echo "<script type='text/javascript'>

	document.getElementById('div_param_action_acces').style.display='none';

	function modif_param_affichage() {
		if(document.getElementById('action_export_zip').checked==true) {
			document.getElementById('div_param_action_acces').style.display='none';
		}
		else {
			document.getElementById('div_param_action_acces').style.display='';
		}
	}

	function tout_cocher(mode) {
		for (var k=0;k<$cpt;k++) {
			if(document.getElementById('id_groupe_'+k)){
				document.getElementById('id_groupe_'+k).checked = mode;
				change_style_groupe(k);
			}
		}
	}

	function change_style_groupe(num) {
		//if(document.getElementById('id_groupe_'+num)) {
		if((document.getElementById('id_groupe_'+num))&&(document.getElementById('label_groupe_'+num))) {
			if(document.getElementById('id_groupe_'+num).checked) {
				document.getElementById('label_groupe_'+num).style.fontWeight='bold';
			}
			else {
				document.getElementById('label_groupe_'+num).style.fontWeight='normal';
			}
		}
	}
	
</script>\n";

			}
			else {
				echo "<p style='color:red'>Vous n'avez choisi ni professeur, ni classe.</p>\n";
			}
			echo "</blockquote>\n";

		}
	}
	else {
		// C'est un professeur qui est connect�
		echo "</p>\n";
	
		echo "<p class='bold'>Choix des mati�res/enseignements&nbsp;:</p>\n";
	
		echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='formulaire'>\n";
	
		$cpt=0;
		$groups=get_groups_for_prof($_SESSION['login']);
		echo "<table class='boireaus' summary='Choix des enseignements'>\n";
		echo "<tr>\n";
		echo "<th>\n";
		echo "<a href='#' onClick='tout_cocher(true);return false;'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a> / <a href='#' onClick='tout_cocher(false);return false;'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
		echo "</th>\n";
		echo "<th>Enseignement</th>\n";
		echo "<th>Description</th>\n";
		echo "<th>Classes</th>\n";
		echo "</tr>\n";
		$alt=1;
		foreach($groups as $current_group) {
			$alt=$alt*(-1);
			echo "<tr class='lig$alt white_hover'>\n";
			echo "<td>\n";
			echo "<input type='checkbox' name='id_groupe[]' id='id_groupe_$cpt' value='".$current_group['id']."' onchange='change_style_groupe($cpt)' />\n";
			echo "</td>\n";
			echo "<td>\n";
			echo "<label id='label_groupe_$cpt' for='id_groupe_$cpt'> ".$current_group['name']."</label>\n";
			echo "</td>\n";
			echo "<td>\n";
			echo "<label for='id_groupe_$cpt'>";
			echo $current_group['description'];
			echo "</label>";
			echo "</td>\n";
			echo "<td>\n";
			echo $current_group['classlist_string'];
			echo "</td>\n";
			echo "</tr>\n";
			$cpt++;
		}
		echo "</table>\n";
	
		//echo "<input type='hidden' name='choix_enseignements' value='y' />\n";
	
		//echo "<p style='color:red'>A FAIRE: Ajouter le choix Du/Au � ce niveau</p>\n";
		echo "<p>";
		echo "<label for='choix_periode_dates' style='cursor: pointer;'> \nExporter le(s) cahier(s) de textes de la date : </label>";
	
		echo "<input type='text' name = 'display_date_debut' size='10' value = \"".$display_date_debut."\" onfocus=\"document.getElementById('choix_periode_dates').checked=true;\" />";
		echo "<label for='choix_periode_dates' style='cursor: pointer;'><a href=\"#calend\" onClick=\"".$cal1->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";
	
		echo "&nbsp;� la date : </label>";
		echo "<input type='text' name = 'display_date_fin' size='10' value = \"".$display_date_fin."\" onfocus=\"document.getElementById('choix_periode_dates').checked=true;\" />";
		echo "<label for='choix_periode_dates' style='cursor: pointer;'><a href=\"#calend\" onClick=\"".$cal2->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";
		echo "<br />\n";
		echo " (<i>Veillez � respecter le format jj/mm/aaaa</i>)</label>\n";
		echo "</p>\n";
	
		echo "<p><input type='submit' value='Valider' /></p>\n";
		echo "</form>\n";
	
		echo "<script type='text/javascript'>

	function tout_cocher(mode) {
		for (var k=0;k<$cpt;k++) {
			if(document.getElementById('id_groupe_'+k)){
				document.getElementById('id_groupe_'+k).checked = mode;
				change_style_groupe(k);
			}
		}
	}

	function change_style_groupe(num) {
		//if(document.getElementById('id_groupe_'+num)) {
		if((document.getElementById('id_groupe_'+num))&&(document.getElementById('label_groupe_'+num))) {
			if(document.getElementById('id_groupe_'+num).checked) {
				document.getElementById('label_groupe_'+num).style.fontWeight='bold';
			}
			else {
				document.getElementById('label_groupe_'+num).style.fontWeight='normal';
			}
		}
	}

</script>\n";

	}

	require("../lib/footer.inc.php");
	die();
}

//==============================
// Le choix des groupes est fait
//==============================

// Pr�paration de l'arborescence

$gepiSchoolName=getSettingValue('gepiSchoolName');
$gepiYear=getSettingValue('gepiYear');

require("cdt_lib.php");

$dirname=get_user_temp_directory();
if(!$dirname) {
	echo "<p style='color:red;'>Probl�me avec le dossier temporaire.</p>\n";
	require("../lib/footer.inc.php");
	die();
}

if($_SESSION['statut']=='professeur') {
	echo "</p>\n";
}
else {
	echo " | <a href='".$_SERVER['PHP_SELF']."'>Retour au choix des classes ou du professeur</a>";
	echo "</p>\n";
}

echo "<div id='div_archive_zip'></div>\n";
echo "<p class='bold'>Affichage des cahiers de textes extraits</p>\n";

// R�cup�rer le max de getSettingValue("begin_bookings") et $display_date_debut
$tmp_tab=explode("/",$display_date_debut);
$jour=$tmp_tab[0];
$mois=$tmp_tab[1];
$annee=$tmp_tab[2];
$date_debut_tmp=mktime(0,0,0,$mois,$jour,$annee);
$timestamp_debut_export=max(getSettingValue("begin_bookings"),$date_debut_tmp);

// R�cup�rer le min de getSettingValue("end_bookings") et $display_date_fin
$tmp_tab=explode("/",$display_date_fin);
$jour=$tmp_tab[0];
$mois=$tmp_tab[1];
$annee=$tmp_tab[2];
$date_fin_tmp=mktime(0,0,0,$mois,$jour,$annee);
$timestamp_fin_export=max(getSettingValue("end_bookings"),$date_fin_tmp);

// Permettre de choisir l'ordre dans lequel exporter?
$current_ordre='ASC';

//echo "\$action=$action<br />";

if($action=='acces') {
	$length = rand(35, 45);
	for($len=$length,$r='';strlen($r)<$len;$r.=chr(!mt_rand(0,2)? mt_rand(48,57):(!mt_rand(0,1) ? mt_rand(65,90) : mt_rand(97,122))));
	$dirname = "acces_cdt_".$r;
	$create = mkdir("../documents/".$dirname, 0700);
	if(!$create) {
		echo "<p style='color:red;'>Probl�me avec le dossier temporaire../documents/".$dirname."</p>\n";
		require("../lib/footer.inc.php");
		die();
	}

	// Enregistrement dans la base de cet acc�s ouvert
	// Il faut y stocker la liste des login profs concern�s pour afficher en page d'accueil la pr�sence d'un cdt ouvert en consultation
	$date1_acces="$annee-$mois-$jour $heure:$minute:00";
	$date2_acces=isset($_POST['date2_acces']) ? $_POST['date2_acces'] : "";

	if($date2_acces=='') {
		$date2_acces=$date1_acces;
	}
	else {
		$tab_tmp_date=explode('/',$date2_acces);
		$date2_acces=$tab_tmp_date[2]."-".$tab_tmp_date[1]."-".$tab_tmp_date[0]." $heure:$minute:00";
	}

	$description_acces=isset($_POST['description_acces']) ? $_POST['description_acces'] : "Test";

	/*
	$chemin_acces="documents/".$dirname."/index.html";
	$res=enregistrement_creation_acces_cdt($chemin_acces, $description_acces, $date1_acces, $date2_acces, $id_groupe);
	if(!$res) {
		echo "<p style='color:red;'>Erreur lors de l'enregistrement de la mise en place de l'acc�s.</p>\n";
		require("../lib/footer.inc.php");
		die();
	}
	*/
}

//echo "\$dirname=$dirname<br />";

if(($_SESSION['statut']=='professeur')||(isset($login_prof))) {

	$chaine_info_prof="";
	if(isset($login_prof)) {
		$chaine_info_prof=" de ".civ_nom_prenom($login_prof)." ";
	}
	else {
		$login_prof=$_SESSION['login'];
	}

	// Pr�paration de l'arborescence
	$nom_export="export_cdt_".$login_prof."_".strftime("%Y%m%d_%H%M%S");

	if($action=='acces') {
		$chemin_acces="documents/".$dirname."/".$nom_export."/index.html";
		$res=enregistrement_creation_acces_cdt($chemin_acces, $description_acces, $date1_acces, $date2_acces, $id_groupe);
		if(!$res) {
			echo "<p style='color:red;'>Erreur lors de l'enregistrement de la mise en place de l'acc�s.</p>\n";
			require("../lib/footer.inc.php");
			die();
		}
	}

	arbo_export_cdt($nom_export, $dirname);

	$chaine_id_groupe="";

	// G�n�rer la page d'index
	$html="";
	//$html.=html_entete();
	$html.="<h1 style='text-align:center;'>Cahiers de textes $chaine_info_prof(".$gepiSchoolName." - ".$gepiYear.")</h1>\n";
	$html.="<p>Cahier de textes (<i>$display_date_debut - $display_date_fin</i>) de&nbsp;:</p>\n";
	$html.="<ul>\n";
	for($i=0;$i<count($id_groupe);$i++) {
	
		// ===================================================
		// ===================================================
		// A FAIRE
		// VERIFIER QUE LE PROFESSEUR EST ASSOCIE A CE GROUPE
		// ===================================================
		// ===================================================
	
		$tab_champs=array('classes');
		$current_group=get_group($id_groupe[$i],$tab_champs);
		//$current_group=get_group($id_groupe[$i]);
	
		if($i>0) {
			$chaine_id_groupe.=", ";
		}
		$chaine_id_groupe.="'".$id_groupe[$i]."'";
	
		$nom_groupe=preg_replace("/[^A-Za-z0-9]/","_",remplace_accents($current_group['name'],'all'));
		$description_groupe=preg_replace("/[^A-Za-z0-9]/","_",remplace_accents($current_group['description'],'all'));
		$classlist_string_groupe=preg_replace("/[^A-Za-z0-9]/","_",remplace_accents($current_group['classlist_string'],'all'));
		$nom_page_html_groupe=$id_groupe[$i]."_".$nom_groupe."_"."$description_groupe"."_".$classlist_string_groupe.".html";
	
		$nom_fichier[$id_groupe[$i]]=$nom_page_html_groupe;
		$nom_detaille_groupe[$id_groupe[$i]]=$current_group['name']." (<i>".$current_group['description']." en (".$current_group['classlist_string'].")</i>)";
	
		$nom_detaille_groupe_non_html[$id_groupe[$i]]=$current_group['name']." (".$current_group['description']." en (".$current_group['classlist_string']."))";
	
		$html.="<li><a id='lien_id_groupe_$id_groupe[$i]' href='cahier_texte/$nom_page_html_groupe'>".$current_group['name']." (<i>".$current_group['description']." en (".$current_group['classlist_string'].")</i>)</a></li>\n";
	}
	$html.="</ul>\n";
	//$html.=html_pied_de_page();
	
	//================================================================
	// Affichage dans la page d'export de ce qui va �tre fourni en zip
	echo "<a name='affichage_page_index'></a>";
	echo "<div style='border: 1px solid black;'>\n";
	echo $html;
	echo "</div>\n";

	// Pr�caution
	$chaine_id_groupe=preg_replace("/^,/","",$chaine_id_groupe);

	// Correctif des liens tels qu'affich�s dans la page
	echo "<script type='text/javascript'>
		tab_id_groupe=new Array($chaine_id_groupe);
		for(i=0;i<tab_id_groupe.length;i++) {
			if(document.getElementById('lien_id_groupe_'+tab_id_groupe[i])) {
				document.getElementById('lien_id_groupe_'+tab_id_groupe[i]).href='#lien_id_groupe_'+tab_id_groupe[i];
			}
		}
	</script>\n";
	//================================================================
	
	$html=html_entete("Index des cahiers de textes",0).$html;
	$html.=html_pied_de_page();
	
	$f=fopen($dossier_export."/index.html","w+");
	fwrite($f,$html);
	fclose($f);
	
	$tab_fichiers_a_zipper[]=$dossier_export."/index.html";

}
else {
	// C'est une liste de classes/enseignements qui a �t� choisie

	$chaine_info_classes="";
	$chaine_classes="";
	$chaine_id_classe="";
	for($i=0;$i<count($id_classe);$i++) {
		if($i>0) {
			$chaine_info_classes.="_";
			$chaine_classes.=", ";
			$chaine_id_classe.=", ";
		}

		$nom_classe[$i]=get_class_from_id($id_classe[$i]);
		$nom_classe_clean[$i]=preg_replace("/[^A-Za-z0-9]/","_",remplace_accents($nom_classe[$i],'all'));

		$chaine_info_classes.=$nom_classe_clean[$i];
		$chaine_classes.=$nom_classe[$i];
		$chaine_id_classe.="'".$id_classe[$i]."'";
	}

	// Cr�er une page index.html de la liste des classes
	//if(count($id_classe)>1) {

		// Pr�paration de l'arborescence
		$nom_export="export_cdt_classes_".$chaine_info_classes."_".strftime("%Y%m%d_%H%M%S");

		if($action=='acces') {
			$chemin_acces="documents/".$dirname."/".$nom_export."/index.html";
			$res=enregistrement_creation_acces_cdt($chemin_acces, $description_acces, $date1_acces, $date2_acces, $id_groupe);
			if(!$res) {
				echo "<p style='color:red;'>Erreur lors de l'enregistrement de la mise en place de l'acc�s.</p>\n";
				require("../lib/footer.inc.php");
				die();
			}
		}

		arbo_export_cdt($nom_export, $dirname);

		// G�n�rer la page d'index
		$html="";
		//$html.=html_entete();
		$html.="<h1 style='text-align:center;'>Cahiers de textes de $chaine_classes (".$gepiSchoolName." - ".$gepiYear.")</h1>\n";
		$html.="<p>Cahier de textes (<i>$display_date_debut - $display_date_fin</i>) de&nbsp;:</p>\n";
		$html.="<ul>\n";
		for($i=0;$i<count($id_classe);$i++) {
			$nom_page_html_index_classe=$id_classe[$i]."_".$nom_classe_clean[$i].".html";

			$nom_fichier_index[$id_classe[$i]]=$nom_page_html_index_classe;
		
			$html.="<li><a id='lien_id_classe_$id_classe[$i]' href='$nom_page_html_index_classe'>".$nom_classe[$i]."</a></li>\n";
		}
		$html.="</ul>\n";
		
		//================================================================
		// Affichage dans la page d'export de ce qui va �tre fourni en zip
		echo "<a name='affichage_page_index'></a>";
		echo "<div style='border: 1px solid black;'>\n";
		echo $html;
		echo "</div>\n";
		
		// Correctif des liens tels qu'affich�s dans la page
		echo "<script type='text/javascript'>
			tab_id_classe=new Array($chaine_id_classe);
			for(i=0;i<tab_id_classe.length;i++) {
				if(document.getElementById('lien_id_classe_'+tab_id_classe[i])) {
					document.getElementById('lien_id_classe_'+tab_id_classe[i]).href='#lien_id_classe_'+tab_id_classe[i];
				}
			}
		</script>\n";
		//================================================================
		
		$html=html_entete("Index des cahiers de textes",0).$html;
		$html.=html_pied_de_page();

		//echo "Ecriture de ".$dossier_export."/index.html<br />";
		$f=fopen($dossier_export."/index.html","w+");
		fwrite($f,$html);
		fclose($f);
		
		$tab_fichiers_a_zipper[]=$dossier_export."/index.html";
		
	//}


	// Cr�er une page index_$classe.html pour chaque classe
	for($j=0;$j<count($id_classe);$j++) {

		echo "<hr width='200px' />\n";

		$groups=get_groups_for_class($id_classe[$j]);

		$chaine_id_groupe="";
		$chaine_lien_id_groupe="";

		// G�n�rer la page d'index
		$html="";
		//$html.=html_entete();
		$html.="<h1 style='text-align:center;'>Cahiers de textes de ".$nom_classe[$j]."(".$gepiSchoolName." - ".$gepiYear.")</h1>\n";
		$html.="<p>Cahier de textes (<i>$display_date_debut - $display_date_fin</i>) de&nbsp;:</p>\n";
		$html.="<ul>\n";
		for($i=0;$i<count($groups);$i++) {
			$current_group=$groups[$i];
			if(in_array($current_group['id'],$id_groupe)) {
				//$current_group=get_group($id_groupe[$i]);
			
				if($i>0) {
					$chaine_id_groupe.=", ";
					$chaine_lien_id_groupe.=", ";
				}
				$chaine_id_groupe.="'".$current_group['id']."'";
				$chaine_lien_id_groupe.="'".$current_group['id']."_".$id_classe[$j]."'";
			
				$nom_groupe=preg_replace("/[^A-Za-z0-9]/","_",remplace_accents($current_group['name'],'all'));
				$description_groupe=preg_replace("/[^A-Za-z0-9]/","_",remplace_accents($current_group['description'],'all'));
				$classlist_string_groupe=preg_replace("/[^A-Za-z0-9]/","_",remplace_accents($current_group['classlist_string'],'all'));

				$nom_page_html_groupe=$current_group['id']."_".$nom_groupe."_".$description_groupe."_".$classlist_string_groupe.".html";
			
				$nom_fichier[$current_group['id']]=$nom_page_html_groupe;
				$nom_detaille_groupe[$current_group['id']]=$current_group['name']." (<i>".$current_group['description']." en (".$current_group['classlist_string'].")</i>)";
			
				$nom_detaille_groupe_non_html[$current_group['id']]=$current_group['name']." (".$current_group['description']." en (".$current_group['classlist_string']."))";
			
				$html.="<li><a id='lien_id_groupe_".$current_group['id']."_".$id_classe[$j]."' href='cahier_texte/$nom_page_html_groupe'>".$current_group['name']." (<i>".$current_group['description']." en (".$current_group['classlist_string'].")</i>)</a></li>\n";
			}
		}
		$html.="</ul>\n";
		//$html.=html_pied_de_page();
		
		//================================================================
		// Affichage dans la page d'export de ce qui va �tre fourni en zip
		echo "<a name='affichage_page_index_".$id_classe[$j]."'></a>";
		echo "<div style='border: 1px solid black;'>\n";
		echo $html;
		echo "</div>\n";

		// Pr�caution
		$chaine_id_groupe=preg_replace("/^,/","",$chaine_id_groupe);
		$chaine_lien_id_groupe=preg_replace("/^,/","",$chaine_lien_id_groupe);

		// Correctif des liens tels qu'affich�s dans la page
		echo "<script type='text/javascript'>
			tab_id_groupe=new Array($chaine_id_groupe);
			tab_lien_id_groupe=new Array($chaine_lien_id_groupe);

			for(i=0;i<tab_id_groupe.length;i++) {
				if(document.getElementById('lien_id_groupe_'+tab_lien_id_groupe[i])) {
					document.getElementById('lien_id_groupe_'+tab_lien_id_groupe[i]).href='#div_lien_retour_'+tab_id_groupe[i];
				}
			}
		</script>\n";
		//================================================================
		
		$html=html_entete("Index des cahiers de textes de ".$nom_classe[$j],0).$html;
		$html.=html_pied_de_page();

		//echo "Ecriture de ".$dossier_export."/".$nom_fichier_index[$id_classe[$j]]."<br />";
		$f=fopen($dossier_export."/".$nom_fichier_index[$id_classe[$j]],"w+");
		fwrite($f,$html);
		fclose($f);
		
		$tab_fichiers_a_zipper[]=$dossier_export."/".$nom_fichier_index[$id_classe[$j]];

	}

}

echo "<hr width='200px' />\n";

// Dans la page g�n�r�e, permettre de masquer via JavaScript telle ou telle cat�gorie Notices ou devoirs,...
for($i=0;$i<count($id_groupe);$i++) {
	//echo "\$id_groupe[$i]=$id_groupe[$i]<br />";

	unset($chaine_cpt_classe);

	$tab_dates=array();
	$tab_dates2=array();
	$tab_chemin_url=array();

	$tab_notices=array();
	$tab_dev=array();

	$html="";
	//$html.=html_entete();

	if(isset($id_classe)) {
		// On a choisi une liste de classes/enseignements

		$tab_champs=array('classes');
		$current_group=get_group($id_groupe[$i],$tab_champs);

		if(isset($current_group)) {
			$html.="<div id='div_lien_retour_".$id_groupe[$i]."' class='noprint' style='float:right; width:6em'>";
			if(count($current_group["classes"]["list"])==1) {
				$html.="<a id='lien_retour_".$id_groupe[$i]."' href='../index.html'>Retour</a>\n";
			}
			else {
				$chaine_cpt_classe="";
				$cpt=0;
				foreach($current_group["classes"]["classes"] as $current_id_classe => $current_classe) {
					if($cpt>0) {$chaine_cpt_classe.=", ";}

					$html.="<a id='lien_retour_".$id_groupe[$i]."_$cpt' href='../".$nom_fichier_index[$current_id_classe]."'>".$current_classe['classe']."</a>\n";

					$chaine_cpt_classe.=$cpt;

					$cpt++;
				}
			}
			$html.="</div>\n";
		}
	}
	else {
		// On a choisi un professeur
		$html.="<div id='div_lien_retour_".$id_groupe[$i]."' class='noprint' style='float:right; width:6em'><a id='lien_retour_".$id_groupe[$i]."' href='../index.html'>Retour</a></div>\n";
	}

	$html.="<h1 style='text-align:center;'>Cahiers de textes (".$gepiSchoolName." - ".$gepiYear.")</h1>\n";
	$html.="<p style='text-align:center;'>Extraction du $display_date_debut au $display_date_fin</p>\n";
	$html.="<h2 style='text-align:center;'>Cahier de textes de ".$nom_detaille_groupe[$id_groupe[$i]]." (<i>$display_date_debut - $display_date_fin</i>)&nbsp;:</h2>\n";

	$sql="SELECT cte.* FROM ct_entry cte WHERE (contenu != ''
		AND date_ct != ''
		AND date_ct >= '".$timestamp_debut_export."'
		AND date_ct <= '".$timestamp_fin_export."'
		AND id_groupe='".$id_groupe[$i]."'
		) ORDER BY date_ct DESC, heure_entry DESC;";
	//echo "$sql<br />";
	$res=mysql_query($sql);
	$cpt=0;
	while($lig=mysql_fetch_object($res)) {

		//echo "$lig->date_ct<br />";
		$date_notice=strftime("%a %d %b %y", $lig->date_ct);
		if(!in_array($date_notice,$tab_dates)) {
			$tab_dates[]=$date_notice;
			$tab_dates2[]=$lig->date_ct;
		}
		$tab_notices[$date_notice][$cpt]['id_ct']=$lig->id_ct;
		$tab_notices[$date_notice][$cpt]['id_login']=$lig->id_login;
		$tab_notices[$date_notice][$cpt]['contenu']=$lig->contenu;
		//echo " <span style='color:red'>\$tab_notices[$date_notice][$cpt]['contenu']=$lig->contenu</span><br />";
		$cpt++;

	}

	$sql="SELECT ctd.* FROM ct_devoirs_entry ctd WHERE (contenu != ''
		AND date_ct != ''
		AND date_ct >= '".$timestamp_debut_export."'
		AND date_ct <= '".$timestamp_fin_export."'
		AND id_groupe='".$id_groupe[$i]."'
		) ORDER BY date_ct DESC;";
	//echo "$sql<br />";
	$res=mysql_query($sql);
	$cpt=0;
	while($lig=mysql_fetch_object($res)) {
		//echo "$lig->date_ct<br />";
		$date_dev=strftime("%a %d %b %y", $lig->date_ct);
		if(!in_array($date_dev,$tab_dates)) {
			$tab_dates[]=$date_dev;
			$tab_dates2[]=$lig->date_ct;
		}
		$tab_dev[$date_dev][$cpt]['id_ct']=$lig->id_ct;
		$tab_dev[$date_dev][$cpt]['id_login']=$lig->id_login;
		$tab_dev[$date_dev][$cpt]['contenu']=$lig->contenu;
		//echo " <span style='color:green'>\$tab_dev[$date_dev][$cpt]['contenu']=$lig->contenu</span><br />";
		$cpt++;
	}
	//echo "\$current_ordre=$current_ordre<br />";
	//sort($tab_dates);
	if($current_ordre=='ASC') {
		array_multisort ($tab_dates, SORT_DESC, SORT_NUMERIC, $tab_dates2, SORT_ASC, SORT_NUMERIC);
	}
	else {
		array_multisort ($tab_dates, SORT_ASC, SORT_NUMERIC, $tab_dates2, SORT_DESC, SORT_NUMERIC);
	}

	$html.=lignes_cdt($tab_dates, $tab_notices, $tab_dev);

	//================================================================
	echo "<div style='border: 1px solid black;'>\n";
	echo $html;
	echo "</div>\n";

	echo "<script type='text/javascript'>
	if(document.getElementById('div_lien_retour_".$id_groupe[$i]."')) {
		//document.getElementById('div_lien_retour_".$id_groupe[$i]."').style.display='none';
		if(document.getElementById('lien_retour_".$id_groupe[$i]."')) {
			document.getElementById('lien_retour_".$id_groupe[$i]."').href='#affichage_page_index';
		}
";
	if(isset($chaine_cpt_classe)) {
		echo "
		tab_cpt_classe=new Array($chaine_cpt_classe);
		for(i=0;i<tab_cpt_classe.length;i++) {
			if(document.getElementById('lien_retour_".$id_groupe[$i]."_'+tab_cpt_classe[i])) {
				document.getElementById('lien_retour_".$id_groupe[$i]."_'+tab_cpt_classe[i]).href='#affichage_page_index';
			}
		}
";
	}
	echo "
	}
</script>\n";
	//================================================================

	$html=html_entete("CDT: ".$nom_detaille_groupe_non_html[$id_groupe[$i]],1).$html;
	$html.=html_pied_de_page();

	$f=fopen($dossier_export."/cahier_texte/".$nom_fichier[$id_groupe[$i]],"w+");
	fwrite($f,$html);
	fclose($f);

	$tab_fichiers_a_zipper[]=$dossier_export."/cahier_texte/".$nom_fichier[$id_groupe[$i]];

	if(count($tab_chemin_url)) {
		$fichier_url=$dossier_export."/url_documents.txt";
		$f=fopen($fichier_url,"a+");
		for($k=0;$k<count($tab_chemin_url);$k++) {
			fwrite($f,$tab_chemin_url[$k]."\n");
		}
		fclose($f);

		$tab_fichiers_a_zipper[]=$fichier_url;
	}

	echo "<hr width='200px' />\n";
}


// G�n�rer des fichiers URL_documents.txt (URL seule), URL_documents.csv (chemin;URL), script bash/batch/auto-it pour t�l�charger en cr�ant/parcourant l'arborescence des documents

if(isset($_SERVER['HTTP_REFERER'])) {
	$tmp=explode("?",$_SERVER['HTTP_REFERER']);
	//$chemin_site=my_ereg_replace("/cahier_texte_2/export_cdt.php$","",$tmp[0]);
	$chemin_site=preg_replace("#/cahier_texte_2#","",dirname($tmp[0]));

	$fichier_url_site=$dossier_export."/url_site.txt";
	$f=fopen($fichier_url_site,"a+");
	fwrite($f,$chemin_site."\n");
	fclose($f);

	$tab_fichiers_a_zipper[]=$fichier_url_site;
}

if($action=='export_zip') {
	require_once("../lib/pclzip.lib.php");
	
	$fichier_archive="../temp/$dirname/".$nom_export.".zip";
	$archive = new PclZip($fichier_archive);
	$v_list = $archive->create($tab_fichiers_a_zipper,"","../temp/$dirname/");
	if($v_list==0) {
		echo "<p>Cahiers de textes extraits&nbsp;: <a href='$dossier_export'>$dossier_export</a></p>\n";
	
		echo "<p style='color:red;'>ERREUR lors de la cr�ation de l'archive&nbsp;:<br />";
		echo $archive->errorInfo(true);
		echo "</p>\n";
	}
	else {
		$basename_fichier_archive=basename($fichier_archive);
		echo "<p class='bold'>Archive des cahiers de textes extraits&nbsp;: <a href='$fichier_archive'>$basename_fichier_archive</a></p>\n";
	
		echo "<script type='text/javascript'>
	if(document.getElementById('div_archive_zip')) {
		document.getElementById('div_archive_zip').innerHTML=\"<p class='bold'>Archive des cahiers de textes extraits&nbsp;: <a href='$fichier_archive'>$basename_fichier_archive</a></p>\"
	}
</script>\n";
	}

	// On fait le m�nage
	for($i=0;$i<count($tab_fichiers_a_zipper);$i++) {
		//echo "unlink($tab_fichiers_a_zipper[$i]);<br />";
		if(file_exists($tab_fichiers_a_zipper[$i])) {unlink($tab_fichiers_a_zipper[$i]);}
	}
	
	rmdir($dossier_export."/cahier_texte");
	rmdir($dossier_export."/css");
	rmdir($dossier_export);
}
elseif($action=='acces') {

	$chaine_info_texte="<br /><p><b>Information&nbsp;:</b><br />Le(s) cahier(s) de textes extrait(s) est(sont) accessible(s) sans authentification � l'adresse suivante&nbsp;:<br /><a href='$dossier_export/index.html' target='_blank'>$dossier_export/index.html</a><br />Consultez la page, copiez l'adresse en barre d'adresse et transmettez la � qui vous souhaitez.<br />N'oubliez pas de supprimer cet acc�s lorsqu'il ne sera plus utile.<br />&nbsp;</p>";

	echo $chaine_info_texte;

	echo "<script type='text/javascript'>

	if(document.getElementById('div_archive_zip')) {
		document.getElementById('div_archive_zip').innerHTML=\"$chaine_info_texte\";
	}

	//url=document.location;
	//alert(url);
	//var reg = new RegExp('cahier_texte_2/export_cdt.*','');
	//alert(document.location.replace(reg,''));
</script>\n";

}



/*
require('../fpdf/fpdf.php');
require('../fpdf/ex_fpdf.php');

define('FPDF_FONTPATH','../fpdf/font/');
define('LargeurPage','210');
define('HauteurPage','297');

require_once("../impression/class_pdf.php");
require_once ("../impression/liste.inc.php");

$marge_haut = 10 ;
$marge_droite = 10 ;
$marge_gauche = 10 ;
$marge_bas = 10 ;
$marge_reliure = 1 ;
$avec_emplacement_trous = 1 ;

if ($marge_reliure==1) {
  if ($marge_gauche < 18) {$marge_gauche = 18;}
}


//Calcul de la Zone disponible
$EspaceX = LargeurPage - $marge_droite - $marge_gauche ;
$EspaceY = HauteurPage - $marge_haut - $marge_bas;

$X_tableau = $marge_gauche;


//ent�te classe et ann�e scolaire
$L_entete_classe = 65;
$H_entete_classe = 14;
$X_entete_classe = $EspaceX - $L_entete_classe + $marge_gauche;
$Y_entete_classe = $marge_haut;

$X_entete_matiere = $marge_gauche;
$Y_entete_matiere = $marge_haut;
$L_entete_discipline = 65;
$H_entete_discipline = 14;

$pdf=new rel_PDF("P","mm","A4");
$pdf->SetTopMargin($marge_haut);
$pdf->SetRightMargin($marge_droite);
$pdf->SetLeftMargin($marge_gauche);
$pdf->SetAutoPageBreak(true, $marge_bas);

$pdf->AddPage("P"); //ajout d'une page au document
$pdf->SetDrawColor(0,0,0);
$pdf->SetFont('Arial');
$pdf->SetXY(20,20);
$pdf->SetFontSize(14);
$pdf->Cell(90,7, "TEST",0,2,'');

$pdf->SetXY(20,40);
$pdf->SetFontSize(10);
$pdf->Cell(150,7, "Blablabla.",0,2,'');

$nom_releve=date("Ymd_Hi");
$nom_releve = 'Test'.'.pdf';
//send_file_download_headers('application/pdf',$nom_releve);
$pdf->Output("../temp/".get_user_temp_directory()."/".$nom_releve,'F');

echo "<p><a href='../temp/".get_user_temp_directory()."/".$nom_releve."'>$nom_releve</a></p>";
//die();
*/

echo "<p><br /></p>\n";
require("../lib/footer.inc.php");
?>
