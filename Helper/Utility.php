<?php

namespace Hiecor\PaymentMethod\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;

class Utility extends AbstractHelper
{
    public function __construct(
     	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
     {
     	$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
     	$this->scopeConfig = $scopeConfig;
     }

    
	//get config details from *core_config_data* table 
	public function getConfig()
	{	
		$configData = array();
	    $configData['userName'] = $this->scopeConfig->getValue('payment/hiecor_paymentmethod/user_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$configData['authKey'] = $this->scopeConfig->getValue('payment/hiecor_paymentmethod/authorization_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$configData['hiecorUrl'] = $this->scopeConfig->getValue('payment/hiecor_paymentmethod/hiecor_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$configData['agentId'] = $this->scopeConfig->getValue('payment/hiecor_paymentmethod/agent_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);	
		
		$configData['hiecorSource'] = $this->scopeConfig->getValue('payment/hiecor_paymentmethod/hiecor_source', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$configData['isMethodActive'] = $this->scopeConfig->getValue('payment/hiecor_paymentmethod/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		return $configData;
	}

    //common api post data method
    public function postApiCall($product_details,$endPoint)
    {	
    	$configData = $this->getConfig();
    	$hiecorUrl = rtrim($configData['hiecorUrl'],"/");
    	$hiecorUrl = $hiecorUrl.'/'.$endPoint;

		$ch = curl_init();
		$dataString = json_encode($product_details);
		$ch = curl_init($hiecorUrl);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
		    'X-USERNAME: '.$configData['userName'],
		    'X-AUTH-KEY: '.$configData['authKey'],
		    'X-AGENT-ID: '.$configData['agentId']
			)
		);
		$result = curl_exec($ch);
		$response = json_decode($result,true);
		curl_close($ch);
		return $response;
	}

    //common api GET data method
    public function getApiCall($endPoint)
    {	
    	$configData = $this->getConfig();
    	$hiecorUrl = rtrim($configData['hiecorUrl'],"/");
    	$hiecorUrl = $hiecorUrl.'/'.$endPoint;

		$ch = curl_init();
	    $headers = array(
	    'Content-Type: application/json',
		    'X-USERNAME: '.$configData['userName'],
		    'X-AUTH-KEY: '.$configData['authKey'],
		    'X-AGENT-ID: '.$configData['agentId']
	    );
	    curl_setopt($ch, CURLOPT_URL, $hiecorUrl);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	    $result = curl_exec($ch);
		$response = json_decode($result,true);
		curl_close($ch);
		return $response;
	}

	
	public function getProductImages($magentoPId)
	{
        $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($magentoPId);        
		$image = $product->getMediaGalleryImages();
		
        $images = array();
		foreach ($image as $key => $value) {
			$images[] = $value->getUrl();
		}
		return $images;
	}
    
}
