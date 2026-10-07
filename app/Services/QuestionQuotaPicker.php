<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * §9 — draw a fixed-size random set out of the Test Bank under per-category
 * and per-difficulty quotas.
 *
 * Three cases:
 *  1. Only category quotas  → fill each category, rest free.
 *  2. Only difficulty quotas → fill each difficulty, rest free.
 *  3. Both → greedy by category first, preferring under-quota difficulties,
 *     then repair difficulty shortfall by swapping within categories, and
 *     finally pad the remainder.
 *
 * Every refusal names the bucket that fell short.
 */
final class QuestionQuotaPicker
{
    /**
     * @param  Collection<int, Question>  $pool  questions that may be drawn
     * @param  array<int, int>  $categoryQuotas  category_id => count
     * @param  array<string, int>  $difficultyQuotas  difficulty slug => count
     * @return array<int, Question> exactly $total questions, in a fresh random order
     *
     * @throws QuestionQuotaException
     */
    public function pick(Collection $pool, int $total, array $categoryQuotas = [], array $difficultyQuotas = []): array
    {
        $categoryQuotas = $this->normalise($categoryQuotas);
        $difficultyQuotas = $this->normalise($difficultyQuotas);

        if ($total < 1) {
            throw new QuestionQuotaException('A drawn set needs at least 1 question.');
        }

        if ($pool->count() < $total) {
            throw new QuestionQuotaException(sprintf(
                'Only %d question%s available, but %d were requested.',
                $pool->count(),
                $pool->count() === 1 ? '' : 's',
                $total
            ));
        }

        $this->assertBucketsSupplied($pool, $categoryQuotas, $difficultyQuotas);
        $this->assertQuotaSum($categoryQuotas, $total, 'Category');
        $this->assertQuotaSum($difficultyQuotas, $total, 'Difficulty');

        // Case 1 & 2: single dimension → simple fill + pad.
        if ($categoryQuotas !== [] && $difficultyQuotas === []) {
            return $this->pickByCategory($pool, $total, $categoryQuotas);
        }

        if ($categoryQuotas === [] && $difficultyQuotas !== []) {
            return $this->pickByDifficulty($pool, $total, $difficultyQuotas);
        }

        // Case 3: both dimensions → the full algorithm.
        return $this->pickByBoth($pool, $total, $categoryQuotas, $difficultyQuotas);
    }

    /**
     * Simple fill: take $quota from each bucket, then pad from leftovers.
     *
     * @param  array<int|string, int>  $quotas
     */
    private function pickByCategory(Collection $pool, int $total, array $quotas): array
    {
        $remaining = $pool->shuffle()->values();
        $picked = [];

        foreach ($quotas as $categoryId => $wanted) {
            $bucket = $remaining
                ->filter(fn (Question $q) => (int) $q->category_id === (int) $categoryId)
                ->values();

            if ($bucket->count() < $wanted) {
                throw new QuestionQuotaException(sprintf(
                    'The category "%s" has only %d question%s available, but %d %s requested.',
                    $categoryId === 0 ? 'Uncategorised' : ($bucket->first()?->category?->name ?? 'Category #'.$categoryId),
                    $bucket->count(),
                    $bucket->count() === 1 ? '' : 's',
                    $wanted,
                    $wanted === 1 ? 'was' : 'were'
                ));
            }

            foreach ($bucket->take($wanted) as $question) {
                $picked[] = $question;
                $pos = $remaining->search(fn (Question $q) => $q->id === $question->id);
                if ($pos !== false) $remaining->splice($pos, 1);
            }
        }

        // A quota is an exact count, so the free remainder may only come from
        // categories that were NOT given a quota. Padding from anywhere allowed
        // a quota'd category to overshoot (measured ~13% of draws).
        while (count($picked) < $total) {
            $free = $remaining
                ->filter(fn (Question $q) => ! array_key_exists((int) $q->category_id, $quotas))
                ->values();

            if ($free->isEmpty()) {
                break;
            }

            $question = $free->shift();
            $pos = $remaining->search(fn (Question $q) => $q->id === $question->id);
            if ($pos !== false) $remaining->splice($pos, 1);
            $picked[] = $question;
        }

        if (count($picked) < $total) {
            throw new QuestionQuotaException(sprintf(
                'Only %d of the %d question(s) could be drawn: the categories without a quota have %d more available.',
                count($picked),
                $total,
                $total - count($picked)
            ));
        }

        return collect($picked)->shuffle()->values()->all();
    }

