<?php
/*
 * $Id$
 *
 * Copyright 2001, 2007 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
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
@set_time_limit(0);



// Initialisations files
require_once("../lib/initialisations.inc.php");

extract($_GET, EXTR_OVERWRITE);
extract($_POST, EXTR_OVERWRITE);

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


// Ebauche de liste des variables re�ues:
// $choix_edit correspond au choix de ce qui doit �tre affich�:
// Pour $choix_edit=1:
//    - Tous les �l�ves que le prof a en cours, ou rattach� � une classe qu'a le prof, ou tous les �l�ves selon le choix param�tr� en admin dans Droits d'acc�s
//    - En compte scolarit� ou cpe: Tous les �l�ves de la classe
// $choix_edit=2
//    - Uniquement l'�l�ve s�lectionn�: la variable $login_eleve, qui est de toute fa�on affect�e, doit alors �tre prise en compte pour limiter l'affichage � cet �l�ve
// $choix_edit=3
//    - Ce choix correspond aux classes avec plusieur professeurs principaux
//      On a alors une variable $login_prof affect�e pour limiter les affichages aux �l�ves suivi par un des profs principaux seulement
//      Cette variable $login_prof ne devrait �tre prise en compte que dans le cas $choix_edit==3
// $choix_edit=4
//    - Affichage du bulletin des avis sur la classe


// V�rification sur $id_classe
if(!isset($id_classe)) {
	header("Location: ../accueil.php?msg=Classe non choisie pour les bulletins simplifi�s");
	die();
}
elseif(!is_numeric($id_classe)) {
	header("Location: ../accueil.php?msg=Classe invalide ($id_classe) pour les bulletins simplifi�s");
	die();
}
$nom_classe=get_nom_classe($id_classe);
if(!$nom_classe) {
	header("Location: ../accueil.php?msg=Classe invalide ($id_classe) pour les bulletins simplifi�s");
	die();
}


include "../lib/periodes.inc.php";
include "../lib/bulletin_simple.inc.php";
//include "../lib/bulletin_simple_bis.inc.php";
//==============================
// AJOUT: boireaus 20080209
include "../lib/bulletin_simple_classe.inc.php";
//include "../lib/bulletin_simple_classe_bis.inc.php";
//==============================
require_once("../lib/header.inc");

// V�rifications de s�curit�
if (
	($_SESSION['statut'] == "responsable" AND getSettingValue("GepiAccesBulletinSimpleParent") != "yes") OR
	($_SESSION['statut'] == "eleve" AND getSettingValue("GepiAccesBulletinSimpleEleve") != "yes")
	) {
	tentative_intrusion(2, "Tentative de visualisation d'un bulletin simplifi� sans y �tre autoris�.");
	echo "<p>Vous n'�tes pas autoris� � visualiser cette page.</p>";
	require "../lib/footer.inc.php";
	die();
}

// Et une autre v�rification de s�curit� : est-ce que si on a un statut 'responsable' le $login_eleve est bien un �l�ve dont le responsable a la responsabilit�
if ($_SESSION['statut'] == "responsable") {
	$test = mysql_query("SELECT count(e.login) " .
			"FROM eleves e, responsables2 re, resp_pers r " .
			"WHERE (" .
			"e.login = '" . $login_eleve . "' AND " .
			"e.ele_id = re.ele_id AND " .
			"re.pers_id = r.pers_id AND " .
			"r.login = '" . $_SESSION['login'] . "' AND (re.resp_legal='1' OR re.resp_legal='2'))");
	if (mysql_result($test, 0) == 0) {
	    tentative_intrusion(3, "Tentative d'un parent de visualiser un bulletin simplifi� d'un �l�ve ($login_eleve) dont il n'est pas responsable l�gal.");
	    echo "Vous ne pouvez visualiser que les bulletins simplifi�s des �l�ves pour lesquels vous �tes responsable l�gal.\n";
	    require("../lib/footer.inc.php");
		die();
	}
}

// Et une autre...
if ($_SESSION['statut'] == "eleve" AND strtoupper($_SESSION['login']) != strtoupper($login_eleve)) {
    tentative_intrusion(3, "Tentative d'un �l�ve de visualiser un bulletin simplifi� d'un autre �l�ve ($login_eleve).");
    echo "Vous ne pouvez visualiser que vos bulletins simplifi�s.\n";
    require("../lib/footer.inc.php");
	die();
}

// Et encore une : si on a un reponsable ou un �l�ve, alors seul l'�dition pour un �l�ve seul est autoris�e
if (($_SESSION['statut'] == "responsable" OR $_SESSION['statut'] == "eleve") AND $choix_edit != "2") {
    tentative_intrusion(3, "Tentative (�l�ve ou parent) de changement du mode de visualisation d'un bulletin simplifi� (le mode impos� est la visualisation pour un seul �l�ve)");
    echo "N'essayez pas de tricher...\n";
    require("../lib/footer.inc.php");
	die();
}

//if ($_SESSION['statut'] == "professeur" AND getSettingValue("GepiAccesMoyennesProfToutesClasses") != "yes") {
if ($_SESSION['statut'] == "professeur" AND getSettingValue("GepiAccesBulletinSimpleProfToutesClasses") != "yes") {
	$test = mysql_num_rows(mysql_query("SELECT jgc.* FROM j_groupes_classes jgc, j_groupes_professeurs jgp WHERE (jgp.login='".$_SESSION['login']."' AND jgc.id_groupe = jgp.id_groupe AND jgc.id_classe = '".$id_classe."')"));
	if ($test == "0") {
		tentative_intrusion("2", "Tentative d'acc�s par un prof � une classe (".$nom_classe.") dans laquelle il n'enseigne pas, sans en avoir l'autorisation.");
		echo "Vous ne pouvez pas acc�der � cette classe car vous n'y �tes pas professeur !";
		require ("../lib/footer.inc.php");
		die();
	}
}

//if ($_SESSION['statut'] == "professeur" AND getSettingValue("GepiAccesMoyennesProfToutesClasses") != "yes" AND getSettingValue("GepiAccesMoyennesProfTousEleves") != "yes" and $choix_edit == "2") {
if ($_SESSION['statut'] == "professeur" AND
getSettingValue("GepiAccesBulletinSimpleProfToutesClasses") != "yes" AND
getSettingValue("GepiAccesBulletinSimpleProfTousEleves") != "yes" AND
$choix_edit == "2") {

	$test = mysql_num_rows(mysql_query("SELECT jeg.* FROM j_eleves_groupes jeg, j_groupes_professeurs jgp WHERE (jgp.login='".$_SESSION['login']."' AND jeg.id_groupe = jgp.id_groupe AND jeg.login = '".$login_eleve."')"));
	if ($test == "0") {
		tentative_intrusion("2", "Tentative d'acc�s par un prof � un bulletin simplifi� d'un �l�ve ($login_eleve) qu'il n'a pas en cours, sans en avoir l'autorisation.");
		echo "Vous ne pouvez pas acc�der � cet �l�ve !";
		require ("../lib/footer.inc.php");
		die();
	}
}

//debug_var();

// On a pass� les barri�res, on passe au traitement

$gepiYear = getSettingValue("gepiYear");

if ($periode1 > $periode2) {
  $temp = $periode2;
  $periode2 = $periode1;
  $periode1 = $temp;
}

// On teste la pr�sence d'au moins un coeff pour afficher la colonne des coef
$test_coef = mysql_num_rows(mysql_query("SELECT coef FROM j_groupes_classes WHERE (id_classe='".$id_classe."' and coef > 0)"));
//echo "\$test_coef=$test_coef<br />";
// Apparemment, $test_coef est r�affect� plus loin dans un des include()
$nb_coef_superieurs_a_zero=$test_coef;


// On regarde si on affiche les cat�gories de mati�res
$affiche_categories = sql_query1("SELECT display_mat_cat FROM classes WHERE id='".$id_classe."'");
if ($affiche_categories == "y") { $affiche_categories = true; } else { $affiche_categories = false;}


// Si le rang des �l�ves est demand�, on met � jour le champ rang de la table matieres_notes
$affiche_rang = sql_query1("SELECT display_rang FROM classes WHERE id='".$id_classe."'");
if ($affiche_rang == 'y') {
    $periode_num=$periode1;
    while ($periode_num < $periode2+1) {
        include "../lib/calcul_rang.inc.php";
        $periode_num++;
    }
}

/*
// On regarde si on affiche les cat�gories de mati�res
$affiche_categories = sql_query1("SELECT display_mat_cat FROM classes WHERE id='".$id_classe."'");
if ($affiche_categories == "y") { $affiche_categories = true; } else { $affiche_categories = false;}
*/
//echo "\$choix_edit=$choix_edit<br />";

