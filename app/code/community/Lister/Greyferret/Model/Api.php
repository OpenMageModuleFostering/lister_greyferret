<?php
/**
 * Repesents Lister_Greyferret Api.
 *
 * PHP version 5.5
 *
 * @category  PHP
 * @package   Lister_Greyferret_Model_Api
 * @author    info@listertechnologies.com 
 * @license   General Public License
 * @link      https://greyferret.com/
 */

class Lister_Greyferret_Model_Api extends Mage_Core_Model_Abstract
{
    /**
     * The greyferret url.
     *
     * @var string
     */
    private $_url;
    
    /**
     * The greyferret client key.
     *
     * @var string
     */ 
    private $_clientKey;
    
    /**
     * The time out of api call.
     *
     * @var integer
     */    
    private $_timeOut;
    
    /**
     * Category object.
     *
     * @var object
     */
    private $_categoryObj;
    
    /**
     * Order object.
     *
     * @var object
     */    
    private $_orderObj;
    
    /**
     * Order collection Url.
     *
     * @var object
     */ 
    private $_orderCollectionObj;
    
    /**
     * Customer object.
     *
     * @var object
     */ 
    private $_customerObj;
    
    /**
     * Cron status flag.
     *
     * @var string
     */ 
    protected $cronStatus = false;
    
    /**
     * Log status flag.
     *
     * @var string
     */ 
    protected $apiLog = false;
    
    /**
     * Category list (Order,Customer,Category).
     *
     * @var integer
     */   
    protected $categoryList;
    
    /**
     * Api State (true/false)
     *
     * @var boolean
     */ 
    protected $apiState = false;
    
    /**
     * The country object.
     *
     * @var object
     */
    protected $countryObj;
    
    /**
     * The primary id.
     *
     * @var int
     */
    protected $currentId;
    
    /**
     * The primary id.
     *
     * @var int
     */
    protected $_userObj;
	
	/**
     * The primary id.
     *
     * @var int
     */
    protected $entityTypeArray = array("customerEntity" => "customer", "catalogEntity" => 'category', "orderEntity" => "order");
	
	/**
     * The primary id.
     *
     * @var int
     */
    protected $entityStatusArray = array("initiate" => "Initiated", "inprogress" => "Inprogress", "pending" => "Pending", "complete" => 'Completed');

    const MODULE_NAME = 'Greyferret';
    const LOGFILE = 'greyferret.log';
    const CONFIG_DIR_API = 'greyferret_options/configurable_cron/%s';
    const API_URL = 'greyferret_api_url';
    const USER_NAME = 'greyferret_use_name';
    const CLIENT_KEY = 'greyferret_api_key';
    const TIME_OUT = 'greyferret_timeout';
    const CRON_STATUS = 'greyferret_cron';
    const CATEGORY_LIST    = 'greyferret_list';
    const API_STATE = 'greyferret_state';
    const DEBUG_MODE = 'greyferret_debug';
    const CATEGORY_URL = 'category';
    const CUSTOMER_URL = 'customer'; 
    const ORDER_URL = 'order';
    
    /**
    * Prepare a Greyferret API call.
    * @param array $options CURL option array.
    * @return void
    */
    public function _construct($options = array())
    { 
	    parent::_construct();
		$this->_init('greyferret/api');				
        $this->cronStatus=self::getConfig(self::CONFIG_DIR_API, self::CRON_STATUS);
        $this->categoryList = self::getConfig(self::CONFIG_DIR_API, self::CATEGORY_LIST);
        $this->apiState = self::getConfig(self::CONFIG_DIR_API, self::API_STATE);
        $this->apiLog = self::getConfig(self::CONFIG_DIR_API, self::DEBUG_MODE);
        $this->_timeOut = self::getConfig(self::CONFIG_DIR_API, self::TIME_OUT);
        $this->_url = self::getConfig(self::CONFIG_DIR_API, self::API_URL);
        $this->_userName = self::getConfig(self::CONFIG_DIR_API, self::USER_NAME);
        $this->_clientKey = self::getConfig(self::CONFIG_DIR_API, self::CLIENT_KEY);
        $this->_orderObj = Mage::getModel('sales/order');
        $this->_categoryObj = Mage::getModel('catalog/category');
        $this->_customerObj = Mage::getResourceModel('customer/customer_collection');
        $this->_userObj = Mage::getModel('customer/customer');
        $this->countryObj = Mage::getModel('directory/country');        
		$this->extractVar($this->entityTypeArray);
		$this->extractVar($this->entityStatusArray);	
        $this->_handler = new Varien_Http_Adapter_Curl();
        $opt = array(
            'timeout'    => $this->_timeOut,
            'verifypeer' => false,
            'verifyhost' => false
        );
        if (!empty($options)) {
            $opt = array_merge($opt, $options);
        }
        $this->_handler->setConfig($opt);
        $this->_handler->addOption(CURLINFO_HEADER_OUT, true);
        $this->_handler->addOption(CURLOPT_RETURNTRANSFER, true);
        unset($opt);        
    } 
    
