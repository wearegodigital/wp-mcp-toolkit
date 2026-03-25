#!/bin/bash
# WP MCP Toolkit — Development Audit Log
# SQLite-based task tracking for phase lifecycle
#
# Usage:
#   .omc/audit.sh log --phase "workspace-1" --task "Create container" --action "implemented" --status "completed" --description "..."
#   .omc/audit.sh list [--phase X] [--status X]
#   .omc/audit.sh summary --phase "workspace-1"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DB_PATH="$SCRIPT_DIR/audit.db"

# Initialize database if it doesn't exist
init_db() {
    sqlite3 "$DB_PATH" <<'SQL'
CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp TEXT NOT NULL DEFAULT (datetime('now')),
    phase TEXT NOT NULL,
    task TEXT NOT NULL,
    action TEXT NOT NULL,
    files_touched TEXT,
    status TEXT NOT NULL,
    description TEXT,
    retry_context TEXT,
    lines_added INTEGER,
    lines_removed INTEGER
);
CREATE INDEX IF NOT EXISTS idx_audit_phase ON audit_log(phase);
CREATE INDEX IF NOT EXISTS idx_audit_status ON audit_log(status);
SQL
}

# Log a new entry
cmd_log() {
    local phase="" task="" action="" files="" status="" description="" retry_context="" lines_added="" lines_removed=""

    while [[ $# -gt 0 ]]; do
        case "$1" in
            --phase) phase="$2"; shift 2 ;;
            --task) task="$2"; shift 2 ;;
            --action) action="$2"; shift 2 ;;
            --files) files="$2"; shift 2 ;;
            --status) status="$2"; shift 2 ;;
            --description) description="$2"; shift 2 ;;
            --retry-context) retry_context="$2"; shift 2 ;;
            --lines-added) lines_added="$2"; shift 2 ;;
            --lines-removed) lines_removed="$2"; shift 2 ;;
            *) echo "Unknown option: $1"; exit 1 ;;
        esac
    done

    if [[ -z "$phase" || -z "$task" || -z "$action" || -z "$status" ]]; then
        echo "Required: --phase, --task, --action, --status"
        exit 1
    fi

    sqlite3 "$DB_PATH" "INSERT INTO audit_log (phase, task, action, files_touched, status, description, retry_context, lines_added, lines_removed) VALUES ('$(echo "$phase" | sed "s/'/''/g")', '$(echo "$task" | sed "s/'/''/g")', '$(echo "$action" | sed "s/'/''/g")', '$(echo "$files" | sed "s/'/''/g")', '$(echo "$status" | sed "s/'/''/g")', '$(echo "$description" | sed "s/'/''/g")', '$(echo "$retry_context" | sed "s/'/''/g")', ${lines_added:-NULL}, ${lines_removed:-NULL});"

    echo "Logged: [$status] $phase / $task"
}

# List entries with optional filters
cmd_list() {
    local where=""

    while [[ $# -gt 0 ]]; do
        case "$1" in
            --phase) where="$where AND phase = '$2'"; shift 2 ;;
            --status) where="$where AND status = '$2'"; shift 2 ;;
            --action) where="$where AND action = '$2'"; shift 2 ;;
            *) shift ;;
        esac
    done

    # Strip leading " AND "
    where="${where# AND }"
    if [[ -n "$where" ]]; then
        where="WHERE $where"
    fi

    sqlite3 -header -column "$DB_PATH" "SELECT id, timestamp, phase, task, action, status, description FROM audit_log $where ORDER BY id;"
}

# Phase summary
cmd_summary() {
    local phase=""
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --phase) phase="$2"; shift 2 ;;
            *) shift ;;
        esac
    done

    if [[ -z "$phase" ]]; then
        echo "Required: --phase"
        exit 1
    fi

    echo "=== Phase: $phase ==="
    echo ""
    sqlite3 "$DB_PATH" "SELECT 'Total tasks: ' || COUNT(*) FROM audit_log WHERE phase = '$phase';"
    sqlite3 "$DB_PATH" "SELECT 'Completed: ' || COUNT(*) FROM audit_log WHERE phase = '$phase' AND status = 'completed';"
    sqlite3 "$DB_PATH" "SELECT 'Failed: ' || COUNT(*) FROM audit_log WHERE phase = '$phase' AND status = 'failed';"
    echo ""
    echo "--- Tasks ---"
    sqlite3 -header -column "$DB_PATH" "SELECT task, status, description FROM audit_log WHERE phase = '$phase' ORDER BY id;"
}

# Main dispatch
init_db

case "${1:-}" in
    log) shift; cmd_log "$@" ;;
    list) shift; cmd_list "$@" ;;
    summary) shift; cmd_summary "$@" ;;
    *)
        echo "Usage: audit.sh {log|list|summary} [options]"
        echo ""
        echo "  log      --phase X --task X --action X --status X [--description X] [--files X] [--retry-context X]"
        echo "  list     [--phase X] [--status X] [--action X]"
        echo "  summary  --phase X"
        ;;
esac
