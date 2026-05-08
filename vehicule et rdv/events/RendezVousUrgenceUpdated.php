<?php

declare(strict_types=1);

class RendezVousUrgenceUpdated
{
    private int $rdvId;
    private int $score;
    private array $details;
    private array $rdv;

    public function __construct(int $rdvId, int $score, array $details, array $rdv)
    {
        $this->rdvId = $rdvId;
        $this->score = $score;
        $this->details = $details;
        $this->rdv = $rdv;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->rdvId,
            'urgence_score' => $this->score,
            'urgence_details' => $this->details,
            'rdv' => $this->rdv,
        ];
    }
}
