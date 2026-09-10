# Exploratory follow-up: make the percentage output unit explicit

Planned September 10, 2026, after inspecting the first five percentage-case outputs in the original 64-case run. Those traces calculated correct intermediate changes but repeatedly debated whether `ANSWER: p/q` meant the percentage coefficient or fractional change. This follow-up is exploratory and is not a prespecified primary benchmark.

Use all 16 original percentage prompts (all eight parameter pairs, both wordings), in their original order, without choosing cases based on performance. Append exactly:

> In the final ANSWER line, p/q must be the numerical coefficient of the percentage, with no percent symbol. For example, a 5% increase must be reported as ANSWER: 5/1, not ANSWER: 1/20.

The expected answers remain the original percentage coefficients. This example clarifies the output convention and does not give the calculation formula or any evaluated answer.

Run the same pinned model/runtime, unquantized bfloat16, seed 20260910, greedy decoding, official chat template, and 4096-token ceiling. Run once per prompt after the complete original run; no retries or selective regeneration. Save all outputs and use the unchanged exact-answer parser and completion requirement. Score 256/1024/4096 prefixes of each greedy stream as in the main protocol.

Report full completion and exact-answer counts for original and clarified percentage sets, token usage, paired changes, and reasoning review. Do not combine the follow-up with the 64-case primary score or claim a causal estimate beyond these fixed prompts. Any gain could reflect output convention, the added example, or prompt length; the study does not isolate those factors. Any persistent arithmetic errors must be reported separately from unit interpretation.

No follow-up output had been generated when this protocol was written.
