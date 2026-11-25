<?php

declare(strict_types=1);
use Tests\TestCase;

/*
 * Pest configuration.
 *
 * Feature tests boot the framework (and therefore the MySQL connection), unit tests
 * stay framework-free so they run in milliseconds.
 */

uses(TestCase::class)->in('Feature');
