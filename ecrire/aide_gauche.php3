<?

include ("inc_version.php3");

if (file_exists("inc_connect.php3")) {
	include_local ("inc_connect.php3");
	include_local ("inc_auth.php3");
	include_local ("inc_admin.php3");
	include_local ("inc_acces.php3");
	$aide_statut = ($connect_statut == "0minirezo") ? "admin" : "redac";
}
else $aide_statut = "admin";

?>
<HTML>
<HEAD>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<style>
<!--
	a {text-decoration: none; }
	A:Hover {text-decoration: underline;}

-->
</style>
</HEAD>

<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" TOPMARGIN="0" LEFTMARGIN="0" MARGINWIDTH="0" MARGINHEIGHT="0">




<?

function rubrique($titre, $statut = "redac") {
	global $aide;
	global $ligne;
	global $larubrique;
	global $texte;
	global $afficher;
	global $aff_ligne;
	global $rubrique;
	global $les_rub;
	
	global $aide_statut;
	
	if (($statut == "admin" AND $aide_statut == "admin") OR ($statut == "redac")) {
		$larubrique++;
		$ligne++;
		
		$texte[$ligne]="<TR><TD><IMG SRC='IMG2/rien.gif' BORDER=0 WIDTH=10 HEIGHT=1></TD></TR><TD BGCOLOR='#044476' COLSPAN=2><A HREF='#LIEN'>#IMG</A>	<B><A HREF='#LIEN'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#FFFFFF'>$titre</FONT></A></B></TD></TR>";
		$rubrique[$ligne]=$larubrique;
		
		if (ereg(",$larubrique,","$les_rub")){
			$afficher[$larubrique]=1;
		}else{
			$afficher[$larubrique]=0;
		}
		
		$aff_ligne[$ligne]=1;
	}
}

function article($titre, $lien, $statut = "redac") {
	global $aide;
	global $ligne;
	global $larubrique;
	global $rubrique;
	global $texte;
	global $afficher;
	global $les_rub;
	global $aide_statut;
	

	if (($statut == "admin" AND $aide_statut == "admin") OR ($statut == "redac")) {
		$ligne++;
		
		$rubrique[$ligne]=$larubrique;
		
		if ($aide==$lien) {
			$afficher[$larubrique]=1;
			$texte[$ligne]= "<TR><TD BGCOLOR='#DDDDDD' ALIGN='right' COLSPAN=2><FONT FACE='Arial,Helvetica,sans-serif' SIZE=2>$titre</font> <IMG SRC='IMG2/triangle.gif' BORDER=0 ALIGN='middle'></TD></TR>";
		}
		else {
			$texte[$ligne]= "<TR><TD><FONT FACE='Arial,Helvetica,sans-serif' SIZE=2><B><A HREF='aide_index.php3?aide=$lien&les_rub=$les_rub' TARGET='_top'><IMG SRC='IMG2/triangle.gif' BORDER=0></A></B></font></TD><TD BGCOLOR='#FFFFFF'><FONT FACE='Arial,Helvetica,sans-serif' SIZE=2><A HREF='aide_index.php3?aide=$lien&les_rub=$les_rub' TARGET='_top'>$titre</A></font></TD></TR>";
		}
	}
}



?>

<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=0>

<?

if ($supp_rub) $les_rub=ereg_replace(",$supp_rub,","",$les_rub);
if ($addrub) $les_rub.=",$addrub,";

rubrique("Installation de SPIP","admin");
article("R&eacute;gler les droits d'acc&egrave;s", "install0", "admin");
article("1. Votre connexion mySQL", "install1", "admin");
article("3. Choix de votre base", "install2", "admin");
article("5. Informations personnelles", "install5", "admin");
article("V&eacute;rification par FTP", "ftp_auth", "admin");

