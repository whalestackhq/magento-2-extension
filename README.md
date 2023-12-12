# Whalestack Extension for Magento 2

This is the official Magento 2 extension for the [Whalestack](https://www.whalestack.com) cryptocurrency payment gateway. Easily accept Bitcoin, stablecoins (EURC, USDC) and other cryptocurrencies from your customers and directly settle payments in your preferred currency.

The Bitcoin extension for Magento 2 helps you elevate your Magento shop to new heights by offering crypto and stablecoin payment methods. It implements the PHP REST API documented at https://www.whalestack.com/en/api-docs

## Key Features

* Accepts cryptocurrencies and stablecoins payments from customers.
* Instantly settles in crypto or stablecoins.
* Sets the product price in your national currency - 45 fiat currencies are available, see full list [here](https://www.whalestack.com/en/currencies).
* Integrates seemlessly into Magento 2
* Sets the product price in your national currency.
* Sets the checkout page in your preferred language.
* Automatically generates invoices.
* Eliminates chargebacks and gives you control over refunds.
* Eliminates currency volatility risks due to instant conversions and settlement.
* Translates the plugin into any required language.
* Includes payment state management for underpaid and completed payments.

## Supported Shop Currencies

Argentine Peso (ARS), Australian Dollar (AUD), Bahraini Dinar (BHD), Bangladeshi Taka (BDT), Bermudian Dollar (BMD), Bitcoin (BTC), Brazilian Real (BRL), British Pound (GBP), Canadian Dollar (CAD), Chilean Peso (CLP), Chinese Yuan (CNY), Czech Koruna (CZK), Danish Krone (DKK), Emirati Dirham (AED), Ethereum (ETH), Euro (EUR), Hong Kong Dollar (HKD), Hungarian Forint (HUF), Indian Rupee (INR), Indonesian Rupiah (IDR), Israeli Shekel (ILS), Japanese Yen (JPY), Korean Won (KRW), Kuwaiti Dinar (KWD), Litecoin (LTC), Malaysian Ringgit (MYR), Mexican Peso (MXN), Myanmar Kyat (MMK), New Zealand Dollar (NZD), Nigerian Naira (NGN), Norwegian Krone (NOK), Pakistani Rupee (PKR), Philippine Peso (PHP), Polish Zloty (PLN), Ripple (XRP), Russian Ruble (RUB), Saudi Arabian Riyal (SAR), Singapore Dollar (SGD), South African Rand (ZAR), Sri Lankan Rupee (LKR), Stellar (XLM), Swedish Krona (SEK), Swiss Franc (CHF), Taiwan Dollar (TWD), Thai Baht (THB), Turkish Lira (TRY), Ukrainian Hryvnia (UAH), US Dollar (USD), Venezuelan Bolivar (VEF), Vietnamese Dong (VND)

## Requirements

* A Whalestack merchant account -> Sign up [here](https://www.whalestack.com).
* API Key and API Secret -> Find them [here](https://www.whalestack.com/en/api-settings).
* Complete your account master data [here](https://www.whalestack.com/en/account-settings).

## Extension installation

* Create a folder structure in Magento root as app/code/Whalestack/PaymentGateway.
* Download and extract the zip folder from Github and upload the extension files to app/code/Coinqvest/PaymentGateway.
* Login to your SSH and run below commands:

    ```bash
    php bin/magento setup:upgrade
  
    // For Magento version 2.0.x to 2.1.x
    php bin/magento setup:static-content:deploy
  
    // For Magento version 2.2.x & above
    php bin/magento setup:static-content:deploy –f
   
    php bin/magento cache:flush
    
    rm -rf var/cache var/generation var/di var/page_cache generated/*
  
    ```
   
# Module Configuration

A detail configuration guide is available [here](https://www.whalestack.com/en/magento).

Please also inspect our [API documentation](https://www.whalestack.com/en/api-docs) for more info or send us an email to service@whalestack.com.

Support and Feedback
--------------------
Your feedback is appreciated! If you have specific problems or bugs with this Magento module, please file an issue on Github. For general feedback and support requests, send an email to service@whalestack.com.

## Contributing
1. Fork it ( https://github.com/whalestackhq/magento-2-module/fork )
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create a new Pull Request