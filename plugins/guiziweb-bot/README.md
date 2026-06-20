# guiziweb-bot

Automation skills for the **guiziwebbot** GitHub bot. They run inside the `claude-dispatch` GitHub Actions workflow (in `Guiziweb/.github`) when you comment `@guiziwebbot fix` on an issue or a pull request, and can also be run locally.

The bot never merges: every change lands as a Pull Request that you review and merge. Branch protection enforces this.

## Skills

| Skill | Description |
|-------|-------------|
| [`guiziweb-bot:fix-issue`](./skills/fix-issue/SKILL.md) | Implement the change described in an issue and open a Pull Request that closes it, assigned to you |
| [`guiziweb-bot:revise-pr`](./skills/revise-pr/SKILL.md) | Apply the latest review feedback to a Pull Request and push it to the PR branch |

Both skills surface progress through three labels carried on the issue and the PR: `in progress` while the bot works, `needs review` when it hands the work back to you, `blocked` when it needs your input. These labels are created once when a repository is onboarded to the bot.

## Local development

This plugin lives inside the [`guiziweb-plugins`](../../README.md) marketplace repo. To test without installing through the marketplace:

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/guiziweb-bot/
```