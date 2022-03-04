<?php

/**
 * Fonctions pour l'EdT
 *
 * @package		GEPI
 * @subpackage	EmploisDuTemps
 * @copyright	Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Julien Jocal, Pascal Fautrero
 * @license		GNU/GPL, see COPYING.txt
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

// =============================================================================
//
//                                  PROTOS
//
//
// array 	function RetrieveWeeks()
// string 	function RetrieveColumnWeek($tab_data, $index_box, $jour, $week)
// void 	function SwapContainers(&$tab_data, &$index_box, $jour)
// void 	function FixColumnPositions(&$tab_data, $entetes) 
// void 	function VerifierTablesDelestage() 
// array    function EtudeDeCasTroisCours($tab_cours) 
// array    function ConstruireEnteteEDT() 
// array    function ConstruireCreneauxEDT() 
// void     function RemplirBox($elapse_time, &$tab_data_jour, &$index_box, $type, $id_creneaux, $id_groupe, $id_cours, $taille_box, $couleur, $contenu)
// string   function NameTemplateEDT()
// string   function ContenuCreneau($id_creneaux, $jour_semaine, $type_edt, $enseignement)

// =============================================================================
//
//		fonction interne pour FixColumnPositions
//
// =============================================================================
function RetrieveWeeks() {
	$week = array();
	$week[0] = "";
	$week[1] = "";
	$sql_request = "SELECT DISTINCT type_edt_semaine FROM edt_semaines ORDER BY type_edt_semaine";
	$req = mysql_query($sql_request);
	if ($req) {
		$i = 0;
		while ($rep = mysql_fetch_array($req)) {
			if ($rep['type_edt_semaine'] != "") {
				$week[$i] = "Sem.".$rep['type_edt_semaine'];
				$i++;
			}
		}
	}
	return $week;
}

// =============================================================================
//
//		fonction interne pour FixColumnPositions
//
// =============================================================================
function RetrieveColumnWeek($tab_data, $index_box, $jour, $week) {
	$index_box++;
	$NotFound = TRUE;
	$ReturnValue = "";
	while (($tab_data[$jour]['type'][$index_box] != "fin_conteneur") AND ($NotFound)){
		$pos1=FALSE;
		$pos2=FALSE;
		if($tab_data[$jour]['contenu'][$index_box]!='') {
			if((isset($week[0]))&&($week[0]!='')) {
				$pos1 = strpos($tab_data[$jour]['contenu'][$index_box], $week[0]);
			}
			if((isset($week[1]))&&($week[1]!='')) {
				$pos2 = strpos($tab_data[$jour]['contenu'][$index_box], $week[1]);
			}
		}
		if ($pos1 !== FALSE) {
			$NotFound = FALSE;
			$ReturnValue = $week[0];
		}
		else if ($pos2 !== FALSE) {
			$NotFound = FALSE;
			$ReturnValue = $week[1];
		}
		$index_box++;
	}
	return $ReturnValue;
}

// =============================================================================
//
//		fonction interne pour FixColumnPositions
//
// =============================================================================
function SwapContainers(&$tab_data, &$index_box, $jour) {
	$aux_tab = array();
	$index_container1 = $index_box;
	$index_container2 = $index_box+1;
	while ($tab_data[$jour]['type'][$index_container2] != "conteneur") {
		$index_container2++;	
	}
	$index = $index_container1;
	$index_destination = 0;
	while ($tab_data[$jour]['type'][$index] != "fin_conteneur") {
		RemplirBox($tab_data[$jour]['elapse_time'][$index],
					$aux_tab[$jour], 
					$index_destination, 
					$tab_data[$jour]['type'][$index],
					$tab_data[$jour]['id_creneau'][$index],
					$tab_data[$jour]['id_groupe'][$index],
					$tab_data[$jour]['id_cours'][$index],
					$tab_data[$jour]['duree'][$index],
					$tab_data[$jour]['couleur'][$index],
					$tab_data[$jour]['contenu'][$index]);
		$index++;
	}
	RemplirBox($tab_data[$jour]['elapse_time'][$index],
				$aux_tab[$jour], 
				$index_destination, 
				$tab_data[$jour]['type'][$index],
				$tab_data[$jour]['id_creneau'][$index],
				$tab_data[$jour]['id_groupe'][$index],
				$tab_data[$jour]['id_cours'][$index],
				$tab_data[$jour]['duree'][$index],
				$tab_data[$jour]['couleur'][$index],
				$tab_data[$jour]['contenu'][$index]);

	// =========================================
	$index = $index_container2;
	$index_destination = $index_container1;
	while ($tab_data[$jour]['type'][$index] != "fin_conteneur") {

		RemplirBox($tab_data[$jour]['elapse_time'][$index],
					$tab_data[$jour], 
					$index_destination, 
					$tab_data[$jour]['type'][$index],
					$tab_data[$jour]['id_creneau'][$index],
					$tab_data[$jour]['id_groupe'][$index],
					$tab_data[$jour]['id_cours'][$index],
					$tab_data[$jour]['duree'][$index],
					$tab_data[$jour]['couleur'][$index],
					$tab_data[$jour]['contenu'][$index]);
		$index++;
	}
	RemplirBox($tab_data[$jour]['elapse_time'][$index],
				$tab_data[$jour], 
				$index_destination, 
				$tab_data[$jour]['type'][$index],
				$tab_data[$jour]['id_creneau'][$index],
				$tab_data[$jour]['id_groupe'][$index],
				$tab_data[$jour]['id_cours'][$index],
				$tab_data[$jour]['duree'][$index],
				$tab_data[$jour]['couleur'][$index],
				$tab_data[$jour]['contenu'][$index]);	

				
	// =========================================
	$index = 0;

	while ($aux_tab[$jour]['type'][$index] != "fin_conteneur") {
		RemplirBox($aux_tab[$jour]['elapse_time'][$index],
					$tab_data[$jour], 
					$index_destination, 
					$aux_tab[$jour]['type'][$index],
					$aux_tab[$jour]['id_creneau'][$index],
					$aux_tab[$jour]['id_groupe'][$index],
					$aux_tab[$jour]['id_cours'][$index],
					$aux_tab[$jour]['duree'][$index],
					$aux_tab[$jour]['couleur'][$index],
					$aux_tab[$jour]['contenu'][$index]);
		$index++;
	}
	RemplirBox($aux_tab[$jour]['elapse_time'][$index],
				$aux_tab[$jour], 
				$index_destination, 
				$aux_tab[$jour]['type'][$index],
				$aux_tab[$jour]['id_creneau'][$index],
				$aux_tab[$jour]['id_groupe'][$index],
				$aux_tab[$jour]['id_cours'][$index],
				$aux_tab[$jour]['duree'][$index],
				$aux_tab[$jour]['couleur'][$index],
				$aux_tab[$jour]['contenu'][$index]);
	
	$index_box = $index_destination-1;

}

// =============================================================================
//
//		Organise les créneaux Semaine A - Semaine B
//		de façon à rendre la lecture plus élégante
//		les créneaux Sem.B sont tous alignés à droite
//		les créneaux Sem.A sont tous alignés à gauche
//
// =============================================================================
function FixColumnPositions(&$tab_data, $entetes) 
{
	$week = array();
	$week = RetrieveWeeks();
    $jour = 0;
	$conteneur = 0;
    while (isset($entetes['entete'][$jour])) {
        $index_box = 0;
        while (isset($tab_data[$jour]['type'][$index_box]))
        {
            if ($tab_data[$jour]['type'][$index_box] == "conteneur")
            {
				$conteneur++;
				$pos = strpos($tab_data[$jour]['duree'][$index_box], "demicellule");
				if (!($pos === FALSE)) {
					if ($conteneur != 1) {
						$conteneur = 0;
					}
					else {
						$ColumnWeek = RetrieveColumnWeek($tab_data, $index_box, $jour, $week);
						if ($ColumnWeek == $week[1]) {
							SwapContainers($tab_data, $index_box, $jour);
							$conteneur = 0;
						}
					}
				}
            }
            $index_box++;
        }
        $jour++;
    }

}
// ======================================================
//
//
//
// ======================================================
function VerifierTablesDelestage() 
{
	// ======= table pour optimiser les requêtes sql
    $sql = "CREATE TABLE IF NOT EXISTS j_eleves_groupes_delestage (
                login VARCHAR(50),
                id_groupe INT(11),
                periode INT(11)
                ) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
    $req_creation = mysql_query($sql) or die(mysql_error());
	// ======= table pour optimiser les requêtes sql
    $sql = "CREATE TABLE IF NOT EXISTS j_eleves_groupes_delestage2 (
                login VARCHAR(50),
                id_groupe INT(11),
                periode INT(11)
                ) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
    $req_creation = mysql_query($sql) or die(mysql_error());
}	

// ======================================================
//
//
//
// ======================================================
function EtudeDeCasTroisCours($tab_cours) 
{
    // ====================== travail préparatoire
    $tab_cas['indice'] = 0;

    $duree1 = $tab_cours['duree'][0];
    $heuredeb_dec1 = $tab_cours['heuredeb_dec'][0];
    $id_semaine1 = $tab_cours['id_semaine'][0];
    
    $duree2 = $tab_cours['duree'][1];
    $heuredeb_dec2 = $tab_cours['heuredeb_dec'][1];
    $id_semaine2 = $tab_cours['id_semaine'][1];
    
    $duree3 = $tab_cours['duree'][2];
    $heuredeb_dec3 = $tab_cours['heuredeb_dec'][2];
    $id_semaine3 = $tab_cours['id_semaine'][2];

    $somme_heures = $heuredeb_dec1 + $heuredeb_dec2 + $heuredeb_dec3;
    $somme_durees = 0;

    if (($heuredeb_dec1 == 0.5) AND ($duree1 > 1)) {
        $duree1 = 1;
    }
    if (($heuredeb_dec2 == 0.5) AND ($duree2 > 1)) {
        $duree2 = 1;
    }
    if (($heuredeb_dec3 == 0.5) AND ($duree3 > 1)) {
        $duree3 = 1;
    }
    if ($duree1 == 1) {
        $somme_durees += 1;
    } 
    else {
        $somme_durees += 2;
    }   

    if ($duree2 == 1) {
        $somme_durees += 1;
    } 
    else {
        $somme_durees += 2;
    }   

    if ($duree3 == 1) {
        $somme_durees += 1;
    } 
    else {
        $somme_durees += 2;
    }  
    // ======================== Etudes des cas
    if ($somme_heures == 0) {
        if ($somme_durees == 3) {
            $tab_cas['cas_detecte'] = 26;
        } 
        else if ($somme_durees == 4) {
            $tab_cas['cas_detecte'] = 30;
        }
        else if ($somme_durees == 5) {
            $tab_cas['cas_detecte'] = 29;
        }
        else if ($somme_durees == 6) {
            $tab_cas['cas_detecte'] = 22;
        }  
        else {
            $tab_cas['cas_detecte'] = "erreur 1";
        } 
    }  
    else if ($somme_heures == 0.5) {
        if ($somme_durees == 5) {
            $tab_cas['cas_detecte'] = 23;
        } 
        else if ($somme_durees == 4) {
            $tab_cas['cas_detecte'] = 21;
        } 
        else if ($somme_durees == 3) {
            if ($heuredeb_dec1 == 0.5) {
                if ($id_semaine1 == "0") {
                    $tab_cas['cas_detecte'] = 18;
                    $tab_cas['indice'] = 0;
                }
                else if (($id_semaine1 == $id_semaine2) OR ($id_semaine1 == $id_semaine3)){
                    $tab_cas['cas_detecte'] = 20;
                    $tab_cas['indice'] = 0;
                }
                else {
                    $tab_cas['cas_detecte'] = 27;
                    $tab_cas['indice'] = 0;
                }
            } 
            else if ($heuredeb_dec2 == 0.5) {
                if ($id_semaine2 == "0") {
                    $tab_cas['cas_detecte'] = 18;
                    $tab_cas['indice'] = 1;
                }
                else if (($id_semaine2 == $id_semaine1) OR ($id_semaine2 == $id_semaine3)){
                    $tab_cas['cas_detecte'] = 20;
                    $tab_cas['indice'] = 1;
                }
                else {
                    $tab_cas['cas_detecte'] = 27;
                    $tab_cas['indice'] = 1;
                }
            }
            else if ($heuredeb_dec3 == 0.5) {
                if ($id_semaine3 == "0") {
                    $tab_cas['cas_detecte'] = 18;
                    $tab_cas['indice'] = 2;
                }
                else if (($id_semaine3 == $id_semaine1) OR ($id_semaine3 == $id_semaine2)){
                    $tab_cas['cas_detecte'] = 20;
                    $tab_cas['indice'] = 2;
                }
                else {
                    $tab_cas['cas_detecte'] = 27;
                    $tab_cas['indice'] = 2;
                }
            }
            else {
                $tab_cas['cas_detecte'] = "erreur 2";
            }
        } 
        else {
            $tab_cas['cas_detecte'] = "erreur 3";
        }
    }  
    else if ($somme_heures == 1) {
        if ($somme_durees == 4) {
            $tab_cas['cas_detecte'] = 24;
        } 
        else if ($somme_durees == 3) {
            if ($heuredeb_dec1 == 0) {
                if ($id_semaine1 == "0") {
                    $tab_cas['cas_detecte'] = 17;
                    $tab_cas['indice'] = 0;
                }
                else if (($id_semaine1 == $id_semaine2) OR ($id_semaine1 == $id_semaine3)){
                    $tab_cas['cas_detecte'] = 19;
                    $tab_cas['indice'] = 0;
                }
                else {
                    $tab_cas['cas_detecte'] = 28;
                    $tab_cas['indice'] = 0;
                }
            } 
            else if ($heuredeb_dec2 == 0) {
                if ($id_semaine2 == "0") {
                    $tab_cas['cas_detecte'] = 17;
                    $tab_cas['indice'] = 1;
                }
                else if (($id_semaine2 == $id_semaine1) OR ($id_semaine2 == $id_semaine3)){
                    $tab_cas['cas_detecte'] = 19;
                    $tab_cas['indice'] = 1;
                }
                else {
                    $tab_cas['cas_detecte'] = 28;
                    $tab_cas['indice'] = 1;
                }
            }
            else if ($heuredeb_dec3 == 0) {
                if ($id_semaine3 == "0") {
                    $tab_cas['cas_detecte'] = 17;
                    $tab_cas['indice'] = 2;
                }
                else if (($id_semaine3 == $id_semaine1) OR ($id_semaine3 == $id_semaine2)){
                    $tab_cas['cas_detecte'] = 19;
                    $tab_cas['indice'] = 2;
                }
                else {
                    $tab_cas['cas_detecte'] = 28;
                    $tab_cas['indice'] = 2;
                }
            }
            else {
                $tab_cas['cas_detecte'] = "erreur 4";
            }
        } 
        else {
            $tab_cas['cas_detecte'] = "erreur 5";
        }
    } 
    else if ($somme_heures == 1.5) {
        $tab_cas['cas_detecte'] = 25;
    } 
    else {
        $tab_cas['cas_detecte'] = "erreur 6";
    } 
    return $tab_cas;
}


// =============================================================================
//
//                  
//
// =============================================================================
function ConstruireEnteteEDT() 
{
    $table_data = array();

    $req_jours = mysql_query("SELECT jour_horaire_etablissement FROM horaires_etablissement WHERE ouvert_horaire_etablissement = 1") or die(mysql_error());
    $jour_sem_tab = array();
    while($data_sem_tab = mysql_fetch_array($req_jours)) {
	    $jour_sem_tab[] = $data_sem_tab["jour_horaire_etablissement"];
        $tab_data['entete'][] = $data_sem_tab["jour_horaire_etablissement"];
    }
    return $tab_data;
}
// =============================================================================
//
//                  
//
// =============================================================================
function ConstruireCreneauxEDT() 
{
    $table_data = array();
    $req_id_creneaux = mysql_query("SELECT id_definie_periode FROM edt_creneaux
							    WHERE type_creneaux != 'pause'") or die(mysql_error());
    $nbre_lignes = mysql_num_rows($req_id_creneaux);
    if ($nbre_lignes == 0) {
        $nbre_lignes = 1;
    }
    if ($nbre_lignes > 12) {
        $nbre_lignes = 12;
    }
    $tab_data['nb_creneaux'] = $nbre_lignes;

    $reglages_creneaux = GetSettingEdt("edt_aff_creneaux");
    //Cas où le nom des créneaux sont inscrits à gauche
    if ($reglages_creneaux == "noms") {
	    $tab_creneaux = retourne_creneaux();
	    $i=0;
	    while($i<count($tab_creneaux)){
		    $tab_id_creneaux = retourne_id_creneaux();
		    $c=0;
		    while($c<count($tab_id_creneaux)){
                $tab_data['creneaux'][$i] = $tab_creneaux[$i];
			    $i ++;
			    $c ++;
		    }
	    }
    }
    
    // Cas où les heures sont inscrites à gauche au lieu du nom des créneaux
    elseif ($reglages_creneaux == "heures") {
	    $tab_horaire = retourne_horaire();
	    for($i=0; $i<count($tab_horaire); ) {
    
	    $tab_id_creneaux = retourne_id_creneaux();
		    $c=0;
		    while($c<count($tab_id_creneaux)){
                $tab_data['creneaux'][$i] = $tab_horaire[$i]["heure_debut"]."<br />".$tab_horaire[$i]["heure_fin"];
			    $i++;
			    $c ++;
		    }
	    }
    }
    return $tab_data;
}

// =============================================================================
//
// =============================================================================
function RemplirBox($elapse_time, &$tab_data_jour, &$index_box, $type, $id_creneaux, $id_groupe, $id_cours, $taille_box, $couleur, $contenu)
{
	// 20130528
	global $login_prof_contenu_creneaux_courant;

    $tab_data_jour['type'][$index_box] = $type;
    $tab_data_jour['duree'][$index_box] = $taille_box;
    $tab_data_jour['contenu'][$index_box] = $contenu;
    $tab_data_jour['couleur'][$index_box] = $couleur;
    $tab_data_jour['id_creneau'][$index_box] = $id_creneaux;
    $tab_data_jour['id_cours'][$index_box] = $id_cours;
    $tab_data_jour['id_groupe'][$index_box] = $id_groupe;
    $tab_data_jour['elapse_time'][$index_box] = $elapse_time;
    if ($elapse_time%2 == 0)
    {
        $tab_data_jour['heuredeb_dec'][$index_box] = 0;
    }
    else
    {
        $tab_data_jour['heuredeb_dec'][$index_box] = 1;
    }

	if($login_prof_contenu_creneaux_courant!="") {
		$tab_data_jour['login_prof'][$index_box] = $login_prof_contenu_creneaux_courant;
	}
	if($id_creneaux!="") {
		$sql="SELECT heuredebut_definie_periode FROM edt_creneaux WHERE id_definie_periode='$id_creneaux';";
		$res_tmp=mysql_query($sql);
		if(mysql_num_rows($res_tmp)>0) {
			$tab_data_jour['heuredebut'][$index_box] = mysql_result($res_tmp, 0, 'heuredebut_definie_periode');
		}
	}

    // ====================== Récupérer la durée chiffrée du cours (en heures)
    preg_match_all('#[0-9]+#',$taille_box,$extract);
    if (isset($extract[0][0])) {
        $extract[0][0] = $extract[0][0] / 2;
        $tab_data_jour['duree_valeur'][$index_box] = $extract[0][0];
    }
    else {
        $tab_data_jour['duree_valeur'][$index_box] = 0;
    }
    // ===================== Récupérer le créneau de début de séance
	$tab_creneaux = retourne_creneaux();
    $index_creneau = $elapse_time;
    if ($index_creneau % 2 != 0) {
        $index_creneau--;
    }
    $index_creneau = $index_creneau / 2;
    if ($index_creneau >= count($tab_creneaux)) {
        $index_creneau = 0;
    }
    $tab_data_jour['affiche_creneau'][$index_box] = $tab_creneaux[$index_creneau];
    if ($elapse_time % 2 != 0) {
        $tab_data_jour['affiche_creneau'][$index_box] .= " milieu ";
    }
    else {
        $tab_data_jour['affiche_creneau'][$index_box] .= " début ";
    }

    // ===================== Définir une couleur spécifique pour le créneau du repas
    if (($type == "vide") AND ($couleur == "cadre")) {
        $sql_request = "SELECT type_creneaux FROM edt_creneaux
							        WHERE id_definie_periode  = '".$id_creneaux."'";
        $req_type_creneaux = mysql_query($sql_request) or die(mysql_error());
        if ($req_type_creneaux) {
            if ($rep_type_creneau = mysql_fetch_array($req_type_creneaux)) {
                if ($rep_type_creneau['type_creneaux'] == "repas") {
                    $tab_data_jour['couleur'][$index_box] = "cadreRepas";
                }

            }
        }

    }
    $index_box++;
}


// =============================================================================
//
//     	Récupère le nom du dossier gepi/templates/... utilisé     
//
// =============================================================================
function NameTemplateEDT()
{
    return "DefaultEDT";
}


// =============================================================================
//
//          fonction de Julien Jocal reprise et adaptée
//
// =============================================================================
function ContenuCreneau($id_creneaux, $jour_semaine, $type_edt, $enseignement, $id_aid, $id_semaine, $period)
{
	// 20130128
	global $contenu_creneaux_edt_avec_span_title;

	// 20130528
	global $login_prof_contenu_creneaux_courant;

	$login_prof_contenu_creneaux_courant="";

    if (($period != NULL) AND ($period != '0')) {
        $calendrier = "(id_calendrier = '".$period."' OR id_calendrier = '0')";
    }
    else {
        $calendrier = "1=1";
    }
    if (($id_semaine == "") OR ($id_semaine =="0") OR ($id_semaine == NULL)) {
	    // On récupère l'id
        if ($enseignement == "") {
	        $req_recup_id = mysql_fetch_array(mysql_query("SELECT id_cours, login_prof FROM edt_cours WHERE
										        id_aid = '".$id_aid."' AND
										        jour_semaine = '".$jour_semaine."' AND
										        id_definie_periode = '".$id_creneaux."' AND
                                                $calendrier
                                                "));
        }
        else {
	        $req_recup_id = mysql_fetch_array(mysql_query("SELECT id_cours, login_prof FROM edt_cours WHERE
										        id_groupe = '".$enseignement."' AND
										        jour_semaine = '".$jour_semaine."' AND
										        id_definie_periode = '".$id_creneaux."' AND
                                                $calendrier
                                                "));
        }

	}
    else {
	    // On récupère l'id
        if ($enseignement == "") {

	        $req_recup_id = mysql_fetch_array(mysql_query("SELECT id_cours, login_prof FROM edt_cours WHERE
										        id_aid = '".$id_aid."' AND
										        jour_semaine = '".$jour_semaine."' AND
                                                id_semaine = '".$id_semaine."' AND
										        id_definie_periode = '".$id_creneaux."'AND
                                                $calendrier
                                                "));
        }
        else {
	        $req_recup_id = mysql_fetch_array(mysql_query("SELECT id_cours, login_prof FROM edt_cours WHERE
										        id_groupe = '".$enseignement."' AND
										        jour_semaine = '".$jour_semaine."' AND
                                                id_semaine = '".$id_semaine."' AND
										        id_definie_periode = '".$id_creneaux."'AND
                                                $calendrier
                                                "));

        }


	}

	// Pour afficher des détails en attribut title
	$info_alt="";

	// On vérifie si $enseignement est ou pas pas un AID (en vérifiant qu'il est bien renseigné)

	if (($id_aid != NULL) AND ($id_aid != "")) 
    {
		//$info_alt.="\C'est un AID\n";
		//echo "c'est un AID";
		$req_nom_aid = mysql_query("SELECT nom, indice_aid FROM aid WHERE id = '".$id_aid."'");
		$rep_nom_aid = mysql_fetch_array($req_nom_aid);

		// On récupère le nom de l'aid
		$req_nom_complet = mysql_query("SELECT nom FROM aid_config WHERE indice_aid = '".$rep_nom_aid["indice_aid"]."'");
		$rep_nom_complet = mysql_fetch_array($req_nom_complet);
		$aff_matiere = $rep_nom_complet["nom"]." ".$rep_nom_aid["nom"];

		$contenu="";

		// On compte les élèves de l'aid $aff_nbre_eleve
		$req_nbre_eleves = mysql_query("SELECT login FROM j_aid_eleves WHERE id_aid = '".$id_aid."' ORDER BY login");
		$aff_nbre_eleve = mysql_num_rows($req_nbre_eleves);
		for($a=0; $a < $aff_nbre_eleve; $a++) {
			$rep_eleves[$a]["login"] = mysql_result($req_nbre_eleves, $a, "login");
			$noms = mysql_fetch_array(mysql_query("SELECT nom, prenom FROM eleves WHERE login = '".$rep_eleves[$a]["login"]."'"));
			$contenu .= $noms["nom"]." ".$noms["prenom"]."<br />";
		}
		$titre_listeleve = "Liste des élèves (".$aff_nbre_eleve.")";
		$id_div_p = $jour_semaine.$rep_nom_aid["nom"].$id_creneaux.$enseignement;
		$id_div = strtr($id_div_p, " -|/'&;", "wwwwwww");
		//$classe_js = "<a href=\"#\" onclick=\"afficher_div('".$id_div."','Y',10,10);return false;\">".$rep_nom_aid["nom"]."</a>
		//	".creer_div_infobulle($id_div, $titre_listeleve, "#330033", $contenu, "#FFFFFF", 20,0,"y","y","n","n");
		$classe_js = $rep_nom_aid["nom"];

		// On dresse la liste des noms de prof (on n'affiche que le premier)
		//$noms_prof = mysql_fetch_array(mysql_query("SELECT nom, civilite FROM j_aid_utilisateurs jau, utilisateurs u WHERE
		//							id_aid = '".$analyse[1]."' AND
		//							jau.id_utilisateur = u.login
		//							ORDER BY nom LIMIT 1")); // on n'en garde qu'un
		$req_nom_prof = mysql_query("SELECT nom, civilite FROM utilisateurs WHERE login ='".$req_recup_id['login_prof']."'");
		$rep_nom_prof = mysql_fetch_array($req_nom_prof);

		$login_prof_contenu_creneaux_courant=$req_recup_id['login_prof'];

		//$rep_nom_prof['civilite'] = $noms_prof["nom"].' ';//$noms_prof["civilite"].' '.
		//$rep_nom_prof['nom'] = " ";


	}
    else if ($enseignement != "") 
    {
		//$info_alt.="\nEnseignement $enseignement\n";
		// on récupère le nom court des groupes en question
		$req_id_classe = mysql_query("SELECT id_classe FROM j_groupes_classes WHERE id_groupe ='".$enseignement."'");
        $res="";
        while ($rep_id_classe = mysql_fetch_array($req_id_classe)) {
    		$req_classe = mysql_query("SELECT classe FROM classes WHERE id ='".$rep_id_classe['id_classe']."'");
            $rep_classe = mysql_fetch_array($req_classe);
		    $res = $res." ".$rep_classe['classe'];
        }
        $rep_classe['classe'] = $res;

		$info_alt.=" en $res";

		// On récupère la période active en passant d'abord par le calendrier
		$query_cal = mysql_query("SELECT numero_periode FROM edt_calendrier WHERE
														debut_calendrier_ts <= '".date("U")."'
														AND fin_calendrier_ts >= '".date("U")."'
														AND numero_periode != '0'
														AND classe_concerne_calendrier LIKE '%".$rep_id_classe['id_classe']."%'")
									OR trigger_error('Impossible de lire le calendrier.', E_USER_NOTICE);
		$p_c = mysql_fetch_array($query_cal);

		$query_periode = mysql_query("SELECT num_periode FROM periodes WHERE verouiller = 'N' OR verouiller = 'P'")
									OR trigger_error('Impossible de récupérer la bonne période.', E_USER_NOTICE);
		$p = mysql_fetch_array($query_periode);

		$per = isset($p_c["numero_periode"]) ? $p_c["numero_periode"] : (isset($p["num_periode"]) ? $p["num_periode"] : "1");

		// On compte le nombre d'élèves
		$req_compter_eleves = mysql_query("SELECT COUNT(*) FROM j_eleves_groupes WHERE periode = '".$per."' AND id_groupe ='".$enseignement."'");
		$rep_compter_eleves = mysql_fetch_array($req_compter_eleves);
		$aff_nbre_eleve = $rep_compter_eleves[0];

		// On récupère la liste des élèves de l'enseignement
		if (($type_edt == "prof") OR ($type_edt == "salle")) {
			$current_group = get_group($enseignement);

			$contenu="";

			// $per étant le numéro de la période
			if (isset($current_group["eleves"][$per]["users"])) {
				foreach ($current_group["eleves"][$per]["users"] as $eleve_login) {
					$contenu .= $eleve_login['nom']." ".$eleve_login['prenom']."<br />";
				}
			}

			$titre_listeleve = "Liste des élèves (".$aff_nbre_eleve.")";

			$info_alt.=" ($aff_nbre_eleve élèves)";

			//$classe_js = aff_popup($rep_classe['classe'], "edt", $titre_listeleve, $contenu);
			$id_div_p = $jour_semaine.$rep_classe['classe'].$id_creneaux.rand();
			$id_div = strtr($id_div_p, " -|/'&;", "wwwwwww");
			//$classe_js = "<a href=\"#\" onclick=\"afficher_div('".$id_div."','Y',10,10);return false;\">".$rep_classe['classe']."</a>
			//	".creer_div_infobulle($id_div, $titre_listeleve, "#330033", $contenu, "#FFFFFF", 20,0,"y","y","n","n");
            $classe_js = $rep_classe['classe'];
		}
		// On récupère le nom et la civilite du prof en question
        if ($id_semaine == "") {
            $req_login_prof = mysql_query("SELECT login_prof FROM edt_cours WHERE 
                                                                        id_groupe ='".$enseignement."' AND
                                                                        id_definie_periode = '".$id_creneaux."' AND
                                                                        jour_semaine = '".$jour_semaine."' AND
                                                                        $calendrier");
		}
        else {

             $req_login_prof = mysql_query("SELECT login_prof FROM edt_cours WHERE 
                                                                    id_groupe ='".$enseignement."' AND
                                                                    id_definie_periode = '".$id_creneaux."' AND
                                                                    jour_semaine = '".$jour_semaine."' AND
                                                                    id_semaine = '".$id_semaine."'  AND
                                                                    $calendrier");
		}
		$rep_login_prof = mysql_fetch_array($req_login_prof);
		//$req_nom_prof = mysql_query("SELECT nom, civilite FROM utilisateurs WHERE login ='".$rep_login_prof['login']."'");
		$req_nom_prof = mysql_query("SELECT nom, civilite FROM utilisateurs WHERE login ='".$rep_login_prof['login_prof']."'");
		$rep_nom_prof = mysql_fetch_array($req_nom_prof);

		$login_prof_contenu_creneaux_courant=$rep_login_prof['login_prof'];

		// On récupère le nom de l'enseignement en question (en fonction du paramètre long ou court)
		if (GetSettingEdt("edt_aff_matiere") == "long") {
		    $req_matiere = mysql_query("SELECT nom_complet FROM matieres WHERE matiere IN (SELECT id_matiere FROM j_groupes_matieres WHERE id_groupe ='".$enseignement."') ");
		    $rep_matiere = mysql_fetch_array($req_matiere);
			$aff_matiere = $rep_matiere['nom_complet'];
            $aff_matiere = my_ereg_replace('[&]','&amp;',$aff_matiere);

		}
		elseif (GetSettingEdt("edt_aff_matiere") == "nom_court_groupe") {
			$req_2_matiere = mysql_query("SELECT name FROM groupes WHERE id='".$enseignement."'");
			$rep_2_matiere = mysql_fetch_array($req_2_matiere);
			$aff_matiere = $rep_2_matiere['name'];
		}
		elseif (GetSettingEdt("edt_aff_matiere") == "description_groupe") {
			$req_2_matiere = mysql_query("SELECT description FROM groupes WHERE id='".$enseignement."'");
			$rep_2_matiere = mysql_fetch_array($req_2_matiere);
			$aff_matiere = $rep_2_matiere['description'];
		}
		else {
			// GetSettingEdt("edt_aff_matiere") == "court"
			$req_2_matiere = mysql_query("SELECT id_matiere FROM j_groupes_matieres WHERE id_groupe ='".$enseignement."'");
			$rep_2_matiere = mysql_fetch_array($req_2_matiere);
			$aff_matiere = $rep_2_matiere['id_matiere'];
		}

		//$info_alt="";
		$req_tmp_grp = mysql_query("SELECT * FROM groupes WHERE id='".$enseignement."'");
		if(mysql_num_rows($req_tmp_grp)>0) {
		$lig_tmp_grp = mysql_fetch_object($req_tmp_grp);
			$info_alt=$lig_tmp_grp->name." (".$lig_tmp_grp->description.") ".$info_alt;
		}

	}

    else
    {
		//$info_alt.="\nGroupe non renseigné dans l'appel de la fonction.\n";
		// le groupe n'est pas renseigné, donc, on affiche en fonction
		$aff_matiere = 'inc.';
		$classe_js = NULL;
		$aff_nbre_eleve = '0';
		$aff_sem = NULL;
		$rep_salle = NULL;

	}

	// On récupère le type de semaine si besoin
	$req_sem = mysql_query("SELECT id_semaine FROM edt_cours WHERE id_cours ='".$req_recup_id["id_cours"]."'");
	$rep_sem = mysql_fetch_array($req_sem);
	if ($rep_sem["id_semaine"] == "0") {
		$aff_sem = '';
	}else {
		//$aff_sem = '<span style="font-color:#663333;"> - Sem.'.$rep_sem["id_semaine"].'</span>';
		$aff_sem = '- Sem.'.$rep_sem["id_semaine"]." - ";
	}

	//=============================
	// Initialisation
	$rep_salle="";
	// On récupère le nom complet de la salle en question
	if (GetSettingEdt("edt_aff_salle") == "nom") {
		$salle_aff = "nom_salle";
	}else {
		$salle_aff = "numero_salle";
	}
	//$req_id_salle = mysql_query("SELECT id_salle FROM edt_cours WHERE id_groupe ='".$enseignement."' AND id_definie_periode ='".$id_creneaux."' AND jour_semaine ='".$jour_semaine."'");

	$sql="SELECT id_salle FROM edt_cours WHERE id_cours ='".$req_recup_id["id_cours"]."'";
	$req_id_salle = mysql_query($sql);
	$rep_id_salle = mysql_fetch_array($req_id_salle);

	//$info_alt.=" $sql";
	//$sql="SELECT ".$salle_aff." FROM salle_cours WHERE id_salle ='".$rep_id_salle['id_salle']."'";
	$sql="SELECT * FROM salle_cours WHERE id_salle ='".$rep_id_salle['id_salle']."'";
	$req_salle = mysql_query($sql);
	//$tab_rep_salle = mysql_fetch_array($req_salle);
	//$rep_salle = $tab_rep_salle[0];
	if(mysql_num_rows($req_salle)>0) {
		$lig_rep_salle = mysql_fetch_object($req_salle);
		$rep_salle = $lig_rep_salle->$salle_aff;

		// Si le champ nom_salle est vide:
		if($rep_salle=='') {
			//$rep_salle=$rep_id_salle["numero_salle"];
			//$rep_salle=$rep_id_salle["numero_salle"];
			$rep_salle = $lig_rep_salle->numero_salle;
		}
	}

	//$info_alt.=" $sql";
	if($rep_salle!="") {$info_alt.=" en salle $rep_salle";}
	//=============================

	if (!isset($rep_nom_prof['nom'])){
        $rep_nom_prof['nom'] = " ";
    }


	$ChaineComplete="";
	// 20130128
	if(($contenu_creneaux_edt_avec_span_title!="n")&&($info_alt!="")) {
		if($aff_sem!="") {
			$info_alt.="\nEn semaine ".$rep_sem["id_semaine"];
		}
		if($enseignement!="") {
			$info_alt.="\n(enseignement n°".$enseignement.")";
		}

		$ChaineComplete.="<span title=\"$info_alt\">";
	}

	if ($type_edt == "prof"){
		if ($id_aid == "") 
		{
			$ChaineComplete.=$aff_matiere." ".$classe_js."<br />\n";
		}
		else
		{
			$ChaineComplete.=$aff_matiere."<br />\n";
		} 

		$ChaineComplete.=$aff_sem." <i>".$rep_salle."</i>";

		if ($aff_nbre_eleve != 0)
		{
			//$ChaineComplete = $ChaineComplete.",".$aff_nbre_eleve." él.\n";
		}
		//echo "$ChaineComplete<br />";
	} elseif (($type_edt == "classe") OR ($type_edt == "eleve")) {

		$ChaineComplete.=$aff_matiere."<br />".$rep_nom_prof['nom']."<br /><i>".$rep_salle."</i> ".$aff_sem."";

	} elseif ($type_edt == "salle"){

		if ($id_aid == "") 
		{
			$ChaineComplete.=$aff_matiere."<br/>".$rep_nom_prof['nom']." ".$classe_js."<br />\n";
		}
		else
		{
			$ChaineComplete.=$aff_matiere."<br/>".$rep_nom_prof['nom']."<br/>\n";
		} 

		$ChaineComplete.= $aff_sem;
		//return ("".$aff_matiere."<br />\n".$rep_nom_prof['civilite']." ".$rep_nom_prof['nom']." ".$aff_sem."<br />\n".$classe_js."\n");
	}

	// 20130128
	if(($contenu_creneaux_edt_avec_span_title!="n")&&($info_alt!="")) {
		$ChaineComplete.="</span>";
	}

	return $ChaineComplete;
}

// Fonction qui renvoie le nombre de lignes du tableau EdT

function nbre_lignes_tab_edt(){
	$compter_lignes = mysql_query("SELECT nom_definie_periode FROM edt_creneaux");
	$nbre_lignes = (mysql_num_rows($compter_lignes)) + 1;
	return $nbre_lignes;
}


// Fonction qui renvoie le nombre de colonnes du tableau EdT

function nbre_colonnes_tab_edt(){
	//global $compter_colonnes;
	$compter_colonnes = mysql_query("SELECT jour_horaire_etablissement FROM horaires_etablissement");
	$nbre_colonnes = (mysql_num_rows($compter_colonnes)) + 1;
	return $nbre_colonnes;
}


// Fonction qui renvoie la liste des créneaux (M1, M2, M3, ...) dans l'ordre de la journée

function retourne_creneaux(){

	$req_nom_creneaux_r = mysql_query("SELECT nom_definie_periode FROM edt_creneaux WHERE type_creneaux != 'pause' ORDER BY heuredebut_definie_periode");
	if ($req_nom_creneaux_r) {
		$rep_creneaux = array();
		while($data_creneaux = mysql_fetch_array($req_nom_creneaux_r)) {
			$rep_creneaux[] = $data_creneaux["nom_definie_periode"];
		}
	}else{
		$rep_creneaux = '';
	}
	return $rep_creneaux;
}

// Fonction qui retourne la liste des horaires 08h00 - 09h00 au lieu des M1 M2 et Cie

function retourne_horaire(){

	$req_nom_horaire = mysql_query("SELECT heuredebut_definie_periode, heurefin_definie_periode FROM edt_creneaux WHERE type_creneaux != 'pause' ORDER BY heuredebut_definie_periode");

	if ($req_nom_horaire) {
		$num_nom_horaire = mysql_num_rows($req_nom_horaire);
		$horaire = array();
		for($i=0; $i<$num_nom_horaire; $i++) {
			$horaire1[$i]["heure_debut"] = mysql_result($req_nom_horaire, $i, "heuredebut_definie_periode");
			$exp_hor = explode(":", $horaire1[$i]["heure_debut"]);
			$horaire[$i]["heure_debut"] = $exp_hor[0].":".$exp_hor[1]; // On enlève les secondes
			$horaire1[$i]["heure_fin"] = mysql_result($req_nom_horaire, $i, "heurefin_definie_periode");
			$exp_hor = explode(":", $horaire1[$i]["heure_fin"]);
			$horaire[$i]["heure_fin"] = $exp_hor[0].":".$exp_hor[1];
		}
	}else{
		$horaire = '';
	}

	return $horaire;
}


// Fonction qui renvoie la liste des id_creneaux dans l'ordre de la journée

function retourne_id_creneaux(){

	$req_id_creneaux = mysql_query("SELECT id_definie_periode FROM edt_creneaux
								WHERE type_creneaux != 'pause'
								ORDER BY heuredebut_definie_periode");
	// On compte alors le nombre de réponses et on renvoie en fonction de la réponse
	$nbre_rep = count($req_id_creneaux);
	if ($nbre_rep == 0) {
		return "aucun";
	}elseif ($req_id_creneaux) {
		$rep_id_creneaux = array();
		while($data_id_creneaux = mysql_fetch_array($req_id_creneaux)) {
			$rep_id_creneaux[] = $data_id_creneaux["id_definie_periode"];
		}
	}else{
		Die('Erreur sur retourne_id_creneaux 1');
	}
	return $rep_id_creneaux;
}


// Fonction qui renvoie un tableau des réglages de la table edt-setting

function retourne_setting_edt($reglage_edt){

	$req_edt_set = mysql_query("SELECT valeur FROM edt_setting WHERE reglage ='".$reglage_edt."'");
	$rep_edt_set = mysql_fetch_array($req_edt_set);

	$setting_edt = $rep_edt_set["valeur"];

	return $setting_edt;
}


// Fonction qui renvoie les id_groupe à un horaire donné (jour_semaine id_definie_periode)

function retourne_ens($jour_semaine, $id_creneaux){

	$req_nom_creneaux = mysql_query("SELECT nom_definie_periode FROM edt_creneaux WHERE id_definie_periode ='".$id_creneaux."'");
	$rep_nom_creneaux = mysql_fetch_array($req_nom_creneaux);
	// On récupère tous les enseignements de l'horaire
	$req_ens = mysql_query("SELECT id_groupe FROM edt_cours WHERE id_definie_periode='".$id_creneaux."' && jour_semaine ='".$jour_semaine."'");

	$result_ens = array();
	while($rep_ens = mysql_fetch_array($req_ens)) {
		$result_ens[] = $rep_ens;
	}
	return $result_ens;
}


// Fonction qui renvoie les enseignements d'un professeur (id_groupe)

function enseignements_prof($login_prof, $rep){

	$req = mysql_query("SELECT id_groupe FROM j_groupes_professeurs WHERE login ='".$login_prof."'");
	$enseignements_prof_num = mysql_num_rows($req);

	if ($rep === 1) {
		// on renvoie alors le nombre d'enseignements
		return $enseignements_prof_num;
	} else {
		$result = array();
		while($enseignements_prof = mysql_fetch_array($req)) {
			// on renvoie alors la liste des enseignements
			$result[] = $enseignements_prof;
		}
		return $result;
	}
}
function semaine_actu(){

		/**
		* On cherche à déterminer à quel type de semaine se rattache la semaine actuelle
		* Il y a deux possibilités : soit l'établissement utilise les semaines classiques ISO soit il a défini
		* des numéros spéciaux.
 		*/
	global $sem; // permet de modifier les requêtes si nécessaire pour avoir les cours sur une semaine donnée
		//
		$rep = array();

		$semaine = date("W") + ($sem);

		$query_s = mysql_query("SELECT type_edt_semaine FROM edt_semaines WHERE id_edt_semaine = '".$semaine."' LIMIT 1");
		$rep["type"] = mysql_result($query_s, 0);

		return $rep;
	}

