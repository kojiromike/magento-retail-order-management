![eBay Enterprise](static/logo-vert.png)

**Magento Retail Order Management Extension**
# Troubleshooting Guide

The intended audience for this guide is Magento system integrators. You should review the [Magento Retail Order Management Extension Overview](OVERVIEW.md) and [Installation and Configuration Guide](INSTALL.md) before proceeding.

Knowledge of [TBD], is assumed in this document.

## Contents

- [Common Configuration Problems](#common-configuration-problems)
  - [SFTP Credentials](#sftp-credentials)
  - [Web Services](#web-services)
  - [Order Create Retry](#order-create-retry)
- [Using the System and Exception Logs](#using-the-system-and-exception-logs)
  - [ROM Extended Magento Logging](#rom-extended-magento-logging)
  - [Reading the System Log](#reading-the-system-log)
    - [Log Format](#log-format)
    - [Monitoring and Viewing Logs](#monitoring-and-viewing-logs)
  - [Log Messages: Examples](#log-messages-examples)
  - [Reading the Exception Log](#reading-the-exception-log)

## Common Configuration Problems

Configuration issues are the most common cause of problems.

### SFTP Credentials

The private key must be pasted as-is, with newlines. Note that the Public Key (Fig. 1) is displayed as soon as the private key is correctly installed. Use the Test SFTP Connection button to ensure you are able to connect.

![ts-privatekey](static/rom-ts-privatekey-config.png)

### Web Services
The API Key (Fig. 2) is obscured, so the only way to ensure it is correct is to use the Test API Connection.

![ts-webservices](static/rom-ts-webservices-config.png)

### Order Create Retry

The cron job ```eb2c_order_create_retry``` attempts to resend OrderCreateRequests for orders that were successfully processed through payment, but for some reason, had not been submitted to the OMS.

If the eb2c_order_create_retry is unable to retrieve the original OrderCreateRequest (which is stored along with the order), it will log a message at the _warn_ level, and leave the order status unchanged.

This means that the eb2c_order_create_retry job will continue to try to resubmit the order. Manual intervention will be needed to resolve the issue.

The warning message will look something like this:

```2014-08-13T17:28:21+00:00 WARN (4): [ EbayEnterprise_Eb2cOrder_Model_Create::retryOrderCreate ]: Original OrderCreateRequest not found: 00054100000031```

In this example, Magento Order 00054100000031 will need manual attention in order to be processed.

## Using the System and Exception Logs

Troubleshooting typically involves reviewing configuration options and occasionally enabling logging in order to review system and exception logs.

By default, Magento ships with logging turned off. During system implementation, setting the Log Level to ```INFO``` should suffice. ```DEBUG``` is intended for system developers, and is extremely verbose - it may introduce more noise into the logs than desired for most needs.

### ROM Extended Magento Logging

The Magento Retail Order Management Extension provides expanded logging capabilities to help diagnose configuration issues and other problems.

The system logs messages based on a Log Level set in the configuration. Depending upon the configuration, the system will log when it encounters an error, or when it issues a request to an outside services, when it receives a message, etc.

![rom-config icon](static/rom-ov-log-config.png)

### Reading the System Log

As the extension interacts with continuously evolving external systems, it is difficult to quantify every possible message that may be received. Instead, we rely on the external systems to return actionable error messages, which are placed into the System Log.

The actual messages may change over time. However, armed with a general knowledge of the logs and their formats, troubleshooting most problems should be straightforward.

In this section, we will review:

* _Log Format_ the general format of a log message
* _Monitoring_ the system from the command line
* _Examples_ some specific log examples to give you an idea of real-world messages

> **_IMPORTANT!_** During system testing you should monitor requests and responses to ensure that the remote host is correctly configured for your installation. The ROM Extension requires corresponding configuration both in Magento and on the remote host. The remote host will provide helpful messages that are needed to resolve configuration issues.


#### Log Format
The system log is a plain text file, with the general format of:

```
YYYY-MM-DDThh:mm:ss+TZ LOGLEVEL (n): [Module_Class::Method] Message
```
where:

* The first column is the timestamp of the message.
* ```LOGLEVEL (n)``` Indicates the severity of the message, along with the numeric value of that severity. The higher the log value, the less severe the message.
* ```[Module_Class(optional ::Method)]``` indicates which Module emitted the entry
* ```Message``` is the text of the log message - which can span multiple lines

#### Monitoring and Viewing Logs
As the log file is a plain text file, any editor will be able to peruse it. You can also

```
tail -f log/system.log
```

to watch the log and monitor entries near realtime.

----
### Log Messages: Examples
In the section, we will examine logging for two sets of interactions with the Inventory ROM Services. The intent is to provide you with a general sense of how messages are presented, and what is logged.

Of note:

* Messages can span multiple lines.
* Some XML requests or responses can be large; it will help to have a reliable XML formatter into which you can paste XML log message that are difficult to parse by eye.

#### Example: Inventory Service Requests and Response

##### Quantity

* Request: this is an outbound Quantity Request, issued when an item is added to cart:

```
2014-06-02T19:27:21+00:00 INFO (6): [EbayEnterprise_Eb2cCore_Model_Api] Validating request:
<QuantityRequestMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><QuantityRequest itemId="45-1112" lineId="item0"></QuantityRequest></QuantityRequestMessage>
```

* Response: a successful response

```
2014-06-02T19:27:22+00:00 INFO (6): [EbayEnterprise_Eb2cCore_Model_Api] Received response for request to https://beta5-na.gsipartners.com/v1.0/stores/MAGT1/inventory/quantity/get.xml:
HTTP/1.1 200 OK
Connection: close
Content-length: 265
Content-type: application/xml; charset=UTF-8
Date: Mon, 02 Jun 2014 19:27:24 GMT
Server: Mule EE Core Extensions/3.3.2

<?xml version="1.0" encoding="UTF-8"?>
<QuantityResponseMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0">

      <QuantityResponse lineId="item0" itemId="45-1112">
      <Quantity>991</Quantity>
   </QuantityResponse>

</QuantityResponseMessage>
```


##### Allocation

* Request. A good example of where the XML can start to get a little more tricky to review by eye, but not impossible. Here we have requested an allocation of quantity 1 for SKU 45-1112.

```
2014-06-02T19:28:47+00:00 INFO (6): [EbayEnterprise_Eb2cCore_Model_Api] Validating request:
<AllocationRequestMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0" requestId="MAGTNA-MAGT1-14" reservationId="MAGTNA-MAGT1-14"><OrderItem itemId="45-1112" lineId="24"><Quantity>1</Quantity><ShipmentDetails><ShippingMethod>ANY_STD</ShippingMethod><ShipToAddress><Line1>123 Main St.</Line1><City>Philadelphia</City><MainDivision>PA</MainDivision><CountryCode>US</CountryCode><PostalCode>19106</PostalCode></ShipToAddress></ShipmentDetails></OrderItem></AllocationRequestMessage>
```

* Response. We have been allocated a quantity of 1.

```
2014-06-02T19:28:49+00:00 INFO (6): [EbayEnterprise_Eb2cCore_Model_Api] Received response for request to https://beta5-na.gsipartners.com/v1.0/stores/MAGT1/inventory/allocations/create.xml:
HTTP/1.1 200 OK
Connection: close
Content-length: 330
Content-type: application/xml; charset=UTF-8
Date: Mon, 02 Jun 2014 19:28:51 GMT
Server: Mule EE Core Extensions/3.3.2

<?xml version="1.0" encoding="UTF-8"?>
<AllocationResponseMessage xmlns="http://api.gsicommerce.com/schema/checkout/1.0"
                           reservationId="MAGTNA-MAGT1-14">
   <AllocationResponse lineId="24" itemId="45-1112">
      <AmountAllocated>1</AmountAllocated>
   </AllocationResponse>
</AllocationResponseMessage>
```

---


###Reading the Exception Log

The Exception log is not enhanced by the ROM Extension. Although the ROM Extension will throw Exceptions for some errors, it will _always_ log the problem to the System Log as well.

So, when an exception is encountered:

1. Review the Exception Log.
2. If the exception is being thrown by ```EbayEnterprise_Eb2c*``` module, proceed to the System log.
