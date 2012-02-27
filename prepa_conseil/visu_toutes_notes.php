<?php
/*
* $Id$
*
* Copyright 2001, 2012 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Stephane Boireau
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
if (getSettingValue("active_module_absence")=='2'){
    require_once("../lib/initialisationsPropel.inc.php");
}

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
	header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
	die();
} else if ($resultat_session == '0') {
	header("Location: ../logout.php?auto=1");
	die();
}

if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

//Initialisation
//$id_classe = isset($_POST['id_classe']) ? $_POST['id_classe'] :  NULL;
//$num_periode = isset($_POST['num_periode']) ? $_POST['num_periode'] :  NULL;
// Modifi� pour pouvoir r�cup�rer ces variables en GET pour les CSV
$id_classe = isset($_POST['id_classe']) ? $_POST['id_classe'] : (isset($_GET['id_classe']) ? $_GET['id_classe'] : NULL);
//$num_periode = isset($_POST['num_periode']) ? $_POST['num_periode'] : (isset($_GET['num_periode']) ? $_GET['num_periode'] : NULL);
$num_periode = isset($_POST['num_periode']) ? $_POST['num_periode'] : (isset($_GET['num_periode']) ? $_GET['num_periode'] : "1");

$utiliser_coef_perso=isset($_POST['utiliser_coef_perso']) ? $_POST['utiliser_coef_perso'] : (isset($_GET['utiliser_coef_perso']) ? $_GET['utiliser_coef_perso'] : "n");
$coef_perso=isset($_POST['coef_perso']) ? $_POST['coef_perso'] : (isset($_GET['coef_perso']) ? $_GET['coef_perso'] : NULL);

//$note_sup_10=isset($_POST['note_sup_10']) ? $_POST['note_sup_10'] : (isset($_GET['note_sup_10']) ? $_GET['note_sup_10'] : NULL);
//$mode_moy_perso=isset($_POST['mode_moy_perso']) ? $_POST['mode_moy_perso'] : (isset($_GET['mode_moy_perso']) ? $_GET['mode_moy_perso'] : NULL);
$mode_moy_perso=isset($_POST['mode_moy_perso']) ? $_POST['mode_moy_perso'] : (isset($_GET['mode_moy_perso']) ? $_GET['mode_moy_perso'] : array());

if ($num_periode=="annee") {
	$referent="annee";
} else {
	$referent="une_periode";
}

// On filtre au niveau s�curit� pour s'assurer qu'un prof n'est pas en train de chercher
// � visualiser des donn�es pour lesquelles il n'est pas autoris�

if (isset($id_classe)) {
	// On regarde si le type est correct :
	if (!is_numeric($id_classe)) {
		tentative_intrusion("3", "Changement de la valeur de id_classe pour un type non num�rique, en changeant la valeur d'un champ 'hidden' d'un formulaire.");
		echo "Erreur.";
		require ("../lib/footer.inc.php");
		die();
	}
	// On teste si le professeur a le droit d'acc�der � cette classe
	if ($_SESSION['statut'] == "professeur" AND getSettingValue("GepiAccesMoyennesProfToutesClasses") != "yes") {
		$test = mysql_num_rows(mysql_query("SELECT jgc.* FROM j_groupes_classes jgc, j_groupes_professeurs jgp WHERE (jgp.login='".$_SESSION['login']."' AND jgc.id_groupe = jgp.id_groupe AND jgc.id_classe = '".$id_classe."')"));
		if ($test == "0") {
			tentative_intrusion("3", "Tentative d'acc�s par un prof � une classe dans laquelle il n'enseigne pas, sans en avoir l'autorisation. Tentative avanc�e : changement des valeurs de champs de type 'hidden' du formulaire.");
			echo "Vous ne pouvez pas acc�der � cette classe car vous n'y �tes pas professeur !";
			require ("../lib/footer.inc.php");
			die();
		}
	}
}


function my_echo($texte) {
	$debug=0;
	if($debug!=0) {
		echo $texte;
	}
}


$larg_tab = isset($_POST['larg_tab']) ? $_POST['larg_tab'] :  NULL;
$bord = isset($_POST['bord']) ? $_POST['bord'] :  NULL;

//$aff_abs = isset($_POST['aff_abs']) ? $_POST['aff_abs'] :  NULL;
//$aff_reg = isset($_POST['aff_reg']) ? $_POST['aff_reg'] :  NULL;
//$aff_doub = isset($_POST['aff_doub']) ? $_POST['aff_doub'] :  NULL;
//$aff_rang = isset($_POST['aff_rang']) ? $_POST['aff_rang'] :  NULL;

// Modifi� pour pouvoir r�cup�rer ces variables en GET pour les CSV
$aff_abs = isset($_POST['aff_abs']) ? $_POST['aff_abs'] : (isset($_GET['aff_abs']) ? $_GET['aff_abs'] : NULL);
$aff_reg = isset($_POST['aff_reg']) ? $_POST['aff_reg'] : (isset($_GET['aff_reg']) ? $_GET['aff_reg'] : NULL);
$aff_doub = isset($_POST['aff_doub']) ? $_POST['aff_doub'] : (isset($_GET['aff_doub']) ? $_GET['aff_doub'] : NULL);
$aff_rang = isset($_POST['aff_rang']) ? $_POST['aff_rang'] : (isset($_GET['aff_rang']) ? $_GET['aff_rang'] : NULL);

//echo "\$aff_rang=$aff_rang<br />";

//============================
//$aff_date_naiss = isset($_POST['aff_date_naiss']) ? $_POST['aff_date_naiss'] :  NULL;
$aff_date_naiss = isset($_POST['aff_date_naiss']) ? $_POST['aff_date_naiss'] : (isset($_GET['aff_date_naiss']) ? $_GET['aff_date_naiss'] : NULL);
//============================

$couleur_alterne = isset($_POST['couleur_alterne']) ? $_POST['couleur_alterne'] :  NULL;

//================================
if(file_exists("../visualisation/draw_graphe.php")){
	$temoin_graphe="oui";
}
else{
	$temoin_graphe="non";
}
//================================

//============================
// Colorisation des r�sultats
$vtn_couleur_texte=isset($_POST['vtn_couleur_texte']) ? $_POST['vtn_couleur_texte'] : array();
$vtn_couleur_cellule=isset($_POST['vtn_couleur_cellule']) ? $_POST['vtn_couleur_cellule'] : array();
$vtn_borne_couleur=isset($_POST['vtn_borne_couleur']) ? $_POST['vtn_borne_couleur'] : array();
$vtn_coloriser_resultats=isset($_POST['vtn_coloriser_resultats']) ? $_POST['vtn_coloriser_resultats'] : "n";
/*
for($i=0;$i<count($vtn_borne_couleur);$i++) {
echo "\$vtn_borne_couleur[$i]=$vtn_borne_couleur[$i]<br />\n";
}
*/
//============================

//============================
$avec_moy_gen_periodes_precedentes = isset($_POST['avec_moy_gen_periodes_precedentes']) ? $_POST['avec_moy_gen_periodes_precedentes'] :  (isset($_GET['avec_moy_gen_periodes_precedentes']) ? $_GET['avec_moy_gen_periodes_precedentes'] :  NULL);
//============================

include "../lib/periodes.inc.php";

// On appelle les �l�ves
if ($_SESSION['statut'] == "professeur" AND getSettingValue("GepiAccesMoyennesProfTousEleves") != "yes" AND getSettingValue("GepiAccesMoyennesProfToutesClasses") != "yes") {
	// On ne s�lectionne que les �l�ves que le professeur a en cours
	if ($referent=="une_periode")
		// Calcul sur une seule p�riode
		$appel_donnees_eleves = mysql_query("SELECT DISTINCT e.* " .
				"FROM eleves e, j_eleves_classes jec, j_eleves_groupes jeg, j_groupes_professeurs jgp " .
				"WHERE (" .
				"jec.id_classe='$id_classe' AND " .
				"e.login = jeg.login AND " .
				"jeg.login = jec.login AND " .
				"jeg.id_groupe = jgp.id_groupe AND " .
				"jgp.login = '".$_SESSION['login']."' AND " .
				"jec.periode = '$num_periode' AND " .
				"jeg.periode = '$num_periode') " .
				"ORDER BY e.nom,e.prenom");
	else {
		// Calcul sur l'ann�e
		$appel_donnees_eleves = mysql_query("SELECT DISTINCT e.* " .
				"FROM eleves e, j_eleves_classes jec, j_eleves_groupes jeg, j_groupes_professeurs jgp " .
				"WHERE (" .
				"jec.id_classe='$id_classe' AND " .
				"e.login = jeg.login AND " .
				"jeg.login = jec.login AND " .
				"jeg.id_groupe = jgp.id_groupe AND " .
				"jgp.login = '".$_SESSION['login']."') " .
				"ORDER BY e.nom,e.prenom");
	}
} else {
	if ($referent=="une_periode")
		// Calcul sur une seule p�riode
		$appel_donnees_eleves = mysql_query("SELECT DISTINCT e.* FROM eleves e, j_eleves_classes j WHERE (j.id_classe='$id_classe' AND j.login = e.login AND j.periode='$num_periode') ORDER BY nom,prenom");
	else {
		// Calcul sur l'ann�e
		$appel_donnees_eleves = mysql_query("SELECT DISTINCT e.* FROM eleves e, j_eleves_classes j WHERE (j.id_classe='$id_classe' AND j.login = e.login) ORDER BY nom,prenom");
	}
}

$nb_lignes_eleves = mysql_num_rows($appel_donnees_eleves);
$nb_lignes_tableau = $nb_lignes_eleves;

//==============================
// Initialisation
// Conserv� pour le mode annee
$moy_classe_point = 0;
$moy_classe_effectif = 0;
$moy_classe_min = 20;
$moy_classe_max = 0;
$moy_cat_classe_point = array();
$moy_cat_classe_effectif = array();
$moy_cat_classe_min = array();
$moy_cat_classe_max = array();
//==============================


// =====================================
// AJOUT: boireaus
$largeur_graphe=700;
$hauteur_graphe=600;
$taille_police=3;
$epaisseur_traits=2;
$titre="Graphe";
$graph_title=$titre;
//$v_legend2="moyclasse";
$compteur=0;
$nb_series=2;

if(getSettingValue('graphe_largeur_graphe')){
	$largeur_graphe=getSettingValue('graphe_largeur_graphe');
}
else{
	$largeur_graphe=600;
}

if(getSettingValue('graphe_hauteur_graphe')){
	$hauteur_graphe=getSettingValue('graphe_hauteur_graphe');
}
else{
	$hauteur_graphe=400;
}

if(getSettingValue('graphe_taille_police')){
	$taille_police=getSettingValue('graphe_taille_police');
}
else{
	$taille_police=2;
}

if(getSettingValue('graphe_epaisseur_traits')){
	$epaisseur_traits=getSettingValue('graphe_epaisseur_traits');
}
else{
	$epaisseur_traits=2;
}

if(getSettingValue('graphe_temoin_image_escalier')){
	$temoin_image_escalier=getSettingValue('graphe_temoin_image_escalier');
}
else{
	$temoin_image_escalier="non";
}

if(getSettingValue('graphe_tronquer_nom_court')){
	$tronquer_nom_court=getSettingValue('graphe_tronquer_nom_court');
}
else{
	$tronquer_nom_court=0;
}

// =====================================

// On teste la pr�sence d'au moins un coeff pour afficher la colonne des coef
$sql="SELECT coef FROM j_groupes_classes WHERE (id_classe='".$id_classe."' and coef > 0);";
//echo "$sql<br />";
//$test_coef=mysql_num_rows(mysql_query($sql));
$nb_coef_non_nuls=mysql_num_rows(mysql_query($sql));
$ligne_supl = 0;
if ($nb_coef_non_nuls!=0) {$ligne_supl = 1;}
//echo "\$test_coef=$test_coef<br />";
//echo "\$ligne_supl=$ligne_supl<br />";
// Dans calcul_moy_gen.inc.php, $test_coef est le r�sultat d'une requ�te mysql_query()
// On met en r�serve le $test_coef correspondant au nombre de coef non nuls
//$test_coef_avant_calcul_moy_gen=$test_coef;

$temoin_note_sup10="n";
$temoin_note_bonus="n";
if($utiliser_coef_perso=='y') {
	/*
	if(isset($note_sup_10)) {
		$ligne_supl++;
		$temoin_note_sup10="y";
	}
	*/
	$nb_note_sup_10=0;
	$nb_note_bonus=0;
	foreach($mode_moy_perso as $tmp_id_groupe => $tmp_mode_moy) {
		if($mode_moy_perso[$tmp_id_groupe]=='sup10') {
			$temoin_note_sup10="y";
			$nb_note_sup_10++;
		}
		if($mode_moy_perso[$tmp_id_groupe]=='bonus') {
			$temoin_note_bonus="y";
			$nb_note_bonus++;
		}
	}
}
else {
	$sql="SELECT 1=1 FROM j_groupes_classes jgc WHERE jgc.id_classe='".$id_classe."' AND jgc.mode_moy='sup10';";
	$test_note_sup10=mysql_query($sql);
	$nb_note_sup_10=mysql_num_rows($test_note_sup10);
	if($nb_note_sup_10>0) {
		//$ligne_supl++;
		$temoin_note_sup10="y";
	}

	$sql="SELECT 1=1 FROM j_groupes_classes jgc WHERE jgc.id_classe='".$id_classe."' AND jgc.mode_moy='bonus';";
	$test_note_bonus=mysql_query($sql);
	$nb_note_bonus=mysql_num_rows($test_note_bonus);
	if($nb_note_bonus>0) {
		//$ligne_supl++;
		$temoin_note_bonus="y";
	}
}

if(($temoin_note_sup10=="y")||($temoin_note_bonus=="y")) {
	$ligne_supl++;
}

// On regarde si on doit afficher les moyennes des cat�gories de mati�res
$affiche_categories = sql_query1("SELECT display_mat_cat FROM classes WHERE id='".$id_classe."'");
if ($affiche_categories == "y") {
	$affiche_categories = true;
} else {
	$affiche_categories = false;
}

// Si le rang des �l�ves est demand�, on met � jour le champ rang de la table matieres_notes
if (($aff_rang) and ($referent=="une_periode")) {
	$periode_num=$num_periode;

	// La variable $test_coef est r�clam�e par calcul_rang.inc.php
	if(!isset($test_coef)) {
		$test_coef=$nb_coef_non_nuls;
	}

	include "../lib/calcul_rang.inc.php";
}

/*
// On regarde si on doit afficher les moyennes des cat�gories de mati�res
$affiche_categories = sql_query1("SELECT display_mat_cat FROM classes WHERE id='".$id_classe."'");
if ($affiche_categories == "y") {
	$affiche_categories = true;
} else {
	$affiche_categories = false;
}
*/

