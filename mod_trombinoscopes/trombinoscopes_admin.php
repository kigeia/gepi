<?php
/*
*$Id$
*
 * Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Christian Chapel
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


/**
 * Param�trage du trombinoscope
 *
 * @param $_POST['activer'] activation/d�sactivation
 * @param $_POST['num_aid_trombinoscopes']
 * @param $_POST['activer_personnels']
 * @param $_POST['activer_redimensionne']
 * @param $_POST['activer_rotation']
 * @param $_POST['l_max_aff_trombinoscopes']
 * @param $_POST['h_max_imp_trombinoscopes']
 * @param $_POST['l_max_imp_trombinoscopes']
 * @param $_POST['h_max_imp_trombinoscopes']
 * @param $_POST['nb_col_imp_trombinoscopes']
 * @param $_POST['l_resize_trombinoscopes']
 * @param $_POST['h_resize_trombinoscopes']
 * @param $_POST['sousrub']
 * @param $_POST['supprime']
 * @param $_POST['is_posted']
 *
 * @return $accessibilite
 * @return $titre_page
 * @return $niveau_arbo
 * @return $gepiPathJava
 * @return $msg
 * @return $repertoire
 * @return $post_reussi
 *
 */

$accessibilite="y";
$titre_page = "Gestion du module trombinoscope";
$niveau_arbo = 1;
$gepiPathJava="./..";
$post_reussi=FALSE;


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
// Check access
if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

/******************************************************************
 *    Enregistrement des variables pass�es en $_POST si besoin
 ******************************************************************/
