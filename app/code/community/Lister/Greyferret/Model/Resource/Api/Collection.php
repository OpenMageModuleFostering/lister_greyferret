<?php

/**
 * Repesents Lister_Greyferret Api.
 *
 * PHP version 5.5
 *
 * @category  PHP
 * @package   Lister_Greyferret_Model_Resource_Api_Collection
 * @author    info@listertechnologies.com
 * @license   General Public License
 * @link      https://greyferret.com/
 */

class Lister_Greyferret_Model_Resource_Api_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
	/**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {	
        $this->_init('greyferret/api');
    }
}