// Fonction qui renvoie la duree d'un enseignement à un créneau et un jour donné pour renseigner le rollspan

function renvoie_duree($id_creneaux, $jour_semaine, $enseignement){
	$req_duree = mysql_query("SELECT duree FROM edt_cours WHERE jour_semaine = '".$jour_semaine."' AND id_definie_periode = '".$id_creneaux."' AND id_groupe = '".$enseignement."'");
	$rep_duree = mysql_fetch_array($req_duree);
	$reponse_duree = $rep_duree["duree"];

	if ($reponse_duree == 1) {
		$duree = "1";
	}
	elseif ($reponse_duree == 2) {
		$duree = "2";
	}
	elseif ($reponse_duree == 3) {
		$duree = "3";
	}
	elseif ($reponse_duree == 4) {
		$duree = "4";
	}
	elseif ($reponse_duree == 5) {
		$duree = "5";
	}
	elseif ($reponse_duree == 6) {
		$duree = "6";
	}
	elseif ($reponse_duree == 7) {
		$duree = "7";
	}
	elseif ($reponse_duree == 8) {
		$duree = "8";
	}
	elseif ($reponse_duree === 0 OR $reponse_duree == 0) {
		$duree = "n";
	}
	else $duree = "2";

	return $duree;
}

// Fonction qui renvoie l'heure de début d'un cours
function renvoie_heuredeb($id_creneaux, $jour_semaine, $enseignement){
	$req_heuredeb = mysql_query("SELECT heuredeb_dec FROM edt_cours WHERE jour_semaine = '".$jour_semaine."' AND id_definie_periode = '".$id_creneaux."' AND id_groupe = '".$enseignement."'");
	$rep_heuredeb = mysql_fetch_array($req_heuredeb);
	$reponse_heuredeb = $rep_heuredeb["heuredeb_dec"];
		// Heure debut = 0 (debut créneau) ou 0.5 (milieu créneau)
	return $reponse_heuredeb;
}

