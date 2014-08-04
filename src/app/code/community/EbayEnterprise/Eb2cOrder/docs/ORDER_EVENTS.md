# Order Events

The Eb2cOrder module includes support for consuming order events triggered by the ROMS OMS. The Eb2cOrder module will consume messages sent through an AMQP queue and trigger events in Magento for each ROMS order event received.

The events triggered in Magento will include the XML message sent by the ROMS OMS over AMQP. The XML message will conform to [OrderEvents.xsd](../xsd/OrderEvents.xsd).

## Events

The events dispatched in Magento for specific order events will be named according to the following specification:

1. Event names will begin with `ebayenterprise_order_event_`
2. The name of the ROMS order event will be converted from CamelCase to underscore_case.

Events dispatched for order events will include a "message" which will be a string of the XML message received from the OMS.

```php
/**
 * Sample observer method
 */
class Observer {
	public function responseToBackorderEvent($observer) {
		// retrieve the XML message from the event
		$message = $observer->getEvent()->getMessage();
		// do something with the message
		$this->_processMessage($message);
	}
}
```

## Subscriber Configuration

Configuration is provided by the AMQP module via Active Config.

The following configuration values are provided by the module and removed from the admin config:

| Configuration Name | Provided Value |
|--------------------|----------------|
| Connection Type | PhpAmqpLib\Connection\AMQPSSLConnection |
| Connection Additional | `<ssl_options><verify_peer>0</verify_peer></ssl_options>` |
| Event Suffix | order_event_message_received |

## Order Event to Magento Status Mappings

Much of the support for order events consists of updating the order to be in an appropriate state and status in Magento. In such cases, the order state for the event will be defined below. The order status to use is configured in the Magento admin configuration under System -> Configuration -> Retail Order Management -> Order Management.

## Supported Events

This section will include a list of order events support is included for and a brief description of what level of support is provided.

| Order Event | Description |
|-------------|-------------|
| BackOrder   | Orders will be placed into a "holded" state in Magento and the status [configured for Backorder](#order-event-to-magento-status-mappings). |
