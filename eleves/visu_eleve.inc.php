<?php

/*
 * @version $Id$
 *
 * Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Julien Jocal, Stephane Boireau
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

if(($_SERVER['SCRIPT_NAME']!="$gepiPath/eleves/visu_eleve.php")&&
($_SERVER['SCRIPT_NAME']!="$gepiPath/mod_abs2/fiche_eleve.php")) {
	echo "<p style='color:red'>Inclusion non autoris�e depuis ".$_SERVER['SCRIPT_NAME']."</p>\n";
	require_once("../lib/footer.inc.php");
	die();
}

//debug_var();

$Recherche_sans_js=isset($_POST['Recherche_sans_js']) ? $_POST['Recherche_sans_js'] : (isset($_GET['Recherche_sans_js']) ? $_GET['Recherche_sans_js'] : NULL);

//echo "<div class='norme'><p class='bold'><a href='../accueil.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>\n";

//if(!isset($ele_login)) {

//if((!isset($ele_login))&&(!isset($_POST['Recherche_sans_js']))) {
if((!isset($ele_login))&&(!isset($Recherche_sans_js))) {
	echo "<div class='norme'><p class='bold'><a href='../accueil.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>\n";
	echo "</p>\n";
	echo "</div>\n";

	//=============================================
	// Formulaire pour navigateur SANS Javascript:
	echo "<noscript>
	<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='formulaire1'>
		<p>
		Afficher les ".$gepiSettings['denomination_eleves']." dont le <b>nom</b> contient&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input type='text' name='rech_nom' value='' />
		<input type='hidden' name='page' value='$page' />
		<input type='submit' name='Recherche_sans_js' value='Rechercher' />
		</p>
	</form>

	<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post' name='formulaire1'>
		<p>
		Afficher les ".$gepiSettings['denomination_eleves']." dont le <b>pr�nom</b> contient&nbsp;: <input type='text' name='rech_prenom' value='' />
		<input type='hidden' name='page' value='$page' />
		<input type='submit' name='Recherche_sans_js' value='Rechercher' />
		</p>
	</form>

</noscript>\n";
	//=============================================

	// Portion d'AJAX:
	echo "<script type='text/javascript'>

	function cherche_eleves(type) {
		rech_nom_ou_prenom=document.getElementById('rech_'+type).value;

		//var url = 'liste_eleves.php';
		var url = '../eleves/liste_eleves.php';
		var myAjax = new Ajax.Request(
			url,
			{
				method: 'post',
				postBody: 'rech_'+type+'='+rech_nom_ou_prenom+'&page=$page',
				onComplete: affiche_eleves
			});

	}

	function affiche_eleves(xhr) {
		if (xhr.status == 200) {
			document.getElementById('liste_eleves').innerHTML = xhr.responseText;
		}
		else {
			document.getElementById('liste_eleves').innerHTML = xhr.status;
		}
	}

	function affichage_et_action(type) {
		if(document.getElementById('rech_'+type).value=='') {
			document.getElementById('Recherche_'+type).style.display='none';
		}
		else {
			document.getElementById('Recherche_'+type).style.display='';
			cherche_eleves(type);
		}
	}

	/*
	function cherche_eleves(type) {
		rech_nom_ou_prenom=document.getElementById('rech_'+type).value;

		new Ajax.Updater($('liste_eleves'),'../eleves/liste_eleves.php?rech_'+type+'='+rech_nom_ou_prenom+'&page=$page',{method: 'get'});
	}
	*/
</script>\n";


	// DIV avec formulaire pour navigateur AVEC Javascript:
	echo "<div id='recherche_avec_js' style='display:none;'>\n";

	echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' onsubmit=\"cherche_eleves('nom');return false;\" method='post' name='formulaire'>";
	echo "<p>\n";
	echo "Afficher les ".$gepiSettings['denomination_eleves']." dont le <b>nom</b> contient&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input type='text' name='rech_nom' id='rech_nom' value='' onchange=\"affichage_et_action('nom')\" />\n";
	echo "<input type='hidden' name='page' value='$page' />\n";
	echo "<input type='button' name='Recherche' id='Recherche_nom' value='Rechercher' onclick=\"cherche_eleves('nom')\" />\n";
	echo "</p>\n";
	echo "</form>\n";

	echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' onsubmit=\"cherche_eleves('prenom');return false;\" method='post' name='formulaire'>";
	echo "<p>\n";
	echo "Afficher les ".$gepiSettings['denomination_eleves']." dont le <b>pr�nom</b> contient&nbsp;: <input type='text' name='rech_prenom' id='rech_prenom' value='' onchange=\"affichage_et_action('prenom')\" />\n";
	echo "<input type='hidden' name='page' value='$page' />\n";
	echo "<input type='button' name='Recherche' id='Recherche_prenom' value='Rechercher' onclick=\"cherche_eleves('prenom')\" />\n";
	echo "</p>\n";
	echo "</form>\n";

	echo "<div id='liste_eleves'></div>\n";

	echo "</div>\n";
	echo "<script type='text/javascript'>
document.getElementById('recherche_avec_js').style.display='';
affichage_et_action('nom');
affichage_et_action('prenom');

