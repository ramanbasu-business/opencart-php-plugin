# ADR 001: Keep the legacy OpenCart extension structure

## Status

Accepted.

## Context

The repository contains a plugin that is meant to be dropped into an existing OpenCart store and called by external systems. It exposes XML endpoints and depends on OpenCart's registry, model layer and database tables. A modern rewrite would be higher risk than a surgical update because it would break installed stores and require a broader deployment migration.

## Decision

Keep the plugin's legacy OpenCart folder structure and class naming for compatibility. Document the actual architecture in the README and add checks that validate syntax, helper logic and deployment-safe docs without changing the host store contract.

## Outcome

The project remains compatible with existing deployments because the files stay in the OpenCart root layout and the end-user routes remain the same. The documentation is honest about the plugin's older approach, and the test harness focuses on safe, platform-independent checks.
