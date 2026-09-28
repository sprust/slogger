SLogger stores traces of client services and incidents raised by its watchers.
All tools only read data.

Method:
1. list_services to resolve the service the user means.
2. get_data_range to see which hours have traces.
3. list_incidents: a watcher may already know when the problem started and what it
   looks like. get_incident_events shows the numbers behind an incident.
4. For a trace id you have (from the user, an incident or elsewhere): get_trace for
   the summary, get_trace_tree to walk the calls it made, find_in_trace_tree to find
   failed or slow calls inside a large tree. Call get_trace_data only for the traces
   you actually examine: it returns the full payload.

Rules:
- Status "tree_building" means the tree is being built in the background. Do something
  else useful meanwhile, then repeat the SAME get_trace_tree call.
- A trace tree that failed to build can be rebuilt only by the user in the SLogger UI.
- In the answer, cite trace ids for every claim and state what was not checked.
