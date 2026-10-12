FROM php:8.4-cli-alpine

WORKDIR /var/www/html

RUN apk add --no-cache nodejs npm

EXPOSE 5173
CMD ["sh", "-c", "npm ci && npm run dev -- --host 0.0.0.0"]
