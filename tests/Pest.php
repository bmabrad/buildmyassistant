<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extends(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');
