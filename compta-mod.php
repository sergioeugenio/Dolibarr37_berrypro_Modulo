<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 */

/**
 *	\file       htdocs/compta/index.php
 *	\ingroup    compta
 *	\brief      Main page of accountancy area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (! empty($conf->commande->enabled))
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->commande->enabled))
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (! empty($conf->tax->enabled))
	require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';

// L'espace compta/treso doit toujours etre actif car c'est un espace partage
// par de nombreux modules (banque, facture, commande a facturer, etc...) independamment
// de l'utilisation de la compta ou non. C'est au sein de cet espace que chaque sous fonction
// est protegee par le droit qui va bien du module concerne.
//if (!$user->rights->compta->general->lire)
//  accessforbidden();

$langs->load("compta");
$langs->load("bills");
if (! empty($conf->commande->enabled))
	$langs->load("orders");

$action=GETPOST('action', 'alpha');
$bid=GETPOST('bid', 'int');

// Security check
$socid='';
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 * Actions
 */


/*
 * View
 */

$now=dol_now();

$facturestatic=new Facture($db);
$facturesupplierstatic=new FactureFournisseur($db);

$form = new Form($db);
$formfile = new FormFile($db);
$thirdpartystatic = new Societe($db);

llxHeader("",$langs->trans("AccountancyTreasuryArea"));

print_fiche_titre($langs->trans("AccountancyTreasuryArea"));


print '<div class="fichecenter"><div class="fichethirdleft" style="width: 48%">';//se añade style="width: 48% a<div class="fichethirdleft">


$max=3;


/*
 * Search invoices Buscar una factura a cliente
 */
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	print '<form method="post" action="'.DOL_URL_ROOT.'/compta/facture/list.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td colspan="3">'.$langs->trans("SearchACustomerInvoice").'</td></tr>';
	print "<tr ".$bc[0]."><td>".$langs->trans("Ref").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>';
	print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "<tr ".$bc[0]."><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
	print '</tr>';
	print "</table></form><br>";
}

/*
 * Search supplier invoices Buscar una factura de proveedor
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire)
{
	print '<form method="post" action="'.DOL_URL_ROOT.'/fourn/facture/list.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchASupplierInvoice").'</td></tr>';
	print "<tr ".$bc[0].">";
	print "<td>".$langs->trans("Ref").':</td><td><input type="text" name="search_ref" class="flat" size="18"></td>';
	print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "<tr ".$bc[0]."><td>".$langs->trans("RefSupplier").':</td><td><input type="text" name="search_ref_supplier" class="flat" size="18"></td>';
	print '</tr>';
	print "</table></form><br>";
}
*/

/*
 * Search donations
 */
if (! empty($conf->don->enabled) && $user->rights->don->lire)
{
	$langs->load("donations");
    print '<form method="post" action="'.DOL_URL_ROOT.'/compta/dons/liste.php">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchADonation").'</td></tr>';
    print "<tr ".$bc[0].">";
    print "<td>".$langs->trans("Ref").':</td><td><input type="text" name="search_ref" class="flat" size="18"></td>';
    print '<td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
    //print "<tr ".$bc[0]."><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
    print '</tr>';
    print "</table></form><br>";
}

