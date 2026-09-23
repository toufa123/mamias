<?php

declare(strict_types=1);

/*
|------------------------------------------------------------------------------
| Interactive command picker for the MAMIAS Makefile
|------------------------------------------------------------------------------
|
| The picker itself is rendered with php-school/cli-menu (the library
| nunomaduro/laravel-console-menu wraps for Artisan commands) — used directly
| here, with no Laravel framework boot, so this still works when the whole
| Docker stack is down (e.g. to run dev-up itself from cold). Everything
| around the picker — danger confirmation, the FILTER=/FILE= prompts, run
| output — stays on laravel/prompts, already a dependency of apps/.
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
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\MenuItem\AsciiArtItem;
use PhpSchool\CliMenu\MenuItem\LineBreakItem;
use PhpSchool\CliMenu\MenuItem\StaticItem;
use PhpSchool\Terminal\InputCharacter;
use PhpSchool\Terminal\NonCanonicalReader;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

chdir(__DIR__);

$autoload = __DIR__.'/apps/vendor/autoload.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "menu: apps/vendor/autoload.php not found — run composer install.\n");
    exit(2);
}

require $autoload;

if (! function_exists('\Laravel\Prompts\confirm') || ! class_exists(CliMenuBuilder::class)) {
    fwrite(STDERR, "menu: laravel/prompts or php-school/cli-menu is not installed.\n");
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

// Target column width, so item labels line up regardless of group headers.
$width = max(array_map(static fn (array $c): int => strlen($c['target']), $commands));

$byTarget = array_column($commands, null, 'target');

$runButtonLabel = '  ▶ Run checked items';

$labels = [];

foreach ($commands as $command) {
    $labels[$command['target']] = sprintf(
        '  %-'.$width.'s  %s%s',
        $command['target'],
        $command['danger'] ? '⚠ ' : '',
        $command['description'],
    );
}

$logo = <<<'LOGO'
╔══════════════════════╗
║        MAMIAS        ║
╚══════════════════════╝
LOGO;

// Wide enough that no item, group header, or the run button wraps — a
// checkbox marker ("[✔] "/"[ ] ") eats 4 columns of content width, plus
// padding (2×4) and border (2×2) around it. setWidth() clamps to the real
// terminal width on its own if this exceeds it, so overshooting is harmless.
$contentWidth = max(array_map(
    'mb_strwidth',
    [...$labels, ...array_unique(array_column($commands, 'group')), $runButtonLabel],
));
$menuWidth = $contentWidth + 4 + (2 * 4) + (2 * 2);

/**
 * Hand the terminal back to normal (canonical) mode for the duration of a
 * run — passthru's live output and laravel/prompts' own prompts both need
 * this, and cli-menu's raw mode would otherwise fight them for the screen.
 * Mirrors CliMenu's own (private) tearDownTerminal() exactly, using only its
 * public Terminal API — deliberately NOT $cliMenu->close(), which would also
 * flip isOpen() to false and make flash()/confirm() throw afterwards.
 */
function releaseTerminal(CliMenu $cliMenu): void
{
    $terminal = $cliMenu->getTerminal();
    $terminal->restoreOriginalConfiguration();
    $terminal->enableCanonicalMode();
    $terminal->enableEchoBack();
    $terminal->enableCursor();
}

/**
 * The other half of releaseTerminal() — mirrors CliMenu's own (private)
 * configureTerminal(), so cli-menu can render again afterwards.
 */
function reclaimTerminal(CliMenu $cliMenu): void
{
    $terminal = $cliMenu->getTerminal();
    $terminal->disableCanonicalMode();
    $terminal->disableEchoBack();
    $terminal->disableCursor();
    $terminal->clear();
}

/**
 * Discard anything already sitting in the input buffer.
 *
 * Flash/Confirm dismiss on the very next keystroke they read — including one
 * you pressed *before* they were even displayed (e.g. an impatient extra
 * Enter while `make` was running). Without this, that stray keystroke gets
 * consumed instantly and the dialogue disappears in the same frame it's
 * drawn, which looks exactly like it never rendered at all.
 */
function drainStdin(): void
{
    stream_set_blocking(STDIN, false);

    while (fread(STDIN, 1024) !== '') {
        // discard
    }

    stream_set_blocking(STDIN, true);
}

/**
 * Run one target through `make` with live output.
 *
 * Also tees that same output to a temp file so a failure's actual message
 * can be shown afterwards — passthru() alone only streams to the terminal,
 * it never hands the text back to PHP. `set -o pipefail` is required: without
 * it, piping through `tee` would report *tee's* exit code (always 0)
 * instead of the target's real one.
 */
function runTarget(string $target, string $arguments, ?string &$output = null): bool
{
    $line = trim('make --no-print-directory '.$target.' '.$arguments);

    note('❯ '.$line);

    $logFile = tempnam(sys_get_temp_dir(), 'mamias_menu_');
    $piped = 'set -o pipefail; '.$line.' 2>&1 | tee '.escapeshellarg($logFile);

    $exitCode = 0;
    passthru('bash -c '.escapeshellarg($piped), $exitCode);

    $output = trim((string) @file_get_contents($logFile));
    @unlink($logFile);

    if ($exitCode === 0) {
        info($target.' finished.');

        return true;
    }

    error($target.' exited with code '.$exitCode.'.');

    return false;
}

