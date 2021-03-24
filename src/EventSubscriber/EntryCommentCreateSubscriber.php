<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\EntryCommentCreatedEvent;
use App\Message\EntryCommentNotificationMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EntryCommentCreateSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntryCommentCreatedEvent::class => 'onEntryCommentCreated',
        ];
    }

    public function onEntryCommentCreated(EntryCommentCreatedEvent $event)
    {
        $this->messageBus->dispatch(new EntryCommentNotificationMessage($event->getComment()->getId()));
    }
}