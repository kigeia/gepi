<?php
/*
*
* $Id$
*
* Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, St�phane Boireau, Christian Chapel
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


$sql="SELECT 1=1 FROM droits WHERE id='/bulletin/bull_index.php';";
$res_test=mysql_query($sql);
if (mysql_num_rows($res_test)==0) {
	$sql="INSERT INTO droits VALUES ('/bulletin/bull_index.php', 'V', 'V', 'F', 'V', 'F', 'F', 'F', 'F', 'Edition des bulletins', '1');";
	$res_insert=mysql_query($sql);
}
if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

//================================

$intercaler_releve_notes=isset($_POST['intercaler_releve_notes']) ? $_POST['intercaler_releve_notes'] : NULL;

$mode_bulletin=isset($_POST['mode_bulletin']) ? $_POST['mode_bulletin'] : NULL;

// Variable non encore utilis�e:
$contexte_document_produit="bulletin";
// Pour sur le verso du bulletin n'avoir qu'un relev� de notes et pas deux... et surtout pas celui de l'�l�ve suivant dans la liste:
$nb_releve_par_page=1;

$bull_pdf_debug=isset($_POST['bull_pdf_debug']) ? $_POST['bull_pdf_debug'] : "n";

//debug_var();

// Fonction de recherche des conteneurs derniers enfants (sans enfants (non parents, en somme))
// avec recalcul des moyennes lanc�...
function recherche_enfant($id_parent_tmp){
	global $current_group, $periode_num, $id_racine;
	$sql="SELECT * FROM cn_conteneurs WHERE parent='$id_parent_tmp'";
	//echo "<!-- $sql -->\n";
	$res_enfant=mysql_query($sql);
	if(mysql_num_rows($res_enfant)>0){
		while($lig_conteneur_enfant=mysql_fetch_object($res_enfant)){
			recherche_enfant($lig_conteneur_enfant->id);
		}
	}
	else{
		$arret = 'no';
		$id_conteneur_enfant=$id_parent_tmp;
		// Mise_a_jour_moyennes_conteneurs pour un enfant non parent...
		mise_a_jour_moyennes_conteneurs($current_group, $periode_num,$id_racine,$id_conteneur_enfant,$arret);
		//echo "<!-- ========================================== -->\n";
	}
}


//====================================================
//=============== ENTETE STANDARD ====================
if (!isset($_POST['valide_select_eleves'])) {
	//**************** EN-TETE *********************
	$titre_page = "Edition des bulletins";
	require_once("../lib/header.inc");
	//**************** FIN EN-TETE *****************
}
//============== FIN ENTETE STANDARD =================
//====================================================
//============== ENTETE BULLETIN HTML ================
elseif ((isset($_POST['mode_bulletin']))&&($_POST['mode_bulletin']=='html')) {
	//=============================================
	// Faire les extractions pour le relev� de notes si jamais cela a �t� demand�.
	//$intercaler_releve_notes="y";
	if(isset($intercaler_releve_notes)) {
		// On n'extrait les relev�s de notes que pour la/les p�riodes choisies pour les bulletins
		$choix_periode='periode';
		include("../cahier_notes/initialisations_header_releves_html.php");
	}
	//=============================================
	include("header_bulletin_html.php");
}
//============ FIN ENTETE BULLETIN HTML ==============
//====================================================
//============== ENTETE BULLETIN HTML ================
elseif ((isset($_POST['mode_bulletin']))&&($_POST['mode_bulletin']=='pdf')) {

	$mode_utf8_pdf=getSettingValue("mode_utf8_bulletins_pdf");
	if($mode_utf8_pdf=="") {$mode_utf8_pdf="n";}

	// DEBUG D�commenter la ligne ci-dessous pour d�bugger
	//echo "<p style='color:red;'>Insertion d'une ligne avant le Header pour provoquer l'affichage dans le navigateur et ainsi rep�rer des erreurs.</p>";
	//echo "\$bull_pdf_debug=$bull_pdf_debug<br />";
	if($bull_pdf_debug=='y') {
		echo "<p style='color:red'>DEBUG:<br />
La g�n�ration du PDF va �chouer parce qu'on affiche ces informations de debuggage,<br />
mais il se peut que vous ayez ainsi des pr�cisions sur ce qui pose probl�me.<br />
</p>\n";
	}

	include("header_bulletin_pdf.php");

	//=============================================
	// Faire les extractions pour le relev� de notes si jamais cela a �t� demand�.
	//$intercaler_releve_notes="y";
	if(isset($intercaler_releve_notes)) {
		// On n'extrait les relev�s de notes que pour la/les p�riodes choisies pour les bulletins
		$choix_periode='periode';
		// REVOIR LE HEADER POUR PDF: QUE FAUT-IL EXTRAIRE COMME PARAMETRES SPECIFIQUES AU PDF
		//include("../cahier_notes/initialisations_header_releves_html.php");
		include("header_releve_pdf.php");
	}
	//=============================================

/*
	//=========================================
	//cr�ation du PDF en mode Portrait, unit�e de mesure en mm, de taille A4
	$pdf=new bul_PDF('p', 'mm', 'A4');
	$nb_eleve_aff = 1;
	$categorie_passe = '';
	$categorie_passe_count = 0;
	$pdf->SetCreator($gepiSchoolName);
	$pdf->SetAuthor($gepiSchoolName);
	$pdf->SetKeywords('');
	$pdf->SetSubject('Bulletin');
	$pdf->SetTitle('Bulletin');
	$pdf->SetDisplayMode('fullwidth', 'single');
	$pdf->SetCompression(TRUE);
	$pdf->SetAutoPageBreak(TRUE, 5);

	$pdf->AddPage(); //ajout d'une page au document
	$pdf->SetFont('Arial');

	if ( !isset($X_etab) or empty($X_etab) ) {
		$X_etab = '5';
		$Y_etab = '5';
	}
	$pdf->SetXY($X_etab,$Y_etab);
	$pdf->SetFont('Arial','',14);
	$gepiSchoolName=getSettingValue("gepiSchoolName") ? getSettingValue("gepiSchoolName") : "gepiSchoolName";
	$pdf->Cell(90,7, $gepiSchoolName,0,2,'');


	//fermeture du fichier pdf et lecture dans le navigateur 'nom', 'I/D'
	$nom_bulletin = 'bulletin_'.$nom_bulletin.'.pdf';
	$pdf->Output($nom_bulletin,'I');
	die();
	//=========================================
*/
}
//============ FIN ENTETE BULLETIN HTML ==============

//echo "microtime()=".microtime()."<br />";
//echo "time()=".time()."<br />";

$debug="n";
$tab_instant=array();
include("bull_func.lib.php");

if((!isset($_POST['mode_bulletin']))||($_POST['mode_bulletin']!='pdf')) {
	//==============================
	$motif="Duree_totale";
	//decompte_debug($motif,"T�moin de $motif initialisation");
	decompte_debug($motif,"");
	flush();
	//==============================
}

//$tab_id_classe=isset($_POST['tab_id_classe']) ? $_POST['tab_id_classe'] : NULL;
//$tab_periode_num=isset($_POST['tab_periode_num']) ? $_POST['tab_periode_num'] : NULL;
//$choix_periode_num=isset($_POST['choix_periode_num']) ? $_POST['choix_periode_num'] : NULL;
$tab_id_classe=isset($_POST['tab_id_classe']) ? $_POST['tab_id_classe'] : (isset($_GET['tab_id_classe']) ? $_GET['tab_id_classe'] : NULL);
$tab_periode_num=isset($_POST['tab_periode_num']) ? $_POST['tab_periode_num'] : (isset($_GET['tab_periode_num']) ? $_GET['tab_periode_num'] : NULL);
$choix_periode_num=isset($_POST['choix_periode_num']) ? $_POST['choix_periode_num'] : (isset($_GET['choix_periode_num']) ? $_GET['choix_periode_num'] : NULL);

