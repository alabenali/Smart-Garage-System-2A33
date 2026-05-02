<?php

declare(strict_types=1);

class RendezVousUrgenceListener
{
    private UrgenceBroadcaster $broadcaster;

    public function __construct(UrgenceBroadcaster $broadcaster)
    {
        $this->broadcaster = $broadcaster;
    }

    public function handle(RendezVousUrgenceUpdated $event): void
    {
        $this->broadcaster->broadcast($event->toArray());
    }
}
