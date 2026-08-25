# Kongono Database Application

This Experimental outer integration package combines exact-current generated
data access with Kongono's accepted generated composition lifecycle. It opens
one explicit connector session for each generated operation, closes that
session through reverse generated cleanup, and validates a single typed data
operation root before application startup.

The package selects no provider, compiler, credentials, transaction policy,
mapping discovery, migration behavior, retry, pool, ambient connection, ORM,
HTTP integration, or deployment entrypoint. Applications supply startup inputs
explicitly and retain ownership of work and transaction decisions.
