<?php

/* 
 * ED : 2017 08 22  when stock not enough : set outofStock (for state Reappro) but without update quantity
 */

class OrderDetail extends OrderDetailCore
{
	protected function checkProductStock($product, $id_order_state)
    {
        if ($id_order_state != Configuration::get('PS_OS_CANCELED') && $id_order_state != Configuration::get('PS_OS_ERROR')) {
            $update_quantity = true;
            if (!StockAvailable::dependsOnStock($product['id_product'])) {
				
				// check if new qtt > 0
				$new_qtt = $product['stock_quantity'];
				$new_qtt -= $product['cart_quantity'];
				// if new qtt >= 0 : update stock
				if($new_qtt >= 0){
					$update_quantity = StockAvailable::updateQuantity($product['id_product'], $product['id_product_attribute'], -(int)$product['cart_quantity']);
					
					if ($update_quantity) {
						$product['stock_quantity'] -= $product['cart_quantity'];
					}
				//else status = outofstock + send email to supplier 
				}else{
					//if ($product['stock_quantity'] < 0 && Configuration::get('PS_STOCK_MANAGEMENT')) {
						// Modif Ecomiz 2017 08 22 : when stock not enough : set outofStock (for state Reappro) but without update quantity
						$this->outOfStock = true;
					//	}
				}
            }

            /*if ($update_quantity) {
                $product['stock_quantity'] -= $product['cart_quantity'];
            }*/

            /*if ($product['stock_quantity'] < 0 && Configuration::get('PS_STOCK_MANAGEMENT')) {
                $this->outOfStock = true;
            }*/
            Product::updateDefaultAttribute($product['id_product']);
        }
    }
}
