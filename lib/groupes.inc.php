<?php
/*
 * $Id$
 *
 */

function get_groups_for_prof($_login,$mode=NULL) {
	//Modif Eric
    //$query = mysql_query("select id_groupe from j_groupes_professeurs WHERE login = '" . $_login . "'");
	// Par discipline puis par classe
	if(!isset($mode)){
		/*
		$requete_sql = "SELECT jgp.id_groupe, jgm.id_matiere,  jgc.id_classe
						FROM j_groupes_professeurs jgp, j_groupes_matieres jgm, j_groupes_classes jgc
						WHERE (" .
						"login = '" . $_login . "'
						AND jgp.id_groupe=jgm.id_groupe
						AND jgp.id_groupe=jgc.id_groupe) " .
						"GROUP BY jgp.id_groupe ".
						"ORDER BY jgm.id_matiere, jgc.id_classe" ;
		*/
		$requete_sql = "SELECT jgp.id_groupe, jgm.id_matiere,  jgc.id_classe
						FROM j_groupes_professeurs jgp, j_groupes_matieres jgm, j_groupes_classes jgc, classes c
						WHERE (" .
						"login = '" . $_login . "'
						AND jgp.id_groupe=jgm.id_groupe
						AND jgp.id_groupe=jgc.id_groupe
						AND jgc.id_classe=c.id) " .
						"GROUP BY jgp.id_groupe ".
						"ORDER BY jgm.id_matiere, c.classe" ;
	}
	else{
		/*
		$requete_sql = "SELECT jgp.id_groupe, jgm.id_matiere,  jgc.id_classe
						FROM j_groupes_professeurs jgp, j_groupes_matieres jgm, j_groupes_classes jgc
						WHERE (" .
						"login = '" . $_login . "'
						AND jgp.id_groupe=jgm.id_groupe
						AND jgp.id_groupe=jgc.id_groupe) " .
						"GROUP BY jgp.id_groupe ".
						"ORDER BY jgc.id_classe, jgm.id_matiere" ;
		*/
		$requete_sql = "SELECT jgp.id_groupe, jgm.id_matiere, jgc.id_classe
						FROM j_groupes_professeurs jgp, j_groupes_matieres jgm, j_groupes_classes jgc, classes c
						WHERE (" .
						"login = '" . $_login . "'
						AND jgp.id_groupe=jgm.id_groupe
						AND jgp.id_groupe=jgc.id_groupe
						AND jgc.id_classe=c.id) " .
						"GROUP BY jgp.id_groupe ".
						"ORDER BY c.classe, jgm.id_matiere" ;
	}
	$query = mysql_query($requete_sql);

    $nb = mysql_num_rows($query);

    $groups = array();
    for ($i=0;$i<$nb;$i++) {
        $_id_groupe = mysql_result($query, $i, "id_groupe");
        $groups[] = get_group($_id_groupe);
    }

	// current_group["classes"]["classes"][$c_id]["classe"]

	//echo $requete_sql;
    return $groups;
}

function get_groups_for_class($_id_classe) {

    if (!is_numeric($_id_classe)) $_id_classe = "0";

    $query = mysql_query("select g.name, g.id, g.description ".
                            "from groupes g, j_groupes_classes j ".
                            "where (" .
                            "g.id = j.id_groupe " .
                            " and j.id_classe = '" . $_id_classe . "'".
                            ") ORDER BY j.priorite, g.name");

    $nb = mysql_num_rows($query);
    $temp = array();
    for ($i=0;$i<$nb;$i++) {
        $temp[$i]["name"] = mysql_result($query, $i, "name");
        $temp[$i]["description"] = mysql_result($query, $i, "description");
        $temp[$i]["id"] = mysql_result($query, $i, "id");
        $get_classes = mysql_query("SELECT c.id, c.classe, c.nom_complet FROM classes c, j_groupes_classes j WHERE (" .
                                        "c.id = j.id_classe and j.id_groupe = '" . $temp[$i]["id"]."')");
        $nb_classes = mysql_num_rows($get_classes);
        for ($k=0;$k<$nb_classes;$k++) {
            $c_id = mysql_result($get_classes, $k, "id");
            $c_classe = mysql_result($get_classes, $k, "classe");
            $c_nom_complet = mysql_result($get_classes, $k, "nom_complet");

            $temp[$i]["classes"][] = array("id" => $c_id, "classe" => $c_classe, "nom_complet" => $c_nom_complet);
			if($k==0) {$temp[$i]["classlist_string"]="";} else {$temp[$i]["classlist_string"].=", ";}
			$temp[$i]["classlist_string"].=$c_classe;
        }
    }

    return $temp;
}

