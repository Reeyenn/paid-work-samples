import { filterTasks, type Task } from './queue';
type Tool={name:string;title:string;description:string;inputSchema:object;annotations:{readOnlyHint:boolean};execute:(input:unknown)=>unknown};
type ModelContext={registerTool:(tool:Tool,options:{signal:AbortSignal})=>void|Promise<void>};
export function registerQueueTools(getTasks:()=>Task[],setFilters:(query:string,status:string)=>void){
 const context=(document as Document&{modelContext?:ModelContext}).modelContext;
 if(!context?.registerTool)return;
 const lifecycle=new AbortController();
 const tool:Tool={name:'filter_demo_work_queue',title:'Filter demo work queue',description:'Filter the visible fictional work queue by text and status. Returns matching items; does not change records.',inputSchema:{type:'object',properties:{query:{type:'string'},status:{type:'string',enum:['All','Ready','In progress','Done']}},required:['query','status'],additionalProperties:false},annotations:{readOnlyHint:false},execute(input){
  if(!input||typeof input!=='object')throw new Error('Expected a filter object.');
  const data=input as Record<string,unknown>;
  if(typeof data.query!=='string'||typeof data.status!=='string'||!['All','Ready','In progress','Done'].includes(data.status)||Object.keys(data).some(k=>!['query','status'].includes(k)))throw new Error('Invalid query or status.');
  setFilters(data.query,data.status);
  return {items:filterTasks(getTasks(),data.query,data.status).map(({id,title,status})=>({id,title,status}))};
 }};
 try{Promise.resolve(context.registerTool(tool,{signal:lifecycle.signal})).catch(()=>lifecycle.abort());}catch{lifecycle.abort();}
 return ()=>lifecycle.abort();
}
