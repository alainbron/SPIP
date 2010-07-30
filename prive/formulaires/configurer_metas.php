<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_configurer_metas_charger_dist($form)
{
	$f = charger_fonction('charger', "formulaires/$form", true);
	if ($f)
		return $f($form);
	else {
		$infos = formulaires_configurer_metas_infos($form);
		if (!is_array($infos)) return $infos;
		return $GLOBALS[$infos['meta']];
	}
}

function formulaires_configurer_metas_verifier_dist($form)
{
	$f = charger_fonction('verifier', "formulaires/$form", true);
	return $f ? $f($form) : array();
}

function formulaires_configurer_metas_traiter_dist($form)
{
	$f = charger_fonction('traiter', "formulaires/$form", true);
	if ($f)
		return $f($form);
	else {
		$infos = formulaires_configurer_metas_infos($form);
		if (!is_array($infos)) return $infos; // fait ci-dessus en fait
		$vars = formulaires_configurer_metas_recense($infos['path'], PREG_PATTERN_ORDER);
		$meta = $infos['meta'];
		foreach (array_unique($vars[2]) as $k) {
			$v = _request($k);
			ecrire_meta($k, is_array($v) ? serialise($v) : $v, 'oui', $meta);
		}
		return !isset($infos['prefix']) ? array()
		: array('redirect' => generer_url_ecrire($infos['prefix']));
	}
}

// version amelioree de la RegExp de cfg_formulaire.
define('_EXTRAIRE_SAISIES', 
	'#<(select|textarea|input)[^>]*\sname=["\'](\w+)(\[\w*\])?["\'](?: class=["\']([^\'"]*)["\'])?( multiple=)?[^>]*?>#ims');

// determiner la liste des noms des saisies d'un formulaire
// (a refaire avec SAX)
function formulaires_configurer_metas_recense($form, $opt='')
{
	if (!$opt) $opt = PREG_SET_ORDER;
	$f = file_get_contents($form);
	if ($f AND preg_match_all(_EXTRAIRE_SAISIES, $f, $r, $opt))
		return $r;
	else return array();
}

define('_EXTRAIRE_PLUGIN', '@(?:' . _DIR_PLUGINS . '|' . _DIR_EXTENSIONS .')/?([^/]+)/@');

// Recuperer la version compilee de plugin.xml et normaliser
// Si ce n'est pas un plugin, dire qu'il faut prendre la table std des meta.
function formulaires_configurer_metas_infos($form){

	$path = find_in_path($form.'.' . _EXTENSION_SQUELETTES, 'formulaires/');
	if (!$path) return ''; // cas traite en amont normalement.
	if (!preg_match(_EXTRAIRE_PLUGIN, $path, $plugin))
		return array('path' => $path, 'meta' => 'meta');
	$plugin = $plugin[1];
	$get_infos = charger_fonction('get_infos','plugins');
	$infos = $get_infos($plugin);
	if (!is_array($infos) OR !isset($infos['prefix']))
		return _T('erreur_plugin_nom_manquant');
	$prefix = $infos['prefix'];
	$infos['path'] = $path;
	if (!isset($infos['meta'])) $infos['meta'] = ($prefix . '_metas');
	return $infos;
}
?>