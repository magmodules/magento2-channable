# Channable Connect for Magento® 2

The Channable Connect extension makes it effortless to connect your Magento® 2 catalog with the Channable platform.

## Installation
To make the integration process as easy as possible for you, we have developed various plugins for your webshop software package. 
This is the manual for installing the Magento® 2 Plugin.
Before you start up the installation process, we recommend that you make a backup of your webshop files, as well as the database.

There are 2 different methods to install the Magento® 2 extension.
1.	Install by using Composer 
2.	Install by using the Magento® Marketplace
   
### Installation using Composer ###
Magento® 2 use the Composer to manage the module package and the library. Composer is a dependency manager for PHP. Composer declare the libraries your project depends on and it will manage (install/update) them for you.

Check if your server has composer installed by running the following command:
```
composer –v
``` 
If your server doesn’t have composer installed, you can easily install it by using this manual: https://getcomposer.org/doc/00-intro.md

Step-by-step to install the Magento® 2 extension through Composer:

1.	Connect to your server running Magento® 2 using SSH or other method (make sure you have access to the command line).
2.	Locate your Magento® 2 project root.
3.	Install the Magento® 2 extension through composer and wait till it's completed:
```
composer require magmodules/magento2-channable
``` 
4.	Once completed run the Magento® module enable command:
```
bin/magento module:enable Magmodules_Channable
``` 
5.	After that run the Magento® upgrade and clean the caches:
```
php bin/magento setup:upgrade
php bin/magento cache:flush
```
6.  If Magento® is running in production mode you also need to redeploy the static content:
```
php bin/magento setup:static-content:deploy
```
7.  After the installation: Go to your Magento® admin portal and open ‘Stores’ > ‘Configuration’ > ‘Magmodules’ > ‘Channable’.
   
### Installation using the [Magento® Marketplace](https://marketplace.magento.com/magmodules-magento2-channable.html) ###
Get your authentication keys
Overview of Magento® authentication
The repo.magento.com repository, where Magento® 2 and third-party component Composer packages are stored, requires authentication. To provide secure authentication, we enable you to generate a pair 32-¬¬character authentication tokens you can use to access the repository. You generate, access, and can also delete or regenerate your keys using Magento® Marketplace.
   
To get your authentication keys:
   
1. Go to [Channable Connect extension on the Magento® Marketplace.](https://marketplace.magento.com/magmodules-magento2-channable.html)
2. Proceed the purchase (0,00)
2. Sign In and enter your login credentials. If you don’t have a free account, click Create an Account.   
3. After you log in, click My Access Keys.
4. Get your secure access keys on Magento® Marketplace
5. If you already have keys, use the Public Key as your user name and the Private Key as your password.
6. To create a new key pair, click Create a New Access Key.
7. When prompted, enter a descriptive name to identify the key pair.
8. Click Generate New. Use the Public key as your user name and the Private key as your password.
   
## Development by Magmodules

We are a Dutch Magento® Only Agency dedicated to the development of extensions for Magento® 1 and Magento® 2. All our extensions are coded by our own team and our support team is always there to help you out. 

[Visit Magmodules.eu](https://www.magmodules.eu/)

## Developed for Channable

Channable is a cloud based data feed management platform designed to greatly simplify the online advertising operations of marketing agencies and web shop owners. With Channable you can export your products to various different channels such as comparison shopping engines, affiliate platforms and marketplaces. 

[Visit Channable.com](https://www.channable.com/)

## Links

[Knowledgebase](https://www.magmodules.eu/help/magento2-channable)

[Terms and Conditions](https://www.magmodules.eu/terms.html)

[Contact Us](https://www.magmodules.eu/contact-us.html)

## Compatibility

* PHP Versions: PHP 7.2, PHP 7.3, PHP 7.4, PHP 8.1, PHP 8.2
* Magento Versions: Magento 2.3.3 up to Magento 2.4.6