if ($affiche_categories) {
	$get_cat = mysql_query("SELECT id FROM matieres_categories");
	$categories = array();
	while ($row = mysql_fetch_array($get_cat, MYSQL_ASSOC)) {
		$categories[] = $row["id"];
		$moy_cat_classe_point[$row["id"]] = 0;
		$moy_cat_classe_effectif[$row["id"]] = 0;
		$moy_cat_classe_min[$row["id"]] = 20;
		$moy_cat_classe_max[$row["id"]] = 0;
	}

	$cat_names = array();
	foreach ($categories as $cat_id) {
		$cat_names[$cat_id] = html_entity_decode_all_version(mysql_result(mysql_query("SELECT nom_court FROM matieres_categories WHERE id = '" . $cat_id . "'"), 0));
	}
}

//$avec_moy_gen_periodes_precedentes="y";

// $nb_periode vaut 4 s'il y a 3 p�riodes
//echo "\$nb_periode=$nb_periode<br />";
if($referent=="une_periode") {
	if(!isset($avec_moy_gen_periodes_precedentes)) {
		$p=$num_periode;
		// Pour faire un tour dans la boucle seulement:
		$periode_limit=$p+1;
	}
	else {
		$p=1;
		// Pour faire un tour dans la boucle seulement:
		$periode_limit=$num_periode+1;
	}
}
else {
	$p=1;
	// Pour aller jusqu'� la derni�re p�riode
	$periode_limit=$nb_periode;
	// $nb_periode initialis� par periodes.inc.php vaut 4 dans le cas o� il y a 3 trimestres
}

$coefficients_a_1="non";
$affiche_graph="n";

while ($p < $periode_limit) {
	$periode_num=$p;
	include "../lib/calcul_moy_gen.inc.php";

	// Dans calcul_moy_gen.inc.php, les indices $i et $j sont:
	// $i: �l�ve
	// $j: groupe
	$tab_moy['periodes'][$p]=array();
	$tab_moy['periodes'][$p]['tab_login_indice']=$tab_login_indice;         // [$login_eleve]
	$tab_moy['periodes'][$p]['moy_gen_eleve']=$moy_gen_eleve;               // [$i]
	$tab_moy['periodes'][$p]['moy_gen_eleve1']=$moy_gen_eleve1;             // [$i]
	//$tab_moy['periodes'][$p]['moy_gen_classe1']=$moy_gen_classe1;           // [$i]
	$tab_moy['periodes'][$p]['moy_generale_classe']=$moy_generale_classe;
	$tab_moy['periodes'][$p]['moy_generale_classe1']=$moy_generale_classe1;
	$tab_moy['periodes'][$p]['moy_max_classe']=$moy_max_classe;
	$tab_moy['periodes'][$p]['moy_min_classe']=$moy_min_classe;

	// Il faudrait r�cup�rer/stocker les cat�gories?
	$tab_moy['periodes'][$p]['moy_cat_eleve']=$moy_cat_eleve;               // [$i][$cat]
	$tab_moy['periodes'][$p]['moy_cat_classe']=$moy_cat_classe;             // [$i][$cat]
	$tab_moy['periodes'][$p]['moy_cat_min']=$moy_cat_min;                   // [$i][$cat]
	$tab_moy['periodes'][$p]['moy_cat_max']=$moy_cat_max;                   // [$i][$cat]

	$tab_moy['periodes'][$p]['quartile1_classe_gen']=$quartile1_classe_gen;
	$tab_moy['periodes'][$p]['quartile2_classe_gen']=$quartile2_classe_gen;
	$tab_moy['periodes'][$p]['quartile3_classe_gen']=$quartile3_classe_gen;
	$tab_moy['periodes'][$p]['quartile4_classe_gen']=$quartile4_classe_gen;
	$tab_moy['periodes'][$p]['quartile5_classe_gen']=$quartile5_classe_gen;
	$tab_moy['periodes'][$p]['quartile6_classe_gen']=$quartile6_classe_gen;
	$tab_moy['periodes'][$p]['place_eleve_classe']=$place_eleve_classe;

	$tab_moy['periodes'][$p]['current_eleve_login']=$current_eleve_login;   // [$i]
	//$tab_moy['periodes'][$p]['current_group']=$current_group;
	if(($p==1)||((isset($num_periode))&&($p==$num_periode))) {
		$tab_moy['current_group']=$current_group;                                     // [$j]
	}
	$tab_moy['periodes'][$p]['current_eleve_note']=$current_eleve_note;     // [$j][$i]
	$tab_moy['periodes'][$p]['current_eleve_statut']=$current_eleve_statut; // [$j][$i]
	//$tab_moy['periodes'][$p]['current_group']=$current_group;
	$tab_moy['periodes'][$p]['current_coef']=$current_coef;                 // [$j]
	$tab_moy['periodes'][$p]['current_classe_matiere_moyenne']=$current_classe_matiere_moyenne; // [$j]

	$tab_moy['periodes'][$p]['current_coef_eleve']=$current_coef_eleve;     // [$i][$j] ATTENTION
	$tab_moy['periodes'][$p]['moy_min_classe_grp']=$moy_min_classe_grp;     // [$j]
	$tab_moy['periodes'][$p]['moy_max_classe_grp']=$moy_max_classe_grp;     // [$j]
	if(isset($current_eleve_rang)) {
		// $current_eleve_rang n'est pas renseign� si $affiche_rang='n'
		$tab_moy['periodes'][$p]['current_eleve_rang']=$current_eleve_rang; // [$j][$i]
	}
	$tab_moy['periodes'][$p]['quartile1_grp']=$quartile1_grp;               // [$j]
	$tab_moy['periodes'][$p]['quartile2_grp']=$quartile2_grp;               // [$j]
	$tab_moy['periodes'][$p]['quartile3_grp']=$quartile3_grp;               // [$j]
	$tab_moy['periodes'][$p]['quartile4_grp']=$quartile4_grp;               // [$j]
	$tab_moy['periodes'][$p]['quartile5_grp']=$quartile5_grp;               // [$j]
	$tab_moy['periodes'][$p]['quartile6_grp']=$quartile6_grp;               // [$j]
	$tab_moy['periodes'][$p]['place_eleve_grp']=$place_eleve_grp;           // [$j][$i]

	$tab_moy['periodes'][$p]['current_group_effectif_avec_note']=$current_group_effectif_avec_note; // [$j]

	/*
	echo "<pre>";
	echo print_r($tab_moy);
	echo "</pre>";
	*/
	$p++;
}

$tab_moy['categories']['id']=$categories;
$tab_moy['categories']['nom_from_id']=$tab_noms_categories;
$tab_moy['categories']['id_from_nom']=$tab_id_categories;



/*
	// Calcul du nombre de mati�res � afficher
	if ($affiche_categories) {
		// On utilise les valeurs sp�cifi�es pour la classe en question
		//$groupeinfo = mysql_query("SELECT DISTINCT jgc.id_groupe, jgc.coef, jgc.categorie_id ".
		$sql="SELECT DISTINCT jgc.id_groupe, jgc.coef, jgc.categorie_id, jgc.mode_moy ".
		"FROM j_groupes_classes jgc, j_groupes_matieres jgm, j_matieres_categories_classes jmcc, matieres m " .
		"WHERE ( " .
		"jgc.categorie_id = jmcc.categorie_id AND " .
		"jgc.id_classe='".$id_classe."' AND " .
		"jgm.id_groupe=jgc.id_groupe AND " .
		"m.matiere = jgm.id_matiere" .
		") " .
		"ORDER BY jmcc.priority,jgc.priorite,m.nom_complet";
	} else {
		//$groupeinfo = mysql_query("SELECT DISTINCT jgc.id_groupe, jgc.coef
		$sql="SELECT DISTINCT jgc.id_groupe, jgc.coef, jgc.mode_moy
		FROM j_groupes_classes jgc, j_groupes_matieres jgm
		WHERE (
		jgc.id_classe='".$id_classe."' AND
		jgm.id_groupe=jgc.id_groupe
		)
		ORDER BY jgc.priorite,jgm.id_matiere";
	}
	//echo "$sql<br />";
	$groupeinfo=mysql_query($sql);
	$lignes_groupes = mysql_num_rows($groupeinfo);
*/

$lignes_groupes=count($tab_moy['current_group']);

// Pour d�bugger:
$lignes_debug="";
$ele_login_debug="debenaz_a";
$lignes_debug.="<p><b>$ele_login_debug</b><br />";

unset($current_eleve_login);

//
// d�finition des premi�res colonnes nom, r�gime, doublant, ...
//
$displayed_categories = array();
$j = 0;
while($j < $nb_lignes_tableau) {
	// colonne nom+pr�nom
	$current_eleve_login[$j] = mysql_result($appel_donnees_eleves, $j, "login");
	$col[1][$j+$ligne_supl] = @mysql_result($appel_donnees_eleves, $j, "nom")." ".@mysql_result($appel_donnees_eleves, $j, "prenom");
	$ind = 2;

	//echo "<p>\$current_eleve_login[$j]=$current_eleve_login[$j]<br />";
	//echo "\$col[1][$j+$ligne_supl]=".$col[1][$j+$ligne_supl]."<br />";
	//=======================================
	// colonne date de naissance
	if (($aff_date_naiss)&&($aff_date_naiss=='y')) {
		$tmpdate=mysql_result($appel_donnees_eleves, $j, "naissance");
		$tmptab=explode("-",$tmpdate);
		if(strlen($tmptab[0])==4){$tmptab[0]=substr($tmptab[0],2,2);}
		$col[$ind][$j+$ligne_supl]=$tmptab[2]."/".$tmptab[1]."/".$tmptab[0];
		$ind++;
	}
	//=======================================

	// colonne r�gime
	if ((($aff_reg)&&($aff_reg=='y')) or (($aff_doub)&&($aff_doub=='y'))) {
		$regime_doublant_eleve = mysql_query("SELECT * FROM j_eleves_regime WHERE login = '$current_eleve_login[$j]';");
	}
	if (($aff_reg)&&($aff_reg=='y')) {
		$col[$ind][$j+$ligne_supl] = @mysql_result($regime_doublant_eleve, 0, "regime");
		$ind++;
	}
	// colonne doublant
	if (($aff_doub)&&($aff_doub=='y')) {
		$col[$ind][$j+$ligne_supl] = @mysql_result($regime_doublant_eleve, 0, "doublant");
		$ind++;
	}
	// Colonne absence
	if (($aff_abs)&&($aff_abs=='y')) {
        if (getSettingValue("active_module_absence") != '2' || getSettingValue("abs2_import_manuel_bulletin") == 'y') {
            $abs_eleve = "NR";
            if ($referent == "une_periode")
                $abs_eleve = sql_query1("SELECT nb_absences FROM absences WHERE
			login = '$current_eleve_login[$j]' and
			periode = '" . $num_periode . "'
			");
            else {
                $abs_eleve = sql_query1("SELECT sum(nb_absences) FROM absences WHERE
			login = '$current_eleve_login[$j]'");
            }

            if ($abs_eleve == '-1')
                $abs_eleve = "NR";
            $col[$ind][$j + $ligne_supl] = $abs_eleve;
            $ind++;
        }else {
            $eleve = EleveQuery::create()->findOneByLogin($current_eleve_login[$j]);
            if ($eleve != null) {
                if ($referent == "une_periode") {
                    $abs_eleve = strval($eleve->getDemiJourneesAbsenceParPeriode($num_periode)->count());
                } else {
                    $date_jour = new DateTime('now');
                    $month = $date_jour->format('m');
                    if ($month > 7) {
                        $date_debut = new DateTime($date_jour->format('y') . '-09-01');
                        $date_fin = new DateTime($date_jour->format('y') + 1 . '-08-31');
                    } else {
                        $date_debut = new DateTime($date_jour->format('y') - 1 . '-09-01');
                        $date_fin = new DateTime($date_jour->format('y') . '-08-31');
                    }
                    $abs_eleve = strval($eleve->getDemiJourneesAbsence($date_debut, $date_fin)->count());
                }
            } else {
                $abs_eleve = "NR";
            }
            $col[$ind][$j + $ligne_supl] = $abs_eleve;
            $ind++;
        }
    }

	// Colonne rang
	if (($aff_rang) and ($aff_rang=='y') and ($referent=="une_periode")) {
		$rang = sql_query1("select rang from j_eleves_classes where (
			periode = '".$num_periode."' and
			id_classe = '".$id_classe."' and
			login = '".$current_eleve_login[$j]."' )
			");
		if (($rang == 0) or ($rang == -1)) $rang = "-";
		$col[$ind][$j+$ligne_supl] = $rang;
		//echo "\$col[$ind][$j+$ligne_supl])=".$col[$ind][$j+$ligne_supl]."<br />";
		$ind++;
	}

	$j++;
}

// Etiquettes des premi�res colonnes
//$ligne1[1] = "Nom ";
$ligne1[1] = "<a href='#' onclick=\"document.getElementById('col_tri').value='1';".
			"document.forms['formulaire_tri'].submit();\"".
			" style='text-decoration:none; color:black;'>".
			"Nom ".
			"</a>";
$ligne1_csv[1] = "Nom ";
//=========================
if (($aff_date_naiss)&&($aff_date_naiss=='y')) {
	$ligne1[] = "<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Date de naissance")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"date de naissance\" />";
	$ligne1_csv[] = "Date de naissance";
}
//=========================
if (($aff_reg)&&($aff_reg=='y')) {
	$ligne1[] = "<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("R�gime")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"r�gime\" />";
	$ligne1_csv[]="R�gime";
}
if(($aff_doub)&&($aff_doub=='y')) {
	$ligne1[] = "<img src=\"../lib/create_im_mat.php?texte=Redoublant&amp;width=22\" width=\"22\" border=\"0\" alt=\"doublant\" />";
	$ligne1_csv[]="Redoublant";
}
if (($aff_abs)&&($aff_abs=='y')) {
	$ligne1[] = "<a href='#' onclick=\"document.getElementById('col_tri').value='".(count($ligne1)+1)."';".
				"document.forms['formulaire_tri'].submit();\">".
				"<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("1/2 journ�es d'absence")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"1/2 journ�es d'absence\" />".
				"</a>";

	$ligne1_csv[]="1/2 journ�es d'absence";
}
if (($aff_rang) and ($aff_rang=='y') and ($referent=="une_periode")){
	$ligne1[] = "<a href='#' onclick=\"document.getElementById('col_tri').value='".(count($ligne1)+1)."';".
				"document.getElementById('sens_tri').value='inverse';".
				"document.forms['formulaire_tri'].submit();\">".
				"<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Rang de l'�l�ve")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"Rang de l'�l�ve\" />".
				"</a>";
	//"<img src=\"../lib/create_im_mat.php?texte=".rawurlencode(html_entity_decode("Rang de l&apos;&eacute;l&egrave;ve"))."&amp;width=22\" width=\"22\" border=\"0\" alt=\"Rang de l'�l�ve\" />".

	//echo count($ligne1);

	$ligne1_csv[]="Rang de l'�l�ve";
}

