<?php
/* Copyright (C) 2014      Ferran Marcet <fmarcet@2byte.es>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/pos/class/actions_expenses.class.php
 *	\ingroup    expenses
 *	\brief      File Class expenses
 */

require 'massorders.class.php';

/**
 *	\class      ActionsExpenses
 *	\brief      Class Actions of the module expenses
 */
class ActionsMassorders
{
	var $db;
	var $dao;

	var $mesg;
	var $error;
	var $errors=array();
	//! Numero de l'erreur
	var $errno = 0;

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Instantiation of DAO class
	 *
	 * @return	void
	 */
	private function getInstanceDao()
	{
		if (! is_object($this->dao))
		{
			$this->dao = new Massorders($this->db);
		}
	}

	/**
	 * 	Enter description here ...
	 *
	 * 	@param	string	$action		Action type
	 */
	function printObjectLine($parameters, &$object, &$action='', $hook)
	{
		global $conf,$user,$langs, $bcdd;
		
		$langs->load("massorders@massorders");
		if($parameters['line']->special_code == 4){
		
			$tpl = dol_buildpath('/massorders/tpl/invoices.tpl.php');
			
			$res=@include $tpl;
			
			return 1;
		}
		return 0;

	}
}
?>