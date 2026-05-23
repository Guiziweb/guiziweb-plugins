---
name: plugin-add-stimulus-controller
description: Add a Stimulus JS controller to a Sylius plugin
argument-hint: "[ControllerName] [admin|shop] [lazy|eager]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Stimulus Controller to a Sylius Plugin

Ask the user for ControllerName, target (admin/shop), and fetch mode (lazy/eager) if not provided.

---

## Context — how it works in a plugin

In a **Sylius application**, controllers in `assets/controllers/` are auto-discovered.

In a **Sylius plugin**, controllers are declared as an npm package. The test application installs the plugin via `"@vendor/my-plugin": "file:../../.."` in its `package.json`. The stimulus-bridge resolves controller files by reading `symfony.controllers` from the plugin's `package.json`.

Two files must be in sync:
- `package.json` → declares the controller and its JS file path
- `assets/{admin|shop}/controllers.json` → enables it and sets fetch mode

---

## 1. Create the JS controller

`assets/{admin|shop}/controllers/{ControllerName}Controller.js`

```javascript
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // expose methods as actions (called via data-action="event->controller#method")
    myAction() {
        // ...
    }
}
```

**Stimulus conventions:**
- No `addEventListener` in `connect()` — use `data-action` attributes in templates instead
- Values: `static values = { myParam: String }` → accessed via `this.myParamValue`
- Targets: `static targets = ['myEl']` → accessed via `this.myElTarget`

---

## 2. Declare in `package.json`

Add under `symfony.controllers` in the plugin root `package.json`:

```json
{
  "name": "@vendor/my-plugin",
  "symfony": {
    "controllers": {
      "my-controller": {
        "main": "assets/admin/controllers/MyControllerController.js",
        "fetch": "eager"
      }
    }
  }
}
```

- `fetch: "eager"` → loaded on every page (needed if controller must be ready before user interaction, e.g. on modal open)
- `fetch: "lazy"` → loaded only when the controller appears in the DOM

> **Do NOT add `"webpackMode": "lazy"` for eager controllers** — it conflicts.

---

## 3. Enable in `assets/{admin|shop}/controllers.json`

```json
{
  "controllers": {
    "@vendor/my-plugin": {
      "my-controller": {
        "enabled": true,
        "fetch": "eager"
      }
    }
  },
  "entrypoints": []
}
```

The fetch mode here must match `package.json`.

---

## 4. Use in a Twig template

```twig
<div
    data-controller="vendor--my-plugin--my-controller"
    data-vendor--my-plugin--my-controller-my-param-value="someValue"
    data-action="eventname->vendor--my-plugin--my-controller#myAction">
</div>
```

Controller name convention: `@vendor/my-plugin` + `my-controller` → `vendor--my-plugin--my-controller`
Value attribute: `data-{controller-id}-{value-name}-value`

---

## 5. Build assets

**First time adding a controller** (or after modifying `package.json`):

```bash
# --force is required: yarn won't re-copy the package if version hasn't changed
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn install --force && yarn build"
```

**After modifying only the JS file** (no `package.json` change):

```bash
docker compose run --rm nodejs
```

> The default `nodejs` container command runs `cd vendor/sylius/test-application && yarn install && yarn build`.
> Without `--force`, yarn sees the package version as unchanged and skips re-copying `package.json`.

---

## Troubleshooting

**`Controller "@vendor/my-plugin/my-controller" does not exist in the package`**
→ Controller declared in `assets/controllers.json` but missing from `package.json`. Add it, then rebuild with `--force`.

**Controller not reacting to events**
→ Check the `data-action` attribute syntax: `"eventname->controller-id#methodName"`. For Bootstrap modal events: `"show.bs.modal->controller-id#methodName"`.

**`fetch: lazy` + event not firing**
→ Lazy controllers are loaded only when they appear in the DOM. If the event fires before the controller is connected, use `fetch: eager` instead.