rubrique("Les articles");
article("Les raccourcis typographiques","raccourcis");
article("Titre, surtitre, soustitre","arttitre");
article("Choisir la rubrique","artrub");
article("Descriptif rapide","artdesc");
article("Chapeau / Article virtuel","artchap");
article("Texte","arttexte");
article("Ins&eacute;rer des images","artimg");
article("Date","artdate");
article("Date de publication ant&eacute;rieure","artdate_redac");
article("Les auteurs","artauteurs");
article("Logo de l'article","logoart","admin");
article("Le statut de l'article","artstatut");
article("Proposer son article","artprop");


rubrique("Les rubriques");
article("Une structure hi&eacute;rarchis&eacute;e","rubhier");
article("Choisir la rubrique","rubrub","admin");
article("Logo de la rubrique","rublogo","admin");

rubrique("Les br&egrave;ves");
article("Les br&egrave;ves","breves");
article("Choisir la rubrique","brevesrub");
article("Le lien hypertexte","breveslien");
article("Le statut de la br&egrave;ve","brevesstatut","admin");
article("Le logo de la br&egrave;ve","breveslogo","admin");

rubrique("Les mots-cl&eacute;s");
article("Principe des mots-cl&eacute;s","mots");
article("Les mots-cl&eacute;s","artmots");
article("Les groupes de mots","motsgroupes","admin");


rubrique("Les sites r&eacute;f&eacute;renc&eacute;s");
article("R&eacute;f&eacute;rencer un site","reference");
article("Sites syndiqu&eacute;s","rubsyn");
article("Articles syndiqu&eacute;s","artsyn");


rubrique("La messagerie interne");
article("<img src='IMG2/m_envoi.gif' align='left' border=0> Les messages entre utilisateurs","messut");
article("<img src='IMG2/m_envoi_bleu.gif' align='left' border=0> Les pense-b&ecirc;te","messpense");
article("Le calendrier","messcalen");
article("Configuration personnelle de la messagerie","messconf");


rubrique("Suivi des forums","admin");
article("Suivi des forums","suiviforum","admin");

rubrique("Configuration pr&eacute;cise","admin");
article("Nom et adresse de votre site","confnom","admin");
article("Contenu des articles","confart","admin");
article("Articles post-dat&eacute;s","confdates","admin");
article("Fonctionnement des forums","confforums","admin");
article("Syst&egrave;me de br&egrave;ves","confbreves","admin");
article("Messagerie interne","confmessagerie","admin");
article("Statistiques des visites","confstat","admin");
article("Envoi automatique de mails","confmails","admin");
article("Moteur de recherche int&eacute;gr&eacute;","confmoteur","admin");

rubrique("Configuration de l'interface personnelle");
article("Modifier l'interface","intermodif");
article("Interface simplifi&eacute;e / compl&egrave;te","intersimple");
article("Le cookie de correspondance","cookie");

for ($i=0; $i<=count($texte); $i++) {
	
	$larubrique=$rubrique[$i];
	$aff=$afficher[$larubrique];
	
	if ($aff == 1 OR $aff_ligne[$i] == 1) {
		if ($aff == 1) {
			$supp_rub="$larubrique";
			
			$texte[$i]=ereg_replace("#IMG","<img src='IMG2/triangle-bleu-bas.gif' alt='&gt;' width='14' height='14' border='0'>",$texte[$i]);
			$texte[$i]=ereg_replace("#LIEN","aide_gauche.php3?les_rub=$les_rub&supp_rub=$supp_rub&aide=$aide",$texte[$i]);
		}
		else {
			$ajouter_rub="$larubrique";
			$texte[$i]=ereg_replace("#IMG","<img src='IMG2/triangle-bleu.gif' alt='&gt;' width='14' height='14' border='0'>",$texte[$i]);
//			$texte[$i]=ereg_replace("#LIEN","aide_gauche.php3?les_rub=$les_rub&add_rub=$add_rub&aide=$aide",$texte[$i]);
			$texte[$i]=ereg_replace("#LIEN","aide_gauche.php3?les_rub=$les_rub&addrub=$ajouter_rub&aide=$aide",$texte[$i]);

		}
		echo $texte[$i]."\n";
	}
}


?>




</TABLE>

</BODY>
</HTML>