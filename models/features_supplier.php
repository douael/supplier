<?php
/**
* 2010-2017 EcomiZ
*
*  @author	EcomiZ
*  @copyright 2010-2017
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  @version  Release: 1.0.0 $Revision: 1 $
*/
require_once (_PS_MODULE_DIR_.'featuressupplier/models/reappro.php');
class FeaturesSupplierModel extends ObjectModel
{
	public $id_featuressupplier;
	public $feature_reference;
	public $id_supplier;

	public static $definition = array(
		'table' => 'featuressupplier',
		'primary' => 'id_featuressupplier',
		'multilang' => false,
		'fields' => array(
			'id_featuressupplier' => array(
				'type' => ObjectModel::TYPE_INT
			),
			'feature_reference' => array(
				'type' => ObjectModel::TYPE_STRING,
				'required' => true
			),
			'id_supplier' => array(
				'type' => ObjectModel::TYPE_INT,
				'required' => true
			)
		)
	);

	/*
	 * getFeatureSupplier by id_feature_value (optional id_lang)
	 * return array(quantity, reference, label, supplier)
	 */

	public static function getFeatureSupplier($id_feature_value, $id_lang = 1){
		
		$return = array();
		$feature = new FeatureValue($id_feature_value);
		
		if($feature->id_feature == Configuration::get('FEATURESSUPPLIER_PIECE_ID')) {
			// format : quantity#reference#label
			$value_explode = explode("#",$feature->value[$id_lang]);
			//ppp($value_explode);
			$keys = array("quantity", "reference", "label", "ean", "prix_ht", "montant_total");
			//ppp($keys);
			$array_value = array_values($value_explode);
			//ppp(count($array_value));
			/*if(count($array_value) != 6){
				for($i=count($array_value);$i<6;$i++){
					$array_value[] = 0;
				}
				
			}*/
			//ppp(($array_value));
			$return = array_combine($keys, $array_value);
			$ref = $value_explode[1];

			$aFeature_already = Db::getInstance()->getValue("SELECT id_supplier FROM `"._DB_PREFIX_."featuressupplier` WHERE feature_reference = '$ref'");
			$return["supplier"] = $aFeature_already;
		}
		
		//ddd($return);

		return $return;
	}

	/*
	 * getFeatureNameByRef by reference
	 * return label
	 * return label + prix ht + montant total if $price = true
	 */

	public static function getFeatureNameByRef($ref, $price = false)
	{
		$val = Db::getInstance()->getRow("SELECT * FROM `"._DB_PREFIX_."feature_value_lang` WHERE value LIKE '%$ref%'");
		$val = self::getFeatureSupplier($val['id_feature_value']);
		if($price === true){
			$return = $val['label'].' - '.$val['ean'].' - '.$val['prix_ht'].' - '.$val['montant_total'];
		}else{
			$return = $val['label'];
		}
		
		return $return;
	}

	public static function getReferencesByProduct($product){
		$references = array();
		$product = new Product($product);
		$features = $product->getFeatures();

		foreach($features as $feature){
			$feature_value = new FeatureValue($feature['id_feature_value']);
			if($feature['id_feature'] == Configuration::get("FEATURESSUPPLIER_PIECE_ID")){
				$references[] = self::getFeatureSupplier($feature_value->id);
			}
		}

		return $references;
	}

	/* 
	 * create and send email
	 * model email :
	 * 1 = standard order
	 * 2 = out of stock order
	 * 3 = pre order
	 * 4 = reappro
	 * 1 email = 1 feature, 1 supplier, multiple or one order
	 * $aProductsToSend is used to pre order case 3 : check only product from the current preorder
	 */

