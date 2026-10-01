# Project specification

## Problem

The store needs a connector that can push catalog, order and inventory information between an OpenCart store and an external marketplace or fulfilment platform. The connector must expose XML endpoints that can be called by the remote system, and it must accept XML job payloads that update products and order states without requiring a custom storefront.

## Scope

- product export
- category export
- order export
- shipping and inventory export
- XML import jobs for products and order updates
- cron-driven job execution
- job logging and retrieval

## Out of scope

- creating a standalone e-commerce application
- introducing a modern API framework or database layer
- migrating the plugin to a new platform architecture
- treating the extension as a SaaS or multi-tenant product

## Main components

- OpenCart `catalog/controller/scoc/*` endpoints
- `scoc_lib` as the central helper layer
- `scoc_encoder` for query-string authentication
- `scoc_importer` for XML-driven updates
- OpenCart data models for products, categories, orders and returns

## Expected behaviour

The extension should respond to authenticated XML requests and return well-formed XML payloads containing product, category, inventory or order data. Where jobs are posted, the plugin should save the payload, queue it for processing, and record status updates and logs.

## Risks and constraints

- This plugin lives inside the OpenCart file structure and depends on the host store runtime.
- Legacy authentication and XML patterns require careful review before shipping to a public environment.
- The repo should document what is real in the code and avoid claiming more than the plugin actually does.
