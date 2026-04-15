---
name: add-behat
description: Add Behat UI tests (admin + JavaScript) to an existing Sylius plugin
allowed-tools: Bash, Read, Edit, Write, Glob, Grep
---

# Adding Behat Tests to a Sylius Plugin

Behat is already installed in the skeleton. This skill covers creating the first scenarios and pitfalls to avoid.

---

## Prerequisites

Behat and FOB extensions are already in `composer.json` (base skeleton). Nothing to install.

---

## 1. Create `behat.yml`

Copy from the dist file:

```bash
cp behat.yml.dist behat.yml
```

`behat.yml` is in `.gitignore` — each developer has their own copy.

---

## 2. Initialize the test database

```bash
ENV=test make database-init
```

> Don't forget `ENV=test`. Without it, the `sylius_test` database doesn't exist and Behat crashes with `Unknown database 'sylius_test'`.

---

## 3. JavaScript tests (Chrome via CDP)

For tests that require JavaScript (LiveComponents, Stimulus controllers), Behat uses a Chrome container via the Chrome DevTools Protocol.

### Add Chrome to `compose.yml`

```yaml
chrome:
    image: zenika/alpine-chrome
    command: --no-sandbox --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222 --remote-allow-origins=*
    shm_size: 2gb
```

> `zenika/alpine-chrome` supports ARM64 (Apple Silicon). Do NOT use `selenium/standalone-chrome` — it's x86 only.

### Configure `behat.yml.dist`

In the `MinkExtension` section, set `javascript_session: chrome` and point it to the container:

```yaml
Behat\MinkExtension:
    javascript_session: chrome
    sessions:
        symfony:
            symfony: ~
        chrome:
            chrome:
                api_url: http://chrome:9222
                validate_certificate: false
```

Tag scenarios that need JavaScript with `@javascript`:

```gherkin
@my_suite @javascript @ui
Feature: ...
```

Chrome accesses the app via `http://nginx` (set in `tests/TestApplication/.env` as `BEHAT_BASE_URL`). The Docker network handles resolution automatically.

---

## 4. Create the feature file

Features go in `features/`. Example: `features/admin/searching_orders_with_ai.feature`.

**Important rule**: use a tag prefixed with the plugin name to avoid conflicts with Sylius built-in suites.

```gherkin
@grid_assistant_ai_search @javascript @ui
Feature: Searching orders using AI assistant
    In order to find specific orders quickly
    As an Administrator
    I want to use natural language to filter the orders grid

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt"
        And the store ships everywhere for Free
        And the store allows paying with "Cash on Delivery"
        And there is a customer account "john.doe@gmail.com"
        And there is a customer "john.doe@gmail.com" that placed an order "#00000022"
        And the customer bought a single "PHP T-Shirt"
        And the customer chose "Free" shipping method to "United States" with "Cash on Delivery" payment
        And I am logged in as an administrator

    Scenario: Filtering orders by customer email
        When I browse orders
        And I search for "orders from john.doe@gmail.com" using the AI assistant
        Then I should see a single order from customer "john.doe@gmail.com"
```

---

## 5. Declare the suite in `tests/Behat/Resources/suites.yml`

```yaml
default:
    suites:
        grid_assistant_ai_search:
            contexts:
                # DB cleanup between scenarios — REQUIRED
                - sylius.behat.context.hook.doctrine_orm

                # Transforms (string → Doctrine object) — REQUIRED
                - sylius.behat.context.transform.shared_storage
                - sylius.behat.context.transform.address
                - sylius.behat.context.transform.customer
                - sylius.behat.context.transform.channel
                - sylius.behat.context.transform.product
                - sylius.behat.context.transform.shipping_method
                - sylius.behat.context.transform.payment

                # Fixture setup
                - sylius.behat.context.setup.admin_security
                - sylius.behat.context.setup.channel
                - sylius.behat.context.setup.customer
                - sylius.behat.context.setup.order
                - sylius.behat.context.setup.product
                - sylius.behat.context.setup.shipping
                - sylius.behat.context.setup.payment

                # UI contexts
                - sylius.behat.context.ui.admin.managing_orders

                # Custom plugin context
                - my_plugin.context.ui.admin.my_context

            filters:
                tags: "@grid_assistant_ai_search"
```

> **Prefix both the suite name AND the tag** with the plugin name. `behat.yml.dist` imports Sylius suites — if you use `managing_orders`, Behat runs the scenario twice → `Duplicate entry` in the database.

---

## 6. Custom context for JavaScript interactions

For interactions not covered by Sylius contexts (e.g. a custom UI component), create a context extending `RawMinkContext`:

```php
use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Webmozart\Assert\Assert;

final class MyContext extends RawMinkContext implements Context
{
    /**
     * @When I do something on the page
     */
    public function iDoSomethingOnThePage(): void
    {
        $page = $this->getSession()->getPage();

        $input = $page->find('css', 'input[name="query"]');
        Assert::notNull($input, 'Input not found.');
        $input->setValue('some value');

        $button = $page->find('css', 'button[type="submit"]');
        Assert::notNull($button, 'Submit button not found.');
        $button->click();

        // Wait for JS interaction to complete (e.g. AJAX + redirect)
        $this->getSession()->wait(60000, 'document.readyState === "complete" && window.location.href.includes("expected-param")');
    }
}
```

Register it in `tests/Behat/Resources/services.xml`:

```xml
<service id="my_plugin.context.ui.admin.my_context"
         class="Tests\MyPlugin\Behat\Context\Ui\Admin\MyContext" />
```

---

## 7. Common pitfalls

### `context class not found`
The service ID doesn't exist. Find the correct name:
```bash
grep -o 'id="sylius\.behat\.context\.[^"]*"' vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services/contexts/transform.xml
```

### `Argument must be of type CustomerInterface, string given`
A **transform** context is missing. Transforms convert Gherkin strings (email, name...) into Doctrine objects. Add the corresponding transform to the suite.

### `Duplicate entry` in database
Two suites share the same tag → the scenario runs twice. Prefix the tag.

### `Unknown database 'sylius_test'`
Run `ENV=test make database-init` first.

### `there is a customer "email" that placed an order` fails
This step expects a `CustomerInterface` (from SharedStorage). You must first create the customer with:
```gherkin
And there is a customer account "john.doe@gmail.com"
```

### Chrome image not found for ARM64
`selenium/standalone-chrome` is x86 only. Use `zenika/alpine-chrome` instead.

---

## 8. Run the tests

```bash
ENV=test make behat
```

To run a specific suite only:
```bash
ENV=test make behat ARGS="--suite=grid_assistant_ai_search"
```

---

## Available Sylius contexts

All available context IDs:

```bash
# Setup
grep -o 'id="sylius\.behat\.context\.setup\.[^"]*"' vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services/contexts/setup.xml

# Transform
grep -o 'id="sylius\.behat\.context\.transform\.[^"]*"' vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services/contexts/transform.xml

# UI Admin
grep -o 'id="sylius\.behat\.context\.ui\.admin\.[^"]*"' vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services/contexts/ui.xml
```