//echo "\$test_coef=$test_coef<br />";
// Dans calcul_moy_gen.inc.php, $test_coef est le r�sultat d'une requ�te mysql_query()
//$test_coef=$test_coef_avant_calcul_moy_gen;

if($nb_coef_non_nuls!=0) {$col[1][0] = "Coefficient";}

// Etiquettes des trois derni�res lignes
$col[1][$nb_lignes_tableau+$ligne_supl] = "Moyenne";
$col[1][$nb_lignes_tableau+1+$ligne_supl] = "Min";
$col[1][$nb_lignes_tableau+2+$ligne_supl] = "Max";
$ind = 2;
$nb_col = 1;
$k= 1;

//=========================
if (($aff_date_naiss)&&($aff_date_naiss=='y')) {
	if ($nb_coef_non_nuls != 0) $col[$ind][0] = "-";
	$col[$ind][$nb_lignes_tableau+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+1+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+2+$ligne_supl] = "-";
	$nb_col++;
	$k++;
	$ind++;
}
//=========================

if (($aff_reg)&&($aff_reg=='y')) {
	if ($nb_coef_non_nuls != 0) $col[$ind][0] = "-";
	$col[$ind][$nb_lignes_tableau+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+1+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+2+$ligne_supl] = "-";
	$nb_col++;
	$k++;
	$ind++;
}
if (($aff_doub)&&($aff_doub=='y')) {
	if ($nb_coef_non_nuls != 0) $col[$ind][0] = "-";
	$col[$ind][$nb_lignes_tableau+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+1+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+2+$ligne_supl] = "-";
	$nb_col++;
	$k++;
	$ind++;
}
if (($aff_abs)&&($aff_abs=='y')) {
	if ($nb_coef_non_nuls != 0) $col[$ind][0] = "-";
	$col[$ind][$nb_lignes_tableau+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+1+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+2+$ligne_supl] = "-";
	$nb_col++;
	$k++;
	$ind++;
}
if (($aff_rang) and ($aff_rang=='y') and ($referent=="une_periode")) {
	if ($nb_coef_non_nuls != 0) $col[$ind][0] = "-";
	$col[$ind][$nb_lignes_tableau+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+1+$ligne_supl] = "-";
	$col[$ind][$nb_lignes_tableau+2+$ligne_supl] = "-";
	$nb_col++;
	$k++;
	$ind++;
}

//=============================
// Utilis� pour referent=annee
// On initialise les totaux coef et notes pour les lignes �l�ves ($j)
$j = '0';
while($j < $nb_lignes_tableau) {
	//$total_coef[$j+$ligne_supl] = 0;
	$total_coef_classe[$j+$ligne_supl] = 0;
	$total_coef_eleve[$j+$ligne_supl] = 0;
	
	//$total_points[$j+$ligne_supl] = 0;
	//$total_points_classe[$j+$ligne_supl] = 0;
	$total_points_eleve[$j+$ligne_supl] = 0;
	
	//$total_cat_coef[$j+$ligne_supl] = array();
	//$total_cat_coef_classe[$j+$ligne_supl] = array();
	$total_cat_coef_eleve[$j+$ligne_supl] = array();
	
	//$total_cat_points[$j+$ligne_supl] = array();
	//$total_cat_points_classe[$j+$ligne_supl] = array();
	$total_cat_points_eleve[$j+$ligne_supl] = array();
	// =================================
	// MODIF: boireaus
	if ($affiche_categories) {
		foreach ($categories as $cat_id) {
			//$total_cat_coef[$j+$ligne_supl][$cat_id] = 0;
			//$total_cat_coef_classe[$j+$ligne_supl][$cat_id] = 0;
			$total_cat_coef_eleve[$j+$ligne_supl][$cat_id] = 0;
	
			//$total_cat_points[$j+$ligne_supl][$cat_id] = 0;
			//$total_cat_points_classe[$j+$ligne_supl][$cat_id] = 0;
			$total_cat_points_eleve[$j+$ligne_supl][$cat_id] = 0;
		}
	}
	// =================================
	$j++;
}
//=============================


//=============================
// AJOUT: boireaus
$chaine_matieres=array();
$chaine_moy_eleve1=array();
$chaine_moy_classe=array();
//$chaine_moy_classe="";
//=============================


//if((($utiliser_coef_perso=='y')&&(isset($note_sup_10)))||($temoin_note_sup10=='y')) {
//if($temoin_note_sup10=='y') {
if(($temoin_note_sup10=='y')||($temoin_note_bonus=='y')) {
	//$col[1][1]="Note&gt;10";
	//$col[1][1]="Note sup 10";
	$col[1][1]="Mode moy";
	//$col_csv[1][1]="Note sup 10";
    $col_sup=0;    
    if(isset($avec_moy_gen_periodes_precedentes)){
        $col_sup=$periode_num;
    }
	for($t=2;$t<=$nb_col+$lignes_groupes+$col_sup;$t++) {$col[$t][1]='-';}

	if ($affiche_categories) {
		foreach ($categories as $cat_id) {
			$col[$t][1]='-';
			$t++;
		}
	}
	// Pour la colonne moyenne g�n�rale
	if ($ligne_supl >= 1) {
		$col[$t][1]='-';
	}
}

//
// d�finition des colonnes mati�res
//
$i= '0';

$num_debut_colonnes_matieres=$nb_col+1;
$num_debut_lignes_eleves=$ligne_supl;
//echo "\$num_debut_colonnes_matieres=$num_debut_colonnes_matieres<br />";
//echo "\$num_debut_lignes_eleves=$num_debut_lignes_eleves<br />";