/**
 * Renvoie les informations sur le groupe demand�
 *
 * @param integer $_id_groupe identifiant du groupe
 * @param array $tab_champs r�glages permis par la fonction : all, matieres, classes, eleves, periodes, profs
 * @return array Tableaux imbriques des informations du groupe
 */
function get_group($_id_groupe,$tab_champs=array('all')) {

	$get_matieres='n';
	$get_classes='n';
	$get_eleves='n';
	$get_periodes='n';
	$get_profs='n';
	if(in_array('all',$tab_champs)) {
		$get_matieres='y';
		$get_classes='y';
		$get_eleves='y';
		$get_profs='y';
		$get_periodes='y';
	}
	else {
		if(in_array('matieres',$tab_champs)) {$get_matieres='y';}
		if(in_array('classes',$tab_champs)) {$get_classes='y';}
		if(in_array('eleves',$tab_champs)) {$get_eleves='y';$get_classes='y';$get_periodes='y';}
		if(in_array('periodes',$tab_champs)) {$get_periodes='y';$get_classes='y';}
		if(in_array('profs',$tab_champs)) {$get_profs='y';}
	}

    if (!is_numeric($_id_groupe)) {$_id_groupe = "0";}

	// Informations g�n�rales sur le groupe:
    $sql="select name, id, description ".
                            "from groupes ".
                            "where (" .
                            "id = '" . $_id_groupe . "'".
                            ")";
    //echo "$sql<br />";
    $query = mysql_query($sql);
    $temp["name"] = mysql_result($query, 0, "name");
    $temp["description"] = mysql_result($query, 0, "description");
    $temp["id"] = mysql_result($query, 0, "id");

	if($get_matieres=='y') {
		// Mati�res
		$matiere = mysql_query("SELECT m.matiere, m.nom_complet, m.categorie_id FROM matieres m, j_groupes_matieres j " .
														"WHERE (" .
														"m.matiere = j.id_matiere and " .
														"j.id_groupe = '" . $_id_groupe . "')");
		if (mysql_num_rows($matiere) > 0) {
			$temp["matiere"]["matiere"] = mysql_result($matiere, 0, "matiere");
			$temp["matiere"]["nom_complet"] = mysql_result($matiere, 0, "nom_complet");
			$temp["matiere"]["categorie_id"] = mysql_result($matiere, 0, "categorie_id");
		}
	}

	if($get_classes=='y') {
		// Classes
	
		//$get_classes = mysql_query("SELECT c.id, c.classe, c.nom_complet, j.priorite, j.coef, j.categorie_id, j.saisie_ects, j.valeur_ects FROM classes c, j_groupes_classes j WHERE (" .
		$sql="SELECT c.id, c.classe, c.nom_complet, j.priorite, j.coef, j.mode_moy, j.categorie_id, j.saisie_ects, j.valeur_ects FROM classes c, j_groupes_classes j WHERE (" .
										"c.id = j.id_classe and j.id_groupe = '" . $_id_groupe . "') ORDER BY c.classe, c.nom_complet;";
		//                                "c.id = j.id_classe and j.id_groupe = '" . $_id_groupe . "')");
		//echo "$sql<br />";
		$get_classes = mysql_query($sql);
		//                                "c.id = j.id_classe and j.id_groupe = '" . $_id_groupe . "')");
		$nb_classes = mysql_num_rows($get_classes);
		for ($k=0;$k<$nb_classes;$k++) {
			$c_id = mysql_result($get_classes, $k, "id");
			$c_classe = mysql_result($get_classes, $k, "classe");
			$c_nom_complet = mysql_result($get_classes, $k, "nom_complet");
			$c_priorite = mysql_result($get_classes, $k, "priorite");
			$c_coef = mysql_result($get_classes, $k, "coef");
			$c_mode_moy = mysql_result($get_classes, $k, "mode_moy");
			$c_saisie_ects = mysql_result($get_classes, $k, "saisie_ects") > '0' ? true : false;
			$c_valeur_ects = mysql_result($get_classes, $k, "valeur_ects");
			$c_cat_id = mysql_result($get_classes, $k, "categorie_id");
			$temp["classes"]["list"][] = $c_id;
			$temp["classes"]["classes"][$c_id] = array("id" => $c_id, "classe" => $c_classe, "nom_complet" => $c_nom_complet, "priorite" => $c_priorite, "coef" => $c_coef, "mode_moy" => $c_mode_moy, "saisie_ects" => $c_saisie_ects, "valeur_ects" => $c_valeur_ects, "categorie_id" => $c_cat_id);
		}
	
		$str = null;
		foreach ($temp["classes"]["classes"] as $classe) {
			$str .= $classe["classe"] . ", ";
		}
		$str = substr($str, 0, -2);
		$temp["classlist_string"] = $str;
	}

	if($get_profs=='y') {
		// Professeurs
		$temp["profs"]["list"] = array();
		$temp["profs"]["users"] = array();
	
		$get_profs = mysql_query("SELECT u.login, u.nom, u.prenom, u.civilite FROM utilisateurs u, j_groupes_professeurs j WHERE (" .
									"u.login = j.login and j.id_groupe = '" . $_id_groupe . "') ORDER BY u.nom, u.prenom");
	
		$nb = mysql_num_rows($get_profs);
		for ($i=0;$i<$nb;$i++){
			$p_login = mysql_result($get_profs, $i, "login");
			$p_nom = mysql_result($get_profs, $i, "nom");
			$p_prenom = mysql_result($get_profs, $i, "prenom");
			$civilite = mysql_result($get_profs, $i, "civilite");
			$temp["profs"]["list"][] = $p_login;
			$temp["profs"]["users"][$p_login] = array("login" => $p_login, "nom" => $p_nom, "prenom" => $p_prenom, "civilite" => $civilite);
		}
	}

	if($get_periodes=='y') {
		// P�riodes
		$temp["periodes"]=array();
		// Pour le nom et le nombre de periodes, on suppose qu'elles sont identiques dans toutes les classes du groupe
		$periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '". $temp["classes"]["list"][0] ."' ORDER BY num_periode");
		$nb_periode = mysql_num_rows($periode_query) + 1 ;
		$i = "1";
		while ($i < $nb_periode) {
			$temp["periodes"][$i]["nom_periode"] = mysql_result($periode_query, $i-1, "nom_periode");
			$temp["periodes"][$i]["num_periode"] = $i;
			$i++;
		}
		$temp["nb_periode"] = $nb_periode;
		// Verrouillage
	
		// Initialisation
		$i = "1";
		$all_clos = "";
		$all_open = "";
		$all_clos_part = "";
		while ($i < $nb_periode) {
			$liste_ver_per[$i] = "";
			$i++;
		}
	
		foreach ($temp["classes"]["list"] as $c_id) {
		$periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '". $temp["classes"]["classes"][$c_id]["id"] ."' ORDER BY num_periode");
		$nb_periode = mysql_num_rows($periode_query) + 1 ;
		$i = "1";
		while ($i < $nb_periode) {
			$temp["classe"]["ver_periode"][$c_id][$i] = mysql_result($periode_query, $i-1, "verouiller");
			$liste_ver_per[$i] .= $temp["classe"]["ver_periode"][$c_id][$i];
			$i++;
		}
		$all_clos .= "O";
		$all_open .= "N";
		$all_clos_part .= "P";
		}
		$i = "1";
		while ($i < $nb_periode) {
			if ($liste_ver_per[$i] == $all_clos)
				// Toutes les classes sont closes
				$temp["classe"]["ver_periode"]["all"][$i] = 0;
			else if ($liste_ver_per[$i] == $all_clos_part)
				// Toutes les classes sont partiellement closes
				$temp["classe"]["ver_periode"]["all"][$i] = 1;
			else if ($liste_ver_per[$i] == $all_open)
				// Toutes les classes sont ouvertes
				$temp["classe"]["ver_periode"]["all"][$i] = 3;
			else if (substr_count($liste_ver_per[$i], "N") > 0)
				// Au moins une classe est ouverte
				$temp["classe"]["ver_periode"]["all"][$i] = 2;
			else
				$temp["classe"]["ver_periode"]["all"][$i] = -1;
			$i++;
		}
	}

	if($get_eleves=='y') {
		// El�ves
		foreach ($temp["periodes"] as $key => $period) {
			$temp["eleves"][$key]["list"] = array();
			$temp["eleves"][$key]["users"] = array();
			$get_eleves = mysql_query("SELECT distinct j.login, e.nom, e.prenom FROM eleves e, j_eleves_groupes j WHERE (" .
										"e.login = j.login and j.id_groupe = '" . $_id_groupe . "' and j.periode = '" . $period["num_periode"] . "') " .
										"ORDER BY e.nom, e.prenom");
	
			$nb = mysql_num_rows($get_eleves);
			for ($i=0;$i<$nb;$i++){
				$e_login = mysql_result($get_eleves, $i, "login");
				$e_nom = mysql_result($get_eleves, $i, "nom");
				$e_prenom = mysql_result($get_eleves, $i, "prenom");
				$e_classe = mysql_result(mysql_query("SELECT id_classe FROM j_eleves_classes WHERE (login = '" . $e_login . "' and periode = '" . $key . "')"), 0);
				$temp["eleves"][$key]["list"][] = $e_login;
				$temp["eleves"][$key]["users"][$e_login] = array("login" => $e_login, "nom" => $e_nom, "prenom" => $e_prenom, "classe" => $e_classe);
			}
		}
	
		$get_all_eleves = mysql_query("SELECT distinct j.login, e.nom, e.prenom FROM eleves e, j_eleves_groupes j WHERE (" .
										"e.login = j.login and j.id_groupe = '" . $_id_groupe . "') " .
										"ORDER BY e.nom, e.prenom");
		$nb = mysql_num_rows($get_all_eleves);
		$temp["eleves"]["all"]["list"] = array();
		for ($i=0;$i<$nb;$i++){
			$e_login = mysql_result($get_all_eleves, $i, "login");
			$temp["eleves"]["all"]["list"][] = $e_login;
	
			foreach ($temp["periodes"] as $key => $period) {
				if (in_array($e_login, $temp["eleves"][$key]["list"])) {
					$temp["eleves"]["all"]["users"][$e_login] = $temp["eleves"][$key]["users"][$e_login];
					break 1;
				}
			}
		}
	}

    return $temp;
}

