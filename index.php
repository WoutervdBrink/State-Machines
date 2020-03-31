<?php

use Knevelina\States\Machine;
use Knevelina\States\MachineSerializer;

require_once 'vendor/autoload.php';

$machine = new Machine('a', 'b');

$q0 = $machine->state('q0');
$q1 = $machine->state('q1');
$q2 = $machine->state('q2');
$q3 = $machine->state('q3');
$q4 = $machine->state('q4');
$q5 = $machine->state('q5', true);

$machine
    ->transition($q0, 'a', $q1)
    ->transition($q0, 'b', $q3)

    ->transition($q1, 'a', $q1)
    ->transition($q1, 'b', $q2)

    ->transition($q2, 'a', $q2)
    ->transition($q2, 'b', $q5)

    ->transition($q3, 'a', $q3)
    ->transition($q3, 'b', $q4)

    ->transition($q4, 'a', $q4)
    ->transition($q4, 'b', $q5)

    ->transition($q5, 'a', $q5)
    ->transition($q5, 'b', $q5);

$equivalentStates = $machine->getEquivalentStates();
$complete = $machine->isComplete();

echo '<p>The machine is'.($complete ? '' : ' not').' complete.</p>';
echo '<ul>';
foreach ($equivalentStates as [$q_i, $q_j]) {
    echo sprintf('<li>%s and %s are equivalent.</li>', $q_i->getId(), $q_j->getId());
}
echo '</ul>';

$minimized = $machine->getMinimizedMachine();

echo '<h3>Original machine</h3>';
echo '<textarea rows="30" cols="50">'.MachineSerializer::getDot($machine).'</textarea>';

echo '<h3>Minimized machine</h3>';
echo '<textarea rows="30" cols="50">'.MachineSerializer::getDot($minimized).'</textarea>';
