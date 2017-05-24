# Channable Connect for Magento® 2

The Channable Connect extension makes it effortless to connect your Magento® 2 catalog with the Channable platform. Please note this extension is still in BETA.

## Installation
To make the integration process as easy as possible for you, we have developed various plugins for your webshop software package. 
This is the manual for installing the Magento® 2 Plugin.
Before you start up the installation process, we recommend that you make a backup of your webshop files, as well as the database.
   
   There are 2 different methods to install the Magento® 2 extension.
   1.	Install by using Composer 
   2.	Install by using the Magento® Marketplace (not yet available)
   
### Installation using Composer ###
   Magento® 2 use the Composer to manage the module package and the library. Composer is a dependency manager for PHP. Composer declare the libraries your project depends on and it will manage (install/update) them for you.
   
   Check if your server has composer installed by running the following command:
   ```
   composer –v
   ``` 
   If your server doesn’t have the composer install, you can easily install it. https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx
   
   Step-by-step to install the Magento® 2 extension by Composer:
   
   1.	Run the ssh console.
   2.	Locate your Root
   3.	Install the Magento® 2 extension
   4.	Cache and Deploy
   
   1.Run your SSH Console to connect to your Magento® 2 store
   2.Locate the root of your Magento® 2 store.
   3.Enter the command line in your Root and wait as composer will download the extension for you:
   ```
   composer require magmodules/magento2-channable
   ```
   4.When it’s finished you can clean the caches and deploy the content in your Magento® environment using the following command line;
   
   ```
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```
   
   If Magento® is running in production mode, deploy the static content:
   ```
   php bin/magento setup:static-content:deploy
   ```
   After the installation. Go to your Magento® admin portal, to ‘Stores’ > ‘Configuration’ > ‘Magmodules’ > ‘Channable’.
   
### Installation using the Magento® Marketplace (not yet available) ###
Get your authentication keys
Overview of Magento® authentication
The repo.magento.com repository, where Magento® 2 and third-party component Composer packages are stored, requires authentication. To provide secure authentication, we enable you to generate a pair 32-¬¬character authentication tokens you can use to access the repository. You generate, access, and can also delete or regenerate your keys using Magento® Marketplace.
   
To get your authentication keys:
   
1. Go to Magento® Marketplace.
2. Click Sign In and enter your login credentials. If you don’t have a free account, click Create an Account.   
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
