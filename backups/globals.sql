--
-- PostgreSQL database cluster dump
--

\restrict ckA9vyE2HRhZAvkwvAx0iyh1u1XdKQ0cSBpZJKJWOjLeoCE8aQXxbSO3p2FqDB1

SET default_transaction_read_only = off;

SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;

--
-- Roles
--

CREATE ROLE admin_mamias;
ALTER ROLE admin_mamias WITH SUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:PLe8c6e/IIO5Oz38XIhXZw==$0yHW87dKbvqNjWkUpPr8xlLxnK+hMPPFvFdXWs6jezA=:FMOmZiOVjWxjQ8ZaQ9OYppiNWHPppdaULVjV7RTSIyc=';
CREATE ROLE postgres;
ALTER ROLE postgres WITH SUPERUSER INHERIT CREATEROLE CREATEDB LOGIN REPLICATION BYPASSRLS;
CREATE ROLE replicator;
ALTER ROLE replicator WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN REPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:GK2R0eTo87Ad1gOnai+6Kw==$K6dDNGb4CClwVaMOsPlekMi/TFgM4b4+4eyU2r+Pt08=:GIpS7SO2kb2WL6g+uklCBn6fpBOvZ0LAdf1I/Pm9A/w=';

--
-- User Configurations
--








\unrestrict ckA9vyE2HRhZAvkwvAx0iyh1u1XdKQ0cSBpZJKJWOjLeoCE8aQXxbSO3p2FqDB1

--
-- PostgreSQL database cluster dump complete
--