	public static function contentEmail($model, $data = null, $aProductsToSend = array()){
		$from_name = Configuration::get('PS_SHOP_NAME');
		$orders_to_send = array();
		$orders_to_send_fini = array();
		$product_list = array();
		
		//var_dump($model);
		
		switch ($model) {

/* **********  case 1 & 2 : with ORDER  ************* */

			case 1: // for standard order			1 order, 1 customer (>>> NO MAIL SEND)
			case 2: // for out of stock order		1 order, 1 customer

					$id_order = $data;
					$order = new Order($id_order);
					// get order detail
					$order_detail = $order->getOrderDetailList();

// !! TODO : n'envoyer ici que les produits qui sont concerné par le out of stock
// actuellement ça doit envoyer pr les produits de la cmd (? à vérifier)
					// get list of products non fini
					foreach($order_detail as $order_product){
						$product = new Product($order_product["product_id"]);
						$features = $product->getFeatures();

						// for $i quantity : if customer buy X quantity of a product
						for($i = 1;$i<= $order_product['product_quantity'];$i++){
							foreach($features as $feature){
								// if product feature Produit non fini
								if($feature['id_feature'] == Configuration::get("FEATURESSUPPLIER_PRODUIT_FINI_ID")){
									$feature_value = new FeatureValue($feature['id_feature_value']);
									if($feature_value->value[1] == "non" || $feature_value->value[1] == "Non"){
										//$product_list[] = $order_product["product_id"];
										$orders_to_send[$id_order][] = $order_product["product_id"];
									}
									
									// ajout 2017 10 31
									else if($feature_value->value[1] == "oui" || $feature_value->value[1] == "Oui"){
										//$product_list[] = $order_product["product_id"];
										$orders_to_send_fini[$id_order][$order_product["product_id"].'-'.$order_product["product_attribute_id"]]["product_id"] = $order_product["product_id"];
										$orders_to_send_fini[$id_order][$order_product["product_id"].'-'.$order_product["product_attribute_id"]]["product_attribute_id"] = $order_product["product_attribute_id"];
										$orders_to_send_fini[$id_order][$order_product["product_id"].'-'.$order_product["product_attribute_id"]]["quantity"] = $order_product['product_quantity'];
									}
								}
							}
						}
					}
					//ddd($orders_to_send_fini);

					$subject ="New supplier order ID $id_order for $from_name";

				break;

/* **********  case 3 : pre orders : multiple orders, one product  ************* */

			case 3:
				//ddd($data);
				foreach($data as $data_order){
					$id_order = $data_order;
					$order = new Order($id_order);
					// get order detail
					$order_detail = $order->getOrderDetailList();

					// get list of products non fini

					foreach($order_detail as $order_product){
						//we have to check on wich pre order (date) are the products
						// we add only products of the current pre order
						if(in_array($order_product["product_id"],$aProductsToSend)){
							$product = new Product($order_product["product_id"]);
							$features = $product->getFeatures();

							// FALSE !! quantity product are already manage 
							// for $i quantity : if customer buy X quantity of a product
							//for($i = 1;$i<= $order_product['product_quantity'];$i++){
								foreach($features as $feature){
									// if product feature Produit non fini
									if($feature['id_feature'] == Configuration::get("FEATURESSUPPLIER_PRODUIT_FINI_ID")){
										$feature_value = new FeatureValue($feature['id_feature_value']);
										if($feature_value->value[1] == "non" || $feature_value->value[1] == "Non"){
											$orders_to_send[$id_order][] = $order_product["product_id"];
										}
										// ajout 2017 10 31
										else if($feature_value->value[1] == "oui" || $feature_value->value[1] == "Oui"){
											//$product_list[] = $order_product["product_id"];
											$orders_to_send_fini[$id_order][$order_product["product_id"].'-'.$order_product["product_attribute_id"]]["product_id"] = $order_product["product_id"];
											$orders_to_send_fini[$id_order][$order_product["product_id"].'-'.$order_product["product_attribute_id"]]["product_attribute_id"] = $order_product["product_attribute_id"];
											$orders_to_send_fini[$id_order][$order_product["product_id"].'-'.$order_product["product_attribute_id"]]["quantity"] = $order_product['product_quantity'];
										}
									}
								}
							//}
						}
					}
				}

				$subject = "New order for $from_name";

				break;

/* **********  case 4 : reappro : without order, only product  ************* */

			case 4: // reappro
	
				$id_order = "REAPPRO";

				// select all product with threshold where threshold and minimal qtt > 0 
				$sql = '
				SELECT *
				FROM ' . _DB_PREFIX_ . 'productthreshold
				';
				$productthreshold = Db::getInstance()->ExecuteS($sql);
				$product_to_reappro = array();
				foreach($productthreshold as $order_product){
					// get current stock
					$current_stock = StockAvailable::getQuantityAvailableByProduct($order_product["id_product"], 0);
					
					// modif 2017 10 12 : add to insert reappro
					$sql = '
					SELECT SUM(quantity)
					FROM ' . _DB_PREFIX_ . 'reappro
					WHERE id_product = '.$order_product["id_product"].'
					AND status = 0
					';
					$virtualStock = Db::getInstance()->getValue($sql);
					//ddd($virtualStock);
					// add virtual stock to current stock
					$current_stock += $virtualStock;

					// compare current stock with threshold
					$delta = 0;
					if($current_stock <= $order_product["threshold_qtt"]){
						// if threshold <= current stock
						// calculate delta : minimal_qtt - current_stock = delta
						$delta = $order_product["minimal_qtt"] - $current_stock;
					}

					$product = new Product($order_product["id_product"]);
					$features = $product->getFeatures();

					// modif 2017 10 12 : add to insert reappro
					if($delta > 0){
						foreach($features as $feature){
							if($feature['id_feature'] == Configuration::get("FEATURESSUPPLIER_PRODUIT_FINI_ID")){
								$feature_value = new FeatureValue($feature['id_feature_value']);
								if($feature_value->value[1] == "non" || $feature_value->value[1] == "Non"){
									$product_to_reappro[$order_product["id_product"]] = $delta;
								}
								// ajout 2017 10 31
								/*else if($feature_value->value[1] == "oui" || $feature_value->value[1] == "Oui"){
									//$product_list[] = $order_product["product_id"];
									$product_to_reappro[$order_product["id_product"]] = $delta;
								}*/
							}
						}
					}

					// for $i quantity : if customer buy X quantity of a product
					for($i = 1;$i<= $delta;$i++){
						foreach($features as $feature){
							// if product feature Produit non fini
							if($feature['id_feature'] == Configuration::get("FEATURESSUPPLIER_PRODUIT_FINI_ID")){
								$feature_value = new FeatureValue($feature['id_feature_value']);
								if($feature_value->value[1] == "non" || $feature_value->value[1] == "Non"){
									//$product_list[] = $order_product["id_product"];
									$orders_to_send[$id_order][] = $order_product["id_product"];
								}
								// ajout 2017 10 31
								/*else if($feature_value->value[1] == "oui" || $feature_value->value[1] == "Oui"){
									//$product_list[] = $order_product["product_id"];
									$orders_to_send_fini[$id_order][$order_product["product_id"].'-'.$order_product["product_attribute_id"]]["product_id"] = $order_product["product_id"];
									$orders_to_send_fini[$id_order][$order_product["product_id"].'-'.$order_product["product_attribute_id"]]["product_attribute_id"] = $order_product["product_attribute_id"];
									$orders_to_send_fini[$id_order][$order_product["product_id"].'-'.$order_product["product_attribute_id"]]["quantity"] = $order_product['product_quantity'];
								}*/
							}
						}
					}
				}

				foreach($product_to_reappro as $id_product => $quantity){
						$current_date = date("Y-m-d H:i:s");
						$reappro = new ReapproModel();
						$reappro->date_add = $current_date;
						$reappro->date_upd = $current_date;
						$reappro->status = 0;
						$reappro->id_product = $id_product;
						$reappro->quantity = $quantity;
						$reappro->save();

				}

				$subject = "New order for $from_name";
				break;

/* **********  END SWITCH ************* */
		}

/* **********  COMMON ALL CASES ************* */

		foreach($orders_to_send as $id_order_to_send => $product_list){
			// get list of references from list of product non fini
			$references = array();
			foreach($product_list as $product_nonfini){
				$references[] = self::getReferencesByProduct($product_nonfini);
			}

			// group by supplier
			$all_supplier = array();
			foreach($references as $pdt){
				foreach($pdt as $reference){
					$supplier = new Supplier($reference['supplier']);
					$all_supplier[$supplier->id_supplier] = $supplier->meta_description[1];
				}
			}

			// get final list of references group by supplier
			$aSupplierMail = array();
			foreach($all_supplier as $key => $supplier){

				$id_supplier = $key;
				$product_non_fini = $references;
				
				foreach($product_non_fini as $product){
					foreach($product as $reference){
						if(isset($reference['supplier']) && $reference['supplier'] != "" &&
						$reference['supplier'] == $id_supplier){
							$aSupplierMail[$id_supplier][] = $reference;
						}
					}
				}
			}

			// group by reference :
			$aResultByRef = array();

			foreach( $aSupplierMail as $key0 => $val0 )
			{
				foreach( $val0 as $key1 => $val1 )
				{
					if( !isset( $aResultByRef[$key0][ $val1['quantity'].$val1['reference'] ] ) )
					{
						$aResultByRef[$key0][ $val1['quantity'].$val1['reference'] ] = array
						(
							'reference' => $val1['reference'],
							'label' => $val1['label'],
							'quantity' => $val1['quantity'],
							'supplier' => $val1['supplier'],
							'prix_ht' => $val1['prix_ht'],
							'montant_total' => $val1['montant_total'],
							'ean' => $val1['ean'],
							'supplier' => $val1['supplier'],
							'total' => 0
						);
					}
					$aResultByRef[$key0][ $val1['quantity'].$val1['reference'] ]['total']++;
					
				}
			}

			if($model == 1 || $model == 2 || $model == 3){
				// get customer infos

				$order_info = new Order($id_order_to_send);
				$address = new Address($order_info->id_address_delivery);
				
				$dest_name = $address->firstname." ".$address->lastname;
				$dest_company = $address->company;
				$dest_address = $address->address1." ".$address->address2;
				$dest_city = $address->postcode." ".$address->city." ".$address->country;
				$dest_phone = $address->phone." ".$address->phone_mobile;
				$str_id_order = 'ID '.$id_order_to_send;
				$order_info_date = $order_info->date_add;
				$order_info_ref = $order_info->reference;
			}else if ($model == 4){
				// get shop info (reappro)
				$shop_address = '';
				$shop = new Shop(1);
				$shop_address_obj = $shop->getAddress();
				if (isset($shop_address_obj) && $shop_address_obj instanceof Address) {
					$shop_address = AddressFormat::generateAddress($shop_address_obj, array(), ' - ', ' ');
				}

				$dest_name = $from_name;
				$dest_company = "";
				$dest_address = $shop_address;
				$dest_city = "";
				$dest_phone = Configuration::get('PS_SHOP_PHONE', null, null, $id_shop);
				$str_id_order = "";
				$order_info_date = $current_date = date("Y-m-d H:i:s");
				$order_info_ref = "";
			}

			// for each supplier : create and send a email
			foreach($aResultByRef as $key => $supplier){
				$aMess = array();
				$aMess ["title"] = "Une nouvelle commande $str_id_order a été passée sur $from_name \n";
				$aMess ["order_date"] = $order_info_date;
				$aMess ["reference"] = $order_info_ref;
				$aMess ["dest"][] = $dest_name." \n";
				$aMess ["dest"][] = $dest_company." \n";
				$aMess ["dest"][] = $dest_address." \n";
				$aMess ["dest"][] = $dest_city." \n";
				$aMess ["dest"][] = $dest_phone." \n";
				
				foreach($supplier as $reference){
					//$aMess .= $reference['total']." x [".$reference['quantity']." x ". $reference['reference'] . " (".$reference['label'].")] \n";
					$aMess ["cmd"][] = $reference['total']." x [".$reference['quantity']." x ". $reference['reference'] . " (".$reference['label'].")| EAN: (".$reference['ean'].") | Prix HT: ".$reference['prix_ht']." | Montant total: ".$reference['montant_total']." ] \n";
				}

				/*foreach($supplier as $reference){
					var_dump($reference['total']." x [".$reference['quantity']." x ". $reference['reference'] . " (".$reference['label'].")] \n");
					
				}*/
				
				$strMess = "<strong>Une nouvelle commande $str_id_order a été passée sur $from_name </strong><br/>";
				$strMess .= "<strong>DESTINATAIRE</strong> : <br/>";
				$strMess .= $dest_name."  <br/>";
				$strMess .= $dest_company."  <br/>";
				$strMess .= $dest_address."  <br/>";
				$strMess .= $dest_city."  <br/>";
				$strMess .= $dest_phone."  <br/>";
				$strMess .= "  <br/>";
				$strMess .= "<strong>COMMANDE</strong> :  <br/>";
				foreach($supplier as $reference){
					// $strMess .= $reference['quantity']." x ". $reference['reference'] . " (".$reference['label'].")  <br/>";
					//$strMess .= $reference['total']." x [".$reference['quantity']." x ". $reference['reference'] . " (".$reference['label'].")] <br/>";
					$strMess .= $reference['total']." x [".$reference['quantity']." x ". $reference['reference'] . " (".$reference['label'].") | EAN: (".$reference['ean'].") | Prix HT: ".$reference['prix_ht']." | Montant total: ".$reference['montant_total']." ] <br/>";
				}
				$strMess .= " <hr/>";
				$content[$key][$id_order_to_send] = $aMess;
				$content[$key][$id_order_to_send]["txt"] = $strMess;
			}
		}
		
		if(isset($content) && !empty($content) && $content != ""){
			foreach($content as $key => $orders){
				$to = $key; // id supplier
				$email_content = "";

				foreach($orders as $order){
					$email_content .= $order['txt'];
				}

				self::createEmail($email_content, $subject, $to, $model, $orders);
			}
		}
		
		//ajout 2017 10 31 : ajout produit fini ****************************************
		
		if(isset($orders_to_send_fini) && !empty($orders_to_send_fini)){
			foreach($orders_to_send_fini as $id_order_to_send => $product_list){
				
				if($model == 1 || $model == 2 || $model == 3){
					// get customer infos

					$order_info = new Order($id_order_to_send);
					$address = new Address($order_info->id_address_delivery);
					
					$dest_name = $address->firstname." ".$address->lastname;
					$dest_company = $address->company;
					$dest_address = $address->address1." ".$address->address2;
					$dest_city = $address->postcode." ".$address->city." ".$address->country;
					$dest_phone = $address->phone." ".$address->phone_mobile;
					$str_id_order = 'ID '.$id_order_to_send;
					$order_info_date = $order_info->date_add;
					$order_info_ref = $order_info->reference;
				}else if ($model == 4){
					// get shop info (reappro)
					$shop_address = '';
					$shop = new Shop(1);
					$shop_address_obj = $shop->getAddress();
					if (isset($shop_address_obj) && $shop_address_obj instanceof Address) {
						$shop_address = AddressFormat::generateAddress($shop_address_obj, array(), ' - ', ' ');
					}

					$dest_name = $from_name;
					$dest_company = "";
					$dest_address = $shop_address;
					$dest_city = "";
					$dest_phone = Configuration::get('PS_SHOP_PHONE', null, null, $id_shop);
					$str_id_order = "";
					$order_info_date = $current_date = date("Y-m-d H:i:s");
					$order_info_ref = "";
				}

				// group by reference :
				$aResultByRef = array();
				
				//ppp($product_list);
				
				// group by supplier
				$all_supplier = array();
				foreach($product_list as $reference){
					$productFini = new Product($reference["product_id"]);
					$supplier = new Supplier($productFini->id_supplier);
					$all_supplier[$supplier->id_supplier] = $supplier->meta_description[1];

				}
				
				//ppp($all_supplier);

				// get final list of references group by supplier
				$aSupplierMail = array();
				foreach($all_supplier as $key => $supplier){

					$id_supplier = $key;
					$product_non_fini = $product_list;
					
					foreach($product_non_fini as $reference){
						//foreach($product as $reference){
							$productFini = new Product($reference["product_id"]);
							$supplier = new Supplier($productFini->id_supplier);
							if(isset($productFini->id_supplier) && $productFini->id_supplier != "" &&
							$productFini->id_supplier == $id_supplier){
								$aSupplierMail[$id_supplier][] = $reference;
							}
						//}
					}
				}
				
				//ppp($aSupplierMail);
				


				foreach( $aSupplierMail as $key0 =>  $product_list )
				{
					foreach( $product_list as $key1 => $val1 )
					//foreach( $product_list as $val1)
					{
						$attribute_name = '';
						$productFini = new Product($val1["product_id"]);

						if( !isset( $aResultByRef[$val1["product_id"].'-'.$val1["product_attribute_id"]] ) )
						{
							// check if there is id_product_attribute in order detail for this product this order
							$order_details = OrderDetail::getList($id_order_to_send);

							foreach($order_details as $order_detail){
								if($order_detail["product_id"] == $val1["product_id"] && $order_detail["product_attribute_id"] == $val1["product_attribute_id"]){
									$attribute_name = $order_detail['product_name'];
									$product_supplier_reference = $order_detail['product_supplier_reference'];
									$product_price = $order_detail['product_price'];
								}
							}
							
							// variable to send (check if combination or not) label, ref, price :
							if(isset($attribute_name) && $attribute_name != ""){
								$label_to_send = $attribute_name;
							}else{
								$label_to_send = $productFini->name[1];
							}
							
							if(isset($product_supplier_reference) && $product_supplier_reference != ""){
								$reference_to_send = $product_supplier_reference;
							}else{
								$reference_to_send = $productFini->reference;
							}
							
							if(isset($product_supplier_reference) && $product_supplier_reference != ""){
								$price_to_send = $product_price;
							}else{
								$price_to_send = $productFini->price;
							}
							
							// var to send :
							//$aResultByRef[$key0][ $val1['quantity'].$val1['reference'] ] = array
							$aResultByRef[$key0][$val1["product_id"].'-'.$val1["product_attribute_id"]] = array
							(
								'reference' => $reference_to_send,
								'label' => $label_to_send,
								'quantity' => 1,
								'supplier' => $productFini->supplier_name,
								'prix_ht' => $productFini->price,
								'montant_total' => $price_to_send,
								'ean' => $productFini->ean13,
								'id_supplier' => $productFini->id_supplier,
								'total' => $val1["quantity"],
								'attribute_name' => $attribute_name
							);
						}
						//$aResultByRef[$val1["product_id"]]['total']++;
					}
				}
				//ppp($aResultByRef);
				//ddd($aResultByRef);
				// foreach product 
				
				//ppp($aResultByRef);
				foreach($aResultByRef as $key => $supplier){
					
					$aMess = array();
					$aMess ["title"] = "Une nouvelle commande $str_id_order a été passée sur $from_name \n";
					$aMess ["order_date"] = $order_info_date;
					$aMess ["reference"] = $order_info_ref;
					$aMess ["dest"][] = $dest_name." \n";
					$aMess ["dest"][] = $dest_company." \n";
					$aMess ["dest"][] = $dest_address." \n";
					$aMess ["dest"][] = $dest_city." \n";
					$aMess ["dest"][] = $dest_phone." \n";
					
					$strMess = "<strong>Une nouvelle commande $str_id_order a été passée sur $from_name </strong><br/>";
					$strMess .= "<strong>DESTINATAIRE</strong> : <br/>";
					$strMess .= $dest_name."  <br/>";
					$strMess .= $dest_company."  <br/>";
					$strMess .= $dest_address."  <br/>";
					$strMess .= $dest_city."  <br/>";
					$strMess .= $dest_phone."  <br/>";
					$strMess .= "  <br/>";
					$strMess .= "<strong>COMMANDE</strong> :  <br/>";
					
					foreach($supplier as $reference){
						//$reference = $supplier;
						$aMess ["cmd"][] = $reference['total']." x [". $reference['reference'] . " (".$reference['label']. ") | EAN: (".$reference['ean'].") | Prix HT: ".$reference['prix_ht']." ] \n";

						$strMess .= $reference['total']." x [". $reference['reference'] . " (".$reference['label'].") | EAN: (".$reference['ean'].") | Prix HT: ".$reference['prix_ht']." ] <br/>";

						$strMess .= " <hr/>";
					}

					$contentFini[$key][$id_order_to_send] = $aMess;
					$contentFini[$key][$id_order_to_send]["txt"] = $strMess;
				}
			}
			
		}
		//ddd($contentFini);
		
		if(isset($contentFini) && !empty($contentFini) && $contentFini != ""){
			foreach($contentFini as $key => $orders){
				$to = $key; // id supplier
				$email_content = "";

				foreach($orders as $order){
					$email_content .= $order['txt'];
				}

				self::createEmail($email_content, $subject, $to, $model, $orders);
			}
		}
		
		//END ajout 2017 10 31 : ajout produit fini ****************************************
	}

