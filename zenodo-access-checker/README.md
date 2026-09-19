# Zenodo Access Checker for Articulate Storyline

## A lightweight domain-reachability check for external learning resources

Online learning often depends on resources hosted outside the learning environment. Persistent identifiers such as DOIs help learners locate scholarly resources over time, but a persistent link does not guarantee that the external repository will be reachable at the moment a learner needs it.

I encountered this issue while developing a Teaching Philosophy resource in **Articulate Storyline** containing seven DOI links to materials hosted on **Zenodo**. Rather than allowing learners to click several links before discovering a possible access problem, I wanted Storyline to provide a brief advisory when Zenodo could not be reached.

The result is this small JavaScript gadget.

It is deliberately simple. It does **not** validate individual DOIs, retrieve Zenodo records, inspect repository content, or use the Zenodo API. It performs a lightweight **domain-reachability check** from the learner's browser.

If the request succeeds, nothing happens. If the request fails or times out, a Storyline variable displays a temporary advisory.

## Design principle

The checker asks one question:

> **Can the learner's browser currently reach zenodo.org?**

This distinction became important during development.

Initial tests attempted to request specific API or DOI resources and inspect the HTTP response. That introduced cross-origin restrictions, redirects, endpoint behaviour, and other factors that could generate a warning even though the learner could open Zenodo normally.

The final implementation therefore uses a `no-cors` request. The script does not need to read or interpret Zenodo's response. It only needs to determine whether the browser can complete the request.

## JavaScript

```javascript
// ============================================================
// ZENODO ACCESS CHECKER FOR ARTICULATE STORYLINE
//
// Purpose:
// Performs a lightweight browser-side reachability check
// for zenodo.org.
//
// Storyline variable:
//   ZenodoIssue
//   Type: True/False
//   Default: False
//
// Behaviour:
//   Zenodo reachable -> ZenodoIssue remains False
//   Request fails/times out -> ZenodoIssue becomes True
//
// This is a reachability check, NOT a Zenodo API,
// DOI-validation, or repository-health test.
// ============================================================

(function () {

    const player = GetPlayer();

    // Reset status whenever the slide is entered.
    player.SetVar("ZenodoIssue", false);

    const testURL = "https://zenodo.org/";
    const timeoutMS = 5000;

    const controller = new AbortController();

    const timeoutID = setTimeout(function () {
        controller.abort();
    }, timeoutMS);

    fetch(testURL, {
        method: "GET",
        mode: "no-cors",
        cache: "no-store",
        signal: controller.signal
    })
    .then(function () {

        clearTimeout(timeoutID);

        // Request completed successfully.
        player.SetVar("ZenodoIssue", false);

        console.log(
            "Zenodo Access Checker: Zenodo is reachable."
        );
    })
    .catch(function (error) {

        clearTimeout(timeoutID);

        // Request failed or exceeded the timeout.
        player.SetVar("ZenodoIssue", true);

        console.warn(
            "Zenodo Access Checker: possible access issue.",
            error
        );
    });

})();
```

## Storyline setup

Create a **True/False variable** called:

```text
ZenodoIssue
```

Set its default value to:

```text
False
```

On the slide containing the Zenodo resources, execute the JavaScript when the slide timeline starts.

The script explicitly resets `ZenodoIssue` to `False` each time the check begins. This prevents a previous failed check from being treated as the current state when the learner revisits the page.

Create a Storyline layer called:

```text
Zenodo Advisory
```

On the base layer, create a trigger:

```text
Show layer: Zenodo Advisory
When: ZenodoIssue changes
Condition: ZenodoIssue = True
```

In the pilot implementation, the advisory layer remains visible for **10 seconds** and then closes automatically. It also contains a **Close (X) button**, allowing the learner to dismiss it immediately.

When Zenodo is reachable, the learner sees nothing.

### Example advisory text

> **Zenodo Access Advisory**
>
> Some resources on this page are hosted on Zenodo, an open-access repository for scholarly publications and research resources.
>
> Zenodo is currently experiencing intermittent access and performance issues. DOI-linked resources may load slowly or be temporarily unavailable. **If you encounter an issue, please try again later.**

## Controlled A/B testing

Before deployment, both paths were tested deliberately.

For the normal condition:

```javascript
const testURL = "https://zenodo.org/";
```

The request completed and the advisory did not appear.

To simulate an unreachable external service, temporarily substitute a deliberately nonexistent domain:

```javascript
const testURL = "https://this-domain-does-not-exist.invalid/";
```

The request should fail and Storyline should display the advisory.

After testing, restore the production URL:

```javascript
const testURL = "https://zenodo.org/";
```

This A/B test verifies both sides of the interaction rather than assuming that the absence of an error means the checker is functioning.

## Why `no-cors`?

A Storyline course and Zenodo normally operate on different domains. A conventional JavaScript `fetch()` can therefore encounter browser cross-origin restrictions even when a learner can successfully navigate to the same resource.

For this use case, Storyline does not need the contents of Zenodo's response.

Using:

```javascript
mode: "no-cors"
```

allows the checker to focus on **reachability rather than retrieval**.

That is the central design decision behind this implementation.

## Cache and revisiting

The implementation uses several safeguards so that an earlier result is not intentionally reused as the current result.

```javascript
player.SetVar("ZenodoIssue", false);
```

resets the Storyline variable when the check begins, while:

```javascript
cache: "no-store"
```

requests that the reachability test not be satisfied using a stored HTTP response.

In the pilot Storyline implementation, the player is also configured to **start from new** rather than resume a previous course state.

These measures do not eliminate every form of caching or network infrastructure outside Storyline's control, but they reduce the likelihood that an old application state or cached HTTP response determines the learner's current advisory.

## What this checker does — and does not do

This is intentionally a small **learning-technology gadget**, not a comprehensive service-monitoring system.

It can indicate that the learner's browser could not complete a request to the external domain within the specified period.

It **cannot** determine whether Zenodo as a whole is operational, whether an individual DOI is valid, whether a specific record exists, or why a request failed. Local connectivity, browser security, institutional firewalls, privacy tools, DNS problems, and other network conditions can also affect the result.

For that reason, the learner-facing message should be framed as an **advisory about a possible access issue**, rather than declaring that Zenodo is down.

## Why I built it

Open educational resources are useful partly because learning can continue after a course, workshop, or microcredential has ended. Persistent identifiers and open licensing help make that possible, but continued learning also depends on learners being able to reach the resources when they need them.

This small experiment emerged from a practical instructional-design problem: **how should an online learning experience respond when an external resource on which it depends may temporarily be unavailable?**

The solution was not to add another quiz, screen, or technical dependency. It was simply to give the learner useful information at the point of need.

Sometimes a small piece of code is enough.

## Current implementation

The initial implementation is being piloted on a Teaching Philosophy page containing seven Zenodo DOI links. A single reachability check is used for the page rather than testing each DOI individually.

## Suggested repository description

> A lightweight JavaScript domain-reachability checker that lets Articulate Storyline warn learners of possible Zenodo access issues before they follow external DOI links.

## Notes

The JavaScript was developed and debugged with coding assistance from OpenAI. The implementation itself is standard browser-side JavaScript integrated with Articulate Storyline variables and triggers.