//=========================
// AJOUT: boireaus 20080316
$coefficients_a_1="non";
$affiche_graph = 'n';
/*
$get_cat = mysql_query("SELECT id FROM matieres_categories");
$categories = array();
while ($row = mysql_fetch_array($get_cat, MYSQL_ASSOC)) {
  	$categories[] = $row["id"];
}
*/

//$affiche_deux_moy_gen=1;

for($loop=$periode1;$loop<=$periode2;$loop++) {
	$periode_num=$loop;
	include "../lib/calcul_moy_gen.inc.php";

	$tab_moy['periodes'][$periode_num]=array();
	$tab_moy['periodes'][$periode_num]['tab_login_indice']=$tab_login_indice;         // [$login_eleve]
	$tab_moy['periodes'][$periode_num]['moy_gen_eleve']=$moy_gen_eleve;               // [$i]
	$tab_moy['periodes'][$periode_num]['moy_gen_eleve1']=$moy_gen_eleve1;             // [$i]
	//$tab_moy['periodes'][$periode_num]['moy_gen_classe1']=$moy_gen_classe1;           // [$i]
	$tab_moy['periodes'][$periode_num]['moy_generale_classe']=$moy_generale_classe;
	$tab_moy['periodes'][$periode_num]['moy_generale_classe1']=$moy_generale_classe1;
	$tab_moy['periodes'][$periode_num]['moy_max_classe']=$moy_max_classe;
	$tab_moy['periodes'][$periode_num]['moy_min_classe']=$moy_min_classe;

	// Il faudrait r�cup�rer/stocker les cat�gories?
	$tab_moy['periodes'][$periode_num]['moy_cat_eleve']=$moy_cat_eleve;               // [$i][$cat]
	$tab_moy['periodes'][$periode_num]['moy_cat_classe']=$moy_cat_classe;             // [$i][$cat]
	$tab_moy['periodes'][$periode_num]['moy_cat_min']=$moy_cat_min;                   // [$i][$cat]
	$tab_moy['periodes'][$periode_num]['moy_cat_max']=$moy_cat_max;                   // [$i][$cat]

	$tab_moy['periodes'][$periode_num]['quartile1_classe_gen']=$quartile1_classe_gen;
	$tab_moy['periodes'][$periode_num]['quartile2_classe_gen']=$quartile2_classe_gen;
	$tab_moy['periodes'][$periode_num]['quartile3_classe_gen']=$quartile3_classe_gen;
	$tab_moy['periodes'][$periode_num]['quartile4_classe_gen']=$quartile4_classe_gen;
	$tab_moy['periodes'][$periode_num]['quartile5_classe_gen']=$quartile5_classe_gen;
	$tab_moy['periodes'][$periode_num]['quartile6_classe_gen']=$quartile6_classe_gen;
	$tab_moy['periodes'][$periode_num]['place_eleve_classe']=$place_eleve_classe;

	$tab_moy['periodes'][$periode_num]['current_eleve_login']=$current_eleve_login;   // [$i]
	//$tab_moy['periodes'][$periode_num]['current_group']=$current_group;
	if($loop==$periode1) {
		$tab_moy['current_group']=$current_group;                                     // [$j]
	}
	$tab_moy['periodes'][$periode_num]['current_eleve_note']=$current_eleve_note;     // [$j][$i]
	$tab_moy['periodes'][$periode_num]['current_eleve_statut']=$current_eleve_statut; // [$j][$i]
	//$tab_moy['periodes'][$periode_num]['current_group']=$current_group;
	$tab_moy['periodes'][$periode_num]['current_coef']=$current_coef;                 // [$j]
	$tab_moy['periodes'][$periode_num]['current_classe_matiere_moyenne']=$current_classe_matiere_moyenne; // [$j]

	$tab_moy['periodes'][$periode_num]['current_coef_eleve']=$current_coef_eleve;     // [$i][$j] ATTENTION
	$tab_moy['periodes'][$periode_num]['moy_min_classe_grp']=$moy_min_classe_grp;     // [$j]
	$tab_moy['periodes'][$periode_num]['moy_max_classe_grp']=$moy_max_classe_grp;     // [$j]
	if(isset($current_eleve_rang)) {
		// $current_eleve_rang n'est pas renseign� si $affiche_rang='n'
		$tab_moy['periodes'][$periode_num]['current_eleve_rang']=$current_eleve_rang; // [$j][$i]
	}
	$tab_moy['periodes'][$periode_num]['quartile1_grp']=$quartile1_grp;               // [$j]
	$tab_moy['periodes'][$periode_num]['quartile2_grp']=$quartile2_grp;               // [$j]
	$tab_moy['periodes'][$periode_num]['quartile3_grp']=$quartile3_grp;               // [$j]
	$tab_moy['periodes'][$periode_num]['quartile4_grp']=$quartile4_grp;               // [$j]
	$tab_moy['periodes'][$periode_num]['quartile5_grp']=$quartile5_grp;               // [$j]
	$tab_moy['periodes'][$periode_num]['quartile6_grp']=$quartile6_grp;               // [$j]
	$tab_moy['periodes'][$periode_num]['place_eleve_grp']=$place_eleve_grp;           // [$j][$i]

	$tab_moy['periodes'][$periode_num]['current_group_effectif_avec_note']=$current_group_effectif_avec_note; // [$j]

/*
// De calcul_moy_gen.inc.php, on r�cup�re en sortie:
//     - $moy_gen_eleve[$i]
//     - $moy_gen_eleve1[$i] idem avec les coef forc�s � 1
//     - $moy_gen_classe[$i]
//     - $moy_gen_classe1[$i] idem avec les coef forc�s � 1
//     - $moy_generale_classe
//     - $moy_max_classe
//     - $moy_min_classe

// A VERIFIER, mais s'il n'y a pas de coef sp�cifique pour un �l�ve, on devrait avoir
//             $moy_gen_classe[$i] == $moy_generale_classe
// NON: Cela correspond � un mode de calcul qui ne retient que les mati�res suivies par l'�l�ve pour calculer la moyenne g�n�rale
//      Le LATIN n'est pas compt� dans cette moyenne g�n�rale si l'�l�ve ne fait pas latin.
//      L'Allemand n'est pas comptabilis� si l'�l�ve ne fait pas allemand
// FAIRE LE TOUR DES PAGES POUR VIRER TOUS CES $moy_gen_classe s'il en reste?

//     - $moy_cat_classe[$i][$cat]
//     - $moy_cat_eleve[$i][$cat]

//     - $moy_cat_min[$i][$cat] �gale � $moy_min_categorie[$cat]
//     - $moy_cat_max[$i][$cat] �gale � $moy_max_categorie[$cat]

// L� le positionnement au niveau moyenne g�n�rale:
//     - $quartile1_classe_gen
//       �
//     - $quartile6_classe_gen
//     - $place_eleve_classe[$i]

// On a r�cup�r� en interm�diaire les
//     - $current_eleve_login[$i]
//     - $current_group[$j]
//     - $current_eleve_note[$j][$i]
//     - $current_eleve_statut[$j][$i]
//     - $current_coef[$j] (qui peut �tre diff�rent du $coef_eleve pour une mati�re sp�cifique)
//     - $categories -> id
//     - $current_classe_matiere_moyenne[$j] (moyenne de la classe dans la mati�re)

// AJOUT�:
//     - $current_coef_eleve[$i][$j]
//     - $moy_min_classe_grp[$j]
//     - $moy_max_classe_grp[$j]
//     - $current_eleve_rang[$j][$i] sous r�serve que $affiche_rang=='y'
//     - $quartile1_grp[$j] � $quartile6_grp[$j]
//     - $place_eleve_grp[$j][$i]
//     - $current_group_effectif_avec_note[$j] pour le nombre de "vraies" moyennes pour le rang (pas disp, abs,...)
//     - $tab_login_indice[LOGIN_ELEVE]=$i

//     $categories[] = $row["id"];
//     $tab_noms_categories[$row["id"]]=$row["nom_complet"];
//     $tab_id_categories[$row["nom_complet"]]=$row["id"];

*/

}