    /**
    * Run the greyferret service for the order,customer,category.
    * @return string
    */
    public function run()
    {		
        $option = explode(',', $this->categoryList);
        if ($this->cronStatus) {
            foreach ($option as $list) {
                switch ($list) {
                case 1:				    
                    $this->getCustomerList();
                    $this->selectGreyferretLog($this->customerEntity);
                    $customerStatus = $this->entityStatus;
                    break;

               case 2:
                    $this->getCategoryList();
                    $this->selectGreyferretLog($this->catalogEntity);
                    $catalogStatus = $this->entityStatus;
                    break;
                    
                case 3:
                    $this->getOrderList();
                    $this->selectGreyferretLog($this->orderEntity);
                    $orderStatus = $this->entityStatus;
                    break;
                }
            }
            if($customerStatus == $this->complete && $catalogStatus == $this->complete && $orderStatus == $this->complete) {				
				Mage::getModel('core/config')->saveConfig('greyferret_options/configurable_cron/greyferret_state', 'Completed');				
            } else {				
				Mage::getModel('core/config')->saveConfig('greyferret_options/configurable_cron/greyferret_state', 'Completed with errors');	
	    }
	    Mage::getModel('core/config')->saveConfig('greyferret_options/configurable_cron/greyferret_cron', '0'); 	      
        } else {
            return false;
        }
    }
    /**
    * Run the greyferret service with complete category list .
    * @return string
    */
    public function getCategoryList()
    {      
        $categoryArray = array();
        $tree = $this->_categoryObj->getTreeModel();
        $tree->load();
        $collection = $tree->getCollection();		
		$this->selectGreyferretLog($this->catalogEntity);
		$this->saveLog($this->entityLastId,$this->catalogEntity,$this->entityId,$this->initiate);      
		if(!is_null($this->entityId)) {	
			$collection->addAttributeToFilter(array(
								array(
								'attribute' => 'entity_id',
								'gt' => is_null($this->entityLastId)?0:$this->entityLastId
								),
								array(
								'attribute' => 'entity_id',
								'in' => is_null($this->entityId)?'':$this->entityId
								)
							));
			$this->saveLog($this->entityLastId,$this->catalogEntity,null,$this->inprogress);							
		} else {			
			$ids = $collection->getAllIds();			
		}		
        if ($ids) {
            foreach ($ids as $id) {
                $this->_categoryObj->load($id);
                $categoryArray = $this->createCategoryArray($this->_categoryObj);				
                $this->call($categoryArray, $this->catalogEntity);
            }
        }
        $this->selectGreyferretLog($this->catalogEntity);
		if(is_null($this->entityId)) {		    
		    $this->saveLog($this->entityLastId,$this->catalogEntity,null,$this->complete);	                                                        
		}
    }
    /**
    * Create category array.
    * @param object $catObj category object.
    * @return array
    */
    public function createCategoryArray($catObj)
    {
        $returnArray = array();
        $entityId = $catObj->getId();
        $name = $catObj->getName();
        $description = $catObj->getDescription()?$catObj->getDescription():"";
        $parentId = $catObj->getParentId();
        $returnArray['categoryId'] = $entityId;
        $returnArray['categoryName'] = $name;
        $returnArray['categoryDesc'] = $description;
        $returnArray['parentCategoryId'] = $parentId;
        $returnArray['createdBy'] = 'Magento Ecommerce';
        return $returnArray;
    }
    /**
    * Run the greyferret service for complete list of orders.
    * @return string
    */    
    public function getOrderList() 
    {		       
        $orderArray = array();        
        $collection = $this->_orderObj->getCollection();
        $collection->addFieldToFilter('status', array('eq' => array('complete')));                
        $this->selectGreyferretLog($this->orderEntity);
		$this->saveLog($this->entityLastId,$this->orderEntity,$this->entityId,$this->initiate);		
		if(!is_null($this->entityId)) {	
			$collection->addFieldToFilter('entity_id',
						     array( 
							  array('in' => explode(',',$this->entityId)),
							  array('gt' => $this->entityLastId)
 						          )
						     );							
			$this->saveLog($this->entityLastId,$this->orderEntity,null,$this->inprogress);							
		}
        
        foreach ($collection as $order) {			
            $orderArray = $this->createOrderArray($order);	    
            $this->call($orderArray, $this->orderEntity, true);           
        }
        $this->selectGreyferretLog($this->orderEntity);
		if(is_null($this->entityId)) {
			$this->saveLog($this->entityLastId,$this->orderEntity,null,$this->complete);	
		}    
    }
    /**
    * Create order array.
    * @param object $orderObj order object.
    * @return array
    */    
    public function createOrderArray($orderObj) 
    {
	    $returnArray = $productArray = array();
        $orderid = $orderObj->getId();
        $items = $orderObj->getAllVisibleItems();        
        $customerid = $orderObj->getCustomerId();
        $date = $orderObj->getCreatedAt();            
        foreach ($items as $item) { 			
	        $returnArray['Order_id'] = trim($item->getOrderId());                  
            $returnArray['customer_id'] = $customerid;
            $returnArray['createdBy'] = 'Magento Ecommerce';
            $returnArray['currency_code'] = $orderObj->getBaseCurrencyCode();
            $returnArray['date_created'] = $date;                       
            $product_id = $item->getProductId();                    
            $product = $item->getProduct();                    
            $cats = $product->getCategoryIds();                
            $category_id = $cats[0];
            $category = $this->_categoryObj->load($category_id);                
            $productArray['categoryId'] = $category->getId();                            
            $productArray['categoryName'] = $category->getName();
            $productArray['discount'] = $item->getDiscountAmount();
			$productArray['qty'] = $item->getQtyOrdered();
			$productArray['sku'] = trim($item->getSku());
            $productArray['skuName'] = $item->getName();
            $productArray['totalAmount'] = $item->getPrice();
			$returnArray['products'] = array($productArray);
            $returnArray['purchaseDate'] = $date;                
        }             
        return $returnArray;            
    }
    /**
    * Run the greyferret service for complete list of customers.
    */
    public function getCustomerList() 
    {	    
             
        $customerArray = array();                
        $collection = $this->_customerObj
            ->addNameToSelect()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('group_id')
            ->addAttributeToSelect('email')
            ->joinAttribute('shipping_street', 'customer_address/street', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_postcode', 'customer_address/postcode', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_city', 'customer_address/city', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_telephone', 'customer_address/telephone', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_fax', 'customer_address/fax', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_region', 'customer_address/region', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_country_code', 'customer_address/country_id', 'default_shipping', null, 'left');
                
        $this->selectGreyferretLog($this->customerEntity); 
        $this->saveLog($this->entityLastId,$this->customerEntity,$this->entityId,$this->initiate); 
        
		if(!is_null($this->entityId) ) {	
			$collection->addAttributeToFilter(array(
				array(
				'attribute' => 'entity_id',
				'gt' => is_null($this->entityLastId)?0:$this->entityLastId
				),
				array(
				'attribute' => 'entity_id',
				'in' => is_null($this->entityId)?'':$this->entityId
				)
				));
				
			$this->saveLog($this->entityLastId,$this->customerEntity,null,$this->inprogress);									
		   }		
			
		foreach ($collection as $item) {
				$customerArray = $this->createCustomerArray($item);             
				$this->call($customerArray,$this->customerEntity);		
			}  
		$this->selectGreyferretLog($this->customerEntity);
		if(is_null($this->entityId)) {
			$this->saveLog($this->entityLastId,$this->customerEntity,null,$this->complete);	
		}
    }
    /**
    * Create customer array.
    * @param object $customerObj customer object.
    * @return array
    */    
    public function createCustomerArray($customerInfo) 
    {
        if (is_object($customerInfo)) {
			$returnArray['customerId'] = $customerInfo->getId();
			$returnArray['firstName'] = $customerInfo->firstname;
			$returnArray['lastName'] = $customerInfo->lastname;
			$returnArray['createdBy'] = $customerInfo->created_at;
			$returnArray['Address1'] = $customerInfo->shipping_street;                        
			$returnArray['pinCode'] = $customerInfo->shipping_postcode;
			$returnArray['email1'] = $customerInfo->email;
			$returnArray['city'] = $customerInfo->shipping_city;
			$returnArray['state'] = $customerInfo->shipping_region;                                               
			$countryCode = $customerInfo->shipping_country_code;	
        } else {                        
            $returnArray = array();
            $returnArray['customerId'] = empty($customerInfo['customer_id'])?$customerInfo['parent_id']:$customerInfo['customer_id'];
            $returnArray['firstName'] = $customerInfo['firstname'];
            $returnArray['lastName'] = $customerInfo['lastname'];
            $returnArray['email1'] = empty($customerInfo['email'])?$this->findEmail($customerInfo['customer_id']):$customerInfo['email'];
            $returnArray['createdBy'] = $customerInfo['updated_at'];
            $returnArray['Address1'] = $customerInfo['street'];                      
            $returnArray['pinCode'] = $customerInfo['postcode'];   
            $returnArray['city'] = $customerInfo['city'];   
            $returnArray['state'] = $customerInfo['region'];                                                  
            $countryCode = $customerInfo['country_id']; 
        }

        if (!empty($countryCode)) {                                                
            $country = $this->countryObj->loadByCode($countryCode);
            $returnArray['country'] = $country->getName(); 
        }       
        return $returnArray;
    }
        
