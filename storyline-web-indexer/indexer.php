<?php
// Custom eLearning Indexing Engine for Articulate Storyline
// Public Edition - Canonical Host + Microsite Boundary + Clean Sitemap
//
// Key changes from V7:
// 1. Forces canonical URLs for the configured host regardless of how this script is opened.
// 2. Keeps each sitemap inside the current microsite directory.
// 3. Generates transcript.html and references.html, but keeps them out of the sitemap by default.
// 4. Adds noindex,follow to support pages by default.
// 5. Filters Storyline asset/support URLs out of sitemap candidates.
// 6. Normalizes www links to the configured canonical host.
// 7. Keeps video sitemap support with exact same-basename JPG thumbnails.

header("Content-Type: text/html; charset=UTF-8");

// -----------------------------------------------------------------------------
// Configuration
// -----------------------------------------------------------------------------

$canonicalScheme = 'https';
$canonicalHost = 'example.com'; // CHANGE THIS to your public domain

// Support pages are still generated, but normally should not be search landing pages.
$includeReferencesInSitemap = false;
$includeTranscriptInSitemap = false;
$indexReferencesPage = false;
$indexTranscriptPage = false;

// Keep useful same-microsite HTML/PDF links found inside Storyline data.
$includeDiscoveredInternalPagesInSitemap = true;

// Optional override. Leave null to derive a readable title from the folder name.
$courseTitleOverride = null;

// Optional video metadata overrides by exact filename.
// Add more entries here when you want a polished title/description for a featured video.
$videoMetadataOverrides = [
    // 'your-video.mp4' => [
    //     'title' => 'Your Video Title',
    //     'description' => 'A concise description of the video.'
    // ]
];

$courseFolder = __DIR__;
$dataJsFolder = $courseFolder . '/html5/data/js';

$extractedLinks = [];
$extractedText = [];
$videos = [];

// -----------------------------------------------------------------------------
// Helper functions
// -----------------------------------------------------------------------------

