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

        while (count($picked) < $total && $remaining->isNotEmpty()) {
            $picked[] = $remaining->shift();
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

        while (count($picked) < $total && $remaining->isNotEmpty()) {
            $picked[] = $remaining->shift();
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
        $remaining = $pool->shuffle()->values();
        $picked = [];
        $difficultyTaken = [];

        // Pass 1 — each category, taking under-quota difficulties first.
        foreach ($categoryQuotas as $categoryId => $wanted) {
            $bucket = $remaining
                ->filter(fn (Question $q) => (int) $q->category_id === (int) $categoryId)
                ->values();

            $this->takeFromBucket($bucket, $remaining, $picked, $wanted, $difficultyQuotas, $difficultyTaken);
        }

        // Pass 2 — repair difficulty shortfalls within same category.
        foreach ($difficultyQuotas as $difficulty => $quota) {
            $shortfall = $this->repairDifficulty(
                $remaining, $picked, $difficulty, $difficultyQuotas, $difficultyTaken
            );

            if ($shortfall <= 0) {
                continue;
            }

            $room = $total - count($picked);

            if ($shortfall > $room) {
                throw new QuestionQuotaException(sprintf(
                    'The category quotas already claim %d of the %d slots, leaving no room for the %d "%s" '
                    .'question(s) still needed. Lower a category quota or raise the set size.',
                    count($picked),
                    $total,
                    $shortfall,
                    $difficulty
                ));
            }

            $available = $remaining->filter(fn (Question $q) => $q->difficulty === $difficulty)->count();

            if ($available < $shortfall) {
                throw new QuestionQuotaException(sprintf(
                    'Difficulty "%s" still needs %d question(s), but only %d %s left once the category quotas '
                    .'are filled. The two sets of quotas cannot be met together.',
                    $difficulty,
                    $shortfall,
                    $available,
                    $available === 1 ? 'is' : 'are'
                ));
            }

            $bucket = $remaining->filter(fn (Question $q) => $q->difficulty === $difficulty)->values();
            $this->takeFromBucket($bucket, $remaining, $picked, $shortfall, $difficultyQuotas, $difficultyTaken);
        }

        // Pass 3 — pad the rest.
        while (count($picked) < $total && $remaining->isNotEmpty()) {
            $question = $remaining->shift();
            $picked[] = $question;
            $difficultyTaken[$question->difficulty] = ($difficultyTaken[$question->difficulty] ?? 0) + 1;
        }

        if (count($picked) < $total) {
            throw new QuestionQuotaException(sprintf(
                'Only %d of the %d question(s) requested could be drawn with these quotas.',
                count($picked),
                $total
            ));
        }

        return collect($picked)->shuffle()->values()->all();
    }

    /**
     * Remove $wanted questions from $bucket, preferring under-quota difficulties.
     *
     * @param  array<int, Question>  $picked
     * @param  array<string, int>  $difficultyQuotas
     * @param  array<string, int>  $difficultyTaken
     */
    private function takeFromBucket(
        Collection $bucket,
        Collection $remaining,
        array &$picked,
        int $wanted,
        array $difficultyQuotas,
        array &$difficultyTaken
    ): void {
        while ($wanted > 0 && $bucket->isNotEmpty()) {
            // Prefer a question whose difficulty is still under quota.
            $index = $bucket->search(
                fn (Question $q) => ($difficultyQuotas[$q->difficulty] ?? 0) > ($difficultyTaken[$q->difficulty] ?? 0)
            );

            if ($index === false) {
                $index = 0;
            }

            $question = $bucket->splice($index, 1)->first();

            $position = $remaining->search(fn (Question $q) => $q->id === $question->id);
            if ($position !== false) {
                $remaining->splice($position, 1);
            }

            $picked[] = $question;
            $difficultyTaken[$question->difficulty] = ($difficultyTaken[$question->difficulty] ?? 0) + 1;
            $wanted--;
        }
    }

    /**
     * Swap a held question for one of $difficulty in the SAME category,
     * but only if the held difficulty has already met its own quota.
     *
     * Returns how many of $difficulty are still owed afterwards.
     *
     * @param  array<int, Question>  $picked
     * @param  array<string, int>  $difficultyQuotas
     * @param  array<string, int>  $difficultyTaken
     */
    private function repairDifficulty(
        Collection $remaining,
        array &$picked,
        string $difficulty,
        array $difficultyQuotas,
        array &$difficultyTaken
    ): int {
        $owed = ($difficultyQuotas[$difficulty] ?? 0) - ($difficultyTaken[$difficulty] ?? 0);

        if ($owed <= 0) {
            return 0;
        }

        foreach ($picked as $index => $held) {
            if ($owed <= 0) {
                break;
            }

            if ($held->difficulty === $difficulty) {
                continue;
            }

            // Don't take from a difficulty that is itself still owed.
            if (($difficultyQuotas[$held->difficulty] ?? 0) > ($difficultyTaken[$held->difficulty] ?? 0)) {
                continue;
            }

            $swap = $remaining->search(
                fn (Question $q) => $q->difficulty === $difficulty
                    && (int) $q->category_id === (int) $held->category_id
            );

            if ($swap === false) {
                continue;
            }

            $replacement = $remaining->splice($swap, 1)->first();

            $picked[$index] = $replacement;
            $remaining->push($held);

            $difficultyTaken[$held->difficulty]--;
            $difficultyTaken[$difficulty] = ($difficultyTaken[$difficulty] ?? 0) + 1;
            $owed--;
        }

        return max(0, $owed);
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