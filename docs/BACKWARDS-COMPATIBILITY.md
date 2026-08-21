# Backwards compatibility

Laravel Guard follows Semantic Versioning after the 1.0 release.

## Stable API

Public contracts, documented configuration keys, command names and options, finding identifiers, reporter formats, and the tenant model APIs are covered by the backwards-compatibility promise. Deprecations remain available for at least one minor release before removal in the next major release.

## Not covered

Classes under internal implementation namespaces, undocumented container bindings, exact console wording, finding message prose, and experimental features explicitly marked as such may change in a minor release. Finding identifiers and machine-readable fields remain stable even when explanatory prose improves.

Security fixes may tighten detection in a minor or patch release. Such changes can introduce new findings, but do not silently change a finding identifier's meaning.
