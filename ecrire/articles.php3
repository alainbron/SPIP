<?php

include ("inc.php3");
include_local ("inc_logos.php3");
include_local ("inc_index.php3");
include_local ("inc_mots.php3");


$articles_surtitre = lire_meta("articles_surtitre");
$articles_soustitre = lire_meta("articles_soustitre");
$articles_descriptif = lire_meta("articles_descriptif");
$articles_chapeau = lire_meta("articles_chapeau");
$articles_ps = lire_meta("articles_ps");
$articles_redac = lire_meta("articles_redac");
$articles_mots = lire_meta("articles_mots");
$forums_publics = lire_meta("forums_publics");

$requete_fichier = "articles.php3?id_article=$id_article";



//////////////////////////////////////////////////////
// Determiner les droits d'edition
//

$query = "SELECT statut, titre, id_rubrique FROM spip_articles WHERE id_article=$id_article";
$result = mysql_query($query);
if ($row = mysql_fetch_array($result)) {
	$statut_article = $row[0];
	$titre_article = $row[1];
	$rubrique_article = $row[2];
}
else {
	$statut_article = '';
}

$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
$result_auteur = mysql_query($query);

$flag_auteur = (mysql_num_rows($result_auteur) > 0);
$flag_editable = (acces_rubrique($rubrique_article)
	OR ($flag_auteur AND ($statut_article == 'prepa' OR $statut_article == 'prop' OR !$rubrique_article)));


//////////////////////////////////////////////////////
// Appliquer les modifications
//

$suivi_edito = lire_meta("suivi_edito");

if ($statut_nouv) {
	$ok = false;
	if (acces_rubrique($rubrique_article)) $ok = true;
	else if ($flag_auteur) {
		if ($statut_nouv == 'prop' AND $statut_article == 'prepa')
			$ok = true;
		else if ($statut_nouv == 'prepa' AND !$rubrique_article)
			$ok = true;
	}
	if ($ok) {
		$query = "UPDATE spip_articles SET statut='$statut_nouv' WHERE id_article=$id_article";
		$result = mysql_query($query);
		calculer_rubriques();

		if ($statut_nouv == 'publie' AND $statut_nouv != $statut_article) {
			$query = "UPDATE spip_articles SET date=NOW() WHERE id_article=$id_article";
			$result = mysql_query($query);
			if (lire_meta('activer_moteur') == 'oui') {
				indexer_article($id_article);
			}
		}
		if ($statut_nouv == 'publie' AND $statut_article != $statut_nouv) {
			envoyer_mail_publication($id_article);
		}
	
		if ($statut_nouv == "prop" AND $statut_article != $statut_nouv AND $statut_article != 'publie') {
			envoyer_mail_proposition($id_article);
		}
		$statut_article = $statut_nouv;
		$flag_editable = (acces_rubrique($rubrique_article)
			OR ($flag_auteur AND ($statut_article == 'prepa' OR $statut_article == 'prop')));
	}
}


if ($jour && $flag_editable) {
	if ($annee == "0000") $mois = "00";
	if ($mois == "00") $jour = "00";
	$query = "UPDATE spip_articles SET date='$annee-$mois-$jour' WHERE id_article=$id_article";
	$result = mysql_query($query);
}


if ($jour_redac && $flag_editable) {
	if ($annee_redac < 1001) $annee_redac += 9000;

	if ($mois_redac == "00") $jour_redac = "00";

	
	if ($avec_redac=="non"){
		$annee_redac = '0000';
		$mois_redac = '00';
		$jour_redac = '00';
	}

	$query = "UPDATE spip_articles SET date_redac='$annee_redac-$mois_redac-$jour_redac' WHERE id_article=$id_article";
	$result = mysql_query($query);
}

// reunit les textes decoupes parce que trop longs
$nb_texte = 0;
while ($nb_texte ++ < 100){		// 100 pour eviter une improbable boucle infinie
	$varname = "texte$nb_texte";
	$texte_plus = $$varname;	// double $ pour obtenir $texte1, $texte2...
	if ($texte_plus){
		$texte_plus = ereg_replace("<!--SPIP-->[\n\r]*","\n\n\n",$texte_plus);
		$texte_ajout .= $texte_plus;
	} else {
		break;
	}
}
$texte = $texte_ajout . $texte;

if ($titre && !$ajout_forum && $flag_editable) {
	$surtitre = addslashes(corriger_caracteres($surtitre));
	$titre = addslashes(corriger_caracteres($titre));
	$soustitre = addslashes(corriger_caracteres($soustitre));
	$descriptif = addslashes(corriger_caracteres($descriptif));
	$chapo = addslashes(corriger_caracteres($chapo));
	$texte = addslashes(corriger_caracteres($texte));
	$ps = addslashes(corriger_caracteres($ps));

	// Verifier qu'on envoie bien dans une rubrique autorisee
	if ($flag_auteur OR acces_rubrique($id_rubrique)) {
		$change_rubrique = "id_rubrique=\"$id_rubrique\",";
	} else {
		$change_rubrique = "";
	}

	$query = "UPDATE spip_articles SET surtitre=\"$surtitre\", titre=\"$titre\", soustitre=\"$soustitre\", $change_rubrique descriptif=\"$descriptif\", chapo=\"$chapo\", texte=\"$texte\", ps=\"$ps\" WHERE id_article=$id_article";
	$result = mysql_query($query);
	calculer_rubriques();
	if ($statut_article == 'publie') {
		if (lire_meta('activer_moteur') == 'oui') {
			indexer_article($id_article);
		}
	}
	
	
	// Passer les documents en inclus=non
	mysql_query("UPDATE spip_documents SET inclus='non' WHERE id_article='$id_article'");
	

	// afficher le nouveau titre dans la barre de fenetre
	$titre_article = stripslashes($titre);
}



//////////////////////////////////////////////////////
// Affichage de la colonne de gauche
//

