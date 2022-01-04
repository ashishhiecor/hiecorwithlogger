<?php
namespace Hiecor\PaymentMethod\Controller\Adminhtml\Create;
class Index extends \Magento\Backend\App\Action
{
         protected $resultPageFactory = false;      
         public function __construct(
                 \Magento\Backend\App\Action\Context $context,
                 \Magento\Framework\View\Result\PageFactory $resultPageFactory
         ) {
                 parent::__construct($context);
                 $this->resultPageFactory = $resultPageFactory;
         } 
         public function execute()
         {
                 $resultPage = $this->resultPageFactory->create();
                 $resultPage->setActiveMenu('Hiecor_PaymentMethod::menu');
                 $resultPage->getConfig()->getTitle()->prepend(__('HieCOR Logs'));
                 return $resultPage;
         }
         protected function _isAllowed()
         {
                 return $this->_authorization->isAllowed('Hiecor_PaymentMethod::menu');
         }
}