// Fonction qui renvoie la liste des professeurs, des classe ou des salles

function renvoie_liste($type) {

	$rep_liste = array();
	if ($type == "prof") {
		$req_liste = mysql_query("SELECT nom, prenom, login FROM utilisateurs WHERE etat ='actif' AND statut='professeur' ORDER BY nom");

		$nb_liste = mysql_num_rows($req_liste);

	$tab_liste = array();

		for($i=0;$i<$nb_liste;$i++) {
			$rep_liste[$i]["nom"] = mysql_result($req_liste, $i, "nom");
			$rep_liste[$i]["prenom"] = mysql_result($req_liste, $i, "prenom");
			$rep_liste[$i]["login"] = mysql_result($req_liste, $i, "login");
			}
	return $rep_liste;
	}
	if ($type == "classe") {
		$req_liste = mysql_query("SELECT id, classe FROM classes ORDER BY classe");

		$nb_liste = mysql_num_rows($req_liste);

	$tab_liste = array();

		for($i=0;$i<$nb_liste;$i++) {
			$rep_liste[$i]["id"] = mysql_result($req_liste, $i, "id");
			$rep_liste[$i]["classe"] = mysql_result($req_liste, $i, "classe");
			}
	return $rep_liste;
	}
	if ($type == "salle") {
		$req_liste = mysql_query("SELECT id_salle, numero_salle, nom_salle FROM salle_cours ORDER BY numero_salle");

		$nb_liste = mysql_num_rows($req_liste);

	$tab_liste = array();

		for($i=0;$i<$nb_liste;$i++) {
			$rep_liste[$i]["id_salle"] = mysql_result($req_liste, $i, "id_salle");
			$rep_liste[$i]["numero_salle"] = mysql_result($req_liste, $i, "numero_salle");
			$rep_liste[$i]["nom_salle"] = mysql_result($req_liste, $i, "nom_salle");
			}
	return $rep_liste;
	}
	if ($type == "eleve") {
		$req_liste = mysql_query("SELECT nom, prenom, login FROM eleves GROUP BY nom");

		$nb_liste = mysql_num_rows($req_liste);

	$tab_liste = array();

		for($i=0;$i<$nb_liste;$i++) {
			$rep_liste[$i]["nom"] = mysql_result($req_liste, $i, "nom");
			$rep_liste[$i]["prenom"] = mysql_result($req_liste, $i, "prenom");
			$rep_liste[$i]["login"] = mysql_result($req_liste, $i, "login");
			}
	return $rep_liste;
	}
}