debut_page("&laquo; $titre_article &raquo;");
debut_gauche();

debut_boite_info();

echo "<CENTER>";

if ($statut_article == "publie") {
	$post_dates = lire_meta("post_dates");
	
	if ($post_dates == "non") {
		$query = "SELECT * FROM spip_articles WHERE id_article=$id_article AND date<=NOW()";
		$result = mysql_query($query);
		if (mysql_num_rows($result) > 0) {
			echo "<A HREF='../spip_redirect.php3?id_article=$id_article&recalcul=oui'><img src='IMG2/voirenligne.gif' alt='voir en ligne' width='48' height='48' border='0' align='right'></A>";
		}
	}
	else {
		echo "<A HREF='../spip_redirect.php3?id_article=$id_article&recalcul=oui'><img src='IMG2/voirenligne.gif' alt='voir en ligne' width='48' height='48' border='0' align='right'></A>";
	}
}

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>ARTICLE NUM&Eacute;RO&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$id_article</B></FONT>";
echo "</CENTER>";

fin_boite_info();


//////////////////////////////////////////////////////
// Logos de l'article
//

$arton = "arton$id_article";
$artoff = "artoff$id_article";
$arton_ok = get_image($arton);
if ($arton_ok) $artoff_ok = get_image($artoff);

if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article) AND ($options == 'avancees' OR $arton_ok)) {

	debut_boite_info();

	afficher_boite_logo($arton, "LOGO DE L'ARTICLE".aide ("logoart"));
	if (($options == 'avancees' AND $arton_ok) OR $artoff_ok) {
		echo "<P>";
		afficher_boite_logo($artoff, "LOGO POUR SURVOL");
	}

	fin_boite_info();
}



//////////////////////////////////////////////////////
// Suivi forums publics
//

if ($forums_publics != 'non' AND acces_rubrique($rubrique_article) AND $connect_statut == '0minirezo' AND $options == 'avancees' AND $statut_article == 'publie') {
	debut_boite_info();
	echo "<CENTER><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#333333'><B>";
	echo "<A HREF='articles_forum.php3?id_article=$id_article'>G&eacute;rer le forum public</A>";
	echo "</B></FONT></CENTER>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
	echo "</font>";
	fin_boite_info();
}


//////////////////////////////////////////////////////
// Accepter forums...
//

$forums_publics = lire_meta("forums_publics");

if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article) AND $options == 'avancees' AND $forums_publics != "non") {

	if ($change_accepter_forum){
		$query_pet="UPDATE spip_articles SET accepter_forum='$change_accepter_forum' WHERE id_article='$id_article'";	
		$result_pet=mysql_query($query_pet);
		
	}

	$query = "SELECT * FROM spip_articles WHERE id_article='$id_article'";
	$result = mysql_query($query);

	if ($row = mysql_fetch_array($result)) {
		$accepter_forum=$row["accepter_forum"];
	}


	debut_boite_info();
	echo "<CENTER><TABLE WIDTH=100% CELLPADDING=2 BORDER=1 CLASS='hauteur'><TR><TD WIDTH=100% ALIGN='center' BGCOLOR='#FFCC66'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#333333'><B>";
	echo bouton_block_invisible("forumarticle");
	echo "FORUM POUR CET ARTICLE";
	echo "</B></FONT></TD></TR></TABLE></CENTER>";
	echo debut_block_invisible("forumarticle");
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
	echo "\n<FORM ACTION='articles.php3' METHOD='get'>";
	echo "\n<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";
	if ($accepter_forum!="non"){
		echo "<P><input type='radio' name='change_accepter_forum' value='oui' id='accepterforum' checked>";
		echo "<B><label for='accepterforum'>Article avec forum (fonctionnement normal)</label></B>";
		echo "<P><input type='radio' name='change_accepter_forum' value='non' id='refuserforum'>";
		echo "<label for='refuserforum'>Ne pas afficher de forum pour cet article.</label>";
	}else{
		echo "<P><input type='radio' name='change_accepter_forum' value='oui' id='accepterforum'>";
		echo "<label for='accepterforum'>Article avec forum (fonctionnement normal)</label>";
		echo "<P><input type='radio' name='change_accepter_forum' value='non' id='refuserforum' checked>";
		echo "<B><label for='refuserforum'>Ne pas afficher de forum pour cet article.</label></B>";
	}
	echo "<P align='right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'>";
	echo "</FORM>";
	echo fin_block();
	fin_boite_info();

}


//////////////////////////////////////////////////////
// Petitions
//

if ($petition) {
	if ($petition == "on") {
		if (!$email_unique) $email_unique="non";
		if (!$site_obli) $site_obli="non";
		if (!$site_unique) $site_unique="non";
		if (!$message) $message="non";
		
		$texte_petition = addslashes($texte_petition);
		
		$query_pet = "UPDATE spip_petitions SET email_unique='$email_unique', site_obli='$site_obli', site_unique='$site_unique', message='$message', texte=\"$texte_petition\" WHERE id_article='$id_article'";
		$result_pet = mysql_query($query_pet);

		$query_pet = "INSERT INTO spip_petitions (id_article,email_unique,site_obli,site_unique,message,texte) VALUES ('$id_article','oui','non','non','oui','')";
		$result_pet = mysql_query($query_pet);
	}
	if ($petition=="off") {
		$query_pet="DELETE FROM spip_petitions WHERE id_article=$id_article";
		$result_pet=mysql_query($query_pet);
	}

}



$query_petition="SELECT * FROM spip_petitions WHERE id_article=$id_article";
$result_petition=mysql_query($query_petition);
if (mysql_num_rows($result_petition)>0) $petition=true;
else $petition=false;

