<?php
/*
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

// On indique qu'il faut creer des variables non protégées (voir fonction cree_variables_non_protegees())
$variables_non_protegees = 'yes';

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


if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

// Intialisation
unset($indice_aid);
$indice_aid = isset($_POST["indice_aid"]) ? $_POST["indice_aid"] : (isset($_GET["indice_aid"]) ? $_GET["indice_aid"] : NULL);
unset($aid_id);
$aid_id = isset($_POST["aid_id"]) ? $_POST["aid_id"] : (isset($_GET["aid_id"]) ? $_GET["aid_id"] : NULL);


// On appelle les informations de l'aid pour les afficher :
$call_data = mysql_query("SELECT * FROM aid_config WHERE indice_aid = '$indice_aid'");
$nom_aid = @mysql_result($call_data, 0, "nom");
$note_max = @mysql_result($call_data, 0, "note_max");
$type_note = @mysql_result($call_data, 0, "type_note");
$display_begin = @mysql_result($call_data, 0, "display_begin");
$display_end = @mysql_result($call_data, 0, "display_end");



//===========================
// Couleurs utilisées
$couleur_devoirs = '#AAE6AA';
$couleur_fond = '#AAE6AA';
$couleur_moy_cn = '#96C8F0';
//===========================



$nom_table = "class_temp".SESSION_ID();

if (isset($_POST['is_posted'])) {
	check_token();

	$indice_max_log_eleve=$_POST['indice_max_log_eleve'];
	//echo "\$indice_max_log_eleve=$indice_max_log_eleve<br />";

	$sql="SELECT e.* FROM eleves e, j_aid_eleves j WHERE (j.id_aid='$aid_id' and e.login = j.login and j.indice_aid='$indice_aid')";
	//echo "$sql<br />";
	$quels_eleves=mysql_query($sql);
	$lignes = mysql_num_rows($quels_eleves);
	//echo "\$lignes=$lignes (nombre d'élèves inscrits dans l'AID)<br />";
	$j = '0';
	while($j < $lignes) {
		$reg_eleve_login = mysql_result($quels_eleves, $j, "login");

		//echo "<hr /><p>Elève $reg_eleve_login<br />";

		//echo "\$reg_eleve_login=$reg_eleve_login<br />";
		//$call_classe = mysql_query("SELECT DISTINCT id_classe FROM j_eleves_classes WHERE login = '$reg_eleve_login' ORDER BY periode DESC");
		$sql="SELECT DISTINCT id_classe FROM j_eleves_classes WHERE login = '$reg_eleve_login' ORDER BY periode DESC";
		//echo "$sql<br />";
		$call_classe = mysql_query($sql);
		//echo "$sql<br />";
		// On passe en revue tous les élèves inscrits à l'AID, même si ils ne sont pas dans une classe...
		// ... par contre, dans la partie saisie, seuls les élèves effectivement dans une classe sont proposés.
		if(mysql_num_rows($call_classe)>0){
			$id_classe = mysql_result($call_classe, '0', "id_classe");
			$sql="SELECT * FROM periodes WHERE id_classe = '$id_classe'  ORDER BY num_periode";
			//echo "$sql<br />";
			$periode_query = mysql_query($sql);
			$nb_periode = mysql_num_rows($periode_query) ;
			if ($type_note == 'last') {$last_periode_aid = min($nb_periode,$display_end);}
			$k='1';
			while ($k < $nb_periode + 1) {
				//echo "<p>Période $k<br />";
				if (($k >= $display_begin) and ($k <= $display_end)) {
					$ver_periode[$k] = mysql_result($periode_query, $k-1, "verouiller");
					//if ($ver_periode[$k] == "N"){
					if ((($_SESSION['statut']=='secours')&&($ver_periode[$k] != "O"))||
						(($_SESSION['statut']!='secours')&&($ver_periode[$k] == "N"))) {
						//echo "La période n'est pas fermée en saisie.<br />";
						//=========================
						// AJOUT: boireaus 20071003
						unset($log_eleve);
						$log_eleve=$_POST['log_eleve_'.$k];
						unset($note_eleve);
						// On n'a pas nécessairement de note
						// cf: if (($type_note=='every') or (($type_note=='last') and ($k == $last_periode_aid))) {
						if(isset($_POST['note_eleve_'.$k])) {
							$note_eleve=$_POST['note_eleve_'.$k];
						}
						//=========================

						//echo "\$log_eleve=$log_eleve et \$note_eleve=$note_eleve<br />";

						//=========================
						// AJOUT: boireaus 20071003
						// Récupération du numéro de l'élève dans les saisies:
						$num_eleve=-1;
						//for($i=0;$i<count($log_eleve);$i++){
						for($i=0;$i<$indice_max_log_eleve;$i++){
							if(isset($log_eleve[$i])){
								if(my_strtolower("$reg_eleve_login"."_t".$k)==my_strtolower("$log_eleve[$i]")){
									$num_eleve=$i;
									break;
								}
							}
						}
						//echo "\$num_eleve=$num_eleve<br />";
						if($num_eleve!=-1){
							//echo "L'élève a été trouvé dans le tableau \$log_eleve soumis.<br />";
							//=========================
							// MODIF: boireaus 20071003
							//$nom_log = $reg_eleve_login."_t".$k;
							$nom_log = "app_eleve_".$k."_".$num_eleve;
							//=========================

							//$nom_log2 = $reg_eleve_login."_n_t".$k;

							if (isset($NON_PROTECT[$nom_log])){
								$app = traitement_magic_quotes(corriger_caracteres($NON_PROTECT[$nom_log]));
							}
							else{
								$app = "";
							}

							//echo "\$app=$app<br />";

							$elev_statut = '';
							//=========================
							if(isset($note_eleve[$num_eleve])) {
								// cf: if (($type_note=='every') or (($type_note=='last') and ($k == $last_periode_aid))) {
								$note=$note_eleve[$num_eleve];

								if (($note == 'disp')) {
									$note = '0';
									$elev_statut = 'disp';
								}
								else if (($note == '-')) {
									$note = '0';
									$elev_statut = '-';
								}
								else if (($note == 'abs')) {
									$note = '0';
									$elev_statut = 'abs';
								} else if (preg_match ("/^[0-9\.\,]{1,}$/", $note)) {
									$note = str_replace(",", ".", "$note");
									if (($note < 0) or ($note > $note_max)) {
										$note = '';
										$elev_statut = '';
									}
								}
								else {
									$note = '';
									$elev_statut = 'other';
								}
							}
							//=========================

							//echo "\$note=$note et \$elev_statut=$elev_statut<br />";

							$sql="SELECT * FROM aid_appreciations WHERE (login='$reg_eleve_login' AND periode='$k' and id_aid = '$aid_id' and indice_aid='$indice_aid');";
							//echo "$sql<br />";
							$test_eleve_app_query = mysql_query($sql);
							$test = mysql_num_rows($test_eleve_app_query);
							if ($test != "0") {
								//echo "Il y avait déjà un enregistrement.<br />";
								if (($type_note=='every') or (($type_note=='last') and ($k == $last_periode_aid))) {
									$sql="UPDATE aid_appreciations SET appreciation='$app', note='$note',statut='$elev_statut' WHERE (login='$reg_eleve_login' AND periode='$k' and id_aid = '$aid_id' and indice_aid='$indice_aid');";
									//echo "$sql<br />";
									$register=mysql_query($sql);
								} else {
									$sql="UPDATE aid_appreciations SET appreciation='$app' WHERE (login='$reg_eleve_login' AND periode='$k' and id_aid = '$aid_id' and indice_aid='$indice_aid');";
									//echo "$sql<br />";
									$register=mysql_query($sql);
								}
							} else {
								//echo "Il n'y avait pas encore d'enregistrement.<br />";
								if (($type_note=='every') or (($type_note=='last') and ($k == $last_periode_aid))) {
									$sql="INSERT INTO aid_appreciations SET login='$reg_eleve_login',id_aid='$aid_id',periode='$k',appreciation='$app', note = '$note', statut='$elev_statut', indice_aid='$indice_aid';";
									//echo "$sql<br />";
									$register=mysql_query($sql);
								} else {
									$sql="INSERT INTO aid_appreciations SET login='$reg_eleve_login',id_aid='$aid_id',periode='$k',appreciation='$app',statut='$elev_statut', indice_aid='$indice_aid';";
									//echo "$sql<br />";
									$register=mysql_query($sql);
								}
							}
							if (!$register) {$msg = "Erreur lors de l'enregistrement des données de la période $k ";} else {$msg = "Les modifications ont été enregistrées !";$affiche_message = 'yes';}
						}
					}
				}
				$k++;
			}
		}
		$j++;
	}
}
//
// on calcule le nombre maximum de périodes dans une classe
//

$call_data = mysql_query("DROP TABLE IF EXISTS $nom_table");
$call_data = mysql_query("CREATE TEMPORARY TABLE $nom_table (id_classe integer, num integer NOT NULL)");
$msg_pb="";
if(!$call_data) {
	$msg_pb="ERREUR&nbsp;: La création d'une table temporaire a échoué.<br />Le droit de créer des tables temporaires n'est peut-être pas attribué à l'utilisateur MySQL.<br />La présente page risque de ne pas fonctionner.";
}
$call_data = mysql_query("SELECT * FROM classes");
$nombre_lignes = mysql_num_rows($call_data);
$i = 0;
while ($i < $nombre_lignes){
	$id_classe = mysql_result($call_data, $i, "id");
	$periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '$id_classe' ORDER BY num_periode");
	$k = mysql_num_rows($periode_query);
	$call_reg = mysql_query("insert into $nom_table Values('$id_classe', '$k')");
	$i++;
}
$call_data = mysql_query("SELECT max(num) as max FROM $nom_table");
$nb_periode_max = mysql_result($call_data, 0, "max");

$message_enregistrement = "Les modifications ont été enregistrées !";
$themessage  = 'Des notes ou des appréciations ont été modifiées. Voulez-vous vraiment quitter sans enregistrer ?';
//**************** EN-TETE *****************
$titre_page = "Saisie des appréciations ".$nom_aid;
require_once("../lib/header.inc.php");
//**************** FIN EN-TETE *****************
//debug_var();
?>
<script type="text/javascript" language="javascript">
change = 'no';
</script>
<p class=bold><a href="../accueil.php" onclick="return confirm_abandon (this, change, '<?php echo $themessage; ?>')"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour accueil</a>
<?php

if (!isset($aid_id)) {
	?></p><?php
	if ($_SESSION['statut'] != "secours") {
		$sql="SELECT a.nom, a.id, a.numero FROM j_aid_utilisateurs j, aid a WHERE (j.id_utilisateur = '" . $_SESSION['login'] . "' and a.id = j.id_aid and a.indice_aid=j.indice_aid and j.indice_aid='$indice_aid') ORDER BY a.numero, a.nom";
		//echo "$sql<br />";
		$call_prof_aid = mysql_query($sql);
		$nombre_aid = mysql_num_rows($call_prof_aid);
		if ($nombre_aid == "0") {
			echo "<p>$nom_aid : Vous n'êtes pas professeur responsable. Vous n'avez donc pas à entrer d'appréciations.</p></html></body>\n";
			die();
		} else {
			$i = "0";
			echo "<p>Vous êtes professeur responsable dans les $nom_aid :<br />\n";
			while ($i < $nombre_aid) {
				$aid_display = mysql_result($call_prof_aid, $i, "nom");
				$aid_id = mysql_result($call_prof_aid, $i, "id");
				$aid_numero = mysql_result($call_prof_aid, $i, "numero")." : ";
				if ($aid_numero == " : ") {$aff_numero_aid = "";} else {$aff_numero_aid = $aid_numero;}
				echo "<br /><span class='bold'>".$aff_numero_aid.$aid_display."</span>
				 --- <a href='saisie_aid.php?aid_id=".$aid_id."&amp;indice_aid=".$indice_aid."'>Saisir les appréciations pour cette rubrique</a>\n";
				$i++;
			}
			echo "</p>\n";
		}
	} else {
		$call_prof_aid = mysql_query("SELECT * FROM aid WHERE indice_aid='$indice_aid' ORDER BY numero, nom");
		$nombre_aid = mysql_num_rows($call_prof_aid);
		if ($nombre_aid == "0") {
			echo "<p>$nom_aid : Il n'y a pas d'entrées !</p>\n";
		} else {
			$i = "0";
			echo "<p><b>".$nom_aid." - Saisie des appréciations :</b><br />\n";
			while ($i < $nombre_aid) {
				$aid_display = mysql_result($call_prof_aid, $i, "nom");
				$aid_id = mysql_result($call_prof_aid, $i, "id");
				$aid_numero = mysql_result($call_prof_aid, $i, "numero")." : ";
				if ($aid_numero == " : ") {$aff_numero_aid = "";} else {$aff_numero_aid = $aid_numero;}
				echo "<br /><span class='bold'>".$aff_numero_aid.$aid_display."</span> --- <a href='saisie_aid.php?aid_id=$aid_id&amp;indice_aid=$indice_aid'>Saisir les appréciations.</a>\n";
				$i++;
			}
			echo "</p>\n";
		}
	}
} else {

	echo " | <a href='saisie_aid.php?indice_aid=$indice_aid' onclick=\"return confirm_abandon (this, change, '$themessage')\">Choix $nom_aid</a></p>\n";

	if($msg_pb!='') {
		echo "<p style='color:red'>$msg_pb</p>\n";
	}

	echo "<form enctype='multipart/form-data' action='saisie_aid.php' method='post'>\n";
	echo "<center><input type='submit' value='Enregistrer' /></center>\n";

	$calldata = mysql_query("SELECT nom FROM aid where (id = '$aid_id'  and indice_aid='$indice_aid')");
	$aid_nom = mysql_result($calldata, 0, "nom");


	echo "<p class='grand'>Appréciations $nom_aid : $aid_nom</p>\n";
	echo "<table class='boireaus' border=1 cellspacing=2 cellpadding=5>\n";

	$indice_max_log_eleve=0;
	$num_id=10;
	$num = '1';
	// Initialisation de $num3 pour le cas où il n'y a pas de période ouverte:
	$num3=0;
	while ($num < $nb_periode_max + 1) {
		if ($type_note == 'last') {
			$last_periode_aid = min($num,$display_end);
		}
		$appel_login_eleves = mysql_query("SELECT DISTINCT a.login
									FROM j_eleves_classes cc, j_aid_eleves a, $nom_table c, eleves e
									WHERE (a.id_aid='$aid_id' AND
									cc.login = a.login AND
									a.login = e.login AND
									cc.id_classe = c.id_classe AND
									c.num = $num AND
									a.indice_aid='$indice_aid') ORDER BY e.nom, e.prenom");
		$nombre_lignes = mysql_num_rows($appel_login_eleves);
		if ($nombre_lignes != '0') {
			echo "<tr>\n";
			echo "<th><b>Nom Prénom</b></th>\n";

			$call_data = mysql_query("SELECT * FROM $nom_table WHERE num = '$num' ");
			$id_classe = mysql_result($call_data, '0', 'id_classe');
			$periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '$id_classe'  ORDER BY num_periode");

			$i = "1";
			while ($i < $num + 1) {
				$nom_periode[$i] = mysql_result($periode_query, $i-1, "nom_periode");
				echo "<th><b>$nom_periode[$i]</b></th>\n";
				$i++;
			}
			while ($i < $nb_periode_max + 1) {
				echo "<th>X</th>\n";
				$i++;
			}
			echo "</tr>\n";

			$i = "0";
			$alt=1;
			while($i < $nombre_lignes) {
				$current_eleve_login = mysql_result($appel_login_eleves, $i, 'login');
				$appel_donnees_eleves = mysql_query("SELECT * FROM eleves WHERE (login = '$current_eleve_login')");
				$current_eleve_nom = mysql_result($appel_donnees_eleves, '0', "nom");
				$current_eleve_prenom = mysql_result($appel_donnees_eleves, '0', "prenom");
				$appel_classe_eleve = mysql_query("SELECT DISTINCT c.* FROM classes c, j_eleves_classes cc WHERE (cc.login = '$current_eleve_login' AND cc.id_classe = c.id) ORDER BY cc.periode DESC");
				$current_eleve_classe = mysql_result($appel_classe_eleve, '0', "classe");
				$current_eleve_id_classe = mysql_result($appel_classe_eleve, '0', "id");

				$alt=$alt*(-1);
				echo "<tr class='lig$alt'>\n";
				echo "<td>$current_eleve_nom $current_eleve_prenom $current_eleve_classe</td>\n";
				$k = '1';

				$periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '$current_eleve_id_classe'  ORDER BY num_periode");

				while ($k < $num + 1) {
					if (($k >= $display_begin) and ($k <= $display_end)) {

						$current_eleve_app_query = mysql_query("SELECT * FROM aid_appreciations WHERE (login='$current_eleve_login' AND periode='$k' AND id_aid = '$aid_id' and indice_aid='$indice_aid')");
						$current_eleve_statut_t[$k] = @mysql_result($current_eleve_app_query, 0, "statut");
						$current_eleve_app_t[$k] = @mysql_result($current_eleve_app_query, 0, "appreciation");
						$current_eleve_note_t[$k] = @mysql_result($current_eleve_app_query, 0, "note");
						$current_eleve_login_t[$k] = $current_eleve_login."_t".$k;
						$current_eleve_login_n_t[$k] = $current_eleve_login."_n_t".$k;

						$ver_periode[$k] = mysql_result($periode_query, $k-1, "verouiller");
						//if ($ver_periode[$k] != "N") {
						if ((($_SESSION['statut']=='secours')&&($ver_periode[$k] == "O"))||
							(($_SESSION['statut']!='secours')&&($ver_periode[$k] != "N"))) {
							echo "<td><b>";
							if ($current_eleve_app_t[$k] != '') {
								echo "$current_eleve_app_t[$k]";
							} else {
								echo "-";
							}
							if ((($type_note=='every') or (($type_note=='last') and ($k == $last_periode_aid))) and ($current_eleve_note_t[$k] !='')) {
								echo "<br />Note (sur $note_max) : ";
								if ($current_eleve_statut_t[$k] == 'other') {
									echo "&nbsp;";
								} else if ($current_eleve_statut_t[$k] != '') {
									echo "$current_eleve_statut_t[$k]";
								} else {
									echo "$current_eleve_note_t[$k]";
								}
							}
							echo "</b></td>\n";
						} else {
							//echo "<td><textarea id=\"n".$k.$num_id."\" onKeyDown=\"clavier(this.id,event);\" name=\"no_anti_inject_".$current_eleve_login_t[$k]."\" rows=4 cols=60 wrap='virtual' onchange=\"changement()\">";
							//echo "<td>\n<textarea id=\"n1".$k.$num_id."\" onKeyDown=\"clavier(this.id,event);\" name=\"no_anti_inject_".$current_eleve_login_t[$k]."\" rows=4 cols=60 wrap='virtual' onchange=\"changement()\">";

							$num2=2*$num_id;
							$num3=$num2+1;
							//echo "<td>\n";
							//echo "<td id=\"td_".$k.$num3."\" bgcolor=\"$couleur_fond\">\n";

							echo "<td id=\"td_".$k.$num3."\">\n";

							//=========================
							// MODIF: boireaus 20071003
							//echo "<textarea id=\"n".$k.$num2."\" onKeyDown=\"clavier(this.id,event);\" name=\"no_anti_inject_".$current_eleve_login_t[$k]."\" rows=4 cols=60 wrap='virtual' onchange=\"changement()\">";

							echo "<input type='hidden' name='log_eleve_".$k."[$i]' value=\"".$current_eleve_login_t[$k]."\" />\n";

							echo "<textarea id=\"n".$k.$num2."\" onKeyDown=\"clavier(this.id,event);\" name=\"no_anti_inject_app_eleve_".$k."_".$i."\" rows=4 cols=60 wrap='virtual' onchange=\"changement()\">";
							//=========================

							echo "$current_eleve_app_t[$k]";
							echo "</textarea>\n";
							if (($type_note=='every') or (($type_note=='last') and ($k == $last_periode_aid))) {
								echo "<br />Note (sur $note_max) : ";
								//echo "<input id=\"n".$k.$num_id."\" onKeyDown=\"clavier(this.id,event);\" type=text size = '4' name=$current_eleve_login_n_t[$k] value=";
								//echo "<input id=\"n2".$k.$num_id."\" onKeyDown=\"clavier(this.id,event);\" type=text size = '4' name=$current_eleve_login_n_t[$k] value=";
								//$num2++;
								//=========================
								// MODIF: boireaus 20071003
								//echo "<input id=\"n".$k.$num3."\" onKeyDown=\"clavier(this.id,event);\" type=text size = '4' name=$current_eleve_login_n_t[$k] value=";
								echo "<input id=\"n".$k.$num3."\" onKeyDown=\"clavier(this.id,event);\" type=text size = '4' name=\"note_eleve_".$k."[$i]\" value=";
								//=========================
								if ($current_eleve_statut_t[$k] == 'other') {
									echo "\"\"";
								} else if ($current_eleve_statut_t[$k] != '') {
									echo "\"".$current_eleve_statut_t[$k]."\"";
								} else {
									echo "\"".$current_eleve_note_t[$k]."\"";
								}
								//echo " onchange=\"changement()\" /></td>\n";
								echo " onfocus=\"javascript:this.select()\" onchange=\"verifcol(".$k.$num3.");changement()\" />\n";
							}
							echo "</td>\n";
						}
					} else {
						echo "<td>-</td>\n";
					}
					$k++;
				}

				while ($k < $nb_periode_max + 1) {
					echo "<td>X</td>\n";
					$k++;
				}

				echo "</tr>\n";

				$i++;
				$num_id++;

				$indice_max_log_eleve++;
			}
		}
		$num++;
	}
	?>
	</table>

	<table>
	<tr><td>
	<?php
		//echo "<input type='hidden' name='indice_max_log_eleve' value='$i' />\n";
		echo "<input type='hidden' name='indice_max_log_eleve' value='$indice_max_log_eleve' />\n";

		echo add_token_field();
	?>
	<input type=hidden name=is_posted value="yes" />
	<input type=hidden name=aid_id value="<?php echo "$aid_id";?>" />
	<input type=hidden name=indice_aid value="<?php echo "$indice_aid";?>" />
	<center><div id="fixe"><input type=submit value=Enregistrer /></div></center>
	</td></tr>
	</table>
	</form>
	<?php


//=============================================================
// MODIF: boireaus
echo "
<script type='text/javascript' language='JavaScript'>

function verifcol(num_id){
	document.getElementById('n'+num_id).value=document.getElementById('n'+num_id).value.toLowerCase();
	if(document.getElementById('n'+num_id).value=='a'){
		document.getElementById('n'+num_id).value='abs';
	}
	if(document.getElementById('n'+num_id).value=='d'){
		document.getElementById('n'+num_id).value='disp';
	}
	if(document.getElementById('n'+num_id).value=='n'){
		document.getElementById('n'+num_id).value='-';
	}
	note=document.getElementById('n'+num_id).value;

	if((note!='-')&&(note!='disp')&&(note!='abs')&&(note!='')){
		if((note.search(/^[0-9.]+$/)!=-1)&&(note.lastIndexOf('.')==note.indexOf('.',0))){
		if((note>20)||(note<0)){
			couleur='red';
		}
		else{
			couleur='$couleur_devoirs';
		}
		}
		else{
		couleur='red';
		}
	}
	else{
		couleur='$couleur_devoirs';
	}
	eval('document.getElementById(\'td_'+num_id+'\').style.background=couleur');
}

for(i=10;i<".$k.$num3.";i++){
	if(i/2-Math.round(i/2)!=0){
		if(document.getElementById('n'+i)){
			if(document.getElementById('n'+i).value!=''){
				eval(\"verifcol(\"+i+\")\");
			}
		}
	}
}

</script>
";
//=============================================================


}
require("../lib/footer.inc.php");
?>