// Fonction qui retourne le nom long et court de la classe d'un élève

function aff_nom_classe($log_eleve) {
	$req_id_classe = mysql_query("SELECT id_classe FROM j_eleves_classes WHERE login = '".$log_eleve."'");
	$rep_id_classe = mysql_fetch_array($req_id_classe);

	$req_nom_classe = mysql_query("SELECT classe, nom_complet FROM classes WHERE id ='".$rep_id_classe["id_classe"]."'");

	$rep_nom_classe1 = mysql_fetch_array($req_nom_classe);
	$rep_nom_classe = $rep_nom_classe1["classe"];

	return $rep_nom_classe;
}

// Fonction qui renvoie la liste des élèves dont le nom commence par la lettre $alpha

function renvoie_liste_a($type, $alpha){
	if ($type == "eleve") {
		$req_eleves_a = mysql_query("SELECT login, nom, prenom FROM eleves WHERE nom LIKE '$alpha%' ORDER BY nom");

		$nb_liste = mysql_num_rows($req_eleves_a);

	$rep_liste = array();

		for($i=0;$i<$nb_liste;$i++) {
			$rep_liste[$i]["nom"] = mysql_result($req_eleves_a, $i, "nom");
			$rep_liste[$i]["prenom"] = mysql_result($req_eleves_a, $i, "prenom");
			$rep_liste[$i]["login"] = mysql_result($req_eleves_a, $i, "login");
			}
	return $rep_liste;
	}
}