if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article) AND ($options == 'avancees' OR $petition==true)) {

	while($row=mysql_fetch_array($result_petition)){
		$id_rubrique=$row[0];
		$email_unique=$row[1];
		$site_obli=$row[2];
		$site_unique=$row[3];
		$message=$row[4];
		$texte_petition=$row[5];
		
	}


	debut_boite_info();
	echo "<CENTER><TABLE WIDTH=100% CELLPADDING=2 BORDER=1 CLASS='hauteur'><TR><TD WIDTH=100% ALIGN='center' BGCOLOR='#FFCC66'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#333333'><B>";
	echo bouton_block_invisible("petitionarticle");
	echo "P&Eacute;TITION";
	echo "</B></FONT></TD></TR></TABLE></CENTER>";
	echo debut_block_invisible("petitionarticle");
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
	
	echo "\n<FORM ACTION='articles.php3' METHOD='post'>";
	echo "\n<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";

	if ($petition){
		echo "<P><input type='radio' name='petition' value='on' id='petitionon' checked>";
		echo "<B><label for='petitionon'>Cet article est une p&eacute;tition</label></B>";

		echo "<FONT SIZE=1>";
		if ($email_unique=="oui")
			echo "<BR><input type='checkbox' name='email_unique' value='oui' id='emailunique' checked>";
		else
			echo "<BR><input type='checkbox' name='email_unique' value='oui' id='emailunique'>";
		echo " <label for='emailunique'>une seule signature par adresse email</label>";
		if ($site_obli=="oui")
			echo "<BR><input type='checkbox' name='site_obli' value='oui' id='siteobli' checked>";
		else
			echo "<BR><input type='checkbox' name='site_obli' value='oui' id='siteobli'>";
		echo " <label for='siteobli'>indiquer obligatoirement un site Web</label>";
		if ($site_unique=="oui")
			echo "<BR><input type='checkbox' name='site_unique' value='oui' id='siteunique' checked>";
		else
			echo "<BR><input type='checkbox' name='site_unique' value='oui' id='siteunique'>";
		echo " <label for='siteunique'>une seule signature par site Web</label>";
		if ($message=="oui")
			echo "<BR><input type='checkbox' name='message' value='oui' id='message' checked>";
		else
			echo "<BR><input type='checkbox' name='message' value='oui' id='message'>";
		echo " <label for='message'>possibilit&eacute; d'envoyer un message</label>";
		
		echo "<P>Descriptif de cette p&eacute;tition&nbsp;:</BR>";
		echo "<TEXTAREA NAME='texte_petition' CLASS='forml' ROWS='4' COLS='10' wrap=soft>";
		echo $texte_petition;
		echo "</TEXTAREA><P>\n";
		
		
		
		
		echo "</FONT>";

	}else{
		echo "<P><input type='radio' name='petition' value='on' id='petitionon'>";
		echo "<label for='petitionon'>Cet article est une p&eacute;tition</label>";
	}
	if (!$petition){
		echo "<P><input type='radio' name='petition' value='off' id='petitionoff' checked>";
		echo "<B><label for='petitionoff'>Cet article ne propose pas de p&eacute;tition</label></B>";
	}else{
		echo "<P><input type='radio' name='petition' value='off' id='petitionoff'>";
		echo "<label for='petitionoff'>Cet article ne propose pas de p&eacute;tition</label>";
	}
	
	echo "<P align='right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'>";
	echo "</FORM>";
	echo "</FONT>";
	echo fin_block();
	fin_boite_info();
	
}


debut_droite();

// Afficher la hierarchie (recurrence)
function parent($collection){
	global $parents;
	global $coll;
	$parents=ereg_replace("(~+)","\\1~",$parents);
	if ($collection!=0){	
		$query2="SELECT * FROM spip_rubriques WHERE id_rubrique=\"$collection\"";
		$result2=mysql_query($query2);

		while($row=mysql_fetch_array($result2)){
			$id_rubrique=$row[0];
			$id_parent=$row[1];
			$titre=typo($row[2]);
			
			if ($id_rubrique==$coll){
				if (acces_restreint_rubrique($id_rubrique))
					$parents="~ <IMG SRC='IMG2/triangle-anim.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=4 FACE='Verdana,Arial,Helvetica,sans-serif'><B>".majuscules($titre)."</B></FONT><BR>\n$parents";
				else
					$parents="~ <IMG SRC='IMG2/triangle.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=4 FACE='Verdana,Arial,Helvetica,sans-serif'><B>".majuscules($titre)."</B></FONT><BR>\n$parents";
			}else{
				if (acces_restreint_rubrique($id_rubrique))
					$parents="~ <IMG SRC='IMG2/triangle-bas-anim.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'>$titre</a></FONT><BR>\n$parents";
				else
					$parents="~ <IMG SRC='IMG2/triangle-bas.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'>$titre</a></FONT><BR>\n$parents";
			}
		}
	parent($id_parent);
	}
}

function mySel($varaut,$variable){
	$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}
	return $retour;
}



//////////////////////////////////////////////////////
// Lire l'article
//

$query = "SELECT * FROM spip_articles WHERE id_article='$id_article'";
$result = mysql_query($query);

if ($row = mysql_fetch_array($result)) {
	$id_article = $row[0];
	$surtitre = $row[1];
	$titre = $row[2];
	$soustitre = $row[3];
	$id_rubrique = $row[4];
	$descriptif = $row[5];
	$chapo = $row[6];
	$texte = $row[7];
	$ps = $row[8];
	$date = $row[9];
	$statut_article = $row[10];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
}



if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date_redac, $regs)) {
        $mois_redac = $regs[2];
        $jour_redac = $regs[3];
        $annee_redac = $regs[1];
        if ($annee_redac > 4000) $annee_redac -= 9000;
}

if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)) {
        $mois = $regs[2];
        $jour = $regs[3];
        $annee = $regs[1];
}


echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0><TR><TD WIDTH=\"100%\">";
echo "<FONT FACE='Georgia,Garamond,Times,serif'>";


//////////////////////////////////////////////////////
// Afficher la hierarchie
//