//pour calculer la moyenne annee de chaque matiere
$moyenne_annee_matiere=array();
$prev_cat_id = null;
while($i < $lignes_groupes) {
	//=============================
	// Utilis� pour referent=annee
	$moy_max = -1;
	$moy_min = 21;
	//=============================

	$nb_col++;
	$k++;

	foreach ($moyenne_annee_matiere as $tableau => $value) { unset($moyenne_annee_matiere[$tableau]);}

	//$var_group_id = mysql_result($groupeinfo, $i, "id_groupe");
	//$current_group = get_group($var_group_id);

	// On choisit une p�riode pour la r�cup des infos g�n�rales sur le groupe (id, coef,... bref des trucs qui ne d�pendent pas de la p�riode)
	if($referent=='une_periode') {$p=$num_periode;}
	else {$p=1;}

	$var_group_id=$tab_moy['current_group'][$i]['id'];
	$current_group=$tab_moy['current_group'][$i];

	// Coeff pour la classe
	//$current_coef = mysql_result($groupeinfo, $i, "coef");
	$current_coef=$tab_moy['periodes'][$p]['current_coef'][$i];

	// Mode de calcul sur la moyenne: standard (-) ou note sup�rieure � 10
	//$current_mode_moy = mysql_result($groupeinfo, $i, "mode_moy");
	$current_mode_moy=$current_group["classes"]["classes"][$id_classe]["mode_moy"];

	// A FAIRE: A l'affichage, il faudrait mettre 1.0(*) quand le coeff n'est pas 1.0 pour tous les �l�ves � cause de coeffs personnalis�s.
	if($utiliser_coef_perso=='y') {
		if(isset($coef_perso[$var_group_id])) {
			$current_coef=$coef_perso[$var_group_id];
			//$_SESSION['coef_perso_'.$current_group['matiere']['matiere']]=$coef_perso[$var_group_id];
			$_SESSION['coef_perso_'.$current_group['id']]=$coef_perso[$var_group_id];
		}

		// Les mode_moy_perso impos�s depuis index2.php:
		//if(isset($note_sup_10[$var_group_id])) {
		if((isset($mode_moy_perso[$var_group_id]))&&($mode_moy_perso[$var_group_id]=='sup10')) {
			//$col[$nb_col][1]='X';
			//$_SESSION['note_sup_10_'.$current_group['matiere']['matiere']]='y';
			$col[$nb_col][1]='sup10';
			//$_SESSION['mode_moy_'.$current_group['matiere']['matiere']]='sup10';
			$_SESSION['mode_moy_'.$current_group['id']]='sup10';
			$current_mode_moy='sup10';
		}
		elseif((isset($mode_moy_perso[$var_group_id]))&&($mode_moy_perso[$var_group_id]=='bonus')) {
			$col[$nb_col][1]='bonus';
			//$_SESSION['mode_moy_'.$current_group['matiere']['matiere']]='bonus';
			$_SESSION['mode_moy_'.$current_group['id']]='bonus';
			$current_mode_moy='bonus';
		}
		else {
			// On remet en standard
			//unset($_SESSION['mode_moy_'.$current_group['matiere']['matiere']]);
			//$_SESSION['mode_moy_'.$current_group['matiere']['matiere']]='-';
			$_SESSION['mode_moy_'.$current_group['id']]='-';
			$current_mode_moy='-';
		}

	}
	else {
		//if($current_mode_moy=='sup10') {$col[$nb_col][1]='X';}
		if($current_mode_moy=='sup10') {$col[$nb_col][1]='sup10';}
		if($current_mode_moy=='bonus') {$col[$nb_col][1]='bonus';}
	}


	if ($affiche_categories) {
	// On regarde si on change de cat�gorie de mati�re
		if ($current_group["classes"]["classes"][$id_classe]["categorie_id"] != $prev_cat_id) {
			$prev_cat_id = $current_group["classes"]["classes"][$id_classe]["categorie_id"];
		}
	}


	// Boucle sur la liste des �l�ves retourn�s par la requ�te
	$j = '0';
	while($j < $nb_lignes_tableau) {

		if($current_eleve_login[$j]==$ele_login_debug) {
			$lignes_debug.="<p>\$current_group['name']=".$current_group['name']."<br />";
			$lignes_debug.="\$current_coef=".$current_coef."<br />";
			$lignes_debug.="\$current_mode_moy=".$current_mode_moy."<br />";
		}

		// Valeur des lignes du bas avec moyenne classe/min/max pour le groupe $i... pour pouvoir mettre dans les liens draw_graphe.php
		if ($referent == "une_periode") {
			$moy_classe_tmp=$tab_moy['periodes'][$p]['current_classe_matiere_moyenne'][$i];
			$moy_min_classe_grp=$tab_moy['periodes'][$p]['moy_min_classe_grp'][$i];
			$moy_max_classe_grp=$tab_moy['periodes'][$p]['moy_max_classe_grp'][$i];
		}
		else {
			$call_moyenne = mysql_query("SELECT round(avg(note),1) moyenne FROM matieres_notes WHERE (statut ='' AND id_groupe='" . $current_group["id"] . "')");
			$moy_classe_tmp = @mysql_result($call_moyenne, 0, "moyenne");
		}


		/*
		// Coefficient personnalis� pour l'�l�ve?
		$sql="SELECT value FROM eleves_groupes_settings WHERE (" .
				"login = '".$current_eleve_login[$j]."' AND " .
				"id_groupe = '".$current_group["id"]."' AND " .
				"name = 'coef')";
		$test_coef_personnalise = mysql_query($sql);
		if (mysql_num_rows($test_coef_personnalise) > 0) {
			$coef_eleve = mysql_result($test_coef_personnalise, 0);
		} else {
			// Coefficient du groupe:
			$coef_eleve = $current_coef;
		}
		//$coef_eleve=number_format($coef_eleve,1, ',', ' ');
		*/
		/*
		// On recherche l'indice de l'�l�ve dans tab_moy pour la p�riode $p... qui vaut $num_periode pour $referent==une_periode et 1 sinon
		// Mais pour le coef, il doit �tre le m�me pour toutes les p�riodes
		// Par contre pour l'indice de l'�l�ve, cela peut changer
		// !!!!!!!!!!!!
		// A REVOIR !!!
		// !!!!!!!!!!!!
		$indice_j_ele=$tab_moy['periodes'][$p]['tab_login_indice'][$current_eleve_login[$j]];
		$coef_eleve=$tab_moy['periodes'][$p]['current_coef_eleve'][$indice_j_ele][$i];
		*/

		if ($referent == "une_periode") {
	
			if (!in_array($current_eleve_login[$j], $current_group["eleves"][$num_periode]["list"])) {
				// L'�l�ve ne suit pas cet enseignement
				$col[$k][$j+$ligne_supl] = "/";
			}
			else {
				// L'�l�ve suit cet enseignement

				// On r�cup�re l'indice de l'�l�ve dans $tab_moy pour la p�riode $num_periode
				//$indice_j_ele=$tab_moy['periodes'][$num_periode]['tab_login_indice'][$current_eleve_login[$j]];
				$indice_j_ele=$tab_moy['periodes'][$num_periode]['tab_login_indice'][strtoupper($current_eleve_login[$j])];
				$coef_eleve=$tab_moy['periodes'][$num_periode]['current_coef_eleve'][$indice_j_ele][$i];

				//echo "\$current_eleve_login[$j]=$current_eleve_login[$j]<br />";
				//echo "\$indice_j_ele=$indice_j_ele<br />";
				/*
				$current_eleve_note_query = mysql_query("SELECT * FROM matieres_notes WHERE (login='$current_eleve_login[$j]' AND id_groupe='" . $current_group["id"] . "' AND periode='$num_periode')");
				$current_eleve_statut = @mysql_result($current_eleve_note_query, 0, "statut");
				*/

				$current_eleve_statut=$tab_moy['periodes'][$num_periode]['current_eleve_statut'][$i][$indice_j_ele];
				$current_eleve_note=$tab_moy['periodes'][$num_periode]['current_eleve_note'][$i][$indice_j_ele];

				//echo "\$current_eleve_note=$current_eleve_note<br />";

				if ($current_eleve_statut != "") {
					$col[$k][$j+$ligne_supl] = $current_eleve_statut;
				}
				elseif($current_eleve_note=='-') {
					$col[$k][$j+$ligne_supl] = '-';
				}
				else {
					$temp=$current_eleve_note;
					//echo "\$current_eleve_note=$current_eleve_note<br />";
					if($temp != '')  {
						$col[$k][$j+$ligne_supl] = number_format($temp,1, ',', ' ');
						if ($current_coef > 0) {
							// ===================================
							// MODIF: boireaus
							//if (!in_array($prev_cat_id, $displayed_categories)) $displayed_categories[] = $prev_cat_id;
							if ($affiche_categories) {
								if (!in_array($prev_cat_id, $displayed_categories)) {$displayed_categories[] = $prev_cat_id;}
							}
							// ===================================
	
							/*
							// Coefficient personnalis� pour l'�l�ve?
							$sql="SELECT value FROM eleves_groupes_settings WHERE (" .
									"login = '".$current_eleve_login[$j]."' AND " .
									"id_groupe = '".$current_group["id"]."' AND " .
									"name = 'coef')";
							$test_coef_personnalise = mysql_query($sql);
							if (mysql_num_rows($test_coef_personnalise) > 0) {
								$coef_eleve = mysql_result($test_coef_personnalise, 0);
							} else {
								// Coefficient du groupe:
								$coef_eleve = $current_coef;
								if($utiliser_coef_perso=='y') {
									if ((isset($note_sup_10[$current_group["id"]]))&&($note_sup_10[$current_group["id"]]=='y')&&($temp<10)) {
										$coef_eleve=0;
										//echo $current_eleve_login[$j]." groupe n�".$current_group["id"]." (".$current_group["name"]."): coeff 0<br />";
									}
								}
								else {
									if(($current_mode_moy=='sup10')&&($temp<10)) {$coef_eleve=0;}
								}
							}
							//$coef_eleve=number_format($coef_eleve,1, ',', ' ');
	
							//$total_coef[$j+$ligne_supl] += $current_coef;
							$total_coef_eleve[$j+$ligne_supl] += $coef_eleve;
							$total_coef_classe[$j+$ligne_supl] += $current_coef;
							//$total_points[$j+$ligne_supl] += $current_coef*$temp;
							//$total_points[$j+$ligne_supl] += $coef_eleve*$temp;
							$total_points_eleve[$j+$ligne_supl] += $coef_eleve*$temp;
							$total_points_classe[$j+$ligne_supl] += $current_coef*$temp;
	
							if ($affiche_categories) {
								//$total_cat_coef[$j+$ligne_supl][$prev_cat_id] += $current_coef;
								$total_cat_coef_classe[$j+$ligne_supl][$prev_cat_id] += $current_coef;
								$total_cat_coef_eleve[$j+$ligne_supl][$prev_cat_id] += $coef_eleve;
	
								//$total_cat_points[$j+$ligne_supl][$prev_cat_id] += $current_coef*$temp;
								$total_cat_points_eleve[$j+$ligne_supl][$prev_cat_id] += $coef_eleve*$temp;
								$total_cat_points_classe[$j+$ligne_supl][$prev_cat_id] += $current_coef*$temp;
							}
							*/
						}
					} else {
						$col[$k][$j+$ligne_supl] = '-';
					}


					$sql="SELECT * FROM j_eleves_groupes WHERE id_groupe='".$current_group["id"]."' AND periode='$num_periode'";
					$test_eleve_grp=mysql_query($sql);
					if(mysql_num_rows($test_eleve_grp)>0){
						if(!isset($chaine_matieres[$j+$ligne_supl])){
						//if($chaine_matieres[$j+$ligne_supl]==""){
							$chaine_matieres[$j+$ligne_supl]=$current_group["matiere"]["matiere"];
							//$chaine_moy_eleve1[$j+$ligne_supl]=$lig_moy->note;
							$chaine_moy_eleve1[$j+$ligne_supl]=$col[$k][$j+$ligne_supl];
							$chaine_moy_classe[$j+$ligne_supl]=$moy_classe_tmp;
						}
						else{
							$chaine_matieres[$j+$ligne_supl].="|".$current_group["matiere"]["matiere"];
							//$chaine_moy_eleve1[$j+$ligne_supl].="|".$lig_moy->note;
							$chaine_moy_eleve1[$j+$ligne_supl].="|".$col[$k][$j+$ligne_supl];
							$chaine_moy_classe[$j+$ligne_supl].="|".$moy_classe_tmp;
						}
					}


				}
				//echo "\$col[$k][$j+$ligne_supl]=".$col[$k][$j+$ligne_supl]."<br />";
			}

		}
		else {
			// ANNEE ENTIERE... on fait les calculs
			$p = "1";
			$moy = 0;
			$non_suivi = 2;
			$coef_moy = 0;
			while ($p < $nb_periode) {

				// On r�cup�re l'indice de l'�l�ve dans $tab_moy pour la p�riode $num_periode
				//$indice_j_ele=$tab_moy['periodes'][$p]['tab_login_indice'][$current_eleve_login[$j]];
				//$coef_eleve=$tab_moy['periodes'][$p]['current_coef_eleve'][$indice_j_ele][$i];


				if (!in_array($current_eleve_login[$j], $current_group["eleves"][$p]["list"])) {
					$non_suivi = $non_suivi*2;

					if($current_eleve_login[$j]==$ele_login_debug) {
						$lignes_debug.="P�riode $p: Non suivi<br />";
					}
				}
				else {
					// On r�cup�re l'indice de l'�l�ve dans $tab_moy pour la p�riode $num_periode
					//$indice_j_ele=$tab_moy['periodes'][$p]['tab_login_indice'][$current_eleve_login[$j]];
					$indice_j_ele=$tab_moy['periodes'][$p]['tab_login_indice'][strtoupper($current_eleve_login[$j])];

					//$current_eleve_note_query = mysql_query("SELECT * FROM matieres_notes WHERE (login='$current_eleve_login[$j]' AND id_groupe='" . $current_group["id"] . "' AND periode='$p')");
					//$current_eleve_statut = @mysql_result($current_eleve_note_query, 0, "statut");

					$current_eleve_statut=$tab_moy['periodes'][$p]['current_eleve_statut'][$i][$indice_j_ele];
					$current_eleve_note=$tab_moy['periodes'][$p]['current_eleve_note'][$i][$indice_j_ele];

					//if ($current_eleve_statut == "") {
					if(($current_eleve_statut=="")&&($current_eleve_note!="")&&($current_eleve_note!="-")) {
						//$temp = @mysql_result($current_eleve_note_query, 0, "note");
						$temp=$current_eleve_note;
						if  ($temp != '')  {
							$moy += $temp;
							$coef_moy++;
						}
					}

					if($current_eleve_login[$j]==$ele_login_debug) {
						$lignes_debug.="\$current_eleve_statut=$current_eleve_statut<br />";
						$lignes_debug.="\$current_eleve_note=$current_eleve_note<br />";
						$lignes_debug.="Total pour la mati�re: $moy (pour $coef_moy note(s))<br />";
					}

					/*
					if($current_eleve_login[$j]=='BABOUIN_D') {
						echo "<p>\$tab_moy['periodes'][$p]['current_eleve_statut'][$i][$indice_j_ele]=".$current_eleve_statut."<br />";
						echo "\$tab_moy['periodes'][$p]['current_eleve_note'][$i][$indice_j_ele]=".$current_eleve_note."<br />";
						echo "\$moy=$moy et \$coef_moy=$coef_moy<br />";
					}
					*/
				}
				$p++;
			}
            $moy_eleve_grp_courant_annee="-";
			if ($non_suivi == (pow(2,$nb_periode))) {
				// L'�l�ve n'a suivi la mati�re sur aucune p�riode
				$col[$k][$j+$ligne_supl] = "/";

				if($current_eleve_login[$j]==$ele_login_debug) {
					$lignes_debug.="Enseignement non suivi de l'ann�e.<br />";
				}
			}
			else if ($coef_moy != 0) {
				// L'�l�ve a au moins une note sur au moins une p�riode
				$moy = $moy/$coef_moy;

				if($current_eleve_login[$j]==$ele_login_debug) {
					$lignes_debug.="Moyenne annuelle: $moy<br />";
				}

				$moy_min = min($moy_min,$moy);
				$moy_max = max($moy_max,$moy);
				$col[$k][$j+$ligne_supl] = number_format($moy,1, ',', ' ');
				if ($current_coef > 0) {
					//$temoin_current_note_bonus="n";

					$coef_eleve = $current_coef;
					$sql="SELECT value FROM eleves_groupes_settings WHERE (" .
							"login = '".$current_eleve_login[$j]."' AND " .
							"id_groupe = '".$current_group["id"]."' AND " .
							"name = 'coef')";
					$test_coef_personnalise = mysql_query($sql);
					if (mysql_num_rows($test_coef_personnalise) > 0) {
						$coef_eleve = mysql_result($test_coef_personnalise, 0);
					}

					//==============================
					// Pour prendre en compte les coef pour les cat�gories:
					$coef_eleve_reserve=$coef_eleve;
					// On met en r�serve le coef pour ne pas tenir compte des mode_moy au niveau des cat�gories
					//==============================

					// A FAIRE: PRENDRE EN COMPTE AUSSI mode_moy=bonus et mode_moy=ameliore
					if($utiliser_coef_perso=='y') {
						//if((isset($note_sup_10[$current_group["id"]]))&&($note_sup_10[$current_group["id"]]=='y')&&($moy<10)) {
						if(($current_mode_moy=='sup10')&&($moy<10)) {
							$coef_eleve=0;
							//echo $current_eleve_login[$j]." groupe n�".$current_group["id"]." (".$current_group["name"]."): coeff 0<br />";
						}
						/*
						elseif($current_mode_moy=='bonus')) {
							$temoin_current_note_bonus="y";
						}
						*/
					}
					else {
						if(($current_mode_moy=='sup10')&&($moy<10)) {$coef_eleve=0;}
						//elseif($current_mode_moy=='bonus') {$temoin_current_note_bonus="y";}
					}

					if($current_eleve_login[$j]==$ele_login_debug) {
						$lignes_debug.="\$current_coef=$current_coef<br />";
						$lignes_debug.="\$coef_eleve_reserve=$coef_eleve_reserve<br />";
						$lignes_debug.="\$coef_eleve=$coef_eleve<br />";
					}
	
					if (!in_array($prev_cat_id, $displayed_categories)) {$displayed_categories[] = $prev_cat_id;}
					//$total_coef[$j+$ligne_supl] += $current_coef;
					$total_coef_classe[$j+$ligne_supl] += $current_coef;

					// On ne compte pas le coef dans le total pour une note � bonus
					//if($temoin_current_note_bonus!="y") {
					if($current_mode_moy!="bonus") {
						$total_coef_eleve[$j+$ligne_supl] += $coef_eleve;
					}

					if($current_eleve_login[$j]==$ele_login_debug) {
						$lignes_debug.="<b>Total des coef:</b> ".$total_coef_eleve[$j+$ligne_supl]."<br />";
					}

					//$total_points[$j+$ligne_supl] += $current_coef*$moy;
					// On fait le m�me calcul pour la classe que pour l'�l�ve, mais sans les particularit�s de coefficients personnalis�s pour un �l�ve...
					// ... mais du coup, on ne g�re pas non plus les mode_moy: A REVOIR
					//$total_points_classe[$j+$ligne_supl] += $current_coef*$moy;

					//if($temoin_current_note_bonus!="y") {
					if($current_mode_moy!="bonus") {
						// Cas standard et sup10
						$total_points_eleve[$j+$ligne_supl] += $coef_eleve*$moy;
						// Dans le cas d'une note_sup_10 si $moy<10, $coef=0 si bien que �a n'augmente pas le total

						if($current_eleve_login[$j]==$ele_login_debug) {
							$lignes_debug.="On augmente le total des points de $coef_eleve*$moy<br />";
						}

					}
					elseif($moy>10) { // Cas d'une note � bonus:
						$total_points_eleve[$j+$ligne_supl] += $coef_eleve*($moy-10);

						if($current_eleve_login[$j]==$ele_login_debug) {
							$lignes_debug.="On augmente le total des points de $coef_eleve*($moy-10)<br />";
						}
					}

					if($current_eleve_login[$j]==$ele_login_debug) {
						$lignes_debug.="<b>Total des points:</b> ".$total_points_eleve[$j+$ligne_supl]."<br />";
					}

					if ($affiche_categories) {
						//$total_cat_coef[$j+$ligne_supl][$prev_cat_id] += $current_coef;
						//$total_cat_points[$j+$ligne_supl][$prev_cat_id] += $current_coef*$moy;

						// Pour les cat�gories, on ne tient pas compte des mode_moy: les coef comptent normalement
						// On utilise donc le coef_eleve_reserve mis en r�serve avant l'�ventuelle mise � z�ro dans le cas sup10 avec une note inf�rieure � 10
						//$total_cat_coef_eleve[$j+$ligne_supl][$prev_cat_id] += $coef_eleve;
						$total_cat_coef_eleve[$j+$ligne_supl][$prev_cat_id] += $coef_eleve_reserve;
						//$total_cat_coef_classe[$j+$ligne_supl][$prev_cat_id] += $current_coef;
						//$total_cat_points_eleve[$j+$ligne_supl][$prev_cat_id] += $coef_eleve*$moy;
						$total_cat_points_eleve[$j+$ligne_supl][$prev_cat_id] += $coef_eleve_reserve*$moy;
						//$total_cat_points_classe[$j+$ligne_supl][$prev_cat_id] += $current_coef*$moy;
						// Avec le $total_cat_points_classe, la diff�rence porte sur les coef personnalis�s (eleves_groupes_settings) et coef_perso
						// Faut-il tenir compte de �a ou se contenter pour la moyenne de classe des moyennes des moy_ele_cat?

						if($current_eleve_login[$j]==$ele_login_debug) {
							$lignes_debug.="<p>On augmente le total des coef de la cat�gorie $prev_cat_id de $coef_eleve_reserve<br />";
							$lignes_debug.="\$total_cat_coef_eleve[$j+$ligne_supl][$prev_cat_id]=".$total_cat_coef_eleve[$j+$ligne_supl][$prev_cat_id]."<br />";

							$lignes_debug.="On augmente le total des points de la cat�gorie $prev_cat_id de $coef_eleve_reserve*$moy<br />";
							$lignes_debug.="\$total_cat_points_eleve[$j+$ligne_supl][$prev_cat_id]=".$total_cat_points_eleve[$j+$ligne_supl][$prev_cat_id]."<br />";
						}
					}
				}
			}
			else {
				// Bien que suivant la mati�re, l'�l�ve n'a aucune note � toutes les p�riode (absent, pas de note, disp ...)
				$col[$k][$j+$ligne_supl] = "-";
			}


			$sql="SELECT * FROM j_eleves_groupes WHERE id_groupe='".$current_group["id"]."'";
			$test_eleve_grp=mysql_query($sql);
			if(mysql_num_rows($test_eleve_grp)>0) {
				//if($chaine_matieres[$j+$ligne_supl]==""){
				if(!isset($chaine_matieres[$j+$ligne_supl])){
					$chaine_matieres[$j+$ligne_supl]=$current_group["matiere"]["matiere"];
					//$chaine_moy_eleve1[$j+$ligne_supl]=$lig_moy->note;
					$chaine_moy_eleve1[$j+$ligne_supl]=$moy_eleve_grp_courant_annee;
					$chaine_moy_classe[$j+$ligne_supl]=$moy_classe_tmp;
				}
				else{
					if($chaine_matieres[$j+$ligne_supl]==""){
						$chaine_matieres[$j+$ligne_supl]=$current_group["matiere"]["matiere"];
						//$chaine_moy_eleve1[$j+$ligne_supl]=$lig_moy->note;
						$chaine_moy_eleve1[$j+$ligne_supl]=$moy_eleve_grp_courant_annee;
						$chaine_moy_classe[$j+$ligne_supl]=$moy_classe_tmp;
					}
					else{
						$chaine_matieres[$j+$ligne_supl].="|".$current_group["matiere"]["matiere"];
						//$chaine_moy_eleve1[$j+$ligne_supl].="|".$lig_moy->note;
						$chaine_moy_eleve1[$j+$ligne_supl].="|".$moy_eleve_grp_courant_annee;
						$chaine_moy_classe[$j+$ligne_supl].="|".$moy_classe_tmp;
					}
				}
			}

		}
		$j++;
		//echo "<br />";
	}


	// Lignes du bas avec moyenne classe/min/max pour le groupe $i
	if ($referent == "une_periode") {
		//$call_moyenne = mysql_query("SELECT round(avg(note),1) moyenne FROM matieres_notes WHERE (statut ='' AND id_groupe='" . $current_group["id"] . "' AND periode='$num_periode')");
		//$call_max = mysql_query("SELECT max(note) note_max FROM matieres_notes WHERE (statut ='' AND id_groupe='" . $current_group["id"] . "' AND periode='$num_periode')");
		//$call_min = mysql_query("SELECT min(note) note_min FROM matieres_notes WHERE (statut ='' AND id_groupe='" . $current_group["id"] . "' AND periode='$num_periode')");

		//$temp = @mysql_result($call_moyenne, 0, "moyenne");

		$temp=$tab_moy['periodes'][$p]['current_classe_matiere_moyenne'][$i];
		$moy_min_classe_grp=$tab_moy['periodes'][$p]['moy_min_classe_grp'][$i];
		$moy_max_classe_grp=$tab_moy['periodes'][$p]['moy_max_classe_grp'][$i];

	}
	else {
		$call_moyenne = mysql_query("SELECT round(avg(note),1) moyenne FROM matieres_notes WHERE (statut ='' AND id_groupe='" . $current_group["id"] . "')");
		$temp = @mysql_result($call_moyenne, 0, "moyenne");
	}

	//$moy_classe_tmp=$temp;

	//========================================
	//================================
	if($temoin_graphe=="oui"){
		if($i==$lignes_groupes-1){
			for($loop=0;$loop<$nb_lignes_tableau;$loop++){

				if(isset($chaine_moy_eleve1[$loop+$ligne_supl])) {

					$col_csv[1][$loop+$ligne_supl]=$col[1][$loop+$ligne_supl];

					$tmp_col=$col[1][$loop+$ligne_supl];
					//echo "\$current_eleve_login[$loop]=$current_eleve_login[$loop]<br />";
					$col[1][$loop+$ligne_supl]="<a href='../visualisation/draw_graphe.php?".
					"temp1=".strtr($chaine_moy_eleve1[$loop+$ligne_supl],',','.').
					"&amp;temp2=".strtr($chaine_moy_classe[$loop+$ligne_supl],',','.').
					"&amp;etiquette=".$chaine_matieres[$loop+$ligne_supl].
					"&amp;titre=$graph_title".
					"&amp;v_legend1=".$current_eleve_login[$loop].
					"&amp;v_legend2=moyclasse".
					"&amp;compteur=$compteur".
					"&amp;nb_series=$nb_series".
					"&amp;id_classe=$id_classe".
					"&amp;mgen1=".
					"&amp;mgen2=";
					//"&amp;periode=$periode".
					$col[1][$loop+$ligne_supl].="&amp;tronquer_nom_court=$tronquer_nom_court";
					if($referent == "une_periode"){
						$col[1][$loop+$ligne_supl].="&amp;periode=".rawurlencode("P�riode ".$num_periode);
					}
					else{
						$col[1][$loop+$ligne_supl].="&amp;periode=".rawurlencode("Ann�e");
					}
					$col[1][$loop+$ligne_supl].="&amp;largeur_graphe=$largeur_graphe".
					"&amp;hauteur_graphe=$hauteur_graphe".
					"&amp;taille_police=$taille_police".
					"&amp;epaisseur_traits=$epaisseur_traits".
					"&amp;temoin_image_escalier=$temoin_image_escalier".
					"' target='_blank'>".$tmp_col.
					"</a>";

				}
			}
			//echo "\$chaine_moy_classe=".$chaine_moy_classe."<br /><br />\n";
		}
	}
	// ===============================
	//========================================




	if ($nb_coef_non_nuls != 0) {
		if ($current_coef > 0) {
			// A FAIRE: A l'affichage, il faudrait mettre 1.0(*) quand le coeff n'est pas 1.0 pour tous les �l�ves � cause de coeffs personnalis�s.
			$col[$k][0] = number_format($current_coef,1, ',', ' ');
		} else {
			$col[$k][0] = "-";
		}
	}

	if ($temp != '') {
		//$col[$k][$nb_lignes_tableau+$ligne_supl] = $temp;
		$col[$k][$nb_lignes_tableau+$ligne_supl] = number_format($temp,1, ',', ' ');
	} else {
		$col[$k][$nb_lignes_tableau+$ligne_supl] = '-';
	}

	if ($referent == "une_periode") {
		//$temp = @mysql_result($call_min, 0, "note_min");
		$temp = $moy_min_classe_grp;
		if ($temp != '') {
			//$col[$k][$nb_lignes_tableau+1+$ligne_supl] = $temp;
			$col[$k][$nb_lignes_tableau+1+$ligne_supl] = number_format($temp,1, ',', ' ');
		} else {
			$col[$k][$nb_lignes_tableau+1+$ligne_supl] = '-';
		}
		//$temp = @mysql_result($call_max, 0, "note_max");
		$temp = $moy_max_classe_grp;
		if ($temp != '') {
			//$col[$k][$nb_lignes_tableau+2+$ligne_supl] = $temp;
			$col[$k][$nb_lignes_tableau+2+$ligne_supl] = number_format($temp,1, ',', ' ');
		} else {
			$col[$k][$nb_lignes_tableau+2+$ligne_supl] = '-';
		}
	}
	else {
		// Moyenne annuelle
		if ($moy_min <=20) {
			$col[$k][$nb_lignes_tableau+1+$ligne_supl] = number_format($moy_min,1, ',', ' ');
		}
		else {
			$col[$k][$nb_lignes_tableau+1+$ligne_supl] = '-';
		}

		if ($moy_max >= 0) {
			$col[$k][$nb_lignes_tableau+2+$ligne_supl] = number_format($moy_max,1, ',', ' ');
		}
		else {
			$col[$k][$nb_lignes_tableau+2+$ligne_supl] = '-';
		}
	}

	$nom_complet_matiere = $current_group["description"];
	$nom_complet_coupe = (strlen($nom_complet_matiere) > 20)? urlencode(substr($nom_complet_matiere,0,20)."...") : urlencode($nom_complet_matiere);

	$nom_complet_coupe_csv=(strlen($nom_complet_matiere) > 20) ? substr($nom_complet_matiere,0,20) : $nom_complet_matiere;
	$nom_complet_coupe_csv=preg_replace("/;/","",$nom_complet_coupe_csv);

	//$ligne1[$k] = "<img src=\"../lib/create_im_mat.php?texte=$nom_complet_coupe&width=22\" width=\"22\" border=\"0\" />";
	//$ligne1[$k] = "<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("$nom_complet_coupe")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"$nom_complet_coupe\" />";

	$ligne1[$k]="<a href='#' onclick=\"document.getElementById('col_tri').value='$k';";
	$ligne1[$k].="document.forms['formulaire_tri'].submit();\">";
	$ligne1[$k] .= "<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("$nom_complet_coupe")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"$nom_complet_coupe\" />";
	$ligne1[$k].="</a>";

	$ligne1_csv[$k] = "$nom_complet_coupe_csv";
	$i++;
}
// Fin de la boucle sur la liste des groupes/enseignements
/*
echo "<p style='color:red'>";
for($loop=0;$loop<$nb_lignes_tableau;$loop++) {

	//echo "\$col[1][$loop+$ligne_supl]=".$col[1][$j+$ligne_supl]."<br />";
	echo "\$col[1][$loop+$ligne_supl]=".$col[1][$loop+$ligne_supl]."<br />";

}
echo "</p>";
*/
//==================================================================================================================
//==================================================================================================================
//==================================================================================================================

