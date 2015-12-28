CREATE SCHEMA IF NOT EXISTS tests;

CREATE OR REPLACE FUNCTION tests.test_returns_integer()
RETURNS integer
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 42;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_integer_as_string()
RETURNS character varying
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT '42'::varchar;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_string()
RETURNS character varying
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 'hello'::varchar;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_numeric()
RETURNS numeric
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 3.14159::numeric;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_real()
RETURNS real
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 3.14::real;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_bool_true()
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT true;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_bool_false()
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT false;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_date()
RETURNS date
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT CURRENT_TIMESTAMP::date;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_timestamp()
RETURNS timestamp
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT CURRENT_TIMESTAMP::timestamp;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_time()
RETURNS time
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT CURRENT_TIMESTAMP::time;
$$;

DROP FUNCTION IF EXISTS tests.test_returns_composite();
DROP FUNCTION IF EXISTS tests.test_returns_setof_composite();
DROP TYPE IF EXISTS tests.composite1;
CREATE TYPE tests.composite1 AS (
  a integer,
  b varchar
);

CREATE FUNCTION tests.test_returns_composite()
RETURNS tests.composite1
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT (1, 'hello')::tests.composite1;
$$;

CREATE FUNCTION tests.test_returns_setof_composite()
RETURNS SETOF tests.composite1
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT (1, 'hello')::tests.composite1
  UNION SELECT (2, 'bye')::tests.composite1;
$$;

CREATE OR REPLACE FUNCTION tests._hidden_function()
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT true;
$$;

CREATE OR REPLACE FUNCTION tests.function_in_tests_schema()
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT true;
$$;

CREATE OR REPLACE FUNCTION tests.function_raising_exception()
RETURNS boolean
LANGUAGE PLPGSQL
IMMUTABLE
AS $$
BEGIN
  RAISE EXCEPTION 'an exception';
  SELECT true;
END;
$$;

-- test arguments
CREATE OR REPLACE FUNCTION tests.test_returns_incremented_integer(integer)
RETURNS integer
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1 + 1;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_incremented_numeric(numeric)
RETURNS numeric
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1 + 1.5;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_incremented_real(real)
RETURNS real
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT ($1 + 1.42)::real;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_cat_string(varchar)
RETURNS varchar
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1 || '.';
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_same_bool(boolean)
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_same_date(date)
RETURNS date
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_same_timestamp(timestamp)
RETURNS timestamp
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_same_time(time)
RETURNS time
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1;
$$;

CREATE OR REPLACE FUNCTION tests.test_integer_array_arg(list integer[]) 
RETURNS SETOF integer
LANGUAGE plpgsql
IMMUTABLE
AS $$
DECLARE 
  i integer;
BEGIN
  FOREACH i IN ARRAY list LOOP
    RETURN NEXT i;
  END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION tests.test_varchar_array_arg(list varchar[]) 
RETURNS SETOF varchar
LANGUAGE plpgsql
IMMUTABLE
AS $$
DECLARE 
  i varchar;
BEGIN
  FOREACH i IN ARRAY list LOOP
    RETURN NEXT i;
  END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION tests.test_returns_accented_string()
RETURNS character varying
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 'héllo'::varchar;
$$;
