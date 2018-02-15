<?php
/**
* 2010-2017 EcomiZ
*
*  @author	EcomiZ
*  @copyright 2010-2017
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  @version  Release: 1.0.0 $Revision: 1 $
*/

class ReapproModel extends ObjectModel
{
	public $id_reappro;
	public $date_add;
	public $date_upd;
	public $status;
	public $id_product;
	public $quantity;

	public static $definition = array(
		'table' => 'reappro',
		'primary' => 'id_reappro',
		'multilang' => false,
		'fields' => array(
			'id_reappro' => array(
				'type' => ObjectModel::TYPE_INT
			),
			'date_add' => array(
				'type' => ObjectModel::TYPE_DATE,
				'required' => true
			),
			'date_upd' => array(
				'type' => ObjectModel::TYPE_DATE,
				'required' => true
			),
			'status' => array(
				'type' => ObjectModel::TYPE_INT,
				'required' => true
			),
			'id_product' => array(
				'type' => ObjectModel::TYPE_INT,
				'required' => true
			),
			'quantity' => array(
				'type' => ObjectModel::TYPE_INT,
				'required' => true
			)
		)
	);

	/*
	 * getReappro count not yet received
	 * 
	 */

	public static function getReappro(){
		
		$count = Db::getInstance()->getValue("SELECT count(*) FROM "._DB_PREFIX_."reappro WHERE status != 1");

		return $count;
	}
}
