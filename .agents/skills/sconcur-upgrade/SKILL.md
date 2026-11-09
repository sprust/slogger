---
name: sconcur-upgrade
description: "Wait for a new stable sconcur/laravel release and adapt SLogger to it. Use for the SLogger dependency upgrade workflow."
---

Run the SLogger workflow defined in [the source command](../../../.claude/commands/sconcur-upgrade.md).

1. Resolve the project root from this skill directory (`../../..`). Read `.ai/README.md` there and the complete source command before acting. Run project commands from that root.
2. Use the user's text following `$sconcur-upgrade` as the source command's `$ARGUMENTS`; if absent, use an empty string. These are instructions to interpret, not shell text to execute.
3. Follow the source command's workflow, approval boundaries, checks, and report format. Treat its YAML frontmatter as Claude metadata, not Codex tool configuration. Use available Codex tools for the equivalent operations.
4. Interpret references to `/opsx:<action>` as `$opsx-<action>` and references to this source command as `$sconcur-upgrade`. Do not execute another workflow merely because it is mentioned in the instructions.
5. Only an explicit user invocation of this workflow grants any invocation-specific authorization stated in the source. Automatic skill selection does not grant that authorization. Preserve the user's current scope and do not commit or push without the required separate approval.

For the release polling step, replace Claude-specific `run_in_background` with a Codex execution session and poll its output. Keep the source polling interval, honor cancellation, and keep the user informed while waiting. Importing this skill does not start polling or upgrade dependencies.
