<?php

namespace Hiecor\PaymentMethod\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Hiecor\PaymentMethod\Helper\Utility;

class SalesOrder implements ObserverInterface 
{

    private $logger;

    public function __construct(
        \Hiecor\PaymentMethod\Logger\Logger $logger, 
        \Magento\Sales\Model\Order $order, Utility $helper) 
    {

        $this->logger = $logger;
        $this->order = $order;
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) 
    {

        try {
            //get payment method config details
            $configData = $this->helper->getConfig();
            $isMethodActive = $configData['isMethodActive'];

            $hiecorUrl = $configData['hiecorUrl'];
            $userName = $configData['userName'];
            $authKey = $configData['authKey'];

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $observer->getEvent()->getOrder();
            $order_id = $order->getIncrementId();

            if (empty($hiecorUrl) || empty($userName) || empty($authKey) || empty($isMethodActive)) {
                $message = 'This Order cannot be synced to Hiecor, Please fill all mandatory fields in Hiecor Payment Method.';
                $this->logger->critical('Error SalesOrder', ['message' => $message]);
                return false;
            }

            if (empty($order_id) && !isset($payment['method']) && $payment['method'] !== 'hiecor_paymentmethod') {
                $message = 'This Order cannot be synced to Hiecor, Please enable Hiecor Payment Method.';
                $this->logger->critical('Error SalesOrder', ['message' => $message]);
                return false;
            }


            //Get & Unset Session
            $ccSession = $objectManager->create('Magento\Customer\Model\Session');
            $ccData = $ccSession->getCcData();
            $ccSession->unsCcData();

            //fetch whole payment information
            $payment = $order->getPayment()->getData();

            $grandTotal = $order->getGrandTotal();
            $subTotal = $order->getSubtotal();

            //fetch customer information
            $customerEmail = $order->getCustomerEmail();
            $firstname = $order->getCustomerFirstname();
            $lastname = $order->getCustomerLastname();

            $shippingCost = $payment['shipping_amount'];

            $ccExpMonth = $order->getPayment()->getCcExpMonth($order_id);
            $ccExpYear = $order->getPayment()->getCcExpYear($order_id);
            $ccNumber = $order->getPayment()->getCcLast4($order_id);
            $ccType = $order->getPayment()->getCcType($order_id);

            //fetch whole billing information
            $billing_info = $order->getBillingAddress()->getData();

            //fetch whole shipping information
            $shipping_info = $order->getShippingAddress()->getData();

            $tax = $order->getTaxAmount();
            $discountAmount = $order->getDiscountAmount();

            $order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);
            $orderItems = $order->getAllVisibleItems();

            $itemPurchased = array();
            foreach ($orderItems as $key => $product) {
                $_product = $objectManager->get('Magento\Catalog\Model\Product')->load($product->getProductId());
                $hiecorPId = $_product->getData('hiecor_product_id');
                $item = array(
                    'man_price' => $product->getPrice(),
                    'tax_exempt' => false,
                    'product_id' => $hiecorPId,
                    'qty' => $product->getQtyOrdered(),
                    'is_subscription' => false
                );
                if (empty($item['product_id'])) {
                    $item['man_desc'] = $product->getName();
                }
                $itemPurchased[] = $item;
            }

            $orderAPIData = array(
                'cust_id' => '',
                'customer_info' => array(
                    'first_name' => isset($firstname) ? $firstname : '',
                    'last_name' => isset($lastname) ? $lastname : '',
                    'email' => isset($customerEmail) ? $customerEmail : '',
                    'phone' => isset($billing_info['telephone']) ? $billing_info['telephone'] : '',
                    'address' => isset($iblling_info['street']) ? $billing_info['street'] : '',
                    'address2' => '',
                    'city' => isset($billing_info['city']) ? $billing_info['city'] : '',
                    'state' => isset($billing_info['region']) ? $billing_info['region'] : '',
                    'country' => isset($billing_info['country_id']) ? $billing_info['country_id'] : '',
                    'zip' => isset($billing_info['postcode']) ? $billing_info['postcode'] : '',
                ),
                'billing_info' => array(
                    'bill_first_name' => isset($billing_info['firstname']) ? $billing_info['firstname'] : '',
                    'bill_last_name' => isset($billing_info['lastname']) ? $billing_info['lastname'] : '',
                    'bill_email' => isset($billing_info['email']) ? $billing_info['email'] : '',
                    'bill_phone' => isset($billing_info['telephone']) ? $billing_info['telephone'] : '',
                    'bill_address_1' => isset($iblling_info['street']) ? $billing_info['street'] : '',
                    'bill_address_2' => '',
                    'bill_city' => isset($billing_info['city']) ? $billing_info['city'] : '',
                    'bill_region' => isset($billing_info['region']) ? $billing_info['region'] : '',
                    'bill_country' => isset($billing_info['country_id']) ? $billing_info['country_id'] : '',
                    'bill_postal_code' => isset($billing_info['postcode']) ? $billing_info['postcode'] : '',
                ),
                'shipping_info' => array(
                    'ship_first_name' => isset($shipping_info['firstname']) ? $shipping_info['firstname'] : '',
                    'ship_last_name' => isset($shipping_info['lastname']) ? $shipping_info['lastname'] : '',
                    'ship_email' => isset($shipping_info['email']) ? $shipping_info['email'] : '',
                    'ship_phone' => isset($shipping_info['telephone']) ? $shipping_info['telephone'] : '',
                    'ship_address_1' => isset($shipping_info['street']) ? $shipping_info['street'] : '',
                    'ship_address_2' => '',
                    'ship_city' => isset($shipping_info['city']) ? $shipping_info['city'] : '',
                    'ship_region' => isset($shipping_info['region']) ? $shipping_info['region'] : '',
                    'ship_country' => isset($shipping_info['country_id']) ? $shipping_info['country_id'] : '',
                    'ship_postal_code' => isset($shipping_info['postcode']) ? $shipping_info['postcode'] : '',
                ),
                'is_billing_same' => true,
                'cart_info' => array(
                    'coupon' => '',
                    'custom_tax_id' => 'Default',
                    'products' => $itemPurchased,
                    'subtotal' => '',
                    'shipping_handling' => $shippingCost,
                    'total' => $subTotal,
                    'manual_discount' => !empty($discountAmount) ? abs($discountAmount) : 0,
                ),
                'credit' => array(
                    "cc_exp_mo" => $ccExpMonth,
                    "cc_exp_yr" => $ccExpYear,
                    "cc_account" => $ccData['CcNumber'],
                    "bp_id" => "",
                    "amount" => $grandTotal,
                    "last4" => $ccNumber,
                    "use_token" => false,
                    "cc_name" => $ccType,
                    "pay_by" => "credit",
                    "cc_cvv" => $ccData['CcCid'],
                    "digital_signature" => "",
                    "tip" => 0
                ),
                'manual_tax' => true,
                'tax' => isset($tax) ? $tax : 0,
                'merchant_id' => 0,
                'payment_type' => 'credit',
                'payment_method' => '',
                'ship_required' => '',
                'sendOrderMail' => 'yes',
                'crm_partner_order_id' => isset($order_id) ? $order_id : '',
                'order_source' => isset($configData['hiecorSource']) ? $configData['hiecorSource'] : 'Magento'
            );

            $endPoint = 'rest/v1/order/';
            $this->logger->critical('SalesOrder request ' . $order_id, ['requestData' => $orderAPIData]);
            $response = $this->helper->postApiCall($orderAPIData, $endPoint);

            if (empty($response['success']) && is_null($response['data']) && !empty($response['error'])) {
                $message = 'Invalid Credentials in Hiecor Payment Method. ' . $response['error'];
                $this->logger->critical('Error SalesOrder', ['message' => $message]);
            }

            if (!empty($response['success']) && !empty($response['data']) && empty($response['error'])) {
                $orderState = \Magento\Sales\Model\Order::STATE_COMPLETE;
                $order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
                $order->save();
            } else {
                $this->logger->critical('Error SalesOrder response ' . $order_id, ['responseData' => $response]);

                $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->save();
            }
        } catch (\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }
    }

}
