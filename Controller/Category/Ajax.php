<?php
namespace Cybage\Multifilter\Controller\Category;

use Magento\Framework\App\Action\Context;

class Ajax extends \Magento\Framework\App\Action\Action 
{

    /**
     *
     * @var \Magento\Framework\Session\Generic
     */
    protected $_multifilterSession;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param Context $context
     * @param \Magento\Framework\Session\Generic $session
     */
    public function __construct(
		\Magento\Framework\App\Action\Context $context, 
		\Magento\Framework\Session\Generic $multifilterSession, 
		\Magento\Framework\Registry $coreRegistry
	) {
        $this->_multifilterSession = $multifilterSession;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Intialization of request
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
            
			$this->_multifilterSession->setType('custom');
            
			$this->_objectManager->get('\Magento\Framework\Session\SessionManager')
				->setCurrentPage($currentPage);
				
            $this->_coreRegistry->register('activeSortOpt', $activeSortOpt);
            $this->_coreRegistry->register('activeLimit', $activeLimit);
            
			$this->_multifilterSession->setActiveLimit($activeLimit);
            $this->_multifilterSession->setActiveSort($activeSortOpt);
            $this->_multifilterSession->setViewMode($viewMode);
            
			$this->_coreRegistry->register('type', '');
            $this->_coreRegistry->register('catagories', $categories);
            $this->_coreRegistry->register('attributes', $attributes);
            
			$this->_view->loadLayout();
            $layout = $this->_view->getLayout();
            $block = $layout->getBlock('category.products.list');
            $this->getResponse()->setBody($block->toHtml());
            $this->_view->loadLayoutUpdates();
        }
    }
}