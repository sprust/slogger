/* eslint-disable */
/* tslint:disable */
/*
 * ---------------------------------------------------------------
 * ## THIS FILE WAS GENERATED VIA SWAGGER-TYPESCRIPT-API        ##
 * ##                                                           ##
 * ## AUTHOR: acacode                                           ##
 * ## SOURCE: https://github.com/acacode/swagger-typescript-api ##
 * ---------------------------------------------------------------
 */

export namespace AdminApi {
  /**
 * No description
 * @name AuthMeList
 * @request GET:/admin-api/auth/me
 * @secure
 * @response `200` `{
    data: {
    id: number,
    first_name: string,
    last_name?: string | null,
    email: string,
    api_token: string,

},

}` description
*/
  export namespace AuthMeList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        first_name: string;
        last_name?: string | null;
        email: string;
        api_token: string;
      };
    };
  } /**
 * No description
 * @name AuthLoginCreate
 * @request POST:/admin-api/auth/login
 * @response `200` `{
    data: {
    id: number,
    first_name: string,
    last_name?: string | null,
    email: string,
    api_token: string,

},

}` description
*/
  export namespace AuthLoginCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /** @format email */
      email: string;
      /**
       * @minLength 5
       * @maxLength 50
       */
      password: string;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        first_name: string;
        last_name?: string | null;
        email: string;
        api_token: string;
      };
    };
  } /**
 * No description
 * @name DashboardDatabaseList
 * @request GET:/admin-api/dashboard/database
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    size: number,
    total_documents_count: number,
    memory_usage: number,
    collections: ({
    name: string,
    size: number,
    indexes_size: number,
    total_size: number,
    count: number,
    avg_obj_size: number,
    indexes: ({
    name: string,
    size: number,
    usage: number,

})[],

})[],

})[],

}` description
*/
  export namespace DashboardDatabaseList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        name: string;
        size: number;
        total_documents_count: number;
        memory_usage: number;
        collections: {
          name: string;
          size: number;
          indexes_size: number;
          total_size: number;
          count: number;
          avg_obj_size: number;
          indexes: {
            name: string;
            size: number;
            usage: number;
          }[];
        }[];
      }[];
    };
  } /**
 * No description
 * @name ToolsLinksList
 * @request GET:/admin-api/tools/links
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    url: string,

})[],

}` description
*/
  export namespace ToolsLinksList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        name: string;
        url: string;
      }[];
    };
  } /**
 * No description
 * @name ServicesList
 * @request GET:/admin-api/services
 * @secure
 * @response `200` `{
    data: ({
    id: number,
    name: string,

})[],

}` description
*/
  export namespace ServicesList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        name: string;
      }[];
    };
  } /**
 * No description
 * @name TraceAggregatorTracesCreate
 * @request POST:/admin-api/trace-aggregator/traces
 * @secure
 * @response `200` `{
    data: {
    items: ({
    trace: {
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    has_profiling: boolean,
    additional_fields: ({
    key: string,
    values: (string)[],

})[],
    logged_at: string,
    created_at: string,
    updated_at: string,

},

})[],
    paginator: {
    total: number,
    per_page: number,
    current_page: number,
    total_pages: number,

},

},

}` description
*/
  export namespace TraceAggregatorTracesCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /** @min 1 */
      page: number;
      /** @min 1 */
      per_page?: number;
      service_ids?: number[];
      trace_id?: string | null;
      all_traces_in_tree?: boolean;
      logging_from_preset?: "one_hour" | "two_hours" | "four_hours" | "six_hours";
      /** @format date */
      logging_from?: string;
      /** @format date */
      logging_to?: string;
      types?: string[];
      tags?: string[];
      statuses?: string[];
      /** @format float */
      duration_from?: number | null;
      /** @format float */
      duration_to?: number | null;
      /** @format float */
      memory_from?: number | null;
      /** @format float */
      memory_to?: number | null;
      /** @format float */
      cpu_from?: number | null;
      /** @format float */
      cpu_to?: number | null;
      data?: {
        filter?: {
          field?: string;
          null?: boolean;
          numeric?: {
            /** @format float */
            value?: number;
            comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
          };
          string?: {
            value?: string;
            comp?: "equals" | "contains" | "starts" | "ends";
          };
          boolean?: {
            value?: boolean;
          };
        }[];
        fields?: string[];
      };
      has_profiling?: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        items: {
          trace: {
            service?: {
              id: number;
              name: string;
            };
            trace_id: string;
            parent_trace_id?: string | null;
            type: string;
            status: string;
            tags: string[];
            duration?: number | null;
            memory?: number | null;
            cpu?: number | null;
            has_profiling: boolean;
            additional_fields: {
              key: string;
              values: string[];
            }[];
            logged_at: string;
            created_at: string;
            updated_at: string;
          };
        }[];
        paginator: {
          total: number;
          per_page: number;
          current_page: number;
          total_pages: number;
        };
      };
    };
  } /**
 * No description
 * @name TraceAggregatorTracesDetail
 * @request GET:/admin-api/trace-aggregator/traces/{traceId}
 * @secure
 * @response `200` `{
    data: {
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    data: {
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
  \** @maxItems 0 *\
    children?: (string)[] | null,

})[],

})[],

})[],

})[],

})[],

},
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace TraceAggregatorTracesDetail {
    export type RequestParams = {
      traceId: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        service?: {
          id: number;
          name: string;
        };
        trace_id: string;
        parent_trace_id?: string | null;
        type: string;
        status: string;
        tags: string[];
        data: {
          key: string;
          value: string;
          children?: {
            key: string;
            value: string;
            children?: {
              key: string;
              value: string;
              children?: {
                key: string;
                value: string;
                children?: {
                  key: string;
                  value: string;
                  children?: {
                    key: string;
                    value: string;
                    /** @maxItems 0 */
                    children?: string[] | null;
                  }[];
                }[];
              }[];
            }[];
          }[];
        };
        duration?: number | null;
        memory?: number | null;
        cpu?: number | null;
        logged_at: string;
        created_at: string;
        updated_at: string;
      };
    };
  } /**
 * No description
 * @name TraceAggregatorTracesTreeDetail
 * @request GET:/admin-api/trace-aggregator/traces/{traceId}/tree
 * @secure
 * @response `200` `{
    data: {
    tracesCount: number,
    items: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    children: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    children: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    children: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    children: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
  \** @maxItems 0 *\
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
  \** @maxItems 0 *\
    children: (string)[],
    depth: number,

})[],
    depth: number,

})[],
    depth: number,

})[],
    depth: number,

})[],
    depth: number,

})[],

},

}` description
*/
  export namespace TraceAggregatorTracesTreeDetail {
    export type RequestParams = {
      traceId: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        tracesCount: number;
        items: {
          service?: {
            id: number;
            name: string;
          };
          trace_id: string;
          parent_trace_id?: string | null;
          type: string;
          status: string;
          tags: string[];
          duration?: number | null;
          memory?: number | null;
          cpu?: number | null;
          logged_at: string;
          children: {
            service?: {
              id: number;
              name: string;
            };
            trace_id: string;
            parent_trace_id?: string | null;
            type: string;
            status: string;
            tags: string[];
            duration?: number | null;
            memory?: number | null;
            cpu?: number | null;
            logged_at: string;
            children: {
              service?: {
                id: number;
                name: string;
              };
              trace_id: string;
              parent_trace_id?: string | null;
              type: string;
              status: string;
              tags: string[];
              duration?: number | null;
              memory?: number | null;
              cpu?: number | null;
              logged_at: string;
              children: {
                service?: {
                  id: number;
                  name: string;
                };
                trace_id: string;
                parent_trace_id?: string | null;
                type: string;
                status: string;
                tags: string[];
                duration?: number | null;
                memory?: number | null;
                cpu?: number | null;
                logged_at: string;
                children: {
                  service?: {
                    id: number;
                    name: string;
                  };
                  trace_id: string;
                  parent_trace_id?: string | null;
                  type: string;
                  status: string;
                  /** @maxItems 0 */
                  tags: string[];
                  duration?: number | null;
                  memory?: number | null;
                  cpu?: number | null;
                  logged_at: string;
                  /** @maxItems 0 */
                  children: string[];
                  depth: number;
                }[];
                depth: number;
              }[];
              depth: number;
            }[];
            depth: number;
          }[];
          depth: number;
        }[];
      };
    };
  } /**
 * No description
 * @name TraceAggregatorTracesProfilingCreate
 * @request POST:/admin-api/trace-aggregator/traces/{traceId}/profiling
 * @secure
 * @response `200` `{
    data: {
    nodes: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
  \** @maxItems 0 *\
    data: (string)[],
    recursionNodeId?: number | null,
  \** @maxItems 0 *\
    children?: (string)[] | null,

})[],

})[],

})[],

})[],

})[],

})[],

},

}` description
*/
  export namespace TraceAggregatorTracesProfilingCreate {
    export type RequestParams = {
      traceId: any;
    };
    export type RequestQuery = {};
    export type RequestBody = {
      /** @minLength 1 */
      caller?: string | null;
      excluded_callers?: string[];
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        nodes: {
          id: number;
          calling: string;
          data: {
            name: string;
            value: number;
            weight_percent: number;
          }[];
          recursionNodeId?: number | null;
          children?: {
            id: number;
            calling: string;
            data: {
              name: string;
              value: number;
              weight_percent: number;
            }[];
            recursionNodeId?: number | null;
            children?: {
              id: number;
              calling: string;
              data: {
                name: string;
                value: number;
                weight_percent: number;
              }[];
              recursionNodeId?: number | null;
              children?: {
                id: number;
                calling: string;
                data: {
                  name: string;
                  value: number;
                  weight_percent: number;
                }[];
                recursionNodeId?: number | null;
                children?: {
                  id: number;
                  calling: string;
                  data: {
                    name: string;
                    value: number;
                    weight_percent: number;
                  }[];
                  recursionNodeId?: number | null;
                  children?: {
                    id: number;
                    calling: string;
                    /** @maxItems 0 */
                    data: string[];
                    recursionNodeId?: number | null;
                    /** @maxItems 0 */
                    children?: string[] | null;
                  }[];
                }[];
              }[];
            }[];
          }[];
        }[];
      };
    };
  } /**
 * No description
 * @name TraceAggregatorTracesContentTypesCreate
 * @request POST:/admin-api/trace-aggregator/traces-content/types
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    count: number,

})[],

}` description
*/
  export namespace TraceAggregatorTracesContentTypesCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      service_ids?: number[];
      /** @minLength 1 */
      text?: string | null;
      logging_from_preset?: "one_hour" | "two_hours" | "four_hours" | "six_hours";
      /** @format date */
      logging_from?: string;
      /** @format date */
      logging_to?: string;
      /** @format float */
      duration_from?: number | null;
      /** @format float */
      duration_to?: number | null;
      /** @format float */
      memory_from?: number | null;
      /** @format float */
      memory_to?: number | null;
      /** @format float */
      cpu_from?: number | null;
      /** @format float */
      cpu_to?: number | null;
      data?: {
        filter?: {
          field?: string;
          null?: boolean;
          numeric?: {
            /** @format float */
            value?: number;
            comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
          };
          string?: {
            value?: string;
            comp?: "equals" | "contains" | "starts" | "ends";
          };
          boolean?: {
            value?: boolean;
          };
        }[];
      };
      has_profiling?: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        name: string;
        count: number;
      }[];
    };
  } /**
 * No description
 * @name TraceAggregatorTracesContentTagsCreate
 * @request POST:/admin-api/trace-aggregator/traces-content/tags
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    count: number,

})[],

}` description
*/
  export namespace TraceAggregatorTracesContentTagsCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      service_ids?: number[];
      /** @minLength 1 */
      text?: string | null;
      types?: string[];
      logging_from_preset?: "one_hour" | "two_hours" | "four_hours" | "six_hours";
      /** @format date */
      logging_from?: string;
      /** @format date */
      logging_to?: string;
      /** @format float */
      duration_from?: number | null;
      /** @format float */
      duration_to?: number | null;
      /** @format float */
      memory_from?: number | null;
      /** @format float */
      memory_to?: number | null;
      /** @format float */
      cpu_from?: number | null;
      /** @format float */
      cpu_to?: number | null;
      data?: {
        filter?: {
          field?: string;
          null?: boolean;
          numeric?: {
            /** @format float */
            value?: number;
            comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
          };
          string?: {
            value?: string;
            comp?: "equals" | "contains" | "starts" | "ends";
          };
          boolean?: {
            value?: boolean;
          };
        }[];
      };
      has_profiling?: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        name: string;
        count: number;
      }[];
    };
  } /**
 * No description
 * @name TraceAggregatorTracesContentStatusesCreate
 * @request POST:/admin-api/trace-aggregator/traces-content/statuses
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    count: number,

})[],

}` description
*/
  export namespace TraceAggregatorTracesContentStatusesCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      service_ids?: number[];
      /** @minLength 1 */
      text?: string | null;
      types?: string[];
      tags?: string[];
      logging_from_preset?: "one_hour" | "two_hours" | "four_hours" | "six_hours";
      /** @format date */
      logging_from?: string;
      /** @format date */
      logging_to?: string;
      /** @format float */
      duration_from?: number | null;
      /** @format float */
      duration_to?: number | null;
      /** @format float */
      memory_from?: number | null;
      /** @format float */
      memory_to?: number | null;
      /** @format float */
      cpu_from?: number | null;
      /** @format float */
      cpu_to?: number | null;
      data?: {
        filter?: {
          field?: string;
          null?: boolean;
          numeric?: {
            /** @format float */
            value?: number;
            comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
          };
          string?: {
            value?: string;
            comp?: "equals" | "contains" | "starts" | "ends";
          };
          boolean?: {
            value?: boolean;
          };
        }[];
      };
      has_profiling?: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        name: string;
        count: number;
      }[];
    };
  } /**
 * No description
 * @name TraceAggregatorTraceMetricsCreate
 * @request POST:/admin-api/trace-aggregator/trace-metrics
 * @secure
 * @response `200` `{
    data: {
    loggedAtFrom: string,
    items: ({
    timestamp: string,
    timestamp_to: string,
    fields: ({
    field: string,
    indicators: ({
    name: string,
    value: number,

})[],

})[],

})[],

},

}` description
*/
  export namespace TraceAggregatorTraceMetricsCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      timestamp_period:
        | "5 minutes"
        | "30 minutes"
        | "1 hour"
        | "4 hours"
        | "12 hours"
        | "1 day"
        | "3 days"
        | "7 days"
        | "15 days"
        | "1 month"
        | "3 months"
        | "6 month"
        | "1 year";
      timestamp_step: "s5" | "s10" | "s30" | "min" | "min5" | "min10" | "min30" | "h" | "h4" | "h12" | "d" | "m";
      fields?: ("count" | "duration" | "memory" | "cpu")[];
      data_fields?: string[];
      service_ids?: number[];
      /** @format date */
      logging_to?: string;
      types?: string[];
      tags?: string[];
      statuses?: string[];
      /** @format float */
      duration_from?: number | null;
      /** @format float */
      duration_to?: number | null;
      /** @format float */
      memory_from?: number | null;
      /** @format float */
      memory_to?: number | null;
      /** @format float */
      cpu_from?: number | null;
      /** @format float */
      cpu_to?: number | null;
      data?: {
        filter?: {
          field?: string;
          null?: boolean;
          numeric?: {
            /** @format float */
            value?: number;
            comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
          };
          string?: {
            value?: string;
            comp?: "equals" | "contains" | "starts" | "ends";
          };
          boolean?: {
            value?: boolean;
          };
        }[];
      };
      has_profiling?: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        loggedAtFrom: string;
        items: {
          timestamp: string;
          timestamp_to: string;
          fields: {
            field: string;
            indicators: {
              name: string;
              value: number;
            }[];
          }[];
        }[];
      };
    };
  } /**
 * No description
 * @name TraceAggregatorTraceMetricsFieldsList
 * @request GET:/admin-api/trace-aggregator/trace-metrics/fields
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    value: string,

})[],

}` description
*/
  export namespace TraceAggregatorTraceMetricsFieldsList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        name: string;
        value: string;
      }[];
    };
  } /**
 * No description
 * @name TraceAggregatorTraceTimestampPeriodsList
 * @request GET:/admin-api/trace-aggregator/trace-timestamp-periods
 * @secure
 * @response `200` `{
    data: ({
    period: {
    name: string,
    value: string,

},
    timestamps: ({
    value: string,
    title: string,

})[],

})[],

}` description
*/
  export namespace TraceAggregatorTraceTimestampPeriodsList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        period: {
          name: string;
          value: string;
        };
        timestamps: {
          value: string;
          title: string;
        }[];
      }[];
    };
  } /**
 * No description
 * @name TraceAggregatorDynamicIndexesList
 * @request GET:/admin-api/trace-aggregator/dynamic-indexes
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    name: string,
    indexName: string,
    collectionNames: (string)[],
    fields: ({
    name: string,
    title: string,

})[],
    inProcess: boolean,
    created: boolean,
    error?: string | null,
    actualUntilAt: string,
    createdAt: string,

})[],

}` description
*/
  export namespace TraceAggregatorDynamicIndexesList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: string;
        name: string;
        indexName: string;
        collectionNames: string[];
        fields: {
          name: string;
          title: string;
        }[];
        inProcess: boolean;
        created: boolean;
        error?: string | null;
        actualUntilAt: string;
        createdAt: string;
      }[];
    };
  } /**
 * No description
 * @name TraceAggregatorDynamicIndexesStatsList
 * @request GET:/admin-api/trace-aggregator/dynamic-indexes/stats
 * @secure
 * @response `200` `{
    data: {
    in_process_count: number,
    errors_count: number,
    total_count: number,
    indexes_in_process: ({
    collectionName: string,
    name: string,
    progress: number,

})[],

},

}` description
*/
  export namespace TraceAggregatorDynamicIndexesStatsList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        in_process_count: number;
        errors_count: number;
        total_count: number;
        indexes_in_process: {
          collectionName: string;
          name: string;
          progress: number;
        }[];
      };
    };
  }
  /**
   * No description
   * @name TraceAggregatorDynamicIndexesDelete
   * @request DELETE:/admin-api/trace-aggregator/dynamic-indexes/{id}
   * @secure
   * @response `200` `void` description
   */
  export namespace TraceAggregatorDynamicIndexesDelete {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = void;
  } /**
 * No description
 * @name TraceAggregatorStatesList
 * @request GET:/admin-api/trace-aggregator/states
 * @secure
 * @response `200` `{
    data: {
    items: ({
    id: string,
    title: string,
    store_version: number,
    store_data: string,
    created_at: string,

})[],
    paginator: {
    total: number,
    per_page: number,
    current_page: number,
    total_pages: number,

},

},

}` description
*/
  export namespace TraceAggregatorStatesList {
    export type RequestParams = {};
    export type RequestQuery = {
      /** @min 1 */
      page: number;
      /** @min 1 */
      version: number;
      search_query?: string | null;
      auto: boolean;
    };
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        items: {
          id: string;
          title: string;
          store_version: number;
          store_data: string;
          created_at: string;
        }[];
        paginator: {
          total: number;
          per_page: number;
          current_page: number;
          total_pages: number;
        };
      };
    };
  } /**
 * No description
 * @name TraceAggregatorStatesCreate
 * @request POST:/admin-api/trace-aggregator/states
 * @secure
 * @response `200` `{
    data: {
    id: string,
    title: string,
    store_version: number,
    store_data: string,
    created_at: string,

},

}` description
*/
  export namespace TraceAggregatorStatesCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 2000
       */
      title: string;
      /** @min 1 */
      store_version: number;
      store_data: string;
      auto: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: string;
        title: string;
        store_version: number;
        store_data: string;
        created_at: string;
      };
    };
  }
  /**
   * No description
   * @name TraceAggregatorStatesDelete
   * @request DELETE:/admin-api/trace-aggregator/states/{id}
   * @secure
   * @response `200` `void` description
   */
  export namespace TraceAggregatorStatesDelete {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = void;
  } /**
 * No description
 * @name TraceCleanerSettingsList
 * @request GET:/admin-api/trace-cleaner/settings
 * @secure
 * @response `200` `{
    data: ({
    id: number,
    days_lifetime: number,
    type?: string | null,
    only_data: boolean,
    deleted: boolean,
    created_at: string,
    updated_at: string,

})[],

}` description
*/
  export namespace TraceCleanerSettingsList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        days_lifetime: number;
        type?: string | null;
        only_data: boolean;
        deleted: boolean;
        created_at: string;
        updated_at: string;
      }[];
    };
  } /**
 * No description
 * @name TraceCleanerSettingsCreate
 * @request POST:/admin-api/trace-cleaner/settings
 * @secure
 * @response `200` `{
    data: {
    id: number,
    days_lifetime: number,
    type?: string | null,
    only_data: boolean,
    deleted: boolean,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace TraceCleanerSettingsCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /** @min 1 */
      days_life_time: number;
      type?: string | null;
      only_data: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        days_lifetime: number;
        type?: string | null;
        only_data: boolean;
        deleted: boolean;
        created_at: string;
        updated_at: string;
      };
    };
  } /**
 * No description
 * @name TraceCleanerSettingsPartialUpdate
 * @request PATCH:/admin-api/trace-cleaner/settings/{settingId}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    days_lifetime: number,
    type?: string | null,
    only_data: boolean,
    deleted: boolean,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace TraceCleanerSettingsPartialUpdate {
    export type RequestParams = {
      settingId: any;
    };
    export type RequestQuery = {};
    export type RequestBody = {
      /** @min 1 */
      days_life_time: number;
      only_data: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        days_lifetime: number;
        type?: string | null;
        only_data: boolean;
        deleted: boolean;
        created_at: string;
        updated_at: string;
      };
    };
  }
  /**
   * No description
   * @name TraceCleanerSettingsDelete
   * @request DELETE:/admin-api/trace-cleaner/settings/{settingId}
   * @secure
   * @response `200` `void` description
   */
  export namespace TraceCleanerSettingsDelete {
    export type RequestParams = {
      settingId: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = void;
  } /**
 * No description
 * @name TraceCleanerSettingsProcessesDetail
 * @request GET:/admin-api/trace-cleaner/settings/{settingId}/processes
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    setting_id: number,
    cleared_count: number,
    error?: string | null,
    cleared_at?: string | null,
    created_at: string,
    updated_at: string,

})[],

}` description
*/
  export namespace TraceCleanerSettingsProcessesDetail {
    export type RequestParams = {
      settingId: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: string;
        setting_id: number;
        cleared_count: number;
        error?: string | null;
        cleared_at?: string | null;
        created_at: string;
        updated_at: string;
      }[];
    };
  } /**
 * No description
 * @name LogsList
 * @request GET:/admin-api/logs
 * @secure
 * @response `200` `{
    data: {
    items: ({
    level: string,
    message: string,
    context: string,
    channel: string,
    logged_at: string,

})[],
    paginator: {
    total: number,
    per_page: number,
    current_page: number,
    total_pages: number,

},

},

}` description
*/
  export namespace LogsList {
    export type RequestParams = {};
    export type RequestQuery = {
      /** @min 1 */
      page: number;
      /** @maxLength 255 */
      search_query?: string | null;
      /** @maxLength 255 */
      level?: string | null;
    };
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        items: {
          level: string;
          message: string;
          context: string;
          channel: string;
          logged_at: string;
        }[];
        paginator: {
          total: number;
          per_page: number;
          current_page: number;
          total_pages: number;
        };
      };
    };
  }
}

