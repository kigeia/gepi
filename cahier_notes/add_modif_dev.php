<?php
/*
 * @version: $Id$
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

if (!checkAccess()) {
    header("Location: ../logout.php?auto=1");
    die();
}


//On v�rifie si le module est activ�
if (getSettingValue("active_carnets_notes")!='y') {
    die("Le module n'est pas activ�.");
}

isset($id_retour);
$id_retour = isset($_POST["id_retour"]) ? $_POST["id_retour"] : (isset($_GET["id_retour"]) ? $_GET["id_retour"] : NULL);
isset($id_devoir);
$id_devoir = isset($_POST["id_devoir"]) ? $_POST["id_devoir"] : (isset($_GET["id_devoir"]) ? $_GET["id_devoir"] : NULL);
isset($mode_navig);
$mode_navig = isset($_POST["mode_navig"]) ? $_POST["mode_navig"] : (isset($_GET["mode_navig"]) ? $_GET["mode_navig"] : NULL);


if ($id_devoir)  {
    $query = mysql_query("SELECT id_conteneur, id_racine FROM cn_devoirs WHERE id = '$id_devoir'");
    $id_racine = mysql_result($query, 0, 'id_racine');
    $id_conteneur = mysql_result($query, 0, 'id_conteneur');
} else if ((isset($_POST['id_conteneur'])) or (isset($_GET['id_conteneur']))) {
    $id_conteneur = isset($_POST['id_conteneur']) ? $_POST['id_conteneur'] : (isset($_GET['id_conteneur']) ? $_GET['id_conteneur'] : NULL);
    $query = mysql_query("SELECT id_racine FROM cn_conteneurs WHERE id = '$id_conteneur'");
    $id_racine = mysql_result($query, 0, 'id_racine');
} else {
    header("Location: ../logout.php?auto=1");
    die();
}
//Configuration du calendrier
include("../lib/calendrier/calendrier.class.php");
$cal = new Calendrier("formulaire", "display_date");
$cal2 = new Calendrier("formulaire", "date_ele_resp");


// On teste si le carnet de notes appartient bien � la personne connect�e
if (!(Verif_prof_cahier_notes ($_SESSION['login'],$id_racine))) {
    $mess=rawurlencode("Vous tentez de p�n�trer dans un carnet de notes qui ne vous appartient pas !");
    header("Location: index.php?msg=$mess");
    die();
}

$appel_cahier_notes = mysql_query("SELECT * FROM cn_cahier_notes WHERE id_cahier_notes ='$id_racine'");
$id_groupe = mysql_result($appel_cahier_notes, 0, 'id_groupe');
$current_group = get_group($id_groupe);
$periode_num = mysql_result($appel_cahier_notes, 0, 'periode');
include "../lib/periodes.inc.php";

// On teste si la periode est v�rrouill�e !
if ($current_group["classe"]["ver_periode"]["all"][$periode_num] <= 1) {
    $mess=rawurlencode("Vous tentez de p�n�trer dans un carnet de notes dont la p�riode est bloqu�e !");
    header("Location: index.php?msg=$mess");
    die();
}

$matiere_nom = $current_group["matiere"]["nom_complet"];
$matiere_nom_court = $current_group["matiere"]["matiere"];
$nom_classe = $current_group["classlist_string"];

isset($id_devoir);
$id_devoir = isset($_POST["id_devoir"]) ? $_POST["id_devoir"] : (isset($_GET["id_devoir"]) ? $_GET["id_devoir"] : NULL);

//debug_var();

// enregistrement des donn�es
if (isset($_POST['ok'])) {
	check_token();
	unset($tab_group);
	$tab_group=array();

	$msg="";

    $reg_ok = "yes";
    $new='no';
    if ((isset($_POST['new_devoir'])) and ($_POST['new_devoir'] == 'yes')) {
        $reg = mysql_query("insert into cn_devoirs (id_racine,id_conteneur,nom_court) values ('$id_racine','$id_conteneur','nouveau')");
        $id_devoir = mysql_insert_id();
        $new='yes';
        if (!$reg) {$reg_ok = "no";}

		$creation_dev_autres_groupes=isset($_POST['creation_dev_autres_groupes']) ? $_POST['creation_dev_autres_groupes'] : 'n';
		$id_autre_groupe=isset($_POST['id_autre_groupe']) ? $_POST['id_autre_groupe'] : array();
		if(($creation_dev_autres_groupes=='y')&&(count($id_autre_groupe)>0)) {
			// Cr�er un tableau des id_groupe, id_cahier_notes=id_racine, id_conteneur, id_devoir

			// On r�cup�re le nom, la description,... de l'emplacement/boite/conteneur pour pouvoir cr�er le m�me si n�cessaire
			$id_emplacement=isset($_POST['id_emplacement']) ? $_POST['id_emplacement'] : $id_racine;

			$sql="SELECT * FROM cn_conteneurs WHERE id='$id_emplacement';";
			//echo "$sql<br />\n";
			$res_infos_conteneur=mysql_query($sql);
			$lig_conteneur=mysql_fetch_object($res_infos_conteneur);
			$nom_court_conteneur=$lig_conteneur->nom_court;
			$nom_complet_conteneur=$lig_conteneur->nom_complet;
			$description_conteneur=$lig_conteneur->description;
			$mode_conteneur=$lig_conteneur->mode;
			$coef_conteneur=$lig_conteneur->coef;
			$arrondir_conteneur=$lig_conteneur->arrondir;
			$ponderation_conteneur=$lig_conteneur->ponderation;
			$display_parents_conteneur=$lig_conteneur->display_parents;
			$display_bulletin_conteneur=$lig_conteneur->display_bulletin;

			$cpt=0;
			// Boucle sur les autres enseignements sur lesquels cr�er le m�me devoir
			for($i=0;$i<count($id_autre_groupe);$i++) {
				//$tab_group[$i]=get_group($id_autre_groupe);
				$tmp_group=get_group($id_autre_groupe[$i]);
				// V�rifier que la p�riode est bien ouverte en saisie
				if($tmp_group["classe"]["ver_periode"]["all"][$periode_num]>=2) {

					$tmp_id_racine="";

					$sql="SELECT id_cahier_notes FROM cn_cahier_notes WHERE (id_groupe='".$tmp_group['id']."' AND periode='$periode_num');";
					//echo "$sql<br />\n";
					$res_idcn=mysql_query($sql);
					if(mysql_num_rows($res_idcn)==0) {
						// On cr�e le cahier de notes

						$tmp_nom_complet_matiere = $tmp_group["matiere"]["nom_complet"];
						$tmp_nom_court_matiere = $tmp_group["matiere"]["matiere"];
						$reg = mysql_query("INSERT INTO cn_conteneurs SET id_racine='', nom_court='".traitement_magic_quotes($tmp_group["description"])."', nom_complet='". traitement_magic_quotes($tmp_nom_complet_matiere)."', description = '', mode = '2', coef = '1.0', arrondir = 's1', ponderation = '0.0', display_parents = '0', display_bulletin = '1', parent = '0'");
						if ($reg) {
							$tmp_id_racine = mysql_insert_id();
							// On renseigne le champ id_racine avec la m�me valeur que l'id du conteneur: c'est la racine du cahier de notes
							$reg = mysql_query("UPDATE cn_conteneurs SET id_racine='$tmp_id_racine', parent = '0' WHERE id='$tmp_id_racine'");
							// On d�clare le cahier de notes avec cet identifiant
							$reg = mysql_query("INSERT INTO cn_cahier_notes SET id_groupe = '".$tmp_group['id']."', periode = '$periode_num', id_cahier_notes='$tmp_id_racine'");
						}
					}
					else {
						$lig_tmp=mysql_fetch_object($res_idcn);
						$tmp_id_racine=$lig_tmp->id_cahier_notes;
					}

					if(($tmp_id_racine!="")&&(is_numeric($tmp_id_racine))) {
						// Si le conteneur/boite n'est pas � la racine, on teste s'il faut cr�er un conteneur/boite dans l'autre enseignement
						if($id_emplacement!=$id_racine) {
							// La m�me boite existe-t-elle dans cet autre enseignement?
							$sql="SELECT * FROM cn_conteneurs WHERE nom_court='".addslashes($nom_court_conteneur)."' AND id_racine='$tmp_id_racine';";
							//echo "$sql<br />\n";
							$test_conteneur=mysql_query($sql);
							if(mysql_num_rows($test_conteneur)>0) {
								$lig_tmp=mysql_fetch_object($test_conteneur);
								$tmp_id_conteneur=$lig_tmp->id;
							}
							else {
								// Par d�faut, on met le devoir � la racine si le conteneur de m�me nom n'existe pas
								$tmp_id_conteneur=$tmp_id_racine;

								// si la case 'creer_conteneur' a �t� coch�e, on cr�e ici une boite comme celle du devoir mod�le
								if((isset($_POST['creer_conteneur']))&&($_POST['creer_conteneur']=="y")) {
									$sql="INSERT INTO cn_conteneurs SET id_racine='$tmp_id_racine',
																		nom_court='".addslashes($nom_court_conteneur)."',
																		nom_complet='".addslashes($nom_complet_conteneur)."',
																		description='".addslashes($description_conteneur)."',
																		mode='".addslashes($mode_conteneur)."',
																		coef='".addslashes($coef_conteneur)."',
																		arrondir='".addslashes($arrondir_conteneur)."',
																		ponderation='".addslashes($ponderation_conteneur)."',
																		display_parents='".addslashes($display_parents_conteneur)."',
																		display_bulletin='".addslashes($display_bulletin_conteneur)."',
																		parent='$tmp_id_racine';";
									if($insert_conteneur=mysql_query($sql)) {
										$tmp_id_conteneur=mysql_insert_id();
									}
									else {
										// Sinon, le devoir sera a la racine... mais on met un avertissement
										$msg.="Le conteneur/boite pour l'enseignement ".$tmp_group["name"]." (n�".$tmp_group["id"].") en ".$tmp_group["classlist_string"]." n'a pas �t� cr��.<br />";
									}
								}

							}
						}
						else {
							// La boite du devoir est la racine du cahier de notes
							$tmp_id_conteneur=$tmp_id_racine;
						}

						if((is_numeric($tmp_id_conteneur))&&(is_numeric($tmp_id_racine))) {
							$sql="insert into cn_devoirs (id_racine,id_conteneur,nom_court) values ('$tmp_id_racine','$tmp_id_conteneur','nouveau');";
							//echo "$sql<br />\n";
							$creation_dev=mysql_query($sql);
							$tmp_id_devoir = mysql_insert_id();
	
							$tab_group[$cpt]=$tmp_group;
							$tab_group[$cpt]['id_racine']=$tmp_id_racine;
							$tab_group[$cpt]['id_conteneur']=$tmp_id_conteneur;
							$tab_group[$cpt]['id_devoir']=$tmp_id_devoir;
	
							$cpt++;
						}
						else {
							$msg.="Le devoir n'a pas pu �tre cr�� pour le conteneur '$tmp_id_conteneur' de racine '$tmp_id_racine'.<br />";
							$reg_ok="no";
						}
					}
				}
			}
		}

    }

    if ($_POST['nom_court'])  {
        $nom_court = $_POST['nom_court'];
    } else {
        $nom_court = "Devoir ".$id_devoir;
    }
    $reg = mysql_query("UPDATE cn_devoirs SET nom_court = '".corriger_caracteres($nom_court)."' WHERE id = '$id_devoir'");
    if (!$reg)  $reg_ok = "no";
	for($i=0;$i<count($tab_group);$i++) {
		$sql="UPDATE cn_devoirs SET nom_court = '".corriger_caracteres($nom_court)."' WHERE id = '".$tab_group[$i]['id_devoir']."';";
		//echo "$sql<br />\n";
		$reg=mysql_query($sql);
	}

    if ($_POST['nom_complet'])  {
        $nom_complet = $_POST['nom_complet'];
    } else {
        $nom_complet = $nom_court;
    }

    $reg = mysql_query("UPDATE cn_devoirs SET nom_complet = '".corriger_caracteres($nom_complet)."' WHERE id = '$id_devoir'");
    if (!$reg)  $reg_ok = "no";
	for($i=0;$i<count($tab_group);$i++) {
		$sql="UPDATE cn_devoirs SET nom_complet = '".corriger_caracteres($nom_complet)."' WHERE id = '".$tab_group[$i]['id_devoir']."';";
		//echo "$sql<br />\n";
		$reg=mysql_query($sql);
	}

    if ($_POST['description'])  {
        $reg = mysql_query("UPDATE cn_devoirs SET description = '".corriger_caracteres($_POST['description'])."' WHERE id = '$id_devoir'");
        if (!$reg)  $reg_ok = "no";
    }
	for($i=0;$i<count($tab_group);$i++) {
		$sql="UPDATE cn_devoirs SET description = '".corriger_caracteres($_POST['description'])."' WHERE id = '".$tab_group[$i]['id_devoir']."';";
		//echo "$sql<br />\n";
		$reg=mysql_query($sql);
	}

    if ($_POST['id_emplacement'])  {
        $id_emplacement = $_POST['id_emplacement'];
        $reg = mysql_query("UPDATE cn_devoirs SET id_conteneur = '".$id_emplacement."' WHERE id = '$id_devoir'");
        if (!$reg)  $reg_ok = "no";

		for($i=0;$i<count($tab_group);$i++) {
			$sql="UPDATE cn_devoirs SET id_conteneur = '".$tab_group[$i]['id_conteneur']."' WHERE id = '".$tab_group[$i]['id_devoir']."';";
			//echo "$sql<br />\n";
			$reg=mysql_query($sql);
		}
    }

	/*
    if ($_POST['coef']) {
        $reg = mysql_query("UPDATE cn_devoirs SET coef = '".$_POST['coef']."' WHERE id = '$id_devoir'");
        if (!$reg)  $reg_ok = "no";
    } else {
        $reg = mysql_query("UPDATE cn_devoirs SET coef = '0' WHERE id = '$id_devoir'");
        if (!$reg)  $reg_ok = "no";
    }
	*/
	$tmp_coef=isset($_POST['coef']) ? $_POST['coef'] : 0;
	$reg = mysql_query("UPDATE cn_devoirs SET coef='".$tmp_coef."' WHERE id='$id_devoir'");
	if (!$reg)  $reg_ok = "no";
	for($i=0;$i<count($tab_group);$i++) {
		$sql="UPDATE cn_devoirs SET coef='".$tmp_coef."' WHERE id='".$tab_group[$i]['id_devoir']."';";
		//echo "$sql<br />\n";
		$reg=mysql_query($sql);
	}

	/*
   if (isset($_POST['note_sur'])) {
        $reg = mysql_query("UPDATE cn_devoirs SET note_sur = '".$_POST['note_sur']."' WHERE id = '$id_devoir'");
        if (!$reg)  $reg_ok = "no";
    } else {
        $reg = mysql_query("UPDATE cn_devoirs SET note_sur = '".getSettingValue("referentiel_note")."' WHERE id = '$id_devoir'");
        if (!$reg)  $reg_ok = "no";
    }
	*/
	$note_sur=isset($_POST['note_sur']) ? $_POST['note_sur'] : getSettingValue("referentiel_note");
	$reg = mysql_query("UPDATE cn_devoirs SET note_sur='".$note_sur."' WHERE id='$id_devoir'");
	if (!$reg)  $reg_ok = "no";
	for($i=0;$i<count($tab_group);$i++) {
		$sql="UPDATE cn_devoirs SET note_sur='".$note_sur."' WHERE id='".$tab_group[$i]['id_devoir']."';";
		//echo "$sql<br />\n";
		$reg=mysql_query($sql);
	}

    if ((isset($_POST['ramener_sur_referentiel']))&&($_POST['ramener_sur_referentiel']=="V")) {
        $ramener_sur_referentiel='V';
    } else {
        $ramener_sur_referentiel='F';
    }

    $reg = mysql_query("UPDATE cn_devoirs SET ramener_sur_referentiel = '$ramener_sur_referentiel' WHERE id = '$id_devoir'");
    if (!$reg)  $reg_ok = "no";
	for($i=0;$i<count($tab_group);$i++) {
		$sql="UPDATE cn_devoirs SET ramener_sur_referentiel='$ramener_sur_referentiel' WHERE id='".$tab_group[$i]['id_devoir']."';";
		//echo "$sql<br />\n";
		$reg=mysql_query($sql);
	}

    if (($_POST['facultatif']) and my_ereg("^(O|N|B)$", $_POST['facultatif'])) {
        $reg = mysql_query("UPDATE cn_devoirs SET facultatif = '".$_POST['facultatif']."' WHERE id = '$id_devoir'");
        if (!$reg)  $reg_ok = "no";
		for($i=0;$i<count($tab_group);$i++) {
			$sql="UPDATE cn_devoirs SET facultatif='".$_POST['facultatif']."' WHERE id='".$tab_group[$i]['id_devoir']."';";
			//echo "$sql<br />\n";
			$reg=mysql_query($sql);
		}
    }

    if ($_POST['display_date']) {
        if (my_ereg("([0-9]{2})/([0-9]{2})/([0-9]{4})", $_POST['display_date'])) {
            $annee = substr($_POST['display_date'],6,4);
            $mois = substr($_POST['display_date'],3,2);
            $jour = substr($_POST['display_date'],0,2);
        } else {
            $annee = strftime("%Y");
            $mois = strftime("%m");
            $jour = strftime("%d");
        }
        $date = $annee."-".$mois."-".$jour." 00:00:00";
        $reg = mysql_query("UPDATE cn_devoirs SET date = '".$date."' WHERE id = '$id_devoir'");
        if (!$reg)  $reg_ok = "no";
		for($i=0;$i<count($tab_group);$i++) {
			$sql="UPDATE cn_devoirs SET date='".$date."' WHERE id='".$tab_group[$i]['id_devoir']."';";
			//echo "$sql<br />\n";
			$reg=mysql_query($sql);
		}
    }

	//====================================================
    if ($_POST['date_ele_resp']) {
        if (my_ereg("([0-9]{2})/([0-9]{2})/([0-9]{4})", $_POST['date_ele_resp'])) {
            $annee = substr($_POST['date_ele_resp'],6,4);
            $mois = substr($_POST['date_ele_resp'],3,2);
            $jour = substr($_POST['date_ele_resp'],0,2);
        } else {
            $annee = strftime("%Y");
            $mois = strftime("%m");
            $jour = strftime("%d");
        }
        $date = $annee."-".$mois."-".$jour." 00:00:00";
        $reg = mysql_query("UPDATE cn_devoirs SET date_ele_resp='".$date."' WHERE id = '$id_devoir'");
        if (!$reg)  $reg_ok = "no";
		for($i=0;$i<count($tab_group);$i++) {
			$sql="UPDATE cn_devoirs SET date_ele_resp='".$date."' WHERE id='".$tab_group[$i]['id_devoir']."';";
			//echo "$sql<br />\n";
			$reg=mysql_query($sql);
		}
    }
	//====================================================

	if (isset($_POST['display_parents'])) {
		if($_POST['display_parents']==1) {
			$display_parents=1;
		}
		else {
			$display_parents=0;
		}
	} else {
		$display_parents=0;
	}

	$reg = mysql_query("UPDATE cn_devoirs SET display_parents = '$display_parents' WHERE id = '$id_devoir'");
	if (!$reg) {$reg_ok = "no";}
	for($i=0;$i<count($tab_group);$i++) {
		$sql="UPDATE cn_devoirs SET display_parents='$display_parents' WHERE id='".$tab_group[$i]['id_devoir']."';";
		//echo "$sql<br />\n";
		$reg=mysql_query($sql);
	}

	if (isset($_POST['display_parents_app'])) {
		if($_POST['display_parents_app']==1) {
			$display_parents_app=1;
		}
		else {
			$display_parents_app=0;
		}
	} else {
		$display_parents_app=0;
	}

	$reg = mysql_query("UPDATE cn_devoirs SET display_parents_app = '$display_parents_app' WHERE id = '$id_devoir'");
	if (!$reg) {$reg_ok = "no";}
	for($i=0;$i<count($tab_group);$i++) {
		$sql="UPDATE cn_devoirs SET display_parents_app='$display_parents_app' WHERE id='".$tab_group[$i]['id_devoir']."';";
		//echo "$sql<br />\n";
		$reg=mysql_query($sql);
	}

    //==========================================================
    // MODIF: boireaus
    //
    // Mise � jour des moyennes du conteneur et des conteneurs parent, grand-parent, etc...
    //
    $arret = 'no';
    mise_a_jour_moyennes_conteneurs($current_group, $periode_num,$id_racine,$id_conteneur,$arret);
    // La boite courante est mise � jour...
    // ... mais pas la boite destination.
    // Il faudrait rechercher pour $id_racine les derniers descendants et lancer la mise � jour sur chacun de ces descendants.
    function recherche_enfant($id_parent_tmp){
        global $current_group, $periode_num, $id_racine;
    $sql="SELECT * FROM cn_conteneurs WHERE parent='$id_parent_tmp'";
    //echo "<!-- $sql -->\n";
    $res_enfant=mysql_query($sql);
    if(mysql_num_rows($res_enfant)>0){
        while($lig_conteneur_enfant=mysql_fetch_object($res_enfant)){
            /*
            echo "<!-- nom_court=$lig_conteneur_enfant->nom_court -->\n";
            echo "<!-- nom_complet=$lig_conteneur_enfant->nom_complet -->\n";
            echo "<!-- id=$lig_conteneur_enfant->id -->\n";
            echo "<!-- parent=$lig_conteneur_enfant->parent -->\n";
            echo "<!-- recherche_enfant($lig_conteneur_enfant->id); -->\n";
            */
            recherche_enfant($lig_conteneur_enfant->id);
        }
    }
    else{
        $arret = 'no';
        $id_conteneur_enfant=$id_parent_tmp;
        //echo "<!-- mise_a_jour_moyennes_conteneurs($current_group, $periode_num,$id_racine,$id_conteneur_enfant,$arret); -->\n";
        mise_a_jour_moyennes_conteneurs($current_group, $periode_num,$id_racine,$id_conteneur_enfant,$arret);
        //echo "<!-- ========================================== -->\n";
    }
    }
    recherche_enfant($id_racine);
    //==========================================================

	if ($reg_ok=='yes') {
		if ($new=='yes') {$msg.="Nouvel enregistrement r�ussi.";}
		else {$msg.="Les modifications ont �t� effectu�es avec succ�s.";}
	} else {
		$msg.="Il y a eu un probl�me lors de l'enregistrement";
	}

    //==========================================================
    // Ajout d'un test:
    // Si on modifie un devoir alors que des notes ont �t� report�es sur le bulletin, il faut penser � mettre � jour la recopie vers le bulletin.
    $sql="SELECT 1=1 FROM matieres_notes WHERE periode='".$periode_num."' AND id_groupe='".$id_groupe."';";
    $test_bulletin=mysql_query($sql);
    if(mysql_num_rows($test_bulletin)>0) {
        $msg.=" ATTENTION: Des notes sont pr�sentes sur les bulletins. Si vous avez modifi� un coefficient, des notes,... pensez � mettre � jour la recopie vers les bulletins.";
    }
    //==========================================================

    //
    // retour
    //
    if ($mode_navig == 'retour_saisie') {
        header("Location: ./saisie_notes.php?id_conteneur=$id_retour&msg=$msg");
        die();
    } else if ($mode_navig == 'retour_index') {
        header("Location: ./index.php?id_racine=$id_racine&msg=$msg");
        die();
    } elseif($mode_navig == 'saisie_devoir'){
	//https://127.0.0.1/steph/gepi-cvs/cahier_notes/saisie_notes.php?id_conteneur=63&id_devoir=79
        header("Location: ./saisie_notes.php?id_conteneur=$id_conteneur&id_devoir=$id_devoir&msg=$msg");
        die();
    }
}

