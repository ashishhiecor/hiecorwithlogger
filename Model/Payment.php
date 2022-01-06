<?php

namespace Hiecor\PaymentMethod\Model;

class Payment extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'hiecor_paymentmethod';

    protected $_code = self::CODE;

    protected $_canCapture = true;

    /**
     * Capture Payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $ccSession = $objectManager->create('Magento\Customer\Model\Session');
            $ccDetails = array(
                'CcNumber' => $payment->getCcNumber(),
                'CcCid' => $payment->getCcCid()
            );
            $ccSession->setCcData($ccDetails);

            //transaction is done.
            $payment->setIsTransactionClosed(1);

        } catch (\Exception $e) {
            $this->debug($payment->getData(), $e->getMessage());
        }

        return $this;
    }

    public function getConfigPaymentAction()
    {
        return self::ACTION_AUTHORIZE_CAPTURE;
    }

}
