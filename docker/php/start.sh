#!/bin/sh
set -e

AUTO_MIGRATE="${AUTO_MIGRATE:-true}"
AUTO_SEED="${AUTO_SEED:-true}"
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-60}"

wait_for_database() {
    if [ "${DB_CONNECTION:-}" != "pgsql" ]; then
        return
    fi

    echo "Waiting for PostgreSQL at ${DB_HOST:-postgres}:${DB_PORT:-5432}..."

    attempt=0

    until php -r '
        $host = getenv("DB_HOST") ?: "postgres";
        $port = getenv("DB_PORT") ?: "5432";
        $database = getenv("DB_DATABASE") ?: "";
        $username = getenv("DB_USERNAME") ?: "";
        $password = getenv("DB_PASSWORD") ?: "";

        try {
            new PDO("pgsql:host={$host};port={$port};dbname={$database}", $username, $password, [
                PDO::ATTR_TIMEOUT => 2,
            ]);

            exit(0);
        } catch (Throwable) {
            exit(1);
        }
    '; do
        attempt=$((attempt + 1))

        if [ "$attempt" -ge "$DB_WAIT_TIMEOUT" ]; then
            echo "PostgreSQL is not ready after ${DB_WAIT_TIMEOUT}s." >&2
            exit 1
        fi

        sleep 1
    done

    echo "PostgreSQL is ready."
}

if [ ! -f vendor/autoload.php ]; then
    composer install
fi

if [ "$AUTO_MIGRATE" = "true" ]; then
    wait_for_database
    php artisan migrate --force
fi

if [ "$AUTO_SEED" = "true" ]; then
    php artisan db:seed --force
fi

exec php artisan serve --host=0.0.0.0 --port=25200
