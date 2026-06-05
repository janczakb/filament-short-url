<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support;

class WeightBalancer
{
    /**
     * Balance the weights of variants dynamically to sum up to exactly 100%.
     */
    public static function balanceWeights(mixed $component): void
    {
        $items = $component->getState() ?? [];
        $m = count($items);
        if ($m === 0) {
            return;
        }

        $sum = 0;
        foreach ($items as $key => $item) {
            $items[$key]['weight'] = (int) ($item['weight'] ?? 0);
            $sum += $items[$key]['weight'];
        }

        $diff = 100 - $sum;
        if ($diff !== 0) {
            $diffSteps = (int) ($diff / 5);
            $changeSteps = (int) ($diffSteps / $m);
            $remainderSteps = $diffSteps % $m;

            foreach ($items as $key => $item) {
                $items[$key]['weight'] = max(0, min(100, $items[$key]['weight'] + $changeSteps * 5));
            }

            $step = $remainderSteps > 0 ? 5 : -5;
            $count = abs($remainderSteps);
            $keys = array_keys($items);
            for ($i = 0; $i < $count; $i++) {
                $k = $keys[$i % $m];
                $items[$k]['weight'] = max(0, min(100, $items[$k]['weight'] + $step));
            }

            // Ensure sum is exactly 100 by forcing the difference on the first eligible item
            $finalSum = array_sum(array_column($items, 'weight'));
            $finalDiff = 100 - $finalSum;
            if ($finalDiff !== 0) {
                foreach ($items as $key => $item) {
                    $newWeight = $items[$key]['weight'] + $finalDiff;
                    if ($newWeight >= 0 && $newWeight <= 100) {
                        $items[$key]['weight'] = $newWeight;
                        break;
                    }
                }
            }

            $component->state($items);
            $component->getLivewire()->getErrorBag()->forget($component->getStatePath());
        }
    }

    /**
     * Balance the weights of variants equally (e.g. on add/delete).
     */
    public static function balanceWeightsEqually(mixed $component): void
    {
        $items = $component->getState() ?? [];
        $m = count($items);
        if ($m === 0) {
            return;
        }

        $base = (int) (100 / $m);
        $remainder = 100 % $m;

        foreach ($items as $key => $item) {
            $items[$key]['weight'] = $base;
        }

        $keys = array_keys($items);
        for ($i = 0; $i < $remainder; $i++) {
            $k = $keys[$i];
            $items[$k]['weight'] += 1;
        }

        $component->state($items);

        $component->getLivewire()->getErrorBag()->forget($component->getStatePath());
    }

    /**
     * Update validation errors on the repeater level when a slider is dragged.
     */
    public static function updateRepeaterValidationError(mixed $component): void
    {
        $statePath = $component->getStatePath();
        $repeaterPath = (string) str($statePath)->beforeLast('.')->beforeLast('.');
        $livewire = $component->getLivewire();

        $get = $component->makeGetUtility();
        $items = $get($repeaterPath, isAbsolute: true) ?? [];
        $sum = array_sum(array_column($items, 'weight'));

        if ($sum !== 100) {
            $livewire->addError($repeaterPath, __('filament-short-url::default.weights_sum_error', ['sum' => $sum]));
        } else {
            $livewire->getErrorBag()->forget($repeaterPath);
        }
    }
}
