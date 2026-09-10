# When a correct calculation never becomes an answer

An AI-conducted diagnostic of Spark-X2.5-1.7B, 10 September 2026.

On 64 synthetic exact-math prompts, Spark delivered 37 correctly formatted, exact answers before the 4,096-token limit. Twenty-four outputs exhausted the limit. Several had already derived the correct result, then continued enumerating examples or debating how to express an integer as a fraction. That makes completion behavior an important part of this small experiment, alongside mathematical correctness.

This is a diagnostic sample, not a representative benchmark. It includes a duplicated probability pair and ambiguous percentage units. Both problems are disclosed below; the original scores are preserved.

## Primary results

| Generated-token budget | Exact completed answers | Incomplete | Format failures | Parseable wrong answers |
|---|---:|---:|---:|---:|
| 256 | 0/64 | 64 | 0 | 0 |
| 1,024 | 7/64 | 57 | 0 | 0 |
| 4,096 | 37/64 | 24 | 1 | 2 |

The three budgets are prefixes of the **same greedy generation**, not three independent experiments. A correct answer within an unfinished reasoning trace does not receive primary credit. The model must end its response within the budget and finish with `ANSWER: p/q`, matching the exact rational reference.

| Family | Correct at 4,096 | Completed | Median generated tokens |
|---|---:|---:|---:|
| Equal-distance average speed | 12/16 | 13/16 | 2,569.5 |
| Changing percentage bases | 0/16 | 1/16 | 4,096 |
| Conditional sampling without replacement | 15/16 | 16/16 | 1,306 |
| Noncoprime congruence counts | 10/16 | 10/16 | 3,448 |

Fourteen of 32 wording pairs had both answers correct. Nine pairs disagreed in correctness. The percentage-family score requires particular care: its sole completed response expresses the correct fractional change instead of the expected percentage coefficient. This is a unit mismatch under an underspecified prompt, not persuasive evidence of a mathematical mistake.

## Three failures with different implications

**A format failure with correct mathematics.** `equal_distance_speed-00-reworded` derives the correct average speed, 2520/73 km/h, but ends with `ANSER: 2520/73`. The fixed scorer rejects it. A production consumer might add a tolerant parser, but doing that retrospectively here would change the question being measured.

**A real sampling mistake.** `conditional_without_replacement-01-direct` computes the probability of drawing two blue balls using 15/28 times 15/27. With 13 red and 15 blue balls, the second numerator should be 14. The resulting final answer is 52/177 instead of 2/7. Its reworded partner gets 2/7. This is an arithmetic-modeling error rather than a delivery-format issue.

**A correct impossibility proof followed by a timeout.** `noncoprime_congruences-03-reworded` correctly observes that 10a is congruent to 13 modulo 14 only if gcd(10,14) divides 13. It does not. Instead of finishing, the output enumerates many unnecessary residues, makes several errors in that enumeration, continues beyond the problem's interval, and hits the token cap. More generated text did not improve the already sufficient argument.

Correct final answers can also contain faulty intermediate statements. For example, `noncoprime_congruences-07-direct` ends with the correct zero count, but an unnecessary enumeration says 81 modulo 15 is 3, rather than 6. The exact-answer score alone does not certify the explanation.

## Dataset problems and sensitivity analysis

The generator retained every case from its fixed seed. Inspection found that probability pair 07 is identical to pair 00, including both prompt variants. There are **62 distinct prompt strings and 31 distinct mathematical pairs**. Keeping only the first occurrence of each prompt gives **35/62 exact completed answers**, compared with 37/64 in the prespecified total. These are descriptive counts; neither denominator represents a broad random sample of mathematical ability.

The original percentage prompts request a signed percentage change and a final rational number without explicitly saying whether that rational is a percentage coefficient or a proportion. `changing_percentage_base-06-direct` reports -29/200, a correct proportional change; the reference expects -29/2, the coefficient of -14.5%. Other outputs repeatedly debate the same unit issue and sometimes introduce additional fraction-conversion mistakes.

