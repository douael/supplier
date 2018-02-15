<?php
/**
* 2010-2017 EcomiZ
*
*  @author	EcomiZ
*  @copyright 2010-2017
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  @version  Release: 1.0.0 $Revision: 1 $
*/

class AdminReapproController extends ModuleAdminController
{
	public function __construct()
	{
		$prefix = _DB_PREFIX_;
		$sql = "
			CREATE TABLE IF NOT EXISTS `${prefix}reappro` (
			`id_reappro` int(10) NOT NULL AUTO_INCREMENT,
			`date_add` DATETIME NOT NULL,
			`date_upd` DATETIME NOT NULL,
			`status` int(10) UNSIGNED NOT NULL,
			`id_product` int(10) UNSIGNED NOT NULL,
			`quantity` int(10) UNSIGNED NOT NULL,
			PRIMARY KEY (`id_reappro`),
			FOREIGN KEY (`id_product`) REFERENCES `ps_product` (`id_product`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			";
		Db::getInstance()->Execute($sql);

		$this->bootstrap = true;
		$this->table = 'reappro';
		$this->className = 'ReapproModel';
		$this->identifier = 'id_reappro';
		$this->lang = false;
		$this->addRowAction('edit');
		$this->addRowAction('delete');
		$this->bulk_actions = array(
			'delete' => array(
				'text' => $this->l('Delete selected'),
				'icon' => 'icon-trash',
				'confirm' => $this->l('Delete selected items?')
			)
		);
		$this->_select = "id_product as id_product2, DATE_FORMAT(date_add, '%d/%m/%Y %H:%i:%s') as date_add, DATE_FORMAT(date_upd, '%d/%m/%Y %H:%i:%s') as date_upd";
		$this->context = Context::getContext();

		parent::__construct();

		$this->fields_list = array(
			'id_reappro' => array(
				'title' => 'Id',
				'align' => 'center',
				'width' => 30,
			),
			'date_add' => array(
				'title' => 'Date d\'ajout',
				'align' => 'left',
			),
			'date_upd' => array(
				'title' => 'Date de mise à jour',
				'align' => 'left',
			),
			'status' => array(
				'title' => 'Status ',
				'align' => 'center',
				'callback' => "getStatus"
			),
			'id_product2' => array(
				'title' => 'Id produit',
				'align' => 'center',
				'width' => 30,
			),
			'id_product' => array(
				'title' => 'Produit',
				'align' => 'left',
				'callback' => "getProductName"
			),
			'quantity' => array(
				'title' => 'Quantité',
				'align' => 'center',
				'width' => 30,
				'callback' => "getQtt"
			)
		);
	}

	/*
	 * callback : getStatus
	 */

	public function getStatus($a)
	{
		switch ($a) {
			case 0: 
				$return = "<span class='label label-info'>En attente de réception</span>";
				break;
			case 1: 
				$return = "<span class='label label-success'>Reçue</span>";
				break;
			case 2: 
				$return = "Annulée";
				break;
		}
		return $return;
	}
	
	/*
	 * callback : getQtt style
	 */

	public function getQtt($a)
	{
		return "<span class='badge badge-warning'>$a</span>";
	}

	/*
	 * callback : display supplier name
	 */

	public function getProductName($a)
	{
		$product = new Product($a, 1, 1);
		return $product->name." ".$product->reference;
	}
	
	

	public function renderForm()
	{
		$this->context->controller->addJS(_MODULE_DIR_.'/featuressupplier/views/js/ecocategoryaccessories.js' );
		$this->context->controller->addJqueryPlugin('autocomplete');
		$prefix = _DB_PREFIX_;

		$aStatus_upd = array();
		array_push($aStatus_upd, array('value' => 0, 'name' => "En attente de réception"));
		array_push($aStatus_upd, array('value' => 1, 'name' => "Reçue"));
		//array_push($aStatus_upd, array('value' => 2, 'name' => "Annulée"));
		
		$aStatus_add = array();
		array_push($aStatus_add, array('value' => 0, 'name' => "En attente de réception"));

		$reappro = new ReapproModel(Tools::getValue('id_reappro'));
		
		// if order already in stock 
		if($reappro->status == 1){
			$this->confirmations[] = "Cette commande de réapprovisionnement a déjà été reçue et ne peut plus être modifiée. Vous pouvez gérer les stocks d'un produits directement dans la fiche produit onglet Quantités.";
			if(Tools::getValue("id_reappro") != ""){
				$this->fields_form = array(
					'legend' => array(
						'title' => $this->module->l('Réappro')
					),
					'input' => array(
						array(
							'type' => 'text',
							'label' => $this->module->l('Id produit'),
							'name' => 'id_product',
							'class' => 'ac_input',
							'size' => 30,
							'maxlength' => 32,
							'required' => true,
							'disabled' => true
						),
						array(
							'type' => 'text',
							'label' => $this->module->l('Quantité'),
							'name' => 'quantity',
							'class' => 'ac_input',
							'size' => 30,
							'maxlength' => 32,
							'required' => true,
							'disabled' => true
						),
					)
				);
			}
		}else{
			// if reappro already exist, hide input product
			if(Tools::getValue("id_reappro") != ""){
				$this->fields_form = array(
					'legend' => array(
						'title' => $this->module->l('Réappro')
					),
					'input' => array(
						array(
							'type' => 'text',
							'label' => $this->module->l('Id produit'),
							'name' => 'id_product',
							'class' => 'ac_input',
							'size' => 30,
							'maxlength' => 32,
							'required' => true,
							'disabled' => true
						),
						array(
							'type' => 'text',
							'label' => $this->module->l('Quantité'),
							'name' => 'quantity',
							'class' => 'ac_input',
							'size' => 30,
							'maxlength' => 32,
							'required' => true
						),
						array(
							'desc' => "En choisissant le statut 'Reçue', vous validez la quantité reçue indiquée ci-dessus, et elle sera automatiquement ajoutée au stock produit de la boutique à l'enregistrement. Une commande reçue ne peut plus être modifiée.",
							'type' => 'select',
							'label' => $this->l('Statut'),
							'name' => 'status',
							'class' => 'ac_input',
							'size' => 2,
							'required' => true,
							'options' => array(
								'query' => $aStatus_upd,
								'id' => 'value',
								'name' => 'name'),
						),
					),
					'submit' => array(
						'title' => $this->module->l('Save'),
						'name' => 'submitArticle'
					)
				);
			}else{
				// if new reappro
				$this->fields_form = array(
					'legend' => array(
						'title' => $this->module->l('Réappro')
					),
					'input' => array(
						/*array(
							'type' => 'text',
							'label' => $this->module->l('Id produit'),
							'name' => 'id_product',
							'class' => 'ac_input',
							'size' => 30,
							'maxlength' => 32,
							'required' => true
						),*/
						array(
							'type' => 'autocomplete',
							'class' => 'autocomp',
							'label' => $this->l('Quel produit :'),
							'name' => 'list_product',
							'required' => true,
						),
						array(
							'type' => 'hidden',
							'id' => 'nameMyAssociations',
							'name' => 'nameMyAssociations',
							'required' => true,
						),
						array(
							'type' => 'hidden',
							'id' => 'id_product',
							'name' => 'id_product',
							'required' => true,
						),
						array(
							'type' => 'text',
							'label' => $this->module->l('Quantité'),
							'name' => 'quantity',
							'class' => 'ac_input',
							'size' => 30,
							'maxlength' => 32,
							'required' => true
						),
						array(
							'type' => 'select',
							'label' => $this->l('Statut'),
							'name' => 'status',
							'class' => 'ac_input',
							'size' => 10,
							'required' => true,
							'options' => array(
								'query' => $aStatus_add,
								'id' => 'value',
								'name' => 'name'),
						),
					),
					'submit' => array(
						'title' => $this->module->l('Save'),
						'name' => 'submitArticle'
					)
				);
			}
		}
		return parent::renderForm();
	}

	public function processSave()
	{
		// update
		if (Tools::isSubmit('submitArticle') && (Tools::getValue('id_reappro'))) {
			$current_date = date("Y-m-d H:i:s");
			$reappro = new ReapproModel(Tools::getValue('id_reappro'));
			$reappro->date_upd = $current_date;
			$reappro->status = Tools::getValue('status');
			$reappro->quantity = Tools::getValue('quantity');
			$reappro->save();

			if(Tools::getValue('status') == 1){
				// reappro updated to received
				// we add virtual stock to shop stock
				StockAvailable::updateQuantity($reappro->id_product, "", Tools::getValue('quantity'));
			}
		}

		// insertion
		if (Tools::isSubmit('submitArticle') && !(Tools::getValue('id_reappro'))) {
			
			$current_date = date("Y-m-d H:i:s");
			$reappro = new ReapproModel();
			$reappro->date_add = $current_date;
			$reappro->date_upd = $current_date;
			$reappro->status = Tools::getValue('status');
			$reappro->id_product = Tools::getValue('id_product');
			$reappro->quantity = Tools::getValue('quantity');
			$reappro->save();
		}
	}

	/*
	 * render Kpis, display block with infos before table
	 * display a warning if there are waiting reappro
	 * display success message if 100% reappro received
	 */

	public function renderKpis()
	{
		$count=0;
		$count = ReapproModel::getReappro();

		$kpis = array();
		$helper = new HelperKpi();
		$helper->id = 'box-products-stock';

		if($count == 0){
			$helper->icon = 'icon-check';
			$helper->color = 'color4';
			$helper->title = $this->l('Toutes les commandes de réapprovisionnement ont été reçues.', null, null, false);
			$helper->value = "100%";
		}else{
			$helper->icon = 'icon-time';
			$helper->color = 'color1';
			$helper->title = $this->l('Nombre de commandes de réapprovisionnement en attente de réception :', null, null, false);
			$helper->value = $count;
		}

		$helper->source = "";
		$helper->tooltip = "";
		$helper->refresh = 0;
		$helper->href = "";
		$kpis[] = $helper->generate();
		
		 $helper = new HelperKpiRow();
		$helper->kpis = $kpis;
		return $helper->generate();
	}
}
