<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A booking operation that is well-formed but breaks a business rule.
 *
 * The message is written for the person who triggered it and is safe to show
 * directly in a flash message. Controllers catch this base type so a new rule
 * does not need every call site updated.
 */
abstract class BookingRuleViolation extends RuntimeException {}
