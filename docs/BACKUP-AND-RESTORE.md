# MEPTDI DTS — Backup and Restoration Procedure

Each backup is one archive `dts-backup-YYYYMMDD-HHMMSS.zip` containing:

| File | Contents |
|---|---|
| `database.sql` | Full MySQL dump of the `meptdi_dts` database |
| `files/attachments/...` | Every uploaded attachment (scanned documents, proofs of receipt) |
| `manifest.json` | Date of backup, row count of each table, number of attachment files |

## 1. Creating backups

- **From the system:** Administration › Backup & Restore › *Create backup now*.
- **From the command line:** `php artisan dts:backup`
- **Automatic:** a daily backup is scheduled at 18:00H. It runs only if the Laravel scheduler runs, e.g. a Windows Task Scheduler job every minute:
  `C:\path\to\php.exe C:\xampp\htdocs\LaravelProjects\dts\artisan schedule:run`

Set `DTS_BACKUP_PATH` in `.env` to the approved external storage (e.g. `E:/MEPTDI-DTS-Backups`). Otherwise, copy each archive there after it is created.

## 2. Restoration testing (at least monthly)

- **From the system:** *Test restore* next to an archive.
- **From the command line:** `php artisan dts:restore-test` (latest archive) or `php artisan dts:restore-test "E:/path/dts-backup-....zip"`

The test restores the archive into a separate scratch database (`meptdi_dts_restore_test`). It compares every table's row count and the attachment file count with the manifest, then deletes the scratch database. **The live database is never touched.** The result is kept in the Backup & Restore log and the audit trail.

## 3. Actual restoration (after data loss)

1. Stop use of the system and notify users.
2. Extract the chosen archive to a temporary folder.
3. Re-create the database and import the dump:
   ```
   C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS meptdi_dts; CREATE DATABASE meptdi_dts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   C:\xampp\mysql\bin\mysql.exe -u root meptdi_dts < database.sql
   ```
4. Copy `files/attachments` to `storage/app/private/attachments` (replacing the existing folder).
5. Run `php artisan optimize:clear`, sign in as Administrator, and spot-check recent records and attachments.
6. Record the restoration (date, archive used, reason, personnel) in the office logbook.