//-----------------------------------------------------------------------------------

if ($id_devoir)  {
    $new_devoir = 'no';
    $appel_devoir = mysql_query("SELECT * FROM cn_devoirs WHERE (id ='$id_devoir' and id_racine='$id_racine')");
    $nom_court = mysql_result($appel_devoir, 0, 'nom_court');
    $nom_complet = mysql_result($appel_devoir, 0, 'nom_complet');
    $description = mysql_result($appel_devoir, 0, 'description');
    $coef = mysql_result($appel_devoir, 0, 'coef');
    $note_sur = mysql_result($appel_devoir, 0, 'note_sur');
    $ramener_sur_referentiel = mysql_result($appel_devoir, 0, 'ramener_sur_referentiel');
    $facultatif = mysql_result($appel_devoir, 0, 'facultatif');
    $display_parents = mysql_result($appel_devoir, 0, 'display_parents');
    $display_parents_app = mysql_result($appel_devoir, 0, 'display_parents_app');
    $date = mysql_result($appel_devoir, 0, 'date');
    $id_conteneur = mysql_result($appel_devoir, 0, 'id_conteneur');

    $annee = substr($date,0,4);
    $mois =  substr($date,5,2);
    $jour =  substr($date,8,2);
    $display_date = $jour."/".$mois."/".$annee;

    $date = mysql_result($appel_devoir, 0, 'date_ele_resp');
    $annee = substr($date,0,4);
    $mois =  substr($date,5,2);
    $jour =  substr($date,8,2);
    $date_ele_resp = $jour."/".$mois."/".$annee;

} else {
    $nom_court = "Nouvelle �valuation";
    $nom_complet = "";
    $description = "";
    $new_devoir = 'yes';
    $coef = "1";
    $note_sur = getSettingValue("referentiel_note");
    $ramener_sur_referentiel = "F";
    $display_parents = "1";
    $display_parents_app = "0";
    $facultatif = "O";
    $date = "";
    $annee = strftime("%Y");
    $mois = strftime("%m");
    $jour = strftime("%d");
    $display_date = $jour."/".$mois."/".$annee;
	$date_ele_resp=$display_date;
}
//**************** EN-TETE *****************
$titre_page = "Carnet de notes - Ajout/modification d'une �valuation";
require_once("../lib/header.inc");
//**************** FIN EN-TETE *****************
echo "<form enctype=\"multipart/form-data\" name= \"formulaire\" action=\"add_modif_dev.php\" method=\"post\">\n";
echo add_token_field();
if ($mode_navig == 'retour_saisie') {
    echo "<div class='norme'><p class=bold><a href='./saisie_notes.php?id_conteneur=$id_retour'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>\n";
} else {
    echo "<div class='norme'><p class=bold><a href='index.php?id_racine=$id_racine'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>\n";
}

