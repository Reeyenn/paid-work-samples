"""Exact-answer scoring. Model text is parsed as data, never executed."""
import argparse,csv,json,re
from fractions import Fraction
from pathlib import Path
from collections import defaultdict
ANSWER=re.compile(r'ANSWER:\s*(-?\d+)\s*/\s*([1-9]\d*)\s*')
def parse(text):
    # The model's native reasoning delimiter can share a line with the answer.
    final=text.rsplit('</think>',1)[-1]
    lines=[s.strip() for s in final.splitlines() if s.strip()]
    if not lines:return None
    m=ANSWER.fullmatch(lines[-1])
    if not m:return None
    if max(len(m[1]),len(m[2]))>100:return None
    return Fraction(int(m[1]),int(m[2]))
def score(record,budget):
    text=record['budget_prefixes'].get(str(budget),record['output'])
    value=parse(text)
    complete=record['finish_reason']=='stop' and record['generation_tokens']<=budget
    return {'id':record['id'],'pair':record['pair'],'family':record['family'],'variant':record['variant'],'budget':budget,'expected':record['expected'],'parsed':str(value) if value is not None else '', 'complete':complete,'correct':complete and value==Fraction(record['expected']),'formatted_answer_matches':value==Fraction(record['expected']),'reason':'correct' if complete and value==Fraction(record['expected']) else 'incomplete' if not complete else 'format' if value is None else 'wrong','tokens':record['generation_tokens'],'seconds':record['elapsed_seconds']}
def main():
    p=argparse.ArgumentParser();p.add_argument('--results',type=Path,default=Path(__file__).with_name('results'));args=p.parse_args()
    records=[json.loads(f.read_text()) for f in sorted(args.results.glob('*.json')) if f.name!='environment.json']
    rows=[score(r,b) for r in records for b in (256,1024,4096)]
    out=Path(__file__).parent
    with (out/'scores.csv').open('w') as f:
        w=csv.DictWriter(f,fieldnames=list(rows[0]) if rows else ['id']);w.writeheader();w.writerows(rows)
    summary={'completed_records':len(records),'expected_records':64,'budgets':{}}
    for budget in (256,1024,4096):
        sub=[r for r in rows if r['budget']==budget];pairs=defaultdict(list)
        for r in sub:pairs[r['pair']].append(r)
        valid_pairs=[p for p in pairs.values() if len(p)==2]
        summary['budgets'][str(budget)]={'correct':sum(r['correct'] for r in sub),'denominator':len(sub),'incomplete':sum(r['reason']=='incomplete' for r in sub),'format':sum(r['reason']=='format' for r in sub),'wrong':sum(r['reason']=='wrong' for r in sub),'pairs_evaluated':len(valid_pairs),'both_correct_pairs':sum(all(r['correct'] for r in p) for p in valid_pairs),'discordant_correctness_pairs':sum(p[0]['correct']!=p[1]['correct'] for p in valid_pairs),'families':{family:{'correct':sum(r['correct'] for r in sub if r['family']==family),'n':sum(r['family']==family for r in sub)} for family in sorted({r['family'] for r in sub})}}
    (out/'summary.json').write_text(json.dumps(summary,indent=2));print(json.dumps(summary,indent=2))
if __name__=='__main__':main()
