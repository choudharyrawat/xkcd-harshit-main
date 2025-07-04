#!/bin/bash

# This script sets up a CRON job to send XKCD comics every 24 hours.

# Get the absolute path to the directory containing this script
# This ensures that the cron job can find the cron.php file regardless of
# where the setup script is executed from.
SCRIPT_DIR=$(cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd)

# Define the php script to be executed
CRON_SCRIPT_PATH="${SCRIPT_DIR}/cron.php"

# Check if the cron.php file is executable. If not, make it executable.
if [ ! -x "$CRON_SCRIPT_PATH" ]; then
    echo "Making cron.php executable..."
    chmod +x "$CRON_SCRIPT_PATH"
fi

# Define the cron job command.
# It runs at midnight (00:00) every day.
# The `which php` command finds the path to the PHP executable.
CRON_JOB="0 0 * * * $(which php) ${CRON_SCRIPT_PATH}"

# Add the new cron job.
# This command first lists existing cron jobs, appends the new one,
# and then installs the new crontab. The `grep` command prevents adding
# duplicate jobs.
(crontab -l 2>/dev/null | grep -v -F "$CRON_SCRIPT_PATH" ; echo "$CRON_JOB") | crontab -

echo "✅ CRON job has been set up successfully!"
echo "The job is scheduled to run daily at midnight."
echo "Command: $CRON_JOB"