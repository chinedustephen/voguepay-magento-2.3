# Magento 2.2 Voguepay Payment Gateway

 - **Contributors:** stephen nkwocha
 - **Tags:** voguepay, magento 2, payment gateway, credit card, debit card, nigeria, international, mastercard, visa
 - **Tested with:** PHP 7.1+, Magento CE 2.3
 - **Stable tag:** Still in BETA
 - **License:** GPL-3 - see LICENSE.txt

Take payments on your magento 2 store using Voguepay.

Support for:

 - Credit card
 - Debit card


## Description

Accept Credit card, and Debit card and Voguepay wallet payment payment directly on your store with the Voguepay payment gateway for Magento 2.

#### Take Credit card payments easily and directly on your store

Signup for an account [here](https://voguepay.com)

Voguepay accepts payment form all over the world


### Manual Installation

*  Download the Zip file, Extract and copy the content of the __Code__ directory into your Magento's __app/code__ directory.

Or

*  Copy the zip file to your magento __app__ directory, extract the content directly in your magento app directory and the directory folder __code__ will be created or merged with existing content if it already exists.

*  Enable the Voguepay Payment module:
   From your commandline, in your magento root directory, run
   ```php bin/magento module:enable Voguepay_Payment --clear-static-content && php bin/magento setup:upgrade```

Once the `setup:upgrade` completes the module will be available in the Store Admin.



### Configure the plugin

Configuration can be done using the Administrator section of your Magento store.

* From the admin dashboard, using the left menu navigate to __Stores__ > __Configuration__ > __Sales__ > __Payment Methods__.
* Select __Voguepay Payment__ from the list of recommended modules.
* Set __Enable__ to __Yes__ and fill the rest of the config form accordingly, then click the orange __Save Config__ to save and activate.

  




### Suggestions / Contributions

For issues and feature request, [click here](https://github.com/bosunolanrewaju/magento-rave/issues).
To contribute, fork the repo, add your changes and modifications then create a pull request.


### License

##### GPL-3. See LICENSE.txt
