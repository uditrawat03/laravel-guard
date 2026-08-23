# Releasing

The Git tag is the release source of truth. Release tags and published archives are immutable: never move, replace, or silently delete a tag after publication.

## Preconditions

1. Update `CHANGELOG.md` and the default versions in `.github/workflows/distribution.yml`.
2. Confirm the GitHub-to-Packagist update hook is active.
3. Verify the compatibility workflow is green on the release commit.
4. Run `composer validate --strict`, `vendor/bin/pint --test`, and `composer test` locally.
5. Confirm there are no uncommitted package changes in the release worktree.

## Standard release

1. Create and push a signed `vX.Y.Z` tag from the reviewed release commit.
2. Watch the `Release` workflow. It validates the package, creates the ZIP and SHA-256 checksum, attests build provenance, and publishes the GitHub release.
3. Verify the checksum from a clean directory:

   ```bash
   gh release download vX.Y.Z --repo uditrawat03/laravel-guard
   sha256sum --check laravel-guard.zip.sha256
   ```

4. Confirm Packagist exposes the tag without a custom Composer repository:

   ```bash
   composer show laravel-guard/laravel-guard --all
   ```

5. Run the clean-consumer distribution drill and watch it to completion:

   ```bash
   gh workflow run distribution.yml --repo uditrawat03/laravel-guard \
     -f release_version=X.Y.Z -f rollback_version=X.Y.previous
   gh run watch --repo uditrawat03/laravel-guard
   ```

The drill creates a new Laravel application, installs the release from a Packagist distribution archive, verifies package discovery, downgrades to the previous stable version, and restores the current release. It also runs `composer audit`. The workflow runs weekly so distribution failures are detected between releases.

## Rollback response

Use rollback only when immediate consumer impact is lower than waiting for a patch. Database or configuration changes made by an application remain the application's responsibility.

1. Preserve the affected Git tag, GitHub release, checksum, and provenance evidence.
2. Open an incident or private security advisory and record the affected versions and symptoms.
3. Verify the previous stable release with the distribution drill.
4. In an affected consumer, pin the previous exact version and update with dependencies:

   ```bash
   composer require --dev laravel-guard/laravel-guard:X.Y.previous --with-all-dependencies
   php artisan package:discover
   php artisan guard:doctor
   ```

5. Publish a patched release promptly and roll consumers forward. Do not leave a broad version constraint locked to a known affected version.

## Security hotfix

1. Coordinate privately in a GitHub Security Advisory when disclosure before the fix would increase risk.
2. Branch from the affected supported release line, make the smallest viable fix, and add a regression test.
3. Run the full compatibility matrix and the local release checks.
4. Update `CHANGELOG.md`, security impact, affected versions, upgrade instructions, and credits where appropriate.
5. Publish the next patch tag through the standard release workflow.
6. Run the distribution drill with the hotfix as `release_version` and the affected version as `rollback_version`.
7. Publish the advisory and notify consumers only after the fixed Packagist artifact and GitHub assets are independently verified.

For every release or drill, retain the workflow URL in the release notes, pull request, or pending-feature evidence so the claim can be audited later.