function create_group($_name, $_description, $_matiere, $_classes, $_categorie = 1) {

    $_insert = mysql_query("insert into groupes set name = '" . addslashes($_name) . "', description = '" . addslashes($_description) . "'");
    $_group_id = mysql_insert_id();

    if (!$_insert) {
        $error = mysql_error();
    }
    if (!is_numeric($_categorie)) {
        $_categorie = 1;
    }
    // On v�rifie que la cat�gorie existe
    $temptemp = null;
    $temptemp = mysql_query("select count(id) from matieres_categories WHERE id = '" . $_categorie . "'");
    if (!$temptemp) {
	$test_cat = "0";
    } else {
	 $test_cat = mysql_result($temptemp, 0);
    }
    if ($test_cat == "0") {
        // La cat�gorie n'existe pas : on met la cat�gorie par d�faut
        $_categorie = 1;
    }
    $_insert2 = mysql_query("insert into j_groupes_matieres set id_groupe = '" . $_group_id . "', id_matiere = '" . $_matiere . "'");
    //$_priorite = mysql_result(mysql_query("SELECT priority FROM matieres WHERE matiere = '" . $_matiere . "'"), 0);
    //echo "SELECT priority FROM matieres WHERE matiere = '" . $_matiere . "'";
    $_priorite = mysql_result(mysql_query("SELECT priority FROM matieres WHERE matiere = '" . $_matiere . "'"), 0);
    if (count($_classes) > 0) {
        //$test_per = get_period_num($_classes[0]);
        $test_per = get_period_number($_classes[0]);
    }
    foreach ($_classes as $_id_classe) {
        // On v�rifie que les classes ont bien le m�me nombre de p�riode
        //if (get_period_num($_id_classe) == $test_per) {
        if (get_period_number($_id_classe) == $test_per) {
            $_res = mysql_query("insert into j_groupes_classes set id_groupe = '" . $_group_id . "', id_classe = '" . $_id_classe . "', priorite = '" . $_priorite . "'");
	if (!$_res) {
		echo "<span style='color:red;'>Bug:</span> "."insert into j_groupes_classes set id_groupe = '" . $_group_id . "', id_classe = '" . $_id_classe . "', priorite = '" . $_priorite . "', categorie_id = '" . $_categorie . "'<br />";
		echo "<span style='color:red;'>".mysql_error()."</span>";
		echo "<br />";
	}
	$res = mysql_query("update j_groupes_classes set categorie_id = '".$_categorie."'WHERE (id_groupe = '" . $_group_id . "' and id_classe = '" . $_id_classe . "')");
	if (!$_res) {
		echo "<span style='color:red;'>Bug:</span> "."update j_groupes_classes set categorie_id = '".$_categorie."'WHERE (id_groupe = '" . $_group_id . "' and id_classe = '" . $_id_classe . "'<br />";
		echo "<span style='color:red;'>".mysql_error()."</span>";
		echo "<br />";
	}

        }
    }

    if (!$_insert) {
        return $error;
    } else {
        return $_group_id;
    }
}

