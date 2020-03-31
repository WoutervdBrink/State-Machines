<?php

namespace Knevelina\States;

class Machine
{
    /**
     * The machine's states.
     *
     * @var State[]
     */
    protected $states;

    /**
     * The initial state.
     *
     * @var State
     */
    protected $initial;

    /**
     * The machine's alphabet.
     *
     * @var array
     */
    protected $alphabet;

    /**
     * Construct a new state machine.
     *
     * @param array ...$alphabet
     */
    public function __construct(...$alphabet)
    {
        $this->states = [];
        $this->initial = null;

        $this->alphabet = $alphabet;
    }

    /**
     * Get the machine's states.
     *
     * @return State[]
     */
    public function getStates(): array
    {
        return $this->states;
    }

    /**
     * Get the machine's initial state.
     *
     * @return State
     */
    public function getInitialState(): State
    {
        return $this->initial;
    }

    /**
     * Get the machine's alphabet.
     *
     * @return array
     */
    public function getAlphabet(): array
    {
        return $this->alphabet;
    }

    /**
     * Create and add a new state to the machine. If it is the first state, that
     * state will be the machine's initial state.
     *
     * @param string $id
     * @param boolean $accepting
     * @return State
     */
    public function state(string $id, bool $accepting = false): State
    {
        $state = new State($id, $accepting);

        if ($this->initial === null) {
            $this->initial = $state;
        }

        $this->states[] = $state;

        return $state;
    }

    /**
     * Set the machine's initial state.
     *
     * @param State $state
     * @return void
     */
    public function setInitialState(State $state)
    {
        if (!in_array($state, $this->states)) {
            throw new \InvalidArgumentException(sprintf('State %s is not part of this machine.', $state->id));
        }

        $this->initial = $state;
    }

    /**
     * Add a transition from one state to another state. The states may be
     * identical. The states must be part of the machine.
     *
     * @param State $from
     * @param string $symbol
     * @param State $to
     * @return self
     */
    public function transition(State $from, string $symbol, State $to): self
    {
        if (!in_array($from, $this->states)) {
            throw new \InvalidArgumentException(sprintf('State %s is not part of this machine.', $from->id));
        }

        if (!in_array($to, $this->states)) {
            throw new \InvalidArgumentException(sprintf('State %s is not part of this machine.', $to->id));
        }

        $from->addTransition($symbol, $to);

        return $this;
    }

    /**
     * Check if the machine accepts a string.
     *
     * @param string $string
     * @return boolean
     */
    public function accepts(string $string): bool
    {
        if ($this->initial === null) {
            throw new \RuntimeException('No initial state has been set. Perhaps no states have been defined.');
        }

        $state = $this->initial;

        $symbols = str_split($string);

        foreach ($symbols as $symbol) {
            $state = $state->getNext($symbol);
        }

        return $state->isAccepting();
    }

