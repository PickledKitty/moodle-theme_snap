#!/bin/bash
# Runs Moodle cron every minute in the background. Started by postStartCommand.
# Log: /var/log/moodle-cron.log    Stop: kill "$(cat /tmp/moodle-cron.pid)"

MOODLE_DIR="/var/www/html"
LOG="/var/log/moodle-cron.log"
PIDFILE="/tmp/moodle-cron.pid"

if [ "$1" != "--loop" ]; then
    if [ -f "${PIDFILE}" ] && kill -0 "$(cat ${PIDFILE})" 2>/dev/null \
        && grep -q "cron.sh" "/proc/$(cat ${PIDFILE})/cmdline" 2>/dev/null; then
        echo "Moodle cron already running (PID $(cat ${PIDFILE}))"
        exit 0
    fi
    setsid nohup bash "$0" --loop >> "${LOG}" 2>&1 < /dev/null &
    echo $! > "${PIDFILE}"
    echo "Moodle cron started (log: ${LOG})"
    exit 0
fi

while true; do
    # Keep the log from growing without limit.
    if [ -f "${LOG}" ] && [ "$(stat -c %s "${LOG}")" -gt 10485760 ]; then
        : > "${LOG}"
    fi
    if [ -f "${MOODLE_DIR}/config.php" ]; then
        echo "--- $(date '+%F %T') ---"
        runuser -u www-data -- php "${MOODLE_DIR}/admin/cli/cron.php"
    fi
    sleep 60
done
