<?php declare(strict_types=1);

namespace App\Service\Notification;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\Entry;
use App\Entity\EntryComment;
use App\Entity\EntryCommentNotification;
use App\Entity\EntryNotification;
use App\Entity\Notification;
use App\Factory\MagazineFactory;
use App\Repository\MagazineSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

class EntryCommentNotificationManager
{
    use NotificationTrait;

    public function __construct(
        private MagazineSubscriptionRepository $magazineSubscriptionRepository,
        private IriConverterInterface $iriConverter,
        private MagazineFactory $magazineFactory,
        private PublisherInterface $publisher,
        private Environment $twig,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function send(EntryComment $comment): void
    {
        $subs      = $this->getUsersToNotify($this->magazineSubscriptionRepository->findNewEntrySubscribers($comment->getEntry()));
        $followers = [];

        $usersToNotify = $this->merge($subs, $followers);

        $this->notifyMagazine(new EntryCommentNotification($comment->getUser(), $comment));

        if (!\count($usersToNotify)) {
            return;
        }

        foreach ($usersToNotify as $subscriber) {
            $notification = new EntryCommentNotification($subscriber, $comment);
            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }

    private function getResponse(EntryCommentNotification $notification): string
    {
        return json_encode(
            [
                'commentId'    => $notification->getComment()->getId(),
                'notification' => $this->twig->render('_layout/_toast.html.twig', ['notification' => $notification]),
            ]
        );
    }

    private function notifyMagazine(EntryCommentNotification $notification): void
    {
        try {
            $iri = $this->iriConverter->getIriFromItem($this->magazineFactory->createDto($notification->getComment()->getMagazine()));

            $update = new Update(
                $iri,
                $this->getResponse($notification)
            );

            ($this->publisher)($update);

        } catch (\Exception $e) {
        }
    }
}