# WooCommerce Subscriptions Gifting

[![Build Status](https://travis-ci.org/Prospress/woocommerce-subscriptions-gifting.svg?branch=master)](https://travis-ci.org/Prospress/woocommerce-subscriptions-gifting) [![codecov.io](http://codecov.io/github/Prospress/woocommerce-subscriptions-gifting/coverage.svg?token=d9aaaF18bY&branch=master)](http://codecov.io/github/Prospress/woocommerce-subscriptions-gifting?branch=master)

[WooCommerce Subscriptions](https://www.woothemes.com/products/woocommerce-subscriptions/) makes it possible to offer subscription products in your WooCommerce store.

But what happens if a customer wants to purchase a subscription for someone else?

They could enter the recipient's shipping address for physical products to make sure they receive packages, but what about virtual products, like memberships? And what happens if the recipient moves before the subscription ends, how do they update their shipping address?

WooCommerce Subscriptions Gifting solves these issues and many more, by making it possible for one person to purchase a subscription product for someone else. It then shares the subscription between the purchaser and recipient.

Whether customers are adding products to their cart from the single product page, reviewing their cart or checking out, customers can choose to purchase subscriptions for others by entering the recipient's email address. WooCommerce Subscriptions Gifting will take care of the rest.

## Setup
### Installation

To install Gifting:

1. Download the latest version of the plugin [here](https://github.com/Prospress/woocommerce-subscriptions-gifting/archive/master.zip)
1. Go to **Plugins > Add New > Upload** administration screen on your WordPress site
1. Select the ZIP file you just downloaded
1. Click **Install Now**
1. Click **Activate**

### Corporate Purchasing
As well as purchasing gifts for family and friends, Gifting's underlying logic is also suited to corporate purchasing. When communicating with the purchaser or recipient, more generic terminology, like "Recipient", and more generic phrases, like "purchased a subscription for you" are used so that Gifting can be used in both scenarios with very little customisability required. The only reference to the word 'gift' in customer-facing text is the checkbox label,  _"This is a gift"_, displayed on the Single Product, Checkout and Cart pages (see the [Customer's View](https://github.com/Prospress/woocommerce-subscriptions-gifting#customers-view) section). This text is configurable via a setting in **WooCommerce > Settings > Subscriptions**.

![](https://cldup.com/TlpgBAZQpr.png)

## Customer's View

There are three pages where a customer can choose to purchase a subscription product for someone else:
* Single Product Page
* Cart Page
* Checkout Page

#### Single Product Page
To purchase a subscription for another customer via the Single Product page, a customer can:

1. Go to a subscription product’s Single Product Page
3. Select the checkbox _"This is a gift"_
4. Type in the recipient’s email address
5. Click **Sign Up Now**

![](https://cldup.com/kXmf1yXzGC-3000x3000.png)

#### Cart Page
To gift a subscription via the cart page, the customer can: 

1. Go to the _Cart_ page
1. Click the checkbox _"This is a gift"_
1. Type in the recipient’s email address
1. Click **Update Cart**

![](https://cldup.com/sFeLKUucKK-3000x3000.png)

#### Checkout
To gift a subscription via the checkout, the customer can: 

1. Go to the _Checkout_ page
1. Click the checkbox _"This is a gift"_
1. Type in the recipient’s email address
1. Click **Place Order**

![](https://cldup.com/kA3UOUCnyE-3000x3000.png)

## Shared Subscription Management
Once a subscription product has been purchased for a recipient, both the recipient and purchaser will have access to view and manage certain aspects of the subscription over its lifecycle via the **My Subscriptions** table on the **My Account** Page.

The table below outlines the actions recipients and purchasers can make on gifted subscriptions:

| Action                     | Recipient    | Purchaser   |
|:--------------------------|:------------:|:-----------:|
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

## Other Notes

### Mini Cart Widget
To make it clear to customers whether products are to be gifted or not, the cart items that have been assigned a gift recipient will display recipient details alongside the cart items in the mini cart.

![](https://cldup.com/7Z_W7LHABo-3000x3000.png)

### Completing an Order with Gifted Subscriptions
Once an order with a gifted subscription is placed, customers are given an overview of their order. Here the purchaser can see which products in their order have been purchased and for who.

As seen above, the details of this order are not shared with recipients in case additional products are purchased with the gifted subscription product.

![](https://cldup.com/HNLhiWHwPi-3000x3000.png)

### Processing Orders with Gift Recipients

When an order containing a gifted subscription product is created, the gift recipient email is stored alongside their order line items. When processing orders, this allows you to see which line items in an order are being purchased for a different recipient. 

![](https://cldup.com/pen2oWA7uk-3000x3000.png)

**Note:** _If the recipient is subsequently deleted, in addition to the behaviour already discussed [here](https://github.com/Prospress/woocommerce-subscriptions-gifting/blob/master/README.md#deleting-subscription-recipients), the original order and renewal orders will maintain this information once the recipient is deleted, however, future renewal orders will no longer hold this information._

### WooCommerce Memberships Integration

If your store makes use of the WooCommerce Subscriptions and [WooCommerce Memberships](http://www.woothemes.com/products/woocommerce-memberships/) plugins the ability to purchase membership subscriptions is important. Which is why we have made sure we integrate with WooCommerce Memberships. 

In a nutshell, gift recipients who are purchased a subscription product tied to a membership plan will receive the benefits of the membership rather than the purchaser. All the features of managing the memberships granted through gifted subscriptions remain intact, you can pause, edit, cancel and delete memberships granted to recipients just as normal through the **WooCommerce > Memberships** page.  

### Subscription Grouping

WooCommerce Subscriptions version 2.0 creates subscriptions by [**grouping products**](http://docs.woothemes.com/document/subscriptions/multiple-subscriptions/#section-3) based on their billing schedule.

Gifting will also group products based on the recipient so that even if products have the same billing schedule, if they are for different recipients, they will not be grouped together.

For example, if a customer purchases 3 x monthly subscriptions in the one transaction and two are a gift for the same recipient, 2 subscriptions would be created - one with one line item for the first recipient and the second with the two line items purchased for the other recipient.

This separation of subscriptions for recipient and purchaser allows each subscription to be managed and renewed separately and allows for both the recipient and purchaser to manage their subscriptions separately, even if they were purchased in the same transaction.

### View Subscription Page

When visiting the View Subscription Page, both recipient and purchaser are given a couple of extra details to assist in managing their subscription. 

#### Recipient/Purchaser Details
Details of the other participating person in the gifted subscription are displayed alongside their role in the customer details table on the view subscription page.

<img src="https://cldup.com/6fQ8Lw7s8h-3000x3000.jpeg" height="192" width="555" style="display: block; margin: 0.6em auto;">
<img src="https://cldup.com/hqPdbqpMWB-3000x3000.jpeg" height="195" width="555" style="display: block; margin: 0.6em auto;">

#### Purchaser Details on Renewal Order
Because both recipient and purchaser can pay manual or failed renewals, the renewal purchaser's details are outlined in the Related Orders table.

<img src="https://cldup.com/OjB5qSWSIw-3000x3000.png" height="192" width="538">

*Note: Purchaser details are only displayed if the current user did not purchase the renewal.* 

#### Recipient Account Creation

In the event that a recipient does not have an account with your store at the time of being gifted a subscription, an account will be created in order for them to manage their subscription. Login details of the account, as well as information about where they can manage their new subscription, is sent to the recipient via email.

The first time the new recipient logs into their account they will be prompted to enter their shipping address as well as change their password.

![](https://cldup.com/hbICzQoNWr-3000x3000.png)

<img src="https://cldup.com/W_Qf0vhrOr.png" height="778" width="522" align="right">
#### Shipping Address

When a gifted subscription is purchased, the shipping address of the subscription is set to the recipient's shipping address. This streamlines the process for customers proceeding through the checkout when purchasing subscription products for other users.

*Additional Notes:*
- *If the recipient does not have an account or has not set the shipping address on their account at the time of purchasing the subscription, the subscriptions shipping address will not be set and will display as N/A. This will then be updated when the recipient logs in and enters their details*
- *In order to maintain a gifted subscription's shipping address when manually renewing, the recipient's shipping address will automatically be entered into the checkout shipping fields.*

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

When a downloadable product is purchased for a recipient, by default the recipient is granted the permissions to download the files attached to that product. However it is possible to grant download permissions to both recipient and purchaser.

To enable dual permissions:

1. Go to the **WooCommerce > Settings** administration screen
1. Click the **Subscriptions** tab
1. Scroll down to the **Gifting Subscriptions** section
1. Click **Downloadable Products** to enable or disable dual permissions

Similar to the normal process for downloading files, recipients can download the files they have been granted access to from the **My Account** page and the **My Account > View Subscription** page.

#### Deleting a Recipient's Account

<img src="https://cldup.com/yRwaLdSYSg-3000x3000.png" height="263" width="497" align="right">

WordPress provides an administration interface for [deleting user accounts](http://codex.wordpress.org/Users_Users_SubPanel#Delete_Users).

When deleting a recipient's user account, the recipient will also be removed from any subscription he or she is associated with.

A warning message will be displayed outlining which subscriptions the user will be removed from.

It's important to note that the subscription(s) will not be deleted. The subscription(s) will continue to behave as normal for the purchaser once the subscription recipient has been deleted.