    /**
    * Runs the service call.
    * @param   string  $request  The files and directories to process. For
    * @param   string  $entityType The set of code sniffs we are testing
    * @param   boolean $bulk     If true, send bulk list.
    * @return  string.
    */
    protected function call($request,$entityType,$bulk=true)
    {		
        try {
            $this->_headers = array(
            'Content-Type: application/json',
            'Accept: application/json',           
            'Authorization: Basic '. base64_encode($this->_userName.":".Mage::helper('core')->decrypt($this->_clientKey))
            );
                
            switch($entityType) {
            case 'category':
                $url = $this->_url.self::CATEGORY_URL;
                $currentId = $request['categoryId'];
                break;
            case 'customer':                
                $url = $this->_url.self::CUSTOMER_URL;
                $currentId = $request['customerId'];                             
                break;
            case 'order':
                $url = $this->_url.self::ORDER_URL;
                $currentId = $request['Order_id'];
                break;    
            }
           
			if(array_key_exists('0', $request)) {
					$json =$request;
			} else {
				    $json =array($request);
			}			
            $post = Mage::helper('core')->jsonencode($json);                      
            $this->_handler->write(
                Zend_Http_Client::POST,
                $url,
                '1.1',
                $this->_headers,
                $post    
            );
        
            $response = $this->_handler->read();
                    
            $status = intval($this->_handler->getInfo(CURLINFO_HTTP_CODE));
            $error = array(
            'number' => $this->_handler->getErrno(),
            'message' => $this->_handler->getError()
            );                    
            $this->_handler->close();
            if($this->apiLog) {
				Mage::log(
					"url: ".$url.
					"\nentityType Type: ".$entityType." - ".$currentId.
					"\nError Code: ".$error['number'].
					"\nError Message: ".$error['message'].
					"Response Data: ".trim($response)."\n", 
					null, 
					self::LOGFILE
				);
		    }                                                                              
            $result   = $this->responseCode($currentId,$status, $entityType, $bulk);   
        }
        catch (Exception $e) {
			if($this->apiLog) {
                Mage::logException($e);
            }    
        }
        return $result;
    }
        
