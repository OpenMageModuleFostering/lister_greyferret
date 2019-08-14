<?php
/**
 * Repesents Lister_Greyferret Api.
 *
 * PHP version 5.5
 *
 * @category  PHP
 * @package   Lister_Greyferret_Block_Adminhtml_System_Config_Date
 * @author    Vellaidurai R <vellaidurai.rajendran@listertechnologies.com>
 * @copyright 2015 Lister Technologies Pvt Ltd 
 * @license   copyright @ listertechnologies.com 
 * @link      https://greyferret.com/
 */

class Lister_Greyferret_Block_Adminhtml_System_Config_Date extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
    * Date display in admin panel.
    * @param object $element varient data form element object.
    * @return array
    */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $date = new Varien_Data_Form_Element_Date;
        $format = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
 
        $data = array(
            'name'      => $element->getName(),
            'html_id'   => $element->getId(),
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
        );
        $date->setData($data);
        $date->setValue($element->getValue(), $format);
        $date->setFormat(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
        $date->setClass($element->getFieldConfig()->validate->asArray());
        $date->setForm($element->getForm());
 
        return $date->getElementHtml();
    }
}