parent($id_rubrique);
$parents="~ <IMG SRC='IMG2/triangle-bas.gif' WIDTH=16 HEIGHT=14> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);
echo "$parents";
echo "</TD></TR></TABLE>";

echo "<P>";


function my_sel($num,$tex,$comp){
	if ($num==$comp){
		echo "<OPTION VALUE='$num' SELECTED>$tex\n";
	}else{
		echo "<OPTION VALUE='$num'>$tex\n";
	}

}

function afficher_mois($mois){
	my_sel("00","non connu",$mois);
	my_sel("01","janvier",$mois);
	my_sel("02","f&eacute;vrier",$mois);
	my_sel("03","mars",$mois);
	my_sel("04","avril",$mois);
	my_sel("05","mai",$mois);
	my_sel("06","juin",$mois);
	my_sel("07","juillet",$mois);
	my_sel("08","ao&ucirc;t",$mois);
	my_sel("09","septembre",$mois);
	my_sel("10","octobre",$mois);
	my_sel("11","novembre",$mois);
	my_sel("12","d&eacute;cembre",$mois);
}

function afficher_annee($annee){
	// Cette ligne permettrait de faire des articles sans date de publication
	// my_sel("0000","n.c.",$annee); 

	if($annee<1996 AND $annee <> 0){
		echo "<OPTION VALUE='$annee' SELECTED>$annee\n";
	}
	for($i=1996;$i<date(Y)+2;$i++){
		my_sel($i,$i,$annee);
	}
}

function afficher_jour($jour){
	my_sel("00","n.c.",$jour);
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		my_sel($i,$aff,$jour);
	}
}



echo "<TABLE CELLPADDING=18 CELLSPACING=0 BORDER=1><TR><TD BGCOLOR='#FFFFFF' ALIGN='center'>";
echo "<CENTER>";
echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0>";
echo "<TR>";


//////////////////////////////////////////////////////
// Titre, surtitre, sous-titre
//
echo "<TD>";

if ($statut_article=='publie') {
	echo "<img src='IMG2/puce-verte.gif' alt='X' width='13' height='14' border='0' ALIGN='left'>";
}
else if ($statut_article=='prepa') {
	echo "<img src='IMG2/puce-blanche.gif' alt='X' width='13' height='14' border='0' ALIGN='left'>";
}
else if ($statut_article=='prop') {
	echo "<img src='IMG2/puce-orange.gif' alt='X' width='13' height='14' border='0' ALIGN='left'>";
}
else if ($statut_article == 'refuse') {
	echo "<img src='IMG2/puce-rouge.gif' alt='X' width='13' height='14' border='0' ALIGN='left'>";
}
else if ($statut_article == 'poubelle') {
	echo "<img src='IMG2/puce-poubelle.gif' alt='X' width='13' height='14' border='0' ALIGN='left'>";
}

echo "</TD><TD WIDTH=100% align='center'>";


if (strlen($surtitre) > 1) {
	echo "<FONT FACE='arial,helvetica' SIZE=3><B>";
	echo typo($surtitre);
	echo "</B></FONT><BR>\n";
}

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=4><B>";
echo typo($titre);
echo "</B></FONT><BR>\n";

if (strlen($soustitre) > 1) {
	echo "<FONT FACE='arial,helvetica' SIZE=3><B>";
	echo typo($soustitre);
	echo "</B></FONT><BR>\n";
}
echo "</TD>";


//////////////////////////////////////////////////////
// Bouton 'modifier l'article'
//

echo "<TD align='right'>";
if ($flag_editable) {
	echo "<P align=right>";
	echo "<A HREF='articles_edit.php3?id_article=$id_article' onMouseOver=\"modifier_article.src='IMG2/modifier-article-on.gif'\" onMouseOut=\"modifier_article.src='IMG2/modifier-article-off.gif'\"><img src='IMG2/modifier-article-off.gif' alt='Modifier cet article' width='51' height='53' border='0' name='modifier_article'></A>";
}

echo "</TD></TR></TABLE>";

echo "<P align=left>";
echo "<FONT FACE='Georgia,Garamond,Times,serif'>";


if (strlen($descriptif) > 1) {
	echo "<DIV align='left'>";
	debut_boite_info();

	echo "<img src='IMG2/descriptif.gif' alt='DESCRIPTIF' width='59' height='12' border='0'><BR>";
	echo "<FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'>";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</FONT>";
	fin_boite_info();
	echo "<P>";
}


//////////////////////////////////////////////////////
// Affichage date redac et date publi
//

