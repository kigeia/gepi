<?php
/*
 * @version: $Id$
 *
 * Copyright 2001, 2012 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Gabriel Fischer
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

// Initialisation des feuilles de style apr�s modification pour am�liorer l'accessibilit�
$accessibilite="y";

// Initialisations files
require_once("../lib/initialisations.inc.php");
require_once("../lib/transform_functions.php");

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
    header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
    die();
} else if ($resultat_session == '0') {
    header("Location: ../logout.php?auto=1");
    die();
}

// Il faudra corriger le droit dans utilitaires/updates/access_rights.inc.php
//$tab_req[] = "INSERT INTO droits VALUES ('/cahier_texte_2/see_all.php', 'F', 'V', 'V', 'V', 'V', 'V', 'F', 'F', 'Consultation des cahiers de texte', '');";
//$tab_req[] = "INSERT INTO droits VALUES ('/cahier_texte/see_all.php', 'F', 'V', 'V', 'V', 'V', 'V', 'F', 'F', 'Consultation des cahiers de texte', '');";
// Passer � V pour administrateur
if($_SESSION['statut']!='administrateur') {
	if (!checkAccess()) {
		header("Location: ../logout.php?auto=1");
		die();
	}
}

//On v�rifie si le module est activ�
if (getSettingValue("active_cahiers_texte")!='y') {
    die("Le module n'est pas activ�.");
}

//include "../lib/mincals.inc";


unset($day);
$day = isset($_POST["day"]) ? $_POST["day"] : (isset($_GET["day"]) ? $_GET["day"] : date("d"));
unset($month);
$month = isset($_POST["month"]) ? $_POST["month"] : (isset($_GET["month"]) ? $_GET["month"] : date("m"));
unset($year);
$year = isset($_POST["year"]) ? $_POST["year"] : (isset($_GET["year"]) ? $_GET["year"] : date("Y"));
$id_classe = isset($_POST["id_classe"]) ? $_POST["id_classe"] : (isset($_GET["id_classe"]) ? $_GET["id_classe"] : -1);
unset($id_groupe);
$id_groupe = isset($_POST["id_groupe"]) ? $_POST["id_groupe"] :(isset($_GET["id_groupe"]) ? $_GET["id_groupe"] :NULL);

if (is_numeric($id_groupe)) {
    $current_group = get_group($id_groupe);
} else {
    $current_group = false;
}

unset($selected_eleve);
$login_eleve = isset($_POST["login_eleve"]) ? $_POST["login_eleve"] :(isset($_GET["login_eleve"]) ? $_GET["login_eleve"] :false);
if ($login_eleve) {
	$selected_eleve = mysql_fetch_object(mysql_query("SELECT e.login, e.nom, e.prenom FROM eleves e WHERE login = '" . $login_eleve . "'"));
} else {
	$selected_eleve = false;
}

if ($_SESSION['statut'] == 'eleve') {
	$selected_eleve = mysql_fetch_object(mysql_query("SELECT e.login, e.nom, e.prenom FROM eleves e WHERE login = '".$_SESSION['login'] . "'"));
} elseif ($_SESSION['statut'] == "responsable") {
	$get_eleves = mysql_query("SELECT e.login, e.nom, e.prenom " .
			"FROM eleves e, resp_pers r, responsables2 re " .
			"WHERE (" .
			"e.ele_id = re.ele_id AND " .
			"re.pers_id = r.pers_id AND " .
			"r.login = '".$_SESSION['login']."')");

	if (mysql_num_rows($get_eleves) == 1) {
			// Un seul �l�ve associ� : on initialise tout de suite la variable $selected_eleve
			// Cela signifie entre autre que l'on ne prend pas en compte $login_eleve, fermant ainsi une
			// potentielle faille de s�curit�.
		$selected_eleve = mysql_fetch_object($get_eleves);
	} elseif (mysql_num_rows($get_eleves) == 0) {
		$selected_eleve = false;
	} elseif (mysql_num_rows($get_eleves) > 1 and $selected_eleve) {
		// Si on est l�, c'est que la variable $login_eleve a �t� utilis�e pour
		// g�n�rer $selected_eleve
		// On va v�rifier que l'�l�ve ainsi s�lectionn� fait bien partie des �l�ves
		// associ�s � l'utilisateur au statut 'responsable'
		$ok = false;
		while($test = mysql_fetch_object($get_eleves)) {
			if ($test->login == $selected_eleve->login) $ok = true;
		}
		if (!$ok) $selected_eleve = false;
	}
}
$selected_eleve_login = $selected_eleve ? $selected_eleve->login : "";

// Nom complet de la classe
$appel_classe = mysql_query("SELECT classe FROM classes WHERE id='$id_classe'");
$classe_nom = @mysql_result($appel_classe, 0, "classe");

// Nom complet de la mati�re
$matiere_nom = $current_group["matiere"]["nom_complet"];
(!isset($_GET['ordre']) or (($_GET['ordre'] != '') and ($_GET['ordre']!= 'DESC')))?$current_ordre='':$current_ordre=$_GET['ordre'];
($current_ordre == '')?$ordre='DESC':$ordre='';
(!isset($_GET['imprime']) or (($_GET['imprime'] != 'y') and ($_GET['imprime']!= 'n')))?$current_imprime='n':$current_imprime=$_GET['imprime'];
if ($current_imprime == 'n') {
	$imprime='y';
	$text_imprime="Version imprimmable";
	$largeur = "30%";
} else {
	$imprime='n';
	$text_imprime="Retour";
	$largeur = "5%";
}

// On ajoute un retour vers la page de signature des cdt si c'est un administrateur
$_retour = ($_SESSION["retour"] == 'admin_ct') ? $_SESSION["retour"] : 'visa_ct';
$code_retour_admin = '<p><a href="../cahier_texte_admin/' . $_retour .'.php">RETOUR vers la signature des cahiers de textes</a></p>';
$retour_admin = ($_SESSION["statut"] == 'administrateur') ? $code_retour_admin : '';

if(isset($_GET['retour_cdt'])) {
	$_SESSION['retour_cdt']=$_GET['retour_cdt'];
}

if((isset($_SESSION['retour_cdt']))&&($_SESSION['retour_cdt']='visa_ct')) {
	$_retour='visa_ct';
	$retour_admin='<p><a href="../cahier_texte_admin/' . $_retour .'.php#tableau_des_enseignants">RETOUR vers la signature des cahiers de textes</a></p>';
}

//**************** EN-TETE *****************
if ($current_imprime=='n') $titre_page = "Cahier de textes - Vue d'ensemble";
require_once("../lib/header.inc");
if ($current_imprime=='y') echo "<div id='container'>\n";
//**************** FIN EN-TETE *************
// Cr�ation d'un espace entre le bandeau et le reste ainsi que le retour pour l'admin
echo "<p></p>\n" . $retour_admin;

//On v�rifie si le module est activ�
if (getSettingValue("active_cahiers_texte")!='y') {
	die("<p class='grand centre_texte'>Le cahier de textes n'est pas accessible pour le moment.</p>");
}
// echo "<table border='0' width=\"98%\" cellspacing=0 align=\"center\">\n";
echo "<div class='centre_table'>\n";
	// echo "<tr>";
	// echo "<td valign='top' width=".$largeur.">";
	echo "<div class='ct_col_gauche'>";
		if ($current_imprime=='n') {
			if ($_SESSION['statut'] == 'responsable') {
				echo make_eleve_select_html('see_all.php', $_SESSION['login'], $selected_eleve, $year, $month, $day);
			}
			if ($selected_eleve_login != "") echo make_matiere_select_html('see_all.php', $selected_eleve_login, $id_groupe, $year, $month, $day);

			if ($_SESSION['statut'] != "responsable" and $_SESSION['statut'] != "eleve") {
				echo make_classes_select_html('see_all.php', $id_classe, $year, $month, $day);
				if ($id_classe != -1) echo make_matiere_select_html('see_all.php', $id_classe, $id_groupe, $year, $month, $day);
			}
		}
	echo "</div>\n";
	// echo "</td>\n";
	// echo "<td style=\"text-align:center;\">\n";
	echo "<div class='ct_col_centre'>\n";
		echo "<p>\n";
			echo "<span class='grand'>\n";
				echo "Cahier de textes";
				if ($current_group) {
					echo " - $matiere_nom";
					echo " - classe de ".$current_group['classlist_string'];
				}
				if ($id_classe != -1) {
					echo "<br />$classe_nom\n";
				}
			echo "</span>\n";

				// Test si le cahier de texte est partag�
			if ($current_group) {
				echo "<br /><strong>(";
				$i=0;
			  foreach ($current_group["profs"]["users"] as $prof) {
					if ($i != 0) echo ", ";
					echo substr($prof["prenom"],0,1) . ". " . $prof["nom"];
					$i++;
				}
				echo ")</strong>";
			}
		echo "</p>\n";
	echo "</div>\n";
	// echo "</td>\n";
	// echo "</tr>\n";
echo "</div>\n";
// echo "</table>\n";

if ($current_group) {
	echo "<div class='no_print'>\n";
		if ($current_imprime=='n') {
			//if ($_SESSION["statut"] == "professeur" OR $_SESSION["statut"] == "scolarite" OR $_SESSION["statut"] == "cpe" OR $_SESSION["statut"] == "autre") {
			if ($_SESSION["statut"] == "administrateur" OR $_SESSION["statut"] == "professeur" OR $_SESSION["statut"] == "scolarite" OR $_SESSION["statut"] == "cpe" OR $_SESSION["statut"] == "autre") {
				echo "<a href='see_all.php'>\n<img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour\n</a> - ";
				if ($_SESSION["statut"] == "professeur") {
					echo "<a href='./index.php'>\n<img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour vers mes cahiers de textes\n</a> - ";
				}
			} else {
				echo "<a href='consultation.php?id_classe=$id_classe&amp;login_eleve=$selected_eleve_login&amp;id_groupe=$id_groupe'>\n<img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour\n</a> - ";
			}
			// if ($current_imprime=='n') 
			echo "<a href='see_all.php?id_classe=$id_classe&amp;login_eleve=$selected_eleve_login&amp;id_groupe=$id_groupe&amp;ordre=$ordre&amp;imprime=$current_imprime'>\nTrier dans l'ordre inverse\n</a> - \n";
		}
		echo "<a href='see_all.php?id_classe=$id_classe&amp;login_eleve=$selected_eleve_login&amp;id_groupe=$id_groupe&amp;ordre=$current_ordre&amp;imprime=$imprime'>\n$text_imprime\n</a>\n";
		// } retour ne s'affichait pas sur la page imprimable
	echo "</div>\n";
}

echo "<hr />";
$test_cahier_texte = mysql_query("SELECT contenu  FROM ct_entry WHERE (id_groupe='$id_groupe')");
$nb_test = mysql_num_rows($test_cahier_texte);
if ($nb_test == 0) {
	echo "\n<h2 class='gepi centre_texte'>\n";
	if ($_SESSION['statut'] == "responsable") {
		echo "Choisissez un �l�ve et une mati�re.";
	} elseif ($_SESSION['statut'] == "eleve") {
		echo "Choisissez une mati�re.";
	} else {
		echo "Choisissez une classe et une mati�re.";
	}
	echo "\n</h2>\n";
	require("../lib/footer.inc.php");
	die();
}
// Affichage des informations g�n�rales
$appel_info_cahier_texte = mysql_query("SELECT contenu, id_ct  FROM ct_entry WHERE (id_groupe='$id_groupe' and date_ct='')");
$nb_cahier_texte = mysql_num_rows($appel_info_cahier_texte);
$content = @mysql_result($appel_info_cahier_texte, 0, 'contenu');
$id_ct = @mysql_result($appel_info_cahier_texte, 0, 'id_ct');
include "../lib/transform.php";
$html .= affiche_docs_joints($id_ct,"c");
if ($html != '') {
	// echo "<div  style=\"border-bottom-style: solid; border-width:2px; border-color: ".$couleur_bord_tableau_notice."; \"><strong>INFORMATIONS GENERALES</strong></div>";
	//echo "\n<div class='see_all_notice>\n";
	echo "<h2 class='grande_ligne couleur_bord_tableau_notice'>\n<strong>INFORMATIONS GENERALES</strong>\n</h2>\n";
	// echo "<table style=\"border-style:solid; border-width:0px; border-color: ".$couleur_bord_tableau_notice."; padding: 2px; margin: 2px;\" width = '100%' cellpadding='5'><tr><td>".$html."</td></tr></table>";
echo "<div class='see_all_general couleur_bord_tableau_notice'>".$html."</div>";
}

	// echo "<div  style=\"border-bottom-style: solid; border-width:2px; border-color: ".$couleur_bord_tableau_notice."; \"><strong>CAHIER DE TEXTES: comptes rendus de s�ance</strong></div><br />";
echo "<h2 class='grande_ligne couleur_bord_tableau_notice'>\n<strong>CAHIER DE TEXTES: comptes rendus de s�ance</strong>\n</h2>\n";

if(($_SESSION['statut']=='eleve')||($_SESSION['statut']=='responsable')) {
	$req_notices =
		"select 'c' type, contenu, date_ct, id_ct
		from ct_entry
		where (contenu != ''
		and id_groupe='".$id_groupe."'
		and date_ct != ''
		and date_ct >= '".getSettingValue("begin_bookings")."'
		and date_ct <= '".getSettingValue("end_bookings")."'
		and date_ct <= '".time()."')
		ORDER BY date_ct ".$current_ordre.", heure_entry ".$current_ordre;
}
else {
	$req_notices =
		"select 'c' type, contenu, date_ct, id_ct
		from ct_entry
		where (contenu != ''
		and id_groupe='".$id_groupe."'
		and date_ct != ''
		and date_ct >= '".getSettingValue("begin_bookings")."'
		and date_ct <= '".getSettingValue("end_bookings")."')
		ORDER BY date_ct ".$current_ordre.", heure_entry ".$current_ordre;
}
//echo "$req_notices<br />";
$res_notices = mysql_query($req_notices);
$notice = mysql_fetch_object($res_notices);

$ts_limite_visibilite_devoirs_pour_eleves=time()+getSettingValue('delai_devoirs')*24*3600;

if(($_SESSION['statut']=='eleve')||($_SESSION['statut']=='responsable')) {
	$ts_limite_dev=$ts_limite_visibilite_devoirs_pour_eleves;
}
else {
	$ts_limite_dev=getSettingValue("end_bookings");
}
$req_devoirs =
	"select 't' type, contenu, date_ct, id_ct, date_visibilite_eleve
	from ct_devoirs_entry
	where (contenu != ''
	and id_groupe = '".$id_groupe."'
	and date_ct != ''
	and date_ct >= '".getSettingValue("begin_bookings")."'
	and date_ct <= '".$ts_limite_dev."'
	) order by date_ct ".$current_ordre;
$res_devoirs = mysql_query($req_devoirs);
$devoir = mysql_fetch_object($res_devoirs);

$timestamp_courant=time();
// Boucle d'affichage des notices dans la colonne de gauche
$date_ct_old = -1;
while (true) {
	if ($current_ordre == "DESC") {
		// On met les notices du jour avant les devoirs � rendre aujourd'hui
		if ($notice && (!$devoir || $notice->date_ct >= $devoir->date_ct)) {
			// Il y a encore une notice et elle est plus r�cente que le prochain devoir, o� il n'y a plus de devoirs
			$not_dev = $notice;
			$notice = mysql_fetch_object($res_notices);

			$type_notice="notice";
		} elseif($devoir) {
			// Plus de notices et toujours un devoir, ou devoir plus r�cent
			$not_dev = $devoir;
			$devoir = mysql_fetch_object($res_devoirs);

			$type_notice="devoir";
		} else {
			// Plus rien � afficher, on sort de la boucle
			break;
		}
	} else {
		// On met les notices du jour avant les devoirs � rendre aujourd'hui
		if ($notice && (!$devoir || $notice->date_ct <= $devoir->date_ct)) {
			// Il y a encore une notice et elle est plus r�cente que le prochain devoir, o� il n'y a plus de devoirs
			$not_dev = $notice;
			$notice = mysql_fetch_object($res_notices);

			$type_notice="notice";
		} elseif($devoir) {
			// Plus de notices et toujours un devoir, ou devoir plus r�cent
			$not_dev = $devoir;
			$devoir = mysql_fetch_object($res_devoirs);

			$type_notice="devoir";
		} else {
			// Plus rien � afficher, on sort de la boucle
			break;
		}
	}

	if(($type_notice!="devoir")||
		($not_dev->date_visibilite_eleve=="")||
		(($not_dev->date_visibilite_eleve!="")&&(mysql_date_to_unix_timestamp($not_dev->date_visibilite_eleve)<=$timestamp_courant))||
		(verif_groupe_appartient_prof($id_groupe)==1)) {
		// Passage en HTML
		//$content = &$not_dev->contenu;
		// INSERT INTO setting SET name='depolluer_MSOffice', value='y';
		if(getSettingValue('depolluer_MSOffice')=='y') {
			//$content = &my_ereg_replace('.*<\!\[endif\]-->',"",$not_dev->contenu);
			$content = &preg_replace('#.*<\!\[endif\]-->#',"",$not_dev->contenu);
		}
		else {
			$content = &$not_dev->contenu;
		}
		include ("../lib/transform.php");

		$html .= affiche_docs_joints($not_dev->id_ct,$not_dev->type);
		echo "<h3 class='see_all_h3'>\n<strong>\n";
			if ($not_dev->type == "t") {
				echo("A faire pour le : ");
			}
			echo(strftime("%a %d %b %y", $not_dev->date_ct));
		echo "</strong>\n</h3>\n";
		// Num�rotation des notices si plusieurs notices sur la m�me journ�e
		if ($not_dev->type == "c") {
			if ($date_ct_old == $not_dev->date_ct) {
				$num_notice++;
				echo " <strong><em>(notice N� ".$num_notice.")</em></strong>";
			} else {
				// on afffiche "(notice N� 1)" uniquement s'il y a plusieurs notices dans la m�me journ�e
				$nb_notices = sql_query1("SELECT count(id_ct) FROM ct_entry WHERE (id_groupe='" . $current_group["id"] ."' and date_ct='".$not_dev->date_ct."')");
				if ($nb_notices > 1)
					echo " <strong><em>(notice N� 1)</em></strong>";
				// On r�initialise le compteur
				$num_notice = 1;
			}
		}
		// echo("<table style=\"border-style:solid; border-width:1px; border-color: ".$couleur_bord_tableau_notice.";\" width=\"100%\" cellpadding=\"1\" bgcolor=\"".$color_fond_notices[$not_dev->type]."\">\n<tr>\n<td>\n$html</td>\n</tr>\n</table>\n<br/>\n");
		echo "<div class='see_all_notice couleur_bord_tableau_notice color_fond_notices_".$not_dev->type."'>";
		/* if ($not_dev->type == "t") {
			echo "see_all_a_faire'>\n";
		} else {
			echo "see_all_compte_rendu'>\n";
		}*/

		if(($type_notice=='devoir')&&($not_dev->date_visibilite_eleve!='0000-00-00 00:00:00')) {
			echo "<div style='float:right; width: 6em; border: 1px solid black; margin: 2px; font-size: xx-small; text-align: center;'>Donn� le ".formate_date($not_dev->date_visibilite_eleve)."</div>\n";
		}

		echo "$html\n</div>\n";
		if ($not_dev->type == "c") $date_ct_old = $not_dev->date_ct;
	}
}

//if ($current_imprime=='n') echo "</td></tr></table>";
//echo "</td></tr></table>";
require("../lib/footer.inc.php");
?>