$msg = '';
if(isset($_POST['is_posted'])) {
	check_token();

	if (isset($_POST['num_aid_trombinoscopes'])) {
		if ($_POST['num_aid_trombinoscopes']!='') {
			if (!saveSetting("num_aid_trombinoscopes", $_POST['num_aid_trombinoscopes']))
					$msg = "Erreur lors de l'enregistrement du param�tre num_aid_trombinoscopes !";
		} else {
			$del_num_aid_trombinoscopes = mysql_query("delete from setting where NAME='num_aid_trombinoscopes'");
			$gepiSettings['num_aid_trombinoscopes']="";
		}
	}
	if (isset($_POST['activer'])) {
		if (!saveSetting("active_module_trombinoscopes", $_POST['activer']))
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation !";
		if (!cree_repertoire_multisite())
		$msg = "Erreur lors de la cr�ation du r�pertoire photos de l'�tablissement !";
	}
	
	if (isset($_POST['activer_personnels'])) {
		if (!saveSetting("active_module_trombino_pers", $_POST['activer_personnels']))
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation du trombinoscope des personnels !";
	}
	
	if (isset($_POST['activer_redimensionne'])) {
		if (!saveSetting("active_module_trombinoscopes_rd", $_POST['activer_redimensionne']))
				$msg = "Erreur lors de l'enregistrement du param�tre de redimenssionement des photos !";
	}
	if (isset($_POST['activer_rotation'])) {
		if (!saveSetting("active_module_trombinoscopes_rt", $_POST['activer_rotation']))
				$msg = "Erreur lors de l'enregistrement du param�tre rotation des photos !";
	}
	if (isset($_POST['l_max_aff_trombinoscopes'])) {
		if (!saveSetting("l_max_aff_trombinoscopes", $_POST['l_max_aff_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du param�tre largeur maximum !";
	}
	if (isset($_POST['h_max_aff_trombinoscopes'])) {
		if (!saveSetting("h_max_aff_trombinoscopes", $_POST['h_max_aff_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du param�tre hauteur maximum !";
	}
	if (isset($_POST['l_max_imp_trombinoscopes'])) {
		if (!saveSetting("l_max_imp_trombinoscopes", $_POST['l_max_imp_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du param�tre largeur maximum !";
	}
	if (isset($_POST['h_max_imp_trombinoscopes'])) {
		if (!saveSetting("h_max_imp_trombinoscopes", $_POST['h_max_imp_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du param�tre hauteur maximum !";
	}
	
	if (isset($_POST['nb_col_imp_trombinoscopes'])) {
		if (!saveSetting("nb_col_imp_trombinoscopes", $_POST['nb_col_imp_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du nombre de colonnes sur les trombinos imprim�s !";
	}
	
	if (isset($_POST['l_resize_trombinoscopes'])) {
		if (!saveSetting("l_resize_trombinoscopes", $_POST['l_resize_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du param�tre l_resize_trombinoscopes !";
	}
	if (isset($_POST['h_resize_trombinoscopes'])) {
		if (!saveSetting("h_resize_trombinoscopes", $_POST['h_resize_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du param�tre h_resize_trombinoscopes !";
	}

	if (isset($_POST['sousrub'])) {
		// suppression
		if (isset ($_POST['supprime']) && $_POST['supprime']="yes"){
			if($_POST['sousrub']=="dp"){
			// suppression des photos du personnel
			$msg = efface_photos("personnels");
			if ($msg == FALSE)
				$msg = "Erreur lors de la suppression des photos du personnel";
			} else if ($_POST['sousrub']=="de"){
			// suppression des photos des �l�ves
			$msg = efface_photos("eleves");
			if ($msg == FALSE)
			$msg = "Erreur lors de la suppression des photos des �l�ves";
			}
		}
	}

	if(isset ($_POST['voirPerso']) && $_POST['voirPerso']=="yes"){
		// Affichage du personnel sans photo
		if (!recherche_personnel_sans_photo()){
		$msg = "Erreur lors de la s�lection du personnel sans photo";
		}else{
		$personnel_sans_photo=recherche_personnel_sans_photo();
		$msg.="liste des personnels sans photo en bas de page <br/>";
		}
	}
	
	if (isset ($_POST['voirEleve']) && $_POST['voirEleve']=="yes"){
	// Affichage des �l�ves sans photo
	if (!recherche_eleves_sans_photo()){
		$msg = "Erreur lors de la s�lection des �l�ves sans photo";
	}else{
	$eleves_sans_photo=recherche_eleves_sans_photo();
	$msg.="liste des �l�ves sans photo en bas de page";
	}
	}

}

if (isset($_POST['is_posted']) and ($msg=='')) {
  $msg = "Les modifications ont �t� enregistr�es !";
  $post_reussi=TRUE;
}






if (isset($_POST['action']) and ($_POST['action'] == 'upload'))  {
	check_token();
  // Le t�l�chargement s'est-il bien pass� ?
  $sav_file = isset($_FILES["nom_du_fichier"]) ? $_FILES["nom_du_fichier"] : NULL;
  if ($sav_file){
	// copie du fichier vers /temp
	$dirname ="../temp";
	$reponse=telecharge_fichier($sav_file,$dirname,'application/zip',"zip" );
	if ($reponse!="ok"){
	  $msg = $reponse;
	}else{
	  // d�zipage du fichier
	  $reponse = dezip_PclZip_fichier($dirname."/".$sav_file['name'],$dirname,1);
	  if ($reponse!="ok"){
		$msg = $reponse;
	  }else{
		//suppression du fichier .zip
		if (!@unlink ($dirname."/".$_FILES["nom_du_fichier"]['name']))
		  $msg .= "Erreur lors de la suppression de ".$dirname."/".$_FILES["nom_du_fichier"];

		// copy des fichiers vers /photos
		if (isset($GLOBALS['multisite']) AND $GLOBALS['multisite'] == 'y') {
			  // On r�cup�re le RNE de l'�tablissement
		  if (!$repertoire=getSettingValue("gepiSchoolRne"))
			return ("Erreur lors de la r�cup�ration du dossier �tablissement.");
		} else {
		  $repertoire="";
		}
		if ($repertoire!="")
		  $repertoire.="/";
		$repertoire="../photos/".$repertoire;
		//El�ves
		$folder = $dirname."/photos/eleves/";
		$dossier = opendir($folder);
		while ($Fichier = readdir($dossier)) {
		  if ($Fichier != "." && $Fichier != "..") {
			$source=$folder.$Fichier;
			if ($Fichier != "index.html") {
			  $dest=$repertoire."eleves/".$Fichier;
			  if (isset ($_POST["ecraser"]) && ($_POST["ecraser"]="yes")){
				@copy($source, $dest);
			  }else{
				if (!is_file($dest))
				  @copy($source, $dest);
			  }
			}
			if (!@unlink ($source))
			  $msg .= "Erreur lors de la suppression de ".$source;
		  }
		}
		closedir($dossier);
		if (!rmdir ($folder))
			  $msg .= "Erreur lors de la suppression de ".$folder;
		//Personnels
		$folder = $dirname."/photos/personnels/";
		$dossier = opendir($folder);
		while ($Fichier = readdir($dossier)) {
		  $source=$folder.$Fichier;
		  if ($Fichier != "." && $Fichier != "..") {
			if ($Fichier != "index.html") {
			  $dest=$repertoire."personnels/".$Fichier;
			  if (isset ($_POST["ecraser"]) && ($_POST["ecraser"]="yes")){
				@copy($source, $dest);
			  }else{
				if (!is_file($dest))
				  @copy($source, $dest);
			  }
			}
			if (!@unlink ($source))
			  $msg .= "Erreur lors de la suppression de ".$source;
		  }
		}
		closedir($dossier);
		if (!rmdir ($folder))
			  $msg .= "Erreur lors de la suppression de ".$folder;
	  }
	  $folder = $dirname."/photos/";
	  $dossier = opendir($folder);
	  while ($Fichier = readdir($dossier)) {
		if ($Fichier != "." && $Fichier != "..") {
		  $source=$folder."/".$Fichier;
		  if (!@unlink ($source))
			$msg .= "Erreur lors de la suppression de ".$source;
		}
	  }
	  closedir($dossier);
	  if (!rmdir ($folder))
			$msg .= "Erreur lors de la suppression de ".$folder;
	}
  }
  if ($msg==""){
	$msg= "La restauration s'est bien d�roul�e";
	$post_reussi=TRUE;
  }
}










// header
//$titre_page = "Gestion du module trombinoscope";


// En multisite, on ajoute le r�pertoire RNE
if (isset($GLOBALS['multisite']) AND $GLOBALS['multisite'] == 'y') {
	  // On r�cup�re le RNE de l'�tablissement
  $repertoire=getSettingValue("gepiSchoolRne")."/";
}else{
  $repertoire="";
}

/****************************************************************
                     HAUT DE PAGE
****************************************************************/

// ====== Inclusion des balises head et du bandeau =====
include_once("../lib/header_template.inc");

if (!suivi_ariane($_SERVER['PHP_SELF'],$titre_page))
		echo "erreur lors de la cr�ation du fil d'ariane";
/****************************************************************
			FIN HAUT DE PAGE
****************************************************************/

if (getSettingValue("GepiAccesModifMaPhotoEleve")=='yes') {

  $req_trombino = mysql_query("select indice_aid, nom from aid_config order by nom");
  $nb_aid = mysql_num_rows($req_trombino);
  $i = 0;
  for($i = 0;$i < $nb_aid;$i++){
	  $aid_trouve[$i]["indice"]= mysql_result($req_trombino,$i,'indice_aid');
	  $aid_trouve[$i]["nom"]= mysql_result($req_trombino,$i,'nom');
	  if (getSettingValue("num_aid_trombinoscopes")==$aid_trouve[$i]["indice"]){
		$aid_trouve[$i]["selected"]= TRUE;
		echo getSettingValue("num_aid_trombinoscopes")." : ".$aid_trouve[$i]["indice"];
	  }else {
		$aid_trouve[$i]["selected"]= FALSE;

	  }
  }
}


/*
 * TODO : 
 * <?php if ( $sousrub === 've' ) {
 * }
 *
 * if ( $sousrub === 'vp' ) {
 * }
 *
 * if ( $sousrub === 'de' ) {
 * }
 *
 * if ( $sousrub === 'dp' ) {
 * }
 *
 * if ( $sousrub === 'deok' ) {
 * }
 *
 * if ( $sousrub === 'dpok' ) {
 * }
 */

/*require_once("../lib/header.inc");
?>

<p class='bold'><a href="../accueil_modules.php"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a></p>

<h2>Configuration g�n�rale</h2>
<i>La d�sactivation du module trombinoscope n'entra�ne aucune suppression des donn�es. Lorsque le module est d�sactiv�, il n'y a pas d'acc�s au module.</i>
<br />
<form action="trombinoscopes_admin.php" name="form1" method="post">
<p><strong>El�ves&nbsp;:</strong></p>
<blockquote>
<input type="radio" name="activer" id='activer_y' value="y" <?php if (getSettingValue("active_module_trombinoscopes")=='y') echo " checked='checked'"; ?>  />
<label for='activer_y' style='cursor:pointer'>&nbsp;Activer le module trombinoscope</label><br />
<input type="radio" name="activer" id='activer_n' value="n" <?php
	if (getSettingValue("active_module_trombinoscopes")!='y'){echo " checked='checked'";}
?>  /><label for='activer_n' style='cursor:pointer'>&nbsp;D�sactiver le module trombinoscope</label>
<input type="hidden" name="is_posted" value="1" />
</blockquote>

<p><strong>Personnels&nbsp;:</strong></p>
<blockquote>
<input type="radio" name="activer_personnels" id='activer_personnels_y' value="y" <?php if (getSettingValue("active_module_trombino_pers")=='y') echo " checked='checked'"; ?>  /><label for='activer_personnels_y' style='cursor:pointer'>&nbsp;Activer le module trombinoscope des personnels</label><br />
<input type="radio" name="activer_personnels" id='activer_personnels_n' value="n" <?php
	if (getSettingValue("active_module_trombino_pers")!='y'){echo " checked='checked'";}
?>  /><label for='activer_personnels_n' style='cursor:pointer'>&nbsp;D�sactiver le module trombinoscope des personnels</label>
</blockquote>

<br />

<h2>Configuration d'affichage et de stockage</h2>
&nbsp;&nbsp;&nbsp;&nbsp;<i>Les valeurs ci-dessous vous servent au param�trage des valeurs maxi des largeurs et des hauteurs.</i><br />
<span style="font-weight: bold;">Pour l'�cran</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;largeur maxi <input name="l_max_aff_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("l_max_aff_trombinoscopes"); ?>" />&nbsp;
hauteur maxi&nbsp;<input name="h_max_aff_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("h_max_aff_trombinoscopes"); ?>" />
<br /><span style="font-weight: bold;">Pour l'impression</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;largeur maxi <input name="l_max_imp_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("l_max_imp_trombinoscopes"); ?>" />&nbsp;
hauteur maxi&nbsp;<input name="h_max_imp_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("h_max_imp_trombinoscopes"); ?>" />&nbsp;Nombre de colonnes&nbsp;<input name="nb_col_imp_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("nb_col_imp_trombinoscopes"); ?>" />

<br /><span style="font-weight: bold;">Pour le stockage sur le serveur</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;largeur <input name="l_resize_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("l_resize_trombinoscopes"); ?>" />&nbsp;
hauteur &nbsp;<input name="h_resize_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("h_resize_trombinoscopes"); ?>" />

<br />
<h2>Configuration du redimensionnement des photos</h2>
<i>La d�sactivation du redimensionnement des photos n'entra�ne aucune suppression des donn�es. Lorsque le syst�me de redimensionnement est d�sactiv�, les photos transfer�es sur le site ne seront pas r�duites en <?php echo getSettingValue("l_resize_trombinoscopes");?>x<?php echo getSettingValue("h_resize_trombinoscopes");?>.</i>
<br /><br />
<input type="radio" name="activer_redimensionne" id="activer_redimensionne_y" value="y" <?php if (getSettingValue("active_module_trombinoscopes_rd")=='y') echo " checked='checked'"; ?> /><label for='activer_redimensionne_y' style='cursor:pointer'>&nbsp;Activer le redimensionnement des photos en <?php echo getSettingValue("l_resize_trombinoscopes");?>x<?php echo getSettingValue("h_resize_trombinoscopes");?></label><br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Remarque</b> attention GD doit �tre actif sur le serveur de GEPI pour utiliser le redimensionnement.<br />
<input type="radio" name="activer_redimensionne" id="activer_redimensionne_n" value="n" <?php if (getSettingValue("active_module_trombinoscopes_rd")=='n') echo " checked='checked'"; ?> /><label for='activer_redimensionne_n' style='cursor:pointer'>&nbsp;D�sactiver le redimensionnement des photos</label>
<ul><li>Rotation de l'image : <input name="activer_rotation" value="" type="radio" <?php if (getSettingValue("active_module_trombinoscopes_rt")=='') { ?>checked='checked'<?php } ?> /> 0�
<input name="activer_rotation" value="90" type="radio" <?php if (getSettingValue("active_module_trombinoscopes_rt")=='90') { ?>checked='checked'<?php } ?> /> 90�
<input name="activer_rotation" value="180" type="radio" <?php if (getSettingValue("active_module_trombinoscopes_rt")=='180') { ?>checked='checked'<?php } ?> /> 180�
<input name="activer_rotation" value="270" type="radio" <?php if (getSettingValue("active_module_trombinoscopes_rt")=='270') { ?>checked='checked'<?php } ?> /> 270� &nbsp;S�lectionner une valeur si vous d�sirez une rotation de la photo originale</li>
</ul>

<h2>Gestion de l'acc�s des �l�ves</h2>
Dans la page "Gestion g�n�rale"->"Droits d'acc�s", vous avez la possibilit� de donner � <b>tous les �l�ves</b> le droit d'envoyer/modifier lui-m�me sa photo dans l'interface "G�rer mon compte".
<br />
<b>Si cette option est activ�e</b>, vous pouvez, ci-dessous, g�rer plus finement quels �l�ves ont le droit d'envoyer/modifier leur photo.
<br /><b>Marche � suivre :</b>
<ul>
<li>Cr�ez une "cat�gorie d'AID" ayant par exemple pour intitul� "trombinoscope".</li>
<li>Configurez l'affichage de cette cat�gorie d'AID de sorte que :
<br />- L'AID n'appara�sse pas dans le bulletin officiel,
<br />- L'AID n'appara�sse pas dans le bulletin simplifi�.
<br />Les autres param�tres n'ont pas d'importance.</li>
<li>Dans la "Liste des aid de la cat�gorie", ajoutez une ou plusieurs AIDs.</li>
<li>Ci-dessous, s�lectionner dans la liste des cat�gories d'AIDs, celle portant le nom que vous avez donn� ci-dessus.
<i>(cette liste n'apparara�t pas si vous n'avez pas donn� la possibilit� � tous les �l�ves d'envoyer/modifier leur photo dans "Gestion g�n�rale"->"Droits d'acc�s")</i>.
</li>
<li>Tous les �l�ves inscrits dans une des AIDs de la cat�gorie sus-nomm�e pourront alors envoyer/modifier leur photo (<em>� l'exception des �l�ves sans num�ro Sconet ou "elenoet"</em>).</li>
</ul>

<?php
if (getSettingValue("GepiAccesModifMaPhotoEleve")=='yes') {
    $req_trombino = mysql_query("select indice_aid, nom from aid_config order by nom");
    $nb_aid = mysql_num_rows($req_trombino);
    ?>
    <b>Nom de la cat&eacute;gorie d'AID permettant de g&eacute;rer l'acc&egrave;s des &eacute;l&egrave;ves : </b><select name="num_aid_trombinoscopes" size="1">
    <option value="">(aucune)</option>
    <?php
    $i = 0;
    while($i < $nb_aid){
        $indice_aid = mysql_result($req_trombino,$i,'indice_aid');
        $aid_nom = mysql_result($req_trombino,$i,'nom');
        $i++;
        echo "<option value='".$indice_aid."' ";
        if (getSettingValue("num_aid_trombinoscopes")==$indice_aid) echo " selected='selected'";
        echo ">".$aid_nom."</option>";
    }
    ?>
    </select><br />
    <b>Remarque&nbsp;:</b> Si "aucune" AID n'est d�finie, <b>tous les �l�ves</b> peuvent envoyer/modifier leur photo (<em>sauf ceux sans elenoet</em>).
    <br />
<?php
}
?>

<input type="hidden" name="is_posted" value="1" />
<div class="center"><input type="submit" value="Enregistrer" style="font-variant: small-caps;" /></div>
</form>

<a name="gestion_fichiers"></a>
<h2>Gestion des fichiers</h2>
<ul>
<li>Suppression
 <ul>
  <?php //if( file_exists('../photos/personnels/') ) { ?>
  <?php if( file_exists('../photos/'.$repertoire.'personnels/') ) { ?>
  <li><a href="trombinoscopes_admin.php?sousrub=dp#validation">Vider le dossier photos des personnels</a></li>
  <?php //} if( file_exists('../photos/eleves/') ) {?>
  <?php } if( file_exists('../photos/'.$repertoire.'eleves/') ) {?>
  <li><a href="trombinoscopes_admin.php?sousrub=de#validation">Vider le dossier photos des �l�ves</a></li>
  <?php } ?>
 </ul>
</li>
<li>Gestion
 <ul>
  <?php //if( file_exists('../photos/personnels/') ) { ?>
  <?php if( file_exists('../photos/'.$repertoire.'personnels/') ) { ?>
  <li><a href="trombinoscopes_admin.php?sousrub=vp#liste">Voir les personnels n'ayant pas de photos</a></li>
  <?php //} if( file_exists('../photos/eleves/') ) {?>
  <?php } if( file_exists('../photos/'.$repertoire.'eleves/') ) {?>
  <li><a href="trombinoscopes_admin.php?sousrub=ve#liste">Voir les �l�ves n'ayant pas de photos</a></li>
  <?php } ?>
 </ul>
</li>
</ul>


<?php if ( $sousrub === 've' ) {

	$cpt_eleve = '0';
	$requete_liste_eleve = "SELECT * FROM ".$prefix_base."eleves e, ".$prefix_base."j_eleves_classes jec, ".$prefix_base."classes c WHERE e.login = jec.login AND jec.id_classe = c.id GROUP BY e.login ORDER BY id_classe, nom, prenom ASC";
	$resultat_liste_eleve = mysql_query($requete_liste_eleve) or die('Erreur SQL !'.$requete_liste_eleve.'<br />'.mysql_error());
        while ( $donnee_liste_eleve = mysql_fetch_array ($resultat_liste_eleve))
	{
		$photo = '';
		$eleve_login[$cpt_eleve] = $donnee_liste_eleve['login'];
		$eleve_nom[$cpt_eleve] = $donnee_liste_eleve['nom'];
		$eleve_prenom[$cpt_eleve] = $donnee_liste_eleve['prenom'];
		$eleve_classe[$cpt_eleve] = $donnee_liste_eleve['nom_complet'];
		$eleve_classe_court[$cpt_eleve] = $donnee_liste_eleve['classe'];
		$eleve_elenoet[$cpt_eleve] = $donnee_liste_eleve['elenoet'];
		$nom_photo = nom_photo($eleve_elenoet[$cpt_eleve]);
		//$photo = "../photos/eleves/".$nom_photo;
		$photo = $nom_photo;
		//if (($nom_photo != "") and (file_exists($photo))) { $eleve_photo[$cpt_eleve] = 'oui'; } else { $eleve_photo[$cpt_eleve] = 'non'; }
		if (($nom_photo) and (file_exists($photo))) { $eleve_photo[$cpt_eleve] = 'oui'; } else { $eleve_photo[$cpt_eleve] = 'non'; }
		$cpt_eleve = $cpt_eleve + 1;
	}

	?><a name="liste"></a><h2>Liste des �l�ves n'ayant pas de photos</h2>
	<table cellpadding="1" cellspacing="1" style="margin: auto; border: 0px; background: #088CB9; color: #E0EDF1; text-align: center;" summary="El�ves sans photo">
	   <tr>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Nom</td>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Pr�nom</td>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Classe</td>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Num�ro �l�ve</td>
	   </tr>
	<?php
	$cpt_eleve = '0'; $classe_passe = ''; $i = '1';
	while ( !empty($eleve_login[$cpt_eleve]) )
	{
	        if ($i === '1') { $i = '2'; $couleur_cellule = 'background: #B7DDFF;'; } else { $couleur_cellule = 'background: #88C7FF;'; $i = '1'; }
		if ( $eleve_photo[$cpt_eleve] === 'non' )
		{
			if ( $eleve_classe[$cpt_eleve] != $classe_passe and $cpt_eleve != '0' ) { ?><tr><td colspan="4">&nbsp;</td></tr><?php }
		    ?><tr style="<?php echo $couleur_cellule; ?>">
		        <td style="text-align: left;"><?php echo $eleve_nom[$cpt_eleve]; ?></td>
		        <td style="text-align: left;"><?php echo $eleve_prenom[$cpt_eleve]; ?></td>
		        <td style="text-align: center;"><?php echo $eleve_classe[$cpt_eleve].' ('.$eleve_classe_court[$cpt_eleve].')'; ?></td>
		        <td style="text-align: center;"><?php echo $eleve_elenoet[$cpt_eleve]; ?></td>
		      </tr><?php
		}
		$classe_passe = $eleve_classe[$cpt_eleve];
		$cpt_eleve = $cpt_eleve + 1;
	}
?>
</table><br />
<?php }

if ( $sousrub === 'vp' ) {

	$cpt_personnel = '0';
	$requete_liste_personnel = "SELECT * FROM ".$prefix_base."utilisateurs u WHERE u.statut='professeur' ORDER BY nom, prenom ASC";
	$resultat_liste_personnel = mysql_query($requete_liste_personnel) or die('Erreur SQL !'.$requete_liste_personnel.'<br />'.mysql_error());
        while ( $donnee_liste_personnel = mysql_fetch_array ($resultat_liste_personnel))
	{
		$photo = '';
		$personnel_login[$cpt_personnel] = $donnee_liste_personnel['login'];
		$personnel_nom[$cpt_personnel] = $donnee_liste_personnel['nom'];
		$personnel_prenom[$cpt_personnel] = $donnee_liste_personnel['prenom'];

		$codephoto = $personnel_login[$cpt_personnel];
		$nom_photo = nom_photo($codephoto,"personnels");
		//$photo = '../photos/personnels/'.$nom_photo;
		$photo = $nom_photo;
		//if (($nom_photo != "") and (file_exists($photo))) { $personnel_photo[$cpt_personnel] = 'oui'; } else { $personnel_photo[$cpt_personnel] = 'non'; }
		if (($nom_photo) and (file_exists($photo))) { $personnel_photo[$cpt_personnel] = 'oui'; } else { $personnel_photo[$cpt_personnel] = 'non'; }
		$cpt_personnel = $cpt_personnel + 1;
	}

	?><a name="liste"></a><h2>Liste des personnels n'ayant pas de photos</h2>
	<table cellpadding="1" cellspacing="1" style="margin: auto; border: 0px; background: #088CB9; color: #E0EDF1; text-align: center;" summary="Personnels sans photo">
	   <tr>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Nom</td>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Pr�nom</td>
	   </tr>
	<?php
	$cpt_personnel = '0'; $i = '1';
	while ( !empty($personnel_login[$cpt_personnel]) )
	{
	        if ($i === '1') { $i = '2'; $couleur_cellule = 'background: #B7DDFF;'; } else { $couleur_cellule = 'background: #88C7FF;'; $i = '1'; }
		if ( $personnel_photo[$cpt_personnel] === 'non' )
		{
		    ?><tr style="<?php echo $couleur_cellule; ?>">
		        <td style="text-align: left;"><?php echo $personnel_nom[$cpt_personnel]; ?></td>
		        <td style="text-align: left;"><?php echo $personnel_prenom[$cpt_personnel]; ?></td>
		      </tr><?php
		}
		$cpt_personnel = $cpt_personnel + 1;
	}
?>
</table><br />
<?php }

if ( $sousrub === 'de' ) {

	?><a name="validation"></a><div style="background-color: #FFFCDF; margin-left: 80px; margin-right: 80px; padding: 10px;  border-left: 5px solid #FF1F28; text-align: center; color: rgb(255, 0, 0); font-weight: bold;"><img src="../mod_absences/images/attention.png" alt="Attention" /><div style="margin: 10px;">Vous allez supprimer toutes les photos d'identit� �l�ve que contient le dossier photo de GEPI, �tes vous d'accord ?<br /><br /><a href="trombinoscopes_admin.php">NON</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="trombinoscopes_admin.php?sousrub=deok#supprime">OUI</a></div></div><?php
}

if ( $sousrub === 'dp' ) {

	?>
	<a name="validation"></a><div style="background-color: #FFFCDF; margin-left: 80px; margin-right: 80px; padding: 10px;  border-left: 5px solid #FF1F28; text-align: center; color: rgb(255, 0, 0); font-weight: bold;"><img src="../mod_absences/images/attention.png" alt="Attention" /><div style="margin: 10px;">Vous allez supprimer toutes les photos d'identit� personnel que contient le dossier photo de GEPI, �tes vous d'accord ?<br /><br /><a href="trombinoscopes_admin.php">NON</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="trombinoscopes_admin.php?sousrub=dpok#supprime">OUI</a></div></div>
<?php
}


if ( $sousrub === 'deok' ) {

	// on liste les fichier du dossier photos/eleves
	$fichier_sup=array();
	//$folder = "../photos/eleves/";
	$folder = "../photos/".$repertoire."eleves/";
	$cpt_fichier = '0';
	$dossier = opendir($folder);
	while ($Fichier = readdir($dossier)) {
	  if ($Fichier != "." && $Fichier != ".." && $Fichier != "index.html") {
	    $nomFichier = $folder."".$Fichier;
	    $fichier_sup[$cpt_fichier] = $nomFichier;
	    $cpt_fichier = $cpt_fichier + 1;
	  }
	}
	closedir($dossier);

	//on supprime tout les fichiers
	$cpt_fichier = '0';
	?>
	<a name="supprime"></a>
	<!--h2>Liste des fichiers concern�s et leurs �tats</h2-->
	<h2>Liste des fichiers concern�s</h2>

	<?php
		if(count($fichier_sup)==0) {
			echo "<p style='margin-left: 50px;'>Le dossier <strong>$folder</strong> ne contient pas de photo.</p>\n";
		}
		else {
	?>

			<table cellpadding="1" cellspacing="1" style="margin: auto; border: 0px; background: #088CB9; color: #E0EDF1; text-align: center;" summary="Suppression">
			<tr>
				<td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Fichier</td>
				<td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Etat</td>
			</tr><?php $i = '1';
			while ( !empty($fichier_sup[$cpt_fichier]) )
			{
					if ($i === '1') { $i = '2'; $couleur_cellule = 'background: #B7DDFF;'; } else { $couleur_cellule = 'background: #88C7FF;'; $i = '1'; }
				if(file_exists($fichier_sup[$cpt_fichier]))
				{
					@unlink($fichier_sup[$cpt_fichier]);

					if(file_exists($fichier_sup[$cpt_fichier]))
					{ $etat = '<span style="color:red;">erreur, vous n\'avez pas les droits pour supprimer ce fichier</span>'; } else { $etat = 'supprim�'; }
					?>
				<tr style="<?php echo $couleur_cellule; ?>">
					<td style="text-align: left; padding-left: 2px; padding-right: 2px;"><?php echo $fichier_sup[$cpt_fichier]; ?></td>
					<td style="text-align: left; padding-left: 2px; padding-right: 2px;"><?php echo $etat; ?></td>
				</tr><?php
				}
			$cpt_fichier = $cpt_fichier + 1;
			}

			echo "</table>\n";
		}
}

if ( $sousrub === 'dpok' ) {

	// on liste les fichier du dossier photos/personnels
	$fichier_sup=array();
	//$folder = "../photos/personnels/";
	$folder = "../photos/".$repertoire."personnels/";
	$cpt_fichier = '0';
	$dossier = opendir($folder);
	while ($Fichier = readdir($dossier)) {
	  if ($Fichier != "." && $Fichier != ".." && $Fichier != "index.html") {
	    $nomFichier = $folder."".$Fichier;
	    $fichier_sup[$cpt_fichier] = $nomFichier;
	    $cpt_fichier = $cpt_fichier + 1;
	  }
	}
	closedir($dossier);

	//on supprime tout les fichiers
	$cpt_fichier = '0';
	?>
	<a name="supprime"></a>
	<!--h2>Liste des fichiers concern�s et leurs �tats</h2-->
	<h2>Liste des fichiers concern�s</h2>

	<?php
		if(count($fichier_sup)==0) {
			echo "<p style='margin-left: 50px;'>Le dossier <strong>$folder</strong> ne contient pas de photo.</p>\n";
		}
		else {
	?>

			<table cellpadding="1" cellspacing="1" style="margin: auto; border: 0px; background: #088CB9; color: #E0EDF1; text-align: center;" summary="Suppression">
			<tr>
				<td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Fichier</td>
				<td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Etat</td>
			</tr><?php $i = '1';
			while ( !empty($fichier_sup[$cpt_fichier]) )
			{
					if ($i === '1') { $i = '2'; $couleur_cellule = 'background: #B7DDFF;'; } else { $couleur_cellule = 'background: #88C7FF;'; $i = '1'; }
				if(file_exists($fichier_sup[$cpt_fichier]))
				{
					@unlink($fichier_sup[$cpt_fichier]);

					if(file_exists($fichier_sup[$cpt_fichier]))
					{ $etat = '<span style="color:red;">erreur, vous n\'avez pas les droits pour supprimer ce fichier</span>'; } else { $etat = 'supprim�'; }
					?>
				<tr style="<?php echo $couleur_cellule; ?>">
					<td style="text-align: left; padding-left: 2px; padding-right: 2px;"><?php echo $fichier_sup[$cpt_fichier]; ?></td>
					<td style="text-align: left; padding-left: 2px; padding-right: 2px;"><?php echo $etat; ?></td>
				</tr><?php
				}
			$cpt_fichier = $cpt_fichier + 1;
			}

			echo "</table>\n";
		}
}


echo "<p><br /></p>\n";
require("../lib/footer.inc.php"); ?>
 *
 */


/****************************************************************
			BAS DE PAGE
****************************************************************/
$tbs_microtime	="";
$tbs_pmv="";
require_once ("../lib/footer_template.inc.php");

/****************************************************************
			On s'assure que le nom du gabarit est bien renseign�
****************************************************************/
if ((!isset($_SESSION['rep_gabarits'])) || (empty($_SESSION['rep_gabarits']))) {
	$_SESSION['rep_gabarits']="origine";
}

//==================================
// D�commenter la ligne ci-dessous pour afficher les variables $_GET, $_POST, $_SESSION et $_SERVER pour DEBUG:
// $affiche_debug=debug_var();


$nom_gabarit = '../templates/'.$_SESSION['rep_gabarits'].'/mod_trombinoscopes/trombinoscopes_admin_template.php';

$tbs_last_connection=""; // On n'affiche pas les derni�res connexions
include($nom_gabarit);

// ------ on vide les tableaux -----
unset($menuAffiche);



?>
