<?php
/**
 *
 *
 * Copyright 2010 Josselin Jacquard
 *
 * This file and the mod_abs2 module is distributed under GPL version 3, or
 * (at your option) any later version.
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

// Initialisation des feuilles de style après modification pour améliorer l'accessibilité
$accessibilite="y";

// Initialisations files
require_once("../lib/initialisationsPropel.inc.php");
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

if (!checkAccess()) {
    header("Location: ../logout.php?auto=1");
    die();
}

//recherche de l'utilisateur avec propel
$utilisateur = UtilisateurProfessionnelPeer::getUtilisateursSessionEnCours();
if ($utilisateur == null) {
	header("Location: ../logout.php?auto=1");
	die();
}

//On vérifie si le module est activé
if (getSettingValue("active_module_absence")!='2') {
    die("Le module n'est pas activé.");
}

if ($utilisateur->getStatut()!="cpe" && $utilisateur->getStatut()!="scolarite") {
    die("acces interdit");
}

//récupération des paramètres de la requète
$id_traitement = isset($_POST["id_traitement"]) ? $_POST["id_traitement"] :(isset($_GET["id_traitement"]) ? $_GET["id_traitement"] :NULL);
$modif = isset($_POST["modif"]) ? $_POST["modif"] :(isset($_GET["modif"]) ? $_GET["modif"] :null);
$menu = isset($_POST["menu"]) ? $_POST["menu"] :(isset($_GET["menu"]) ? $_GET["menu"] : Null);

$message_enregistrement = '';
$traitement = AbsenceEleveTraitementQuery::create()->findPk($id_traitement);
if ($traitement == null) {
    $message_enregistrement .= 'Modification impossible : traitement non trouvée.';
    include("visu_traitement.php");
    die();
}

//debug_var();

if ($modif == 'type') {    
	$traitement->setAbsenceEleveType(AbsenceEleveTypeQuery::create()->findPk($_POST["id_type"]));   
} elseif ($modif == 'commentaire') {
    $traitement->setCommentaire($_POST["commentaire"]);
} elseif ($modif == 'justification') {
    $traitement->setAbsenceEleveJustification(AbsenceEleveJustificationQuery::create()->findPk($_POST["id_justification"]));
} elseif ($modif == 'motif') {
    $traitement->setAbsenceEleveMotif(AbsenceEleveMotifQuery::create()->findPk($_POST["id_motif"]));
} elseif ($modif == 'enlever_saisie') {
    $j_saisie_traitement_col = JTraitementSaisieEleveQuery::create()->filterByAbsenceEleveTraitement($traitement)->filterByASaisieId($_POST["id_saisie"])->find();
    $count_delete = $j_saisie_traitement_col->count();
    foreach ($j_saisie_traitement_col as $j_saisie_traitement) {
    	$j_saisie_traitement->delete();
    }
} elseif ($modif == 'supprimer') {
    $traitement->delete();
    if($menu){
        include("visu_saisie.php");
    }else{
        include("liste_traitements.php");
    }    
    die;
} elseif ($modif == 'modifier_heures_saisies') {

	$message_enregistrement="";

	// Tableau des id_saisie à modifier
	$id_saisie=$_POST['id_saisie'];

	// Date de début transmise au format aaaa-mm-jj
	try {
		$tab_date=explode("-",$_POST['date_debut']);
		$tmp_date=$tab_date[2].".".$tab_date[1].".".$tab_date[0];
		$date_debut = new DateTime($tmp_date);
	} catch (Exception $x) {
		$message_enregistrement .= "Mauvais format de date.<br/>";
	}

	// Date de fin transmise au format aaaa-mm-jj
	try {
		$tab_date=explode("-",$_POST['date_fin']);
		$tmp_date=$tab_date[2].".".$tab_date[1].".".$tab_date[0];
		$date_fin = new DateTime($tmp_date);
	} catch (Exception $x) {
		$message_enregistrement .= "Mauvais format de date.<br/>";
	}

	// Heure de début transmise au format HH:MM
	try {
		$heure_debut = new DateTime($_POST['heure_debut']);
		$date_debut->setTime($heure_debut->format('H'), $heure_debut->format('i'));
	} catch (Exception $x) {
		$message_enregistrement .= "Mauvais format d'heure.<br/>";
	}

	// Heure de fin transmise au format HH:MM
	try {
		$heure_fin = new DateTime($_POST['heure_fin']);
		$date_fin->setTime($heure_fin->format('H'), $heure_fin->format('i'));
	} catch (Exception $x) {
		$message_enregistrement .= "Mauvais format d'heure.<br/>";
	}

	if ($message_enregistrement == "") {
		for($loop=0;$loop<count($id_saisie);$loop++) {
			$saisie = AbsenceEleveSaisieQuery::create()->includeDeleted()->findPk($id_saisie[$loop]);
			if ($saisie != null) {
				$saisie->setDebutAbs($date_debut);
				$saisie->setFinAbs($date_fin);
				$saisie->save();
			}
		}
	}

	include("visu_traitement.php");
	die();
}

if (!$traitement->isModified()) {
    if (isset($count_delete) && $count_delete > 0) {
	$message_enregistrement .= 'Saisie supprimée';
    } else {
	$message_enregistrement .= 'Pas de modifications';
    }
} else {
    if ($traitement->validate()) {
	$traitement->save();
	$message_enregistrement .= 'Modification enregistrée';
    } else {
	$no_br = true;
	foreach ($traitement->getValidationFailures() as $erreurs) {
	    $message_enregistrement .= $erreurs;
	    if ($no_br) {
		$no_br = false;
	    } else {
		$message_enregistrement .= '<br/>';
	    }
	}
	$traitement->reload();
    }
}

include("visu_traitement.php");
?>
