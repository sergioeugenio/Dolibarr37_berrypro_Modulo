<?php
/* Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Ferran Marcet        <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

function berryproadmin_prepare_head()
{
	global $langs, $conf, $user;
	$langs->load("info@berrypro.eu");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/berrypro/admin/berrypro.php',1);
	$head[$h][1] = $langs->trans("MASSOSetup"); //Vigilar
	$head[$h][2] = 'configuration';
	$h++;

	$head[$h][0] = dol_buildpath('/berrypro/admin/about.php',1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	return $head;
}
?>