// Fonction qui renvoie la liste des élèves d'une classe

function renvoie_liste_classe($id_classe_post){
	$req_liste_login = mysql_query("SELECT login FROM j_eleves_classes WHERE id_classe = '".$id_classe_post."' AND periode = '1'") OR die ('Erreur : renvoie_liste_classe() : '.mysql_error().'.');
	$nb_eleves = mysql_num_rows($req_liste_login);

	$rep_liste_eleves = array();

		for($i=0; $i<$nb_eleves; $i++) {

			$rep_liste_eleves[$i]["login"] = mysql_result($req_liste_login, $i, "login");
		}
	return $rep_liste_eleves;
}

// Fonction qui renvoie le nom qui correspond à l'identifiant envoyé et au type (salle, prof, classe et élève)

function renvoie_nom_long($id, $type){
	{
	if ($type == "prof") {
		$req_nom_long = mysql_query("SELECT nom, prenom, civilite FROM utilisateurs WHERE login = '".$id."'");
		$nom = @mysql_result($req_nom_long, 0, 'nom');
    	$prenom = @mysql_result($req_nom_long, 0, 'prenom');
    	$civilite = @mysql_result($req_nom_long, 0, 'civilite');

    	$nom_long = $civilite." ".$nom." ".$prenom;
	}

	elseif ($type == "eleve") {
		$req_nom_long = mysql_query("SELECT nom, prenom FROM eleves WHERE login = '".$id."'");
		$nom = @mysql_result($req_nom_long, 0, 'nom');
    	$prenom = @mysql_result($req_nom_long, 0, 'prenom');

    	$nom_long = $prenom." ".$nom;
	}
	elseif ($type == "salle") {
		$req_nom_long = mysql_query("SELECT nom_salle FROM salle_cours WHERE id_salle = '".$id."'");
		$nom = @mysql_result($req_nom_long, 0, 'nom_salle');

    	$nom_long = 'la '.$nom;
	}
	elseif ($type == "classe") {
		$req_nom_long = mysql_query("SELECT nom_complet FROM classes WHERE id = '".$id."'");
		$nom = @mysql_result($req_nom_long, 0, 'nom_complet');

		$nom_long = 'la classe de '.$nom;
	}
	}
	return $nom_long;
}


