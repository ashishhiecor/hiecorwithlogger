<?php

namespace Hiecor\PaymentMethod\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Hiecor\PaymentMethod\Helper\Utility;

class CheckoutProductSave implements ObserverInterface 
{

    private $logger;
    protected $orderRepository;
    protected $action;

    public function __construct(
        \Hiecor\PaymentMethod\Logger\Logger $logger, 
        \Magento\Sales\Model\Order $order, 
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository, 
        \Magento\Catalog\Model\ResourceModel\Product\Action $action, 
        Utility $helper) 
    {

        $this->logger = $logger;
        $this->order = $order;
        $this->orderRepository = $orderRepository;
        $this->action = $action;
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) 
    {
        try {
            //get payment method config details
            $configData = $this->helper->getConfig();
            $hiecorUrl = $configData['hiecorUrl'];
            $userName = $configData['userName'];
            $authKey = $configData['authKey'];

            if (empty($hiecorUrl) || empty($userName) || empty($authKey)) {
                $message = 'This product cannot be synced to Hiecor, Please fill all mandatory fields in Hiecor Payment Method.';
                $this->logger->critical('Error CheckoutProductSave', ['message' => $message]);
                return false;
            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $observer->getEvent()->getOrder();
            $orderId = $order->getIncrementId();
            $order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
            $orderItems = $order->getAllVisibleItems();

            foreach ($orderItems as $key => $product) {
                $magentoPId = $product->getProductId();
                $productRepo = $objectManager->get('Magento\Catalog\Model\Product')->load($magentoPId);
                $storeId = $product->getStoreId();
                $hiecorProductId = $productRepo->getData('hiecor_product_id');

                if (!empty($hiecorProductId)) {
                    continue;
                }

                $sku = $product->getSku();
                $manageStock = $objectManager->get('\Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku');
                $qty = $manageStock->execute($sku);
                $unlimited_stock = (isset($qty[0]['manage_stock']) && $qty[0]['manage_stock'] == 1) ? 0 : 1;
                $stocks = (($unlimited_stock == 0) && !empty($qty[0]['qty'])) ? $qty[0]['qty'] : 0;

                $orderedQty = $product->getQtyOrdered();

                $categories = $productRepo->getCategoryIds(); /* will return category ids array */
                $categoryName = array();

                foreach ($categories as $category) {
                    $cat = $objectManager->create('Magento\Catalog\Model\Category')->load($category);
                    $categoryName[] = $cat->getName();
                }

                $product_details = array(
                    'title' => $product->getName(),
                    'price' => $product->getPrice(),
                    'stock' => $stocks + $orderedQty,
                    "type" => "straight",
                    "long_description" => "",
                    "short_description" => "",
                    "product_code" => $sku,
                    "weight" => "0",
                    "length" => "0",
                    "width" => "0",
                    "height" => "0",
                    "price_special" => "0",
                    "unlimited_stock" => $unlimited_stock,
                    "taxable" => "Yes",
                    "raw_product_cost" => "",
                    "upc" => "",
                    "category_name" => !empty($categoryName) ? $categoryName : 'Uncategorized',
                    "external_prod_id" => $magentoPId,
                    "external_prod_source" => "Magento",
                    "days_or_months" => "months",
                    "sub_days_between" => "",
                    "sub_lifetime" => "",
                    "hide_from_pos" => "0",
                    "hide_from_web" => "1",
                    "images" => $this->helper->getProductImages($magentoPId)
                );

                // Insert product in hiecor
                // Check in Product lookup by product code product exist or not in hiecor
                $endPoint = 'rest/v1/product/search/?product_code=' . urlencode($sku);
                $this->logger->critical('CheckoutProductSave search request ' . $magentoPId, ['requestData' => $endPoint]);
                $hiecorProduct = $this->helper->getApiCall($endPoint);
                $this->logger->critical('CheckoutProductSave search response ' . $magentoPId, ['responseData' => $hiecorProduct]);

                if (!empty($hiecorProduct['success']) && !empty($hiecorProduct['data'])) {
                    if (count($hiecorProduct['data']) > 1) {
                        $message = 'This product cannot be synced to Hiecor.More then one product have same SKU : ' . $sku;
                        $this->logger->critical('Error CheckoutProductSave', ['message' => $message]);
                        continue;
                    } else {
                        $hiecorPId = !empty($hiecorProduct['data'][0]['product_id']) ? $hiecorProduct['data'][0]['product_id'] : '';
                    }
                } elseif ($hiecorProduct['success'] == false && is_null($hiecorProduct['data']) && !empty($hiecorProduct['error'])) {
                    $message = 'Invalid Credentials in Hiecor Payment Method. ' . $hiecorProduct['error'];
                    $this->logger->critical('Error CheckoutProductSave', ['message' => $message]);
                } else {
                    $endPoint = 'rest/v1/product/create-product/';
                    $this->logger->critical('CheckoutProductSave request ' . $magentoPId, ['requestData' => $product_details]);
                    $response = $this->helper->postApiCall($product_details, $endPoint);
                    $this->logger->critical('CheckoutProductSave response ' . $magentoPId, ['responseData' => $response]);
                    $hiecorPId = !empty($response['data'][0]['product_id']) ? $response['data'][0]['product_id'] : '';
                }

                //create mapping in magento table
                if (!empty($hiecorPId)) {
                    $this->action->updateAttributes([$magentoPId], ['hiecor_product_id' => $hiecorPId], $storeId);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }
    }

}
