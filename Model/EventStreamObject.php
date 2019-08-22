<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use function is_string;
use function json_decode;
use function json_encode;

/**
 * Class EventStreamObject.
 *
 * @ORM\Entity
 * @ORM\Table(name="event_stream", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_version",
 *          columns={"version", "uuid"})
 * })
 */
class EventStreamObject
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
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
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

    public function __construct()
    {
        $this->created = new DateTimeImmutable();
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
     * @return EventStreamObject
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
     * @return EventStreamObject
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
     * @return EventStreamObject
     */
    public function setVersion($version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @param \DateTimeImmutable $created
     *
     * @return EventStreamObject
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
     * @return EventStreamObject
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
     * @return EventStreamObject
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
     * @return EventStreamObject
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
        return is_string($this->payload) ? json_decode($this->payload, true) : $this->payload;
    }

    /**
     * @param array $payload
     *
     * @return EventStreamObject
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
     * @return EventStreamObject
     */
    public function setMessage($message): self
    {
        $this->message = $message;

        return $this;
    }
}
