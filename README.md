# revision-ten/cqrs
A basic CQRS implementation.

**For a proper implementation with all the bells and whistles look at [prooph](https://github.com/prooph) instead.**



To create boilerplate code for your commands run:

`bin/console make:cqrscommand`

### Recommended class naming convention and requirements

##### Aggregate class:

*Aggregate*

`final class XYZ extends Aggregate`

##### Command class:

*AggregateAction*Command

`final class XYZ extends Command implements CommandInterface`

##### Command handler class:

*AggregateAction*Handler

`final class XYZ extends Handler implements HandlerInterface`

##### Event class:

*AggregateAction*Event

`final class XYZ extends Event implements EventInterface`

##### Event listener class:

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
