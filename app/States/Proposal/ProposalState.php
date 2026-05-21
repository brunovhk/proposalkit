<?php

declare(strict_types=1);

namespace App\States\Proposal;

use App\Models\Proposal;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Defines the allowed lifecycle transitions for commercial proposals.
 *
 * @extends State<Proposal>
 */
abstract class ProposalState extends State
{
    public string $value {
        get => $this->getValue();
    }

    /**
     * Configure all valid proposal status transitions.
     */
    #[\Override]
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Sent::class)
            ->allowTransition(Sent::class, Viewed::class)
            ->allowTransition(Sent::class, Accepted::class)
            ->allowTransition(Sent::class, Declined::class)
            ->allowTransition(Viewed::class, Accepted::class)
            ->allowTransition(Viewed::class, Declined::class);
    }
}
