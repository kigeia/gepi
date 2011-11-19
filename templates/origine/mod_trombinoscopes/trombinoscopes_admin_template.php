<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
/**
 * $Id$
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
*
* ******************************************** *
* Appelle les sous-mod�les                     *
* templates/origine/header_template.php        *
* templates/origine/bandeau_template.php       *
* ******************************************** *
*/

/**
 *
 * @author regis
 */

?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">

<head>
<!-- on inclut l'ent�te -->
	<?php
	  $tbs_bouton_taille = "..";
	  include('../templates/origine/header_template.php');
	?>

  <script type="text/javascript" src="../templates/origine/lib/fonction_change_ordre_menu.js"></script>

	<link rel="stylesheet" type="text/css" href="../templates/origine/css/bandeau.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="../templates/origine/css/gestion.css" media="screen" />

<!-- corrections internet Exploreur -->
	<!--[if lte IE 7]>
		<link title='bandeau' rel='stylesheet' type='text/css' href='../templates/origine/css/accueil_ie.css' media='screen' />
		<link title='bandeau' rel='stylesheet' type='text/css' href='../templates/origine/css/bandeau_ie.css' media='screen' />
	<![endif]-->
	<!--[if lte IE 6]>
		<link title='bandeau' rel='stylesheet' type='text/css' href='../templates/origine/css/accueil_ie6.css' media='screen' />
	<![endif]-->
	<!--[if IE 7]>
		<link title='bandeau' rel='stylesheet' type='text/css' href='../templates/origine/css/accueil_ie7.css' media='screen' />
	<![endif]-->


<!-- Style_screen_ajout.css -->
	<?php
		if (count($Style_CSS)) {
			foreach ($Style_CSS as $value) {
				if ($value!="") {
					echo "<link rel=\"$value[rel]\" type=\"$value[type]\" href=\"$value[fichier]\" media=\"$value[media]\" />\n";
				}
			}
		}
	?>

<!-- Fin des styles -->



</head>


<!-- ************************* -->
<!-- D�but du corps de la page -->
<!-- ************************* -->
<body onload="show_message_deconnexion();<?php echo $tbs_charger_observeur;?>">

<!-- on inclut le bandeau -->
	<?php include('../templates/origine/bandeau_template.php');?>

<!-- fin bandeau_template.html      -->

  <div id='container'>