$tab_moy['categories']['id']=$categories;
$tab_moy['categories']['nom_from_id']=$tab_noms_categories;
$tab_moy['categories']['id_from_nom']=$tab_id_categories;


$sql="SELECT DISTINCT e.*
FROM eleves e, j_eleves_classes c 
WHERE (
c.id_classe='$id_classe' AND 
e.login = c.login
) ORDER BY e.nom,e.prenom;";
$res_ele= mysql_query($sql);
if(mysql_num_rows($res_ele)>0) {
	while($lig_ele=mysql_fetch_object($res_ele)) {
		$tab_moy['eleves'][]=$lig_ele->login;
		/*
		$tab_moy['ele'][$lig_ele->login]=array();
		$tab_moy['ele'][$lig_ele->login]['nom']=$lig_ele->nom;
		$tab_moy['ele'][$lig_ele->login]['prenom']=$lig_ele->prenom;
		$tab_moy['ele'][$lig_ele->login]['sexe']=$lig_ele->sexe;
		$tab_moy['ele'][$lig_ele->login]['naissance']=$lig_ele->naissance;
		$tab_moy['ele'][$lig_ele->login]['elenoet']=$lig_ele->elenoet;
		*/
	}
}

$display_moy_gen=sql_query1("SELECT display_moy_gen FROM classes WHERE id='".$id_classe."';");

