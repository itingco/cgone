# Verification Notes

Date: 2026-08-17

## Completed in build environment

- `composer.json` parsed successfully and targets PHP `^8.1`.
- All PHP source files under `app`, `bootstrap`, `config`, `database`, `routes`, `tests`, and `public` passed `php -l` using the available PHP 8.4 runtime.
- Static scan found no use of known PHP >8.1-only constructs checked by `scripts/verify-source.sh` (`json_validate`, readonly class syntax, enum declarations).
- Static Blade route-name references were checked against declared route names; no missing literal route references were found.
- `git diff --check` reports no whitespace errors.

## Not executable in this build environment

The container used to assemble this artifact does not have Composer installed, and terminal DNS access to `getcomposer.org` is unavailable. Therefore `vendor/` could not be installed and these runtime commands could not be executed here:

```bash
php artisan test
php artisan route:list
php artisan migrate --seed
```

Run those commands on the target/development server after `composer install`.

## SQL Server verification required after deployment

After migration, execute:

```text
database/sql/sqlserver_ledger_protection.sql
```

Then verify trigger installation with:

```text
database/sql/verify_sqlserver_setup.sql
```

The SQL Server integration check is required because SQLite-based application tests cannot validate SQL Server trigger behavior.