A separate exploratory follow-up appends a unit clarification to all 16 percentage prompts, including the example that 5% should be written as 5/1, not 1/20. It preserves their expected answers and generation settings. Its protocol was published before its generations, but selected **after inspecting original failures**. The added wording, example and prompt length all change together; it cannot isolate a unit-only causal effect.

With the clarification, **2/16** responses deliver the exact completed answer, compared with 0/16 originally. Three complete and thirteen hit the cap. The remaining completed response ends with `ANSER: -13/80`: it both misspells the label and gives the proportional change instead of the requested -65/4 percentage coefficient. The scorer assigns it to the format category first, so the zero “wrong” count does not mean every completed calculation obeyed the unit instruction. Neither short budget receives any completed answer.

Six selected follow-up outputs were reviewed in full, including all three completed responses. Correct coefficients still appear repeatedly in some capped traces. Clarification is therefore not a complete remedy in these examples. The follow-up is a small exploratory comparison, not evidence of a reliable improvement rate.

## Reproducibility and limits

The base protocol and dataset were committed publicly before evaluation generation. There are eight generated pairs in each of four families, using seed 20260910. Ground truths use Python Fraction arithmetic and independent checks/enumeration in the generator. Every completed record is retained, including failures. A separate 17+28 runtime smoke test is excluded.

The checkpoint is [XHToken/Spark-X2.5-1.7B](https://huggingface.co/XHToken/Spark-X2.5-1.7B), revision `448e61eb392c00f2c403185c5b56d5e0665bfaab`. Execution uses the publisher's [Spark MLX runtime](https://github.com/XHToken/Spark-MLX-LLM), revision `de2b4379fa1e2f2e1f99d84c83f0e008f651d86c`, on an Apple M4 Pro with 24 GiB unified memory. Weights are bfloat16, unquantized. Decoding is greedy: temperature 0, top-p 1, top-k 0, one sample per prompt, 4,096 generated-token maximum, using the official chat template. `trust_remote_code` is false.

The base run generated 179,727 tokens. Summed measured generation elapsed time was 3,448.8 seconds; this excludes download, setup, review and other work. Other local work ran concurrently, so these timings are observations rather than controlled hardware benchmarks. Detailed versions, rendered prompts, output token IDs, finish reasons and timing are preserved with the results.

The reasoning review covers all 64 base outputs. It was performed by an AI assistant, not an independent human or formal proof checker. Review notes distinguish self-corrected errors, final-answer mistakes, trace errors and incomplete outputs; the review itself can be wrong. Neither exact-answer matching nor this review establishes faithful internal reasoning. Synthetic generation does not establish absence of training contamination. One model, one runtime, one decoding setup and a short fixed prompt set do not support model-ranking claims.

## Files

- `protocol.md`, `build_dataset.py`, `dataset.jsonl`: original design and reference answers.
- `run_eval.py`, `results/`: generations and environment, without model weights.
- `score.py`, `test_scoring.py`, `scores.csv`, `summary.json`: primary answer scoring.
- `analyze.py`, `analysis.json`: explicitly exploratory diagnostics and deduplication.
- `reasoning-review.json`: per-output AI review.
- `unit-clarification/`: separate follow-up protocol, data and results.

To recompute scores from the saved results, run `python3 score.py` and `python3 analyze.py` in this directory. Generating new outputs requires the separately installed pinned runtime and model checkpoint; consult `results/environment.json` and pass the model path to `run_eval.py --model ... --out ...`. Use a fresh output directory for an independent replication. The runner skips already completed records when resuming, so reusing this results directory would not perform a new evaluation.

Contest publication/eligibility is unconfirmed. This report is not an accepted entry or an award. Original dataset and scripts are CC0-1.0; model weights remain under their publisher's license and are not redistributed here.
