<?php
/**
* 2010-2017 EcomiZ
*
*  @author	EcomiZ
*  @copyright 2010-2017
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  @version  Release: 1.0.0 $Revision: 1 $
*/

class AdminFeaturesSupplierController extends ModuleAdminController
{
	public function __construct()
	{
		$this->bootstrap = true;
		$this->table = 'featuressupplier';
		$this->className = 'FeaturesSupplierModel';
		$this->identifier = 'id_featuressupplier';
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
		$this->context = Context::getContext();

		parent::__construct();

		$this->fields_list = array(
			'feature_reference' => array(
				'title' => 'Feature reference - ean - prix ht - montant total',
				'align' => 'left',
				'width' => 200,
				'callback' => "getFeatureName"
			),
			'id_supplier' => array(
				'title' => 'Supplier',
				'align' => 'left',
				'width' => 200,
				'callback' => "getSupplierName"
			),
		);
	}

	/*
	 * callback : display feature name
	 */

	public function getFeatureName($a)
	{
		$return = FeaturesSupplierModel::getFeatureNameByRef($a, true);
		return $a.' - '.$return;
	}

	/*
	 * callback : display supplier name
	 */

	public function getSupplierName($a)
	{
		$name = Db::getInstance()->getValue("SELECT name FROM `"._DB_PREFIX_."supplier` WHERE id_supplier = $a");
		return $name;
	}

	public function renderForm()
	{
		$prefix = _DB_PREFIX_;
		$aFeatureValue = array();
		
		// get feature
		$featureValue = FeatureValue::getFeatureValuesWithLang("1", Configuration::get('FEATURESSUPPLIER_PIECE_ID'));
		
		// when UPDATE feature
		if(Tools::getValue('id_featuressupplier')){
			$feature = new FeaturesSupplierModel(Tools::getValue('id_featuressupplier'));
			
			$label = FeaturesSupplierModel::getFeatureNameByRef($feature->feature_reference);
			array_push($aFeatureValue, array('value' => $feature->feature_reference, 'name' => $feature->feature_reference.' - '.$label));
			$feature_desc = $this->module->l('Feature to update');

		// when NEW association feature/supplier : only display feature not already associated with supplier
		}else{
			$aFeatureRef = array();
//ddd($featureValue);
			// for all feature, get infos
			foreach($featureValue as $feature){
				
				$val = FeaturesSupplierModel::getFeatureSupplier($feature['id_feature_value']);
				//ppp($val);
				if(empty($val['supplier']) || $val['supplier'] == ""){
					$aFeatureRef[$val["reference"]] = $val["label"];
				}
			}
			//die();

			foreach($aFeatureRef as $key => $ref){
				array_push($aFeatureValue, array('value' => $key, 'name' => $key.' - '.$ref));
			}

			// different message when there is no feature to associate
			if(empty($aFeatureValue) || $aFeatureValue == ""){
				$feature_desc = $this->module->l('All features already associated with supplier. To update one feature, go back to the table and modify some feature.');
			} else {
				$feature_desc = $this->module->l('Select one or multiple features. Only not associated features to supplier are displayed here.');
			}

		}

		// get supplier
		$supplier = Supplier::getSuppliers();

		$aSupplier = array();
		array_push($aSupplier, array('value' => 0, 'name' => $this->l('Choose :')));
		foreach($supplier as $supp){
			array_push($aSupplier, array('value' => $supp['id_supplier'], 'name' => $supp['name']));
		}

		// form
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Feature - supplier')
			),
			'input' => array(
				array(
					'type' => 'select',
					'label' => $this->l('feature'),
					'name' => 'feature_reference[]',
					'desc' => $feature_desc,
					'class' => 'ac_input',
					 'multiple' => true,
					 'size' => 10,
					'required' => true,
					'options' => array(
						'query' => $aFeatureValue,
						'id' => 'value',
						'name' => 'name'),
				),
				array(
					'type' => 'select',
					'label' => $this->l('supplier'),
					'name' => 'id_supplier',
					'class' => 'ac_input',
					'desc' => $this->module->l('Select supplier for this/these feature/s'),

					'required' => true,
					'options' => array(
						'query' => $aSupplier,
						'id' => 'value',
						'name' => 'name'),
				),
			),
			'submit' => array(
				'title' => $this->module->l('Save'),
				'name' => 'submitArticle'
			)
		);
		return parent::renderForm();
	}

	public function processSave()
	{
		if(!Tools::getValue('id_supplier') || Tools::getValue('id_supplier') == ""){
			$this->errors[] = Tools::displayError('Supplier required');
		} else {
			// update
			if (Tools::isSubmit('submitArticle') && (Tools::getValue('id_featuressupplier'))) {
				$article = new FeaturesSupplierModel(Tools::getValue('id_featuressupplier'));
				$article->id_supplier = Tools::getValue('id_supplier');
				$article->save();
			}

			// insertion
			if (Tools::isSubmit('submitArticle') && !(Tools::getValue('id_featuressupplier'))) {
				
				$aFeatures = Tools::getValue('feature_reference');
				
				if(!empty($aFeatures)){
					// multiple select
					foreach($aFeatures as $feature){
						$article = new FeaturesSupplierModel();
						$article->feature_reference = $feature;
						$article->id_supplier = Tools::getValue('id_supplier');
						$article->save();
					}
				} else {
					$this->errors[] = Tools::displayError('At least one feature required');
				}
			}
		}
	}

	/*
	 * render Kpis, display block with infos before table
	 * display a warning if there are feature not associated to supplier
	 * display success message if 100% feature associated
	 */

	public function renderKpis()
	{
		$count=0;
		$aFeature_al = array();
		$aFeatureRef = array();
		$featureValue = FeatureValue::getFeatureValuesWithLang("1", Configuration::get('FEATURESSUPPLIER_PIECE_ID'));

		$aFeature_already = Db::getInstance()->executeS("SELECT feature_reference FROM `"._DB_PREFIX_."featuressupplier`");
		
		
		foreach($aFeature_already as $feature_already){
			$aFeature_al[] = $feature_already['feature_reference'];
		}
		
		foreach($featureValue as $feature){
			$val = FeaturesSupplierModel::getFeatureSupplier($feature['id_feature_value']);
			
			if(empty($val['supplier']) || $val['supplier'] == ""){
				$aFeatureRef[$val["reference"]] = $val["label"];
			}
		}

		$count = count($aFeatureRef);

		$kpis = array();
		$helper = new HelperKpi();
		$helper->id = 'box-products-stock';

		if($count == 0){
			$helper->icon = 'icon-check';
			$helper->color = 'color4';
			$helper->title = $this->l('Features associated with supplier :', null, null, false);
			$helper->value = "100%";
		}else{
			$helper->icon = 'icon-warning';
			$helper->color = 'color2';
			$helper->title = $this->l('Nb features not associated with supplier !', null, null, false);
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
