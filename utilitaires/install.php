<?php
/*
* $Id$
*
* Copyright 2001, 2005 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
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
require_once("../lib/global.inc");
$nom_fic = "../secure/connect.inc.php";

function test_ecriture_secure() {
	$ok = 'no';
	if ($f = @fopen("../secure/test", "w")) {
		@fputs($f, '<'.'?php $ok = "yes"; ?'.'>');
		@fclose($f);
		include("../secure/test");
		$del = @unlink("../secure/test");
	}
	return $ok;
}


function begin_html() {
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Installation de GEPI</title>
<meta HTTP-EQUIV="Content-Type" content="text/html; charset=iso-8859-1" />
<META HTTP-EQUIV="Pragma" CONTENT="no-cache" />
<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache" />
<META HTTP-EQUIV="Expires" CONTENT="0" />
<LINK REL="stylesheet" href="../style.css" type="text/css" />
<link rel="shortcut icon" type="image/x-icon" href="../favicon.ico" />
<link rel="icon" type="image/ico" href="../favicon.ico" />
</head>
<body>
<center>
<table width="450">
<tr><td width="450">
	<?php
}

function end_html() {
	echo '
	</td></tr></table>
	</center>
	</body>
	</html>
	';
}

unset($etape);
$etape = isset($_POST["etape"]) ? $_POST["etape"] : (isset($_GET["etape"]) ? $_GET["etape"] : NULL);


if (file_exists($nom_fic)) {
	require_once("../secure/connect.inc.php");
	if (@mysql_connect("$dbHost", "$dbUser", "$dbPass")) {
		if (@mysql_select_db("$dbDb")) {
			$call_test = @mysql_query("SELECT * FROM setting WHERE NAME='sessionMaxLength'");
			$test2 = @mysql_num_rows($call_test);
			$call_test = @mysql_query("SELECT * FROM utilisateurs");
			$test3 = @mysql_num_rows($call_test);
			if (($test2 !=0) and ($test3 !=0)) {
				begin_html();
				if ($etape == 5) {
					echo "<br /><h2 class='gepi'>Derni�re �tape : C'est termin� !</h2>\n";
					echo "<p>&nbsp;</p>\n";
					echo "<p>Vous pouvez maintenant commencer � utiliser GEPI ...</p>\n";
					echo "<p>Pour vous connecter la premi�re fois en tant qu'administrateur, utilisez le nom de connection \"admin\" et le mot de passe \"azerty\". N'oubliez pas de changer le mot de passe !</p>\n";
					echo "<br /><center><a href = '../login.php'>Se connecter � GEPI</a></center>\n";
				} else {
					echo "<h2 class='gepi'>Espace interdit - GEPI est d�j� install�.</h2>\n";
				}
				end_html();
				die();
			}
		}
	}
}

if ($etape == 4) {

	begin_html();

	echo "<br /><h2 class='gepi'>Quatri�me �tape : Cr�ation des tables de la base</h2>\n";
	echo "<p>";

	$link = mysql_connect($_POST['adresse_db'], $_POST['login_db'], $_POST['pass_db']);

	if ($_POST['choix_db'] == "new_gepi") {
		$sel_db = $_POST['table_new'];
		$result=mysql_query("CREATE DATABASE `$sel_db`;");
	}
	else {
		$sel_db = $_POST['choix_db'];
	}
	mysql_select_db("$sel_db");

	$fd = fopen("../sql/structure_gepi.sql", "r");
	$result_ok = 'yes';
	while (!feof($fd)) {
		//=============================================
		// MODIF: boireaus d'apr�s P.Chadefaux 20071110
		//$query = fgets($fd, 5000);
		// Ligne 113 du structure_gepi.sql, le CREATE TABLE `model_bulletin` comporte 6799 caract�res.
		$query = fgets($fd, 8000);
		//=============================================
		$query = trim($query);
		//=============================================
		// MODIF: boireaus 20080218
		//if (substr($query,-1)==";") {
		if((substr($query,-1)==";")&&(substr($query,0,3)!="-- ")) {
		//=============================================
			$reg = mysql_query($query);
			if (!$reg) {
				echo "<p><font color=red>ERROR</font> : '$query' : ";
				echo "<p>Erreur retourn�e : ".mysql_error()."</p>\n";
				$result_ok = 'no';
			}
		}
	}
	fclose($fd);

	if ($result_ok == 'yes') {
		$fd = fopen("../sql/data_gepi.sql", "r");
		while (!feof($fd)) {
			$query = fgets($fd, 5000);
			$query = trim($query);
			//=============================================
			// MODIF: boireaus 20080218
			//if (substr($query,-1)==";") {
			if((substr($query,-1)==";")&&(substr($query,0,3)!="-- ")) {
			//=============================================
				$reg = mysql_query($query);
				if (!$reg) {
					echo "<p><font color=red>ERROR</font> : '$query'</p>\n";
					echo "<p>Erreur retourn�e : ".mysql_error()."</p>\n";
					$result_ok = 'no';
				}
			}
		}
		fclose($fd);
	}

	if ($result_ok == 'yes') {
		$ok = 'yes';
		if (file_exists($nom_fic)) @unlink($nom_fic);
		if (file_exists("../secure/connect.cfg")) @unlink("../secure/connect.cfg");
		$f = @fopen($nom_fic, "wb");
		if (!$f) {
			$ok = 'no';
		} else {
			$url = parse_url($_SERVER['REQUEST_URI']);
			//$pathgepi = explode("/",$url['path']);
			//$gepipath = "/".$pathgepi[1];
			$gepipath = substr($url['path'], 0, -24);
			$conn = "<"."?php\n";
			$conn .= "# La ligne suivante est � modifier si vous voulez utiliser le multisite\n";
			$conn .= "\$multisite = 'n';\n";
			$conn .= "# Les cinq lignes suivantes sont � modifier selon votre configuration\n";
			$conn .= "# Pensez � renommer ce fichier connect.cfg.php en connect.inc.php\n";
			$conn .= "#\n";
			$conn .= "# ligne suivante : le nom du serveur qui herberge votre base mysql.\n";
			$conn .= "# Si c'est le m�me que celui qui heberge les scripts, mettre \"localhost\"\n";
			$conn .= "\$dbHost=\"".$_POST['adresse_db']."\";\n";
			$conn .= "# ligne suivante : le nom de votre base mysql\n";
			$conn .= "\$dbDb=\"$sel_db\";\n";
			$conn .= "# ligne suivante : le nom de l'utilisateur mysql qui a les droits sur la base\n";
			$conn .= "\$dbUser=\"".$_POST['login_db']."\";\n";
			$conn .= "# ligne suivante : le mot de passe de l'utilisateur mysql ci-dessus\n";
			$conn .= "\$dbPass=\"".$_POST['pass_db']."\";\n";
			$conn .= "# Chemin relatif vers GEPI\n";
			$conn .= "\$gepiPath=\"$gepipath\";\n";
			$conn .= "#\n";
			$conn .= "# Authentification par CAS ?\n";
			$conn .= "# Si vous souhaitez int�grer Gepi dans un environnement SSO avec CAS,\n";
			$conn .= "# vous devrez renseigner le fichier /secure/config_cas.inc.php avec les\n";
			$conn .= "# informations n�cessaires � l'identification du serveur CAS\n";
			$conn .= "\$use_cas = false; // false|true\n";
			$conn .= "?".">";

			@fputs($f, $conn);
			if (!@fclose($f)) $ok='no';
		}

		if ($ok == 'yes') {
			echo "<B>La structure de votre base de donn�es est install�e.</B>\n<p>Vous pouvez passer � l'�tape suivante.</p>\n";
			echo "<FORM ACTION='install.php' METHOD='post'>\n";
			echo "<INPUT TYPE='hidden' NAME='etape' VALUE='5' />\n";
			echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>' /></div>\n";
			echo "</FORM>\n";
		}
	}

	if (($result_ok != 'yes') or ($ok != 'yes')) {
		echo "<p><B>L'op�ration a �chou�.</B> Retournez � la page pr�c�dente, s�lectionnez une autre base ou cr�ez-en une nouvelle. V�rifiez les informations fournies par votre h�bergeur.</p>\n";
	}

	end_html();

}

else if ($etape == 3) {

	begin_html();

	echo "<br /><h2 class='gepi'>Troisi�me �tape : Choix de votre base</h2>\n";

	echo "<p>&nbsp;</p>\n";

	echo "<FORM ACTION='install.php' METHOD='post'>\n";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='4' />\n";
	echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"".$_POST['adresse_db']."\" SIZE='40' />\n";
	echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"".$_POST['login_db']."\" />\n";
	echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"".$_POST['pass_db']."\" />\n";

	$link = @mysql_connect($_POST['adresse_db'],$_POST['login_db'],$_POST['pass_db']);
	$result = @mysql_list_dbs();

	echo "<fieldset><label><B>Choisissez votre base :</B><br /></label>\n";
	$checked = false;
	if ($result AND (($n = @mysql_num_rows($result)) > 0)) {
		echo "<p><B>Le serveur MySQL contient plusieurs bases de donn�es.<br />S�lectionnez celle dans laquelle vous voulez implanter GEPI</b></p>\n";
		echo "<UL>\n";
		$bases = "";
		for ($i = 0; $i < $n; $i++) {
			$table_nom = mysql_dbname($result, $i);
			//$base = "<INPUT NAME=\"choix_db\" VALUE=\"".$table_nom."\" TYPE=Radio id='tab$i'";
			//$base_fin = " /><label for='tab$i'>".$table_nom."</label><br />\n";
			$base = "<li style='list-style-type:none;'><INPUT NAME=\"choix_db\" VALUE=\"".$table_nom."\" TYPE=Radio id='tab$i'";
			$base_fin = " /><label for='tab$i'>".$table_nom."</label></li>\n";
			if ($table_nom == $_POST['login_db']) {
				$bases = "$base CHECKED$base_fin".$bases;
				$checked = true;
			}
			else {
				$bases .= "$base$base_fin\n";
			}
		}
		echo $bases."</UL>\n";
		echo "ou... ";
	}
	else {
		echo "<B>Le programme d'installation n'a pas pu lire les noms des bases de donn�es install�es.</B>Soit aucune base n'est disponible, soit la fonction permettant de lister les bases a �t� d�sactiv�e pour des raisons de s�curit�.<p>\n";
		if ($_POST['login_db']) {
			echo "Dans la seconde alternative, il est probable qu'une base portant votre nom de connexion soit utilisable :\n";
			echo "<UL>\n";
			echo "<INPUT NAME=\"choix_db\" VALUE=\"".$_POST['login_db']."\" TYPE=Radio id='stand' CHECKED />\n";
			echo "<label for='stand'>".$_POST['login_db']."</label><br />\n";
			echo "</UL>\n";
			echo "ou... ";
			$checked = true;
		}
	}
	echo "<INPUT NAME=\"choix_db\" VALUE=\"new_gepi\" TYPE=Radio id='nou'";
	if (!$checked) echo " CHECKED";
	echo " /> <label for='nou'>Cr�er une nouvelle base de donn�es :</label> ";
	echo "<INPUT TYPE='text' NAME='table_new' CLASS='fondo' VALUE=\"gepi\" SIZE='20' /></fieldset>\n\n";
	echo "<p><b>Attention</b> : lors de la prochaine �tape :</p>\n";
	echo "<ul>\n";
	if (file_exists($nom_fic)) echo "<li>le fichier \"".$nom_fic."\" sera actualis� avec les donn�es que vous avez fourni,</li>\n";
	echo "<LI>les tables GEPI seront cr��es dans la base s�lectionn�e. Si celle-ci contient d�j� des tables GEPI, ces tables, ainsi que les donn�es qu'elles contiennent, seront supprim�es et remplac�es par une nouvelle structure.</LI>\n</ul>\n";

	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>' /></div>\n";


	echo "</FORM>\n";

	end_html();

}

else if ($etape == 2) {
	begin_html();

	echo "<br /><h2 class='gepi'>Deuxi�me �tape : Essai de connexion au serveur Mysql</h2>\n";

	echo "<!--";
	$link = mysql_connect($_POST['adresse_db'],$_POST['login_db'],$_POST['pass_db']);
	$db_connect = mysql_errno();
	echo "-->\n";

	//echo "<P>\n";

	if (($db_connect=="0") && $link){
		echo "<B>La connexion a r�ussi.</B><p> Vous pouvez passer � l'�tape suivante.</p>\n";

		echo "<FORM ACTION='install.php' METHOD='post'>\n";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='3' />\n";
		echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"".$_POST['adresse_db']."\" SIZE='40' />\n";
		echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"".$_POST['login_db']."\" />\n";
		echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"".$_POST['pass_db']."\" />\n";

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>' /></div>\n";

		echo "</FORM>\n";
	}
	else {
		echo "<B>La connexion au serveur MySQL a �chou�.</B>\n";
		echo "<p>Revenez � la page pr�c�dente, et v�rifiez les informations que vous avez fournies.</p>\n";
		echo mysql_error();
	}

	end_html();

}
else if ($etape == 1) {
	begin_html();

	echo "<br />\n<h2 class='gepi'>Premi�re �tape : la connexion MySQL</h2>\n";

	echo "<P>Vous devez avoir en votre possession les codes de connexion au serveur MySQL. Si ce n'est pas le cas, contactez votre h�bergeur ou bien l'administrateur technique du serveur sur lequel vous voulez implanter GEPI.</p>\n";

	unset($adresse_db);
	$adresse_db = isset($_POST["adresse_db"]) ? $_POST["adresse_db"] : 'localhost';
	$login_db = '';
	$pass_db = '';

	echo "<FORM ACTION='install.php' METHOD='post'>\n";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='2' />\n";
	echo "<fieldset><label><B>Adresse de la base de donn�e</B><br /></label>\n";
	echo "(Souvent cette adresse correspond � celle de votre site, parfois elle correspond � la mention &laquo;localhost&raquo;, parfois elle est laiss�e totalement vide.)<br />\n";
	echo "<INPUT  TYPE='text' NAME='adresse_db' CLASS='formo' VALUE=\"$adresse_db\" SIZE='40' /></fieldset><br />\n";

	echo "<fieldset><label><B>L'identifiant de connexion</B><br /></label>\n";
	echo "<INPUT TYPE='text' NAME='login_db' CLASS='formo' VALUE=\"$login_db\" SIZE='40' /></fieldset><br />\n";

	echo "<fieldset><label><B>Le mot de passe de connexion</B><br /></label>\n";
	echo "<INPUT TYPE='password' NAME='pass_db' CLASS='formo' VALUE=\"$pass_db\" SIZE='40' /></fieldset><br />\n";

	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>' /></div>\n";
	echo "</FORM>\n";

	end_html();

} else if (!$etape) {
	$affiche_etape0 = 'no';
	$file_existe = 'no';
	if (file_exists($nom_fic)) {
		$affiche_etape0 = 'yes';
		$file_existe = 'yes';
	}
	// on test la possibilit� d'�crire dans le r�pertoire
	$test_write = test_ecriture_secure();
	if ($test_write == 'no') $affiche_etape0 = 'yes';

	if ($affiche_etape0 == 'yes') {
		begin_html();
		echo "<h2 class='gepi'>Installation de la base Mysql</h2>\n";
		echo "<FORM ACTION='install.php' METHOD='post'>\n";
		if ($test_write == 'no') {
			echo "<h3 class='gepi'>Probl�me de droits d'acc�s :</h3>\n";
			echo "<p>Le r�pertoire \"/secure\" n'est pas accessible en �criture.</p>\n";
			echo "<P>Utilisez votre client FTP afin de r�gler ce probl�me ou bien contactez l'administrateur technique. Une fois cette manipulation effectu�e, vous pourrez continuer en cliquant sur le bouton en bas de la page.</p>\n";
			echo "<INPUT TYPE='hidden' NAME='etape' VALUE='' />\n";
		} else {
			echo "<INPUT TYPE='hidden' NAME='etape' VALUE='1' />\n";
		}
		if ($file_existe == 'yes') {
			echo "<h3 class='gepi'>Pr�sence d'un fichier ".$nom_fic." :</h3>\n";
			echo "<p>Un fichier nomm� <b>\"connect.inc.php\"</b> est actuellement pr�sent dans le r�pertoire \"/secure\".
			C'est peut-�tre la trace d'une ancienne installation. Par ailleurs, ce fichier contient peut-�tre les informations de connexion � la base MySql que vous souhaitez conserver.
			<br /><b>Attention : ce fichier et ce qu'il contient sera supprim� lors de cette nouvelle installation</b>.</p>\n";
		}


		echo "<INPUT TYPE='submit' CLASS='fondl' Value = 'Continuer' NAME='Continuer' />\n";
		echo "</FORM>\n";
		end_html();
	} else {
		header("Location: ./install.php?etape=1");
	}
}
?>
