<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
/*
* $Id$
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

	<link rel="stylesheet" type="text/css" href="../templates/origine/css/accueil.css" media="screen" />
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

<?php
	if(isset($_GET['ajout_index_documents'])) {
		echo ajout_index_sous_dossiers("../documents");

		$sql="SELECT * FROM infos_actions WHERE titre='Contr�le des index dans les documents des CDT requis';";
		$res_test=mysql_query($sql);
		if(mysql_num_rows($res_test)>0) {
			while($lig_ia=mysql_fetch_object($res_test)) {
				$sql="DELETE FROM infos_actions_destinataires WHERE id_info='$lig_ia->id';";
				$del=mysql_query($sql);
				if($del) {
					$sql="DELETE FROM infos_actions WHERE id='$lig_ia->id';";
					$del=mysql_query($sql);
				}
			}
		}

	}
?>

	<form action="index.php" id="form1" method="post">
	  <p class="center">
<?php
echo add_token_field();
?>
		<input type="submit" value="Enregistrer" />
	  </p>
	<h2>Activation des cahiers de textes</h2>
	  <p class="italic">
		  La d�sactivation des cahiers de textes n'entra�ne aucune suppression des donn�es.
		  Lorsque le module est d�sactiv�, les professeurs n'ont pas acc�s au module et la consultation
		  publique des cahiers de textes est impossible.
	  </p>
	<fieldset class="no_bordure">
	  <legend class="invisible">Activation</legend>

		<input type="radio"
				 name="activer"
				 id="activer_y"
				 value="y"
			 onchange='changement();'
				<?php if (getSettingValue("active_cahiers_texte")=='y') echo " checked='checked'"; ?> />
		<label for='activer_y' style='cursor: pointer;'>
		  Activer les cahiers de textes (consultation et �dition)
		</label>
	  <br />
		<input type="radio" 
				 name="activer" 
				 id="activer_n" 
				 value="n"
			 onchange='changement();'
				<?php if (getSettingValue("active_cahiers_texte")=='n') echo " checked='checked'"; ?> />
		<label for='activer_n' style='cursor: pointer;'>
		  D�sactiver les cahiers de textes (consultation et �dition)
		</label>
	  </fieldset>
	  
	  
	  <h2>Version des cahiers de textes</h2>
<?php $extensions = get_loaded_extensions();
  if(!in_array('pdo_mysql',$extensions)) {
?>
	  <p>
		<span style='color:red'>
		  ATTENTION
		</span>
	  Il semble que l'extension php 'pdo_mysql' ne soit pas pr�sente.
	  <br />
	  Cela risque de rendre impossible l'utilisation de la version 2 du cahier de texte";
	  </p>
<?php
  }
  ?>
	  <p class="italic">
		La version 2 du cahier de texte necessite php 5.2.x minimum
	  </p>
	  <fieldset class="no_bordure">
		<legend class="invisible">Version</legend>
		<input type="radio"
				 name="version"
				 id="version_1"
				 value="1"
			 onchange='changement();'
				<?php if (getSettingValue("GepiCahierTexteVersion")=='1') echo " checked='checked'"; ?> />
		<label for='version_1' style='cursor: pointer;'>
		  Cahier de texte version 1
		</label>
		(<span class="italic">
		  le cahier de texte version 1 ne sera plus support� dans la future version 1.5.3
		</span>)
		<br />
		  <input type="radio"
				 name="version"
				 id="version_2"
				 value="2"
			 onchange='changement();'
				<?php if (getSettingValue("GepiCahierTexteVersion")=='2') echo " checked='checked'"; ?> />
		<label for='version_2' style='cursor: pointer;'>
		  Cahier de texte version 2
		</label>
	  </fieldset>
	  
	  <h2>D�but et fin des cahiers de textes</h2>
	  <p class="italic">
		Seules les rubriques dont la date est comprise entre la date de d�but et la date de fin des cahiers
		de textes sont visibles dans l'interface de consultation publique.
		<br />
		L'�dition (modification/suppression/ajout) des cahiers de textes par les utilisateurs de GEPI
		n'est pas affect�e par ces dates.
	  </p>
	  <fieldset class="no_bordure">
		<legend class="invisible">Version</legend>
        Date de d�but des cahiers de textes :
<?php
        $bday = strftime("%d", getSettingValue("begin_bookings"));
        $bmonth = strftime("%m", getSettingValue("begin_bookings"));
        $byear = strftime("%Y", getSettingValue("begin_bookings"));
        genDateSelector("begin_", $bday, $bmonth, $byear,"more_years")
?>
	  <br />
        Date de fin des cahiers de textes :
<?php
        $eday = strftime("%d", getSettingValue("end_bookings"));
        $emonth = strftime("%m", getSettingValue("end_bookings"));
        $eyear= strftime("%Y", getSettingValue("end_bookings"));
        genDateSelector("end_",$eday,$emonth,$eyear,"more_years")
?>
		<input type="hidden" name="is_posted" value="1" />
	  </fieldset>

	  <h2>Acc�s public</h2>
	  <fieldset class="no_bordure">
		<legend class="invisible">acc�s public</legend>
		  <input type='radio' 
				 name='cahier_texte_acces_public' 
				 id='cahier_texte_acces_public_n' 
				 value='no'
			 onchange='changement();'
				<?php if (getSettingValue("cahier_texte_acces_public") == "no") echo " checked='checked'";?> /> 
		<label for='cahier_texte_acces_public_n' style='cursor: pointer;'>
		  D�sactiver la consultation publique des cahiers de textes 
		  (seuls des utilisateurs logu�s pourront y avoir acc�s en consultation, s'ils y sont autoris�s)
		</label>
	  <br />
		  <input type='radio' 
				 name='cahier_texte_acces_public' 
				 id='cahier_texte_acces_public_y' 
				 value='yes'
			 onchange='changement();'
				<?php if (getSettingValue("cahier_texte_acces_public") == "yes") echo " checked='checked'";?> /> 
		<label for='cahier_texte_acces_public_y' style='cursor: pointer;'>
		  Activer la consultation publique des cahiers de textes 
		  (tous les cahiers de textes visibles directement, ou par la saisie d'un login/mdp global)
		</label>
	  </fieldset>
	  <p>
		-&gt; Acc�s � l'<a href='../public/index.php?id_classe=-1'>interface publique de consultation des cahiers de textes</a>
	  </p>
	  <p class="italic">
		En l'absence de mot de passe et d'identifiant, l'acc�s � l'interface publique de consultation 
		des cahiers de textes est totalement libre.
	  </p>
	  <p>
		Identifiant :
		<input type="text" 
			   name="cahiers_texte_login_pub"
			 onchange='changement();'
			 title="Identifiant"
			   value="<?php echo getSettingValue("cahiers_texte_login_pub"); ?>" 
			   size="20" />
	  </p>
	  <p>
		Mot de passe :
		<input type="text" 
			   name="cahiers_texte_passwd_pub"
			 onchange='changement();'
			 title="Mot de passe"
			   value="<?php echo getSettingValue("cahiers_texte_passwd_pub"); ?>" 
			   size="20" />
	  </p>

	  <h2>D�lai de visualisation des devoirs</h2>
	  <p class="italic">
		Indiquez ici le d�lai en jours pendant lequel les devoirs seront visibles, � compter du jour de
		visualisation s�lectionn�, dans l'interface publique de consultation des cahiers de textes.
		<br />
		Mettre la valeur 0 si vous ne souhaitez pas activer le module de remplissage des devoirs.
		Dans ce cas, les professeurs font figurer les devoirs � faire dans la m�me case que le contenu des
		s�ances.
	  </p>
	  <p>
		D�lai :
		<input type="text"
			   name="delai_devoirs"
			 onchange='changement();'
			 title="D�lai des devoirs"
			   value="<?php echo getSettingValue("delai_devoirs"); ?>"
			   size="2" />
		jours
	  </p>

	  <h2>Visibilit� des documents joints</h2>
	  <p>
		<input type="checkbox"
			   name="cdt_possibilite_masquer_pj"
			   id="cdt_possibilite_masquer_pj"
			   onchange='changement();'
			   title="Visibilit� des documents joints"
			   value="y"
		       <?php if(getSettingValue("cdt_possibilite_masquer_pj")=="y") {echo " checked";} ?>
			   />
		<label for='cdt_possibilite_masquer_pj'> Possibilit� pour les professeurs de cacher aux �l�ves et responsables les documents joints aux Cahiers de textes.</label>
	  </p>

	  <h2>Visa des cahiers de texte</h2>
	  <fieldset class="no_bordure">
		<legend class="invisible">Visa</legend>
		  <input type='radio'
				 name='visa_cdt_inter_modif_notices_visees'
				 id='visa_cdt_inter_modif_notices_visees_y'
				 value='yes'
			 onchange='changement();'
			   <?php if (getSettingValue("visa_cdt_inter_modif_notices_visees") == "yes") echo " checked='checked'";?> />
		<label for='visa_cdt_inter_modif_notices_visees_y' style='cursor: pointer;'>
		 Activer l'interdiction pour les enseignants de modifier une notice ant�rieure � la date fix�e lors du visa de leur cahier de textes.
		</label>
	  <br />
		  <input type='radio'
				 name='visa_cdt_inter_modif_notices_visees'
				 id='visa_cdt_inter_modif_notices_visees_n'
				 value='no'
			 onchange='changement();'
			   <?php if (getSettingValue("visa_cdt_inter_modif_notices_visees") == "no") echo " checked='checked'";?> />
		<label for='visa_cdt_inter_modif_notices_visees_n' style='cursor: pointer;'>
		  D�sactiver l'interdiction pour les enseignants de modifier une notice apr�s la signature
		  des cahiers de textes
		</label>
	  </fieldset>


	  <h2>Cahiers de texte en commun</h2>
	  <fieldset class="no_bordure">
		<legend class="invisible">Cahiers de texte en commun</legend>
			<p>Dans le CDT2, par d�faut, un professeur ne peut pas modifier une notice/devoir r�alis� par un coll�gue, m�me si il s'agit d'un enseignement partag� (<i>plusieurs professeurs devant un m�me groupe d'�l�ves</i>).<br />
			Pour modifier ce param�trage&nbsp;:</p>
		  <input type='radio'
				 name='cdt_autoriser_modif_multiprof'
				 id='cdt_autoriser_modif_multiprof_y'
				 value='yes'
			 onchange='changement();'
			   <?php if (getSettingValue("cdt_autoriser_modif_multiprof") == "yes") {echo " checked='checked'";}?> />
		<label for='cdt_autoriser_modif_multiprof_y' style='cursor: pointer;'>
		  Autoriser les coll�gues travaillant en binome sur une enseignement � modifier les notices/devoirs cr��s par leur coll�gue.
		</label>
	  <br />
		  <input type='radio'
				 name='cdt_autoriser_modif_multiprof'
				 id='cdt_autoriser_modif_multiprof_n'
				 value='no'
			 onchange='changement();'
			   <?php if ((getSettingValue("cdt_autoriser_modif_multiprof") == "no")||(getSettingValue("cdt_autoriser_modif_multiprof") == "")) {echo " checked='checked'";}?> />
		<label for='cdt_autoriser_modif_multiprof_n' style='cursor: pointer;'>
		  Interdire la modification de notice/devoir cr��s par leur coll�gue.
		</label>
	  </fieldset>


	  <p class="center">
		<input type="submit" value="Enregistrer" />
	  </p>
	</form>

	<hr />
	
	<h2>Gestion des cahiers de textes</h2>
	<ul>
	  <li><a href='modify_limites.php'>Espace disque maximal, taille maximale d'un fichier</a></li>
	  <li><a href='modify_type_doc.php'>Types de fichiers autoris�s en t�l�chargement</a></li>
	  <li><a href='admin_ct.php'>Administration des cahiers de textes</a> (recherche des incoh�rences, modifications, suppressions)</li>
	  <li><a href='visa_ct.php'>Viser les cahiers de textes</a> (Signer les cahiers de textes)</li>
	  <li><a href='index.php?ajout_index_documents=y'>Prot�ger les sous-dossiers de 'documents/' contre des acc�s anormaux</a></li>
	  <li><a href='../cahier_texte_2/archivage_cdt.php'>Archivage des cahiers de textes en fin d'ann�e scolaire</a></li>
	  <li><a href='../cahier_texte_2/export_cdt.php'>Export de cahiers de textes et acc�s inspecteur (<i>sans authentification</i>)</a></li>
	</ul>
	
	<hr />
	
	<h2>Astuce</h2>
	<p>
	  Si vous souhaitez n'utiliser que le module Cahier de textes dans Gepi, consultez la page suivante&nbsp;:
	  <br />
	  <a href='http://www.sylogix.org/projects/gepi/wiki/Use_only_cdt'>
		http://www.sylogix.org/projects/gepi/wiki/Use_only_cdt
	  </a>
	</p>

	<hr />

	<h2>Rappel du B.O.</h2>

	<?php
		require("../lib/textes.inc.php");
		echo $cdt_texte_bo;
	?>

<!-- D�but du pied -->
	<div id='EmSize' style='visibility:hidden; position:absolute; left:1em; top:1em;'></div>

	<script type='text/javascript'>
		var ele=document.getElementById('EmSize');
		var em2px=ele.offsetLeft
		//alert('1em == '+em2px+'px');
	</script>


	<script type='text/javascript'>
		temporisation_chargement='ok';
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


