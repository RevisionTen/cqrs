<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class EventQueueObject.
 *
 * @ORM\Entity
 * @ORM\Table(name="event_qeue", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_version",
 *          columns={"version", "uuid", "user"})
 * })
 */
class EventQueueObject
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private $uuid;

    /**
     * @var string
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private $commandUuid;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * TODO: Change to datetime_immutable once https://github.com/doctrine/doctrine2/pull/6988 is fixed.
     *
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var string
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private $event;

    /**
     * @var string
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private $aggregateClass;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $user;

    /**
     * @var array
     *
     * @ORM\Column(type="text")
     */
    private $payload;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $message;

    /**
     * EventQueueObject constructor.
     *
     * @param EventStreamObject $eventStreamObject
     */
    public function __construct(EventStreamObject $eventStreamObject)
    {
        $this->setUuid($eventStreamObject->getUuid());
        $this->setCommandUuid($eventStreamObject->getCommandUuid());
        $this->setVersion($eventStreamObject->getVersion());
        $this->setCreated($eventStreamObject->getCreated());
        $this->setEvent($eventStreamObject->getEvent());
        $this->setAggregateClass($eventStreamObject->getAggregateClass());
        $this->setUser($eventStreamObject->getUser());
        $this->setPayload($eventStreamObject->getPayload());
        $this->setMessage($eventStreamObject->getMessage());
    }

    /**
     * Transforms the EventQueueObject into an EventStreamObject
     * and returns it.
     *
     * @return EventStreamObject
     */
    public function getEventStreamObject(): EventStreamObject
    {
        $eventStreamObject = new EventStreamObject();
        $eventStreamObject->setUuid($this->getUuid());
        $eventStreamObject->setCommandUuid($this->getCommandUuid());
        $eventStreamObject->setVersion($this->getVersion());
        $eventStreamObject->setCreated($this->getCreated());
        $eventStreamObject->setEvent($this->getEvent());
        $eventStreamObject->setAggregateClass($this->getAggregateClass());
        $eventStreamObject->setUser($this->getUser());
        $eventStreamObject->setPayload($this->getPayload());
        $eventStreamObject->setMessage($this->getMessage());

        return $eventStreamObject;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return EventQueueObject
     */
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return string
     */
    public function getCommandUuid(): string
    {
        return $this->commandUuid;
    }

    /**
     * @param string $commandUuid
     *
     * @return EventQueueObject
     */
    public function setCommandUuid(string $commandUuid): self
    {
        $this->commandUuid = $commandUuid;

        return $this;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return EventQueueObject
     */
    public function setVersion($version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreated(): \DateTimeImmutable
    {
        // TODO: remove createFromMutable once https://github.com/doctrine/doctrine2/pull/6988 is fixed.
        return ($this->created instanceof \DateTimeImmutable) ? $this->created : \DateTimeImmutable::createFromMutable($this->created);
    }

    /**
     * @param \DateTimeImmutable $created
     *
     * @return EventQueueObject
     */
    public function setCreated($created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @param string $event
     *
     * @return EventQueueObject
     */
    public function setEvent($event): self
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return string
     */
    public function getAggregateClass(): string
    {
        return $this->aggregateClass;
    }

    /**
     * @param string $aggregateClass
     *
     * @return EventQueueObject
     */
    public function setAggregateClass($aggregateClass): self
    {
        $this->aggregateClass = $aggregateClass;

        return $this;
    }

    /**
     * @return int
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @param int $user
     *
     * @return EventQueueObject
     */
    public function setUser($user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return \is_string($this->payload) ? json_decode($this->payload, true) : $this->payload;
    }

    /**
     * @param array $payload
     *
     * @return EventQueueObject
     */
    public function setPayload($payload): self
    {
        $this->payload = json_encode($payload);

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return EventQueueObject
     */
    public function setMessage($message): self
    {
        $this->message = $message;

        return $this;
    }
}

class_alias('RevisionTen\CQRS\Model\EventQueueObject', 'RevisionTen\CQRS\Model\EventQeueObject');
