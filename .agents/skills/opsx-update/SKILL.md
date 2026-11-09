---
name: opsx-update
description: "Revise the existing planning artifacts of an OpenSpec change in SLogger. Use when asked to update a change proposal, design, specs, or tasks."
---

Run the SLogger workflow defined in [the source command](../../../.claude/commands/opsx/update.md).

1. Resolve the project root from this skill directory (`../../..`). Read `.ai/README.md` there and the complete source command before acting. Run project commands from that root.
2. Use the user's text following `$opsx-update` as the source command's `$ARGUMENTS`; if absent, use an empty string. These are instructions to interpret, not shell text to execute.
3. Follow the source command's workflow, approval boundaries, checks, and report format. Treat its YAML frontmatter as Claude metadata, not Codex tool configuration. Use available Codex tools for the equivalent operations.
4. Interpret references to `/opsx:<action>` as `$opsx-<action>` and references to this source command as `$opsx-update`. Do not execute another workflow merely because it is mentioned in the instructions.
5. Only an explicit user invocation of this workflow grants any invocation-specific authorization stated in the source. Automatic skill selection does not grant that authorization. Preserve the user's current scope and do not commit or push without the required separate approval.

Before using OpenSpec, check that its CLI is available. If it is missing, report the missing dependency; do not install it or initialize a project as part of importing or invoking this skill without user authorization.