function update_group_class_options($_id_groupe, $_id_classe, $_options) {
    if (!is_numeric($_id_groupe)) {$_id_groupe = 0;}
    if (!is_numeric($_id_classe)) {$_id_classe = 0;}
    if (!is_numeric($_options["coef"])) {$_options["coef"] = 0;}
    if (!is_numeric($_options["priorite"])) {$_options["priorite"] = 0;}

    if(!isset($_options["mode_moy"])) {$_options["mode_moy"]="-";}
    elseif (($_options["mode_moy"]!='sup10')&&($_options["mode_moy"]!='bonus')) {$_options["mode_moy"]="-";}

    if ((!isset($_options["saisie_ects"]))||(!in_array($_options["saisie_ects"],array("0","1")))) {$_options["saisie_ects"] = 0;}
    if ((!isset($_options["valeur_ects"]))||(!is_numeric($_options["valeur_ects"]))) {$_options["valeur_ects"] = 0;}
    if (!is_numeric($_options["coef"])) {$_options["coef"] = 0;}
    if (!is_numeric($_options["categorie_id"])) {$_options["categorie_id"] = 0;}
    $sql="update j_groupes_classes set priorite = '" . $_options["priorite"] . "',
                                                     coef = '" . $_options["coef"] . "',
                                                     mode_moy='".$_options["mode_moy"]."',
                                                     saisie_ects = '" . $_options['saisie_ects']."',
                                                     valeur_ects = '" . $_options['valeur_ects']."',
                                                     categorie_id = '" . $_options["categorie_id"] ."' ".
                        "WHERE (id_groupe = '" . $_id_groupe . "' and id_classe = '" . $_id_classe . "');";
    $res = mysql_query($sql);
    if (!$res) {
        return false;
    } else {
        return true;
    }

}

