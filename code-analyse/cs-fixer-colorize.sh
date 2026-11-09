#!/usr/bin/env bash

# Runs the given command and colorizes its php-cs-fixer output.
#
# Usage: colorize.sh <command> [args...]
#
# php-cs-fixer must be invoked with --no-ansi: its own diff colorizer degrades
# to quadratic time on large diffs (hundreds of files) and the command hangs.
# So the colors are applied here instead, line by line.
#
# The command is run by this script instead of being piped into it from the
# makefile because make uses /bin/sh (dash), which has no `pipefail`: a pipe in
# the recipe would swallow the php-cs-fixer exit code and never fail the build.
#
# Colors are disabled when stdout is not a terminal or NO_COLOR is set.

set -uo pipefail

color=0

if [ -t 1 ] && [ -z "${NO_COLOR:-}" ] && [ "${TERM:-dumb}" != 'dumb' ]; then
    color=1
fi

"$@" | awk -v color="$color" '
BEGIN {
    if (color) {
        red    = "\033[31m"
        green  = "\033[32m"
        yellow = "\033[33m"
        cyan   = "\033[36m"
        bold   = "\033[1m"
        dim    = "\033[2m"
        off    = "\033[0m"
    }
}

{
    line = $0
    sub(/\r$/, "", line)
}

# progress bar: 1933/6443 [====] 30%
line ~ /^[ \t]*[0-9]+\/[0-9]+ \[/ {
    print dim line off
    next
}

# file header: 1) path/to/File.php (rule_one, rule_two)
line ~ /^[ \t]*[0-9]+\)[ \t]/ {
    gsub(/code-analyse\/\.\.\//, "", line)

    if (match(line, /\([^()]*\)$/)) {
        head = substr(line, 1, RSTART - 1)
        rules = substr(line, RSTART)
    } else {
        head = line
        rules = ""
    }

    print bold yellow head off dim rules off
    next
}

line ~ /^[ \t]*-+ (begin|end) diff -+$/ {
    print dim line off
    next
}

line ~ /^(---|\+\+\+) / {
    print bold line off
    next
}

line ~ /^@@ / {
    print cyan line off
    next
}

line ~ /^\+/ {
    print green line off
    next
}

line ~ /^-/ {
    print red line off
    next
}

line ~ /^(Found|Fixed|Checked) / {
    print bold line off
    next
}

{
    print line
}
'
