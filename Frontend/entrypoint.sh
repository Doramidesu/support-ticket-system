#!/bin/sh

PLACEHOLDER="VITE_API_URL_PLACEHOLDER"
REAL_URL=${VITE_API_URL}

if [ -z "$REAL_URL" ]; then
  echo "Error: VITE_API_URL environment variable is not set."
  exit 1
fi

for file in /usr/share/nginx/html/assets/*.js;
do
  sed -i "s|${PLACEHOLDER}|${REAL_URL}|g" $file
done

exec nginx -g 'daemon off;'