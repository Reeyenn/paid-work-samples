"""Original synthetic evaluation data; CC0-1.0. No external benchmark questions."""
import json, random, hashlib
from fractions import Fraction as F
from itertools import combinations
from pathlib import Path
SEED=20260910
rng=random.Random(SEED)
rows=[]
def add(family,index,answer,prompts,parameters,derivation):
    pair=f'{family}-{index:02d}'
    for variant,prompt in zip(['direct','reworded'],prompts):
        rows.append(dict(id=f'{pair}-{variant}',pair=pair,family=family,variant=variant,parameters=parameters,prompt=prompt+' Give concise mathematical reasoning. Finish with a line exactly in the form ANSWER: p/q, where p and q are integers and q is positive. Do not round.',expected=f'{answer.numerator}/{answer.denominator}',derivation=derivation))
for i in range(8):
    a,b=rng.sample(range(13,98),2); d=rng.randrange(30,240)
    ans=F(2*a*b,a+b)
    assert F(2*d)/(F(d,a)+F(d,b))==ans
    add('equal_distance_speed',i,ans,[f'A vehicle travels {d} km at {a} km/h and returns along the same {d} km route at {b} km/h. With no stops, what is its average speed in km/h over the whole trip?',f'The outbound and inbound legs are each {d} kilometers long. The speeds are {a} and {b} kilometers per hour respectively. Calculate total distance divided by total elapsed time, in kilometers per hour.'],dict(a=a,b=b,d=d),f'2*{a}*{b}/({a}+{b})')
for i in range(8):
    a,b=rng.sample(range(11,61),2)
    ans=(F(100+a,100)*F(100-b,100)-1)*100
    assert ans==F(a-b)-F(a*b,100)
    add('changing_percentage_base',i,ans,[f'A price first increases by {a}% and then decreases by {b}% of the increased price. What is the signed percentage change from the original price? Report positive for an increase and negative for a decrease.',f'Start with a price of 100 units. Multiply it by (1 + {a}/100), then multiply the result by (1 - {b}/100). Express the final price minus the original price as a signed percentage of the original price.'],dict(increase=a,decrease=b),f'{a}-{b}-{a}*{b}/100')
for i in range(8):
    r=rng.randint(3,13); b=rng.randint(3,15)
    ans=F(r*(r-1),(r+b)*(r+b-1)-b*(b-1))
    draws=list(combinations(range(r+b),2));eligible=[z for z in draws if any(k<r for k in z)]
    assert ans==F(sum(all(k<r for k in z) for z in eligible),len(eligible))
    add('conditional_without_replacement',i,ans,[f'A bag contains {r} red and {b} blue balls. Two balls are sampled uniformly without replacement. Given that at least one of the two is red, what is the probability that both are red?',f'All unordered pairs from {r} distinct red balls and {b} distinct blue balls are equally likely. Discard the pairs containing two blue balls. Among the remaining pairs, what fraction contain two red balls?'],dict(red=r,blue=b),f'C({r},2)/(C({r+b},2)-C({b},2))')
for i in range(8):
    m,n=rng.choice([(6,10),(8,12),(9,15),(10,14),(12,18),(14,21)])
    u=rng.randrange(m);v=rng.randrange(n);lo=rng.randint(10,200);hi=lo+rng.randint(250,950)
    count=sum(x%m==u and x%n==v for x in range(lo,hi+1));ans=F(count)
    # Independent enumeration via first progression and intersection.
    first=lo+(u-lo)%m
    assert count==len(set(range(first,hi+1,m)).intersection(x for x in range(lo,hi+1) if x%n==v))
    add('noncoprime_congruences',i,ans,[f'How many integers x in the inclusive interval [{lo}, {hi}] satisfy both x congruent to {u} modulo {m} and x congruent to {v} modulo {n}?',f'Consider integers from {lo} through {hi}, including both endpoints. Count those whose remainder upon division by {m} is {u} and whose remainder upon division by {n} is {v}.'],dict(m=m,n=n,u=u,v=v,lo=lo,hi=hi),f'Enumerate inclusive interval and test both remainders; total {count}')
assert len(rows)==64 and len({x['id'] for x in rows})==64
assert all(rows[i]['expected']==rows[i+1]['expected'] for i in range(0,len(rows),2))
path=Path(__file__).with_name('dataset.jsonl')
path.write_text(''.join(json.dumps(x,ensure_ascii=False,sort_keys=True)+'\n' for x in rows))
print(f'{len(rows)} prompts, 32 matched pairs; seed {SEED}; SHA256 {hashlib.sha256(path.read_bytes()).hexdigest()}')
