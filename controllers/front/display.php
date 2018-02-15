<?php
/**
* 2010-2017 EcomiZ
*
*  @author    EcomiZ
*  @copyright 2010-2017
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  @version  Release: 1.3.0 $Revision: 1 $
*/

class EcoPresseDisplayModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $sql = ' SELECT * FROM '._DB_PREFIX_.'eco_presse ORDER BY date_article DESC';
        $data = Db::getInstance()->ExecuteS($sql);
        $this->context->smarty->assign(
            array(
                'data' => $data,
                'width' => Configuration::get('ECO_PRESSE_WIDTH_IMG'),
                'height' => Configuration::get('ECO_PRESSE_HEIGHT_IMG'),
                'title' => Configuration::get('ECO_PRESSE_TITLE'),
                'display_legend' => Configuration::get('ECO_PRESSE_DISPLAY_LEGEND'),
                'float' => Configuration::get('ECO_PRESSE_FLOAT'),
            )
        );
        $this->setTemplate('display.tpl');
    }
}
