<?php

use Bjanczak\FilamentShortUrl\Filament\Forms\Components\SegmentControl;

it('exposes segment control configuration via fluent api', function () {
    $field = SegmentControl::make('destination_type')
        ->options([
            'single' => 'Single URL',
            'split' => 'A/B Split',
        ])
        ->icons([
            'single' => 'heroicon-o-link',
        ])
        ->disabledOptions(['split'])
        ->size('lg')
        ->variant('ghost')
        ->separators(false)
        ->fullWidth()
        ->iconOnly()
        ->expandSelectedLabel();

    $options = $field->getNormalizedOptions();

    expect($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('ghost')
        ->and($field->hasSeparators())->toBeFalse()
        ->and($field->isFullWidth())->toBeTrue()
        ->and($field->isIconOnly())->toBeTrue()
        ->and($field->shouldExpandSelectedLabel())->toBeTrue()
        ->and($options['single']['label'])->toBe('Single URL')
        ->and($options['single']['icon'])->toBe('heroicon-o-link')
        ->and($options['split']['disabled'])->toBeTrue()
        ->and($field->getOptionKeys())->toBe(['single', 'split']);
});

it('normalizes rich option arrays for segment control', function () {
    $field = SegmentControl::make('theme')
        ->options([
            'light' => [
                'label' => 'Light',
                'icon' => 'heroicon-o-sun',
            ],
            'dark' => [
                'label' => 'Dark',
                'icon' => 'heroicon-o-moon',
                'disabled' => true,
                'tooltip' => 'Dark mode unavailable',
            ],
        ]);

    $options = $field->getNormalizedOptions();

    expect($options['dark']['disabled'])->toBeTrue()
        ->and($options['dark']['tooltip'])->toBe('Dark mode unavailable')
        ->and($options['light']['icon'])->toBe('heroicon-o-sun');
});

it('preserves integer option keys for segment control', function () {
    $field = SegmentControl::make('redirect_status_code')
        ->options([
            302 => 'Temporary (302)',
            301 => 'Permanent (301)',
        ]);

    expect($field->getOptionKeys())->toBe([302, 301])
        ->and($field->getNormalizedOptions()[302]['label'])->toBe('Temporary (302)');
});