/*
if (isset($_POST['ok'])) {
	echo "|<a href='saisie_notes.php?id_conteneur=$id_conteneur&amp;id_devoir=$id_devoir'>Saisir</a>\n";
}
*/


/*
// D�plac�e var /lib/share.inc.php
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

// Interface simplifi�e
//$interface_simplifiee=isset($_POST['interface_simplifiee']) ? $_POST['interface_simplifiee'] : (isset($_GET['interface_simplifiee']) ? $_GET['interface_simplifiee'] : "");

$interface_simplifiee=isset($_POST['interface_simplifiee']) ? $_POST['interface_simplifiee'] : (isset($_GET['interface_simplifiee']) ? $_GET['interface_simplifiee'] : getPref($_SESSION['login'],'add_modif_dev_simpl','n'));


//echo "<a href='".$_SERVER['PHP_SELF']."?id_conteneur=$id_conteneur";
echo " | <a href='add_modif_dev.php?id_conteneur=$id_conteneur";
if(isset($mode_navig)){
	echo "&amp;mode_navig=$mode_navig";
}
if(isset($id_devoir)){
	echo "&amp;id_devoir=$id_devoir";
}
if(isset($id_retour)){
	echo "&amp;id_retour=$id_retour";
}
//if($interface_simplifiee!=""){
if($interface_simplifiee=="y"){
	echo "&amp;interface_simplifiee=n";
	echo "'>Interface compl�te</a>\n";
}
else{
	echo "&amp;interface_simplifiee=y";
	echo "'>Interface simplifi�e</a>\n";
}

echo "\n";



//echo "<p class='bold'> Classe : $nom_classe | Mati�re : $matiere_nom ($matiere_nom_court)| P�riode : $nom_periode[$periode_num] <input type=\"submit\" name='ok' value=\"Enregistrer\" style=\"font-variant: small-caps;\" /></p>\n";
echo "<p class='bold'> Classe : $nom_classe | Mati�re : ".htmlentities("$matiere_nom ($matiere_nom_court)")."| P�riode : $nom_periode[$periode_num] <input type=\"submit\" name='ok' value=\"Enregistrer\" style=\"font-variant: small-caps;\" /></p>\n";
echo "</div>";


echo "<h2 class='gepi'>Configuration de l'�valuation :</h2>\n";



//if($interface_simplifiee!=""){
if($interface_simplifiee=="y"){
	// R�cup�rer les param�tres � afficher.
	// Dans un premier temps, un choix pour tous.
	// Dans le futur, permettre un param�trage par utilisateur

	$aff_nom_court=getPref($_SESSION['login'],'add_modif_dev_nom_court','y');
	$aff_nom_complet=getPref($_SESSION['login'],'add_modif_dev_nom_complet','n');
	$aff_description=getPref($_SESSION['login'],'add_modif_dev_description','n');
	$aff_coef=getPref($_SESSION['login'],'add_modif_dev_coef','y');
	$aff_note_autre_que_referentiel=getPref($_SESSION['login'],'add_modif_dev_note_autre_que_referentiel','n');
	$aff_date=getPref($_SESSION['login'],'add_modif_dev_date','y');
	$aff_date_ele_resp=getPref($_SESSION['login'],'add_modif_dev_date_ele_resp','y');
	$aff_boite=getPref($_SESSION['login'],'add_modif_dev_boite','y');


	echo "<div align='center'>\n";
	//echo "<table border='1'>\n";
	echo "<table class='boireaus' border='1' summary='Parametres du devoir'>\n";

	//#aaaae6
	//#aae6aa

	if($aff_nom_court=='y'){
		echo "<tr>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Nom court:</td>\n";
		echo "<td>\n";
		echo "<input type='text' name = 'nom_court' size='40' value = \"".$nom_court."\" onfocus=\"javascript:this.select()\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else{
		echo "<tr style='display:none;'>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Nom court:</td>\n";
		echo "<td>\n";
		echo "<input type='hidden' name = 'nom_court' size='40' value = \"".$nom_court."\" onfocus=\"javascript:this.select()\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}


	if($aff_nom_complet=='y'){
		echo "<tr>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Nom complet:</td>\n";
		echo "<td>\n";
		echo "<input type='text' name = 'nom_complet' size='40' value = \"".$nom_complet."\" onfocus=\"javascript:this.select()\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else{
		echo "<tr style='display:none;'>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Nom complet:</td>\n";
		echo "<td>\n";
		echo "<input type='hidden' name = 'nom_complet' size='40' value = \"".$nom_complet."\" onfocus=\"javascript:this.select()\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}


	if($aff_description=='y'){
		echo "<tr>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Description:</td>\n";
		echo "<td>\n";
		echo "<textarea name='description' rows='2' cols='40' >".$description."</textarea>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else{
		echo "<tr style='display:none;'>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Description:</td>\n";
		echo "<td>\n";
		//echo "<textarea name='description' rows='2' cols='40' >".$description."</textarea>\n";
		echo "<input type='hidden' name='description' value='$description' />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}


	if($aff_coef=='y'){
		echo "<tr>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Coefficient:</td>\n";
		echo "<td>\n";
		echo "<input type='text' name = 'coef' size='4' value = \"".$coef."\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else{
		echo "<tr style='display:none;'>\n";
		echo "<td>Coefficient:</td>\n";
		echo "<td>\n";
		echo "<input type='hidden' name = 'coef' size='4' value = \"".$coef."\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}


	if($aff_note_autre_que_referentiel=='y'){
		if(getSettingValue("note_autre_que_sur_referentiel")=="V") {
			echo "<tr>\n";
			echo "<td style='background-color: #aae6aa; font-weight: bold;'>Note sur : </td>\n";
	   		echo "<td><input type='text' name = 'note_sur' size='4' value = \"".$note_sur."\" onfocus=\"javascript:this.select()\" /></td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td style='background-color: #aae6aa; font-weight: bold; vertical-align: top;'>Ramener la note sur ".getSettingValue("referentiel_note")."<br />lors du calcul de la moyenne : </td>\n";
    		echo "<td><input type='checkbox' name='ramener_sur_referentiel' value='V' "; if ($ramener_sur_referentiel == 'V') {echo " checked";} echo " /><br />\n";
			echo "<span style=\"font-size: x-small;\">Exemple avec 3 notes : 18/20 ; 4/10 ; 1/5<br />\n";
			echo "Case coch�e : moyenne = 18/20 + 8/20 + 4/20 = 30/60 = 10/20<br />\n";
			echo "Case non coch�e : moyenne = 18/20 + 4/10 + 1/5 = 23/35 = 13,1/20</span><br /><br />\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
	} else {
		echo "<tr style='display:none;'>\n";
		echo "<td>Note sur :</td>\n";
		echo "<td>\n";
		echo "<input type='hidden' name = 'note_sur' value = '".getSettingValue("referentiel_note")."' />\n";
		echo "<input type='hidden' name = 'ramener_sur_referentiel' value = 'F' />\n";
 		echo "</td>\n";
		echo "</tr>\n";
	}


	if($aff_date=='y'){
		echo "<tr>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Date:</td>\n";
		echo "<td>\n";
		echo "<input type='text' name='display_date' id='display_date' size='10' value = \"".$display_date."\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />\n";
		echo "<a href=\"#calend\" onClick=\"".$cal->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"";
		if($aff_date_ele_resp!='y'){
			echo " onchange=\"document.getElementById('date_ele_resp').value=document.getElementById('display_date').value\"";
		}
		echo "><img src=\"../lib/calendrier/petit_calendrier.gif\" border=\"0\" alt=\"Petit calendrier\" /></a>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else{
		echo "<tr style='display:none;'>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Date:</td>\n";
		echo "<td>\n";
		echo "<input type='hidden' name = 'display_date' size='10' value = \"".$display_date."\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if($aff_date_ele_resp=='y'){
		echo "<tr>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Date de visibilit�<br />de la note pour les<br />�l�ves et responsables:</td>\n";
		echo "<td>\n";
		echo "<input type='text' name = 'date_ele_resp' id='date_ele_resp' size='10' value = \"".$date_ele_resp."\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />\n";
		echo "<a href=\"#calend\" onClick=\"".$cal2->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" border=\"0\" alt=\"Petit calendrier\" /></a>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else{
		echo "<tr style='display:none;'>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Date de visibilit�<br />de la note pour les<br />�l�ves et responsables:</td>\n";
		echo "<td>\n";
		echo "<input type='hidden' name='date_ele_resp' size='10' value = \"".$date_ele_resp."\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}


	if($aff_boite=='y'){
		echo "<tr>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Emplacement de l'�valuation:</td>\n";
		echo "<td>\n";

		echo "<select size='1' name='id_emplacement'>\n";
		$appel_conteneurs = mysql_query("SELECT * FROM cn_conteneurs WHERE id_racine ='$id_racine' order by nom_court");
		$nb_cont = mysql_num_rows($appel_conteneurs);
		$i = 0;
		while ($i < $nb_cont) {
			$id_cont = mysql_result($appel_conteneurs, $i, 'id');
			$nom_conteneur = mysql_result($appel_conteneurs, $i, 'nom_court');
			echo "<option value='$id_cont' ";
			if ($id_cont == $id_conteneur) echo "selected";
			//echo " >$nom_conteneur</option>\n";
			if($nom_conteneur==""){echo " >---</option>\n";}else{echo " >$nom_conteneur</option>\n";}
			$i++;
		}
		echo "</select>\n";

		echo "</td>\n";
		echo "</tr>\n";
	}
	else{
		echo "<tr style='display:none;'>\n";
		echo "<td style='background-color: #aae6aa; font-weight: bold;'>Emplacement de l'�valuation:</td>\n";
		echo "<td>\n";
		echo "<input type='hidden' name='id_emplacement' size='10' value='$id_conteneur' />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

/*	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "<a href='".$_SERVER['PHP_SELF']."?id_conteneur=$id_conteneur";
	if(isset($id_devoir)){
		echo "&amp;mode_navig=$mode_navig";
	}
	if(isset($id_devoir)){
		echo "&amp;id_devoir=$id_devoir";
	}
	echo "'>Interface compl�te</a>\n";
	echo "</td>\n";
	echo "</tr>\n";
*/
	echo "</table>\n";
	echo "</div>\n";
	echo "<input type='hidden' name='facultatif' value='$facultatif' />\n";
	echo "<input type='hidden' name='display_parents' value='$display_parents' />\n";
	echo "<input type='hidden' name='display_parents_app' value='$display_parents_app' />\n";
	echo "<input type='hidden' name='interface_simplifiee' value='$interface_simplifiee' />\n";

	if($aff_nom_court=='y'){
		echo "<script type='text/javascript'>
	document.formulaire.nom_court.focus();
</script>\n";
	}

	//echo "<center><input type=\"submit\" name='ok' value=\"Enregistrer\" style=\"font-variant: small-caps;\" /></center>\n";
	//echo "<br />\n";
}
else{
	//====================================
	// Noms et conteneur
	// =================

	echo "<table summary='Nom et conteneur du devoir'>\n";
	//echo "<tr><td>Nom court : </td><td><input type='text' name = 'nom_court' size='40' value = \"".$nom_court."\" /></td></tr>\n";
	echo "<tr><td>Nom court : </td><td><input type='text' name = 'nom_court' size='40' value = \"".$nom_court."\" onfocus=\"javascript:this.select()\" /></td></tr>\n";
	//echo "<tr><td>Nom complet : </td><td><input type='text' name = 'nom_complet' size='40' value = \"".$nom_complet."\" /></td></tr>\n";
	echo "<tr><td>Nom complet : </td><td><input type='text' name = 'nom_complet' size='40' value = \"".$nom_complet."\" onfocus=\"javascript:this.select()\" /></td></tr>\n";
	echo "<tr><td>Description : </td><td><textarea name='description' rows='2' cols='40' >".$description."</textarea></td></tr></table>\n";
	echo "<br />\n";
	echo "<table summary='Emplacement du devoir'><tr><td><h3 class='gepi'>Emplacement de l'�valuation : </h3></td>\n<td>";
	echo "<select size='1' name='id_emplacement'>\n";
	$appel_conteneurs = mysql_query("SELECT * FROM cn_conteneurs WHERE id_racine ='$id_racine' order by nom_court");
	$nb_cont = mysql_num_rows($appel_conteneurs);
	$i = 0;
	while ($i < $nb_cont) {
	$id_cont = mysql_result($appel_conteneurs, $i, 'id');
	$nom_conteneur = mysql_result($appel_conteneurs, $i, 'nom_court');
	echo "<option value='$id_cont' ";
	if ($id_cont == $id_conteneur) echo "selected";
	//echo " >$nom_conteneur</option>\n";
	if($nom_conteneur==""){echo " >---</option>\n";}else{echo " >$nom_conteneur</option>\n";}
	$i++;
	}
	echo "</select></td></tr></table>\n";

	//====================================
	// Coeff
	// =====

	echo "<h3 class='gepi'>Coefficient de l'�valuation</h3>\n";
	echo "<table summary='Ponderation'><tr><td>Valeur de la pond�ration dans le calcul de la moyenne (si 0, la note de l'�valuation n'intervient pas dans le calcul de la moyenne) : </td>";
	echo "<td><input type='text' name = 'coef' size='4' value = \"".$coef."\" onfocus=\"javascript:this.select()\" /></td></tr></table>\n";

	//====================================
	// Note autre que sur 20
	// =====
	if(getSettingValue("note_autre_que_sur_referentiel")=="V") {
	    echo "<h3 class='gepi'>Notation</h3>\n";
	    echo "<table summary='Referentiel'><tr><td>Note sur : </td>";
	    echo "<td><input type='text' name = 'note_sur' size='4' value = \"".$note_sur."\" onfocus=\"javascript:this.select()\" /></td></tr>\n";
	    echo "<tr><td>Ramener la note sur ".getSettingValue("referentiel_note")." lors du calcul de la moyenne : <br />";
		echo "<span style=\"font-size: x-small;\">Exemple avec 3 notes : 18/20 ; 4/10 ; 1/5<br />";
		echo "Case coch�e : moyenne = 18/20 + 8/20 + 4/20 = 30/60 = 10/20<br />";
		echo "Case non coch�e : moyenne = 18/20 + 4/10 + 1/5 = 23/35 = 13,1/20</span><br /><br />";
		echo "</td>";
		echo "</td><td><input type='checkbox' name='ramener_sur_referentiel' value='V'"; if ($ramener_sur_referentiel == 'V') {echo " checked";} echo " /><br />";
		echo "</td></tr>\n";
	} else {
		echo "<input type='hidden' name = 'note_sur' value = '".getSettingValue("referentiel_note")."'/>\n";
		echo "<input type='hidden' name = 'ramener_sur_referentiel' value = 'F' />\n";
	}

	//====================================
	// Statut
	// ======

	echo "<h3 class='gepi'>Statut de l'�valuation</h3>\n";
	echo "<table summary='Statut du devoir'><tr><td><input type='radio' name='facultatif' id='facultatif_O' value='O' "; if ($facultatif=='O') echo "checked"; echo " /></td><td>";
	echo "<label for='facultatif_O' style='cursor: pointer;'>";
	echo "La note de l'�valuation entre dans le calcul de la moyenne.";
	echo "</label>";
	echo "</td></tr>\n<tr><td><input type='radio' name='facultatif' id='facultatif_B' value='B' "; if ($facultatif=='B') echo "checked"; echo " /></td><td>";
	echo "<label for='facultatif_B' style='cursor: pointer;'>";
	echo "Seules les notes de l'�valuation sup�rieures � 10 entrent dans le calcul de la moyenne.";
	echo "</label>";
	echo "</td></tr>\n<tr><td><input type='radio' name='facultatif' id='facultatif_N' value='N' "; if ($facultatif=='N') echo "checked"; echo " /></td><td>";
	echo "<label for='facultatif_N' style='cursor: pointer;'>";
	echo "La note de l'�valuation n'entre dans le calcul de la moyenne que si elle am�liore la moyenne.";
	echo "</label>";
	echo "</td></tr></table>\n";

	//====================================
	// Date
	// ====

	echo "<a name=\"calend\"></a><h3 class='gepi'>Date de l'�valuation (format jj/mm/aaaa) : </h3>
	<b>Remarque</b> : c'est cette date qui est prise en compte pour l'�dition des relev�s de notes � diff�rentes p�riodes de l'ann�e.
	<input type='text' name = 'display_date' id='display_date' size='10' value = \"".$display_date."\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />";
	echo "<a href=\"#calend\" onClick=\"".$cal->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" border=\"0\" alt=\"Petit calendrier\" /></a>\n";


	echo "<a name=\"calend\"></a><h3 class='gepi'>Date de visibilit� de l'�valuation pour les �l�ves et responsables (format jj/mm/aaaa) : </h3>
	<b>Remarque</b> : Cette date permet de ne rendre la note visible qu'une fois que le devoir est corrig� en classe.
	<input type='text' name='date_ele_resp' id='date_ele_resp' size='10' value=\"".$date_ele_resp."\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />";
	echo "<a href=\"#calend\" onClick=\"".$cal2->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" border=\"0\" alt=\"Petit calendrier\" /></a>\n";

	//====================================
	// Relev� de notes
	// ===============

	echo "<h3 class='gepi'>Affichage sur le relev� de notes</h3>\n";
	echo "<table summary='Visibilit�'>\n";
	echo "<tr><td><label for='display_parents' style='cursor: pointer;'>";
	echo "Faire <b>appara�tre cette �valuation</b> sur le <b>relev� de notes</b> de l'�l�ve : ";
	echo "</label>";
	echo "</td><td><input type='checkbox' name='display_parents' id='display_parents' value='1' "; if ($display_parents == 1) echo " checked"; echo " /></td></tr>\n";

	echo "<tr><td><label for='display_parents_app' style='cursor: pointer;'>";
	echo "<b>L'appr�ciation</b> de l'�valuation est affichable sur le <b>relev� de notes</b> de l'�l�ve (si l'option pr�c�dente a �t� valid�e) :";
	echo "</label>";
	echo "</td><td><input type='checkbox' name='display_parents_app' id='display_parents_app' value='1' "; if ($display_parents_app == 1) echo " checked"; echo " /></td></tr>\n";

  echo "</table>\n";

	echo "<script type='text/javascript'>
	document.formulaire.nom_court.focus();
</script>\n";

}

