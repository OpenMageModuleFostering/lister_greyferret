<?php
/**
 * Repesents Lister_Greyferret Api.
 *
 * PHP version 5.5
 *
 * @category  PHP
 * @package   Lister_Greyferret_Model_Adminhtml_System_Config_Cron_Schedule
 * @author    info@listertechnologies.com
 * @license   General Public License
 * @link      https://greyferret.com/
 */


class Lister_Greyferret_Model_Adminhtml_System_Config_Cron_Schedule extends Mage_Core_Model_Config_Data
{
    const CRON_STRING_PATH = 'crontab/jobs/greyferret_cron/schedule/cron_expr'; 
    
    /**
    * Set cron with expression
    * @return string
    */    
    protected function _afterSave()
    {   
        $Status = $this->getData('groups/configurable_cron/fields/greyferret_cron/value');            
        if ($Status == true) {
            $Time = $this->getData('groups/configurable_cron/fields/greyferret_time/value');
            $Calender = $this->getData('groups/configurable_cron/fields/greyferret_date/value');  
            $DateArray = explode('/', $Calender);
            $cronExprArray = array(
            intval($Time[1]),                                   
            intval($Time[0]),                                   
            intval($DateArray[1]),                              
            intval($DateArray[0]),                             
            '*',                                                
            intval($DateArray[2]),                              
            );
            $cronExprString = join(' ', $cronExprArray);
        } else {
            $cronExprString = "";
        }
        try {            
            Mage::getModel('core/config_data')
            ->load(self::CRON_STRING_PATH, 'path')
            ->setValue($cronExprString)
            ->setPath(self::CRON_STRING_PATH)
            ->save();
            if ($Status == false) {
                Mage::getModel('core/config')->saveConfig('greyferret_options/configurable_cron/greyferret_state', 'Disable');
            } else if ($Status == true) {                            
                Mage::getModel('core/config')->saveConfig('greyferret_options/configurable_cron/greyferret_state', 'Yet to start');
            }
        }
        catch (Exception $e) {            
            Mage::throwException(Mage::helper('cron')->__('Unable to save the cron expression.'));
 
        }                
    }
}
