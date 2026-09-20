# Extending Articulate Storyline Beyond the LMS: Making Learning Content Discoverable on the Open Web

Articulate Storyline is commonly used to create interactive learning experiences delivered through a Learning Management System (LMS). But the usefulness of Storyline does not necessarily have to end at the LMS boundary.

A Storyline-published website can provide an LMS-like learning experience on the open web. It can support sample syllabi, demonstrations, teaching resources, professional development materials, portfolios, and free community learning.

Used this way, Storyline can help extend lifelong learning beyond the digital or virtual classroom.

But moving Storyline onto the open web introduces a challenge that is much less important inside an LMS:

**Can Google and other web crawlers actually discover the learning content?**

## Storyline for learning beyond the LMS

There are good reasons why learning professionals might want to publish selected Storyline experiences directly to the web.

A course or microcredential may end, but learning does not. Public learning resources can remain available for learners to revisit later and can potentially be discovered by people who were never enrolled in the original course.

This creates opportunities for sample curricula, open educational resources, community education, demonstrations, and other forms of lifelong learning.

Storyline can also offer aspiring instructional designers another important opportunity.

Much of an instructional designer's best work may belong to a current or previous employer. Publishing that material in a personal portfolio can create intellectual-property, confidentiality, privacy, or contractual concerns.

Instead, an instructional designer can create original sample learning experiences that demonstrate instructional design, interaction design, multimedia, navigation, accessibility, and development capabilities without reproducing an employer's proprietary learning materials.

A Storyline-based website can therefore allow prospective employers to experience what the instructional designer can actually build.

## The problem: humans and crawlers don't necessarily see the same website

A human visitor can enter a Storyline-published website and navigate through slides, text, videos, references, interactions, and other learning resources.

A conventional web crawler may encounter something quite different.

Storyline relies heavily on dynamically rendered content within its player. Consequently, material that is readily visible to the learner may not be presented to a crawler in the same way as the content of a conventional HTML webpage.

This distinction became important while I was experimenting with Storyline as the foundation for public-facing learning and portfolio websites.

Suggestions such as simplifying the player, removing navigation controls, changing the homepage, or presenting projects as thumbnails can improve the experience for a human visitor.

They do not, by themselves, address the separate technical question:

> **How do we make the underlying Storyline content more accessible to web crawlers without sacrificing the interactive experience designed for the learner?**

## Designing for two audiences

I approached the problem by treating the Storyline website as having two complementary audiences.

```text
STORYLINE WEBSITE
│
┌────────────┴────────────┐
│                         │
▼                         ▼
HUMAN VISITOR               WEB CRAWLER
│                         │
▼                         ▼
Storyline Experience          sitemap.xml
Navigation                    transcript.html
Interaction                   references.html
Video                         video metadata
Learning Resources                 │
▼
Search / Discovery
```

For the human visitor, the Storyline experience remains intact.

For the web crawler, a lightweight server-side indexing utility creates conventional machine-readable resources representing useful content contained within the Storyline publication.

The design principle is simple:

> **Keep Storyline for the learner. Provide a machine-readable companion layer for the crawler.**

## The Storyline Web Indexer

I developed a lightweight PHP utility—the Storyline Web Indexer—to experiment with this approach.

The indexer examines a published Storyline project and produces supporting web resources that can be accessed without requiring a crawler to navigate the Storyline player.

Depending on its configuration, it can:

- extract usable Storyline text into a conventional HTML transcript;
- identify external links and scholarly references;
- create a references page;
- distinguish links belonging to the current Storyline microsite from external or sibling resources;
- discover locally hosted video files;
- associate selected videos with thumbnail and descriptive metadata; and
- generate a standard and video-aware XML sitemap.

In one of my Storyline implementations, the indexing process identified 72 usable text blocks, 10 external citations, and seven locally hosted videos.

The original interactive learning experience remained intact.

## The generated companion resources

The indexer creates resources such as:

**`transcript.html`**
A conventional HTML representation of extracted textual content.

**`references.html`**
A conventional page containing external references and links identified within the Storyline publication.

**`sitemap.xml`**
An XML sitemap identifying the principal Storyline URL and selected supporting resources, with optional video information where appropriate.

These resources do not replace Storyline.

They provide another representation of selected information for systems that do not experience the learning environment in the same way as a human learner.

## Connecting the resources to search engines

Generating a sitemap is only part of the process.

After publishing or updating a Storyline site, the generated resources should be reviewed and the sitemap made available to search engines.

