-- Runs once, on the first boot of an empty PostgreSQL volume.
-- Gives the test suite its own database so `php artisan test` never touches
-- the development data.
CREATE DATABASE goers_test OWNER goers;
