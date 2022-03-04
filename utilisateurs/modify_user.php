<?php
/*
*
* Copyright 2001, 2012 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
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

// On indique qu'il faut crée des variables non protégées (voir fonction cree_variables_non_protegees())
$variables_non_protegees = 'yes';

// Initialisations files
$affiche_connexion = 'yes';
$niveau_arbo = 1;
require_once("../lib/initialisations.inc.php");
require_once("../lib/share-trombinoscope.inc.php");

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
$user_login = isset($_POST["user_login"]) ? $_POST["user_login"] : (isset($_GET["user_login"]) ? $_GET["user_login"] : NULL);
$msg = '';

$journal_connexions=isset($_POST['journal_connexions']) ? $_POST['journal_connexions'] : (isset($_GET['journal_connexions']) ? $_GET['journal_connexions'] : 'n');
$duree=isset($_POST['duree']) ? $_POST['duree'] : NULL;

// fonction de sécuritée
// uid de pour ne pas refaire renvoyer plusieurs fois le même formulaire
// autoriser la validation de formulaire $uid_post===$_SESSION['uid_prime']
if(empty($_SESSION['uid_prime'])) {
	$_SESSION['uid_prime']='';
}

if (empty($_GET['uid_post']) and empty($_POST['uid_post'])) {
	$uid_post='';
}
else {
	if (isset($_GET['uid_post'])) {
		$uid_post=$_GET['uid_post'];
	}
	if (isset($_POST['uid_post'])) {
		$uid_post=$_POST['uid_post'];
	}
}

$uid = md5(uniqid(microtime(), 1));
// on remplace les %20 par des espaces
$uid_post = preg_replace('/%20/',' ',$uid_post);
if($uid_post===$_SESSION['uid_prime']) {
	$valide_form = 'oui';
}
else {
	$valide_form = 'non';
}
$_SESSION['uid_prime'] = $uid;
// fin de la fonction de sécurité

// fin pour module trombinoscope

if (isset($_POST['valid']) and ($_POST['valid'] == "yes")) {
check_token();
//------------------------------------------------------
//--- Partie retirée par Thomas Belliard
// Cas LCS : on teste s'il s'agit d'un utilisateur local ou non
//	if (getSettingValue("use_sso") == "lcs"){
//		if ($_POST['is_lcs'] == "y") {
//			$is_pwd = 'n';
//		}
//		else {
//			$is_pwd = 'y';
//		}
//	}elseif(getSettingValue("use_sso") == 'cas'){
//
//		$is_pwd = 'n';
//
//	}
//	else {
//		$is_pwd = "y";
//	}
//------------------------------------------------------

	// On teste si on doit enregistrer un mot de passe ou non :
	if ($_POST['reg_auth_mode'] == "gepi" || $gepiSettings['ldap_write_access'] == "yes") {
		$is_pwd = "y";
	} else {
		$is_pwd = "n";
	}


	if ($_POST['reg_nom'] == '')  {
		$msg = "Veuillez entrer un nom pour l'utilisateur !";
	}
	else {
		$k = 0;
		while ($k < $_POST['max_mat']) {
			$temp = "matiere_".$k;
			$reg_matiere[$k] = $_POST[$temp];
			$k++;
		}

		//
		// actions si un nouvel utilisateur a été défini
		//

		$temoin_ajout_ou_modif_ok="n";

		if ((isset($_POST['new_login'])) and ($_POST['new_login']!='') and (preg_match("/^[a-zA-Z_]{1}[a-zA-Z0-9_.]{0,".($longmax_login-1)."}$/", $_POST['new_login'])) ) {
			// Modif Thomas : essayons d'accepter des logins sensibles à la casse, pour mieux s'adapter aux sources externes (LDAP).
			//$_POST['new_login'] = strtoupper($_POST['new_login']);
			$reg_password_c = md5($NON_PROTECT['password1']);
			$resultat = "";
			if (($_POST['no_anti_inject_password1'] != $_POST['reg_password2']) and ($is_pwd == "y")) {
				$msg = "Erreur lors de la saisie : les deux mots de passe ne sont pas identiques, veuillez recommencer !";
			} else if ((!(verif_mot_de_passe($_POST['no_anti_inject_password1'],0)))  and ($is_pwd == "y")) {
				$msg = "Erreur lors de la saisie du mot de passe (<em>voir les recommandations</em>), veuillez recommencer !";
				if((isset($info_verif_mot_de_passe))&&($info_verif_mot_de_passe!="")) {$msg.="<br />".$info_verif_mot_de_passe;}
			} else {
				// Le teste suivant détecte si un utilisateur existe avec le même login (insensible à la casse)
				$test = mysql_query("SELECT login FROM utilisateurs WHERE (login = '".$_POST['new_login']."' OR login = '".strtoupper($_POST['new_login'])."')");
				$nombreligne = mysql_num_rows($test);
				if ($nombreligne != 0) {
					$resultat = "NON";
					$msg = "*** Attention ! Un utilisateur ayant le même identifiant existe déjà. Enregistrement impossible ! ***";
				}
				if ($resultat != "NON") {
					// On enregistre l'utilisateur

					// Si on a activé l'accès LDAP en écriture, on commence par ça.
					// En cas d'échec, l'enregistrement ne sera pas poursuivi.

					// On ne continue que si le LDAP est configuré en écriture, qu'on a activé
					// l'auth LDAP ou SSO, et que c'est un de ces deux modes qui a été choisi pour cet utilisateur.
					if (LDAPServer::is_setup() && $gepiSettings["ldap_write_access"] == "yes" && ($session_gepi->auth_ldap || $session_gepi->auth_sso) && ($_POST['reg_auth_mode'] == 'ldap' || $_POST['reg_auth_mode'] == 'sso')) {
						$write_ldap = true;
						$write_ldap_success = false;
						// On tente de créer l'utilisateur sur l'annuaire LDAP
						$ldap_server = new LDAPServer();
						if ($ldap_server->test_user($_POST['new_login'])) {
							// L'utilisateur a été trouvé dans l'annuaire. On ne l'enregistre pas.
							$write_ldap_success = true;
							$msg.= "L'utilisateur n'a pas pu être ajouté à l'annuaire LDAP, car il y est déjà présent. Il va néanmoins être créé dans la base Gepi.";
						} else {
							$write_ldap_success = $ldap_server->add_user($_POST['new_login'], $_POST['reg_nom'], $_POST['reg_prenom'], $_POST['reg_email'], $_POST['reg_civilite'], $NON_PROTECT['password1'], $_POST['reg_statut']);
						}
					} else {
						$write_ldap = false;
					}

					# On poursuit si le LDAP s'est bien passé (ou bien si on n'avait rien à faire avec...)
					if (!$write_ldap or ($write_ldap && $write_ldap_success)) {
						// Ensuite, on enregistre dans la base, en distinguant selon le type d'authentification.
						if ($_POST['reg_auth_mode'] == "gepi") {
							// On enregistre le mot de passe
							$reg_data = mysql_query("INSERT INTO utilisateurs SET nom='".$_POST['reg_nom']."',prenom='".$_POST['reg_prenom']."',civilite='".$_POST['reg_civilite']."',login='".$_POST['new_login']."',password='$reg_password_c',statut='".$_POST['reg_statut']."',email='".$_POST['reg_email']."', auth_mode = '".$_POST['reg_auth_mode']."',etat='actif', change_mdp='y'");
						} else {
							// Auth LDAP ou SSO, pas de mot de passe.
							$reg_data = mysql_query("INSERT INTO utilisateurs SET nom='".$_POST['reg_nom']."',prenom='".$_POST['reg_prenom']."',civilite='".$_POST['reg_civilite']."',login='".$_POST['new_login']."',password='',statut='".$_POST['reg_statut']."',email='".$_POST['reg_email']."', auth_mode = '".$_POST['reg_auth_mode']."',etat='actif', change_mdp='n'");
						}

						if ($_POST['reg_statut'] == "professeur") {
							$del = mysql_query("DELETE FROM j_professeurs_matieres WHERE id_professeur = '".$_POST['new_login']."'");
							$m = 0;
							while ($m < $_POST['max_mat']) {
								if ($reg_matiere[$m] != '') {
									$test = mysql_query("SELECT * FROM j_professeurs_matieres WHERE (id_professeur = '".$_POST['new_login']."' and id_matiere = '$reg_matiere[$m]')");
									$resultat = mysql_num_rows($test);
									if ($resultat == 0) {
										$reg = mysql_query("INSERT INTO j_professeurs_matieres SET id_professeur = '".$_POST['new_login']."', id_matiere = '$reg_matiere[$m]', ordre_matieres = '0'");
									}
								}
								$reg_matiere[$m] = '';
								$m++;
							}
						}

						$msg="Vous venez de créer un nouvel utilisateur !<br />Par défaut, cet utilisateur est considéré comme actif.";
						//$msg = $msg."<br />Pour imprimer les paramètres de l'utilisateur (identifiant, mot de passe, ...), cliquez <a href='impression_bienvenue.php?user_login=".$_POST['new_login']."&mot_de_passe=".urlencode($NON_PROTECT['password1'])."' target='_blank'>ici</a> !";
						$msg = $msg."<br />Pour imprimer les paramètres de l'utilisateur (identifiant, mot de passe, ...), cliquez <a href='impression_bienvenue.php?user_login=".$_POST['new_login']."&amp;mot_de_passe=".urlencode($NON_PROTECT['password1'])."' target='_blank'>ici</a> !";
						$msg = $msg."<br />Attention : ultérieurement, il vous sera impossible d'imprimer à nouveau le mot de passe d'un utilisateur ! ";
						$user_login = $_POST['new_login'];

						$temoin_ajout_ou_modif_ok="y";
					}
				}
			}

			if($temoin_ajout_ou_modif_ok=="y") {
				if ($_POST['reg_statut']=='scolarite'){
					$sql="SELECT c.id FROM classes c;";
					$res_liste_classes=mysql_query($sql);
					if(mysql_num_rows($res_liste_classes)>0){
						while($ligtmp=mysql_fetch_object($res_liste_classes)) {
							$sql="INSERT INTO j_scol_classes SET id_classe='$ligtmp->id', login='".$_POST['new_login']."';";
							$insert=mysql_query($sql);
							if(!$insert){
								$msg.="<br />Erreur lors de l'association avec la classe ".get_class_from_id($ligtmp->id);
							}
						}
					}
				}
			}

		}
		//
		//action s'il s'agit d'une modification
		//
		else if ((isset($user_login)) and ($user_login!='')) {

			// On regarde quel est le format du login, majuscule ou minuscule...
			$test = sql_count(sql_query("SELECT login FROM utilisateurs WHERE (login = '".$user_login."')"));
			if ($test == "0") $user_login = strtoupper($user_login);

			if (isset($_POST['deverrouillage'])) {
				$reg_data = sql_query("UPDATE utilisateurs SET date_verrouillage=now() - interval " . getSettingValue("temps_compte_verrouille") . " minute  WHERE (login='".$user_login."')");
			}

			// Si on change le mode d'authentification, il faut quelques opérations particulières
			$old_auth_mode = mysql_result(mysql_query("SELECT auth_mode FROM utilisateurs WHERE login = '".$user_login."'"), 0);
			if ($old_auth_mode == "gepi" && ($_POST['reg_auth_mode'] == "ldap" || $_POST['reg_auth_mode'] == "sso")) {
				// On passe du mode Gepi à un mode externe : il faut supprimer le mot de passe
				$oldmd5password = mysql_result(mysql_query("SELECT password FROM utilisateurs WHERE login = '".$user_login."'"), 0);
				mysql_query("UPDATE utilisateurs SET password = '', salt = '' WHERE login = '".$user_login."'");
				$msg = "Passage à un mode d'authentification externe : ";
				// Et si on a un accès en écriture au LDAP, il faut créer l'utilisateur !
				if ($gepiSettings['ldap_write_access'] == "yes") {
					$create_ldap_user = true;
					$msg .= "le mot de passe de l'utilisateur est inchangé.<br/>";
				} else {
					$msg .= "le mot de passe de l'utilisateur a été effacé.<br/>";
				}
			} elseif (($old_auth_mode == "sso" || $old_auth_mode == "ldap") && $_POST['reg_auth_mode'] == "gepi") {
				// On passe d'un mode externe à un mode Gepi. On prévient l'admin qu'il faut modifier le mot de passe.
				$msg = "Passage d'un mode d'authentification externe à un mode local : le mot de passe de l'utilisateur *doit* être réinitialisé.<br/>";
				// Et si accès en écriture au LDAP, on supprime le compte.
				if ($gepiSettings['ldap_write_access'] == "yes" && (!isset($_POST['prevent_ldap_removal']) or $_POST['prevent_ldap_removal'] != "yes")) {
					$delete_ldap_user = true;
				}
			}
			$change = "yes";
			$flag = '';
			if ($_POST['reg_statut'] != "professeur") {
				$test = mysql_query("SELECT * FROM j_groupes_professeurs WHERE (login='".$user_login."')");
				$nb = mysql_num_rows($test);
				if ($nb != 0) {
					$msg = "Impossible de changer le statut. Cet utilisateur est actuellement professeur dans certaines classes !";
					$change = "no";
				} else {
					$k = 0;
					while ($k < $_POST['max_mat']) {
						$reg_matiere[$k] = '';
						$k++;
					}
				}
			}

			if ($_POST['reg_statut'] == "professeur") {
				//$test = mysql_query("SELECT jgm.id_matiere FROM j_groupes_professeurs jgp, j_groupes_matieres jgm WHERE (" .
				$test = mysql_query("SELECT DISTINCT(jgm.id_matiere) FROM j_groupes_professeurs jgp, j_groupes_matieres jgm WHERE (" .
					"jgp.login = '".$user_login."' and " .
					"jgm.id_groupe = jgp.id_groupe)");
				$nb = mysql_num_rows($test);
				if ($nb != 0) {
					$k = 0;
					$change = "yes";
					while ($k < $nb) {
						// ===============
						// Pour chaque matière associée au prof, on réinitialise le témoin:
						$flag="no";
						// ===============
						$id_matiere = mysql_result($test, $k, 'id_matiere');
						//echo "\$k=$k<br />";
						//echo "\$id_matiere=$id_matiere<br />";
						$m = 0;
						while ($m < $_POST['max_mat']) {
							//echo "\$m=$m - \$id_matiere=$id_matiere - \$reg_matiere[$m]=$reg_matiere[$m]";
							if ($id_matiere == $reg_matiere[$m]) {
								$flag = "yes";
							}
							//if(isset($flag)){echo " \$flag=$flag";}
							//echo "<br />";
							$m++;
						}
						if ($flag != "yes") {
							$change = "no";
						}
						$k++;
					}
					if ($change == "no") {
						$msg = "Impossible de changer les matières. Cet utilisateur est actuellement professeur dans certaines classes des matières que vous voulez supprimer !";
					}
				}
			}

			if ($change == "yes") {
				// Variable utilisée pour la partie photo:
				$temoin_ajout_ou_modif_ok="y";

				$sql="SELECT statut FROM utilisateurs WHERE login='$user_login';";
				$res_statut_user=mysql_query($sql);
				$lig_tmp=mysql_fetch_object($res_statut_user);

				// Si l'utilisateur était CPE, il faut supprimer les associations dans la table j_eleves_cpe
				if($lig_tmp->statut=="cpe"){
					if($_POST['reg_statut']!="cpe"){
						$sql="DELETE FROM j_eleves_cpe WHERE cpe_login='$user_login';";
						$nettoyage=mysql_query($sql);
					}
				}

				// Si l'utilisateur était SCOLARITE, il faut supprimer les associations dans la table j_scol_classes
				if($lig_tmp->statut=="scolarite"){
					if($_POST['reg_statut']!="scolarite"){
						$sql="DELETE FROM j_scol_classes WHERE login='$user_login';";
						$nettoyage=mysql_query($sql);
					}
				}

				// On effectue les opérations LDAP
				if (isset($create_ldap_user) && $create_ldap_user) {
					$ldap_server = new LDAPServer;
					if ($ldap_server->test_user($user_login)) {
						// L'utilisateur a été trouvé dans l'annuaire. On ne l'enregistre pas.
						$write_ldap_success = true;
						$msg.= "L'utilisateur n'a pas pu être ajouté à l'annuaire LDAP, car il y est déjà présent.<br/>";
					} else {
						$write_ldap_success = $ldap_server->add_user($user_login, $_POST['reg_nom'], $_POST['reg_prenom'], $_POST['reg_email'], $_POST['reg_civilite'], md5(rand()), $_POST['reg_statut']);
						// On transfert le mot de passe à la main
						$ldap_server->set_manual_password($user_login, "{MD5}".base64_encode(pack("H*",$oldmd5password)));
					}
				}

				if (isset($delete_ldap_user) && $delete_ldap_user) {
					$ldap_server = new LDAPServer;
					if (!$ldap_server->test_user($user_login)) {
						// L'utilisateur n'a pas été trouvé dans l'annuaire.
						$write_ldap_success = true;
					} else {
						$write_ldap_success = $ldap_server->delete_user($user_login);
					}
				}


				$reg_data = mysql_query("UPDATE utilisateurs SET nom='".$_POST['reg_nom']."',prenom='".$_POST['reg_prenom']."',civilite='".$_POST['reg_civilite']."', login='".$_POST['reg_login']."',statut='".$_POST['reg_statut']."',email='".$_POST['reg_email']."',etat='".$_POST['reg_etat']."',auth_mode='".$_POST['reg_auth_mode']."' WHERE login='".$user_login."'");
				$del = mysql_query("DELETE FROM j_professeurs_matieres WHERE id_professeur = '".$user_login."'");
				$m = 0;
				while ($m < $_POST['max_mat']) {
					$num=$m+1;
					if ($reg_matiere[$m] != '') {
						$test = mysql_query("SELECT * FROM j_professeurs_matieres WHERE (id_professeur = '".$user_login."' and id_matiere = '$reg_matiere[$m]')");
						$resultat = mysql_num_rows($test);
						if ($resultat == 0) {
						$reg = mysql_query("INSERT INTO j_professeurs_matieres SET id_professeur = '".$user_login."', id_matiere = '$reg_matiere[$m]', ordre_matieres = '$num'");
						}
						$reg_matiere[$m] = '';
					}
					$m++;
				}
				if (!$reg_data) {
					$msg = "Erreur lors de l'enregistrement des données";
				} else {
					$msg.="Les modifications ont bien été enregistrées !";
				}
			}
		} // elseif...
		else {
			if (mb_strlen($_POST['new_login']) > $longmax_login) {
				$msg = "L'identifiant est trop long, il ne doit pas dépasser ".$longmax_login." caractères.";
			}
			else {
				$msg = "L'identifiant de l'utilisateur doit être constitué uniquement de lettres et de chiffres !";
			}
		}


		if($temoin_ajout_ou_modif_ok=="y"){
			// pour le module trombinoscope
			// Envoi de la photo
			$i_photo = 0;
			$calldata_photo = mysql_query("SELECT * FROM utilisateurs WHERE (login = '".$user_login."')");

		// En multisite, on ajoute le répertoire RNE
		if (isset($GLOBALS['multisite']) AND $GLOBALS['multisite'] == 'y') {
			  // On récupère le RNE de l'établissement
		  $repertoire="../photos/".$_COOKIE['RNE']."/personnels/";
		}else{
		  $repertoire="../photos/personnels/";
		}
			//$repertoire = '../photos/personnels/';
			$code_photo = md5(mb_strtolower($user_login));



					if(isset($_POST['suppr_filephoto']) and $valide_form === 'oui' ){
						if($_POST['suppr_filephoto']=='y'){
							if(unlink($repertoire.$code_photo.".jpg")){
								$msg = "La photo ".$repertoire.$code_photo.".jpg a été supprimée. ";
							}
							else{
								$msg = "Echec de la suppression de la photo ".$repertoire.$code_photo.".jpg ";
							}
						}
					}

					// filephoto
					if(isset($_FILES['filephoto']['tmp_name'])){
						$filephoto_tmp=$_FILES['filephoto']['tmp_name'];
						if ( $filephoto_tmp != '' and $valide_form === 'oui' ){
							$filephoto_name=$_FILES['filephoto']['name'];
							$filephoto_size=$_FILES['filephoto']['size'];
							$filephoto_type=$_FILES['filephoto']['type']; 
							// Tester la taille max de la photo?
							if ((preg_match('/jpg$/',strtolower($filephoto_name)) || preg_match('/jpeg$/',strtolower($filephoto_name))) && ($filephoto_type == "image/jpeg" || $filephoto_type == "image/pjpeg")) {
								if(is_uploaded_file($filephoto_tmp)){
									$dest_file = $repertoire.$code_photo.".jpg";
									$source_file = $filephoto_tmp;
									$res_copy=copy("$source_file" , "$dest_file");
									if($res_copy){
										$msg = "Mise en place de la photo effectuée.";
										if (getSettingValue("active_module_trombinoscopes_rd")=='y') {
											// si le redimensionnement des photos est activé on redimensionne
											if (getSettingValue("active_module_trombinoscopes_rt")!='')
												$redim_OK=redim_photo($dest_file,getSettingValue("l_resize_trombinoscopes"), getSettingValue("h_resize_trombinoscopes"),getSettingValue("active_module_trombinoscopes_rt"));
											else
												$redim_OK=redim_photo($dest_file,getSettingValue("l_resize_trombinoscopes"), getSettingValue("h_resize_trombinoscopes"));
											if (!$redim_OK) $msg .= " Echec du redimensionnement de la photo.";
										}
									}
									else{
										$msg = "Erreur lors de la mise en place de la photo.";
									}
								}
								else{
									$msg = "Erreur lors de l'upload de la photo.";
								}
							}
							else {
								$msg = "Erreur : seuls les fichiers ayant l'extension .jpg ou .jpeg sont autorisés.";
							}
						}
					}


				// si suppression de la fiche il faut supprimer la photo

			// fin pour le module trombinoscope
		}
	}
}
elseif(isset($_POST['suppression_assoc_user_groupes'])) {
	check_token();

	$user_group=isset($_POST["user_group"]) ? $_POST["user_group"] : array();

	$call_classes = mysql_query("SELECT g.id group_id, g.name name, c.classe classe, c.id classe_id " .
			"FROM j_groupes_professeurs jgp, j_groupes_classes jgc, groupes g, classes c WHERE (" .
			"jgp.login = '$user_login' and " .
			"g.id = jgp.id_groupe and " .
			"jgc.id_groupe = jgp.id_groupe and " .
			"c.id = jgc.id_classe) order by jgc.id_classe");
	$nb_classes = mysql_num_rows($call_classes);
	if($nb_classes>0) {
		$k = 0;
		$user_classe=array();
		while ($k < $nb_classes) {
			$user_classe['classe_nom_court'] = mysql_result($call_classes, $k, "classe");
			$user_classe['matiere_nom_court'] = mysql_result($call_classes, $k, "name");
			$user_classe['classe_id'] = mysql_result($call_classes, $k, "classe_id");
			$user_classe['group_id'] = mysql_result($call_classes, $k, "group_id");

			if(!in_array($user_classe['group_id'],$user_group)) {
				$sql="DELETE FROM j_groupes_professeurs WHERE id_groupe='".$user_classe['group_id']."' AND login='$user_login';";
				//echo "$sql<br />\n";
				$suppr=mysql_query($sql);
				if($suppr) {
					$msg.="Suppression de l'association avec l'enseignement ".$user_classe['matiere_nom_court']." en ".$user_classe['classe_nom_court']."<br />\n";
				}
				else {
					$msg.="ERREUR lors de la suppression de l'association avec l'enseignement ".$user_classe['matiere_nom_court']." en ".$user_classe['classe_nom_court']."<br />\n";
				}
			}
			$k++;
		}
		unset($user_classe);
	}
}

// On appelle les informations de l'utilisateur pour les afficher :
if (isset($user_login) and ($user_login!='')) {

	$call_user_info = mysql_query("SELECT * FROM utilisateurs WHERE login='".$user_login."'");
	$user_auth_mode = mysql_result($call_user_info, "0", "auth_mode");
	$user_nom = mysql_result($call_user_info, "0", "nom");
	$user_prenom = mysql_result($call_user_info, "0", "prenom");
	$user_civilite = mysql_result($call_user_info, "0", "civilite");
	$user_statut = mysql_result($call_user_info, "0", "statut");
	$user_email = mysql_result($call_user_info, "0", "email");
	$user_etat = mysql_result($call_user_info, "0", "etat");
	$date_verrouillage = mysql_result($call_user_info, "0", "date_verrouillage");

	$call_matieres = mysql_query("SELECT * FROM j_professeurs_matieres j WHERE j.id_professeur = '".$user_login."' ORDER BY ordre_matieres");
	$nb_mat = mysql_num_rows($call_matieres);
	$k = 0;
	while ($k < $nb_mat) {
		$user_matiere[$k] = mysql_result($call_matieres, $k, "id_matiere");
		$k++;
	}

	// Utilisateurs précédent/suivant:
	//$sql="SELECT login,nom,prenom FROM utilisateurs WHERE statut='$user_statut' ORDER BY nom,prenom";
	$sql="SELECT login,nom,prenom FROM utilisateurs WHERE statut='$user_statut' AND etat='actif' ORDER BY nom,prenom";
	$res_liste_user=mysql_query($sql);
	if(mysql_num_rows($res_liste_user)>0){
		$login_user_prec="";
		$login_user_suiv="";
		$temoin_tmp=0;
		$liste_options_user="";
		while($lig_user_tmp=mysql_fetch_object($res_liste_user)){
			if("$lig_user_tmp->login"=="$user_login"){
				$liste_options_user.="<option value='$lig_user_tmp->login' selected='true'>".mb_strtoupper($lig_user_tmp->nom)." ".ucfirst(mb_strtolower($lig_user_tmp->prenom))."</option>\n";
				$temoin_tmp=1;
				if($lig_user_tmp=mysql_fetch_object($res_liste_user)){
					$login_user_suiv=$lig_user_tmp->login;
					$liste_options_user.="<option value='$lig_user_tmp->login'>".mb_strtoupper($lig_user_tmp->nom)." ".ucfirst(mb_strtolower($lig_user_tmp->prenom))."</option>\n";
				}
				else{
					$login_user_suiv="";
				}
			}
			else{
					$liste_options_user.="<option value='$lig_user_tmp->login'>".mb_strtoupper($lig_user_tmp->nom)." ".ucfirst(mb_strtolower($lig_user_tmp->prenom))."</option>\n";
			}
			if($temoin_tmp==0){
				$login_user_prec=$lig_user_tmp->login;
			}
		}
	}

} else {
	$nb_mat = 0;
	if (isset($_POST['reg_civilite']))
		$user_civilite = $_POST['reg_civilite'];
	else
		$user_civilite = 'M.';
	$user_auth_mode = isset($_POST['reg_auth_mode']) ? $_POST['reg_auth_mode'] : "gepi";
	if (isset($_POST['reg_nom'])) $user_nom = $_POST['reg_nom'];
	if (isset($_POST['reg_prenom'])) $user_prenom = $_POST['reg_prenom'];
	if (isset($_POST['reg_statut'])) $user_statut = $_POST['reg_statut'];
	if (isset($_POST['reg_email'])) $user_email = $_POST['reg_email'];
	if (isset($_POST['reg_etat'])) $user_etat = $_POST['reg_etat'];

	if ((isset($_POST['matiere_0']))&&($_POST['matiere_0']!='')) {
		$user_matiere[0]=$_POST['matiere_0'];
	}
}

$themessage  = 'Des informations ont été modifiées. Voulez-vous vraiment quitter sans enregistrer ?';
$themessage2 = "Êtes-vous sûr de vouloir effectuer cette opération ?\\n Actuellement cet utilisateur se connecte à GEPI en s\'authentifiant auprès d\'un SSO.\\n En attribuant un mot de passe, vous lancerez la procédure, qui génèrera un mot de passe local. Cet utilisateur ne pourra donc plus se connecter à GEPI via le SSO mais uniquement localement.";
//**************** EN-TETE *****************
//$titre_page = "Gestion des utilisateurs | Modifier un utilisateur";
$titre_page = "Création/modification d'un personnel";
require_once("../lib/header.inc.php");
//**************** FIN EN-TETE *****************
//debug_var();
?>
<script type='text/javascript'>
	function display_password_fields(id,rw){
		if ($(id).value=='gepi' || rw == true) {
			$('password_fields').style.display='block';
			$('password_fields').style.visibility='visible';
		} else {
			$('password_fields').style.visibility='hidden';
			$('password_fields').style.display='none';
		}
	}

	change='no';
</script>

<?php

//echo "\$login_user_prec=$login_user_prec<br />";
//echo "\$login_user_suiv=$login_user_suiv<br />";

echo "<form enctype='multipart/form-data' name='form_choix_user' action='modify_user.php' method='post'>\n";

echo "<p class='bold'>";
echo "<a href='index.php?mode=personnels' onclick=\"return confirm_abandon (this, change, '$themessage')\"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a> | <a href='javascript:centrerpopup(\"help.php\",600,480,\"scrollbars=yes,statusbar=no,resizable=yes\")'>Aide</a>";

// dans le cas de LCS, existence d'utilisateurs locaux repérés grâce au champ password non vide.
$testpassword = sql_query1("select password from utilisateurs where login = '".$user_login."'");
if ($testpassword == -1) $testpassword = '';
if (isset($user_login) and ($user_login!='')) {
	if ((getSettingValue('use_sso') != "cas" and getSettingValue("use_sso") != "lemon"  and ((getSettingValue("use_sso") != "lcs") or ($testpassword !='')) and getSettingValue("use_sso") != "ldap_scribe") OR $block_sso) {
		echo " | <a href=\"change_pwd.php?user_login=".$user_login."\" onclick=\"return confirm_abandon (this, change, '$themessage')\">Changer le mot de passe</a>\n";
	} else if (getSettingValue('use_sso') == "lcs") {
		echo " | <a href=\"change_pwd.php?user_login=".$user_login."&amp;attib_mdp=yes\" onclick=\"return confirm ('$themessage2')\">Attribuer un mot de passe</a>\n";
  }
	echo " | <a href=\"modify_user.php\" onclick=\"return confirm_abandon (this, change, '$themessage')\">Ajouter un nouvel utilisateur</a>\n";
}

if(isset($liste_options_user)){
	if("$liste_options_user"!=""){
		if("$login_user_prec"!=""){echo " | <a href='modify_user.php?user_login=$login_user_prec' onclick=\"return confirm_abandon (this, change, '$themessage')\">Précédent</a>\n";}
		echo " | <select name='user_login' onchange=\"if(confirm_abandon (this, change, '$themessage')){document.form_choix_user.submit()}\">\n";
		echo $liste_options_user;
		echo "</select>\n";
		if("$login_user_suiv"!=""){echo " | <a href='modify_user.php?user_login=$login_user_suiv' onclick=\"return confirm_abandon (this, change, '$themessage')\">Suivant</a>\n";}
	}
}
echo "</p>\n";
echo "</form>\n";

$ldap_write_access = getSettingValue("ldap_write_access") == "yes" ? true : false;
if (!LDAPServer::is_setup()) {$ldap_write_access = false;}

if ($ldap_write_access) {
	echo "<p><strong><span style='color: red;'>Attention !</strong> Un accès LDAP en écriture a été défini.
			En conséquence, toute modification effectuée sur un utilisateur ayant pour mode d'authentification LDAP ou SSO sera répercutée sur le LDAP (cela inclut la création du mot de passe, pour les nouveaux utilisateurs).
			Si l'utilisateur existe déjà dans l'annuaire LDAP, ses informations ne seront pas mises à jour dans l'annuaire mais il sera tout de même créé dans la base Gepi.
			En cas de modification d'un utilisateur existant à la fois dans l'annuaire et dans Gepi, les modifications seront répercutées sur l'annuaire LDAP.</strong></p>";
}
?>

<form enctype="multipart/form-data" action="modify_user.php" method="post">
<fieldset style='border: 1px solid grey; background-image: url("../images/background/opacite50.png");'>
		<legend style='border: 1px solid grey; background-color: white;'>Informations utilisateur</legend>
<?php
echo add_token_field();
if (isset($user_login)) {
	echo "<div style='float:right; width:; height:;'><a href='".$_SERVER['PHP_SELF']."?user_login=$user_login&amp;journal_connexions=y#connexion' title='Journal des connexions'><img src='../images/icons/document.png' width='16' height='16' alt='Journal des connexions' /></a></div>\n";
}
?>
<!--span class = "norme"-->
<div class = "norme">
<b>Identifiant <?php
if (!isset($user_login)) echo "(<em>" . $longmax_login . " caractères maximum</em>) ";?>:</b>
<?php
if (isset($user_login) and ($user_login!='')) {
	echo "<b>".$user_login."</b>\n";
	echo "<input type='hidden' name='reg_login' value=\"".$user_login."\" />\n";
} else {
	echo "<input type='text' name='new_login' size='20' value=\"";
	if (isset($user_login)) {echo $user_login;}
	elseif(isset($_POST['new_login'])) {echo $_POST['new_login'];}
	echo "\" onchange=\"changement()\" />\n";
}

if (!$session_gepi->auth_ldap || !$session_gepi->auth_sso) {
	$remarque = "<p style='font-size: small;'><em>Note : ";
	if (!$session_gepi->auth_ldap && !$session_gepi->auth_sso) {
		$remarque .= "les modes d'authentification LDAP et SSO sont actuellement inactifs. Si vous choisissez l'un de ces modes, l'utilisateur ne disposera d'aucun moyen de s'authentifier dans Gepi.</em></p>";
	} else {
		$remarque .= "l'authentification ";
		if (!$session_gepi->auth_ldap) {
			$remarque .= "LDAP ";
		} else {
			$remarque .= "SSO ";
		}
		$remarque .= "est actuellement inactive. Si vous choisissez ce mode d'authentification, l'utilisateur ne disposera d'aucun moyen de s'authentifier dans Gepi.</em></p>";
	}
	echo $remarque;
}

if (($_SESSION['statut']=='administrateur') and (isset($user_login)) and ($user_login!='')) {
	echo "<div style='float:right; width:10em; margin-top: 0.5em; text-align:center; border: 1px solid black;'>\n";
	echo affiche_actions_compte($user_login, '_blank');
	echo "<br />\n";
	echo affiche_reinit_password($user_login);
	echo "</div>\n";
}

?>
<table summary="Infos">
	<tr><td>
	<table summary="Authentification">
<tr><td>Authentification&nbsp;:</td>
<?php
if (!isset($user_login) or $user_login == '') {
	$rw_access = $ldap_write_access ? "true":"false";
	$onchange_value = "changement(); display_password_fields(this.id,".$rw_access.");";
} else {
	$onchange_value = "changement();";
}
?>
	<td><select id="select_auth_mode" name="reg_auth_mode" size="1" onchange="<?php echo $onchange_value; ?>">
<option value='gepi' <?php if ($user_auth_mode=='gepi') echo " selected ";  ?>>Locale (base Gepi)</option>
<option value='ldap' <?php if ($user_auth_mode=='ldap') echo " selected ";  ?>>LDAP</option>
<option value='sso' <?php if ($user_auth_mode=='sso') echo " selected ";  ?>>SSO (Cas, LCS, LemonLDAP)</option>
</select>
</td></tr>
<?php
if ($ldap_write_access) {
	echo "<tr><td></td>&nbsp;<td>";
	echo "<p style='font-size: small;'><input type='checkbox' name='prevent_ldap_removal' value='yes' checked /> Ne pas supprimer du LDAP<br/>(si cette case est décochée et que vous passez d'un mode d'authentification LDAP ou SSO à un mode d'authentification locale, l'utilisateur sera supprimé de l'annuaire LDAP).</p>";
	echo "</td></tr>";
}
 ?>
<tr><td>Nom&nbsp;:</td><td><input type=text name=reg_nom size=20 <?php if (isset($user_nom)) { echo "value=\"".$user_nom."\"";}?> /></td></tr>
<tr><td>Prénom&nbsp;:</td><td><input type=text name=reg_prenom size=20 <?php if (isset($user_prenom)) { echo "value=\"".$user_prenom."\"";}?> /></td></tr>
<tr><td>Civilité&nbsp;:</td><td><select name="reg_civilite" size="1" onchange="changement()">
<option value=''>(néant)</option>
<option value='M.' <?php if ($user_civilite=='M.') echo " selected ";  ?>>M.</option>
<option value='Mme' <?php if ($user_civilite=='Mme') echo " selected ";  ?>>Mme</option>
<option value='Mlle' <?php if ($user_civilite=='Mlle') echo " selected ";  ?>>Mlle</option>
</select>
</td></tr>
<tr><td>Email&nbsp;:</td><td><input type=text name=reg_email size=30 <?php if (isset($user_email)) { echo "value=\"".$user_email."\"";}?> onchange="changement()" /></td></tr>
</table>
</td>

<td>
<?php
// trombinoscope

if(getSettingValue("active_module_trombinoscopes")=='y'){

	// En multisite, on ajoute le répertoire RNE
	if (isset($GLOBALS['multisite']) AND $GLOBALS['multisite'] == 'y') {
		  // On récupère le RNE de l'établissement
	  $repertoire="../photos/".$_COOKIE['RNE']."/personnels/";
	}else{
	  $repertoire="../photos/personnels/";
	}
	if ((isset($user_login))and($user_login!='')&&(isset($user_nom))and($user_nom!='')&&(isset($user_prenom))and($user_prenom!='')) {
		$code_photo = md5(mb_strtolower($user_login));
		$photo=$repertoire.$code_photo.".jpg";
		echo "<table style='text-align: center;' summary='Photo'>\n";
		echo "<tr>\n";
		echo "<td style='text-align: center;'>\n";
		$temoin_photo="non";
		if(file_exists($photo)){
			$temoin_photo="oui";
			//echo "<td>\n";
			echo "<div align='center'>\n";
			// la photo sera réduite si nécessaire
			$dimphoto=dimensions_affichage_photo($photo,getSettingValue('l_max_aff_trombinoscopes'),getSettingValue('h_max_aff_trombinoscopes'));
			echo '<img src="'.$photo.'" style="width: '.$dimphoto[0].'px; height: '.$dimphoto[1].'px; border: 0px; border-right: 3px solid #FFFFFF; float: left;" alt="" />';
			//echo "</td>\n";
			//echo "<br />\n";
			echo "</div>\n";
			echo "<div style='clear:both;'></div>\n";
		}
		echo "<div align='center'>\n";
		echo "<span style='font-size:xx-small;'>\n";
		echo "<a href='#' onClick=\"document.getElementById('div_upload_photo').style.display='';return false;\">\n";
		if($temoin_photo=="oui"){
			echo "Modifier le fichier photo</a>\n";
		}
		else{
			echo "Envoyer un fichier photo</a>\n";
		}
	}
	else{
		echo "<table style='text-align: center;' summary='Photo'>\n";
		echo "<tr>\n";
		echo "<td style='text-align: center;'>\n";
		$temoin_photo="non";
		echo "<div align='center'>\n";
		echo "<span style='font-size:xx-small;'>\n";
		echo "<a href='#' onClick=\"document.getElementById('div_upload_photo').style.display='';return false;\">\n";
		echo "Envoyer un fichier photo</a>\n";
	}

	?></span>

	<div id="div_upload_photo" style="display: none;">
		<input type="file" name="filephoto" size="12" />
		<input type="hidden" name="uid_post" value="<?php echo preg_replace('/ /','%20',$uid); ?>" />
	<?php
	if ((isset($user_login))and($user_login!='')&&(isset($user_nom))and($user_nom!='')&&(isset($user_prenom))and($user_prenom!='')) {
		if(file_exists($photo)){
			?><br /><input type="checkbox" name="suppr_filephoto" id="suppr_filephoto" value="y" />
			&nbsp;<label for="suppr_filephoto" style="cursor: pointer; cursor: hand;">Supprimer la photo existante</label><?php
		}
	}
	?>
		<br /><input type="submit" value="Enregistrer" />
	</div>
	</div>
	</td>
	</tr>
	</table><?php
}
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";
// fin trombinoscope
?>

<?php
if (!(isset($user_login)) or ($user_login=='')) {
	# On créé un nouvel utilisateur. On définit son mot de passe.
	echo "<div id='password_fields' style='visibility: visible;'>";
	echo "<table summary='Mot de passe'><tr><td>Mot de passe (".getSettingValue("longmin_pwd") ." caractères minimum) : </td><td><input type=password name=no_anti_inject_password1 size=20 onchange=\"changement()\" /></td></tr>\n";
	echo "<tr><td>Mot de passe (à confirmer) : </td><td><input type=password name=reg_password2 size=20 onchange=\"changement()\" /></td></tr></table>\n";
	echo "<br /><b>Attention : le mot de passe doit comporter ".getSettingValue("longmin_pwd")." caractères minimum et doit être composé à la fois de lettres et de chiffres.</b>\n";
	echo "<br /><b>Remarque</b> : lors de la création d'un utilisateur, il est recommandé de choisir le NUMEN comme mot de passe.<br />\n";
	echo "</td></tr></table>\n";
	echo "</div>";
}
?>
<br />Statut (consulter l'<a href='javascript:centrerpopup("help.php",600,480,"scrollbars=yes,statusbar=no,resizable=yes")'>aide</a>) : <SELECT name=reg_statut size=1 onchange="changement()">
<?php if (!isset($user_statut)) $user_statut = "professeur"; ?>
<option value="professeur" <?php if ($user_statut == "professeur") { echo ' selected="selected"';}?>>Professeur</option>
<option value="administrateur" <?php if ($user_statut == "administrateur") { echo ' selected="selected"';}?>>Administrateur</option>
<option value="cpe" <?php if ($user_statut == "cpe") { echo ' selected="selected"';}?>>C.P.E.</option>
<option value="scolarite" <?php if ($user_statut == "scolarite") { echo ' selected="selected"';}?>>Scolarité</option>
<option value="secours" <?php if ($user_statut == "secours") { echo ' selected="selected"';}?>>Secours</option>
<?php
if (getSettingValue("statuts_prives") == "y") {
	if ($user_statut == "autre") { $sel = ' selected="selected"';}else{ $sel = '';}
	echo '
	<option value="autre"'.$sel.'>Autre</option>';
}
?>

</select>
<?php
if (getSettingValue("statuts_prives") == "y") {
	if ($user_statut == "autre") {
		echo "<a href='creer_statut.php' onclick=\"return confirm_abandon (this, change, '$themessage')\">Préciser le statut 'autre'</a>";
	}
}
?>
<br />

<br />Etat :<select name="reg_etat" size="1" onchange="changement()">
<?php if (!isset($user_etat)) $user_etat = "actif"; ?>
<option value="actif" <?php if ($user_etat == "actif") { echo "selected";}?>>Actif</option>
<option value="inactif" <?php if ($user_etat == "inactif") { echo "selected";}?>>Inactif</option>
</select>
<br />

<?php
$k = 0;
while ($k < $nb_mat+1) {
	$num_mat = $k+1;
	echo "Matière N°$num_mat (<em>si professeur</em>)&nbsp;: ";
	$temp = "matiere_".$k;
	echo "<select size=1 name='$temp' onchange=\"changement()\">\n";
	$calldata = mysql_query("SELECT * FROM matieres ORDER BY matiere");
	$nombreligne = mysql_num_rows($calldata);
	echo "<option value='' "; if (!(isset($user_matiere[$k]))) {echo " selected";} echo ">(vide)</option>\n";
	$i = 0;
	while ($i < $nombreligne){
		$matiere_list = mysql_result($calldata, $i, "matiere");
		$matiere_complet_list = mysql_result($calldata, $i, "nom_complet");
		//echo "<option value=$matiere_list "; if (isset($user_matiere[$k]) and ($matiere_list == $user_matiere[$k])) {echo " selected";} echo ">$matiere_list | $matiere_complet_list</option>\n";
		echo "<option value=$matiere_list "; if (isset($user_matiere[$k]) and ($matiere_list == $user_matiere[$k])) {echo " selected";} echo ">$matiere_list | ".htmlspecialchars($matiere_complet_list)."</option>\n";
		$i++;
	}
	echo "</select><br />\n";
	$k++;
}
$nb_mat++;

if (isset($user_login) and ($user_login!='') and ($user_statut=='scolarite')) {
	echo "Suivez ce lien pour <a href='../classes/scol_resp.php?quitter_la_page=y' target='_blank'>associer le compte avec des classes</a>.<br />\n";
}

// Déverrouillage d'un compte
if (isset($user_login) and ($user_login!='')) {
	$day_now   = date("d");
	$month_now = date("m");
	$year_now  = date("Y");
	$hour_now  = date("H");
	$minute_now = date("i");
	$seconde_now = date("s");
	$now = mktime($hour_now, $minute_now, $seconde_now, $month_now, $day_now, $year_now);

	$annee_verrouillage = mb_substr($date_verrouillage,0,4);
	$mois_verrouillage =  mb_substr($date_verrouillage,5,2);
	$jour_verrouillage =  mb_substr($date_verrouillage,8,2);
	$heures_verrouillage = mb_substr($date_verrouillage,11,2);
	$minutes_verrouillage = mb_substr($date_verrouillage,14,2);
	$secondes_verrouillage = mb_substr($date_verrouillage,17,2);
	$date_verrouillage = mktime($heures_verrouillage, $minutes_verrouillage, $secondes_verrouillage, $mois_verrouillage, $jour_verrouillage, $annee_verrouillage);
	if ($date_verrouillage  > ($now- getSettingValue("temps_compte_verrouille")*60)) {
		echo "<br /><center><table border=\"1\" cellpadding=\"5\" width = \"90%\" bgcolor=\"#FFB0B8\"  summary='Verrouillage'><tr><td>\n";
		echo "<h2>Verrouillage/Déverrouillage du compte</h2>\n";
		echo "Suite à un trop grand nombre de tentatives de connexions infructueuses, le compte est actuellement verrouillé.";
		echo "<br /><input type=\"checkbox\" name=\"deverrouillage\" value=\"yes\" onchange=\"changement()\" /> Cochez la case pour deverrouiller le compte";
		echo "</td></tr></table></center>\n";
	}
}

echo "<input type=hidden name=max_mat value=$nb_mat />\n";
?>
<input type=hidden name=valid value="yes" />
<?php if (isset($user_login)) echo "<input type=hidden name=user_login value=\"".$user_login."\" />\n"; ?>
<center><input type=submit value=Enregistrer /></center>
<!--/span-->
</div>
</fieldset>
</form>

<?php
	if((isset($user_login))&&(isset($user_statut))&&($user_statut=='professeur')) {
		$groups=get_groups_for_prof($user_login,NULL,array('classes', 'matieres'));
		if(count($groups)>0) {
			echo "<p>&nbsp;</p>\n";
			echo "<form enctype='multipart/form-data' action='modify_user.php' method='post'>\n";
			echo "<fieldset style='border: 1px solid grey; background-image: url(\"../images/background/opacite50.png\");'>
	<legend style='border: 1px solid grey; background-color: white;'>Enseignements du professeur</legend>";
			echo add_token_field();
			echo "<p>Le professeur est associé aux enseignements suivants.<br />Vous pouvez supprimer (<i>décocher</i>) l'association avec certains enseignements&nbsp;:</p>";
			$k = 0;
			foreach($groups as $current_group) {
				echo "<input type='checkbox' id='user_group_$k' name='user_group[]' value='".$current_group["id"]."' checked /><label for='user_group_$k'> ".$current_group['name']." (<em>".$current_group['description'];
				if((($current_group['name']!=$current_group['matiere']['matiere']))&&
				 (($current_group['description']!=$current_group['matiere']['nom_complet']))) {
					echo " (".$current_group['matiere']['matiere'].")";
				}
				echo "</em>) en ".$current_group['classlist_string']."</label><br />\n";
				$k++;
			}
			echo "<input type='hidden' name='user_login' value='$user_login' />\n";
			echo "<input type='hidden' name='suppression_assoc_user_groupes' value='y' />\n";
			echo "<center><input type='submit' value=\"Supprimer l'association avec les enseignements décochés\" /></center>\n";
			echo "</fieldset>\n";
			echo "</form>\n";
		}
	}
	echo "<p>&nbsp;</p>\n";

	if((isset($user_login))&&($journal_connexions=='n')) {
		echo "<p><a href='".$_SERVER['PHP_SELF']."?user_login=$user_login&amp;journal_connexions=y#connexion' title='Journal des connexions'>Journal des connexions</a></p>\n";
	}

	if($journal_connexions=='y') {
		// Journal des connexions
		echo "<a name=\"connexion\"></a>\n";
		if (isset($_POST['duree'])) {
			$duree = $_POST['duree'];
		} else {
			$duree = '7';
		}
		
		journal_connexions($user_login,$duree,'modify_user');

	}

	echo "<p>&nbsp;</p>\n";
?>

<?php require("../lib/footer.inc.php");?>
