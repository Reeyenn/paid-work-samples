# Prespecified protocol — Spark math study

Written before running the evaluated model on these 64 prompts, 10 September 2026.

Question: How robust are exact mathematical answers to equivalent wording, and how often does an output budget prevent delivery of an otherwise correct answer?

- Original synthetic dataset: 32 matched pairs, 8 pairs in each of four families: equal-distance average speed, changing percentage bases, conditional sampling without replacement, and noncoprime congruence counting.
- Seed 20260910. All generated cases retained, including impossible congruence systems. This is a diagnostic sample, not a representative math benchmark. It cannot establish absence of training contamination or general mathematical ability.
- Frozen dataset SHA256: e87f0d487e479097d569b0bdfc3ce295e0f5212cc66d3a0ce246fc1f0b214ec6.
- Ground truths use exact rational arithmetic, with independent enumeration/checks in the generator. No approximate numeric matches.
- Original checkpoint revision 448e61eb392c00f2c403185c5b56d5e0665bfaab, official Spark MLX runtime revision de2b4379fa1e2f2e1f99d84c83f0e008f651d86c, bfloat16, no quantization, greedy decoding, 4096 generated-token maximum, one sample per prompt.
- Preserve raw output, actual rendered prompt, token IDs, finish reason, elapsed time and package versions. No cherry-picking, silent retries or removal of failures. A separate 17+28 smoke prompt checks the runtime and does not count toward results.
- Examine prefixes at 256, 1024 and 4096 generated tokens of the SAME greedy run. These are output-delivery budget observations, not independent runs, different prompting experiments, or pass@k estimates. If EOS occurs before a budget, use the complete output.
- Primary score: last nonempty output line must match `ANSWER: p/q` (integer p, positive integer q) and equal the exact reference rational. Unparseable, missing, incorrect and truncated results count as failures. Report format failures separately from parseable wrong answers. A correct marked answer inside a truncated trace is not a completed response.
- Report all family totals, per-prompt outcomes, both-correct matched-pair rate and pair disagreement. Pair disagreement is descriptive, not evidence that wording alone caused a stochastic difference beyond this fixed run.
- Review every final output for explicit mathematical errors using the stored derivation and problem parameters, including correct-final-answer cases. Publish review labels and quote the relevant step when flagging an error. This reasoning review is AI-conducted and fallible, not an independent human audit or proof verification.
- Retain and report interrupted cases; resume only incomplete cases and label reruns. Never execute model-generated code.

Dataset and original scripts: CC0-1.0. Model weights remain Apache-2.0 at the publisher; they will not be uploaded with results.

Contest eligibility and required Hugging Face publication remain unconfirmed. A completed experiment is not an accepted entry or an award.

Implementation clarification before inspecting evaluation outputs: the separate smoke run showed the native `</think>` delimiter can occur immediately before the final answer on the same line. The scorer removes the reasoning prefix through the last `</think>` and applies the specified last-line rule to the answer text. Prefix budgets only receive primary correctness credit if the original run completed with EOS within that budget; a correct marked answer in an unfinished prefix is tracked separately. This avoids giving credit to provisional reasoning answers.
