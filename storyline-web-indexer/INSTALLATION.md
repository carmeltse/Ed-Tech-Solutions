# Storyline Web Indexer — Installation and Search Console Guide

This guide explains how to configure and run `indexer.php` beside a published Articulate Storyline project, review the generated companion resources, and submit the resulting sitemap to Google Search Console.

> **Before you begin:** Use the indexer only with Storyline content that is appropriate for public discovery. The script is designed to expose selected text, links, and media in conventional web formats.

## 1. Requirements

You need:

- a web server that can run a supported version of PHP;
- permission to place files in the Storyline publication directory;
- permission for PHP to write files in that directory;
- an Articulate Storyline web publication containing the normal `html5/data/js` structure; and
- access to the website's Google Search Console property if you intend to submit the sitemap to Google.

The public edition does **not** require a database, API key, hosting password, or Google credential.

## 2. Back up the Storyline publication

Before adding the utility, keep a clean copy of the original Storyline web publication. The indexer does not intentionally modify Storyline's own files, but a backup makes rollback straightforward.

## 3. Place `indexer.php` in the Storyline microsite root

Put `indexer.php` in the same public directory as the Storyline publication you want to index.

Example:

```text
/public_html/pedagogy/
├── index.html
├── story.html
├── html5/
├── mobile/
├── indexer.php
└── ...
```

The script uses its own directory as the microsite boundary. If it is installed at `/pedagogy/`, the generated sitemap is kept within that microsite rather than collecting unrelated pages elsewhere on the same domain.

## 4. Configure the canonical domain

Open `indexer.php` and change:

```php
$canonicalScheme = 'https';
$canonicalHost = 'example.com';
```

to your public canonical domain, for example:

```php
$canonicalScheme = 'https';
$canonicalHost = 'www.example.org';
```

Use only the hostname in `$canonicalHost` — do not include `https://` or a path.

The script derives the microsite path from the directory in which it is running.

## 5. Review the optional settings

The public edition defaults to generating `references.html` and `transcript.html` while marking them `noindex,follow` and keeping them out of the sitemap. This lets them function as companion resources without automatically making them search landing pages.

```php
$includeReferencesInSitemap = false;
$includeTranscriptInSitemap = false;
$indexReferencesPage = false;
$indexTranscriptPage = false;
```

Change these only if you intentionally want the generated support pages indexed and/or listed in the sitemap.

You can also supply a human-readable course title:

```php
$courseTitleOverride = 'My Storyline Learning Site';
```

If left as `null`, the script derives a title from the folder name.

## 6. Optional: add featured-video metadata

The indexer recursively detects `.mp4`, `.webm`, and `.m4v` files. A video is included as a featured video entry in the sitemap when a `.jpg` with the **same basename** is placed beside it.

```text
video_ABC123.mp4
video_ABC123.jpg
```

You can provide a cleaner title and description using the exact video filename:

```php
$videoMetadataOverrides = [
    'video_ABC123.mp4' => [
        'title' => 'Teaching Philosophy Reflection',
        'description' => 'A short reflection on the evolution of a teaching philosophy.'
    ]
];
```

Review all video titles, descriptions, and thumbnails before publication.

## 7. Run the indexer

After uploading the configured script, open it once in a browser using its public URL, for example:

```text
https://www.example.org/pedagogy/indexer.php
```

The status page reports what it found and whether it generated the companion files.

The script creates or refreshes:

```text
references.html
transcript.html
sitemap.xml
```

It also reports detected local videos and any same-domain links that were deliberately excluded because they fall outside the current microsite.

## 8. Review the generated resources before submitting anything

Open each generated file directly and check it.

### `transcript.html`

Confirm that the extracted text is meaningful and that no confidential, proprietary, personal, test-answer, or unintended material has been exposed.

### `references.html`

Check that external links are relevant and that no internal administrative or restricted URLs have been surfaced.

### `sitemap.xml`

Open the sitemap in a browser and confirm that:

- the main Storyline URL is correct;
- URLs use the intended canonical hostname and HTTPS;
- unrelated sibling microsites are absent;
- any listed internal pages belong to the current microsite; and
- featured-video URLs and thumbnails are correct.

If anything is wrong, correct the configuration or Storyline publication and rerun the indexer before proceeding.

## 9. Submit `sitemap.xml` in Google Search Console

Google's current Search Console guidance says that a sitemap must already be posted on your site and accessible to Googlebot. In Search Console, open the correct property, choose **Sitemaps**, enter the sitemap URL in **Add a new sitemap**, and select **Submit**.

For example:

```text
https://www.example.org/pedagogy/sitemap.xml
```

A successful sitemap submission tells Google where the sitemap is located; it does not upload the sitemap to Google and does not guarantee that every listed URL will be crawled or indexed.

Official Google documentation:

- [Sitemaps report](https://support.google.com/webmasters/answer/7451001)
- [Basic Search Console usage](https://support.google.com/webmasters/answer/6258314)

## 10. Inspect important URLs

For an important page, paste its complete URL into the Search Console inspection bar.

Google's **URL Inspection** tool can show what Google knows about the URL and can run a live test. If the page is accessible and indexable, you can use **Request indexing** for an individual URL.

Useful URLs to inspect include the main Storyline microsite and any conventional public pages that you intentionally configured for indexing.

If `transcript.html` or `references.html` retain the default `noindex,follow` setting, **do not request indexing for those pages**. The `noindex` directive intentionally tells search engines not to index them.

For many new or updated URLs, Google recommends using a sitemap rather than submitting large numbers of individual indexing requests.

Official Google documentation:

- [URL Inspection tool](https://support.google.com/webmasters/answer/9012289)
- [Inspect and troubleshoot a single page](https://support.google.com/webmasters/answer/12482179)

## 11. Do not treat submission as a ranking guarantee

A successful live test, sitemap submission, or indexing request does not guarantee inclusion or ranking in Google Search. Search Console is useful for discovery, crawl diagnostics, and indexing status; it does not override Google's indexing and ranking systems.

## 12. Secure the utility after generation

If you do not need `indexer.php` to remain publicly executable, remove it from the production site or restrict access to it after generating and verifying the companion files. Keep your working copy in a secure development location or version-control repository.

Never place hosting passwords, API keys, database credentials, access tokens, or other secrets inside `indexer.php`.

See [`SECURITY.md`](SECURITY.md) for the security checklist and [`MAINTENANCE.md`](MAINTENANCE.md) for the republishing workflow.