// Derni�re colonne des moyennes g�n�rales: de cat�gories et de classe
//if ($ligne_supl == 1) {
if ($ligne_supl >= 1) {
	// Les moyennes pour chaque cat�gorie
	if ($affiche_categories) {
		foreach($displayed_categories as $cat_id) {
			$nb_col++;
			//$ligne1[$nb_col] = "<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Moyenne : " . $cat_names[$cat_id])."&amp;width=22\" width=\"22\" border=\"0\" alt=\"".$cat_names[$cat_id]."\" />";

			$ligne1[$nb_col] = "<a href='#' onclick=\"document.getElementById('col_tri').value='".$nb_col."';".
				"document.forms['formulaire_tri'].submit();\">".
				"<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Moyenne : " . $cat_names[$cat_id])."&amp;width=22\" width=\"22\" border=\"0\" alt=\"".$cat_names[$cat_id]."\" />".
				"</a>";

			$ligne1_csv[$nb_col] = "Moyenne : " . $cat_names[$cat_id];

			//if(isset($note_sup_10)) {$col[$nb_col][1]='-';}
			if($temoin_note_sup10=='y') {$col[$nb_col][1]='-';}

			if($referent=='une_periode') {
				$j = '0';
				while($j < $nb_lignes_tableau) {

					//$indice_j_ele=$tab_moy['periodes'][$num_periode]['tab_login_indice'][$current_eleve_login[$j]];
					$indice_j_ele=$tab_moy['periodes'][$num_periode]['tab_login_indice'][strtoupper($current_eleve_login[$j])];
					$tmp_moy_cat_ele=$tab_moy['periodes'][$num_periode]['moy_cat_eleve'][$indice_j_ele][$cat_id];

					//echo "$current_eleve_login[$j]: \$tab_moy['periodes'][$num_periode]['moy_cat_eleve'][$indice_j_ele][$cat_id]=".$tmp_moy_cat_ele."<br />";

					if(($tmp_moy_cat_ele!='')&&($tmp_moy_cat_ele!='-')) {
						//$col[$nb_col][$j+$ligne_supl]=number_format($tmp_moy_cat_ele,1, ',', ' ');
						$col[$nb_col][$j+$ligne_supl]=nf($tmp_moy_cat_ele,1);
					} else {
						$col[$nb_col][$j+$ligne_supl] = '/';
					}
					$j++;
				}

				$col[$nb_col][0] = "-";

				// On r�cup�re les valeurs avec le $indice_j_ele du dernier �l�ve, mais les moyennes de cat�gories pour la classe doivent �tre les m�mes quel que soit l'�l�ve
				$tmp_moy_cat_classe=$tab_moy['periodes'][$num_periode]['moy_cat_classe'][$indice_j_ele][$cat_id];

				//echo "$current_eleve_login[$j-1]: \$tab_moy['periodes'][$num_periode]['moy_cat_classe'][$indice_j_ele][$cat_id]=".$tmp_moy_cat_classe."<br />";

				if(($tmp_moy_cat_classe!='')&&($tmp_moy_cat_classe!='-')) {
					//$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = number_format($tmp_moy_cat_classe,1, ',', ' ');
					$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = nf($tmp_moy_cat_classe,1);
				}
				else {
					$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = "-";
				}

				$tmp_moy_cat_min=$tab_moy['periodes'][$num_periode]['moy_cat_min'][$indice_j_ele][$cat_id];
				if(($tmp_moy_cat_min!='')&&($tmp_moy_cat_min!='-')) {
					//$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = number_format($tmp_moy_cat_min,1, ',', ' ');
					$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = nf($tmp_moy_cat_min,1);
				}
				else {
					$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = "-";
				}

				$tmp_moy_cat_max=$tab_moy['periodes'][$num_periode]['moy_cat_max'][$indice_j_ele][$cat_id];
				if(($tmp_moy_cat_max!='')&&($tmp_moy_cat_max!='-')) {
					//$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = number_format($tmp_moy_cat_max,1, ',', ' ');
					$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = nf($tmp_moy_cat_max,1);
				}
				else {
					$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = "-";
				}
			}
			else {
				// Mode Ann�e enti�re
				$j = '0';
				while($j < $nb_lignes_tableau) {
					if ($total_cat_coef_eleve[$j+$ligne_supl][$cat_id] > 0) {
						$col[$nb_col][$j+$ligne_supl] = number_format($total_cat_points_eleve[$j+$ligne_supl][$cat_id]/$total_cat_coef_eleve[$j+$ligne_supl][$cat_id],1, ',', ' ');

						if($current_eleve_login[$j]==$ele_login_debug) {
							$lignes_debug.="Moyenne de la cat�gorie $cat_id=".$total_cat_points_eleve[$j+$ligne_supl][$cat_id]."/".$total_cat_coef_eleve[$j+$ligne_supl][$cat_id]."=".$col[$nb_col][$j+$ligne_supl]."<br />";
						}

						//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
						// A REVOIR... calcul des moyennes min/max/classe de cat�gories,...
						//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
						//$moy_cat_classe_point[$cat_id] +=$total_cat_points_classe[$j+$ligne_supl][$cat_id]/$total_cat_coef_classe[$j+$ligne_supl][$cat_id];
						$moy_cat_classe_point[$cat_id] +=$total_cat_points_eleve[$j+$ligne_supl][$cat_id]/$total_cat_coef_eleve[$j+$ligne_supl][$cat_id];

						$moy_cat_classe_effectif[$cat_id]++;

						$moy_cat_classe_min[$cat_id] = min($moy_cat_classe_min[$cat_id],$total_cat_points_eleve[$j+$ligne_supl][$cat_id]/$total_cat_coef_eleve[$j+$ligne_supl][$cat_id]);

						$moy_cat_classe_max[$cat_id] = max($moy_cat_classe_max[$cat_id],$total_cat_points_eleve[$j+$ligne_supl][$cat_id]/$total_cat_coef_eleve[$j+$ligne_supl][$cat_id]);
					} else {
						$col[$nb_col][$j+$ligne_supl] = '/';
					}
					$j++;
				}

				$col[$nb_col][0] = "-";
				if ($moy_cat_classe_point[$cat_id] == 0) {
					$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = "-";
					$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = "-";
					$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = "-";
				} else {
					$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = number_format($moy_cat_classe_point[$cat_id]/$moy_cat_classe_effectif[$cat_id],1, ',', ' ');
					$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = number_format($moy_cat_classe_min[$cat_id],1, ',', ' ');
					$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = number_format($moy_cat_classe_max[$cat_id],1, ',', ' ');
				}
			}
		}
	}

	//================================================================================================

	// La moyenne g�n�rale des �l�ves (derni�re colonne... ou avant-derni�re dans le cas ann�e_enti�re)
	$nb_col++;

	unset($num_p1);
	unset($num_p2);
	if($referent=='une_periode') {
		if(!isset($avec_moy_gen_periodes_precedentes)) {
			$num_p1=$num_periode;
			$num_p2=$num_p1+1;
		}
		else {
			$num_p1=1;
			$num_p2=$num_periode+1;
		}
	}
	else {
		if(isset($avec_moy_gen_periodes_precedentes)) {
			$num_p1=1;
			$num_p2=$nb_periode;
		}
	}

	if((isset($num_p1))&&(isset($num_p2))) {
		for($loop=$num_p1;$loop<$num_p2;$loop++) {
			if($loop>$num_p1) {$nb_col++;}
	
			//if(isset($note_sup_10)) {$col[$nb_col][1]='-';}
			if($temoin_note_sup10=='y') {$col[$nb_col][1]='-';}
		
			$ligne1[$nb_col]="<a href='#' onclick=\"document.getElementById('col_tri').value='$nb_col';";
			if(preg_match("/^Rang/i",$ligne1[$nb_col])) {$ligne1[$nb_col].="document.getElementById('sens_tri').value='inverse';";}
			$ligne1[$nb_col].="document.forms['formulaire_tri'].submit();\">";
			$ligne1[$nb_col].="<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Moyenne g�n�rale P$loop")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"Moyenne g�n�rale P$loop\" />";
			$ligne1[$nb_col].="</a>";
			$ligne1_csv[$nb_col] = "Moyenne g�n�rale P$loop";
			$j = '0';
			while($j < $nb_lignes_tableau) {

//				if($referent=='une_periode') {
					//$indice_j_ele=$tab_moy['periodes'][$num_periode]['tab_login_indice'][$current_eleve_login[$j]];
					if(isset($tab_moy['periodes'][$loop]['tab_login_indice'][strtoupper($current_eleve_login[$j])])) {
						$indice_j_ele=$tab_moy['periodes'][$loop]['tab_login_indice'][strtoupper($current_eleve_login[$j])];
						$tmp_moy_gen_ele=$tab_moy['periodes'][$loop]['moy_gen_eleve'][$indice_j_ele];
						if(($tmp_moy_gen_ele!='')&&($tmp_moy_gen_ele!='-')) {
							$col[$nb_col][$j+$ligne_supl] = number_format($tmp_moy_gen_ele,1, ',', ' ');
						}
						else {
							$col[$nb_col][$j+$ligne_supl] = '/';
						}
					}
					else {
						$col[$nb_col][$j+$ligne_supl] = '/';
					}
/*
				}
				else {
					// En mode annee, on fait les calculs
					if ($total_coef_eleve[$j+$ligne_supl] > 0) {
		
						$col[$nb_col][$j+$ligne_supl] = number_format($total_points_eleve[$j+$ligne_supl]/$total_coef_eleve[$j+$ligne_supl],1, ',', ' ');
		
		
						if($current_eleve_login[$j]==$ele_login_debug) {
							$lignes_debug.="<b>Moyenne de l'�l�ve=</b>".$total_points_eleve[$j+$ligne_supl]."/".$total_coef_eleve[$j+$ligne_supl]."=".$col[$nb_col][$j+$ligne_supl]."<br />";
						}
		
		
						// A REVOIR: IL FAUDRAIT CALCULER LES MOYENNES GENERALES DE CLASSE COMME MOYENNES DES MOYENNES GENERALES DES ELEVES
						// C'est presque le cas: les tableaux $total_points_classe et $total_points_classe sont des totaux effectu�s pour chaque �l�ve en prenant les coef non bricol�s.
						//$moy_classe_point +=$total_points[$j+$ligne_supl]/$total_coef[$j+$ligne_supl];
						//$moy_classe_point+=$total_points_classe[$j+$ligne_supl]/$total_coef_classe[$j+$ligne_supl];
						$moy_classe_point+=$total_points_eleve[$j+$ligne_supl]/$total_coef_eleve[$j+$ligne_supl];
						$moy_classe_effectif++;
		
						//$moy_classe_min = min($moy_classe_min,$total_points[$j+$ligne_supl]/$total_coef[$j+$ligne_supl]);
						//$moy_classe_max = max($moy_classe_max,$total_points[$j+$ligne_supl]/$total_coef[$j+$ligne_supl]);
						$moy_classe_min = min($moy_classe_min,$total_points_eleve[$j+$ligne_supl]/$total_coef_eleve[$j+$ligne_supl]);
						$moy_classe_max = max($moy_classe_max,$total_points_eleve[$j+$ligne_supl]/$total_coef_eleve[$j+$ligne_supl]);
					} else {
						$col[$nb_col][$j+$ligne_supl] = '/';
					}
				}
*/
				$j++;
			}
	
	
			// Lignes moyennes des derni�res colonnes:
			//if($referent=='une_periode') {
				$col[$nb_col][0] = "-";
		
				$tmp_moy_gen_classe=$tab_moy['periodes'][$loop]['moy_generale_classe'];
				$moy_classe_min=$tab_moy['periodes'][$loop]['moy_min_classe'];
				$moy_classe_max=$tab_moy['periodes'][$loop]['moy_max_classe'];
		
				if(($tmp_moy_gen_classe=='')||($tmp_moy_gen_classe=='-')) {
					$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = "-";
					$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = "-";
					$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = "-";
				} else {
					$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = number_format($tmp_moy_gen_classe,1, ',', ' ');
					$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = number_format($moy_classe_min,1, ',', ' ');
					$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = number_format($moy_classe_max,1, ',', ' ');
				}
/*
			}
			else {
				$col[$nb_col][0] = "-";
				if ($moy_classe_point == 0) {
					$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = "-";
					$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = "-";
					$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = "-";
				} else {
					// A REVOIR: IL FAUDRAIT CALCULER LES MOYENNES GENERALES DE CLASSE COMME MOYENNES DES MOYENNES GENERALES DES ELEVES
					$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = number_format($moy_classe_point/$moy_classe_effectif,1, ',', ' ');
					$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = number_format($moy_classe_min,1, ',', ' ');
					$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = number_format($moy_classe_max,1, ',', ' ');
				}
			}

			// Colonne rang (en fin de tableau (derni�re colonne) dans le cas Ann�e enti�re)
			if (($aff_rang) and ($referent!="une_periode")) {
				// Calculer le rang dans le cas ann�e enti�re
				//$nb_col++;

				// Pr�paratifs
		
				// Initialisation d'un tableau pour les rangs et affectation des valeurs r�index�es dans un tableau temporaire
				my_echo("<table>");
				my_echo("<tr>");
				my_echo("<td>");
					my_echo("<table>");
				unset($tmp_tab);
				$k=0;
				unset($rg);
				while($k < $nb_lignes_tableau) {
					$rg[$k]=$k;
		
					if ($total_coef_eleve[$k+$ligne_supl] > 0) {
						$tmp_tab[$k]=my_ereg_replace(",",".",$col[$nb_col][$k+1]);
						my_echo("<tr>");
						my_echo("<td>".($k+1)."</td><td>".$col[1][$k+1]."</td><td>".$col[$nb_col][$k+1]."</td><td>$tmp_tab[$k]</td>");
						my_echo("</tr>");
					}
					else {
						my_echo("<tr>");
						my_echo("<td>".($k+1)."</td><td>".$col[1][$k+1]."</td><td>".$col[$nb_col][$k+1]."</td><td>$tmp_tab[$k] --</td>");
						my_echo("</tr>");
						$tmp_tab[$k]="?";
					}
		
					$k++;
				}
					my_echo("</table>");
				my_echo("</td>");
		
				array_multisort ($tmp_tab, SORT_DESC, SORT_NUMERIC, $rg, SORT_ASC, SORT_NUMERIC);
		
				my_echo("<td>");
					my_echo("<table>");
				$k=0;
				while($k < $nb_lignes_tableau) {
					if(isset($rg[$k])) {
						my_echo("<tr><td>\$rg[$k]+1=".($rg[$k]+1)."</td><td>".$col[1][$rg[$k]+1]."</td></tr>");
		
					}
					$k++;
				}
					my_echo("</table>");
				my_echo("</td>");
				my_echo("</tr>");
				my_echo("</table>");
		
				// On ajoute une colonne
				$nb_col++;
		
				// Initialisation de la colonne ajout�e
				$j=1;
				while($j <= $nb_lignes_tableau) {
					$col[$nb_col][$j]="-";
					$j++;
				}
		
				// Affectation des rangs dans la colonne ajout�e
				$k=0;
				while($k < $nb_lignes_tableau) {
					if(isset($rg[$k])) {
						$col[$nb_col][$rg[$k]+1]=$k+1;
					}
					$k++;
				}
		
				// Remplissage de la ligne de titre
				//$ligne1[$nb_col] = "<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Rang de l'�l�ve")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"Rang de l'�l�ve\" />";
		
				$ligne1[$nb_col] = "<a href='#' onclick=\"document.getElementById('col_tri').value='".$nb_col."';".
						"document.getElementById('sens_tri').value='inverse';".
						"document.forms['formulaire_tri'].submit();\">".
						"<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Rang de l'�l�ve")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"Rang de l'�l�ve\" />".
						"</a>";
		
				$ligne1_csv[$nb_col] = "Rang de l'�l�ve";
		
				// Remplissage de la ligne coefficients
				$col[$nb_col][0] = "-";
		
				// Remplissage des lignes Moyenne g�n�rale, minimale et maximale
				$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = "-";
				$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = "-";
				$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = "-";
			}
*/

		}
	}

	if($referent!='une_periode') {
		if(isset($avec_moy_gen_periodes_precedentes)) {
			$nb_col++;
		}

		//if(isset($note_sup_10)) {$col[$nb_col][1]='-';}
		if($temoin_note_sup10=='y') {$col[$nb_col][1]='-';}
	
		$ligne1[$nb_col]="<a href='#' onclick=\"document.getElementById('col_tri').value='$nb_col';";
		if(preg_match("/^Rang/i",$ligne1[$nb_col])) {$ligne1[$nb_col].="document.getElementById('sens_tri').value='inverse';";}
		$ligne1[$nb_col].="document.forms['formulaire_tri'].submit();\">";
		$ligne1[$nb_col].="<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Moyenne g�n�rale")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"Moyenne g�n�rale\" />";
		$ligne1[$nb_col].="</a>";
		$ligne1_csv[$nb_col] = "Moyenne g�n�rale";
		$j = '0';



		for($y=0;$y<$nb_lignes_tableau+$ligne_supl;$y++) {
			my_echo("\$col[1][$y]=".$col[1][$y]."<br />");
		}

		while($j < $nb_lignes_tableau) {

			//echo "\$total_coef_eleve[$j+$ligne_supl]=".$total_coef_eleve[$j+$ligne_supl]."<br />";

			// En mode annee, on fait les calculs
			if ($total_coef_eleve[$j+$ligne_supl] > 0) {

				$col[$nb_col][$j+$ligne_supl] = number_format($total_points_eleve[$j+$ligne_supl]/$total_coef_eleve[$j+$ligne_supl],1, ',', ' ');

				my_echo("\$col[$nb_col][$j+$ligne_supl]=".$col[$nb_col][$j+$ligne_supl]."<br />");

				if($current_eleve_login[$j]==$ele_login_debug) {
					$lignes_debug.="<b>Moyenne de l'�l�ve=</b>".$total_points_eleve[$j+$ligne_supl]."/".$total_coef_eleve[$j+$ligne_supl]."=".$col[$nb_col][$j+$ligne_supl]."<br />";
				}


				// A REVOIR: IL FAUDRAIT CALCULER LES MOYENNES GENERALES DE CLASSE COMME MOYENNES DES MOYENNES GENERALES DES ELEVES
				// C'est presque le cas: les tableaux $total_points_classe et $total_points_classe sont des totaux effectu�s pour chaque �l�ve en prenant les coef non bricol�s.
				//$moy_classe_point +=$total_points[$j+$ligne_supl]/$total_coef[$j+$ligne_supl];
				//$moy_classe_point+=$total_points_classe[$j+$ligne_supl]/$total_coef_classe[$j+$ligne_supl];
				$moy_classe_point+=$total_points_eleve[$j+$ligne_supl]/$total_coef_eleve[$j+$ligne_supl];
				$moy_classe_effectif++;

				//$moy_classe_min = min($moy_classe_min,$total_points[$j+$ligne_supl]/$total_coef[$j+$ligne_supl]);
				//$moy_classe_max = max($moy_classe_max,$total_points[$j+$ligne_supl]/$total_coef[$j+$ligne_supl]);
				if(($moy_classe_min!="-")&&($moy_classe_min!="")) {
					//echo "\$moy_classe_min = min($moy_classe_min,".$total_points_eleve[$j+$ligne_supl]."/".$total_coef_eleve[$j+$ligne_supl].")=".$moy_classe_min."<br />";
					$moy_classe_min = min($moy_classe_min,$total_points_eleve[$j+$ligne_supl]/$total_coef_eleve[$j+$ligne_supl]);
				}
				else {
					$moy_classe_min = $total_points_eleve[$j+$ligne_supl]/$total_coef_eleve[$j+$ligne_supl];
				}
				$moy_classe_max = max($moy_classe_max,$total_points_eleve[$j+$ligne_supl]/$total_coef_eleve[$j+$ligne_supl]);
			} else {
				$col[$nb_col][$j+$ligne_supl] = '/';
			}
			$j++;
		}


		// Lignes moyennes des derni�res colonnes:

		//echo "\$nb_col=$nb_col<br />";
		//echo "\$moy_classe_point=$moy_classe_point<br />";
		//echo "\$moy_classe_min=$moy_classe_min<br />";

		$col[$nb_col][0] = "-";

		if(($temoin_note_sup10=='y')||($temoin_note_bonus=='y')) {
			$col[$nb_col][1] = "-";
		}
		my_echo("\$col[$nb_col][0]=".$col[$nb_col][0]."<br />");
		my_echo("\$col[$nb_col][1]=".$col[$nb_col][1]."<br />");


		if ($moy_classe_point == 0) {
			$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = "-";
			$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = "-";
			$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = "-";
		} else {
			// A REVOIR: IL FAUDRAIT CALCULER LES MOYENNES GENERALES DE CLASSE COMME MOYENNES DES MOYENNES GENERALES DES ELEVES
			$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = number_format($moy_classe_point/$moy_classe_effectif,1, ',', ' ');
			$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = number_format($moy_classe_min,1, ',', ' ');
			$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = number_format($moy_classe_max,1, ',', ' ');
		}





		$corr=0;
		// Ajout d'une ligne de d�calage si il y a une ligne de coeff
		if($col[1][0]=="Coefficient") {
			//$b_inf=1;
			//$b_sup=$nb_lignes_tableau+1;
			//$corr=1;
			$corr++;
		}
		// Ajout d'une ligne de d�calage si il y a une ligne mode_moy
		//if($temoin_note_sup10=='y') {
		if(($temoin_note_sup10=='y')||($temoin_note_bonus=='y')) {
			$corr++;
		}




		// Colonne rang (en fin de tableau (derni�re colonne) dans le cas Ann�e enti�re)
		if (($aff_rang) and ($aff_rang=='y') and ($referent!="une_periode")) {

			// Calculer le rang dans le cas ann�e enti�re
			//$nb_col++;

			// Pr�paratifs

			// Initialisation d'un tableau pour les rangs et affectation des valeurs r�index�es dans un tableau temporaire
			my_echo("<table>");
			my_echo("<tr>");
			my_echo("<td>");
				my_echo("<table>");
			unset($tmp_tab);
			$k=0;
			unset($rg);
			while($k < $nb_lignes_tableau) {
				$rg[$k]=$k;
	
				if ($total_coef_eleve[$k+$ligne_supl] > 0) {
					//$tmp_tab[$k]=preg_replace("/,/",".",$col[$nb_col][$k+1]);
					$tmp_tab[$k]=preg_replace("/,/",".",$col[$nb_col][$k+$corr]);
					my_echo("<tr>");
					//my_echo("<td>".($k+1)."</td><td>".$col[1][$k+1]."</td><td>".$col[$nb_col][$k+1]."</td><td>$tmp_tab[$k]</td>");
					my_echo("<td>".($k+$corr)."</td><td>".$col[1][$k+$corr]."</td><td>".$col[$nb_col][$k+$corr]."</td><td>$tmp_tab[$k]</td>");
					my_echo("</tr>");
				}
				else {
					my_echo("<tr>");
					//my_echo("<td>".($k+1)."</td><td>".$col[1][$k+1]."</td><td>".$col[$nb_col][$k+1]."</td><td>$tmp_tab[$k] --</td>");
					my_echo("<td>".($k+$corr)."</td><td>".$col[1][$k+$corr]."</td><td>".$col[$nb_col][$k+$corr]."</td><td>$tmp_tab[$k] --</td>");
					my_echo("</tr>");
					$tmp_tab[$k]="?";
				}
	
				$k++;
			}
				my_echo("</table>");
			//my_echo("PLOP");
			my_echo("</td>");
	
			array_multisort ($tmp_tab, SORT_DESC, SORT_NUMERIC, $rg, SORT_ASC, SORT_NUMERIC);
	
			my_echo("<td>");
				my_echo("<table>");
			$k=0;
			while($k < $nb_lignes_tableau) {
				if(isset($rg[$k])) {
					//my_echo("<tr><td>\$rg[$k]+1=".($rg[$k]+1)."</td><td>".$col[1][$rg[$k]+1]."</td></tr>");
					my_echo("<tr><td>\$rg[$k]+$corr=".($rg[$k]+$corr)."</td><td>".$col[1][$rg[$k]+$corr]."</td></tr>");
	
				}
				$k++;
			}
				my_echo("</table>");
			my_echo("</td>");
			my_echo("</tr>");
			my_echo("</table>");
	
			// On ajoute une colonne
			$nb_col++;
	
			// Initialisation de la colonne ajout�e
			$j=1;
			while($j <= $nb_lignes_tableau) {
				$col[$nb_col][$j]="-";
				$j++;
			}

			// Affectation des rangs dans la colonne ajout�e
			$k=0;
			while($k < $nb_lignes_tableau) {
				if(isset($rg[$k])) {
					//$col[$nb_col][$rg[$k]+1]=$k+1;
					//$col[$nb_col][$rg[$k]+1]=$k+$corr;
					$col[$nb_col][$rg[$k]+$corr]=$k+1;
					//$col[$nb_col][$rg[$k]+$corr]=$k+1;
				}
				$k++;
			}

			/*
			echo "\$ligne_supl=$ligne_supl<br />";
			echo "\$col[$nb_col]<br />";
			echo "<pre>";
			print_r($col[$nb_col]);
			echo "</pre>";
			*/

			// Remplissage de la ligne de titre
			//$ligne1[$nb_col] = "<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Rang de l'�l�ve")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"Rang de l'�l�ve\" />";
	
			$ligne1[$nb_col] = "<a href='#' onclick=\"document.getElementById('col_tri').value='".$nb_col."';".
					"document.getElementById('sens_tri').value='inverse';".
					"document.forms['formulaire_tri'].submit();\">".
					"<img src=\"../lib/create_im_mat.php?texte=".rawurlencode("Rang de l'�l�ve")."&amp;width=22\" width=\"22\" border=\"0\" alt=\"Rang de l'�l�ve\" />".
					"</a>";
	
			$ligne1_csv[$nb_col] = "Rang de l'�l�ve";
	
			// Remplissage de la ligne coefficients
			$col[$nb_col][0] = "-";
	
			// Remplissage des lignes Moyenne g�n�rale, minimale et maximale
			$col[$nb_col][$nb_lignes_tableau+$ligne_supl] = "-";
			$col[$nb_col][$nb_lignes_tableau+1+$ligne_supl] = "-";
			$col[$nb_col][$nb_lignes_tableau+2+$ligne_supl] = "-";
		}

	}

}
/*
echo "<p style='color:green'>";
for($loop=0;$loop<$nb_lignes_tableau;$loop++) {

	//echo "\$col[1][$loop+$ligne_supl]=".$col[1][$j+$ligne_supl]."<br />";
	echo "\$col[1][$loop+$ligne_supl]=".$col[1][$loop+$ligne_supl]."<br />";

}
echo "</p>";
*/
//====================
// DEBUG:
//echo $lignes_debug;
//====================

