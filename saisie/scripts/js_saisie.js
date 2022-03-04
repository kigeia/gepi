/**
 * Fonction qui récupère l'appréciation d'un textarea précis pour le sauvegarder
 **/
function ajaxAppreciations(eleveperiode, enseignement, textId){
	var csrf_alea=document.getElementById('csrf_alea').value;

	var essai = $(textId);
	// On récupère le contenu du textarea dont l'id est textId
	var contenu = $F(textId);
	// On définit le nom du fichier qui va traiter la requête
	var url = "ajax_appreciations.php";
	o_options = new Object();
	o_options = {postBody: 'var1='+eleveperiode+'&var2='+enseignement+'&var3='+contenu+'&csrf_alea='+csrf_alea};

	// On construit la requête ajax
	//var laRequete = new Ajax.Request(url,o_options);
	// Il faudra envisager d'utiliser Ajax.Updater pour renvoyer une phrase de confirmation
	//  ou alors résupérer un retour par Ajax.Request avec onSuccess ou onFailure
	//alert(enseignement+' \n'+eleveperiode+' \n'+textId+' \n Essai = ' +essai+' \nContenu = '+contenu);
	new Ajax.Updater($('div_verif_'+textId),url,o_options);
}

function ajaxVerifAppreciations(eleveperiode, enseignement, textId){
	var csrf_alea=document.getElementById('csrf_alea').value;


	var essai = $(textId);
	// On récupère le contenu du textarea dont l'id est textId
	var contenu = $F(textId);
	// On définit le nom du fichier qui va traiter la requête
	var url = "../saisie/ajax_appreciations.php";
	o_options = new Object();
	o_options = {postBody: 'mode=verif&var1='+eleveperiode+'&var2='+enseignement+'&var3='+contenu+'&csrf_alea='+csrf_alea};
	// On construit la requête ajax
	//var laRequete = new Ajax.Request(url,o_options);
	new Ajax.Updater($('div_verif_'+textId),url,o_options);


	// Il faudra envisager d'utiliser Ajax.Updater pour renvoyer une phrase de confirmation
	//  ou alors résupérer un retour par Ajax.Request avec onSuccess ou onFailure
	//alert(enseignement+' \n'+eleveperiode+' \n'+textId+' \n Essai = ' +essai+' \nContenu = '+contenu);

}


function ajaxVerifAvis(eleveperiode, id_classe, textId){
	var csrf_alea=document.getElementById('csrf_alea').value;

	//alert('plop');

	var essai = $(textId);
	// On récupère le contenu du textarea dont l'id est textId
	var contenu = $F(textId);
	//alert(contenu);

	// On définit le nom du fichier qui va traiter la requête
	var url = "../saisie/ajax_appreciations.php";
	o_options = new Object();
	o_options = {postBody: 'mode=verif_avis&var1='+eleveperiode+'&var2='+id_classe+'&var3='+contenu+'&csrf_alea='+csrf_alea};
	// On construit la requête ajax
	//var laRequete = new Ajax.Request(url,o_options);
	new Ajax.Updater($('div_verif_'+textId),url,o_options);


	// Il faudra envisager d'utiliser Ajax.Updater pour renvoyer une phrase de confirmation
	//  ou alors résupérer un retour par Ajax.Request avec onSuccess ou onFailure
	//alert(enseignement+' \n'+eleveperiode+' \n'+textId+' \n Essai = ' +essai+' \nContenu = '+contenu);

}


