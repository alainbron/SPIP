<?php


// fonctions de recherche et de reservation
// dans l'arborescence des boucles

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_COMPILO_INDEX")) return;
define("_INC_COMPILO_INDEX", "1");

// index_pile retourne la position dans la pile du champ SQL $nom_champ 
// en prenant la boucle la plus proche du sommet de pile (indique par $idb).
// Si on ne trouve rien, on considere que ca doit provenir du contexte 
// (par l'URL ou l'include) qui a ete recopie dans Pile[0]
// (un essai d'affinage a debouche sur un bug vicieux)
// Si ca reference un champ SQL, on le memorise dans la structure $boucles
// afin de construire un requete SQL minimale (plutot qu'un brutal 'SELECT *')

function index_pile($idb, $nom_champ, &$boucles, $explicite='') {
  global $exceptions_des_tables, $table_des_tables, $tables_des_serveurs_sql;

	$i = 0;
	if (strlen($explicite)) {
	// Recherche d'un champ dans un etage superieur
	  while (($idb != $explicite) && ($idb !='')) {
#		spip_log("Cherchexpl: $nom_champ '$explicite' '$idb' '$i'");
			$i++;
			$idb = $boucles[$idb]->id_parent;
		}
	}

#	spip_log("Cherche: $nom_champ a partir de '$idb'");
	$c = strtolower($nom_champ);
	// attention: entre la boucle nommee 0, "" et le tableau vide,
	// il y a incoherences qu'il vaut mieux eviter
	while ($boucles[$idb]) {
		$r = $boucles[$idb]->type_requete;
		$s = $boucles[$idb]->sql_serveur;
		if (!$s) 
		  { $s = 'localhost';
    // indirection (pour les rares cas ou le nom de la table!=type)
		    $t = $table_des_tables[$r];
		  }
		// pour les tables non Spip
		if (!$t) {$nom_table = $t = $r; }
		else $nom_table = 'spip_' . $t;

#		spip_log("Go: idb='$idb' r='$r' c='$c' nom='$nom_champ' s=$s t=$t");
		$desc = $tables_des_serveurs_sql[$s][$nom_table];
		if (!$desc) {
			erreur_squelette(_T('zbug_table_inconnue', array('table' => $r)),
				"'$idb'");
			# continuer pour chercher l'erreur suivante
			return  "'#" . $r . ':' . $nom_champ . "'";
		}
		$excep = $exceptions_des_tables[$r][$c];
		if ($excep) {
			// entite SPIP alias d'un champ SQL
			if (!is_array($excep)) {
				$e = $excep;
				$c = $excep;
			} 
			// entite SPIP alias d'un champ dans une autre table SQL
			else {
				$t = $excep[0];
				$e = $excep[1].' AS '.$c;
			}
		}
		else {
			// $e est le type SQL de l'entree
			// entite SPIP homonyme au champ SQL
			if ($desc['field'][$c])
				$e = $c;
			else
				unset($e);
		}

#		spip_log("Dans $idb ('$t' '$e'): $desc");

		// On l'a trouve
		if ($e) {
			$boucles[$idb]->select[] = $t . "." . $e;
			return '$Pile[$SP' . ($i ? "-$i" : "") . '][\'' . $c . '\']';
		}
#		spip_log("On remonte vers $i");
		// Sinon on remonte d'un cran
		$idb = $boucles[$idb]->id_parent;
		$i++;
	}

#	spip_log("Pas vu $nom_champ");
	// esperons qu'il y sera
	return('$Pile[0][\''.$nom_champ.'\']');
}

# calculer_champ genere le code PHP correspondant a une balise Spip
# Retourne une EXPRESSION php 
function calculer_champ($p) {
	$p = calculer_balise($p->nom_champ, $p);

	// definir le type et les traitements
	// si ca ramene le choix par defaut, ce n'est pas un champ 

	if (($p->code) && ($p->code != '$Pile[0][\''.$nom.'\']')) {
		// Par defaut basculer en numerique pour les #ID_xxx
		if (substr($nom,0,3) == 'ID_') $p->statut = 'num';
	}

	else {
	// on renvoie la forme initiale '#TOTO'
	$p->code = "'#" . $nom . "'";
	$p->statut = 'php';	// pas de traitement
	
	}

	// Retourner l'expression php correspondant au champ + ses filtres
	return applique_filtres($p);
}

