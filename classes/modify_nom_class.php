<?php
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

$msg = null;

if (isset($is_posted) and ($is_posted == '1')) {
	check_token();

	if (isset($display_rang)) $display_rang = 'y'; else $display_rang = 'n';
	if (isset($display_address)) $display_address = 'y'; else $display_address = 'n';
	if (isset($display_coef)) $display_coef = 'y'; else $display_coef = 'n';
	if (isset($display_mat_cat)) $display_mat_cat = 'y'; else $display_mat_cat = 'n';
	if (isset($display_nbdev)) $display_nbdev = 'y'; else $display_nbdev = 'n';
	if (isset($display_moy_gen)) $display_moy_gen = 'y'; else $display_moy_gen = 'n';

	//if (!isset($modele_bulletin)) $$modele_bulletin = 1;
	if (!isset($modele_bulletin)) {$modele_bulletin = 1;}

	// =========================
	// AJOUT: boireaus
	//rn_formule
	//rn_sign_nblig

	if(strlen(preg_replace("/[0-9]/","",$rn_sign_nblig))!=0){$rn_sign_nblig=3;}

	if (isset($rn_nomdev)){$rn_nomdev='y';}else{$rn_nomdev='n';}
	if (isset($rn_toutcoefdev)){$rn_toutcoefdev='y';}else{$rn_toutcoefdev='n';}
	if (isset($rn_coefdev_si_diff)){$rn_coefdev_si_diff='y';}else{$rn_coefdev_si_diff='n';}
	if (isset($rn_datedev)){$rn_datedev='y';}else{$rn_datedev='n';}
	if (isset($rn_sign_chefetab)){$rn_sign_chefetab='y';}else{$rn_sign_chefetab='n';}
	if (isset($rn_sign_pp)){$rn_sign_pp='y';}else{$rn_sign_pp='n';}
	if (isset($rn_sign_resp)){$rn_sign_resp='y';}else{$rn_sign_resp='n';}
	// =========================

    // Mod ECTS
    if (!isset($ects_type_formation)) $ects_type_formation = '';
    if (!isset($ects_parcours)) $ects_parcours = '';
    if (!isset($ects_code_parcours)) $ects_code_parcours = '';
    if (!isset($ects_domaines_etude)) $ects_domaines_etude = '';
    if (!isset($ects_fonction_signataire_attestation)) $ects_fonction_signataire_attestation = '';


	if (isset($id_classe)) {
		if ($reg_class_name) {
			//$register_class = mysql_query("UPDATE classes SET classe='$reg_class_name', nom_complet='$reg_nom_complet', suivi_par='$reg_suivi_par', formule= '$reg_formule', format_nom='$reg_format', display_rang='$display_rang', display_address='$display_address', display_coef='$display_coef', display_mat_cat ='$display_mat_cat' WHERE id = '$id_classe'");
			//$register_class = mysql_query("UPDATE classes SET classe='$reg_class_name', nom_complet='$reg_nom_complet', suivi_par='$reg_suivi_par', formule= '$reg_formule', format_nom='$reg_format', display_rang='$display_rang', display_address='$display_address', display_coef='$display_coef', display_mat_cat ='$display_mat_cat', display_nbdev ='$display_nbdev' WHERE id = '$id_classe'");

			//$register_class = mysql_query("UPDATE classes SET classe='$reg_class_name', nom_complet='$reg_nom_complet', suivi_par='$reg_suivi_par', formule= '$reg_formule', format_nom='$reg_format', display_rang='$display_rang', display_address='$display_address', display_coef='$display_coef', display_mat_cat ='$display_mat_cat', display_nbdev ='$display_nbdev',display_moy_gen='$display_moy_gen' WHERE id = '$id_classe'");

			$register_class = mysql_query("UPDATE classes SET classe='$reg_class_name',
													nom_complet='$reg_nom_complet',
													suivi_par='$reg_suivi_par',
													formule= '".html_entity_decode($reg_formule)."',
													format_nom='$reg_format',
													display_rang='$display_rang',
													display_address='$display_address',
													display_coef='$display_coef',
													display_mat_cat ='$display_mat_cat',
													display_nbdev ='$display_nbdev',
													display_moy_gen='$display_moy_gen',
													modele_bulletin_pdf='$modele_bulletin',
													rn_nomdev='$rn_nomdev',
													rn_toutcoefdev='$rn_toutcoefdev',
													rn_coefdev_si_diff='$rn_coefdev_si_diff',
													rn_datedev='$rn_datedev',
													rn_sign_chefetab='$rn_sign_chefetab',
													rn_sign_pp='$rn_sign_pp',
													rn_sign_resp='$rn_sign_resp',
													rn_sign_nblig='$rn_sign_nblig',
													rn_formule='$rn_formule',
                                                    ects_type_formation='".$ects_type_formation."',
                                                    ects_parcours='".$ects_parcours."',
                                                    ects_code_parcours='".$ects_code_parcours."',
                                                    ects_domaines_etude='".$ects_domaines_etude."',
                                                    ects_fonction_signataire_attestation='".$ects_fonction_signataire_attestation."'
												WHERE id = '$id_classe'");

			if (!$register_class) {
					$msg .= "Une erreur s'est produite lors de la modification de la classe.";
					} else {
					$msg .= "La classe a bien �t� modifi�e.";
			}
			// On enregistre les infos relatives aux cat�gories de mati�res
			$get_cat = mysql_query("SELECT id, nom_court, priority FROM matieres_categories");
			while ($row = mysql_fetch_array($get_cat, MYSQL_ASSOC)) {
				$reg_priority = $_POST['priority_'.$row["id"]];
				if (isset($_POST['moyenne_'.$row["id"]])) {$reg_aff_moyenne = 1;} else { $reg_aff_moyenne = 0;}
				if (!is_numeric($reg_priority)) $reg_priority = 0;
				if (!is_numeric($reg_aff_moyenne)) $reg_aff_moyenne = 0;

				//$test = mysql_result(mysql_query("select count(classe_id) FROM j_matieres_categories_classes WHERE (categorie_id = '" . $row["id"] . "' and classe_id = '" . $id_classe . "')"), 0);

				$res_test=mysql_query("select count(classe_id) FROM j_matieres_categories_classes WHERE (categorie_id = '" . $row["id"] . "' and classe_id = '" . $id_classe . "')");
				$test = mysql_result($res_test, 0);

				if ($test == 0) {
					// Pas d'entr�e... on cr��
					$res = mysql_query("INSERT INTO j_matieres_categories_classes SET classe_id = '" . $id_classe . "', categorie_id = '" . $row["id"] . "', priority = '" . $reg_priority . "', affiche_moyenne = '" . $reg_aff_moyenne . "'");
				} else {
					// Entr�e existante, on met � jour
					$res = mysql_query("UPDATE j_matieres_categories_classes SET priority = '" . $reg_priority . "', affiche_moyenne = '" . $reg_aff_moyenne . "' WHERE (classe_id = '" . $id_classe . "' and categorie_id = '" . $row["id"] . "')");
				}
				if (!$res) {
					$msg .= "<br/>Une erreur s'est produite lors de l'enregistrement des donn�es de cat�gorie.";
				}
			}

		} else {
		$msg .= "Veuillez pr�ciser le nom de la classe !";
		}
	} else {
		if ($reg_class_name) {
		//$register_class = mysql_query("INSERT INTO classes SET classe = '$reg_class_name', nom_complet = '$reg_nom_complet', suivi_par = '$reg_suivi_par', formule = '$reg_formule', format_nom = '$reg_format', display_rang = '$display_rang', display_address = '$display_address', display_coef = '$display_coef', display_mat_cat = '$display_mat_cat'");
		//$register_class = mysql_query("INSERT INTO classes SET classe = '$reg_class_name', nom_complet = '$reg_nom_complet', suivi_par = '$reg_suivi_par', formule = '$reg_formule', format_nom = '$reg_format', display_rang = '$display_rang', display_address = '$display_address', display_coef = '$display_coef', display_mat_cat = '$display_mat_cat', display_nbdev ='$display_nbdev'");
		$register_class = mysql_query("INSERT INTO classes SET classe = '$reg_class_name',
													nom_complet = '$reg_nom_complet',
													suivi_par = '$reg_suivi_par',
													formule = '$reg_formule',
													format_nom = '$reg_format',
													display_rang = '$display_rang',
													display_address = '$display_address',
													display_coef = '$display_coef',
													display_mat_cat = '$display_mat_cat',
													display_nbdev ='$display_nbdev',
													display_moy_gen='$display_moy_gen',
													modele_bulletin_pdf='$modele_bulletin',
													rn_nomdev='$rn_nomdev',
													rn_toutcoefdev='$rn_toutcoefdev',
													rn_coefdev_si_diff='$rn_coefdev_si_diff',
													rn_datedev='$rn_datedev',
													rn_sign_chefetab='$rn_sign_chefetab',
													rn_sign_pp='$rn_sign_pp',
													rn_sign_resp='$rn_sign_resp',
													rn_sign_nblig='$rn_sign_nblig',
													rn_formule='$rn_formule',
                                                    ects_type_formation='".$ects_type_formation."',
                                                    ects_parcours='".$ects_parcours."',
                                                    ects_code_parcours='".$ects_code_parcours."',
                                                    ects_domaines_etude='".$ects_domaines_etude."',
                                                    ects_fonction_signataire_attestation='".$ects_fonction_signataire_attestation."'
												");
		if (!$register_class) {
			$msg .= "Une erreur s'est produite lors de l'enregistrement de la nouvelle classe.";
		} else {
			$msg .= "La nouvelle classe a bien �t� enregistr�e.";
			$id_classe = mysql_insert_id();

			// On enregistre les infos relatives aux cat�gories de mati�res
			$get_cat = mysql_query("SELECT id, nom_court, priority FROM matieres_categories");
			while ($row = mysql_fetch_array($get_cat, MYSQL_ASSOC)) {
				$reg_priority = $_POST['priority_'.$row["id"]];
				if (isset($_POST['moyenne_'.$row["id"]])) {$reg_aff_moyenne = 1;} else { $reg_aff_moyenne = 0;}
				if (!is_numeric($reg_priority)) $reg_priority = 0;
				if (!is_numeric($reg_aff_moyenne)) $reg_aff_moyenne = 0;

				$res = mysql_query("INSERT INTO j_matieres_categories_classes SET classe_id = '" . $id_classe . "', categorie_id = '" . $row["id"] . "', priority = '" . $reg_priority . "', affiche_moyenne = '" . $reg_aff_moyenne . "'");

				if (!$res) {
					$msg .= "<br/>Une erreur s'est produite lors de l'enregistrement des donn�es de cat�gorie.";
				}
			}
		}

		} else {
		$msg .= "Veuillez pr�ciser le nom de la classe !";
		}
	}
}


$themessage  = 'Des informations ont �t� modifi�es. Voulez-vous vraiment quitter sans enregistrer ?';
//**************** EN-TETE *******************************
$titre_page = "Gestion des classes | Modifier les param�tres";
require_once("../lib/header.inc");
//**************** FIN EN-TETE ***************************

$id_class_prec=0;
$id_class_suiv=0;

$chaine_options_classes="";
if (isset($id_classe)) {
	// =================================
	// AJOUT: boireaus
	//$chaine_options_classes="";
	$sql="SELECT id, classe FROM classes ORDER BY classe";
	$res_class_tmp=mysql_query($sql);
	if(mysql_num_rows($res_class_tmp)>0){
		$id_class_prec=0;
		$id_class_suiv=0;
		$temoin_tmp=0;

		$cpt_classe=0;
		$num_classe=-1;

		while($lig_class_tmp=mysql_fetch_object($res_class_tmp)){
			if($lig_class_tmp->id==$id_classe){
				// Index de la classe dans les <option>
				$num_classe=$cpt_classe;

				$chaine_options_classes.="<option value='$lig_class_tmp->id' selected='true'>$lig_class_tmp->classe</option>\n";
				$temoin_tmp=1;
				if($lig_class_tmp=mysql_fetch_object($res_class_tmp)){
					$chaine_options_classes.="<option value='$lig_class_tmp->id'>$lig_class_tmp->classe</option>\n";
					$id_class_suiv=$lig_class_tmp->id;
				}
				else{
					$id_class_suiv=0;
				}
			}
			else {
				$chaine_options_classes.="<option value='$lig_class_tmp->id'>$lig_class_tmp->classe</option>\n";
			}

			if($temoin_tmp==0){
				$id_class_prec=$lig_class_tmp->id;
			}

			$cpt_classe++;
		}
	}
	// =================================
}

echo "<form action='".$_SERVER['PHP_SELF']."' name='form1' method='post'>\n";

echo "<p class=bold><a href='index.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
if($id_class_prec!=0){echo " | <a href='".$_SERVER['PHP_SELF']."?id_classe=$id_class_prec' onclick=\"return confirm_abandon (this, change, '$themessage')\">Classe pr�c�dente</a>";}

if($chaine_options_classes!="") {

	echo "<script type='text/javascript'>
	// Initialisation
	change='no';

	function confirm_changement_classe(thechange, themessage)
	{
		if (!(thechange)) thechange='no';
		if (thechange != 'yes') {
			document.form1.submit();
		}
		else{
			var is_confirmed = confirm(themessage);
			if(is_confirmed){
				document.form1.submit();
			}
			else{
				document.getElementById('id_classe').selectedIndex=$num_classe;
			}
		}
	}
</script>\n";


	echo " | <select name='id_classe' id='id_classe' onchange=\"confirm_changement_classe(change, '$themessage');\">\n";
	echo $chaine_options_classes;
	echo "</select>\n";
}

if($id_class_suiv!=0){echo " | <a href='".$_SERVER['PHP_SELF']."?id_classe=$id_class_suiv' onclick=\"return confirm_abandon (this, change, '$themessage')\">Classe suivante</a>";}

//=========================
// On ne propose l'infobulle de navigation que pour une classe d�j� existante.
$ouvrir_infobulle_nav="n";
if(isset($id_classe)) {
	$titre="Navigation";
	$texte="";
	$texte.="<img src='../images/icons/date.png' alt='' /> <a href='periodes.php?id_classe=$id_classe' onclick=\"return confirm_abandon (this, change, '$themessage')\">P�riodes</a><br />";
	$texte.="<img src='../images/icons/edit_user.png' alt='' /> <a href='classes_const.php?id_classe=$id_classe' onclick=\"return confirm_abandon (this, change, '$themessage')\">El�ves</a><br />";
	$texte.="<img src='../images/icons/document.png' alt='' /> <a href='../groupes/edit_class.php?id_classe=$id_classe' onclick=\"return confirm_abandon (this, change, '$themessage')\">Enseignements</a><br />";
	$texte.="<img src='../images/icons/document.png' alt='' /> <a href='../groupes/edit_class_grp_lot.php?id_classe=$id_classe' onclick=\"return confirm_abandon (this, change, '$themessage')\">config.simplifi�e</a><br />";
	//$texte.="<img src='../images/icons/configure.png' alt='' /> <a href='modify_nom_class.php?id_classe=$id_classe' onclick=\"return confirm_abandon (this, change, '$themessage')\">Param�tres</a>";
	
	$ouvrir_infobulle_nav=getSettingValue("ouvrir_infobulle_nav");
	
	if($ouvrir_infobulle_nav=="y") {
		$texte.="<div id='save_mode_nav' style='float:right; width:20px; height:20px;'><a href='#' onclick='modif_mode_infobulle_nav();return false;'><img src='../images/vert.png' width='16' height='16' /></a></div>\n";
	}
	else {
		$texte.="<div id='save_mode_nav' style='float:right; width:20px; height:20px;'><a href='#' onclick='modif_mode_infobulle_nav();return false;'><img src='../images/rouge.png' width='16' height='16' /></a></div>\n";
	}

	$texte.="<script type='text/javascript'>
	// <![CDATA[
	function modif_mode_infobulle_nav() {
		new Ajax.Updater($('save_mode_nav'),'classes_ajax_lib.php?mode=ouvrir_infobulle_nav',{method: 'get'});
	}
	//]]>
</script>\n";

	$tabdiv_infobulle[]=creer_div_infobulle('navigation_classe',$titre,"",$texte,"",14,0,'y','y','n','n');
	
	echo " | <a href='#' onclick=\"afficher_div('navigation_classe','y',-100,20);\"";
	echo ">";
	echo "Navigation";
	echo "</a>";
}
//=========================

echo "</p>\n";
echo "</form>\n";

if(getSettingValue('GepiAdminImprBulSettings')!='yes') {
	echo "<p><b>Remarque&nbsp;: </b>Connectez vous avec un compte ayant le statut \"scolarit�\" pour �diter les bulletins et avoir acc�s � d'autres param�tres d'affichage.</p>\n";
}

if (isset($id_classe)) {

	$call_nom_class = mysql_query("SELECT * FROM classes WHERE id = '$id_classe'");

	if(mysql_num_rows($call_nom_class)==0) {
		echo "<p>L'identifiant de classe '$id_classe' est inconnu.</p>\n";
		require("../lib/footer.inc.php");
		die();
	}

	$classe = mysql_result($call_nom_class, 0, 'classe');
	$nom_complet = mysql_result($call_nom_class, 0, 'nom_complet');
	$suivi_par = mysql_result($call_nom_class, 0, 'suivi_par');
	$formule = mysql_result($call_nom_class, 0, 'formule');
	$format_nom = mysql_result($call_nom_class, 0, 'format_nom');
	$display_rang = mysql_result($call_nom_class, 0, 'display_rang');
	$display_address = mysql_result($call_nom_class, 0, 'display_address');
	$display_coef = mysql_result($call_nom_class, 0, 'display_coef');
	$display_mat_cat = mysql_result($call_nom_class, 0, 'display_mat_cat');
	$display_nbdev = mysql_result($call_nom_class, 0, 'display_nbdev');
	$display_moy_gen = mysql_result($call_nom_class, 0, 'display_moy_gen');
	$modele_bulletin_pdf = mysql_result($call_nom_class, 0, 'modele_bulletin_pdf');

	// =========================
	// AJOUT: boireaus
	$rn_nomdev=mysql_result($call_nom_class, 0, 'rn_nomdev');
	$rn_toutcoefdev=mysql_result($call_nom_class, 0, 'rn_toutcoefdev');
	$rn_coefdev_si_diff=mysql_result($call_nom_class, 0, 'rn_coefdev_si_diff');
	$rn_datedev=mysql_result($call_nom_class, 0, 'rn_datedev');
	$rn_formule=mysql_result($call_nom_class, 0, 'rn_formule');
	$rn_sign_chefetab=mysql_result($call_nom_class, 0, 'rn_sign_chefetab');
	$rn_sign_pp=mysql_result($call_nom_class, 0, 'rn_sign_pp');
	$rn_sign_resp=mysql_result($call_nom_class, 0, 'rn_sign_resp');
	$rn_sign_nblig=mysql_result($call_nom_class, 0, 'rn_sign_nblig');
	// =========================

    //=========================
    // Ajout : Module ECTS
    $ects_type_formation = mysql_result($call_nom_class, 0, 'ects_type_formation');
    $ects_parcours = mysql_result($call_nom_class, 0, 'ects_parcours');
    $ects_code_parcours = mysql_result($call_nom_class, 0, 'ects_code_parcours');
    $ects_fonction_signataire_attestation = mysql_result($call_nom_class, 0, 'ects_fonction_signataire_attestation');
    $ects_domaines_etude = mysql_result($call_nom_class, 0, 'ects_domaines_etude');

} else {
	$classe = '';
	$nom_complet = '';
	$suivi_par = '';
	$formule = '';
	//$format_nom = 'np';
	$format_nom = 'cni';
	$display_rang = 'n';
	$display_address = 'n';
	$display_coef = 'n';
	$display_mat_cat = 'n';
	$display_nbdev = 'n';
	$display_moy_gen = 'n';
	$modele_bulletin_pdf = NULL;

	// =========================
	// AJOUT: boireaus
	$rn_nomdev='n';
	$rn_toutcoefdev='n';
	$rn_coefdev_si_diff='n';
	$rn_datedev='n';
	$rn_formule='';
	$rn_sign_chefetab='n';
	$rn_sign_pp='n';
	$rn_sign_resp='n';
	$rn_sign_nblig=3;
	// =========================

    // Mod ECTS
    $ects_type_formation = '';
    $ects_parcours = '';
    $ects_code_parcours = '';
    $ects_fonction_signataire_attestation = '';
    $ects_domaines_etude = '';
}

?>
<form enctype="multipart/form-data" action="modify_nom_class.php" method="post">
<?php
echo add_token_field();
?>
<p>Nom court de la classe&nbsp;: <input type=text size=30 name=reg_class_name value = "<?php echo $classe; ?>" onchange='changement()' /></p>
<p>Nom complet de la classe&nbsp;: <input type=text size=50 name=reg_nom_complet value = "<?php echo $nom_complet; ?>"  onchange='changement()' /></p>
<p>Pr�nom et nom du signataire des bulletins<?php if ($gepiSettings['active_mod_ects'] == "y") echo " et des attestations ECTS" ?> (chef d'�tablissement ou son repr�sentant)&nbsp;: <br /><input type=text size=30 name=reg_suivi_par value = "<?php echo $suivi_par; ?>"  onchange='changement()' /></p>
<?php
if ($gepiSettings['active_mod_ects'] == "y") {
    ?>
<p>Fonction du signataire sus-nomm� (ex.: "Proviseur")&nbsp;: <br /><input type="text" size="40" name="ects_fonction_signataire_attestation" value="<?php echo $ects_fonction_signataire_attestation;?>" onchange='changement()' /></p>
<?php
}
    ?>
<p>Formule � ins�rer sur les bulletins (cette formule sera suivie des nom et pr�nom de la personne d�sign�e ci_dessus&nbsp;:<br /> <input type=text size=80 name=reg_formule value = "<?php echo $formule; ?>"  onchange='changement()' /></p>

<p><b>Formatage de l'identit� des professeurs pour les bulletins&nbsp;:</b>
<br /><br />
<input type="radio" name="reg_format" id='reg_format_np' value="<?php echo "np"; ?>" <?php if ($format_nom=="np") echo " checked "; ?> onchange='changement()' />
<label for='reg_format_np' style='cursor: pointer;'>Nom Pr�nom (Durand Albert)</label>
<br />
<input type="radio" name="reg_format" id='reg_format_pn' value="<?php echo "pn"; ?>" <?php if ($format_nom=="pn") echo " checked "; ?> onchange='changement()' />
<label for='reg_format_pn' style='cursor: pointer;'>Pr�nom Nom (Albert Durand)</label>
<br />
<input type="radio" name="reg_format" id='reg_format_in' value="<?php echo "in"; ?>" <?php   if ($format_nom=="in") echo " checked "; ?> onchange='changement()' />
<label for='reg_format_in' style='cursor: pointer;'>Initiale-Pr�nom Nom (A. Durand)</label>
<br />
<input type="radio" name="reg_format" id='reg_format_ni' value="<?php echo "ni"; ?>" <?php   if ($format_nom=="ni") echo " checked "; ?> onchange='changement()' />
<label for='reg_format_ni' style='cursor: pointer;'>Initiale-Pr�nom Nom (Durand A.)</label>
<br />
<input type="radio" name="reg_format" id='reg_format_cnp' value="<?php echo "cnp"; ?>" <?php   if ($format_nom=="cnp") echo " checked "; ?> onchange='changement()' />
<label for='reg_format_cnp' style='cursor: pointer;'>Civilit� Nom Pr�nom (M. Durand Albert)</label>
<br />
<input type="radio" name="reg_format" id='reg_format_cpn' value="<?php echo "cpn"; ?>" <?php   if ($format_nom=="cpn") echo " checked "; ?> onchange='changement()' />
<label for='reg_format_cpn' style='cursor: pointer;'>Civilit� Pr�nom Nom (M. Albert Durand)</label>
<br />
<input type="radio" name="reg_format" id='reg_format_cin' value="<?php echo "cin"; ?>" <?php   if ($format_nom=="cin") echo " checked "; ?> onchange='changement()' />
<label for='reg_format_cin' style='cursor: pointer;'>Civ. initiale-Pr�nom Nom (M. A. Durand)</label>
<br />
<input type="radio" name="reg_format" id='reg_format_cni' value="<?php echo "cni"; ?>" <?php   if ($format_nom=="cni") echo " checked "; ?> onchange='changement()' />
<label for='reg_format_cni' style='cursor: pointer;'>Civ. Nom initiale-Pr�nom  (M. Durand A.)</label>

<input type=hidden name=is_posted value=1 />
<?php if (isset($id_classe)) {echo "<input type=hidden name=id_classe value=$id_classe />";} ?>
<br />
<br />
<!-- ========================================= -->
<table style="border: 0;" cellpadding="5" cellspacing="5">
<tr>
	<td colspan='3'>
	  <h2><b>Param�tres g�n�raux&nbsp;: </b></h2>
	</td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
    <label for='display_mat_cat' style='cursor: pointer;'>Afficher les cat�gories de mati�res sur le bulletin (HTML), les relev�s de notes (HTML), et les outils de visualisation&nbsp;:</label>
    </td>
    <td><input type="checkbox" value="y" name="display_mat_cat" id="display_mat_cat"  <?php   if ($display_mat_cat=="y") echo " checked "; ?> onchange='changement()' />
    </td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
	<td style="font-variant: small-caps;">
	Param�trage des cat�gories de mati�re pour cette classe (uniquement si case ci-dessus coch�e)
	</td>
	<td>
		<table style='border: 1px solid black;'>
		<tr>
			<td style='width: auto;'>Cat�gorie</td><td style='width: 100px; text-align: center;'>Priorit� d'affichage</td><td style='width: 100px; text-align: center;'>Afficher la moyenne sur le bulletin</td>
		</tr>
		<?php
		$get_cat = mysql_query("SELECT id, nom_court, priority FROM matieres_categories");
		while ($row = mysql_fetch_array($get_cat, MYSQL_ASSOC)) {
			// Pour la cat�gorie, on r�cup�re les infos d�j� enregistr�es pour la classe
			if (isset($id_classe)) {
				$infos = mysql_fetch_object(mysql_query("SELECT priority, affiche_moyenne FROM j_matieres_categories_classes WHERE (categorie_id = '" . $row["id"] ."' and classe_id = '" . $id_classe . "')"));
			} else {
				$infos = false;
			}
			if (!$infos) {
				$current_priority = $row["priority"];
				$current_affiche_moyenne = "0";
			} else {
				$current_priority = $infos->priority;
				$current_affiche_moyenne = $infos->affiche_moyenne;
			}

			echo "<tr>\n";
			echo "<td style='padding: 5px;'>".$row["nom_court"]."</td>\n";
			echo "<td style='padding: 5px; text-align: center;'>\n";
					echo "<select name='priority_".$row["id"]."' size='1' onchange='changement()'>\n";
					for ($i=0;$i<11;$i++) {
						echo "<option value='$i'";
						if ($current_priority == $i) echo " SELECTED";
						echo ">$i</option>\n";
					}
					echo "</select>\n";
			echo "</td>\n";
			echo "<td style='padding: 5px; text-align: center;'>\n";
				echo "<input type='checkbox' name='moyenne_".$row["id"]."'";
				if ($current_affiche_moyenne == '1') echo " CHECKED";
				echo " onchange='changement()' />\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		?>
		</table>
</td>
</tr>
<!-- ========================================= -->
<tr>
	<td colspan='3'>
	  <h2><b>Param�tres bulletin HTML&nbsp;: </b></h2>
	</td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps; width: 35%;">
    <label for='display_rang' style='cursor: pointer;'>Afficher sur le bulletin le rang de chaque �l�ve&nbsp;:</label>
    </td>
    <td><input type="checkbox" value="y" name="display_rang" id="display_rang"  <?php   if ($display_rang=="y") echo " checked "; ?>  onchange='changement()' />
    </td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
    <label for='display_address' style='cursor: pointer;'>Afficher le bloc adresse du responsable de l'�l�ve&nbsp;:</label>
    </td>
    <td><input type="checkbox" value="y" name="display_address" id="display_address"  <?php   if ($display_address=="y") echo " checked "; ?>  onchange='changement()' />
    </td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
    <label for='display_coef' style='cursor: pointer;'>Afficher les coefficients des mati�res (uniquement si au moins un coef diff�rent de 0)&nbsp;:</label>
    </td>
    <td><input type="checkbox" value="y" name="display_coef" id="display_coef"  <?php   if ($display_coef=="y") echo " checked "; ?>  onchange='changement()' />
    </td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
    <label for='display_moy_gen' style='cursor: pointer;'>Afficher les moyennes g�n�rales sur les bulletins (uniquement si au moins un coef diff�rent de 0)&nbsp;:</label>
    </td>
    <td><input type="checkbox" value="y" name="display_moy_gen" id="display_moy_gen"  <?php   if ($display_moy_gen=="y") echo " checked "; ?> onchange='changement()' />
    </td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
    <label for='display_nbdev' style='cursor: pointer;'>Afficher le nombre de devoirs sur le bulletin&nbsp;:</label>
    </td>
    <td><input type="checkbox" value="y" name="display_nbdev" id="display_nbdev"  <?php   if ($display_nbdev=="y") echo " checked "; ?> onchange='changement()' />
    </td>
</tr>
<!-- ========================================= -->
<tr>
	<td colspan='3'>
	  <h2><b>Param�tres bulletin PDF&nbsp;: </b></h2>
	</td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
	<td style="font-variant: small-caps;">
	   S�lectionner le mod�le de bulletin pour l'impression en PDF&nbsp;:
	</td>
	<td><?php
	    // Pour la classe, quel est le mod�le de bulletin d�ja selectionn�
	    $quel_modele=$modele_bulletin_pdf;


		// s�lection des mod�le des bulletins.
	    //$requete_modele = mysql_query('SELECT id_model_bulletin, nom_model_bulletin FROM '.$prefix_base.'model_bulletin ORDER BY '.$prefix_base.'model_bulletin.nom_model_bulletin ASC');
		$requete_modele = mysql_query("SELECT id_model_bulletin, valeur as nom_model_bulletin FROM ".$prefix_base."modele_bulletin WHERE nom='nom_model_bulletin' ORDER BY ".$prefix_base."modele_bulletin.valeur ASC;");
		if(mysql_num_rows($requete_modele)==0) {
			echo "<p style='color:red'>ANOMALIE&nbsp;: Il n'existe aucun mod�le de bulletin PDF.";
			if($_SESSION['login']=='administrateur') {
				echo "Vous devriez effectuer/forcer une <a href='../utilitaires/maj.php'>mise � jour de la base</a> pour corriger.<br />Prenez tout de m�me soin de v�rifier que personne d'autre que vous n'est connect�.\n";
			}
			else {
				echo "Contactez l'administrateur pour qu'il effectue une mise � jour de la base.\n";
			}
			echo "</p>\n";
		}
		else {
			//echo $quel_modele;
			echo "<select tabindex=\"5\" name=\"modele_bulletin\" onchange='changement()'>";
			if ($quel_modele == NULL) {
			echo "<option value=\"NULL\" selected=\"selected\" >Aucun mod�le de s�lectionn�</option>";
			}
			while($donner_modele = mysql_fetch_array($requete_modele)) {
				echo "<option value=\"".$donner_modele['id_model_bulletin']."\"";
				if($quel_modele==$donner_modele['id_model_bulletin']) {
					echo "selected=\"selected\"";
				}
				echo ">".ucfirst($donner_modele['nom_model_bulletin'])."</option>\n";
			}
			echo "</select>\n";
		}
		?>
	</td>
</tr>
<!-- ========================================= -->
<tr>
	<td colspan='3'>
	  <h2><b>Param�tres des relev�s de notes&nbsp;: </b></h2>
	</td>
</tr>
<!--
Afficher le nom des devoirs.
Afficher tous les coefficients des devoirs.
Afficher les coefficients des devoirs si des coefficients diff�rents
> > sont pr�sents.
Afficher les dates des devoirs.
> >
> >Et
Afficher un texte... (correspondant � ta demande)
> >Et encore
Afficher une case pour la signature des parents/responsables
Afficher une case pour la signature du prof principal
Afficher une case pour la signature du chef d'�tablissement
-->
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
	<label for='rn_nomdev' style='cursor: pointer;'>Afficher le nom des devoirs&nbsp;:</label></td>
    <td><input type="checkbox" value="y" name="rn_nomdev" id="rn_nomdev"  <?php   if ($rn_nomdev=="y") echo " checked "; ?> onchange='changement()' /></td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
	<label for='rn_toutcoefdev' style='cursor: pointer;'>Afficher tous les coefficients des devoirs&nbsp;:</label></td>
    <td><input type="checkbox" value="y" name="rn_toutcoefdev" id="rn_toutcoefdev"  <?php   if ($rn_toutcoefdev=="y") echo " checked "; ?> onchange='changement()' /></td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
	<label for='rn_coefdev_si_diff' style='cursor: pointer;'>Afficher les coefficients des devoirs si des coefficients diff�rents sont pr�sents&nbsp;:</label></td>
    <td><input type="checkbox" value="y" name="rn_coefdev_si_diff" id="rn_coefdev_si_diff"  <?php   if ($rn_coefdev_si_diff=="y") echo " checked "; ?> onchange='changement()' /></td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
	<label for='rn_datedev' style='cursor: pointer;'>Afficher les dates des devoirs&nbsp;:</label></td>
    <td><input type="checkbox" value="y" name="rn_datedev" id="rn_datedev"  <?php   if ($rn_datedev=="y") echo " checked "; ?> onchange='changement()' /></td>
</tr>

<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
	<td style="font-variant: small-caps;">Formule/Message � ins�rer sous le relev� de notes&nbsp;:</td>
	<td><input type=text size=40 name="rn_formule" value="<?php echo $rn_formule; ?>" onchange='changement()' /></td>
</tr>

<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
	<label for='rn_sign_chefetab' style='cursor: pointer;'>Afficher une case pour la signature du chef d'�tablissement&nbsp;:</label></td>
    <td><input type="checkbox" value="y" name="rn_sign_chefetab" id="rn_sign_chefetab"  <?php   if ($rn_sign_chefetab=="y") echo " checked "; ?> onchange='changement()' /></td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
	<label for='rn_sign_pp' style='cursor: pointer;'>Afficher une case pour la signature du prof principal&nbsp;:</label></td>
    <td><input type="checkbox" value="y" name="rn_sign_pp" id="rn_sign_pp"  <?php   if ($rn_sign_pp=="y") echo " checked "; ?> onchange='changement()' /></td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">
	<label for='rn_sign_resp' style='cursor: pointer;'>Afficher une case pour la signature des parents/responsables&nbsp;:</label></td>
    <td><input type="checkbox" value="y" name="rn_sign_resp" id="rn_sign_resp"  <?php   if ($rn_sign_resp=="y") echo " checked "; ?>  onchange='changement()' /></td>
</tr>

<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">Nombre de lignes pour la signature&nbsp;:</td>
    <td><input type="text" name="rn_sign_nblig" value="<?php echo $rn_sign_nblig;?>" onchange='changement()' /></td>
</tr>

<?php
if ($gepiSettings['active_mod_ects'] == "y") {
    ?>
<tr>
	<td colspan='3'>
	  <h2><b>Param�tres des attestations ECTS&nbsp;: </b></h2>
	</td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">Type de formation (ex: "Classe pr�paratoire scientifique")&nbsp;:</td>
    <td><input type="text" size="40" name="ects_type_formation" value="<?php echo $ects_type_formation;?>" onchange='changement()' /></td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">Nom complet du parcours de formation (ex: "BCPST (Biologie, Chimie, Physique et Sciences de la Terre)")&nbsp;:</td>
    <td><input type="text" size="40" name="ects_parcours" value="<?php echo $ects_parcours;?>" onchange='changement()' /></td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">Nom cours du parcours de formation (ex: "BCPST")&nbsp;:</td>
    <td><input type="text" size="40" name="ects_code_parcours" value="<?php echo $ects_code_parcours;?>" onchange='changement()' /></td>
</tr>
<tr>
	<td>&nbsp;&nbsp;&nbsp;</td>
    <td style="font-variant: small-caps;">Domaines d'�tude (ex: "Biologie, Chimie, Physique, Math�matiques, Sciences de la Terre")&nbsp;:</td>
    <td><input type="text" size="40" name="ects_domaines_etude" value="<?php echo $ects_domaines_etude;?>" onchange='changement()' /></td>
</tr>

    <?php
} else {
?>
<input type="hidden" name="ects_type_formation" value="<?php echo $ects_type_formation;?>" />
<input type="hidden" name="ects_parcours" value="<?php echo $ects_parcours;?>" />
<input type="hidden" name="ects_code_parcours" value="<?php echo $ects_code_parcours;?>" />
<input type="hidden" name="ects_domaines_etude" value="<?php echo $ects_domaines_etude;?>" />
<input type="hidden" name="ects_fonction_signataire_attestation" value="<?php echo $ects_fonction_signataire_attestation;?>" />
<?php } ?>


</table>
<center><input type=submit value="Enregistrer" style="margin-top: 30px; margin-bottom: 100px;" /></center>
</form>
<?php

if($ouvrir_infobulle_nav=='y') {
	echo "<script type='text/javascript'>
	setTimeout(\"afficher_div('navigation_classe','y',-100,20);\",1000)
</script>\n";
}

require("../lib/footer.inc.php");

?>