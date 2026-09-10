"""Exploratory diagnostics; never changes the prespecified scorer or records."""
import json
from pathlib import Path
from statistics import median
from score import score

ROOT = Path(__file__).parent

def records(directory):
    return [json.loads(p.read_text()) for p in sorted(directory.glob('*.json'))
            if p.name != 'environment.json']

def metrics(items):
    rows = [score(r, 4096) for r in items]
    return dict(n=len(rows), correct=sum(r['correct'] for r in rows),
                complete=sum(r['complete'] for r in rows),
                median_tokens=median(r['tokens'] for r in rows) if rows else None,
                total_tokens=sum(r['tokens'] for r in rows))

def main():
    data = [json.loads(line) for line in (ROOT/'dataset.jsonl').read_text().splitlines()]
    base = records(ROOT/'results')
    if len(base) != len(data):
        raise SystemExit('Primary generation incomplete; refusing final analysis.')
    by_id = {r['id']: r for r in base}
    seen, unique, duplicates = {}, [], []
    for item in data:
        key = item['prompt']
        if key in seen:
            duplicates.append({'id':item['id'], 'duplicates':seen[key]})
        else:
            seen[key] = item['id']
            unique.append(by_id[item['id']])
    result = {'analysis_status':'exploratory', 'primary':metrics(base),
              'deduplicated':metrics(unique), 'duplicate_prompts':duplicates,
              'family_metrics':{f:metrics([r for r in base if r['family']==f])
                                for f in sorted({r['family'] for r in base})}}
    follow = records(ROOT/'unit-clarification'/'results')
    if len(follow) == 16:
        pairs = [{'id':r['id'], 'original':score(by_id[r['id']],4096),
                  'clarified':score(r,4096)} for r in follow]
        result['unit_clarification'] = {
            'original':metrics([by_id[r['id']] for r in follow]),
            'clarified':metrics(follow), 'pairs':pairs,
            'limitation':'Selected after viewing original failures; added wording and example confound a unit-only causal interpretation.'}
    else:
        result['unit_clarification'] = {'status':'incomplete','completed':len(follow)}
    (ROOT/'analysis.json').write_text(json.dumps(result,indent=2)+'\n')
    print(json.dumps({k:v for k,v in result.items() if k!='unit_clarification'},indent=2))

if __name__ == '__main__':
    main()
