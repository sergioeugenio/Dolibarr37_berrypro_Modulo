<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *		\file       htdocs/custom/berrypro/boxes/box_commandes_conf.php
 *		\filebase       htdocs/core/boxes/box_commandes.php
 *		\ingroup    commande
 *		\brief      Modulo para generar el panel de Listado de Pedidos de clientes confirmados o en expedicion pendiente de facturar
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last orders confirmados o en expedición
 */
class box_commandes_conf extends ModeleBoxes
{
    var $boxcode="lastcustomerorders";
    var $boximg="object_order";
    var $boxlabel="BP-Ultimos Pedidos de clientes \"confirmados\" o \"en expedicion\"";//Título en la casilla de selección desplegable
    var $depends = array("commande");

	  var $db;
	  var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *  Load data for box to show them later
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
     */
    function loadBox($max=20)
    {
        global $user, $langs, $db, $conf;

		    $this->max=$max;

		    include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
        $commandestatic=new Commande($db);
        $userstatic = new User($db);

        $url2 = DOL_URL_ROOT."/custom/berrypro/list_commande.php?";

        $this->info_box_head = array (//Título del panel
        'text' => $langs->trans("LastModifiedCustomersOrders", $max).
        ' - <a href="'.$url2.'" class="bold"> Ver m&aacute;s...</a>'
        );

        if ($user->rights->commande->lire)
        {
            $sql = "SELECT s.nom as name, s.rowid as socid,";
            $sql.= " c.ref, c.tms, c.rowid, c.ref_client, c.date_commande, c.date_livraison,";
            $sql.= " c.fk_statut, c.fk_user_valid, c.facture, c.total_ht";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
            $sql.= ", ".MAIN_DB_PREFIX."commande as c";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE c.fk_soc = s.rowid";
            $sql.= " AND c.entity = ".$conf->entity;
            $sql.= ' AND c.fk_statut IN (1,2,3)';
            $sql.= ' AND c.facture = 0'; // need to create invoice
            if (! empty($conf->global->ORDER_BOX_LAST_ORDERS_VALIDATED_ONLY)) $sql.=" AND c.fk_statut = 1";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
            if ($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE) $sql.= " ORDER BY c.date_commande ASC, c.ref DESC ";
            else $sql.= " ORDER BY c.tms DESC, c.ref DESC ";
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

                    $this->info_box_contents[$i][] = array('td' => 'align="left" width="16"',
                    'logo' => $this->boximg,
                    'url' => DOL_URL_ROOT."/commande/card.php?id=".$objp->rowid);

                    $this->info_box_contents[$i][] = array('td' => 'align="left"',
                    'text' => $objp->ref,
                    'url' => DOL_URL_ROOT."/commande/card.php?id=".$objp->rowid);

					          $this->info_box_contents[$i][] = array('td' => 'align="left" width="16"',
                    'logo' => 'company',
                    'url' => DOL_URL_ROOT."/comm/card.php?socid=".$objp->socid);

					          $this->info_box_contents[$i][] = array('td' => 'align="left"',
                    'text' => $objp->name,
                    'url' => DOL_URL_ROOT."/comm/card.php?socid=".$objp->socid);

					/*$this->info_box_contents[$i][] = array('td' => 'align="right"',
                    'text' => price($objp->total_ht),
					);*/

					if (! empty($conf->global->ORDER_BOX_LAST_ORDERS_SHOW_VALIDATE_USER))
					{
						if ($objp->fk_user_valid > 0) $userstatic->fetch($objp->fk_user_valid);
						$this->info_box_contents[$i][] = array('td' => 'align="right"',
    	                'text' => (($objp->fk_user_valid > 0)?$userstatic->getNomUrl(1):''),
						'url' => (($objp->fk_user_valid > 0)?DOL_URL_ROOT.'/user/card.php?id='.$objp->fk_user_valid:'')
						);
					}

                    $this->info_box_contents[$i][] = array('td' => 'align="left"',
                    'text' => $objp->ref_client,
                    );

					          $this->info_box_contents[$i][] = array('td' => 'align="right"',
                    'text' => dol_print_date($date,'day'),
                    );
                    $this->info_box_contents[$i][] = array('td' => 'align="right"',
                    'text' => dol_print_date($datel,'day'),
                    );

          $this->info_box_contents[$i][] = array('td' => 'align="right" width="18"',
                    'text' => $commandestatic->LibStatut($objp->fk_statut,$objp->facture,3));

                    $i++;
                }

          if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedOrders"));

				$db->free($result);
            }
            else {
                $this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
            }
        }
        else {
            $this->info_box_contents[0][0] = array('align' => 'left',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
        }
    }

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
    function showBox($head = null, $contents = null)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}
