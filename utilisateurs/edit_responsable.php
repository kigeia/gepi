<?php
/*
 *
 * Copyright 2001, 2012 Thomas Belliard, Laurent Delineau, Eric Lebrun
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

// Initialisation des variables
$mode = isset($_POST["mode"]) ? $_POST["mode"] : (isset($_GET["mode"]) ? $_GET["mode"] : false);
$action = isset($_POST["action"]) ? $_POST["action"] : (isset($_GET["action"]) ? $_GET["action"] : false);

$msg = '';

$compteur_aff_time=0;
function aff_time() {
	global $compteur_aff_time;

	// Pour tenter de repérer à quel niveau cela traine:
	$debug=0;
	if($debug==1) {
		echo "$compteur_aff_time: ".strftime("%D %T")."<br />";
	}

	$compteur_aff_time++;
}

aff_time();

// Si on est en traitement par lot, on sélectionne tout de suite la liste des utilisateurs impliqués
$error = false;
if ($mode == "classe") {
	$nb_comptes = 0;
	if ($_POST['classe'] == "all") {
		$quels_parents = mysql_query("SELECT distinct(r.login), u.auth_mode " .
				"FROM utilisateurs u, resp_pers r, responsables2 re, classes c, j_eleves_classes jec, eleves e WHERE (" .
				"u.login = r.login AND r.pers_id = re.pers_id AND " .
				"re.ele_id = e.ele_id AND " .
				"e.login = jec.login AND " .
				"jec.id_classe = c.id)");
		if (!$quels_parents) $msg .= mysql_error();
	} elseif (is_numeric($_POST['classe'])) {
		$quels_parents = mysql_query("SELECT distinct(r.login), u.auth_mode " .
				"FROM utilisateurs u, resp_pers r, responsables2 re, classes c, j_eleves_classes jec, eleves e WHERE (" .
				"u.login = r.login AND r.pers_id = re.pers_id AND " .
				"re.ele_id = e.ele_id AND " .
				"e.login = jec.login AND " .
				"jec.id_classe = '" . $_POST['classe']."')");
		if (!$quels_parents) $msg .= mysql_error();
	} else {
		$error = true;
		$msg .= "Vous devez sélectionner au moins une classe !<br />";
	}
}

aff_time();

// Trois actions sont possibles depuis cette page : activation, désactivation et suppression.
// L'édition se fait directement sur la page de gestion des responsables
if (!$error) {
	if($action) {
		check_token();
	}

	if ($action == "rendre_inactif") {
		// Désactivation d'utilisateurs actifs
		if ($mode == "individual") {
			// Désactivation pour un utilisateur unique
			$test = mysql_result(mysql_query("SELECT count(login) FROM utilisateurs WHERE (login = '" . $_GET['parent_login']."' AND etat = 'actif')"), 0);
			if ($test == "0") {
				$msg .= "Erreur lors de la désactivation de l'utilisateur : celui-ci n'existe pas ou bien est déjà inactif.";
			} else {
				$res = mysql_query("UPDATE utilisateurs SET etat='inactif' WHERE (login = '".$_GET['parent_login']."')");
				if ($res) {
					$msg .= "L'utilisateur ".$_GET['parent_login'] . " a été désactivé.";
				} else {
					$msg .= "Erreur lors de la désactivation de l'utilisateur.";
				}
			}
		} elseif ($mode == "classe" and !$error) {
			// Pour tous les parents qu'on a déjà sélectionnés un peu plus haut, on désactive les comptes
			while ($current_parent = mysql_fetch_object($quels_parents)) {
				$test = mysql_result(mysql_query("SELECT count(login) FROM utilisateurs WHERE login = '" . $current_parent->login ."'"), 0);
				if ($test > 0) {
					// L'utilisateur existe bien dans la tables utilisateurs, on désactive
					$res = mysql_query("UPDATE utilisateurs SET etat = 'inactif' WHERE login = '" . $current_parent->login . "'");
					if (!$res) {
						$msg .= "Erreur lors de la désactivation du compte ".$current_parent->login."<br />";
					} else {
						$nb_comptes++;
					}
				}
			}
			$msg .= "$nb_comptes comptes ont été désactivés.";
		}
	} elseif ($action == "rendre_actif") {
		// Activation d'utilisateurs préalablement désactivés
		if ($mode == "individual") {
			// Activation pour un utilisateur unique
			$test = mysql_result(mysql_query("SELECT count(login) FROM utilisateurs WHERE (login = '" . $_GET['parent_login']."' AND etat = 'inactif')"), 0);
			if ($test == "0") {
				$msg .= "Erreur lors de la désactivation de l'utilisateur : celui-ci n'existe pas ou bien est déjà actif.";
			} else {
				$res = mysql_query("UPDATE utilisateurs SET etat='actif' WHERE (login = '".$_GET['parent_login']."')");
				if ($res) {
					$msg .= "L'utilisateur ".$_GET['parent_login'] . " a été activé.";
				} else {
					$msg .= "Erreur lors de l'activation de l'utilisateur.";
				}
			}
		} elseif ($mode == "classe") {
			// Pour tous les parents qu'on a déjà sélectionnés un peu plus haut, on désactive les comptes
			while ($current_parent = mysql_fetch_object($quels_parents)) {
				$test = mysql_result(mysql_query("SELECT count(login) FROM utilisateurs WHERE login = '" . $current_parent->login ."'"), 0);
				if ($test > 0) {
					// L'utilisateur existe bien dans la tables utilisateurs, on désactive
					$res = mysql_query("UPDATE utilisateurs SET etat = 'actif' WHERE login = '" . $current_parent->login . "'");
					if (!$res) {
						$msg .= "Erreur lors de l'activation du compte ".$current_parent->login."<br />";
					} else {
						$nb_comptes++;
					}
				}
			}
			$msg .= "$nb_comptes comptes ont été activés.";
		}

	} elseif ($action == "supprimer") {
		// Suppression d'un ou plusieurs utilisateurs
		if ($mode == "individual") {
			// Suppression pour un utilisateur unique
			$test = mysql_result(mysql_query("SELECT count(login) FROM utilisateurs WHERE (login = '" . $_GET['parent_login']."')"), 0);
			if ($test == "0") {
				$msg .= "Erreur lors de la suppression de l'utilisateur : celui-ci n'existe pas.";
			} else {
				$res = mysql_query("DELETE FROM utilisateurs WHERE (login = '".$_GET['parent_login']."')");
				if ($res) {
					$msg .= "L'utilisateur ".$_GET['parent_login'] . " a été supprimé.";
					$res2 = mysql_query("UPDATE resp_pers SET login='' WHERE login = '".$_GET['parent_login'] . "'");
				} else {
					$msg .= "Erreur lors de la suppression de l'utilisateur.";
				}
			}
		} elseif ($mode == "classe") {
			// Pour tous les parents qu'on a déjà sélectionnés un peu plus haut, on désactive les comptes
			while ($current_parent = mysql_fetch_object($quels_parents)) {
				$test = mysql_result(mysql_query("SELECT count(login) FROM utilisateurs WHERE login = '" . $current_parent->login ."'"), 0);
				if ($test > 0) {
					// L'utilisateur existe bien dans la tables utilisateurs, on désactive
					$res = mysql_query("DELETE FROM utilisateurs WHERE login = '" . $current_parent->login . "'");
					if (!$res) {
						$msg .= "Erreur lors de l'activation du compte ".$current_parent->login."<br />";
					} else {
						$res = mysql_query("UPDATE resp_pers SET login = '' WHERE login = '" . $current_parent->login ."'");
						$nb_comptes++;
					}
				}
			}
			$msg .= "$nb_comptes comptes ont été supprimés.";
		}
	} elseif ($action == "reinit_password") {
		if ($mode != "classe") {
			$msg .= "Erreur : Vous devez sélectionner une classe.";
		} elseif ($mode == "classe") {
			if ($_POST['classe'] == "all") {
				$msg .= "Vous allez réinitialiser les mots de passe de tous les utilisateurs ayant le statut 'responsable'.<br />Si vous êtes vraiment sûr de vouloir effectuer cette opération, cliquez sur le lien ci-dessous :";
				$msg .= "<br /><a href=\"reset_passwords.php?user_status=responsable&amp;mode=html".add_token_in_url()."\" target='_blank'>Réinitialiser les mots de passe (Impression HTML)</a> - ou (<a href=\"reset_passwords.php?user_status=responsable&amp;mode=html&amp;affiche_adresse_resp=y".add_token_in_url()."\" target='_blank'>Impression HTML avec adresse</a>)";
				$msg .= "<br /><a href=\"reset_passwords.php?user_status=responsable&amp;mode=csv".add_token_in_url()."\" target='_blank'>Réinitialiser les mots de passe (Export CSV)</a>";
			} else if (is_numeric($_POST['classe'])) {
				$msg .= "Vous allez réinitialiser les mots de passe de tous les utilisateurs ayant le statut 'responsable' pour la classe de ".get_class_from_id($_POST['classe']).".<br />Si vous êtes vraiment sûr de vouloir effectuer cette opération, cliquez sur le lien ci-dessous :";
				$msg .= "<br /><a href=\"reset_passwords.php?user_status=responsable&amp;user_classe=".$_POST['classe']."&amp;mode=html".add_token_in_url()."\" target='_blank'>Réinitialiser les mots de passe (Impression HTML)</a> - ou (<a href=\"reset_passwords.php?user_status=responsable&amp;user_classe=".$_POST['classe']."&amp;mode=html&amp;affiche_adresse_resp=y".add_token_in_url()."\" target='_blank'>Impression HTML avec adresse</a>)";
				$msg .= "<br /><a href=\"reset_passwords.php?user_status=responsable&amp;user_classe=".$_POST['classe']."&amp;mode=csv".add_token_in_url()."\" target='_blank'>Réinitialiser les mots de passe (Export CSV)</a>";
			}
		}
	}elseif ($action == "change_auth_mode") {
		$ldap_write_access=false;
		if ($gepiSettings['ldap_write_access'] == "yes") {
			$ldap_write_access = true;
			$ldap_server = new LDAPServer;
		}
		$nb_comptes = 0;
		$reg_auth_mode = (in_array($_POST['reg_auth_mode'], array("gepi", "ldap", "sso"))) ? $_POST['reg_auth_mode'] : "gepi";
		if ($mode != "classe") {
			$msg .= "Erreur : Vous devez sélectionner une classe.";
		} elseif ($mode == "classe") {
			while ($current_parent = mysql_fetch_object($quels_parents)) {
				$test = mysql_result(mysql_query("SELECT count(login) FROM utilisateurs WHERE login = '" . $current_parent->login ."'"), 0);
				if ($test > 0) {
					// L'utilisateur existe bien dans la tables utilisateurs, on modifie
					// Si on change le mode d'authentification, il faut quelques opérations particulières
					$old_auth_mode = $current_parent->auth_mode;
					if ($_POST['reg_auth_mode'] != $old_auth_mode) {
						// On modifie !
						$nb_comptes++;
						$res = mysql_query("UPDATE utilisateurs SET auth_mode = '".$reg_auth_mode."' WHERE login = '".$current_parent->login."'");

						// On regarde si des opérations spécifiques sont nécessaires
						if ($old_auth_mode == "gepi" && ($_POST['reg_auth_mode'] == "ldap" || $_POST['reg_auth_mode'] == "sso")) {
							// On passe du mode Gepi à un mode externe : il faut supprimer le mot de passe
							$oldmd5password = mysql_result(mysql_query("SELECT password FROM utilisateurs WHERE login = '".$current_parent->login."'"), 0);
							mysql_query("UPDATE utilisateurs SET password = '', salt = '' WHERE login = '".$current_parent->login."'");
							// Et si on a un accès en écriture au LDAP, il faut créer l'utilisateur !
							if ($ldap_write_access) {
								$create_ldap_user = true;
							}
						} elseif (($old_auth_mode == "sso" || $old_auth_mode == "ldap") && $_POST['reg_auth_mode'] == "gepi") {
							// Passage au mode Gepi, rien de spécial à faire, si ce n'est annoncer à l'administrateur
							// qu'il va falloir réinitialiser les mots de passe
							$pass_init_required = true;
							// Et si accès en écriture au LDAP, on supprime le compte.
							if ($ldap_write_access) {
								$delete_ldap_user = true;
							}
						}

						// On effectue les opérations LDAP
						if (isset($create_ldap_user) && $create_ldap_user) {
							if (!$ldap_server->test_user($current_parent->login)) {
								$parent = mysql_fetch_object(mysql_query("SELECT distinct(r.login), r.nom, r.prenom, r.civilite, r.mel " .
														"FROM resp_pers r WHERE (" .
														"r.login = '" . $current_parent->login."')"));
								$write_ldap_success = $ldap_server->add_user($parent->login, $parent->nom, $parent->prenom, $parent->mel, $parent->civilite, md5(rand()), "responsable");
								// On transfert le mot de passe à la main
								$ldap_server->set_manual_password($current_parent->login, "{MD5}".base64_encode(pack("H*",$oldmd5password)));
							}
						}
						if (isset($delete_ldap_user) && $delete_ldap_user) {
							if (!$ldap_server->test_user($current_parent->login)) {
								// L'utilisateur n'a pas été trouvé dans l'annuaire.
								$write_ldap_success = true;
							} else {
								$write_ldap_success = $ldap_server->delete_user($current_parent->login);
							}
						}

					}
				}
			}
			$msg .= "$nb_comptes comptes ont été modifiés.";
			if (isset($pass_init_required) && $pass_init_required) {
				$msg .= "<br/>Attention ! Des modifications appliquées nécessitent la réinitialisation de mots de passe des utilisateurs !";
			}
		}
	}
}

aff_time();

//**************** EN-TETE *****************
$titre_page = "Modifier des comptes responsables";
require_once("../lib/header.inc.php");
//**************** FIN EN-TETE *****************

//debug_var();

aff_time();

?>
<p class='bold'>
<a href="index.php"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a> |
<a href="create_responsable.php"> Ajouter de nouveaux comptes</a>
<?php

	$quels_parents = mysql_query("SELECT u.*, r.pers_id FROM utilisateurs u, resp_pers r WHERE (u.statut = 'responsable' AND r.login = u.login) ORDER BY u.nom,u.prenom LIMIT 1");
	if(mysql_num_rows($quels_parents)==0){
		echo "<p>Aucun compte responsable n'existe encore.<br />Vous pouvez ajouter des comptes responsables à l'aide du lien ci-dessus.</p>\n";
		require("../lib/footer.inc.php");
		die;
	}
	echo " | <a href='impression_bienvenue.php?mode=responsable'>Fiches bienvenue</a>";

	echo " | <a href='import_prof_csv.php?export_statut=responsable'>Export CSV</a>";

	echo " | <a href='edit_eleve.php'>Comptes élèves</a>";

	echo "</p>\n";

	aff_time();

	echo "<form action='edit_responsable.php' method='post'>\n";
	echo add_token_field();

	echo "<p style='font-weight:bold;'>Actions par lot pour les comptes responsables existants : </p>\n";
	flush();
	echo "<blockquote>\n";
	echo "<p>\n";
	echo "<select name='classe' size='1'>\n";
	echo "<option value='none'>Sélectionnez une classe</option>\n";
	echo "<option value='all'>Toutes les classes</option>\n";

	//$quelles_classes = mysql_query("SELECT id,classe FROM classes ORDER BY classe");
	$quelles_classes = mysql_query("SELECT DISTINCT c.id,c.classe FROM classes c,
																		j_eleves_classes jec,
																		eleves e,
																		responsables2 r,
																		resp_pers rp,
																		utilisateurs u
										WHERE jec.login=e.login AND
												e.ele_id=r.ele_id AND
												r.pers_id=rp.pers_id AND
												rp.login=u.login AND
												jec.id_classe=c.id
										ORDER BY classe");

	while ($current_classe = mysql_fetch_object($quelles_classes)) {
		echo "<option value='".$current_classe->id."'";
		if((isset($_POST['classe']))&&($_POST['classe']==$current_classe->id)) {echo " selected='selected'";}
		echo ">".$current_classe->classe."</option>\n";
	}
	//flush();
	echo "</select>\n";
	echo "<br />\n";
	aff_time();
	flush();


	echo "<input type='hidden' name='mode' value='classe' />\n";
	echo "<input type='radio' name='action' id='action_rendre_inactif' value='rendre_inactif' /> <label for='action_rendre_inactif' style='cursor:pointer;'>Rendre inactif</label>\n";
	echo "<input type='radio' name='action' id='action_rendre_actif' value='rendre_actif' style='margin-left: 20px;'/> <label for='action_rendre_actif' style='cursor:pointer;'>Rendre actif </label>\n";
	if ($session_gepi->auth_locale || $gepiSettings['ldap_write_access']) {
		echo "<input type='radio' name='action' id='action_reinit_password' value='reinit_password' style='margin-left: 20px;'/> <label for='action_reinit_password' style='cursor:pointer;'>Réinitialiser mots de passe</label>\n";
	}
	echo "<input type='radio' name='action' id='action_supprimer' value='supprimer' style='margin-left: 20px;' /> <label for='action_supprimer' style='cursor:pointer;'>Supprimer</label><br />\n";
	echo "<input type='radio' name='action' value='change_auth_mode' /> Modifier authentification : ";


	echo "<select id='select_auth_mode' name='reg_auth_mode' size='1'>\n";
	echo "<option value='gepi'";
	if((isset($_POST['reg_auth_mode']))&&($_POST['reg_auth_mode']=='gepi')) {echo " selected='selected'";}
	echo ">Locale (base Gepi)</option>\n";
	echo "<option value='ldap'";
	if((isset($_POST['reg_auth_mode']))&&($_POST['reg_auth_mode']=='ldap')) {echo " selected='selected'";}
	echo ">LDAP</option>\n";
	echo "<option value='sso'";
	if((isset($_POST['reg_auth_mode']))&&($_POST['reg_auth_mode']=='sso')) {echo " selected='selected'";}
	echo ">SSO (Cas, LCS, LemonLDAP)</option>\n";
	echo "</select>\n";


	echo "<br />\n";
	echo "&nbsp;<input type='submit' name='Valider' value='Valider' />\n";
	echo "</p>\n";

	echo "</blockquote>\n";
	echo "</form>\n";


	echo "<p><br /></p>\n";

	echo "<p><b>Liste des comptes responsables existants</b> :</p>\n";
	echo "<blockquote>\n";

	$afficher_tous_les_resp=isset($_POST['afficher_tous_les_resp']) ? $_POST['afficher_tous_les_resp'] : "n";
	$critere_recherche=isset($_POST['critere_recherche']) ? $_POST['critere_recherche'] : "";
	//$critere_recherche=preg_replace("/[^a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/u", "", $critere_recherche);
	$critere_recherche=nettoyer_caracteres_nom($critere_recherche, 'a', ' -','');
  	$critere_recherche_login=isset($_POST['critere_recherche_login']) ? $_POST['critere_recherche_login'] : (isset($_GET['critere_recherche_login']) ? $_GET['critere_recherche_login'] : "");
	//$critere_recherche_login=preg_replace("/[^a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/u", "", $critere_recherche_login);
	$critere_recherche_login=nettoyer_caracteres_nom($critere_recherche_login, 'a', ' -_.','');

	$critere_id_classe=isset($_POST['critere_id_classe']) ? preg_replace('/[^0-9]/', '', $_POST['critere_id_classe']) : (isset($_POST['classe']) ? preg_replace('/[^0-9]/', '', $_POST['classe']) : "");
	//====================================

	echo "<form enctype='multipart/form-data' name='form_rech' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
	echo "<table style='border:1px solid black;' summary=\"Filtrage\">\n";
	echo "<tr>\n";
	echo "<td valign='top' rowspan='7'>\n";
	echo "Filtrage:";
	echo "</td>\n";
	echo "<td>\n";
	echo "<input type='submit' name='filtrage' value='Afficher' /> les responsables ayant un login dont le <b>nom</b> contient: ";
	echo "<input type='text' name='critere_recherche' value='$critere_recherche' />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>\n";
	echo "<input type='submit' name='filtrage' value='Afficher' /> les responsables ayant un <b>login</b> qui contient: ";
	echo "<input type='text' name='critere_recherche_login' value='$critere_recherche_login' />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>\n";
	echo "<input type='submit' name='filtrage' value='Afficher' /> les responsables (<em>ayant un login</em>) d'élève(s) de la <b>classe</b> de: ";
	echo "<select name='critere_id_classe'>\n";
	echo "<option value=''>---</option>\n";
	$sql="SELECT DISTINCT id, classe FROM classes c, j_eleves_classes jec, eleves e, utilisateurs u, resp_pers rp, responsables2 r WHERE c.id=jec.id_classe AND jec.login=e.login AND e.ele_id=r.ele_id AND r.pers_id=rp.pers_id AND rp.login=u.login ORDER BY classe;";
	$res_classes=mysql_query($sql);
	if(mysql_num_rows($res_classes)>0) {
		while($lig_classe=mysql_fetch_object($res_classes)) {
			echo "<option value='$lig_classe->id'";
			if($lig_classe->id==$critere_id_classe) {echo " selected='true'";}
			echo ">$lig_classe->classe</option>\n";
		}
	}
	echo "</select>\n";
	echo "</td>\n";
	echo "</tr>\n";


	$sql="SELECT u.*,rp.pers_id FROM utilisateurs u, resp_pers rp, responsables2 r, eleves e  WHERE u.statut='responsable' AND rp.login=u.login AND rp.pers_id=r.pers_id AND r.ele_id=e.ele_id AND e.login NOT IN (SELECT login FROM j_eleves_classes) ORDER BY u.nom,u.prenom;";
	//echo "$sql<br />";
	$test_parents = mysql_query($sql);
	if(mysql_num_rows($test_parents)>0) {
		$nb_resp_anormaux_actifs=0;
		while($lig_resp_anormaux=mysql_fetch_object($test_parents)) {
			if($lig_resp_anormaux->etat=='actif') {
				$nb_resp_anormaux_actifs++;
			}
		}
		echo "<tr>\n";
		echo "<td>\n";
		echo "ou";
		echo "</td>\n";
		echo "</tr>\n";
	
		echo "<tr>\n";
		echo "<td>\n";
		echo "<input type='button' name='button_afficher_resp_eleves_sans_classe' value=\"Afficher tous les responsables d'élèves hors classe\" onClick=\"document.getElementById('afficher_resp_eleves_sans_classe').value='y'; document.form_rech.submit();\" /> (<em>".mysql_num_rows($test_parents)." dont ".$nb_resp_anormaux_actifs." actif";
		if($nb_resp_anormaux_actifs>0) {echo "s";}
		echo "</em>)\n";
		echo "<input type='hidden' name='afficher_resp_eleves_sans_classe' id='afficher_resp_eleves_sans_classe' value='n' />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td>\n";
	echo "ou";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>\n";
	echo "<input type='button' name='afficher_tous' value='Afficher tous les responsables ayant un login' onClick=\"document.getElementById('afficher_tous_les_resp').value='y'; document.form_rech.submit();\" />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<input type='hidden' name='afficher_tous_les_resp' id='afficher_tous_les_resp' value='n' />\n";
	echo "</form>\n";
	//====================================
	echo "<br />\n";

?>
<!--table border="1"-->
<table class='boireaus' border='1' summary="Liste des comptes existants">
<tr>
	<th>Identifiant</th>
	<th>Nom Prénom</th>
	<th>Responsable de</th>
	<th>Etat</th>
	<th>Mode auth.</th>
	<th>Supprimer</th>
	<th colspan="4">Réinitialiser le mot de passe</th>
</tr>
<?php
//$quels_parents = mysql_query("SELECT u.*, r.pers_id FROM utilisateurs u, resp_pers r WHERE (u.statut = 'responsable' AND r.login = u.login) ORDER BY u.nom,u.prenom");

if((!isset($_POST['afficher_resp_eleves_sans_classe']))||($_POST['afficher_resp_eleves_sans_classe']!='y')) {
	//if($critere_id_classe=='') {
	if(($critere_id_classe=='')||($afficher_tous_les_resp=='y')) {
		$sql="SELECT u.*, r.pers_id FROM utilisateurs u, resp_pers r WHERE (u.statut = 'responsable' AND r.login = u.login";
	}
	else {
		$sql="SELECT DISTINCT u.*, r.pers_id FROM classes c, j_eleves_classes jec, eleves e, utilisateurs u, resp_pers rp, responsables2 r WHERE (u.statut = 'responsable' AND rp.login=u.login";
	}

	if($afficher_tous_les_resp!='y'){
		if($critere_recherche!=""){
			$sql.=" AND u.nom like '%".$critere_recherche."%'";
		} else {
			if($critere_recherche_login!=""){
				$sql.=" AND u.login like '%".$critere_recherche_login."%'";
			}
		}
	
		if($critere_id_classe!='') {
			$sql.=" AND rp.login = u.login AND jec.id_classe='$critere_id_classe' AND jec.login=e.login AND e.ele_id=r.ele_id AND r.pers_id=rp.pers_id";
		}
	}
	$sql.=") ORDER BY u.nom,u.prenom";

	// Effectif sans login avec filtrage sur le nom:
	//$nb1 = mysql_num_rows(mysql_query($sql));
	
	if($afficher_tous_les_resp!='y'){
		if(($critere_recherche=="")&&($critere_recherche_login=="")&&($critere_id_classe=="")) {
			$sql.=" LIMIT 20";
		}
	}
}
else {
	$sql="SELECT u.*,rp.pers_id FROM utilisateurs u, resp_pers rp, responsables2 r, eleves e  WHERE u.statut='responsable' AND rp.login=u.login AND rp.pers_id=r.pers_id AND r.ele_id=e.ele_id AND e.login NOT IN (SELECT login FROM j_eleves_classes) ORDER BY u.nom,u.prenom;";
}
//echo "$sql<br />\n";
$quels_parents = mysql_query($sql);

// Effectif sans login avec filtrage sur le nom:
$nb1 = mysql_num_rows($quels_parents);

$compteur_resp=0;
$alt=1;
while ($current_parent = mysql_fetch_object($quels_parents)) {
	$alt=$alt*(-1);
	echo "<tr class='lig$alt white_hover' style='text-align:center;'>\n";
		echo "<td>";
			echo "<a href='../responsables/modify_resp.php?pers_id=".$current_parent->pers_id."&amp;journal_connexions=y'>".$current_parent->login."</a>";
		echo "</td>\n";
		echo "<td>";
			echo $current_parent->nom . " " . $current_parent->prenom;
		echo "</td>\n";
		echo "<td>";
		$sql="SELECT DISTINCT e.nom,e.prenom,c.classe FROM eleves e,
												j_eleves_classes jec,
												classes c,
												responsables2 r
											WHERE e.login=jec.login AND
												jec.id_classe=c.id AND
												r.ele_id=e.ele_id AND
												r.pers_id='$current_parent->pers_id' AND
												(r.resp_legal='1' OR r.resp_legal='2')
											ORDER BY e.nom,e.prenom";
		$res_enfants=mysql_query($sql);
		//echo "$sql<br />";
		if(mysql_num_rows($res_enfants)==0){
			echo "<span style='color:red;' title='Aucun élève, ou plus des élèves qui ne sont plus dans aucune classe'>Aucun élève</span>";
		}
		else{
			while($current_enfant=mysql_fetch_object($res_enfants)){
				echo casse_mot($current_enfant->prenom,'majf2')." ".casse_mot($current_enfant->nom,'maj')." (<i>".$current_enfant->classe."</i>)<br />\n";
			}
		}
		echo "</td>\n";
		echo "<td align='center'>";
			if ($current_parent->etat == "actif") {
				echo "<font color='green'>".$current_parent->etat."</font>";
				if($current_parent->login!='') {
					echo "<br />";
					echo "<a href='edit_responsable.php?action=rendre_inactif&amp;mode=individual&amp;parent_login=".$current_parent->login.add_token_in_url()."'>Désactiver";
				}
			} else {
				echo "<font color='red'>".$current_parent->etat."</font>";
				if($current_parent->login!='') {
					echo "<br />";
					echo "<a href='edit_responsable.php?action=rendre_actif&amp;mode=individual&amp;parent_login=".$current_parent->login.add_token_in_url()."'>Activer";
				}
			}
			echo "</a>";
		echo "</td>\n";

		echo "<td>";
		echo $current_parent->auth_mode;
		echo "</td>\n";

		echo "<td>";
		echo "<a href='edit_responsable.php?action=supprimer&amp;mode=individual&amp;parent_login=".$current_parent->login.add_token_in_url()."' onclick=\"javascript:return confirm('Êtes-vous sûr de vouloir supprimer l\'utilisateur ?')\">Supprimer</a>";
		echo "</td>";

		if($current_parent->etat == "actif" && ($current_parent->auth_mode == "gepi" || $gepiSettings['ldap_write_access'] == "yes")) {
			echo "<td>";
			//echo "<br />";
			echo "<a href=\"reset_passwords.php?user_login=".$current_parent->login."&amp;user_status=responsable&amp;mode=html".add_token_in_url()."\" onclick=\"javascript:return confirm('Êtes-vous sûr de vouloir effectuer cette opération ?\\n Celle-ci est irréversible, et réinitialisera le mot de passe de l\'utilisateur avec un mot de passe alpha-numérique généré aléatoirement.\\n En cliquant sur OK, vous lancerez la procédure, qui génèrera une page contenant la fiche-bienvenue à imprimer immédiatement pour distribution à l\'utilisateur concerné.')\" target='_blank'>Aléatoirement</a>";
			echo "</td>";
			echo "<td>";
			echo "<a href=\"reset_passwords.php?user_login=".$current_parent->login."&amp;user_status=responsable&amp;mode=html&amp;affiche_adresse_resp=y".add_token_in_url()."\" onclick=\"javascript:return confirm('Êtes-vous sûr de vouloir effectuer cette opération ?\\n Celle-ci est irréversible, et réinitialisera le mot de passe de l\'utilisateur avec un mot de passe alpha-numérique généré aléatoirement.\\n En cliquant sur OK, vous lancerez la procédure, qui génèrera une page contenant la fiche-bienvenue à imprimer immédiatement pour distribution à l\'utilisateur concerné.')\" target='_blank'>Aléa.avec&nbsp;adresse</a>";
			echo "</td>";
			echo "<td>";
			echo "<a href=\"change_pwd.php?user_login=".$current_parent->login.add_token_in_url()."\" onclick=\"javascript:return confirm('Êtes-vous sûr de vouloir effectuer cette opération ?\\n Celle-ci réinitialisera le mot de passe de l\'utilisateur avec un mot de passe que vous choisirez.\\n En cliquant sur OK, vous lancerez une page qui vous demandera de saisir un mot de passe et de le valider.')\" target='_blank'>Choisi </a>";
			echo "</td>\n";
		}
		else {
			echo "<td colspan='3'>\n";
			echo "&nbsp;";
			echo "</td>\n";
		}
	echo "</tr>\n";
	$compteur_resp++;
	flush();
}
echo "</table>\n";
aff_time();
echo "<p>$compteur_resp ligne(s) affichée(s).</p>\n";
echo "</blockquote>\n";

?>

<?php
if (mysql_num_rows($quels_parents) == "0") {
	echo "<p>Pour créer de nouveaux comptes d'accès associés aux responsables d'élèves définis dans Gepi, vous devez cliquer sur le lien 'Ajouter de nouveaux comptes' ci-dessus.</p>";
}


echo "<p><em>NOTES&nbsp;:</em></p>
<a name='bloc_adresse'></a>
<blockquote>
<p>Si vous générez des Fiches bienvenue avec Bloc adresse du responsable de l'élève, il peut arriver que si les paramètres sont mal choisis, l'adresse n'apparaisse pas... ou hors champ.</p>\n";

echo "<p>Contrôler les paramétrages aberrants pour un format <a href='".$_SERVER['PHP_SELF']."?check_param_bloc_adresse_html=a4#bloc_adresse'>A4</a> ou un un format <a href='".$_SERVER['PHP_SELF']."?check_param_bloc_adresse_html=a3#bloc_adresse'>A3</a></p>";

if(isset($_GET['check_param_bloc_adresse_html'])) {
	if($_GET['check_param_bloc_adresse_html']=='a4') {
		echo "<p>Contrôle des paramètres pour la version A4&nbsp;:</p>";
		$retour_check=check_param_bloc_adresse_html('a4');
	}
	else {
		echo "<p>Contrôle des paramètres pour la version A3&nbsp;:</p>";
		$retour_check=check_param_bloc_adresse_html('a3');
	}

	if($retour_check=='') {
		echo "<p style='color:green'>";
		echo "Pas de valeur aberrante trouvée.";
	}
	else {
		echo "<p style='color:red'>";
		echo "".$retour_check;
	}
	echo "</p>";
}

echo "<br /><p style='text-indent: -6em; margin-left: 6em;'><em>Remarque&nbsp;:</em> Le bloc adresse des responsables d'un élève est positionné dans les bulletins HTML et Fiches Bienvenue avec les mêmes paramètres.<br />Ils sont définis dans la page <a href='../bulletin/param_bull.php#bloc_adresse'>Paramètres d'impression des bulletins</a></p>\n";
echo "</blockquote>\n";
echo "<p><br /></p>\n";
require("../lib/footer.inc.php");
?>
