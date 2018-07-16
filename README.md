#revision-ten/cqrs
A basic CQRS Implementation.

###Class Naming Convention and Requirements

**Aggregate:**

*Aggregate*

`extends Aggregate`

**Command:**

*AggregateAction*Command

`extends Command implements CommandInterface`

**Command Handler:**

*AggregateAction*Handler

`extends Handler implements HandlerInterface`

**Event:**

*AggregateAction*Event

`extends Event implements EventInterface`

**Event Listener:**

*AggregateAction*Listener

`implements ListenerInterface`


Example:
```
Text

TextEditCommand
TextEditHandler
TextEditEvent
TextEditListener

TextDeleteCommand
TextDeleteHandler
TextDeleteEvent
TextDeleteListener
```