    /**
    * Handle the response code from curl request.
    * @param   string  $lastId last insert id
    * @param   string  $status rest call status
    * @param   string  $entityType entity type
    * @param   boolean $bulk   If true, send bulk list.
    * @return  string.
    */        
    protected function responseCode($lastId,$status,$entityType,$bulk) 
    {
        try {
            switch ($status) {                
            case 400:
				if($bulk == true) {
                	$this->savePid($lastId,$entityType);
                }
                Mage::throwException('Not a valid inputs:');                                         
                break;            
            case 200:
				$this->saveLog($lastId,$entityType,NULL,$this->pending);
				break;                   
            } 
            return true;
        } catch (Exception $e) {
            $this->_errorCode    = $e->getCode();
            $this->_errorMessage = $e->getMessage();
        }
        return false;
    }
    
    /**
    * Get the configuration from the given URI.
    * @param   string  $dir   path
    * @param   boolean $field field.
    * @return  string.
    */
    protected function getConfig($dir,$field) 
    {
        try {                        
            return  trim(Mage::getStoreConfig(sprintf($dir, $field))) ? trim(Mage::getStoreConfig(sprintf($dir, $field))) : false;                 
        } catch (Exception $exc) { 
            return false; 
        }
    }
    
    /**
    * Get the configuration from the given URI.
    * @param   string  $lastId last insert id    
    * @param   string  $entityType entity type
    * @param   boolean $pId primary id.
    * * @param   string  $status rest call status
    * @return  string.
    */
    protected function saveLog($lastId,$entityType,$pId,$status) 
    {
        try {  
			$inputArray = array();
			if(!is_null($lastId)) {
				$inputArray['entityLastId'] = $lastId;
			} else {
				$inputArray['entityLastId'] = null;	
			}
			if(!is_null($pId)) {
				$inputArray['entityId'] = $pId;
			} else {
				$inputArray['entityId'] = null;	
			}
			if(!is_null($entityType)) {
				$inputArray['entityType'] = $entityType;
			} else {
				$inputArray['entityType'] = null;	
			}	
			if(!is_null($status)) {
				$inputArray['entityStatus'] = $status;			
			} else {
				$inputArray['entityStatus'] = null;	
			}
		
			if(count($inputArray)>0) {			    										
				$model = Mage::getModel('greyferret/api');                 
				$this->selectGreyferretLog($entityType);
				if(is_null($this->greyId)) {					
					$model->setData($inputArray);
					$insertId = $model->save()->getId();	
				} else {
					$model->load($this->greyId)->addData($inputArray);						
					$model->setId($this->greyId)->save();
				}
			}
        } catch (Exception $exc) { 
            return false; 
        }
    }
    
    
    /**
    * Get the configuration from the given URI.
    * @param   string  $entityType   entity type
    * @return  string.
    */
    protected function selectGreyferretLog($entityType) 
    {
        try {  
			$model = Mage::getModel('greyferret/api');			    
			$collection = $model->getCollection($entityType);
			$collection->addFieldToFilter('entityType', $entityType);
			$inputLog = $collection->getData();
			if(count($inputLog) > 0) {
				$extractLog = call_user_func_array('array_merge', $inputLog);
				$this->extractVar($extractLog);
			} else {
				$this->greyId=$this->entityType=$this->entityLastId=$this->entityStatus=null;
			}
			if(count($extractLog) > 0)              					
				return true;
			else
				return false;
	 } catch (Exception $exc) { 
            return false; 
        }
    }
    