export type QueryParamsType = Record<string | number, any>;
export type ResponseFormat = keyof Omit<Body, "body" | "bodyUsed">;

export interface FullRequestParams extends Omit<RequestInit, "body"> {
  /** set parameter to `true` for call `securityWorker` for this request */
  secure?: boolean;
  /** request path */
  path: string;
  /** content type of request body */
  type?: ContentType;
  /** query params */
  query?: QueryParamsType;
  /** format of response (i.e. response.json() -> format: "json") */
  format?: ResponseFormat;
  /** request body */
  body?: unknown;
  /** base url */
  baseUrl?: string;
  /** request cancellation token */
  cancelToken?: CancelToken;
}

export type RequestParams = Omit<FullRequestParams, "body" | "method" | "query" | "path">;

export interface ApiConfig<SecurityDataType = unknown> {
  baseUrl?: string;
  baseApiParams?: Omit<RequestParams, "baseUrl" | "cancelToken" | "signal">;
  securityWorker?: (securityData: SecurityDataType | null) => Promise<RequestParams | void> | RequestParams | void;
  customFetch?: typeof fetch;
}

export interface HttpResponse<D extends unknown, E extends unknown = unknown> extends Response {
  data: D;
  error: E;
}

type CancelToken = Symbol | string | number;

export enum ContentType {
  Json = "application/json",
  FormData = "multipart/form-data",
  UrlEncoded = "application/x-www-form-urlencoded",
  Text = "text/plain",
}

