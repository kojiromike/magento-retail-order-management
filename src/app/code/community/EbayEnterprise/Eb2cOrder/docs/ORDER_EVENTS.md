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

| Relative Config Path | Provided Value |
|-----------------|-------------|
| vhost | / |
| port | 5672 |
| connection_type | PhpAmqpLib\Connection\AMQPConnection |
| connection_insist | 0 |
| connection_login_method |  |
| connection_locale | en-US |
| connection_timeout | 3 |
| connection_read_write_timeout | 3 |
| connection_context |  |
| exchange_type | direct |
| exchange_name |  |
| exchange_passive | 0 |
| exchange_durable | 0 |
| exchange_auto_delete | 0 |
| exchange_internal | 0 |
| exchange_nowait | 0 |
| queue_passive | 0 |
| queue_durable | 1 |
| queue_exclusive | 0 |
| queue_auto_delete | 0 |
| queue_nowait | 0 |
| route_keys |  |
| queue_binding_nowait | 0 |
| message_event_suffix | order_event |

## Order Event to Magento Status Mappings

Much of the support for order events consists of updating the order to be in an appropriate state and status in Magento. In such cases, the order state for the event will be defined below. The order status to use is configured in the Magento admin configuration under System -> Configuration -> Retail Order Management -> Order Management.

## Supported Events

| Order Event | Description |
|-------------|-------------|
| BackOrder   | Orders will be placed into a "holded" state in Magento and the status [configured for Backorder](#order-event-to-magento-status-mappings). |
| Cancel      | Orders will be canceled and the status will be set to what is configured for the following reasons: [Full Order Cancel](#order-event-to-magento-status-mappings) and [Payment Authorization Failure](#order-event-to-magento-status-mappings) |
