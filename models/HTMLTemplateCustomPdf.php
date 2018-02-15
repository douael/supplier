<?php
 
class HTMLTemplateCustomPdf extends HTMLTemplate
{
	public $custom_model;
 
	public function __construct($custom_object, $smarty)
	{
		//ddd($custom_object);
		
		// footer informations
		$this->shop = new Shop(Context::getContext()->shop->id);
		
		$custom_object->shop_address = $this->getShopAddress2(1);
		
		$this->custom_model = (array)$custom_object;
		$this->smarty = $smarty;
 
		// header informations
		$id_lang = Context::getContext()->language->id;
		$this->title = HTMLTemplateCustomPdf::l('Commande CIDER');
		
	}
 
	/**
	 * Returns the template's HTML content
	 * @return string HTML content
	 */
	public function getContent()
	{
		$this->smarty->assign(array(
			'custom_model' => $this->custom_model,
		));
 
		return $this->smarty->fetch(_PS_MODULE_DIR_ . 'featuressupplier/pdf/custom_template_content.tpl');
	}
 
	public function getLogo()
	{
		$this->smarty->assign(array(
			'custom_model' => $this->custom_model,
		));
 
		return $this->smarty->fetch(_PS_MODULE_DIR_ . 'featuressupplier/pdf/custom_template_logo.tpl');
	}

	protected function getLogo2()
    {
        $logo = '';

        $id_shop = (int)$this->shop->id;

        if (Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop);
        } elseif (Configuration::get('PS_LOGO', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $id_shop);
        }
        return $logo;
    }
 
	public function getHeader()
	{
		 $id_shop = (int)$this->shop->id;
        $shop_name = Configuration::get('PS_SHOP_NAME', null, null, $id_shop);

        $path_logo = $this->getLogo2();
       // $path_logo = "";

        $width = 0;
        $height = 0;
        if (!empty($path_logo)) {
            list($width, $height) = getimagesize($path_logo);
        }

        // Limit the height of the logo for the PDF render
        $maximum_height = 100;
        if ($height > $maximum_height) {
            $ratio = $maximum_height / $height;
            $height *= $ratio;
            $width *= $ratio;
        }

        $this->smarty->assign(array(
            'logo_path' => $path_logo,
            'img_ps_dir' => 'http://'.Tools::getMediaServer(_PS_IMG_)._PS_IMG_,
            'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'),
            'date' => $this->date,
            'title' => $this->title,
            'shop_name' => $shop_name,
            'shop_details' => Configuration::get('PS_SHOP_DETAILS', null, null, (int)$id_shop),
            'width_logo' => $width,
            'height_logo' => $height
        ));
		/*$this->smarty->assign(array(
			'custom_model' => $this->custom_model,
		));*/
 
		return $this->smarty->fetch(_PS_MODULE_DIR_ . 'featuressupplier/pdf/custom_template_header.tpl');
	}
	
	protected function getShopAddress2($br = null)
    {
        $shop_address = '';
		if($br)
			$br = " <br/> ";
		else
			$br = " - ";

        $shop_address_obj = $this->shop->getAddress();
        if (isset($shop_address_obj) && $shop_address_obj instanceof Address) {
            $shop_address = AddressFormat::generateAddress($shop_address_obj, array(), $br, ' ');
        }

        return $shop_address;
    }
 
	/**
	 * Returns the template filename
	 * @return string filename
	 */
	public function getFooter()
	{
		$shop_address = $this->getShopAddress2();

        $id_shop = (int)$this->shop->id;

        $this->smarty->assign(array(
            'available_in_your_account' => $this->available_in_your_account,
            'shop_address' => $shop_address,
            'shop_fax' => Configuration::get('PS_SHOP_FAX', null, null, $id_shop),
            'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, $id_shop),
            'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop),
            'free_text' => Configuration::get('PS_INVOICE_FREE_TEXT', (int)Context::getContext()->language->id, null, $id_shop)
        ));
		
		return $this->smarty->fetch(_PS_MODULE_DIR_ . 'featuressupplier/pdf/custom_template_footer.tpl');
	}
 
	/**
	 * Returns the template filename
	 * @return string filename
	 */
	public function getFilename()
	{
		return 'custom_pdf.pdf';
	}
 
	/**
	 * Returns the template filename when using bulk rendering
	 * @return string filename
	 */
	public function getBulkFilename()
	{
		return 'custom_pdf.pdf';
	}
}