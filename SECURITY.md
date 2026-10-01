# Security Policy

## Scope

This repository contains a legacy OpenCart PHP extension that is designed to be installed into an existing OpenCart store and accessed through XML routes and job endpoints. It is not a standalone web service and should be treated as a store-installed extension.

## Supported security posture

The codebase currently relies on the following assumptions:

- the extension is deployed inside a trusted OpenCart environment
- the host store is behind HTTPS
- the admin credentials used by the integration are managed at the store level
- secret values are configured on the server, not derived from request metadata

## Important security notes from the code

The runtime auth logic in `src/system/library/scoc_encoder.php` intentionally does the following:

- checks the configured store secret from OpenCart config or the environment variable `SCOC_SECRET_KEY`
- rejects authentication when no secret is configured
- does not trust the browser `Origin` header as a secret source

This is a deliberate fail-closed design and is safer than deriving a cryptographic key from client-controlled request metadata.

## Secrets and credentials

- Do not commit real credentials to the repository.
- Use examples such as `CHANGE_ME` only in documentation or test fixtures.
- Keep the configured secret in a secure server-side secret manager or environment configuration.
- Avoid embedding credentials in XML examples, sample payloads, or route guides.

## Reporting a vulnerability

Please report suspected security issues privately.

Use one of the following options:

- open a private GitHub security advisory for this repository, if enabled
- contact the maintainer through the repository owner’s verified private channel
- provide a minimal reproduction and a description of the issue without exposing production credentials or customer data

Please do not disclose vulnerabilities in public issues before they are reviewed and patched.

## Response expectations

The project is a small legacy OpenCart plugin, so the expected response is pragmatic and review-focused rather than enterprise-scale. The maintainer will:

- review the report
- triage severity and exploitability
- apply the smallest safe fix
- document the change in the changelog if appropriate

## Security checklist for deployments

Before deploying this extension to a live OpenCart store:

- verify TLS is enabled for all integration traffic
- restrict access to the XML endpoints to the known downstream system
- set a strong server-side secret value for `scoc_secret_key` or `SCOC_SECRET_KEY`
- review any cron or job URLs for authentication and network exposure
- ensure no staging or production URLs remain in public-facing examples

## Project limitation

This repository is not a modern API platform and does not implement current standard authentication patterns such as OAuth2, signed API tokens, or per-client key scopes. It should be evaluated as a legacy extension inside a managed OpenCart deployment rather than as a general-purpose public API service.
