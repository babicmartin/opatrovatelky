# Performance Tests

Use this folder for benchmark and load checks.

Current candidates:

- Repository list queries and filters.
- Autosave endpoint latency.
- PDF generation.
- Video metadata reads.

Keep these tests separate from the default PHPUnit run and define explicit thresholds before enabling them in CI.
