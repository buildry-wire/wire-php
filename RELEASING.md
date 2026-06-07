# Releasing wire-php (Packagist: buildry-wire/wire-php)

Packagist publishes automatically from Git tags. There are **no secrets** in the
release workflow — Packagist pulls new versions over its GitHub webhook.

## One-time Packagist setup (browser, maintainer)

1. Sign in at https://packagist.org with the GitHub account that owns the repo.
2. Click **Submit**, paste the repo URL `https://github.com/buildry-wire/wire-php`,
   and submit. Packagist reads `composer.json` for the package name
   (`buildry-wire/wire-php`).
3. Enable auto-updates so new tags publish without manual resubmission:
   - Preferred: install the **Packagist Composer integration** GitHub App
     (https://github.com/apps/packagist) and grant it the repo. This wires the
     webhook automatically.
   - Or, manually: on the package page open **Settings**, copy your Packagist
     **API token**, then in the GitHub repo go to
     **Settings → Webhooks → Add webhook** with
     `https://packagist.org/api/github?username=<your-packagist-username>`,
     content type `application/json`, secret = your Packagist API token,
     and the "Just the push event" trigger (covers tags).

Once connected, every pushed tag updates the package on Packagist within seconds.

## Cut a release

1. Move the changelog items under `## [x.y.z] - YYYY-MM-DD` in `CHANGELOG.md`.
   (No version field in `composer.json` — Packagist derives the version from the
   Git tag.)
2. Commit on `main`, then tag and push:
   ```bash
   git tag vX.Y.Z
   git push origin vX.Y.Z
   ```
3. The `release.yml` workflow runs the test suite and creates a GitHub Release.
   Packagist picks up the new tag over its webhook and publishes it.

## Verify

```bash
composer require buildry-wire/wire-php:^X.Y.Z
```
