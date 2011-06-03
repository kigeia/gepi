<?php
/*
 * $Id: index.php 2554 2008-10-12 14:49:29Z crob $
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

// SQL : INSERT INTO droits VALUES ( '/mod_ooo/gerer_modeles_ooo.php', 'V', 'F', 'F', 'F', 'F', 'F', 'F', 'F', 'Mod�le Ooo : Index', '');
// maj : $tab_req[] = "INSERT INTO droits VALUES ( '/mod_ooo/gerer_modeles_ooo.php', 'V', 'F', 'F', 'F', 'F', 'F', 'F', 'F', 'Mod�le Ooo : Index', '');;";
if (!checkAccess()) {
    header("Location: ../logout.php?auto=1");
	die();
}


include_once('./lib/lib_mod_ooo.php'); //les fonctions
$nom_fichier_modele_ooo =''; //variable � initialiser � blanc pour inclure le fichier suivant et �viter une notice. Pour les autres inclusions, cela est inutile.
include_once('./lib/chemin.inc.php'); // le chemin des dossiers contenant les  mod�les

//Liste des fichiers � compl�ter � la main (3 donn�es par fichier)
    // L'ent�te de la section pour le 1er fichier de la section sinon "" (vide)
    //Le nom du fichier en minuscule avec son extension
	//La description du document
	
    //Retenue
    $entete_section[]="MODULE DISCIPLINE";
	$fich[]="retenue.odt";
    $utilisation[]="Formulaire de retenue";	
    //rapport incident
    $entete_section[]="";
	$fich[]="rapport_incident.odt";
    $utilisation[]="Formulaire de rapport d'incident";
	//Exclusion temporaire
    $entete_section[]="";
	$fich[]="discipline_exclusion.odt";
    $utilisation[]="Exclusion temporaire de l'�tablissement";

    //mod�le ABS2
	$entete_section[]="MODULE ABSENCE";
    $fich[]="absence_extraction_demi-journees.ods";
    $utilisation[]="ABS2 : Tableau des demi-journ�es d'absences";
	
	$entete_section[]="";
    $fich[]="absence_extraction_saisies.ods";
    $utilisation[]="ABS2 : Tableau des saisies d'absences";
	
	$entete_section[]="";
    $fich[]="absence_extraction_traitements.ods";
    $utilisation[]="ABS2 : Tableau des traitements d'absences";

    $entete_section[]="";
    $fich[]="absence_extraction_bilan.ods";
    $utilisation[]="ABS2 : Tableau bilan par jour par �l�ve au format tableur";

    $entete_section[]="";
    $fich[]="absence_extraction_bilan.odt";
    $utilisation[]="ABS2 : Tableau bilan par jour par �l�ve au format traitement de textes";

	$entete_section[]="";
    $fich[]="absence_modele_lettre_parents.odt";
    $utilisation[]="ABS2 : Mod�le de lettre aux parents";
	
	$entete_section[]="";
    $fich[]="absence_modele_impression_par_lot.odt";
    $utilisation[]="ABS2 : Mod�le d'impression par lot";

	$entete_section[]="";
    $fich[]="absence_email.txt";
    $utilisation[]="ABS2 : Mod�le du courriel envoy� aux parents";
	
	$entete_section[]="";
    $fich[]="absence_sms.txt";
    $utilisation[]="ABS2 : Mod�le de SMS envoy� aux parents";


    //Fiches brevet
	$entete_section[]="MODULE NOTANET";
    $fich[]="fb_CLG_lv2.ods";
    $utilisation[]="Fiche brevet s�rie coll�ge LV2";
	
	$entete_section[]="";
    $fich[]="fb_CLG_dp6.ods";
    $utilisation[]="Fiche brevet s�rie coll�ge ODP 6 heures";
	
	$entete_section[]="";
    $fich[]="fb_PRO.ods";
    $utilisation[]="Fiche brevet s�rie professionnelle sans ODP";
	
	$entete_section[]="";
    $fich[]="fb_PRO_dp6.ods";
    $utilisation[]="Fiche brevet s�rie professionnelle ODP 6 heures";
	
	$entete_section[]="";
    $fich[]="fb_PRO_agri.ods";
    $utilisation[]="Fiche brevet s�rie professionnelle option agricole";
	
	$entete_section[]="";
    $fich[]="fb_TECHNO.ods";
    $utilisation[]="Fiche brevet s�rie technologique sans ODP";
	
	$entete_section[]="";
    $fich[]="fb_TECHNO_dp6.ods";
    $utilisation[]="Fiche brevet s�rie technologique ODP 6 heures";
	
	$entete_section[]="";
    $fich[]="fb_TECHNO_agri.ods";
    $utilisation[]="Fiche brevet s�rie technologique option agricole";

    //rapport incident
	$entete_section[]="MODULE ECTS";
    $fich[]="documents_ects.odt";
    $utilisation[]="Documents ECTS (pour BTS, pr�pas...)";
	
	
    $nbfich=sizeof($fich);
// Fin liste des fichiers

$PHP_SELF=basename($_SERVER['PHP_SELF']);
creertousrep($nom_dossier_modeles_ooo_mes_modeles.$rne);

$retour_apres_upload=isset($_POST['retour_apres_upload']) ? $_POST['retour_apres_upload'] : (isset($_GET['retour_apres_upload']) ? $_GET['retour_apres_upload'] : NULL);
if(!isset($retour_apres_upload)) {
	$retour=$_SESSION['retour'];
	$_SESSION['retour']=$_SERVER['PHP_SELF'] ;
}
else {
	$retour="../accueil.php";
}

//**************** EN-TETE *****************
$titre_page = "Mod�le Open Office - g�rer ses mod�les";
require_once("../lib/header.inc");
//**************** FIN EN-TETE *****************
echo "<SCRIPT LANGUAGE=\"Javascript\" SRC=\"./lib/mod_ooo.js\"> </SCRIPT>";
//debug_var();

if (isset($_GET['op'])) { $op=$_GET["op"]; }
if (isset($_GET['fic'])) { $fic=$_GET["fic"]; }
if (isset($_POST['btn'])) { $btn=$_POST["btn"]; }
if (isset($_POST['fich_cible'])) { $fich_cible=$_POST["fich_cible"]; }

echo "<p class='bold'><a href='".$retour;
if(isset($btn)) {echo "?retour_apres_upload=y";}
echo "'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
echo "</p>\n";
echo "<br />\n";
echo "<p>Ce module est destin� � g�rer les mod�les Open Office de Gepi.</p>\n";
echo "</p>\n";
echo "<br />\n";

if ((isset($op)) && ($op=="supp")) { //Supprimer un fichier perso
     // alert("EFFACER $fic");
	  @unlink("$nom_dossier_modeles_ooo_mes_modeles$rne$fic");
}

echo "<body>";


if (!isset($btn)) { //premier passage : formulaire
    echo "<p >Un mod�le personnalis�, envoy� sur le serveur sera utilis� par Gepi</p><hr>\n";
    echo "<p >Peu importe le nom actuel (gardez le format Open Office : ODT - texte, ODS - tableur ou txt - texte), chaque fichier sera renomm� correctement.<br />\n";
    echo "Les fichiers personnalis�s peuvent �tre supprim�s (ic�ne poubelle), contrairement � ceux par d�faut.<br />\n";
	echo "L'ensemble des fichiers peut �tre consult� en cliquant sur leur ic�ne.</p><br />\n";
	echo "Lorsque vous cr�ez un nouveau mod�le, bien faire attention � la syntaxe des variables utilis�es dans le mod�le par d�faut.</p><br />\n";
    echo "Elles sont sensibles � la case. Le format d'une variable est [var.xxxxx]</p><br /><br />\n";
    echo "<p><u>Cas particulier du mod�le de lettre aux parents pour le module absence 2 : </u><br />\n";
    echo "Une modification trop importante de ce mod�le peut entrainer des disfonctionnements ou des probl�mes de mise en page avec la fonctionnalit� d'impression par lot des courriers. </u><br />\n";
    echo "C'est pourquoi il est recommand�, dans ce cas l�, de se limiter a des modifications simple (nature du texte par exemple) du mod�le de base propos� dans G�pi.</p><br /><br />\n";
    //Tableau des diff�rents fichiers � envoyer
    echo "<table class='boireaus' align='center'>\n";
    echo "<tr>\n";
    echo "<td>Mod�le initial<br/>Visualiser</td>\n";
    echo "<td>Mod�le personnel <br/>Supprimer / Visualiser</td>\n";
    echo "<td>Nom du fichier</td>\n";
    echo "<td>Description du fichier</td>\n";
    echo "<td>Choisir le fichier <br/>� t�l�charger</td>\n";
    echo "<td>Action</td>\n";
    echo "</tr>\n";
	$alt=1;
    for ($i=0;$i<$nbfich;$i++) {
	  $alt=$alt*(-1);
      //Une ligne du tableau
      //paire ou impaire	  
	  if ($entete_section[$i] != "") { // Cas d'un ent�te
	      echo "<tr>";
	      echo "<td colspan=\"6\"></br></br><b>$entete_section[$i]</br></br></b></br></br></td>";
		  echo "</tr>";
	  }
	  echo "<tr class='lig$alt'><form name=\"form$i\" method='post' ENCTYPE='multipart/form-data' action='$PHP_SELF' onsubmit=\"return bonfich('$i')\" >\n";
	echo add_token_field();
	  echo "<input type=\"hidden\" name=fich_cible value=$fich[$i] >\n";
		 $type_ext = renvoi_nom_image(extension_nom_fichier($fich[$i]));
		 echo "<td align='center'><a href=\"$nom_dossier_modeles_ooo_par_defaut$fich[$i]\"><img src=\"./images/$type_ext\" border=\"0\" title=\"Consulter le mod�le par d�faut\"></a>\n";
		 echo "</td>\n";
	  if  (file_exists($nom_dossier_modeles_ooo_mes_modeles.$rne.$fich[$i]))   {
		 echo "<td align='center'><a href=\"$PHP_SELF?op=supp&fic=$fich[$i]".add_token_in_url()."\" onclick='return confirmer()'><img src=\"./images/poubelle.gif\" border=\"0\" title=\"ATTENTION, suppression imm�diate !\"></a>\n";
		 echo "&nbsp;&nbsp;<a HREF=\"$nom_dossier_modeles_ooo_mes_modeles$rne$fich[$i]\"><img src=\"./images/$type_ext\" border=\"0\" title=\"Consulter le nouveau mod�le\"></a>\n";
		 echo "</td>\n";
	  } else {
		 echo "</td>\n<td>&nbsp;</td>\n";
	  }

	  echo "<td>$fich[$i]</td><td>\n";
	  echo "$utilisation[$i]</td><td>\n";
	  echo "<INPUT TYPE=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"512000\">";
	  echo "<input type='file' name='monfichier' value='il a cliqu� le bougre'>&nbsp;</td><td>\n";
	  echo "&nbsp;&nbsp;<input type='submit' name='btn' Align='middle' value='Envoyer' />&nbsp;&nbsp;  \n";
	  echo "</td></form>\n";
	  echo "</tr>\n";
    }
    echo "</table>\n";

}
else { // passage 2 : le nom du fichier a �t� choisi
    //print_r($_FILES['monfichier']);
	echo "<h2>fichier envoy� : ".$_FILES['monfichier']['name']." </h2>\n";
	check_token();
    $desterreur=$PHP_SELF;
    $dest=$desterreur;
    //alert($dest);

    //R�cup du fichier t�l�charg�
    $t=$_FILES['monfichier'];
    //print_r($t);

    $monfichiername=$t['name'];
    $monfichiertype=$t['type'];
    $monfichiersize=$t['size'];
    $monfichiertmp_name=$t['tmp_name'];

    if ($monfichiername=="") {
       alert ("Pas de fichier indiqu� ! Il faut recommencer...");
       $dest=$desterreur;
       echo "<script language='JavaScript'>\n";
       echo "<!-- \n";
       echo "w=window.open('','mafenetre');\n"; //r�cup�rer le m�me objet fen�tre
       echo "w.document.writeln('<h3>Fermeture en cours...</h3>');\n";
       echo "// - JavaScript - -->\n";
       echo "</script>\n";
       aller_a($dest);
    }
    else {
        echo "<script language='JavaScript'>\n";
        echo "<!-- \n";
        echo "w=window.open('','mafenetre');\n"; //r�cup�rer le m�me objet fen�tre
        echo "w.document.writeln('<h3>copie en cours...</h3>');\n";
        echo "// - JavaScript - -->\n";
        echo "</script>\n";


        $fichiercopie=strtolower($monfichiername);
        //alert("fichier copi� : ".$fichiercopie);

        $cible=$nom_dossier_modeles_ooo_mes_modeles.$rne.$fich_cible;
        //alert("avant la copie".$cible);
        if (!move_uploaded_file($monfichiertmp_name,$cible)) {
            echo "Erreur de copie<br />\n";
            echo "origine     : $monfichiername <br />\n";
            echo "destination : $nom_dossier_modeles_ooo_mes_modeles$rne".$fichiercopie;
            $me="La copie ne s'est pas effectu�e !\n V�rifiez la taille du fichier (max 512ko)\n";
            alert($me);
            $dest=$desterreur;
        }
        else {
            //echo "<p>$cible a �t� copi�</p>";
            $dest.="?fichier=$cible";
            echo($fich_cible." a �t� copi� correctement :<br />");
            echo "<p align='center'>";
            unset($monfichiername);
            echo "<form name='retour' method='POST' action='$PHP_SELF'>\n";
            echo "<input type='hidden' name='retour_apres_upload' value='y' />\n";
            echo "<input type='submit' name='ret' Align='middle' value='Retour' />\n";
            echo "</form>\n";

            }
        } //fin de monfichier != ""
        echo "<script language='JavaScript'>\n";
        echo "<!-- JavaScript\n";
        echo "w.close()\n";
        echo "// - JavaScript - -->\n";
        echo "</script>\n";

}
?>
</body>
</html>