//===============================
// A FAIRE: 20080424
// INTERCALER ICI un dispositif analogue � celui de index1.php pour trier autrement

if((isset($_POST['col_tri']))&&($_POST['col_tri']!='')) {
	// Pour activer my_echo � des fins de debug, passer $debug � 1 dans la d�claration de la fonction plus haut dans la page
	my_echo("\$_POST['col_tri']=".$_POST['col_tri']."<br />");
	$col_tri=$_POST['col_tri'];

	$nb_colonnes=$nb_col;

	// if ($test_coef != 0) $col[1][0] = "Coefficient";

	$corr=0;
	// Ajout d'une ligne de d�calage si il y a une ligne de coeff
	if($col[1][0]=="Coefficient") {
		//$b_inf=1;
		//$b_sup=$nb_lignes_tableau+1;
		//$corr=1;
		$corr++;
	}
	// Ajout d'une ligne de d�calage si il y a une ligne mode_moy
	//if($temoin_note_sup10=='y') {
	if(($temoin_note_sup10=='y')||($temoin_note_bonus=='y')) {
		$corr++;
	}
	/*
	else {
		//$b_inf=0;
		//$b_sup=$nb_lignes_tableau;
		$corr=0;
	}
	*/

	// V�rifier si $col_tri est bien un entier compris entre 0 et $nb_col ou $nb_col+1
	if((strlen(preg_replace("/[0-9]/","",$col_tri))==0)&&($col_tri>0)&&($col_tri<=$nb_colonnes)) {
		my_echo("<table>");
		my_echo("<tr><td valign='top'>");
		unset($tmp_tab);
		for($loop=0;$loop<$nb_lignes_tableau;$loop++) {
		//for($loop=$b_inf;$loop<$b_sup;$loop++) {
			// Il faut le POINT au lieu de la VIRGULE pour obtenir un tri correct sur les notes
			//$tmp_tab[$loop]=my_ereg_replace(",",".",$col_csv[$col_tri][$loop]);
			//$tmp_tab[$loop]=my_ereg_replace(",",".",$col[$col_tri][$loop]);
			$tmp_tab[$loop]=preg_replace("/,/",".",$col[$col_tri][$loop+$corr]);
			//$tmp_tab[$loop]=my_ereg_replace(",",".",$col[$col_tri][$loop]);
			my_echo("\$tmp_tab[$loop]=".$tmp_tab[$loop]."<br />");
		}

		my_echo("</td>");
		my_echo("<td valign='top'>");

		$i=0;
		while($i < $nb_lignes_tableau) {
		//$i=$b_inf;
		//while($i < $b_sup) {
			//my_echo($col_csv[1][$i]."<br />");
			my_echo($col[1][$i+$corr]."<br />");
			$i++;
		}
		my_echo("</td>");
		my_echo("<td valign='top'>");


		//$i=0;
		//while($i < $nb_lignes_tableau) {
		$i=0;
		while($i < $nb_lignes_tableau) {
			$rg[$i]=$i;
			$i++;
		}

		// Tri du tableau avec stockage de l'ordre dans $rg d'apr�s $tmp_tab
		array_multisort ($tmp_tab, SORT_DESC, SORT_NUMERIC, $rg, SORT_ASC, SORT_NUMERIC);


		$i=0;
		while($i < $nb_lignes_tableau) {
			my_echo("\$rg[$i]=".$rg[$i]."<br />");
			$i++;
		}
		my_echo("</td>");
		my_echo("<td valign='top'>");


		// On utilise des tableaux temporaires le temps de la r�affectation dans l'ordre
		$tmp_col=array();
		//$tmp_col_csv=array();

		$i=0;
		$rang_prec = 1;
		$note_prec='';
		while ($i < $nb_lignes_tableau) {
			$ind = $rg[$i];
			if ($tmp_tab[$i] == "-") {
				//$rang_gen = '0';
				$rang_gen = '-';
			}
			else {
				if ($tmp_tab[$i] == $note_prec) {
					$rang_gen = $rang_prec;
				}
				else {
					$rang_gen = $i+1;
				}
				$note_prec = $tmp_tab[$i];
				$rang_prec = $rang_gen;
			}

			//$col[$nb_col+1][$ind]="ind=$ind, i=$i et rang_gen=$rang_gen";
			for($m=1;$m<=$nb_colonnes;$m++) {
				my_echo("\$tmp_col[$m][$i]=\$col[$m][$ind+$corr]=".$col[$m][$ind+$corr]."<br />");
				$tmp_col[$m][$i]=$col[$m][$ind+$corr];
				//$tmp_col_csv[$m][$ind]=$col_csv[$m][$ind];

			}
			$i++;
		}
		my_echo("</td></tr>");
		my_echo("</table>");

		// On r�affecte les valeurs dans le tableau initial � l'aide du tableau temporaire
		if((isset($_POST['sens_tri']))&&($_POST['sens_tri']=="inverse")) {
			for($m=1;$m<=$nb_colonnes;$m++) {
				for($i=0;$i<$nb_lignes_tableau;$i++) {
					$col[$m][$i+$corr]=$tmp_col[$m][$nb_lignes_tableau-1-$i];
					//$col_csv[$m][$i]=$tmp_col_csv[$m][$nombre_eleves-1-$i];
				}
			}
		}
		else {
			for($m=1;$m<=$nb_colonnes;$m++) {
				//$col[$m]=$tmp_col[$m];
				//$col_csv[$m]=$tmp_col_csv[$m];
				// Pour ne pas perdre les lignes de moyennes de classe
				for($i=0;$i<$nb_lignes_tableau;$i++) {
					$col[$m][$i+$corr]=$tmp_col[$m][$i];
					//$col_csv[$m][$i]=$tmp_col_csv[$m][$i];
				}
			}
		}
	}
}
//=========================

