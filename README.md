# OpenCart PHP Plugin

This repository contains a legacy OpenCart extension for XML-based catalog, order, shipping and inventory synchronization. It is not a standalone PHP application; it is meant to be installed into an existing OpenCart store and invoked through the store’s controller routes.

The code in this repository follows the real OpenCart layout used by installed extensions:

- controllers live under `src/catalog/controller/scoc/`
- shared library logic lives under `src/system/library/`
- XML and job handling are implemented in legacy PHP classes rather than a modern service layer

## What the plugin does

From the source code, the extension provides these main capabilities:

- product export
- order export
- category export
- shipping export
- inventory export
- XML import jobs
- cron-driven processing for queued jobs
- job lookup and job log output endpoints
- install-time database setup for the job table

## Main code paths

### Controllers

- `src/catalog/controller/scoc/install.php` – creates the job table and prints an install message
- `src/catalog/controller/scoc/export.php` – exposes XML endpoints for authentication and export output
- `src/catalog/controller/scoc/import.php` – receives XML payloads and dispatches processing
- `src/catalog/controller/scoc/job.php` – returns job and log XML
- `src/catalog/controller/scoc/cron.php` – runs queued jobs from cron

### Libraries

- `src/system/library/scoc_lib.php` – central business logic, version metadata, job storage and XML responses
- `src/system/library/scoc_encoder.php` – validates the `u` and `p` login parameters and decrypts the encrypted password when configured
- `src/system/library/scoc_importer.php` – imports XML content into the OpenCart store
- `src/system/library/scoc_utility.php` – sanitisation helpers for XML-safe strings
- `src/system/library/scoc_TripleDES.php` – encryption/decryption helper used by the auth flow
- `src/system/library/scoc_xmllog.php` – XML log helper for job output

## Authentication model

The actual auth flow is legacy and query-string based. The encoder checks the request query string, validates `u` and `p`, and then compares the supplied admin credentials against the OpenCart user table.

Important points from the code:

- the plugin expects a configured secret key and refuses authentication when it is missing
- the secret is resolved from OpenCart config or the environment variable `SCOC_SECRET_KEY`
- the code does not trust the browser `Origin` header as a secret source
- unauthenticated or invalid requests return XML failure responses

## Export and import routes

The plugin exposes route-based endpoints in the style used by OpenCart controllers.

### Install

- `index.php?route=scoc/install/index`

### Auth

- `index.php?route=scoc/export/auth&u=admin&p=CHANGE_ME`

### Product export

- `index.php?route=scoc/export/product`
- `index.php?route=scoc/export/productcsv`
- `index.php?route=scoc/export/csv`

### Order export

- `index.php?route=scoc/export/order`

### Category export

- `index.php?route=scoc/export/category`

### Shipping and inventory export

- `index.php?route=scoc/export/shipping`
- `index.php?route=scoc/export/inventorydownload`

### XML job handling

- `index.php?route=scoc/import`
- `index.php?route=scoc/job/getjob&id=1&u=admin&p=CHANGE_ME`
- `index.php?route=scoc/cron`

The example route values in the repository are placeholders only. They are not production credentials and should be replaced with store-specific values when the plugin is deployed.

## Deployment model

1. Copy the `src/` directory into the root of the target OpenCart installation so the `catalog` and `system` trees land in the correct OpenCart locations.
2. Load the install route once to create the job table.
3. Configure the downstream system to call the export/import endpoints using the required `u`, `p`, and job parameters.
4. Use cron or a scheduler to hit the cron route for queued imports.

## Security notes

This extension is intentionally a legacy OpenCart plugin. It does not implement modern authentication protocols such as OAuth, API keys with scoped permissions, or signed JWTs.

The current repo includes safeguards that are aligned with the codebase:

- secrets are configured from server-side config/environment, not request metadata
- a missing secret causes a fail-closed auth response
- no real client identifiers or credentials are checked into the public repo

See [SECURITY.md](SECURITY.md) for the project’s security policy and disclosure process.

## Testing

The project includes a small PHPUnit suite focused on runtime-safe checks for the legacy code:

- `tests/ScocUtilityTest.php` validates XML-safe string cleaning
- `tests/ScocEncoderSecurityTest.php` validates the fail-closed auth behavior and secret resolution

The repo also includes a PHP quality workflow under `.github/workflows/php-quality.yml`.

## Project status

The source currently reports plugin version `4.0.2.1` in `scoc_lib.php`, while earlier notes in `version.txt` document the historical version milestones that were shipped in older store installs. This is a maintained legacy extension, not a new application framework.

## Related documents

- [docs/adr/001-legacy-opencart-sync.md](docs/adr/001-legacy-opencart-sync.md)
- [SECURITY.md](SECURITY.md)
- [CHANGELOG.md](CHANGELOG.md)