/**
 * Show a genuinely multi-line message — Flash/Confirm can't: CliMenu::flash()
 * and ::confirm() both call guardSingleLine() and throw on any "\n" in the
 * text, no way around that. StaticItem has no such limit (it's what group
 * headers already are), so this swaps the SAME menu's item list to one
 * StaticItem per line plus a dismiss prompt, waits for Enter using the exact
 * read loop Confirm uses internally, then puts the real items back.
 *
 * Reuses $cliMenu's own Terminal rather than opening a second CliMenu for
 * this: UnixTerminal caches canonical/echo state per-instance from whatever
 * `stty` reported at *construction* time, so a second instance built while
 * already in raw mode would restore back to raw (not the real original) on
 * its own close() — leaving the outer menu's cached state stale either way.
 * One Terminal, mutated and restored in place, has no such mismatch.
 */
function showMultilineNotice(CliMenu $cliMenu, string $title, string $body): void
{
    $originalItems = $cliMenu->getItems();

    $lines = array_filter(array_map('trim', explode("\n", $body)), static fn (string $l): bool => $l !== '');

    $cliMenu->setItems([
        new StaticItem($title),
        new LineBreakItem('─'),
        ...array_map(static fn (string $l): StaticItem => new StaticItem($l), $lines !== [] ? $lines : ['(no output)']),
        new LineBreakItem(' '),
        new StaticItem('Press Enter to continue...'),
    ]);

    $cliMenu->redraw(true);

    drainStdin();

    $reader = new NonCanonicalReader($cliMenu->getTerminal());

    while ($char = $reader->readCharacter()) {
        if ($char->isControl() && $char->getControl() === InputCharacter::ENTER) {
            break;
        }
    }

    $cliMenu->setItems($originalItems);
}

Prompt::fallbackWhen(false);

intro('MAMIAS  ·  make');

while (true) {
    $ran = false;
    $checked = [];

    $builder = (new CliMenuBuilder)
        ->setTitle('MAMIAS: Deployment')
        ->setExitButtonText('Quit')
        ->setBorder(1, 2, 'yellow')
        ->setPadding(2, 4)
        ->setWidth($menuWidth)
        ->setMarginAuto();

    $builder->addAsciiArt($logo, AsciiArtItem::POSITION_CENTER, 'MAMIAS');
    $builder->addLineBreak(' ');
    $builder->addStaticItem(stackStatus());
    $builder->addLineBreak('─');

    $group = null;

    foreach ($commands as $command) {
        if ($command['group'] !== $group) {
            if ($group !== null) {
                $builder->addLineBreak('─');
            }

            $group = $command['group'];
            $builder->addStaticItem($group);
        }

        $builder->addCheckboxItem($labels[$command['target']], function (CliMenu $cliMenu) use (&$checked, $command): void {
            $checked = in_array($command['target'], $checked, true)
                ? array_values(array_diff($checked, [$command['target']]))
                : [...$checked, $command['target']];
        });
    }

    $builder->addLineBreak('─');
    $builder->addItem($runButtonLabel, function (CliMenu $cliMenu) use (&$checked, &$ran, $byTarget): void {
        if ($checked === []) {
            drainStdin();
            $cliMenu->flash('Nothing checked — tick at least one item first.')->display();

            return;
        }

        $dangerCount = count(array_filter($checked, static fn (string $t): bool => $byTarget[$t]['danger']));

        if ($dangerCount > 0) {
            drainStdin();
            $cliMenu->confirm(
                "⚠ {$dangerCount} checked item(s) are destructive — you'll confirm each one before it runs."
            )->display('I understand');
        }

        // Hand the terminal to the runs below (passthru + laravel/prompts both
        // need it), then take it back — deliberately not $cliMenu->close(),
        // so the menu is still open() when we flash the result on it below.
        releaseTerminal($cliMenu);

        $failures = [];

        foreach ($checked as $target) {
            $command = $byTarget[$target];

            if ($command['danger'] === true) {
                warning($command['target'].' is destructive: '.$command['description']);

                if (confirm(label: 'Run it anyway?', default: false) !== true) {
                    note('Skipped '.$target.'.');

                    continue;
                }
            }

            $output = null;

            if (! runTarget($target, extraArguments($target), $output)) {
                $failures[$target] = $output;
            }
        }

        // reclaimTerminal() already cleared the screen; redraw(false) avoids
        // doing that a second time.
        reclaimTerminal($cliMenu);
        $cliMenu->redraw(false);

        // One notice per failure, each showing that target's real (possibly
        // multi-line) output in full — not a collapsed one-liner — so you see
        // exactly what went wrong without scrolling back through the live
        // output above.
        foreach ($failures as $target => $output) {
            showMultilineNotice($cliMenu, '⚠ '.$target.' failed', $output !== '' ? $output : '(no output)');
        }

        $ran = true;
        $cliMenu->close();
    });

    $builder->build()->open();

    if (! $ran) {
        outro('Bye.');
        break;
    }
}
