# v14 release validation

Checks performed while building this archive:

- all PHP files passed `php -l`;
- clean `sql/install.sql` parsed into 27 executable statements;
- every root and module update SQL file parsed successfully;
- no module update SQL file is metadata-only/empty;
- upgrade chain validation passed with 21 root/module update versions and no duplicate versions;
- all routes declared in system module manifests point to existing controller files and methods;
- all multi-row `INSERT ... VALUES` statements in clean install SQL have matching column/value counts;
- trusted-proxy, menu cycle/tree, header validation and request-path smoke checks passed;
- no default administrator is created by clean SQL; the first administrator is created by the web installer.

A real MySQL/MariaDB integration run is environment-specific and remains mandatory before deployment. Use a disposable empty database and follow `tests/README.md`.
