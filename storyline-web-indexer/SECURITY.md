# Storyline Web Indexer — Security Considerations

The Storyline Web Indexer is intentionally designed to make selected content easier for machines to discover. That makes content review and basic server security part of responsible deployment.

## Never put secrets in the script

Do **not** place any of the following inside `indexer.php` or another file committed to a public repository:

- web-hosting usernames or passwords;
- FTP/SFTP credentials;
- database credentials;
- API keys;
- OAuth tokens or personal access tokens;
- private keys; or
- recovery codes.

The public edition of the indexer does not require these credentials to perform its intended function.

If a future modification needs a secret, use an appropriate server-side secret-management mechanism or environment configuration rather than hard-coding the secret into public source code.

## Protect administrative accounts

Use strong, unique passwords for GitHub, the hosting provider, domain registrar, SFTP/SSH, and other administrative services. Enable **multifactor authentication (MFA)** wherever the service supports it.

MFA protects the administrative account; it is not a substitute for keeping credentials out of source code.

## Keep the server environment maintained

Use a supported PHP version and follow the hosting provider's patching and security guidance. Remove unused plugins, scripts, accounts, and administrative interfaces where practical.

Use only the file and directory permissions required for the site and for PHP to generate the companion files.

## Treat public execution as an administrative function

Running `indexer.php` causes a recursive scan of the Storyline microsite and rewrites generated files. It is intended as a publishing/maintenance utility, not as a page that ordinary learners need to use.

After generation and verification, consider **removing the public copy of `indexer.php` or restricting access to it** if it does not need to remain executable on the production site. Keep a clean source copy in version control for the next publishing cycle.

Do not implement access control by hard-coding a password into the PHP file.

## Review what the indexer exposes

The generated resources are designed to make information easier to inspect or discover. Before deployment, check that they do not expose:

- confidential or proprietary material;
- personal information;
- internal-only URLs;
- unpublished assessment answers;
- private file paths or diagnostic information;
- employer-owned material that you do not have permission to publish; or
- media that was not intended for public distribution.

This is particularly important for portfolios. Build original demonstration content rather than publishing a previous employer's proprietary learning materials without authorization.

## Understand `index` versus `noindex`

In the public edition, `transcript.html` and `references.html` are generated with `noindex,follow` by default and are excluded from the sitemap by default. This is a deliberate conservative setting.

If you change the configuration to make these pages indexable, review their contents first. Turning on indexing is a publishing decision, not merely a technical switch.

## Back up before testing changes

Keep a clean copy of the Storyline web publication before testing a modified indexer. Test substantial changes on a non-production copy when practical.

## Repository hygiene

Before every public commit, review the diff for credentials, private domains, local paths, personal data, or site-specific configuration that should not be shared.

The example source uses `example.com` deliberately. Replace it only in the **deployed copy** if you do not want your production domain embedded in the reusable public template.

## Scope

This document provides practical deployment precautions for this small utility. It is not a complete web-server hardening guide, vulnerability assessment, or substitute for the security requirements of your organization or hosting provider.
