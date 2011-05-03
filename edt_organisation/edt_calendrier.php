<?php

/*
 *
 * @version $Id$
 *
 * Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Julien Jocal
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
 *
 * Fichier destin� � param�trer le calendrier de Gepi pour l'Emploi du temps
 */

$titre_page = "Emploi du temps - Calendrier";
$affiche_connexion = 'yes';
$niveau_arbo = 1;

// Initialisations files
require_once("../lib/initialisations.inc.php");

// fonctions edt
require_once("./fonctions_edt.php");

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
   header("Location:utilisateurs/mon_compte.php?change_mdp=yes&retour=accueil#changemdp");
   die();
} else if ($resultat_session == '0') {
    header("Location: ../logout.php?auto=1");
    die();
}

// S�curit�
if (!checkAccess()) {
    header("Location: ../logout.php?auto=2");
    die();
}
// S�curit� suppl�mentaire par rapport aux param�tres du module EdT / Calendrier
if (param_edt($_SESSION["statut"]) != "yes") {
	Die(ASK_AUTHORIZATION_TO_ADMIN);
}

	// Initialisation des variables
	
	
$data = Array();	
	
$data['calendrier'] = isset($_GET["calendrier"]) ? $_GET["calendrier"] : (isset($_POST["calendrier"]) ? $_POST["calendrier"] : NULL);
$data['new_periode'] = isset($_GET['new_periode']) ? $_GET['new_periode'] : (isset($_POST['new_periode']) ? $_POST['new_periode'] : NULL);
$data['nom_periode'] = isset($_POST["nom_periode"]) ? $_POST["nom_periode"] : NULL;
$data['classes_concernees'] = isset($_POST["classes_concernees"]) ? $_POST["classes_concernees"] : NULL;
$data['jour_debut'] = isset($_POST["jour_debut"]) ? $_POST["jour_debut"] : NULL;
$data['jour_fin'] = isset($_POST["jour_fin"]) ? $_POST["jour_fin"] : NULL;
$data['jour_dperiode'] = isset($_POST["jour_dperiode"]) ? $_POST["jour_dperiode"] : NULL;
//$data['mois_dperiode'] = isset($_POST["mois_dperiode"]) ? $_POST["mois_dperiode"] : NULL;
//$data['annee_dperiode'] = isset($_POST["annee_dperiode"]) ? $_POST["annee_dperiode"] : NULL;
$data['heure_debut'] = isset($_POST["heure_deb"]) ? $_POST["heure_deb"] : NULL;
$data['jour_fperiode'] = isset($_POST["jour_fperiode"]) ? $_POST["jour_fperiode"] : NULL;
//$data['mois_fperiode'] = isset($_POST["mois_fperiode"]) ? $_POST["mois_fperiode"] : NULL;
//$data['annee_fperiode'] = isset($_POST["annee_fperiode"]) ? $_POST["annee_fperiode"] : NULL;
$data['heure_fin'] = isset($_POST["heure_fin"]) ? $_POST["heure_fin"] : NULL;
$data['choix_periode'] = isset($_POST["choix_periode"]) ? $_POST["choix_periode"] : NULL;
$data['etabferme'] = isset($_POST["etabferme"]) ? $_POST["etabferme"] : NULL;
$data['vacances'] = isset($_POST["vacances"]) ? $_POST["vacances"] : NULL;
$data['supprimer'] = isset($_GET["supprimer"]) ? $_GET["supprimer"] : NULL;
$data['modifier'] = isset($_GET["modifier"]) ? $_GET["modifier"] : (isset($_POST["modifier"]) ? $_POST["modifier"] : NULL);
$data['copier_edt'] = isset($_GET["copier_edt"]) ? $_GET["copier_edt"] : (isset($_POST["copier_edt"]) ? $_POST["copier_edt"] : NULL);
$data['coller_edt'] = isset($_GET["coller_edt"]) ? $_GET["coller_edt"] : (isset($_POST["coller_edt"]) ? $_POST["coller_edt"] : NULL);
$data['modif_ok'] = isset($_POST["modif_ok"]) ? $_POST["modif_ok"] : NULL;
$data['message'] = NULL;

	// Quelques variables utiles
