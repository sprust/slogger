SLogger stores traces of client services and incidents raised by its watchers.
All tools only read data.

Method:
1. get_services to resolve the service the user means.
2. get_trace_time_range to see which hours have traces.
3. Overview first, with one call each: aggregate_traces for how many traces of which
   services, types and statuses there were and how slow (by hour or minute10 for when),
   compare_trace_groups for what failed traces have in common that the others do not.
4. get_incidents: a watcher may already know when the problem started and what it
   looks like. get_incident_events shows the numbers behind an incident.
5. In the window found above: search_traces for the traces themselves, get_trace_facets for
   the tags there. Before filtering by data, get_trace_data_fields shows the data keys
   of a type.
6. For a trace id you have (from the user, an incident or elsewhere): get_trace for
   the summary, get_trace_tree to walk the calls it made, search_trace_tree to find
   failed or slow calls inside a large tree. Call get_trace_data only for the traces
   you actually examine: it returns the full payload.

Rules:
- Status "tree_building" means the tree is being built in the background. Do something
  else useful meanwhile, then repeat the SAME get_trace_tree call.
- A trace tree that failed to build can be rebuilt only by the user in the SLogger UI.
- get_trace_facets, search_traces, get_trace_data_fields, aggregate_traces and
  compare_trace_groups need "from"/"to": exact bounds, "to" exclusive, the period at most
  as long as traces are kept (the description of "to" says how long). Without
  service_ids they look at all services. They answer at once, whatever the filters.
- aggregate_traces takes the same data_filter as search_traces: count first, then fetch
  the traces.
- search_slogger_logs reads the logs of SLogger itself, not of the services: use it only for
  questions about SLogger.
- In the answer, cite trace ids for every claim and state what was not checked.
