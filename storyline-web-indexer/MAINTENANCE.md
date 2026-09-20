# Storyline Web Indexer — Maintenance Workflow

The machine-readable companion layer should be refreshed whenever the underlying Storyline publication changes materially.

## Recommended publishing cycle

```text
Edit Storyline
      ↓
Publish for Web
      ↓
Deploy / replace Storyline files
      ↓
Run indexer.php
      ↓
Review transcript.html + references.html
      ↓
Review sitemap.xml + video metadata
      ↓
Verify public URLs
      ↓
Check Google Search Console
```

## After every material Storyline republish

1. **Deploy the updated Storyline publication.** Confirm that the human-facing course still loads and behaves correctly before troubleshooting the indexing layer.
2. **Run `indexer.php` again.** The script regenerates `references.html`, `transcript.html`, and `sitemap.xml` from the current publication.
3. **Read the status report.** Look for unexpected changes in extracted text, external references, internal links, sibling-site exclusions, detected videos, or featured-video count.
4. **Review the generated HTML.** Extraction is heuristic. Storyline publishing changes, unusual text, or new assets can produce material that should be filtered or corrected before public use.
5. **Review `sitemap.xml`.** Confirm the canonical domain, microsite path, internal URLs, and any video entries.
6. **Check the site as a visitor.** Open the Storyline site, generated resources, sitemap, featured video, and thumbnail from their public URLs.
7. **Use Search Console when appropriate.** A submitted sitemap can remain registered with Google and will be recrawled. For significant changes, inspect the important public URL and use a live test or request indexing where appropriate.

## When the Storyline folder moves

The script derives the microsite path from the directory in which it runs. If you move a publication from, for example, `/prototype/` to `/portfolio/`, move/configure the indexer with it and rerun it in the new location.

Then verify that the new `sitemap.xml` contains the new canonical URLs. Handle redirects from the old public URLs separately at the web-server or hosting layer if they are needed.

## When the domain changes

Update:

```php
$canonicalScheme = 'https';
$canonicalHost = 'example.com';
```

Rerun the indexer and inspect every generated URL. A domain migration also requires normal website-migration and Search Console work beyond the scope of this utility.

## When a featured video changes

If you replace or rename a featured video:

- confirm the video extension remains supported (`.mp4`, `.webm`, or `.m4v`);
- place a `.jpg` with the exact same basename beside the video if you want a video sitemap entry;
- update `$videoMetadataOverrides` if the filename, title, or description changed; and
- rerun the indexer and inspect the resulting video entry in `sitemap.xml`.

Example:

```text
reflection-2026.mp4
reflection-2026.jpg
```

## When references change

Rerun the indexer whenever Storyline content adds, removes, or changes links. Review `references.html` rather than assuming every extracted URL should be published as a useful reference.

## When Storyline itself changes

Articulate can change Storyline's published file structure over time. This utility currently expects course data under:

```text
html5/data/js
```

If a future Storyline release changes its publication structure, extraction may return fewer or no results even though the human-facing course still works. Treat that as a compatibility issue and review the script before assuming the course contains no text or links.

## Version-control practice

Keep the public/generic source code in version control, but do not commit:

- passwords or hosting credentials;
- API keys or access tokens;
- private server paths or configuration secrets;
- proprietary Storyline source material; or
- generated content containing information that should not be public.

For meaningful code changes, consider testing on a non-production copy of the Storyline site before replacing the production version.

## Periodic check

Even without a Storyline republish, periodically confirm that:

- the main Storyline URL still loads;
- the sitemap remains publicly accessible;
- external references have not become inappropriate or broken;
- featured media still resolves; and
- Search Console is not reporting new crawl or indexing problems for important public URLs.

The guiding principle is simple: **the crawler layer should evolve with the learner layer.**
