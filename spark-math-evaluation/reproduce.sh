#!/bin/sh
set -eu
# Run from this directory; use the pinned Python runtime documented in environment.json.
# MODEL_PATH points to a separately downloaded pinned model snapshot.
: "${MODEL_PATH:?Set MODEL_PATH to the pinned local model snapshot}"
python3 run_eval.py --model "$MODEL_PATH" --out replication-results
python3 score.py --results replication-results
python3 unit-clarification/run_eval.py --model "$MODEL_PATH" --out unit-clarification/replication-results
python3 unit-clarification/score.py --results unit-clarification/replication-results
# The score scripts write summary.json and scores.csv alongside themselves.
# Use a fresh checkout if preserving the committed derived score files matters.
