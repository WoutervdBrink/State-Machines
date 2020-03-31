<?php

namespace Knevelina\States;

class MachineSerializer
{
    /**
     * Escape a string for use in a DOT output.
     *
     * @param string $string
     * @return string
     */
    protected static function escapeString(string $string): string
    {
        $string = str_replace('\\', '\\\\', $string);
        return str_replace('"', '\"', $string);
    }

    /**
     * Get the DOT representation of the machine.
     *
     * @param Machine $machine
     * @return string
     */
    public static function getDot(Machine $machine): string
    {
        $i = 0;
        $stateMap = [];
        $dot = 'digraph {'.PHP_EOL;
        foreach ($machine->getStates() as $state) {
            $id = $stateMap[$state->getId()] = 's_'.$i++;
            $dot .= sprintf(
                "    %s [label=\"%s\", shape=\"%s\"];\n",
                $id,
                static::escapeString($state->getId()),
                $state->isAccepting() ? 'doublecircle' : 'circle'
            );
        }
        $dot .= PHP_EOL;
        foreach ($machine->getStates() as $state) {
            foreach ($state->getTransitions() as $symbol => $to) {
                $fromId = $stateMap[$state->getId()];
                $toId = $stateMap[$to->getId()];
                $dot .= sprintf(
                    "    %s -> %s [label=\"%s\"];\n",
                    $fromId,
                    $toId,
                    $symbol
                );
            }
        }
        $dot .= '}';
        return $dot;
    }

    /**
     * Unserialize the serialization of a state machine.
     *
     * @param object $serialized
     * @return Machine
     */
    public static function unserialize(object $serialized): Machine
    {
        $machine = new Machine($serialized->alphabet);
        $stateMap = [];

        foreach ($serialized->states as $serializedState) {
            $stateMap[$serializedState->id] = $machine->state($serializedState->id, $serializedState->accepting);
        }

        foreach ($serialized->states as $serializedState) {
            foreach ($serializedState->transitions as $symbol => $to) {
                $machine->transition($stateMap[$serializedState->id], $symbol, $stateMap[$to]);
            }
        }

        $machine->setInitialState($stateMap[$serialized->initial]);

        return $machine;
    }

    /**
     * Serialize a state machine.
     *
     * @param Machine $machine
     * @return object
     */
    public static function serialize(Machine $machine): object
    {
        $serialized = (object)[
            'alphabet' => $machine->getAlphabet(),
            'states' => [],
            'initial' => null
        ];

        if ($initial = $machine->getInitialState()) {
            $serialized->initial = $initial->getId();
        }

        foreach ($machine->getStates() as $state) {
            $serializedState = (object)[
                'id' => $state->getId(),
                'accepting' => $state->isAccepting(),
                'transitions' => (object)[]
            ];

            foreach ($state->getTransitions() as $symbol => $next) {
                $serializedState->transitions->{$symbol} = $next->getId();
            }

            $serialized->states[] = $serializedState;
        }
        
        return $serialized;
    }
}