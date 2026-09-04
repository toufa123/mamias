#!/usr/bin/env bash
# =============================================================================
#  Interactive command picker for the MAMIAS Makefile ("make", or "make menu").
#
#  The menu is generated from the Makefile itself. Every target carrying a
#  "## description" annotation appears here, grouped under the nearest preceding
#  "##@ Group" header, in file order. Add an annotated target and it shows up on
#  its own — there is no second list to keep in sync.
#
#  Annotations:
#    ##@ Group name          section header
#    target: ## Description  menu entry
#    target: ##! Description menu entry that asks for confirmation first
# =============================================================================
set -uo pipefail

MAKEFILE="${MAKEFILE_PATH:-Makefile}"

if [ ! -f "$MAKEFILE" ]; then
    echo "menu: $MAKEFILE not found (run make from the repository root)." >&2
    exit 1
fi

# ── Colours, only when writing to a terminal ─────────────────────────────────
if [ -t 1 ] && [ "${NO_COLOR:-}" = "" ]; then
    BOLD=$'\033[1m'; DIM=$'\033[2m'; RESET=$'\033[0m'
    CYAN=$'\033[36m'; YELLOW=$'\033[33m'; RED=$'\033[31m'; GREEN=$'\033[32m'
else
    BOLD=""; DIM=""; RESET=""; CYAN=""; YELLOW=""; RED=""; GREEN=""
fi

# ── Parse the Makefile into ordered entries ──────────────────────────────────
# Each entry is "G<TAB>group" or "T<TAB>target<TAB>danger<TAB>description".
#
# The danger field is "!" or "-", never empty: tab is IFS whitespace, so `read`
# collapses runs of it and an empty field would silently shift the description
# into the wrong variable.
entries=()
targets=()
dangers=()

while IFS= read -r line; do
    if [[ $line =~ ^##@[[:space:]]*(.+)$ ]]; then
        entries+=("G	${BASH_REMATCH[1]}")
    elif [[ $line =~ ^([a-zA-Z0-9_-]+):[^=#]*##(!?)[[:space:]]*(.+)$ ]]; then
        danger="${BASH_REMATCH[2]:-}"
        [ -n "$danger" ] || danger="-"
        entries+=("T	${BASH_REMATCH[1]}	${danger}	${BASH_REMATCH[3]}")
        targets+=("${BASH_REMATCH[1]}")
        dangers+=("$danger")
    fi
done <"$MAKEFILE"

if [ ${#targets[@]} -eq 0 ]; then
    echo "menu: no annotated targets found in $MAKEFILE." >&2
    exit 1
fi

# ── Render ───────────────────────────────────────────────────────────────────
print_menu() {
    local i=0 kind group target danger desc

    printf '\n%s╭─ MAMIAS ─ make%s  %s%s%s\n' "$BOLD" "$RESET" "$DIM" "$(stack_status)" "$RESET"

    for entry in "${entries[@]}"; do
        IFS=$'\t' read -r kind a b c <<<"$entry"
        if [ "$kind" = "G" ]; then
            group="$a"
            printf '%s│%s\n%s│ %s%s%s\n' "$BOLD" "$RESET" "$BOLD" "$CYAN$group$RESET" "$BOLD" "$RESET"
        else
            target="$a"; danger="$b"; desc="$c"
            i=$((i + 1))
            if [ "$danger" = "!" ]; then
                printf '%s│%s  %s%2d%s) %s%-22s%s %s%s%s\n' \
                    "$BOLD" "$RESET" "$BOLD" "$i" "$RESET" "$RED" "$target" "$RESET" "$DIM" "$desc" "$RESET"
            else
                printf '%s│%s  %s%2d%s) %s%-22s%s %s%s%s\n' \
                    "$BOLD" "$RESET" "$BOLD" "$i" "$RESET" "$GREEN" "$target" "$RESET" "$DIM" "$desc" "$RESET"
            fi
        fi
    done

    printf '%s│%s\n%s╰─%s %sq%s) quit   %s(red = destructive)%s\n\n' \
        "$BOLD" "$RESET" "$BOLD" "$RESET" "$BOLD" "$RESET" "$DIM" "$RESET"
}

# One-line summary of what is currently running, so you can see the stack state
# before picking. Kept cheap and never fatal — Docker may not be running at all.
stack_status() {
    local running
    running=$(docker compose --profile dev ps --status running --format '{{.Name}}' 2>/dev/null | grep -c . || true)

    if [ -z "$running" ] || [ "$running" = "0" ]; then
        echo "stack: down"
    else
        echo "stack: $running container(s) up"
    fi
}

# Targets that take an optional variable. Asking here means the interactive
# path exposes the same knobs as the direct "make dev-test FILTER=..." call.
extra_args() {
    local target="$1" value=""

    case "$target" in
        dev-test)
            read -r -p "  FILTER (blank = whole suite): " value
            [ -n "$value" ] && printf 'FILTER=%s' "$value"
            ;;
        dev-db-restore | dev-db-full-restore)
            read -r -p "  FILE (blank = latest snapshot): " value
            [ -n "$value" ] && printf 'FILE=%s' "$value"
            ;;
    esac
}

run_target() {
    local target="$1" danger="$2" args reply

    if [ "$danger" = "!" ]; then
        printf '\n%s%s is destructive.%s\n' "$RED$BOLD" "$target" "$RESET"
        read -r -p "  Type 'yes' to continue: " reply
        if [ "$reply" != "yes" ]; then
            printf '%sAborted.%s\n' "$YELLOW" "$RESET"
            return 0
        fi
    fi

    args="$(extra_args "$target")"

    printf '\n%s❯ make %s %s%s\n\n' "$BOLD" "$target" "$args" "$RESET"
    # Recurse into make rather than duplicating any logic here.
    make --no-print-directory "$target" $args
    printf '\n%s❮ make %s finished (exit %s)%s\n' "$DIM" "$target" "$?" "$RESET"
}

# ── Non-interactive: behave like "make help" instead of hanging on read ──────
if [ ! -t 0 ]; then
    print_menu
    exit 0
fi

if [ "${1:-}" = "--list" ]; then
    print_menu
    exit 0
fi

while true; do
    print_menu

    # Test read's status, not just its output: on EOF (stdin closed mid-pipe) it
    # returns non-zero with an empty value, and treating that as "redraw" would
    # spin the menu forever.
    if ! read -r -p "Select a number (or q): " choice; then
        printf '\n'
        exit 0
    fi

    case "$choice" in
        q | Q | quit | exit)
            printf 'Bye.\n'
            exit 0
            ;;
        "")
            continue
            ;;
        *[!0-9]*)
            printf '%sNot a number.%s\n' "$YELLOW" "$RESET"
            continue
            ;;
    esac

    if [ "$choice" -lt 1 ] || [ "$choice" -gt "${#targets[@]}" ]; then
        printf '%sPick between 1 and %s.%s\n' "$YELLOW" "${#targets[@]}" "$RESET"
        continue
    fi

    idx=$((choice - 1))
    run_target "${targets[$idx]}" "${dangers[$idx]}"

    printf '\n'
    if ! read -r -p "Press Enter for the menu, or q to quit: " again; then
        printf '\n'
        exit 0
    fi
    case "$again" in
        q | Q) printf 'Bye.\n'; exit 0 ;;
    esac
done
