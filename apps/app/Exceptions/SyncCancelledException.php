<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown from a fetch job's per-record progress callback when the user has
 * requested an abort, so the job stops immediately instead of waiting for the
 * next chunk boundary. Caught by the job and turned into a "cancelled" state.
 */
class SyncCancelledException extends RuntimeException {}
