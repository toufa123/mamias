--
-- PostgreSQL database cluster dump
--

\restrict XPbmcSihcK7RjBQ4biIpUyj3AywxnWyEO1UucegMVbjCwg85RsYCW5opSJ8wIMM

SET default_transaction_read_only = off;

SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;

--
-- Roles
--

CREATE ROLE admin_mamias;
ALTER ROLE admin_mamias WITH SUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:JtFQcHwGdzj67ewi9x6lIw==$hFBvd2X0P9Mv0dhOLy9OGbenR12y01clI95IxWThv28=:PCUu4x0MvT82MB5NUBPvIOLmSoLIcdXwVhAwc1ejTGY=';
CREATE ROLE postgres;
ALTER ROLE postgres WITH SUPERUSER INHERIT CREATEROLE CREATEDB LOGIN REPLICATION BYPASSRLS;
CREATE ROLE replicator;
ALTER ROLE replicator WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN REPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:bXI4t4sHwdhcvP+CxP/c1Q==$9FpKCw65Flck2cYqwaBnCoMdHFdwOkF+qB5tOkYuL30=:vtjDhjsMQ/o8jOmrhI0JFPPKO6EMETLtqVXyxH63SG0=';

--
-- User Configurations
--








\unrestrict XPbmcSihcK7RjBQ4biIpUyj3AywxnWyEO1UucegMVbjCwg85RsYCW5opSJ8wIMM

--
-- PostgreSQL database cluster dump complete
--

