<?php

declare(strict_types=1);

/*
|------------------------------------------------------------------------------
| Interactive command picker for the MAMIAS Makefile
|------------------------------------------------------------------------------
|
| Rendered with laravel/prompts — the same library the Laravel installer uses,
| so this gets arrow-key navigation, colours and the familiar boxed styling for
| free. It is already a dependency of apps/, so nothing extra is installed.
|
| The command list is parsed out of the Makefile's own annotations, exactly like
| menu.sh does, so the two stay in step and neither duplicates the target list:
|
|   ##@ Group name          section header
|   target: ## Description  menu entry
|   target: ##! Description entry that must be confirmed before it runs
|
| Exit code 2 means "I cannot render" (no autoloader, or not a terminal); the
| Makefile falls back to menu.sh in that case.
|
*/

use Laravel\Prompts\Prompt;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

chdir(__DIR__);

$autoload = __DIR__.'/apps/vendor/autoload.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "menu: apps/vendor/autoload.php not found — run composer install.\n");
    exit(2);
}

require $autoload;

if (! function_exists('\Laravel\Prompts\select')) {
    fwrite(STDERR, "menu: laravel/prompts is not installed.\n");
    exit(2);
}

// Prompts needs a real terminal for the interactive renderer. Bail out with the
// fallback code rather than throwing when piped (CI, `make menu < /dev/null`).
if (! stream_isatty(STDIN) || ! stream_isatty(STDOUT)) {
    exit(2);
}

/**
 * Parse the Makefile annotations into an ordered command list.
 *
 * @return list<array{target: string, group: string, danger: bool, description: string}>
 */
function parseMakefile(string $path): array
{
    $commands = [];
    $group = 'General';

    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (preg_match('/^##@\s*(.+)$/', $line, $m) === 1) {
            $group = trim($m[1]);

            continue;
        }

        if (preg_match('/^([a-zA-Z0-9_-]+):[^=#]*##(!?)\s*(.+)$/', $line, $m) === 1) {
            $commands[] = [
                'target' => $m[1],
                'group' => $group,
                'danger' => $m[2] === '!',
                'description' => trim($m[3]),
            ];
        }
    }

    return $commands;
}

/**
 * One-line summary of the running stack. Never fatal — Docker may be stopped.
 */
function stackStatus(): string
{
    $output = @shell_exec('docker compose --profile dev ps --status running --format "{{.Name}}" 2>/dev/null');

    $running = array_filter(array_map('trim', explode("\n", (string) $output)));

    return $running === []
        ? 'stack: down'
        : 'stack: '.count($running).' container(s) up';
}

/**
 * Targets that accept an optional variable, so the interactive path exposes the
 * same knobs as calling `make dev-test FILTER=...` directly.
 */
function extraArguments(string $target): string
{
    $ask = match ($target) {
        'dev-test' => ['FILTER', 'Filter tests by name', 'blank = whole suite'],
        'dev-db-restore', 'dev-db-full-restore' => ['FILE', 'Snapshot to restore', 'blank = latest'],
        default => null,
    };

    if ($ask === null) {
        return '';
    }

    [$name, $label, $placeholder] = $ask;

    $value = trim(text(label: $label, placeholder: $placeholder, required: false));

    return $value === '' ? '' : $name.'='.escapeshellarg($value);
}

$commands = parseMakefile(__DIR__.'/Makefile');

if ($commands === []) {
    fwrite(STDERR, "menu: no annotated targets found in the Makefile.\n");
    exit(2);
}

// Build the select options once. Labels are plain text: prompts measures widths
// itself, and embedded ANSI would throw the column alignment off.
$width = max(array_map(static fn (array $c): int => strlen($c['target']), $commands));

$options = [];

foreach ($commands as $command) {
    $options[$command['target']] = sprintf(
        '%-10s %-'.$width.'s  %s%s',
        $command['group'],
        $command['target'],
        $command['danger'] ? '⚠ ' : '',
        $command['description'],
    );
}

$options['__quit'] = str_repeat(' ', 10).' Quit';

$byTarget = array_column($commands, null, 'target');

Prompt::fallbackWhen(false);

intro('MAMIAS  ·  make');

while (true) {
    $target = select(
        label: 'What would you like to run?',
        options: $options,
        scroll: 20,
        hint: 'Arrows to move, Enter to run, Ctrl+C to quit.',
        info: stackStatus(),
    );

    if ($target === '__quit') {
        outro('Bye.');
        break;
    }

    $command = $byTarget[$target];

    if ($command['danger'] === true) {
        warning($command['target'].' is destructive: '.$command['description']);

        if (confirm(label: 'Run it anyway?', default: false) !== true) {
            note('Aborted.');

            continue;
        }
    }

    $arguments = extraArguments($target);

    $line = trim('make --no-print-directory '.$target.' '.$arguments);

    note('❯ '.$line);

    // Hand the terminal over so the target's own output streams through live.
    $exitCode = 0;
    passthru($line, $exitCode);

    if ($exitCode === 0) {
        info($target.' finished.');
    } else {
        error($target.' exited with code '.$exitCode.'.');
    }
}
