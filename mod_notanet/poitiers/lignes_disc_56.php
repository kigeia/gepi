<?php
	echo "<tr>\n";

	echo "<td rowspan='3' colspan='4' width='".$fb_largeur_col_disc."%' style='font-size:".$fb_titretab."pt;'>\n";
	echo "DISCIPLINES";
	echo "</td>\n";

	echo "<td colspan='2' style='border: 1px solid black; text-align:center; font-size:".$fb_titretab."pt;'>\n";
	echo "NOTE MOYENNE<br />affect�e du coefficient";
	echo "</td>\n";

	echo "<td rowspan='3' valign='bottom' width='".$fb_largeur_col_note."%' style='line-height: ".$fb_txttab_lineheight."pt;'>\n";
	echo "Note<br />moyenne<br />de la<br />classe";
	//echo "Note<br />moyenne<br />de la<br />classe<br />0 � 20";
	echo "</td>\n";

	echo "<td rowspan='3' style='line-height: ".$fb_txttab_lineheight."pt; font-size:".$fb_titretab."pt;' width='".$fb_largeur_col_app."%'>\n";
	echo "Appr�ciations des professeurs";
	echo "</td>\n";

	echo "</tr>\n";
	//=====================
	echo "<tr>\n";

	// La colonne discipline est dans le rowspan de la ligne pr�c�dente.

	//echo "<td style='border: 1px solid black; text-align:center;'>\n";
	echo "<td colspan='2' style='font-weight:bold; font-size:".$fb_titretab."pt;'>\n";
	//echo "3�me � option";
	echo "3�me";
	echo "</td>\n";

	// Les colonnes note moyenne de la classe et appr�ciations des profs sont dans le rowspan de la ligne pr�c�dente.

	echo "</tr>\n";
	//=====================
	echo "<tr>\n";

	// La colonne discipline est dans le rowspan de la ligne pr�c�dente.

	//echo "<td style='border: 1px solid black; text-align:center;'>\n";
	echo "<td width='".$fb_largeur_col_opt."%' style='font-size:".$fb_titretab."pt;'>\n";
	//echo "LV2";
	//echo $fb_intitule_col[1];
	echo $tabmatieres["fb_intitule_col"][1];
	echo "</td>\n";

	echo "<td width='".$fb_largeur_col_opt."%' style='line-height: ".$fb_txttab_lineheight."pt; font-size:".$fb_titretab."pt;'>\n";
	//echo "A module d�couverte professionnelle<br />6 heures";
	//echo $fb_intitule_col[2];
	echo $tabmatieres["fb_intitule_col"][2];
	echo "</td>\n";

	// Les colonnes note moyenne de la classe et appr�ciations des profs sont dans le rowspan de la ligne pr�c�dente.

	echo "</tr>\n";

	//=====================

	$TOTAL=0;
	$SUR_TOTAL=array();
	$SUR_TOTAL[1]=0;
	$SUR_TOTAL[2]=0;
	$temoin_NOTNONCA=0;
	for($j=$indice_premiere_matiere;$j<=$indice_max_matieres;$j++){
		$temoin_note_non_numerique="n";
		//if($tabmatieres[$j][0]!=''){
		//if($tabmatieres[$j][0]!=''){
		if(($tabmatieres[$j][0]!='')&&($tabmatieres[$j]['socle']=='n')){
			//if($tabmatieres[$j][-1]!='NOTNONCA'){
			if(($tabmatieres[$j][-1]!='NOTNONCA')&&($tabmatieres[$j][-4]!='non dispensee dans l etablissement')){

				//$tabmatieres[$j]['fb_col'][1]
				//$SUR_TOTAL+=$tabmatieres[$j][-2]*20;
				//echo "<tr><td>".$tabmatieres[$j]['fb_col'][1]."</td></tr>";
				//if(ctype_digit($tabmatieres[$j]['fb_col'][1])){$SUR_TOTAL[1]+=$tabmatieres[$j]['fb_col'][1];}
				//if(ctype_digit($tabmatieres[$j]['fb_col'][2])){$SUR_TOTAL[2]+=$tabmatieres[$j]['fb_col'][2];}

				/*
				if(strlen(my_ereg_replace("[0-9]","",$tabmatieres[$j]['fb_col'][1]))==0){$SUR_TOTAL[1]+=$tabmatieres[$j]['fb_col'][1];}
				if(strlen(my_ereg_replace("[0-9]","",$tabmatieres[$j]['fb_col'][2]))==0){$SUR_TOTAL[2]+=$tabmatieres[$j]['fb_col'][2];}
				*/

				// ************************************
				// A REVOIR
				// PROBLEME AVEC CES TOTAUX: SI UN ELEVE EST AB, DI ou NN, IL NE FAUDRAIT PAS AUGMENTER???...
				if((strlen(my_ereg_replace("[0-9]","",$tabmatieres[$j]['fb_col'][1]))==0)&&($tabmatieres[$j][-1]!='PTSUP')){
					$SUR_TOTAL[1]+=$tabmatieres[$j]['fb_col'][1];
				}
				if((strlen(my_ereg_replace("[0-9]","",$tabmatieres[$j]['fb_col'][2]))==0)&&($tabmatieres[$j][-1]!='PTSUP')){
					$SUR_TOTAL[2]+=$tabmatieres[$j]['fb_col'][2];
				}
				// ************************************

				//echo "<tr><td>$SUR_TOTAL[1]</td></tr>";



				// Initialisation
				$lignes_opt_facultative_alternative="";



				echo "<tr>\n";

				// Discipline
				//echo "<td style='border: 1px solid black; text-align:left;'>\n";
				// Les trois colonnes du colspan='3' servent � couvrir les d�coupages de colonnes sur les lignes B2i,...
				echo "<td colspan='4' style='font-size:".$fb_textetab."pt;'";
				//if($tabmatieres[$j][-1]=='PTSUP'){
				//	echo " rowspan='2'";
				//}
				echo ">\n";
				//echo "<p class='discipline'>";
				//echo "<span class='discipline'>";

				if(!isset($tabmatieres[$j]["lig_speciale"])) {

					/*
					// Il n'y a pas d'option facultative en s�rie PROFESSIONNELLE
					// ET la langue vivante correspond � une lig_speciale

					//if($tabmatieres[$j][0]=="OPTION FACULTATIVE (1)"){echo ": ".$tabmatieres[$j][-5];}
					//if($tabmatieres[$j][0]=="OPTION FACULTATIVE (1)"){
					if($tabmatieres[$j][0]=="OPTION FACULTATIVE") {
						echo "<p class='discipline' style='font-style:italic; font-weight:bold;'>Option facultative</p>\n";
						echo "</td>\n";
						echo "<td colspan='2' style='font-size:".$fb_textetab."pt;'>\n";
						echo "Points au-dessus de 10";
						echo "</td>\n";
						// Colonne Moy classe
						echo "<td>&nbsp;</td>\n";
						// Colonne App
						echo "<td>&nbsp;</td>\n";
						echo "</tr>\n";


						$lignes_opt_facultative_alternative="<tr>\n";
						$lignes_opt_facultative_alternative.="<td colspan='4' style='font-size:".$fb_textetab."pt;'><p class='discipline'>\n";
						if($type_brevet==0) {
							$lignes_opt_facultative_alternative.="Latin ou grec ou langue vivante 2\n";
							$lignes_opt_facultative_alternative.="</p></td>\n";
							$lignes_opt_facultative_alternative.="<td style='background-color:lightgrey;'>&nbsp;</td>\n";
							$lignes_opt_facultative_alternative.="<td>&nbsp;</td>\n";
						}
						elseif($type_brevet==1) {
							$lignes_opt_facultative_alternative.="Latin ou grec<br />ou d�couverte professionnelle 3 h\n";
							$lignes_opt_facultative_alternative.="</p></td>\n";
							$lignes_opt_facultative_alternative.="<td>&nbsp;</td>\n";
							$lignes_opt_facultative_alternative.="<td style='background-color:lightgrey;'>&nbsp;</td>\n";
						}
						$lignes_opt_facultative_alternative.="<td>&nbsp;</td>\n";
						$lignes_opt_facultative_alternative.="<td>&nbsp;</td>\n";
						$lignes_opt_facultative_alternative.="</tr>\n";

						if($type_brevet==1) {
							// Pour le brevet COLLEGE DP6, on ins�re la ligne alternative correspondant au brevet COLLEGE s�rie LV2
							echo $lignes_opt_facultative_alternative;
						}


						echo "<tr>\n";
						echo "<td colspan='4' style='font-size:".$fb_textetab."pt;'>\n";
						echo "<p class='discipline'>";
						//echo ucfirst(strtolower($tabmatieres[$j][0]));

						$nom_opt="";
						if($type_brevet==0) {
							//echo "Latin, grec ou DP3";
							$nom_opt="Latin, grec ou DP3";
						}
						elseif($type_brevet==1) {
							//echo "Latin, grec ou LV2";
							$nom_opt="Latin, grec ou LV2";
						}
						echo "$nom_opt";

						// ==============================
						// recherche de la mati�re facultative pour l'�l�ve
						//$sql_mat_fac="SELECT mat FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND matiere='".$tabmatieres[$j][0]."'";
						$sql_mat_fac="SELECT m.nom_complet AS matiere FROM notanet n, matieres m WHERE n.login='$lig1->login' AND n.id_classe='$id_classe[$i]' AND n.notanet_mat='".$tabmatieres[$j][0]."' AND m.matiere=n.matiere";
						$res_mat_fac=mysql_query($sql_mat_fac);
						if(mysql_num_rows($res_mat_fac)>0){
							$lig_mat_fac=mysql_fetch_object($res_mat_fac);

							//echo ": ".$lig_mat_fac->mat;
							//echo ": ".ucfirst(strtolower($lig_mat_fac->matiere));

							echo " : ";

							echo ucfirst(strtolower($lig_mat_fac->matiere));
						}
						// ==============================

						//echo ": ".$lig_mat_fac->mat;
					}
					elseif(substr(ucfirst(strtolower($tabmatieres[$j][0])),0,14)=='Langue vivante') {
						echo "<p class='discipline'>";
						echo ucfirst(strtolower($tabmatieres[$j][0]))." : ";

						// ==============================
						// recherche de la langue vivante pour l'�l�ve
						//$sql_mat_fac="SELECT mat FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND matiere='".$tabmatieres[$j][0]."'";
						$sql_mat_lv="SELECT m.nom_complet AS matiere FROM notanet n, matieres m WHERE n.login='$lig1->login' AND n.id_classe='$id_classe[$i]' AND n.notanet_mat='".$tabmatieres[$j][0]."' AND m.matiere=n.matiere";
						$res_mat_lv=mysql_query($sql_mat_lv);
						if(mysql_num_rows($res_mat_lv)>0){
							$lig_mat_lv=mysql_fetch_object($res_mat_lv);

							echo ucfirst(strtolower($lig_mat_lv->matiere));
						}
					}
					else {
					*/
						echo "<p class='discipline'>";
						echo ucfirst(accent_min(strtolower($tabmatieres[$j][0])));
					//}
				}
				else{
					echo "<p class='discipline'>";
					echo ucfirst(accent_min(strtolower($tabmatieres[$j]["lig_speciale"])));
				}
				//echo "</span>\n";
				echo "</p>\n";
				echo "</td>\n";



				// EXTRACTION POUR LA(ES) COLONNE(S) "NOTE MOYENNE affect�e du coefficient"
				$valeur_tmp="&nbsp;";
				//$sql="SELECT note FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND matiere='".$tabmatieres[$j][0]."'";
				$sql="SELECT note FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND notanet_mat='".$tabmatieres[$j][0]."'";
				$res_note=mysql_query($sql);
				if(mysql_num_rows($res_note)){
					$lig_note=mysql_fetch_object($res_note);
					//echo "$lig_note->note";
					//$valeur_tmp="$lig_note->note";
					//$valeur_tmp=$lig_note->note*$tabmatieres[$j][-2];
					if(($lig_note->note!='AB')&&($lig_note->note!='DI')&&($lig_note->note!='NN')){
						$valeur_tmp=$lig_note->note*$tabmatieres[$j][-2];
						/*
						if($tabmatieres[$j][-1]=='PTSUP'){
							$TOTAL+=max(0,$lig_note->note-10);
						}
						else{
						*/
						// Le cas PTSUP est calcul� plus loin
						if($tabmatieres[$j][-1]!='PTSUP'){
							//$TOTAL+=$lig_note->note;
							$TOTAL+=$valeur_tmp;
						}
						else {
							$ptsup=$lig_note->note-10;
							if($ptsup>0){
								//echo "$ptsup";
								$valeur_tmp=$ptsup;
								//if(($lig_note->note!='AB')&&($lig_note->note!='DI')&&($lig_note->note!='NN')){
									$TOTAL+=$ptsup;
								//}
							}
							else{
								//echo "0";
								$valeur_tmp=0;
							}
						}
					}
					else{
						$valeur_tmp=$lig_note->note;
						$temoin_note_non_numerique="y";

						if(($tabmatieres[$j][-1]!='PTSUP')){
							if($num_fb_col==1){
								$SUR_TOTAL[1]-=$tabmatieres[$j]['fb_col'][1];
							}
							else{
								$SUR_TOTAL[2]-=$tabmatieres[$j]['fb_col'][2];
							}
						}
					}
					//$note="$lig_note->note";
				}
				else{
					// FAUT-IL UN TEMOIN POUR DECREMENTER LE SUR_TOTAL ?
					if(($tabmatieres[$j][-1]!='PTSUP')){
						if($num_fb_col==1){
							$SUR_TOTAL[1]-=$tabmatieres[$j]['fb_col'][1];
						}
						else{
							$SUR_TOTAL[2]-=$tabmatieres[$j]['fb_col'][2];
						}
					}
				}


				/*
				if($tabmatieres[$j][-1]=='PTSUP'){
					$valeur_tmp=max($valeur_tmp-10,0);
				}
				*/



				// COLONNE s�rie LV2
				echo "<td ";
				//echo "style='border: 1px solid black; text-align:right;'>\n";
				/*
				if($tabmatieres[$j][-1]=='PTSUP'){
					echo "style='border: 1px solid black; text-align:center;'>\n";
					echo "<b>Points > � 10</b>";
				}
				else{
				*/
					/*
					$sql="SELECT note FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND matiere='".$tabmatieres[$j][0]."'";
					$res_note=mysql_query($sql);
					if(mysql_num_rows($res_note)){
						$lig_note=mysql_fetch_object($res_note);
						echo "$lig_note->note";
						if(($lig_note->note!='AB')&&($lig_note->note!='DI')&&($lig_note->note!='NN')){
							$TOTAL+=$lig_note->note;
						}
						//$note="$lig_note->note";
					}
					else{
						echo "&nbsp;";
						//$note="&nbsp;";
					}
					*/
					echo "style='border: 1px solid black; font-size:".$fb_textetab."pt;";

					/*
					if(($tabmatieres[$j][0]=="OPTION FACULTATIVE")&&($nom_opt=='Latin, grec ou LV2')) {
						echo " background-color:lightgrey;";
					}
					*/

					if($num_fb_col==1){
						echo " text-align:center;";
						echo "'>\n";
						echo "$valeur_tmp";
					}
					else{
						echo " text-align:right;";
						//if (strtolower($tabmatieres[$j][0])=='d�couverte professionnelle 6 heures') {echo " background-color:lightgrey;";}
						if($tabmatieres[$j]['fb_col'][1]=="X") {
							echo " background-color:lightgrey;";
						}
						echo "'>\n";
						echo "&nbsp;";
					}

					//$nb=$tabmatieres[$j][-2]*20;
					//echo " / $nb";
					//if($tabmatieres[$j][-1]!='PTSUP'){
					if(($tabmatieres[$j][-1]!='PTSUP')&&(strtolower($tabmatieres[$j][0])!='d�couverte professionnelle 6 heures')) {
						if($tabmatieres[$j]['fb_col'][1]!="X"){
							if(($temoin_note_non_numerique=="n")||($num_fb_col==2)) {
								echo " / ".$tabmatieres[$j]['fb_col'][1];
							}
						}
					}

					// DEBUG:
					// echo "<br />$TOTAL/$SUR_TOTAL[1]";


				//}
				echo "</td>\n";


				// COLONNE s�rie DP6
				//echo "style='border: 1px solid black; text-align:center;'>\n";
				echo "<td ";
				/*
				if($tabmatieres[$j][-1]=='PTSUP'){
					echo "style='border: 1px solid black; text-align:center;'>\n";
					echo "<b>Points > � 10</b>";
				}
				else{
				*/
					/*
					echo "style='border: 1px solid black; text-align:right;'>\n";
					// TRICHE... Mon dispositif ne permet pas de g�rer correctement ce double affichage
					// Il faudrait /40 pour la 2� LV ou d�couverte professionnelle 6H.
					if($tabmatieres[$j][0]=='DEUXIEME LANGUE VIVANTE'){
						echo " / 40";
					}
					else{
						echo " / 20";
					}
					*/
					//echo "style='border: 1px solid black; text-align:right;'>\n";
					echo "style='border: 1px solid black; font-size:".$fb_textetab."pt;";

					if(strtolower($tabmatieres[$j][0])=='langue vivante 2') {
						echo "background-color:lightgrey;";
					}

					if($num_fb_col==2){
						echo " text-align:center;";
						echo "'>\n";
						echo "$valeur_tmp";
					}
					else{
						echo " text-align:right;";
						/*
						if($tabmatieres[$j][0]=="OPTION FACULTATIVE"){
							echo " background-color:lightgrey;";
						}
						*/
						echo "'>\n";
						echo "&nbsp;";
					}

					//$nb=$tabmatieres[$j][-2]*20;
					//echo " / $nb";
					//if($tabmatieres[$j][-1]!='PTSUP'){
					if(($tabmatieres[$j][-1]!='PTSUP')&&(strtolower($tabmatieres[$j][0])!='langue vivante 2')) {
						if(($temoin_note_non_numerique=="n")||($num_fb_col==1)) {
							echo " / ".$tabmatieres[$j]['fb_col'][2];
						}
					}
				//}
				echo "</td>\n";


				// Moyenne classe
				echo "<td ";
				/*
				if($tabmatieres[$j][-1]=='PTSUP'){
					echo "rowspan='2' ";
				}
				*/
				echo "style='border: 1px solid black; text-align:center; font-size:".$fb_textetab."pt;'>\n";
				if($fb_mode_moyenne==1){
					echo $moy_classe[$j];
				}
				else{
					//$sql="SELECT mat FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND matiere='".$tabmatieres[$j][0]."'";
					$sql="SELECT matiere FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND notanet_mat='".$tabmatieres[$j][0]."'";
					$res_mat=mysql_query($sql);
					if(mysql_num_rows($res_mat)>0){
						$lig_mat=mysql_fetch_object($res_mat);
						//echo "$lig_mat->mat: ";

						//$sql="SELECT ROUND(AVG(note),1) moyenne_mat FROM notanet WHERE id_classe='$id_classe[$i]' AND mat='".$lig_mat->mat."'";
						$sql="SELECT ROUND(AVG(note),1) moyenne_mat FROM notanet WHERE id_classe='$id_classe[$i]' AND matiere='".$lig_mat->matiere."' AND note!='AB' AND note!='DI' AND note!='NN';";
						//echo "$sql<br />";
						$res_moy=mysql_query($sql);
						if(mysql_num_rows($res_moy)>0){
							$lig_moy=mysql_fetch_object($res_moy);
							echo "$lig_moy->moyenne_mat";
						}
					}
				}
				echo "</td>\n";




				// Appr�ciation
				echo "<td ";
				//echo "style='border: 1px solid black; text-align:center;'>\n";
				echo "style='border: 1px solid black; text-align:left; font-size:".$fb_textetab."pt;'>\n";

				if($avec_app=="y") {
					$sql="SELECT appreciation FROM notanet_app na,
													notanet_corresp nc
												WHERE na.login='$lig1->login' AND
													nc.notanet_mat='".$tabmatieres[$j][0]."' AND
													nc.matiere=na.matiere;";
					//echo "$sql<br />";
					$res_app=mysql_query($sql);
					if(mysql_num_rows($res_app)>0){
						$lig_app=mysql_fetch_object($res_app);
						echo "$lig_app->appreciation";
					}
					else{
						echo "&nbsp;";
					}
				}
				else {
					echo "&nbsp;";
				}

				echo "</td>\n";

				echo "</tr>\n";
			}
			else{
				//if($tabmatieres[$j][-4]!='non dispensee dans l etablissement'){
				if($tabmatieres[$j][-1]=="NOTNONCA"){
					$temoin_NOTNONCA++;
					//echo "<!-- \$temoin_NOTNONCA=$temoin_NOTNONCA \n\$tabmatieres[$j][0]=".$tabmatieres[$j][0]."-->\n";
				}
				//}
			}
			// ...=====...($tabmatieres[$j][-1]!='NOTNONCA')&&($tabmatieres[$j][-4]!='non dispensee dans l etablissement')
		}
		else{

			// Cas de la s�rie professionnelle sans option de s�rie:
			// Il faut quand m�me ajouter l'option alternative pour la s�rie professionnelle avec option DP6h
			if(isset($tabmatieres[$j]['fb_lig_alt'])) {
				echo "<tr>\n";

				echo "<td colspan='4' style='font-size:".$fb_textetab."pt;'>\n";
				echo "<p class='discipline'>".$tabmatieres[$j]['fb_lig_alt']."</p>\n";
				echo "</td>\n";

				echo "<td style='border: 1px solid black; text-align:left; background-color:lightgrey;'>&nbsp;</td>\n";
				echo "<td style='border: 1px solid black; text-align:right; font-size:".$fb_textetab."pt;'>/".$tabmatieres[$j]['fb_col'][2]."</td>\n";
				echo "<td style='border: 1px solid black; text-align:left;'>&nbsp;</td>\n";
				echo "<td style='border: 1px solid black; text-align:left;'>&nbsp;</td>\n";

				echo "</tr>\n";
			}

			// Fin du isset($tabmatieres[$j]["lig_speciale"])
		}
	}
	// FIN DE ...
	//=====================



	//=====================
	/*
	echo "<tr>\n";

	echo "<td style='border: 1px solid black; text-align:left; font-weight:bold;'>\n";
	echo "<p class='discipline'>";
	echo "Socle B2i";
	echo "</p>";
	echo "</td>\n";

	echo "<td colspan='3' style='border: 1px solid black; text-align:center; font-weight:bold;'>\n";
	echo "&nbsp;";
	echo "</td>\n";

	echo "<td style='border: 1px solid black; text-align:left;'>\n";
	echo "&nbsp;";
	echo "</td>\n";

	echo "<td style='border: 1px solid black; text-align:left;'>\n";
	echo "&nbsp;";
	echo "</td>\n";

	echo "</tr>\n";
	//=====================
	echo "<tr>\n";

	echo "<td style='border: 1px solid black; text-align:left; font-weight:bold;'>\n";
	echo "<p class='discipline'>";
	echo "Socle Niveau A2 de langue";
	echo "</p>";
	echo "</td>\n";

	echo "<td colspan='3' style='border: 1px solid black; text-align:center; font-weight:bold;'>\n";
	echo "&nbsp;";
	echo "</td>\n";

	echo "<td style='border: 1px solid black; text-align:left;'>\n";
	echo "&nbsp;";
	echo "</td>\n";

	echo "<td style='border: 1px solid black; text-align:left;'>\n";
	echo "&nbsp;";
	echo "</td>\n";

	echo "</tr>\n";
	*/
	//=====================


	//=====================

	if($temoin_NOTNONCA>0){
		// ON TRAITE LES MATIERES NOTNONCA
		echo "<tr>\n";

		echo "<td colspan='4' style='border: 1px solid black; text-align:left; font-style:italic; font-weight:bold; font-size:".$fb_textetab."pt;'>\n";
		echo "A titre indicatif";
		echo "</td>\n";

		//echo "<td colspan='2' style='font-weight:bold; font-size:".$fb_titretab."pt; line-height: ".$fb_tittab_lineheight."pt;'>\n";
		echo "<td colspan='2' style='font-weight:bold; font-size:".$fb_titretab."pt; line-height: ".$fb_tittab_lineheight."pt;'>\n";
		echo "TOTAL DES POINTS";
		echo "</td>\n";

		/*
		echo "<td style='font-weight:bold; font-size:".$fb_titretab."pt; line-height: ".$fb_tittab_lineheight."pt;'>\n";
		echo "TOTAL DES POINTS";
		echo "</td>\n";
		*/


		$nb_info_bis=$temoin_NOTNONCA+1;
		echo "<td rowspan='$nb_info_bis' style='border: 1px solid black; text-align:center; font-size:".$fb_textetab."pt;'>\n";
		echo "&nbsp;";
		echo "</td>\n";


		// Colonne Appr�ciations
		echo "<td style='border: 1px solid black; text-align:center;'>\n";
		echo "&nbsp;";
		echo "</td>\n";

		echo "</tr>\n";

		$num_lig=0;
		// On repasse en revue toutes les mati�res en ne retenant que celles qui sont NOTNONCA
		for($j=$indice_premiere_matiere;$j<=$indice_max_matieres;$j++){
			//if($tabmatieres[$j][0]!=''){
			//if(($tabmatieres[$j][0]!='')&&($tabmatieres[$j][-1]=='NOTNONCA')){
			if(($tabmatieres[$j][0]!='')&&($tabmatieres[$j][-1]=='NOTNONCA')&&($tabmatieres[$j]['socle']=='n')){
				if($tabmatieres[$j][-4]!='non dispensee dans l etablissement'){
					echo "<tr>\n";

					echo "<td colspan='4' style='border: 1px solid black; text-align:left; font-size:".$fb_textetab."pt;'>\n";
					echo "<p class='discipline'>";
					echo ucfirst(accent_min(strtolower($tabmatieres[$j][0])));
					//echo "</p>";
					//echo "</td>\n";
					echo " : ";

					/*
					// Moyenne de la classe
					echo "<td style='border: 1px solid black; text-align:center;'>\n";
					//echo $moy_classe[$j];
					if($fb_mode_moyenne==1){
						echo $moy_classe[$j];
					}
					else{
						//$sql="SELECT mat FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND matiere='".$tabmatieres[$j][0]."'";
						$sql="SELECT matiere FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND notanet_mat='".$tabmatieres[$j][0]."'";
						$res_mat=mysql_query($sql);
						if(mysql_num_rows($res_mat)>0){
							$lig_mat=mysql_fetch_object($res_mat);
							//echo "$lig_mat->mat: ";

							//$sql="SELECT ROUND(AVG(note),1) moyenne_mat FROM notanet WHERE id_classe='$id_classe[$i]' AND mat='".$lig_mat->mat."'";
							//$sql="SELECT ROUND(AVG(note),1) moyenne_mat FROM notanet WHERE id_classe='$id_classe[$i]' AND matiere='".$lig_mat->matiere."';";
							$sql="SELECT ROUND(AVG(note),1) moyenne_mat FROM notanet WHERE id_classe='$id_classe[$i]' AND matiere='".$lig_mat->matiere."' AND note!='AB' AND note!='DI' AND note!='NN';";
							//echo "$sql<br />";
							$res_moy=mysql_query($sql);
							if(mysql_num_rows($res_moy)>0){
								$lig_moy=mysql_fetch_object($res_moy);
								echo "$lig_moy->moyenne_mat";
							}
						}
					}
					echo "</td>\n";
					*/

					// Moyenne de l'�l�ve
					//echo "<td style='border: 1px solid black; text-align:center;'>\n";
					//$sql="SELECT note FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND matiere='".$tabmatieres[$j][0]."'";
					$sql="SELECT note FROM notanet WHERE login='$lig1->login' AND id_classe='$id_classe[$i]' AND notanet_mat='".$tabmatieres[$j][0]."'";
					$res_note=mysql_query($sql);
					if(mysql_num_rows($res_note)){
						$lig_note=mysql_fetch_object($res_note);
						echo $lig_note->note."/20";
					}
					else{
						echo "&nbsp;";
					}
					//echo "</td>\n";

					// Appr�ciation
					//echo "<td style='border: 1px solid black; text-align:center;'>\n";
					//echo "&nbsp;";
					//echo "</td>\n";

					// Colonne total des lignes calcul�es (non NOTNONCA)...
					if($num_lig==0){
						$nb_info=$temoin_NOTNONCA;


						//echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:right;'>\n";
						if($num_fb_col==1){
							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:center; font-size:".$fb_textetab."pt;'>\n";
							//echo "$TOTAL / 220";
							echo "$TOTAL";
							echo " / ".$SUR_TOTAL[1];
							echo "</td>\n";

							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:right; font-size:".$fb_textetab."pt;'>\n";
							//echo "/ 240";
							echo " / ".$SUR_TOTAL[2];
							echo "</td>\n";
						}
						else{
							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:right; font-size:".$fb_textetab."pt;'>\n";
							//echo "/ 220";
							echo " / ".$SUR_TOTAL[1];
							echo "</td>\n";

							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:center; font-size:".$fb_textetab."pt;'>\n";
							//echo "$TOTAL / 220";
							echo "$TOTAL";
							echo " / ".$SUR_TOTAL[2];
							echo "</td>\n";
						}
						//echo "</td>\n";

						//$num_lig++;
					}

					/*
					// Colonne Note moyenne de la classe
					if($num_lig==0) {
						$nb_info_bis=$nb_info+1;
						echo "<td rowspan='$nb_info_bis' style='border: 1px solid black; text-align:center;'>\n";
						echo "&nbsp;";
						echo "</td>\n";
					}
					*/

					// Appr�ciation
					echo "<td ";
					//style='border: 1px solid black; text-align:center;'>\n";
					//echo "&nbsp;";
					echo "style='border: 1px solid black; text-align:left; font-size:".$fb_textetab."pt;'>\n";

					if($avec_app=="y") {
						$sql="SELECT appreciation FROM notanet_app na,
														notanet_corresp nc
													WHERE na.login='$lig1->login' AND
														nc.notanet_mat='".$tabmatieres[$j][0]."' AND
														nc.matiere=na.matiere;";
						//echo "$sql<br />";
						$res_app=mysql_query($sql);
						if(mysql_num_rows($res_app)>0){
							$lig_app=mysql_fetch_object($res_app);
							echo "$lig_app->appreciation";
						}
						else{
							echo "&nbsp;";
						}
					}
					else {
						echo "&nbsp;";
					}
					echo "</td>\n";


					$num_lig++;

					/*
					echo "<td style='border: 1px solid black; text-align:right;'>\n";
					echo "/20";
					echo "</td>\n";
					*/

					echo "</tr>\n";
				}
				else{
					// Mati�re 'non dispensee dans l etablissement'
					// On affiche seulement les intitul�s et le total des bar�mes...
					echo "<tr>\n";

					echo "<td colsapn='4' style='border: 1px solid black; text-align:left; font-size:".$fb_textetab."pt;'>\n";
					echo "<p class='discipline'>";
					echo ucfirst(accent_min(strtolower($tabmatieres[$j][0])));
					echo "</p>";
					echo "</td>\n";

					/*
					echo "<td style='border: 1px solid black; text-align:center;'>\n";
					echo "&nbsp;";
					echo "</td>\n";

					echo "<td style='border: 1px solid black; text-align:center;'>\n";
					echo "&nbsp;";
					echo "</td>\n";

					echo "<td style='border: 1px solid black; text-align:center;'>\n";
					echo "&nbsp;";
					echo "</td>\n";
					*/

					if($num_lig==0){
						$nb_info=$temoin_NOTNONCA;

						/*
						//echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:right;'>\n";
						if($num_fb_col==1){
							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:center;'>\n";
							echo "$TOTAL / 220";
							echo "</td>\n";

							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:right;'>\n";
							echo "/ 240";
							echo "</td>\n";
						}
						else{
							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:right;'>\n";
							echo "/ 220";
							echo "</td>\n";

							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:center;'>\n";
							echo "$TOTAL / 240";
							echo "</td>\n";
						}
						*/
						//echo "</td>\n";
						if($num_fb_col==1){
							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:center;' font-size:".$fb_textetab."pt;>\n";
							//echo "$TOTAL / 220";
							echo "$TOTAL";
							echo " / ".$SUR_TOTAL[1];
							echo "</td>\n";

							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:right;' font-size:".$fb_textetab."pt;>\n";
							//echo "/ 240";
							echo " / ".$SUR_TOTAL[2];
							echo "</td>\n";
						}
						else{
							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:right; font-size:".$fb_textetab."pt;'>\n";
							//echo "/ 220";
							echo " / ".$SUR_TOTAL[1];
							echo "</td>\n";

							echo "<td rowspan='$nb_info' style='border: 1px solid black; text-align:center; font-size:".$fb_textetab."pt;'>\n";
							//echo "$TOTAL / 220";
							echo "$TOTAL";
							echo " / ".$SUR_TOTAL[2];
							echo "</td>\n";
						}

						//$num_lig++;
					}

					// Appr�ciation
					echo "<td style='border: 1px solid black; text-align:center;'>\n";
					echo "&nbsp;";
					echo "</td>\n";

					$num_lig++;

					echo "</tr>\n";
				}
			}
		}
		// FIN DE LA BOUCLE SUR LA LISTE DES MATIERES
	}
	// FIN DU TRAITEMENT DES MATIERES NOTNONCA
?>
