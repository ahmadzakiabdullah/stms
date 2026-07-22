# File Storage

File storage in STMS is managed through Laravel's `Storage` facade and the `config/filesystems.php` configuration. The default disk is `local`, which stores files in `storage/app/`. Publicly accessible files (avatars, logos, tournament banners) use the `public` disk with a symbolic link from `public/storage`.

Currently, all file operations use the **local disk**. The `php artisan storage:link` command must be run after deployment to create the public symlink. File uploads are validated through Form Requests with size and MIME type constraints.

The application is **cloud-ready**. Switching to `s3` (or MinIO for on-premise S3-compatible storage) requires only changing the `FILESYSTEM_DISK` environment variable and updating `config/filesystems.php` with S3 credentials. No application code changes are needed because all file interactions go through the `Storage` facade. File visibility (public vs. private) should be carefully reviewed when migrating to cloud storage to ensure sensitive documents remain access-controlled.