if ($flag_editable AND ($options == 'avancees' OR $statut_article == 'publie')) {
	debut_cadre_relief();

	echo "<FORM ACTION='articles.php3' METHOD='GET'>";
	echo "<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";

	if ($statut_article == 'publie') {	
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND='IMG2/rien.gif'>";
		echo "<TR><TD BGCOLOR='$couleur_foncee' COLSPAN=2><FONT SIZE=2 COLOR='#FFFFFF'><B>DATE DE PUBLICATION EN LIGNE :";
		echo aide ("artdate");
		echo "</B></FONT></TR>";
		echo "<TR><TD ALIGN='center' BGCOLOR='#FFFFFF'>";
		echo "<SELECT NAME='jour' SIZE=1 CLASS='fondl'>";
		afficher_jour($jour);
		echo "</SELECT> ";
		echo "<SELECT NAME='mois' SIZE=1 CLASS='fondl'>";
		afficher_mois($mois);
		echo "</SELECT> ";
		echo "<SELECT NAME='annee' SIZE=1 CLASS='fondl'>";
		afficher_annee($annee);
		echo "</SELECT>";
 		
		echo "</TD><TD ALIGN='right' BGCOLOR='#FFFFFF'>";
		echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'>";
		echo "</TD></TR></TABLE>";
	}
	else {
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND='IMG2/rien.gif'>";
		echo "<TR><TD BGCOLOR='$couleur_foncee'><FONT SIZE=2 COLOR='#FFFFFF'><B>DATE DE CREATION DE L'ARTICLE";
		echo " :</B></FONT></TR>";

		echo "<TR><TD ALIGN='center' BGCOLOR='#FFFFFF'>";
		echo "<B>";
		echo affdate($date);
		echo aide ("artdate");
		echo "</TD></TR></TABLE>";
	
	}
	
	
	if (($options == 'avancees' AND $articles_redac != "non") OR ("$annee_redac-$mois_redac-$jour_redac" != "0000-00-00")) {
		echo "<P><TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND='IMG2/rien.gif'>";
		echo "<TR><TD BGCOLOR='#E4E4E4' COLSPAN=2><FONT SIZE=2 COLOR='#000000'><B>DATE DE PUBLICATION ANT&Eacute;RIEURE :</B></FONT></TR>";

		echo "<TR><TD ALIGN='left' BGCOLOR='#FFFFFF'>";
		if ("$annee_redac-$mois_redac-$jour_redac" == "0000-00-00") {
			echo "<INPUT TYPE='radio' NAME='avec_redac' VALUE='non' id='on' checked>  <B><label for='on'>Ne pas afficher de date de publication ant&eacute;rieure.</label></B>";
			echo "<BR><INPUT TYPE='radio' NAME='avec_redac' VALUE='oui' id='off'>";
			echo " <label for='off'>Afficher la date de publication ant&eacute;rieure.</label> ";
			
			echo "<INPUT TYPE='hidden' NAME='jour_redac' VALUE=\"1\">";
			echo "<INPUT TYPE='hidden' NAME='mois_redac' VALUE=\"1\">";
			echo "<INPUT TYPE='hidden' NAME='annee_redac' VALUE=\"0\">";
		}
		else{
			echo "<INPUT TYPE='radio' NAME='avec_redac' VALUE='non' id='on'>  <label for='on'>Ne pas afficher de date de publication ant&eacute;rieure.</label>";
			echo "<BR><INPUT TYPE='radio' NAME='avec_redac' VALUE='oui' id='off' checked>";
			echo " <B><label for='off'>Afficher :</label></B> ";
			
			echo "<SELECT NAME='jour_redac' SIZE=1 CLASS='fondl'>";
			afficher_jour($jour_redac);
			echo "</SELECT> &nbsp;";
			echo "<SELECT NAME='mois_redac' SIZE=1 CLASS='fondl'>";
			afficher_mois($mois_redac);
			echo "</SELECT> &nbsp;";
			echo "<INPUT TYPE='text' NAME='annee_redac' CLASS='fondl' VALUE=\"$annee_redac\" SIZE='5'>";
		}
		echo "</TD><TD ALIGN='right' BGCOLOR='#FFFFFF'>";
		echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'>";
		echo aide ("artdate_redac");
		echo "</TD></TR></TABLE>";
	
	}

	echo "</FORM>";
	fin_cadre_relief();
}

if (!$flag_editable AND $statut_article == 'publie') {
	echo "<CENTER>".affdate($date)."</CENTER><P>";
}



//////////////////////////////////////////////////////
// 'Article propose pour la publication'
//

if ($statut_article == 'prop') {
	echo "<P><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='red'><B>Article propos&eacute; pour la publication. N'h&eacute;sitez pas &agrave; donner votre avis gr&acirc;ce au forum attach&eacute; &agrave; ce article (en bas de page).</B></FONT></P>";
}


//////////////////////////////////////////////////////
// Liste des auteurs de l'article
//

debut_cadre_relief();

echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC'>";
if ($flag_editable AND $options == 'avancees') {
	echo bouton_block_invisible("auteursarticle");
}
echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'><B>LES AUTEURS</B></FONT>";
echo aide ("artauteurs");
echo "</TABLE>";


//////////////////////////////////////////////////////
// Recherche d'auteur
//

if ($cherche_auteur) {
	echo "<P ALIGN='left'>";
	$query = "SELECT id_auteur, nom FROM spip_auteurs";
	$result = mysql_query($query);
	unset($table_auteurs);
	unset($table_ids);
	while ($row = mysql_fetch_array($result)) {
		$table_auteurs[] = $row[1];
		$table_ids[] = $row[0];
	}
	$resultat = mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
	debut_boite_info();
	if (!$resultat) {
		echo "<B>Aucun r&eacute;sultat pour \"$cherche_auteur\".</B><BR>";
	}
	if (count($resultat) == 1) {
		$ajout_auteur = 'oui';
		list(, $nouv_auteur) = each($resultat);
		echo "<B>L'auteur suivant a &eacute;t&eacute; ajout&eacute; &agrave; l'article :</B><BR>";
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$nouv_auteur";
		$result = mysql_query($query);
		echo "<UL>";
		while ($row = mysql_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$nom_auteur = $row['nom'];
			$email_auteur = $row['email'];
			$bio_auteur = $row['bio'];

			echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$nom_auteur</FONT></B>";
			echo "</FONT>\n";
		}
		echo "</UL>";
	}
	else if (count($resultat) < 16) {
		reset($resultat);
		unset($les_auteurs);
		while (list(, $id_auteur) = each($resultat)) $les_auteurs[] = $id_auteur;
		if ($les_auteurs) {
			$les_auteurs = join(',', $les_auteurs);
			echo "<B>Plusieurs auteurs trouv&eacute;s pour \"$cherche_auteur\":</B><BR>";
			$query = "SELECT * FROM spip_auteurs WHERE id_auteur IN ($les_auteurs) ORDER BY nom";
			$result = mysql_query($query);
			echo "<UL>";
			while ($row = mysql_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];
	
				echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$nom_auteur</FONT></B>";
			
				if ($email_auteur) echo " ($email_auteur)";
				echo " | <A HREF=\"articles.php3?id_article=$id_article&ajout_auteur=oui&nouv_auteur=$id_auteur\">Ajouter cet auteur</A>";
			
				if (trim($bio_auteur)) {
					echo "<BR><FONT SIZE=1>".propre(couper($bio_auteur, 100))."</FONT>\n";
				}
				echo "</FONT><p>\n";
			}
			echo "</UL>";
		}
	}
	else {
		echo "<B>Trop de r&eacute;sultats pour \"$cherche_auteur\" ; veuillez affiner la recherche.</B><BR>";
	}
	fin_boite_info();
	echo "<P>";

}



