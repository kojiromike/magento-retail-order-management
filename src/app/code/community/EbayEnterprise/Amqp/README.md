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