//===============================


$nb_lignes_tableau = $nb_lignes_tableau + 3 + $ligne_supl;



function affiche_tableau_csv2($nombre_lignes, $nb_col, $ligne1, $col, $col_csv) {
	$chaine="";
	$j = 1;
	while($j < $nb_col+1) {
		if($j>1){
			//echo ";";
			$chaine.=";";
		}
		//echo $ligne1[$j];
		$chaine.=$ligne1[$j];
		$j++;
	}
	//echo "<br />";
	//echo "\n";
	$chaine.="\n";

	$i = "0";
	while($i < $nombre_lignes) {
		$j = 1;
		while($j < $nb_col+1) {
			if($j>1){
				//echo ";";
				$chaine.=";";
			}
			//echo $col[$j][$i];
			if(isset($col_csv[$j][$i])) {
				$chaine.=$col_csv[$j][$i];
			}
			else {
				$chaine.=$col[$j][$i];
			}
			$j++;
		}
		//echo "<br />";
		//echo "\n";
		$chaine.="\n";
		$i++;
	}
	return $chaine;
}


if(isset($_GET['mode'])) {
	if($_GET['mode']=="csv") {
		$classe = sql_query1("SELECT classe FROM classes WHERE id = '$id_classe'");

		if ($referent == "une_periode") {
			$chaine_titre="Classe_".$classe."_Resultats_".$nom_periode[$num_periode]."_Annee_scolaire_".getSettingValue("gepiYear");
		} else {
			$chaine_titre="Classe_".$classe."_Resultats_Moyennes_annuelles_Annee_scolaire_".getSettingValue("gepiYear");
		}

		$now = gmdate('D, d M Y H:i:s') . ' GMT';

		$nom_fic=$chaine_titre."_".$now.".csv";

		// Filtrer les caract�res dans le nom de fichier:
		$nom_fic=preg_replace("/[^a-zA-Z0-9_.-]/","",remplace_accents($nom_fic,'all'));

		/*
		header('Content-Type: text/x-csv');
		header('Expires: ' . $now);
		// lem9 & loic1: IE need specific headers
		if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) {
			header('Content-Disposition: inline; filename="' . $nom_fic . '"');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header('Content-Disposition: attachment; filename="' . $nom_fic . '"');
			header('Pragma: no-cache');
		}
		*/
		send_file_download_headers('text/x-csv',$nom_fic);

		$fd="";
		$fd.=affiche_tableau_csv2($nb_lignes_tableau, $nb_col, $ligne1_csv, $col, $col_csv);
		echo $fd;
		die();
	}
}