function xmlEscape($value) {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function pathToUrl($absolutePath, $courseFolder, $baseUrl) {
    $relative = str_replace('\\', '/', substr($absolutePath, strlen($courseFolder)));
    $relative = ltrim($relative, '/');

    $encodedParts = array_map('rawurlencode', explode('/', $relative));
    return rtrim($baseUrl, '/') . '/' . implode('/', $encodedParts);
}

function findVideoThumbnail($videoPath, $courseFolder, $baseUrl) {
    $dir = dirname($videoPath);
    $base = pathinfo($videoPath, PATHINFO_FILENAME);

    // FEATURE RULE:
    // Only an exact same-basename .jpg beside the video qualifies.
    // Example:
    //   video_ABC123.mp4
    //   video_ABC123.jpg
    $candidate = $dir . '/' . $base . '.jpg';

    if (is_file($candidate)) {
        return pathToUrl($candidate, $courseFolder, $baseUrl);
    }

    return null;
}

function humanizeFilename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/[_\-]+/', ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

function humanizeFolderName($folderName) {
    $name = preg_replace('/[_\-]+/', ' ', $folderName);
    $name = preg_replace('/\s+/', ' ', $name);
    return ucwords(trim($name));
}

/**
 * Normalize canonical-host / www canonical-host links to the configured host and HTTPS.
 * Fragments are removed because they do not belong in a sitemap.
 * Query strings are retained for reference-page display but are stripped later
 * from sitemap candidates.
 */
function normalizeCanonicalUrl($url, $canonicalScheme, $canonicalHost) {
    $parts = parse_url($url);

    if ($parts === false || empty($parts['host'])) {
        return $url;
    }

    $host = strtolower($parts['host']);

    if ($host !== strtolower($canonicalHost) && $host !== 'www.' . strtolower($canonicalHost)) {
        return $url;
    }

    $path = $parts['path'] ?? '/';
    if ($path === '') {
        $path = '/';
    }

    $normalized = $canonicalScheme . '://' . $canonicalHost . $path;

    if (!empty($parts['query'])) {
        $normalized .= '?' . $parts['query'];
    }

    return $normalized;
}

/**
 * Return true only when the URL belongs to this exact microsite directory.
 * Example for /pedagogy:
 *   ALLOW https://qint.com/pedagogy/
 *   ALLOW https://qint.com/pedagogy/example.pdf
 *   REJECT https://qint.com/portfolio/
 */
function isInsideMicrosite($url, $canonicalHost, $coursePath) {
    $parts = parse_url($url);

    if ($parts === false || empty($parts['host'])) {
        return false;
    }

    if (strtolower($parts['host']) !== strtolower($canonicalHost)) {
        return false;
    }

    $path = $parts['path'] ?? '/';

    if ($coursePath === '') {
        return true;
    }

    return $path === $coursePath || strpos($path, $coursePath . '/') === 0;
}

/**
 * Remove query strings/fragments and reject Storyline engine/support assets.
 */
function cleanSitemapCandidate($url, $canonicalScheme, $canonicalHost, $coursePath) {
    $url = normalizeCanonicalUrl($url, $canonicalScheme, $canonicalHost);
    $parts = parse_url($url);

    if ($parts === false || empty($parts['host'])) {
        return null;
    }

    if (!isInsideMicrosite($url, $canonicalHost, $coursePath)) {
        return null;
    }

    $path = $parts['path'] ?? '/';
    $lowerPath = strtolower($path);

    // Do not expose Storyline engine directories or generated support files.
    $blockedPathFragments = [
        '/story_content/',
        '/html5/',
        '/mobile/',
        '/lms/',
        '/lib/'
    ];

    foreach ($blockedPathFragments as $fragment) {
        if (strpos($lowerPath, $fragment) !== false) {
            return null;
        }
    }

    $basename = strtolower(basename($path));

    $blockedBasenames = [
        'transcript.html',
        'references.html',
        'sitemap.xml',
        'analytics-frame.html',
        'indexer_v7.php',
        'indexer_v8.php',
        'story.html',
        'index.html'
    ];

    if (in_array($basename, $blockedBasenames, true)) {
        return null;
    }

    // Only page/document-like targets belong in the standard sitemap.
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $allowedExtensions = ['', 'html', 'htm', 'php', 'pdf'];

    if (!in_array($extension, $allowedExtensions, true)) {
        return null;
    }

    // Sitemaps should contain canonical clean URLs, not tracking/query variants.
    $clean = $canonicalScheme . '://' . $canonicalHost . $path;

    // Normalize a bare microsite path to a trailing slash.
    if ($coursePath !== '' && $path === $coursePath) {
        $clean .= '/';
    }

    return $clean;
}

// -----------------------------------------------------------------------------
// Canonical public base URL
// -----------------------------------------------------------------------------

// The script directory determines which microsite this copy belongs to.
// Example:
//   /pedagogy/indexer_v8.php  ->  https://qint.com/pedagogy/
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDir = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');

$coursePath = $scriptDir;
$baseUrl = $canonicalScheme . '://' . $canonicalHost . $coursePath;

$folderName = basename($courseFolder);
$courseTitle = $courseTitleOverride ?: humanizeFolderName($folderName);

// -----------------------------------------------------------------------------
// 1. Scan Storyline data files for links and meaningful course text
// -----------------------------------------------------------------------------

if (is_dir($dataJsFolder)) {
    $files = scandir($dataJsFolder);

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'js') {
            continue;
        }

        $content = file_get_contents($dataJsFolder . '/' . $file);

        // Extract Links & DOIs
        preg_match_all('/https?:\/\/[^\s"\']+/i', $content, $urlMatches);

        if (!empty($urlMatches[0])) {
            foreach ($urlMatches[0] as $url) {
                $cleanUrl = rtrim($url, ',;)}]');

                if (filter_var($cleanUrl, FILTER_VALIDATE_URL)) {
                    $cleanUrl = normalizeCanonicalUrl(
                        $cleanUrl,
                        $canonicalScheme,
                        $canonicalHost
                    );
                    $extractedLinks[] = $cleanUrl;
                }
            }
        }

        // Extract Course Text & Slide Paragraphs
        preg_match_all('/"([^"]{15,})"/', $content, $textMatches);

        if (!empty($textMatches[1])) {
            foreach ($textMatches[1] as $textChunk) {
                $cleanText = json_decode('"' . $textChunk . '"') ?? $textChunk;
                $cleanText = strip_tags($cleanText);

                $cleanText = str_replace(
                    ["\n", "\r", "\t", "\\n", "\\r", "\\t"],
                    " ",
                    $cleanText
                );

                $cleanText = preg_replace('/\s+/', ' ', $cleanText);
                $cleanText = trim($cleanText);

                if (substr_count($cleanText, ' ') >= 2 && strlen($cleanText) > 20) {
                    $isNoise = false;

                    $bannedPhrases = [
                        'Charset', 'CharsBold', 'ChaItalic', 'CBold',
                        'acc_', 'modern_video', '%count%', '%answer%', 'three_image_',
                        'To watch this video', 'Clear and return',
                        'Enter your name', 'Table with', 'Slide:',
                        'Description automatically generated', 'Picture containing',
                        'Logo, company name', 'txt__default',
                        'Straight Arrow', 'Connector', 'Hotspot', 'Rectangle', 'Oval',
                        'rgba(', 'free exploration mode', '360 degree image',
                        'keyboard shortcuts', 'Toggle accessible', 'Toggle background',
                        'Next (Ctrl', 'Previous (Ctrl', 'Hide captions', 'Show captions',
                        'Enter full-screen', 'Exit full-screen', 'time limit set',
                        'before submitting', 'local playback', 'Flash cannot access',
                        'Please rotate your device'
                    ];

                    foreach ($bannedPhrases as $ban) {
                        if (stripos($cleanText, $ban) !== false) {
                            $isNoise = true;
                            break;
                        }
                    }

                    if (!$isNoise) {
                        if (
                            preg_match('/\.(png|jpg|jpeg|gif|mp4|webm|svg|js|css|xml)/i', $cleanText) ||
                            preg_match('/Shape[a-zA-Z0-9_-]+/i', $cleanText) ||
                            preg_match('/^M\s*-?\d+\.?\d*,\s*-?\d+\.?\d*/', $cleanText) ||
                            preg_match('/^[0-9\s\.\-]+$/', $cleanText)
                        ) {
                            $isNoise = true;
                        }
                    }

                    if (!$isNoise) {
                        $words = explode(' ', $cleanText);

                        foreach ($words as $word) {
                            if (strlen($word) > 25 && strpos($word, 'http') === false) {
                                $isNoise = true;
                                break;
                            }
                        }
                    }

                    if (!$isNoise) {
                        $extractedText[] = $cleanText;
                    }
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Discover local video files recursively
// -----------------------------------------------------------------------------

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $courseFolder,
        FilesystemIterator::SKIP_DOTS
    )
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $ext = strtolower($fileInfo->getExtension());

    if (!in_array($ext, ['mp4', 'webm', 'm4v'], true)) {
        continue;
    }

    $absolutePath = $fileInfo->getPathname();
    $videoUrl = pathToUrl($absolutePath, $courseFolder, $baseUrl);
    $thumbnailUrl = findVideoThumbnail($absolutePath, $courseFolder, $baseUrl);
    $filename = $fileInfo->getFilename();

    $defaultTitle = humanizeFilename($filename);
    $defaultDescription = 'Video content from ' . ($defaultTitle !== '' ? $defaultTitle : $courseTitle);

    if (isset($videoMetadataOverrides[$filename])) {
        $videoTitle = $videoMetadataOverrides[$filename]['title'] ?? $defaultTitle;
        $videoDescription = $videoMetadataOverrides[$filename]['description'] ?? $defaultDescription;
    } else {
        $videoTitle = $defaultTitle;
        $videoDescription = $defaultDescription;
    }

    $videos[] = [
        'path' => $absolutePath,
        'url' => $videoUrl,
        'thumbnail' => $thumbnailUrl,
        'title' => $videoTitle,
        'description' => $videoDescription
    ];
}

// -----------------------------------------------------------------------------
// 3. Remove duplicates
// -----------------------------------------------------------------------------

$extractedLinks = array_values(array_unique($extractedLinks));
$extractedText = array_values(array_unique($extractedText));

// -----------------------------------------------------------------------------
// 4. Separate same-microsite, sibling-qint, and external links
// -----------------------------------------------------------------------------

$internalLinks = [];
$siblingSiteLinks = [];
$externalLinks = [];

foreach ($extractedLinks as $link) {
    $parts = parse_url($link);
    $host = strtolower($parts['host'] ?? '');

    if ($host === strtolower($canonicalHost)) {
        if (isInsideMicrosite($link, $canonicalHost, $coursePath)) {
            $internalLinks[] = $link;
        } else {
            // Example: /portfolio/ link discovered while indexing /pedagogy/.
            // Keep it out of this microsite's sitemap.
            $siblingSiteLinks[] = $link;
        }
    } else {
        $externalLinks[] = $link;
    }
}

$internalLinks = array_values(array_unique($internalLinks));
$siblingSiteLinks = array_values(array_unique($siblingSiteLinks));
$externalLinks = array_values(array_unique($externalLinks));

// -----------------------------------------------------------------------------
// 5. Generate HTML References page
// -----------------------------------------------------------------------------

$referencesRobots = $indexReferencesPage ? 'index,follow' : 'noindex,follow';

$htmlRefs = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
$htmlRefs .= '<meta name="robots" content="' . $referencesRobots . '">';
$htmlRefs .= '<title>' . htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8') .
             ' References</title></head><body>' . PHP_EOL;
$htmlRefs .= '<h1>Course References and DOIs</h1><ul>' . PHP_EOL;

foreach ($externalLinks as $extLink) {
    $htmlRefs .= '<li><a href="' . htmlspecialchars($extLink, ENT_QUOTES, 'UTF-8') .
        '" target="_blank" rel="noopener noreferrer">' .
        htmlspecialchars($extLink, ENT_QUOTES, 'UTF-8') .
        '</a></li>' . PHP_EOL;
}

$htmlRefs .= '</ul></body></html>';
file_put_contents($courseFolder . '/references.html', $htmlRefs);

// -----------------------------------------------------------------------------
// 6. Generate HTML Transcript page
// -----------------------------------------------------------------------------

$transcriptRobots = $indexTranscriptPage ? 'index,follow' : 'noindex,follow';

$htmlTranscript = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
$htmlTranscript .= '<meta name="robots" content="' . $transcriptRobots . '">';
$htmlTranscript .= '<title>' . htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8') .
                   ' Transcript</title></head><body>' . PHP_EOL;
$htmlTranscript .= '<h1>' . htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8') .
                   ' Transcript</h1>' . PHP_EOL;

foreach ($extractedText as $paragraph) {
    $htmlTranscript .= '<p>' .
        htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8') .
        '</p>' . PHP_EOL;
}

$htmlTranscript .= '</body></html>';
file_put_contents($courseFolder . '/transcript.html', $htmlTranscript);

// -----------------------------------------------------------------------------
// 7. Generate standard + video-aware sitemap
// -----------------------------------------------------------------------------

$sitemapXml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$sitemapXml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
$sitemapXml .= '        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . PHP_EOL;

// Main course page: always canonical, always trailing slash.
$mainCourseUrl = rtrim($baseUrl, '/') . '/';

$sitemapXml .= '  <url>' . PHP_EOL;
$sitemapXml .= '    <loc>' . xmlEscape($mainCourseUrl) . '</loc>' . PHP_EOL;

// Only videos with an exact same-basename .jpg are added to the sitemap.
foreach ($videos as $video) {
    if (empty($video['thumbnail'])) {
        continue;
    }

    $title = $video['title'] !== '' ? $video['title'] : $courseTitle . ' Video';
    $description = $video['description'] !== ''
        ? $video['description']
        : 'Video content from ' . $title;

    $sitemapXml .= '    <video:video>' . PHP_EOL;
    $sitemapXml .= '      <video:thumbnail_loc>' .
        xmlEscape($video['thumbnail']) .
        '</video:thumbnail_loc>' . PHP_EOL;
    $sitemapXml .= '      <video:title>' .
        xmlEscape($title) .
        '</video:title>' . PHP_EOL;
    $sitemapXml .= '      <video:description>' .
        xmlEscape($description) .
        '</video:description>' . PHP_EOL;
    $sitemapXml .= '      <video:content_loc>' .
        xmlEscape($video['url']) .
        '</video:content_loc>' . PHP_EOL;
    $sitemapXml .= '    </video:video>' . PHP_EOL;
}

$sitemapXml .= '  </url>' . PHP_EOL;

// Optional support pages. Disabled by default.
if ($includeReferencesInSitemap) {
    $sitemapXml .= '  <url><loc>' .
        xmlEscape(rtrim($baseUrl, '/') . '/references.html') .
        '</loc></url>' . PHP_EOL;
}

if ($includeTranscriptInSitemap) {
    $sitemapXml .= '  <url><loc>' .
        xmlEscape(rtrim($baseUrl, '/') . '/transcript.html') .
        '</loc></url>' . PHP_EOL;
}

// Same-microsite links discovered in Storyline data.
if ($includeDiscoveredInternalPagesInSitemap) {
    $sitemapCandidates = [];

    foreach ($internalLinks as $intLink) {
        $candidate = cleanSitemapCandidate(
            $intLink,
            $canonicalScheme,
            $canonicalHost,
            $coursePath
        );

        if ($candidate !== null && $candidate !== $mainCourseUrl) {
            $sitemapCandidates[] = $candidate;
        }
    }

    $sitemapCandidates = array_values(array_unique($sitemapCandidates));
    sort($sitemapCandidates);

    foreach ($sitemapCandidates as $candidate) {
        $sitemapXml .= '  <url><loc>' .
            xmlEscape($candidate) .
            '</loc></url>' . PHP_EOL;
    }
}

$sitemapXml .= '</urlset>';
file_put_contents($courseFolder . '/sitemap.xml', $sitemapXml);

// -----------------------------------------------------------------------------
// 8. Status report
// -----------------------------------------------------------------------------

$videoEntriesWritten = 0;

foreach ($videos as $video) {
    if (!empty($video['thumbnail'])) {
        $videoEntriesWritten++;
    }
}

echo "<h1>Indexing Engine V8 Complete!</h1>";
echo "<p>Canonical site base URL: <strong>" .
     htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') .
     "</strong></p>";
echo "<p>Found " . count($externalLinks) . " external citation(s) mapped to references.html.</p>";
echo "<p>Found " . count($siblingSiteLinks) .
     " sibling same-domain link(s) excluded from this microsite sitemap.</p>";
echo "<p>Found " . count($internalLinks) .
     " same-microsite link(s) eligible for filtering.</p>";
echo "<p>Found " . count($extractedText) .
     " clean text block(s) extracted to transcript.html.</p>";
echo "<p>Found <strong>" . count($videos) . "</strong> local video file(s).</p>";
echo "<p>Featured <strong>" . $videoEntriesWritten .
     "</strong> video(s) with matching JPG thumbnails in sitemap.xml.</p>";

echo "<p>references.html indexing: <strong>" .
     ($indexReferencesPage ? "index" : "noindex") .
     "</strong>; sitemap inclusion: <strong>" .
     ($includeReferencesInSitemap ? "yes" : "no") .
     "</strong>.</p>";

echo "<p>transcript.html indexing: <strong>" .
     ($indexTranscriptPage ? "index" : "noindex") .
     "</strong>; sitemap inclusion: <strong>" .
     ($includeTranscriptInSitemap ? "yes" : "no") .
     "</strong>.</p>";

if (!empty($videos)) {
    echo "<h2>Detected Videos</h2><ul>";

    foreach ($videos as $video) {
        echo "<li><a href=\"" .
             htmlspecialchars($video['url'], ENT_QUOTES, 'UTF-8') .
             "\" target=\"_blank\">" .
             htmlspecialchars($video['url'], ENT_QUOTES, 'UTF-8') .
             "</a> — " .
             (!empty($video['thumbnail'])
                 ? "<strong>FEATURED</strong> — matching JPG found"
                 : "not featured") .
             "</li>";
    }

    echo "</ul>";
}

if (!empty($siblingSiteLinks)) {
    echo "<h2>Sibling Same-Domain Links Excluded from Sitemap</h2><ul>";

    foreach ($siblingSiteLinks as $link) {
        echo "<li>" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "</li>";
    }

    echo "</ul>";
}

if ($videoEntriesWritten === 0 && count($videos) > 0) {
    echo "<p><strong>Note:</strong> No video was added to the video sitemap. " .
         "To feature one, place a <code>.jpg</code> with the exact same basename beside its video file.</p>";
}

if ($videoEntriesWritten > 1) {
    echo "<p><strong>Note:</strong> More than one matching JPG was found. " .
         "If you want only one featured video, leave a matching JPG beside only that one video.</p>";
}

echo "<p><strong>Clean sitemap.xml generated and updated.</strong></p>";
?>