//////////////////////////////////////////////////////
// Appliquer les modifications sur les auteurs 
//

if ($ajout_auteur && $flag_editable) {
	if ($nouv_auteur > 0) {
		$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$nouv_auteur' AND id_article='$id_article'";
		$result=mysql_query($query);
		$query="INSERT INTO spip_auteurs_articles (id_auteur,id_article) VALUES ('$nouv_auteur','$id_article')";
		$result=mysql_query($query);
	}
}

if ($supp_auteur && $flag_editable) {
	$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$supp_auteur' AND id_article='$id_article'";
	$result=mysql_query($query);

}



//////////////////////////////////////////////////////
// Afficher les auteurs 
//

unset($les_auteurs);

$query = "SELECT * FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien ".
	"WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=$id_article ".
	"GROUP BY auteurs.id_auteur ORDER BY auteurs.nom";
$result = mysql_query($query);

if (mysql_num_rows($result)) {
	$ifond = 0;

	echo "\n<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''>\n";
	while ($row = mysql_fetch_array($result)) {
		$id_auteur = $row[0];
		$nom_auteur = $row[1];
		$email_auteur = $row[3];
		$url_site_auteur = $row[5];
		$statut_auteur = $row[8];
		if ($row['messagerie'] == 'non' OR $row['login'] == '') $messagerie = 'non';
	
		$les_auteurs[] = $id_auteur;
	
		if ($connect_statut == "0minirezo") $aff_articles = "('prepa', 'prop', 'publie', 'refuse')";
		else $aff_articles = "('prop', 'publie')";
		
		$query2 = "SELECT COUNT(articles.id_article) AS compteur ".
			"FROM spip_auteurs_articles AS lien, spip_articles AS articles ".
			"WHERE lien.id_auteur=$id_auteur AND articles.id_article=lien.id_article ".
			"AND articles.statut IN $aff_articles GROUP BY lien.id_auteur";
		$result2 = mysql_query($query2);
		if ($result2) list($nombre_articles) = mysql_fetch_row($result2);
		else $nombre_articles = 0;

		$ifond = $ifond ^ 1;
		$couleur = ($ifond) ? '#FFFFFF' : $couleur_claire;

		$url_auteur = "auteurs_edit.php3?id_auteur=$id_auteur&redirect=".rawurlencode("articles.php3?id_article=$id_article");

		echo "<TR BGCOLOR='$couleur' WIDTH=\"100%\">";
		echo "<TD WIDTH=23>";
		echo "<A HREF=\"$url_auteur\">";
		switch ($statut_auteur) {
		case "0minirezo":
			echo "<img src='IMG2/bonhomme-noir.gif' alt='Admin' width='23' height='12' border='0'>";
			break;					
		case "2redac":
		case "1comite":
			echo "<img src='IMG2/bonhomme-bleu.gif' alt='Admin' width='23' height='12' border='0'>";
			break;					
		case "5poubelle":
			echo "<img src='IMG2/bonhomme-rouge.gif' alt='Admin' width='23' height='12' border='0'>";
			break;					
		case "nouveau":
			echo "&nbsp;";
			break;
		default:
			echo "&nbsp;";
		}
		echo "</A>";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		echo "<A HREF=\"$url_auteur\">$nom_auteur</A>";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		echo bouton_imessage($id_auteur)."&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		if ($email_auteur) echo "<A HREF='mailto:$email_auteur'>email</A>";
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		if ($url_site_auteur) echo "<A HREF='$url_site_auteur'>site</A>";
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial2' ALIGN='right'>";
		if ($nombre_articles > 1) echo "$nombre_articles articles";
		else if ($nombre_articles == 1) echo "1 article";
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial1' align='right'>";
		if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
			echo "<A HREF='articles.php3?id_article=$id_article&supp_auteur=$id_auteur'>Retirer l'auteur</A>";
		}
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "</TR>\n";
	}
	echo "</TABLE>\n";

	$les_auteurs = join(',', $les_auteurs);
}


//////////////////////////////////////////////////////
// Ajouter un auteur
//

if ($flag_editable AND $options == 'avancees') {
	echo debut_block_invisible("auteursarticle");
	
	$query = "SELECT * FROM spip_auteurs WHERE ";
	if ($les_auteurs) $query .= "id_auteur NOT IN ($les_auteurs) AND ";
	$query .= "statut<>'5poubelle' AND statut<>'nouveau' ORDER BY statut, nom";
	$result = mysql_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<P>";
		echo "<FORM ACTION='articles.php3' METHOD='post'>";
		echo "<DIV align=right><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B>AJOUTER UN AUTEUR : &nbsp; </B></FONT>\n";
		echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

		if (mysql_num_rows($result) > 50 AND $flag_mots_ressemblants) {
			echo "<INPUT TYPE='text' NAME='cherche_auteur' CLASS='fondl' VALUE='' SIZE='20'>";
			echo "<INPUT TYPE='submit' NAME='Chercher' VALUE='Chercher' CLASS='fondo'>";
		}
		else {
			echo "<INPUT TYPE='Hidden' NAME='ajout_auteur' VALUE='oui'>";
			echo "<SELECT NAME='nouv_auteur' SIZE='1' STYLE='WIDTH=150' CLASS='fondl'>";
			$group = false;
			$group2 = false;
	
			while($row=mysql_fetch_array($result)) {
				$id_auteur = $row[0];
				$nom = $row[1];
				$email = $row[3];
				$statut = $row[8];
	
				$statut=ereg_replace("0minirezo", "Administrateur", $statut);
				$statut=ereg_replace("1comite", "R&eacute;dacteur", $statut);
				$statut=ereg_replace("2redac", "R&eacute;dacteur", $statut);
				$statut=ereg_replace("5poubelle", "Effac&eacute;", $statut);
	
				$premiere = strtoupper(substr(trim($nom), 0, 1));
	
				if ($connect_statut != '0minirezo') {
					if ($p = strpos($email, '@')) $email = substr($email, 0, $p).'@...';
				}
	
				if ($statut != $statut_old) {
					echo "\n<OPTION VALUE=\"x\">";
					echo "\n<OPTION VALUE=\"x\"> $statut".'s';
				}
			
				if ($premiere != $premiere_old AND ($statut != 'Administrateur' OR !$premiere_old)) {
					echo "\n<OPTION VALUE=\"x\">";
				}
	
				$texte_option = couper("$nom ($email) ", 40);
				echo "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;$texte_option";
				$statut_old = $statut;
				$premiere_old = $premiere;
			}
			
			echo "</SELECT>";
			echo "<INPUT TYPE='submit' NAME='Ajouter' VALUE='Ajouter' CLASS='fondo'>";
		}
		echo "</FORM>";
	}
	echo fin_block();
}

