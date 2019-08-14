<?php
/**
 * Repesents Lister_Greyferret Api.
 *
 * PHP version 5.5
 *
 * @category  PHP
 * @package   Lister_Greyferret_Model_Observer
 * @author    info@listertechnologies.com
 * @license   General Public License
 * @link      https://greyferret.com/
 */

class Lister_Greyferret_Model_Observer extends Lister_Greyferret_Model_Api
{
    
    /**
    * Get customer details.
    * @param object $observer customer event observer.
    * @return string 
    */ 
    public function getCustomer(Varien_Event_Observer $observer) 
    {
        $option = explode(',', $this->categoryList);
        if (in_array("1", $option)) {    
            if(get_class($observer->getCustomerAddress()) == 'Mage_Customer_Model_Address') {				
				$address = $observer->getCustomerAddress()->getData();
				if($address['is_default_billing'] == true) {														
					$customerArray[] = $this->createCustomerArray($address);
					$response = $this->getCall($customerArray, $this->customerEntity);
					return $response;
				}
			}
        } else {
            Mage::log('Customer endpoint not selected for greyferret', null, self::LOGFILE);
            return false;
        }
    }    
    
    /**
    * Get category details.
    * @param object $observer category event observer.
    * @return string 
    */
    public function getCategory(Varien_Event_Observer $observer) 
    {
        $option = explode(',', $this->categoryList);        
        if (in_array("2", $option)) {        
            $categoryArray = array();
            $category = $observer->getEvent()->getDataObject();            
            $categoryArray = $this->createCategoryArray($category);           				
            $response = $this->getCall($categoryArray, $this->catalogEntity);
            return $response;
        } else {
            Mage::log('Category endpoint not selected for greyferret', null, self::LOGFILE);
            return false;
        }            
    }
    
    /**
    * Get Order details.
    * @param object $observer order event observer.
    * @return string 
    */ 
    public function getOrder(Varien_Event_Observer $observer) 
    {
        $option = explode(',', $this->categoryList);        
        if (in_array("3", $option)) {
            $orderArray = array();
            $order = $observer->getEvent()->getData('order');           
            if ($order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE) {
                $orderArray[] = $this->createOrderArray($order);                           
                $response = $this->getCall($orderArray, $this->orderEntity);
                return $response;
            } else {
                return false;
            }                
        } else {
            Mage::log('Order endpoint not selected for greyferret', null, self::LOGFILE);
            return false;
        }                
    }
    
    /**
    * Runs the api call.
    * @param   array   $inputArray Input array send to greyferret.
    * @param   boolean $type       If true, send bulk list.
    * @return  string.
    */
    public function getCall($inputArray,$type) 
    {        		
        if ($this->apiState == 'Completed') {        
            $response = $this->call($inputArray, $type,false); 
            return $response;            
        } else {
            return false;
        }
    }    
}
