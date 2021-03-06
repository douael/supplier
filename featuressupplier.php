<?php
/**
* 2010-2017 EcomiZ
*
*  @author	EcomiZ
*  @copyright 2010-2017
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  @version  Release: 1.0.0 $Revision: 1 $
*/

require_once _PS_MODULE_DIR_.'featuressupplier/models/features_supplier.php';

class FeaturesSupplier extends Module
{
	public function __construct()
	{
		$this->name = 'featuressupplier';
		$this->tab = 'administration';
		$this->version = '1.0.0';
		$this->author = 'ED';
		$this->displayName = $this->l('Features Supplier');
		$this->description = $this->l('Features Supplier par ED');
		$this->bootstrap = true;
		parent::__construct();
	}

	public function install()
	{
		$this->createDb();
		$this->installBackOffice();
		Configuration::updateValue('FEATURESSUPPLIER_PIECE_ID', "");
		Configuration::updateValue('FEATURESSUPPLIER_PRODUIT_FINI_ID', "");
		Configuration::updateValue('FEATURESSUPPLIER_CHRONO_PDF', 1);
		return parent::install() && $this->registerHook('displayHeader') && $this->registerHook('actionOrderStatusPostUpdate');
	}

	public function uninstall()
	{
		if (!parent::uninstall() || !$this->removeDb() || !$this->uninstallModuleTab('AdminFeaturesSupplier'))
			return false;
		return parent::uninstall();
	}
	
	public function removeDb()
	{
		$curdate = date("YmdHi");
		$backup = _DB_PREFIX_.'featuressupplier_'.$curdate;
		if (Db::getInstance()->Execute('
			CREATE TABLE '.$backup.' LIKE '._DB_PREFIX_.'featuressupplier; 
			INSERT '.$backup.' SELECT * FROM '._DB_PREFIX_.'featuressupplier;
			DROP TABLE '._DB_PREFIX_.'featuressupplier;
			'
				))
			return true;
		return false;
	}
	public function installBackOffice()
	{
		$id_lang_en = LanguageCore::getIdByIso('en');
		$id_lang_fr = LanguageCore::getIdByIso('fr');
		$id_root_tab = Tab::getIdFromClassName('AdminED');
		if (empty($id_root_tab)) {
			$this->installModuleTab('AdminED', array($id_lang_fr => 'Modules ED',
			$id_lang_en => 'ED module'), '0');
			$id_root_tab = Tab::getIdFromClassName('AdminED');
		}
		$this->installModuleTab('AdminFeaturesSupplier', array($id_lang_fr => 'Features Supplier',
		$id_lang_en => 'Features Supplier'), $id_root_tab);
	}

	private function installModuleTab($tab_class, $tab_name, $id_tab_parent)
	{
		$tab = new Tab();
		$tab->name = $tab_name;
		$tab->class_name = $tab_class;
		$tab->module = $this->name;
		$tab->id_parent = (int)$id_tab_parent;
		if (!$tab->save()) {
			return false;
		}
		return true;
	}

	private function uninstallModuleTab($tab_class)
	{
		$id_tab = Tab::getIdFromClassName($tab_class);
		if ($id_tab != 0) {
			$tab = new Tab($id_tab);
			$tab->delete();
			return true;
		}
		return false;
	}

	public function getContent()
	{
		$output = null;

		if (Tools::isSubmit('submit'.$this->name)) {
			$featuressupplier_bcc_email = Tools::getValue('FEATURESSUPPLIER_BCC_EMAIL');
			$featuressupplier_piece_id = Tools::getValue('FEATURESSUPPLIER_PIECE_ID');
			$featuressupplier_produit_fini_id = Tools::getValue('FEATURESSUPPLIER_PRODUIT_FINI_ID');
			$featuressupplier_status_id = Tools::getValue('FEATURESSUPPLIER_STATUS_ID');
			$featuressupplier_status_reappro_id = Tools::getValue('FEATURESSUPPLIER_STATUS_REAPPRO_ID');

			if (!$featuressupplier_piece_id || empty($featuressupplier_piece_id) || !$featuressupplier_produit_fini_id || empty($featuressupplier_produit_fini_id) || !$featuressupplier_status_id || empty($featuressupplier_status_id) || !$featuressupplier_status_reappro_id || empty($featuressupplier_status_reappro_id) || !$featuressupplier_bcc_email || empty($featuressupplier_bcc_email)) {
				$output .= $this->displayError($this->l('Invalid Configuration value'));
			} else {
				Configuration::updateValue('FEATURESSUPPLIER_BCC_EMAIL', $featuressupplier_bcc_email);
				Configuration::updateValue('FEATURESSUPPLIER_PIECE_ID', $featuressupplier_piece_id);
				Configuration::updateValue('FEATURESSUPPLIER_PRODUIT_FINI_ID', $featuressupplier_produit_fini_id);
				Configuration::updateValue('FEATURESSUPPLIER_STATUS_ID', $featuressupplier_status_id);
				Configuration::updateValue('FEATURESSUPPLIER_STATUS_REAPPRO_ID', $featuressupplier_status_reappro_id);

				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		}

		// contact
		$form = '';
		$form .= '<br/>
			<fieldset>
			<legend>ED</legend>
			<p>
			'.$this->l('This module has been developped by').
			'<strong> ED </strong><br />
			</p>
			</fieldset>';

		return $output.$this->displayForm().$form;
	}

	public function displayForm()
	{
		// Get default Language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
		$fields_form = array();
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Settings'),
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('BCC email'),
					'name' => 'FEATURESSUPPLIER_BCC_EMAIL',
					'size' => 100,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Id Feature Pièce'),
					'name' => 'FEATURESSUPPLIER_PIECE_ID',
					'size' => 20,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Id Feature Produit fini'),
					'name' => 'FEATURESSUPPLIER_PRODUIT_FINI_ID',
					'size' => 20,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Id Status to send supplier mail'),
					'name' => 'FEATURESSUPPLIER_STATUS_ID',
					'size' => 20,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Id Status to send supplier mail (outofstock)'),
					'name' => 'FEATURESSUPPLIER_STATUS_REAPPRO_ID',
					'size' => 20,
					'required' => true
				),
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;		// false -> remove toolbar
		$helper->toolbar_scroll = true;	  // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array(
			'save' =>
			array(
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
				'&token='.Tools::getAdminTokenLite('AdminModules'),
			),
			'back' => array(
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);

		// Load current value
		$helper->fields_value['FEATURESSUPPLIER_BCC_EMAIL'] = Configuration::get('FEATURESSUPPLIER_BCC_EMAIL');
		$helper->fields_value['FEATURESSUPPLIER_PIECE_ID'] = Configuration::get('FEATURESSUPPLIER_PIECE_ID');
		$helper->fields_value['FEATURESSUPPLIER_PRODUIT_FINI_ID'] = Configuration::get('FEATURESSUPPLIER_PRODUIT_FINI_ID');
		$helper->fields_value['FEATURESSUPPLIER_STATUS_ID'] = Configuration::get('FEATURESSUPPLIER_STATUS_ID');
		$helper->fields_value['FEATURESSUPPLIER_STATUS_REAPPRO_ID'] = Configuration::get('FEATURESSUPPLIER_STATUS_REAPPRO_ID');

		return $helper->generateForm($fields_form);
	}