export class HttpClient<SecurityDataType = unknown> {
  public baseUrl: string = "";
  private securityData: SecurityDataType | null = null;
  private securityWorker?: ApiConfig<SecurityDataType>["securityWorker"];
  private abortControllers = new Map<CancelToken, AbortController>();
  private customFetch = (...fetchParams: Parameters<typeof fetch>) => fetch(...fetchParams);

  private baseApiParams: RequestParams = {
    credentials: "same-origin",
    headers: {},
    redirect: "follow",
    referrerPolicy: "no-referrer",
  };

  constructor(apiConfig: ApiConfig<SecurityDataType> = {}) {
    Object.assign(this, apiConfig);
  }

  public setSecurityData = (data: SecurityDataType | null) => {
    this.securityData = data;
  };

  protected encodeQueryParam(key: string, value: any) {
    const encodedKey = encodeURIComponent(key);
    return `${encodedKey}=${encodeURIComponent(typeof value === "number" ? value : `${value}`)}`;
  }

  protected addQueryParam(query: QueryParamsType, key: string) {
    return this.encodeQueryParam(key, query[key]);
  }

  protected addArrayQueryParam(query: QueryParamsType, key: string) {
    const value = query[key];
    return value.map((v: any) => this.encodeQueryParam(key, v)).join("&");
  }

