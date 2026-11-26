#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
: "${GML_TEST_CORE_DIR:?Core test checkout required}"
: "${GML_TEST_WP_ROOT:?Disposable WordPress installation required}"
: "${GML_TEST_PRODUCT_DIR:?Product checkout required}"
test "${GML_DATABASE_TESTS:-}" = 1
scenario_count=0
run_state() {
    local output
    if ! output="$(php "$ROOT/state-clarity.php" "$1" 2>&1)"; then
        printf '%s\n' "$output" >&2
        return 1
    fi
    printf '%s\n' "$output"
    grep -Fq 'MARKER wordpress_bootstrap_loaded' <<<"$output"
    grep -Fq "OK state clarity $1" <<<"$output"
    scenario_count=$((scenario_count + 1))
}
run_state prepare
run_state remove-script
# Deliberately hold the dispatcher beyond the existing 30-second event.
# This is controlled fixture timing, not production WP-Cron acceptance.
sleep 32
for scenario in delayed recover text-edit critical failure failure-recover reschedule ui-race tm-signal verify; do
    run_state "$scenario"
done
test "$scenario_count" -eq 12
echo "MARKER state_clarity_scenarios_ran=$scenario_count"

extra_count=0
for scenario in admin-snapshot-race parser-exclusions; do
    if ! output="$(php "$ROOT/$scenario.php" 2>&1)"; then
        printf '%s\n' "$output" >&2
        exit 1
    fi
    printf '%s\n' "$output"
    grep -Fq 'MARKER wordpress_bootstrap_loaded' <<<"$output"
    case "$scenario" in
        admin-snapshot-race) grep -Fq 'OK admin snapshot race' <<<"$output" ;;
        parser-exclusions) grep -Fq 'OK parser exclusions' <<<"$output" ;;
    esac
    extra_count=$((extra_count + 1))
done
test "$extra_count" -eq 2
echo "MARKER state_clarity_extra_scenarios_ran=$extra_count"