fin_cadre_relief();



//////////////////////////////////////////////////////
// Liste des mots-cles de l'article
//

if ($options == 'avancees' AND $articles_mots != 'non') {
	formulaire_mots('articles', $id_article, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}


//////////////////////////////////////////////////////
// Modifier le statut de l'article
//


?>
<SCRIPT LANGUAGE="JavaScript">
<!-- Beginning of JavaScript -
function change_bouton(selObj){

	var selection=selObj.options[selObj.selectedIndex].value;

	if (selection=="publie"){
		document.statut.src="IMG2/puce-verte.gif";
	}
	if (selection=="prepa"){
		document.statut.src="IMG2/puce-blanche.gif";
	}
	if (selection=="prop"){
		document.statut.src="IMG2/puce-orange.gif";
	}
	if (selection=="refuse"){
		document.statut.src="IMG2/puce-rouge.gif";
	}
	if (selection=="poubelle"){
		document.statut.src="IMG2/puce-poubelle.gif";
	}
}

// - End of JavaScript - -->
</SCRIPT>
<?php

if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article)) {
	echo "<FORM ACTION='articles.php3' METHOD='get'>";
	debut_cadre_relief();
	echo "<CENTER>";
	
	echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

	echo "<B>Cet article est :</B> ";

	echo "<SELECT NAME='statut_nouv' SIZE='1' CLASS='fondl' onChange='change_bouton(this)'>";

	echo "<OPTION" . mySel("prepa", $statut_article) .">en cours de r&eacute;daction\n";
	echo "<OPTION" . mySel("prop", $statut_article) . ">propos&eacute; &agrave; l'&eacute;valuation\n";
	echo "<OPTION" . mySel("publie", $statut_article) . ">publi&eacute; en ligne\n";
	echo "<OPTION" . mySel("poubelle", $statut_article) . ">&agrave; la poubelle\n";
	echo "<OPTION" . mySel("refuse", $statut_article) . ">refus&eacute;\n";

	echo "</SELECT>";

	echo " \n";

	if ($statut_article=='publie') {
		echo "<img src='IMG2/puce-verte.gif' alt='X' width='13' height='14' border='0' NAME='statut'>";
	}
	else if ($statut_article=='prepa') {
		echo "<img src='IMG2/puce-blanche.gif' alt='X' width='13' height='14' border='0' NAME='statut'>";
	}
	else if ($statut_article=='prop') {
		echo "<img src='IMG2/puce-orange.gif' alt='X' width='13' height='14' border='0' NAME='statut'>";
	}
	else if ($statut_article == 'refuse') {
		echo "<img src='IMG2/puce-rouge.gif' alt='X' width='13' height='14' border='0' NAME='statut'>";
	}
	else if ($statut_article == 'poubelle') {
		echo "<img src='IMG2/puce-poubelle.gif' alt='X' width='13' height='14' border='0' NAME='statut'>";
	}
	echo " \n";

	echo "<INPUT TYPE='submit' NAME='Modifier' VALUE='Modifier' CLASS='fondo'>";
	echo aide ("artstatut");
	echo "</CENTER>";
	fin_cadre_relief();
	echo "</FORM>";
}



//////////////////////////////////////////////////////
// Corps de l'article
//

echo "<DIV align=justify>";

echo "<P align=justify><B>";
echo justifier(propre($chapo));
echo "</B>";

echo "<P align=justify>";
echo justifier(propre($texte));

if ($ps) {
	echo "<FONT SIZE=2><P align=justify><B>P.S.</B> ";
	echo justifier(propre($ps));
	echo "</FONT>";
}


if ($les_notes) {
	echo "<FONT SIZE=2><P align=justify>";
	echo propre($les_notes);
	echo "</FONT>";
}


echo "<DIV align=right>";



//////////////////////////////////////////////////////
// Bouton "modifier cet article"
//

if ($flag_editable) {
	echo "<A HREF='articles_edit.php3?id_article=$id_article' onMouseOver=\"modifier_article2.src='IMG2/modifier-article-on.gif'\" onMouseOut=\"modifier_article2.src='IMG2/modifier-article-off.gif'\"><img src='IMG2/modifier-article-off.gif' alt='Modifier cet article' width='51' height='53' border='0' name='modifier_article2'></A>";
}


echo "<DIV ALIGN=left>";