  protected toQueryString(rawQuery?: QueryParamsType): string {
    const query = rawQuery || {};
    const keys = Object.keys(query).filter((key) => "undefined" !== typeof query[key]);
    return keys
      .map((key) => (Array.isArray(query[key]) ? this.addArrayQueryParam(query, key) : this.addQueryParam(query, key)))
      .join("&");
  }

  protected addQueryParams(rawQuery?: QueryParamsType): string {
    const queryString = this.toQueryString(rawQuery);
    return queryString ? `?${queryString}` : "";
  }

  private contentFormatters: Record<ContentType, (input: any) => any> = {
    [ContentType.Json]: (input: any) =>
      input !== null && (typeof input === "object" || typeof input === "string") ? JSON.stringify(input) : input,
    [ContentType.Text]: (input: any) => (input !== null && typeof input !== "string" ? JSON.stringify(input) : input),
    [ContentType.FormData]: (input: any) =>
      Object.keys(input || {}).reduce((formData, key) => {
        const property = input[key];
        formData.append(
          key,
          property instanceof Blob
            ? property
            : typeof property === "object" && property !== null
            ? JSON.stringify(property)
            : `${property}`,
        );
        return formData;
      }, new FormData()),
    [ContentType.UrlEncoded]: (input: any) => this.toQueryString(input),
  };

