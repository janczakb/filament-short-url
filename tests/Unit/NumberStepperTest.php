<?php

use Bjanczak\FilamentShortUrl\Filament\Forms\Components\NumberStepper;

it('exposes number stepper configuration via fluent api', function () {
    $field = NumberStepper::make('max_visits')
        ->minValue(1)
        ->maxValue(500)
        ->step(5)
        ->nullable()
        ->nullLabel('No limit')
        ->suffix('visits')
        ->variant('outline')
        ->size('lg');

    expect($field->getMinValue())->toBe(1)
        ->and($field->getMaxValue())->toBe(500)
        ->and($field->getStep())->toBe(5)
        ->and($field->isNullable())->toBeTrue()
        ->and($field->getNullLabel())->toBe('No limit')
        ->and($field->getDisplaySuffix())->toBe('visits')
        ->and($field->getVariant())->toBe('outline')
        ->and($field->getSize())->toBe('lg')
        ->and($field->isInteger())->toBeTrue()
        ->and($field->isNumeric())->toBeTrue();
});

it('supports negative min and max bounds for number stepper', function () {
    $field = NumberStepper::make('offset')
        ->minValue(-10)
        ->maxValue(10)
        ->variant('primary')
        ->size('md');

    expect($field->getMinValue())->toBe(-10)
        ->and($field->getMaxValue())->toBe(10);
});

it('uses number state casts by default', function () {
    $field = NumberStepper::make('count');

    expect($field->getDefaultStateCasts())->not->toBeEmpty();
});