/////// Documents joints...
function afficher_documents_non_inclus($row) {
	global $nb_image, $connect_id_auteur, $flag_editable;
	$id_article = $row['id_article'];
	$id_document = $row['id_document'];
	$titre = $row ['titre'];
	$descriptif = $row['descriptif'];
	$numero = $row['numero_document'];
	$nom_fichier_preview = $row['nom_fichier_preview'];
	$largeur = $row['largeur_preview'];
	$hauteur = $row['hauteur_preview'];
	$inclus = $row['inclus'];
	$type = $row['type'];
	$nom_fichier_doc = $row['nom_fichier_doc'];
	$largeur_doc = $row['largeur_doc'];
	$hauteur_doc = $row['hauteur_doc'];
	$taille_fichier_doc = $row['taille_fichier_doc'];


	if ($numero > $nb_image) $nb_image = $numero;
	if (!$titre) $titre = "Document $numero";	

	echo "<tr class='arial1'>";

	
	if ($flag_editable)
		echo "<td><b><a href='document_edit.php3?id_document=$id_document&id_article=$id_article'><img src='IMG2/document.gif' align='middle' alt='[DOC]' width='16' height='12' border='0'> $titre</a></b></td>";
	else 
		echo "<td><b><img src='IMG2/document.gif' align='middle' alt='[DOC]' width='16' height='12' border='0'> $titre</b></td>";
	
	


	echo "<td>";
		if ($taille_fichier_doc > 0){
			echo "<a href='../IMG/$nom_fichier_doc'>Voir le fichier ".strtoupper($type)."</a>";	
		} else {
			echo "$type";		
		}
	
	echo "</td>";
	
	echo "<td>";
		if ($taille_fichier_doc > 0){
			echo taille_en_octets($taille_fichier_doc);
		}
		else {
			echo "<font color='red'>Pas de document li&eacute;</font>";
		}
	echo "</td>";
	echo "<td>";
		if ($largeur_doc>0) echo "$largeur_doc x $hauteur_doc pixels";
	echo "</td>";
	
	if ($flag_editable){
		$hash = calculer_action_auteur("supp_def ".$id_document);

		echo "<td align='right'>";
		echo "<a href='../spip_image.php3?redirect=articles.php3&hash_id_auteur=$connect_id_auteur&hash=$hash&id_article=$id_article&def_supp=$id_document'>Supprimer ce document</a>";
		echo "</td>";
	}
	echo "</tr>";
}

$query = "SELECT * FROM spip_documents WHERE id_article=$id_article AND inclus='non' ORDER BY numero_document";
$result = mysql_query($query);

if (mysql_num_rows($result)>0){

	echo "<p><table width=100% cellpadding=3 cellspacing=0 border=0>";
	echo "<tr width=100% background=''>";
	echo "<td width=40%><hr noshade></td>";
	echo "<td>&nbsp;&nbsp;</td>";
	echo "<td><font size=1 face='verdana,arial,helvetica,sans-serif'><b>DOCUMENTS&nbsp;ASSOCI&Eacute;S</b></font></td>";
	echo "<td>&nbsp;&nbsp;</td>";
	echo "<td width=40%><hr noshade></td>";
	echo "</tr></table>";
	
	while ($row = mysql_fetch_array($result))
	{
		echo "<table width=100% cellpadding=0 cellspacing=0 border=0>";
		afficher_documents_non_inclus($row);
		echo "</table>";
	}
}
if ($flag_editable){
	$nb_image ++;
	echo "<div align='right'><b><a href='document_edit.php3?id_article=$id_article&new=oui&nb_image=$nb_image'>Ajouter un document li&eacute; &agrave; cet article</a></b></div>";
}



//////////////////////////////////////////////////////
// "Demander la publication"
//

if ($flag_auteur AND $statut_article == 'prepa') {
	echo "<P>";
	debut_cadre_relief();
	echo "<B>Lorsque votre article est termin&eacute;, vous pouvez proposer sa publication.</B>";
	echo aide ("artprop");
	bouton("Demander la publication de cet article", "articles.php3?id_article=$id_article&statut_nouv=prop");
	fin_cadre_relief();
}


echo "</TD></TR></TABLE>";


//////////////////////////////////////////////////////
// Forums
//

echo "<BR><BR>";

$forum_retour = urlencode("articles.php3?id_article=$id_article");

echo "<P align='right'>";
echo "<A HREF='forum_envoi.php3?statut=prive&adresse_retour=".$forum_retour."&id_article=$id_article&titre_message=".urlencode($titre)."' onMouseOver=\"message.src='IMG2/message-on.gif'\" onMouseOut=\"message.src='IMG2/message-off.gif'\">";
echo "<img src='IMG2/message-off.gif' alt='Poster un message' width='51' height='52' border='0' name='message'></A>";
echo "<P align='left'>";


	$query_forum = "SELECT COUNT(*) FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0";
 	$result_forum = mysql_query($query_forum);
 	$total = 0;
 	if ($row = mysql_fetch_array($result_forum)) $total = $row[0];

	if (!$debut) $debut = 0;
	$total_afficher = 8;
	echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
	if ($total > $total_afficher) {
		echo "<CENTER>";
		for ($i = 0; $i < $total; $i = $i + $total_afficher){
			$y = $i + $total_afficher - 1;
			if ($i == $debut)
				echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
			else
				echo "[<A HREF='articles.php3?id_article=$id_article&debut=$i'>$i-$y</A>] ";
		}
		echo "</CENTER>";
		echo "</font>";
	}



$query_forum = "SELECT * FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0 ORDER BY date_heure DESC LIMIT $debut,$total_afficher";
$result_forum = mysql_query($query_forum);
afficher_forum($result_forum, $forum_retour);
	


	if (!$debut) $debut = 0;
	$total_afficher = 8;
	echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
	if ($total > $total_afficher) {
		echo "<CENTER>";
		for ($i = 0; $i < $total; $i = $i + $total_afficher){
			$y = $i + $total_afficher - 1;
			if ($i == $debut)
				echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
			else
				echo "[<A HREF='articles.php3?id_article=$id_article&debut=$i'>$i-$y</A>] ";
		}
		echo "</CENTER>";
		echo "</font>";
	}


echo "</FONT>";


fin_page();

?>