// Fonction qui affiche toutes les salles sans enseignements à un horaire donné

function aff_salles_vides($id_creneaux, $id_jour_semaine){

	// tous les id de toutes les salles
	$req_liste_salle = mysql_query("SELECT id_salle FROM salle_cours");
		$tab_toutes = array();
		while($rep_toutes = mysql_fetch_array($req_liste_salle))
		{
		$tab_toutes[]=$rep_toutes["id_salle"];
		}
	// Tous les id des salles qui ont cours à id_creneaux et id_jour_semaine
	$req_liste_salle_c = mysql_query("SELECT id_salle FROM edt_cours WHERE id_definie_periode = '".$id_creneaux."' AND jour_semaine = '".$id_jour_semaine."'");
		$tab_utilisees = array();
		while($rep_utilisees = mysql_fetch_array($req_liste_salle_c))
		{
		$tab_utilisees[]=$rep_utilisees["id_salle"];
		}

	$result = array_diff($tab_toutes, $tab_utilisees);

	return $result;
}


// checked pour le paramétrage de l'EdT
function aff_checked($aff, $valeur){
	$req_aff = mysql_query("SELECT valeur FROM edt_setting WHERE reglage = '".$aff."'");
	$rep_aff = mysql_fetch_array($req_aff);

	if ($rep_aff['valeur'] === $valeur) {
		$retour_aff = ("checked='checked' ");
	}
	else {
		$retour_aff = ("");
	}
	return $retour_aff;
}

