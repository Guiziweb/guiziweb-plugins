# Code Review Plugin

Automated code review for Sylius plugin pull requests, triggered via CI.

## Installation

```bash
claude plugin marketplace add Guiziweb/guiziweb-plugins
```

## How it works

A `repository_dispatch` event (`claude-review`) triggers a GitHub Actions workflow in `Guiziweb/.github`. The workflow runs Claude Code with this plugin and calls:

```
/code-review <repo>/pull/<pr> --comment
```

Claude reviews the PR diff and posts inline comments for high-signal issues only:

- Code that will fail to compile or parse (syntax errors, type errors, missing imports)
- Code that will definitely produce wrong results (clear logic errors)

It ignores style concerns, subjective suggestions, and anything a linter (PHPStan, ECS) would catch.