if ($new_devoir=='yes') {
	echo "<input type='hidden' name='new_devoir' value='yes' />\n";

	//$sql="SELECT g.name, jgc.id_classe FROM groupes g, j_groupes_classes jgc WHERE ";
	$tab_group=get_groups_for_prof($_SESSION['login']);
	if(count($tab_group)>1) {

		if($interface_simplifiee=="y"){echo "<div align='center'>\n";}
		echo "<input type='checkbox' id='creation_dev_autres_groupes' name='creation_dev_autres_groupes' value='y' onchange=\"display_div_autres_groupes()\" /><label for='creation_dev_autres_groupes'> Cr�er le m�me devoir pour d'autres enseignements.</label><br />\n";
	
		echo "<div id='div_autres_groupes'>\n";
		echo "<table class='boireaus' summary='Autres enseignements'>\n";
		echo "<tr>\n";
		echo "<th rowspan='2'>";
		echo "<a href='javascript:modif_case(true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
		echo "<a href='javascript:modif_case(false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
		echo "</th>\n";
		echo "<th colspan='3'>Enseignement</th>\n";
		//echo "<th rowspan='2'>Boite</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>Nom</th>\n";
		echo "<th>Description</th>\n";
		echo "<th>Classe</th>\n";
		echo "</tr>\n";
	
		$alt=1;
		$cpt=0;
		for($i=0;$i<count($tab_group);$i++) {
			if($tab_group[$i]['id']!=$id_groupe) {
				// Tester si la p�riode est aussi ouverte pour le groupe... ou sinon si une seule p�riode est ouverte en saisie?
				$alt=$alt*(-1);
				echo "<tr class='lig$alt'>\n";
				echo "<td>\n";
				if($tab_group[$i]["classe"]["ver_periode"]["all"][$periode_num]>=2) {
					echo "<input type='checkbox' name='id_autre_groupe[]' id='case_$cpt' value='".$tab_group[$i]['id']."' />\n";
					echo "</td>\n";
					echo "<td><label for='case_$cpt'>".htmlentities($tab_group[$i]['name'])."</label></td>\n";
					echo "<td><label for='case_$cpt'>".htmlentities($tab_group[$i]['description'])."</label></td>\n";
					echo "<td><label for='case_$cpt'>".$tab_group[$i]['classlist_string']."</label></td>\n";
					$cpt++;
				}
				else {
					echo "<span style='color:red;'>Clos</span>";
					echo "</td>\n";
					echo "<td>".htmlentities($tab_group[$i]['name'])."</td>\n";
					echo "<td>".htmlentities($tab_group[$i]['description'])."</td>\n";
					echo "<td>".$tab_group[$i]['classlist_string']."</td>\n";
				}
				//echo "<td>...</td>\n";
				echo "</tr>\n";
			}
		}
		echo "</table>\n";
		// A METTRE AU POINT: 
		//echo "<input type='checkbox' name='creer_arbo_conteneurs' id='creer_arbo_conteneurs' value='y' /><label for='creer_arbo_conteneurs'> Cr�er si n�cessaire la m�me arborescence de boites/conteneurs.</label>\n";
		echo "<input type='checkbox' name='creer_conteneur' id='creer_conteneur' value='y' /><label for='creer_conteneur'> Cr�er si n�cessaire ";
		if(getSettingValue('gepi_denom_boite_genre')=="m") {echo "le ";} else {echo "la ";}
		echo getSettingValue('gepi_denom_boite');
		//echo "la boite conteneur";
		echo ".</label>\n";
		echo "</div>\n";
		if($interface_simplifiee=="y"){echo "</div>\n";}

		echo "<script type='text/javascript'>
function display_div_autres_groupes() {
	if(document.getElementById('creation_dev_autres_groupes').checked==true) {
		document.getElementById('div_autres_groupes').style.display='';
	}
	else {
		document.getElementById('div_autres_groupes').style.display='none';
	}
}
display_div_autres_groupes();

function modif_case(statut){
	for(k=0;k<$cpt;k++){
		if(document.getElementById('case_'+k)){
			document.getElementById('case_'+k).checked=statut;
		}
	}
}

</script>\n";

	}
}
echo "<input type='hidden' name='id_devoir' value='$id_devoir' />\n";
echo "<input type='hidden' name='id_conteneur' value='$id_conteneur' />\n";
echo "<input type='hidden' name='mode_navig' value='$mode_navig' />\n";
echo "<input type='hidden' name='id_retour' value='$id_retour' />\n";

//echo "<center><input type=\"submit\" name='ok' value=\"Enregistrer\" style=\"font-variant: small-caps;\" /></center>\n";
echo "<div style='display:none'><input type=\"hidden\" name='ok' value=\"Enregistrer\" /></div>\n";
echo "<p style='text-align:center;'><input type=\"submit\" name='ok1' value=\"Enregistrer\" style=\"font-variant: small-caps;\" /><br/>\n";
echo "<input type=\"button\" name='ok2' value=\"Enregistrer et saisir dans la foul�e\" style=\"font-variant: small-caps;\" onClick=\"document.forms['formulaire'].mode_navig.value='saisie_devoir';document.forms['formulaire'].submit();\" /></p>\n";

echo "</form>\n";
echo "<br />\n";
require("../lib/footer.inc.php");
?>
