# EIC Messages
The message implementation is based on a message bus like implementation.
The module provides a MessageBusInterface as well as a QueueMessageBus service which implements it.

## MessageBus
The bus handles the dispatching of messages (as array or message entities) and act as a collector for handlers.
A handler's job is to provide stamps to apply on all concerned messages and implement the handle method which will be called by the bus.

## How to
### Create a notification/stream message
Inject the correct message bus instance.
Please be aware that the StreamHandler doesn't put the item in the queue but saves it instead.
Only handlers having the QueueItemProducerTrait does it for the moment (E.g: NotificationHandler).
```
$messageBus->dispatch([
'template' => 'notify_new_membership_request',
'uid' => 1,
'field_title' => 'Hello world!"
]);
```