/*
 * Unpaid customers invoices Facturas a clientes pendientes de cobro
 */
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	$facstatic=new Facture($db);

	$sql = "SELECT f.rowid, f.facnumber, f.fk_statut, f.datef, f.type, f.total, f.total_ttc, f.paye, f.tms,";
	$sql.= " f.date_lim_reglement as datelimite,";
	$sql.= " s.nom, s.rowid as socid,";
	$sql.= " sum(pf.amount) as am";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
	$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND f.fk_soc = ".$socid;
	$sql.= " GROUP BY f.rowid, f.facnumber, f.fk_statut, f.datef, f.type, f.total, f.total_ttc, f.paye, f.tms, f.date_lim_reglement, s.nom, s.rowid";
	$sql.= " ORDER BY f.datef ASC, f.facnumber ASC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$var=false;
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("BillsCustomersUnpaid",$num).' <a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">('.$num.')</a></td>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right">'.$langs->trans("Received").'</td>';
		print '<td width="16">&nbsp;</td>';
		print '</tr>';
		if ($num)
		{
			$societestatic = new Societe($db);
			$total_ttc = $totalam = $total = 0;
			while ($i < $num && $i < $conf->liste_limit)
			{
				$obj = $db->fetch_object($resql);

				print '<tr '.$bc[$var].'>';
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				$facturestatic->ref=$obj->facnumber;
				$facturestatic->id=$obj->rowid;
				$facturestatic->type=$obj->type;
				print $facturestatic->getNomUrl(1,'');
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				if ($db->jdate($obj->datelimite) < ($now - $conf->facture->client->warning_delay)) print img_warning($langs->trans("Late"));
				print '</td>';
				print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
				$filename=dol_sanitizeFileName($obj->facnumber);
				$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->facnumber);
				$urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
				print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';
				print '<td align="left">' ;
                $societestatic->id=$obj->socid;
                $societestatic->nom=$obj->nom;
                $societestatic->client=1;
				print $societestatic->getNomUrl(1,'customer',44);
				print '</a></td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.price($obj->am).'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3,$obj->am).'</td>';
				print '</tr>';

				$total_ttc +=  $obj->total_ttc;
				$total += $obj->total;
				$totalam +=  $obj->am;
				$var=!$var;
				$i++;
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToTake").': '.price($total_ttc-$totalam).')</font> </td>';
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($total).'</td>';
			print '<td align="right">'.price($total_ttc).'</td>';
			print '<td align="right">'.price($totalam).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else
		{
			$colspan=5;
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr '.$bc[$var].'><td colspan="'.$colspan.'">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table><br>';
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

/**
 * Draft customers invoices Facturas a clientes borrador
 */
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	$sql  = "SELECT f.facnumber, f.rowid, f.total_ttc, f.type,";
	$sql.= " s.nom, s.rowid as socid";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut = 0";
	$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

	if ($socid)
	{
		$sql .= " AND f.fk_soc = $socid";
	}

	$resql = $db->query($sql);

	if ( $resql )
	{
		$var = false;
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("CustomersDraftInvoices").($num?' ('.$num.')':'').'</td></tr>';
		if ($num)
		{
			$companystatic=new Societe($db);

			$i = 0;
			$tot_ttc = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td class="nowrap">';
				$facturestatic->ref=$obj->facnumber;
				$facturestatic->id=$obj->rowid;
				$facturestatic->type=$obj->type;
				print $facturestatic->getNomUrl(1,'');
				print '</td>';
				print '<td class="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->nom=$obj->nom;
				$companystatic->client=1;
				print $companystatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td align="right" class="nowrap">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc+=$obj->total_ttc;
				$i++;
				$var=!$var;
			}

			print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" align="right">'.price($tot_ttc).'</td>';
			print '</tr>';
		}
		else
		{
			print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print "</table><br>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}



//Listado de pedidos validados
if ($user->rights->societe->client->voir )
{
	$sql = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
	$sql.= ' c.date_valid, c.date_commande, c.note_private, c.date_livraison, c.fk_statut, c.facture as facturee';
	//$sql = 'SELECT s.nom, s.rowid as socid, c.rowid, c.ref, c.total_ht, c.date_commande, c.fk_statut';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
	$sql.= ', '.MAIN_DB_PREFIX.'commande as c';
	$sql.= ' WHERE c.fk_soc = s.rowid';
	$sql.= ' AND c.fk_statut = 1';

	$resql = $db->query($sql);
	if ($resql)
	{
		
		$total=0;
		$var=false;
		$num = $db->num_rows($resql);
		$i = 0;

		//Aqui se imprime la cabecera de la lista
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ListOfOrders",$num).' - '.$langs->trans('StatusOrderValidatedShort').'</td>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td align="right">'.$langs->trans("OrderDate").'</td>';
		print '<td align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td width="16">&nbsp;</td>';
		print '</tr>';


		$generic_commande = new Commande($db);
		$companystatic = new Societe($db);
		//En el bucle se imprime la lista de pedidos
		while ($i<$num) 
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			$generic_commande->id=$objp->rowid;
			$generic_commande->ref=$objp->ref;
			print '<tr class="nocellnopadd">';
			print '<td class="nobordernopadding nowrap">';
			print '<table class="nobordernopadding">';
			print '<tr class="nocellnopadd">';
			print '<td class="nobordernopadding nowrap">';
			print $generic_commande->getNomUrl(1,($viewstatut != 2?0:$objp->fk_statut));
			print '</td>';
			print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
			$filename=dol_sanitizeFileName($objp->ref);
			$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($objp->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->rowid;
			print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);
			print '</td>';
			print '</tr></table>';

			print '</td>';

			// Company
			$companystatic->id=$objp->socid;
			$companystatic->nom=$objp->nom;
			$companystatic->client=$objp->client;
			print '<td>';
			print $companystatic->getNomUrl(1,'customer');

			// Order date
			print '<td align="right">';
			print dol_print_date($db->jdate($objp->date_commande), 'day');

			// Amount HT
			print '<td align="right" class="nowrap">'.price($objp->total_ht).'</td>';
			print '</td></tr>';


			$total+=$objp->total_ht;
			$i++;
		}

		print '<tr class="liste_total">';
		print '<td class="nowrap" colspan="3">'.$langs->trans('Total').'</td>';
		// Total
		print '<td align="right" class="nowrap">'.price($total).'</td>';
		print '<td class="nowrap">&nbsp;</td>';
		print '</tr>';
		print '</table><br>';
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 * Draft suppliers invoices Facturas de proveedores borrador
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
{
	$sql  = "SELECT f.ref, f.rowid, f.total_ttc, f.type,";
	$sql.= " s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut = 0";
	$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND f.fk_soc = ".$socid;

	$resql = $db->query($sql);

	if ( $resql )
	{
		$var = false;
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("SuppliersDraftInvoices").($num?' ('.$num.')':'').'</td></tr>';
		if ($num)
		{
			$companystatic=new Societe($db);

			$i = 0;
			$tot_ttc = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td class="nowrap">';
				$facturesupplierstatic->ref=$obj->ref;
				$facturesupplierstatic->id=$obj->rowid;
				$facturesupplierstatic->type=$obj->type;
				print $facturesupplierstatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td>';
				$companystatic->id=$obj->socid;
				$companystatic->nom=$obj->nom;
				$companystatic->client=1;
				print $companystatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc+=$obj->total_ttc;
				$i++;
				$var=!$var;
			}

			print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" align="right">'.price($tot_ttc).'</td>';
			print '</tr>';
		}
		else
		{
			print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print "</table><br>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
 */

print '</div><div class="fichetwothirdright" style="width: 51%" ><div class="ficheaddleft" style="width: 100%">'; //se añade style="width: 51%" a <div class="fichetwothirdright"> y style="width: 100%" a <div class="ficheaddleft">

/*
 * Search supplier invoices Buscar una factura de proveedor
 */
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire)
{
	print '<form method="post" action="'.DOL_URL_ROOT.'/fourn/facture/list.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchASupplierInvoice").'</td></tr>';
	print "<tr ".$bc[0].">";
	print "<td>".$langs->trans("Ref").':</td><td><input type="text" name="search_ref" class="flat" size="18"></td>';
	print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "<tr ".$bc[0]."><td>".$langs->trans("RefSupplier").':</td><td><input type="text" name="search_ref_supplier" class="flat" size="18"></td>';
	print '</tr>';
	print "</table></form><br>";
}

/*
// Last modified customer invoices Las 3 últimas facturas a clientes modificadas
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	$langs->load("boxes");
	$facstatic=new Facture($db);

	$sql = "SELECT f.rowid, f.facnumber, f.fk_statut, f.type, f.total, f.total_ttc, f.paye, f.tms,";
	$sql.= " f.date_lim_reglement as datelimite,";
	$sql.= " s.nom, s.rowid as socid,";
	$sql.= " sum(pf.amount) as am";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc";
	$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND f.fk_soc = ".$socid;
	$sql.= " GROUP BY f.rowid, f.facnumber, f.fk_statut, f.type, f.total, f.total_ttc, f.paye, f.tms, f.date_lim_reglement, s.nom, s.rowid";
	$sql.= " ORDER BY f.tms DESC ";
	$sql.= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$var=false;
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("BoxTitleLastCustomerBills",$max).'</td>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
		print '<td width="16">&nbsp;</td>';
		print '</tr>';
		if ($num)
		{
			$total_ttc = $totalam = $total = 0;
			while ($i < $num && $i < $conf->liste_limit)
			{
				$obj = $db->fetch_object($resql);

				print '<tr '.$bc[$var].'>';
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				$facturestatic->ref=$obj->facnumber;
				$facturestatic->id=$obj->rowid;
				$facturestatic->type=$obj->type;
				print $facturestatic->getNomUrl(1,'');
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				if ($obj->fk_statut == 1 && ! $obj->paye && $db->jdate($obj->datelimite) < ($now - $conf->facture->client->warning_delay)) print img_warning($langs->trans("Late"));
				print '</td>';
				print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
				$filename=dol_sanitizeFileName($obj->facnumber);
				$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->facnumber);
				$urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
				print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';
				print '<td align="left">';
				$thirdpartystatic->id=$obj->socid;
				$thirdpartystatic->nom=$obj->nom;
				$thirdpartystatic->client=1;
				print $thirdpartystatic->getNomUrl(1,'customer',44);
				print '</td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.dol_print_date($db->jdate($obj->tms),'day').'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3,$obj->am).'</td>';
				print '</tr>';

				$total_ttc +=  $obj->total_ttc;
				$total += $obj->total;
				$totalam +=  $obj->am;
				$var=!$var;
				$i++;
			}
		}
		else
		{
			$colspan=5;
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr '.$bc[$var].'><td colspan="'.$colspan.'">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table><br>';
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
*/

/*
// Last modified supplier invoices Las 3 últimas facturas de proveedores modificadas
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
{
	$langs->load("boxes");
	$facstatic=new FactureFournisseur($db);

	$sql = "SELECT ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.total_ttc, ff.tms, ff.paye";
	$sql.= ", s.nom, s.rowid as socid";
	$sql.= ", SUM(pf.amount) as am";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = ff.fk_soc";
	$sql.= " AND ff.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql.= " AND ff.fk_soc = ".$socid;
	$sql.= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.total_ttc, ff.tms, ff.paye, s.nom, s.rowid";
	$sql.= " ORDER BY ff.tms DESC ";
	$sql.= $db->plimit($max, 0);

	$resql=$db->query($sql);
	if ($resql)
	{
		$var=false;
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("BoxTitleLastSupplierBills",$max).'</td>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
		print '<td width="16">&nbsp;</td>';
		print "</tr>\n";
		if ($num)
		{
			$i = 0;
			$total = $total_ttc = $totalam = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td>';
				$facstatic->ref=$obj->ref;
				$facstatic->id=$obj->rowid;
				print $facstatic->getNomUrl(1,'');
				print '</td>';
				print '<td>';
				$thirdpartystatic->id=$obj->socid;
				$thirdpartystatic->nom=$obj->nom;
				$thirdpartystatic->fournisseur=1;
				print $thirdpartystatic->getNomUrl(1,'supplier',44);
				print '</td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total_ht).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.dol_print_date($db->jdate($obj->tms),'day').'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3).'</td>';
				print '</tr>';
				$total += $obj->total_ht;
				$total_ttc +=  $obj->total_ttc;
				$totalam +=  $obj->am;
				$i++;
				$var = !$var;
			}
		}
		else
		{
			$colspan=5;
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr '.$bc[$var].'><td colspan="'.$colspan.'">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table><br>';
	}
	else
	{
		dol_print_error($db);
	}
}
*/


// Last donations
if (! empty($conf->don->enabled) && $user->rights->societe->lire)
{
	include_once DOL_DOCUMENT_ROOT.'/compta/dons/class/don.class.php';

	$langs->load("boxes");
    $donationstatic=new Don($db);

	$sql = "SELECT d.rowid, d.lastname, d.firstname, d.societe, d.datedon as date, d.tms as dm, d.amount, d.fk_statut";
	$sql.= " FROM ".MAIN_DB_PREFIX."don as d";
	$sql.= " WHERE d.entity = ".$conf->entity;
	$sql.= $db->order("d.tms","DESC");
	$sql.= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result)
	{
		$var=false;
		$num = $db->num_rows($result);

		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td>'.$langs->trans("BoxTitleLastModifiedDonations",$max).'</td>';
        print '<td class="liste_titre" align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td class="liste_titre" align="right">'.$langs->trans("DateModificationShort").'</td>';
        print '<td class="liste_titre" width="16">&nbsp;</td>';
		print '</tr>';
		if ($num)
		{
			$var = True;
			$total_ttc = $totalam = $total = 0;

			$var=true;
			while ($i < $num && $i < $max)
			{
				$objp = $db->fetch_object($result);
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				$donationstatic->id=$objp->rowid;
				$donationstatic->lastname=$objp->lastname;
				$donationstatic->firstname=$objp->firstname;
				$label=$donationstatic->getFullName($langs);
				if ($objp->societe) $label.=($label?' - ':'').$objp->societe;
				$donationstatic->ref=$label;
				print '<td>'.$donationstatic->getNomUrl(1).'</td>';
                print '<td align="right">'.price($objp->amount).'</td>';
				print '<td align="right">'.dol_print_date($db->jdate($objp->dm),'day').'</td>';
                print '<td>'.$donationstatic->LibStatut($objp->fk_statut,3).'</td>';
				print '</tr>';

				$i++;
			}

		}
		else
		{
			print '<tr '.$bc[$var].'><td colspan="4">'.$langs->trans("None").'</td></tr>';
		}
		print '</table><br>';
	}
	else dol_print_error($db);
}

/*
 * Social contributions to pay Cargas a pagar
if (! empty($conf->tax->enabled) && $user->rights->tax->charges->lire)
{
	if (!$socid)
	{
		$chargestatic=new ChargeSociales($db);

		$sql = "SELECT c.rowid, c.amount, c.date_ech, c.paye,";
		$sql.= " cc.libelle,";
		$sql.= " SUM(pc.amount) as sumpaid";
		$sql.= " FROM (".MAIN_DB_PREFIX."c_chargesociales as cc, ".MAIN_DB_PREFIX."chargesociales as c)";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = c.rowid";
		$sql.= " WHERE c.fk_type = cc.id";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND c.paye = 0";
		$sql.= " GROUP BY c.rowid, c.amount, c.date_ech, c.paye, cc.libelle";

		$resql = $db->query($sql);
		if ( $resql )
		{
			$var = false;
			$num = $db->num_rows($resql);

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("ContributionsToPay").($num?' ('.$num.')':'').'</td>';
			print '<td align="center">'.$langs->trans("DateDue").'</td>';
			print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
			print '<td align="right">'.$langs->trans("Paid").'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			if ($num)
			{
				$i = 0;
				$tot_ttc=0;
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);
					print "<tr ".$bc[$var].">";
					$chargestatic->id=$obj->rowid;
					$chargestatic->ref=$obj->libelle;
					$chargestatic->lib=$obj->libelle;
					$chargestatic->paye=$obj->paye;
					print '<td>'.$chargestatic->getNomUrl(1).'</td>';
					print '<td align="center">'.dol_print_date($obj->date_ech,'day').'</td>';
					print '<td align="right">'.price($obj->amount).'</td>';
					print '<td align="right">'.price($obj->sumpaid).'</td>';
					print '<td align="center">'.$chargestatic->getLibStatut(3).'</td>';
					print '</tr>';
					$tot_ttc+=$obj->amount;
					$var = !$var;
					$i++;
				}

				print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Total").'</td>';
				print '<td align="right">'.price($tot_ttc).'</td>';
				print '<td align="right"></td>';
				print '<td align="right">&nbsp</td>';
				print '</tr>';
			}
			else
			{
				print '<tr '.$bc[$var].'><td colspan="5">'.$langs->trans("None").'</td></tr>';
			}
			print "</table><br>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}
}
*/

/*
 * Customers orders to be billed 
 */
if (! empty($conf->facture->enabled) && ! empty($conf->commande->enabled) && $user->rights->commande->lire)
{
	$commandestatic=new Commande($db);
	$langs->load("orders");

	$sql = "SELECT sum(f.total) as tot_fht, sum(f.total_ttc) as tot_fttc,";
	$sql.= " s.nom, s.rowid as socid,";
	$sql.= " c.rowid, c.ref, c.facture, c.fk_statut, c.total_ht, c.total_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= ", ".MAIN_DB_PREFIX."commande as c";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_source = c.rowid AND el.sourcetype = 'commande'";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON el.fk_target = f.rowid AND el.targettype = 'facture'";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND c.fk_soc = ".$socid;
	$sql.= " AND c.fk_statut = 3";
	$sql.= " AND c.facture = 0";
	$sql.= " GROUP BY s.nom, s.rowid, c.rowid, c.ref, c.facture, c.fk_statut, c.total_ht, c.total_ttc";

	$resql = $db->query($sql);
	if ( $resql )
	{
		$var=false;
		$num = $db->num_rows($resql);

		if ($num)
		{
			$i = 0;
			print '<table class="noborder" width="100%">';
			print "<tr class=\"liste_titre\">";
			print '<td colspan="2">'.$langs->trans("OrdersToBill").' <a href="'.DOL_URL_ROOT.'/commande/liste.php?status=3&afacturer=1">('.$num.')</a></td>';
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
			print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
			print '<td align="right">'.$langs->trans("ToBill").'</td>';
			print '<td align="center" width="16">&nbsp;</td>';
			print '</tr>';
			$tot_ht=$tot_ttc=$tot_tobill=0;
			$societestatic = new Societe($db);
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				print "<tr ".$bc[$var].">";
				print '<td class="nowrap">';

				$commandestatic->id=$obj->rowid;
				$commandestatic->ref=$obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				print $commandestatic->getNomUrl(1);
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';
				print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td align="left">';
                $societestatic->id=$obj->socid;
                $societestatic->nom=$obj->nom;
                $societestatic->client=1;
                print $societestatic->getNomUrl(1,'customer',44);
				print '</a></td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total_ht).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.price($obj->total_ttc-$obj->tot_fttc).'</td>';
				print '<td>'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,3).'</td>';
				print '</tr>';
				$tot_ht += $obj->total_ht;
				$tot_ttc += $obj->total_ttc;
				//print "x".$tot_ttc."z".$obj->tot_fttc;
				$tot_tobill += ($obj->total_ttc-$obj->tot_fttc);
				$i++;
				$var=!$var;
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToBill").': '.price($tot_tobill).')</font> </td>';
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($tot_ht).'</td>';
			print '<td align="right">'.price($tot_ttc).'</td>';
			print '<td align="right">'.price($tot_tobill).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			print '</table><br>';
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Unpaid customers invoices Facturas a clientes pendientes de cobro
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	$facstatic=new Facture($db);

	$sql = "SELECT f.rowid, f.facnumber, f.fk_statut, f.datef, f.type, f.total, f.total_ttc, f.paye, f.tms,";
	$sql.= " f.date_lim_reglement as datelimite,";
	$sql.= " s.nom, s.rowid as socid,";
	$sql.= " sum(pf.amount) as am";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
	$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND f.fk_soc = ".$socid;
	$sql.= " GROUP BY f.rowid, f.facnumber, f.fk_statut, f.datef, f.type, f.total, f.total_ttc, f.paye, f.tms, f.date_lim_reglement, s.nom, s.rowid";
	$sql.= " ORDER BY f.datef ASC, f.facnumber ASC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$var=false;
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("BillsCustomersUnpaid",$num).' <a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">('.$num.')</a></td>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right">'.$langs->trans("Received").'</td>';
		print '<td width="16">&nbsp;</td>';
		print '</tr>';
		if ($num)
		{
			$societestatic = new Societe($db);
			$total_ttc = $totalam = $total = 0;
			while ($i < $num && $i < $conf->liste_limit)
			{
				$obj = $db->fetch_object($resql);

				print '<tr '.$bc[$var].'>';
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				$facturestatic->ref=$obj->facnumber;
				$facturestatic->id=$obj->rowid;
				$facturestatic->type=$obj->type;
				print $facturestatic->getNomUrl(1,'');
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				if ($db->jdate($obj->datelimite) < ($now - $conf->facture->client->warning_delay)) print img_warning($langs->trans("Late"));
				print '</td>';
				print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
				$filename=dol_sanitizeFileName($obj->facnumber);
				$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->facnumber);
				$urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
				print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';
				print '<td align="left">' ;
                $societestatic->id=$obj->socid;
                $societestatic->nom=$obj->nom;
                $societestatic->client=1;
				print $societestatic->getNomUrl(1,'customer',44);
				print '</a></td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.price($obj->am).'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3,$obj->am).'</td>';
				print '</tr>';

				$total_ttc +=  $obj->total_ttc;
				$total += $obj->total;
				$totalam +=  $obj->am;
				$var=!$var;
				$i++;
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToTake").': '.price($total_ttc-$totalam).')</font> </td>';
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($total).'</td>';
			print '<td align="right">'.price($total_ttc).'</td>';
			print '<td align="right">'.price($totalam).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else
		{
			$colspan=5;
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr '.$bc[$var].'><td colspan="'.$colspan.'">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table><br>';
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
 */


/*
 * Unpayed supplier invoices Facturas de proveedores pendientes de pago
 */
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
{
	$facstatic=new FactureFournisseur($db);

	$sql = "SELECT ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.total_ttc, ff.paye,";
	$sql.= " s.nom, s.rowid as socid,";
	$sql.= " sum(pf.amount) as am";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = ff.fk_soc";
	$sql.= " AND ff.entity = ".$conf->entity;
	$sql.= " AND ff.paye = 0";
	$sql.= " AND ff.fk_statut = 1";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql.= " AND ff.fk_soc = ".$socid;
	$sql.= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.total_ttc, ff.paye,";
	$sql.= " s.nom, s.rowid";

	$resql=$db->query($sql);
	if ($resql)
	{
		$var=false;
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("BillsSuppliersUnpaid",$num).' <a href="'.DOL_URL_ROOT.'/fourn/facture/impayees.php">('.$num.')</a></td>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right">'.$langs->trans("Paid").'</td>';
		print '<td width="16">&nbsp;</td>';
		print "</tr>\n";
		$societestatic = new Societe($db);
		if ($num)
		{
			$i = 0;
			$total = $total_ttc = $totalam = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				print '<tr '.$bc[$var].'><td>';
				$facstatic->ref=$obj->ref;
				$facstatic->id=$obj->rowid;
				print $facstatic->getNomUrl(1,'');
				print '</td>';
                $societestatic->id=$obj->socid;
                $societestatic->nom=$obj->nom;
                $societestatic->client=0;
				print '<td>'.$societestatic->getNomUrl(1, 'supplier', 44).'</td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total_ht).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.price($obj->am).'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3).'</td>';
				print '</tr>';
				$total += $obj->total_ht;
				$total_ttc +=  $obj->total_ttc;
				$totalam +=  $obj->am;
				$i++;
				$var = !$var;
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToPay").': '.price($total_ttc-$totalam).')</font> </td>';
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($total).'</td>';
			print '<td align="right">'.price($total_ttc).'</td>';
			print '<td align="right">'.price($totalam).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else
		{
			$colspan=5;
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr '.$bc[$var].'><td colspan="'.$colspan.'">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table><br>';
	}
	else
	{
		dol_print_error($db);
	}
}

/**
 * Draft suppliers invoices Facturas de proveedores borrador
 */
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
{
	$sql  = "SELECT f.ref, f.rowid, f.total_ttc, f.type,";
	$sql.= " s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut = 0";
	$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND f.fk_soc = ".$socid;

	$resql = $db->query($sql);

	if ( $resql )
	{
		$var = false;
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("SuppliersDraftInvoices").($num?' ('.$num.')':'').'</td></tr>';
		if ($num)
		{
			$companystatic=new Societe($db);

			$i = 0;
			$tot_ttc = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td class="nowrap">';
				$facturesupplierstatic->ref=$obj->ref;
				$facturesupplierstatic->id=$obj->rowid;
				$facturesupplierstatic->type=$obj->type;
				print $facturesupplierstatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td>';
				$companystatic->id=$obj->socid;
				$companystatic->nom=$obj->nom;
				$companystatic->client=1;
				print $companystatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc+=$obj->total_ttc;
				$i++;
				$var=!$var;
			}

			print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" align="right">'.price($tot_ttc).'</td>';
			print '</tr>';
		}
		else
		{
			print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print "</table><br>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

//Listado de pedidos a proveedor aprobados
if ($user->rights->societe->client->voir) 
{
	$sql = "SELECT s.rowid as socid, s.nom, cf.date_commande as dc,";
	$sql.= " cf.rowid,cf.ref, cf.ref_supplier, cf.fk_statut, cf.total_ttc, cf.fk_user_author";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ";
	$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur as cf";
	$sql.= " WHERE cf.fk_soc = s.rowid ";
	$sql.= " AND cf.fk_statut = 2";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total=0;
		$var=false;
		$num = $db->num_rows($resql);
		$i = 0;

		//Aqui se imprime la cabecera de la lista
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("SuppliersOrders",$num).' - '.$langs->trans('StatusOrderApprovedShort').'</td>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
		//print '<td align="right">'.'</td>';
		print '<td align="right" colspan="2">'.$langs->trans("AmountTTC").'</td>';
		print '<td width="16">&nbsp;</td>';
		print '</tr>';

		$objectstatic=new CommandeFournisseur($db);
		$companystatic = new Societe($db);
		while ($i<$num) 
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;

			print "<tr ".$bc[$var].">";

			// Ref
			print '<td><a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a>';
			$filename=dol_sanitizeFileName($obj->ref);
			$filedir=$conf->fournisseur->dir_output.'/commande' . '/' . dol_sanitizeFileName($obj->ref);
			print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			print '</td>'."\n";

			// Company
			print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' ';
			print $obj->nom.'</a></td>'."\n";

			// Amount
			print '<td align="right" width="100" colspan="2">'.price($obj->total_ttc)."</td>";

			$total+=$obj->total_ttc;
			$i++;
		}

		print '<tr class="liste_total">';
		print '<td class="nowrap" colspan="3">'.$langs->trans('Total').'</td>';
		// Total
		print '<td align="right" class="nowrap">'.price($total).'</td>';
		print '<td class="nowrap">&nbsp;</td>';
		print '</tr>';
		print '</table><br>';
		$db->free($resql);		
	}
	else
	{
		dol_print_error($db);
	}
}


/**
 * Social contributions to pay Cargas a pagar
 */
if (! empty($conf->tax->enabled) && $user->rights->tax->charges->lire)
{
	if (!$socid)
	{
		$chargestatic=new ChargeSociales($db);

		$sql = "SELECT c.rowid, c.amount, c.date_ech, c.paye,";
		$sql.= " cc.libelle,";
		$sql.= " SUM(pc.amount) as sumpaid";
		$sql.= " FROM (".MAIN_DB_PREFIX."c_chargesociales as cc, ".MAIN_DB_PREFIX."chargesociales as c)";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = c.rowid";
		$sql.= " WHERE c.fk_type = cc.id";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND c.paye = 0";
		$sql.= " GROUP BY c.rowid, c.amount, c.date_ech, c.paye, cc.libelle";

		$resql = $db->query($sql);
		if ( $resql )
		{
			$var = false;
			$num = $db->num_rows($resql);

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("ContributionsToPay").($num?' ('.$num.')':'').'</td>';
			print '<td align="center">'.$langs->trans("DateDue").'</td>';
			print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
			print '<td align="right">'.$langs->trans("Paid").'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			if ($num)
			{
				$i = 0;
				$tot_ttc=0;
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);
					print "<tr ".$bc[$var].">";
					$chargestatic->id=$obj->rowid;
					$chargestatic->ref=$obj->libelle;
					$chargestatic->lib=$obj->libelle;
					$chargestatic->paye=$obj->paye;
					print '<td>'.$chargestatic->getNomUrl(1).'</td>';
					print '<td align="center">'.dol_print_date($obj->date_ech,'day').'</td>';
					print '<td align="right">'.price($obj->amount).'</td>';
					print '<td align="right">'.price($obj->sumpaid).'</td>';
					print '<td align="center">'.$chargestatic->getLibStatut(3).'</td>';
					print '</tr>';
					$tot_ttc+=$obj->amount;
					$var = !$var;
					$i++;
				}

				print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Total").'</td>';
				print '<td align="right">'.price($tot_ttc).'</td>';
				print '<td align="right"></td>';
				print '<td align="right">&nbsp</td>';
				print '</tr>';
			}
			else
			{
				print '<tr '.$bc[$var].'><td colspan="5">'.$langs->trans("None").'</td></tr>';
			}
			print "</table><br>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}
}

// TODO Mettre ici recup des actions en rapport avec la compta
$resql = 0;
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("TasksToDo").'</td>';
	print "</tr>\n";
	$var = True;
	$i = 0;
	while ($i < $db->num_rows($resql))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;

		print "<tr ".$bc[$var]."><td>".dol_print_date($obj->da,"day")."</td>";
		print "<td><a href=\"action/fiche.php\">$obj->libelle $obj->label</a></td></tr>";
		$i++;
	}
	$db->free($resql);
	print "</table><br>";
}


print '</div></div></div>';


llxFooter();

$db->close();