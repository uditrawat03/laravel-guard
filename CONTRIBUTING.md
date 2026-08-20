# Contributing

Run the quality checks before submitting a change:

```bash
composer install
vendor/bin/pint --test
composer test
composer validate --strict
```

Rules must use a stable identifier, explain impact and remediation, include a calibrated confidence, avoid executing application source, and have positive and negative fixtures. Secret-related fixtures must be synthetic and non-functional.

Changes to finding fingerprints, rule IDs, suppression behavior, or public contracts require explicit release notes because they affect baselines and CI consumers.