	/* 
	 * create and send email
	 */

	public static function createEmail($content, $subject, $to, $model, $orders){
		//ppp($content);
		//ppp($subject);
		//ppp($to);
		//ppp($model);
		//ddd($orders);
		// get supplier info : email and address
		$id_supplier = $to;
		$supplier = new Supplier($id_supplier);
		$to = $supplier->meta_description[1];
		$address_supplier_obj = new Address(Address::getAddressIdBySupplierId($id_supplier));
		if (isset($address_supplier_obj) && $address_supplier_obj instanceof Address) {
			$avoid = array();
			$avoid['avoid'][] = 'firstname';
			$avoid['avoid'][] = 'lastname';
			$address_supplier = $supplier->name.' <br/>';
			$address_supplier .= AddressFormat::generateAddress($address_supplier_obj, $avoid, '<br/>');
		}
		//ppp($avoid);
		//ppp($id_supplier);
		//ddd($address_supplier);

		// define content tpl
		$pdf_content['address_supplier'] = $address_supplier;
		$pdf_content['orders'] = $orders;
		$pdf_content['modalites'] = $supplier->description[1];
		
		// generate PDF
		require_once _PS_MODULE_DIR_ . 'featuressupplier/models/HTMLTemplateCustomPdf.php';

		$id_pdf = Configuration::get('FEATURESSUPPLIER_CHRONO_PDF');

		$pdf = new PDF((object)$pdf_content, 'CustomPdf', Context::getContext()->smarty);

		$pdf_name = (string)"CIDER_".Tools::str2url($to)."_". date("YmdHis")."_".$id_pdf.".pdf";
		// attache PDF
		$file_attachement['content'] = $pdf->render(false);
		$file_attachement['name'] = $pdf_name;
		$file_attachement['mime'] = 'application/pdf';

		$id_lang = 1;

		$template = 'new-order-supplier';
		$to; // emailSupplier
		//$to = "pierrick.foy@gmail.com";
		$from = Configuration::get('PS_SHOP_EMAIL');
		$from_name = Configuration::get('PS_SHOP_NAME');
		$bcc = Configuration::get('FEATURESSUPPLIER_BCC_EMAIL');
		$aBcc = explode(',',$bcc);
		//$aBcc = "pierrick.foy@gmail.com";
		$tpl_var = array(
			'{cnt}' => $content
		);

		// send email to supplier with PDF attached
		if(
			Mail::Send(
				$id_lang, // id lang
				$template, // template
				Mail::l($subject, $id_lang), // subject
				$tpl_var, // template vars
				$to, // $to
				null, // to name
				null, // from
				null, // from name
				$file_attachement, // pdf
				null, _PS_MODULE_DIR_ . 'featuressupplier/mails/',
				false, // die
				null, // id shop
				$aBcc//bcc
			)
		){
			$message = "Feature supplier : EMAIL OK to ".$to;
			PrestaShopLogger::addLog($message, 1);
		}else{
			$message = "Feature supplier : EMAIL KO to ".$to;
			PrestaShopLogger::addLog($message, 3);
		}
		$id_pdf++;
		
		Configuration::updateValue('FEATURESSUPPLIER_CHRONO_PDF', $id_pdf);
	}
}