    public function isComplete(): bool
    {
        foreach ($this->states as $state) {
            foreach ($this->alphabet as $symbol) {
                if (!$state->hasNext($symbol)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Render the D array, used in the algorithm which retrieves equivalent
     * states of the machine.
     *
     * @param array $D
     * @return void
     */
    protected function renderD(array $D)
    {
        $count = count($this->states);

        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>D</th>';
        for ($i = 0; $i < $count; $i++) {
            echo '<th>'.$this->states[$i]->getId().'</th>';
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        for ($j = 0; $j < $count; $j++) {
            echo '<tr>';
            echo '<th>'.$this->states[$j]->getId().'</th>';

            for ($i = 0; $i < $count; $i++) {
                echo '<td>'.$D[$i][$j].'</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render the S array, used in the algorithm which retrieves equivalent
     * states of the machine.
     *
     * @param array $S
     * @return void
     */
    protected function renderS(array $S)
    {
        $count = count($this->states);

        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>S</th>';
        for ($i = 0; $i < $count; $i++) {
            echo '<th>'.$this->states[$i]->getId().'</th>';
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        for ($j = 0; $j < $count; $j++) {
            echo '<tr>';
            echo '<th>'.$this->states[$j]->getId().'</th>';

            for ($i = 0; $i < $count; $i++) {
                echo '<td>{';

                echo implode(', ', array_map(function ($mn) {
                    [$m, $n] = $mn;
                    return sprintf('(%d, %d)', $m, $n);
                }, $S[$i][$j]));
                echo '}</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Get the machine's equivalent states. When <code>$debug</code> is true,
     * the method will print information about what the algorithm is doing.
     *
     * @param boolean $debug
     * @return array
     */
    public function getEquivalentStates(bool $debug = false): array
    {
        if (!$this->isComplete()) {
            throw new \RuntimeException('The machine is not a complete DFA. Retrieving equivalent states is only possible for complete DFAs.');
        }

        $D = [];
        $S = [];
        $count = count($this->states);

        // Initialization
        for ($i = 0; $i < $count; $i++) {
            $q_i = $this->states[$i];
            $D[$i] = [];
            $S[$i] = [];

            for ($j = 0; $j < $count; $j++) {
                $q_j = $this->states[$j];
                $D[$i][$j] = 0;
                $S[$i][$j] = [];
            }
        }

        if ($debug) {
            echo '<h3>Step 1: initialization</h3>';
            $this->renderD($D);
        }

        // Step 2
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                $q_i = $this->states[$i];
                $q_j = $this->states[$j];

                if ($q_i->isAccepting() !== $q_j->isAccepting()) {
                    $D[$i][$j] = 1;
                }
            }
        }

        if ($debug) {
            echo '<h3>Step 2: accepting states are distinguishable from non-accepting states</h3>';
            $this->renderD($D);
        }

        // Step 3
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                $q_i = $this->states[$i];
                $q_j = $this->states[$j];
                // Step 3.1: does there exist an a \in alphabet with
                // t(q_i, a) = q_m,
                // t(q_j, a) = q_n,
                // and D[m, n]?
                $exists_3_1 = false;
                foreach ($this->alphabet as $a) {
                    $q_m = $q_i->getNext($a);
                    $q_n = $q_j->getNext($a);
                    
                    $m = array_keys($this->states, $q_m)[0];
                    $n = array_keys($this->states, $q_n)[0];

                    if ($D[$m][$n] || $D[$n][$m]) {
                        // then DIST[i, j]
                        $this->dist($D, $S, $i, $j, $debug);
                        $exists_3_1 = true;
                    }
                }

                if (!$exists_3_1) {
                    // else: foreach a in alphabet:
                    // let t(q_i, a) = q_m,
                    // let t(q_j, a) = q_n,
                    foreach ($this->alphabet as $a) {
                        $q_m = $q_i->getNext($a);
                        $q_n = $q_j->getNext($a);

                        $m = array_keys($this->states, $q_m)[0];
                        $n = array_keys($this->states, $q_n)[0];

                        if (
                            // [i, j] != [m, n]
                            $q_i !== $q_m && $q_j !== $q_n
                        ) {
                            // add [i, j] to S[m, n]
                            $S[$m][$n][] = [$i, $j];
                        }

                        if (
                            // [i, j] != [n, m]
                            $q_i !== $q_n && $q_j !== $q_m
                        ) {
                            // add [i, j] to S[n, m]
                            $S[$n][$m][] = [$i, $j];
                        }
                    }
                }

                if ($debug) {
                    echo sprintf('<h3>After %d, %d:</h3>', $i, $j);
                    $this->renderD($D);
                    $this->renderS($S);
                }
            }
        }

        // END

        if ($debug) {
            echo '<h3>End of algorithm:</h3>';
            $this->renderD($D);
        }

        $equivalent = [];

        // Any two states for which D[q1, q2] is zero are now equivalent.
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                // If we do not do this, the machine will indicate that two
                // states are equivalent twice, as D is the cartesian product
                // of the machine's states.
                if ($i > $j) {
                    continue;
                }

                if ($D[$i][$j] === 0) {
                    $q_i = $this->states[$i];
                    $q_j = $this->states[$j];

                    if ($q_i !== $q_j) {
                        $equivalent[] = [$q_i, $q_j];
                    }
                }
            }
        }

        return $equivalent;
    }

    /**
     * Get the minimized version of this machine. Returns itself if the machine
     * is already minimal.
     *
     * @return Machine
     */
    public function getMinimizedMachine(): Machine
    {
        $equivalentStates = $this->getEquivalentStates();

        if (count($equivalentStates) === 0) {
            // We are already minimal!
            return $this;
        }

        $combinedStates = array_reduce($equivalentStates, function ($combinedStates, $equivalentStates) {
            return [
                ...$equivalentStates,
                ...$combinedStates
            ];
        }, []);

        $machine = new Machine($this->getAlphabet());
        $stateMap = [];

        foreach ($this->getStates() as $state) {
            if (!in_array($state, $combinedStates)) {
                $stateMap[$state->getId()] = $machine->state($state->getId());
            }
        }

        foreach ($equivalentStates as [$q_i, $q_j]) {
            $label = $this->getStateLabel($q_i, $q_j);
            $newState = $machine->state($label);;

            $stateMap[$label] = $newState;
            $stateMap[$q_i->getId()] = $newState;
            $stateMap[$q_j->getId()] = $newState;
        }

        foreach ($this->getStates() as $state) {
            if (!in_array($state, $combinedStates)) {
                foreach ($state->getTransitions() as $symbol => $to) {
                    $machine->transition(
                        $stateMap[$state->getId()],
                        $symbol,
                        $stateMap[$to->getId()]
                    );
                }
            }
        }

        foreach ($equivalentStates as [$q_i, $q_j]) {
            $label = $this->getStateLabel($q_i, $q_j);

            // The states are equivalent, so only need to consider the first
            // (or second, does not matter) state.
            foreach ($q_i->getTransitions() as $symbol => $to) {
                // As we added state mapping for every combined and separate
                // state, the state classes will get resolved to the correct
                // generated combined state.
                $machine->transition(
                    $stateMap[$label],
                    $symbol,
                    $stateMap[$to->getId()]
                );
            }
        }

        return $machine;
    }

    /**
     * Get the new label (ID) for combining two states.
     *
     * @param State $i
     * @param State $j
     * @return string
     */
    protected function getStateLabel(State $i, State $j): string
    {
        return sprintf('%s_%s', $i->getId(), $j->getId());
    }

    /**
     * The dist function, which helps the equivalent state algorithm.
     *.
     * @param array $D
     * @param array $S
     * @param integer $i
     * @param integer $j
     * @param boolean $debug
     * @return void
     */
    protected function dist(array &$D, array $S, int $i, int $j, bool $debug = false)
    {
        if ($debug) {
            echo sprintf('dist(D, S, %d, %d)<br>', $i, $j);
        }
        $D[$i][$j] = 1;

        foreach ($S[$i][$j] as [$m, $n]) {
            $this->dist($D, $S, $m, $n, $debug);
        }
    }
}