if(document.getElementById('rech_nom')) {document.getElementById('rech_nom').focus();}
</script>\n";


	if(isset($id_classe)) {
		$sql="SELECT DISTINCT e.login,e.nom,e.prenom FROM eleves e, j_eleves_classes jec WHERE jec.login=e.login AND jec.id_classe='$id_classe' ORDER BY e.nom,e.prenom;";
		//echo "$sql<br />";
		$res_ele=mysql_query($sql);
		if(mysql_num_rows($res_ele)>0) {
			echo "<p>".ucfirst($gepiSettings['denomination_eleves'])." de la classe de ".get_class_from_id($id_classe).":</p>\n";

			/*
			echo "<table class='boireaus' border='1' summary='Tableau des �l�ves'>\n";
			echo "<tr>\n";
			echo "<th>Nom</th>\n";
			echo "<th>Pr�nom</th>\n";
			echo "</tr>\n";
			while($lig_ele=mysql_query($res_ele)) {
				echo "";
			}
			*/

			$tab_txt=array();
			$tab_lien=array();

			while($lig_ele=mysql_fetch_object($res_ele)) {
				$tab_txt[]=ucfirst(strtolower($lig_ele->prenom))." ".strtoupper($lig_ele->nom);
				//$tab_lien[]=$_SERVER['PHP_SELF']."?ele_login=".$lig_ele->login;
				$tab_lien[]=$_SERVER['PHP_SELF']."?ele_login=".$lig_ele->login."&amp;id_classe=".$id_classe;
			}

			echo "<blockquote>\n";
			tab_liste($tab_txt,$tab_lien,3);
			echo "</blockquote>\n";

		}
	}


	if($_SESSION['statut']=='scolarite') {
		$sql="SELECT DISTINCT c.id,c.classe FROM classes c, j_scol_classes jsc WHERE jsc.id_classe=c.id AND jsc.login='".$_SESSION['login']."' ORDER BY classe";
	}
	elseif($_SESSION['statut']=='professeur') {
		$sql="SELECT DISTINCT c.id,c.classe FROM classes c,j_groupes_classes jgc,j_groupes_professeurs jgp WHERE jgp.login = '".$_SESSION['login']."' AND jgc.id_groupe=jgp.id_groupe AND jgc.id_classe=c.id ORDER BY c.classe";
	}
	elseif($_SESSION['statut']=='cpe') {
		$sql="SELECT DISTINCT c.id,c.classe FROM classes c,j_eleves_cpe jec,j_eleves_classes jecl WHERE jec.cpe_login = '".$_SESSION['login']."' AND jec.e_login=jecl.login AND jecl.id_classe=c.id ORDER BY c.classe";
	}
	elseif(($_SESSION['statut']=='administrateur')||($_SESSION['statut']=='secours')) {
		$sql="SELECT DISTINCT c.id,c.classe FROM classes c ORDER BY c.classe";
	}
	elseif($_SESSION['statut'] == 'autre'){
		$sql="SELECT DISTINCT c.id,c.classe FROM classes c ORDER BY c.classe";
	}
	//echo "$sql<br />";
	$res_clas=mysql_query($sql);
	if(mysql_num_rows($res_clas)>0) {
		echo "<p>Ou choisir un ".$gepiSettings['denomination_eleve']." dans une classe:</p>\n";

		$tab_txt=array();
		$tab_lien=array();

		while($lig_clas=mysql_fetch_object($res_clas)) {
			$tab_txt[]=$lig_clas->classe;
			$tab_lien[]=$_SERVER['PHP_SELF']."?id_classe=".$lig_clas->id;
		}

		echo "<blockquote>\n";
		tab_liste($tab_txt,$tab_lien,4);
		echo "</blockquote>\n";
	}

	//=============================================

}
//elseif(isset($_POST['Recherche_sans_js'])) {
elseif(isset($Recherche_sans_js)) {
	// On ne passe ici que si JavaScript est d�sactiv�
	echo "<div class='norme'><p class='bold'><a href='../accueil.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>\n";
	echo " | <a href='".$_SERVER['PHP_SELF']."'>Choisir un autre ".$gepiSettings['denomination_eleve']."</a>\n";
	echo "</p>\n";
	echo "</div>\n";

	//include("recherche_eleve.php");
	//include("$gepiPath/eleves/recherche_eleve.php");
	include("../eleves/recherche_eleve.php");
}
else {
	echo "<form action='".$_SERVER['PHP_SELF']."' name='form1' method='post'>\n";

	echo "<div class='norme'><p class='bold'><a href='../accueil.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>\n";

	//echo " | <a href='".$_SERVER['PHP_SELF']."'>Choisir un autre �l�ve</a>\n";
	echo " | <a href='".$_SERVER['PHP_SELF']."'>Choisir un autre ".$gepiSettings['denomination_eleve']."/classe</a>\n";

	if(!isset($id_classe)) {
		$sql="SELECT id_classe FROM j_eleves_classes WHERE login='$ele_login' ORDER BY periode DESC;";
		$res_class_tmp=mysql_query($sql);
		if(mysql_num_rows($res_class_tmp)>0){
			$lig_class_tmp=mysql_fetch_object($res_class_tmp);
			$id_classe=$lig_class_tmp->id_classe;
		}
	}

	if(isset($id_classe)) {
		/*
		if($_SESSION['statut']=='administrateur') {
			$sql="SELECT e.nom,e.prenom,e.login FROM eleves e, j_eleves_classes jec WHERE jec.login=e.login ORDER BY e.nom, e.prenom;";
		}
		elseif($_SESSION['statut']=='scolarite') {
			$sql="SELECT 1=1 FROM j_scol_classes jsc, j_eleves_classes jec WHERE jec.id_classe=jsc.id_classe AND jsc.login='".$_SESSION['login']."' AND jec.login='".$ele_login."';";
			$test=mysql_query($sql);
		}
		elseif($_SESSION['statut']=='cpe') {
			$sql="SELECT 1=1 FROM j_eleves_cpe WHERE cpe_login='".$_SESSION['login']."' AND e_login='".$ele_login."';";
			$test=mysql_query($sql);
		}
		elseif($_SESSION['statut']=='professeur') {
		}
		*/

		$sql="SELECT DISTINCT e.nom,e.prenom,e.login FROM eleves e, j_eleves_classes jec WHERE jec.login=e.login AND jec.id_classe='$id_classe' ORDER BY e.nom, e.prenom;";

		$chaine_options_eleves="";

		$res_ele_tmp=mysql_query($sql);
		if(mysql_num_rows($res_ele_tmp)>0){
			$ele_login_prec="";
			$ele_login_suiv="";
			$temoin_tmp=0;
			while($lig_ele_tmp=mysql_fetch_object($res_ele_tmp)) {
				if($lig_ele_tmp->login==$ele_login) {
					$chaine_options_eleves.="<option value='$lig_ele_tmp->login' selected='true'>$lig_ele_tmp->nom $lig_ele_tmp->prenom</option>\n";
					$temoin_tmp=1;
					if($lig_ele_tmp=mysql_fetch_object($res_ele_tmp)) {
						$chaine_options_eleves.="<option value='$lig_ele_tmp->login'>$lig_ele_tmp->nom $lig_ele_tmp->prenom</option>\n";
						$ele_login_suiv=$lig_ele_tmp->login;
					}
					else {
						$ele_login_suiv="";
					}
				}
				else {
					$chaine_options_eleves.="<option value='$lig_ele_tmp->login'>$lig_ele_tmp->nom $lig_ele_tmp->prenom</option>\n";
				}
				if($temoin_tmp==0) {
					$ele_login_prec=$lig_ele_tmp->login;
				}
			}
		}
		// =================================

		// Initialisation
		if(!isset($onglet)) {
			$onglet="eleve";
		}

		if($ele_login_prec!=""){
			echo " | <a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login_prec&amp;id_classe=$id_classe";
			echo "'";
			echo " onclick=\"passer_a_eleve('$ele_login_prec','$id_classe');return false;\"";
			echo ">".ucfirst($gepiSettings['denomination_eleve'])." pr�c�dent</a>";
		}
		if($chaine_options_eleves!="") {
			echo " | <select name='ele_login' onchange=\"document.forms['form1'].submit();\">\n";
			echo $chaine_options_eleves;
			echo "</select>\n";
		}
		if($ele_login_suiv!=""){
			echo " | <a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login_suiv&amp;id_classe=$id_classe";
			echo "'";
			echo " onclick=\"passer_a_eleve('$ele_login_suiv','$id_classe');return false;\"";
			echo ">".ucfirst($gepiSettings['denomination_eleve'])." suivant</a>";
		}

		//echo "</p>\n";

		echo "<input type='hidden' name='id_classe' value='$id_classe' />\n";

		unset($id_classe);
	}

	echo "</p>\n";
	echo "</div>\n";

	echo "<input type='hidden' name='onglet' id='onglet_courant' value='";
	if(isset($onglet)) {echo $onglet;}
	echo "' />\n";
	echo "</form>\n";

	// Affichage des onglets pour l'�l�ve choisi

	echo "<div id='patience'>
<noscript>
Patientez pendant l'extraction des donn�es... merci.
</noscript>
</div>\n";
	// Avec �a, le message ne disparait pas quand on a d�sactiv� JavaScript...

	echo "<script type='text/javascript'>
	document.getElementById('patience').innerHTML=\"Patientez pendant l'extraction des donn�es... merci.\";

	function passer_a_eleve(ele_login,id_classe) {
		if(document.getElementById('onglet_courant')) {
			onglet=document.getElementById('onglet_courant').value;
		}
		else {
			onglet='eleve';
		}
		//alert('".$_SERVER['PHP_SELF']."?id_classe='+id_classe+'&ele_login='+ele_login+'&onglet='+onglet);
		document.location.replace('".$_SERVER['PHP_SELF']."?id_classe='+id_classe+'&ele_login='+ele_login+'&onglet='+onglet);
	}
</script>\n";

	flush();

	// Couleurs pour les onglets:
	$tab_couleur['eleve']="moccasin";
	$tab_couleur['responsables']="mintcream";
	$tab_couleur['enseignements']="whitesmoke";
	$tab_couleur['bulletins']="lightyellow";
	$tab_couleur['bulletin']="lemonchiffon";
	$tab_couleur['releves']="papayawhip";
	$tab_couleur['releve']="seashell";
	$tab_couleur['cdt']="linen";
	$tab_couleur['anna']="blanchedalmond";
	$tab_couleur['absences']="azure";
	$tab_couleur['discipline']="salmon";
    $tab_couleur['fp']="linen";

	// On v�rifie que l'�l�ve existe
	$sql="SELECT 1=1 FROM eleves WHERE login='$ele_login';";
	$res_ele=mysql_query($sql);

	if(mysql_num_rows($res_ele)==0){
		// On ne devrait pas arriver l�.
		echo "<p>L'".$gepiSettings['denomination_eleve']." dont le login serait $ele_login n'est pas dans la table 'eleves'.</p>\n";
	}
	else{
		//================================
		unset($day);
		$day = isset($_POST["day"]) ? $_POST["day"] : (isset($_GET["day"]) ? $_GET["day"] : date("d"));
		unset($month);
		$month = isset($_POST["month"]) ? $_POST["month"] : (isset($_GET["month"]) ? $_GET["month"] : date("m"));
		unset($year);
		$year = isset($_POST["year"]) ? $_POST["year"] : (isset($_GET["year"]) ? $_GET["year"] : date("Y"));
		// V�rification
		settype($month,"integer");
		settype($day,"integer");
		settype($year,"integer");
		$minyear = strftime("%Y", getSettingValue("begin_bookings"));
		$maxyear = strftime("%Y", getSettingValue("end_bookings"));
		if ($day < 1) $day = 1;
		if ($day > 31) $day = 31;
		if ($month < 1) $month = 1;
		if ($month > 12) $month = 12;
		if ($year < $minyear) $year = $minyear;
		if ($year > $maxyear) $year = $maxyear;
		# Make the date valid if day is more then number of days in month
		while (!checkdate($month, $day, $year)) $day--;
		$today=mktime(0,0,0,$month,$day,$year);
		//================================
		// Dates pour l'extraction des cahiers de textes: 1j avant et 7j apr�s
		$date_ct1=$today-1*24*3600;
		$date_ct2=$today+7*24*3600;

		$j_sem_prec=date("d",$today-7*24*3600);
		$m_sem_prec=date("m",$today-7*24*3600);
		$y_sem_prec=date("Y",$today-7*24*3600);

		$j_sem_suiv=date("d",$today+7*24*3600);
		$m_sem_suiv=date("m",$today+7*24*3600);
		$y_sem_suiv=date("Y",$today+7*24*3600);
		//================================

		// A FAIRE:
		// Contr�ler si la personne connect�e a le droit de consulter les infos sur cet �l�ve
		$acces_eleve="n";
		$acces_responsables="n";
		$acces_enseignements="n";
		$acces_releves="n";
		$acces_bulletins="n";
		$acces_anna="n";
		$acces_absences="n";
		$acces_discipline="n";
        $acces_fp="n";

		$active_annees_anterieures=getSettingValue('active_annees_anterieures');

		if($_SESSION['statut']=='administrateur') {
			$acces_eleve="y";
			$acces_responsables="y";
			$acces_enseignements="y";
			$acces_releves="n";
			$acces_bulletins="y";
			$acces_absences="y";
			if($active_annees_anterieures=='y') {
				$acces_anna="y";
			}
			$acces_discipline="y";
            $acces_fp="y";
		}
		elseif($_SESSION['statut']=='scolarite') {
			if (getSettingValue("GepiAccesTouteFicheEleveScolarite")!='yes') {
			    $sql="SELECT 1=1 FROM j_scol_classes jsc, j_eleves_classes jec WHERE jec.id_classe=jsc.id_classe AND jsc.login='".$_SESSION['login']."' AND jec.login='".$ele_login."';";

			    $test=mysql_query($sql);

			    if(mysql_num_rows($test)==0) {
				    echo "<p>Vous n'�tes pas responsable d'un ".$gepiSettings['denomination_eleve']." dont le login serait $ele_login.</p>\n";
				    require_once("../lib/footer.inc.php");
				    die();
			    }
			}

			$acces_eleve="y";
			$acces_responsables="y";
			$acces_enseignements="y";
			$acces_absences="y";

			$acces_discipline="y";
            $acces_fp="y";

			$GepiAccesReleveScol=getSettingValue('GepiAccesReleveScol');
			if($GepiAccesReleveScol=="yes") {
				$acces_releves="y";
			}

			if($active_annees_anterieures=='y') {
				$AAScolTout=getSettingValue('AAScolTout');
				if($AAScolTout=="yes") {
					$acces_anna="y";
				}
				else {
					$AAScolResp=getSettingValue('AAScolResp');
					//if(()&&($AAScolResp=="yes")) {
					// On filtre plus haut: un compte scolarit� n'a acc�s qu'aux �l�ves dont il est "responsable"
					if($AAScolResp=="yes") {
						$acces_anna="y";
					}
				}
			}

			$acces_bulletins="y";
		}
		elseif($_SESSION['statut']=='cpe') {
			if (getSettingValue("GepiAccesTouteFicheEleveCpe")!='yes') {
			    $sql="SELECT 1=1 FROM j_eleves_cpe WHERE cpe_login='".$_SESSION['login']."' AND e_login='".$ele_login."';";
			    $test=mysql_query($sql);

			    if(mysql_num_rows($test)==0) {
				    echo "<p>Vous n'�tes pas responsable d'un ".$gepiSettings['denomination_eleve']." dont le login serait $ele_login.</p>\n";
				    require_once("../lib/footer.inc.php");
				    die();
			    }
			}

			$acces_eleve="y";
			$acces_responsables="y";
			$acces_enseignements="y";
			$acces_absences="y";

			$acces_discipline="y";
            $acces_fp="y";

			$GepiAccesReleveCpe=getSettingValue('GepiAccesReleveCpe');
			if($GepiAccesReleveCpe=="yes") {
				$acces_releves="y";
			}

			if($active_annees_anterieures=='y') {
				$AACpeTout=getSettingValue('AACpeTout');
				if($AACpeTout=="yes") {
					$acces_anna="y";
				}
				else {
					$AACpeResp=getSettingValue('AACpeResp');
					//if(()&&($AACpeResp=="yes")) {
					// On filtre plus haut: un compte cpe n'a acc�s qu'aux �l�ves dont il est "responsable"
					if($AACpeResp=="yes") {
						$acces_anna="y";
					}
				}
			}

			$acces_bulletins="y";
		}
		elseif($_SESSION['statut']=='professeur') {

			$acces_eleve="y";
			$acces_responsables="n";
			$acces_enseignements="y";
			$acces_releves="n";
			$acces_bulletins="n";
			$acces_absences="n";

			$acces_discipline="n";
            $acces_fp="y";

			$sql="SELECT 1=1 FROM j_eleves_professeurs WHERE login='".$ele_login."' AND professeur='".$_SESSION['login']."';";
			$test=mysql_query($sql);

			if(mysql_num_rows($test)>0) {
				$is_pp="y";
				$acces_absences="y";
			}
			else {
				$is_pp="n";
			}

			// Contr�le de l'acc�s � l'onglet Responsables
			$GepiAccesGestElevesProf=getSettingValue('GepiAccesGestElevesProf');
			if($GepiAccesGestElevesProf=="yes") {
				$acces_responsables="y";
			}
			else {
				$GepiAccesGestElevesProfP=getSettingValue('GepiAccesGestElevesProfP');
				if(($GepiAccesGestElevesProfP=="yes")&&($is_pp=="y")) {
					$acces_responsables="y";
				}
			}

			// Contr�le de l'acc�s du prof au relev� de notes:
			$GepiAccesReleveProfP=getSettingValue('GepiAccesReleveProfP');
			if(($GepiAccesReleveProfP=="yes")&&($is_pp=="y")) {
				$acces_releves="y";
			}

			$eleve_classe_prof="n";
			$eleve_groupe_prof="n";

			//=====================================
			$sql="SELECT 1=1 FROM j_eleves_classes jec,
								j_groupes_classes jgc,
								j_groupes_professeurs jgp
							WHERE jec.login='".$ele_login."' AND
									jec.id_classe=jgc.id_classe AND
									jgc.id_groupe=jgp.id_groupe AND
									jgp.login='".$_SESSION['login']."';";
			//echo "$sql<br />";
			$test_eleve_classe_prof=mysql_query($sql);

			if(mysql_num_rows($test_eleve_classe_prof)>0) {
				$eleve_classe_prof="y";
			}
			//=====================================
			$sql="SELECT 1=1 FROM j_eleves_groupes jeg,
								j_groupes_professeurs jgp
							WHERE jeg.login='".$ele_login."' AND
									jeg.id_groupe=jgp.id_groupe AND
									jgp.login='".$_SESSION['login']."';";
			//echo "$sql<br />";
			$test_eleve_groupe_prof=mysql_query($sql);

			if(mysql_num_rows($test_eleve_groupe_prof)>0) {
				$eleve_groupe_prof="y";
			}
			//=====================================

			if($acces_releves=='n') {
				$GepiAccesReleveProfToutesClasses=getSettingValue('GepiAccesReleveProfToutesClasses');
				if($GepiAccesReleveProfToutesClasses=='yes') {
					$acces_releves="y";
				}
				else {
					$GepiAccesReleveProfTousEleves=getSettingValue('GepiAccesReleveProfTousEleves');
					//echo "\$GepiAccesReleveProfTousEleves=$GepiAccesReleveProfTousEleves<br />";
					if($GepiAccesReleveProfTousEleves=='yes') {
						/*
						$sql="SELECT 1=1 FROM j_eleves_classes jec,
											j_groupes_classes jgc,
											j_groupes_professeurs jgp
										WHERE jec.login='".$ele_login."' AND
												jec.id_classe=jgc.id_classe AND
												jgc.id_groupe=jgp.id_groupe AND
												jgp.login='".$_SESSION['login']."';";
						//echo "$sql<br />";
						$test_eleve_classe_prof=mysql_query($sql);

						if(mysql_num_rows($test_eleve_classe_prof)>0) {
						*/

						if($eleve_classe_prof=='y') {
							$acces_releves="y";
							//$eleve_classe_prof="y";
						}
					}

					if($acces_releves=='n') {
						//echo "\$GepiAccesReleveProf=$GepiAccesReleveProf<br />";
						$GepiAccesReleveProf=getSettingValue('GepiAccesReleveProf');
						if($GepiAccesReleveProf=='yes') {
							/*
							$sql="SELECT 1=1 FROM j_eleves_groupes jeg,
												j_groupes_professeurs jgp
											WHERE jeg.login='".$ele_login."' AND
													jeg.id_groupe=jgp.id_groupe AND
													jgp.login='".$_SESSION['login']."';";
							//echo "$sql<br />";
							$test_eleve_groupe_prof=mysql_query($sql);

							if(mysql_num_rows($test_eleve_groupe_prof)>0) {
							*/

							if($eleve_groupe_prof=='y') {
								$acces_releves="y";
								//$eleve_groupe_prof="y";
							}
						}
					}
				}
			}

			if(($eleve_classe_prof=="y")||($eleve_groupe_prof=="y")) {
				$acces_absences="y";
			}

			if((($eleve_classe_prof=="y")&&(substr(getSettingValue('visuDiscProfClasses'),0,1)=='y'))||
				(($eleve_groupe_prof=="y")&&(substr(getSettingValue('visuDiscProfGroupes'),0,1)=='y'))) {
				$acces_discipline="y";
			}


			//echo "\$acces_releves=$acces_releves<br />";

			// Contr�le de l'acc�s du prof aux bulletins:
			$GepiAccesBulletinSimplePP=getSettingValue('GepiAccesBulletinSimplePP');
			if(($GepiAccesBulletinSimplePP=="yes")&&($is_pp=="y")) {
				$acces_bulletins="y";
			}

			if($acces_bulletins=='n') {
				$GepiAccesBulletinSimpleProfToutesClasses=getSettingValue('GepiAccesBulletinSimpleProfToutesClasses');
				if($GepiAccesBulletinSimpleProfToutesClasses=='yes') {
					$acces_bulletins="y";
				}
				else {
					$GepiAccesBulletinSimpleProfTousEleves=getSettingValue('GepiAccesBulletinSimpleProfTousEleves');
					if($GepiAccesBulletinSimpleProfTousEleves=='yes') {
						if ($eleve_classe_prof=="y") {
							$acces_bulletins="y";
						}
						else {
							/*
							$sql="SELECT 1=1 FROM j_eleves_classes jec,
												j_groupes_classes jgc,
												j_groupes_professeurs jgp
											WHERE jec.login='".$ele_login."' AND
													jec.id_classe=jgc.id_classe AND
													jgc.id_groupe=jgp.id_groupe AND
													jgp.login='".$_SESSION['login']."';";
							//echo "$sql<br />";
							$test=mysql_query($sql);

							if(mysql_num_rows($test)>0) {
							*/
							if($eleve_classe_prof=='y') {
								$acces_bulletins="y";
							}
						}
					}

					if($acces_bulletins=='n') {
						$GepiAccesBulletinSimpleProf=getSettingValue('GepiAccesBulletinSimpleProf');
						if($GepiAccesBulletinSimpleProf=='yes') {
							if ($eleve_groupe_prof=="y") {
								$acces_bulletins="y";
							}
							else {
								/*
								$sql="SELECT 1=1 FROM j_eleves_groupes jeg,
													j_groupes_professeurs jgp
												WHERE jeg.login='".$ele_login."' AND
														jeg.id_groupe=jgp.id_groupe AND
														jgp.login='".$_SESSION['login']."';";
								//echo "$sql<br />";
								$test=mysql_query($sql);

								if(mysql_num_rows($test)>0) {
								*/
								if($eleve_groupe_prof=='y') {
									$acces_bulletins="y";
								}
							}
						}
					}
				}
			}

			if($active_annees_anterieures=='y') {

				$AAProfTout=getSettingValue('AAProfTout');
				if($AAProfTout=='yes') {
					$acces_anna="y";
				}
				else {
					$AAProfClasses=getSettingValue('AAProfClasses');
					if($AAProfClasses=='yes') {
						if ($eleve_classe_prof=="y") {
							$acces_anna="y";
						}
						else {
							/*
							$sql="SELECT 1=1 FROM j_eleves_classes jec,
												j_groupes_classes jgc,
												j_groupes_professeurs jgp
											WHERE jec.login='".$ele_login."' AND
													jec.id_classe=jgc.id_classe AND
													jgc.id_groupe=jgp.id_groupe AND
													jgp.login='".$_SESSION['login']."';";
							//echo "$sql<br />";
							$test=mysql_query($sql);

							if(mysql_num_rows($test)>0) {
							*/
							if($eleve_classe_prof=='y') {
								$acces_anna="y";
							}
						}
					}
					else {
						$AAProfGroupes=getSettingValue('AAProfGroupes');
						if($AAProfGroupes=='yes') {
							if ($eleve_groupe_prof=="y") {
								$acces_anna="y";
							}
							else {
								/*
								$sql="SELECT 1=1 FROM j_eleves_groupes jeg,
													j_groupes_professeurs jgp
												WHERE jeg.login='".$ele_login."' AND
														jeg.id_groupe=jgp.id_groupe AND
														jgp.login='".$_SESSION['login']."';";
								//echo "$sql<br />";
								$test=mysql_query($sql);

								if(mysql_num_rows($test)>0) {
								*/
								if($eleve_groupe_prof=='y') {
									$acces_anna="y";
								}
							}
						}
					}
				}
			}
		}
		elseif($_SESSION['statut'] == 'autre'){

			// On r�cup�re les droits de ce statuts pour savoir ce qu'on peut afficher
			$sql_d = "SELECT * FROM droits_speciaux WHERE id_statut = '" . $_SESSION['statut_special_id'] . "'";
			$query_d = mysql_query($sql_d);
			$auth_other = array();

			while($rep_d = mysql_fetch_array($query_d)){

				//print_r($rep_d);
				if ($rep_d['nom_fichier'] == '/voir_resp' AND $rep_d['autorisation'] == 'V') {
					$acces_responsables = "y";
				}
				if ($rep_d['nom_fichier'] == '/voir_ens' AND $rep_d['autorisation'] == 'V') {
					$acces_enseignements = "y";
				}
				if ($rep_d['nom_fichier'] == '/voir_notes' AND $rep_d['autorisation'] == 'V') {
					$acces_releves = "y";
				}if ($rep_d['nom_fichier'] == '/voir_bulle' AND $rep_d['autorisation'] == 'V') {
					$acces_bulletins = "y";
				}
				if ($rep_d['nom_fichier'] == '/voir_abs' AND $rep_d['autorisation'] == 'V') {
					$acces_absences = "y";
				}
				if ($rep_d['nom_fichier'] == '/voir_anna' AND $rep_d['autorisation'] == 'V') {
					$acces_anna = "y";
				}

				if ($rep_d['nom_fichier'] == '/mod_discipline/saisie_incident.php' AND $rep_d['autorisation'] == 'V') {
					$acces_discipline="y";
				}

			}

			// A GERER $acces_discipline="y";

		}

		// A REVOIR par la suite
		$active_cahiers_texte=getSettingValue("active_cahiers_texte");
		if($active_cahiers_texte=='y') {
			$acces_cdt="y";
		}
		else {
			$acces_cdt="n";
		}
        $test_outils_comp = sql_query1("select count(outils_complementaires) from aid_config where outils_complementaires='y'");
        if ($test_outils_comp != 0) {
            $acces_fp="y";
        }
        else {
            $acces_fp="n";
		}

		$active_mod_discipline=getSettingValue("active_mod_discipline");
		if($active_mod_discipline!='y') {
			$acces_discipline="n";
		}

		//===========================================
		// Extraction de quelques donn�es sur l'�tablissement
		$RneEtablissement=getSettingValue("gepiSchoolRne") ? getSettingValue("gepiSchoolRne") : "";
		$gepiSchoolName=getSettingValue("gepiSchoolName") ? getSettingValue("gepiSchoolName") : "gepiSchoolName";
		$gepiSchoolAdress1=getSettingValue("gepiSchoolAdress1") ? getSettingValue("gepiSchoolAdress1") : "";
		$gepiSchoolAdress2=getSettingValue("gepiSchoolAdress2") ? getSettingValue("gepiSchoolAdress2") : "";
		$gepiSchoolZipCode=getSettingValue("gepiSchoolZipCode") ? getSettingValue("gepiSchoolZipCode") : "";
		$gepiSchoolCity=getSettingValue("gepiSchoolCity") ? getSettingValue("gepiSchoolCity") : "";
		$gepiSchoolPays=getSettingValue("gepiSchoolPays") ? getSettingValue("gepiSchoolPays") : "";
		$gepiYear = getSettingValue("gepiYear");

		$gepi_prof_suivi=getSettingValue("prof_suivi") ? getSettingValue("prof_suivi") : "";

		// Photo si module trombino actif
		$active_module_trombinoscopes=getSettingValue("active_module_trombinoscopes");
		//$bull_photo_largeur_max=getSettingValue("bull_photo_largeur_max") ? getSettingValue("bull_photo_largeur_max") : 100;
		//$bull_photo_hauteur_max=getSettingValue("bull_photo_hauteur_max") ? getSettingValue("bull_photo_hauteur_max") : 100;
		$photo_largeur_max=150;
		$photo_hauteur_max=150;

		// Lieu de naissance (peut ne pas �tre activ�):
		$ele_lieu_naissance=getSettingValue("ele_lieu_naissance") ? getSettingValue("ele_lieu_naissance") : "n";

		//===========================================
		// Initialisations concernant les relev�s de notes
		$p_releve_margin=getSettingValue("p_releve_margin") ? getSettingValue("p_releve_margin") : "";
		$releve_textsize=getSettingValue("releve_textsize") ? getSettingValue("releve_textsize") : 10;
		$releve_titlesize=getSettingValue("releve_titlesize") ? getSettingValue("releve_titlesize") : 16;
/*
		echo "<style type='text/css'>
	.releve_grand {
		color: #000000;
		font-size: ".$releve_titlesize."pt;
		font-style: normal;
	}

	.releve {
		color: #000000;
		font-size: ".$releve_textsize."pt;
		font-style: normal;\n";
		if($p_releve_margin!=""){
			echo "      margin-top: ".$p_releve_margin."pt;\n";
			echo "      margin-bottom: ".$p_releve_margin."pt;\n";
		}
		echo "}\n";

		echo "td.releve_empty{
		width:auto;
		padding-right: 20%;
	}

	.boireaus td {
		text-align:left;
	}\n";
		*/

		$active_cahiers_texte=getSettingValue("active_cahiers_texte") ? getSettingValue("active_cahiers_texte") : "n";

		// R�cup�ration des variables du bloc adresses:
		// Liste de r�cup�ration � extraire de la boucle �l�ves pour limiter le nombre de requ�tes... A FAIRE
		// Il y a d'autres r�cup�ration de largeur et de positionnement du bloc adresse � extraire...
		// PROPORTION 30%/70% POUR LE 1er TABLEAU ET ...
		$releve_addressblock_logo_etab_prop=getSettingValue("releve_addressblock_logo_etab_prop") ? getSettingValue("releve_addressblock_logo_etab_prop") : 40;
		$releve_addressblock_autre_prop=100-$releve_addressblock_logo_etab_prop;

		// Taille des polices sur le bloc adresse:
		$releve_addressblock_font_size=getSettingValue("releve_addressblock_font_size") ? getSettingValue("releve_addressblock_font_size") : 12;

		// Taille de la cellule Classe et Ann�e scolaire sur le bloc adresse:
		$releve_addressblock_classe_annee=getSettingValue("releve_addressblock_classe_annee") ? getSettingValue("releve_addressblock_classe_annee") : 35;
		// Calcul du pourcentage par rapport au tableau contenant le bloc Classe, Ann�e,...
		$releve_addressblock_classe_annee2=round(100*$releve_addressblock_classe_annee/(100-$releve_addressblock_logo_etab_prop));

		// D�bug sur l'ent�te pour afficher les cadres
		$releve_addressblock_debug=getSettingValue("releve_addressblock_debug") ? getSettingValue("releve_addressblock_debug") : "n";

		// Nombre de sauts de lignes entre le tableau logo+etab et le nom, pr�nom,... de l'�l�ve
		$releve_ecart_bloc_nom=getSettingValue("releve_ecart_bloc_nom") ? getSettingValue("releve_ecart_bloc_nom") : 0;

		// Afficher l'�tablissement d'origine de l'�l�ve:
		$releve_affiche_etab=getSettingValue("releve_affiche_etab") ? getSettingValue("releve_affiche_etab") : "n";

		// Bordure classique ou trait-noir:
		$releve_bordure_classique=getSettingValue("releve_bordure_classique") ? getSettingValue("releve_bordure_classique") : "y";
		if($releve_bordure_classique!="y"){
			$releve_class_bordure=" class='uneligne' ";
		}
		else{
			$releve_class_bordure="";
		}

		$releve_addressblock_length=getSettingValue("releve_addressblock_length") ? getSettingValue("releve_addressblock_length") : 6;
		$releve_addressblock_padding_top=getSettingValue("releve_addressblock_padding_top") ? getSettingValue("releve_addressblock_padding_top") : 0;
		$releve_addressblock_padding_text=getSettingValue("releve_addressblock_padding_text") ? getSettingValue("releve_addressblock_padding_text") : 0;
		$releve_addressblock_padding_right=getSettingValue("releve_addressblock_padding_right") ? getSettingValue("releve_addressblock_padding_right") : 0;



		// Affichage ou non du nom et de l'adresse de l'�tablissement
		$releve_affich_nom_etab=getSettingValue("releve_affich_nom_etab") ? getSettingValue("releve_affich_nom_etab") : "y";
		$releve_affich_adr_etab=getSettingValue("releve_affich_adr_etab") ? getSettingValue("releve_affich_adr_etab") : "y";
		if(($releve_affich_nom_etab!="n")&&($releve_affich_nom_etab!="y")) {$releve_affich_nom_etab="y";}
		if(($releve_affich_adr_etab!="n")&&($releve_affich_adr_etab!="y")) {$releve_affich_adr_etab="y";}

		$releve_ecart_entete=getSettingValue("releve_ecart_entete") ? getSettingValue("releve_ecart_entete") : 0;


		$releve_mention_doublant=getSettingValue("releve_mention_doublant") ? getSettingValue("releve_mention_doublant") : "n";


		$releve_cellspacing=getSettingValue("releve_cellspacing") ? getSettingValue("releve_cellspacing") : 2;
		$releve_cellpadding=getSettingValue("releve_cellpadding") ? getSettingValue("releve_cellpadding") : 5;


		$releve_affiche_numero=getSettingValue("releve_affiche_numero") ? getSettingValue("releve_affiche_numero") : "n";


		$releve_affiche_signature=getSettingValue("releve_affiche_signature") ? getSettingValue("releve_affiche_signature") : "y";

		$releve_affiche_formule=getSettingValue("releve_affiche_formule") ? getSettingValue("releve_affiche_formule") : "n";
		$releve_formule_bas=getSettingValue("releve_formule_bas") ? getSettingValue("releve_formule_bas") : "Relev� � conserver pr�cieusement. Aucun duplicata ne sera d�livr�. - GEPI : solution libre de gestion et de suivi des r�sultats scolaires.";


		$releve_col_hauteur=getSettingValue("releve_col_hauteur") ? getSettingValue("releve_col_hauteur") : 0;
		$releve_largeurtableau=getSettingValue("releve_largeurtableau") ? getSettingValue("releve_largeurtableau") : 800;
		$releve_col_matiere_largeur=getSettingValue("releve_col_matiere_largeur") ? getSettingValue("releve_col_matiere_largeur") : 150;

		$gepi_prof_suivi=getSettingValue("gepi_prof_suivi") ? getSettingValue("gepi_prof_suivi") : "professeur principal";

		$releve_affiche_eleve_une_ligne=getSettingValue("releve_affiche_eleve_une_ligne") ? getSettingValue("releve_affiche_eleve_une_ligne") : "n";
		$releve_mention_nom_court=getSettingValue("releve_mention_nom_court") ? getSettingValue("releve_mention_nom_court") : "y";

		$releve_photo_largeur_max=getSettingValue("releve_photo_largeur_max") ? getSettingValue("releve_photo_largeur_max") : 100;
		$releve_photo_hauteur_max=getSettingValue("releve_photo_hauteur_max") ? getSettingValue("releve_photo_hauteur_max") : 100;

		$releve_categ_font_size=getSettingValue("releve_categ_font_size") ? getSettingValue("releve_categ_font_size") : 10;
		$releve_categ_bgcolor=getSettingValue("releve_categ_bgcolor") ? getSettingValue("releve_categ_bgcolor") : "";

		$releve_affiche_tel=getSettingValue("releve_affiche_tel") ? getSettingValue("releve_affiche_tel") : "n";
		$releve_affiche_fax=getSettingValue("releve_affiche_fax") ? getSettingValue("releve_affiche_fax") : "n";

		if($releve_affiche_fax=="y"){
			$gepiSchoolFax=getSettingValue("gepiSchoolFax");
		}

		if($releve_affiche_tel=="y"){
			$gepiSchoolTel=getSettingValue("gepiSchoolTel");
		}

		$releve_affiche_INE_eleve=getSettingValue("releve_affiche_INE_eleve") ? getSettingValue("releve_affiche_INE_eleve") : "n";

		$genre_periode=getSettingValue("genre_periode") ? getSettingValue("genre_periode") : "M";

		$activer_photo_releve=getSettingValue("activer_photo_releve") ? getSettingValue("activer_photo_releve") : "n";
		$active_module_trombinoscopes=getSettingValue("active_module_trombinoscopes") ? getSettingValue("active_module_trombinoscopes") : "n";
		//===========================================





		// Biblioth�que de fonctions:
		//include("visu_ele_func.lib.php");
		//include("$gepiPath/eleves/visu_ele_func.lib.php");
		include("../eleves/visu_ele_func.lib.php");

		// On extrait un tableau de l'ensemble des infos sur l'�l�ve (bulletins, relev�s de notes,... inclus)
		$tab_ele=info_eleve($ele_login);

		echo "<script type='text/javascript'>
	document.getElementById('patience').style.display='none';
</script>\n";

		/*
		// Initialisation
		if(!isset($onglet)) {
			$onglet="eleve";
		}
		*/
		//====================================
		// Onglet Informations g�n�rales sur l'�l�ve
		echo "<div id='t_eleve' class='t_onglet' style='";
		if($onglet=='eleve') {
			echo "border-bottom-color: ".$tab_couleur['eleve']."; ";
		}
		else {
			echo "border-bottom-color: black; ";
		}
		echo "background-color: ".$tab_couleur['eleve']."; ";
		echo "'>";
		echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=eleve' onclick=\"affiche_onglet('eleve');return false;\">";
		//echo "<b>".$tab_ele['nom']." ".$tab_ele['prenom']." (<i>".$tab_ele['liste_classes']."</i>)</b>";
		echo "<b>".$tab_ele['nom']." ".$tab_ele['prenom']." (<i>";
		if(isset($tab_ele['liste_classes'])) {
			echo $tab_ele['liste_classes'];
		}
		else {
			echo "Aucune classe";
		}
		echo "</i>)</b>";
		echo "</a>";
		echo "</div>\n";

		// Onglet Informations responsables
		if($acces_responsables=="y") {
			echo "<div id='t_responsables' class='t_onglet' style='";
			if($onglet=='responsables') {
				echo "border-bottom-color: ".$tab_couleur['responsables']."; ";
			}
			else {
				echo "border-bottom-color: black; ";
			}
			echo "background-color: ".$tab_couleur['responsables']."; ";
			echo "'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=responsables' onclick=\"affiche_onglet('responsables');return false;\">Responsables</a>";
			echo "</div>\n";
		}

		// Onglet Enseignements suivis
		if($acces_enseignements=="y") {
			echo "<div id='t_enseignements' class='t_onglet' style='";
			if($onglet=='enseignements') {
				echo "border-bottom-color: ".$tab_couleur['enseignements']."; ";
			}
			else {
				echo "border-bottom-color: black; ";
			}
			echo "background-color: ".$tab_couleur['enseignements']."; ";
			echo "'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=enseignements' onclick=\"affiche_onglet('enseignements');return false;\">Enseignements</a>";
			echo "</div>\n";
		}

		// Onglet Bulletins
		if($acces_bulletins=="y") {
			echo "<div id='t_bulletins' class='t_onglet' style='";
			if($onglet=='bulletins') {
				echo "border-bottom-color: ".$tab_couleur['bulletins']."; ";
			}
			else {
				echo "border-bottom-color: black; ";
			}
			echo "background-color: ".$tab_couleur['bulletins']."; ";
			echo "'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=bulletins' onclick=\"affiche_onglet('bulletins');return false;\">Bulletins</a>";
			echo "</div>\n";
		}

		// Onglet Relev�s de notes
		if($acces_releves=="y") {
			echo "<div id='t_releves' class='t_onglet' style='";
			if($onglet=='releves') {
				echo "border-bottom-color: ".$tab_couleur['releves']."; ";
			}
			else {
				echo "border-bottom-color: black; ";
			}
			echo "background-color: ".$tab_couleur['releves']."; ";
			echo "'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=releves' onclick=\"affiche_onglet('releves');return false;\">Relev�s de notes</a>";
			echo "</div>\n";
		}

		// Onglet Cahier de textes
		if($acces_cdt=="y") {
			echo "<div id='t_cdt' class='t_onglet' style='";
			if($onglet=='cdt') {
				echo "border-bottom-color: ".$tab_couleur['cdt']."; ";
			}
			else {
				echo "border-bottom-color: black; ";
			}
			echo "background-color: ".$tab_couleur['cdt']."; ";
			echo "'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=cdt' onclick=\"affiche_onglet('cdt');return false;\">Cahier de textes</a>";
			echo "</div>\n";
		}

		// Onglet fiches projet
		if($acces_fp=="y") {
			echo "<div id='t_fp' class='t_onglet' style='";
			if($onglet=='fp') {
				echo "border-bottom-color: ".$tab_couleur['fp']."; ";
			}
			else {
				echo "border-bottom-color: black; ";
			}
			echo "background-color: ".$tab_couleur['fp']."; ";
			echo "'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=fp' onclick=\"affiche_onglet('fp');return false;\">Tous les projets</a>";
			echo "</div>\n";
		}


		// Onglet Absences
		if($acces_absences=="y") {
			echo "<div id='t_absences' class='t_onglet' style='";
			if($onglet=='absences') {
				echo "border-bottom-color: ".$tab_couleur['absences']."; ";
			}
			else {
				echo "border-bottom-color: black; ";
			}
			echo "background-color: ".$tab_couleur['absences']."; ";
			echo "'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=absences' onclick=\"affiche_onglet('absences');return false;\">Absences</a>";
			echo "</div>\n";
		}

		// Onglet Discipline
		if($acces_discipline=="y") {
			echo "<div id='t_discipline' class='t_onglet' style='";
			if($onglet=='discipline') {
				echo "border-bottom-color: ".$tab_couleur['discipline']."; ";
			}
			else {
				echo "border-bottom-color: black; ";
			}
			echo "background-color: ".$tab_couleur['discipline']."; ";
			echo "'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=discipline' onclick=\"affiche_onglet('discipline');return false;\">Discipline</a>";
			echo "</div>\n";
		}

		// Onglet Ann�es ant�rieures
		if($acces_anna=="y") {
			echo "<div id='t_anna' class='t_onglet' style='";
			if($onglet=='anna') {
				echo "border-bottom-color: ".$tab_couleur['anna']."; ";
			}
			else {
				echo "border-bottom-color: black; ";
			}
			echo "background-color: ".$tab_couleur['anna']."; ";
			echo "'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=anna' onclick=\"affiche_onglet('anna');return false;\">Ann�es ant.</a>";
			echo "</div>\n";
		}
		//=====================================================================================

		//====================================
		echo "<div style='clear:both;'></div>\n";
		//====================================

		//=====================================================================================

		// On passe aux cadres contenu des onglets

		//===================
		// Onglet ELEVE
		//===================

		echo "<div id='eleve' class='onglet' style='";
		if($onglet!="eleve") {echo " display:none;";}
		echo "background-color: ".$tab_couleur['eleve']."; ";
		echo "'>";
		echo "<h2>Informations sur l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";
		//affichage de la date de sortie de l'�l�ve de l'�tablissement
		if ($tab_ele['date_sortie']!=0) {
		   echo "<span class=\"red\">Date de sortie de l'�tablissement : le ".affiche_date_sortie($tab_ele['date_sortie'])."<br/><br/></span>";;
		}
		
		echo "<table border='0' summary='Infos �l�ve'>\n";
		echo "<tr>\n";
		echo "<td valign='top'>\n";

			echo "<table class='boireaus' summary='Infos �l�ve (1)'>\n";
			echo "<tr><th style='text-align: left;'>Nom&nbsp;:</th><td>";
			if(($_SESSION['statut']=='administrateur')||($_SESSION['statut']=='scolarite')) {
				echo "<a href='modify_eleve.php?eleve_login=".$ele_login."&amp;quelles_classes=certaines&amp;order_type=nom,prenom&amp;motif_rech='>".$tab_ele['nom']."</a>";
			}
			else {
				echo $tab_ele['nom'];
			}
			echo "</td></tr>\n";
			echo "<tr><th style='text-align: left;'>Pr�nom&nbsp;:</th><td>".$tab_ele['prenom']."</td></tr>\n";
			echo "<tr><th style='text-align: left;'>Sexe&nbsp;:</th><td>".$tab_ele['sexe']."</td></tr>\n";
			echo "<tr><th style='text-align: left;'>N�";
			if($tab_ele['sexe']=='F') {echo "e";}
			echo " le&nbsp;:</th><td>".$tab_ele['naissance']."</td></tr>\n";
			if(isset($tab_ele['lieu_naissance'])) {echo "<tr><th style='text-align: left;'>�&nbsp;:</th><td>".$tab_ele['lieu_naissance']."</td></tr>\n";}

			echo "<tr><th style='text-align: left;'>R�gime&nbsp;:</th><td>";
			if ($tab_ele['regime'] == "d/p") {echo "Demi-pensionnaire";}
			if ($tab_ele['regime'] == "ext.") {echo "Externe";}
			if ($tab_ele['regime'] == "int.") {echo "Interne";}
			if ($tab_ele['regime'] == "i-e"){
				echo "Interne&nbsp;extern�";
				if (strtoupper($tab_ele['sexe'])!= "F") {echo "e";}
			}
			echo "</td></tr>\n";

			echo "<tr><th style='text-align: left;'>Redoublant&nbsp;:</th><td>";
			if ($tab_ele['doublant'] == 'R'){
				echo "Oui";
			}
			else {
				echo "Non";
			}
			echo "</td></tr>\n";

			if(($_SESSION['statut']=='administrateur')||($_SESSION['statut']=='scolarite')||($_SESSION['statut']=='cpe')) {
				echo "<tr><th style='text-align: left;'>Elenoet&nbsp;:</th><td>".$tab_ele['elenoet']."</td></tr>\n";
				echo "<tr><th style='text-align: left;'>Ele_id&nbsp;:</th><td>".$tab_ele['ele_id']."</td></tr>\n";
				echo "<tr><th style='text-align: left;'>N�INE&nbsp;:</th><td>".$tab_ele['no_gep']."</td></tr>\n";
			}

			echo "<tr><th style='text-align: left;'>Email&nbsp;:</th><td>";
			$tmp_date=getdate();
			//echo "<a href='mailto:".$tab_ele['email']."?subject=GEPI&amp;body=";
			echo "<a href='mailto:".$tab_ele['email']."?subject=GEPI&amp;body=";
			if($tmp_date['hours']>=18) {echo "Bonsoir";} else {echo "Bonjour";}
			echo ",%0d%0aCordialement.'>";
			echo $tab_ele['email'];
			echo "</a>";
			echo "</td></tr>\n";

			//echo "<tr><th>:</th><td>".$tab_ele['']."</td></tr>\n";
			echo "</table>\n";
		echo "</td>\n";

		if($active_module_trombinoscopes=="y") {
			echo "<td valign='top'>\n";
				$photo=nom_photo($tab_ele['elenoet']);
				//if("$photo"!=""){
				if($photo){
					//$photo="../photos/eleves/".$photo;
					if(file_exists($photo)){
						$dimphoto=redimensionne_image_releve($photo);
						echo '<img src="'.$photo.'" style="width: '.$dimphoto[0].'px; height: '.$dimphoto[1].'px; border: 0px; border-right: 3px solid #FFFFFF; float: left;" alt="" />'."\n";
					}
				}
			echo "</td>\n";
		}
		echo "</tr>\n";
		echo "</table>\n";

		if(isset($tab_ele['etab_id'])) {
			if ($tab_ele['etab_id'] != '990') {
				if ($RneEtablissement != $tab_ele['etab_id']) {
					echo "<p>Etablissement d'origine : ";
					echo $tab_ele['etab_niveau_nom']." ".$tab_ele['etab_type']." ".$tab_ele['etab_nom']." (".$tab_ele['etab_cp']." ".$tab_ele['etab_ville'].")\n";
				}
			} else {
				echo "<p>Etablissement d'origine : ";
				echo "hors de France\n";
			}
			echo "</p>\n";
		}
		echo "</div>\n";

		//===================================================

		//=======================
		// Onglet RESPONSABLES
		//=======================

		if($acces_responsables=="y") {
			echo "<div id='responsables' class='onglet' style='";
			if($onglet!="responsables") {echo " display:none;";}
			echo "background-color: ".$tab_couleur['responsables']."; ";
			echo "'>";
			echo "<h2>Responsables de l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";

			if((!isset($tab_ele['resp']))||(count($tab_ele['resp'])==0)) {
				echo "<p>Aucun responsable n'a �t� enregistr�.</p>\n";
			}
			else {
				echo "<table border='0' summary='Infos responsables'>\n";
				echo "<tr>\n";
				$cpt_resp_legal0=0;
				for($i=0;$i<count($tab_ele['resp']);$i++) {
					if($tab_ele['resp'][$i]['resp_legal']!=0) {
						echo "<td valign='top'>\n";
						echo "<p>Responsable l�gal <b>".$tab_ele['resp'][$i]['resp_legal']."</b></p>\n";

						echo "<table class='boireaus' summary='Infos responsables (1)'>\n";
						echo "<tr><th style='text-align: left;'>Nom:</th><td>";

						if(($_SESSION['statut']=='administrateur')||($_SESSION['statut']=='scolarite')) {
							echo "<a href='../responsables/modify_resp.php?pers_id=".$tab_ele['resp'][$i]['pers_id']."'>".$tab_ele['resp'][$i]['nom']."</a>";
						}
						else {
							echo $tab_ele['resp'][$i]['nom'];
						}

						echo "</td></tr>\n";
						echo "<tr><th style='text-align: left;'>Pr�nom:</th><td>".$tab_ele['resp'][$i]['prenom']."</td></tr>\n";
						echo "<tr><th style='text-align: left;'>Civilit�:</th><td>".$tab_ele['resp'][$i]['civilite']."</td></tr>\n";
						if($tab_ele['resp'][$i]['tel_pers']!='') {echo "<tr><th style='text-align: left;'>T�l.pers:</th><td>".$tab_ele['resp'][$i]['tel_pers']."</td></tr>\n";}
						if($tab_ele['resp'][$i]['tel_port']!='') {echo "<tr><th style='text-align: left;'>T�l.port:</th><td>".$tab_ele['resp'][$i]['tel_port']."</td></tr>\n";}
						if($tab_ele['resp'][$i]['tel_prof']!='') {echo "<tr><th style='text-align: left;'>T�l.prof:</th><td>".$tab_ele['resp'][$i]['tel_prof']."</td></tr>\n";}
						if($tab_ele['resp'][$i]['mel']!='') {
							$tmp_date=getdate();
							echo "<tr><th style='text-align: left;'>Courriel:</th><td>";
							echo "<a href='mailto:".$tab_ele['resp'][$i]['mel']."?subject=GEPI&amp;body=";
							if($tmp_date['hours']>=18) {echo "Bonsoir";} else {echo "Bonjour";}
							echo ",%0d%0aCordialement.'>";
							echo $tab_ele['resp'][$i]['mel'];
							echo "</a>";
							echo "</td></tr>\n";
						}

						if(!isset($tab_ele['resp'][$i]['etat'])) {
							echo "<tr><th style='text-align: left;'>Dispose d'un compte:</th><td>Non</td></tr>\n";
						}
						else {
							echo "<tr><th style='text-align: left;'>Dispose d'un compte:</th><td>Oui (";
							if($tab_ele['resp'][$i]['etat']=='actif') {
								echo "<span style='color:green;'>";
							}
							else{
								echo "<span style='color:red;'>";
							}
							echo $tab_ele['resp'][$i]['etat'];
							echo "</span>)\n";
							echo "</td></tr>\n";
						}

						if($tab_ele['resp'][$i]['adr1']!='') {echo "<tr><th style='text-align: left;'>Ligne 1 adresse:</th><td>".$tab_ele['resp'][$i]['adr1']."</td></tr>\n";}
						if($tab_ele['resp'][$i]['adr2']!='') {echo "<tr><th style='text-align: left;'>Ligne 2 adresse:</th><td>".$tab_ele['resp'][$i]['adr2']."</td></tr>\n";}
						if($tab_ele['resp'][$i]['adr3']!='') {echo "<tr><th style='text-align: left;'>Ligne 3 adresse:</th><td>".$tab_ele['resp'][$i]['adr3']."</td></tr>\n";}
						if($tab_ele['resp'][$i]['adr4']!='') {echo "<tr><th style='text-align: left;'>Ligne 4 adresse:</th><td>".$tab_ele['resp'][$i]['adr4']."</td></tr>\n";}
						if($tab_ele['resp'][$i]['cp']!='') {echo "<tr><th style='text-align: left;'>Code postal:</th><td>".$tab_ele['resp'][$i]['cp']."</td></tr>\n";}
						if($tab_ele['resp'][$i]['commune']!='') {echo "<tr><th style='text-align: left;'>Commune:</th><td>".$tab_ele['resp'][$i]['commune']."</td></tr>\n";}
						if($tab_ele['resp'][$i]['pays']!='') {echo "<tr><th style='text-align: left;'>Pays:</th><td>".$tab_ele['resp'][$i]['pays']."</td></tr>\n";}

						echo "</table>\n";
						echo "</td>\n";
					}
					else {
						$cpt_resp_legal0++;
					}
				}
				echo "</tr>\n";
				echo "</table>\n";

				// Simples contacts non responsables l�gaux
				if($cpt_resp_legal0>0) {
					echo "<table border='0' summary='Infos responsables (0)'>\n";
					echo "<tr>\n";
					for($i=0;$i<count($tab_ele['resp']);$i++) {

						if($tab_ele['resp'][$i]['resp_legal']==0) {
							echo "<td valign='top'>\n";
							echo "<p>Contact (<i>non responsable l�gal</i>)</p>\n";

							echo "<table class='boireaus' summary='Infos resp0'>\n";
							echo "<tr><th style='text-align: left;'>Nom:</th><td>".$tab_ele['resp'][$i]['nom']."</td></tr>\n";
							echo "<tr><th style='text-align: left;'>Pr�nom:</th><td>".$tab_ele['resp'][$i]['prenom']."</td></tr>\n";
							echo "<tr><th style='text-align: left;'>Civilit�:</th><td>".$tab_ele['resp'][$i]['civilite']."</td></tr>\n";
							if($tab_ele['resp'][$i]['tel_pers']!='') {echo "<tr><th style='text-align: left;'>T�l.pers:</th><td>".$tab_ele['resp'][$i]['tel_pers']."</td></tr>\n";}
							if($tab_ele['resp'][$i]['tel_port']!='') {echo "<tr><th style='text-align: left;'>T�l.port:</th><td>".$tab_ele['resp'][$i]['tel_port']."</td></tr>\n";}
							if($tab_ele['resp'][$i]['tel_prof']!='') {echo "<tr><th style='text-align: left;'>T�l.prof:</th><td>".$tab_ele['resp'][$i]['tel_prof']."</td></tr>\n";}
							if($tab_ele['resp'][$i]['mel']!='') {echo "<tr><th style='text-align: left;'>Courriel:</th><td>".$tab_ele['resp'][$i]['mel']."</td></tr>\n";}

							if(!isset($tab_ele['resp'][$i]['etat'])) {
								echo "<tr><th style='text-align: left;'>Dispose d'un compte:</th><td>Non</td></tr>\n";
							}
							else {
								echo "<tr><th style='text-align: left;'>Dispose d'un compte:</th><td>Oui (";
								if($tab_ele['resp'][$i]['etat']=='actif') {
									echo "<span style='color:green;'>";
								}
								else{
									echo "<span style='color:red;'>";
								}
								echo $tab_ele['resp'][$i]['etat'];
								echo "</span>)\n";
								echo "</td></tr>\n";
							}

							if($tab_ele['resp'][$i]['adr1']!='') {echo "<tr><th style='text-align: left;'>Ligne 1 adresse:</th><td>".$tab_ele['resp'][$i]['adr1']."</td></tr>\n";}
							if($tab_ele['resp'][$i]['adr2']!='') {echo "<tr><th style='text-align: left;'>Ligne 2 adresse:</th><td>".$tab_ele['resp'][$i]['adr2']."</td></tr>\n";}
							if($tab_ele['resp'][$i]['adr3']!='') {echo "<tr><th style='text-align: left;'>Ligne 3 adresse:</th><td>".$tab_ele['resp'][$i]['adr3']."</td></tr>\n";}
							if($tab_ele['resp'][$i]['adr4']!='') {echo "<tr><th style='text-align: left;'>Ligne 4 adresse:</th><td>".$tab_ele['resp'][$i]['adr4']."</td></tr>\n";}
							if($tab_ele['resp'][$i]['cp']!='') {echo "<tr><th style='text-align: left;'>Code postal:</th><td>".$tab_ele['resp'][$i]['cp']."</td></tr>\n";}
							if($tab_ele['resp'][$i]['commune']!='') {echo "<tr><th style='text-align: left;'>Commune:</th><td>".$tab_ele['resp'][$i]['commune']."</td></tr>\n";}
							if($tab_ele['resp'][$i]['pays']!='') {echo "<tr><th style='text-align: left;'>Pays:</th><td>".$tab_ele['resp'][$i]['pays']."</td></tr>\n";}

							echo "</table>\n";
							echo "</td>\n";
						}
					}
					echo "</tr>\n";
					echo "</table>\n";
				}
			}
			echo "</div>\n";
		}

		//===================================================

		//========================
		// Onglet ENSEIGNEMENTS
		//========================

		if($acces_enseignements=="y") {
			echo "<div id='enseignements' class='onglet' style='";
			if($onglet!="enseignements") {echo " display:none;";}
			echo "background-color: ".$tab_couleur['enseignements']."; ";
			echo "'>";
			echo "<h2>Enseignements suivis par l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";

			if((!isset($tab_ele['periodes']))||(!isset($tab_ele['groupes']))) {
				echo "<p>Aucune p�riode ou aucun enseignement n'a �t� trouv� pour cet ".$gepiSettings['denomination_eleve'].".</p>\n";
			}
			else {
				echo "<table class='boireaus' summary='Enseignements'>\n";
				echo "<tr>\n";
				echo "<th>Enseignement</th>\n";
				echo "<th>Professeur(s)</th>\n";
				for($j=0;$j<count($tab_ele['periodes']);$j++) {
					echo "<th>\n";
					echo $tab_ele['periodes'][$j]['nom_periode'];
					echo "</th>\n";
				}
				echo "</tr>\n";

				$alt=1;
				for($i=0;$i<count($tab_ele['groupes']);$i++) {
					$alt=$alt*(-1);
					echo "<tr class='lig$alt'>\n";
					echo "<th>".htmlentities($tab_ele['groupes'][$i]['name'])."<br /><span style='font-size: x-small;'>".htmlentities($tab_ele['groupes'][$i]['description'])."</span></th>\n";
					echo "<td>\n";
                                        $nbre_professeurs = isset($tab_ele['groupes'][$i]['prof']) ? count($tab_ele['groupes'][$i]['prof']) : 0;
					for($j=0;$j<$nbre_professeurs;$j++) {
						if($tab_ele['groupes'][$i]['prof'][$j]['email']!='') {
							echo "<a href='mailto:".$tab_ele['groupes'][$i]['prof'][$j]['email']."?subject=GEPI - [".remplace_accents($tab_ele['nom'],'all')." ".remplace_accents($tab_ele['prenom'],'all')."]&amp;body=";
							if($tmp_date['hours']>=18) {echo "Bonsoir";} else {echo "Bonjour";}
							echo ",%0d%0aCordialement.'>";
						}
						if(isset($tab_ele['classe'][0]['id_classe'])) {
							echo affiche_utilisateur($tab_ele['groupes'][$i]['prof'][$j]['prof_login'], $tab_ele['classe'][0]['id_classe']);
						}
						else {
							echo ucfirst(strtolower($tab_ele['groupes'][$i]['prof'][$j]['prenom']));
							echo " ";
							echo ucfirst(strtolower($tab_ele['groupes'][$i]['prof'][$j]['nom']));
						}
						if($tab_ele['groupes'][$i]['prof'][$j]['email']!='') {echo "</a>";}

						echo "<br />\n";
					}
					echo "</td>\n";
					for($j=0;$j<count($tab_ele['periodes']);$j++) {
						echo "<td";
						if(in_array($tab_ele['periodes'][$j]['num_periode'],$tab_ele['groupes'][$i]['periodes'])) {
							echo ">\n";
							//echo "X";
							echo $tab_ele['periodes'][$j]['classe'];
						}
						else {
							echo " style='background-color: gray;";
							echo "'>\n";
							echo "&nbsp;";
						}
						echo "</td>\n";
					}
					echo "</tr>\n";
				}
				echo "</table>\n";

				echo "<p><b>".ucfirst($gepi_prof_suivi)."</b>: ";
				for($loop=0;$loop<count($tab_ele['classe']);$loop++) {
					if($loop>0) {echo ", ";}
					if($tab_ele['classe'][$loop]['pp']['email']!="") {
						//echo "<a href='mailto:".$tab_ele['classe'][$loop]['pp']['email']."'>";
						//echo "<a href='mailto:".$tab_ele['classe'][$loop]['pp']['email']."'>";
						echo "<a href='mailto:".$tab_ele['classe'][$loop]['pp']['email']."?subject=GEPI - [".remplace_accents($tab_ele['nom'],'all')." ".remplace_accents($tab_ele['prenom'],'all')."]&amp;body=";
						if($tmp_date['hours']>=18) {echo "Bonsoir";} else {echo "Bonjour";}
						echo ",%0d%0aCordialement.'>";
					}
					echo $tab_ele['classe'][$loop]['pp']['civ_nom_prenom'];
					if($tab_ele['classe'][$loop]['pp']['email']!="") {
						echo "</a>";
					}
					echo " (<i>".$tab_ele['classe'][$loop]['classe']."</i>)";
				}
				echo "</p>\n";

				echo "<p><b>CPE charg�(e) du suivi</b>: ";
				if($tab_ele['cpe']['email']!="") {
					//echo "<a href='mailto:".$tab_ele['cpe']['email']."'>";
					//echo "<a href='mailto:".$tab_ele['cpe']['email']."'>";
					echo "<a href='mailto:".$tab_ele['cpe']['email']."?subject=GEPI - [".remplace_accents($tab_ele['nom'],'all')." ".remplace_accents($tab_ele['prenom'],'all')."]&amp;body=";
					if($tmp_date['hours']>=18) {echo "Bonsoir";} else {echo "Bonjour";}
					echo ",%0d%0aCordialement.'>";
				}
				echo $tab_ele['cpe']['civ_nom_prenom'];
				if($tab_ele['cpe']['email']!="") {
					echo "</a>";
				}
				echo "</p>\n";

				if($tab_ele['equipe_liste_email']!="") {
					$tmp_date=getdate();
					//echo "<p>Ecrire un email � <a href='mailto:".$tab_ele['equipe_liste_email']."?subject=GEPI&amp;body=";
					echo "<p>Ecrire un email � <a href='mailto:".$tab_ele['equipe_liste_email']."?subject=GEPI - [".remplace_accents($tab_ele['nom'],'all')." ".remplace_accents($tab_ele['prenom'],'all')."]&amp;body=";
					if($tmp_date['hours']>=18) {echo "Bonsoir";} else {echo "Bonjour";}
					if(preg_match("/,/",$tab_ele['equipe_liste_email'])) {echo " � tou(te)s";}
					echo ",%0d%0aCordialement.'>tous les enseignants et au CPE de l'�l�ve</a>.</p>\n";
				}
			}
			echo "</div>\n";
		}
		//===================================================

		//===================
		// Onglet BULLETINS
		//===================

		$tab_onglets_bull=array();
		if($acces_bulletins=="y") {
			echo "<div id='bulletins' class='onglet' style='";
			if($onglet!="bulletins") {echo " display:none;";}
			echo "background-color: ".$tab_couleur['bulletins']."; ";
			echo "'>";

			echo "<h2>Bulletins de l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";

			$sql="SELECT MIN(periode) AS min_per, MAX(periode) AS max_per FROM matieres_notes WHERE login='".$ele_login."';";
			//echo "$sql<br />";
			$res_per=mysql_query($sql);
			if(mysql_num_rows($res_per)>0) {
				$lig_per=mysql_fetch_object($res_per);
				// Afficher les trois trimestres sur le bulletin simplifi� affiche des infos erron�es quant au nom des professeurs si l'�l�ve a chang� de classe.
				$periode_numero_1=$lig_per->min_per;
				$periode_numero_2=$lig_per->max_per;

				//echo "\$periode_numero_1=$periode_numero_1<br />";
				//echo "\$periode_numero_2=$periode_numero_2<br />";

				if(($periode_numero_1!='')&&($periode_numero_2!='')) {

					include "../lib/bulletin_simple.inc.php";

					//$tab_onglets_bull=array();
					for($n_per=$periode_numero_1;$n_per<=$periode_numero_2;$n_per++) {
						$periode1=$n_per;
						$tab_onglets_bull[]="bulletin_$periode1";

						echo "<div id='t_bulletin_$periode1' class='t_onglet' style='";
						if(isset($onglet2)) {
							if($onglet2=="bulletin_$periode1") {
								echo "border-bottom-color: ".$tab_couleur['bulletin']."; ";
							}
						}
						else {
							if($n_per==$periode_numero_1) {
								echo "border-bottom-color: ".$tab_couleur['bulletin']."; ";
							}
						}
						echo "background-color: ".$tab_couleur['bulletin']."; ";
						echo "'>";

						echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=bulletins&amp;onglet2=bulletin_$periode1' onclick=\"affiche_onglet('bulletins');affiche_onglet_bull('bulletin_$periode1');return false;\">";
						echo "P�riode $periode1";
						echo "</a>";
						echo "</div>\n";

					}

					//====================================
					echo "<div style='clear:both;'></div>\n";
					//====================================

					for($n_per=$periode_numero_1;$n_per<=$periode_numero_2;$n_per++) {
						$periode1=$n_per;
						$periode2=$n_per;

						$index_per=-1;

						for($loop=0;$loop<count($tab_ele['periodes']);$loop++) {
							if($tab_ele['periodes'][$loop]['num_periode']==$n_per) {
								$index_per=$loop;
								break;
							}
						}

						$id_classe=$tab_ele['periodes'][$index_per]['id_classe'];

						// Boucle sur la liste des classes de l'�l�ve pour que $id_classe soit fix� avant l'appel: periodes.inc.php
						include "../lib/periodes.inc.php";


						// On teste la pr�sence d'au moins un coeff pour afficher la colonne des coef
						$test_coef = mysql_num_rows(mysql_query("SELECT coef FROM j_groupes_classes WHERE (id_classe='".$id_classe."' and coef > 0)"));
						// Apparemment, $test_coef est r�affect� plus loin dans un des include()
						$nb_coef_superieurs_a_zero=$test_coef;

						// On regarde si on affiche les cat�gories de mati�res
						$affiche_categories = sql_query1("SELECT display_mat_cat FROM classes WHERE id='".$id_classe."'");
						if ($affiche_categories == "y") { $affiche_categories = true; } else { $affiche_categories = false;}

						// Si le rang des �l�ves est demand�, on met � jour le champ rang de la table matieres_notes
						$affiche_rang = sql_query1("SELECT display_rang FROM classes WHERE id='".$id_classe."'");
						if ($affiche_rang == 'y') {
							$periode_num=$periode1;
							while ($periode_num < $periode2+1) {
								include "../lib/calcul_rang.inc.php";
								$periode_num++;
							}
						}

						$coefficients_a_1="non";
						$affiche_graph = 'n';

						//unset($tab_moy_gen);
						//unset($tab_moy_cat_classe);
						for($loop=$periode1;$loop<=$periode2;$loop++) {
							$periode_num=$loop;
							include "../lib/calcul_moy_gen.inc.php";
							//$tab_moy_gen[$loop]=$moy_generale_classe;

							//======================================================================
							$tab_moy['periodes'][$periode_num]=array();
							$tab_moy['periodes'][$periode_num]['tab_login_indice']=$tab_login_indice;         // [$login_eleve]
							$tab_moy['periodes'][$periode_num]['moy_gen_eleve']=$moy_gen_eleve;               // [$i]
							$tab_moy['periodes'][$periode_num]['moy_gen_eleve1']=$moy_gen_eleve1;             // [$i]
							//$tab_moy['periodes'][$periode_num]['moy_gen_classe1']=$moy_gen_classe1;           // [$i]
							$tab_moy['periodes'][$periode_num]['moy_generale_classe']=$moy_generale_classe;
							$tab_moy['periodes'][$periode_num]['moy_generale_classe1']=$moy_generale_classe1;
							$tab_moy['periodes'][$periode_num]['moy_max_classe']=$moy_max_classe;
							$tab_moy['periodes'][$periode_num]['moy_min_classe']=$moy_min_classe;

							// Il faudrait r�cup�rer/stocker les cat�gories?
							$tab_moy['periodes'][$periode_num]['moy_cat_eleve']=$moy_cat_eleve;               // [$i][$cat]
							$tab_moy['periodes'][$periode_num]['moy_cat_classe']=$moy_cat_classe;             // [$i][$cat]
							$tab_moy['periodes'][$periode_num]['moy_cat_min']=$moy_cat_min;                   // [$i][$cat]
							$tab_moy['periodes'][$periode_num]['moy_cat_max']=$moy_cat_max;                   // [$i][$cat]

							$tab_moy['periodes'][$periode_num]['quartile1_classe_gen']=$quartile1_classe_gen;
							$tab_moy['periodes'][$periode_num]['quartile2_classe_gen']=$quartile2_classe_gen;
							$tab_moy['periodes'][$periode_num]['quartile3_classe_gen']=$quartile3_classe_gen;
							$tab_moy['periodes'][$periode_num]['quartile4_classe_gen']=$quartile4_classe_gen;
							$tab_moy['periodes'][$periode_num]['quartile5_classe_gen']=$quartile5_classe_gen;
							$tab_moy['periodes'][$periode_num]['quartile6_classe_gen']=$quartile6_classe_gen;
							$tab_moy['periodes'][$periode_num]['place_eleve_classe']=$place_eleve_classe;

							$tab_moy['periodes'][$periode_num]['current_eleve_login']=$current_eleve_login;   // [$i]
							//$tab_moy['periodes'][$periode_num]['current_group']=$current_group;
							if($loop==$periode1) {
								$tab_moy['current_group']=$current_group;                                     // [$j]
							}
							$tab_moy['periodes'][$periode_num]['current_eleve_note']=$current_eleve_note;     // [$j][$i]
							$tab_moy['periodes'][$periode_num]['current_eleve_statut']=$current_eleve_statut; // [$j][$i]
							//$tab_moy['periodes'][$periode_num]['current_group']=$current_group;
							$tab_moy['periodes'][$periode_num]['current_coef']=$current_coef;                 // [$j]
							$tab_moy['periodes'][$periode_num]['current_classe_matiere_moyenne']=$current_classe_matiere_moyenne; // [$j]

							$tab_moy['periodes'][$periode_num]['current_coef_eleve']=$current_coef_eleve;     // [$i][$j] ATTENTION
							$tab_moy['periodes'][$periode_num]['moy_min_classe_grp']=$moy_min_classe_grp;     // [$j]
							$tab_moy['periodes'][$periode_num]['moy_max_classe_grp']=$moy_max_classe_grp;     // [$j]
							if(isset($current_eleve_rang)) {
								// $current_eleve_rang n'est pas renseign� si $affiche_rang='n'
								$tab_moy['periodes'][$periode_num]['current_eleve_rang']=$current_eleve_rang; // [$j][$i]
							}
							$tab_moy['periodes'][$periode_num]['quartile1_grp']=$quartile1_grp;               // [$j]
							$tab_moy['periodes'][$periode_num]['quartile2_grp']=$quartile2_grp;               // [$j]
							$tab_moy['periodes'][$periode_num]['quartile3_grp']=$quartile3_grp;               // [$j]
							$tab_moy['periodes'][$periode_num]['quartile4_grp']=$quartile4_grp;               // [$j]
							$tab_moy['periodes'][$periode_num]['quartile5_grp']=$quartile5_grp;               // [$j]
							$tab_moy['periodes'][$periode_num]['quartile6_grp']=$quartile6_grp;               // [$j]
							$tab_moy['periodes'][$periode_num]['place_eleve_grp']=$place_eleve_grp;           // [$j][$i]

							$tab_moy['periodes'][$periode_num]['current_group_effectif_avec_note']=$current_group_effectif_avec_note; // [$j]
							//======================================================================

						}

						$display_moy_gen=sql_query1("SELECT display_moy_gen FROM classes WHERE id='".$id_classe."'");

						echo "<div id='bulletin_$periode1' class='onglet' style='";
						echo " background-color: ".$tab_couleur['bulletin'].";";
						if((isset($onglet2))&&(substr($onglet2,0,9)=='bulletin_')) {
							if('bulletin_'.$n_per!=$onglet2) {
								echo " display:none;";
							}
						}
						else {
							if($n_per!=$periode_numero_1) {
								echo " display:none;";
							}
						}
						echo "'>\n";

						//bulletin($ele_login,1,1,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$nb_coef_superieurs_a_zero,$affiche_categories,'y');
						bulletin($tab_moy,$ele_login,1,1,$periode1,$periode2,$nom_periode,$gepiYear,$id_classe,$affiche_rang,$nb_coef_superieurs_a_zero,$affiche_categories,'y');

						echo "</div>\n";
					}
				}
				else {
					// Il ne faut pas proposer de bulletin
					echo "<p>Aucun bulletin � ce jour.</p>\n";
				}
			}
			else {
				// Il ne faut pas proposer de bulletin
				echo "<p>Aucun bulletin � ce jour.</p>\n";
			}

			echo "</div>\n";
		}
		//===================================================

		//===================================================

		//==========================
		// Onglet RELEVES DE NOTES
		//==========================

		$tab_onglets_rel=array();
		if($acces_releves=="y") {
			echo "<div id='releves' class='onglet' style='";
			if($onglet!="releves") {echo " display:none;";}
			echo "background-color: ".$tab_couleur['releves']."; ";
			echo "'>";

			$sql="SELECT MIN(ccn.periode) AS min_per, MAX(ccn.periode) AS max_per FROM cn_cahier_notes ccn,j_eleves_groupes jeg WHERE jeg.login='".$ele_login."' AND jeg.id_groupe=ccn.id_groupe AND jeg.periode=ccn.periode;";
			//echo "$sql<br />";
			$res_per=mysql_query($sql);
			if(mysql_num_rows($res_per)>0) {
				$lig_per=mysql_fetch_object($res_per);
				$periode_numero_1=$lig_per->min_per;
				$periode_numero_2=$lig_per->max_per;
			}
			else {
				// Il ne faut pas proposer de relev� de notes?
			}

			echo "<h2>Relev�s de notes de l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";

			$id_releve_1="";

			//echo "\$periode_numero_1=$periode_numero_1<br />";
			//echo "\$periode_numero_2=$periode_numero_2<br />";

			if(($periode_numero_1!="")&&($periode_numero_2!="")) {
				//$tab_onglets_rel=array();
				for($n_per=$periode_numero_1;$n_per<=$periode_numero_2;$n_per++) {
					$periode1=$n_per;
					$tab_onglets_rel[]="releve_$periode1";

					echo "<div id='t_releve_$periode1' class='t_onglet' style='";
					if(isset($onglet2)) {
						if($onglet2=="releve_$periode1") {
							echo "border-bottom-color: ".$tab_couleur['releve']."; ";
						}
					}
					else {
						if($n_per==$periode_numero_1) {
							echo "border-bottom-color: ".$tab_couleur['releve']."; ";
						}
					}
					echo "background-color: ".$tab_couleur['releve']."; ";
					echo "'>";
					echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=releves&amp;onglet2=releve_$periode1' onclick=\"affiche_onglet('releves');affiche_onglet_rel('releve_$periode1');return false;\">";
					echo "P�riode $periode1";
					echo "</a>";
					echo "</div>\n";
				}

				//====================================
				echo "<div style='clear:both;'></div>\n";
				//====================================

				// Liste des infos � faire apparaitre sur le relev� de notes:
				// Si des appr�ciations ont �t� saisies et que dans les param�tres du devoir il est pr�cis� qu'elles doivent �tre visibles des parents, il n'y a pas de raison de ne pas les afficher
				$tab_ele['rn_app']='y';
				/*
				$tab_ele['rn_app']='n';
				$tab_ele['rn_nomdev']='y';
				$tab_ele['rn_toutcoefdev']='y';
				$tab_ele['rn_coefdev_si_diff']='y';
				$tab_ele['rn_datedev']='y';
				$tab_ele['rn_sign_chefetab']='n';
				$tab_ele['rn_sign_pp']='n';
				$tab_ele['rn_sign_resp']='n';
				$tab_ele['rn_formule']='';
				*/

				for($n_per=$periode_numero_1;$n_per<=$periode_numero_2;$n_per++) {
					$periode1=$n_per;
					$periode2=$n_per;

					$index_per=-1;
					for($loop=0;$loop<count($tab_ele['periodes']);$loop++) {
						if($tab_ele['periodes'][$loop]['num_periode']==$n_per) {
							$index_per=$loop;
							break;
						}
					}

					if($index_per!=-1) {
						// On r�cup�re la classe de l'�l�ve sur la p�riode consid�r�e
						$id_classe=$tab_ele['periodes'][$index_per]['id_classe'];
						//echo "\$id_classe=$id_classe<br />";

						// Boucle sur la liste des classes de l'�l�ve pour que $id_classe soit fix� avant l'appel: periodes.inc.php
						include "../lib/periodes.inc.php";

						// On teste la pr�sence d'au moins un coeff pour afficher la colonne des coef
						$test_coef = mysql_num_rows(mysql_query("SELECT coef FROM j_groupes_classes WHERE (id_classe='".$id_classe."' and coef > 0)"));
						//echo "\$test_coef=$test_coef<br />";
						// Apparemment, $test_coef est r�affect� plus loin dans un des include()
						$nb_coef_superieurs_a_zero=$test_coef;

						// On regarde si on affiche les cat�gories de mati�res
						$affiche_categories = sql_query1("SELECT display_mat_cat FROM classes WHERE id='".$id_classe."'");
						if ($affiche_categories == "y") { $affiche_categories = true; } else { $affiche_categories = false;}

						echo "<div id='releve_$periode1' class='onglet' style='";
						echo " background-color: ".$tab_couleur['releve'].";";
						if((isset($onglet2))&&(substr($onglet2,0,7)=='releve_')) {
							if('releve_'.$n_per!=$onglet2) {
								echo " display:none;";
							}
						}
						else {
							if($n_per!=$periode_numero_1) {
								echo " display:none;";
							}
						}
						echo "'>\n";
						//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
						// IL MANQUE UN PAQUET D'INITIALISATIONS POUR LES APPELS global DANS releve_html()
						//echo "<pre>";
						//print_r($tab_ele);
						//echo "</pre>";
						releve_html($tab_ele,$id_classe,$periode1,$index_per);
						//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
						echo "</div>\n";
					}
				}
			}
			else {
				echo "<p>Aucune note � ce jour.</p>\n";
			}
			echo "</div>\n";
		}

		//=============================================

		//========================
		// Onglet CAHIER DE TEXTES
		//========================

		if($acces_cdt=="y") {
			echo "<div id='cdt' class='onglet' style='";
			if($onglet!="cdt") {echo " display:none;";}
			echo "background-color: ".$tab_couleur['cdt']."; ";
			echo "'>";
			echo "<h2>Cahier de textes de l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";

			echo "<p align='center'>";
			echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=cdt&amp;day=$j_sem_prec&amp;month=$m_sem_prec&amp;year=$y_sem_prec'><img src='../images/icons/back.png' width='16' height='16' alt='Semaine pr�c�dente' /></a> ";
			echo "Du ".jour_en_fr(date("D",$date_ct1))." ".date("d/m/Y",$date_ct1)." au ".jour_en_fr(date("D",$date_ct2))." ".date("d/m/Y",$date_ct2);
			echo " <a href='".$_SERVER['PHP_SELF']."?ele_login=$ele_login&amp;onglet=cdt&amp;day=$j_sem_suiv&amp;month=$m_sem_suiv&amp;year=$y_sem_suiv'><img src='../images/icons/forward.png' width='16' height='16' alt='Semaine suivante' /></a>";
			echo "</p>\n";

			$couleur_dev="#FFCCCF";
			$couleur_entry="#C7FF99";

			echo "<div align='center'>\n";
			echo "<table class='boireaus' border='1' summary='CDT'>\n";
			echo "<tr><th>Date</th><th>Travail � effectuer</th><th>Compte rendu de s�ance</th></tr>\n";

			// On compte les entr�es du cdt
			if (isset($tab_ele['cdt'])) {
				$nbre_cdt = count($tab_ele['cdt']);
			}else{
				$nbre_cdt = 0;
			}

			for($j=0;$j<$nbre_cdt;$j++) {

				echo "<tr>\n";
				echo "<td>\n";
				//echo "Date ".jour_en_fr(date("D",$tab_ele['cdt'][$j]['date_ct']))." ".date("d/m/Y",$tab_ele['cdt'][$j]['date_ct'])."<br />\n";
				echo ucfirst(jour_en_fr(date("D",$tab_ele['cdt'][$j]['date_ct'])))." ".date("d/m/Y",$tab_ele['cdt'][$j]['date_ct'])."<br />\n";
				echo "</td>\n";

				//echo "<td valign='top' style='padding:3px;'>\n";
				echo "<td valign='top'>\n";
				//echo "<div style='border:1px solid black; padding:2px;'>\n";
				if(isset($tab_ele['cdt'][$j]['dev'])) {
					for($k=0;$k<count($tab_ele['cdt'][$j]['dev']);$k++) {
						//echo "<div style='border:1px solid black; background-color: lightyellow; margin:1px; display:block; width:40%;'>\n";
						echo "<table class='boireaus' border='1' style='margin:3px; width:100%;' summary='CDT'>\n";
						echo "<tr style='background-color:$couleur_dev;'>\n";
						echo "<td>\n";
						echo $tab_ele['groupes'][$tab_ele['index_grp'][$tab_ele['cdt'][$j]['dev'][$k]['id_groupe']]]['matiere_nom_complet']." <span style='font-size:x-small;'>(".$tab_ele['groupes'][$tab_ele['index_grp'][$tab_ele['cdt'][$j]['dev'][$k]['id_groupe']]]['name'].")</span>";
						echo "</td>\n";

						echo "<td>\n";
						//echo "Prof ".$tab_ele['cdt'][$j]['dev'][$k]['id_login']."<br />\n";
						echo $tab_ele['groupes'][$tab_ele['index_grp'][$tab_ele['cdt'][$j]['dev'][$k]['id_groupe']]]['prof_liste']."<br />\n";
						echo "</td>\n";
						echo "</tr>\n";

						echo "<tr style='background-color:$couleur_dev;'>\n";
						echo "<td colspan='2' style='text-align:left;'>\n";
						//echo "Date ".jour_en_fr(date("D",$tab_ele['cdt'][$j]['dev'][$k]['date_ct']))." ".date("d/m/Y",$tab_ele['cdt'][$j]['dev'][$k]['date_ct'])."<br />\n";
						echo nl2br($tab_ele['cdt'][$j]['dev'][$k]['contenu']);

						$adj=affiche_docs_joints($tab_ele['cdt'][$j]['dev'][$k]['id_ct'],"t");
						if($adj!='') {
							echo "<div style='border: 1px dashed black'>\n";
							echo $adj;
							echo "</div>\n";
						}

						//echo "</div>\n";
						echo "</td>\n";
						echo "</tr>\n";
						echo "</table>\n";
					}
				}
				echo "</td>\n";

				//echo "<td valign='top' style='padding:3px;'>\n";
				echo "<td valign='top'>\n";
				if(isset($tab_ele['cdt'][$j]['entry'])) {
					for($k=0;$k<count($tab_ele['cdt'][$j]['entry']);$k++) {
						//echo "<div style='border:1px solid black; background-color: lightgreen; margin:1px; display:block; width:40%;'>\n";
						//echo "Groupe ".$tab_ele['cdt'][$j]['entry'][$k]['id_groupe']."<br />\n";
						//echo "Prof ".$tab_ele['cdt'][$j]['entry'][$k]['id_login']."<br />\n";
						//echo "Date ".jour_en_fr(date("D",$tab_ele['cdt'][$j]['dev'][$k]['date_ct']))." ".date("d/m/Y",$tab_ele['cdt'][$j]['dev'][$k]['date_ct'])."<br />\n";
						//echo $tab_ele['cdt'][$j]['entry'][$k]['contenu'];
						echo "<table class='boireaus' border='1' style='margin:3px; width:100%;' summary='CDT'>\n";
						echo "<tr style='background-color:$couleur_entry;'>\n";
						echo "<td>\n";
						echo $tab_ele['groupes'][$tab_ele['index_grp'][$tab_ele['cdt'][$j]['entry'][$k]['id_groupe']]]['matiere_nom_complet']." <span style='font-size:x-small;'>(".$tab_ele['groupes'][$tab_ele['index_grp'][$tab_ele['cdt'][$j]['entry'][$k]['id_groupe']]]['name'].")</span>";
						echo "</td>\n";

						echo "<td>\n";
						echo $tab_ele['groupes'][$tab_ele['index_grp'][$tab_ele['cdt'][$j]['entry'][$k]['id_groupe']]]['prof_liste']."<br />\n";
						echo "</td>\n";
						echo "</tr>\n";

						echo "<tr style='background-color:$couleur_entry;'>\n";
						echo "<td colspan='2' style='text-align:left;'>\n";
						echo nl2br($tab_ele['cdt'][$j]['entry'][$k]['contenu']);

						$adj=affiche_docs_joints($tab_ele['cdt'][$j]['entry'][$k]['id_ct'],"c");
						if($adj!='') {
							echo "<div style='border: 1px dashed black'>\n";
							echo $adj;
							echo "</div>\n";
						}
						echo "</td>\n";
						echo "</tr>\n";
						echo "</table>\n";
					}
				}

				//echo "</div>\n";
				echo "</tr>\n";
			}

			//echo "</div>\n";
			echo "</table>\n";
			echo "</div>\n";

			echo "</div>\n";
		}


		//=============================================

		//========================
		// Onglet FICHES PROJET
		//========================

		if($acces_fp=="y") {
			echo "<div id='fp' class='onglet' style='";
			if($onglet!="fp") {echo " display:none;";}
			echo "background-color: ".$tab_couleur['fp']."; ";
			echo "'>";
      $call_data = mysql_query("SELECT j.indice_aid, j.id_aid FROM  j_aid_eleves j, aid_config a where
       j.login='$ele_login' and a.indice_aid=j.indice_aid and a.outils_complementaires='y' order by j.indice_aid");

      $nb_aid = mysql_num_rows($call_data);
      if ($nb_aid>0) {
  			echo "<h2>Tous les projets de l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";
        echo "<table width=\"80%\" border=\"1\" cellspacing=\"1\" cellpadding=\"3\">";
        $z=0;
        while ($z < $nb_aid) {
          $indice_aid = @mysql_result($call_data, $z, "indice_aid");
          $aid_id =  @mysql_result($call_data, $z, "id_aid");
          $nom_type_aid =  sql_query1("SELECT nom FROM aid_config  WHERE (indice_aid='$indice_aid')");
          $nom_aid = sql_query1("SELECT nom FROM aid WHERE (id='$aid_id' and indice_aid='$indice_aid')");
          $aid_prof_resp_query = mysql_query("SELECT id_utilisateur FROM j_aid_utilisateurs WHERE (id_aid='$aid_id' and indice_aid='$indice_aid')");
          $nb_lig = mysql_num_rows($aid_prof_resp_query);
          $n = '0';
          while ($n < $nb_lig) {
            $aid_prof_resp_login = @mysql_result($aid_prof_resp_query, $n, "id_utilisateur");
            $aid_prof_query = @mysql_query("SELECT nom,prenom FROM utilisateurs WHERE login='$aid_prof_resp_login'");
            $aid_prof_resp_nom[$n] = @mysql_result($aid_prof_query, 0, "nom");
            $aid_prof_resp_prenom[$n] = @mysql_result($aid_prof_query, 0, "prenom");
            $n++;
          }
          echo "<tr><td><span class='small'><b>$nom_type_aid</b>";
          $n = '0';
          while ($n < $nb_lig) {
            echo "<br /><i>$aid_prof_resp_nom[$n] $aid_prof_resp_prenom[$n]</i>";
            $n++;
          }
          echo "</span></td>";
          echo "<td><span class='small'><a href='../aid/modif_fiches.php?aid_id=$aid_id&amp;indice_aid=$indice_aid&amp;annee=&amp;action=visu&amp;retour=' target='_blank'>$nom_aid</a></span></td></tr>";
          $z++;
        }
        echo "</table>";
      } else {
  			echo "<h2>L'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']." n'est actuellement inscrit dans aucun projet.</h2>\n";
      }

      // Affichage des projets des ann�es ant�rieures
      $id_nat = sql_query1("select no_gep from eleves where login='".$ele_login."'");
      $call_data = mysql_query("select ta.annee, ta.id, a.id, ta.nom, a.nom, a.responsables
      from archivage_aids a, archivage_types_aid ta, archivage_aid_eleve ae
      where ta.outils_complementaires='y' and
      a.id=ae.id_aid and
      ae.id_eleve='".$id_nat."' and
      a.id_type_aid = ta.id
      order by ta.annee");

      $nb_aid = mysql_num_rows($call_data);
      if ($nb_aid>0) {
        echo "<h2>Les projets des ann�es ant�rieures</h2>";
        echo "<table width=\"$larg_tab\" border=\"1\" cellspacing=\"1\" cellpadding=\"3\">";
        $z=0;
        while ($z < $nb_aid) {
          $annee = @mysql_result($call_data, $z, "ta.annee");
          $indice_aid = @mysql_result($call_data, $z, "ta.id");
          $aid_id =  @mysql_result($call_data, $z, "a.id");
          $nom_type_aid = @mysql_result($call_data, $z, "ta.nom");
          $nom_aid =  @mysql_result($call_data, $z, "a.nom");
          $aid_prof_resp =  @mysql_result($call_data, $z, "a.responsables");
          echo "<tr>\n";
          echo "<td><span class='small'>".$annee."</td>\n";
          echo "<td><span class='small'><b>$nom_type_aid</b>";
          echo "<br /><i>$aid_prof_resp</i>";
          echo "</span></td>\n";
          echo "<td><span class='small'><a href='../aid/modif_fiches.php?aid_id=$aid_id&amp;indice_aid=$indice_aid&amp;annee=$annee&amp;action=visu&amp;retour=' target='_blank'>$nom_aid</a></span></td>\n</tr>\n";
          $z++;
        }
        echo "</table>\n";
      }

      /**************************************************************************
      * Cas ou le plugin "gestion_autorisations_publications" existe et est activ�
      ****************************************************************************/
      //On v�rifie si le module est activ�
      $test_plugin = sql_query1("select ouvert from plugins where nom='gestion_autorisations_publications'");
      if ($test_plugin=='y') {
        include_once("../mod_plugins/gestion_autorisations_publications/functions_gestion_autorisations_publications.php");
        echo verifie_autorisation_publication($ele_login,"professeur");
      }

			echo "</div>\n";
		}


		//===================================================

		//===================================================

		//========================
		// Onglet ABSENCES
		//========================

		// $tab_ele['absences']

		if($acces_absences=="y") {
			echo "<div id='absences' class='onglet' style='";
			if($onglet!="absences") {echo " display:none;";}
			echo "background-color: ".$tab_couleur['absences']."; ";
			echo "'>";
			if(getSettingValue("active_module_absence")=='y' || getSettingValue("abs2_import_manuel_bulletin")=='y') {
			    echo "<h2>Absences et retards de l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";

			    if(count($tab_ele['absences'])==0) {
				    echo "<p>Aucun bilan d'absences n'est enregistr�.</p>\n";
			    }
			    else {
				    echo "<table class='boireaus' summary='Bilan des absences'>\n";
				    echo "<tr>\n";
				    echo "<th>P�riode</th>\n";
				    echo "<th>Nombre d'absences</th>\n";
				    echo "<th>Absences non justifi�es</th>\n";
				    echo "<th>Nombre de retards</th>\n";
				    echo "<th>Appr�ciation</th>\n";
				    echo "</tr>\n";
				    $alt=1;
				    for($loop=0;$loop<count($tab_ele['absences']);$loop++) {
					    $alt=$alt*(-1);
					    echo "<tr class='lig$alt'>\n";
					    echo "<td>".$tab_ele['absences'][$loop]['periode']."</td>\n";
					    echo "<td>".$tab_ele['absences'][$loop]['nb_absences']."</td>\n";
					    echo "<td>".$tab_ele['absences'][$loop]['non_justifie']."</td>\n";
					    echo "<td>".$tab_ele['absences'][$loop]['nb_retards']."</td>\n";
					    echo "<td>".$tab_ele['absences'][$loop]['appreciation']."</td>\n";
					    echo "</tr>\n";
				    }
				    echo "</table>\n";
			    }
						    // On ajoute le suivi par cr�neaux si il y en a
			    if ($tab_ele['abs_quotidien']['autorisation'] == 'oui') {
				    // On affiche
				    echo '<br /><p class="bold">Le d�tail des absences enregistr�es : </p>';

				    echo '
				    <table class="boireaus" style="margin-left: 4em;" summary="D&eacute;tail des absences">
					    <tr>
						    <th>R/A</th>
						    <th>Jour</th>
						    <th>Heure</th>
						    <th>Cr�neau</th>
					    </tr>';
				    foreach($tab_ele["abs_quotidien"] as $abs){
					    if (isset($abs["retard_absence"]) AND ($abs["retard_absence"] == 'A' OR $abs["retard_absence"] == 'R')) {
						    $aff_couleur = ' style="background-color: green;"';
						    $aff_abs_lettre = 'R';
						    if ($abs["retard_absence"] == 'A') {
							    $aff_couleur = ' style="background-color: red;"';
							    $aff_abs_lettre = 'A';
						    }
						    echo '
					    <tr>
						    <td' . $aff_couleur . '>' . $aff_abs_lettre . '</td>
						    <td>' . $abs["jour_semaine"] . '</td>
						    <td>' . $abs["debut_heure"] . '</td>
						    <td>' . $abs["creneau"] . '</td>
					    </tr>';
					    }
				    }
				    echo '</table>'."\n";
			    }
			} elseif (getSettingValue("active_module_absence")=='2') {
			    echo "<h2>Absences et retards de l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";
			    // Initialisations files
			    require_once("../lib/initialisationsPropel.inc.php");
			    $eleve = EleveQuery::create()->findOneByLogin($ele_login);

			    echo "<table class='boireaus' summary='Bilan des absences'>\n";
			    echo "<tr>\n";
			    echo "<th>P�riode</th>\n";
			    echo "<th>Nombre d'absences<br/>(1/2 journ�es)</th>\n";
			    echo "<th>Absences non justifi�es</th>\n";
			    echo "<th>Nombre de retards</th>\n";
			    echo "<th>Appr�ciation</th>\n";
			    echo "</tr>\n";
			    $alt=1;
			    foreach($eleve->getPeriodeNotes() as $periode_note) {
				    //$periode_note = new PeriodeNote();
				    if ($periode_note->getDateDebut() == null) {
					//periode non commencee
					continue;
				    }
				    $alt=$alt*(-1);
				    echo "<tr class='lig$alt'>\n";
				    echo "<td>".$periode_note->getNomPeriode();
				    echo " du ".$periode_note->getDateDebut('d/m/Y');
				    echo " au ";
                                    if ($periode_note->getDateFin() == null) {
					echo '(non pr�cis�)';
				    } else {
					echo $periode_note->getDateFin('d/m/Y');
				    }
				    echo "</td>\n";
				    echo "<td>";
				    echo $eleve->getDemiJourneesAbsenceParPeriode($periode_note)->count();
				    echo "</td>\n";
				    echo "<td>";
				    echo $eleve->getDemiJourneesNonJustifieesAbsenceParPeriode($periode_note)->count();
				    echo "</td>\n";
				    echo "<td>";
				    echo $eleve->getRetardsParPeriode($periode_note)->count();
				    echo "</td>\n";
				    echo "<td>"."</td>\n";
				    echo "</tr>\n";
			    }
			    echo "</table>\n";
			}
			echo "</div>\n";
		}


		//===================================================

		//========================
		// Onglet DISCIPLINE
		//========================
		if($acces_discipline=="y") {
			echo "<div id='discipline' class='onglet' style='";
			if($onglet!="discipline") {echo " display:none;";}
			echo "background-color: ".$tab_couleur['discipline']."; ";
			echo "'>";
			echo "<h2>Incidents \"concernant\" l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";

			//=======================
			//Configuration du calendrier
			include("../lib/calendrier/calendrier.class.php");
			$cal1 = new Calendrier("form_date_disc", "date_debut_disc");
			$cal2 = new Calendrier("form_date_disc", "date_fin_disc");
			//=======================


			echo "<form action='".$_SERVER['PHP_SELF']."' name='form_date_disc' method='post' />\n";
			echo "<p>Extraire les incidents entre le ";
			//echo "<input type='text' name='date_debut_disc' value='' />\n";
			echo "<input type='text' name = 'date_debut_disc' id= 'date_debut_disc' size='10' value = \"".$date_debut_disc."\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />\n";
			echo "<a href=\"#\" onClick=\"".$cal1->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";

			echo "et le ";
			//echo "<input type='text' name='date_fin_disc' value='' />\n";
			echo "<input type='text' name = 'date_fin_disc' id= 'date_fin_disc' size='10' value = \"".$date_fin_disc."\" onKeyDown=\"clavier_date(this.id,event);\" AutoComplete=\"off\" />\n";
			echo "<a href=\"#\" onClick=\"".$cal2->get_strPopup('../lib/calendrier/pop.calendrier.php', 350, 170)."\"><img src=\"../lib/calendrier/petit_calendrier.gif\" alt=\"Calendrier\" border=\"0\" /></a>\n";

			echo "<input type='submit' name='restreindre_intervalle_dates' value='Valider' />\n";

			echo "<input type='hidden' name='onglet' value='discipline' />\n";
			echo "<input type='hidden' name='ele_login' value=\"$ele_login\" />\n";
			//echo "<input type='hidden' name='id_classe' value='$id_classe' />\n";

			echo "</p>\n";
			echo "</form>\n";

			if(isset($tab_ele['tab_mod_discipline'])) {
				echo $tab_ele['tab_mod_discipline'];
			}

			echo "</div>\n";
		}
		//===================================================


		//===================================================

		//========================
		// Onglet ANNEES ANTERIEURES
		//========================

		if($acces_anna=="y") {
			echo "<div id='anna' class='onglet' style='";
			if($onglet!="anna") {echo " display:none;";}
			echo "background-color: ".$tab_couleur['anna']."; ";
			echo "'>";
			echo "<h2>Donn�es d'ann�es ant�rieures de l'".$gepiSettings['denomination_eleve']." ".$tab_ele['nom']." ".$tab_ele['prenom']."</h2>\n";

			require("../mod_annees_anterieures/fonctions_annees_anterieures.inc.php");

			//echo $_SERVER['HTTP_USER_AGENT']."<br />\n";
			if(preg_match("/gecko/i",$_SERVER['HTTP_USER_AGENT'])){
				//echo "gecko=true<br />";
				$gecko=true;
			}
			else{
				//echo "gecko=false<br />";
				$gecko=false;
			}



			echo "<p>Liste des ann�es scolaires et p�riodes pour lesquelles des donn�es ont �t� conserv�es pour cet ".$gepiSettings['denomination_eleve']." :</p>\n";

			// R�cup�rer les ann�es-scolaires et p�riodes pour lesquelles on trouve l'INE dans archivage_disciplines
			//$sql="SELECT DISTINCT annee,num_periode,nom_periode FROM archivage_disciplines WHERE ine='$ine' ORDER BY annee DESC, num_periode ASC";
			//$sql="SELECT DISTINCT annee FROM archivage_disciplines WHERE ine='$ine' ORDER BY annee DESC;";
			$sql="SELECT DISTINCT annee FROM archivage_disciplines WHERE ine='".$tab_ele['no_gep']."' ORDER BY annee ASC;";
			$res_ant=mysql_query($sql);

			if(mysql_num_rows($res_ant)==0){
				echo "<p>Aucun r�sultat ant�rieur n'a �t� conserv� pour cet ".$gepiSettings['denomination_eleve'].".</p>\n";
			}
			else{

				unset($tab_annees);

				$nb_annees=mysql_num_rows($res_ant);

				//echo "<p>Bulletins simplifi�s:</p>\n";
				//echo "<table border='0'>\n";
				echo "<table class='boireaus' summary='Bulletins'>\n";
				$alt=1;
				echo "<tr class='lig$alt'>\n";
				echo "<th rowspan='".$nb_annees."' valign='top'>Bulletins simplifi�s:</th>";
				$cpt=0;
				while($lig_ant=mysql_fetch_object($res_ant)){

					$tab_annees[]=$lig_ant->annee;

					if($cpt>0){
						//echo "<tr>\n";
						$alt=$alt*(-1);
						echo "<tr class='lig$alt'>\n";
					}
					echo "<td style='font-weight:bold;'>$lig_ant->annee : </td>\n";

					$sql="SELECT DISTINCT num_periode,nom_periode FROM archivage_disciplines WHERE ine='".$tab_ele['no_gep']."' AND annee='$lig_ant->annee' ORDER BY num_periode ASC";
					$res_ant2=mysql_query($sql);

					if(mysql_num_rows($res_ant2)==0){
						echo "<td>Aucun r�sultat ant�rieur n'a �t� conserv� pour cet ".$gepiSettings['denomination_eleve'].".</td>\n";
					}
					else{

						if(!isset($id_classe)) {
							$id_classe=$tab_ele['classe'][0]['id_classe'];
						}

						$cpt=0;
						while($lig_ant2=mysql_fetch_object($res_ant2)){
							//if($cpt>0){echo "<td> - </td>\n";}

							// $id_classe=$tab_ele['periodes'][$index_per]['id_classe']

							echo "<td style='text-align:center;'><a href='../mod_annees_anterieures/popup_annee_anterieure.php?id_classe=$id_classe&amp;logineleve=".$ele_login."&amp;annee_scolaire=$lig_ant->annee&amp;num_periode=$lig_ant2->num_periode&amp;mode=bull_simp' target='_blank'>$lig_ant2->nom_periode</a></td>\n";
							$cpt++;
						}
					}
					echo "</tr>\n";
					$cpt++;
				}
				echo "</table>\n";

				//echo "<p><br /></p>\n";
				echo "<br />\n";

				//echo "<p>Avis des conseils de classes:<br />\n";
				//echo "<table border='0'>\n";
				echo "<table class='boireaus' summary='Avis des conseils'>\n";
				$alt=1;
				echo "<tr class='lig$alt'>\n";
				echo "<th rowspan='".$nb_annees."' valign='top'>Avis des conseils de classes:</th>";
				$cpt=0;
				for($i=0;$i<count($tab_annees);$i++){
					if($cpt>0){
						//echo "<tr>\n";
						$alt=$alt*(-1);
						echo "<tr class='lig$alt'>\n";
					}
					//echo "<td style='font-weight:bold;'>\n";
					echo "<td>\n";

					echo "Ann�e-scolaire <a href='../mod_annees_anterieures/popup_annee_anterieure.php?logineleve=".$ele_login."&amp;annee_scolaire=".$tab_annees[$i]."&amp;mode=avis_conseil";
					if(isset($id_classe)){echo "&amp;id_classe=$id_classe";}
					echo "' target='_blank'>$tab_annees[$i]</a>";
					//echo "<br />\n";

					echo "</td>\n";
					echo "</tr>\n";
					$cpt++;
				}
				echo "</table>\n";
				//echo "</p>\n";
			}

			echo "</div>\n";
		}
		//===================================================

		//=====================================================================================

		//========================
		// Bricolages Javascript
		//========================

		// Liste des onglets de niveau 1
		//$tab_onglets=array('eleve','responsables','enseignements','releves','bulletins','cdt','anna','absences');
		$tab_onglets=array('eleve','responsables','enseignements','releves','bulletins','cdt','fp','anna','absences','discipline');
		$chaine_tab_onglets="tab_onglets=new Array(";
		for($i=0;$i<count($tab_onglets);$i++) {
			if($i>0) {$chaine_tab_onglets.=", ";}
			$chaine_tab_onglets.="'".$tab_onglets[$i]."'";
		}
		$chaine_tab_onglets.=");";

		// Liste des onglets dans l'onglet bulletins
		$chaine_tab_onglets_bull="tab_onglets=new Array(";
		for($i=0;$i<count($tab_onglets_bull);$i++) {
			if($i>0) {$chaine_tab_onglets_bull.=", ";}
			$chaine_tab_onglets_bull.="'".$tab_onglets_bull[$i]."'";
		}
		$chaine_tab_onglets_bull.=");";

		// Liste des onglets dans l'onglet relev�s de notes
		$chaine_tab_onglets_rel="tab_onglets=new Array(";
		for($i=0;$i<count($tab_onglets_rel);$i++) {
			if($i>0) {$chaine_tab_onglets_rel.=", ";}
			$chaine_tab_onglets_rel.="'".$tab_onglets_rel[$i]."'";
		}
		$chaine_tab_onglets_rel.=");";


		echo "<script type='text/javascript'>
	function affiche_onglet(id) {
		$chaine_tab_onglets

		for(i=0;i<=tab_onglets.length;i++) {
			if(document.getElementById(tab_onglets[i])) {
				document.getElementById(tab_onglets[i]).style.display='none';
			}
			if(document.getElementById('t_'+tab_onglets[i])) {
				document.getElementById('t_'+tab_onglets[i]).style.borderBottomColor='black';
				document.getElementById('t_'+tab_onglets[i]).style.borderBottomWidth='1px';
			}
		}

		if(document.getElementById(id)) {
			document.getElementById(id).style.display='';
		}
		if(document.getElementById('t_'+id)) {
			document.getElementById('t_'+id).style.borderBottomColor='white';

			document.getElementById('t_'+id).style.borderBottomWidth='0px';
		}

		document.getElementById('onglet_courant').value=id;
	}

	function affiche_onglet_bull(id) {
		$chaine_tab_onglets_bull

		for(i=0;i<tab_onglets.length;i++) {
			if(document.getElementById(tab_onglets[i])) {
				document.getElementById(tab_onglets[i]).style.display='none';
			}
			if(document.getElementById('t_'+tab_onglets[i])) {
				document.getElementById('t_'+tab_onglets[i]).style.borderBottomColor='black';
				document.getElementById('t_'+tab_onglets[i]).style.borderBottomWidth='1px';
			}
		}

		if(document.getElementById(id)) {
			document.getElementById(id).style.display='';
		}
		if(document.getElementById('t_'+id)) {
			document.getElementById('t_'+id).style.borderBottomColor='white';
			document.getElementById('t_'+id).style.borderBottomWidth='0px';
		}
	}

	function affiche_onglet_rel(id) {
		$chaine_tab_onglets_rel

		for(i=0;i<tab_onglets.length;i++) {
			if(document.getElementById(tab_onglets[i])) {
				document.getElementById(tab_onglets[i]).style.display='none';
			}
			if(document.getElementById('t_'+tab_onglets[i])) {
				document.getElementById('t_'+tab_onglets[i]).style.borderBottomColor='black';
				document.getElementById('t_'+tab_onglets[i]).style.borderBottomWidth='1px';
			}
		}

		if(document.getElementById(id)) {
			document.getElementById(id).style.display='';
		}
		if(document.getElementById('t_'+id)) {
			document.getElementById('t_'+id).style.borderBottomColor='white';
			document.getElementById('t_'+id).style.borderBottomWidth='0px';
		}
	}
</script>\n";

		/*
		echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
		echo "<input type='hidden' name='onglet_courant' id='onglet_courant' value='";
		if(isset($onglet)) {echo $onglet;}
		echo "' />\n";
		echo "</form>\n";
		*/
		echo "<p><br /></p>\n";
	}
}
?>
