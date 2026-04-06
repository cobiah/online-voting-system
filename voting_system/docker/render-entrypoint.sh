#!/bin/sh
set -eu

PORT_TO_USE="${PORT:-10000}"

# Always rewrite Apache port settings, regardless of their previous value.
sed -E -i "s/^Listen [0-9]+$/Listen ${PORT_TO_USE}/" /etc/apache2/ports.conf
sed -E -i "s#<VirtualHost \\*:[0-9]+>#<VirtualHost *:${PORT_TO_USE}>#" /etc/apache2/sites-available/000-default.conf

apache2ctl -t

exec "$@"
