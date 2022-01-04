<?php

namespace Hiecor\PaymentMethod\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /* Product Custom Title */
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'hiecor_product_id');
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'hiecor_product_id',
            [
                'type' => 'varchar',
                'label' => 'HieCOR Product ID',
                'input' => 'text',
                'required' => false,
                'sort_order' => 40,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
            ]
        );
    }
}