function update_group($_id_groupe, $_name, $_description, $_matiere, $_classes, $_professeurs, $_eleves) {
    global $msg;
    $former_groupe = get_group($_id_groupe);
    $errors = false;
    if ($_name != $former_groupe["name"] OR $_description != $former_groupe["description"]) {
        $sql="update groupes set name = '" . $_name . "', description = '" . $_description . "' WHERE id = '" . $_id_groupe . "';";
        $update = mysql_query($sql);
        if (!$update) {
			$errors = true;
			$msg.="ERREUR sur $sql<br />";
		}
    }

    if ($_matiere != $former_groupe["matiere"]["matiere"]) {
        $sql="update j_groupes_matieres set id_matiere = '" . $_matiere . "' WHERE id_groupe = '" . $_id_groupe . "';";
        $update2=mysql_query($sql);
        if (!$update2) {
			$errors = true;
			$msg.="ERREUR sur $sql<br />";
		}
    }

    // Mise � jour des classes

    $deleted_classes = array_diff($former_groupe["classes"]["list"], $_classes);
    $new_classes = array_diff($_classes, $former_groupe["classes"]["list"]);

    // Avant de modifier quoi que ce soit, il faut s'assurer que les nouvelles classes ont le m�me nombre de p�riodes
    $check_periods = get_period_number($former_groupe["classes"]["list"][0]);
    $per_error = false;
    foreach ($new_classes as $id_classe) {
        if (get_period_number($id_classe)!= $check_periods) {
            $per_error = true;
			$msg.="ERREUR: get_period_number($id_classe)=".get_period_number($id_classe)." mais \$check_periods=$check_periods<br />";
        }
    }
    if (!$per_error) {
        $mat_priority = mysql_result(mysql_query("SELECT priority FROM matieres WHERE matiere = '".$_matiere ."'"), 0);
        foreach ($new_classes as $id_classe) {
            $sql="insert into j_groupes_classes set id_groupe = '" . $_id_groupe . "', id_classe = '" . $id_classe . "', priorite = '".$mat_priority."';";
            $res = mysql_query($sql);
            if (!$res) {
				$errors = true;
				$msg.="ERREUR sur $sql<br />";
			}
        }

        foreach ($deleted_classes as $id_classe) {
            $sql="delete from j_groupes_classes where (id_groupe = '" . $_id_groupe . "' and id_classe = '" . $id_classe . "');";
            $res = mysql_query($sql);
            if (!$res) {
				$errors = true;
				$msg.="ERREUR sur $sql<br />";
			}
        }
    } else {
        $errors = true;
    }

    // Mise � jour des professeurs

    $deleted_profs = array_diff((array)$former_groupe["profs"]["list"], (array)$_professeurs);
    $new_profs = array_diff((array)$_professeurs, (array)$former_groupe["profs"]["list"]);

    foreach ($new_profs as $p_login) {
        $sql="insert into j_groupes_professeurs set id_groupe = '" . $_id_groupe . "', login = '" . $p_login . "';";
        $res=mysql_query($sql);
		if (!$res) {
			$errors = true;
			$msg.="ERREUR sur $sql<br />";
		}
    }

    foreach ($deleted_profs as $p_login) {
        $sql="delete from j_groupes_professeurs where (id_groupe = '" . $_id_groupe . "' and login = '" . $p_login . "');";
        $res = mysql_query($sql);
		if (!$res) {
			$errors = true;
			$msg.="ERREUR sur $sql<br />";
		}
    }


    // Mise � jour des �l�ves

    // Cette premi�re �tape est juste pour les situations o� l'on a envoy� un tableau vite comme argument
    // signalant que l'on ne veut pas manipuler les �l�ves
    if (count($_eleves) != 0) {
        foreach($former_groupe["periodes"] as $period) {
            $deleted_eleves = array_diff((array)$former_groupe["eleves"][$period["num_periode"]]["list"], (array)$_eleves[$period["num_periode"]]);
            $new_eleves = array_diff((array)$_eleves[$period["num_periode"]], (array)$former_groupe["eleves"][$period["num_periode"]]["list"]);

            foreach ($new_eleves as $e_login) {
                $sql="insert into j_eleves_groupes set id_groupe = '" . $_id_groupe . "', login = '" . $e_login . "', periode = '" . $period["num_periode"] . "';";
                $res = mysql_query($sql);
				if (!$res) {
					$errors = true;
					$msg.="ERREUR sur $sql<br />";
				}
            }

            foreach ($deleted_eleves as $e_login) {
                if (test_before_eleve_removal($e_login, $_id_groupe, $period["num_periode"])) {
                    $sql="delete from j_eleves_groupes where (id_groupe = '" . $_id_groupe . "' and login = '" . $e_login . "' and periode = '" . $period["num_periode"] . "');";
                    $res = mysql_query($sql);
					if (!$res) {
						$errors = true;
						$msg.="ERREUR sur $sql<br />";
					}
                } else {
                    $msg .= "Erreur lors de la suppression de l'�l�ve ayant le login '" . $e_login . "', pour la p�riode '" . $period["num_periode"] . " (des notes ou appr�ciations existent).<br/>";
                }
            }
        }
    }

    if ($errors) {
        return false;
    } else {
        return true;
    }
}


