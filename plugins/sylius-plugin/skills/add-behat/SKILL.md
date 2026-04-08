---
name: add-behat
description: Add Behat UI tests (admin + Panther) to an existing Sylius plugin
allowed-tools: Bash, Read, Edit, Write, Glob, Grep
---

# Adding Behat Tests to a Sylius Plugin

Behat is already installed in the skeleton. This skill covers creating the first scenarios and pitfalls to avoid.

---

## Prerequisites

Behat, Panther and FOB extensions are already in `composer.json` (base skeleton). Nothing to install.

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

## 3. Create the feature file

Features go in `features/`. Example: `features/admin/browsing_orders.feature`.

**Important rule**: use a tag prefixed with the plugin name to avoid conflicts with Sylius built-in suites.

```gherkin
@grid_assistant_managing_orders @ui
Feature: Browsing orders
    In order to manage orders
    As an Administrator
    I want to be able to browse orders

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

    Scenario: Browsing orders list
        When I browse orders
        Then I should see a single order from customer "john.doe@gmail.com"
```

---

## 4. Declare the suite in `tests/Behat/Resources/suites.yml`

```yaml
default:
    suites:
        grid_assistant_managing_orders:
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

            filters:
                tags: "@grid_assistant_managing_orders"
```

> **Prefix both the suite name AND the tag** with the plugin name. `behat.yml.dist` imports Sylius suites — if you use `managing_orders`, Behat runs the scenario twice → `Duplicate entry` in the database.

---

## 5. Common pitfalls

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

---

## 6. Run the tests

```bash
ENV=test make behat
```

To run a specific suite only:
```bash
ENV=test make behat ARGS="--suite=grid_assistant_managing_orders"
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