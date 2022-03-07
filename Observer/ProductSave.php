<?php

namespace Hiecor\PaymentMethod\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Hiecor\PaymentMethod\Helper\Utility;

class ProductSave implements ObserverInterface
{ 

	private $logger;
	protected $action;
 
    public function __construct(
     	\Hiecor\PaymentMethod\Logger\Logger $logger,
     	\Magento\Framework\Message\ManagerInterface $messageManager,
     	\Magento\Catalog\Model\ResourceModel\Product\Action $action,
        Utility $helper) 
 	{
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->action = $action;
        $this->helper   = $helper;
 	}	
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	try {
    		
    		//get payment method config details
            $configData = $this->helper->getConfig();
            $hiecorUrl  = $configData['hiecorUrl'];
            $userName   = $configData['userName'];
            $authKey    = $configData['authKey'];
            $agentId    = $configData['agentId'];

            if(empty($hiecorUrl) || empty($userName) || empty($authKey) || empty($agentId)){
            	$message = __('This product cannot be synced to Hiecor, Please fill all mandatory fields in Hiecor Payment Method.'); 
				$this->messageManager->addErrorMessage($message);
				$this->logger->critical('Error ProductSave', ['message' => 'This product cannot be synced to Hiecor, Please fill all mandatory fields in Hiecor Payment Method.']);
            	return false;
            }

	        $product  = 	$observer->getEvent()->getProduct();  // get product object
	        $storeId  =     $product->getStoreId(); // get store id
	        $sku      = 	$product->getSku();
	        $magentoPId= 	$product->getId();
	        $name     = 	$product->getName();
	        $price    = 	$product->getPrice();
	        $stocks   =     $product->getStockData();
	        $images   = 	$this->helper->getProductImages($magentoPId);

			$hiecorProductId = $product->getData('hiecor_product_id');
			$unlimited_stock = ($stocks['manage_stock'] == 1) ? 0 : 1;
			$qty      =    (($stocks['manage_stock'] == 1) && !empty($stocks['qty'])) ? $stocks['qty'] : 0;


			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$products = $objectManager->get('Magento\Framework\Registry')->registry('current_product');
			$categories = $products->getCategoryIds(); /*will return category ids array*/
			$categoryName = array();

			foreach($categories as $category){
			    $cat = $objectManager->create('Magento\Catalog\Model\Category')->load($category);
			    $categoryName[] = $cat->getName();
			}
	        
   			$product_details = array(
		                   'title'        => $name,
		                   'price'        => $price,
		                   'stock'        =>  $qty,
		                   "type"        =>"straight",
		                   "long_description"=>"",
		                   "short_description"=>"",
		                   "product_code"=>$sku,
		                   "weight"=>"0",
		                   "length"=>"0",
		                   "width"=>"0",
		                   "height"=>"0",
		                   "price_special"=>"0",
		                   "unlimited_stock"=>$unlimited_stock,
		                   "taxable"=>"Yes",
		                   "raw_product_cost"=>"",
		                   "upc"=>"",
		                   "category_name"=> !empty($categoryName) ? $categoryName : 'Uncategorized',
		                   "external_prod_id"=>$magentoPId,
		                   "external_prod_source"=>"Magento",
		                   "days_or_months"=>"months",
		                   "sub_days_between"=>"",
		                   "sub_lifetime"=>"",
		                   "hide_from_pos"=>"0",
		                   "hide_from_web"=>"1",
		                   "images"=>$images
		        );
		    
				// Insert & update product in hiecor
   				$this->logger->critical('ProductSave request '.$magentoPId, ['requestData' => $product_details]);
	        	
	        	if(!empty($hiecorProductId)){
					$product_details['product_id'] = $hiecorProductId;
					$endPoint='rest/v1/product/update-product/';
					$response = $this->helper->postApiCall($product_details,$endPoint);
					$this->logger->critical('ProductSave response postApiCall '.$magentoPId, ['responseData' => $product_details]);

					if( empty($response['success']) && is_null($response['data']) && !empty($response['error']) ) {
						$message = __('Invalid Credentials in Hiecor Payment Method. '.$response['error']); 
						$this->messageManager->addErrorMessage($message);
					}
				}else{
		        	// Check in Product lookup by product code product exist or not in hiecor
		        	// $urlSKU = str_replace(' ', '%20', $sku);
		        	//$urlSKU = urlencode($sku);
	        		$endPoint = 'rest/v1/product/search/?product_code='.$sku;
	        		$endPoint = urlencode($endPoint);

					$hiecorProduct = $this->helper->getApiCall($endPoint);
					$this->logger->critical('ProductSave response getApiCall '.$magentoPId, ['responseData' => $hiecorProduct]);

					if( !empty($hiecorProduct['success']) && !empty($hiecorProduct['data']) ){
						$hiecorPId = !empty($hiecorProduct['data'][0]['product_id']) ? $hiecorProduct['data'][0]['product_id'] : '';
						$errorMsg = "This product cannot be synced to Hiecor.More then one product have same SKU : $sku";
						$message = __($errorMsg); 
						$this->messageManager->addErrorMessage($message);
						$this->logger->critical('Error ProductSave', ['message' => $errorMsg]);

					}elseif( $hiecorProduct['success'] == false && is_null($hiecorProduct['data']) && !empty($hiecorProduct['error']) ) {
						$errorMsg = "Invalid Credentials in Hiecor Payment Method. ".$hiecorProduct['error'];
						$message = __($errorMsg); 
						$this->messageManager->addErrorMessage($message);
						$this->logger->critical('Error ProductSave', ['message' => $errorMsg]);
					
					}else{
			            $endPoint='rest/v1/product/create-product/';
			        	$response = $this->helper->postApiCall($product_details,$endPoint);
			        	$hiecorPId = !empty($response['data'][0]['product_id']) ? $response['data'][0]['product_id'] : '';
			        	$this->logger->critical('ProductSave response postApiCall '.$magentoPId, ['responseData' => $response]);
					}

			        //create mapping in magento table
			        if(!empty($hiecorPId)){
				        $this->action->updateAttributes([$magentoPId], ['hiecor_product_id' => $hiecorPId], $storeId);
					}else{
						$message = __('Product does not created in Hiecor!!!'); 
						$this->messageManager->addErrorMessage($message);
					}
		    	}
		    } catch (\Exception $e) {
		             $this->logger->critical('Error message', ['exception' => $e]);
		    }
	    
	}

	   
}

?>