$data['annee_actu'] = date("Y"); // ann�e
$data['mois_actu'] = date("m"); // mois sous la forme 01 � 12
$data['jour_actu'] = date("d"); // jour sous la forme 01 � 31
$data['date_jour'] = date("d/m/Y"); //jour/mois/ann�e


// =======================================================================
//
//						Controlleur
//
// =======================================================================

/* ============================================ On efface quand c'est demand� ====================================== */

if (isset($data['calendrier']) AND isset($data['supprimer'])) {

	$req_supp = mysql_query("DELETE FROM edt_calendrier WHERE id_calendrier = '".$data['supprimer']."'") or Die ('Suppression impossible !');
    if ($supprimer != 0) {
        $req_supp_cours = mysql_query("DELETE FROM edt_cours WHERE id_calendrier = '".$data['supprimer']."'") or Die ('Suppression impossible !');
    }

}
/* ============================================ On copie le contenu de l'edt ====================================== */

if (isset($data['calendrier']) AND isset($data['copier_edt'])) {
    $_SESSION['copier_periode_edt'] = $data['copier_edt'];
    $req_edt_periode = mysql_query("SELECT nom_calendrier FROM edt_calendrier WHERE id_calendrier ='".$data['copier_edt']."'");
    $rep_edt_periode = mysql_fetch_array($req_edt_periode);
    $data['message'] = "Le contenu de la p�riode \"".$rep_edt_periode['nom_calendrier']."\" est pr�t � �tre dupliqu�"; 
}

/* ============================================ On colle le contenu de l'edt dans la nouvelle p�riode ====================================== */