//======================================================
//==================CHOIX DES CLASSES===================
if(!isset($tab_id_classe)) {
	//echo "<p class='bold'><a href='index.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
	echo "<p class='bold'><a href='../accueil.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
	if((($_SESSION['statut']=='scolarite')&&(getSettingValue('GepiScolImprBulSettings')=='yes'))||
	(($_SESSION['statut']=='professeur')&&(getSettingValue('GepiProfImprBulSettings')=='yes'))||
	(($_SESSION['statut']=='administrateur')&&(getSettingValue('GepiAdminImprBulSettings')=='yes'))) {
		echo " | <a href='param_bull.php' target='_blank'>Param�tres d'impression des bulletins</a>";
	}
	echo " | <a href='index.php'>Ancien dispositif</a>";
	echo "</p>\n";

	echo "<p class='bold'>Choix des classes:</p>\n";

	if (($_SESSION['statut'] == 'professeur') and getSettingValue("GepiProfImprBul")!='yes') {
		echo "<p>Droits insuffisants pour effectuer cette op�ration</p>\n";
		require("../lib/footer.inc.php");
		die();
	}

	// Liste des classes avec �l�ve:
	//$sql="SELECT DISTINCT c.* FROM j_eleves_classes jec, classes c WHERE c.id=jec.id_classe ORDER BY c.classe;";
	if ($_SESSION["statut"] == "scolarite") {
		// On s�lectionne les classes associ�es au compte scolarit�
		$sql="SELECT DISTINCT c.* FROM classes c, j_scol_classes jsc, j_eleves_classes jec WHERE (jec.id_classe=c.id AND jsc.id_classe=c.id AND jsc.login='".$_SESSION['login']."') ORDER BY c.classe;";

		$message_0="Aucune classe (<i>avec �l�ve</i>) ne vous est affect�e.";
	}
	//elseif ($_SESSION["statut"] == "administrateur") {
	elseif (($_SESSION["statut"] == "administrateur")||($_SESSION["statut"] == "secours")) {
		// On selectionne toutes les classes
		//$sql="SELECT DISTINCT c.* FROM classes c WHERE 1";
		$sql="SELECT DISTINCT c.* FROM j_eleves_classes jec, classes c WHERE (c.id=jec.id_classe) ORDER BY c.classe;";

		$message_0="Aucune classe avec �l�ve n'a �t� trouv�e.";
	}
	elseif ($_SESSION["statut"] == "professeur") {
		$sql="SELECT DISTINCT c.* FROM classes c, j_eleves_professeurs jep, j_eleves_classes jec WHERE (jep.professeur='".$_SESSION['login']."' AND jep.login = jec.login AND jec.id_classe = c.id);";

			$message_0="Aucune classe (<i>avec �l�ve</i>) ne vous est affect�e pour l'�dition des bulletins.";
	}
	else {
		// On ne devrait pas arriver jusque-l�...
		echo "<p>Droits insuffisants pour effectuer cette op�ration</p>\n";
		require("../lib/footer.inc.php");
		die();
	}

	$call_classes=mysql_query($sql);

	$nb_classes=mysql_num_rows($call_classes);
	if($nb_classes==0){
		//echo "<p>Aucune classe avec �l�ve affect� n'a �t� trouv�e.</p>\n";
		echo "<p>".$message_0."</p>\n";

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

		//affichage 2 colonnes
		if(($cpt>0)&&(round($cpt/$nb_classes_par_colonne)==$cpt/$nb_classes_par_colonne)){
			echo "</td>\n";
			echo "<td align='left'>\n";
		}

		echo "<label id='label_tab_id_classe_$cpt' for='tab_id_classe_$cpt' style='cursor: pointer;'><input type='checkbox' name='tab_id_classe[]' id='tab_id_classe_$cpt' value='$lig_clas->id' onchange='change_style_classe($cpt)' /> $lig_clas->classe</label>";
		echo "<br />\n";
		$cpt++;
	}

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<p><a href='#' onClick='ModifCase(true)'>Tout cocher</a> / <a href='#' onClick='ModifCase(false)'>Tout d�cocher</a></p>\n";

	echo "<p><input type='submit' value='Valider' /></p>\n";
	echo "</form>\n";

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
//======================================================
//=================CHOIX DES PERIODES===================
//elseif(!isset($tab_periode_num)) {
//elseif(!isset($choix_periode_num)) {
elseif((!isset($choix_periode_num))||(!isset($tab_periode_num))) {
	//debug_var();

	echo "<p class='bold'>";
	//echo "<a href='".$_SERVER['PHP_SELF']."'>Choisir d'autres classes</a>\n";
	echo "<a href='bull_index.php'>Choisir d'autres classes</a>\n";
	if((($_SESSION['statut']=='scolarite')&&(getSettingValue('GepiScolImprBulSettings')=='yes'))||
	(($_SESSION['statut']=='professeur')&&(getSettingValue('GepiProfImprBulSettings')=='yes'))||
	(($_SESSION['statut']=='administrateur')&&(getSettingValue('GepiAdminImprBulSettings')=='yes'))) {
		echo " | <a href='param_bull.php' target='_blank'>Param�tres d'impression des bulletins</a>";
	}
	echo "</p>\n";

	// Choisir les p�riodes permettant l'�dition de bulletin

	echo "<p class='bold'>Choix des p�riodes:</p>\n";

	/*
	$sql="SELECT MAX(num_periode) max_per FROM periodes;";
	$res_max_per=mysql_query($sql);
	$lig_max_per=mysql_fetch_object($res_max_per);
	$max_per=$lig_max_per->max_per;
	*/

	$tab_periode_num_excluses=array();

	//$tab_periode_num=array();

	echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='formulaire'>\n";

	$max_per=0;
	for($i=0;$i<count($tab_id_classe);$i++) {
		// Est-ce bien un entier?
		if((strlen(preg_replace("/[0-9]/","",$tab_id_classe[$i])))||($tab_id_classe[$i]=="")) {
			echo "<p>Identifiant de classe erron�: <span style='color:red'>".$tab_id_classe[$i]."</span></p>\n";
			require("../lib/footer.inc.php");
			die();
		}

		echo "<input type='hidden' name='tab_id_classe[$i]' value='".$tab_id_classe[$i]."' />\n";

		$sql="SELECT p.* FROM periodes p WHERE p.id_classe='".$tab_id_classe[$i]."' ORDER BY num_periode;";
		$res_per=mysql_query($sql);

		if(mysql_num_rows($res_per)>0) {
			$ligne_debug[$i]="<tr><td>".get_class_from_id($tab_id_classe[$i])."</td>\n";
			while($lig_per=mysql_fetch_object($res_per)) {
				if($lig_per->verouiller=="O") {
					$ligne_debug[$i].="<td style='background-color:lightgreen; text-align:center;'>Close</td>\n";
				}
				elseif($lig_per->verouiller=="N") {
					$ligne_debug[$i].="<td style='background-color:red; text-align:center;'>Non close</td>\n";
					if(!in_array($lig_per->num_periode,$tab_periode_num_excluses)) {$tab_periode_num_excluses[]=$lig_per->num_periode;}
					/*
					if($_SESSION['statut']=='scolarite') {
						$ligne_debug[$i].=" <a href='verrouillage.php' target='_blank'><img src='../images/icons/configure.png' width='16' height='16' title='Verrouillage/d�verrouillage'/></a>\n";
					}
					*/
				}
				else {
					$ligne_debug[$i].="<td style='background-color:orange; text-align:center;'>Partiellement close</td>\n";
				}

				if($lig_per->num_periode>$max_per) {$max_per=$lig_per->num_periode;}
			}
			$ligne_debug[$i].="</tr>\n";
		}
	}



	echo "<table class='boireaus' summary='Choix des p�riodes'>\n";
	echo "<tr>\n";
	echo "<th>Classe</th>\n";
	for($i=1;$i<=$max_per;$i++) {
		echo "<th>P�riode $i</th>\n";
	}
	echo "</tr>\n";

	for($i=0;$i<count($tab_id_classe);$i++) {
		echo $ligne_debug[$i];
	}

	echo "<tr>\n";
	echo "<th>Choix</th>\n";
	for($i=1;$i<=$max_per;$i++) {
		if(!in_array($i,$tab_periode_num_excluses)) {
			echo "<td style='background-color:lightgreen; text-align:center;'><input type='checkbox' name='tab_periode_num[]' value='$i' /></td>\n";
		}
		else {
			echo "<td style='background-color:red; text-align:center;'>P�riode non close<br />pour une classe au moins";
			if($_SESSION['statut']=='scolarite') {
				echo " <a href='verrouillage.php' target='_blank'><img src='../images/icons/configure.png' width='16' height='16' title='Verrouillage/d�verrouillage'/></a>\n";
			}
			echo "</td>\n";
		}
	}
	echo "</tr>\n";

	echo "</table>\n";

	echo "<input type='hidden' name='choix_periode_num' value='fait' />\n";

	echo "<p><input type='submit' value='Valider' /></p>\n";
	echo "</form>\n";

}
//======================================================
//==============CHOIX DE LA SELECTION D'ELEVES==========
elseif(!isset($_POST['valide_select_eleves'])) {

	//debug_var();

	$preselection_eleves=isset($_POST['preselection_eleves']) ? $_POST['preselection_eleves'] : (isset($_GET['preselection_eleves']) ? $_GET['preselection_eleves'] : NULL);

	echo "<p class='bold'>";
	echo "<a href='".$_SERVER['PHP_SELF']."'>Choisir d'autres classes</a>\n";
	//echo " | <a href='".$_SERVER['PHP_SELF']."'>Choisir d'autres p�riodes</a>\n";
	//echo " | <a href='#' onClick='history.go(-1);'>Choisir d'autres p�riodes</a>\n";
	echo " | <a href='".$_SERVER['PHP_SELF']."' onClick=\"document.forms['form_retour'].submit();return false;\">Choisir d'autres p�riodes</a>\n";
	if((($_SESSION['statut']=='scolarite')&&(getSettingValue('GepiScolImprBulSettings')=='yes'))||
	(($_SESSION['statut']=='professeur')&&(getSettingValue('GepiProfImprBulSettings')=='yes'))||
	(($_SESSION['statut']=='administrateur')&&(getSettingValue('GepiAdminImprBulSettings')=='yes'))) {
		echo " | <a href='param_bull.php' target='_blank'>Param�tres d'impression des bulletins</a>";
	}
	echo "</p>\n";

	//===========================
	// FORMULAIRE POUR LE RETOUR AU CHOIX DES PERIODES
	echo "\n<!-- Formulaire de retour au choix des p�riodes -->\n";
	echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='form_retour'>\n";
	for($i=0;$i<count($tab_id_classe);$i++) {
		echo "<input type='hidden' name='tab_id_classe[$i]' value='".$tab_id_classe[$i]."' />\n";
	}
	for($j=0;$j<count($tab_periode_num);$j++) {
		echo "<input type='hidden' name='tab_periode_num[$j]' value='".$tab_periode_num[$j]."' />\n";
	}
	echo "</form>\n";
	//===========================


	//echo "<p class='bold'>S�lection des �l�ves:</p>\n";
	echo "<p class='bold'>S�lection des �l�ves et param�tres:</p>\n";

	echo "\n<!-- Formulaire de s�lection des �l�ves et de param�trage -->\n";
	echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='formulaire' target='_blank'>\n";

	//=======================================

	// HTML ou PDF
	$type_bulletin_par_defaut=getSettingValue('type_bulletin_par_defaut');
	if(($type_bulletin_par_defaut!='html')&&($type_bulletin_par_defaut!='pdf')) {$type_bulletin_par_defaut='html';}

	// A remplacer par la suite par un choix:
	//echo "<input type='hidden' name='mode_bulletin' value='html' />\n";
	echo "<table border='0' summary='Choix du type de bulletin'>\n";
	echo "<tr>\n";
	echo "<td valign='top'>\n";
	echo "<input type='radio' name='mode_bulletin' id='mode_bulletin_html' value='html' onchange='display_div_modele_bulletin_pdf();display_param_b_adr_pg()' ";
	if($type_bulletin_par_defaut=='html') {echo "checked ";}
	echo "/> ";
	echo "</td>\n";
	echo "<td>\n";
	echo "<label for='mode_bulletin_html' style='cursor:pointer;'>Bulletin HTML</label>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td valign='top'>\n";
	$test = sql_query1("SHOW TABLES LIKE 'modele_bulletin';");
	if ($test == -1) {
		echo "&nbsp;</td>\n";
		echo "<td valign='top'>\n";
		echo "Les mod�les PDF utilisent une table 'modele_bulletin' qui semble absente.<br />\n";
		if($_SESSION['statut']=='administrateur') {
			echo "Visitez la page de <a href='test_modele_bull.php'>cr�ation de cette table</a> d'apr�s l'ancienne table 'model_bulletin' pour permettre l'impression de bulletins PDF.";
		}
		else {
			echo "Le remplissage de la table doit �tre effectu� en admin.";
		}
	}
	else {
		$sql="SELECT 1=1 FROM modele_bulletin LIMIT 1;";
		//echo "$sql<br />";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)==0) {
			echo "&nbsp;</td>\n";
			echo "<td valign='top'>\n";
			echo "Les mod�les PDF utilisent une table 'modele_bulletin' qui semble absente.<br />\n";
			if($_SESSION['statut']=='administrateur') {
				echo "Visitez la page de <a href='test_modele_bull.php'>cr�ation de cette table</a> d'apr�s l'ancienne table 'model_bulletin' pour permettre l'impression de bulletins PDF.";
			}
			else {
				echo "Le remplissage de la table doit �tre effectu� en admin.";
			}
		}
		else {
			echo "<input type='radio' name='mode_bulletin' id='mode_bulletin_pdf' value='pdf' onchange='display_div_modele_bulletin_pdf();display_param_b_adr_pg()' ";
			if($type_bulletin_par_defaut=='pdf') {echo "checked ";}
			echo "/> ";
			echo "</td>\n";
			echo "<td>\n";
			echo "<label for='mode_bulletin_pdf' style='cursor:pointer;'>Bulletin PDF</label>\n";

			echo "<br />\n";
			//echo "<span id='div_modele_bulletin_pdf'>\n";
			echo "<div id='div_modele_bulletin_pdf'>\n";

				$option_modele_bulletin=getSettingValue("option_modele_bulletin");
				if (($option_modele_bulletin==2)||($option_modele_bulletin==3)) { //Par d�faut  le mod�le d�fini pour les classes
					echo "Choisir le mod�le de bulletin<br />\n";
					// s�lection des mod�les des bulletins PDF
					//$sql='SELECT id_model_bulletin, nom_model_bulletin FROM modele_bulletin ORDER BY modele_bulletin.nom_model_bulletin ASC';
					$sql="SELECT DISTINCT id_model_bulletin,valeur FROM modele_bulletin WHERE nom='nom_model_bulletin' ORDER BY id_model_bulletin ASC";
					//echo "$sql<br />";
					$requete_modele = mysql_query($sql);
					echo "<select tabindex=\"5\" name=\"type_bulletin\">";
					$option_modele_bulletin=getSettingValue("option_modele_bulletin");
					if ($option_modele_bulletin==2) { //Par d�faut  le mod�le d�fini pour les classes
						echo "<option value=\"-1\">Utiliser les mod�les pr�-s�lectionn�s par classe</option>\n";
					}
	
					while($donner_modele = mysql_fetch_array($requete_modele)) {
						echo "<option value=\"".$donner_modele['id_model_bulletin']."\"";
						echo ">".ucfirst($donner_modele['valeur'])."</option>\n";
					}
					echo "</select>\n";
					echo "<br />\n";
				}
				else {
					echo "<input type='hidden' name='type_bulletin' value='-1' />\n";
				}
			//echo "</span>\n";

			echo "<input type='checkbox' name='use_cell_ajustee' id='use_cell_ajustee' value='n' /><label for='use_cell_ajustee' style='cursor: pointer;'> Ne pas utiliser la nouvelle fonction use_cell_ajustee() pour l'�criture des appr�ciations.</label>";

			$titre_infobulle="Fonction cell_ajustee()\n";
			$texte_infobulle="Pour les appr�ciations sur les bulletins, relev�s,... on utilisait auparavant la fonction DraxTextBox() de FPDF.<br />Cette fonction avait parfois un comportement curieux avec des textes tronqu�s ou beaucoup plus petits dans la cellule que ce qui semblait pouvoir tenir dans la case.<br />La fonction cell_ajustee() est une fonction que mise au point pour tenter de faire mieux que DraxTextBox().<br />Comme elle n'a pas �t� exp�riment�e par suffisamment de monde sur trunk, nous avons mis une case � cocher qui permet d'utiliser l'ancienne fonction DrawTextBox() si cell_ajustee() ne se r�v�lait pas aussi bien fichue que nous l'esp�rons;o).<br />\n";
			//$texte_infobulle.="\n";
			$tabdiv_infobulle[]=creer_div_infobulle('a_propos_cell_ajustee',$titre_infobulle,"",$texte_infobulle,"",35,0,'y','y','n','n');
	
			echo "<a href=\"#\" onclick='return false;' onmouseover=\"afficher_div('a_propos_cell_ajustee','y',100,100);\"  onmouseout=\"cacher_div('a_propos_cell_ajustee');\"><img src='../images/icons/ico_ampoule.png' width='15' height='25' /></a>";

			echo "<br />\n";

			// Debug
			echo "<input type='checkbox' name='bull_pdf_debug' id='bull_pdf_debug' value='y' />&nbsp;<label for='bull_pdf_debug' style='cursor: pointer;'>Activer le debug pour afficher les erreurs perturbant la g�n�ration de PDF.</label>\n";

			$titre_infobulle="Debug\n";
			$texte_infobulle="Il arrive que la g�n�ration de PDF �choue.<br />Les raisons peuvent �tre variables (<em>manque de ressources serveur, bug,...</em>).<br />Dans ce cas, la pr�sence d'un plugin lecteur PDF peut emp�cher de voir quelles erreurs provoquent l'�chec.<br />En cochant la case DEBUG, vous obtiendrez l'affichage des erreurs et ainsi vous pourrez obtenir de l'aide plus facilement sur la liste 'gepi-users'<br />\n";
			//$texte_infobulle.="\n";
			$tabdiv_infobulle[]=creer_div_infobulle('div_bull_debug_pdf',$titre_infobulle,"",$texte_infobulle,"",35,0,'y','y','n','n');
	
			echo "<a href=\"#\" onclick='return false;' onmouseover=\"afficher_div('div_bull_debug_pdf','y',100,100);\"  onmouseout=\"cacher_div('div_bull_debug_pdf');\"><img src='../images/icons/ico_ampoule.png' width='15' height='25' /></a>";

			echo "<br />\n";

			echo "<br />\n";

			echo "</div>\n";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<script type='text/javascript'>
	function display_div_modele_bulletin_pdf() {
		if(document.getElementById('div_modele_bulletin_pdf')) {
			if(document.getElementById('mode_bulletin_pdf').checked==true) {
				document.getElementById('div_modele_bulletin_pdf').style.display='';
			}
			else {
				document.getElementById('div_modele_bulletin_pdf').style.display='none';
			}
		}
	}

	display_div_modele_bulletin_pdf();
</script>\n";


	//echo "<input type='hidden' name='un_seul_bull_par_famille' value='non' />\n";
	//=======================================
	echo "<input type='hidden' name='choix_periode_num' value='fait' />\n";

	//=======================================
	//echo "<div style='float:right; width:40%'>\n";
	echo "<div id='div_parametres'>\n";
	echo "<table border='0' summary='Tableau des param�tres'>\n";
	echo "<tr><td valign='top'><input type='checkbox' name='un_seul_bull_par_famille' id='un_seul_bull_par_famille' value='oui' /></td><td><label for='un_seul_bull_par_famille' style='cursor: pointer;'>Ne pas imprimer de bulletin pour le deuxi�me parent<br />(<i>m�me dans le cas de parents s�par�s</i>).</label></td></tr>\n";

	// A FAIRE:
	// Tester et ne pas afficher:
	// - si tous les coeff sont � 1
	//$test_coef=mysql_num_rows(mysql_query("SELECT coef FROM j_groupes_classes WHERE (id_classe='".$id_classe."' and coef!='1.0')"));
	//if($test_coef>0){
		/*
		echo "<tr>\n";
		echo "<td colspan=\"2\"><b>Calcul des moyennes g�n�rales";
		// Ne pas afficher la mention de cat�gorie, si on n'affiche pas les cat�gories dans cette classe.
		//$affiche_categories = sql_query1("SELECT display_mat_cat FROM classes WHERE id='".$id_classe."'");
		//if ($affiche_categories == "y") {
			echo " (<i>et par cat�gorie</i>)";
		//}
		echo ".</b></td>\n";
		echo "</tr>\n";
		echo "<tr><td valign='top'><input type='checkbox' name='coefficients_a_1' id='coefficients_a_1' value='oui' /></td><td><label for='coefficients_a_1' style='cursor: pointer;'>Forcer les coefficients des mati�res � 1, ind�pendamment des coefficients saisis dans les param�tres de la classe.</label></td></tr>\n";
		*/
		echo "<tr><td valign='top'><input type='checkbox' name='coefficients_a_1' id='coefficients_a_1' value='oui'  /></td><td><label for='coefficients_a_1' style='cursor: pointer;'>Forcer, dans le calcul des moyennes g�n�rales, les coefficients des mati�res � 1, ind�pendamment des coefficients saisis dans les param�tres de la classe.</label></td></tr>\n";

		echo "<tr><td valign='top'><input type='checkbox' name='moyennes_periodes_precedentes' id='moyennes_periodes_precedentes' value='y'  /></td><td><label for='moyennes_periodes_precedentes' style='cursor: pointer;'>Afficher les moyennes de l'�l�ve pour les p�riodes pr�c�dentes (<i>incompatible avec l'affichage des moyennes min/max/classe dans la m�me cellule que la moyenne de l'�l�ve</i>).</label></td></tr>\n";

		echo "<tr><td valign='top'><input type='checkbox' name='tri_par_etab_orig' id='tri_par_etab_orig' value='y' /></td><td><label for='tri_par_etab_orig' style='cursor: pointer;'>Trier les bulletins par �tablissement d'origine.</label></td></tr>\n";

		if(($_SESSION['statut']=='scolarite')||($_SESSION['statut']=='administrateur')) {
			echo "<tr><td valign='top'><input type='checkbox' name='forcer_recalcul_moy_conteneurs' id='forcer_recalcul_moy_conteneurs' value='y' /></td><td><label for='forcer_recalcul_moy_conteneurs' style='cursor: pointer;'>Forcer le recalcul des moyennes de conteneurs.</label></td></tr>\n";

			echo "<tr><td valign='top'><input type='checkbox' name='forcer_recalcul_rang' id='forcer_recalcul_rang' value='y' /></td><td><label for='forcer_recalcul_rang' style='cursor: pointer;'>Forcer le recalcul des rangs.</label></td></tr>\n";
		}
	//}
	echo "</table>\n";

	//===========================================
	$b_adr_pg_defaut=isset($_SESSION['b_adr_pg']) ? $_SESSION['b_adr_pg'] : "xx";

	echo "<div id='div_param_b_adr_pg'>\n";
	echo "<br />\n";
	echo "<p><b>Bloc adresse responsable et page de garde&nbsp;:</b></p>\n";
	echo "<blockquote>\n";
	echo "<input type='radio' name='b_adr_pg' id='b_adr_pg_xx' value='xx' ";
	if($b_adr_pg_defaut=="xx") {
		echo "checked='checked' ";
	}
	echo "/><label for='b_adr_pg_xx' style='cursor:pointer'> D'apr�s les param�tres du bulletin HTML</label><br />\n";

	echo "<input type='radio' name='b_adr_pg' id='b_adr_pg_nn' value='nn' ";
	if($b_adr_pg_defaut=="nn") {
		echo "checked='checked' ";
	}
	echo "/><label for='b_adr_pg_nn' style='cursor:pointer'> sans bloc adresse ni page de garde</label><br />\n";

	echo "<input type='radio' name='b_adr_pg' id='b_adr_pg_yn' value='yn' ";
	if($b_adr_pg_defaut=="yn") {
		echo "checked='checked' ";
	}
	echo "/><label for='b_adr_pg_yn' style='cursor:pointer'> avec bloc adresse sans page de garde</label><br />\n";

	echo "<input type='radio' name='b_adr_pg' id='b_adr_pg_ny' value='ny' ";
	if($b_adr_pg_defaut=="ny") {
		echo "checked='checked' ";
	}
	echo "/><label for='b_adr_pg_ny' style='cursor:pointer'> sans bloc adresse avec page de garde</label><br />\n";

	echo "<input type='radio' name='b_adr_pg' id='b_adr_pg_yy' value='yy' ";
	if($b_adr_pg_defaut=="yy") {
		echo "checked='checked' ";
	}
	echo "/><label for='b_adr_pg_yy' style='cursor:pointer'> avec bloc adresse et page de garde</label><br />\n";
	echo "</blockquote>\n";
	echo "</div>\n";
	//===========================================



	// L'admin peut avoir acc�s aux bulletins, mais il n'a de toute fa�on pas acc�s au relev�s de notes.
	$sql="SELECT 1=1 FROM droits WHERE id='/cahier_notes/visu_releve_notes_bis.php' AND ".$_SESSION['statut']."='V';";
	$res_verif_droit=mysql_query($sql);
	if (mysql_num_rows($res_verif_droit)>0) {
		echo "<p><input type='checkbox' name='intercaler_releve_notes' id='intercaler_releve_notes' value='y' onchange='display_param_releve();' /> <label for='intercaler_releve_notes' style='cursor: pointer;'>Intercaler les relev�s de notes</label>\n";

		echo "<span id='pliage_param_releve'>\n";
		echo "(<i>";
		echo "<a href='#' onclick=\"document.getElementById('div_param_releve').style.display='';return false;\">Afficher</a>";
		echo " / \n";
		echo "<a href='#' onclick=\"document.getElementById('div_param_releve').style.display='none';return false;\">Masquer</a>";
		echo " les param�tres du relev� de notes</i>).";
		echo "</span>\n";

		echo "<br />\n";
		echo "(<i>pour permettre par exemple une impression recto/verso</i>).";
		echo "</p>\n";


		echo "<div id='div_param_releve'>\n";
		include("../cahier_notes/tableau_choix_parametres_releves_notes.php");
		echo "</div>\n";

		echo "<script type='text/javascript'>
function CocheLigne(item) {
	for (var i=0;i<".count($tab_id_classe).";i++) {
		if(document.getElementById(item+'_'+i)){
			document.getElementById(item+'_'+i).checked = true;
		}
	}
}

function DecocheLigne(item) {
	for (var i=0;i<".count($tab_id_classe).";i++) {
		if(document.getElementById(item+'_'+i)){
			document.getElementById(item+'_'+i).checked = false;
		}
	}
}

function ToutCocher() {
";
		for($k=0;$k<count($tab_item);$k++) {
			echo "	CocheLigne('".$tab_item[$k]."');\n";
		}
		echo "	CocheLigne('rn_app');
	CocheLigne('rn_adr_resp');
}

function ToutDeCocher() {
";
		for($k=0;$k<count($tab_item);$k++) {
			echo "	DecocheLigne('".$tab_item[$k]."');\n";
		}
		echo "	DecocheLigne('rn_app');
	DecocheLigne('rn_adr_resp');
}

</script>\n";

	}


	echo "<p align='center'><input type='submit' name='bouton_valide_select_eleves1' value='Valider' /></p>\n";
	echo "</div>\n";


	//=======================================
	/*
	echo "<div style='float:right; width:5em;'>\n";
	echo "<input type='submit' name='bouton_valide_select_eleves1' value='Valider' />\n";
	echo "</div>\n";
	*/
	//=======================================



	if(count($tab_id_classe)>1) {
		echo "<p>Pour toutes les classes";
		if(count($tab_periode_num)>1) {
			echo " et toutes les p�riodes";
		}
		echo " <a href='#' onClick='cocher_tous_eleves();return false;'>Cocher tous les �l�ves</a> / <a href='#' onClick='decocher_tous_eleves();return false;'>D�cocher tous les �l�ves</a></p>\n";
	}

	$max_eff_classe=0;
	for($i=0;$i<count($tab_id_classe);$i++) {
		// Est-ce bien un entier?
		if((strlen(preg_replace("/[0-9]/","",$tab_id_classe[$i])))||($tab_id_classe[$i]=="")) {
			echo "<p>Identifiant de classe erron�: <span style='color:red'>".$tab_id_classe[$i]."</span></p></form>\n";
			require("../lib/footer.inc.php");
			die();
		}

		echo "<input type='hidden' name='tab_id_classe[$i]' value='".$tab_id_classe[$i]."' />\n";

		echo "<p class='bold'>Classe de ".get_class_from_id($tab_id_classe[$i])."</p>\n";

		echo "<table class='boireaus' summary='Choix des �l�ves'>\n";
		echo "<tr>\n";
		echo "<th>El�ves</th>\n";
		for($j=0;$j<count($tab_periode_num);$j++) {
			// Est-ce bien un entier?
			if((strlen(preg_replace("/[0-9]/","",$tab_periode_num[$j])))||($tab_periode_num[$j]=="")) {
				echo "<td>Identifiant de p�riode erron�: <span style='color:red'>".$tab_periode_num[$j]."</span></td></tr></table></form>\n";
				require("../lib/footer.inc.php");
				die();
			}

			//echo "<th>P�riode $j</th>\n";
			$sql="SELECT nom_periode FROM periodes WHERE id_classe='".$tab_id_classe[$i]."' AND num_periode='".$tab_periode_num[$j]."';";
			$res_per=mysql_query($sql);
			echo "<th>\n";
			$lig_per=mysql_fetch_object($res_per);

			echo "<input type='hidden' name='tab_periode_num[$j]' value='".$tab_periode_num[$j]."' />\n";

			// En imprimant deux classes l'une � semestres, l'autre � trimestres, on peut choisir la p�riode 3 et provoquer des erreurs sur les semestres...
			if(isset($lig_per->nom_periode)) {
				echo $lig_per->nom_periode;
			}
			else {
				echo "<span style='color:red'>X</span>";
			}

			echo "<br />\n";

			echo "<a href=\"javascript:CocheColonneSelectEleves(".$i.",".$j.");changement();\"><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a> / <a href=\"javascript:DecocheColonneSelectEleves(".$i.",".$j.");changement();\"><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";

			echo "</th>\n";
		}
		echo "</tr>\n";

		/*
		$sql="SELECT e.* FROM eleves e,
						j_eleves_classes jec
			WHERE jec.login=e.login AND
						jec.id_classe='".$tab_id_classe[$i]."' AND
						jec.periode='".$tab_periode_num[$j]."';";
		*/
		$sql="SELECT DISTINCT e.* FROM eleves e,
						j_eleves_classes jec
			WHERE jec.login=e.login AND
						jec.id_classe='".$tab_id_classe[$i]."'
			ORDER BY e.nom,e.prenom;";
		$res_ele=mysql_query($sql);
		$alt=1;
		$cpt=0;
		while($lig_ele=mysql_fetch_object($res_ele)) {
			$alt=$alt*(-1);
			echo "<tr class='lig$alt'>\n";
			echo "<td style='text-align:left;'>".$lig_ele->nom." ".$lig_ele->prenom."</td>\n";
			for($j=0;$j<count($tab_periode_num);$j++) {

				$sql="SELECT 1=1 FROM j_eleves_classes jec
					WHERE jec.id_classe='".$tab_id_classe[$i]."' AND
							jec.periode='".$tab_periode_num[$j]."';";
				$test=mysql_query($sql);
				if(mysql_num_rows($test)>0) {
					echo "<td><input type='checkbox' name='tab_selection_ele_".$i."_".$j."[]' id='tab_selection_ele_".$i."_".$j."_".$cpt."' value=\"".$lig_ele->login."\" ";
					if(!isset($preselection_eleves)) {echo "checked ";}
					//elseif((isset($preselection_eleves[$tab_periode_num[$j]]))&&(in_array($lig_ele->login,$preselection_eleves[$tab_periode_num[$j]]))) {echo "checked ";}
					elseif((isset($preselection_eleves[$tab_periode_num[$j]]))&&(strstr($preselection_eleves[$tab_periode_num[$j]],"|".$lig_ele->login."|"))) {echo "checked ";}
					echo "/></td>\n";
				}
				else {
					echo "<td>-</td>\n";
				}
			}
			echo "</tr>\n";
			$cpt++;
		}
		echo "</table>\n";

		if($max_eff_classe<$cpt) {$max_eff_classe=$cpt;}

	}

//count($tab_periode_num)

	echo "<script type='text/javascript'>

function CocheColonneSelectEleves(i,j) {
	for (var k=0;k<$max_eff_classe;k++) {
		if(document.getElementById('tab_selection_ele_'+i+'_'+j+'_'+k)){
			document.getElementById('tab_selection_ele_'+i+'_'+j+'_'+k).checked = true;
		}
	}
}

function DecocheColonneSelectEleves(i,j) {
	for (var k=0;k<$max_eff_classe;k++) {
		if(document.getElementById('tab_selection_ele_'+i+'_'+j+'_'+k)){
			document.getElementById('tab_selection_ele_'+i+'_'+j+'_'+k).checked = false;
		}
	}
}

function cocher_tous_eleves() {
";

	for($i=0;$i<count($tab_id_classe);$i++) {
		for($j=0;$j<count($tab_periode_num);$j++) {
			echo "CocheColonneSelectEleves($i,$j);\n";
		}
	}

	echo "}
function decocher_tous_eleves() {
";

	for($i=0;$i<count($tab_id_classe);$i++) {
		for($j=0;$j<count($tab_periode_num);$j++) {
			echo "DecocheColonneSelectEleves($i,$j);\n";
		}
	}

	echo "}

function display_param_releve() {
	if(document.getElementById('div_param_releve')) {
		if(document.getElementById('intercaler_releve_notes')) {
			if(document.getElementById('intercaler_releve_notes').checked==true) {
				document.getElementById('div_param_releve').style.display='';
			}
			else {
				document.getElementById('div_param_releve').style.display='none';
			}
		}
	}
	// On donne l'acc�s aux liens de pliage/d�pliage des param�tres du relev� de notes
	if(document.getElementById('pliage_param_releve'))�{document.getElementById('pliage_param_releve').style.display='';}
}
display_param_releve();

// On cache l'acc�s aux liens de pliage/d�pliage des param�tres du relev� de notes
// jusqu'� ce que la case d'insertion des relev�s de notes entre les bulletins ait �t� coch�e (au moins une fois)
if(document.getElementById('pliage_param_releve')) {document.getElementById('pliage_param_releve').style.display='none';}



function display_param_b_adr_pg() {
	if(document.getElementById('div_param_b_adr_pg')) {
		if(document.getElementById('mode_bulletin_html')) {
			if(document.getElementById('mode_bulletin_html').checked==true) {
				document.getElementById('div_param_b_adr_pg').style.display='';
			}
			else {
				document.getElementById('div_param_b_adr_pg').style.display='none';
			}
		}
	}
}
display_param_b_adr_pg();

</script>\n";

	echo "<input type='hidden' name='valide_select_eleves' value='y' />\n";
	echo "<p><input type='submit' name='bouton_valide_select_eleves2' value='Valider' /></p>\n";
	echo "</form>\n";
}
//=======================================================
//==EXTRACTION DES DONNEES PUIS AFFICHAGE DES BULLETINS==
else {
	/*
	echo "<p class='bold'>";
	echo "<a href='".$_SERVER['PHP_SELF']."'>Choisir d'autres classes</a>\n";
	echo " | <a href='#' onClick='history.go(-1);'>Choisir d'autres p�riodes</a>\n";
	echo " | <a href='#' onClick='history.go(-2);'>Choisir d'autres �l�ves</a>\n";
	echo "</p>\n";
	*/

	//debug_var();

	$mode_bulletin=isset($_POST['mode_bulletin']) ? $_POST['mode_bulletin'] : "html";
	$un_seul_bull_par_famille=isset($_POST['un_seul_bull_par_famille']) ? $_POST['un_seul_bull_par_famille'] : "non";
	$coefficients_a_1=isset($_POST['coefficients_a_1']) ? $_POST['coefficients_a_1'] : "non";

	$use_cell_ajustee=isset($_POST['use_cell_ajustee']) ? $_POST['use_cell_ajustee'] : "y";

	$tri_par_etab_orig=isset($_POST['tri_par_etab_orig']) ? $_POST['tri_par_etab_orig'] : "n";

	// 20100615
	$moyennes_periodes_precedentes=isset($_POST['moyennes_periodes_precedentes']) ? $_POST['moyennes_periodes_precedentes'] : "n";

	//$bull_pdf_debug=isset($_POST['bull_pdf_debug']) ? $_POST['bull_pdf_debug'] : "n";

	//========================================
	/*
	echo "<style type='text/css'>
	@media screen{
		#infodiv {
			float: right;
			width: 20em;
			background-color: white;
		}
	}
	@media print{
		#infodiv {
			display:none;
		}
	}
</style>\n";
	*/


	if($mode_bulletin=="html") {
		echo "<div id='infodiv'>
<p id='titre_infodiv' style='font-weight:bold; text-align:center; border:1px solid black;'></p>
<table class='boireaus' width='100%' summary='Tableau de progression'>
<tr>
<th colspan='3' id='td_info'></th>
</tr>
<tr>
<th width='33%'>Classe</th>
<th width='33%'>P�riode</th>
<th>El�ve</th>
</tr>
<tr>
<td id='td_classe'></td>
<td id='td_periode'></td>
<td id='td_ele'></td>
</tr>
</table>
</div>\n";
	}

	//=============================================
	// Faire les extractions pour le relev� de notes si jamais cela a �t� demand�.
	if(isset($intercaler_releve_notes)) {
		include("../cahier_notes/extraction_donnees_releves_notes.php");
	}
	else {
		// On initialise un tableau qui restera vide...
		$tab_releve=array();
	}
	//=============================================


	if($mode_bulletin=="html") {
		echo "<script type='text/javascript'>
	document.getElementById('titre_infodiv').innerHTML='Bulletins';
	document.getElementById('td_info').innerHTML='Pr�paratifs';
	document.getElementById('td_classe').innerHTML='';
	document.getElementById('td_periode').innerHTML='';
	document.getElementById('td_ele').innerHTML='';
</script>\n";
	}

	//========================================


	//========================================
	// RECUPERER LES INFOS ETABLISSEMENT

	//==============================
	$motif="Temoin_1";
	decompte_debug($motif,"Initialisation $motif");
	//==============================

	// Peut-�tre d�placer �a vers un fichier externe d'initialisation de variables
	// Et appeler les variables en 'global' dans les fonctions g�n�rant les bulletins

	/*
	// R�cup�r�s plus haut
	$RneEtablissement=getSettingValue("gepiSchoolRne");
	$gepiSchoolName=getSettingValue("gepiSchoolName");
	$gepiSchoolAdress1=getSettingValue("gepiSchoolAdress1");
	$gepiSchoolAdress2=getSettingValue("gepiSchoolAdress2");
	$gepiSchoolZipCode=getSettingValue("gepiSchoolZipCode");
	$gepiSchoolCity=getSettingValue("gepiSchoolCity");
	$gepiSchoolPays=getSettingValue("gepiSchoolPays");

	$logo_etab=getSettingValue("logo_etab");

	if(!getSettingValue("bull_intitule_app")){
		$bull_intitule_app="Appr�ciations/Conseils";
	}
	else{
		$bull_intitule_app=getSettingValue("bull_intitule_app");
	}

	if(!getSettingValue("bull_affiche_tel")){
		$bull_affiche_tel="n";
	}
	else{
		$bull_affiche_tel=getSettingValue("bull_affiche_tel");
	}

	if(!getSettingValue("bull_affiche_fax")){
		$bull_affiche_fax="n";
	}
	else{
		$bull_affiche_fax=getSettingValue("bull_affiche_fax");
	}

	if($bull_affiche_fax=="y"){
		$gepiSchoolFax=getSettingValue("gepiSchoolFax");
	}

	if($bull_affiche_tel=="y"){
		$gepiSchoolTel=getSettingValue("gepiSchoolTel");
	}

	//========================================

	if(getSettingValue("genre_periode")){
		$genre_periode=getSettingValue("genre_periode");
	}
	else{
		$genre_periode="M";
	}
	*/

	//========================================

	// Absences
	$bull_affiche_absences=getSettingValue("bull_affiche_absences");

	// Prof principal
	$gepi_prof_suivi=getSettingValue("gepi_prof_suivi");

	//=========================================
	// AID
	$bull_affiche_aid=getSettingValue("bull_affiche_aid");

	if ($bull_affiche_aid == 'y') {
		// Initialisations diverses
		unset($call_data_aid_b);
		unset($call_data_aid_e);

		// On pr�pare l'affichage des appr�ciations des Activit�s Interdisciplinaires devant appara�tre en t�te des bulletins :
		if (!isset($call_data_aid_b)){
			$sql="SELECT * FROM aid_config WHERE (order_display1 ='b' and display_bulletin!='n') ORDER BY order_display2;";
			//echo "$sql<br />";
			$call_data_aid_b = mysql_query($sql);
			$nb_aid_b = mysql_num_rows($call_data_aid_b);
			//echo "\$nb_aid_b=$nb_aid_b<br />";
		}

		// On pr�pare l'affichage des appr�ciations des Activit�s Interdisciplinaires devant appara�tre en fin des bulletins :
		if (!isset($call_data_aid_e)){
			$sql="SELECT * FROM aid_config WHERE (order_display1 ='e' and display_bulletin!='n') ORDER BY order_display2;";
			//echo "$sql<br />";
			$call_data_aid_e = mysql_query($sql);
			$nb_aid_e = mysql_num_rows($call_data_aid_e);
			//echo "\$nb_aid_e=$nb_aid_e<br />";
		}
	}
	//=========================================


	// Tableau destin� � stocker toutes les infos
	$tab_bulletin=array();

	//==============================
	$motif="Temoin_1";
	decompte_debug($motif,"$motif avant la boucle classes");
	//==============================

	// Boucle sur les classes
	for($loop_classe=0;$loop_classe<count($tab_id_classe);$loop_classe++) {


		if((isset($_POST['forcer_recalcul_rang']))&&($_POST['forcer_recalcul_rang']=='y')) {
			$sql="SELECT num_periode FROM periodes WHERE id_classe='".$tab_id_classe[$loop_classe]."' ORDER BY num_periode DESC LIMIT 1;";
			$res_per=mysql_query($sql);
			if(mysql_num_rows($res_per)>0) {
				$lig_per=mysql_fetch_object($res_per);
				$recalcul_rang="";
				for($i=0;$i<$lig_per->num_periode;$i++) {$recalcul_rang.="y";}
				$sql="UPDATE groupes SET recalcul_rang='$recalcul_rang' WHERE id in (SELECT id_groupe FROM j_groupes_classes WHERE id_classe='".$tab_id_classe[$loop_classe]."');";
				//echo "$sql<br />";
				$res=mysql_query($sql);
				if(!$res) {
					$msg.="<br />Erreur lors de la programmation du recalcul des rangs pour la classe ".get_nom_classe($tab_id_classe[$loop_classe]).".";
				}
			}
			// Les rangs seront recalcul�s lors de l'appel � calcul_rang.inc.php
		}



		//==============================
		if($mode_bulletin=="html") {
			$motif="Temoin_classe";
			decompte_debug($motif,"$motif classe $loop_classe");
			flush();
			echo "<script type='text/javascript'>
	document.getElementById('td_classe').innerHTML='".get_class_from_id($tab_id_classe[$loop_classe])."';
</script>\n";
		}
		//==============================


		// Remplissage des param�tres du mod�le de bulletin PDF:
		if($mode_bulletin=="pdf") {

			// information d'activation des diff�rentes parties du bulletin
			$tab_modele_pdf["affiche_filigrame"][$tab_id_classe[$loop_classe]]='1'; // affiche un filigramme
			$tab_modele_pdf["texte_filigrame"][$tab_id_classe[$loop_classe]]='DUPLICATA INTERNET'; // texte du filigrame
			$tab_modele_pdf["affiche_logo_etab"][$tab_id_classe[$loop_classe]]='1';
			$tab_modele_pdf["nom_etab_gras"][$tab_id_classe[$loop_classe]]='0';
			$tab_modele_pdf["entente_mel"][$tab_id_classe[$loop_classe]]='1'; // afficher l'adresse mel dans l'ent�te
			$tab_modele_pdf["entente_tel"][$tab_id_classe[$loop_classe]]='1'; // afficher le num�ro de t�l�phone dans l'ent�te
			$tab_modele_pdf["entente_fax"][$tab_id_classe[$loop_classe]]='1'; // afficher le num�ro de fax dans l'ent�te
			$tab_modele_pdf["L_max_logo"][$tab_id_classe[$loop_classe]]=75; $tab_modele_pdf["H_max_logo"][$tab_id_classe[$loop_classe]]=75; //dimension du logo
			$tab_modele_pdf["active_bloc_datation"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les informations de datation du bulletin
			$tab_modele_pdf["taille_texte_date_edition"][$tab_id_classe[$loop_classe]] = '8'; // d�finit la taille de la date d'�dition du bulletin
			$tab_modele_pdf["active_bloc_eleve"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les informations sur l'�l�ve
			$tab_modele_pdf["active_bloc_adresse_parent"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher l'adresse des parents
			$tab_modele_pdf["active_bloc_absence"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les absences de l'�l�ve
			$tab_modele_pdf["active_bloc_note_appreciation"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les notes et appr�ciations
			$tab_modele_pdf["active_bloc_avis_conseil"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les avis du conseil de classe
			$tab_modele_pdf["active_bloc_chef"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher la signature du chef
			$tab_modele_pdf["active_photo"][$tab_id_classe[$loop_classe]] = '0'; // fait - afficher la photo de l'�l�ve
			$tab_modele_pdf["active_coef_moyenne"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher le co�ficient des moyenne par mati�re
			$active_coef_sousmoyene = '1'; // fait - afficher le co�ficient des moyenne par mati�re
			$tab_modele_pdf["active_nombre_note"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher le nombre de note par mati�re sous la moyenne de l'�l�ve
			$tab_modele_pdf["active_nombre_note_case"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher le nombre de note par mati�re
			$tab_modele_pdf["active_moyenne"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les moyennes
			$tab_modele_pdf["active_moyenne_eleve"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher la moyenne de l'�l�ve
			$tab_modele_pdf["active_moyenne_classe"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les moyennes de la classe
			$tab_modele_pdf["active_moyenne_min"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les moyennes minimum
			$tab_modele_pdf["active_moyenne_max"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les moyennes maximum
			$tab_modele_pdf["active_regroupement_cote"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher le nom des regroupement sur le cot�
			$tab_modele_pdf["active_entete_regroupement"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les ent�te des regroupement
			$tab_modele_pdf["active_moyenne_regroupement"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les moyennes des regroupement
			$tab_modele_pdf["active_moyenne_general"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher la moyenne g�n�ral sur le bulletin
			$tab_modele_pdf["active_rang"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher le rang de l'�l�ve
			$tab_modele_pdf["active_graphique_niveau"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher le graphique des niveaux
			$tab_modele_pdf["active_appreciation"][$tab_id_classe[$loop_classe]] = '1'; // fait - afficher les appr�ciations des professeurs

			$tab_modele_pdf["affiche_doublement"][$tab_id_classe[$loop_classe]] = '1'; // affiche si l'�l�ve � doubler
			$tab_modele_pdf["affiche_date_naissance"][$tab_id_classe[$loop_classe]] = '1'; // affiche la date de naissance de l'�l�ve
			$tab_modele_pdf["affiche_lieu_naissance"][$tab_id_classe[$loop_classe]] = '0'; // affiche le lieu de naissance de l'�l�ve
			$tab_modele_pdf["affiche_dp"][$tab_id_classe[$loop_classe]] = '1'; // affiche l'�tat de demi pension ou extern
			$tab_modele_pdf["affiche_nom_court"][$tab_id_classe[$loop_classe]] = '1'; // affiche le nom court de la classe
			$tab_modele_pdf["affiche_effectif_classe"][$tab_id_classe[$loop_classe]] = '1'; // affiche l'effectif de la classe
			$tab_modele_pdf["affiche_numero_impression"][$tab_id_classe[$loop_classe]] = '1'; // affiche le num�ro d'impression des bulletins
			$tab_modele_pdf["affiche_etab_origine"][$tab_id_classe[$loop_classe]] = '0'; // affiche l'�tablissement d'origine

			$tab_modele_pdf["toute_moyenne_meme_col"][$tab_id_classe[$loop_classe]]='0'; // afficher les information moyenne classe/min/max sous la moyenne g�n�ral de l'�l�ve
			$active_coef_sousmoyene = '1'; //afficher le coeficent en dessous de la moyenne de l'�l�ve

			$tab_modele_pdf["entete_model_bulletin"][$tab_id_classe[$loop_classe]] = '1'; //choix du type d'entete des moyennes
			$tab_modele_pdf["ordre_entete_model_bulletin"][$tab_id_classe[$loop_classe]] = '1'; // ordre des ent�tes tableau du bulletin

			// information param�trage
			$tab_modele_pdf["caractere_utilse"][$tab_id_classe[$loop_classe]] = 'Arial';
			// cadre identit�e parents
			$tab_modele_pdf["X_parent"][$tab_id_classe[$loop_classe]]=110; $tab_modele_pdf["Y_parent"][$tab_id_classe[$loop_classe]]=40;
			$tab_modele_pdf["imprime_pour"][$tab_id_classe[$loop_classe]] = 1;
			// cadre identit�e eleve
			$tab_modele_pdf["X_eleve"][$tab_id_classe[$loop_classe]]=5; $tab_modele_pdf["Y_eleve"][$tab_id_classe[$loop_classe]]=40;
			$tab_modele_pdf["cadre_eleve"][$tab_id_classe[$loop_classe]]=1;
			// cadre de datation du bulletin
			$tab_modele_pdf["X_datation_bul"][$tab_id_classe[$loop_classe]]=110; $tab_modele_pdf["Y_datation_bul"][$tab_id_classe[$loop_classe]]=5;
			$tab_modele_pdf["cadre_datation_bul"][$tab_id_classe[$loop_classe]]=1;
			// si les cat�gorie son affich� avec moyenne
			$tab_modele_pdf["hauteur_info_categorie"][$tab_id_classe[$loop_classe]]=5;
			// cadre des notes et app
			$tab_modele_pdf["X_note_app"][$tab_id_classe[$loop_classe]]=5;
			$tab_modele_pdf["Y_note_app"][$tab_id_classe[$loop_classe]]=72;
			$tab_modele_pdf["longeur_note_app"][$tab_id_classe[$loop_classe]]=200;
			$tab_modele_pdf["hauteur_note_app"][$tab_id_classe[$loop_classe]]=175;
			/*
			if($tab_modele_pdf["active_regroupement_cote"][$tab_id_classe[$loop_classe]]==='1') {
				$tab_modele_pdf["X_note_app"][$tab_id_classe[$loop_classe]]=$tab_modele_pdf["X_note_app"][$tab_id_classe[$loop_classe]]+5;
				$tab_modele_pdf["Y_note_app"][$tab_id_classe[$loop_classe]]=$tab_modele_pdf["Y_note_app"][$tab_id_classe[$loop_classe]];
				$tab_modele_pdf["longeur_note_app"][$tab_id_classe[$loop_classe]]=$tab_modele_pdf["longeur_note_app"][$tab_id_classe[$loop_classe]]-5;
				$tab_modele_pdf["hauteur_note_app"][$tab_id_classe[$loop_classe]]=$tab_modele_pdf["hauteur_note_app"][$tab_id_classe[$loop_classe]];
			}
			*/
			//coef des matiere
			$tab_modele_pdf["largeur_coef_moyenne"][$tab_id_classe[$loop_classe]] = 8;
			//nombre de note par mati�re
			$tab_modele_pdf["largeur_nombre_note"][$tab_id_classe[$loop_classe]] = 8;
			//champ des moyennes
			$tab_modele_pdf["largeur_d_une_moyenne"][$tab_id_classe[$loop_classe]] = 10;
			//graphique de niveau
			$tab_modele_pdf["largeur_niveau"][$tab_id_classe[$loop_classe]] = 18;
			//rang de l'�l�ve
			$tab_modele_pdf["largeur_rang"][$tab_id_classe[$loop_classe]] = 8;
			//autres infos
			$tab_modele_pdf["active_reperage_eleve"][$tab_id_classe[$loop_classe]] = '1';
			$tab_modele_pdf["couleur_reperage_eleve1"][$tab_id_classe[$loop_classe]] = '255';
			$tab_modele_pdf["couleur_reperage_eleve2"][$tab_id_classe[$loop_classe]] = '255';
			$tab_modele_pdf["couleur_reperage_eleve3"][$tab_id_classe[$loop_classe]] = '207';
			$tab_modele_pdf["couleur_categorie_cote"][$tab_id_classe[$loop_classe]] = '1';
			$tab_modele_pdf["couleur_categorie_cote1"][$tab_id_classe[$loop_classe]]='239';
			$tab_modele_pdf["couleur_categorie_cote2"][$tab_id_classe[$loop_classe]]='239';
			$tab_modele_pdf["couleur_categorie_cote3"][$tab_id_classe[$loop_classe]]='239';
			$tab_modele_pdf["couleur_categorie_entete"][$tab_id_classe[$loop_classe]] = '1';
			$tab_modele_pdf["couleur_categorie_entete1"][$tab_id_classe[$loop_classe]]='239';
			$tab_modele_pdf["couleur_categorie_entete2"][$tab_id_classe[$loop_classe]]='239';
			$tab_modele_pdf["couleur_categorie_entete3"][$tab_id_classe[$loop_classe]]='239';
			$tab_modele_pdf["couleur_moy_general"][$tab_id_classe[$loop_classe]] = '1';
			$tab_modele_pdf["couleur_moy_general1"][$tab_id_classe[$loop_classe]]='239';
			$tab_modele_pdf["couleur_moy_general2"][$tab_id_classe[$loop_classe]]='239';
			$tab_modele_pdf["couleur_moy_general3"][$tab_id_classe[$loop_classe]]='239';
			$tab_modele_pdf["titre_entete_matiere"][$tab_id_classe[$loop_classe]]='Mati�re';
			$active_coef_sousmoyene = '1'; $tab_modele_pdf["titre_entete_coef"][$tab_id_classe[$loop_classe]]='coef.';
			$tab_modele_pdf["titre_entete_nbnote"][$tab_id_classe[$loop_classe]]='nb. n.';
			$tab_modele_pdf["titre_entete_rang"][$tab_id_classe[$loop_classe]]='rang';
			$titre_entete_appreciation='Appr�ciation/Conseils';
			// cadre absence
			$tab_modele_pdf["X_absence"][$tab_id_classe[$loop_classe]]=5; $tab_modele_pdf["Y_absence"][$tab_id_classe[$loop_classe]]=246.3;
			// entete du bas contient les moyennes g�rn�ral
			$tab_modele_pdf["hauteur_entete_moyenne_general"][$tab_id_classe[$loop_classe]] = 5;
			// cadre des Avis du conseil de classe
			$tab_modele_pdf["X_avis_cons"][$tab_id_classe[$loop_classe]]=5; $tab_modele_pdf["Y_avis_cons"][$tab_id_classe[$loop_classe]]=250; $tab_modele_pdf["longeur_avis_cons"][$tab_id_classe[$loop_classe]]=130; $tab_modele_pdf["hauteur_avis_cons"][$tab_id_classe[$loop_classe]]=37;
			$tab_modele_pdf["cadre_avis_cons"][$tab_id_classe[$loop_classe]]=1;
			// cadre signature du chef
			$tab_modele_pdf["X_sign_chef"][$tab_id_classe[$loop_classe]]=138; $tab_modele_pdf["Y_sign_chef"][$tab_id_classe[$loop_classe]]=250; $tab_modele_pdf["longeur_sign_chef"][$tab_id_classe[$loop_classe]]=67; $tab_modele_pdf["hauteur_sign_chef"][$tab_id_classe[$loop_classe]]=37;
			$tab_modele_pdf["cadre_sign_chef"][$tab_id_classe[$loop_classe]]=0;
			//les moyennes
			$tab_modele_pdf["arrondie_choix"][$tab_id_classe[$loop_classe]]='0.01'; //arrondie de la moyenne
			$tab_modele_pdf["nb_chiffre_virgule"][$tab_id_classe[$loop_classe]]='1'; //nombre de chiffre apr�s la virgule
			$tab_modele_pdf["chiffre_avec_zero"][$tab_id_classe[$loop_classe]]='1'; // si une moyenne se termine par ,00 alors on supprimer les zero

			$tab_modele_pdf["autorise_sous_matiere"][$tab_id_classe[$loop_classe]] = '1'; //autorise l'affichage des sous mati�re
			$tab_modele_pdf["affichage_haut_responsable"][$tab_id_classe[$loop_classe]] = '1'; //affiche le nom du haut responsable de la classe

			$tab_modele_pdf["largeur_matiere"][$tab_id_classe[$loop_classe]] = '40'; // largeur de la colonne matiere

			$tab_modele_pdf["taille_texte_matiere"][$tab_id_classe[$loop_classe]] = '10'; //taille du texte des mati�res

			$tab_modele_pdf["titre_bloc_avis_conseil"][$tab_id_classe[$loop_classe]] = 'Avis du Conseil de classe:'; // titre du bloc avis du conseil de classe
			$tab_modele_pdf["taille_titre_bloc_avis_conseil"][$tab_id_classe[$loop_classe]] = '10'; // taille du titre du bloc avis du conseil
			$tab_modele_pdf["taille_profprincipal_bloc_avis_conseil"][$tab_id_classe[$loop_classe]] = '10'; // taille du texte prof principal du bloc avis conseil de classe
			$tab_modele_pdf["affiche_fonction_chef"][$tab_id_classe[$loop_classe]] = '1'; // affiche la fonction du chef
			$tab_modele_pdf["taille_texte_fonction_chef"][$tab_id_classe[$loop_classe]] = '10'; // taille du texte de la fonction du chef
			$tab_modele_pdf["taille_texte_identitee_chef"][$tab_id_classe[$loop_classe]] = '10'; // taille du texte du nom du chef

			$tab_modele_pdf["cadre_adresse"][$tab_id_classe[$loop_classe]] = ''; // cadre sur l'adresse

			$tab_modele_pdf["centrage_logo"][$tab_id_classe[$loop_classe]] = '0'; // centrer le logo de l'�tablissement
			$tab_modele_pdf["Y_centre_logo"][$tab_id_classe[$loop_classe]] = '18'; // centre du logo sur la page
			$tab_modele_pdf["ajout_cadre_blanc_photo"][$tab_id_classe[$loop_classe]] = '0'; // ajouter un cadre blanc pour la photo de l'�l�ve.

			$tab_modele_pdf["affiche_moyenne_mini_general"][$tab_id_classe[$loop_classe]] = '1'; // permet l'affichage de la moyenne g�n�ral mini
			$tab_modele_pdf["affiche_moyenne_maxi_general"][$tab_id_classe[$loop_classe]] = '1'; // permet l'affichage de la moyenne g�n�ral maxi

			$tab_modele_pdf["affiche_date_edition"][$tab_id_classe[$loop_classe]] = '1'; // affiche la date d'�dition
			$tab_modele_pdf["affiche_ine"][$tab_id_classe[$loop_classe]] = '0'; // affiche l'INE de l'�l�ve

			$tab_modele_pdf["affiche_moyenne_general_coef_1"][$tab_id_classe[$loop_classe]] = '0'; // affichage des moyennes g�n�rales avec coef 1 en plus des autres coeff saisis dans Gestion des classes/<Classe> Enseignements
			
			$tab_modele_pdf["affiche_numero_responsable"][$tab_id_classe[$loop_classe]] = '0'; // affichage du num�ro du responsable legal de l'�l�ve dont le bulletin est imprim�. 1 ==> affiche 0 ==> n'affiche pas
			

			//================================
			//================================
			//================================

			// Mod�le de bulletin PDF
			$type_bulletin=isset($_POST['type_bulletin']) ? $_POST['type_bulletin'] : 1;
			// CONTROLER SI type_bulletin EST BIEN UN ENTIER �ventuellement -1
			if(isset($type_bulletin)) {

				$option_modele_bulletin=getSettingValue("option_modele_bulletin");
				if($option_modele_bulletin==1) {$type_bulletin=-1;}

				//echo "\$type_bulletin=$type_bulletin<br />";
				if ($type_bulletin == -1) {
					// cas mod�le par classe
					$sql="SELECT modele_bulletin_pdf FROM classes WHERE id='".$tab_id_classe[$loop_classe]."';";
					//echo "$sql<br />";
					$res_model=mysql_query($sql);
					if(mysql_num_rows($res_model)==0) {
						$sql="SELECT * FROM modele_bulletin WHERE id_model_bulletin='1';";
					}
					else {
						$lig_mb=mysql_fetch_object($res_model);

						//echo "\$lig_mb->modele_bulletin_pdf=$lig_mb->modele_bulletin_pdf<br />";
						if(($lig_mb->modele_bulletin_pdf=='NULL')||($lig_mb->modele_bulletin_pdf=='')) {
							$sql="SELECT * FROM modele_bulletin WHERE id_model_bulletin='1';";
						}
						else {
							$sql="SELECT * FROM modele_bulletin WHERE id_model_bulletin='".$lig_mb->modele_bulletin_pdf."';";
						}
					}
				} else {
					$sql="SELECT * FROM modele_bulletin WHERE id_model_bulletin='".$type_bulletin."';";
				}
				//echo "$sql<br />";
			}

			//$type_bulletin=3;
			//$sql='SELECT * FROM modele_bulletin WHERE id_model_bulletin="'.$type_bulletin.'"';
			//echo "$sql<br />";
			$requete_model = mysql_query($sql);
			if(mysql_num_rows($requete_model)>0) {
				$cpt=0;
				while($lig_model=mysql_fetch_object($requete_model)) {
					$tab_modele_pdf["$lig_model->nom"][$tab_id_classe[$loop_classe]]=$lig_model->valeur;
				}
			}

			// Il faudrait appliquer d'autres correctifs:
			//echo "\$tab_modele_pdf[\"largeur_nombre_note\"][$tab_id_classe[$loop_classe]]='".$tab_modele_pdf["largeur_nombre_note"][$tab_id_classe[$loop_classe]]."'<br />";
			if($tab_modele_pdf["largeur_nombre_note"][$tab_id_classe[$loop_classe]]=="0") {$tab_modele_pdf["largeur_nombre_note"][$tab_id_classe[$loop_classe]] = 8;}

			if($tab_modele_pdf["active_regroupement_cote"][$tab_id_classe[$loop_classe]]==='1') {
				$tab_modele_pdf["X_note_app"][$tab_id_classe[$loop_classe]]=$tab_modele_pdf["X_note_app"][$tab_id_classe[$loop_classe]]+5;
				$tab_modele_pdf["Y_note_app"][$tab_id_classe[$loop_classe]]=$tab_modele_pdf["Y_note_app"][$tab_id_classe[$loop_classe]];
				$tab_modele_pdf["longeur_note_app"][$tab_id_classe[$loop_classe]]=$tab_modele_pdf["longeur_note_app"][$tab_id_classe[$loop_classe]]-5;
				$tab_modele_pdf["hauteur_note_app"][$tab_id_classe[$loop_classe]]=$tab_modele_pdf["hauteur_note_app"][$tab_id_classe[$loop_classe]];
			}

			//================================
			//================================
			//================================
		}


		//$id_classe=2;
		$id_classe=$tab_id_classe[$loop_classe];
		// Est-ce bien un entier?
		if((strlen(preg_replace("/[0-9]/","",$id_classe)))||($id_classe=="")) {
			echo "<p>Identifiant de classe erron�: <span style='color:red'>$id_classe</span></p>\n";
			require("../lib/footer.inc.php");
			die();
		}


		// ++++++++++++++++++++++++++++++++++++++
		// ++++++++++++++++++++++++++++++++++++++
		// AJOUTER UN TEST: Le visiteur a-t-il le droit d'acc�der � cette page pour cette classe
		if ($_SESSION["statut"] == "scolarite") {
			$sql="SELECT 1=1 FROM classes c, j_scol_classes jsc, j_eleves_classes jec WHERE (jec.id_classe=c.id AND jsc.id_classe=c.id AND jsc.login='".$_SESSION['login']."' AND c.id='$id_classe');";
		}
		//elseif ($_SESSION["statut"] == "administrateur") {
		elseif (($_SESSION["statut"] == "administrateur")||($_SESSION["statut"] == "secours")) {
			// On selectionne toutes les classes
			//$sql="SELECT DISTINCT c.* FROM classes c WHERE 1";
			$sql="SELECT 1=1 FROM j_eleves_classes jec, classes c WHERE (c.id=jec.id_classe) LIMIT 1;";
		}
		elseif ($_SESSION["statut"] == "professeur") {
			$sql="SELECT DISTINCT c.* FROM classes c, j_eleves_professeurs jep, j_eleves_classes jec WHERE (jep.professeur='".$_SESSION['login']."' AND jep.login = jec.login AND jec.id_classe = c.id AND c.id='$id_classe');";
		}
		else {
			// On ne devrait pas arriver jusque-l�...
			echo "<p>Droits insuffisants pour effectuer cette op�ration</p>\n";
			require("../lib/footer.inc.php");
			die();
		}

		$test_acces_classe=mysql_query($sql);
		if(mysql_num_rows($test_acces_classe)==0) {
            tentative_intrusion(2, "Tentative d'un ".$_SESSION["statut"]." (".$_SESSION["login"].") � une classe (".get_class_from_id($id_classe).") sans y �tre autoris�.");
            echo "<p>Vous n'�tes pas autoris�s � �tre ici.</p>\n";
			require("../lib/footer.inc.php");
			die();
		}
		// ++++++++++++++++++++++++++++++++++++++
		// ++++++++++++++++++++++++++++++++++++++


		// Tableau destin� � stocker toutes les infos
		$tab_bulletin[$id_classe]=array();
		if(!isset($intercaler_releve_notes)) {
			// On initialise un tableau qui va rester vide
			$tab_releve[$id_classe]=array();
		}

		$affiche_adresse = sql_query1("SELECT display_address FROM classes WHERE id='".$id_classe."'");
		//echo "\$affiche_adresse=$affiche_adresse<br />";

		//===========================================
		$b_adr_pg=isset($_POST['b_adr_pg']) ? $_POST['b_adr_pg'] : 'xx';
		if($b_adr_pg=='nn') {
			$affiche_adresse="n";
			$page_garde_imprime="n";
		}
		elseif($b_adr_pg=='yn') {
			$affiche_adresse="y";
			$page_garde_imprime="n";
		}
		elseif($b_adr_pg=='ny') {
			$affiche_adresse="n";
			$page_garde_imprime="yes";
		}
		elseif($b_adr_pg=='yy') {
			$affiche_adresse="y";
			$page_garde_imprime="yes";
		}
		$affiche_page_garde=$page_garde_imprime;
		$_SESSION['b_adr_pg']=$b_adr_pg;
		//===========================================

		$sql="SELECT 1=1 FROM j_eleves_classes WHERE id_classe='$id_classe' GROUP BY login;";
		$res_eff_total_classe=mysql_query($sql);
		$eff_total_classe=mysql_num_rows($res_eff_total_classe);


		// Boucle sur les p�riodes
		for($loop_periode_num=0;$loop_periode_num<count($tab_periode_num);$loop_periode_num++) {


			if((isset($_POST['forcer_recalcul_moy_conteneurs']))&&($_POST['forcer_recalcul_moy_conteneurs']=='y')) {
					$sql="SELECT DISTINCT ccn.id_cahier_notes,ccn.id_groupe FROM cn_cahier_notes ccn,groupes g,j_groupes_classes jgc,classes c WHERE
						ccn.id_groupe=g.id AND
						jgc.id_groupe=g.id AND
						c.id=jgc.id_classe AND
						ccn.periode='".$tab_periode_num[$loop_periode_num]."' AND
						c.id='".$id_classe."'
						ORDER BY c.classe,g.description";
					//echo "$sql";
					$res_recalcul_moy_conteneurs=mysql_query($sql);
					if(mysql_num_rows($res_recalcul_moy_conteneurs)>0){
						while($lig_recalcul_moy_conteneurs=mysql_fetch_object($res_recalcul_moy_conteneurs)) {
							$current_group=get_group($lig_recalcul_moy_conteneurs->id_groupe);
							$periode_num=$tab_periode_num[$loop_periode_num];
							$id_racine=$lig_recalcul_moy_conteneurs->id_cahier_notes;
							recherche_enfant($lig_recalcul_moy_conteneurs->id_cahier_notes);
						}
						unset($current_group);
						unset($periode_num);
						unset($id_racine);
					}
			}
	

			//$periode_num=1;
			$periode_num=$tab_periode_num[$loop_periode_num];

			// Est-ce bien un entier?
			if((strlen(preg_replace("/[0-9]/","",$periode_num)))||($periode_num=="")) {
				echo "<p>Identifiant de p�riode erron�: <span style='color:red'>".$periode_num."</span></p>\n";
				require("../lib/footer.inc.php");
				die();
			}

			//==============================
			if($mode_bulletin=="html") {
				$motif="Temoin_periode";
				decompte_debug($motif,"$motif classe $loop_classe p�riode $periode_num");
				flush();
				echo "<script type='text/javascript'>
	document.getElementById('td_periode').innerHTML='".$periode_num."';
</script>\n";
			}
			//==============================

			//============================
			// On vide les variables de la boucle pr�c�dente avant le calcul dans calcul_moy_gen.inc.php
			unset($moy_gen_eleve);
			unset($moy_gen_classe);
			unset($moy_generale_classe);
			unset($moy_max_classe);
			unset($moy_min_classe);

			unset($moy_cat_classe);
			unset($moy_cat_eleve);

			unset($quartile1_classe_gen);
			unset($quartile2_classe_gen);
			unset($quartile3_classe_gen);
			unset($quartile4_classe_gen);
			unset($quartile5_classe_gen);
			unset($quartile6_classe_gen);
			unset($place_eleve_classe);

			unset($current_eleve_login);
			unset($current_group);
			unset($current_eleve_note);
			unset($current_eleve_statut);
			unset($current_coef);
			unset($categories);
			unset($current_classe_matiere_moyenne);

			unset($current_coef_eleve);
			unset($moy_min_classe_grp);
			unset($moy_max_classe_grp);
			unset($current_eleve_rang);

			unset($current_group_effectif_avec_note);

			unset($current_eleve_app);
			//============================


			// Tableau destin� � stocker toutes les infos
			$tab_bulletin[$id_classe][$periode_num]=array();
			if(!isset($intercaler_releve_notes)) {
				// On initialise un tableau qui va rester vide
				$tab_releve[$id_classe][$periode_num]=array();
			}


			$tab_bulletin[$id_classe][$periode_num]['affiche_adresse']=$affiche_adresse;
			//echo "\$tab_bulletin[$id_classe][$periode_num]['affiche_adresse']=".$tab_bulletin[$id_classe][$periode_num]['affiche_adresse']."<br />";

			// Informations sur la p�riode
			$sql="SELECT * FROM periodes WHERE id_classe='$id_classe' AND num_periode='$periode_num';";
			$res_per=mysql_query($sql);
			if(mysql_num_rows($res_per)>0) {
				$lig_per=mysql_fetch_object($res_per);
				$tab_bulletin[$id_classe][$periode_num]['num_periode']=$lig_per->num_periode;
				//$tab_bulletin[$id_classe][$periode_num]['nom_periode']=$lig_per->nom_periode;
				$tab_bulletin[$id_classe][$periode_num]['nom_periode']=preg_replace("/&#039;/","'",$lig_per->nom_periode);
				$tab_bulletin[$id_classe][$periode_num]['verouiller']=$lig_per->verouiller;
			}

			// Liste des �l�ves � �diter/afficher/imprimer (s�lection):
			// tab_ele_".$i."_".$j.
			//$tab_bulletin[$id_classe][$periode_num]['selection_eleves']=array();
			$tab_selection_eleves=isset($_POST['tab_selection_ele_'.$loop_classe.'_'.$loop_periode_num]) ? $_POST['tab_selection_ele_'.$loop_classe.'_'.$loop_periode_num] : array();
			$tab_bulletin[$id_classe][$periode_num]['selection_eleves']=$tab_selection_eleves;


			$affiche_rang = sql_query1("SELECT display_rang FROM classes WHERE id='".$id_classe."'");
			if($mode_bulletin=="pdf") {
				if($tab_modele_pdf["active_rang"][$id_classe]==1) {
					$affiche_rang="y";
				}
				else {
					$affiche_rang="n";
				}
			}


			$affiche_nbdev=sql_query1("SELECT display_nbdev FROM classes WHERE id='".$id_classe."'");
			if($mode_bulletin=="pdf") {
				if(($tab_modele_pdf["active_nombre_note"][$id_classe]==1)||($tab_modele_pdf["active_nombre_note_case"][$id_classe]==1)) {
					$affiche_nbdev="y";
				}
				else {
					$affiche_nbdev="n";
				}
			}



			// On teste si on affiche les graphiques
			if (getSettingValue("bull_affiche_graphiques") == 'yes'){$affiche_graph = 'y';}else{$affiche_graph = 'n';}

			//========================================
			// Afficher la moyenne g�n�rale? (�galement conditionn� par la pr�sence d'un coef non nul au moins)
			$display_moy_gen = sql_query1("SELECT display_moy_gen FROM classes WHERE id='".$id_classe."'");
			//========================================


			//========================================
			// On teste la pr�sence d'au moins un coeff pour afficher la colonne des coef
			$test_coef = mysql_num_rows(mysql_query("SELECT coef FROM j_groupes_classes WHERE (id_classe='".$id_classe."' and coef > 0)"));
			//echo "\$test_coef=$test_coef<br />\n";
			//Afficher les coefficients des mati�res (uniquement si au moins un coef diff�rent de 0)
			if($test_coef>0){
				$affiche_coef = sql_query1("SELECT display_coef FROM classes WHERE id='".$id_classe."'");
			}
			else{
				$affiche_coef = "n";
			}
			//========================================


			//========================================
			$affiche_categories = sql_query1("SELECT display_mat_cat FROM classes WHERE id='".$id_classe."'");
			if ($affiche_categories == "y") {
				$affiche_categories = true;
			} else {
				$affiche_categories = false;
			}

			if($mode_bulletin=="pdf") {
				if(($tab_modele_pdf["active_entete_regroupement"][$id_classe]==1)||($tab_modele_pdf["active_regroupement_cote"][$id_classe]==1)) {
					$affiche_categories = true;
				}
				else {
					$affiche_categories = false;
				}
			}

			// V�rifier si il n'y a pas de b�tise sur les cat�gories.
			if($affiche_categories) {
				$sql="SELECT DISTINCT jmcc.priority ".
				"FROM j_groupes_classes jgc, j_groupes_matieres jgm, j_matieres_categories_classes jmcc, matieres m " .
				"WHERE ( " .
				"jgc.categorie_id = jmcc.categorie_id AND " .
				"jgc.id_classe=jmcc.classe_id AND " .
				"jgc.id_classe='".$id_classe."' AND " .
				"jgm.id_groupe=jgc.id_groupe AND " .
				"m.matiere = jgm.id_matiere" .
				");";
				$res_nb_cat_priorites=mysql_query($sql);
				$nb_cat_priorites=mysql_num_rows($res_nb_cat_priorites);
				//echo "\$nb_cat_priorites=$nb_cat_priorites<br />\n";

				$sql="SELECT DISTINCT jgc.categorie_id ".
				"FROM j_groupes_classes jgc, j_groupes_matieres jgm, j_matieres_categories_classes jmcc, matieres m " .
				"WHERE ( " .
				"jgc.categorie_id = jmcc.categorie_id AND " .
				"jgc.id_classe=jmcc.classe_id AND " .
				"jgc.id_classe='".$id_classe."' AND " .
				"jgm.id_groupe=jgc.id_groupe AND " .
				"m.matiere = jgm.id_matiere" .
				");";
				$res_nb_cat=mysql_query($sql);
				$nb_cat=mysql_num_rows($res_nb_cat);
				//echo "\$nb_cat=$nb_cat<br />\n";

				if($nb_cat_priorites!=$nb_cat) {
					// Tester si les cat�gories de mati�res ont bien des priorit�s diff�rentes au niveau Gestion des mati�res
					// Si ce n'est pas le cas, produire une alerte et sortir

					$sql="SELECT DISTINCT mc.priority ".
					"FROM j_groupes_classes jgc, j_groupes_matieres jgm, j_matieres_categories_classes jmcc, matieres m, matieres_categories mc " .
					"WHERE ( " .
					"mc.id=jmcc.categorie_id AND ".
					"jgc.categorie_id = jmcc.categorie_id AND " .
					"jgc.id_classe=jmcc.classe_id AND " .
					"jgc.id_classe='".$id_classe."' AND " .
					"jgm.id_groupe=jgc.id_groupe AND " .
					"m.matiere = jgm.id_matiere" .
					");";
					$res_nb_cat_priorites_glob=mysql_query($sql);
					$nb_cat_priorites_glob=mysql_num_rows($res_nb_cat_priorites_glob);
					//echo "\$nb_cat_priorites=$nb_cat_priorites<br />\n";

					if($nb_cat_priorites_glob!=$nb_cat) {
						if($mode_bulletin!="pdf") {
							echo "<h1 align='center'>Erreur</h1>";
							echo "<p>Vous avez demand� � afficher les cat�gories de mati�res, mais les priorit�s d'affichage des cat�gories ne sont pas correctement d�finies, ni au niveau global dans Gestion des mati�res, ni au niveau particulier dans Gestion des classes/&lt;Classe&gt; Param�tres<br />Il ne faut pas que deux cat�gories aient la m�me priorit� sans quoi il peut survenir des anomalies d'ordre des mati�res sur le bulletin.</p>\n";
							require("../lib/footer.inc.php");
							die();
						}
						else {

							$pdf=new bul_PDF('p', 'mm', 'A4');
							$pdf->SetCreator($gepiSchoolName);
							$pdf->SetAuthor($gepiSchoolName);
							$pdf->SetKeywords('');
							$pdf->SetSubject('Bulletin');
							$pdf->SetTitle('Bulletin');
							$pdf->SetDisplayMode('fullwidth', 'single');
							$pdf->SetCompression(TRUE);
							$pdf->SetAutoPageBreak(TRUE, 5);

							$pdf->AddPage(); //ajout d'une page au document
							$pdf->SetFont('Arial');
							$pdf->SetXY(20,20);
							$pdf->SetFontSize(14);
							$pdf->Cell(90,7, "ERREUR",0,2,'');

							$pdf->SetXY(20,40);
							$pdf->SetFontSize(10);
							$pdf->Cell(150,7, "Vous avez demand� � afficher les cat�gories de mati�res,",0,2,'');
							$pdf->SetXY(20,45);
							$pdf->Cell(150,7, "mais les priorit�s d'affichage des cat�gories ne sont pas correctement d�finies,",0,2,'');
							$pdf->SetXY(20,50);
							$pdf->Cell(150,7, "ni au niveau global dans Gestion des mati�res,",0,2,'');
							$pdf->SetXY(20,55);
							$pdf->Cell(150,7, "ni au niveau particulier dans Gestion des classes/<Classe> Enseignements",0,2,'');
							$pdf->SetXY(20,65);
							$pdf->Cell(150,7, "Il ne faut pas que deux cat�gories aient la m�me priorit�",0,2,'');
							$pdf->SetXY(20,70);
							$pdf->Cell(150,7, "sans quoi il peut survenir des anomalies d'ordre des mati�res sur le bulletin.",0,2,'');

							$nom_bulletin = 'Erreur_bulletin.pdf';
							$pdf->Output($nom_bulletin,'I');
							die();
						}
					}
				}
			}

			//========================================


			if ($affiche_rang == 'y'){
				// On teste la pr�sence d'au moins un coeff pour afficher la colonne des coef
				$test_coef = mysql_num_rows(mysql_query("SELECT coef FROM j_groupes_classes WHERE (id_classe='".$id_classe."' and coef > 0)"));
				include("../lib/calcul_rang.inc.php");
			}


			$tab_bulletin[$id_classe][$periode_num]['test_coef']=$test_coef;
			if($coefficients_a_1=="oui") {
				// On force la valeur de test_coef si on impose des coef 1 pour le calcul des moyennes g�n�rales dans /lib/calcul_moy_gen.inc.php
				$tab_bulletin[$id_classe][$periode_num]['test_coef']=1;
			}


			// Informations sur la classe
			$sql="SELECT * FROM classes WHERE id='".$id_classe."';";
			$res_classe=mysql_query($sql);
			$lig_classe=mysql_fetch_object($res_classe);

			$tab_bulletin[$id_classe][$periode_num]['id_classe']=$lig_classe->id;
			$tab_bulletin[$id_classe][$periode_num]['classe']=$lig_classe->classe;
			$tab_bulletin[$id_classe][$periode_num]['classe_nom_complet']=$lig_classe->nom_complet;
			$tab_bulletin[$id_classe][$periode_num]['formule']=$lig_classe->formule;
			$tab_bulletin[$id_classe][$periode_num]['suivi_par']=$lig_classe->suivi_par;

			$classe=$lig_classe->classe;
			$classe_nom_complet=$lig_classe->nom_complet;



			// R�cup�rer l'effectif de la classe,...
			$sql="SELECT 1=1 FROM j_eleves_classes WHERE id_classe='$id_classe' AND periode='$periode_num';";
			$res_eff_classe=mysql_query($sql);
			//$lig_eff_classe=mysql_fetch_object($res_eff_classe);
			$eff_classe=mysql_num_rows($res_eff_classe);
			//echo "<p>Effectif de la classe: $eff_classe</p>\n";

			if($eff_classe==0) {
				echo "<p>La classe '$classe' est vide sur la p�riode '$periode_num'.<br />Il n'est pas possible de poursuivre.</p>\n";
				require("../lib/footer.inc.php");
				die();
			}

			//==============================
			if($mode_bulletin=="html") {
				$motif="Temoin_calcul_moy_gen".$id_classe."_".$periode_num;
				decompte_debug($motif,"$motif avant");
				flush();
			}
			//==============================

			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			// 20100615
			//$moyennes_periodes_precedentes="y";
			if((isset($moyennes_periodes_precedentes))&&($periode_num>1)&&(!isset($tab_bulletin[$id_classe][$periode_num]['note_prec']))) {
				$reserve_periode_num=$periode_num;
				for($periode_num=1;$periode_num<$reserve_periode_num;$periode_num++) {
					//echo "\$periode_num=$periode_num<br />";
					include("../lib/calcul_moy_gen.inc.php");

					$tab_bulletin[$id_classe][$reserve_periode_num]['login_prec'][$periode_num]=$current_eleve_login;
					$tab_bulletin[$id_classe][$reserve_periode_num]['group_prec'][$periode_num]=$current_group;
					$tab_bulletin[$id_classe][$reserve_periode_num]['note_prec'][$periode_num]=$current_eleve_note;
					$tab_bulletin[$id_classe][$reserve_periode_num]['statut_prec'][$periode_num]=$current_eleve_statut;
					$tab_bulletin[$id_classe][$reserve_periode_num]['moy_gen_eleve_prec'][$periode_num]=$moy_gen_eleve;

					//============================
					// On vide les variables de la boucle avant le calcul dans calcul_moy_gen.inc.php hors du dispositif p�riodes pr�c�dentes
					unset($moy_gen_eleve);
					unset($moy_gen_classe);
					unset($moy_generale_classe);
					unset($moy_max_classe);
					unset($moy_min_classe);
		
					unset($moy_cat_classe);
					unset($moy_cat_eleve);
		
					unset($quartile1_classe_gen);
					unset($quartile2_classe_gen);
					unset($quartile3_classe_gen);
					unset($quartile4_classe_gen);
					unset($quartile5_classe_gen);
					unset($quartile6_classe_gen);
					unset($place_eleve_classe);
		
					unset($current_eleve_login);
					unset($current_group);
					unset($current_eleve_note);
					unset($current_eleve_statut);
					unset($current_coef);
					unset($categories);
					unset($current_classe_matiere_moyenne);
		
					unset($current_coef_eleve);
					unset($moy_min_classe_grp);
					unset($moy_max_classe_grp);
					unset($current_eleve_rang);
		
					unset($current_group_effectif_avec_note);
		
					unset($current_eleve_app);
					//============================
				}
				$periode_num=$reserve_periode_num;
			}
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


			//========================================
			//if($eff_classe>0) {
				include("../lib/calcul_moy_gen.inc.php");
			//}
			// On r�cup�re la plus grande partie des infos via calcul_moy_gen.inc.php
			// Voir en fin du fichier calcul_moy_gen.inc.php la liste des infos r�cup�r�es
			//========================================
			//==============================
			if($mode_bulletin=="html") {
				$motif="Temoin_calcul_moy_gen".$id_classe."_".$periode_num;
				decompte_debug($motif,"$motif apr�s");
				flush();
			}
			//==============================

			//echo "\$affiche_categories=$affiche_categories<br />";
			// $affiche_categories=1

			/*
			$classe=get_class_from_id($id_classe);

			$tab_bulletin[$id_classe][$periode_num]['classe']=$classe;
			$tab_bulletin[$id_classe][$periode_num]['id_classe']=$id_classe;

			// Informations sur la p�riode
			$sql="SELECT nom_periode FROM periodes WHERE id_classe='$id_classe' AND num_periode='$periode_num';";
			$res_per=mysql_query($sql);
			$lig_per=mysql_fetch_object($res_per);

			$tab_bulletin[$id_classe][$periode_num]['nom_periode']=$lig_per->nom_periode;
			*/

			/*
			// R�cup�rer l'effectif de la classe,...
			$sql="SELECT 1=1 FROM j_eleves_classes WHERE id_classe='$id_classe' AND periode='$periode_num';";
			$res_eff_classe=mysql_query($sql);
			//$lig_eff_classe=mysql_fetch_object($res_eff_classe);
			$eff_classe=mysql_num_rows($res_eff_classe);
			//echo "<p>Effectif de la classe: $eff_classe</p>\n";
			*/

			// Variables simples
			$tab_bulletin[$id_classe][$periode_num]['eff_classe']=$eff_classe;

			// Effectif total sur l'ann�e pour pouvoir parcourir tout $tab_rel pour intercaler bulletin/relev�
			$tab_bulletin[$id_classe][$periode_num]['eff_total_classe']=$eff_total_classe;

			// Variables simples
			$tab_bulletin[$id_classe][$periode_num]['affiche_categories']=$affiche_categories;
			$tab_bulletin[$id_classe][$periode_num]['affiche_coef']=$affiche_coef;
			$tab_bulletin[$id_classe][$periode_num]['affiche_rang']=$affiche_rang;
			$tab_bulletin[$id_classe][$periode_num]['affiche_graph']=$affiche_graph;
			$tab_bulletin[$id_classe][$periode_num]['affiche_nbdev']=$affiche_nbdev;

			$tab_bulletin[$id_classe][$periode_num]['display_moy_gen']=$display_moy_gen;

			// Variables r�cup�r�es de calcul_moy_gen.inc.php
			// $current_group est un tableau obtenu par get_group()
			$tab_bulletin[$id_classe][$periode_num]['groupe']=$current_group;

			// Variables r�cup�r�es de calcul_moy_gen.inc.php
			// Tableaux d'indices [$j][$i] (groupe, �l�ve)
			// A V�RIFIER: Tableaux d'indices [$i][$j] (�l�ve, groupe)
			$tab_bulletin[$id_classe][$periode_num]['note']=$current_eleve_note;
			$tab_bulletin[$id_classe][$periode_num]['statut']=$current_eleve_statut;
			/*
			for($j=0;$j<count($current_eleve_statut);$j++) {
				for($i=0;$i<count($current_eleve_statut[$j]);$i++) {
					echo "\$current_eleve_statut[$j][$i]=".$current_eleve_statut[$j][$i]."<br />";
				}
			}
			*/
			if(isset($current_eleve_rang)) {$tab_bulletin[$id_classe][$periode_num]['rang']=$current_eleve_rang;}
			$tab_bulletin[$id_classe][$periode_num]['coef_eleve']=$current_coef_eleve;

			// Tableaux d'indice $i (correspondant � l'�l�ve)
			$tab_bulletin[$id_classe][$periode_num]['tot_points_eleve']=$tot_points_eleve;
			$tab_bulletin[$id_classe][$periode_num]['total_coef_eleve']=$total_coef_eleve;
			$tab_bulletin[$id_classe][$periode_num]['tot_points_eleve1']=$tot_points_eleve1;
			$tab_bulletin[$id_classe][$periode_num]['total_coef_eleve1']=$total_coef_eleve1;

			// Variables r�cup�r�es de calcul_moy_gen.inc.php
			// Tableau d'indice [$i] �l�ve.. mais cette moyenne g�n�rale ne prend en compte que les options suivies par l'�l�ve si bien que les moyennes g�n�rales de classe diff�rent selon les �l�ves
			$tab_bulletin[$id_classe][$periode_num]['moy_gen_classe']=$moy_gen_classe;
			$tab_bulletin[$id_classe][$periode_num]['moy_gen_eleve']=$moy_gen_eleve;
			//===============
			// Ajout J.Etheve
			$tab_bulletin[$id_classe][$periode_num]['moy_gen_classe_noncoef']=$moy_gen_classe1;
			$tab_bulletin[$id_classe][$periode_num]['moy_gen_eleve_noncoef']=$moy_gen_eleve1;
			//===============

			// Variables r�cup�r�es de calcul_moy_gen.inc.php
			// Variables simples
			$tab_bulletin[$id_classe][$periode_num]['moy_min_classe']=$moy_min_classe;
			$tab_bulletin[$id_classe][$periode_num]['moy_generale_classe']=$moy_generale_classe;
			$tab_bulletin[$id_classe][$periode_num]['moy_max_classe']=$moy_max_classe;
			//===============
			// Ajout J.Etheve
			$tab_bulletin[$id_classe][$periode_num]['moy_min_classe_noncoef']=$moy_min_classe1;
			$tab_bulletin[$id_classe][$periode_num]['moy_generale_classe_noncoef']=$moy_generale_classe1;
			$tab_bulletin[$id_classe][$periode_num]['moy_max_classe_noncoef']=$moy_max_classe1;
			//===============
			$tab_bulletin[$id_classe][$periode_num]['moy_min_classe_grp']=$moy_min_classe_grp;
			$tab_bulletin[$id_classe][$periode_num]['moy_classe_grp']=$current_classe_matiere_moyenne;
			/*
			for($kl=0;$kl<count($current_classe_matiere_moyenne);$kl++) {
				echo "\$current_group[$kl]['name']=".$current_group[$kl]['name']." ";
				echo "\$current_classe_matiere_moyenne[$kl]='".$current_classe_matiere_moyenne[$kl]."'<br />";
			}
			*/
			$tab_bulletin[$id_classe][$periode_num]['moy_max_classe_grp']=$moy_max_classe_grp;


			if($mode_bulletin=="pdf") {
				$tab_bulletin[$id_classe][$periode_num]['affiche_moyenne_general_coef_1']=$tab_modele_pdf["affiche_moyenne_general_coef_1"][$tab_id_classe[$loop_classe]];
				if(($temoin_tous_coef_a_1=='y')||($coefficients_a_1=="oui")) {
					// Si tous les coeff sont � 1, on n'imprime pas deux lignes de moyenne g�n�rale (moy.gen.coefficient�e d'apr�s Gestion des classes/<Classe> Enseignements et moy.gen avec coef � 1) m�me si la case est coch�e dans le mod�le PDF.
					// Si on force les coef � 1, on n'affiche pas non plus deux lignes de moyenne g�n�rale
					$tab_bulletin[$id_classe][$periode_num]['affiche_moyenne_general_coef_1']=0;
				}
			}
			else {
				// Pour l'instant en mode HTML, on ne propose pas les deux moyennes
				// Il faut d�cider o� on fait le param�trage.
				// Les param�tres HTML sont g�n�raux � toutes les classes sauf ceux d�cid�s directement dans bull_index.php alors que les param�tres PDF sont essentiellement li�s aux mod�les.
				$tab_bulletin[$id_classe][$periode_num]['affiche_moyenne_general_coef_1']=0;
			}
			
			//ERIC
			if($mode_bulletin=="pdf") { // affichage du num�ro du responsable
				$tab_bulletin[$id_classe][$periode_num]['affiche_numero_responsable']=$tab_modele_pdf["affiche_numero_responsable"][$tab_id_classe[$loop_classe]];
			}
			
			// Variables r�cup�r�es de calcul_moy_gen.inc.php
			// Quartiles au niveau moyenne g�n�rale:
			// $place_eleve_classe est un tableau d'indice [$i] le num�ro de l'�l�ve
			if(isset($place_eleve_classe)) {$tab_bulletin[$id_classe][$periode_num]['place_eleve_classe']=$place_eleve_classe;}
			// Variables simples
			$tab_bulletin[$id_classe][$periode_num]['quartile1_classe_gen']=$quartile1_classe_gen;
			$tab_bulletin[$id_classe][$periode_num]['quartile2_classe_gen']=$quartile2_classe_gen;
			$tab_bulletin[$id_classe][$periode_num]['quartile3_classe_gen']=$quartile3_classe_gen;
			$tab_bulletin[$id_classe][$periode_num]['quartile4_classe_gen']=$quartile4_classe_gen;
			$tab_bulletin[$id_classe][$periode_num]['quartile5_classe_gen']=$quartile5_classe_gen;
			$tab_bulletin[$id_classe][$periode_num]['quartile6_classe_gen']=$quartile6_classe_gen;

			// Initialisation des quartiles par groupe:
			for($j=0;$j<count($current_group);$j++) {
				$tab_bulletin[$id_classe][$periode_num]['quartile1_grp'][$j]=0;
				$tab_bulletin[$id_classe][$periode_num]['quartile2_grp'][$j]=0;
				$tab_bulletin[$id_classe][$periode_num]['quartile3_grp'][$j]=0;
				$tab_bulletin[$id_classe][$periode_num]['quartile4_grp'][$j]=0;
				$tab_bulletin[$id_classe][$periode_num]['quartile5_grp'][$j]=0;
				$tab_bulletin[$id_classe][$periode_num]['quartile6_grp'][$j]=0;
			}

			$tab_bulletin[$id_classe][$periode_num]['place_eleve']=$place_eleve_grp;
			$tab_bulletin[$id_classe][$periode_num]['quartile1_grp']=$quartile1_grp;
			$tab_bulletin[$id_classe][$periode_num]['quartile2_grp']=$quartile2_grp;
			$tab_bulletin[$id_classe][$periode_num]['quartile3_grp']=$quartile3_grp;
			$tab_bulletin[$id_classe][$periode_num]['quartile4_grp']=$quartile4_grp;
			$tab_bulletin[$id_classe][$periode_num]['quartile5_grp']=$quartile5_grp;
			$tab_bulletin[$id_classe][$periode_num]['quartile6_grp']=$quartile6_grp;
			//for($kl=0;$kl<count($quartile1_grp);$kl++) {echo "\$quartile1_grp[$kl]=".$quartile1_grp[$kl]."<br />";}

			// Variables r�cup�r�es de calcul_moy_gen.inc.php
			// Tableaux d'indices [$i][$cat] (o� $i: eleve et $cat: $categorie_id)
			$tab_bulletin[$id_classe][$periode_num]['moy_cat_classe']=$moy_cat_classe;
			$tab_bulletin[$id_classe][$periode_num]['moy_cat_min']=$moy_cat_min;
			$tab_bulletin[$id_classe][$periode_num]['moy_cat_max']=$moy_cat_max;

			$tab_bulletin[$id_classe][$periode_num]['moy_cat_eleve']=$moy_cat_eleve;

			// matieres_categories(id,nom_court,nom_complet,priority)
			// j_matieres_categories_classes(categorie_id,classe_id,priority,affiche_moyenne)
			// matieres(matiere,nom_complet,priority,categorie_id,matiere_aid,matiere_atelier)
			for($j=0;$j<count($current_group);$j++) {
				//echo "\$current_group[$j]['id']=".$current_group[$j]['id']."<br />";
				//echo "\$current_group[$j]['name']=".$current_group[$j]['name']."<br />";
				//echo "\$current_group[$j]['matiere']['matiere']=".$current_group[$j]['matiere']['matiere']."<br />";
				if(isset($current_group[$j]['matiere']['matiere'])) {
                    /*
					$sql="SELECT mc.id,
								mc.nom_court,
								mc.nom_complet,
								jmcc.priority,
								jmcc.affiche_moyenne
							FROM j_matieres_categories_classes jmcc,
								matieres_categories mc,
								matieres m
							WHERE jmcc.classe_id='$id_classe' AND
								jmcc.categorie_id=m.categorie_id AND
								jmcc.categorie_id=mc.id AND
								m.matiere='".$current_group[$j]['matiere']['matiere']."'
							ORDER BY mc.priority, jmcc.priority, jmcc.categorie_id;";
                    */
							//ORDER BY jmcc.categorie_id, jmcc.priority, mc.priority;";
					$sql="SELECT mc.id,
								mc.nom_court,
								mc.nom_complet,
								jmcc.priority,
								jmcc.affiche_moyenne
							FROM j_matieres_categories_classes jmcc,
								matieres_categories mc,
                                j_groupes_classes jgc
							WHERE jmcc.classe_id='$id_classe' AND
                                  jgc.id_classe=jmcc.classe_id AND
                                  jgc.categorie_id=jmcc.categorie_id AND
								jmcc.categorie_id=mc.id AND
								jgc.id_groupe='".$current_group[$j]['id']."'
							ORDER BY mc.priority, jmcc.priority, jmcc.categorie_id;";
                    //echo "\$current_group[$j]['matiere']['matiere']=".$current_group[$j]['matiere']['matiere']."<br />";
                    //echo "$sql<br />";
					$res_cat=mysql_query($sql);

					if(mysql_num_rows($res_cat)>0) {
						$lig_cat=mysql_fetch_object($res_cat);

						$tab_bulletin[$id_classe][$periode_num]['cat_id'][$j]=$lig_cat->id;
						$tab_bulletin[$id_classe][$periode_num]['nom_cat_court'][$j]=$lig_cat->nom_court;
						$tab_bulletin[$id_classe][$periode_num]['nom_cat_complet'][$j]=$lig_cat->nom_complet;
						$tab_bulletin[$id_classe][$periode_num]['priority'][$j]=$lig_cat->priority;
						$tab_bulletin[$id_classe][$periode_num]['affiche_moyenne'][$j]=$lig_cat->affiche_moyenne;
					}
				}
			}



			if ($affiche_rang == 'y'){
				for($j=0;$j<count($current_group);$j++) {
					/*
					$sql="SELECT 1=1
							FROM j_eleves_groupe jeg
							WHERE jeg.id_groupe='".$current_group[$j]['id']."' AND
								jeg.periode='$periode_num';";
					$res_eff_grp=mysql_query($sql);

					$tab_bulletin[$id_classe][$periode_num]['groupe'][$j]['effectif']=mysql_num_rows($res_cat);
					*/
					// On pourrait utiliser count($tab_bulletin[$id_classe][$periode_num]['groupe'][$j]["eleves"][$key]["list"])
					// avec $key=$periode_num
					$tab_bulletin[$id_classe][$periode_num]['groupe'][$j]['effectif']=count($tab_bulletin[$id_classe][$periode_num]['groupe'][$j]["eleves"][$periode_num]["list"]);

					$tab_bulletin[$id_classe][$periode_num]['groupe'][$j]['effectif_avec_note']=$current_group_effectif_avec_note[$j];
				}
			}


			// L'ordre des mati�res est obtenu via calcul_moy_gen.inc.php dans lequel le $affiche_categorie fixe l'ordre par cat�gories ou non.



			// Boucle �l�ves de la classe $id_classe pour la p�riode $periode_num
			for($i=0;$i<count($current_eleve_login);$i++) {
				// R�initialisation pour ne pas r�cup�rer des infos de l'�l�ve pr�c�dent
				unset($tab_ele);
				$tab_ele=array();

				if (in_array($current_eleve_login[$i],$tab_bulletin[$id_classe][$periode_num]['selection_eleves'])) {

					// ++++++++++++++++++++++++++++++++++++++
					// ++++++++++++++++++++++++++++++++++++++
					// AJOUTER UN TEST: L'�l�ve fait-il bien partie de la classe?
					//                  Inutile: la liste $current_eleve_login est obtenue de calcul_moy_gen.inc.php
					//                  Pas d'injection/intervention possible.
					//                  Le test sur l'acc�s � la classe plus haut (*) doit suffire.
					//                  On pourrait injecter un login dans la s�lection d'�l�ve, mais pas dans $current_eleve_login
					//                  Et on test seulement si $current_eleve_login[$i] est bien dans la s�lection (pas le contraire)
					//                  (*) dans cette section tout de m�me.
					// ++++++++++++++++++++++++++++++++++++++
					// ++++++++++++++++++++++++++++++++++++++

					//==============================

					if($mode_bulletin=="html") {
						$motif="Temoin_eleve".$id_classe."_".$periode_num;
						decompte_debug($motif,"$motif �l�ve $i: ".$current_eleve_login[$i]);
						flush();
						echo "<script type='text/javascript'>
	document.getElementById('td_ele').innerHTML='".$current_eleve_login[$i]."';
</script>\n";
					}
					//==============================

					// R�cup des infos sur l'�l�ve, les responsables, le PP, le CPE,...
					$sql="SELECT * FROM eleves e WHERE e.login='".$current_eleve_login[$i]."';";
					$res_ele=mysql_query($sql);
					$lig_ele=mysql_fetch_object($res_ele);

					$tab_ele['login']=$current_eleve_login[$i];
					$tab_ele['nom']=$lig_ele->nom;
					$tab_ele['prenom']=$lig_ele->prenom;
					$tab_ele['sexe']=$lig_ele->sexe;
					$tab_ele['naissance']=formate_date($lig_ele->naissance);
					$tab_ele['lieu_naissance']=get_commune($lig_ele->lieu_naissance,2);
					$tab_ele['elenoet']=$lig_ele->elenoet;
					$tab_ele['ele_id']=$lig_ele->ele_id;
					$tab_ele['no_gep']=$lig_ele->no_gep;

					$tab_ele['classe']=$classe;
					$tab_ele['id_classe']=$id_classe;
					$tab_ele['classe_nom_complet']=$classe_nom_complet;

					// R�gime et redoublement
					$sql="SELECT * FROM j_eleves_regime WHERE login='".$current_eleve_login[$i]."';";
					$res_ele_reg=mysql_query($sql);
					if(mysql_num_rows($res_ele_reg)>0) {
						$lig_ele_reg=mysql_fetch_object($res_ele_reg);

						$tab_ele['regime']=$lig_ele_reg->regime;
						$tab_ele['doublant']=$lig_ele_reg->doublant;
					}

					//$sql="SELECT e.* FROM etablissements e, j_eleves_etablissements j WHERE (j.id_eleve ='".$current_eleve_login[$i]."' AND e.id = j.id_etablissement);";
					$sql="SELECT e.* FROM etablissements e, j_eleves_etablissements j WHERE (j.id_eleve ='".$tab_ele['elenoet']."' AND e.id = j.id_etablissement);";
					//echo "$sql<br />";
					$data_etab = mysql_query($sql);
					if(mysql_num_rows($data_etab)>0) {
						$tab_ele['etab_id'] = @mysql_result($data_etab, 0, "id");
						$tab_ele['etab_nom'] = @mysql_result($data_etab, 0, "nom");
						$tab_ele['etab_niveau'] = @mysql_result($data_etab, 0, "niveau");
						$tab_ele['etab_type'] = @mysql_result($data_etab, 0, "type");
						$tab_ele['etab_cp'] = @mysql_result($data_etab, 0, "cp");
						$tab_ele['etab_ville'] = @mysql_result($data_etab, 0, "ville");

						if ($tab_ele['etab_niveau']!='') {
							foreach ($type_etablissement as $type_etab => $nom_etablissement) {
								if ($tab_ele['etab_niveau'] == $type_etab) {
									$tab_ele['etab_niveau_nom']=$nom_etablissement;
								}
							}
							if ($tab_ele['etab_cp']==0) {
								$tab_ele['etab_cp']='';
							}

							if (($tab_ele['etab_type']=='aucun')||($tab_ele['etab_type']=='')||($tab_ele['etab_niveau']=='')) {
								$tab_ele['etab_type']='';
							}
							else {
								//$tab_ele['etab_type']= $type_etablissement2[remplace_accents($tab_ele['etab_type'],'')][remplace_accents($tab_ele['etab_niveau'],'')];
								if(strtoupper($tab_ele['etab_niveau'])=='EREA') {
									$tmp_etab_niveau='EREA';
								}
								else {
									$tmp_etab_niveau=strtolower(remplace_accents($tab_ele['etab_niveau'],''));
								}
								$tab_ele['etab_type']= $type_etablissement2[strtolower(remplace_accents($tab_ele['etab_type'],''))][$tmp_etab_niveau];
								//echo "\$type_etablissement2[".$tab_ele['etab_type']."][".$tab_ele['etab_niveau']."]=".$type_etablissement2[remplace_accents($tab_ele['etab_type'],'')][remplace_accents($tab_ele['etab_niveau'],'')]."<br />\n";
							}
						}
					}

					// R�cup infos CPE
					$sql="SELECT u.* FROM j_eleves_cpe jec, utilisateurs u WHERE e_login='".$current_eleve_login[$i]."' AND jec.cpe_login=u.login;";
					$res_cpe=mysql_query($sql);
					if(mysql_num_rows($res_cpe)>0) {
						$lig_cpe=mysql_fetch_object($res_cpe);
						$tab_ele['cpe']=array();

						$tab_ele['cpe']['login']=$lig_cpe->login;
						$tab_ele['cpe']['nom']=$lig_cpe->nom;
						$tab_ele['cpe']['prenom']=$lig_cpe->prenom;
						$tab_ele['cpe']['civilite']=$lig_cpe->civilite;
					}

					// R�cup infos Prof Principal (prof_suivi)
					$sql="SELECT u.* FROM j_eleves_professeurs jep, utilisateurs u WHERE jep.login='".$current_eleve_login[$i]."' AND id_classe='$id_classe' AND jep.professeur=u.login;";
					$res_pp=mysql_query($sql);
					//echo "$sql<br />";
					if(mysql_num_rows($res_pp)>0) {
						$tab_ele['pp']=array();

						$cpt_pp=0;
						while($lig_pp=mysql_fetch_object($res_pp)) {
							$tab_ele['pp'][$cpt_pp]=array();
							$tab_ele['pp'][$cpt_pp]['login']=$lig_pp->login;
							$tab_ele['pp'][$cpt_pp]['nom']=$lig_pp->nom;
							$tab_ele['pp'][$cpt_pp]['prenom']=$lig_pp->prenom;
							$tab_ele['pp'][$cpt_pp]['civilite']=$lig_pp->civilite;
							$cpt_pp++;
						}
					}

					// R�cup infos responsables
					$sql="SELECT rp.*,ra.adr1,ra.adr2,ra.adr3,ra.adr3,ra.adr4,ra.cp,ra.pays,ra.commune,r.resp_legal FROM resp_pers rp,
													resp_adr ra,
													responsables2 r
								WHERE r.ele_id='".$tab_ele['ele_id']."' AND
										r.resp_legal!='0' AND
										r.pers_id=rp.pers_id AND
										rp.adr_id=ra.adr_id
								ORDER BY resp_legal;";
					$res_resp=mysql_query($sql);
					//echo "$sql<br />";
					if(mysql_num_rows($res_resp)>0) {
						$cpt=0;
						while($lig_resp=mysql_fetch_object($res_resp)) {
							$tab_ele['resp'][$cpt]=array();

							$tab_ele['resp'][$cpt]['pers_id']=$lig_resp->pers_id;

							$tab_ele['resp'][$cpt]['login']=$lig_resp->login;
							$tab_ele['resp'][$cpt]['nom']=$lig_resp->nom;
							$tab_ele['resp'][$cpt]['prenom']=$lig_resp->prenom;
							$tab_ele['resp'][$cpt]['civilite']=$lig_resp->civilite;
							$tab_ele['resp'][$cpt]['tel_pers']=$lig_resp->tel_pers;
							$tab_ele['resp'][$cpt]['tel_port']=$lig_resp->tel_port;
							$tab_ele['resp'][$cpt]['tel_prof']=$lig_resp->tel_prof;

							$tab_ele['resp'][$cpt]['adr1']=$lig_resp->adr1;
							$tab_ele['resp'][$cpt]['adr2']=$lig_resp->adr2;
							$tab_ele['resp'][$cpt]['adr3']=$lig_resp->adr3;
							$tab_ele['resp'][$cpt]['adr4']=$lig_resp->adr4;
							$tab_ele['resp'][$cpt]['cp']=$lig_resp->cp;
							$tab_ele['resp'][$cpt]['pays']=$lig_resp->pays;
							$tab_ele['resp'][$cpt]['commune']=$lig_resp->commune;

							$tab_ele['resp'][$cpt]['adr_id']=$lig_resp->adr_id;

							$tab_ele['resp'][$cpt]['resp_legal']=$lig_resp->resp_legal;

							$cpt++;
						}
					}

					// V�rification
					if(mysql_num_rows($res_resp)>2) {
						if($mode_bulletin=="html") {
							echo "<div class='alerte_erreur'><b style='color:red;'>Erreur:</b>";
							echo $tab_ele['nom']." ".$tab_ele['prenom']." a plus de deux responsables l�gaux 1 et 2.<br />C'est une anomalie.<br />";
							for ($z=0;$z<count($tab_ele['resp']);$z++) {
								echo $tab_ele['resp'][$z]['nom']." ".$tab_ele['resp'][$z]['prenom']." (<i>responsable l�gal ".$tab_ele['resp'][$z]['resp_legal']."</i>)<br />";
							}
							echo "Seuls les deux premiers apparaitront sur des bulletins.";
							echo "</div>\n";
						}
					}


					// Rang
					if ($affiche_rang == 'y'){
						$rang = sql_query1("select rang from j_eleves_classes where (
						periode = '".$periode_num."' and
						id_classe = '".$id_classe."' and
						login = '".$current_eleve_login[$i]."' )
						");
						if (($rang == 0)||($rang == -1)) {
							$rang = "-";
						}

						// Rang de l'�l�ve dans la classe (par rapport � la moyenne g�n�rale)
						$tab_bulletin[$id_classe][$periode_num]['rang_classe'][$i]=$rang;
					}

					//========================
					// R�cup�rer les infos AID

					// Pas d'affichage dans le cas d'un bulletin d'une p�riode "examen blanc"
					if ($bull_affiche_aid == 'y') {
						//==============================
						if($mode_bulletin=="html") {
							$motif="Temoin_eleve".$id_classe."_".$periode_num."_".$i;
							decompte_debug($motif,"$motif �l�ve $i (".$current_eleve_login[$i].") avant AID");
							flush();
						}
						//==============================

						// On attaque maintenant l'affichage des appr�ciations des Activit�s Interdisciplinaires devant appara�tre en t�te des bulletins :
						//------------------------------
						// $z est l'indice de $call_data_aid_b
						$z=0;
						// $zz est l'indice des AID effectivement utiles pour l'�l�ve et la p�riode
						$zz=0;
						while ($z < $nb_aid_b) {
							$display_begin = @mysql_result($call_data_aid_b, $z, "display_begin");
							$display_end = @mysql_result($call_data_aid_b, $z, "display_end");
							$type_note = @mysql_result($call_data_aid_b, $z, "type_note");
							$note_max = @mysql_result($call_data_aid_b, $z, "note_max");
							/*
							echo "\$z=$z<br />";
							echo "\$display_begin=$display_begin<br />";
							echo "\$display_end=$display_end<br />";
							echo "\$type_note=$type_note<br />";
							echo "\$note_max=$note_max<br />";
							*/
							if (($periode_num >= $display_begin) and ($periode_num <= $display_end)) {
								$indice_aid = @mysql_result($call_data_aid_b, $z, "indice_aid");
								//$aid_query = mysql_query("SELECT id_aid FROM j_aid_eleves WHERE (login='".$current_eleve_login[$i]."' and indice_aid='$indice_aid')");
								$sql="SELECT id_aid FROM j_aid_eleves WHERE (login='".$current_eleve_login[$i]."' and indice_aid='$indice_aid');";
								//echo "$sql<br />";
								$aid_query = mysql_query($sql);
								$aid_id = @mysql_result($aid_query, 0, "id_aid");
								if ($aid_id != '') {

									$tab_ele['aid_b'][$zz]['display_begin']=$display_begin;
									$tab_ele['aid_b'][$zz]['display_end']=$display_end;

									$tab_ele['aid_b'][$zz]['nom']=@mysql_result($call_data_aid_b, $z, "nom");
									$tab_ele['aid_b'][$zz]['nom_complet']=@mysql_result($call_data_aid_b, $z, "nom_complet");
									$tab_ele['aid_b'][$zz]['message']=@mysql_result($call_data_aid_b, $z, "message");

									$tab_ele['aid_b'][$zz]['display_nom']=@mysql_result($call_data_aid_b, $z, "display_nom");

									//echo "\$tab_ele['aid_b'][$zz]['nom_complet']=".$tab_ele['aid_b'][$zz]['nom_complet']."<br />";
									//echo "\$type_note=".$type_note."<br />";

									$aid_nom_query = mysql_query("SELECT nom FROM aid WHERE (id='$aid_id' and indice_aid='$indice_aid');");
									$tab_ele['aid_b'][$zz]['aid_nom']=@mysql_result($aid_nom_query, 0, "nom");

									//echo "\$tab_ele['aid_b'][$zz]['aid_nom']=".$tab_ele['aid_b'][$z]['aid_nom']."<br />";

									// On regarde maintenant quelle sont les profs responsables de cette AID
									$aid_prof_resp_query = mysql_query("SELECT id_utilisateur FROM j_aid_utilisateurs WHERE (id_aid='$aid_id'  and indice_aid='$indice_aid')");
									$nb_lig = mysql_num_rows($aid_prof_resp_query);
									$n = '0';
									while ($n < $nb_lig) {
										//$aid_prof_resp_login[$n] = mysql_result($aid_prof_resp_query, $n, "id_utilisateur");
										$tab_ele['aid_b'][$zz]['aid_prof_resp_login'][$n]=mysql_result($aid_prof_resp_query, $n, "id_utilisateur");

										//echo "\$tab_ele['aid_b'][$zz]['aid_prof_resp_login'][$n]=".$tab_ele['aid_b'][$zz]['aid_prof_resp_login'][$n]."<br />";

										$n++;
									}
									//------
									// On appelle l'appr�ciation de l'�l�ve, et sa note
									//------
									$current_eleve_aid_appreciation_query = mysql_query("SELECT * FROM aid_appreciations WHERE (login='".$current_eleve_login[$i]."' AND periode='$periode_num' and id_aid='$aid_id' and indice_aid='$indice_aid')");
									$current_eleve_aid_appreciation = @mysql_result($current_eleve_aid_appreciation_query, 0, "appreciation");
									$periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '$id_classe'");
									$periode_max = mysql_num_rows($periode_query);
									if ($type_note == 'last') {$last_periode_aid = min($periode_max,$display_end);}
									if (($type_note == 'every') or (($type_note == 'last') and ($periode_num == $last_periode_aid))) {
										$place_eleve = "";
										$current_eleve_aid_note = @mysql_result($current_eleve_aid_appreciation_query, 0, "note");
										$current_eleve_aid_statut = @mysql_result($current_eleve_aid_appreciation_query, 0, "statut");
										if (($current_eleve_aid_statut == '') and ($note_max != 20) ) {
											$current_eleve_aid_appreciation = "(note sur ".$note_max.") ".$current_eleve_aid_appreciation;
										}
										if ($current_eleve_aid_note == '') {
											$current_eleve_aid_note = '-';
										} else {
											if ($affiche_graph == 'y')  {
												if ($current_eleve_aid_note<5) { $place_eleve=6;}
												if (($current_eleve_aid_note>=5) and ($current_eleve_aid_note<8))  { $place_eleve=5;}
												if (($current_eleve_aid_note>=8) and ($current_eleve_aid_note<10)) { $place_eleve=4;}
												if (($current_eleve_aid_note>=10) and ($current_eleve_aid_note<12)) {$place_eleve=3;}
												if (($current_eleve_aid_note>=12) and ($current_eleve_aid_note<15)) { $place_eleve=2;}
												if ($current_eleve_aid_note>=15) { $place_eleve=1;}

												// Pas id�al: on fait ces requ�tes autant de fois qu'il y a d'�l�ve...
												$quartile1_classe = sql_query1("SELECT COUNT( a.note ) as quartile1 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=15)");
												$quartile2_classe = sql_query1("SELECT COUNT( a.note ) as quartile2 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=12 AND a.note<15)");
												$quartile3_classe = sql_query1("SELECT COUNT( a.note ) as quartile3 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=10 AND a.note<12)");
												$quartile4_classe = sql_query1("SELECT COUNT( a.note ) as quartile4 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=8 AND a.note<10)");
												$quartile5_classe = sql_query1("SELECT COUNT( a.note ) as quartile5 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=5 AND a.note<8)");
												$quartile6_classe = sql_query1("SELECT COUNT( a.note ) as quartile6 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note<5)");

												$tab_ele['aid_b'][$zz]['quartile1_classe']=$quartile1_classe;
												$tab_ele['aid_b'][$zz]['quartile2_classe']=$quartile2_classe;
												$tab_ele['aid_b'][$zz]['quartile3_classe']=$quartile3_classe;
												$tab_ele['aid_b'][$zz]['quartile4_classe']=$quartile4_classe;
												$tab_ele['aid_b'][$zz]['quartile5_classe']=$quartile5_classe;
												$tab_ele['aid_b'][$zz]['quartile6_classe']=$quartile6_classe;

											}
											$current_eleve_aid_note=number_format($current_eleve_aid_note,1, ',', ' ');
										}
										$aid_note_min_query = mysql_query("SELECT MIN(note) note_min FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid')");

										$aid_note_min = @mysql_result($aid_note_min_query, 0, "note_min");
										if ($aid_note_min == '') {
											$aid_note_min = '-';
										} else {
											$aid_note_min=number_format($aid_note_min,1, ',', ' ');
										}
										$aid_note_max_query = mysql_query("SELECT MAX(note) note_max FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid')");
										$aid_note_max = @mysql_result($aid_note_max_query, 0, "note_max");

										if ($aid_note_max == '') {
											$aid_note_max = '-';
										} else {
											$aid_note_max=number_format($aid_note_max,1, ',', ' ');
										}

										$aid_note_moyenne_query = mysql_query("SELECT round(avg(note),1) moyenne FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid')");
										$aid_note_moyenne = @mysql_result($aid_note_moyenne_query, 0, "moyenne");
										if ($aid_note_moyenne == '') {
											$aid_note_moyenne = '-';
										} else {
											$aid_note_moyenne=number_format($aid_note_moyenne,1, ',', ' ');
										}

										$tab_ele['aid_b'][$zz]['aid_note']=$current_eleve_aid_note;
										$tab_ele['aid_b'][$zz]['aid_statut']=$current_eleve_aid_statut;
										$tab_ele['aid_b'][$zz]['aid_note_moyenne']=$aid_note_moyenne;
										$tab_ele['aid_b'][$zz]['aid_note_max']=$aid_note_max;
										$tab_ele['aid_b'][$zz]['aid_note_min']=$aid_note_min;
										$tab_ele['aid_b'][$zz]['place_eleve']=$place_eleve;
									}

									if ($type_note == 'no') {
										$tab_ele['aid_b'][$zz]['aid_note']='-';
										$tab_ele['aid_b'][$zz]['aid_statut']='';
										$tab_ele['aid_b'][$zz]['aid_note_moyenne']='-';
										$tab_ele['aid_b'][$zz]['aid_note_max']='-';
										$tab_ele['aid_b'][$zz]['aid_note_min']='-';
										//$tab_ele['aid_b'][$zz]['place_eleve']=$place_eleve;
									}

									$tab_ele['aid_b'][$zz]['aid_appreciation']=$current_eleve_aid_appreciation;

									//echo "\$tab_ele['aid_b'][$z]['aid_appreciation']=".$tab_ele['aid_b'][$z]['aid_appreciation']."<br />";

									// Vaut-il mieux un tableau $tab_ele['aid_b']
									// ou calquer sur $tab_bulletin[$id_classe][$periode_num]['groupe']
									// La deuxi�me solution r�duirait sans doute le nombre de requ�tes

									$zz++;
								}
							}
							$z++;
						}


						//echo "<p>".$tab_ele['login']."<br />";
						// On attaque maintenant l'affichage des appr�ciations des Activit�s Interdisciplinaires devant appara�tre en fin des bulletins :
						//------------------------------
						// $z est l'indice de $call_data_aid_e
						$z=0;
						// $zz est l'indice des AID effectivement utiles pour l'�l�ve et la p�riode
						$zz=0;
						while ($z < $nb_aid_e) {
							$display_begin = @mysql_result($call_data_aid_e, $z, "display_begin");
							$display_end = @mysql_result($call_data_aid_e, $z, "display_end");
							$type_note = @mysql_result($call_data_aid_e, $z, "type_note");
							$note_max = @mysql_result($call_data_aid_e, $z, "note_max");

							/*
							echo "\$z=$z<br />";
							echo "\$display_begin=$display_begin<br />";
							echo "\$display_end=$display_end<br />";
							echo "\$type_note=$type_note<br />";
							echo "\$note_max=$note_max<br />";
							*/

							if (($periode_num >= $display_begin) and ($periode_num <= $display_end)) {
								$indice_aid = @mysql_result($call_data_aid_e, $z, "indice_aid");
								$aid_query = mysql_query("SELECT id_aid FROM j_aid_eleves WHERE (login='".$current_eleve_login[$i]."' and indice_aid='$indice_aid')");
								$aid_id = @mysql_result($aid_query, 0, "id_aid");
								if ($aid_id != '') {

									$tab_ele['aid_e'][$zz]['display_begin']=$display_begin;
									$tab_ele['aid_e'][$zz]['display_end']=$display_end;

									$tab_ele['aid_e'][$zz]['nom']=@mysql_result($call_data_aid_e, $z, "nom");
									$tab_ele['aid_e'][$zz]['nom_complet']=@mysql_result($call_data_aid_e, $z, "nom_complet");
									$tab_ele['aid_e'][$zz]['message']=@mysql_result($call_data_aid_e, $z, "message");

									$tab_ele['aid_e'][$zz]['display_nom']=@mysql_result($call_data_aid_e, $z, "display_nom");

									//echo "\$tab_ele['aid_e'][$zz]['nom_complet']=".$tab_ele['aid_e'][$zz]['nom_complet']."<br />";
									//echo "\$type_note=".$type_note."<br />";

									$aid_nom_query = mysql_query("SELECT nom FROM aid WHERE (id='$aid_id' and indice_aid='$indice_aid');");
									$tab_ele['aid_e'][$zz]['aid_nom']=@mysql_result($aid_nom_query, 0, "nom");

									//echo "\$tab_ele['aid_e'][$zz]['aid_nom']=".$tab_ele['aid_e'][$zz]['aid_nom']."<br />";

									// On regarde maintenant quelle sont les profs responsables de cette AID
									$aid_prof_resp_query = mysql_query("SELECT id_utilisateur FROM j_aid_utilisateurs WHERE (id_aid='$aid_id'  and indice_aid='$indice_aid')");
									$nb_lig = mysql_num_rows($aid_prof_resp_query);
									$n = '0';
									while ($n < $nb_lig) {
										//$aid_prof_resp_login[$n] = mysql_result($aid_prof_resp_query, $n, "id_utilisateur");
										$tab_ele['aid_e'][$zz]['aid_prof_resp_login'][$n]=mysql_result($aid_prof_resp_query, $n, "id_utilisateur");

										//echo "\$tab_ele['aid_e'][$zz]['aid_prof_resp_login'][$n]=".$tab_ele['aid_e'][$zz]['aid_prof_resp_login'][$n]."<br />";

										$n++;
									}
									//------
									// On appelle l'appr�ciation de l'�l�ve, et sa note
									//------
									$current_eleve_aid_appreciation_query = mysql_query("SELECT * FROM aid_appreciations WHERE (login='".$current_eleve_login[$i]."' AND periode='$periode_num' and id_aid='$aid_id' and indice_aid='$indice_aid')");
									$current_eleve_aid_appreciation = @mysql_result($current_eleve_aid_appreciation_query, 0, "appreciation");
									$periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '$id_classe'");
									$periode_max = mysql_num_rows($periode_query);
									if ($type_note == 'last') {$last_periode_aid = min($periode_max,$display_end);}
									if (($type_note == 'every') or (($type_note == 'last') and ($periode_num == $last_periode_aid))) {
										$place_eleve = "";
										$current_eleve_aid_note = @mysql_result($current_eleve_aid_appreciation_query, 0, "note");
										$current_eleve_aid_statut = @mysql_result($current_eleve_aid_appreciation_query, 0, "statut");
										if (($current_eleve_aid_statut == '') and ($note_max != 20) ) {
											$current_eleve_aid_appreciation = "(note sur ".$note_max.") ".$current_eleve_aid_appreciation;
										}
										if ($current_eleve_aid_note == '') {
											$current_eleve_aid_note = '-';
										} else {
											if ($affiche_graph == 'y')  {
												if ($current_eleve_aid_note<5) { $place_eleve=6;}
												if (($current_eleve_aid_note>=5) and ($current_eleve_aid_note<8))  { $place_eleve=5;}
												if (($current_eleve_aid_note>=8) and ($current_eleve_aid_note<10)) { $place_eleve=4;}
												if (($current_eleve_aid_note>=10) and ($current_eleve_aid_note<12)) {$place_eleve=3;}
												if (($current_eleve_aid_note>=12) and ($current_eleve_aid_note<15)) { $place_eleve=2;}
												if ($current_eleve_aid_note>=15) { $place_eleve=1;}

												// Pas id�al: on fait ces requ�tes autant de fois qu'il y a d'�l�ve...
												$quartile1_classe = sql_query1("SELECT COUNT( a.note ) as quartile1 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=15)");
												$quartile2_classe = sql_query1("SELECT COUNT( a.note ) as quartile2 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=12 AND a.note<15)");
												$quartile3_classe = sql_query1("SELECT COUNT( a.note ) as quartile3 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=10 AND a.note<12)");
												$quartile4_classe = sql_query1("SELECT COUNT( a.note ) as quartile4 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=8 AND a.note<10)");
												$quartile5_classe = sql_query1("SELECT COUNT( a.note ) as quartile5 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=5 AND a.note<8)");
												$quartile6_classe = sql_query1("SELECT COUNT( a.note ) as quartile6 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note<5)");

												$tab_ele['aid_e'][$zz]['quartile1_classe']=$quartile1_classe;
												$tab_ele['aid_e'][$zz]['quartile2_classe']=$quartile2_classe;
												$tab_ele['aid_e'][$zz]['quartile3_classe']=$quartile3_classe;
												$tab_ele['aid_e'][$zz]['quartile4_classe']=$quartile4_classe;
												$tab_ele['aid_e'][$zz]['quartile5_classe']=$quartile5_classe;
												$tab_ele['aid_e'][$zz]['quartile6_classe']=$quartile6_classe;

											}
											$current_eleve_aid_note=number_format($current_eleve_aid_note,1, ',', ' ');
										}
										$aid_note_min_query = mysql_query("SELECT MIN(note) note_min FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid')");

										$aid_note_min = @mysql_result($aid_note_min_query, 0, "note_min");
										if ($aid_note_min == '') {
											$aid_note_min = '-';
										} else {
											$aid_note_min=number_format($aid_note_min,1, ',', ' ');
										}
										$aid_note_max_query = mysql_query("SELECT MAX(note) note_max FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid')");
										$aid_note_max = @mysql_result($aid_note_max_query, 0, "note_max");

										if ($aid_note_max == '') {
											$aid_note_max = '-';
										} else {
											$aid_note_max=number_format($aid_note_max,1, ',', ' ');
										}

										$aid_note_moyenne_query = mysql_query("SELECT round(avg(note),1) moyenne FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid')");
										$aid_note_moyenne = @mysql_result($aid_note_moyenne_query, 0, "moyenne");
										if ($aid_note_moyenne == '') {
											$aid_note_moyenne = '-';
										} else {
											$aid_note_moyenne=number_format($aid_note_moyenne,1, ',', ' ');
										}

										$tab_ele['aid_e'][$zz]['aid_note']=$current_eleve_aid_note;
										$tab_ele['aid_e'][$zz]['aid_statut']=$current_eleve_aid_statut;
										$tab_ele['aid_e'][$zz]['aid_note_moyenne']=$aid_note_moyenne;
										$tab_ele['aid_e'][$zz]['aid_note_max']=$aid_note_max;
										$tab_ele['aid_e'][$zz]['aid_note_min']=$aid_note_min;
										$tab_ele['aid_e'][$zz]['place_eleve']=$place_eleve;
									}


									if ($type_note == 'no') {
										$tab_ele['aid_e'][$zz]['aid_note']='-';
										$tab_ele['aid_e'][$zz]['aid_statut']='';
										$tab_ele['aid_e'][$zz]['aid_note_moyenne']='-';
										$tab_ele['aid_e'][$zz]['aid_note_max']='-';
										$tab_ele['aid_e'][$zz]['aid_note_min']='-';
										//$tab_ele['aid_e'][$zz]['place_eleve']=$place_eleve;
									}

									$tab_ele['aid_e'][$zz]['aid_appreciation']=$current_eleve_aid_appreciation;

									//echo "\$tab_ele['aid_e'][$z]['aid_appreciation']=".$tab_ele['aid_e'][$z]['aid_appreciation']."<br />";

									// Vaut-il mieux un tableau $tab_ele['aid_b']
									// ou calquer sur $tab_bulletin[$id_classe][$periode_num]['groupe']
									// La deuxi�me solution r�duirait sans doute le nombre de requ�tes

									$zz++;
								}
							}
							$z++;
						}

						//==============================
						if($mode_bulletin=="html") {
							$motif="Temoin_eleve".$id_classe."_".$periode_num."_".$i;
							decompte_debug($motif,"$motif �l�ve $i (".$current_eleve_login[$i].") apr�s AID");
							flush();
						}
						//==============================
					}

					//========================




					//==========================================
					// ABSENCES
					//On v�rifie si le module est activ�
					if (getSettingValue("active_module_absence")!='2' || getSettingValue("abs2_import_manuel_bulletin")=='y') {
					    $sql="SELECT * FROM absences WHERE (login='".$current_eleve_login[$i]."' AND periode='$periode_num');";
					    $current_eleve_absences_query = mysql_query($sql);
					    $current_eleve_absences = @mysql_result($current_eleve_absences_query, 0, "nb_absences");
					    $current_eleve_nj = @mysql_result($current_eleve_absences_query, 0, "non_justifie");
					    $current_eleve_retards = @mysql_result($current_eleve_absences_query, 0, "nb_retards");
					    $current_eleve_appreciation_absences = @mysql_result($current_eleve_absences_query, 0, "appreciation");
					} else {
					    // Initialisations files
					    require_once("../lib/initialisationsPropel.inc.php");
					    $eleve = EleveQuery::create()->findOneByLogin($current_eleve_login[$i]);
					    if ($eleve != null) {
						$current_eleve_absences = strval($eleve->getDemiJourneesAbsenceParPeriode($periode_num)->count());
						$current_eleve_nj = strval($eleve->getDemiJourneesNonJustifieesAbsenceParPeriode($periode_num)->count());
						$current_eleve_retards = strval($eleve->getRetardsParPeriode($periode_num)->count());
						$current_eleve_absences_query = mysql_query("SELECT * FROM absences WHERE (login='$current_eleve_login' AND periode='$periode_num')");
						$current_eleve_appreciation_absences = @mysql_result($current_eleve_absences_query, 0, "appreciation");
					    }
					}
					if ($current_eleve_absences === '') { $current_eleve_absences = "?"; }
					if ($current_eleve_nj === '') { $current_eleve_nj = "?"; }
					if ($current_eleve_retards==='') { $current_eleve_retards = "?"; }

					$tab_ele['eleve_absences']=$current_eleve_absences;
					$tab_ele['eleve_nj']=$current_eleve_nj;
					$tab_ele['eleve_retards']=$current_eleve_retards;
					$tab_ele['appreciation_absences']=$current_eleve_appreciation_absences;

					$sql="SELECT u.login login,u.civilite FROM utilisateurs u,
												j_eleves_cpe j
											WHERE (u.login=j.cpe_login AND
												j.e_login='".$current_eleve_login[$i]."');";
					$query = mysql_query($sql);
					$current_eleve_cperesp_login = @mysql_result($query, "0", "login");
					$tab_ele['cperesp_login']=$current_eleve_cperesp_login;
					$current_eleve_cperesp_civilite = @mysql_result($query, "0", "civilite");
					$tab_ele['cperesp_civilite']=$current_eleve_cperesp_civilite;
					//==========================================


					// Boucle groupes de la classe $id_classe pour la p�riode $periode_num
					for($j=0;$j<count($current_group);$j++) {
						//============================================
						// A REVOIR: On fait cette requ�te autant de fois qu'il y a d'�l�ve... l'extraire de la boucle �l�ves en faisant une autre boucle sur $current_group
						// Nombre total de devoirs:
						$sql="SELECT cd.id FROM cn_devoirs cd, cn_cahier_notes ccn WHERE cd.id_racine=ccn.id_cahier_notes AND ccn.id_groupe='".$current_group[$j]["id"]."' AND ccn.periode='$periode_num';";
						//echo "\n<!--sql=$sql-->\n";
						$result_nbct_tot=mysql_query($sql);
						if($result_nbct_tot) {
							$current_matiere_nbct=mysql_num_rows($result_nbct_tot);
						}
						else {
							$current_matiere_nbct=0;
						}

						$tab_bulletin[$id_classe][$periode_num]['groupe'][$j]['nbct']=$current_matiere_nbct;

						//============================================

						// Si l'�l�ve suit l'option, sa note est affect�e (�ventuellement vide)
						if(isset($current_eleve_note[$j][$i])) {
							// Quartiles
							/*
							if($current_eleve_note[$j][$i]!="") {
								if($current_eleve_note[$j][$i]>=15) {
									$tab_bulletin[$id_classe][$periode_num]['quartile1_grp'][$j]++;
									$tab_bulletin[$id_classe][$periode_num]['place_eleve'][$j][$i]=1;
								}
								elseif(($current_eleve_note[$j][$i]>=12)&&($current_eleve_note[$j][$i]<15)) {
									$tab_bulletin[$id_classe][$periode_num]['quartile2_grp'][$j]++;
									$tab_bulletin[$id_classe][$periode_num]['place_eleve'][$j][$i]=2;
								}
								elseif(($current_eleve_note[$j][$i]>=10)&&($current_eleve_note[$j][$i]<12)) {
									$tab_bulletin[$id_classe][$periode_num]['quartile3_grp'][$j]++;
									$tab_bulletin[$id_classe][$periode_num]['place_eleve'][$j][$i]=3;
								}
								elseif(($current_eleve_note[$j][$i]>=8)&&($current_eleve_note[$j][$i]<10)) {
									$tab_bulletin[$id_classe][$periode_num]['quartile4_grp'][$j]++;
									$tab_bulletin[$id_classe][$periode_num]['place_eleve'][$j][$i]=4;
								}
								elseif(($current_eleve_note[$j][$i]>=5)&&($current_eleve_note[$j][$i]<8)) {
									$tab_bulletin[$id_classe][$periode_num]['quartile5_grp'][$j]++;
									$tab_bulletin[$id_classe][$periode_num]['place_eleve'][$j][$i]=5;
								}
								else {
									$tab_bulletin[$id_classe][$periode_num]['quartile6_grp'][$j]++;
									$tab_bulletin[$id_classe][$periode_num]['place_eleve'][$j][$i]=6;
								}
							}
							*/

							//================================
							// Notes des boites/conteneurs/sous-matieres
							unset($cn_note);
							unset($cn_nom);
							unset($cn_id);

							// On teste si des notes de une ou plusieurs boites du carnet de notes doivent �tre affich�e
							$sql="select distinct c.nom_court, c.id, nc.note from cn_cahier_notes cn, cn_conteneurs c, cn_notes_conteneurs nc
							where (
							cn.periode = '".$periode_num."' and
							cn.id_groupe='".$current_group[$j]["id"]."' and
							cn.id_cahier_notes = c.id_racine and
							c.id_racine!=c.id and
							nc.id_conteneur = c.id and
							nc.statut ='y' and
							nc.login='".$current_eleve_login[$i]."' and
							c.display_bulletin = 1
							) ";
							//echo "$sql<br />";
							$test_cn = mysql_query($sql);
							$nb_ligne_cn = mysql_num_rows($test_cn);
							$n = 0;
							while ($n < $nb_ligne_cn) {
								$cn_id[$n] = mysql_result($test_cn, $n, 'c.id');
								$cn_nom[$n] = mysql_result($test_cn, $n, 'c.nom_court');
								$cn_note[$n] = @mysql_result($test_cn, $n ,'nc.note');
								$cn_note[$n] = number_format($cn_note[$n],1, ',', ' ');
								$n++;
							}
							$nb_ligne_par_matiere = max($nb_ligne_cn,1) ;

							$tab_bulletin[$id_classe][$periode_num]['groupe'][$j][$i]['nb_ligne_par_matiere']=$nb_ligne_par_matiere;

							if(isset($cn_note)) {
								$tab_bulletin[$id_classe][$periode_num]['groupe'][$j][$i]['cn_note']=$cn_note;
								$tab_bulletin[$id_classe][$periode_num]['groupe'][$j][$i]['cn_id']=$cn_id;
								$tab_bulletin[$id_classe][$periode_num]['groupe'][$j][$i]['cn_nom']=$cn_nom;
							}

							//================================
							// R�cup appr�ciation
							$sql="SELECT appreciation FROM matieres_appreciations WHERE id_groupe='".$current_group[$j]['id']."' AND periode='$periode_num' AND login='".$current_eleve_login[$i]."';";
							$res_app=mysql_query($sql);
							if(mysql_num_rows($res_app)>0) {
								$lig_app=mysql_fetch_object($res_app);
								$current_eleve_app[$j][$i]=$lig_app->appreciation;
								if($current_eleve_app[$j][$i]=="") {$current_eleve_app[$j][$i]="-";}
							}
							else {
								$current_eleve_app[$j][$i]="-";
							}
							//================================

							// Nombre de contr�les
							$sql="SELECT cnd.note, cd.note_sur FROM cn_notes_devoirs cnd, cn_devoirs cd, cn_cahier_notes ccn WHERE cnd.login='".$current_eleve_login[$i]."' AND cnd.id_devoir=cd.id AND cd.id_racine=ccn.id_cahier_notes AND ccn.id_groupe='".$current_group[$j]["id"]."' AND ccn.periode='$periode_num' AND cnd.statut='';";
							//echo "\n<!--sql=$sql-->\n";
							$result_nbct=mysql_query($sql);
							if($result_nbct) {
								$current_eleve_nbct=mysql_num_rows($result_nbct);
							}
							else {
								$current_eleve_nbct=0;
							}

							$tab_bulletin[$id_classe][$periode_num]['nbct'][$j][$i]=$current_eleve_nbct;

							if((isset($place_eleve_classe[$i]))&&($place_eleve_classe[$i]!="")) {
								$tab_bulletin[$id_classe][$periode_num]['nbct'][$j][$i]=$current_eleve_nbct;
							}

							//++++++++++++++++++++++++
							// Modif d'apr�s F.Boisson
							// Pour remplacer la chaine de caract�res @@Notes par une insertion des notes dans l'appr�ciation du bulletin.
							// on prend les notes dans $string_notes
							$string_notes='';
							if ($result_nbct ) {
								while ($snnote =  mysql_fetch_assoc($result_nbct)) {
									if ($string_notes != '') $string_notes .= ", ";
									$string_notes .= $snnote['note'];
									if(getSettingValue("note_autre_que_sur_referentiel")=="V" || $snnote['note_sur']!=getSettingValue("referentiel_note")) {
										$string_notes .= "/".$snnote['note_sur'];
									}
								}
							}
							$current_eleve_app[$j][$i]=str_replace('@@Notes', $string_notes,$current_eleve_app[$j][$i]);
							//++++++++++++++++++++++++

							//$tab_bulletin[$id_classe][$periode_num]['groupe'][$j][$i]['app']=$current_eleve_app[$j][$i];

							// Structure plus proche des autres donn�es:
							$tab_bulletin[$id_classe][$periode_num]['app'][$j][$i]=$current_eleve_app[$j][$i];

						}
					}

					// Avis du conseil de classe
					$sql="SELECT avis FROM avis_conseil_classe WHERE login='".$current_eleve_login[$i]."' AND periode='$periode_num';";
					$res_avis=mysql_query($sql);
					//echo "$sql<br />";
					if(mysql_num_rows($res_avis)>0) {
						$lig_avis=mysql_fetch_object($res_avis);
						$tab_bulletin[$id_classe][$periode_num]['avis'][$i]=$lig_avis->avis;
						//echo $lig_avis->avis;
					}
					else {
						//$tab_bulletin[$id_classe][$periode_num]['avis'][$i]="-";
						$tab_bulletin[$id_classe][$periode_num]['avis'][$i]="";
					}

					// On affecte la partie �l�ve $tab_ele dans $tab_bulletin
					$tab_bulletin[$id_classe][$periode_num]['eleve'][$i]=$tab_ele;
				}
			}
		}
	}



	//========================================================================
	// A CE STADE LE TABLEAU $tab_bulletin EST RENSEIGN�
	// PLUS AUCUNE REQUETE NE DEVRAIT ETRE NECESSAIRE
	// OU ALORS IL FAUDRAIT LES EFFECTUER AU-DESSUS ET COMPLETER $tab_bulletin
	//
	// IL Y AURA A RENSEIGNER $tab_bulletin[$id_classe][$periode_num]['modele_pdf']
	// SI ON FAIT UNE IMPRESSION DE BULLETIN PDF, POUR NE PAS REFAIRE LES REQUETES
	// POUR CHAQUE ELEVE.
	//========================================================================

	if($mode_bulletin=="html") {
		echo "<script type='text/javascript'>
	document.getElementById('td_info').innerHTML='Affichage';
</script>\n";
	}

	if($mode_bulletin=="pdf") {
		// d�finition d'une variable
		$hauteur_pris = 0;

		/*****************************************
		* d�but de la g�n�ration du fichier PDF  *
		* ****************************************/
		send_file_download_headers('application/pdf','bulletin.pdf');
		//cr�ation du PDF en mode Portrait, unit�e de mesure en mm, de taille A4
		$pdf=new bul_PDF('p', 'mm', 'A4');
		$nb_eleve_aff = 1;
		$categorie_passe = '';
		$categorie_passe_count = 0;
		$pdf->SetCreator($gepiSchoolName);
		$pdf->SetAuthor($gepiSchoolName);
		$pdf->SetKeywords('');
		$pdf->SetSubject('Bulletin');
		$pdf->SetTitle('Bulletin');
		$pdf->SetDisplayMode('fullwidth', 'single');
		$pdf->SetCompression(TRUE);
		$pdf->SetAutoPageBreak(TRUE, 5);

		$responsable_place = 0;
	}

	$compteur_bulletins=0;
	for($loop_classe=0;$loop_classe<count($tab_id_classe);$loop_classe++) {
		$id_classe=$tab_id_classe[$loop_classe];
		$classe=get_class_from_id($id_classe);

		if($mode_bulletin=="html") {
			echo "<script type='text/javascript'>
	document.getElementById('td_classe').innerHTML='".$classe."';
</script>\n";
		}

		for($loop_periode_num=0;$loop_periode_num<count($tab_periode_num);$loop_periode_num++) {

			$periode_num=$tab_periode_num[$loop_periode_num];

			//==============================
			if($mode_bulletin=="html") {
				$motif="Classe_".$id_classe."_".$periode_num;
				decompte_debug($motif,"$motif avant");
				flush();
				echo "<script type='text/javascript'>
	document.getElementById('td_periode').innerHTML='".$periode_num."';
</script>\n";
			}
			//==============================

			if($mode_bulletin=="html") {
				echo "<div class='noprint' style='background-color:white; border: 1px solid red;'>\n";
				echo "<h2>Classe de ".$classe."</h2>\n";
				echo "<p><b>P�riode $periode_num</b></p>\n";

				echo "<p>Effectif de la classe: ".$tab_bulletin[$id_classe][$periode_num]['eff_classe']."</p>\n";
				echo "</div>\n";
			}

			//======================================
			// Pour le tri par �tablissement d'origine
			unset($tmp_tab);
			unset($rg);
			//$tri_par_etab_orig="y";
			if($tri_par_etab_orig=='y') {
				for($k=0;$k<count($tab_bulletin[$id_classe][$periode_num]['eleve']);$k++) {
					$rg[$k]=$k;
					$tmp_tab[$k]=$tab_bulletin[$id_classe][$periode_num]['eleve'][$k]['etab_id'];
				}
				array_multisort ($tmp_tab, SORT_DESC, SORT_NUMERIC, $rg, SORT_ASC, SORT_NUMERIC);
			}
			//======================================

			//$compteur=0;
			//for($i=0;$i<count($tab_bulletin[$id_classe][$periode_num]['eleve']);$i++) {
			for($i=0;$i<$tab_bulletin[$id_classe][$periode_num]['eff_classe'];$i++) {
				if($tri_par_etab_orig=='n') {$rg[$i]=$i;}

				if(isset($tab_bulletin[$id_classe][$periode_num]['selection_eleves'])) {
					//if(isset($tab_bulletin[$id_classe][$periode_num]['eleve'][$i]['login'])) {
					if(isset($tab_bulletin[$id_classe][$periode_num]['eleve'][$rg[$i]]['login'])) {

						//if (in_array($tab_bulletin[$id_classe][$periode_num]['eleve'][$i]['login'],$tab_bulletin[$id_classe][$periode_num]['selection_eleves'])) {
						if (in_array($tab_bulletin[$id_classe][$periode_num]['eleve'][$rg[$i]]['login'],$tab_bulletin[$id_classe][$periode_num]['selection_eleves'])) {

							// ++++++++++++++++++++++++++++++++++++++
							// ++++++++++++++++++++++++++++++++++++++
							// AJOUTER UN TEST: L'�l�ve fait-il bien partie de la classe?
							//                  Inutile: la liste $current_eleve_login est obtenue de calcul_moy_gen.inc.php
							//                  Pas d'injection/intervention possible.
							//                  Le test sur l'acc�s � la classe plus haut (*) doit suffire.
							//                  On pourrait injecter un login dans la s�lection d'�l�ve, mais pas dans $current_eleve_login
							//                  Et on test seulement si $current_eleve_login[$i] est bien dans la s�lection (pas le contraire)
							//                  (*) dans cette section tout de m�me.
							// ++++++++++++++++++++++++++++++++++++++
							// ++++++++++++++++++++++++++++++++++++++

							if($mode_bulletin=="html") {
								echo "<script type='text/javascript'>
	document.getElementById('td_ele').innerHTML='".$tab_bulletin[$id_classe][$periode_num]['eleve'][$rg[$i]]['login']."';
</script>\n";
							}


							if($mode_bulletin=="html") {
								//$motif="Bulletin_eleve".$id_classe."_".$periode_num."_".$i;
								//decompte_debug($motif,"$motif �l�ve $i avant");
								$motif="Bulletin_eleve".$id_classe."_".$periode_num."_".$rg[$i];
								decompte_debug($motif,"$motif �l�ve $rg[$i] avant");
								flush();

								// Saut de page si jamais ce n'est pas le premier bulletin
								if($compteur_bulletins>0) {echo "<p class='saut'>&nbsp;</p>\n";}

								// G�n�ration du bulletin de l'�l�ve
								//bulletin_html($tab_bulletin[$id_classe][$periode_num],$i);
								//bulletin_html($tab_bulletin[$id_classe][$periode_num],$i,$tab_releve[$id_classe][$periode_num]);
								bulletin_html($tab_bulletin[$id_classe][$periode_num],$rg[$i],$tab_releve[$id_classe][$periode_num]);

								//$motif="Bulletin_eleve".$id_classe."_".$periode_num."_".$i;
								//decompte_debug($motif,"$motif �l�ve $i apr�s");
								$motif="Bulletin_eleve".$id_classe."_".$periode_num."_".$rg[$i];
								decompte_debug($motif,"$motif �l�ve $rg[$i] apr�s");
								flush();
							}
							else {
								//bulletin_pdf($tab_bulletin[$id_classe][$periode_num],$i,$tab_releve[$id_classe][$periode_num]);
								bulletin_pdf($tab_bulletin[$id_classe][$periode_num],$rg[$i],$tab_releve[$id_classe][$periode_num]);
							}


							//==============================================================================================
							// PAR LA SUITE, ON POURRA INSERER ICI, SI L'OPTION EST COCHEE, LE RELEVE DE NOTES DE LA PERIODE
							//==============================================================================================

							if($mode_bulletin=="html") {
								echo "<div class='espacement_bulletins'><div align='center'>Espacement (non imprim�) entre les bulletins</div></div>\n";
							}

							$compteur_bulletins++;

							if($mode_bulletin=="html") {
								flush();
							}
						}
					}
				}
			}

			//==============================
			if($mode_bulletin=="html") {
				$motif="Classe_".$id_classe."_".$periode_num;
				decompte_debug($motif,"$motif apr�s");
				flush();
			}
			//==============================

		}
	}

	/*
	echo "<style type='text/css'>
	@media screen{
		.espacement_bulletins {
			width: 100%;
			height: 50px;
			border:1px solid red;
			background-color: white;
		}
	}
	@media print{
		.espacement_bulletins {
			display:none;
		}

		#remarques_bas_de_page {
			display:none;
		}

		.alerte_erreur {
			display:none;
		}
	}
</style>\n";
*/

	if($mode_bulletin=="html") {
		echo "<script type='text/javascript'>
	document.getElementById('infodiv').style.display='none';

    var aElm=document.body.getElementsByTagName('*');
    for(var i=0; i<aElm.length; i++) {
        if(aElm[i].className=='espacement_bulletins') {
            //do something
            //aElm[i].style.color='lime';
            aElm[i].style.display='none';
        }
    }

</script>\n";

	}
}

//==============================
if($mode_bulletin=="html") {
	$motif="Duree_totale";
	//decompte_debug($motif,"$motif apr�s");
	decompte_debug($motif,"$motif");
	flush();
}
//==============================

if((!isset($mode_bulletin))||($mode_bulletin!="pdf")) {
	echo "<div id='remarques_bas_de_page'>
<p><br /></p>
<p>A REVOIR:</p>
<ul>
<li>Les bulletins HTML utilisent les infos display_rang, display_coef,... de la table 'classes'.<br />
Les bulletins PDF utilisent plut�t les infos de la table 'modele_bulletin' il me semble.<br />
Il faudrait peut-�tre revoir le dispositif pour adopter la m�me strat�gie.<br />
On a aussi ajout� des champs dans la table 'classes' pour les relev�s de notes,... faut-il envisager une autre structure?</li>
</ul>
</div>\n";

	require("../lib/footer.inc.php");
}
elseif((isset($mode_bulletin))&&($mode_bulletin=="pdf")) {
	//fermeture du fichier pdf et lecture dans le navigateur 'nom', 'I/D'
	$nom_bulletin = 'bulletin_'.$nom_bulletin.'.pdf';
	$pdf->Output($nom_bulletin,'I');
}

?>