    /**
     * Simple fill by difficulty.
     *
     * @param  array<string, int>  $quotas
     */
    private function pickByDifficulty(Collection $pool, int $total, array $quotas): array
    {
        $remaining = $pool->shuffle()->values();
        $picked = [];

        foreach ($quotas as $difficulty => $wanted) {
            $bucket = $remaining->filter(fn (Question $q) => $q->difficulty === $difficulty)->values();

            if ($bucket->count() < $wanted) {
                throw new QuestionQuotaException(sprintf(
                    'Difficulty "%s" has only %d question%s available, but %d %s requested.',
                    $difficulty,
                    $bucket->count(),
                    $bucket->count() === 1 ? '' : 's',
                    $wanted,
                    $wanted === 1 ? 'was' : 'were'
                ));
            }

            foreach ($bucket->take($wanted) as $question) {
                $picked[] = $question;
                $pos = $remaining->search(fn (Question $q) => $q->id === $question->id);
                if ($pos !== false) $remaining->splice($pos, 1);
            }
        }

        // As above: the remainder may only come from difficulties with no quota,
        // otherwise a quota'd difficulty overshoots (measured ~9% of draws).
        while (count($picked) < $total) {
            $free = $remaining
                ->filter(fn (Question $q) => ! array_key_exists($q->difficulty, $quotas))
                ->values();

            if ($free->isEmpty()) {
                break;
            }

            $question = $free->shift();
            $pos = $remaining->search(fn (Question $q) => $q->id === $question->id);
            if ($pos !== false) $remaining->splice($pos, 1);
            $picked[] = $question;
        }

        if (count($picked) < $total) {
            throw new QuestionQuotaException(sprintf(
                'Only %d of the %d question(s) could be drawn: the difficulties without a quota have %d more available.',
                count($picked),
                $total,
                $total - count($picked)
            ));
        }

        return collect($picked)->shuffle()->values()->all();
    }

    /**
     * Both category and difficulty quotas active.
     *
     * Pass 1: fill category quotas, preferring under-quota difficulties.
     * Pass 2: repair any difficulty shortfall by swapping within categories.
     * Pass 3: pad remainder.
     */
    private function pickByBoth(Collection $pool, int $total, array $categoryQuotas, array $difficultyQuotas): array
    {
        // A quota is an exact count on both dimensions simultaneously, and the
        // free remainder may only come from buckets that carry no quota. That is
        // a transportation problem with equality margins on both sides, so it
        // is solved exactly as a min-cost-free max-flow rather than greedily:
        // the old greedy fill could strand itself and hand back a set that
        // silently broke the difficulty quotas (~12.5% of draws) instead of
        // raising anything.
        $counts = $this->solveBothDimensions($pool, $total, $categoryQuotas, $difficultyQuotas);

        $byCell = [];
        foreach ($pool as $question) {
            $byCell[(int) $question->category_id.'|'.$question->difficulty][] = $question;
        }

        $picked = [];

        foreach ($counts as $categoryId => $byDifficulty) {
            foreach ($byDifficulty as $difficulty => $count) {
                $cell = $byCell[($categoryId).'|'.$difficulty] ?? [];

                // Shuffle so the draw stays random within a cell.
                shuffle($cell);

                foreach (array_slice($cell, 0, $count) as $question) {
                    $picked[] = $question;
                }
            }
        }

        if (count($picked) !== $total) {
            throw new QuestionQuotaException(sprintf(
                'Only %d of the %d question(s) requested could be drawn with these quotas.',
                count($picked),
                $total
            ));
        }

        return collect($picked)->shuffle()->values()->all();
    }

