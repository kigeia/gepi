<?php
@set_time_limit(0);
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

if (!checkAccess()) {
    header("Location: ../logout.php?auto=1");
    die();
}

$liste_tables_del = array(
//absences
//edt_creneaux
//absences_eleves
//absences_gep
//absences_motifs
//aid
//aid_appreciations
//aid_config
//avis_conseil_classe
//classes
"cn_cahier_notes",
"cn_conteneurs",
"cn_devoirs",
"cn_notes_conteneurs",
"cn_notes_devoirs",
"ct_devoirs_entry",
"ct_documents",
"ct_entry",
"ct_devoirs_documents",
"ct_private_entry",
"ct_sequences",
//"ct_types_documents",
//droits
//eleves
"eleves_groupes_settings",
//etablissements
"groupes",
//j_aid_eleves
//j_aid_utilisateurs
//"j_eleves_classes",
//j_eleves_cpe
//j_eleves_etablissements
"j_eleves_groupes",
//j_eleves_professeurs
//j_eleves_regime
"j_groupes_classes",
"j_groupes_matieres",
"j_groupes_professeurs",
"j_professeurs_matieres",
"j_signalement",
//log
//matieres
//matieres_appreciations
//matieres_notes
//messages
//periodes
//responsables
//setting
//suivi_eleve_cpe
//tempo
//tempo2
//temp_gep_import
//utilisateurs
"edt_classes",
"edt_cours"
);

//**************** EN-TETE *****************
$titre_page = "Outil d'initialisation de l'ann�e : Importation des mati�res";
require_once("../lib/header.inc");
//************** FIN EN-TETE ***************
?>
<p class='bold'><a href="index.php"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour accueil initialisation</a></p>
<?php

echo "<center><h3 class='gepi'>Quatri�me phase d'initialisation<br />Importation des associations profs-mati�res-classes (enseignements)</h3></center>";


