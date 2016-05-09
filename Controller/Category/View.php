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
 * @copyright  Copyright (c) 2016 Cybage Software Pvt. Ltd., India
 *             http://www.cybage.com/pages/centers-of-excellence/ecommerce/ecommerce.aspx
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Cybage Software Pvt. Ltd. <Support_ecom@cybage.com>
 */

namespace Cybage\Multifilter\Controller\Category;

class View extends \Magento\Framework\App\Action\Action {

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $multifilterSession;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $productModel;

    /**
     * @param Context $context
     * @param \Magento\Framework\Session\Generic $session
     */
    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\Session\Generic $multifilterSession, \Magento\Framework\Registry $coreRegistry, \Magento\Catalog\Model\Product $productModel) {
        $this->multifilterSession = $multifilterSession;
        $this->coreRegistry = $coreRegistry;
        $this->productModel = $productModel;
        parent::__construct($context);
    }

    /**
     * Intialization of abstract methode for \Magento\Framework\App\Action\Action
     */
    public function execute() {
        
    }

    /**
     * Manipulating core excute funtion for displaying multifiltered products
     *
     * @param $subject: instance of core controller excute function
     * @param $proceed: closure to decide after which step control will be jumped to core 
     * @return ajax response of product collection
     */
    public function aroundExecute(\Magento\Catalog\Controller\Category\View $subject, \Closure $proceed) {
        $moduleActivation = $this->_objectManager->get('Cybage\Multifilter\Helper\Data')->getConfig('multifilter/general/active');
        if ($moduleActivation == '1') {
            $returnValue = $proceed();
            $filters = $this->getRequest()->getParam('checkedFilter');
            $category = $this->coreRegistry->registry('current_category');
            $this->multifilterSession->setCurrentCategory($category->getId());
            $this->multifilterSession->setType('coreblock');
            $this->_view->loadLayout();
            $layout = $this->_view->getLayout();
            $block = $layout->getBlock('category.products.list');
            $this->_view->loadLayoutUpdates();
            return $returnValue;
        } else {
            $this->multifilterSession->setType('coreblock');
            $returnValue = $proceed();
            return $returnValue;
        }
    }

    /**
     * Function to fetch product collection based on selected filters
     *
     * @param $categories: array of selected category
     * @param $attributes: array of selected attributes
     * @return array of product collection
     */
    public function getProducts($categories, $attributes) {
        $registerCat = '';
        $currentCat = $this->multifilterSession->getCurrentCategory();
        if (!empty($categories)) {
            $registerCat = array_unique($categories);
        }
        $collection = $this->productModel->getCollection();
        $collection->joinField(
                'category_id', 'catalog_category_product', 'category_id', 'product_id = entity_id', null
        );
        if (!empty($registerCat)) {
            $collection->addFieldToFilter(
                    'category_id', array('in' => $registerCat)
            );
        } else {
            $childCategories = $this->getChildCat($currentCat);
            if (!empty($childCategories) && is_array($childCategories)) {
                $collection->addFieldToFilter(
                        'category_id', array('in' => $childCategories)
                );
            } else {
                $collection->addFieldToFilter(
                        'category_id', $currentCat
                );
            }
        }
        if (!empty($attributes)) {
            $finalArr = array();
            $vals = array();
            foreach ($attributes as $data) {
                $vals[$data['name']][] = $data['value'];
                $frontendType = $this->checkAttribute($data['name']);
                if ($frontendType == 'multiselect') {
                    $collection->addAttributeToFilter($data['name'], array('finset' => $data['value']));
                }
            }
            foreach ($vals as $k => $v) {
                if ($k == 'price') {
                    $price = '';
                    foreach ($v as $part => $partTwo) {
                        $price = explode('-', $partTwo);
                        if ($price[1] != '') {
                            $filters[] = array('from' => $price[0], 'to' => $price[1]);
                        } else {
                            $filters[] = array('gteq' => $price[0]);
                        }
                    }
                    $collection->addAttributeToFilter($k, array($filters));
                } else {
                    $frontendType = $this->checkAttribute($k);
                    if ($frontendType != 'multiselect') {
                        $collection->addAttributeToFilter($k, array('in' => $v));
                    }
                }
            }
        }
        $parentsId = '';
        $productId = array();
        foreach ($collection as $data) {
            $parentsId = $this->_objectManager->get('\Magento\ConfigurableProduct\Model\Product\Type\Configurable')->getParentIdsByChild($data->getId());
            if (!empty($parentsId)) {
                $finalArr[] = $parentsId[0];
            } else {
                $productId[] = $data->getId();
            }
        }
        if (!empty($finalArr)) {
            $implodedArr = array_unique($finalArr);
            return $implodedArr;
        } else {
            return array_unique($productId);
        }
    }

    /**
     * Function to fetch parent product collection based on child ids
     *
     * @param $implodedArr: array of childs from getProducts() function
     * @return array of parent product collection
     */
    public function getParentCollection($implodedArr, $activeLimit, $activeSortOpt) {
        if (!empty($implodedArr)) {
            $productCollection = $this->productModel->getCollection();
            $productCollection->addFieldToFilter('entity_id', array($implodedArr));
            $productCollection->addAttributeToSelect('*');
            $this->multifilterSession->settotalCount(count($productCollection->getData()));
            $page = $this->multifilterSession->getCurrentPage();
            if ($page > 1 && !empty($page)) {
                $lastLimit = $activeLimit * $page + 1;
                $firstLimit = $activeLimit + 1;
                $productCollection->setPage($firstLimit, $lastLimit);
                $productCollection->setPageSize($activeLimit);
            } else {
                if ($activeLimit) {
                    $productCollection->setPageSize($activeLimit);
                } else {
                    $productCollection->setPageSize(9);
                }
            }

            if ($activeSortOpt) {
                $productCollection->addAttributeToSort("$activeSortOpt", 'DESC');
            }

            if (count($productCollection->getData()) > 0) {
                return $productCollection;
            }
        } else {
            return $implodedArr;
        }
    }

    /**
     * Function to check attribute frontend type
     *
     * @param $attributeCode: attribute code
     * @return attribute frontend type
     */
    public function checkAttribute($attributeCode) {
        $attributeColl = $this->_objectManager->get('\Magento\Catalog\Model\Product\Attribute\Repository')->get($attributeCode);
        $frontendType = $attributeColl->getFrontendInput();
        return $frontendType;
    }

    /**
     * Function to child categories of current category
     *
     * @param $parentCat: parent category id
     * @return array of ids of child categories
     */
    public function getChildCat($parentCat) {
        if (!empty($parentCat)) {
            $childColl = $this->_objectManager->get('\Magento\Catalog\Model\Category')->getCategories($parentCat);
            $childCat = [];
            foreach ($childColl as $category) {
                $childCat[] = $category->getId();
            }
            if (count($childCat) > 0) {
                return $childCat;
            } else {
                return null;
            }
        }
    }

}
