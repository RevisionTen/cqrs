# revision-ten/cqrs
A basic CQRS Implementation.

### Recommended class naming convention and requirements

#####Aggregate class:

*Aggregate*

`final class XYZ extends Aggregate`

#####Command class:

*AggregateAction*Command

`final class XYZ extends Command implements CommandInterface`

#####Command handler class:

*AggregateAction*Handler

`final class XYZ extends Handler implements HandlerInterface`

#####Event class:

*AggregateAction*Event

`final class XYZ extends Event implements EventInterface`

#####Event listener class:

*AggregateAction*Listener

`final class XYZ implements ListenerInterface`


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
