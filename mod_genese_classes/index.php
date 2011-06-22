<?php
/* $Id$ */
/*
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



$sql="SELECT 1=1 FROM droits WHERE id='/mod_genese_classes/index.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/mod_genese_classes/index.php',
administrateur='V',
professeur='F',
cpe='F',
scolarite='F',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='G�n�se des classes: Accueil',
statut='';";
$insert=mysql_query($sql);
}




//======================================================================================
// Section checkAccess() � d�commenter en prenant soin d'ajouter le droit correspondant:
if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}
//======================================================================================


//=========================================================

// Cr�ation des tables

$sql="CREATE TABLE IF NOT EXISTS gc_projets (
id smallint(6) unsigned NOT NULL auto_increment,
projet VARCHAR( 255 ) NOT NULL ,
commentaire TEXT NOT NULL ,
PRIMARY KEY ( id )
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS gc_divisions (
id int(11) unsigned NOT NULL auto_increment,
projet VARCHAR( 255 ) NOT NULL ,
id_classe smallint(6) unsigned NOT NULL,
classe varchar(100) NOT NULL default '',
statut enum( 'actuelle', 'future', 'red', 'arriv' ) NOT NULL DEFAULT 'future',
PRIMARY KEY ( id )
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS gc_options (
id int(11) unsigned NOT NULL auto_increment,
projet VARCHAR( 255 ) NOT NULL ,
opt VARCHAR( 255 ) NOT NULL ,
type ENUM('lv1','lv2','lv3','autre') NOT NULL ,
obligatoire ENUM('o','n') NOT NULL ,
exclusive smallint(6) unsigned NOT NULL,
PRIMARY KEY ( id )
);";
//echo "$sql<br />";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS gc_options_classes (
id int(11) unsigned NOT NULL auto_increment,
projet VARCHAR( 255 ) NOT NULL ,
opt_exclue VARCHAR( 255 ) NOT NULL ,
classe_future VARCHAR( 255 ) NOT NULL ,
commentaire TEXT NOT NULL ,
PRIMARY KEY ( id )
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS gc_ele_arriv_red (
login VARCHAR( 255 ) NOT NULL,
statut ENUM('Arriv','Red') NOT NULL ,
projet VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( login , projet )
);";
//echo "$sql<br />";
$create_table=mysql_query($sql);



$sql="CREATE TABLE IF NOT EXISTS gc_affichages (
id int(11) unsigned NOT NULL auto_increment,
id_aff int(11) unsigned NOT NULL,
id_req int(11) unsigned NOT NULL,
projet VARCHAR( 255 ) NOT NULL ,
type VARCHAR(255) NOT NULL,
valeur varchar(255) NOT NULL,
PRIMARY KEY ( id )
);";
$create_table=mysql_query($sql);


$sql="CREATE TABLE IF NOT EXISTS gc_eleves_options (
id int(11) unsigned NOT NULL auto_increment,
login VARCHAR( 255 ) NOT NULL ,
profil enum('GC','C','RAS','B','TB') NOT NULL default 'RAS',
moy VARCHAR( 255 ) NOT NULL ,
nb_absences VARCHAR( 255 ) NOT NULL ,
non_justifie VARCHAR( 255 ) NOT NULL ,
nb_retards VARCHAR( 255 ) NOT NULL ,
projet VARCHAR( 255 ) NOT NULL ,
id_classe_actuelle VARCHAR(255) NOT NULL ,
classe_future VARCHAR(255) NOT NULL ,
liste_opt VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( id )
);";
//echo "$sql<br />";
$create_table=mysql_query($sql);


$req_test=mysql_query("SHOW INDEXES FROM gc_ele_arriv_red WHERE Key_name='PRIMARY';");
$res_test=mysql_num_rows($req_test);
if ($res_test<2){
  $query=mysql_query("ALTER TABLE gc_ele_arriv_red DROP PRIMARY KEY;");
  if ($query) {
    $query=mysql_query("ALTER TABLE gc_ele_arriv_red ADD PRIMARY KEY ( login , projet );");
    if (!$query) {
      $msg="Echec de la d�finition de la cl� primaire sur 'login' et 'projet' dans 'gc_ele_arriv_red' : Erreur !<br />";
    }
  } else {
      $msg="Echec de la d�finition de la cl� primaire sur 'login' et 'projet' dans 'gc_ele_arriv_red' : Erreur !<br />Cela peut perturber la conservation des redoublants/arrivants lors de la copie de projet.<br />";
  }
}


//=========================================================

// Partie Projets

$projet=isset($_POST['projet']) ? $_POST['projet'] : (isset($_GET['projet']) ? $_GET['projet'] : NULL);
$choix_projet=isset($_POST['choix_projet']) ? $_POST['choix_projet'] : (isset($_GET['choix_projet']) ? $_GET['choix_projet'] : NULL);
$suppr_projet=isset($_POST['suppr_projet']) ? $_POST['suppr_projet'] : (isset($_GET['suppr_projet']) ? $_GET['suppr_projet'] : NULL);
$creer_projet=isset($_POST['creer_projet']) ? $_POST['creer_projet'] : (isset($_GET['creer_projet']) ? $_GET['creer_projet'] : NULL);
$copie_projet=isset($_POST['copie_projet']) ? $_POST['copie_projet'] : (isset($_GET['copie_projet']) ? $_GET['copie_projet'] : NULL);
$projet_new=isset($_POST['projet_new']) ? $_POST['projet_new'] : (isset($_GET['projet_new']) ? $_GET['projet_new'] : NULL);

if(isset($projet)) {
	if(isset($creer_projet)) {
		$projet=my_ereg_replace("[^A-Za-z0-9_]","",$projet);
		if($projet!="") {
			$sql="SELECT 1=1 FROM gc_projets WHERE projet='$projet';";
			$test=mysql_query($sql);
			if(mysql_num_rows($test)==0) {
				$sql="INSERT INTO gc_projets SET projet='$projet', commentaire='';";
				if($insert=mysql_query($sql)) {$msg="Le projet $projet a �t� cr��.";}
			}
			else {
				$msg="Un projet du m�me nom '$projet' existe d�j�.\n";
			}
		}
		else {
			$msg="Les caract�res du nom de projet '$projet' ne sont pas valides.\n";
		}
	}
	elseif(isset($suppr_projet)) {
		$projet=my_ereg_replace("[^A-Za-z0-9_]","",$projet);
		if($projet!="") {
			$sql="DELETE FROM gc_projets WHERE projet='$projet';";
			$del=mysql_query($sql);
			// Il y aura d'autres tables � nettoyer
			$sql="DELETE FROM gc_divisions WHERE projet='$projet';";
			$del=mysql_query($sql);
			$sql="DELETE FROM gc_options WHERE projet='$projet';";
			$del=mysql_query($sql);
			$sql="DELETE FROM gc_eleves_options WHERE projet='$projet';";
			$del=mysql_query($sql);
			$sql="DELETE FROM gc_affichages WHERE projet='$projet';";
			$del=mysql_query($sql);
			$sql="DELETE FROM gc_ele_arriv_red WHERE projet='$projet';";
			$del=mysql_query($sql);
			$sql="DELETE FROM gc_options_classes WHERE projet='$projet';";
			$del=mysql_query($sql);
			$msg="Suppression du projet '$projet' effectu�e.\n";
		}
		else {
			$msg="Les caract�res du nom de projet '$projet' ne sont pas valides.\n";
		}

		unset($projet);
	}
	elseif(isset($copie_projet)) {
		$projet_original=my_ereg_replace("[^A-Za-z0-9_]","",$projet);
		$projet=$projet_new;
		if($projet_original!="") {
			$projet=my_ereg_replace("[^A-Za-z0-9_]","",$projet);
			if($projet!="") {
				$sql="SELECT 1=1 FROM gc_projets WHERE projet='$projet';";
				$test=mysql_query($sql);
				if(mysql_num_rows($test)==0) {
					$sql="INSERT INTO gc_projets SET projet='$projet', commentaire='';";
					if($insert=mysql_query($sql)) {
						$msg="Le projet $projet a �t� cr��.";

						//,'gc_projets'
						//$tab_table=array('gc_affichages','gc_divisions','gc_ele_arriv_red','gc_eleve_fut_classe','gc_eleves_options','gc_options');
						$tab_table=array('gc_affichages','gc_divisions','gc_ele_arriv_red','gc_eleves_options','gc_options','gc_options_classes');
						for($j=0;$j<count($tab_table);$j++) {
							$sql="SELECT * FROM ".$tab_table[$j]." WHERE projet='$projet_original';";
							$res=mysql_query($sql);
							unset($nom_champ);
							for($i=0;$i<mysql_num_fields($res);$i++){
								$nom_champ[$i]=mysql_field_name($res,$i);
							}
							while($tab=mysql_fetch_array($res)) {
								$sql="INSERT INTO ".$tab_table[$j]." SET projet='$projet'";
								for($i=0;$i<count($nom_champ);$i++) {
									// Pour la recopie, on exclut les champs nom initial du projet et id (auto_increment)
									if(($nom_champ[$i]!='projet')&&($nom_champ[$i]!='id')) {$sql.=",".$nom_champ[$i]."='".$tab[$i]."'";}
								}
								$sql.=";";
								//echo "$sql<br />\n";
								$res2=mysql_query($sql);
							}
						}
					}
				}
				else {
					$msg="Un projet du m�me nom '$projet' existe d�j�.\n";
				}
			}
			else {
				$msg="Les caract�res du nom de projet '$projet' ne sont pas valides.\n";
			}
		}
		else {
			$msg="Les caract�res du nom de projet original '$projet_original' ne sont pas valides.\n";
		}
		unset($projet);
	}
}

$truncate_tables=isset($_GET['truncate_tables']) ? $_GET['truncate_tables'] : NULL;
if($truncate_tables=='y') {
	$msg="<p>Nettoyage des tables G�n�se des classes... <font color='red'>A FAIRE</font></p>\n";
	$sql="TRUNCATE TABLE ...;";
	//$del=mysql_query($sql);
}


//**************** EN-TETE *****************
$titre_page = "G�n�se classe: Accueil";
//echo "<div class='noprint'>\n";
require_once("../lib/header.inc");
//echo "</div>\n";
//**************** FIN EN-TETE *****************

//debug_var();

//echo "<div class='noprint'>\n";
echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\" name='form1'>\n";
echo "<p class='bold'><a href='../accueil.php'>Accueil</a>";
//echo "</p>\n";
//echo "</div>\n";

if(!isset($projet)) {
	echo "</p>\n";
	echo "</form>\n";

	echo "<h2>Projets</h2>\n";
	echo "<blockquote>\n";

	echo "<table class='boireaus' summary='Action sur les projets'>\n";
	echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\" name='form_creer_projet'>\n";
	echo "<tr class='lig1'>\n";
	echo "<td style='text-align:left;'>\n";
	echo "Cr�er un nouveau projet&nbsp;: </td><td style='text-align:left;'><input type='text' name='projet' value='' /> <input type='submit' name='creer_projet' value='Cr�er' />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</form>\n";

	$sql="SELECT * FROM gc_projets ORDER BY projet;";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)>0) {
		echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\" name='form_select_projet'>\n";
		echo "<tr class='lig-1'>\n";
		echo "<td style='text-align:left;'>\n";
		echo "S�lectionner un projet existant&nbsp;: </td><td style='text-align:left;'>";
		$lignes_select_projet="<select name='projet'>\n";
		while($lig=mysql_fetch_object($res)) {
			$lignes_select_projet.="<option value='$lig->projet'>$lig->projet</option>\n";
		}
		$lignes_select_projet.="</select>\n";
		echo $lignes_select_projet;
		echo " <input type='submit' name='choix_projet' value='Valider' />\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</form>\n";

		echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\" name='form_suppr'>\n";
		echo "<tr class='lig1'>\n";
		echo "<td style='text-align:left;'>\n";
		echo "<p>Supprimer un projet existant&nbsp;: <!--font color='red'>METTRE UN CONFIRM... ET ENSUITE VIRER LES ENREGISTREMENTS ASSOCI�S AU PROJET DANS TOUTES LES TABLES</font--></td><td style='text-align:left;'>";
		echo $lignes_select_projet;
		//echo " <input type='submit' name='suppr_projet' value='Supprimer' /></p>\n";
		echo " <input type='hidden' name='suppr_projet' value='Supprimer' />\n";

		echo " <input type='button' name='btn_suppr_projet' value='Supprimer' ";
		$themessage="Etes-vous s�r de vouloir supprimer le projet?";
		echo "onclick=\"confirm_submit('$themessage');\" />\n";

		echo "<script type='text/javascript'>
	function confirm_submit(themessage)
	{
		var is_confirmed = confirm(themessage);
		if(is_confirmed){
			document.form_suppr.submit();
		}
	}
</script>\n";

		echo "</td>\n";
		echo "</tr>\n";
		echo "</form>\n";



		echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\" name='form_copie_projet'>\n";
		echo "<tr class='lig-1'>\n";
		echo "<td style='text-align:left;'>\n";
		echo "Faire une copie d'un projet existant&nbsp;: </td><td style='text-align:left;'>";
		echo $lignes_select_projet;
		echo " sous le nom <input type='text' name='projet_new' value='' />";
		echo " <input type='submit' name='copie_projet' value='Copier' /></p>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</form>\n";

	}
	echo "</table>\n";

	echo "</blockquote>\n";

}
else {
	echo " | <a href='index.php'>Autre projet</a>&nbsp;: ";
	$sql="SELECT DISTINCT projet FROM gc_projets ORDER BY projet;";
	$res_proj=mysql_query($sql);
	echo "<select onchange='document.form1.submit();' name='projet'>\n";
	while ($lig_proj=mysql_fetch_object($res_proj)) {
		echo "<option value='$lig_proj->projet'";
		if($lig_proj->projet==$projet) {echo "selected";}
		echo ">$lig_proj->projet</option>\n";
	}
	echo "</select>\n";
	echo "</p>\n";
	echo "</form>\n";

	echo "<h2>Projet $projet</h2>\n";
	echo "<blockquote>\n";

	// Le projet est choisi:
	// Il faut si les classes, options,... ne sont pas encore choisies, choisir les classes actuelles, les futures,... et les options
	// Pouvoir ajouter une option, une classe,...

	echo "<ol>\n";
	echo "<li><a href='select_classes.php?projet=$projet'>Choisir les classes (<i>actuelles et futures</i>)</a></li>\n";
	echo "<li><a href='select_options.php?projet=$projet'>Choisir les options</a></li>\n";
	echo "<li><a href='liste_options.php?projet=$projet'>Lister les options actuelles des �l�ves</a></li>\n";
	echo "<li><a href='import_options.php?projet=$projet'>Importer les options futures des �l�ves d'apr�s un CSV</a></li>\n";
	echo "<li><a href='select_arriv_red.php?projet=$projet'>S�lection des �l�ves redoublants et/ou arrivants</a></li>\n";
	echo "<li><a href='saisie_contraintes_opt_classe.php?projet=$projet'>Saisir les contraintes sur les classes et options</a><br />(<i>pour exclure la pr�sence de certaines options sur certaines classes</i>)</li>\n";
	echo "<li><a href='select_eleves_options.php?projet=$projet'>Saisir les options des �l�ves</a></li>\n";
	echo "<li>";
	echo "<a href='affect_eleves_classes.php?projet=$projet'>Affecter les �l�ves dans les classes</a>\n";
	echo "</li>\n";
	echo "<li>";
	echo "<a href='affiche_listes.php?projet=$projet'>Affichage de listes</a>";
	echo "</li>\n";
	echo "</ol>\n";

	echo "</blockquote>\n";
}

echo "<p><i>NOTES</i>&nbsp;:</p>\n";
echo "<ul>\n";
echo "<li><p>Ce module est destin� � permettre de pr�parer en fin d'ann�e les classes de l'ann�e scolaire suivante.</p></li>\n";
echo "<li><p>Le principal indique les contraintes (<i>telles options sur telles classes uniquement,...</i>) et un ensemble de professeurs, cpe,... tente de fabriquer les classes en respectant les contraintes, en s�parant certains �l�ves, en maintenant ensemble d'autres �l�ves,...<br />
Faire participer les professeurs et cpe permet d'avoir les points de vue en classe et hors des classes.</p></li>\n";
echo "<li><p>Quelques �l�ments sur l'utilisation du dispositif&nbsp;:<br />
Les points 1 � 7 doivent �tre suivis dans l'ordre.<br />
Ensuite, on peut g�n�rer des listes d'�l�ves group�s par options afin de pr�parer sur papier les destinations possibles des �l�ves des diff�rents groupes.<br />
Certains �l�ves doivent �tre affect�s dans certaines classes de fa�on imp�rative du fait de leur jeu d'options.<br />
On affecte ensuite des �l�ves en tentant de cr�er des t�tes de classes.<br />
On compl�te.<br />
On r�partit les cas restants.<br />
Et enfin, on g�n�re un affichage des listes de classes futures... ainsi que les regroupements de langues,...</p>
<p>On proc�de �ventuellement � quelques �changes, puis on pr�sente des listes au principal qui accepte ou non la r�partition propos�e.</p>
</li>\n";
echo "</ul>\n";

require("../lib/footer.inc.php");
?>