// retourne les settings de l'EdT
function GetSettingEdt($param_edt){
	$req_param_edt = mysql_query("SELECT valeur FROM edt_setting WHERE reglage = '".$param_edt."'");
	$rep_param_edt = mysql_fetch_array($req_param_edt);

	$retourne = $rep_param_edt["valeur"];

	return $retourne;
}

// Retourne le nom de la salle
function nom_salle($id_salle_r){
	$req_nom_salle = mysql_query("SELECT nom_salle FROM salle_cours WHERE id_salle = '".$id_salle_r."'");
	$reponse = mysql_fetch_array($req_nom_salle);
		$nom_salle_r = $reponse["nom_salle"];

	return $nom_salle_r;
}
// Retourne le nom de la salle
function numero_salle($id_salle_r){
	$req_nom_salle = mysql_query("SELECT numero_salle FROM salle_cours WHERE id_salle = '".$id_salle_r."'");
	$reponse = mysql_fetch_array($req_nom_salle);
		$nom_salle_r = $reponse["numero_salle"];

	return $nom_salle_r;
}

// Fonction qui renvoie les AID en fonction du statut du demandeur (prof, classe, élève)
function renvoieAid($statut, $nom){
	$sql = "";
	if ($statut == "prof") {
		$sql = "SELECT id_aid, indice_aid FROM j_aid_utilisateurs WHERE id_utilisateur = '".$nom."' ORDER BY indice_aid";
	}elseif ($statut == "classe"){
		$sql = "";
	}elseif ($statut == "eleve"){
		$sql = "SELECT id_aid, indice_aid FROM j_aid_eleves WHERE login = '".$nom."' ORDER BY indice_aid";
	} else{
		return NULL;
	}
	// On envoie la requête
	if ($sql) {
		$requete = mysql_query($sql) OR DIE('Erreur dans la requête : '.mysql_error());
		$nbre = mysql_num_rows($requete);
		// Et on retourne le tableau
			$resultat = array();
		for($i = 0; $i < $nbre; $i++) {
			$resultat[$i]["id_aid"] = mysql_result($requete, $i, "id_aid");
			$resultat[$i]["indice_aid"] = mysql_result($requete, $i, "indice_aid");
		}
		return $resultat;

	} else{
		return NULL;
	}

}

?>