function test_before_group_deletion($_id_groupe) {

    $test = mysql_result(mysql_query("select count(*) FROM matieres_notes WHERE id_groupe = '" . $_id_groupe . "'"), 0);
    $test2 = mysql_result(mysql_query("select count(*) FROM matieres_appreciations WHERE id_groupe = '" . $_id_groupe . "'"), 0);

    if ($test == 0 and $test2 == 0) {
        return true;
    } else {
        return false;
    }
}

function test_before_eleve_removal($_login, $_id_groupe, $_periode) {
    $test = mysql_result(mysql_query("select count(*) FROM matieres_notes WHERE (login = '" . $_login . "' AND id_groupe = '" . $_id_groupe . "' AND periode = '" . $_periode . "')"), 0);
    $test2 = mysql_result(mysql_query("select count(*) FROM matieres_appreciations WHERE (login = '" . $_login . "' AND id_groupe = '" . $_id_groupe . "' AND periode = '" . $_periode . "')"), 0);

    if ($test == 0 and $test2 == 0) {
        return true;
    } else {
        return false;
    }
}


function delete_group($_id_groupe) {
    $errors = null;
    $del1 = mysql_query("DELETE from j_groupes_matieres WHERE id_groupe = '" . $_id_groupe . "'");
    if (!$del1) $errors .= "Erreur lors de la suppression du lien groupe-matiere.<br/>";
    $del2 = mysql_query("DELETE from j_groupes_professeurs WHERE id_groupe = '" . $_id_groupe . "'");
    if (!$del2) $errors .= "Erreur lors de la suppression du lien groupe-professeurs.<br/>";
    $del3 = mysql_query("DELETE from j_eleves_groupes WHERE id_groupe = '" . $_id_groupe . "'");
    if (!$del3) $errors .= "Erreur lors de la suppression du lien groupe-eleves.<br/>";
    $del4 = mysql_query("DELETE from j_groupes_classes WHERE id_groupe = '" . $_id_groupe . "'");
    if (!$del4) $errors .= "Erreur lors de la suppression du lien groupe-classes.<br/>";
    $del5 = mysql_query("DELETE from groupes WHERE id = '" . $_id_groupe . "'");
    if (!$del5) $errors .= "Erreur lors de la suppression du groupe.<br/>";
    $del6 = mysql_query("DELETE from cn_cahier_notes, cn_conteneurs, cn_devoirs, cn_notes_conteneurs, cn_notes_devoirs WHERE (" .
            "cn_cahier_notes.id_groupe = '" . $_id_groupe . "' AND " .
            "cn_conteneurs.id_racine = cn_cahier_notes.id_cahier_notes AND " .
            "cn_notes_conteneurs.id_conteneur = cn_conteneurs.id AND " .
            "(cn_devoirs.id_racine = cn_cahier_notes.id_cahier_notes OR cn_devoirs.id_conteneur = cn_conteneurs.id) AND " .
            "cn_notes_devoirs.id_devoir = cn_devoirs.id)");
    if (!$del6) $errors .= "Erreur lors de la suppression des donn�es relatives au carnet de notes li� au groupe.<br/>";
    $text_ct = sql_query1("SELECT count(id_groupe) from ct_entry WHERE (ct_entry.id_groupe = '" . $_id_groupe . "'");
    if ($text_ct > 0) $errors .= "Attention un cahier de textes li� au groupe supprim� est maintenant \"orphelin\". Rendez-vous dans le module \"cahier de textes\" pour r�gler le probl�me.<br/>";
    /*
    $del7 = mysql_query("DELETE from ct_entry, ct_devoirs_entry, ct_documents WHERE (" .
            "ct_entry.id_groupe = '" . $_id_groupe . "' AND " .
            "ct_devoirs_entry.id_groupe = '" . $_id_groupe . "' AND " .
            "ct_documents.id_ct = ct_entry.id_ct)");
    if (!$del7) $errors .= "Erreur lors de la suppression des donn�es relatives au cahier de textes li� au groupe.<br/>";
    */

    if (!empty($errors)) {
        return $errors;
    } else {
        return true;
    }
}

