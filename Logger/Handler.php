<?php
namespace Hiecor\PaymentMethod\Logger;
 
use Monolog\Logger;
 
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::CRITICAL;
 
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/hiecor_log.log';
}