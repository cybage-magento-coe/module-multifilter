<?php
/**
 * Cybage Multifilter Layered Navigation Plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is available on the World Wide Web at:
 * http://opensource.org/licenses/osl-3.0.php
 * If you are unable to access it on the World Wide Web, please send an email
 * To: Support_ecom@cybage.com.  We will send you a copy of the source file.
 *
 * @category   Multifilter Layered Navigation Plugin
 * @package    Cybage_Multifilter
 * @copyright  Copyright (c) 2014 Cybage Software Pvt. Ltd., India
 *             http://www.cybage.com/pages/centers-of-excellence/ecommerce/ecommerce.aspx
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Cybage Software Pvt. Ltd. <Support_ecom@cybage.com>
 */
namespace Cybage\Multifilter\Block;

use Magento\Framework\View\Element\Template;

class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    
    /**
     *
     * @var \Magento\Framework\Session\Generic
     */
    public $multifilterSession;

    /**
     * @param Template\Context $context
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Model\Layer\FilterList $filterList
     * @param \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Model\Layer\FilterList $filterList,
        \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Session\Generic $multifilterSession,
        array $data = []
    ) {
        $this->multifilterSession = $multifilterSession;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct(
            $context,
            $layerResolver,
            $filterList,
            $visibilityFlag,
            $data
        );
    }
    
    /**
     * Function to get current category
     */
    public function getCurrentCat()
    {
        if (empty($this->multifilterSession->getTopCategory()) && empty($this->multifilterSession->getCategories())) {
            $currentCat = $this->_coreRegistry->registry('current_category');
            return $currentCat->getId();
        } else {
            return $this->multifilterSession->getTopCategory();
        }
    }
}
