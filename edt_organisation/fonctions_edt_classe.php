<?php
/**
 * Ensemble des fonctions qui permettent d'afficher les emplois du temps des classes
 *
 *
 * Copyright 2001, 2009 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Julien Jocal
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

// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//
//                                  PROTOS
//
// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// 
//int function RecupereEnseignementsID($creneau_courant, $jour_semaine, $id_classe, &$tab_enseignement_final)
//int function RecupCoursIdSemaine($creneau_courant, $jour_semaine, $id_classe, $id_semaine, &$tab_enseignement_final)
//int function RecupCoursNotIdSemaine($creneau_courant, $jour_semaine, $id_classe, $id_semaine, &$tab_enseignement_final)
//int function DureeMax2ColonnesClasse($jour_sem, $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j , $rang1, $rang2)
//void function ConstruireColonneClasse($elapse_time, &$tab_cours, $index_record, $duree_max, $jour_sem, $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $id_semaine_previous, &$tab_data, &$index_box)
//void function ConstruireEDTClasse($type_edt, $id_classe)  
//
// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++



// =============================================================================
//
//      $creneau_courant = 0 (M1), 1 (M2) etc
//      $jour_semaine = "lundi, "mardi", etc...
//      $id_classe = id vu dans la table 'classes'
//
//      return = nombre d'enseignements (AIDs inclus) pour la classe, jour et créneau spécifiés
//      $tab_enseignement_final = tableau renvoyé contenant tous les enseignements énumérés
//      champs renvoyés : id_groupe, id_cours, id_semaine, duree, heuredeb_dec 
//
// =============================================================================
function RecupereEnseignementsID($creneau_courant, $jour_semaine, $id_classe, &$tab_enseignement_final, $period)
{
    $tab_id_creneaux = retourne_id_creneaux();

    if (($period != NULL) AND ($period != '0')) {
        $calendrier = "(id_calendrier = '".$period."' OR id_calendrier = '0')";
    }
    else {
        $calendrier = "1=1";
    }

    $req_creneau = LessonsFromClassDaySlotPeriod($id_classe, $jour_semaine, $tab_id_creneaux[$creneau_courant], $calendrier);
    $j = 0;
    while ($rep_creneau = mysql_fetch_array($req_creneau))
    {
        $tab_enseignement_final['id_groupe'][$j] = $rep_creneau['id_groupe'];
        $tab_enseignement_final['id_aid'][$j] = 0;
        $tab_enseignement_final['duree'][$j] = $rep_creneau['duree'];
        $tab_enseignement_final['heuredeb_dec'][$j] = $rep_creneau['heuredeb_dec'];
        $tab_enseignement_final['id_semaine'][$j] = $rep_creneau['id_semaine'];
        $tab_enseignement_final['id_cours'][$j] = $rep_creneau['id_cours'];
        $tab_enseignement_final['aid'][$j] = 0;
        if (GetSettingEdt("edt_aff_couleur") == "coul") {
            $req_matiere = mysql_query("SELECT id_matiere from j_groupes_matieres WHERE id_groupe ='".$rep_creneau['id_groupe']."'");
            $rep_matiere = mysql_fetch_array($req_matiere);
            $matiere = $rep_matiere['id_matiere'];
	        $recher_couleur = "M_".$matiere;
	        $color = GetSettingEdt($recher_couleur);
            $tab_enseignement_final['couleur'][$j] = "cadreCouleur".$color;
        }
        else {
            $tab_enseignement_final['couleur'][$j] = "cadreCouleur";
        }
        $j++;
    }

    $req_creneau = AidFromClassDaySlotPeriod($id_classe, $jour_semaine, $tab_id_creneaux[$creneau_courant], $calendrier);
    while ($rep_creneau = mysql_fetch_array($req_creneau))
    {
        $tab_enseignement_final['id_aid'][$j] = $rep_creneau['id_aid'];
        $tab_enseignement_final['id_groupe'][$j] = 0;
        $tab_enseignement_final['duree'][$j] = $rep_creneau['duree'];
        $tab_enseignement_final['heuredeb_dec'][$j] = $rep_creneau['heuredeb_dec'];
        $tab_enseignement_final['id_semaine'][$j] = $rep_creneau['id_semaine'];
        $tab_enseignement_final['id_cours'][$j] = $rep_creneau['id_cours'];
        $tab_enseignement_final['aid'][$j] = 1;
        $tab_enseignement_final['couleur'][$j] = "cadreCouleur";
        $j++;
    }
    $tab_enseignement_final['id_groupe'][$j] = '';
    $tab_enseignement_final['id_aid'][$j] = '';
    $nb_enseignements = $j;
    if ($nb_enseignements < 0) 
    {
        $nb_enseignements = 0;
    }
    //$nb_enseignements = AidFromClassDaySlotPeriod2($id_classe, $jour_semaine, $tab_id_creneaux[$creneau_courant], $calendrier, $tab_enseignement_final, $j);


    return $nb_enseignements;
}

// =============================================================================
//
//      $creneau_courant = 0 (M1), 1 (M2) etc
//      $jour_semaine = "lundi, "mardi", etc...
//      $id_classe = id vu dans la table 'classes'
//      $id_semaine = "0" ou "A" ou "B"
//
//      return = nombre d'enseignements (AIDs inclus) pour la classe, jour , id_semaine et créneau spécifiés
//      $tab_enseignement_final = tableau renvoyé contenant tous les enseignements énumérés
//      champs renvoyés : id_groupe, id_cours, id_semaine, duree, heuredeb_dec 
//
// =============================================================================
function RecupCoursIdSemaine($creneau_courant, $jour_semaine, $id_classe, $id_semaine, &$tab_enseignement_final, $period)
{
    $tab_id_creneaux = retourne_id_creneaux();

    if (($period != NULL) AND ($period != '0')) {
        $calendrier = "(id_calendrier = '".$period."' OR id_calendrier = '0')";
    }
    else {
        $calendrier = "1=1";
    }
    $req_creneau = LessonsFromClassDaySlotPeriodWeek($id_classe, $jour_semaine, $tab_id_creneaux[$creneau_courant], $calendrier, $id_semaine);
    $j = 0;
    while ($rep_creneau = mysql_fetch_array($req_creneau))
    {
        $tab_enseignement_final['id_groupe'][$j] = $rep_creneau['id_groupe'];
        $tab_enseignement_final['id_aid'][$j] = 0;
        $tab_enseignement_final['duree'][$j] = $rep_creneau['duree'];
        $tab_enseignement_final['heuredeb_dec'][$j] = $rep_creneau['heuredeb_dec'];
        $tab_enseignement_final['id_semaine'][$j] = $rep_creneau['id_semaine'];
        $tab_enseignement_final['id_cours'][$j] = $rep_creneau['id_cours'];
        $tab_enseignement_final['aid'][$j] = 0;
        if (GetSettingEdt("edt_aff_couleur") == "coul") {
            $req_matiere = mysql_query("SELECT id_matiere from j_groupes_matieres WHERE id_groupe ='".$rep_creneau['id_groupe']."'");
            $rep_matiere = mysql_fetch_array($req_matiere);
            $matiere = $rep_matiere['id_matiere'];
	        $recher_couleur = "M_".$matiere;
	        $color = GetSettingEdt($recher_couleur);
            $tab_enseignement_final['couleur'][$j] = "cadreCouleur".$color;
        }
        else {
            $tab_enseignement_final['couleur'][$j] = "cadreCouleur";
        }
        $j++;
    }

    $req_creneau = AidFromClassDaySlotPeriodWeek($id_classe, $jour_semaine, $tab_id_creneaux[$creneau_courant], $calendrier, $id_semaine);
    while ($rep_creneau = mysql_fetch_array($req_creneau))
    {
        $tab_enseignement_final['id_aid'][$j] = $rep_creneau['id_aid'];
        $tab_enseignement_final['id_groupe'][$j] = 0;
        $tab_enseignement_final['duree'][$j] = $rep_creneau['duree'];
        $tab_enseignement_final['heuredeb_dec'][$j] = $rep_creneau['heuredeb_dec'];
        $tab_enseignement_final['id_semaine'][$j] = $rep_creneau['id_semaine'];
        $tab_enseignement_final['id_cours'][$j] = $rep_creneau['id_cours'];
        $tab_enseignement_final['aid'][$j] = 1;
        $tab_enseignement_final['couleur'][$j] = "cadreCouleur";
        $j++;
    }
    $tab_enseignement_final['id_groupe'][$j] = '';
    $nb_enseignements = $j;
    if ($nb_enseignements < 0) 
    {
        $nb_enseignements = 0;
    }

    return $nb_enseignements;
}

// =============================================================================
//
//      $creneau_courant = 0 (M1), 1 (M2) etc
//      $jour_semaine = "lundi, "mardi", etc...
//      $id_classe = id vu dans la table 'classes'
//      $id_semaine = "0" ou "A" ou "B"
//
//      return = nombre d'enseignements (AIDs inclus) pour la classe, jour , id_semaine et créneau spécifiés
//      $tab_enseignement_final = tableau renvoyé contenant tous les enseignements énumérés
//      champs renvoyés : id_groupe, id_cours, id_semaine, duree, heuredeb_dec 
//
// =============================================================================
function RecupCoursNotIdSemaine($creneau_courant, $jour_semaine, $id_classe, $id_semaine, &$tab_enseignement_final, $period)
{
    $tab_id_creneaux = retourne_id_creneaux();

    if (($period != NULL) AND ($period != '0')) {
        $calendrier = "(id_calendrier = '".$period."' OR id_calendrier = '0')";
    }
    else {
        $calendrier = "1=1";
    }
    $req_creneau = LessonsFromClassDaySlotPeriodNotWeek($id_classe, $jour_semaine, $tab_id_creneaux[$creneau_courant], $calendrier, $id_semaine);
    $j = 0;
    while ($rep_creneau = mysql_fetch_array($req_creneau))
    {
        $tab_enseignement_final['id_groupe'][$j] = $rep_creneau['id_groupe'];
        $tab_enseignement_final['id_aid'][$j] = 0;
        $tab_enseignement_final['duree'][$j] = $rep_creneau['duree'];
        $tab_enseignement_final['heuredeb_dec'][$j] = $rep_creneau['heuredeb_dec'];
        $tab_enseignement_final['id_semaine'][$j] = $rep_creneau['id_semaine'];
        $tab_enseignement_final['id_cours'][$j] = $rep_creneau['id_cours'];
        $tab_enseignement_final['aid'][$j] = 0;
        if (GetSettingEdt("edt_aff_couleur") == "coul") {
            $req_matiere = mysql_query("SELECT id_matiere from j_groupes_matieres WHERE id_groupe ='".$rep_creneau['id_groupe']."'");
            $rep_matiere = mysql_fetch_array($req_matiere);
            $matiere = $rep_matiere['id_matiere'];
	        $recher_couleur = "M_".$matiere;
	        $color = GetSettingEdt($recher_couleur);
            $tab_enseignement_final['couleur'][$j] = "cadreCouleur".$color;
        }
        else {
            $tab_enseignement_final['couleur'][$j] = "cadreCouleur";
        }
        $j++;
    }

    $req_creneau = AidFromClassDaySlotPeriodNotWeek($id_classe, $jour_semaine, $tab_id_creneaux[$creneau_courant], $calendrier, $id_semaine);
    while ($rep_creneau = mysql_fetch_array($req_creneau))
    {
        $tab_enseignement_final['id_aid'][$j] = $rep_creneau['id_aid'];
        $tab_enseignement_final['id_groupe'][$j] = 0;
        $tab_enseignement_final['duree'][$j] = $rep_creneau['duree'];
        $tab_enseignement_final['heuredeb_dec'][$j] = $rep_creneau['heuredeb_dec'];
        $tab_enseignement_final['id_semaine'][$j] = $rep_creneau['id_semaine'];
        $tab_enseignement_final['id_cours'][$j] = $rep_creneau['id_cours'];
        $tab_enseignement_final['aid'][$j] = 1;
        $tab_enseignement_final['couleur'][$j] = "cadreCouleur";
        $j++;
    }
    $tab_enseignement_final['id_groupe'][$j] = '';
    $nb_enseignements = $j;
    if ($nb_enseignements < 0) 
    {
        $nb_enseignements = 0;
    }

    return $nb_enseignements;
}
// =============================================================================
//
//          Si des cours se déroulent sur les mêmes créneaux (cas classique semaine A semaine B)
//          cette fonction permet de déterminer la hauteur maximum des deux colonnes à afficher
//          de façon à créer deux "div" conteneurs de width = 50% que l'on remplit par la suite
//
//          $jour_sem = lundi, mardi...
//          $id_classe = id de la classe
//          $tab_id_creneaux = tableau contenant les créneaux (M1, M2...)
//          $elapse_time = position du pointeur de remplissage (0 = M1(début), 1 = M1(milieu), 2 = M2 etc...)
//          $tab_cours = tableau contenant les enseignements 
//          $j = indice pour indiquer le créneau concerné dans $tab_id_creneau
//          $rang1, $rang2 = indique sur quels enregistrements de la requête porte le calcul           
//          
//
// =============================================================================
function DureeMax2ColonnesClasse($jour_sem, $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j , $rang1, $rang2, $period)
{

    $tab_demi_cours = array();
    $id_semaine1 = $tab_cours['id_semaine'][0];
    $duree1 = $tab_cours['duree'][0];
    if ($tab_cours['id_groupe'][1] != "") {
        $id_semaine2 = $tab_cours['id_semaine'][1];
        $duree2 = $tab_cours['duree'][1];
    }
    else {
        $id_semaine2 = "0";
        $duree2 = 2;
    }


    // ===== tests de sécurité sur $rang1 et $rang2
    if ($rang1 <=0) {
        $rang1 = 0;
    }
    if ($rang2 <=0) {
        $rang2 = 0;
    }
    $nb_rows = 0;
    while ($tab_cours['id_groupe'][$nb_rows] != "") {
        $nb_rows++;
    }
    
    if (($nb_rows == 2) AND ($id_semaine1 == $id_semaine2) AND ($id_semaine1 != '0'))
    {
        // ========= étude du cas rebelle 15'' !!
    
        if ($duree1 == 1) {
            $elapse_time1 = $elapse_time + $duree2;
            $elapse_time2 = $elapse_time+1;
            $duree1 = $duree2;
            $duree2 = 0;
        }
        else {
            $elapse_time1 = $elapse_time + $duree1;
            $elapse_time2 = $elapse_time+1;
            $duree2 = 0;
        }
        $j++;
    }
    else {
        // ************************ calcul de la durée max des deux colonnes dans tous les autres cas
        $elapse_time1 = $elapse_time;
        $elapse_time2 = $elapse_time;

        $duree1 = 0;
        $duree2 = 0;

        $id_semaine1 = $tab_cours['id_semaine'][$rang1];
    
        if (isset($tab_cours['id_groupe'][$rang2])) {
            if ($tab_cours['id_groupe'][$rang2] != "") {
                $id_semaine2 = $tab_cours['id_semaine'][$rang2];
            }
        }
        else {
    
    
            $req_id_semaine = mysql_query("SELECT type_edt_semaine FROM edt_semaines GROUP BY type_edt_semaine") or die(mysql_error());
    
            if (mysql_num_rows($req_id_semaine) <= 1) {
                $id_semaine2 = '0';
            }
            else if (mysql_num_rows($req_id_semaine) >= 2) {
                $rep_id_semaine = mysql_fetch_array($req_id_semaine);
                if ($rep_id_semaine['type_edt_semaine'] == $id_semaine1) {
                    $rep_id_semaine = mysql_fetch_array($req_id_semaine);
                }
                $id_semaine2 = $rep_id_semaine['type_edt_semaine'];
            }
            $duree2+=1;
            $elapse_time2+=1;
    
        }
    }
    
    $k = $j;
    do
    {
   
        $nb_rows = RecupCoursIdSemaine($k, $jour_sem, $id_classe, $id_semaine1, $tab_demi_cours, $period);
        $rang_demicours = 0;
        if (($nb_rows == 0) || ($tab_demi_cours['id_semaine'][$rang_demicours] != $id_semaine1))
        {
            if ($elapse_time1 < $elapse_time2)
            {
                $elapse_time1++;
                $duree1++;
                $k = (int)($elapse_time1 / 2);
            }
            else if ($elapse_time1 > $elapse_time2)
            {
                $elapse_aux = $elapse_time1;
                $elapse_time1 = $elapse_time2;
                $elapse_time2 = $elapse_aux;
                $duree_aux = $duree1;
                $duree1 = $duree2;
                $duree2 = $duree_aux;
                $id_semaine_aux = $id_semaine1;
                $id_semaine1 = $id_semaine2;
                $id_semaine2 = $id_semaine_aux;
                $k = (int)($elapse_time1 / 2);
            }
        }
        else 
        {
            if (($tab_demi_cours['heuredeb_dec'][$rang_demicours] != 0) AND ($elapse_time1%2 == 0) )
            {
                $duree1++;
                $elapse_time1++;
    
            }
            if (($tab_demi_cours['heuredeb_dec'][$rang_demicours] == 0) AND ($elapse_time1%2 != 0) AND ($nb_rows == 2))
            {                    
                $rang_demicours++;
            }
            $elapse_time1 += $tab_demi_cours['duree'][$rang_demicours];
            $duree1 += $tab_demi_cours['duree'][$rang_demicours];
            $k = (int)($elapse_time1 / 2);
    

        }
        if (!isset($tab_id_creneaux[$k])) {
            $elapse_time2 = $elapse_time1;
        }
    }
    // ======= tests de sécurité "$elapse_time1 < 25"
    while (($elapse_time1 != $elapse_time2) AND ($elapse_time1 < 25) AND ($elapse_time2 < 25));

    return ($elapse_time1 - $elapse_time);
}

// =============================================================================
//
//          Si des cours se déroulent sur les mêmes créneaux (cas classique semaine A semaine B)
//          cette fonction permet de remplir un des deux div conteneurs
//
//          $elapse_time = position du pointeur de remplissage (0 = M1(début), 1 = M1(milieu), 2 = M2 etc...)
//          $req_creneau = requête sql passée
//          $duree_max = hauteur maximum de la colonne (renvoyée par la fonction DureeMax2Colonnes)
//          $jour_sem = lundi, mardi...
//          $tab_id_creneaux = tableau contenant les créneaux (M1, M2...)
//          $j = indice pour indiquer le créneaux concerné dans $tab_id_creneau
//          $type_edt = "prof", "classe"... utilisé par la fonction AfficheCreneau de Julien Jocal
//          $login_edt = login du prof
//          $id_semaine_previous = '0', 'A' ou 'B'. uniquement utilisé pour remplir la seconde colonne et pour savoir quelle est l'id de la colonne précédente
//
// =============================================================================
function ConstruireColonneClasse($elapse_time, &$tab_cours, $index_record, $duree_max, $jour_sem, $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $id_semaine_previous, &$tab_data, &$index_box, $period)
{

    $elapse_time1 = $elapse_time;
    
    $nb_rows = 0;
    while ($tab_cours['id_groupe'][$nb_rows] != "") {
        $nb_rows++;
    }        
    // =============== 1 enregistrement existe : initialisation
    if (($tab_cours['id_groupe'][$index_record] != "") OR ($tab_cours['id_aid'][$index_record] != "")) {
        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
        $id_semaine = $tab_cours['id_semaine'][$index_record];

        $duree1 = (int)$tab_cours['duree'][$index_record];
        if (($tab_cours['heuredeb_dec'][$index_record] != 0) AND ($elapse_time1%2 == 0))  {
            RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
            $duree1++;
            $elapse_time1++;
            $k = (int)($elapse_time1 / 2);
        }
        $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem,$type_edt, $tab_cours['id_groupe'][$index_record],$tab_cours['id_aid'][$index_record], $id_semaine, $period);
        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$index_record], "cellule".$tab_cours['duree'][$index_record], $tab_cours['couleur'][$index_record], $contenu);
        $elapse_time1 += $duree1;
        $k = (int)($elapse_time1 / 2);
    }
    // =============== aucun enregistrement trouvé : initialisation
    else {
        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
        $duree1 = 2 - ($elapse_time%2);
        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$duree1, "cadre", "");
        $elapse_time1 += $duree1;
        $k = (int)($elapse_time1 / 2);
        $tab_cours['heuredeb_dec'][$index_record]=0;
        $tab_cours['duree'][$index_record]=2;
    }
    // ================= procédure de remplissage
    $end_process = false;
    if (($tab_cours['heuredeb_dec'][$index_record]==0) AND ($tab_cours['duree'][$index_record]==1)) {
        if (($nb_rows == 1) OR ($nb_rows == 2)) {
            // ========== étude des cas n°14,15
            RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
            $duree1++;
            $elapse_time1 ++;
            $k = (int)($elapse_time1 / 2);  
        }
        else if ($nb_rows == 3) {
            // ========== étude des cas n°19, 20
    
            $heuredeb_dec_1 = $tab_cours['heuredeb_dec'][0];
            $id_semaine_1 = $tab_cours['id_semaine'][0];
            $duree1_aux = $tab_cours['duree'][0];
            $id_groupe1_aux = $tab_cours['id_groupe'][0];
            $id_cours1_aux = $tab_cours['id_cours'][0];
    
            $heuredeb_dec_2 = $tab_cours['heuredeb_dec'][1];
            $id_semaine_2 = $tab_cours['id_semaine'][1];
            $duree2_aux = $tab_cours['duree'][1];
            $id_groupe2_aux = $tab_cours['id_groupe'][1];
            $id_cours2_aux = $tab_cours['id_cours'][1];
    
            $heuredeb_dec_3 = $tab_cours['heuredeb_dec'][2];
            $id_semaine_3 = $tab_cours['id_semaine'][2];
            $duree3_aux = $tab_cours['duree'][2];
            $id_groupe3_aux = $tab_cours['id_groupe'][2];
            $id_cours3_aux = $tab_cours['id_cours'][2];
    
            if (($heuredeb_dec_1 != 0) AND ($id_semaine_1 == $tab_cours['id_semaine'][$index_record])) {
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem,$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], $id_semaine_1, $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$duree1_aux, $tab_cours['couleur'][0], $contenu);
                $duree1 += (int)$duree1_aux;
                $elapse_time1 += (int)$duree1_aux;
                $k = (int)($elapse_time1 / 2);
            }
            else if (($heuredeb_dec_2 != 0) AND ($id_semaine_2 == $tab_cours['id_semaine'][$index_record])) {
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem,$type_edt, $tab_cours['id_groupe'][1],$tab_cours['id_aid'][1], $id_semaine_2, $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][1], "cellule".$duree2_aux, $tab_cours['couleur'][1], $contenu);
                $duree1 += (int)$duree2_aux;
                $elapse_time1 += (int)$duree2_aux;
                $k = (int)($elapse_time1 / 2);
            }
            if (($heuredeb_dec_3 != 0) AND ($id_semaine_3 == $tab_cours['id_semaine'][$index_record])) {
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem,$type_edt, $tab_cours['id_groupe'][2],$tab_cours['id_aid'][2], $id_semaine_3, $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][2], "cellule".$duree3_aux, $tab_cours['couleur'][2], $contenu);
                $duree1 += (int)$duree3_aux;
                $elapse_time1 += (int)$duree3_aux;
                $k = (int)($elapse_time1 / 2);
            }
            else {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1 ++;
                $k = (int)($elapse_time1 / 2);
            }
        }
        else 
        {
            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "erreur", $tab_id_creneaux[$j], "none", "none", "cellule2", "cadreRouge", "C06");
            $elapse_time1+=2;
        }
    }
    while (isset($tab_id_creneaux[$k]) AND (!$end_process) AND ($duree1<$duree_max)) {
        //if ($id_semaine_previous == '0') {
        if (isset($id_semaine)) {
            $nb_rows = RecupCoursIdSemaine($k, $jour_sem, $id_classe, $id_semaine, $tab_demi_cours, $period);
  
        }
        else {
            $nb_rows = RecupCoursNotIdSemaine($k, $jour_sem, $id_classe, $id_semaine_previous, $tab_demi_cours, $period);
        }

        $rang_demicours = 0;
        if ($nb_rows == 2) {
            // =========== récupérer les deux cours
            $heuredeb_dec_demi1 = $tab_demi_cours['heuredeb_dec'][0];
            $heuredeb_dec_demi2 = $tab_demi_cours['heuredeb_dec'][1];                
    
            // =========== afficher le bon cours
            if ($elapse_time1%2 == 0) {
                if ($heuredeb_dec_demi1 == 0) {
                    $rang_demicours = 0;
                }
                else {
                    $rang_demicours = 1;
                }
            }
            else {
                if ($heuredeb_dec_demi1 != 0) {
                    $rang_demicours = 0;
                }
                else {
                    $rang_demicours = 1;
               }
            }
            $contenu = ContenuCreneau($tab_id_creneaux[$k],$jour_sem,$type_edt, $tab_demi_cours['id_groupe'][$rang_demicours],$tab_demi_cours['id_aid'][$rang_demicours], $tab_demi_cours['id_semaine'][$rang_demicours], $period);
            RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$k], "", $tab_demi_cours['id_cours'][$rang_demicours], "cellule".$tab_demi_cours['duree'][$rang_demicours], $tab_demi_cours['couleur'][$rang_demicours], $contenu);
            $duree1 += (int)$tab_demi_cours['duree'][$rang_demicours];
            $elapse_time1 += (int)$tab_demi_cours['duree'][$rang_demicours];
            $k = (int)($elapse_time1 / 2);
    
        }
        else if ($nb_rows == 1) {
            if (($tab_demi_cours['heuredeb_dec'][0] != 0) AND ($elapse_time1%2 == 0)) {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$k], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1++;                        
            }
            $contenu = ContenuCreneau($tab_id_creneaux[$k],$jour_sem,$type_edt, $tab_demi_cours['id_groupe'][0],$tab_demi_cours['id_aid'][0], $tab_demi_cours['id_semaine'][0], $period);
            RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$k], "", $tab_demi_cours['id_cours'][0], "cellule".$tab_demi_cours['duree'][0], $tab_demi_cours['couleur'][0], $contenu);
            $duree1 += (int)$tab_demi_cours['duree'][0];
            $elapse_time1 += (int)$tab_demi_cours['duree'][0];
            if (($tab_demi_cours['heuredeb_dec'][0] == 0) AND ($tab_demi_cours['duree'][0] == 1))  {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$k], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1++;                        
            }
     
            $k = (int)($elapse_time1 / 2);
        }
        else if ($duree1 < $duree_max) {
            if ($elapse_time1%2 == 0) {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$k], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1++;
                $k = (int)($elapse_time1 / 2);
            }
            else {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$k], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1++;
                $k = (int)($elapse_time1 / 2);
            }
        }
        else {
            $end_process = true;
        }
    }
    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", "", "", "", "", "", "");

}

// =============================================================================
//
//          Si des cours se déroulent sur les mêmes créneaux (cas classique semaine A semaine B)
//          cette fonction permet de remplir un des deux div conteneurs
//
//          $elapse_time = position du pointeur de remplissage (0 = M1(début), 1 = M1(milieu), 2 = M2 etc...)
//          $req_creneau = requête sql passée
//          $duree_max = hauteur maximum de la colonne (renvoyée par la fonction DureeMax2Colonnes)
//          $jour_sem = lundi, mardi...
//          $tab_id_creneaux = tableau contenant les créneaux (M1, M2...)
//          $j = indice pour indiquer le créneaux concerné dans $tab_id_creneau
//          $type_edt = "prof", "classe"... utilisé par la fonction AfficheCreneau de Julien Jocal
//          $login_edt = login du prof
//          $id_semaine_previous = '0', 'A' ou 'B'. uniquement utilisé pour remplir la seconde colonne et pour savoir quelle est l'id de la colonne précédente
//
// =============================================================================
function ConstruireColonneClasseTiers($elapse_time, &$tab_cours, $index_record, $duree_max, $jour_sem, $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $id_semaine_previous, &$tab_data, &$index_box, &$isFirstColUsed, $period)
{

    $elapse_time1 = $elapse_time;
    
    $nb_rows = 0;
    while ($tab_cours['id_groupe'][$nb_rows] != "") {
        $nb_rows++;
    }        
    // =============== 1 enregistrement existe : initialisation
    if (($tab_cours['id_groupe'][$index_record] != "") OR ($tab_cours['id_aid'][$index_record] != "")) {
        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "tierscellule".$duree_max, "", "");
        $id_semaine = $tab_cours['id_semaine'][$index_record];

        $duree1 = (int)$tab_cours['duree'][$index_record];
        if (($tab_cours['heuredeb_dec'][$index_record] != 0) AND ($elapse_time1%2 == 0))  {
            RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
            $duree1++;
            $elapse_time1++;
            $k = (int)($elapse_time1 / 2);
        }
        $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem,$type_edt, $tab_cours['id_groupe'][$index_record],$tab_cours['id_aid'][$index_record], $id_semaine, $period);
        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$index_record], "cellule".$tab_cours['duree'][$index_record], $tab_cours['couleur'][$index_record], $contenu);
        $elapse_time1 += $duree1;
        $k = (int)($elapse_time1 / 2);
    }
    // =============== aucun enregistrement trouvé : initialisation
    else {
        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "tierscellule".$duree_max, "", "");
        $duree1 = 2 - ($elapse_time%2);
        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$duree1, "cadre", "");
        $elapse_time1 += $duree1;
        $k = (int)($elapse_time1 / 2);
        $tab_cours['heuredeb_dec'][$index_record]=0;
        $tab_cours['duree'][$index_record]=2;
    }
    // ================= procédure de remplissage
    $end_process = false;
    if (($tab_cours['heuredeb_dec'][$index_record]==0) AND ($tab_cours['duree'][$index_record]==1)) {
        if (($nb_rows == 1) OR ($nb_rows == 2)) {
            // ========== étude des cas n°14,15
            RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
            $duree1++;
            $elapse_time1 ++;
            $k = (int)($elapse_time1 / 2);  
        }
        else if ($nb_rows == 3) {
            // ========== étude des cas n°19, 20
    
            $heuredeb_dec_1 = $tab_cours['heuredeb_dec'][0];
            $id_semaine_1 = $tab_cours['id_semaine'][0];
            $duree1_aux = $tab_cours['duree'][0];
            $id_groupe1_aux = $tab_cours['id_groupe'][0];
            $id_cours1_aux = $tab_cours['id_cours'][0];
    
            $heuredeb_dec_2 = $tab_cours['heuredeb_dec'][1];
            $id_semaine_2 = $tab_cours['id_semaine'][1];
            $duree2_aux = $tab_cours['duree'][1];
            $id_groupe2_aux = $tab_cours['id_groupe'][1];
            $id_cours2_aux = $tab_cours['id_cours'][1];
    
            $heuredeb_dec_3 = $tab_cours['heuredeb_dec'][2];
            $id_semaine_3 = $tab_cours['id_semaine'][2];
            $duree3_aux = $tab_cours['duree'][2];
            $id_groupe3_aux = $tab_cours['id_groupe'][2];
            $id_cours3_aux = $tab_cours['id_cours'][2];
    
            if (($heuredeb_dec_1 != 0) AND ($id_semaine_1 == $tab_cours['id_semaine'][$index_record])) {
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem,$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], $id_semaine_1, $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$duree1_aux, $tab_cours['couleur'][0], $contenu);
                $duree1 += (int)$duree1_aux;
                $elapse_time1 += (int)$duree1_aux;
                $k = (int)($elapse_time1 / 2);
            }
            else if (($heuredeb_dec_2 != 0) AND ($id_semaine_2 == $tab_cours['id_semaine'][$index_record])) {
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem,$type_edt, $tab_cours['id_groupe'][1],$tab_cours['id_aid'][1], $id_semaine_2, $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][1], "cellule".$duree2_aux, $tab_cours['couleur'][1], $contenu);
                $duree1 += (int)$duree2_aux;
                $elapse_time1 += (int)$duree2_aux;
                $k = (int)($elapse_time1 / 2);
            }
            if (($heuredeb_dec_3 != 0) AND ($id_semaine_3 == $tab_cours['id_semaine'][$index_record])) {
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem,$type_edt, $tab_cours['id_groupe'][2],$tab_cours['id_aid'][2], $id_semaine_3, $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][2], "cellule".$duree3_aux, $tab_cours['couleur'][2], $contenu);
                $duree1 += (int)$duree3_aux;
                $elapse_time1 += (int)$duree3_aux;
                $k = (int)($elapse_time1 / 2);
            }
            else {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1 ++;
                $k = (int)($elapse_time1 / 2);
            }
        }
        else 
        {
            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "erreur", $tab_id_creneaux[$j], "none", "none", "cellule2", "cadreRouge", "C06");
            $elapse_time1+=2;
        }
    }
    while (isset($tab_id_creneaux[$k]) AND (!$end_process) AND ($duree1<$duree_max)) {
        //if ($id_semaine_previous == '0') {
        if (isset($id_semaine)) {
            $nb_rows = RecupCoursIdSemaine($k, $jour_sem, $id_classe, $id_semaine, $tab_demi_cours, $period);
   
        }
        else {
            $nb_rows = RecupCoursNotIdSemaine($k, $jour_sem, $id_classe, $id_semaine_previous, $tab_demi_cours, $period);
        }

        $rang_demicours = 0;
        if ($nb_rows == 2) {
            // =========== récupérer les deux cours
            //$heuredeb_dec_demi1 = $tab_demi_cours['heuredeb_dec'][0];
            //$heuredeb_dec_demi2 = $tab_demi_cours['heuredeb_dec'][1];                
    
            // =========== afficher le bon cours
            if ($index_record == 0)  {
                    $rang_demicours = 0;
                    $isFirstColUsed = true;
            }
            if ($index_record == 1)  {
                if ($isFirstColUsed == true)  {
                    $rang_demicours = 1;
                }
                else {
                    $rang_demicours = 0;
                }
            }
            if ($index_record == 2) {
                    $rang_demicours = 1;
            }
            $contenu = ContenuCreneau($tab_id_creneaux[$k],$jour_sem,$type_edt, $tab_demi_cours['id_groupe'][$rang_demicours],$tab_demi_cours['id_aid'][$rang_demicours],$tab_demi_cours['id_semaine'][$rang_demicours], $period);
            RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$k], "", $tab_demi_cours['id_cours'][$rang_demicours], "cellule".$tab_demi_cours['duree'][$rang_demicours], $tab_demi_cours['couleur'][$rang_demicours], $contenu);
            $duree1 += (int)$tab_demi_cours['duree'][$rang_demicours];
            $elapse_time1 += (int)$tab_demi_cours['duree'][$rang_demicours];
            $k = (int)($elapse_time1 / 2);
    
        }
        else if ($nb_rows == 1) {
            if (($tab_demi_cours['heuredeb_dec'][0] != 0) AND ($elapse_time1%2 == 0)) {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$k], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1++;                        
            }
            $contenu = ContenuCreneau($tab_id_creneaux[$k],$jour_sem,$type_edt, $tab_demi_cours['id_groupe'][0],$tab_demi_cours['id_aid'][0], $tab_demi_cours['id_semaine'][0], $period);
            RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$k], "", $tab_demi_cours['id_cours'][0], "cellule".$tab_demi_cours['duree'][0], $tab_demi_cours['couleur'][0], $contenu);
            $duree1 += (int)$tab_demi_cours['duree'][0];
            $elapse_time1 += (int)$tab_demi_cours['duree'][0];
            if (($tab_demi_cours['heuredeb_dec'][0] == 0) AND ($tab_demi_cours['duree'][0] == 1))  {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$k], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1++;                        
            }
     
            $k = (int)($elapse_time1 / 2);
        }
        else if ($duree1 < $duree_max) {
            if ($elapse_time1%2 == 0) {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$k], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1++;
                $k = (int)($elapse_time1 / 2);
            }
            else {
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$k], "", "", "cellule1", "cadre", "");
                $duree1++;
                $elapse_time1++;
                $k = (int)($elapse_time1 / 2);
            }
        }
        else {
            $end_process = true;
        }
    }
    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", "", "", "", "", "", "");
    return $elapse_time1;

}
// =============================================================================
//
//          Permet de construire l'emploi du temps de la classe choisie
//          pour simplifier l'implémentation et faciliter le debuggage, la routine étudie séparément 
//          les cas de figures possibles. J'ai dénombré 64 situations différentes en prenant en compte les situations 
//          les plus improbables (exemple : Sur un créneau donné, le prof a deux cours d'1/2 heure chacun).
//          Ceci permet de contrôler les erreurs de saisies commises par l'admin ou permet simplement de résister aux tests loufoques :)
//          j'ai numéroté et répertorié chacune de ces situations
//          Si Nombre d'enregistrements (sur le créneau observé) = 0 : 1 cas (n° 1)
//          Si Nombre d'enregistrements (sur le créneau observé) = 1 : 7 cas (n° 2 ,2' ,3 ,4 ,5 ,6 ,7)        
//          Si Nombre d'enregistrements (sur le créneau observé) = 2 : 12 cas (n° 8, 9 ,10 ,11 ,12 ,12',13, 14 ,15,15',15'' ,16)
//          Si Nombre d'enregistrements (sur le créneau observé) = 3 : 12 cas (n°17, 18, 19, 20 ,21, 22, 23, 24, 25, 26, 27, 28)
//          Si Nombre d'enregistrements (sur le créneau observé) = 4 : 21 cas (non traités au niveau de l'affichage)
//          Si Nombre d'enregistrements (sur le créneau observé) >= 5 : 11 cas (non traités au niveau de l'affichage)
//
// =============================================================================
function ConstruireEDTClasse($id_classe, $period) 
{
    $table_data = array();
    $tab_cours = array();
    $type_edt = "classe";

    $req_jours = mysql_query("SELECT jour_horaire_etablissement FROM horaires_etablissement WHERE ouvert_horaire_etablissement = 1") or die(mysql_error());
    $jour_sem_tab = array();
    while($data_sem_tab = mysql_fetch_array($req_jours)) {
	    $jour_sem_tab[] = $data_sem_tab["jour_horaire_etablissement"];
        $tab_data['entete'][] = $data_sem_tab["jour_horaire_etablissement"];
    }
    $jour=0;
    $req_id_creneaux = mysql_query("SELECT id_definie_periode FROM edt_creneaux
							    WHERE type_creneaux != 'pause'") or die(mysql_error());
    $nbre_lignes = mysql_num_rows($req_id_creneaux);
    if ($nbre_lignes == 0) {
        $nbre_lignes = 1;
    }
    if ($nbre_lignes > 10) {
        $nbre_lignes = 10;
    }
    $tab_data['nb_creneaux'] = $nbre_lignes;
    $index_box = 0;

    $erreur = false;
while (isset($jour_sem_tab[$jour])) {
    $tab_id_creneaux = retourne_id_creneaux();
    $j = 0;
    $elapse_time = 0;
    while (isset($tab_id_creneaux[$j]) AND !$erreur) {
        $nb_rows = RecupereEnseignementsID($j, $jour_sem_tab[$jour], $id_classe, $tab_cours, $period);
        // ========================================== créneau vide
        if ($nb_rows == 0) {
            $delay = 2-($elapse_time%2);
            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$delay, "cadre", "");
            $elapse_time+=$delay;
        }
        // ========================================== 1 seul cours
        else if ($nb_rows == 1) {

            // ---- Le cours a lieu en semaine A ou B
            if ($tab_cours['id_semaine'][0] != '0') {
                $duree_max = $tab_cours['duree'][0];
                $heuredeb_dec = $tab_cours['heuredeb_dec'][0];
                // ========= études des cas n°2 , 6 et 7
                if (($duree_max == 1) ) {    //|| (($duree_max == 2) AND ($heuredeb_dec == 0))
                    if (($heuredeb_dec == 0) AND ($elapse_time%2 != 0))
                    {
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                        $elapse_time++;
                    }
                    else 
                    {
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule2", "", "");
                        if (($duree_max == 1) AND ($heuredeb_dec != 0)) {
                            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                            $elapse_time++;
                        }
                        $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], $tab_cours['id_semaine'][0], $period);
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$duree_max, $tab_cours['couleur'][0], $contenu);
                        $elapse_time+=$duree_max;
                        if (($duree_max == 1) AND ($heuredeb_dec == 0)) {
                            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                            $elapse_time++;
                        }
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule2", "", "");
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule2", "cadre", "");
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
                    }
           
                }
                // ======== étude du cas n°2' 
                else {
                    $duree_max1 = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,0,1 , $period);
                    $duree_max2 = 0;    //DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,1,0 );
                    if ($duree_max1 >= $duree_max2) {
                        $duree_max = $duree_max1;
                    }
                    else {
                        $duree_max = $duree_max2;
                    }
                    ConstruireColonneClasse($elapse_time, $tab_cours, 0, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                    ConstruireColonneClasse($elapse_time, $tab_cours, 1, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][0], $tab_data,$index_box, $period);
                    $elapse_time += $duree_max;
                }
            }
            // ---- Le cours a lieu toutes les semaines
            else {  
                // ======== étude du cas n°5
                if (($tab_cours['heuredeb_dec'][0] != 0) AND ($elapse_time%2 == 0)) {
                    RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                    $elapse_time++;
                }
                // ======== étude du cas n°3
                if (($tab_cours['heuredeb_dec'][0] == 0) AND ($elapse_time%2 == 1) AND ($tab_cours['duree'][0] == 1)) { 
                    RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                    $elapse_time++;
                }
                // ======== étude du cas n°4
                else { 
                    $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], "", $period);
                    RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$tab_cours['duree'][0], $tab_cours['couleur'][0], $contenu);
                    $elapse_time+=(int)$tab_cours['duree'][0];
                }
            }
        }

        // ========================================== 2 cours
        else if ($nb_rows == 2) {
            $duree1 = $tab_cours['duree'][0];
            $heuredeb_dec1 = $tab_cours['heuredeb_dec'][0];
            $id_semaine1 = $tab_cours['id_semaine'][0];

            $duree2 = $tab_cours['duree'][1];
            $heuredeb_dec2 = $tab_cours['heuredeb_dec'][1];
            $id_semaine2 = $tab_cours['id_semaine'][1];


            // ---- cas classique des alignements des cours de langues
            // ---- les id_semaine sont identiques pour les deux cours : 0 0, A A ou B B.
            // ---- cas non traités : génération des deux colonnes à partir de la notion de groupes
            // ---- ici, on n'affiche qu'un créneau et si les cours sont de durées différentes, on 
            // ---- complète avec des créneaux vides.

            if ($id_semaine1 == $id_semaine2 ) { 
                $elapse_time1 = $elapse_time;
                if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree1++;
                }
                if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree2++;
                }
                if ($duree1 > $duree2) 
                { 
                    $duree_max = $duree1;
                }    
                else
                { 
                    $duree_max = $duree2;
                }     
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
                if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                { 
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                    $elapse_time1++;
                }  
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], "", $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$tab_cours['duree'][0], $tab_cours['couleur'][0], $contenu);
                $elapse_time1+=(int)$tab_cours['duree'][0];
                if ($elapse_time1 < ($elapse_time+$duree_max)) 
                { 
                    $time_left = $elapse_time+$duree_max-$elapse_time1;
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$time_left, "cadre", "");
                    $elapse_time1++;
                }   
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
   
                $elapse_time1 = $elapse_time;
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
                if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                { 
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                    $elapse_time1++;
                }  
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][1],$tab_cours['id_aid'][1], "", $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][1], "cellule".$tab_cours['duree'][1], $tab_cours['couleur'][1], $contenu);
                $elapse_time1+=(int)$tab_cours['duree'][1];
                if ($elapse_time1 < ($elapse_time+$duree_max)) 
                { 
                    $time_left = $elapse_time+$duree_max-$elapse_time1;
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$time_left, "cadre", "");
                    $elapse_time1++;
                }   
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
                $elapse_time += $duree_max;

            }   
            // ---- cas classique de deux cours semaine A , semaine B         
            else 
            {
                // ========= étude des cas n°8 et n°9 et n°14 et n°15 et n°16 et 10
                $duree_max1 = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,0,1 , $period);
                $duree_max2 = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,1,0 , $period);
                if ($duree_max1 >= $duree_max2) {
                    $duree_max = $duree_max1;
                }
                else {
                    $duree_max = $duree_max2;
                }
                ConstruireColonneClasse($elapse_time, $tab_cours, 0, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, 1, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][0], $tab_data,$index_box, $period);
                $elapse_time += $duree_max;
            }

        }

        // ========================================== 3 cours
        else if ($nb_rows == 3) {
            $duree1 = $tab_cours['duree'][0];
            $heuredeb_dec1 = $tab_cours['heuredeb_dec'][0];
            $id_semaine1 = $tab_cours['id_semaine'][0];

            $duree2 = $tab_cours['duree'][1];
            $heuredeb_dec2 = $tab_cours['heuredeb_dec'][1];
            $id_semaine2 = $tab_cours['id_semaine'][1];

            $duree3 = $tab_cours['duree'][2];
            $heuredeb_dec3 = $tab_cours['heuredeb_dec'][2];
            $id_semaine3 = $tab_cours['id_semaine'][2];

            $tab_cas = EtudeDeCasTroisCours($tab_cours);

            if (($tab_cas['cas_detecte'] >= 22) AND ($tab_cas['cas_detecte'] <=30)) {  

                $elapse_time1 = $elapse_time;

                // ---- TODO : Calculer proprement $duree_max

                if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree1++;
                }
                if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree2++;
                }
                if (($heuredeb_dec3 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree3++;
                }
                if ($duree1 > $duree2) 
                { 
                    $duree_max = $duree1;
                }    
                else
                { 
                    $duree_max = $duree2;
                }    
                if ($duree3 > $duree_max) 
                { 
                    $duree_max = $duree3;
                }   

                // ---- la variable suivante est une mémoire pour savoir quel enregistrement utiliser. 
                $isFirstColUsed = false;
                // ---- remplissage de la colonne 1/3
                $res = ConstruireColonneClasseTiers($elapse_time, $tab_cours, 0, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $isFirstColUsed, $period);
                //if ($res > $elapse_time + $duree_max)
                //{ 
                //    $duree_max = $res - $elapse_time;
                //} 
                // ---- remplissage de la colonne 2/3   
                $res = ConstruireColonneClasseTiers($elapse_time, $tab_cours, 1, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $isFirstColUsed, $period);
                //if ($res > $elapse_time + $duree_max)
                //{ 
                //    $duree_max = $res - $elapse_time;
                //} 
                // ---- remplissage de la colonne 3/3
                $res = ConstruireColonneClasseTiers($elapse_time, $tab_cours, 2, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $isFirstColUsed, $period);
                //if ($res > $elapse_time + $duree_max)
                //{ 
                //    $duree_max = $res - $elapse_time;
                //} 
                $elapse_time += $duree_max;
            }
            else if ($tab_cas['cas_detecte'] == 17) {
                $indice = $tab_cas['indice'];
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$indice],$tab_cours['id_aid'][$indice], "", $period);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$indice], "cellule".$tab_cours['duree'][$indice], $tab_cours['couleur'][$indice], $contenu);
                $elapse_time+=(int)$tab_cours['duree'][$indice];

                $indice2 = ($indice+1)%3;
                $indice3 = ($indice+2)%3;

                $duree1 = $tab_cours['duree'][$indice2];
                $heuredeb_dec1 = $tab_cours['heuredeb_dec'][$indice2];
                $id_semaine1 = $tab_cours['id_semaine'][$indice2];
    
                $duree2 = $tab_cours['duree'][$indice3];
                $heuredeb_dec2 = $tab_cours['heuredeb_dec'][$indice3];
                $id_semaine2 = $tab_cours['id_semaine'][$indice3];

                if ($tab_cours['id_semaine'][$indice2] == $tab_cours['id_semaine'][$indice3]) {
                    $elapse_time1 = $elapse_time;
                    if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                    { 
                        $duree1++;
                    }
                    if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                    { 
                        $duree2++;
                    }
                    if ($duree1 > $duree2) 
                    { 
                        $duree_max = $duree1;
                    }    
                    else
                    { 
                        $duree_max = $duree2;
                    }    

                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
                    if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                    { 
                        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                        $elapse_time1++;
                    }  
                    $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], "", $period);
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$tab_cours['duree'][0], $tab_cours['couleur'][0], $contenu);
                    $elapse_time1+=(int)$tab_cours['duree'][0];
                    if ($elapse_time1 < ($elapse_time+$duree_max)) 
                    { 
                        $time_left = $elapse_time+$duree_max-$elapse_time1;
                        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$time_left, "cadre", "");
                        $elapse_time1++;
                    }   
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
       
                    $elapse_time1 = $elapse_time;
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
                    if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                    { 
                        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                        $elapse_time1++;
                    }  
                    $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][1],$tab_cours['id_aid'][1], "", $period);
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][1], "cellule".$tab_cours['duree'][1], $tab_cours['couleur'][1], $contenu);
                    $elapse_time1+=(int)$tab_cours['duree'][1];
                    if ($elapse_time1 < ($elapse_time+$duree_max)) 
                    { 
                        $time_left = $elapse_time+$duree_max-$elapse_time1;
                        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$time_left, "cadre", "");
                        $elapse_time1++;
                    }   
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
                    $elapse_time += $duree_max;

                }
                else {
                    $duree_max = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,$indice2,$indice3 , $period);
                    ConstruireColonneClasse($elapse_time, $tab_cours, $indice2, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                    ConstruireColonneClasse($elapse_time, $tab_cours, $indice3, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][$indice2], $tab_data,$index_box, $period);
                    $elapse_time += $duree_max;
                }
            }
            else if ($tab_cas['cas_detecte'] == 18) {

                $indice = $tab_cas['indice'];
                $indice2 = ($indice+1)%3;
                $indice3 = ($indice+2)%3;
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule1", "", "");
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$indice2],$tab_cours['id_aid'][$indice2], "", $period);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$indice2], "cellule".$tab_cours['duree'][$indice2], $tab_cours['couleur'][$indice2], $contenu);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");

                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule1", "", "");
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$indice3],$tab_cours['id_aid'][$indice3], "", $period);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$indice3], "cellule".$tab_cours['duree'][$indice3], $tab_cours['couleur'][$indice3], $contenu);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");

                $elapse_time+=(int)$tab_cours['duree'][$indice2];
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$indice],$tab_cours['id_aid'][$indice], "", $period);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$indice], "cellule".$tab_cours['duree'][$indice], $tab_cours['couleur'][$indice], $contenu);
                $elapse_time+=(int)$tab_cours['duree'][$indice];

            }
            else if ($tab_cas['cas_detecte'] == 19){
                $indice = $tab_cas['indice'];
                $indice2 = ($indice+1)%3;
                $indice3 = ($indice+2)%3;
                if ($tab_cours['id_semaine'][$indice] == $tab_cours['id_semaine'][$indice2]) {
                    $rang = $indice3;
                }
                else {
                    $rang = $indice2;
                }
                $duree_max = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,$indice,$rang , $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, $indice, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, $rang, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][$indice], $tab_data,$index_box, $period);
                $elapse_time += $duree_max;
            }
            else if (($tab_cas['cas_detecte'] == 20) OR ($tab_cas['cas_detecte'] == 21)) {
                $indice = $tab_cas['indice'];
                $indice2 = ($indice+1)%3;
                $indice3 = ($indice+2)%3;

                $duree_max = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,$indice2,$indice3 , $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, $indice2, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, $indice3, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][$indice2], $tab_data,$index_box, $period);
                $elapse_time += $duree_max;
            }
            else {
                $erreur = true;
                echo "<div class= \"cadreInformation\">Erreur sur la génération de l'emploi du temps : ".$tab_cas['cas_detecte']."</div>";
            }
        }
        else
        {
            // ============= gloups ! 4 enseignements ou plus sur le même créneau
            //               
            $contenu = "";
			for($z=0; $z<$nb_rows; $z++) {
                $contenu .= "<p>".ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$z],$tab_cours['id_aid'][$z], "", $period)."</p>";

			}
			// Normalement, l'ajout $tab_cours['id_groupe'][XXX] est inutile.
			// On ne devrait pas avoir de collision d'id_dev pour un créneau sur un jour donné
			//$id_div = "ens_".$tab_id_creneaux[$j]."_".$jour_sem_tab[$jour]."_".$tab_cours['id_groupe'][$z];
			$id_div = "ens_".$tab_id_creneaux[$j]."_".$jour_sem_tab[$jour]."_".$tab_cours['id_groupe'][0];
			$case_tab = "<a href='#' onclick=\"afficher_div('".$id_div."','n',0,0);return false;\">
<img src=\"../templates/".NameTemplateEDT()."/images/voir.png\" title=\"voir les cours\" alt=\"voir les cours\" /> </a>".creer_div_infobulle($id_div, "Liste des enseignements", "#330033", $contenu, "#FFFFFF", 20,0,"y","n","y","n")."\n";
            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", "", "cellule2", "cadreRouge", $case_tab);
            //RemplirBox($elapse_time,$tab_data[$jour], $index_box, "erreur", $tab_id_creneaux[$j], "none", "none", "cellule2", "cadreRouge", "C08");
            $elapse_time += 2;
        }
        $j=(int)($elapse_time/2);
    }
$jour++;
$index_box = 0;
}

return $tab_data;
}


// =============================================================================
//
//          Permet de construire l'emploi du temps de la classe choisie
//          pour simplifier l'implémentation et faciliter le debuggage, la routine étudie séparément 
//          les cas de figures possibles. J'ai dénombré 64 situations différentes en prenant en compte les situations 
//          les plus improbables (exemple : Sur un créneau donné, le prof a deux cours d'1/2 heure chacun).
//          Ceci permet de contrôler les erreurs de saisies commises par l'admin ou permet simplement de résister aux tests loufoques :)
//          j'ai numéroté et répertorié chacune de ces situations
//          Si Nombre d'enregistrements (sur le créneau observé) = 0 : 1 cas (n° 1)
//          Si Nombre d'enregistrements (sur le créneau observé) = 1 : 7 cas (n° 2 ,2' ,3 ,4 ,5 ,6 ,7)        
//          Si Nombre d'enregistrements (sur le créneau observé) = 2 : 12 cas (n° 8, 9 ,10 ,11 ,12 ,12',13, 14 ,15,15',15'' ,16)
//          Si Nombre d'enregistrements (sur le créneau observé) = 3 : 12 cas (n°17, 18, 19, 20 ,21, 22, 23, 24, 25, 26, 27, 28)
//          Si Nombre d'enregistrements (sur le créneau observé) = 4 : 21 cas (non traités au niveau de l'affichage)
//          Si Nombre d'enregistrements (sur le créneau observé) >= 5 : 11 cas (non traités au niveau de l'affichage)
//
// =============================================================================
function ConstruireEDTClasseDuJour($id_classe, $period, $jour) 
{
    $table_data = array();
    $tab_cours = array();
    $type_edt = "classe";
    $entetes = ConstruireEnteteEDT();
    while (!isset($entetes['entete'][$jour])) {
        $jour--;
    }
    $jour_sem_tab[$jour] = $entetes['entete'][$jour];
    $tab_data['entete'][$jour] = $entetes['entete'][$jour];

    $req_id_creneaux = mysql_query("SELECT id_definie_periode FROM edt_creneaux
							    WHERE type_creneaux != 'pause'") or die(mysql_error());
    $nbre_lignes = mysql_num_rows($req_id_creneaux);
    if ($nbre_lignes == 0) {
        $nbre_lignes = 1;
    }
    if ($nbre_lignes > 10) {
        $nbre_lignes = 10;
    }
    $tab_data['nb_creneaux'] = $nbre_lignes;
    $index_box = 0;

    $erreur = false;
while (isset($jour_sem_tab[$jour])) {
    $tab_id_creneaux = retourne_id_creneaux();
    $j = 0;
    $elapse_time = 0;
    while (isset($tab_id_creneaux[$j]) AND !$erreur) {
        $nb_rows = RecupereEnseignementsID($j, $jour_sem_tab[$jour], $id_classe, $tab_cours, $period);
        // ========================================== créneau vide
        if ($nb_rows == 0) {
            $delay = 2-($elapse_time%2);
            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$delay, "cadre", "");
            $elapse_time+=$delay;
        }
        // ========================================== 1 seul cours
        else if ($nb_rows == 1) {

            // ---- Le cours a lieu en semaine A ou B
            if ($tab_cours['id_semaine'][0] != '0') {
                $duree_max = $tab_cours['duree'][0];
                $heuredeb_dec = $tab_cours['heuredeb_dec'][0];
                // ========= études des cas n°2 , 6 et 7
                if (($duree_max == 1) ) {    //|| (($duree_max == 2) AND ($heuredeb_dec == 0))
                    if (($heuredeb_dec == 0) AND ($elapse_time%2 != 0))
                    {
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                        $elapse_time++;
                    }
                    else 
                    {
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule2", "", "");
                        if (($duree_max == 1) AND ($heuredeb_dec != 0)) {
                            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                            $elapse_time++;
                        }
                        $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], $tab_cours['id_semaine'][0], $period);
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$duree_max, $tab_cours['couleur'][0], $contenu);
                        $elapse_time+=$duree_max;
                        if (($duree_max == 1) AND ($heuredeb_dec == 0)) {
                            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                            $elapse_time++;
                        }
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule2", "", "");
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule2", "cadre", "");
                        RemplirBox($elapse_time,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
                    }
           
                }
                // ======== étude du cas n°2' 
                else {
                    $duree_max1 = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,0,1 , $period);
                    $duree_max2 = 0;    //DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,1,0 );
                    if ($duree_max1 >= $duree_max2) {
                        $duree_max = $duree_max1;
                    }
                    else {
                        $duree_max = $duree_max2;
                    }
                    ConstruireColonneClasse($elapse_time, $tab_cours, 0, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                    ConstruireColonneClasse($elapse_time, $tab_cours, 1, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][0], $tab_data,$index_box, $period);
                    $elapse_time += $duree_max;
                }
            }
            // ---- Le cours a lieu toutes les semaines
            else {  
                // ======== étude du cas n°5
                if (($tab_cours['heuredeb_dec'][0] != 0) AND ($elapse_time%2 == 0)) {
                    RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                    $elapse_time++;
                }
                // ======== étude du cas n°3
                if (($tab_cours['heuredeb_dec'][0] == 0) AND ($elapse_time%2 == 1) AND ($tab_cours['duree'][0] == 1)) { 
                    RemplirBox($elapse_time,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                    $elapse_time++;
                }
                // ======== étude du cas n°4
                else { 
                    $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], "", $period);
                    RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$tab_cours['duree'][0], $tab_cours['couleur'][0], $contenu);
                    $elapse_time+=(int)$tab_cours['duree'][0];
                }
            }
        }

        // ========================================== 2 cours
        else if ($nb_rows == 2) {
            $duree1 = $tab_cours['duree'][0];
            $heuredeb_dec1 = $tab_cours['heuredeb_dec'][0];
            $id_semaine1 = $tab_cours['id_semaine'][0];

            $duree2 = $tab_cours['duree'][1];
            $heuredeb_dec2 = $tab_cours['heuredeb_dec'][1];
            $id_semaine2 = $tab_cours['id_semaine'][1];


            // ---- cas classique des alignements des cours de langues
            // ---- les id_semaine sont identiques pour les deux cours : 0 0, A A ou B B.
            // ---- cas non traités : génération des deux colonnes à partir de la notion de groupes
            // ---- ici, on n'affiche qu'un créneau et si les cours sont de durées différentes, on 
            // ---- complète avec des créneaux vides.

            if ($id_semaine1 == $id_semaine2 ) { 
                $elapse_time1 = $elapse_time;
                if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree1++;
                }
                if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree2++;
                }
                if ($duree1 > $duree2) 
                { 
                    $duree_max = $duree1;
                }    
                else
                { 
                    $duree_max = $duree2;
                }     
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
                if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                { 
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                    $elapse_time1++;
                }  
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], "", $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$tab_cours['duree'][0], $tab_cours['couleur'][0], $contenu);
                $elapse_time1+=(int)$tab_cours['duree'][0];
                if ($elapse_time1 < ($elapse_time+$duree_max)) 
                { 
                    $time_left = $elapse_time+$duree_max-$elapse_time1;
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$time_left, "cadre", "");
                    $elapse_time1++;
                }   
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
   
                $elapse_time1 = $elapse_time;
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
                if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                { 
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                    $elapse_time1++;
                }  
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][1],$tab_cours['id_aid'][1], "", $period);
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][1], "cellule".$tab_cours['duree'][1], $tab_cours['couleur'][1], $contenu);
                $elapse_time1+=(int)$tab_cours['duree'][1];
                if ($elapse_time1 < ($elapse_time+$duree_max)) 
                { 
                    $time_left = $elapse_time+$duree_max-$elapse_time1;
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$time_left, "cadre", "");
                    $elapse_time1++;
                }   
                RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
                $elapse_time += $duree_max;

            }   
            // ---- cas classique de deux cours semaine A , semaine B         
            else 
            {
                // ========= étude des cas n°8 et n°9 et n°14 et n°15 et n°16 et 10
                $duree_max1 = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,0,1 , $period);
                $duree_max2 = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,1,0 , $period);
                if ($duree_max1 >= $duree_max2) {
                    $duree_max = $duree_max1;
                }
                else {
                    $duree_max = $duree_max2;
                }
                ConstruireColonneClasse($elapse_time, $tab_cours, 0, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, 1, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][0], $tab_data,$index_box, $period);
                $elapse_time += $duree_max;
            }

        }

        // ========================================== 3 cours
        else if ($nb_rows == 3) {
            $duree1 = $tab_cours['duree'][0];
            $heuredeb_dec1 = $tab_cours['heuredeb_dec'][0];
            $id_semaine1 = $tab_cours['id_semaine'][0];

            $duree2 = $tab_cours['duree'][1];
            $heuredeb_dec2 = $tab_cours['heuredeb_dec'][1];
            $id_semaine2 = $tab_cours['id_semaine'][1];

            $duree3 = $tab_cours['duree'][2];
            $heuredeb_dec3 = $tab_cours['heuredeb_dec'][2];
            $id_semaine3 = $tab_cours['id_semaine'][2];

            $tab_cas = EtudeDeCasTroisCours($tab_cours);

            if (($tab_cas['cas_detecte'] >= 22) AND ($tab_cas['cas_detecte'] <=30)) {  

                $elapse_time1 = $elapse_time;

                // ---- TODO : Calculer proprement $duree_max

                if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree1++;
                }
                if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree2++;
                }
                if (($heuredeb_dec3 != 0) AND ($elapse_time1%2 == 0))
                { 
                    $duree3++;
                }
                if ($duree1 > $duree2) 
                { 
                    $duree_max = $duree1;
                }    
                else
                { 
                    $duree_max = $duree2;
                }    
                if ($duree3 > $duree_max) 
                { 
                    $duree_max = $duree3;
                }   

                // ---- la variable suivante est une mémoire pour savoir quel enregistrement utiliser. 
                $isFirstColUsed = false;
                // ---- remplissage de la colonne 1/3
                $res = ConstruireColonneClasseTiers($elapse_time, $tab_cours, 0, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $isFirstColUsed, $period);
                //if ($res > $elapse_time + $duree_max)
                //{ 
                //    $duree_max = $res - $elapse_time;
                //} 
                // ---- remplissage de la colonne 2/3   
                $res = ConstruireColonneClasseTiers($elapse_time, $tab_cours, 1, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $isFirstColUsed, $period);
                //if ($res > $elapse_time + $duree_max)
                //{ 
                //    $duree_max = $res - $elapse_time;
                //} 
                // ---- remplissage de la colonne 3/3
                $res = ConstruireColonneClasseTiers($elapse_time, $tab_cours, 2, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $isFirstColUsed, $period);
                //if ($res > $elapse_time + $duree_max)
                //{ 
                //    $duree_max = $res - $elapse_time;
                //} 
                $elapse_time += $duree_max;
            }
            else if ($tab_cas['cas_detecte'] == 17) {
                $indice = $tab_cas['indice'];
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$indice],$tab_cours['id_aid'][$indice], "", $period);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$indice], "cellule".$tab_cours['duree'][$indice], $tab_cours['couleur'][$indice], $contenu);
                $elapse_time+=(int)$tab_cours['duree'][$indice];

                $indice2 = ($indice+1)%3;
                $indice3 = ($indice+2)%3;

                $duree1 = $tab_cours['duree'][$indice2];
                $heuredeb_dec1 = $tab_cours['heuredeb_dec'][$indice2];
                $id_semaine1 = $tab_cours['id_semaine'][$indice2];
    
                $duree2 = $tab_cours['duree'][$indice3];
                $heuredeb_dec2 = $tab_cours['heuredeb_dec'][$indice3];
                $id_semaine2 = $tab_cours['id_semaine'][$indice3];

                if ($tab_cours['id_semaine'][$indice2] == $tab_cours['id_semaine'][$indice3]) {
                    $elapse_time1 = $elapse_time;
                    if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                    { 
                        $duree1++;
                    }
                    if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                    { 
                        $duree2++;
                    }
                    if ($duree1 > $duree2) 
                    { 
                        $duree_max = $duree1;
                    }    
                    else
                    { 
                        $duree_max = $duree2;
                    }    

                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
                    if (($heuredeb_dec1 != 0) AND ($elapse_time1%2 == 0))
                    { 
                        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                        $elapse_time1++;
                    }  
                    $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][0],$tab_cours['id_aid'][0], "", $period);
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][0], "cellule".$tab_cours['duree'][0], $tab_cours['couleur'][0], $contenu);
                    $elapse_time1+=(int)$tab_cours['duree'][0];
                    if ($elapse_time1 < ($elapse_time+$duree_max)) 
                    { 
                        $time_left = $elapse_time+$duree_max-$elapse_time1;
                        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$time_left, "cadre", "");
                        $elapse_time1++;
                    }   
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
       
                    $elapse_time1 = $elapse_time;
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule".$duree_max, "", "");
                    if (($heuredeb_dec2 != 0) AND ($elapse_time1%2 == 0))
                    { 
                        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule1", "cadre", "");
                        $elapse_time1++;
                    }  
                    $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][1],$tab_cours['id_aid'][1], "", $period);
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][1], "cellule".$tab_cours['duree'][1], $tab_cours['couleur'][1], $contenu);
                    $elapse_time1+=(int)$tab_cours['duree'][1];
                    if ($elapse_time1 < ($elapse_time+$duree_max)) 
                    { 
                        $time_left = $elapse_time+$duree_max-$elapse_time1;
                        RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "vide", $tab_id_creneaux[$j], "", "", "cellule".$time_left, "cadre", "");
                        $elapse_time1++;
                    }   
                    RemplirBox($elapse_time1,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");
                    $elapse_time += $duree_max;

                }
                else {
                    $duree_max = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,$indice2,$indice3 , $period);
                    ConstruireColonneClasse($elapse_time, $tab_cours, $indice2, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                    ConstruireColonneClasse($elapse_time, $tab_cours, $indice3, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][$indice2], $tab_data,$index_box, $period);
                    $elapse_time += $duree_max;
                }
            }
            else if ($tab_cas['cas_detecte'] == 18) {

                $indice = $tab_cas['indice'];
                $indice2 = ($indice+1)%3;
                $indice3 = ($indice+2)%3;
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule1", "", "");
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$indice2],$tab_cours['id_aid'][$indice2], "", $period);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$indice2], "cellule".$tab_cours['duree'][$indice2], $tab_cours['couleur'][$indice2], $contenu);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");

                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "conteneur", $tab_id_creneaux[$j], "", "", "demicellule1", "", "");
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$indice3],$tab_cours['id_aid'][$indice3], "", $period);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$indice3], "cellule".$tab_cours['duree'][$indice3], $tab_cours['couleur'][$indice3], $contenu);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "fin_conteneur", $tab_id_creneaux[$j], "", "", "", "", "");

                $elapse_time+=(int)$tab_cours['duree'][$indice2];
                $contenu = ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$indice],$tab_cours['id_aid'][$indice], "", $period);
                RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", $tab_cours['id_cours'][$indice], "cellule".$tab_cours['duree'][$indice], $tab_cours['couleur'][$indice], $contenu);
                $elapse_time+=(int)$tab_cours['duree'][$indice];

            }
            else if ($tab_cas['cas_detecte'] == 19){
                $indice = $tab_cas['indice'];
                $indice2 = ($indice+1)%3;
                $indice3 = ($indice+2)%3;
                if ($tab_cours['id_semaine'][$indice] == $tab_cours['id_semaine'][$indice2]) {
                    $rang = $indice3;
                }
                else {
                    $rang = $indice2;
                }
                $duree_max = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,$indice,$rang , $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, $indice, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, $rang, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][$indice], $tab_data,$index_box, $period);
                $elapse_time += $duree_max;
            }
            else if (($tab_cas['cas_detecte'] == 20) OR ($tab_cas['cas_detecte'] == 21)) {
                $indice = $tab_cas['indice'];
                $indice2 = ($indice+1)%3;
                $indice3 = ($indice+2)%3;

                $duree_max = DureeMax2ColonnesClasse($jour_sem_tab[$jour], $id_classe, $tab_id_creneaux, $elapse_time,$tab_cours, $j,$indice2,$indice3 , $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, $indice2, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, '0', $tab_data,$index_box, $period);
                ConstruireColonneClasse($elapse_time, $tab_cours, $indice3, $duree_max, $jour_sem_tab[$jour], $jour, $tab_id_creneaux, $j, $type_edt, $id_classe, $tab_cours['id_semaine'][$indice2], $tab_data,$index_box, $period);
                $elapse_time += $duree_max;
            }
            else {
                $erreur = true;
                echo "<div class= \"cadreInformation\">Erreur sur la génération de l'emploi du temps : ".$tab_cas['cas_detecte']."</div>";
            }
        }
        else
        {
            // ============= gloups ! 4 enseignements ou plus sur le même créneau
            //               
            $contenu = "";
			for($z=0; $z<$nb_rows; $z++) {
                $contenu .= "<p>".ContenuCreneau($tab_id_creneaux[$j],$jour_sem_tab[$jour],$type_edt, $tab_cours['id_groupe'][$z],$tab_cours['id_aid'][$z], "", $period)."</p>";

			}
			// Normalement, l'ajout $tab_cours['id_groupe'][XXX] est inutile.
			// On ne devrait pas avoir de collision d'id_dev pour un créneau sur un jour donné
			//$id_div = "ens_".$tab_id_creneaux[$j]."_".$jour_sem_tab[$jour]."_".$tab_cours['id_groupe'][$z];
			$id_div = "ens_".$tab_id_creneaux[$j]."_".$jour_sem_tab[$jour]."_".$tab_cours['id_groupe'][0];
			$case_tab = "<a href='#' onclick=\"afficher_div('".$id_div."','n',0,0);return false;\">
<img src=\"../templates/".NameTemplateEDT()."/images/voir.png\" title=\"voir les cours\" alt=\"voir les cours\" /> </a>".creer_div_infobulle($id_div, "Liste des enseignements", "#330033", $contenu, "#FFFFFF", 20,0,"y","n","y","n")."\n";
            RemplirBox($elapse_time,$tab_data[$jour], $index_box, "cours", $tab_id_creneaux[$j], "", "", "cellule2", "cadreRouge", $case_tab);
            //RemplirBox($elapse_time,$tab_data[$jour], $index_box, "erreur", $tab_id_creneaux[$j], "none", "none", "cellule2", "cadreRouge", "C08");
            $elapse_time += 2;
        }
        $j=(int)($elapse_time/2);
    }
$jour++;
$index_box = 0;
}

return $tab_data;
}


?>
