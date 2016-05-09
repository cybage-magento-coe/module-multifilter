<?php

namespace Cybage\Multifilter\Controller\Category;

use Magento\Framework\App\Action\Context;

class Ajax extends \Magento\Framework\App\Action\Action {

    /**
     *
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
     * @param Context $context
     * @param \Magento\Framework\Session\Generic $session
     */
    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\Session\Generic $multifilterSession, \Magento\Framework\Registry $coreRegistry) {
        $this->multifilterSession = $multifilterSession;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Intialization of abstract methode for \Magento\Framework\App\Action\Action
     */
    public function execute() {
        $filters = $this->getRequest()->getParam('checkedFilter');
        if (!empty($filters)) {
            $categories = '';
            $attributes = '';
            foreach ($filters as $data) {
                $filterArr[] = explode('?', $data);
                $i = 0;
                foreach ($filterArr as $key => $value) {

                    //Getting all checked catagories 
                    if ($value[0] == 'category') {
                        $categories[] = $value[2];
                    }

                    //Getting all checked attributes
                    if ($value[0] == 'attribute') {
                        $attributes[$i]['name'] = $value[1];
                        $attributes[$i]['value'] = $value[2];
                    }
                    $i++;
                }
            }
            //Fetching product collection based on selected filters

            $activeLimit = $this->getRequest()->getParam('currentLimit');
            $activeSortOpt = $this->getRequest()->getParam('currentSortOpt');
            $viewMode = $this->getRequest()->getParam('viewmode');
            $currentPage = $this->getRequest()->getParam('currentPage');
            $this->multifilterSession->setType('custom');
            $this->_objectManager->get('\Magento\Framework\Session\SessionManager')->setCurrentPage($currentPage);
            $this->coreRegistry->register('activeSortOpt', $activeSortOpt);
            $this->coreRegistry->register('activeLimit', $activeLimit);
            $this->multifilterSession->setActiveLimit($activeLimit);
            $this->multifilterSession->setActiveSort($activeSortOpt);
            $this->multifilterSession->setViewMode($viewMode);
            $this->coreRegistry->register('type', '');
            $this->coreRegistry->register('catagories', $categories);
            $this->coreRegistry->register('attributes', $attributes);
            $this->_view->loadLayout();
            $layout = $this->_view->getLayout();
            $block = $layout->getBlock('category.products.list');
            $this->getResponse()->setBody($block->toHtml());
            $this->_view->loadLayoutUpdates();
        }
    }

}

?>