function get_eleve_groupe_setting($_login, $_id_groupe, $_setting_name) {
    $value = null;
    $select = mysql_query("select value from eleves_groupes_settings WHERE (" .
                        "login = '" . $_login . "' and ".
                        "id_groupe = '" . $_id_groupe . "' and ".
                        "name = '" . $_setting_name . "'".
                        ")");

    $nb = mysql_num_rows($select);
    if ($nb == "0") {
        $value = false;
    } else {
        $value = array();
        for ($i=0;$i<$nb;$i++) {
            $value[] = mysql_result($select, $i, "value");
        }
    }

    return $value;
}

function set_eleve_groupe_setting($_login, $_id_groupe, $_setting_name, $_setting_value) {

    $test = get_eleve_groupe_setting($_login, $_id_groupe, $_setting_name);
    $queries = array();

    if ($test) {
        $queries[] = "delete from eleves_groupes_settings where (login = '" . $_login . "' and id_groupe = '" . $_id_groupe . "' and name = '" . $_setting_name . "')";
    }

    foreach($_setting_value as $value) {
        if ($value != "") $queries[] = "insert into eleves_groupes_settings set login = '" . $_login . "', id_groupe = '" . $_id_groupe . "', name = '" . $_setting_name . "', value = '" . $value ."'";
    }

    foreach($queries as $query) {
        $res = mysql_query($query);
    }
	if ($_setting_name == "coef") {
		$req = mysql_query("update groupes set recalcul_rang = 'y' where (id='".$_id_groupe."')");
	}

    return true;
}

function check_prof_groupe($_login, $_id_groupe) {
    if(empty($_login) || empty($_id_groupe)) {return false;}
    $call_prof = mysql_query("SELECT login FROM j_groupes_professeurs WHERE (id_groupe='".$_id_groupe."' and login='" . $_login . "')");
    $nb = mysql_num_rows($call_prof);

    if ($nb == 0) {
        return false;
    } else {
        return true;
    }
}
?>
