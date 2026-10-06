<?php

namespace App\Services;

/**
 * §9 — raised when a requested random draw cannot be honoured.
 *
 * The message is written for whoever is filling in the form: it names the
 * bucket that is short and says how many were asked for, because "could not be
 * satisfied" on its own tells them nothing to act on.
 */
class QuestionQuotaException extends \Exception {}
