# WooCommerce Subscriptions Gifting

[![Build Status](https://magnum.travis-ci.com/Prospress/woocommerce-subscriptions-gifting.svg?token=7qcKG8toQcpjnZpuJrFT&branch=master)](https://magnum.travis-ci.com/Prospress/woocommerce-subscriptions-gifting) [![codecov.io](http://codecov.io/github/Prospress/woocommerce-subscriptions-gifting/coverage.svg?token=d9aaaF18bY&branch=master)](http://codecov.io/github/Prospress/woocommerce-subscriptions-gifting?branch=master)

This is the repository for Subscriptions Gifting. Important data:

## Branches

* `master` is used for the current released version

##  Overview
WooCommerce Subscriptions Gifting is an extension for WooCommerce Subscriptions that allows customers to purchase subscription products for recipients. 

Whether customers are adding products to their cart from the single product page, reviewing their cart or checking out, customers can choose to purchase subscriptions for others by simply entering the recipients email address. WooCommerce Subscriptions Gifting will manage the rest.

## 1.0 Setup
### 1.1 Installation
You can download the plugin from [here](https://github.com/Prospress/woocommerce-subscriptions-gifting/archive/master.zip). To install it, follow these steps:

1. Go to Plugins > Add New > Upload
2. Select the ZIP file you just downloaded
3. Click Install Now, and then Activate

## 2.0 Usage
### 2.1 Purchasing Subscription Products for Others
There are three ways customers can choose to purchase a subscription product for someone else:
* Single Product Page
* Cart Page
* Checkout Page

#### 2.1.1 Single Product Page
To purchase a subscription for another customer via the Single Product page:

1. Go to a subscription product’s Single Product Page.
3. Select the checkbox “This is a gift”.
4. Type in the recipient’s email address.
5. click “Sign Up Now”.

<img src="https://cldup.com/kXmf1yXzGC-3000x3000.png" height="621" width="740">

#### 2.1.2 Cart Page
When reviewing their cart, customers can also choose a subscription cart item to be gifted by:

1. Selecting the checkbox “This is a gift”.
2. Type in the recipient’s email address.
3. click “Update Cart”.

<img src="https://cldup.com/sFeLKUucKK-3000x3000.png" height="409" width="606">

#### 2.1.3 Checkout
To purchase a gifted subscription via the checkout: 

1. Go to the Checkout Page.
2. Click the checkbox “This is a gift”.
3. Type in the recipient’s email address.
5. click “Place Order”.

<img src="https://cldup.com/kA3UOUCnyE-3000x3000.png" height="736" width="449">

#### 2.1.4 Mini Cart Widget
In order to make it clear to customers how their cart is arranged, the cart items that have been assigned a gift recipient will display recipient details alongside their products in the mini cart. 

<img src="https://cldup.com/7Z_W7LHABo-3000x3000.png" height="534" width="242">

### 2.2 Completing an Order with Gifted Subscriptions
Once an order with a gifted subscription is placed, customers are given an overview of their order. Here the purchaser can see which products in their order have been purchased and for who. 

<img src="https://cldup.com/HNLhiWHwPi-3000x3000.png" height="721" width="450">

#### 2.2.1 Subscription Grouping
Similar to how WooCommerce Subscriptions version 2.0 creates subscriptions through [**subscription product grouping**](http://docs.woothemes.com/document/subscriptions/multiple-subscriptions/#section-3), subscription products with an equivalent billing schedule and recipient are grouped into the one subscription.

For example, if a customer purchases 3 x monthly subscriptions in the one transaction and two are a gift for the same recipient, 2 subscriptions would be created - one with one line item and the second with the two line items purchased for the recipient.

This separation of subscriptions for recipient and purchaser allows each subscription to be managed and renewed separately and allows for both the recipient and purchaser to manage their subscriptions separately.

### 2.3 Subscription Management
Once a subscription has been purchased for a recipient, they are given access to view and manage their subscription from the My Subscriptions table on the My Account Page. The table below outlines the actions recipients can make on subscriptions purchased for them:

| Action                     | Recipient    | Purchaser   |
| :--------------------------|:------------:|:-----------:|
| View Subscription          |       ✔      |      ✔      |
| Suspend Subscription       |       ✔      |      ✔      |
| Reactivate Subscription    |       ✔      |      ✔      |
| Cancel Subscription        |       ✔      |      ✔      |
| Change Shipping Address    |       ✔      |      ✔      |
| Change Payment Method      |       ✔      |      ✔      |
| Manually Renew             |       ✔      |      ✔      |
| Resubscribe                |       ✖      |      ✔      |
| Switch (upgrade/downgrade) |       ✖      |      ✔      |
| View Parent Orders         |       ✖      |      ✔      |
| View Renewal Orders        |       ✔      |      ✔      |

#### 2.3.1 Recipient and Purchaser Privacy
In order to provide a level of privacy between purchaser and recipient when managing a gifted subscription, some limitations are placed on viewing related orders and the manual renewal process. These include:

* **Viewing Parent Orders:** In order to maintain the ability to purchase multiple subscriptions and the potential to have multiple gift recipients in one order, recipients **can not** see the original order in their related orders table on the view subscription page. 
* **Manual Renewal:** Additional products **can not** be added to the cart when manually renewing a gifted subscription. _These additional products would otherwise be visible to both the recipient and purchaser._  

#### 2.3.2 Recipient Account Creation
In the event that a recipient does not have an account with your store at the time of being gifted a subscription, an account will be created in order for them to manage their subscription. Login details of the account as well as information about where they can manage their new subscription is sent to the recipient via email. 

The first time the new recipient logs into their account they will be prompted to enter their shipping address as well as change their password.

<img src="https://cldup.com/hbICzQoNWr-3000x3000.png" height="777" width="597">

#### 2.3.1 Emails
In order to keep recipients up-to-date with their gifted subscriptions, WooCommerce Subscriptions Gifting communicates with them via the following emails:

* **New Recipient Account** email: Sent to a recipient when an account is created for them. This email is sent instead of the *WooCommerce Customer New Account* email for accounts created for gifted subscription recipients.
* **New Recipient Subscription** email: Sent to recipients when an order has been placed which contains subscriptions purchased for them. The email contains the purchaser's name, email and subscription information including the line items included. 
* **Processing Renewal Order** email: Sent to a recipient when payment has been processed for a subscription now awaiting fulfilment for that billing period.
* **Completed Renewal Order** email: Sent to a recipient when the subscription renewal order is marked complete, indicating that an item for the renewal period has been shipped.

### 2.4 Processing Orders with Gift Recipients
When an order is placed which contains gifted subscription products, the gift recipient is stored alongside their order line items. When processing orders this allows you to see which line items in an order are being purchased for another customer. 

<img src="https://cldup.com/pen2oWA7uk-3000x3000.png">

## Notes
### Topics Not Yet Documented
* The differences on the view subscription page for gifted subscriptions: 
  * Purchaser/recipient name in the customer details table
  * The additional information in the related orders table - _Purchased By:_
* How downloadable products work.
* Cannot gift a product while switching
* When manually renewing a gifted subscription the recipient's shipping address is entered into the checkout. 
* ...

### Additional resources

* [Testing readme](tests/README.md)
