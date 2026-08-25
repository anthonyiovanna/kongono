# Kongono Database Mapping

This Experimental package validates one explicit application-owned
`kongono.data-mapping.json` file at build time and produces deterministic
mapping evidence plus ordinary typed PHP Data Mappers and four-operation
repositories.

The package is optional. It does not discover records, inspect attributes,
load generated code in production, own database sessions or transactions, or
provide an ORM. Applications may continue to use the neutral database and SQL
plan packages directly.

Generated output is derived evidence. The package publishes mapping artifacts
and generated PHP under exact content-addressed names and admits one explicitly
selected generated file only after structural, currentness, filename, collision,
unchanged-snapshot, declaring-file, and public-surface checks. Composition and
operation lifecycle ownership remain in the outer database-application package.
