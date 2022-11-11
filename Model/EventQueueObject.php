<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use function is_string;
use function json_decode;
use function json_encode;

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
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private int $id;

    /**
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private string $uuid;

    /**
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private string $commandUuid;

    /**
     * @ORM\Column(type="integer")
     */
    private int $version;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $created;

    /**
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private string $event;

    /**
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private string $aggregateClass;

    /**
     * @ORM\Column(type="integer")
     */
    private int $user;

    /**
     * @ORM\Column(type="text")
     */
    private string $payload;

    /**
     * @ORM\Column(type="string")
     */
    private string $message;

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

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getCommandUuid(): string
    {
        return $this->commandUuid;
    }

    public function setCommandUuid(string $commandUuid): self
    {
        $this->commandUuid = $commandUuid;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getAggregateClass(): string
    {
        return $this->aggregateClass;
    }

    public function setAggregateClass(string $aggregateClass): self
    {
        $this->aggregateClass = $aggregateClass;

        return $this;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPayload(): array
    {
        return json_decode($this->payload, true);
    }

    public function setPayload(array $payload): self
    {
        $this->payload = json_encode($payload);

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}

class_alias('RevisionTen\CQRS\Model\EventQueueObject', 'RevisionTen\CQRS\Model\EventQeueObject');