  protected mergeRequestParams(params1: RequestParams, params2?: RequestParams): RequestParams {
    return {
      ...this.baseApiParams,
      ...params1,
      ...(params2 || {}),
      headers: {
        ...(this.baseApiParams.headers || {}),
        ...(params1.headers || {}),
        ...((params2 && params2.headers) || {}),
      },
    };
  }

  protected createAbortSignal = (cancelToken: CancelToken): AbortSignal | undefined => {
    if (this.abortControllers.has(cancelToken)) {
      const abortController = this.abortControllers.get(cancelToken);
      if (abortController) {
        return abortController.signal;
      }
      return void 0;
    }

    const abortController = new AbortController();
    this.abortControllers.set(cancelToken, abortController);
    return abortController.signal;
  };

  public abortRequest = (cancelToken: CancelToken) => {
    const abortController = this.abortControllers.get(cancelToken);

    if (abortController) {
      abortController.abort();
      this.abortControllers.delete(cancelToken);
    }
  };

  public request = async <T = any, E = any>({
    body,
    secure,
    path,
    type,
    query,
    format,
    baseUrl,
    cancelToken,
    ...params
  }: FullRequestParams): Promise<HttpResponse<T, E>> => {
    const secureParams =
      ((typeof secure === "boolean" ? secure : this.baseApiParams.secure) &&
        this.securityWorker &&
        (await this.securityWorker(this.securityData))) ||
      {};
    const requestParams = this.mergeRequestParams(params, secureParams);
    const queryString = query && this.toQueryString(query);
    const payloadFormatter = this.contentFormatters[type || ContentType.Json];
    const responseFormat = format || requestParams.format;

    return this.customFetch(`${baseUrl || this.baseUrl || ""}${path}${queryString ? `?${queryString}` : ""}`, {
      ...requestParams,
      headers: {
        ...(requestParams.headers || {}),
        ...(type && type !== ContentType.FormData ? { "Content-Type": type } : {}),
      },
      signal: cancelToken ? this.createAbortSignal(cancelToken) : requestParams.signal,
      body: typeof body === "undefined" || body === null ? null : payloadFormatter(body),
    }).then(async (response) => {
      const r = response as HttpResponse<T, E>;
      r.data = null as unknown as T;
      r.error = null as unknown as E;

      const data = !responseFormat
        ? r
        : await response[responseFormat]()
            .then((data) => {
              if (r.ok) {
                r.data = data;
              } else {
                r.error = data;
              }
              return r;
            })
            .catch((e) => {
              r.error = e;
              return r;
            });

      if (cancelToken) {
        this.abortControllers.delete(cancelToken);
      }

      if (!response.ok) throw data;
      return data;
    });
  };
}

/**
 * @title slogger-api-scheme
 * @version 0.1
 */