// cette fonction sert d'API pour demander le champ '$champ' dans la pile
function champ_sql($champ, $p) {
	return index_pile($p->id_boucle, $champ, $p->boucles, $p->nom_boucle);
}

// cette fonction sert d'API pour demander une balise quelconque sans filtre
function calculer_balise($nom, $p) {

	// regarder s'il existe une fonction personnalisee balise_NOM()
	$f = 'balise_' . $nom;
	if (function_exists($f))
		return $f($p);

	// regarder s'il existe une fonction standard balise_NOM_dist()
	$f = 'balise_' . $nom . '_dist';
	if (function_exists($f))
		return $f($p);

	// regarder s'il existe un fichier d'inclusion au nom de la balise
	// contenant une fonction balise_NOM_collecte
	$file = 'inc-' . strtolower($nom) . _EXTENSION_PHP;
	if ($file = find_in_path($file)) {
		include_local($file);
		# une globale ?? defined ou function_exists(..._dyn) serait mieux ?
		$f = $GLOBALS['balise_' . $nom . '_collecte'];
		if (is_array($f))
			return calculer_balise_dynamique($p, $nom, $f);
	}

	// S'agit-il d'un logo ? Une fonction speciale les traite tous
	if (ereg('^LOGO_', $nom))
		return calculer_balise_logo($p);

	// ca pourrait etre un champ SQL homonyme,
	$p->code = index_pile($p->id_boucle, $nom, $p->boucles, $p->nom_boucle);

	// Compatibilite ascendante avec les couleurs html (#FEFEFE) :
	// SI le champ SQL n'est pas trouve
	// ET si la balise a une forme de couleur
	// ET s'il n'y a ni filtre ni etoile
	// ALORS retourner la couleur.
	// Ca permet si l'on veut vraiment de recuperer [(#ACCEDE*)]
	if (ereg("^[\$]Pile[[]0[]][[]'([A-F]{1,6})'[]]$", $p->code, $match)
	AND !$p->etoile
	AND !$p->fonctions) {
		$p->code = "'#". $match[1]."'";
		$p->statut = 'php';
	}

	return $p;
}

//
// Traduction des balises dynamiques, notamment les "formulaire_*"
// Inclusion du fichier associe a son nom.
// Ca donne les arguments a chercher dans la pile,on compile leur localisation
// Ensuite on delegue a une fonction generale definie dans inc-calcul-outils
// qui recevra a l'execution la valeurs des arguments, 
// ainsi que les filtres (qui ne sont donc pas traites a la compil)

function calculer_balise_dynamique($p, $nom, $l) {
	balise_distante_interdite($p);
	$param = param_balise($p);
	$p->code = "executer_balise_dynamique('" . $nom . "',\n\tarray("
	  . join(',',collecter_balise_dynamique($l, $p))
	  . filtres_arglist($param, $p, ',')
	  . "),\n\tarray("
	  . (!$p->fonctions ? '' : ("'" . join("','", $p->fonctions) . "'"))
	  . "), \$GLOBALS['spip_lang'])";
	$p->statut = 'php';
	$p->fonctions = '';

	// Cas particulier de #FORMULAIRE_FORUM : inserer l'invalideur
	if ($nom == 'FORMULAIRE_FORUM')
		$p->code = code_invalideur_forums($p, $p->code);

	return $p;
}

function param_balise(&$p) {
	$a = $p->fonctions;
	if ($a) list(,$nom) = each($a) ; else $nom = '';
	if (!ereg(' *\{ *([^}]+) *\} *',$nom, $m))
	  return '';
	else {
		$filtres= array();
		while (list(, $f) = each($a)) if ($f) $filtres[] = $f;
		$p->fonctions = $filtres;
		return $m[1];
	}
}

// construire un tableau des valeurs interessant un formulaire

function collecter_balise_dynamique($l, $p) {
	$args = array();
	foreach($l as $c) { $x = calculer_balise($c, $p); $args[] = $x->code;}
	return $args;
}

