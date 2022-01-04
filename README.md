magento2-Hiecor-PaymentMethod
=============================

Hiecor payment gateway Magento2 extension


Install
=======

1. Go to Magento2 root folder

2. Enter following commands to install module:

    ```bash
    composer config repositories.Hiecor git "https://github.com/ashishhiecor/Hiecor.git"
    composer require hiecor/paymentmethod:dev-master
    ```
   Wait while dependencies are updated.

3. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable Hiecor_PaymentMethod --clear-static-content
    php bin/magento setup:upgrade
    ```
4. Enable and configure Hiecor Payment Method in Magento Admin under Stores/Configuration/Sales/Payment Methods/Hiecor Payment Method
