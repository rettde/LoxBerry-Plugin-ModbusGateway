#!/bin/bash
ACTION="$1"
SERVICE="$2"

# Whitelist allowed actions
case "$ACTION" in
  start|stop|restart|enable|disable|status) ;;
  *) echo "ERROR: action '$ACTION' not allowed"; exit 1 ;;
esac

# Validate service name pattern
if [[ ! "$SERVICE" =~ ^mbusd@[a-zA-Z0-9._:-]+\.service$ ]]; then
  echo "ERROR: invalid service name '$SERVICE'"; exit 1
fi

systemctl "$ACTION" "$SERVICE"