// Genere l'application d'une liste de filtres
function applique_filtres($p) {

	$statut = $p->statut;
	$fonctions = $p->fonctions;
	$p->fonctions = ''; # pour r�utiliser la structure si r�cursion

	// pretraitements standards
	switch ($statut) {
		case 'num':
			$code = "intval($code)";
			break;
		case 'php':
			break;
		case 'html':
		default:
			$code = "trim($code)";
			break;
	}

//  processeurs standards (cf inc-balises.php3)
	$code = ($p->etoile ? $p->code : champs_traitements($p));
	// Appliquer les filtres perso
	if ($fonctions) {
		foreach($fonctions as $fonc) {
			if ($fonc) {

				$arglist = '';
				if (ereg('([^\{\}]+)\{(.+)\}$', $fonc, $regs)) {
					$fonc = $regs[1];

				        $arglist = filtres_arglist($regs[2],$p, ($fonc == '?' ? ':' : ','));
				}
				if (function_exists($fonc))
				  $code = "$fonc($code$arglist)";
				else if (strpos(" < > <= >= == <> ? ", " $fonc "))
				  $code = "($code $fonc "
				    . substr($arglist,1)
				    . ')';
				else 
				  $code = "erreur_squelette('".
					  texte_script(
						_T('zbug_erreur_filtre', array('filtre' => $fonc))
					)."','" . $p->id_boucle . "')";
			}
		}
	}

	// post-traitement securite
	if ($statut == 'html')
		$code = "interdire_scripts($code)";
	return $code;
}

// analyse des parametres d'un champ etendu
// [...(#CHAMP{parametres})...] ou [...(#CHAMP|filtre{parametres})...]
// retourne une suite de N references aux N valeurs indiqu�es avec N virgules

function filtres_arglist($args, $p, $sep) {
	$arglist ='';
	while (ereg('([^,]+),?(.*)$', $args, $regs)) {
		$arg = trim($regs[1]);
		if ($arg) {
			if ($arg[0] =='$')
				$arg = '$Pile[0][\'' . substr($arg,1) . "']";
			elseif ($arg[0] =='<')
			  $arg = calculer_texte($arg, $p->id_boucle, $p->boucles, $p->id_mere);
			elseif (ereg("^" . NOM_DE_CHAMP ."(.*)$", $arg, $r2)) {
				$p->nom_boucle = $r2[2];
				$p->nom_champ = $r2[3];
				# faudrait verifier !trim(r2[5])
				$arg = calculer_champ($p);
			} 

			$arglist .= $sep . $arg;
		}
		$args=$regs[2];
	}
	return $arglist;
}

//
// Reserve les champs necessaires a la comparaison avec le contexte donne par
// la boucle parente ; attention en recursif il faut les reserver chez soi-meme
// ET chez sa maman
// 
function calculer_argument_precedent($idb, $nom_champ, &$boucles) {

	// si recursif, forcer l'extraction du champ SQL mais ignorer le code
	if ($boucles[$idb]->externe)
		index_pile ($idb, $nom_champ, $boucles); 
	// retourner $Pile[$SP] et pas $Pile[0] (bug recursion en 1ere boucle)
	$prec = $boucles[$idb]->id_parent;
	return (!$prec ? ('$Pile[$SP][\''.$nom_champ.'\']') : 
		index_pile($prec, $nom_champ, $boucles));
}

function rindex_pile($p, $champ, $motif) 
{
	$n = 0;
	$b = $p->id_boucle;
	$p->code = '';
	while ($b != '') {
	if ($s = $p->boucles[$b]->param) {
	  foreach($s as $v) {
		if (strpos($v,$motif) !== false) {
		  $p->code = '$Pile[$SP' . (($n==0) ? "" : "-$n") .
			"]['$champ']";
		  $b = '';
		  break;
		}
	  }
	}
	$n++;
	$b = $p->boucles[$b]->id_parent;
	}
	if (!$p->code) {
		erreur_squelette(_T('zbug_champ_hors_motif',
			array('champ' => '#' . strtoupper($champ),
				'motif' => $motif)
		), $p->id_boucle);
	}
	$p->statut = 'php';
	return $p;
}

?>
