# Releasing

1. Update `CHANGELOG.md` and verify the compatibility matrix.
2. Run `composer validate --strict`, `vendor/bin/pint --test`, and `composer test`.
3. Create and push a signed `vX.Y.Z` tag.
4. The release workflow rebuilds the package, creates SHA-256 checksums, attests the archive provenance, and publishes a GitHub release.

The tag is the release source of truth. Maintainers should protect release tags and require reviewed changes on the default branch.