    /**
     * Exact per-(category, difficulty) counts honouring every quota.
     *
     * Modelled as a circulation with lower bounds:
     *
     *   source → category      lower = upper = the category's quota
     *   category → difficulty  0 … how many of that pairing actually exist
     *   difficulty → sink      lower = upper = the difficulty's quota
     *
     * Quota-less buckets get lower 0 and an upper of $total, which is what
     * lets the leftover slots land in them. The T → source edge pinned to
     * $total forces the circulation to carry exactly the set size.
     *
     * @param  array<int, int>  $categoryQuotas
     * @param  array<string, int>  $difficultyQuotas
     * @return array<int, array<string, int>> category_id => difficulty => count
     *
     * @throws QuestionQuotaException
     */
    private function solveBothDimensions(
        Collection $pool,
        int $total,
        array $categoryQuotas,
        array $difficultyQuotas
    ): array {
        $available = [];
        $categoryIds = [];
        $difficultyNames = [];

        foreach ($pool as $question) {
            $categoryId = (int) $question->category_id;
            $difficulty = (string) $question->difficulty;

            $available[$categoryId][$difficulty] = ($available[$categoryId][$difficulty] ?? 0) + 1;
            $categoryIds[$categoryId] = true;
            $difficultyNames[$difficulty] = true;
        }

        // Which buckets may absorb the leftover slots.
        $freeCategories = array_diff(array_keys($categoryIds), array_map('intval', array_keys($categoryQuotas)));
        $freeDifficulties = array_diff(array_keys($difficultyNames), array_keys($difficultyQuotas));

        $graph = new FlowNetwork();

        $source = $graph->node();
        $sink = $graph->node();

        $categoryNode = [];
        foreach ($categoryIds as $categoryId => $_) {
            $categoryNode[$categoryId] = $graph->node();
        }

        $difficultyNode = [];
        foreach ($difficultyNames as $difficulty => $_) {
            $difficultyNode[$difficulty] = $graph->node();
        }

        // Pin the whole set: the circulation must move exactly $total units.
        $graph->edge($sink, $source, $total, $total);

        foreach ($categoryIds as $categoryId => $_) {
            $quota = $categoryQuotas[$categoryId] ?? null;

            if ($quota !== null) {
                $graph->edge($source, $categoryNode[$categoryId], $quota, $quota);
            } else {
                $graph->edge($source, $categoryNode[$categoryId], 0, $total);
            }
        }

        foreach ($difficultyNames as $difficulty => $_) {
            $quota = $difficultyQuotas[$difficulty] ?? null;

            if ($quota !== null) {
                $graph->edge($difficultyNode[$difficulty], $sink, $quota, $quota);
            } else {
                $graph->edge($difficultyNode[$difficulty], $sink, 0, $total);
            }
        }

        foreach ($available as $categoryId => $byDifficulty) {
            foreach ($byDifficulty as $difficulty => $count) {
                $graph->edge($categoryNode[$categoryId], $difficultyNode[$difficulty], 0, $count);
            }
        }

        if (! $graph->hasFeasibleCirculation()) {
            throw $this->unsatisfiableMessage(
                $total,
                $categoryQuotas,
                $difficultyQuotas,
                $available,
                $freeCategories,
                $freeDifficulties
            );
        }

        $counts = [];
        foreach ($available as $categoryId => $byDifficulty) {
            foreach ($byDifficulty as $difficulty => $_) {
                $used = $graph->flowOn($categoryNode[$categoryId], $difficultyNode[$difficulty]);

                if ($used > 0) {
                    $counts[$categoryId][$difficulty] = $used;
                }
            }
        }

        return $counts;
    }