	public function createDb()
	{
		$prefix = _DB_PREFIX_;
		$sql = "
			CREATE TABLE IF NOT EXISTS `${prefix}featuressupplier` (
				`id_featuressupplier` int(10) NOT NULL AUTO_INCREMENT,
				`feature_reference` varchar(100) NOT NULL,
				`id_supplier` int(10) UNSIGNED NOT NULL,
				PRIMARY KEY (`id_featuressupplier`),
				UNIQUE KEY (`feature_reference`),
				FOREIGN KEY (`id_supplier`) REFERENCES `ps_supplier` (`id_supplier`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				";
		/*
		// Ancienne table dans laquelle on stockait les id features
		// plutôt que les références ; ce qui permet maintenant de ne pas avoir
		// a associer chaque quantité de chaque référence à un fournisseur
		// mais d'avoir 1 ref => 1 supplier, et pas besoin d'associer si une
		// nouvelle caractéristique est créée (référence existante, quantité différente)
		
		$sql = "
			CREATE TABLE IF NOT EXISTS `${prefix}featuressupplier` (
				`id_featuressupplier` int(10) NOT NULL AUTO_INCREMENT,
				`id_feature_value` int(10) UNSIGNED NOT NULL,
				`id_supplier` int(10) UNSIGNED NOT NULL,
				PRIMARY KEY (`id_featuressupplier`),
				UNIQUE KEY (`id_feature_value`),
				FOREIGN KEY (`id_feature_value`) REFERENCES `ps_feature_value` (`id_feature_value`),
				FOREIGN KEY (`id_supplier`) REFERENCES `ps_supplier` (`id_supplier`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				";
			*/
				
				
		if (Db::getInstance()->Execute($sql)) {
			return true;
		}
	}

	public function hookDisplayHeader($params)
	{
		$params;
		$context = Context::getContext();

		// to hide features on product page
		if (isset($context->controller->name)
			&& $context->controller->name == 'product') {
			Tools::addCSS(_MODULE_DIR_.'/featuressupplier/views/css/featuressupplier.css', 'all');
		}
	}
	
	public function hookActionOrderStatusPostUpdate($params)
	{
		// on arrive ici dans tous les cas après un changement de statut
		// 
		
		
		$params;
		// $order = new Order($params["id_order"]);
		// $order->getHistory(1);
		// ddd($params);
		$context = Context::getContext();

		// case 1  : standard order : not use anymore
		$status_to_send = Configuration::get("FEATURESSUPPLIER_STATUS_ID");
		
		// case 2 : out of stock order : send direct supplier email when shop stock not enough
		$status_reappro_to_send = Configuration::get("FEATURESSUPPLIER_STATUS_REAPPRO_ID");
		
		// case 3 : pre order
		// post pre order state is the same as out of stock state
		// problem : emails are send twice to supplier
		// check if in order history there is Pre Order paid
		// that means we dont have to send direct email out of stock CASE 2 :

		$order = new Order($params["id_order"]);
		$is_paid_preorder = $order->getHistory(1, Configuration::get('PREORDER_STATE_ID'));

		// order is not a paid pre order : we can send Out of stock mail to supplier
		if(empty($is_paid_preorder)){
			// here : after update order status
			// we send email to supplier :
			if(isset($status_to_send) && $status_to_send && isset($status_reappro_to_send) && $status_reappro_to_send){

				$order_status = $params["newOrderStatus"]->id;
				$isOrderPaid = $params["newOrderStatus"]->paid;

				//if order is paid
				if($isOrderPaid == 1){
					// CASE 1 : standard order : NO EMAIL TO SUPPLIER SEND 
					if($status_to_send == $order_status){
						//$pdt_list = FeaturesSupplierModel::getProductsNonFiniByOrder($params["id_order"]);
						//FeaturesSupplierModel::contentEmail(1, $params["id_order"]);

					// CASE 2 : out of stock order
					}else if($status_reappro_to_send == $order_status){
						
						FeaturesSupplierModel::contentEmail(2, $params["id_order"]);
					}
				}
			}
		}
	}
}