if (!isset($_POST["action"])) {
    //
    // On s�lectionne le fichier � importer
    //

    echo "<p>Vous allez effectuer la quatri�me �tape : elle consiste � importer le fichier <b>prof_disc_classes.csv</b> contenant les donn�es relatives aux enseignements.";
    echo "<p>ATTENTION ! Avec cette op�ration, vous effacez tous les groupes d'enseignement qui avaient �t� d�finis l'ann�e derni�re. Ils seront �cras�s par ceux que vous allez importer avec la proc�dure courante.</p>";
    echo "<p>Les champs suivants doivent �tre pr�sents, dans l'ordre, et <b>s�par�s par un point-virgule</b> : ";
    echo "<ul><li>Login du professeur</li>" .
            "<li>Nom court de la mati�re</li>" .
            "<li>Le ou les identifiant(s) de classe (s�par�s par un point d'exclamation ; ex : 1S1!1S2)</li>" .
            "<li>Type d'enseignement (CG pour enseignement g�n�ral suivi par toute la classe, OPT pour un enseignement optionnel)</li>" .
            "</ul>";
    echo "<p>Exemple de ligne pour un enseignement g�n�ral :<br/>" .
            "DUPONT.JEAN;MATHS;1S1;CG<br/>" .
            "Exemple de ligne pour un enseignement optionnel avec des �l�ves de plusieurs classes :<br/>" .
            "DURANT.PATRICE;ANGL2;1S1!1S2!1S3;OPT</p>";
    echo "<p>Veuillez pr�ciser le nom complet du fichier <b>prof_disc_classes.csv</b>.";
    echo "<form enctype='multipart/form-data' action='prof_disc_classes.php' method='post'>";
	echo add_token_field();
    echo "<input type='hidden' name='action' value='upload_file' />";
    echo "<p><input type=\"file\" size=\"80\" name=\"csv_file\" />";
    echo "<p><input type='submit' value='Valider' />";
    echo "</form>";

} else {
	check_token(false);
    //
    // Quelque chose a �t� post�
    //
    if ($_POST['action'] == "save_data") {
        //
        // On enregistre les donn�es dans la base.
        // Le fichier a d�j� �t� affich�, et l'utilisateur est s�r de vouloir enregistrer
        //

        $j=0;
        while ($j < count($liste_tables_del)) {
            if (mysql_result(mysql_query("SELECT count(*) FROM $liste_tables_del[$j]"),0)!=0) {
                $del = @mysql_query("DELETE FROM $liste_tables_del[$j]");
            }
            $j++;
        }


        $go = true;
        $i = 0;
        // Compteur d'erreurs
        $error = 0;
        // Compteur d'enregistrement
        $total = 0;
        // Warning mati�re, si jamais une mati�re est cr��e � la vol�e
        $warning_matiere = false;
        while ($go) {

            $reg_prof = $_POST["ligne".$i."_prof"];
            $reg_matiere = $_POST["ligne".$i."_matiere"];
            $reg_classes = $_POST["ligne".$i."_classes"];
            $reg_type = $_POST["ligne".$i."_type"];

            // On nettoie et on v�rifie :
            $reg_prof = preg_replace("/[^A-Za-z0-9\._]/","",trim(strtoupper($reg_prof)));
            if (strlen($reg_prof) > 50) $reg_prof = substr($reg_prof, 0, 50);

            $reg_matiere = preg_replace("/[^A-Za-z0-9\.\-]/","",trim(strtoupper($reg_matiere)));
            if (strlen($reg_matiere) > 50) $reg_matiere = substr($reg_matiere, 0, 50);

            $reg_classes = preg_replace("/[^A-Za-z0-9\.\-!]/","",trim($reg_classes));
            if (strlen($reg_classes) > 2000) $reg_classes = substr($reg_classes, 0, 2000); // C'est juste pour �viter une tentative d'overflow...

            // On ne garde v�ritablement que les types CG et OPT. En effet la g�n�ration par Scribe
            // est suppos�e n'int�grer que ces deux types.
            if ($reg_type != "CG" AND $reg_type != "OPT") $reg_type = false;

            if ($reg_type) {

                // Premi�re �tape : on s'assure que le prof existe. S'il n'existe pas, on laisse tomber.
                $test = mysql_result(mysql_query("SELECT count(login) FROM utilisateurs WHERE login = '" . $reg_prof . "'"),0);
                if ($test == 1) {

                    // Le prof existe. cool. Maintenant on r�cup�re la mati�re.
                    $test = mysql_query("SELECT nom_complet FROM matieres WHERE matiere = '" . $reg_matiere . "'");

                    if (mysql_num_rows($test) == 0) {
                        // La mati�re n'existe pas, on la cr��
                        $res = mysql_query("INSERT INTO matieres SET matiere = '" . $reg_matiere . "', nom_complet = '" . $reg_matiere . "',priority='0',matiere_aid='n',matiere_atelier='n'");
                        $reg_matiere_complet = $reg_matiere;
                        $warning_matiere = true;
                    } else {
                        $reg_matiere_complet = mysql_result($test, 0, "nom_complet");
                    }

                    // Maintenant on en arrive aux classes
                    // On r�cup�re un tableau :
                    $reg_classes = explode("!", $reg_classes);

                    // On d�termine le type de groupe
                    if (count($reg_classes) > 1) {
                        // On force le type "OPT" s'il y a plusieurs classes
                        $reg_type = "OPT";
                    } else {
                        if ($reg_type == "") {
                            // Si on n'a qu'une seule classe et que rien n'est sp�cifi�, on a par d�faut
                            // un cours g�n�ral
                            $reg_type = "CG";
                        }
                    }

                    // Si on arrive ici, c'est que normalement tout est bon.
                    // On va quand m�me s'assurer qu'on a des classes valides.

                    $valid_classes = array();
                    foreach ($reg_classes as $classe) {
                        $test = mysql_query("SELECT id FROM classes WHERE classe = '" . $classe . "'");
                        if (mysql_num_rows($test) == 1) $valid_classes[] = mysql_result($test, 0, "id");
                    }

                    if (count($valid_classes) > 0) {
                        // C'est bon, on a au moins une classe valide. On peut cr�er le groupe !

                        $new_group = mysql_query("INSERT INTO groupes SET name = '" . $reg_matiere . "', description = '" . $reg_matiere_complet . "'");
                        $group_id = mysql_insert_id();
                        if (!$new_group) echo mysql_error();
                        // Le groupe est cr��. On associe la mati�re.
                        $res = mysql_query("INSERT INTO j_groupes_matieres SET id_groupe = '".$group_id."', id_matiere = '" . $reg_matiere . "'");
                        if (!$res) echo mysql_error();
                        // On associe le prof
                        $res = mysql_query("INSERT INTO j_groupes_professeurs SET id_groupe = '" . $group_id . "', login = '" . $reg_prof . "'");
                        if (!$res) echo mysql_error();
                        // On associe la mati�re au prof
                        $res = mysql_query("INSERT INTO j_professeurs_matieres SET id_professeur = '" . $reg_prof . "', id_matiere = '" . $reg_matiere . "'");
                        // On associe le groupe aux classes (ou � la classe)
                        foreach ($valid_classes as $classe_id) {
                            $res = mysql_query("INSERT INTO j_groupes_classes SET id_groupe = '" . $group_id . "', id_classe = '" . $classe_id ."'");
                            if (!$res) echo mysql_error();
                        }

                        // Si le type est � "CG", on associe les �l�ves de la classe au groupe
                        if ($reg_type == "CG") {

                            // On r�cup�re le nombre de p�riodes pour la classe
                            $periods = mysql_result(mysql_query("SELECT count(num_periode) FROM periodes WHERE id_classe = '" . $valid_classes[0] . "'"), 0);
                            $get_eleves = mysql_query("SELECT DISTINCT(login) FROM j_eleves_classes WHERE id_classe = '" . $valid_classes[0] . "'");
                            $nb = mysql_num_rows($get_eleves);
                            for ($e=0;$e<$nb;$e++) {
                                $current_eleve = mysql_result($get_eleves, $e, "login");
                                for ($p=1;$p<=$periods;$p++) {
                                    $res = mysql_query("INSERT INTO j_eleves_groupes SET login = '" . $current_eleve . "', id_groupe = '" . $group_id . "', periode = '" . $p . "'");
                                    if (!$res) echo mysql_error();
                                }
                            }
                        }

                        if (!$new_group) {
                            $error++;
                        } else {
                            $total++;
                        }
                    } // -> Fin du test si on a au moins une classe valide

                } // -> Fin du test o� le prof existe
            }

            $i++;
            if (!isset($_POST['ligne'.$i.'_prof'])) $go = false;
        }

        if ($error > 0) echo "<p><font color=red>Il y a eu " . $error . " erreurs.</font></p>";
        if ($total > 0) echo "<p>" . $total . " groupes ont �t� enregistr�s.</p>";
        if ($warning_matiere) echo "<p><font color=red>Attention !</font> Des mati�res ont �t� cr��es � la vol�e lors de l'importation. Leur nom complet n'a pu �tre d�termin�. Vous devez donc vous rendre sur la page de <a href='../matieres/index.php'>gestion des mati�res</a> pour les renommer.</p>";
        echo "<p><a href='index.php'>Revenir � la page pr�c�dente</a></p>";


    } else if ($_POST['action'] == "upload_file") {
        //
        // Le fichier vient d'�tre envoy� et doit �tre trait�
        // On va donc afficher le contenu du fichier tel qu'il va �tre enregistr� dans Gepi
        // en proposant des champs de saisie pour modifier les donn�es si on le souhaite
        //

        $csv_file = isset($_FILES["csv_file"]) ? $_FILES["csv_file"] : NULL;

        // On v�rifie le nom du fichier... Ce n'est pas fondamentalement indispensable, mais
        // autant forcer l'utilisateur � �tre rigoureux
        if(strtolower($csv_file['name']) == "prof_disc_classes.csv") {

            // Le nom est ok. On ouvre le fichier
            $fp=fopen($csv_file['tmp_name'],"r");

            if(!$fp) {
                // Aie : on n'arrive pas � ouvrir le fichier... Pas bon.
                echo "<p>Impossible d'ouvrir le fichier CSV !</p>";
                echo "<p><a href='prof_disc_classes.php'>Cliquer ici </a> pour recommencer !</center></p>";
            } else {

                // Fichier ouvert ! On attaque le traitement

                // On va stocker toutes les infos dans un tableau
                // Une ligne du CSV pour une entr�e du tableau
                $data_tab = array();

                //=========================
                // On lit une ligne pour passer la ligne d'ent�te:
                $ligne = fgets($fp, 4096);
                //=========================

                    $k = 0;
                    while (!feof($fp)) {
                        $ligne = fgets($fp, 4096);
                        if(trim($ligne)!="") {

                            $tabligne=explode(";",$ligne);

                            // 0 : Login du prof
                            // 1 : nom court de la mati�re
                            // 2 : identifiant(s) de l� (des) classe(s) (Format : 1S1!1S2!1S3)
                            // 3 : type de groupe (CG || OPT)


            // On nettoie et on v�rifie :
            $tabligne[0] = preg_replace("/[^A-Za-z0-9\._]/","",trim(strtoupper($tabligne[0])));
            if (strlen($tabligne[0]) > 50) $tabligne[0] = substr($tabligne[0], 0, 50);

            $tabligne[1] = preg_replace("/[^A-Za-z0-9\.\-]/","",trim(strtoupper($tabligne[1])));
            if (strlen($tabligne[1]) > 50) $tabligne[1] = substr($tabligne[1], 0, 50);

            $tabligne[2] = preg_replace("/[^A-Za-z0-9\.\-!]/","",trim($tabligne[2]));
            if (strlen($tabligne[2]) > 2000) $tabligne[2] = substr($tabligne[2], 0, 2000);

            $tabligne[3] = preg_replace("/[^A-Za-z]/","",trim(strtoupper($tabligne[3])));

            if ($tabligne[3] != "CG" AND $tabligne[3] != "OPT") $tabligne[3] = "";



                            $data_tab[$k] = array();

                            $data_tab[$k]["prof"] = $tabligne[0];
                            $data_tab[$k]["matiere"] = $tabligne[1];
                            $data_tab[$k]["classes"] = $tabligne[2];
                            $data_tab[$k]["type"] = $tabligne[3];
                        }
                    $k++;
                    }

                fclose($fp);

                // Fin de l'analyse du fichier.
                // Maintenant on va afficher tout �a.

                echo "<form enctype='multipart/form-data' action='prof_disc_classes.php' method='post'>";
				echo add_token_field();
                echo "<input type='hidden' name='action' value='save_data' />";
                echo "<table>";
                echo "<tr><td>Login prof</td><td>Mati�re</td><td>Classe(s)</td><td>Type</td></tr>";

                for ($i=0;$i<$k-1;$i++) {

                        echo "<tr>";
                        echo "<td>";
                        echo $data_tab[$i]["prof"];
                        echo "<input type='hidden' name='ligne".$i."_prof' value='" . $data_tab[$i]["prof"] . "'>";
                        echo "</td>";
                        echo "<td>";
                        echo $data_tab[$i]["matiere"];
                        echo "<input type='hidden' name='ligne".$i."_matiere' value='" . $data_tab[$i]["matiere"] . "'>";
                        echo "</td>";
                        echo "<td>";
                        echo $data_tab[$i]["classes"];
                        echo "<input type='hidden' name='ligne".$i."_classes' value='" . $data_tab[$i]["classes"] . "'>";
                        echo "</td>";
                        echo "<td>";
                        echo $data_tab[$i]["type"];
                        echo "<input type='hidden' name='ligne".$i."_type' value='" . $data_tab[$i]["type"] . "'>";
                        echo "</td>";
                        echo "</tr>";
                }

                echo "</table>";

                echo "<input type='submit' value='Enregistrer'>";

                echo "</form>";
            }

        } else if (trim($csv_file['name'])=='') {

            echo "<p>Aucun fichier n'a �t� s�lectionn� !<br />";
            echo "<a href='prof_disc_classes.php'>Cliquer ici </a> pour recommencer !</center></p>";

        } else {
            echo "<p>Le fichier s�lectionn� n'est pas valide !<br />";
            echo "<a href='prof_disc_classes.php'>Cliquer ici </a> pour recommencer !</center></p>";
        }
    }
}
require("../lib/footer.inc.php");
?>