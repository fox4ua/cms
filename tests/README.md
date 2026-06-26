# Automated and release tests

The archive includes PHPUnit-compatible unit tests for SQL parsing, trusted proxy handling, menu validation/tree building, setting validation, SQL safety and upgrade-chain validation.

Run them inside a complete CodeIgniter 4 project test bootstrap. Database feature tests must use an isolated empty MySQL/MariaDB schema and a separate `writable` directory.

Mandatory release pipeline:

1. Run all PHP unit tests.
2. Run `php -l` over every PHP file.
3. Perform a clean web installation into an empty database.
4. Confirm a failed installation cleans the empty database and does not expose raw DB errors.
5. Upgrade the previous production build by applying `sql/updates` in version order.
6. Re-run every idempotent root update and confirm no schema/data error.
7. Verify `/health` returns `200` without touching the database.
8. Verify `/ready` returns `503` when DB or schema is unavailable and does not expose details without `X-CMS-Probe-Token`.
9. Verify module failures appear in `module_operation_logs` and stale locks expire or can be force-released.
10. Test proxy/IP resolution through every production reverse proxy hop.
