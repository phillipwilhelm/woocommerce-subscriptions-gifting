# WooCommerce Subscriptions Gifting

[![Build Status](https://magnum.travis-ci.com/Prospress/woocommerce-subscriptions-gifting.svg?token=7qcKG8toQcpjnZpuJrFT&branch=master)](https://magnum.travis-ci.com/Prospress/woocommerce-subscriptions-gifting) [![codecov.io](http://codecov.io/github/Prospress/woocommerce-subscriptions-gifting/coverage.svg?token=d9aaaF18bY&branch=master)](http://codecov.io/github/Prospress/woocommerce-subscriptions-gifting?branch=master)

This is the repository for Subscriptions Gifting. Important data:

## Branches

* `master` is used for the current released version

##  Overview
WooCommerce Subscriptions Gifting is an extension for WooCommerce Subscriptions that allows customers to purchase subscription products for recipients. 

Whether customers are adding products to their cart from the single product page, reviewing their cart or checking out, customers can choose to purchase subscriptions for others by simply entering the recipients email address. WooCommerce Subscriptions Gifting will manage the rest.

## Setup
### Installation
You can download the plugin from [here](https://github.com/Prospress/woocommerce-subscriptions-gifting/archive/master.zip). To install it, follow these steps:

1. Go to Plugins > Add New > Upload
2. Select the ZIP file you just downloaded
3. Click Install Now, and then Activate

## Usage
### Purchasing Subscription Products for Others
There are three ways customers can choose to purchase a subscription product for someone else:
* Single Product Page
* Cart Page
* Checkout Page

#### Single Product Page
To purchase a subscription for another customer via the Single Product page:

1. Go to a subscription product’s Single Product Page.
3. Select the checkbox “This is a gift”.
4. Type in the recipient’s email address.
5. click “Sign Up Now”.

<img src="https://cldup.com/kXmf1yXzGC-3000x3000.png" height="621" width="740">

#### Cart Page
When reviewing their cart, customers can also choose a subscription cart item to be gifted by:

1. Selecting the checkbox “This is a gift”.
2. Type in the recipient’s email address.
3. click “Update Cart”.

<img src="https://cldup.com/sFeLKUucKK-3000x3000.png" height="409" width="606">

#### Checkout
To purchase a gifted subscription via the checkout: 

1. Go to the Checkout Page.
2. Click the checkbox “This is a gift”.
3. Type in the recipient’s email address.
5. click “Place Order”.

<img src="https://cldup.com/kA3UOUCnyE-3000x3000.png" height="736" width="449">

#### Mini Cart Widget
In order to make it clear to customers how their cart is arranged, the cart items that have been assigned a gift recipient will display recipient details alongside their products in the mini cart. 

<img src="https://cldup.com/7Z_W7LHABo-3000x3000.png" height="534" width="242">

### Completing an Order with Gifted Subscriptions
Once an order with a gifted subscription is placed, customers are given an overview of their order. Here the purchaser can see which products in their order have been purchased and for who. 

<img src="https://cldup.com/HNLhiWHwPi-3000x3000.png" height="721" width="450">

#### Subscription Grouping
Similar to how WooCommerce Subscriptions version 2.0 creates subscriptions through [**subscription product grouping**](http://docs.woothemes.com/document/subscriptions/multiple-subscriptions/#section-3), subscription products with an equivalent billing schedule and recipient are grouped into the one subscription.

For example, if a customer purchases 3 x monthly subscriptions in the one transaction and two are a gift for the same recipient, 2 subscriptions would be created - one with one line item and the second with the two line items purchased for the recipient.

This separation of subscriptions for recipient and purchaser allows each subscription to be managed and renewed separately and allows for both the recipient and purchaser to manage their subscriptions separately.

### Subscription Management
Once a subscription has been purchased for a recipient, they are given access to view and manage their subscription from the My Subscriptions table on the My Account Page. The table below outlines the actions both recipients and purchasers can make on gifted subscriptions:

| Action                     | Recipient    | Purchaser   |
| :--------------------------|:------------:|:-----------:|
| View Subscription          |       ✔      |      ✔      |
| Suspend Subscription       |       ✔      |      ✔      |
| Reactivate Subscription    |       ✔      |      ✔      |
| Cancel Subscription        |       ✔      |      ✔      |
| Change Shipping Address    |       ✔      |      ✔      |
| Change Payment Method      |       ✖      |      ✔      |
| Manually Renew             |       ✔      |      ✔      |
| Resubscribe                |       ✖      |      ✔      |
| Switch (upgrade/downgrade) |       ✖      |      ✔      |
| View Parent Orders         |       ✖      |      ✔      |
| View Renewal Orders        |       ✔      |      ✔      |

#### View Subscription Page
When visiting the View Subscription Page, both recipient and purchaser are given a couple of extra details to assist in managing their subscription. 

* **Recipient/Purchaser Details -** Details of the other participating person in the gifted subscription are displayed alongside their role in the customer details table on the view subscription page. *For example:*
<img src="https://cldup.com/6fQ8Lw7s8h-3000x3000.jpeg" height="192" width="555">
<img src="https://cldup.com/hqPdbqpMWB-3000x3000.jpeg" height="195" width="555">

* **Renewal Purchaser Details -** To give details about which renewal orders have been purchased by who, the renewal purchaser's details are outlined in the Related Orders table. 

<!---
Image 
-->

*Note: Purchaser details are only displayed if the current user did not purchase the renewal.* 

#### Recipient Account Creation
In the event that a recipient does not have an account with your store at the time of being gifted a subscription, an account will be created in order for them to manage their subscription. Login details of the account as well as information about where they can manage their new subscription is sent to the recipient via email.

The first time the new recipient logs into their account they will be prompted to enter their shipping address as well as change their password.

<img src="https://cldup.com/hbICzQoNWr-3000x3000.png" height="777" width="597">

#### Shipping Address
In addition to the differences between a typical subscription and a gifted subscription already discussed, when a gifted subscription is purchased, the shipping address of the subscription is set to the recipient's shipping address. This streamlines the process for customers proceeding through the checkout when purchasing subscription products for other users.

*Additional Notes: In order to maintain a gifted subscription's shipping address when manually renewing, the recipient's shipping address will automatically be entered into the checkout shipping fields.*

#### Recipient and Purchaser Privacy
In order to provide a level of privacy between purchaser and recipient when managing a gifted subscription, some limitations are placed on viewing related orders and the manual renewal process. These include:

* **Viewing Parent Orders:** In order to maintain the ability to purchase multiple subscriptions and the potential to have multiple gift recipients in one order, recipients **can not** see the original order in their related orders table on the view subscription page. 
* **Manual Renewal:** Additional products **can not** be added to the cart when manually renewing a gifted subscription. _These additional products would otherwise be visible to both the recipient and purchaser._  

#### Emails
In order to keep recipients up-to-date with their gifted subscriptions, WooCommerce Subscriptions Gifting communicates with them via the following emails:

* **New Recipient Account** email: Sent to a recipient when an account is created for them. This email is sent instead of the *WooCommerce Customer New Account* email for accounts created for gifted subscription recipients.
* **New Recipient Subscription** email: Sent to recipients when an order has been placed which contains subscriptions purchased for them. The email contains the purchaser's name, email and subscription information including the line items included. 
* **Processing Renewal Order** email: Sent to a recipient when payment has been processed for a subscription now awaiting fulfilment for that billing period.
* **Completed Renewal Order** email: Sent to a recipient when the subscription renewal order is marked complete, indicating that an item for the renewal period has been shipped.

Like WooCommerce emails, you can enable/disable, edit and customize these emails through settings. To edit emails sent by WooCommerce Subscriptions Gifting, go to: **WooCommerce > Settings > Emails.** 

#### Downloadable Products
WooCommerce Subscriptions Gifting supports purchasing downloadable products for recipients. 

When a downloadable product is purchased for a recipient, by default the recipient is granted the permissions to download the files attached to that product. However it is possible to grant download permissions to both recipient and purchaser through the WooCommerce Settings menu, go to **WooCommerce > Settings > Subscriptions.** 

<!---
IMAGE
-->

Similar to the normal process for downloading files, recipients can download the files they have been granted access to from the My Account Page and the View Subscription page.

<!---
Dual Image My Downloads and View Subscriptions line items. 
-->

#### Deleting Subscription Recipients
In the case that a recipient user is requested to be deleted, a warning message will be displayed outlining which users will be removed from their subscriptions. It's **important** to note that the subscription(s) will continue to behave as normal once the subscription recipient has been deleted.

### Processing Orders with Gift Recipients
When an order is placed which contains gifted subscription products, the gift recipient is stored alongside their order line items. When processing orders this allows you to see which line items in an order are being purchased for another customer. 

<img src="https://cldup.com/pen2oWA7uk-3000x3000.png">

### WooCommerce Memberships Integration
If your store makes use of the WooCommerce Subscriptions and [WooCommerce Memberships](http://www.woothemes.com/products/woocommerce-memberships/) plugins the ability to purchase membership subscriptions is important. Which is why we have made sure we integrate with WooCommerce Memberships. 

In a nutshell, gift recipients who are purchased a subscription product tied to a membership plan will receive the benefits of the membership rather than the purchaser. All the features of managing the memberships granted through gifted subscriptions remain intact, you can pause, edit, cancel and delete memberships granted to recipients just as normal through the **WooCommerce > Memberships** page.  

## Notes
### Topics Not Yet Documented
* Cannot gift a product while switching
* Images for Deleting subscription recipients and View Subscription related order table.
* ...

### Additional resources

* [Testing readme](tests/README.md)
