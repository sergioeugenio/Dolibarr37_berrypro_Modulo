<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2012	   Andreu Bisquerra Gaya<jove@bisquerra.com>
 * Copyright (C) 2012	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013	   Ferran Marcet		<fmarcet@2byte.es>
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

/**
 *      \file       orderstoinvoice/core/modules/modOrderstoinvoice.class.php
 *      \ingroup    mymodule
 *      \brief      Description and activation file for module Orderstoinvoice
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * 		\class      modOrderstoinvoice
 *      \brief      Description and activation class for module MyModule
 */
class modBerrypro extends DolibarrModules
{
	/**
	 *   \brief      Constructor. Define names, constants, directories, boxes, permissions
	 *   \param      DB      Database handler
	 */
	function modBerrypro($DB)
	{
        global $langs,$conf;

        $this->db = $DB;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 400020;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'Berrypro';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "BerryPro";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '3.6';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 3;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='bill';

		$this->editor_name = "<b>berrypro.eu</b>";
		$this->editor_web = "www.berrypro.eu";

		// Defined if the directory /mymodule/includes/triggers/ contains triggers or not
		$this->triggers = 0;

		$this->module_parts = array('hooks' => array('invoicecard'));

		// Data directories to create when module is enabled.
		$this->dirs = array();
		$r=0;

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("berrypro.php@berrypro");

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,7);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("berrypro@berrypro");

		// Constants
		$this->const = array();

		// Array to add new pages in new tabs
		$this->tabs = array(
			'product:-subproduct',
			'product:+ProdComp:CardProduct2:@berrypro:Prueba2:/berrypro/card.php?id=__ID__'
		);

        // Dictionnaries
        $this->dictionaries=array();

        // Boxes
		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		//Menu left into products
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=products',
				'type'=>'left',
				'titre'=>'Berrypro',
				'mainmenu'=>'products',
				'leftmenu'=>'berrypro',
				'url'=>'/berrypro/index.php',
				'langs'=>'berrypro@berrypro',
				'position'=>90,
				'enabled'=>'$conf->berrypro->enabled',
				'perms'=>'1',
				'target'=>'',
				'user'=>0);

		$r++; //1

		//Menu left into compta
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=accountancy',
				'type'=>'left',
				'titre'=>'Berrypro',
				'mainmenu'=>'accountancy',
				'leftmenu'=>'berrypro',
				'url'=>'/berrypro/index.php',
				'langs'=>'berrypro@berrypro',
				'position'=>100,
				'enabled'=>'$conf->berrypro->enabled',
				'perms'=>'1',
				'target'=>'',
				'user'=>0);

		$r++; //1

		//Menu left into commercial
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=commercial',
				'type'=>'left',
				'titre'=>'Berrypro',
				'mainmenu'=>'commercial',
				'leftmenu'=>'berrypro',
				'url'=>'/berrypro/index.php',
				'langs'=>'berrypro@berrypro',
				'position'=>110,
				'enabled'=>'$conf->berrypro->enabled',
				'perms'=>'1',
				'target'=>'',
				'user'=>0);

		$r++; //1

		// Elemto de menú izqdo en Financiera para el listado de Fras Pte de Cte vs Proov
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=accountancy,fk_leftmenu=berrypro',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
				'type'=>'left',			// This is a Left menu entry
				'titre'=>'Fras Pte Cte vs Prov',
				'mainmenu'=>'accountancy',
				'url'=>'/berrypro/facturepte.php',
				'langs'=>'berrypro@berrypro',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>102,
				'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
				'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++; //2

		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=accountancy,fk_leftmenu=berrypro',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
				'type'=>'left',			// This is a Left menu entry
				'titre'=>'Facturas Proveedor Ptes',
				'mainmenu'=>'accountancy',
				'url'=>'/berrypro/list_fact_imp.php',
				'langs'=>'berrypro@berrypro',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>104,
				'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
				'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++; //2


		// Elemento de menú izqdo en Comercial para el listado de Presupuestos con Nota Pública
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=berrypro',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
				'type'=>'left',			// This is a Left menu entry
				'titre'=>'Presupuestos con NotPub',
				'mainmenu'=>'commercial',
				'url'=>'/berrypro/list_prod_notpub.php',
				'langs'=>'berrypro',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>112,
				'enabled'=>'$conf->commande->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
				'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++; //3

		// Elemento de menú izqdo en Comercial para el listado de Facturas con Nota Pública
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=berrypro',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
				'type'=>'left',			// This is a Left menu entry
				'titre'=>'Facturas con NotPub',
				'mainmenu'=>'commercial',
				'url'=>'/berrypro/facture_prov_notpub.php',
				'langs'=>'berrypro@berrypro',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>114,
				'enabled'=>'$conf->expedition->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
				'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++; //4

		// Elemento del menú izqdo en Productos para mostrar el listado de productos con:
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=berrypro',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
				'type'=>'left',			// This is a Left menu entry
				'titre'=>'Loquesea',
				'mainmenu'=>'products',
				'url'=>'/berrypro/supplierorders.php',
				'langs'=>'berrypro@berrypro',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>92,
				'enabled'=>'$conf->fournisseur->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
				'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++; //5
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories.
	 *      @return     int             1 if OK, 0 if KO
	 */
	function init()
	{
		$sql = array();

		$result=$this->load_tables();

		return $this->_init($sql);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted.
	 *      @return     int             1 if OK, 0 if KO
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}


	/**
	 *		\brief		Create tables, keys and data required by module
	 * 					Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 					and create data commands must be stored in directory /mymodule/sql/
	 *					This function is called by this->init.
	 * 		\return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/berrypro/sql/');
	}
}

?>
