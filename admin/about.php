<?php
/* Copyright (C) 2013 		Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2014 	Ferran Marcet		  <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/custom/berrypro/admin/about.php
 * Modificar el grupo     \ingroup    esaeb
 *      \brief      Page about
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory
dol_include_once("/berrypro/lib/berrypro.lib.php");


if (!$user->admin) accessforbidden();


$langs->load("admin");
$langs->load("other");
$langs->load("info@berrypro.eu");


/**
 * View
 */

$helpurl='EN:Module_BerryPro|FR:Module_BerryPro_FR|ES:M&oacute;dulo_BerryPro';
llxHeader('',$langs->trans("About"), $helpurl);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("About"),$linkback,'setup');
print '<br>';

$head=berryproadmin_prepare_head();

dol_fiche_head($head, 'about', $langs->trans("BerryPro"), 0, 'bill');

print $langs->trans("AboutInfo").'<br>';
print '<br>';

print $langs->trans("MoreModules").'<br>';
print '&nbsp; &nbsp; &nbsp; '.$langs->trans("MoreModulesLink").'<br>';
print '&nbsp; &nbsp; &nbsp; '.$langs->trans("ModulesHelp");
print '<br>';
print '<br>';
$url='info@berrypro.eu';
print '<a href="'.$url.'" target="_blank"><img border="0" src="'.dol_buildpath('/berrypro/img/logo.png',1).'"></a>';

print '<br>';

dol_fiche_end();


llxFooter();

$db->close();
?>
