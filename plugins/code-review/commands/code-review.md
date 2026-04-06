---
allowed-tools: Bash(gh issue view:*), Bash(gh search:*), Bash(gh issue list:*), Bash(gh pr comment:*), Bash(gh pr diff:*), Bash(gh pr view:*), Bash(gh pr list:*), mcp__github_inline_comment__create_inline_comment
description: Code review a Sylius plugin pull request
---

Provide a code review for the given pull request.

**Agent assumptions (applies to all agents and subagents):**
- All tools are functional and will work without error. Do not test tools or make exploratory calls. Make sure this is clear to every subagent that is launched.
- Only call a tool if it is required to complete the task. Every tool call should have a clear purpose.

To do this, follow these steps precisely:

1. Launch a haiku agent to check if any of the following are true:
   - The pull request is closed
   - The pull request is a draft
   - The pull request does not need code review (e.g. automated PR, trivial change that is obviously correct)
   - Claude has already commented on this PR (check `gh pr view <PR> --comments` for comments left by claude)

   If any condition is true, stop and do not proceed.

Note: Still review Claude generated PRs.

2. Launch a haiku agent to return a list of file paths (not their contents) for all relevant CLAUDE.md files including:
   - The root CLAUDE.md file, if it exists
   - Any CLAUDE.md files in directories containing files modified by the pull request

3. Launch a sonnet agent to view the pull request and return a summary of the changes

4. Launch 4 agents in parallel to independently review the changes. Each agent should return the list of issues, where each issue includes a description and the reason it was flagged. The agents should do the following:

   Agents 1 + 2: CLAUDE.md compliance sonnet agents
   Audit changes for CLAUDE.md compliance in parallel. Pay special attention to Sylius-specific rules:
   - `declare(strict_types=1)` must be present at the top of every PHP file
   - Commits must follow Conventional Commits format
   - Docker commands must go through `make`, never run directly

   Agent 3: Opus bug agent (parallel subagent with agent 4)
   Scan for obvious bugs. Focus only on the diff itself without reading extra context. Flag only significant bugs; ignore nitpicks and likely false positives. For Sylius plugins, pay attention to:
   - Missing resource configuration (services.xml, config)
   - Incorrect Doctrine mappings
   - Missing interface implementations required by Sylius

   Agent 4: Opus bug agent (parallel subagent with agent 3)
   Look for problems that exist in the introduced code. This could be security issues, incorrect logic, etc. Only look for issues that fall within the changed code. For Sylius plugins, pay attention to:
   - Missing `declare(strict_types=1)`
   - Incorrect use of Sylius repositories or factories
   - Breaking changes in public APIs

   **CRITICAL: We only want HIGH SIGNAL issues.** Flag issues where:
   - The code will fail to compile or parse (syntax errors, type errors, missing imports, unresolved references)
   - The code will definitely produce wrong results regardless of inputs (clear logic errors)
   - Clear, unambiguous CLAUDE.md violations where you can quote the exact rule being broken

   Do NOT flag:
   - Code style or quality concerns
   - Potential issues that depend on specific inputs or state
   - Subjective suggestions or improvements

   If you are not certain an issue is real, do not flag it. False positives erode trust and waste reviewer time.

5. For each issue found in the previous step by agents 3 and 4, launch parallel subagents to validate the issue. Use Opus subagents for bugs and logic issues, and sonnet agents for CLAUDE.md violations.

6. Filter out any issues that were not validated in step 5.

7. Output a summary of the review findings to the terminal:
   - If issues were found, list each issue with a brief description.
   - If no issues were found, state: "No issues found. Checked for bugs and CLAUDE.md compliance."

   If `--comment` argument was NOT provided, stop here.

   If `--comment` argument IS provided and NO issues were found, post a summary comment using `gh pr comment` and stop.

   If `--comment` argument IS provided and issues were found, continue to step 8.

8. Create a list of all comments that you plan on leaving.

9. Post inline comments for each issue using `mcp__github_inline_comment__create_inline_comment` with `confirmed: true`. For each comment:
   - Provide a brief description of the issue
   - For small, self-contained fixes, include a committable suggestion block
   - For larger fixes, describe the issue and suggested fix without a suggestion block
   - Never post a committable suggestion UNLESS committing the suggestion fixes the issue entirely.

   **IMPORTANT: Only post ONE comment per unique issue. Do not post duplicate comments.**

Use this list when evaluating issues (these are false positives, do NOT flag):
- Pre-existing issues
- Something that appears to be a bug but is actually correct
- Pedantic nitpicks that a senior Sylius engineer would not flag
- Issues that a linter will catch (PHPStan, ECS)
- General code quality concerns unless explicitly required in CLAUDE.md

Notes:
- Use gh CLI to interact with GitHub. Do not use web fetch.
- Create a todo list before starting.
- You must cite and link each issue in inline comments.