    /**
    * Get the configuration from the given URI.
    * @param   string  $entityType   entity type
    * @return  string.
    */
    protected function readEntityId($entityType) 
    {
        try {  
			 $this->selectGreyferretLog($entityType);
			 return true;
 	} catch (Exception $exc) { 
            return false; 
        }
    }
    
    /**
    * Get the configuration from the given URI.
    * @param   string  $lastId   last inserted id
    * @param   string  $entityType   entity type
    * @return  string.
    */
    protected function savePid($lastId,$entityType) 
    {
        try {
			$this->selectGreyferretLog($entityType);			 			 
			$pid = is_null($this->entityId)?$lastId:$this->entityId.','.$lastId;
			$this->saveLog($this->entityLastId,$this->entityType,$pid,$this->pending);  			 			 
	} catch (Exception $exc) { 
            return false; 
        }
    }    

	/**
    * Get the configuration from the given URI.
    * @param   array  $paramArray
    * @return  string.
    */
    protected function extractVar($paramArray) 
    {
        try {	   
			foreach($paramArray as $key => $value) {
				$this->$key = $value;
			} 			 			 
	} catch (Exception $exc) { 
            	return false; 
        }
    }
    
    /**
    * Get the configuration from the given URI.
    * @param   string  $customerId   customer id    
    * @return  string.
    */
    protected function findEmail($customerId) 
    {
        try { 
				$customerData = $this->_userObj->load($customerId)->getData();
				$email = $customerData['email'];				
				return $email;
            				 			 
	} catch (Exception $exc) { 
            	return false; 
        }
    }
}        


        