export class Api<SecurityDataType extends unknown> extends HttpClient<SecurityDataType> {
  adminApi = {
    /**
 * No description
 *
 * @name AuthMeList
 * @request GET:/admin-api/auth/me
 * @secure
 * @response `200` `{
    data: {
    id: number,
    first_name: string,
    last_name?: string | null,
    email: string,
    api_token: string,

},

}` description
 */
    authMeList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            first_name: string;
            last_name?: string | null;
            email: string;
            api_token: string;
          };
        },
        any
      >({
        path: `/admin-api/auth/me`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name AuthLoginCreate
 * @request POST:/admin-api/auth/login
 * @response `200` `{
    data: {
    id: number,
    first_name: string,
    last_name?: string | null,
    email: string,
    api_token: string,

},

}` description
 */
    authLoginCreate: (
      data: {
        /** @format email */
        email: string;
        /**
         * @minLength 5
         * @maxLength 50
         */
        password: string;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: number;
            first_name: string;
            last_name?: string | null;
            email: string;
            api_token: string;
          };
        },
        any
      >({
        path: `/admin-api/auth/login`,
        method: "POST",
        body: data,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name DashboardDatabaseList
 * @request GET:/admin-api/dashboard/database
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    size: number,
    total_documents_count: number,
    memory_usage: number,
    collections: ({
    name: string,
    size: number,
    indexes_size: number,
    total_size: number,
    count: number,
    avg_obj_size: number,
    indexes: ({
    name: string,
    size: number,
    usage: number,

})[],

})[],

})[],

}` description
 */
    dashboardDatabaseList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            name: string;
            size: number;
            total_documents_count: number;
            memory_usage: number;
            collections: {
              name: string;
              size: number;
              indexes_size: number;
              total_size: number;
              count: number;
              avg_obj_size: number;
              indexes: {
                name: string;
                size: number;
                usage: number;
              }[];
            }[];
          }[];
        },
        any
      >({
        path: `/admin-api/dashboard/database`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name ToolsLinksList
 * @request GET:/admin-api/tools/links
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    url: string,

})[],

}` description
 */
    toolsLinksList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            name: string;
            url: string;
          }[];
        },
        any
      >({
        path: `/admin-api/tools/links`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name ServicesList
 * @request GET:/admin-api/services
 * @secure
 * @response `200` `{
    data: ({
    id: number,
    name: string,

})[],

}` description
 */
    servicesList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            name: string;
          }[];
        },
        any
      >({
        path: `/admin-api/services`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTracesCreate
 * @request POST:/admin-api/trace-aggregator/traces
 * @secure
 * @response `200` `{
    data: {
    items: ({
    trace: {
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    has_profiling: boolean,
    additional_fields: ({
    key: string,
    values: (string)[],

})[],
    logged_at: string,
    created_at: string,
    updated_at: string,

},

})[],
    paginator: {
    total: number,
    per_page: number,
    current_page: number,
    total_pages: number,

},

},

}` description
 */
    traceAggregatorTracesCreate: (
      data: {
        /** @min 1 */
        page: number;
        /** @min 1 */
        per_page?: number;
        service_ids?: number[];
        trace_id?: string | null;
        all_traces_in_tree?: boolean;
        logging_from_preset?: "one_hour" | "two_hours" | "four_hours" | "six_hours";
        /** @format date */
        logging_from?: string;
        /** @format date */
        logging_to?: string;
        types?: string[];
        tags?: string[];
        statuses?: string[];
        /** @format float */
        duration_from?: number | null;
        /** @format float */
        duration_to?: number | null;
        /** @format float */
        memory_from?: number | null;
        /** @format float */
        memory_to?: number | null;
        /** @format float */
        cpu_from?: number | null;
        /** @format float */
        cpu_to?: number | null;
        data?: {
          filter?: {
            field?: string;
            null?: boolean;
            numeric?: {
              /** @format float */
              value?: number;
              comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
            };
            string?: {
              value?: string;
              comp?: "equals" | "contains" | "starts" | "ends";
            };
            boolean?: {
              value?: boolean;
            };
          }[];
          fields?: string[];
        };
        has_profiling?: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            items: {
              trace: {
                service?: {
                  id: number;
                  name: string;
                };
                trace_id: string;
                parent_trace_id?: string | null;
                type: string;
                status: string;
                tags: string[];
                duration?: number | null;
                memory?: number | null;
                cpu?: number | null;
                has_profiling: boolean;
                additional_fields: {
                  key: string;
                  values: string[];
                }[];
                logged_at: string;
                created_at: string;
                updated_at: string;
              };
            }[];
            paginator: {
              total: number;
              per_page: number;
              current_page: number;
              total_pages: number;
            };
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces`,
        method: "POST",
        body: data,
        secure: true,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTracesDetail
 * @request GET:/admin-api/trace-aggregator/traces/{traceId}
 * @secure
 * @response `200` `{
    data: {
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    data: {
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
    children?: ({
    key: string,
    value: string,
  \** @maxItems 0 *\
    children?: (string)[] | null,

})[],

})[],

})[],

})[],

})[],

},
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    created_at: string,
    updated_at: string,

},

}` description
 */
    traceAggregatorTracesDetail: (traceId: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            service?: {
              id: number;
              name: string;
            };
            trace_id: string;
            parent_trace_id?: string | null;
            type: string;
            status: string;
            tags: string[];
            data: {
              key: string;
              value: string;
              children?: {
                key: string;
                value: string;
                children?: {
                  key: string;
                  value: string;
                  children?: {
                    key: string;
                    value: string;
                    children?: {
                      key: string;
                      value: string;
                      children?: {
                        key: string;
                        value: string;
                        /** @maxItems 0 */
                        children?: string[] | null;
                      }[];
                    }[];
                  }[];
                }[];
              }[];
            };
            duration?: number | null;
            memory?: number | null;
            cpu?: number | null;
            logged_at: string;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces/${traceId}`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTracesTreeDetail
 * @request GET:/admin-api/trace-aggregator/traces/{traceId}/tree
 * @secure
 * @response `200` `{
    data: {
    tracesCount: number,
    items: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    children: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    children: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    children: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
    children: ({
    service?: {
    id: number,
    name: string,

},
    trace_id: string,
    parent_trace_id?: string | null,
    type: string,
    status: string,
  \** @maxItems 0 *\
    tags: (string)[],
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,
  \** @maxItems 0 *\
    children: (string)[],
    depth: number,

})[],
    depth: number,

})[],
    depth: number,

})[],
    depth: number,

})[],
    depth: number,

})[],

},

}` description
 */
    traceAggregatorTracesTreeDetail: (traceId: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            tracesCount: number;
            items: {
              service?: {
                id: number;
                name: string;
              };
              trace_id: string;
              parent_trace_id?: string | null;
              type: string;
              status: string;
              tags: string[];
              duration?: number | null;
              memory?: number | null;
              cpu?: number | null;
              logged_at: string;
              children: {
                service?: {
                  id: number;
                  name: string;
                };
                trace_id: string;
                parent_trace_id?: string | null;
                type: string;
                status: string;
                tags: string[];
                duration?: number | null;
                memory?: number | null;
                cpu?: number | null;
                logged_at: string;
                children: {
                  service?: {
                    id: number;
                    name: string;
                  };
                  trace_id: string;
                  parent_trace_id?: string | null;
                  type: string;
                  status: string;
                  tags: string[];
                  duration?: number | null;
                  memory?: number | null;
                  cpu?: number | null;
                  logged_at: string;
                  children: {
                    service?: {
                      id: number;
                      name: string;
                    };
                    trace_id: string;
                    parent_trace_id?: string | null;
                    type: string;
                    status: string;
                    tags: string[];
                    duration?: number | null;
                    memory?: number | null;
                    cpu?: number | null;
                    logged_at: string;
                    children: {
                      service?: {
                        id: number;
                        name: string;
                      };
                      trace_id: string;
                      parent_trace_id?: string | null;
                      type: string;
                      status: string;
                      /** @maxItems 0 */
                      tags: string[];
                      duration?: number | null;
                      memory?: number | null;
                      cpu?: number | null;
                      logged_at: string;
                      /** @maxItems 0 */
                      children: string[];
                      depth: number;
                    }[];
                    depth: number;
                  }[];
                  depth: number;
                }[];
                depth: number;
              }[];
              depth: number;
            }[];
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces/${traceId}/tree`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTracesProfilingCreate
 * @request POST:/admin-api/trace-aggregator/traces/{traceId}/profiling
 * @secure
 * @response `200` `{
    data: {
    nodes: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
    data: ({
    name: string,
    value: number,
    weight_percent: number,

})[],
    recursionNodeId?: number | null,
    children?: ({
    id: number,
    calling: string,
  \** @maxItems 0 *\
    data: (string)[],
    recursionNodeId?: number | null,
  \** @maxItems 0 *\
    children?: (string)[] | null,

})[],

})[],

})[],

})[],

})[],

})[],

},

}` description
 */
    traceAggregatorTracesProfilingCreate: (
      traceId: any,
      data: {
        /** @minLength 1 */
        caller?: string | null;
        excluded_callers?: string[];
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            nodes: {
              id: number;
              calling: string;
              data: {
                name: string;
                value: number;
                weight_percent: number;
              }[];
              recursionNodeId?: number | null;
              children?: {
                id: number;
                calling: string;
                data: {
                  name: string;
                  value: number;
                  weight_percent: number;
                }[];
                recursionNodeId?: number | null;
                children?: {
                  id: number;
                  calling: string;
                  data: {
                    name: string;
                    value: number;
                    weight_percent: number;
                  }[];
                  recursionNodeId?: number | null;
                  children?: {
                    id: number;
                    calling: string;
                    data: {
                      name: string;
                      value: number;
                      weight_percent: number;
                    }[];
                    recursionNodeId?: number | null;
                    children?: {
                      id: number;
                      calling: string;
                      data: {
                        name: string;
                        value: number;
                        weight_percent: number;
                      }[];
                      recursionNodeId?: number | null;
                      children?: {
                        id: number;
                        calling: string;
                        /** @maxItems 0 */
                        data: string[];
                        recursionNodeId?: number | null;
                        /** @maxItems 0 */
                        children?: string[] | null;
                      }[];
                    }[];
                  }[];
                }[];
              }[];
            }[];
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces/${traceId}/profiling`,
        method: "POST",
        body: data,
        secure: true,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTracesContentTypesCreate
 * @request POST:/admin-api/trace-aggregator/traces-content/types
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    count: number,

})[],

}` description
 */
    traceAggregatorTracesContentTypesCreate: (
      data: {
        service_ids?: number[];
        /** @minLength 1 */
        text?: string | null;
        logging_from_preset?: "one_hour" | "two_hours" | "four_hours" | "six_hours";
        /** @format date */
        logging_from?: string;
        /** @format date */
        logging_to?: string;
        /** @format float */
        duration_from?: number | null;
        /** @format float */
        duration_to?: number | null;
        /** @format float */
        memory_from?: number | null;
        /** @format float */
        memory_to?: number | null;
        /** @format float */
        cpu_from?: number | null;
        /** @format float */
        cpu_to?: number | null;
        data?: {
          filter?: {
            field?: string;
            null?: boolean;
            numeric?: {
              /** @format float */
              value?: number;
              comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
            };
            string?: {
              value?: string;
              comp?: "equals" | "contains" | "starts" | "ends";
            };
            boolean?: {
              value?: boolean;
            };
          }[];
        };
        has_profiling?: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            name: string;
            count: number;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces-content/types`,
        method: "POST",
        body: data,
        secure: true,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTracesContentTagsCreate
 * @request POST:/admin-api/trace-aggregator/traces-content/tags
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    count: number,

})[],

}` description
 */
    traceAggregatorTracesContentTagsCreate: (
      data: {
        service_ids?: number[];
        /** @minLength 1 */
        text?: string | null;
        types?: string[];
        logging_from_preset?: "one_hour" | "two_hours" | "four_hours" | "six_hours";
        /** @format date */
        logging_from?: string;
        /** @format date */
        logging_to?: string;
        /** @format float */
        duration_from?: number | null;
        /** @format float */
        duration_to?: number | null;
        /** @format float */
        memory_from?: number | null;
        /** @format float */
        memory_to?: number | null;
        /** @format float */
        cpu_from?: number | null;
        /** @format float */
        cpu_to?: number | null;
        data?: {
          filter?: {
            field?: string;
            null?: boolean;
            numeric?: {
              /** @format float */
              value?: number;
              comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
            };
            string?: {
              value?: string;
              comp?: "equals" | "contains" | "starts" | "ends";
            };
            boolean?: {
              value?: boolean;
            };
          }[];
        };
        has_profiling?: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            name: string;
            count: number;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces-content/tags`,
        method: "POST",
        body: data,
        secure: true,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTracesContentStatusesCreate
 * @request POST:/admin-api/trace-aggregator/traces-content/statuses
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    count: number,

})[],

}` description
 */
    traceAggregatorTracesContentStatusesCreate: (
      data: {
        service_ids?: number[];
        /** @minLength 1 */
        text?: string | null;
        types?: string[];
        tags?: string[];
        logging_from_preset?: "one_hour" | "two_hours" | "four_hours" | "six_hours";
        /** @format date */
        logging_from?: string;
        /** @format date */
        logging_to?: string;
        /** @format float */
        duration_from?: number | null;
        /** @format float */
        duration_to?: number | null;
        /** @format float */
        memory_from?: number | null;
        /** @format float */
        memory_to?: number | null;
        /** @format float */
        cpu_from?: number | null;
        /** @format float */
        cpu_to?: number | null;
        data?: {
          filter?: {
            field?: string;
            null?: boolean;
            numeric?: {
              /** @format float */
              value?: number;
              comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
            };
            string?: {
              value?: string;
              comp?: "equals" | "contains" | "starts" | "ends";
            };
            boolean?: {
              value?: boolean;
            };
          }[];
        };
        has_profiling?: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            name: string;
            count: number;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces-content/statuses`,
        method: "POST",
        body: data,
        secure: true,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTraceMetricsCreate
 * @request POST:/admin-api/trace-aggregator/trace-metrics
 * @secure
 * @response `200` `{
    data: {
    loggedAtFrom: string,
    items: ({
    timestamp: string,
    timestamp_to: string,
    fields: ({
    field: string,
    indicators: ({
    name: string,
    value: number,

})[],

})[],

})[],

},

}` description
 */
    traceAggregatorTraceMetricsCreate: (
      data: {
        timestamp_period:
          | "5 minutes"
          | "30 minutes"
          | "1 hour"
          | "4 hours"
          | "12 hours"
          | "1 day"
          | "3 days"
          | "7 days"
          | "15 days"
          | "1 month"
          | "3 months"
          | "6 month"
          | "1 year";
        timestamp_step: "s5" | "s10" | "s30" | "min" | "min5" | "min10" | "min30" | "h" | "h4" | "h12" | "d" | "m";
        fields?: ("count" | "duration" | "memory" | "cpu")[];
        data_fields?: string[];
        service_ids?: number[];
        /** @format date */
        logging_to?: string;
        types?: string[];
        tags?: string[];
        statuses?: string[];
        /** @format float */
        duration_from?: number | null;
        /** @format float */
        duration_to?: number | null;
        /** @format float */
        memory_from?: number | null;
        /** @format float */
        memory_to?: number | null;
        /** @format float */
        cpu_from?: number | null;
        /** @format float */
        cpu_to?: number | null;
        data?: {
          filter?: {
            field?: string;
            null?: boolean;
            numeric?: {
              /** @format float */
              value?: number;
              comp?: "=" | "!=" | ">" | ">=" | "<" | "<=";
            };
            string?: {
              value?: string;
              comp?: "equals" | "contains" | "starts" | "ends";
            };
            boolean?: {
              value?: boolean;
            };
          }[];
        };
        has_profiling?: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            loggedAtFrom: string;
            items: {
              timestamp: string;
              timestamp_to: string;
              fields: {
                field: string;
                indicators: {
                  name: string;
                  value: number;
                }[];
              }[];
            }[];
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/trace-metrics`,
        method: "POST",
        body: data,
        secure: true,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTraceMetricsFieldsList
 * @request GET:/admin-api/trace-aggregator/trace-metrics/fields
 * @secure
 * @response `200` `{
    data: ({
    name: string,
    value: string,

})[],

}` description
 */
    traceAggregatorTraceMetricsFieldsList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            name: string;
            value: string;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-aggregator/trace-metrics/fields`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTraceTimestampPeriodsList
 * @request GET:/admin-api/trace-aggregator/trace-timestamp-periods
 * @secure
 * @response `200` `{
    data: ({
    period: {
    name: string,
    value: string,

},
    timestamps: ({
    value: string,
    title: string,

})[],

})[],

}` description
 */
    traceAggregatorTraceTimestampPeriodsList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            period: {
              name: string;
              value: string;
            };
            timestamps: {
              value: string;
              title: string;
            }[];
          }[];
        },
        any
      >({
        path: `/admin-api/trace-aggregator/trace-timestamp-periods`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorDynamicIndexesList
 * @request GET:/admin-api/trace-aggregator/dynamic-indexes
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    name: string,
    indexName: string,
    collectionNames: (string)[],
    fields: ({
    name: string,
    title: string,

})[],
    inProcess: boolean,
    created: boolean,
    error?: string | null,
    actualUntilAt: string,
    createdAt: string,

})[],

}` description
 */
    traceAggregatorDynamicIndexesList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: string;
            name: string;
            indexName: string;
            collectionNames: string[];
            fields: {
              name: string;
              title: string;
            }[];
            inProcess: boolean;
            created: boolean;
            error?: string | null;
            actualUntilAt: string;
            createdAt: string;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-aggregator/dynamic-indexes`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorDynamicIndexesStatsList
 * @request GET:/admin-api/trace-aggregator/dynamic-indexes/stats
 * @secure
 * @response `200` `{
    data: {
    in_process_count: number,
    errors_count: number,
    total_count: number,
    indexes_in_process: ({
    collectionName: string,
    name: string,
    progress: number,

})[],

},

}` description
 */
    traceAggregatorDynamicIndexesStatsList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            in_process_count: number;
            errors_count: number;
            total_count: number;
            indexes_in_process: {
              collectionName: string;
              name: string;
              progress: number;
            }[];
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/dynamic-indexes/stats`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name TraceAggregatorDynamicIndexesDelete
     * @request DELETE:/admin-api/trace-aggregator/dynamic-indexes/{id}
     * @secure
     * @response `200` `void` description
     */
    traceAggregatorDynamicIndexesDelete: (id: any, params: RequestParams = {}) =>
      this.request<void, any>({
        path: `/admin-api/trace-aggregator/dynamic-indexes/${id}`,
        method: "DELETE",
        secure: true,
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorStatesList
 * @request GET:/admin-api/trace-aggregator/states
 * @secure
 * @response `200` `{
    data: {
    items: ({
    id: string,
    title: string,
    store_version: number,
    store_data: string,
    created_at: string,

})[],
    paginator: {
    total: number,
    per_page: number,
    current_page: number,
    total_pages: number,

},

},

}` description
 */
    traceAggregatorStatesList: (
      query: {
        /** @min 1 */
        page: number;
        /** @min 1 */
        version: number;
        search_query?: string | null;
        auto: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            items: {
              id: string;
              title: string;
              store_version: number;
              store_data: string;
              created_at: string;
            }[];
            paginator: {
              total: number;
              per_page: number;
              current_page: number;
              total_pages: number;
            };
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/states`,
        method: "GET",
        query: query,
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorStatesCreate
 * @request POST:/admin-api/trace-aggregator/states
 * @secure
 * @response `200` `{
    data: {
    id: string,
    title: string,
    store_version: number,
    store_data: string,
    created_at: string,

},

}` description
 */
    traceAggregatorStatesCreate: (
      data: {
        /**
         * @minLength 1
         * @maxLength 2000
         */
        title: string;
        /** @min 1 */
        store_version: number;
        store_data: string;
        auto: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: string;
            title: string;
            store_version: number;
            store_data: string;
            created_at: string;
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/states`,
        method: "POST",
        body: data,
        secure: true,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name TraceAggregatorStatesDelete
     * @request DELETE:/admin-api/trace-aggregator/states/{id}
     * @secure
     * @response `200` `void` description
     */
    traceAggregatorStatesDelete: (id: any, params: RequestParams = {}) =>
      this.request<void, any>({
        path: `/admin-api/trace-aggregator/states/${id}`,
        method: "DELETE",
        secure: true,
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceCleanerSettingsList
 * @request GET:/admin-api/trace-cleaner/settings
 * @secure
 * @response `200` `{
    data: ({
    id: number,
    days_lifetime: number,
    type?: string | null,
    only_data: boolean,
    deleted: boolean,
    created_at: string,
    updated_at: string,

})[],

}` description
 */
    traceCleanerSettingsList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            days_lifetime: number;
            type?: string | null;
            only_data: boolean;
            deleted: boolean;
            created_at: string;
            updated_at: string;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-cleaner/settings`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceCleanerSettingsCreate
 * @request POST:/admin-api/trace-cleaner/settings
 * @secure
 * @response `200` `{
    data: {
    id: number,
    days_lifetime: number,
    type?: string | null,
    only_data: boolean,
    deleted: boolean,
    created_at: string,
    updated_at: string,

},

}` description
 */
    traceCleanerSettingsCreate: (
      data: {
        /** @min 1 */
        days_life_time: number;
        type?: string | null;
        only_data: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: number;
            days_lifetime: number;
            type?: string | null;
            only_data: boolean;
            deleted: boolean;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/trace-cleaner/settings`,
        method: "POST",
        body: data,
        secure: true,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceCleanerSettingsPartialUpdate
 * @request PATCH:/admin-api/trace-cleaner/settings/{settingId}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    days_lifetime: number,
    type?: string | null,
    only_data: boolean,
    deleted: boolean,
    created_at: string,
    updated_at: string,

},

}` description
 */
    traceCleanerSettingsPartialUpdate: (
      settingId: any,
      data: {
        /** @min 1 */
        days_life_time: number;
        only_data: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: number;
            days_lifetime: number;
            type?: string | null;
            only_data: boolean;
            deleted: boolean;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/trace-cleaner/settings/${settingId}`,
        method: "PATCH",
        body: data,
        secure: true,
        type: ContentType.Json,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name TraceCleanerSettingsDelete
     * @request DELETE:/admin-api/trace-cleaner/settings/{settingId}
     * @secure
     * @response `200` `void` description
     */
    traceCleanerSettingsDelete: (settingId: any, params: RequestParams = {}) =>
      this.request<void, any>({
        path: `/admin-api/trace-cleaner/settings/${settingId}`,
        method: "DELETE",
        secure: true,
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceCleanerSettingsProcessesDetail
 * @request GET:/admin-api/trace-cleaner/settings/{settingId}/processes
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    setting_id: number,
    cleared_count: number,
    error?: string | null,
    cleared_at?: string | null,
    created_at: string,
    updated_at: string,

})[],

}` description
 */
    traceCleanerSettingsProcessesDetail: (settingId: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: string;
            setting_id: number;
            cleared_count: number;
            error?: string | null;
            cleared_at?: string | null;
            created_at: string;
            updated_at: string;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-cleaner/settings/${settingId}/processes`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name LogsList
 * @request GET:/admin-api/logs
 * @secure
 * @response `200` `{
    data: {
    items: ({
    level: string,
    message: string,
    context: string,
    channel: string,
    logged_at: string,

})[],
    paginator: {
    total: number,
    per_page: number,
    current_page: number,
    total_pages: number,

},

},

}` description
 */
    logsList: (
      query: {
        /** @min 1 */
        page: number;
        /** @maxLength 255 */
        search_query?: string | null;
        /** @maxLength 255 */
        level?: string | null;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            items: {
              level: string;
              message: string;
              context: string;
              channel: string;
              logged_at: string;
            }[];
            paginator: {
              total: number;
              per_page: number;
              current_page: number;
              total_pages: number;
            };
          };
        },
        any
      >({
        path: `/admin-api/logs`,
        method: "GET",
        query: query,
        secure: true,
        format: "json",
        ...params,
      }),
  };
}