$affiche_coef=sql_query1("SELECT display_coef FROM classes WHERE id='".$id_classe."';");

if(!getSettingValue("bull_intitule_app")){
	$bull_intitule_app="Appr�ciations/Conseils";
}
else{
	$bull_intitule_app=getSettingValue("bull_intitule_app");
}

//=========================
// Sauvegarde le temps de la session des param�tres pour le passage d'une classe � une autre
$_SESSION['choix_edit']=$choix_edit;
$_SESSION['periode1']=$periode1;
$_SESSION['periode2']=$periode2;
if(isset($login_prof)) {$_SESSION['login_prof']=$login_prof;}
//=========================

if ($choix_edit == '2') {
    //bulletin($login_eleve,1,1,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$test_coef,$affiche_categories);
    //bulletin($login_eleve,1,1,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$nb_coef_superieurs_a_zero,$affiche_categories);

    //bulletin_bis($tab_moy,$login_eleve,1,1,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$nb_coef_superieurs_a_zero,$affiche_categories);
    bulletin($tab_moy,$login_eleve,1,1,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$nb_coef_superieurs_a_zero,$affiche_categories);
}

if ($choix_edit != '2') {
	// Si on arrive l�, on n'est ni �l�ve, ni responsable

	//if ($_SESSION['statut'] == "professeur" AND getSettingValue("GepiAccesMoyennesProfTousEleves") != "yes" AND getSettingValue("GepiAccesMoyennesProfToutesClasses") != "yes") {
	if ($_SESSION['statut'] == "professeur" AND
	getSettingValue("GepiAccesBulletinSimpleProfToutesClasses") != "yes" AND
	getSettingValue("GepiAccesBulletinSimpleProfTousEleves") != "yes") {

		// On ne s�lectionne que les �l�ves que le professeur a en cours
	    //if ($choix_edit == '1') {
	    if (($choix_edit == '1')||(!isset($login_prof))) {
			// On a alors $choix_edit==1 ou $choix_edit==4
	        $appel_liste_eleves = mysql_query("SELECT DISTINCT e.* " .
				"FROM eleves e, j_eleves_classes jec, j_eleves_groupes jeg, j_groupes_professeurs jgp " .
				"WHERE (" .
				"jec.id_classe='$id_classe' AND " .
				"e.login = jeg.login AND " .
				"jeg.login = jec.login AND " .
				"jeg.id_groupe = jgp.id_groupe AND " .
				"jgp.login = '".$_SESSION['login']."') " .
				"ORDER BY e.nom,e.prenom");
	    } else {
			// On a alors $choix_edit==3 uniquement les �l�ves du professeur principal $login_prof
	        $appel_liste_eleves = mysql_query("SELECT DISTINCT e.* " .
				"FROM eleves e, j_eleves_classes jec, j_eleves_groupes jeg, j_groupes_professeurs jgp, j_eleves_professeurs jep " .
				"WHERE (" .
				"jec.id_classe='$id_classe' AND " .
				"e.login = jeg.login AND " .
				"jeg.login = p.login AND " .
				"p.professeur = '".$login_prof."'" .
				"p.login = jec.login AND " .
				"jeg.id_groupe = jgp.id_groupe AND " .
				"jgp.login = '".$_SESSION['login']."') " .
				"ORDER BY e.nom,e.prenom");
	    }
	} else {
	    // On s�lectionne sans restriction

	    //if ($choix_edit == '1') {
	    if (($choix_edit == '1')||(!isset($login_prof))) {
			// On a alors $choix_edit==1 ou $choix_edit==4
	        $appel_liste_eleves = mysql_query("SELECT DISTINCT e.* " .
	        		"FROM eleves e, j_eleves_classes c " .
	        		"WHERE (" .
	        		"c.id_classe='$id_classe' AND " .
	        		"e.login = c.login" .
	        		") ORDER BY e.nom,e.prenom");
	    } else {
			// On a alors $choix_edit==3
	        $appel_liste_eleves = mysql_query("SELECT DISTINCT e.* " .
	        		"FROM eleves e, j_eleves_classes c, j_eleves_professeurs p " .
	        		"WHERE (" .
	        		"c.id_classe='$id_classe' AND " .
	        		"e.login = c.login AND " .
	        		"p.login=c.login AND " .
	        		"p.professeur='$login_prof'" .
	        		") ORDER BY e.nom,e.prenom");
		}
	}

    $nombre_eleves = mysql_num_rows($appel_liste_eleves);

	//=========================
	// AJOUT: boireaus 20080209
	// Affichage des appr�ciations saisies pour la classe
	//echo "2 \$test_coef=$test_coef<br />";
	//bulletin_classe($nombre_eleves,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$test_coef,$affiche_categories);
	//bulletin_classe($nombre_eleves,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$nb_coef_superieurs_a_zero,$affiche_categories);
	//bulletin_classe_bis($tab_moy,$nombre_eleves,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$nb_coef_superieurs_a_zero,$affiche_categories);
	bulletin_classe($tab_moy,$nombre_eleves,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$nb_coef_superieurs_a_zero,$affiche_categories);
	if ($choix_edit == '4') {
		require("../lib/footer.inc.php");
		die();
	}
	echo "<p class=saut>&nbsp;</p>\n";
	//=========================

    $i=0;
    $k=0;
    while ($i < $nombre_eleves) {
        $current_eleve_login = mysql_result($appel_liste_eleves, $i, "login");
        $k++;
        //bulletin($current_eleve_login,$k,$nombre_eleves,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$test_coef,$affiche_categories);
        //bulletin($current_eleve_login,$k,$nombre_eleves,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$nb_coef_superieurs_a_zero,$affiche_categories);
        //bulletin_bis($tab_moy,$current_eleve_login,$k,$nombre_eleves,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$nb_coef_superieurs_a_zero,$affiche_categories);
        bulletin($tab_moy,$current_eleve_login,$k,$nombre_eleves,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$nb_coef_superieurs_a_zero,$affiche_categories);
        if ($i != $nombre_eleves-1) {echo "<p class=saut>&nbsp;</p>";}
        $i++;
    }

}

echo "<div class='noprint'>\n";
//===========================================================
echo "<p><em>NOTE&nbsp;:</em></p>\n";
require("../lib/textes.inc.php");
echo "<p style='margin-left: 3em;'>$explication_bulletin_ou_graphe_vide</p>\n";
//===========================================================
echo "</div>\n";

require("../lib/footer.inc.php");
?>