if (isset($data['calendrier']) AND isset($data['coller_edt']) AND isset($_SESSION['copier_periode_edt'])) {
    if (PeriodExistsInDB($_SESSION['copier_periode_edt'])) {
        if (PeriodExistsInDB($data['coller_edt'])) {
            if ($data['coller_edt'] != $_SESSION['copier_periode_edt']) {
                $req_edt_periode = mysql_query("SELECT * FROM edt_cours WHERE 
                                                            id_calendrier = '".$_SESSION['copier_periode_edt']."'
                                                            ") or die(mysql_error());  
                $i = 0;
                while ($rep_edt_periode = mysql_fetch_array($req_edt_periode)) {
                    $sql = "SELECT id_cours FROM edt_cours WHERE 
                             id_groupe = '".$rep_edt_periode['id_groupe']."' AND
					         id_salle = '".$rep_edt_periode['id_salle']."' AND
					         jour_semaine = '".$rep_edt_periode['jour_semaine']."' AND
					         id_definie_periode = '".$rep_edt_periode['id_definie_periode']."' AND
					         duree = '".$rep_edt_periode['duree']."' AND
					         heuredeb_dec = '".$rep_edt_periode['heuredeb_dec']."' AND
					         id_semaine = '".$rep_edt_periode['id_semaine']."' AND
					         id_calendrier = '".$data['coller_edt']."' AND
					         login_prof = '".$rep_edt_periode['login_prof']."'
                            ";
                    $verif_existence = mysql_query($sql) OR DIE('Erreur dans la v�rification du cours : '.mysql_error());
                    if (mysql_num_rows($verif_existence) == 0) {
				        $nouveau_cours = mysql_query("INSERT INTO edt_cours SET 
                             id_groupe = '".$rep_edt_periode['id_groupe']."',
					         id_salle = '".$rep_edt_periode['id_salle']."',
					         jour_semaine = '".$rep_edt_periode['jour_semaine']."',
					         id_definie_periode = '".$rep_edt_periode['id_definie_periode']."',
					         duree = '".$rep_edt_periode['duree']."',
					         heuredeb_dec = '".$rep_edt_periode['heuredeb_dec']."',
					         id_semaine = '".$rep_edt_periode['id_semaine']."',
					         id_calendrier = '".$data['coller_edt']."',
					         login_prof = '".$rep_edt_periode['login_prof']."'")
				        OR DIE('Erreur dans la cr�ation du cours : '.mysql_error());
                        $i++;
                    }
                }
                if ($i == 0) {
                    $data['message'] = "la duplication a d�j� �t� r�alis�e";
                }
                else {
                    $data['message'] = "duplication r�alis�e. ".$i." cours ont �t� copi�s avec succ�s";
                }
            }
            else {
                $data['message'] = "vous ne pouvez pas dupliquer une p�riode sur elle-m�me"; 
            } 
        }
        else {
            $data['message'] = "la p�riode cible n'existe pas";
        }
    }
    else {
        $data['message'] = "la p�riode � dupliquer n'existe pas";
    }
}



/* ==================== On traite les nouvelles entr�es dans la table ================ */
if (isset($data['new_periode']) AND isset($data['nom_periode'])) {
	$detail_jourdeb = explode("/", $data['jour_debut']);
	$detail_jourfin = explode("/", $data['jour_fin']);

	// ================== v�rifier le format des dates saisies

	if (isset($detail_jourdeb[0]) AND isset($detail_jourdeb[1]) AND isset($detail_jourdeb[2])) {
		if (isset($detail_jourfin[0]) AND isset($detail_jourfin[1]) AND isset($detail_jourfin[2])) {
			if (is_numeric($detail_jourfin[0]) AND is_numeric($detail_jourfin[1]) AND is_numeric($detail_jourfin[2])) {
				if (is_numeric($detail_jourdeb[0]) AND is_numeric($detail_jourdeb[1]) AND is_numeric($detail_jourdeb[2])) {
					$formatdatevalid = true;
				}
				else {
					$formatdatevalid = false;
				}
			}
			else {
				$formatdatevalid = false;
			}
		}
		else {
			$formatdatevalid = false;
		}
	}
	else {
		$formatdatevalid = false;
	}

	if ($formatdatevalid) {
		$jourdebut = $detail_jourdeb[2]."-".$detail_jourdeb[1]."-".$detail_jourdeb[0];
		$jourfin = $detail_jourfin[2]."-".$detail_jourfin[1]."-".$detail_jourfin[0];
			// On ins�re les classes qui sont concern�es (0 = toutes)
			if ($data['classes_concernees'][0] == "0") {
				$classes_concernees_insert = "0";
			}
			else {
					$classes_concernees_insert = "";
				for ($c=0; $c<count($data['classes_concernees']); $c++) {
					$classes_concernees_insert .= $data['classes_concernees'][$c].";";
				}
			} // else
		// On v�rifie que ce nom de p�riode n'existe pas encore
		$req_verif_periode = mysql_fetch_array(mysql_query("SELECT nom_calendrier FROM edt_calendrier WHERE nom_calendrier = '".$data['nom_periode']."'"));
		if ($req_verif_periode[0] == NULL) {
			$data['heure_debut'] = $data['heure_debut'].":00";
				$expdeb = explode(":", $data['heure_debut']);
			$data['heure_fin'] = $data['heure_fin'].":00";
				$expfin = explode(":", $data['heure_fin']);
				// On ins�re ces dates en timestamp Unix GMT
			$heuredeb_ts = gmmktime($expdeb[0], $expdeb[1], 0, $detail_jourdeb[1], $detail_jourdeb[0], $detail_jourdeb[2])
								OR trigger_error('La date de d�but n\'est pas valide. ', E_USER_WARNING);
			$heurefin_ts = gmmktime($expfin[0], $expfin[1], 0, $detail_jourfin[1], $detail_jourfin[0], $detail_jourfin[2])
								OR trigger_error('La date de fin n\'est pas valide. ', E_USER_WARNING);

			// On v�rifie que tout soit bien rempli et on sauvegarde
			if ($data['nom_periode'] != '' AND $heuredeb_ts != '' AND $heurefin_ts != '') {
				$req_insert = mysql_query("INSERT INTO edt_calendrier (`nom_calendrier`, `classe_concerne_calendrier`, `debut_calendrier_ts`, `fin_calendrier_ts`, `jourdebut_calendrier`, `heuredebut_calendrier`, `jourfin_calendrier`, `heurefin_calendrier`, `numero_periode`, `etabferme_calendrier`, `etabvacances_calendrier`)
								VALUES ('".$data["nom_periode"]."',
										'".$classes_concernees_insert."',
										'".$heuredeb_ts."',
										'".$heurefin_ts."',
										'".$jourdebut."',
										'".$data["heure_debut"]."',
										'".$jourfin."',
										'".$data["heure_fin"]."',
										'".$data["choix_periode"]."',
										'".$data["etabferme"]."',
										'".$data["vacances"]."')")
								OR trigger_error('Echec dans la requ�te de cr�ation d\'une nouvelle entr�e !', E_USER_WARNING);
			}

		}else{

			$data['message'] = "Ce nom de p�riode existe d�j�";
		}
	}
	else {
		$data['message'] = "L'une des dates n'a pas le format attendu.";
	}
}

	// =========== TRAITEMENT de la modification de la p�riode =============
if (isset($data['modif_ok']) AND isset($data['nom_periode'])) {
	$jourdebut = $data['jour_dperiode'];
	$jourfin = $data['jour_fperiode'];
	// traitement du timestamp Unix GMT ainsi que des dates et heures MySql
	$exp_jourdeb = explode("/", $jourdebut);
	$exp_jourfin = explode("/", $jourfin);
	$exp_heuredeb = explode(":", $data['heure_debut']);
	$exp_heurefin = explode(":", $data['heure_fin']);
	$deb_ts = gmmktime($exp_heuredeb[0], $exp_heuredeb[1], 0, $exp_jourdeb[1], $exp_jourdeb[0], $exp_jourdeb[2]);
	$jourdebut = $exp_jourdeb[2]."-".$exp_jourdeb[1]."-".$exp_jourdeb[0];
	$fin_ts = gmmktime($exp_heurefin[0], $exp_heurefin[1], 0, $exp_jourfin[1], $exp_jourfin[0], $exp_jourfin[2]);
	$jourfin = $exp_jourfin[2]."-".$exp_jourfin[1]."-".$exp_jourfin[0];
	$modif_periode = mysql_query("UPDATE edt_calendrier
				SET nom_calendrier = '".traitement_magic_quotes($data['nom_periode'])."',
				classe_concerne_calendrier = '".$classes_concernees_insert."',
				debut_calendrier_ts = '".$deb_ts."',
				fin_calendrier_ts = '".$fin_ts."',
				jourdebut_calendrier = '".$jourdebut."',
				heuredebut_calendrier = '".$data['heure_debut']."',
				jourfin_calendrier = '".$jourfin."',
				heurefin_calendrier = '".$data['heure_fin']."',
				numero_periode = '".$data['choix_periode']."',
				etabferme_calendrier = '".$data['etabferme']."',
				etabvacances_calendrier = '".$data['vacances']."'
				WHERE id_calendrier = '".$data['modif_ok']."'")
				OR DIE ('Erreur dans la modification');
}



$data['req_affcalendar'] = mysql_query("SELECT * FROM edt_calendrier ORDER BY jourdebut_calendrier") OR die ('Impossible d\'afficher le calendrier.');
$data['nbre_affcalendar'] = mysql_num_rows($data['req_affcalendar']);
$a = 1;

for ($i=0; $i<$data['nbre_affcalendar']; $i++) {
	$data['rep_affcalendar'][$i]["id_calendrier"] = mysql_result($data['req_affcalendar'], $i, "id_calendrier");
	$data['rep_affcalendar'][$i]["classe_concerne_calendrier"] = mysql_result($data['req_affcalendar'], $i, "classe_concerne_calendrier");
	$data['rep_affcalendar'][$i]["nom_calendrier"] = mysql_result($data['req_affcalendar'], $i, "nom_calendrier");
	$data['rep_affcalendar'][$i]["jourdebut_calendrier"] = mysql_result($data['req_affcalendar'], $i, "jourdebut_calendrier");
	$data['rep_affcalendar'][$i]["heuredebut_calendrier"] = mysql_result($data['req_affcalendar'], $i, "heuredebut_calendrier");
	$data['rep_affcalendar'][$i]["jourfin_calendrier"] = mysql_result($data['req_affcalendar'], $i, "jourfin_calendrier");
	$data['rep_affcalendar'][$i]["heurefin_calendrier"] = mysql_result($data['req_affcalendar'], $i, "heurefin_calendrier");
	$data['rep_affcalendar'][$i]["numero_periode"] = mysql_result($data['req_affcalendar'], $i, "numero_periode");
	$data['rep_affcalendar'][$i]["etabferme_calendrier"] = mysql_result($data['req_affcalendar'], $i, "etabferme_calendrier");
	$data['rep_affcalendar'][$i]["etabvacances_calendrier"] = mysql_result($data['req_affcalendar'], $i, "etabvacances_calendrier");

	// �tablissement ouvert ou ferm� ?
	if ($data['rep_affcalendar'][$i]["etabferme_calendrier"] == "1") {
		$data['ouvert_ferme'][$i] = "ouvert";
	}
	else $data['ouvert_ferme'][$i] = "ferm�";

	// Quelles classes sont concern�es
	$data['expl_aff'][$i] = explode(";", ($data['rep_affcalendar'][$i]["classe_concerne_calendrier"]));

	// Attention, si on compte l'explode, on a une ligne de trop
	if ($data['expl_aff'][$i] == "0" OR $data['rep_affcalendar'][$i]["classe_concerne_calendrier"] == "0") {
		$data['aff_classe_concerne'][$i] = "<span class=\"legende\">Toutes</span>";
	}
	else {
		$data['contenu_infobulle'] = "<span style=\"color: brown;\">".(count($data['expl_aff'][$i]) - 1)." classe(s).</span><br />";
		$contenu_infobulle = "";
		for ($t=0; $t<(count($data['expl_aff'][$i]) - 1); $t++) {
			$req_nomclasse = mysql_fetch_array(mysql_query("SELECT nom_complet FROM classes WHERE id = '".$data["expl_aff"][$i][$t]."'"));
			$contenu_infobulle .= $req_nomclasse["nom_complet"].'<br />';
		}
		//$aff_classe_concerne = aff_popup("Voir", "edt", "Classes concern�es", $contenu_infobulle);
		$id_div = "periode".$data['rep_affcalendar'][$i]["id_calendrier"];
		$data['aff_classe_concerne'][$i] = "<a href=\"#\" onmouseover=\"afficher_div('".$id_div."','Y',10,10);return false;\" onmouseout=\"cacher_div('".$id_div."');\">Liste</a>\n".creer_div_infobulle($id_div, "Liste des classes", "#330033", $contenu_infobulle, "#FFFFFF", 15,0,"n","n","y","n", 1);
	} 

	// On d�termine si c'est une p�riode p�dagogique ou une p�riode de vacances
	if ($data['rep_affcalendar'][$i]["etabvacances_calendrier"] == 0) {
		$data['aff_cours'][$i] = "Cours";
	} else {
		$data['aff_cours'][$i] = "Vac.";
	}

	// On enl�ve les secondes � l'affichage
	$explode_deb = explode(":", $data['rep_affcalendar'][$i]["heuredebut_calendrier"]);
	$data['rep_affcalendar'][$i]["heuredebut_calendrier"] = $explode_deb[0].":".$explode_deb[1];
	$explode_fin = explode(":", $data['rep_affcalendar'][$i]["heurefin_calendrier"]);
	$data['rep_affcalendar'][$i]["heurefin_calendrier"] = $explode_fin[0].":".$explode_fin[1];
	// On affiche les dates au format fran�ais
	$exp_jourdeb = explode("-", $data['rep_affcalendar'][$i]["jourdebut_calendrier"]);
	$data['aff_jourdeb'][$i] = $exp_jourdeb[2]."/".$exp_jourdeb[1]."/".$exp_jourdeb[0];
	$exp_jourfin = explode("-", $data['rep_affcalendar'][$i]["jourfin_calendrier"]);
	$data['aff_jourfin'][$i] = $exp_jourfin[2]."/".$exp_jourfin[1]."/".$exp_jourfin[0];

	// Afficher de deux couleurs diff�rentes

	if ($a == 1) {
		$data['class_tr'][$i] = "ligneimpaire";
		$a ++;
	}
	elseif ($a == 2) {
		$data['class_tr'][$i] = "lignepaire";
		$a = 1;
	}
}
if (isset($data['calendrier']) AND isset($data['modifier'])) {
	// On affiche la p�riode demand�e dans un formulaire
	$rep_modif = mysql_fetch_array(mysql_query("SELECT * FROM edt_calendrier WHERE id_calendrier = '".$data['modifier']."'"));
	// On affiche la liste des classes
	$tab_select = renvoie_liste("classe");
	// On r�cup�re les classes de la p�riode ("zone de temps") � afficher
	$toutes_classes = explode(";", $rep_modif["classe_concerne_calendrier"]);
		// Fonction checked_calendar
		function checked_calendar($tester_classe, $classes_cochees){
			$cl_coch = explode(";", $classes_cochees);
			$return = "";
			for($t=0; $t<count($cl_coch); $t++) {
				if ($tester_classe == $cl_coch[$t]) {
					$return = " checked='checked'";
				}
			}
			return $return;
		}
		$exp_jourdeb = explode("-", $rep_modif["jourdebut_calendrier"]);
		$aff_jourdeb = $exp_jourdeb[2]."/".$exp_jourdeb[1]."/".$exp_jourdeb[0];
		$exp_jourfin = explode("-", $rep_modif["jourfin_calendrier"]);
		$aff_jourfin = $exp_jourfin[2]."/".$exp_jourfin[1]."/".$exp_jourfin[0];
			// On enl�ve les secondes � l'affichage des heures
		$aff_heuredeb = substr($rep_modif["heuredebut_calendrier"], 0, -3);
		$aff_heurefin = substr($rep_modif["heurefin_calendrier"], 0, -3);
		
		$req_periodes = mysql_query("SELECT nom_periode, num_periode FROM periodes WHERE id_classe = '1'");
		$nbre_periodes = mysql_num_rows($req_periodes);	
		
	
		// Choix des classes sur 3 (ou 4) colonnes
		$modulo = count($tab_select) % 3;
			// Calcul du nombre d'entr�e par colonne ($ligne)
		if ($modulo !== 0) {
			$calcul = count($tab_select) / 3;
			$expl = explode(".", $calcul);
			$ligne = $expl[0];
		}else {
			$ligne = count($tab_select) / 3;
		}
		$aff_checked = ""; // par d�faut, le checkbox n'est pas coch�		
}



// CSS et js particulier � l'EdT
$javascript_specifique = "edt_organisation/script/fonctions_edt";
$style_specifique = "templates/".NameTemplateEDT()."/css/style_edt";
$utilisation_jsdivdrag = "";
$utilisation_prototype = "ok";

// =======================================================================
//
//									Vue
//
// =======================================================================

require_once("../lib/header.inc");
//debug_var();
require_once("./views/edt_calendrier_view.html");
require("../lib/footer.inc.php");
?>