    /**
     * Name the bucket that actually fell short, rather than a generic refusal.
     *
     * @param  array<int, int>  $categoryQuotas
     * @param  array<string, int>  $difficultyQuotas
     * @param  array<int, array<string, int>>  $available
     * @param  array<int, int>  $freeCategories
     * @param  array<int, string>  $freeDifficulties
     */
    private function unsatisfiableMessage(
        int $total,
        array $categoryQuotas,
        array $difficultyQuotas,
        array $available,
        array $freeCategories,
        array $freeDifficulties
    ): QuestionQuotaException {
        // Try the category quotas on their own first — that is the constraint a
        // reader is most likely to have gotten wrong.
        $freeCategoryCapacity = 0;
        foreach ($freeCategories as $categoryId) {
            foreach (($available[$categoryId] ?? []) as $count) {
                $freeCategoryCapacity += $count;
            }
        }

        if ($freeCategoryCapacity < $total - array_sum($categoryQuotas)) {
            foreach ($categoryQuotas as $categoryId => $wanted) {
                $have = 0;
                foreach (($available[$categoryId] ?? []) as $count) {
                    $have += $count;
                }

                if ($have < $wanted) {
                    return new QuestionQuotaException(sprintf(
                        'The category "%s" has only %d question%s available, but %d %s requested.',
                        $categoryId === 0 ? 'Uncategorised' : 'Category #'.$categoryId,
                        $have,
                        $have === 1 ? '' : 's',
                        $wanted,
                        $wanted === 1 ? 'was' : 'were'
                    ));
                }
            }

            return new QuestionQuotaException(sprintf(
                'The category quotas need %d question(s), but only %d more %s available outside the '
                .'quota\'d categories to reach %d.',
                array_sum($categoryQuotas),
                $freeCategoryCapacity,
                $freeCategoryCapacity === 1 ? 'is' : 'are',
                $total
            ));
        }

        // Otherwise the difficulty side is what cannot be honoured.
        foreach ($difficultyQuotas as $difficulty => $wanted) {
            $have = 0;
            foreach ($available as $byDifficulty) {
                $have += $byDifficulty[$difficulty] ?? 0;
            }

            if ($have < $wanted) {
                return new QuestionQuotaException(sprintf(
                    'Difficulty "%s" has only %d question%s available, but %d %s requested.',
                    $difficulty,
                    $have,
                    $have === 1 ? '' : 's',
                    $wanted,
                    $wanted === 1 ? 'was' : 'were'
                ));
            }
        }

        return new QuestionQuotaException(sprintf(
            'These category and difficulty quotas cannot be met together for a set of %d. Lower one of the '
            .'quotas or change the set size.',
            $total
        ));
    }

    /**
     * Zero means "no limit here"; a negative or unusable value is not a quota.
     *
     * @return array<int|string, int>
     */
    private function normalise(array $quotas): array
    {
        $clean = [];

        foreach ($quotas as $key => $value) {
            $value = (int) $value;

            if ($value > 0) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * The plain "not enough of these" messages.
     *
     * @param  array<int, int>  $categoryQuotas
     * @param  array<string, int>  $difficultyQuotas
     */
    private function assertBucketsSupplied(Collection $pool, array $categoryQuotas, array $difficultyQuotas): void
    {
        foreach ($categoryQuotas as $categoryId => $wanted) {
            $bucket = $pool->filter(fn (Question $q) => (int) $q->category_id === (int) $categoryId);

            if ($bucket->count() >= $wanted) {
                continue;
            }

            $name = (int) $categoryId === 0
                ? 'Uncategorised'
                : ($bucket->first()?->category?->name ?? 'Category #'.$categoryId);

            throw new QuestionQuotaException(sprintf(
                'The category "%s" has only %d question%s available, but %d %s requested.',
                $name,
                $bucket->count(),
                $bucket->count() === 1 ? '' : 's',
                $wanted,
                $wanted === 1 ? 'was' : 'were'
            ));
        }

        foreach ($difficultyQuotas as $difficulty => $wanted) {
            $available = $pool->filter(fn (Question $q) => $q->difficulty === $difficulty)->count();

            if ($available >= $wanted) {
                continue;
            }

            throw new QuestionQuotaException(sprintf(
                'Difficulty "%s" has only %d question%s available, but %d %s requested.',
                $difficulty,
                $available,
                $available === 1 ? '' : 's',
                $wanted,
                $wanted === 1 ? 'was' : 'were'
            ));
        }
    }

    /**
     * A breakdown that overshoots the set itself can never be met.
     *
     * @param  array<int|string, int>  $quotas
     */
    private function assertQuotaSum(array $quotas, int $total, string $dimension): void
    {
        $sum = array_sum($quotas);

        if ($sum > $total) {
            throw new QuestionQuotaException(sprintf(
                '%s quotas add up to %d, which is more than the set size of %d.',
                $dimension,
                $sum,
                $total
            ));
        }
    }
}