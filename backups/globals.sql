--
-- PostgreSQL database cluster dump
--

\restrict DJSJyFryhZ6hlgg3SJI5K4FbRf6k32ngRFCdeU3ekny7t6GA1jCCtgU7cu9cTE9

SET default_transaction_read_only = off;

SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;

--
-- Roles
--

CREATE ROLE admin_mamias;
ALTER ROLE admin_mamias WITH SUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:dIdefCwGs8+v6ipJrhNe6w==$i7CcR0pqcPAk5bvA48NUKdwWHerUXSejyd067Kur4H4=:mrErNzfm7eo7XOu9VeFxEVQVyU8ZYwJMc/pZz2aqrP4=';
CREATE ROLE postgres;
ALTER ROLE postgres WITH SUPERUSER INHERIT CREATEROLE CREATEDB LOGIN REPLICATION BYPASSRLS;
CREATE ROLE replicator;
ALTER ROLE replicator WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN REPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:pD0G5+La2WdO4erGk+hTQg==$w0EgeU9aPbvYdh2w+pGg5kbod3cjty5Gew8LVO1qfMs=:Cdm95ge29kDYgbn+Wlyc1n0trFv/Mh3vlk5IqZHNN8k=';

--
-- User Configurations
--








\unrestrict DJSJyFryhZ6hlgg3SJI5K4FbRf6k32ngRFCdeU3ekny7t6GA1jCCtgU7cu9cTE9

--
-- PostgreSQL database cluster dump complete
--

