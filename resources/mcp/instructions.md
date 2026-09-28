SLogger stores traces of client services and incidents raised by its watchers.
All tools only read data.

Method:
1. list_services to resolve the service the user means.
2. get_data_range to see which hours have traces.
3. Overview first, with one call each: top_trace_groups for how many traces of which
   services, types and statuses there were and how slow (by hour or minute10 for when),
   compare_trace_groups for what failed traces have in common that the others do not.
4. list_incidents: a watcher may already know when the problem started and what it
   looks like. get_incident_events shows the numbers behind an incident.
5. In the window found above: find_traces for the traces themselves, trace_facets for
   the tags there. Before filtering by data, list_trace_data_fields shows the data keys
   of a type.
6. For a trace id you have (from the user, an incident or elsewhere): get_trace for
   the summary, get_trace_tree to walk the calls it made, find_in_trace_tree to find
   failed or slow calls inside a large tree. Call get_trace_data only for the traces
   you actually examine: it returns the full payload.

Rules:
- Status "tree_building" means the tree is being built in the background. Do something
  else useful meanwhile, then repeat the SAME get_trace_tree call.
- A trace tree that failed to build can be rebuilt only by the user in the SLogger UI.
- trace_facets, find_traces, list_trace_data_fields, top_trace_groups and
  compare_trace_groups build a trace index for the set of filters and the hours of the
  period. To compare services, types or statuses change the filter values and keep the
  same set of filters and hours: the index is reused. top_trace_groups and find_traces
  with the same filters share one. list_dynamic_indexes shows the indexes that exist.
- Status "index_building" works like "tree_building": do something else useful, then
  repeat the SAME call; get_index_status shows how far the index is.
- These tools need "from"/"to" of at most 24 hours, rounded to whole hours: moving the
  window inside the same hours is free, a new hour builds a new index. Without
  service_ids they look at all services.
- slogger_logs reads the logs of SLogger itself, not of the services: use it only for
  questions about SLogger.
- In the answer, cite trace ids for every claim and state what was not checked.
