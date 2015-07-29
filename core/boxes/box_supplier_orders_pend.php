<?php

/* Copyright (C) 2004-2006 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 * \file       htdocs/custom/berrypro/core/boxes/box_supplier_orders_pend.php
 * \filebase       htdocs/core/boxes/box_supplier_orders.php
 * \ingroup    fournisseurs
 * \brief      Module that generates the latest supplier orders box
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class that manages the box showing latest supplier orders
 */
class box_supplier_orders_pend extends ModeleBoxes
{

    var $boxcode = "latestsupplierorderspends";
    var $boximg = "object_order";
    var $boxlabel="BoxLatestSupplierOrdersPtes";
    var $depends = array("fournisseur");

    var $db;
    var $param;
    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *  Load data into info_box_contents array to show array later.
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
     */
    function loadBox($max = 10)
    {
        global $conf, $user, $langs, $db;
        $langs->load("berrypro");

        $this->max = $max;

        include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
        $supplierorderstatic=new CommandeFournisseur($db);

        $this->info_box_head = array('text' => "Pedidos a Proveedor pendientes de recibir"); //$langs->trans("BoxTitleLatest".($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE?"":"Modified")."SupplierOrders", $max));

        if ($user->rights->fournisseur->commande->lire)
        {
            $sql = "SELECT s.nom as name, s.rowid as socid,";
            $sql.= " c.ref, c.tms, c.rowid, c.date_commande, c.date_livraison,";
            $sql.= " c.fk_statut";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
            $sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as c";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE c.fk_soc = s.rowid";
            $sql.= " AND c.entity = ".$conf->entity;
            $sql.= " AND c.fk_statut IN (3,4)";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
            if ($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE) $sql.= " ORDER BY c.date_livraison DESC, c.ref DESC ";
            else $sql.= " ORDER BY c.date_livraison ASC, c.ref DESC ";
            $sql.= $db->plimit($max, 0);

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);

                $i = 0;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
                    $date=$db->jdate($objp->date_commande);
                    $datel=$db->jdate($objp->date_livraison);
					          $datem=$db->jdate($objp->tms);

                    $urlo = DOL_URL_ROOT."/fourn/commande/card.php?id=".$objp->rowid;
                    $urls = DOL_URL_ROOT."/fourn/card.php?socid=".$objp->socid;

                    $this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => $this->boximg,
                    'url' => $urlo);

                    $this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $objp->ref,
                    'url' => $urlo);

                    $this->info_box_contents[$i][2] = array('td' => 'align="left" width="16"',
                    'logo' => 'company',
                    'url' => $urls);

                    $this->info_box_contents[$i][3] = array('td' => 'align="left"',
                    'text' => $objp->name,
                    'url' => $urls);

                    $this->info_box_contents[$i][4] = array('td' => 'align="right"',
                    'text' => dol_print_date($date,'day'),
                    );

                    if (! dol_print_date($datel,'day'))
                      $this->info_box_contents[$i][5] = array('td' => 'align="right"',
                      'text' => "No Indicada",);
                    else
                      $this->info_box_contents[$i][5] = array('td' => 'align="right"',
                      'text' => dol_print_date($datel,'day'),);

                    $this->info_box_contents[$i][6] = array('td' => 'align="right" width="18"',
                    'text' => $supplierorderstatic->LibStatut($objp->fk_statut,3));

                    $i++;
                }

                if ($num == 0)
                    $this->info_box_contents[$i][0] = array('td' => 'align="center"', 'text' => $langs->trans("NoSupplierOrder"));

				$db->free($result);
            }
            else
            {
                $this->info_box_contents[0][0] = array( 'td' => 'align="left"',
                                                        'maxlength'=>500,
                                                        'text' => ($db->error().' sql='.$sql));
            }
        }
        else
        {
            $this->info_box_contents[0][0] = array('td' => 'align="left"',
                'text' => $langs->trans("ReadPermissionNotAllowed"));
        }
    }

    /**
     * 	Method to show box
     *
     * 	@param	array	$head       Array with properties of box title
     * 	@param  array	$contents   Array with properties of box lines
     * 	@return	void
     */
    function showBox($head = null, $contents = null)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}