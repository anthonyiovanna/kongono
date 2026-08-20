# Kongono Routing

This package provides Kongono's bounded, deterministic route declaration and PSR-7 server-request matching model.

Phase 2D supports exact HTTP methods, literal and whole-segment parameters, deterministic specificity, and immutable route-table snapshots. Matching reads only `ServerRequestInterface::getMethod()`, `getUri()`, and `UriInterface::getPath()`; it does not decode or normalize encoded path bytes.

The declarations are Experimental except for the Public package exception marker and the Internal diagnostic and syntax machinery. The package does not dispatch handlers, negotiate methods, redirect paths, read request attributes, or depend on Kongono's first-party HTTP-message implementation.
