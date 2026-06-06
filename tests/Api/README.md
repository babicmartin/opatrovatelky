# API Tests

HTTP endpoint and AJAX/JSON contract tests for presenter signals that behave like an API.

Scope:

- Presenter signals returning JSON via `sendJson()`.
- Autosave-style request/response contracts (the shared `handleAutosavePartial` signal).
- Internal HTTP workflows that act like an API even though they are not REST routes.

`AutosavePartialContractTest` pins the presenter-level contract: the framework same-origin
CSRF guard on signals, the ajax/POST guard, the allowed-context allow-list, entity and
document ownership checks, and the success/error JSON envelope. The underlying field-update
logic is covered separately by `tests/Integration/Service/AutosaveFieldUpdateServiceTest`.

These tests run under the default PHPUnit configuration (suite `Api`).
