-- Database used by the test suite (phpunit.xml: DB_DATABASE=kelaa_testing).
SELECT 'CREATE DATABASE kelaa_testing' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'kelaa_testing')\gexec
