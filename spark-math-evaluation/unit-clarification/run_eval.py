"""Run only original fixed math prompts. No tool execution or external requests."""
import argparse,datetime,hashlib,json,platform,time,sys
from pathlib import Path
import importlib.metadata as metadata
import mlx.core as mx
from mlx_lm import stream_generate
from mlx_lm.sample_utils import make_sampler
from spark_mlx_llm import load
p=argparse.ArgumentParser();p.add_argument('--model',required=True);p.add_argument('--out',required=True);p.add_argument('--smoke',action='store_true');args=p.parse_args()
mx.set_default_device(mx.gpu);mx.random.seed(20260910)
model,tok,config=load(args.model,dtype='bfloat16',tokenizer_config={'trust_remote_code':False},return_config=True)
root=Path(__file__).parent
rows=[json.loads(x) for x in (root/'dataset.jsonl').read_text().splitlines()]
if args.smoke: rows=[{'id':'smoke-only','prompt':'What is 17 + 28? Give the answer briefly.','expected':'45/1'}]
out=Path(args.out);out.mkdir(parents=True,exist_ok=True)
env={'model':'XHToken/Spark-X2.5-1.7B','model_revision':'448e61eb392c00f2c403185c5b56d5e0665bfaab','runtime_revision':'de2b4379fa1e2f2e1f99d84c83f0e008f651d86c','python':platform.python_version(),'platform':platform.platform(),'hardware':'Apple M4 Pro, 24 GiB unified memory','device':'MLX Metal GPU','dtype':'bfloat16','quantization':None,'seed':20260910,'temperature':0,'top_p':1,'top_k':0,'max_tokens':512 if args.smoke else 4096,'sample_count':1,'prompt_style':'official unmodified chat template with add_generation_prompt=True','packages':{x:metadata.version(x) for x in ['mlx','mlx-lm','transformers','huggingface-hub','numpy','spark-mlx-llm']},'dataset_sha256':hashlib.sha256((root/'dataset.jsonl').read_bytes()).hexdigest(),'started_utc':datetime.datetime.now(datetime.timezone.utc).isoformat()}
(out/'environment.json').write_text(json.dumps(env,indent=2))
for index,row in enumerate(rows):
    target=out/(row['id']+'.json')
    if target.exists():print('SKIP completed',row['id'],flush=True);continue
    mx.random.seed(20260910)
    prompt=tok.apply_chat_template([{'role':'user','content':row['prompt']}],tokenize=False,add_generation_prompt=True)
    start=time.monotonic();text='';tokens=[];snapshots={};last=None
    partial=out/(row['id']+'.partial.jsonl')
    with partial.open('w') as fp:
        for response in stream_generate(model,tok,prompt,max_tokens=env['max_tokens'],sampler=make_sampler(temp=0,top_p=1,top_k=0)):
            text+=response.text;tokens.append(int(response.token));last=response
            fp.write(json.dumps({'text':response.text,'token':int(response.token),'generation_tokens':response.generation_tokens,'finish_reason':response.finish_reason})+'\n');fp.flush()
            for budget in [256,1024,4096]:
                if response.generation_tokens<=budget:snapshots[str(budget)]=text
    record={**row,'rendered_prompt':prompt,'output':text,'token_ids':tokens,'budget_prefixes':snapshots,'elapsed_seconds':time.monotonic()-start,'generation_tokens':last.generation_tokens,'prompt_tokens':last.prompt_tokens,'generation_tps':last.generation_tps,'peak_memory_gb':last.peak_memory,'finish_reason':last.finish_reason,'finished_utc':datetime.datetime.now(datetime.timezone.utc).isoformat()}
    target.write_text(json.dumps(record,ensure_ascii=False,indent=2));partial.unlink()
    print(f'{index+1}/{len(rows)} {row["id"]} tokens={last.generation_tokens} finish={last.finish_reason} seconds={record["elapsed_seconds"]:.1f}',flush=True)
    mx.clear_cache()
print('COMPLETE',flush=True)
