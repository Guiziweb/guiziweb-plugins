# guiziweb-bot

Automation skills for the **guiziwebbot** GitHub bot. They run inside the `claude-dispatch` GitHub Actions workflow (in `Guiziweb/.github`) when you comment `@guiziwebbot fix` on an issue or a pull request, and can also be run locally.

The bot never merges: every change lands as a Pull Request that you review and merge. Branch protection enforces this.

## Skills

| Skill | Description |
|-------|-------------|
| [`guiziweb-bot:fix-issue`](./skills/fix-issue/SKILL.md) | Implement the change described in an issue and open a Pull Request that closes it, assigned to you |
| [`guiziweb-bot:revise-pr`](./skills/revise-pr/SKILL.md) | Apply the latest review feedback to a Pull Request and push it to the PR branch |

Both skills manage two issue labels to surface progress: `bot:working` while the bot is on the ticket, `bot:stuck` when it needs you. These labels are created once when a repository is onboarded to the bot.

## Local development

This plugin lives inside the [`guiziweb-plugins`](../../README.md) marketplace repo. To test without installing through the marketplace:

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/guiziweb-bot/
```