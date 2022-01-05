<?php
namespace Hiecor\PaymentMethod\Block;

use Magento\Framework\Filesystem\DriverInterface;

use Magento\Backend\Block\Widget\Context;

class Index extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var DriverInterface
     */
     private $driver;

    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        DriverInterface $driver,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->directoryList       = $directoryList;
        $this->driver              = $driver;
        $varPath                   = $directoryList->getPath('var');
    }

    public function getMyCustomMethod()
    {
        $filename = 'hiecor_log.log';

        $pathLogfile = $this->directoryList->getPath('log') . DIRECTORY_SEPARATOR . $filename;
        //tail the length file content
        $lengthBefore = 5000000;
        try {
            $contents = '';
            $handle = $this->driver->fileOpen($pathLogfile, 'r');
            fseek($handle, -$lengthBefore, SEEK_END);
            if (!$handle) {
                return "Log file is not readable or does not exist at this moment. File path is "
                . $pathLogfile;
            }

            if ($this->driver->stat($pathLogfile)['size'] > 0) {
                $contents = $this->driver->fileReadLine(
                    $handle,
                    $this->driver->stat($pathLogfile)['size']
                );
                if ($contents === false) {
                    return "Log file is not readable or does not exist at this moment. File path is "
                        . $pathLogfile;
                }
                $this->driver->fileClose($handle);
            }
            return nl2br(nl2br($contents));
        } catch (\Exception $e) {
            return $e->getMessage() . $pathLogfile;
        }
    }
}