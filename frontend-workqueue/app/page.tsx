"use client";
import { useEffect, useState, useRef } from "react";
import { flushSync } from "react-dom";
import { registerQueueTools } from "@/lib/webmcp";
import { Check, ChevronRight, Clock3, Inbox, Moon, Search, Sun, RotateCcw, Layers3 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { Sheet, SheetTrigger, SheetContent, SheetTitle, SheetDescription, SheetClose } from "@/components/ui/sheet";
import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import { Empty, EmptyHeader, EmptyTitle, EmptyDescription } from "@/components/ui/empty";
import { initialTasks, filterTasks, type Task, type Status } from "@/lib/queue";

function StatusBadge({status}:{status:Status}) { return <Badge variant="outline" className={`status ${status.toLowerCase().replaceAll(' ','-')}`}>{status === 'Done' ? <Check aria-hidden/> : status === 'In progress' ? <Clock3 aria-hidden/> : <Inbox aria-hidden/>}{status}</Badge>; }
function TaskDetails({task,onChange}:{task:Task;onChange:(id:string,status:Status)=>void}) {
 return <Sheet><SheetTrigger asChild><Button variant="ghost" className="row-link" aria-label={`Open ${task.title}`}><span><span className="task-title">{task.title}</span><span className="task-subtitle">{task.project} · {task.id}</span></span><ChevronRight aria-hidden/></Button></SheetTrigger><SheetContent className="detail-panel" showCloseButton={false}>
 <div className="detail-top"><span className="eyebrow">WORK ITEM / {task.id}</span><SheetClose asChild><Button variant="outline">Close</Button></SheetClose></div>
 <SheetTitle className="detail-title">{task.title}</SheetTitle><SheetDescription>{task.description}</SheetDescription>
 <dl className="details"><div><dt>Project</dt><dd>{task.project}</dd></div><div><dt>Owner</dt><dd>{task.owner}</dd></div><div><dt>Due date</dt><dd>{task.due}</dd></div><div><dt>Status</dt><dd><StatusBadge status={task.status}/></dd></div></dl>
 <h3 className="section-title">Acceptance checklist</h3><ul className="checklist">{task.criteria.map(c=><li key={c}><span aria-hidden>—</span>{c}</li>)}</ul>
 <div className="detail-actions"><p className="muted">Changes stay in this tab and reset when you reload.</p><div className="action-row">{(['Ready','In progress','Done'] as Status[]).map(status=><Button key={status} variant={status==='Done'?'default':'outline'} disabled={task.status===status} onClick={()=>onChange(task.id,status)}>{status==='Done'?'Mark done':status==='Ready'?'Set ready':'Start work'}</Button>)}</div></div>
 </SheetContent></Sheet>;
}
export default function Home(){
 const [tasks,setTasks]=useState(initialTasks); const [query,setQuery]=useState(''); const [tab,setTab]=useState('All'); const [dark,setDark]=useState(false); const [notice,setNotice]=useState('');
 useEffect(()=>{const preference=localStorage.getItem('queue-theme'); const value=preference?preference==='dark':matchMedia('(prefers-color-scheme: dark)').matches;setDark(value); document.documentElement.classList.toggle('dark',value);document.documentElement.classList.toggle('light',!value);},[]);
 const latestTasks=useRef(tasks);
 useEffect(()=>{latestTasks.current=tasks;},[tasks]);
 useEffect(()=>registerQueueTools(()=>latestTasks.current,(q,s)=>flushSync(()=>{setQuery(q);setTab(s);})),[]);
 const toggleTheme=()=>{const value=!dark;setDark(value);document.documentElement.classList.toggle('dark',value);document.documentElement.classList.toggle('light',!value);localStorage.setItem('queue-theme',value?'dark':'light');};
 const update=(id:string,status:Status)=>{setTasks(current=>current.map(t=>t.id===id?{...t,status}:t));setNotice(`${id} is now ${status.toLowerCase()}.`);};
 const visible=filterTasks(tasks,query,tab); const counts={Ready:tasks.filter(t=>t.status==='Ready').length,'In progress':tasks.filter(t=>t.status==='In progress').length,Done:tasks.filter(t=>t.status==='Done').length};
 return <><a className="skip-link" href="#queue">Skip to work queue</a><header className="masthead"><div className="brand"><Layers3 aria-hidden/><strong>WORKROOM</strong><span className="brand-divider"/><span>Project operations</span></div><Button variant="ghost" onClick={toggleTheme} className="theme-control" aria-label={`Switch to ${dark?'light':'dark'} theme`}>{dark?<Sun aria-hidden/>:<Moon aria-hidden/>}<span>{dark?'Light':'Dark'}</span></Button></header>
 <main className="workspace"><div className="page-heading"><div><p className="eyebrow">OPERATIONS / WORK QUEUE</p><h1>Keep the work moving.</h1><p className="muted intro">A shared view of what’s ready, moving, and finished.</p></div><div className="sample-label"><span className="sample-mark">DEMO</span><span>Fictional project data<br/>September 10, 2026</span></div></div>
 <section className="metrics" aria-label="Queue summary">{Object.entries(counts).map(([label,count],i)=><Card key={label} className="metric"><span className="metric-number">{String(count).padStart(2,'0')}</span><div><h2>{label}</h2><p>{['Ready for a handoff','Work happening now','Accepted and complete'][i]}</p></div></Card>)}</section>
 <section id="queue" className="queue-panel" aria-label="Work queue"><div className="queue-heading"><h2>Work queue <span>{tasks.length}</span></h2><Button variant="outline" onClick={()=>{setTasks(initialTasks);setQuery('');setTab('All');setNotice('Demo data and filters reset.');}}><RotateCcw aria-hidden/>Reset demo</Button></div>
 <Tabs value={tab} onValueChange={setTab}><div className="toolbar"><TabsList aria-label="Filter by status" variant="line" className="status-tabs">{['All','Ready','In progress','Done'].map(s=><TabsTrigger key={s} value={s}>{s}</TabsTrigger>)}</TabsList><div className="search"><label htmlFor="search" className="sr-only">Search work by title, project, owner, or ID</label><Search aria-hidden/><Input id="search" type="search" placeholder="Search work…" value={query} onChange={e=>setQuery(e.target.value)}/></div></div>
 {['All','Ready','In progress','Done'].map(s=><TabsContent key={s} value={s}><div className="queue-columns" aria-hidden><span>WORK ITEM</span><span>OWNER</span><span>DUE</span><span>STATUS</span></div><ul className="work-list">{visible.map(task=><li key={task.id} className="work-row"><TaskDetails task={task} onChange={update}/><div className="owner"><span className="avatar" aria-hidden>{task.owner.split(' ').map(n=>n[0]).join('')}</span><span>{task.owner}</span></div><span className="due"><span className="mobile-label">Due </span>{task.due}</span><StatusBadge status={task.status}/></li>)}</ul>{!visible.length&&<Empty className="empty-state"><EmptyHeader><Inbox aria-hidden/><EmptyTitle>No matching work</EmptyTitle><EmptyDescription>Try another search or show all statuses.</EmptyDescription></EmptyHeader><Button variant="outline" onClick={()=>{setQuery('');setTab('All');}}>Clear filters</Button></Empty>}<div className="queue-footer"><span aria-live="polite">{visible.length} of {tasks.length} items</span><span>Open a work item to view details</span></div></TabsContent>)}
 </Tabs></section><p role="status" className="notice" aria-live="polite">{notice}</p><footer className="sample-footer"><span>React + TypeScript portfolio sample · AI-created</span><a href="https://github.com/Reeyenn/paid-work-samples">Source and implementation notes <ChevronRight aria-hidden/></a></footer></main></>;
}
