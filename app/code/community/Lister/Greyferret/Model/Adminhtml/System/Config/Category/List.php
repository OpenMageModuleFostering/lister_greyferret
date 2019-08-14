<?php
/**
 * Repesents Lister_Greyferret Api.
 *
 * PHP version 5.5
 *
 * @category  PHP
 * @package   Lister_Greyferret_Model_Adminhtml_System_Config_Category_List
 * @author    info@listertechnologies.com
 * @copyright 2015 Lister Technologies Pvt Ltd 
 * @license   copyright @ listertechnologies.com 
 * @link      https://greyferret.com/
 */

class Lister_Greyferret_Model_Adminhtml_System_Config_Category_List
{
    /**
    * Greyferret api list.
    * @return void
    */ 
    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>Mage::helper('greyferret')->__('Customer')),
            array('value'=>2, 'label'=>Mage::helper('greyferret')->__('Category')),
            array('value'=>3, 'label'=>Mage::helper('greyferret')->__('Order')),
        );
    }
    /**
    * Greyferret api list.
    * @return void
    */
    public function toArray()
    {
            return array(
            1 => Mage::helper('greyferret')->__('Customer'),
            2 => Mage::helper('greyferret')->__('Category'),
            3 => Mage::helper('greyferret')->__('Order'),
            );
    }
}        
