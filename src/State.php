<?php

namespace Knevelina\States;

class State {
    /**
     * The ID, or label, of the state.
     *
     * @var string
     */
    protected $id;

    /**
     * Whether the state is accepting or not.
     *
     * @var bool
     */
    protected $accepting;

    /**
     * The transitions of the state. Associative array where the key is the
     * symbol which causes a transition to the next state.
     *
     * @var State[]
     */
    protected $transitions;

    /**
     * Construct a new state.
     *
     * @param string $id
     * @param boolean $accepting
     */
    public function __construct(string $id, bool $accepting)
    {
        $this->id = $id;
        $this->accepting = $accepting;
        $this->transitions = [];
    }

    /**
     * Get the ID, or label, of the state.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get whether or not the state is accepting.
     *
     * @return boolean
     */
    public function isAccepting(): bool
    {
        return $this->accepting;
    }

    /**
     * Get the transitions of the state.
     *
     * @return array
     */
    public function getTransitions(): array
    {
        return $this->transitions;
    }

    /**
     * Add a transition to the state.
     *
     * @param string $symbol
     * @param State $to
     * @return void
     */
    public function addTransition(string $symbol, State $to)
    {
        $this->transitions[$symbol] = $to;
    }

    /**
     * Get if a next state exists for a symbol.
     *
     * @param string $symbol
     * @return boolean
     */
    public function hasNext(string $symbol): bool
    {
        return array_key_exists($symbol, $this->transitions);
    }

    /**
     * Get the next state for a symbol. Throws an exception when it does not
     * exist. Use hasNext to prevent this.
     *
     * @param string $symbol
     * @return State
     * @throws \InvalidArgumentException
     */
    public function getNext(string $symbol): State
    {
        if (!$this->hasNext($symbol)) {
            throw new \InvalidArgumentException(sprintf('Transition for symbol %s is not defined in state %s', $symbol, $this->id));
        }

        return $this->transitions[$symbol];
    }
}