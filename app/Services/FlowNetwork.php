<?php

namespace App\Services;

/**
 * A tiny max-flow network, just enough to answer one question for the Test Bank
 * quota picker: "is there a selection of exactly N questions that meets every
 * category and difficulty quota at once?"
 *
 * That is a transportation problem with equality margins on both sides, so it
 * is solved as a circulation with lower bounds rather than greedily. Dinic's
 * algorithm keeps it exact and fast; the graphs here are a handful of nodes.
 *
 * Each edge carries an optional lower bound. {@see hasFeasibleCirculation()}
 * reports whether every bound can hold at the same time, and {@see flowOn()}
 * reads back how much ran along an edge, lower bound included.
 */
final class FlowNetwork
{
    /** @var array<int, array<int, array{to:int, rev:int, cap:int, original:int, lower:int}>> */
    private array $adjacency = [];

    /** @var array<int, int> node id => net outflow still owed once lower bounds are stripped */
    private array $balance = [];

    public function node(): int
    {
        $id = count($this->adjacency);
        $this->adjacency[$id] = [];
        $this->balance[$id] = 0;

        return $id;
    }

    /**
     * Add a forward edge from $from to $to carrying between $lower and $upper units.
     */
    public function edge(int $from, int $to, int $lower, int $upper): void
    {
        $this->addArc($from, $to, $upper - $lower, $lower);

        // Stripping the lower bound leaves the endpoints imbalanced; the excess
        // has to be routed back through a super source/sink for feasibility.
        if ($lower > 0) {
            $this->balance[$from] -= $lower;
            $this->balance[$to] += $lower;
        }
    }

    private function addArc(int $from, int $to, int $capacity, int $lower = 0): void
    {
        $this->adjacency[$from][] = [
            'to' => $to,
            'rev' => count($this->adjacency[$to]),
            'cap' => $capacity,
            'original' => $capacity,
            'lower' => $lower,
        ];

        $this->adjacency[$to][] = [
            'to' => $from,
            'rev' => count($this->adjacency[$from]) - 1,
            'cap' => 0,
            'original' => 0,
            'lower' => 0,
        ];
    }

    /**
     * Units that ran along the $from → $to edge, lower bound included.
     */
    public function flowOn(int $from, int $to): int
    {
        foreach ($this->adjacency[$from] as $arc) {
            if ($arc['to'] === $to) {
                return $arc['lower'] + ($arc['original'] - $arc['cap']);
            }
        }

        return 0;
    }

    /**
     * Whether the lower bounds can all hold at the same time.
     */
    public function hasFeasibleCirculation(): bool
    {
        $superSource = $this->node();
        $superSink = $this->node();

        $required = 0;

        foreach ($this->balance as $node => $delta) {
            if ($delta > 0) {
                $this->addArc($superSource, $node, $delta);
                $required += $delta;
            } elseif ($delta < 0) {
                $this->addArc($node, $superSink, -$delta);
            }
        }

        if ($required === 0) {
            return true;
        }

        $this->maxFlow($superSource, $superSink);

        // If anything is still unspent at the super source, some demanded unit
        // could not be routed and the bounds contradict each other.
        foreach ($this->adjacency[$superSource] as $arc) {
            if ($arc['cap'] > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Dinic's algorithm.
     */
    private function maxFlow(int $source, int $sink): int
    {
        $total = 0;
        $nodeCount = count($this->adjacency);

        while (true) {
            $level = array_fill(0, $nodeCount, -1);
            $level[$source] = 0;
            $queue = [$source];

            while ($queue !== []) {
                $node = array_shift($queue);

                foreach ($this->adjacency[$node] as $arc) {
                    if ($arc['cap'] > 0 && $level[$arc['to']] === -1) {
                        $level[$arc['to']] = $level[$node] + 1;
                        $queue[] = $arc['to'];
                    }
                }
            }

            if ($level[$sink] === -1) {
                break;
            }

            $iterators = array_fill(0, $nodeCount, 0);

            while (($pushed = $this->send($source, $sink, PHP_INT_MAX, $level, $iterators)) > 0) {
                $total += $pushed;
            }
        }

        return $total;
    }

    /**
     * @param  array<int, int>  $level
     * @param  array<int, int>  $iterators
     */
    private function send(int $node, int $sink, int $limit, array $level, array &$iterators): int
    {
        if ($node === $sink) {
            return $limit;
        }

        while ($iterators[$node] < count($this->adjacency[$node])) {
            $index = $iterators[$node];
            $arc = &$this->adjacency[$node][$index];

            if ($arc['cap'] > 0 && $level[$arc['to']] === $level[$node] + 1) {
                $pushed = $this->send($arc['to'], $sink, min($limit, $arc['cap']), $level, $iterators);

                if ($pushed > 0) {
                    $arc['cap'] -= $pushed;
                    $this->adjacency[$arc['to']][$arc['rev']]['cap'] += $pushed;

                    return $pushed;
                }
            }

            $iterators[$node]++;
        }

        return 0;
    }
}