<!-- Fin haut de page -->

	<h2 class="colleHaut">Configuration g�n�rale</h2>
	<p>
	  <em>
		La d�sactivation du module trombinoscope n'entra�ne aucune suppression des donn�es.
		Lorsque le module est d�sactiv�, il n'y a pas d'acc�s au module.
	  </em>
	</p>
	<form action="trombinoscopes_admin.php" id="form1" method="post" title="Configuration g�n�rale">
	  <fieldset>
	<?php
	echo add_token_field();
	?>
		<legend class="bold">�l�ves :</legend>
		<input type="radio"
			   name="activer"
			   id='activer_y'
			   value="y"
			  <?php if (getSettingValue("active_module_trombinoscopes")=='y') echo " checked='checked'"; ?>
			   />
		<label for='activer_y' style='cursor:pointer'>
		  Activer le module trombinoscope
		</label>
		<br/>
		<input type="radio"
			   name="activer"
			   id='activer_n'
			   value="n"
			  <?php if (getSettingValue("active_module_trombinoscopes")!='y') echo " checked='checked'"; ?>
			   />
		<label for='activer_n'
			   style='cursor:pointer'>
		  D�sactiver le module trombinoscope
		</label>
		<input type="hidden" name="is_posted" value="1" />
	  </fieldset>
	  
	  <fieldset>
		<legend class="bold">Personnels :</legend>
		<input type="radio"
			   name="activer_personnels"
			   id='activer_personnels_y'
			   value="y"
			  <?php if (getSettingValue("active_module_trombino_pers")=='y') echo " checked='checked'"; ?>
			   />
		<label for='activer_personnels_y' style='cursor:pointer'>
		  Activer le module trombinoscope des personnels
		</label>
		<br/>
		<input type="radio"
			   name="activer_personnels"
			   id='activer_personnels_n'
			   value="n"
			  <?php if (getSettingValue("active_module_trombino_pers")!='y')echo " checked='checked'"; ?>
			   />
		<label for='activer_personnels_n' style='cursor:pointer'>
		  D�sactiver le module trombinoscope des personnels
		</label>
	  </fieldset>

	  <p class="center">
		<input type="submit"
			   value="Enregistrer"
			   style="font-variant: small-caps;" />
	  </p>

	  <h2>Configuration d'affichage et de stockage</h2>
	  <p>
		<em>
		  Les valeurs ci-dessous vous servent au param�trage des valeurs maxi des largeurs et des hauteurs.
		</em>
	  </p>
	  <fieldset>
		<legend class="bold">Pour l'�cran</legend>
		largeur maxi 
		<input type="text"
			   name="l_max_aff_trombinoscopes"
			   size="3" 
			   maxlength="3" 
			   value="<?php echo getSettingValue("l_max_aff_trombinoscopes"); ?>"
			   title="largeur maxi"
			   />
		hauteur maxi
		<input type="text"
			   name="h_max_aff_trombinoscopes"
			   size="3" 
			   maxlength="3" 
			   value="<?php echo getSettingValue("h_max_aff_trombinoscopes"); ?>"
			   title="hauteur maxi"
			   />
	  </fieldset>
	  
	  <fieldset>
		<legend class="bold">Pour l'impression</legend>
		largeur maxi
		<input type="text"
			   name="l_max_imp_trombinoscopes"
			   size="3" 
			   maxlength="3"
			   title="largeur maxi"
			   value="<?php echo getSettingValue("l_max_imp_trombinoscopes"); ?>" 
			   />
		hauteur maxi
		<input type="text"
			   name="h_max_imp_trombinoscopes"
			   size="3" 
			   maxlength="3"
			   title="hauteur maxi"
			   value="<?php echo getSettingValue("h_max_imp_trombinoscopes"); ?>" 
			   />
		Nombre de colonnes
		<input type="text"
			   name="nb_col_imp_trombinoscopes"
			   size="3" 
			   maxlength="3"
			   value="<?php echo getSettingValue("nb_col_imp_trombinoscopes"); ?>"
			   title="Nombre de colonnes"
			   />
	  </fieldset>

	  <fieldset>
		<legend class="bold">Pour le stockage sur le serveur</legend>
		largeur
		<input type="text"
			   name="l_resize_trombinoscopes"
			   size="3" 
			   maxlength="3"
			   title="largeur"
			   value="<?php echo getSettingValue("l_resize_trombinoscopes"); ?>" 
			   />
		hauteur
		<input type="text"
			   name="h_resize_trombinoscopes"
			   size="3" 
			   maxlength="3"
			   title="hauteur"
			   value="<?php echo getSettingValue("h_resize_trombinoscopes"); ?>" 
			   />
	  </fieldset>

	  <p class="center">
		<input type="submit"
			   value="Enregistrer"
			   style="font-variant: small-caps;" />
	  </p>
	  
	  <h2>Configuration du redimensionnement des photos</h2>
	  <p>
		<em>
		  La d�sactivation du redimensionnement des photos n'entra�ne aucune suppression des donn�es. 
		  Lorsque le syst�me de redimensionnement est d�sactiv�, les photos transfer�es sur le site 
		  ne seront pas r�duites en 
		  <?php echo getSettingValue("l_resize_trombinoscopes");?>x<?php echo getSettingValue("h_resize_trombinoscopes");?>.
		</em>
	  </p>
	  <fieldset>
		<legend class="invisible">Activation</legend>
		<input type="radio" 
			   name="activer_redimensionne" 
			   id="activer_redimensionne_y" 
			   value="y" 
			  <?php if (getSettingValue("active_module_trombinoscopes_rd")=='y') echo " checked='checked'"; ?> 
			   />
		<label for='activer_redimensionne_y' style='cursor:pointer'>
		  Activer le redimensionnement des photos en 
		  <?php echo getSettingValue("l_resize_trombinoscopes");?>x<?php echo getSettingValue("h_resize_trombinoscopes");?>
		</label>
	  <br/>
		<strong>Remarque</strong> attention GD doit �tre actif sur le serveur de GEPI pour utiliser 
		le redimensionnement.
	  <br/>
		<input type="radio" 
			   name="activer_redimensionne" 
			   id="activer_redimensionne_n" 
			   value="n" 
			  <?php if (getSettingValue("active_module_trombinoscopes_rd")=='n') echo " checked='checked'"; ?> 
			   />
		<label for='activer_redimensionne_n' style='cursor:pointer'>
		  D�sactiver le redimensionnement des photos
		</label>
	  </fieldset>

	  <fieldset>
		<legend class="bold">Rotation de l'image :</legend>
		<input name="activer_rotation"
			   value=""
			   type="radio"
			   title="Tourner de 0�"
			  <?php if (getSettingValue("active_module_trombinoscopes_rt")=='') echo "checked='checked'"; ?>
			   />
		0�
		<input name="activer_rotation"
			   value="90"
			   type="radio"
			   title="Tourner de 90�"
			  <?php if (getSettingValue("active_module_trombinoscopes_rt")=='90') echo "checked='checked'"; ?>
			   />
		90�
		<input name="activer_rotation"
			   value="180"
			   type="radio"
			   title="Tourner de 180�"
			  <?php if (getSettingValue("active_module_trombinoscopes_rt")=='180') echo "checked='checked'"; ?>
			  />
		180�
		<input name="activer_rotation"
			   value="270"
			   type="radio"
			   title="Tourner de 270�"
			  <?php if (getSettingValue("active_module_trombinoscopes_rt")=='270') echo "checked='checked'"; ?>
			   />
		270�
		S�lectionner une valeur si vous d�sirez une rotation de la photo originale
	  </fieldset>

	  <p class="center">
		<input type="submit"
			   value="Enregistrer"
			   style="font-variant: small-caps;" />
	  </p>

	  <h2>Gestion de l'acc�s des �l�ves</h2>
	  <p>
		Dans la page "Gestion g�n�rale"-&gt;"Droits d'acc�s", vous avez la possibilit� de donner �
		<strong>tous les �l�ves</strong> le droit d'envoyer/modifier lui-m�me sa photo dans l'interface
		"G�rer mon compte".
	  </p>
	  <p>
		<strong>Si cette option est activ�e</strong>, vous pouvez, ci-dessous, g�rer plus finement quels �l�ves
		ont le droit d'envoyer/modifier leur photo.
	  </p>
	  <p class="bold">
		Marche � suivre :
	  </p>
	  <ul id="expli_AID" class="colleHaut">
		<li>Cr�ez une "cat�gorie d'AID" ayant par exemple pour intitul� "trombinoscope".</li>
		<li>
		  Configurez l'affichage de cette cat�gorie d'AID de sorte que :
		  <ul>
			<li>L'AID n'appara�sse pas dans le bulletin officiel,</li>
			<li>L'AID n'appara�sse pas dans le bulletin simplifi�.</li>
			<li>Les autres param�tres n'ont pas d'importance.</li>
		  </ul>
		</li>
		<li>Dans la "Liste des aid de la cat�gorie", ajoutez une ou plusieurs AIDs.</li>
		<li>
		  Ci-dessous, s�lectionner dans la liste des cat�gories d'AIDs, celle portant le nom que vous avez
		  donn� ci-dessus. <em>(cette liste n'appara�t pas si vous n'avez pas donn� la possibilit� � tous
		  les �l�ves d'envoyer/modifier leur photo dans "Gestion g�n�rale"-&gt;"Droits d'acc�s")</em>.
		</li>
		<li>
		  Tous les �l�ves inscrits dans une des AIDs de la cat�gorie sus-nomm�e pourront alors
		  envoyer/modifier leur photo (<em>� l'exception des �l�ves sans num�ro Sconet ou "elenoet"</em>).
		</li>
	  </ul>

<?php

if (!isset($aid_trouve)) {
?>
	  <p>
		<strong>
		  Vous devez cr�er une AID pour pouvoir limiter l'acc�s des �l�ves au trombinoscope
		</strong>
	  </p>

<?php
} else {
?>
	  <p>
		<strong>
		  Nom de la cat�gorie d'AID permettant de g�rer l'acc�s des �l�ves :
		</strong>
		<select name="num_aid_trombinoscopes" size="1" title="Choisir une AID">
		  <option value="">
			(aucune)
		  </option>
<?php
  foreach ($aid_trouve as $aid_disponible){
?>
		  <option value="<?php echo $aid_disponible["indice"] ;?>"<?php if ($aid_disponible["selected"]){ ?> selected="selected"<?php ;} ?> >
			<?php echo $aid_disponible["nom"] ;?>
		  </option>
<?php
  }
  unset ($aid_disponible)
?>
		</select>
	  </p>
	  <p>
		<strong>Remarque :</strong> Si "aucune" AID n'est d�finie, <strong>tous les �l�ves</strong> peuvent
		envoyer/modifier leur photo (<em>sauf ceux sans elenoet</em>).
	  </p>
	  <p class="center">
		<input type="hidden" 
			   name="is_posted" 
			   value="1" />
		<input type="submit" 
			   value="Enregistrer"
			   style="font-variant: small-caps;" />
	  </p>
<?php
}
?>
	</form>

	<h2>Gestion des fichiers</h2>
<?php if(!file_exists('../photos/'.$repertoire.'eleves/') && !file_exists('../photos/'.$repertoire.'eleves/')) {?>
	  <p>
		Les dossiers photos n'existent pas
	  </p>
<?php } else 
{ ?>
	<form action="trombinoscopes_admin.php" id="form2" method="post" title="Gestion des fichiers">
	<?php
	echo add_token_field();
	?>
	  <fieldset>
		<legend class="bold">
		  <label for="supprime">Suppression</label>
		</legend>
<?php if( file_exists('../photos/'.$repertoire.'personnels/') ) { ?>
		<input type="checkbox"
			   name="sup_pers"
			   id='supprime_personnels'
			   value="oui"
			   />
		<label for="supprime_personnel" id='sup_pers'>
		  Vider le dossier photos des personnels
		</label>
<?php } if( file_exists('../photos/'.$repertoire.'eleves/') ) {  ?>
		<br/>
		<input type="checkbox"
			   name="supp_eleve"
			   id='supprime_eleves'
			   value="oui" />
		<label for="supprime_eleve" id='sup_ele'>
		  Vider le dossier photos des �l�ves
		</label>
		<br/><em>Un fichier de sauvegarde sera cr��, pensez � le r�cup�rer puis le supprimer dans le module de gestion des sauvegardes.</em>
	  </fieldset>
<?php } ?>
	  <p class="center">
		<input type="submit" 
			   value="Vider le(s) dossier(s)"
			   style="font-variant: small-caps;" />
	  </p>
	 </form>


	<form action="trombinoscopes_admin.php" id="form3" method="post" title="Gestion des fichiers">
	<?php
	echo add_token_field();
	?>
	<fieldset>
		<legend class="bold">
		  Gestion
		</legend>
<?php if( file_exists('../photos/'.$repertoire.'personnels/') ) { ?>
		<input type="checkbox"
			   name="voirPerso"
			   id='voir_personnel'
			   value="yes" />
		<label for="voir_personnel">
		  Lister les professeurs n'ayant pas de photos
		</label>
  <?php } if( file_exists('../photos/'.$repertoire.'eleves/') ) {?>
		<br/>
		<input type="checkbox"
			   name="voirEleve"
			   id='voir_eleve'
			   value="yes" />
		<label for="voir_eleve">
		  Lister les �l�ves n'ayant pas de photos
		</label>
  <?php } ?>
	  </fieldset>
	  <p class="center">
		<input type="submit" 
			   value="Lister"
			   style="font-variant: small-caps;" />
	  </p>
	</form>

	<?php if( file_exists('../photos/'.$repertoire.'eleves/') ) {?>
	<form action="trombinoscopes_admin.php" id="form4" method="post" title="Purger les photos">
	<?php
	echo add_token_field();
	?>
	<p>
	<fieldset>
		<legend class="bold">Purger le dossier des photos</legend>
		Supprimer les photos des �l�ves et des professeurs qui ne sont plus r�f�renc�s dans la base.<br/>
		<input type="hidden" name="purge_dossier_photos" value="oui">
		<input type="checkbox"
			   name="cpts_inactifs"
			   id='cpts_inactifs'
			   value="oui" />
		<label for="purge_dossier_photos">
		  Effacer �galement les photos des �l�ves et des professeurs dont le compte est d�sactiv�.
		</label>
		<br/><em>Un fichier de sauvegarde sera cr��, pensez � le r�cup�rer puis le supprimer dans le module de gestion des sauvegardes.</em>
 	  </fieldset>
	  <p class="center">
		<input type="submit" 
			   value="Purger"
			   style="font-variant: small-caps;" />
	</p>
	</form>
 <?php 
 } ?>
 
	<h2>T�l�charger les photos</h2>
	<form method="post" action="trombinoscopes_admin.php" id="formEnvoi1" enctype="multipart/form-data">
	<fieldset>
	<?php
	echo add_token_field();
	?>
		<legend class="bold">
			T�l�charger les photos des �l�ves � partir d'un fichier ZIP
		</legend>
		<input type="hidden" name="action" value="upload_photos_eleves" />
		<input type="file" name="nom_du_fichier" title="Nom du fichier � t�l�charger"/>
		<input type="submit" value="T�l�charger"/>
		<br/>
		<input type="checkbox"
			   name="ecraser"
			   id='ecrase_photo'
			   value="yes" />
		<label for="ecrase_photo">
		  Ecraser les photos si les noms correspondent
		</label>
		<p>
		  <em>
			Si coch�e, les photos d�j� pr�sentes seront remplac�es par les nouvelles.
			Sinon, les anciennes photos seront conserv�es
		  </em>
		</p>

		<p>Le fichier ZIP doit contenir :<br/>
		<span style="margin-left: 40px;">
		- soit les photos  encod�es au format JPEG nomm�es d'apr�s les ELENOET des �l�ves (<em>ELENOET.jpg</em>) ou pour, un GEPI multisite, les login des �l�ves (<em>login.jpg</em>) ;<br/>
		</span><span style="margin-left: 40px;">
		- soit les photos  encod�es au format JPEG et un fichier au <a href="http://fr.wikipedia.org/wiki/Comma-separated_values" target="_blank">format CSV</a>, <b>imp�rativement nomm� <em>correspondances.csv</em></b>, �tablissant les correspondances entre (premier champ) les noms des fichiers photos et (second champ) les ELENOET des �l�ves ou, pour un GEPI multisite, les login des �l�ves (<a href="correspondances.csv" target="_blank">exemple de fichier correspondances.csv</a>) ; pour g�n�rer le fichier correspondances.csv vous pouvez <a href="trombinoscopes_admin.php?liste_eleves=oui<?php echo add_token_in_url(); ?>">r�cup�rer la liste</a> des �l�ves avec pr�nom, nom, eleonet et login.  <br/>
		</span>
		</p>

		<p>La <b>taille maximale</b> d'un fichier t�l�charg� vers le serveur est de <b><?php echo ini_get('upload_max_filesize');?>.</b><br/>Effectuez si n�cessaire votre t�l�chargement en plusieurs fichiers Zip.</p>

	  </fieldset>
	</form>

	<br/>

	<form method="post" action="trombinoscopes_admin.php" id="formEnvoi2" enctype="multipart/form-data">
	<fieldset>
	<?php
	echo add_token_field();
	?>
		<legend class="bold">
			Restaurer les photos � partir d'une sauvegarde (ou d'un fichier ZIP)
		</legend>
		<input type="hidden" name="action" value="upload" />
		<input type="file" name="nom_du_fichier" title="Nom du fichier � t�l�charger"/>
		<input type="submit" value="Restaurer"/>
		<br/>
		<input type="checkbox"
			   name="ecraser"
			   id='ecrase_photo'
			   value="yes" />
		<label for="ecrase_photo">
		  Ecraser les photos si les noms correspondent
		</label>
		<p>
		  <em>
			Si coch�, les photos d�j� pr�sentes seront remplac�es par les nouvelles.
			Sinon, les anciennes photos seront conserv�es
		  </em>
		</p>

		<p>Le fichier ZIP doit contenir une arborescence <b>photos/eleves</b> et/ou <b>photos/personnels</b> contenant respectivement les photos des �l�ves et des personnels encod�es au format JPEG. Les photos des �l�ves doivent �tre nomm�es d'apr�s les ELENOET des �l�ves (<em>ELENOET.jpg</em>) ou, pour un GEPI multisite, d'apr�s les login des �l�ves (<em>login.jpg</em>).

		<p>La <b>taille maximale</b> d'un fichier t�l�charg� vers le serveur est de <b><?php echo ini_get('upload_max_filesize');?>.</b><br/>Effectuez si n�cessaire votre t�l�chargement en plusieurs fichiers ZIP.</p>

	  </fieldset>
	</form>

	<hr/>

  <?php }
  if (isset ($eleves_sans_photo)){
  ?>
	<table class="boireaus">
	  <caption>�l�ves sans photos</caption>
	  <tr>
		<th>Nom</th>
		<th>Pr�nom</th>
	  </tr>
  <?php
		$lig="lig1";
	foreach ($eleves_sans_photo as $pas_photo){
	  if ($lig=="lig1"){
		$lig="lig-1";
	  } else{
		$lig="lig1";
	  }
  ?>
	  <tr class="<?php echo $lig ;?>" >
		<td><?php echo $pas_photo->nom ;?></td>
		<td><?php echo $pas_photo->prenom ;?></td>
	  </tr>
  <?php
	}
	unset($pas_photo);
  ?>
	</table>
  <?php
  }

  if (isset ($personnel_sans_photo)){
  ?>
	<table class="boireaus">
	  <caption>Personnels sans photos</caption>
	  <tr>
		<th>Nom</th>
		<th>Pr�nom</th>
	  </tr>
  <?php 
		$lig="lig1";
	foreach ($personnel_sans_photo as $pas_photo){
	  if ($lig=="lig1"){
		$lig="lig-1";
	  } else{
		$lig="lig1";
	  }
  ?>
	  <tr class="<?php echo $lig ;?>" >
		<td><?php echo $pas_photo->nom ;?></td>
		<td><?php echo casse_mot($pas_photo->prenom,"majf2") ;?></td>
	  </tr>
  <?php 
	}
	unset($pas_photo);
  ?>
	</table>
  <?php 
  }
  ?>





<!-- D�but du pied -->
	<div id='EmSize' style='visibility:hidden; position:absolute; left:1em; top:1em;'></div>

	<script type='text/javascript'>
	  //<![CDATA[
		var ele=document.getElementById('EmSize');
		var em2px=ele.offsetLeft
	  //]]>
	</script>


	<script type='text/javascript'>
	  //<![CDATA[
		temporisation_chargement='ok';
	  //]]>
	</script>

</div>

		<?php
			if ($tbs_microtime!="") {
				echo "
   <p class='microtime'>Page g�n�r�e en ";
   			echo $tbs_microtime;
				echo " sec</p>
   			";
	}
?>

		<?php
			if ($tbs_pmv!="") {
				echo "
	<script type='text/javascript'>
		//<![CDATA[
   			";
				echo $tbs_pmv;
				echo "
		//]]>
	</script>
   			";
		
	}
?>

</body>
</html>