For Google, Google Search Console can be used to submit the XML sitemap and inspect individual URLs. URL Inspection can also be used to test Google's access to a page and, where appropriate, request indexing.

Sitemap submission and individual URL indexing are related but distinct processes, and neither guarantees that Google will ultimately index or rank a particular resource.

The purpose of the Storyline Web Indexer is therefore not to promise search-engine placement.

It is to give crawlers useful conventional web resources that would otherwise be difficult to obtain directly from the interactive Storyline experience.

Detailed deployment, maintenance, and Search Console procedures are documented separately in this repository.

## Maintaining the crawler layer

The generated resources should be treated as part of the publishing lifecycle.

When the underlying Storyline project changes, the workflow is essentially:

**Publish → Run the indexer → Review the generated resources → Verify the sitemap → Deploy → Check Search Console**

This matters because the transcript, references, sitemap, and video metadata should continue to represent the current Storyline publication.

The crawler layer should evolve with the learner layer.

## Cybersecurity and responsible deployment

Because the indexer is a server-side PHP utility, normal web-security practices still apply.

**Web-server usernames, passwords, API keys, database credentials, access tokens, or other secrets should never be embedded in the PHP script or committed to a public GitHub repository.**

Administrative services such as web-hosting control panels and GitHub should use strong authentication controls, including multifactor authentication (MFA) where available. PHP and the hosting environment should also be maintained and patched according to the hosting provider's recommendations.

There is another important consideration specific to an indexing tool.

The purpose of this utility is to make information more discoverable.

Before running it against a public Storyline publication, review the learning material and confirm that its text, links, references, and media are appropriate for public discovery. Confidential, proprietary, personal, or otherwise restricted information should not be exposed simply because an indexing utility can extract it.

More detailed guidance is provided in the project's security documentation.

## Developed through human–AI pair programming

**OpenAI's ChatGPT was used as a coding assistant** during development of the PHP implementation, including code generation, debugging, refinement, and documentation. The solution was iteratively tested and validated by the author in the live Storyline and web-hosting environment.

The development process also provided an opportunity to apply an approach I have been developing around AI-assisted programming.

**Extreme Programming (XP)** is an Agile software-development methodology used by some development teams. It emphasizes short development cycles, frequent feedback and testing, continuous improvement, and practices such as pair programming, in which programmers collaborate closely on the same development problem.

This project reflects my proposed and practised **3-XP (Triad eXtreme Programming)** approach, extending the pairing concept by incorporating an AI agent as a paired programmer.

In this implementation, ChatGPT assisted with iterative coding, troubleshooting, and refinement. I defined the problem and functional requirements, tested the implementation in its actual operating environment, evaluated the results, and retained responsibility for validation and deployment.

The Storyline Web Indexer therefore became both an EdTech utility and a practical experiment in **human–AI paired development**.

## What this solution does—and does not do

The Storyline Web Indexer does not transform Storyline into a conventional HTML content-management system.

It does not guarantee that Google, Bing, or another search engine will index or rank a Storyline site.

It does not replace thoughtful website architecture, accessibility, usability, metadata, or good learning design.

And it does not suggest that every Storyline course belongs on the public web.

Instead, it addresses a narrower problem:

> **When we intentionally use Storyline to create a public learning experience, how can we provide conventional web crawlers with another way to discover useful information contained within that experience?**

## Why this matters for lifelong learning

The experiment began as a technical indexing problem, but it points toward a broader learning-design question.

If learning increasingly moves between classrooms, LMS platforms, workplace systems, open repositories, portfolios, professional communities, and the public web, we need to think not only about how learners interact with resources but also about how they find them again.

A Storyline experience can provide the interaction.

The open web can provide reach and continuity.

The indexing layer helps connect the two.

> **Learning does not have to stop when someone exits the LMS.**

## Technical Resources

The implementation materials are separated from this article so that the project overview remains accessible to learning professionals who do not need the PHP details.

- **Source code:** [`indexer.php`](indexer.php)
- **Installation and Search Console guide:** [`INSTALLATION.md`](INSTALLATION.md)
- **Maintenance workflow:** [`MAINTENANCE.md`](MAINTENANCE.md)
- **Security considerations:** [`SECURITY.md`](SECURITY.md)

The code and documentation are provided for learning professionals who would like to study, test, modify, or adapt the approach to their own Storyline publications.

### Project

**Storyline Web Indexer**
Part of **Ed Tech Solutions**

*Practical, reusable solutions to problems encountered in digital learning, instructional design, and educational technology.*