//**************** EN-TETE *****************
require_once("../lib/header.inc");
//**************** FIN EN-TETE *****************

//debug_var();

if($vtn_coloriser_resultats=='y') {
	check_token(false);
	$sql="DELETE FROM preferences WHERE login='".$_SESSION['login']."' AND name LIKE 'vtn_%';";
	$del=mysql_query($sql);

	foreach($vtn_couleur_texte as $key => $value) {
		$sql="INSERT INTO preferences SET login='".$_SESSION['login']."', name='vtn_couleur_texte$key', value='$value';";
		$insert=mysql_query($sql);
	}
	foreach($vtn_couleur_cellule as $key => $value) {
		$sql="INSERT INTO preferences SET login='".$_SESSION['login']."', name='vtn_couleur_cellule$key', value='$value';";
		$insert=mysql_query($sql);
	}
	foreach($vtn_borne_couleur as $key => $value) {
		$sql="INSERT INTO preferences SET login='".$_SESSION['login']."', name='vtn_borne_couleur$key', value='$value';";
		$insert=mysql_query($sql);
	}
}

//if(!isset($_SESSION['vtn_pref_num_periode'])) {
	$sql="DELETE FROM preferences WHERE name LIKE 'vtn_pref_%' AND login='".$_SESSION['login']."';";
	$del=mysql_query($sql);

	//$tab_pref=array('num_periode', 'larg_tab', 'bord', 'couleur_alterne', 'aff_abs', 'aff_reg', 'aff_doub', 'aff_date_naiss', 'aff_rang');
	$tab_pref=array('num_periode', 'larg_tab', 'bord', 'couleur_alterne', 'aff_abs', 'aff_reg', 'aff_doub', 'aff_date_naiss', 'aff_rang', 'avec_moy_gen_periodes_precedentes');

	for($loop=0;$loop<count($tab_pref);$loop++) {
		$tmp_var=$tab_pref[$loop];
		if($$tmp_var=='') {$$tmp_var="n";}
		$sql="INSERT INTO preferences SET name='vtn_pref_".$tmp_var."', value='".$$tmp_var."', login='".$_SESSION['login']."';";
		//echo "$sql<br />";
		$insert=mysql_query($sql);
		$_SESSION['vtn_pref_'.$tmp_var]=$$tmp_var;
	}

	// Mettre aussi utiliser_coef_perso et vtn_coloriser_resultats
	// PB pour les coef perso, ce sont des associations coef/groupe qui sont faites et le groupe n'est que rarement commun d'une classe � une autre
	$sql="INSERT INTO preferences SET name='vtn_pref_coloriser_resultats', value='$vtn_coloriser_resultats', login='".$_SESSION['login']."';";
	$insert=mysql_query($sql);
	$_SESSION['vtn_pref_coloriser_resultats']=$vtn_coloriser_resultats;
	
//}

$classe = sql_query1("SELECT classe FROM classes WHERE id = '$id_classe'");

// Lien pour g�n�rer un CSV
echo "<div class='noprint' style='float: right; border: 1px solid black; background-color: white; width: 7em; height: 1em; text-align: center; padding-bottom:3px;'>
<a href='".$_SERVER['PHP_SELF']."?mode=csv&amp;id_classe=$id_classe&amp;num_periode=$num_periode";

if(($aff_abs)&&($aff_abs=='y')) {
	echo "&amp;aff_abs=$aff_abs";
}
if(($aff_reg)&&($aff_reg=='y')) {
	echo "&amp;aff_reg=$aff_reg";
}
if(($aff_doub)&&($aff_doub=='y')) {
	echo "&amp;aff_doub=$aff_doub";
}
if(($aff_rang)&&($aff_rang=='y')) {
	echo "&amp;aff_rang=$aff_rang";
}
if(($aff_date_naiss)&&($aff_date_naiss=='y')) {
	echo "&amp;aff_date_naiss=$aff_date_naiss";
}

if($utiliser_coef_perso=='y') {
	echo "&amp;utiliser_coef_perso=y";
	foreach($coef_perso as $key => $value) {
		echo "&amp;coef_perso[$key]=$value";
	}
	/*
	foreach($note_sup_10 as $key => $value) {
		echo "&amp;note_sup_10[$key]=$value";
	}
	*/
	foreach($mode_moy_perso as $tmp_id_groupe => $tmp_mode_moy) {
		echo "&amp;mode_moy_perso[$tmp_id_groupe]=$tmp_mode_moy";
	}
}

if((isset($avec_moy_gen_periodes_precedentes))&&($avec_moy_gen_periodes_precedentes=="y")) {
	echo "&amp;avec_moy_gen_periodes_precedentes=y";
}
//echo "'>CSV</a>
echo "'>Export CSV</a>
</div>\n";

// Pour ajouter une marge:
echo "<div id='div_prepa_conseil_vtn'";
if(isset($_POST['vtn_pref_marges'])) {
	$vtn_pref_marges=preg_replace('/[^0-9]/','',$_POST['vtn_pref_marges']);
	if($vtn_pref_marges!='') {
		echo " style='margin:".$vtn_pref_marges."px;'";
	}
	// Pour permettre de ne pas inserer de margin et memoriser ce choix, on accepte le champ vide:
	$_SESSION['vtn_pref_marges']=$vtn_pref_marges;
}
echo ">\n";

// Affichage de la l�gende de la colorisation
if($vtn_coloriser_resultats=='y') {
	echo "<div class='noprint' style='float: right; width: 10em; text-align: center; padding-bottom:3px;'>\n";

	echo "<p class='bold' style='text-align:center;'>L�gende de la colorisation</p>\n";
	$legende_colorisation="<table class='boireaus' summary='L�gende de la colorisation'>\n";
	$legende_colorisation.="<thead>\n";
		$legende_colorisation.="<tr>\n";
		$legende_colorisation.="<th>Borne<br />sup�rieure</th>\n";
		$legende_colorisation.="<th>Couleur texte</th>\n";
		$legende_colorisation.="<th>Couleur cellule</th>\n";
		$legende_colorisation.="</tr>\n";
	$legende_colorisation.="</thead>\n";
	$legende_colorisation.="<tbody>\n";
	$alt=1;
	foreach($vtn_borne_couleur as $key => $value) {
		$alt=$alt*(-1);
		$legende_colorisation.="<tr class='lig$alt'>\n";
		$legende_colorisation.="<td>$vtn_borne_couleur[$key]</td>\n";
		$legende_colorisation.="<td style='color:$vtn_couleur_texte[$key]'>$vtn_couleur_texte[$key]</td>\n";
		$legende_colorisation.="<td style='color:$vtn_couleur_cellule[$key]'>$vtn_couleur_cellule[$key]</td>\n";
		$legende_colorisation.="</tr>\n";
	}
	$legende_colorisation.="</tbody>\n";
	$legende_colorisation.="</table>\n";

	echo $legende_colorisation;
echo "</div>\n";
}

if ($referent == "une_periode") {
	echo "<p class=bold>Classe : $classe - R�sultats : $nom_periode[$num_periode] - Ann�e scolaire : ".getSettingValue("gepiYear")."</p>";
} else {
	echo "<p class=bold>Classe : $classe - R�sultats : Moyennes annuelles - Ann�e scolaire : ".getSettingValue("gepiYear")."</p>";
}

//echo "\$affiche_categories=$affiche_categories<br />";

affiche_tableau($nb_lignes_tableau, $nb_col, $ligne1, $col, $larg_tab, $bord,0,1,$couleur_alterne);

//if(isset($note_sup_10)) {
if($temoin_note_sup10=='y') {
	//if(count($note_sup_10)==1) {
	if($nb_note_sup_10==1) {
		echo "<p>Une mati�re n'est compt�e que pour les notes sup�rieures � 10.</p>\n";
	}
	else {
		//echo "<p>".count($note_sup_10)." mati�res ne sont compt�es que pour les notes sup�rieures � 10.</p>\n";
		echo "<p>".$nb_note_sup_10." mati�res ne sont compt�es que pour les notes sup�rieures � 10.</p>\n";
	}
}

if($temoin_note_bonus=='y') {
	if($temoin_note_bonus==1) {
		echo "<p>Il y a une mati�re � bonus&nbsp;: ";
	}
	else {
		echo "<p>Il y a ".$temoin_note_bonus." mati�res � bonus&nbsp;: ";
	}

	echo "seuls les points au-dessus de 10/20 comptent (<em>�ventuellement pond�r�s</em>), mais leur coefficient n'est pas int�gr� dans le total des coefficients. (<em>r�gle appliqu�e aux options du Baccalaur�at, par ex.</em>)'</p>\n";
}

if($vtn_coloriser_resultats=='y') {
	echo "<p class='bold'>L�gende de la colorisation&nbsp;:</p>\n";
	echo $legende_colorisation;
}
echo "<p><br /></p>\n";

echo "</div>\n"; // Fin du div_prepa_conseil_vtn

//=======================================================
// MODIF: boireaus 20080424
// Pour permettre de trier autrement...
echo "\n<!-- Formulaire pour l'affichage avec tri sur la colonne cliqu�e -->\n";
echo "<form enctype=\"multipart/form-data\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" name=\"formulaire_tri\">\n";
echo add_token_field();

echo "<input type='hidden' name='col_tri' id='col_tri' value='' />\n";
echo "<input type='hidden' name='sens_tri' id='sens_tri' value='' />\n";

echo "<input type='hidden' name='id_classe' value='$id_classe' />\n";
echo "<input type='hidden' name='num_periode' value='$num_periode' />\n";

if(isset($_POST['aff_abs'])) {
	echo "<input type='hidden' name='aff_abs' value='".$_POST['aff_abs']."' />\n";
}
if(isset($_POST['aff_reg'])) {
	echo "<input type='hidden' name='aff_reg' value='".$_POST['aff_reg']."' />\n";
}
if(isset($_POST['aff_doub'])) {
	echo "<input type='hidden' name='aff_doub' value='".$_POST['aff_doub']."' />\n";
}
if(isset($_POST['aff_rang'])) {
	echo "<input type='hidden' name='aff_rang' value='".$_POST['aff_rang']."' />\n";
}
if(isset($_POST['aff_date_naiss'])) {
	echo "<input type='hidden' name='aff_date_naiss' value='".$_POST['aff_date_naiss']."' />\n";
}

if($utiliser_coef_perso=='y') {
	echo "<input type='hidden' name='utiliser_coef_perso' value='$utiliser_coef_perso' />\n";
	foreach($coef_perso as $key => $value) {
		echo "<input type='hidden' name='coef_perso[$key]' value='$value' />\n";
	}
	if(isset($note_sup_10)) {
		foreach($note_sup_10 as $key => $value) {
			echo "<input type='hidden' name='note_sup_10[$key]' value='$value' />\n";
		}
	}
}

if($vtn_coloriser_resultats=='y') {
	echo "<input type='hidden' name='vtn_coloriser_resultats' value='$vtn_coloriser_resultats' />\n";
	foreach($vtn_couleur_texte as $key => $value) {
		echo "<input type='hidden' name='vtn_couleur_texte[$key]' value='$value' />\n";
	}
	foreach($vtn_couleur_cellule as $key => $value) {
		echo "<input type='hidden' name='vtn_couleur_cellule[$key]' value='$value' />\n";
	}
	foreach($vtn_borne_couleur as $key => $value) {
		echo "<input type='hidden' name='vtn_borne_couleur[$key]' value='$value' />\n";
	}
}

echo "<input type='hidden' name='larg_tab' value='$larg_tab' />\n";
echo "<input type='hidden' name='bord' value='$bord' />\n";
echo "<input type='hidden' name='couleur_alterne' value='$couleur_alterne' />\n";

echo "</form>\n";

if(isset($col_tri)) {
	echo "<script type='text/javascript'>
	if(document.getElementById('td_ligne1_$col_tri')) {
		document.getElementById('td_ligne1_$col_tri').style.backgroundColor='white';
	}
</script>\n";
}
else {
	echo "<script type='text/javascript'>
	if(document.getElementById('td_ligne1_1')) {
		document.getElementById('td_ligne1_1').style.backgroundColor='white';
	}
</script>\n";

	// Infobulle
/*
	echo creer_div_infobulle("div_stop","","","Ce bouton permet s'il est coch� d'interrompre les passages automatiques � la page suivante","",12,0,"n","n","y","n");
	$texte.="</div>\n";
	$texte.="</form>\n";
*/
	$titre="Informations";
	$texte="<p>Cette page affiche les moyennes des �l�ves de la classe de ".$classe.".</p>";
	$texte.="<ul>";
	$texte.="<li>Vous pouvez trier ce tableau � la demande&nbsp;: chaque intitul� de colonne est une clef de tri.</li>";
	$texte.="<li>Vous pouvez aussi exporter ces moyennes au format CSV (<i>lisible par un tableur</i>).</li>";
	$texte.="</ul>";
	//$texte.="";
	//$tabdiv_infobulle[]=creer_div_infobulle('div_informations',$titre,"",$texte,"",35,0,'y','y','n','n');
	$class_special_infobulle="noprint";
	echo creer_div_infobulle('div_informations',$titre,"",$texte,"",35,0,'y','y','n','n');
	$class_special_infobulle="";

	echo "<script type='text/javascript'>
	// Je ne saisis pas pourquoi la capture des mouvements ne fonctionne pas correctement ici???
	// En fait, il y avait un probl�me d'initialisation de xMousePos et yMousePos (corrig� dans position.js)
	//setTimeout(\"if(document.getElementById('div_informations')) {document.onmousemove=crob_position;afficher_div('div_informations','y',20,20);}\",1500);
	setTimeout(\"if(document.getElementById('div_informations')) {afficher_div('div_informations','y',20,20);}\",1500);
</script>\n";

}
//=======================================================

echo "<div class='noprint'>\n";
//===========================================================
echo "<p><em>NOTE&nbsp;:</em></p>\n";
require("../lib/textes.inc.php");
echo "<p style='margin-left: 3em;'>$explication_bulletin_ou_graphe_vide";
echo "<br />\n";
echo "Vous pouvez aussi consulter les moyennes des carnets de notes � un instant T avant la fin de p�riode via <a href='../cahier_notes/index2.php?id_classe=$id_classe'>Visualisation des moyennes des carnets de notes</a> tout en sachant qu'avant la fin de p�riode, toutes les notes ne sont pas encore n�cessairement saisies... et que par cons�quent les informations obtenues peuvent �tre remises en cause par les r�sultats saisis par la suite.";
echo "</p>\n";
//===========================================================
echo "</div>\n";

require("../lib/footer.inc.php");
?>
