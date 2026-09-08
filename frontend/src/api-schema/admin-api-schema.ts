/* eslint-disable */
/* tslint:disable */
// @ts-nocheck
/*
 * ---------------------------------------------------------------
 * ## THIS FILE WAS GENERATED VIA SWAGGER-TYPESCRIPT-API        ##
 * ##                                                           ##
 * ## AUTHOR: acacode                                           ##
 * ## SOURCE: https://github.com/acacode/swagger-typescript-api ##
 * ---------------------------------------------------------------
 */

export enum LoggingFromPresetEnum {
  Custom = "custom",
  LastHour = "last_hour",
  Last2Hours = "last_2_hours",
  Last3Hours = "last_3_hours",
  Last6Hours = "last_6_hours",
  Last12Hours = "last_12_hours",
  LastDay = "last_day",
  Last3Days = "last_3_days",
  LastWeek = "last_week",
  Last2Weeks = "last_2_weeks",
  LastMonth = "last_month",
}

export enum DataCompEnum {
  Value = "=",
  Value1 = "!=",
  Value2 = ">",
  Value3 = ">=",
  Value4 = "<",
  Value5 = "<=",
}

export enum DataCompEnum1 {
  Equals = "equals",
  Contains = "contains",
  Starts = "starts",
  Ends = "ends",
}

export enum LoggingFromPresetEnum1 {
  Custom = "custom",
  LastHour = "last_hour",
  Last2Hours = "last_2_hours",
  Last3Hours = "last_3_hours",
  Last6Hours = "last_6_hours",
  Last12Hours = "last_12_hours",
  LastDay = "last_day",
  Last3Days = "last_3_days",
  LastWeek = "last_week",
  Last2Weeks = "last_2_weeks",
  LastMonth = "last_month",
}

export enum DataCompEnum2 {
  Value = "=",
  Value1 = "!=",
  Value2 = ">",
  Value3 = ">=",
  Value4 = "<",
  Value5 = "<=",
}

export enum DataCompEnum3 {
  Equals = "equals",
  Contains = "contains",
  Starts = "starts",
  Ends = "ends",
}

export enum LoggingFromPresetEnum2 {
  Custom = "custom",
  LastHour = "last_hour",
  Last2Hours = "last_2_hours",
  Last3Hours = "last_3_hours",
  Last6Hours = "last_6_hours",
  Last12Hours = "last_12_hours",
  LastDay = "last_day",
  Last3Days = "last_3_days",
  LastWeek = "last_week",
  Last2Weeks = "last_2_weeks",
  LastMonth = "last_month",
}

export enum DataCompEnum4 {
  Value = "=",
  Value1 = "!=",
  Value2 = ">",
  Value3 = ">=",
  Value4 = "<",
  Value5 = "<=",
}

export enum DataCompEnum5 {
  Equals = "equals",
  Contains = "contains",
  Starts = "starts",
  Ends = "ends",
}

export enum LoggingFromPresetEnum3 {
  Custom = "custom",
  LastHour = "last_hour",
  Last2Hours = "last_2_hours",
  Last3Hours = "last_3_hours",
  Last6Hours = "last_6_hours",
  Last12Hours = "last_12_hours",
  LastDay = "last_day",
  Last3Days = "last_3_days",
  LastWeek = "last_week",
  Last2Weeks = "last_2_weeks",
  LastMonth = "last_month",
}

export enum DataCompEnum6 {
  Value = "=",
  Value1 = "!=",
  Value2 = ">",
  Value3 = ">=",
  Value4 = "<",
  Value5 = "<=",
}

export enum DataCompEnum7 {
  Equals = "equals",
  Contains = "contains",
  Starts = "starts",
  Ends = "ends",
}

export enum TimestampPeriodEnum {
  Value5Minutes = "5 minutes",
  Value30Minutes = "30 minutes",
  Value1Hour = "1 hour",
  Value4Hours = "4 hours",
  Value12Hours = "12 hours",
  Value1Day = "1 day",
  Value3Days = "3 days",
  Value7Days = "7 days",
  Value15Days = "15 days",
  Value1Month = "1 month",
  Value3Months = "3 months",
  Value6Month = "6 month",
  Value1Year = "1 year",
}

export enum TimestampStepEnum {
  S5 = "s5",
  S10 = "s10",
  S30 = "s30",
  Min = "min",
  Min5 = "min5",
  Min10 = "min10",
  Min30 = "min30",
  H = "h",
  H4 = "h4",
  H12 = "h12",
  D = "d",
  M = "m",
}

export enum FieldsEnum {
  Count = "count",
  Duration = "duration",
  Memory = "memory",
  Cpu = "cpu",
}

export enum DataCompEnum8 {
  Value = "=",
  Value1 = "!=",
  Value2 = ">",
  Value3 = ">=",
  Value4 = "<",
  Value5 = "<=",
}

export enum DataCompEnum9 {
  Equals = "equals",
  Contains = "contains",
  Starts = "starts",
  Ends = "ends",
}

export enum WatchersIncidentsListParamsStatusEnum {
  Opened = "opened",
  Closed = "closed",
}

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
  }

  /**
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
  }

  /**
   * No description
   * @name AuthLogoutCreate
   * @request POST:/admin-api/auth/logout
   * @secure
   * @response `200` `any` description
   */
  export namespace AuthLogoutCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = any;
  }

  /**
 * No description
 * @name DashboardDatabaseList
 * @request GET:/admin-api/dashboard/database
 * @secure
 * @response `200` `{
    data: {
    cached_at: string,
    items: ({
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

},

}` description
*/
  export namespace DashboardDatabaseList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        cached_at: string;
        items: {
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
    };
  }

  /**
 * No description
 * @name DashboardSconcurList
 * @request GET:/admin-api/dashboard/sconcur
 * @secure
 * @response `200` `{
    data: {
    available: boolean,
    name: string,
    workers_total: number,
    workers_hung: number,
    cpu_percent: number,
    memory_rss_bytes: number,
    runtime_tasks: number,
    work?: {
    in_process: number,
    "in_process_1_to_5s": number,
    "in_process_5_to_15s": number,
    "in_process_over_15s": number,
    finished: number,
    refused: number,
    measured: number,
    avg_ms: number,

},
    master_cpu_percent: number,
    master_memory_rss_bytes: number,
    groups: ({
    name: string,
    workers_total: number,
    workers_hung: number,
    cpu_percent: number,
    memory_rss_bytes: number,
    runtime_tasks: number,
    work?: {
    in_process: number,
    "in_process_1_to_5s": number,
    "in_process_5_to_15s": number,
    "in_process_over_15s": number,
    finished: number,
    refused: number,
    measured: number,
    avg_ms: number,

},

})[],
    workers: ({
    pid: number,
    group: string,
    hung: boolean,
    uptime_seconds: number,
    cpu_percent: number,
    memory_rss_bytes: number,
    runtime_tasks: number,
    work?: {
    in_process: number,
    "in_process_1_to_5s": number,
    "in_process_5_to_15s": number,
    "in_process_over_15s": number,
    finished: number,
    refused: number,
    measured: number,
    avg_ms: number,

},

})[],

},

}` description
*/
  export namespace DashboardSconcurList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        available: boolean;
        name: string;
        workers_total: number;
        workers_hung: number;
        cpu_percent: number;
        memory_rss_bytes: number;
        runtime_tasks: number;
        work?: {
          in_process: number;
          in_process_1_to_5s: number;
          in_process_5_to_15s: number;
          in_process_over_15s: number;
          finished: number;
          refused: number;
          measured: number;
          avg_ms: number;
        };
        master_cpu_percent: number;
        master_memory_rss_bytes: number;
        groups: {
          name: string;
          workers_total: number;
          workers_hung: number;
          cpu_percent: number;
          memory_rss_bytes: number;
          runtime_tasks: number;
          work?: {
            in_process: number;
            in_process_1_to_5s: number;
            in_process_5_to_15s: number;
            in_process_over_15s: number;
            finished: number;
            refused: number;
            measured: number;
            avg_ms: number;
          };
        }[];
        workers: {
          pid: number;
          group: string;
          hung: boolean;
          uptime_seconds: number;
          cpu_percent: number;
          memory_rss_bytes: number;
          runtime_tasks: number;
          work?: {
            in_process: number;
            in_process_1_to_5s: number;
            in_process_5_to_15s: number;
            in_process_over_15s: number;
            finished: number;
            refused: number;
            measured: number;
            avg_ms: number;
          };
        }[];
      };
    };
  }

  /**
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
  }

  /**
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
  }

  /**
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
      logging_from_preset?: LoggingFromPresetEnum;
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
            comp?: DataCompEnum;
          };
          string?: {
            value?: string;
            comp?: DataCompEnum1;
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
  }

  /**
 * No description
 * @name TraceAggregatorTracesTreeCreate
 * @request POST:/admin-api/trace-aggregator/traces/tree
 * @secure
 * @response `200` `{
    data: ({
    service_id: number,
    parent_trace_id?: string | null,
    trace_id: string,
    type: string,
    tags: (string)[],
    status: string,
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,

})[],

}` description
*/
  export namespace TraceAggregatorTracesTreeCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      trace_id: string;
      fresh: boolean;
      is_child: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        service_id: number;
        parent_trace_id?: string | null;
        trace_id: string;
        type: string;
        tags: string[];
        status: string;
        duration?: number | null;
        memory?: number | null;
        cpu?: number | null;
        logged_at: string;
      }[];
    };
  }

  /**
 * No description
 * @name TraceAggregatorTracesTreeContentCreate
 * @request POST:/admin-api/trace-aggregator/traces/tree/content
 * @secure
 * @response `200` `{
    data: {
    state: {
    root_trace_id: string,
    version: string,
    status: string,
    count: number,
    error?: string | null,
    started_at?: string | null,
    finished_at?: string | null,
    created_at: string,
    updated_at: string,

},
    content?: {
    count: number,
    services: ({
    id: number,
    name: string,
    traces_count: number,

})[],
    types: ({
    name: string,
    traces_count: number,

})[],
    tags: ({
    name: string,
    traces_count: number,

})[],
    statuses: ({
    name: string,
    traces_count: number,

})[],

},

},

}` description
*/
  export namespace TraceAggregatorTracesTreeContentCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      trace_id: string;
      is_child: boolean;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        state: {
          root_trace_id: string;
          version: string;
          status: string;
          count: number;
          error?: string | null;
          started_at?: string | null;
          finished_at?: string | null;
          created_at: string;
          updated_at: string;
        };
        content?: {
          count: number;
          services: {
            id: number;
            name: string;
            traces_count: number;
          }[];
          types: {
            name: string;
            traces_count: number;
          }[];
          tags: {
            name: string;
            traces_count: number;
          }[];
          statuses: {
            name: string;
            traces_count: number;
          }[];
        };
      };
    };
  }

  /**
 * No description
 * @name TraceAggregatorTracesTreeProcessesList
 * @request GET:/admin-api/trace-aggregator/traces/tree/processes
 * @secure
 * @response `200` `{
    data: ({
    root_trace_id: string,
    version: string,
    status: string,
    count: number,
    error?: string | null,
    started_at?: string | null,
    finished_at?: string | null,
    created_at: string,
    updated_at: string,

})[],

}` description
*/
  export namespace TraceAggregatorTracesTreeProcessesList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        root_trace_id: string;
        version: string;
        status: string;
        count: number;
        error?: string | null;
        started_at?: string | null;
        finished_at?: string | null;
        created_at: string;
        updated_at: string;
      }[];
    };
  }

  /**
 * No description
 * @name TraceAggregatorTracesTreeProcessesCancelPartialUpdate
 * @request PATCH:/admin-api/trace-aggregator/traces/tree/processes/cancel
 * @secure
 * @response `200` `{
    data: {
    root_trace_id: string,
    version: string,
    status: string,
    count: number,
    error?: string | null,
    started_at?: string | null,
    finished_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace TraceAggregatorTracesTreeProcessesCancelPartialUpdate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      root_trace_id: string;
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        root_trace_id: string;
        version: string;
        status: string;
        count: number;
        error?: string | null;
        started_at?: string | null;
        finished_at?: string | null;
        created_at: string;
        updated_at: string;
      };
    };
  }

  /**
   * No description
   * @name TraceAggregatorTracesTreeProcessesDeletePartialUpdate
   * @request PATCH:/admin-api/trace-aggregator/traces/tree/processes/delete
   * @secure
   * @response `200` `any` description
   */
  export namespace TraceAggregatorTracesTreeProcessesDeletePartialUpdate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      root_trace_id: string;
    };
    export type RequestHeaders = {};
    export type ResponseBody = any;
  }

  /**
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
  /** @maxItems 0 *\/
    children?: (string)[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

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
          children?:
            | {
                key: string;
                value: string;
                children?:
                  | {
                      key: string;
                      value: string;
                      children?:
                        | {
                            key: string;
                            value: string;
                            children?:
                              | {
                                  key: string;
                                  value: string;
                                  children?:
                                    | {
                                        key: string;
                                        value: string;
                                        /** @maxItems 0 */
                                        children?: string[] | null;
                                        can_be_filtered: boolean;
                                      }[]
                                    | null;
                                  can_be_filtered: boolean;
                                }[]
                              | null;
                            can_be_filtered: boolean;
                          }[]
                        | null;
                      can_be_filtered: boolean;
                    }[]
                  | null;
                can_be_filtered: boolean;
              }[]
            | null;
          can_be_filtered: boolean;
        };
        duration?: number | null;
        memory?: number | null;
        cpu?: number | null;
        logged_at: string;
        created_at: string;
        updated_at: string;
      };
    };
  }

  /**
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
  /** @maxItems 0 *\/
    data: (string)[],
    recursionNodeId?: number | null,
  /** @maxItems 0 *\/
    children?: (string)[] | null,

})[] | null,

})[] | null,

})[] | null,

})[] | null,

})[] | null,

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
          children?:
            | {
                id: number;
                calling: string;
                data: {
                  name: string;
                  value: number;
                  weight_percent: number;
                }[];
                recursionNodeId?: number | null;
                children?:
                  | {
                      id: number;
                      calling: string;
                      data: {
                        name: string;
                        value: number;
                        weight_percent: number;
                      }[];
                      recursionNodeId?: number | null;
                      children?:
                        | {
                            id: number;
                            calling: string;
                            data: {
                              name: string;
                              value: number;
                              weight_percent: number;
                            }[];
                            recursionNodeId?: number | null;
                            children?:
                              | {
                                  id: number;
                                  calling: string;
                                  data: {
                                    name: string;
                                    value: number;
                                    weight_percent: number;
                                  }[];
                                  recursionNodeId?: number | null;
                                  children?:
                                    | {
                                        id: number;
                                        calling: string;
                                        /** @maxItems 0 */
                                        data: string[];
                                        recursionNodeId?: number | null;
                                        /** @maxItems 0 */
                                        children?: string[] | null;
                                      }[]
                                    | null;
                                }[]
                              | null;
                          }[]
                        | null;
                    }[]
                  | null;
              }[]
            | null;
        }[];
      };
    };
  }

  /**
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
      logging_from_preset?: LoggingFromPresetEnum1;
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
            comp?: DataCompEnum2;
          };
          string?: {
            value?: string;
            comp?: DataCompEnum3;
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
  }

  /**
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
      logging_from_preset?: LoggingFromPresetEnum2;
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
            comp?: DataCompEnum4;
          };
          string?: {
            value?: string;
            comp?: DataCompEnum5;
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
  }

  /**
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
      logging_from_preset?: LoggingFromPresetEnum3;
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
            comp?: DataCompEnum6;
          };
          string?: {
            value?: string;
            comp?: DataCompEnum7;
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
  }

  /**
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
      timestamp_period: TimestampPeriodEnum;
      timestamp_step: TimestampStepEnum;
      fields?: FieldsEnum[];
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
            comp?: DataCompEnum8;
          };
          string?: {
            value?: string;
            comp?: DataCompEnum9;
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
  }

  /**
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
  }

  /**
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
  }

  /**
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
  }

  /**
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
  }

  /**
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
  }

  /**
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
  }

  /**
 * No description
 * @name TraceCleanerProcessesList
 * @request GET:/admin-api/trace-cleaner/processes
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    cleared_collections_count: number,
    cleared_traces_count: number,
    error?: string | null,
    cleared_at?: string | null,
    created_at: string,
    updated_at: string,

})[],

}` description
*/
  export namespace TraceCleanerProcessesList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: string;
        cleared_collections_count: number;
        cleared_traces_count: number;
        error?: string | null;
        cleared_at?: string | null;
        created_at: string;
        updated_at: string;
      }[];
    };
  }

  /**
 * No description
 * @name WatchersList
 * @request GET:/admin-api/watchers
 * @secure
 * @response `200` `{
    data: ({
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

})[],

}` description
*/
  export namespace WatchersList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        name: string;
        type: string;
        enabled: boolean;
        cooldown_seconds: number;
        collect_since?: string | null;
        last_checked_at?: string | null;
        last_triggered_at?: string | null;
        created_at: string;
        updated_at: string;
      }[];
    };
  }

  /**
 * No description
 * @name WatchersTypesList
 * @request GET:/admin-api/watchers/types
 * @secure
 * @response `200` `{
    data: ({
    type: string,
    title: string,
    description: string,
    default_cooldown_seconds: number,
    has_trace_filter: boolean,
    fields: ({
    key: string,
    title: string,
    value_type: string,
    default: number,
    min: number,
    max?: number | null,

})[],

})[],

}` description
*/
  export namespace WatchersTypesList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        type: string;
        title: string;
        description: string;
        default_cooldown_seconds: number;
        has_trace_filter: boolean;
        fields: {
          key: string;
          title: string;
          value_type: string;
          default: number;
          min: number;
          max?: number | null;
        }[];
      }[];
    };
  }

  /**
 * No description
 * @name WatchersIncidentsList
 * @request GET:/admin-api/watchers/incidents
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    watcher_id: number,
    status: string,
    first_event_at: string,
    last_event_at: string,
    events_count: number,
    closed_at?: string | null,
    closed_by_user_id?: number | null,

})[],

}` description
*/
  export namespace WatchersIncidentsList {
    export type RequestParams = {};
    export type RequestQuery = {
      /** @min 1 */
      page?: number;
      /**
       * @min 1
       * @max 200
       */
      per_page?: number;
      status?: WatchersIncidentsListParamsStatusEnum | null;
      watcher_id?: number | null;
    };
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: string;
        watcher_id: number;
        status: string;
        first_event_at: string;
        last_event_at: string;
        events_count: number;
        closed_at?: string | null;
        closed_by_user_id?: number | null;
      }[];
    };
  }

  /**
 * No description
 * @name WatchersIncidentsStatList
 * @request GET:/admin-api/watchers/incidents/stat
 * @secure
 * @response `200` `{
    data: {
    opened_count: number,

},

}` description
*/
  export namespace WatchersIncidentsStatList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        opened_count: number;
      };
    };
  }

  /**
 * No description
 * @name WatchersIncidentsEventsList
 * @request GET:/admin-api/watchers/incidents/{id}/events
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    incident_id: string,
    occurred_at: string,
    payload: {
    settings: {
    threshold?: number | null,
    period_minutes?: number | null,
    window_minutes?: number | null,
    baseline_minutes?: number | null,
    growth_percent?: number | null,
    duration?: number | null,

},
    measured: {
    buffer_count?: number | null,
    invalid_count?: number | null,
    since?: string | null,
    window_from?: string | null,
    window_to?: string | null,
    window_count?: number | null,
    window_per_minute?: number | null,
    baseline_per_minute?: number | null,
    growth_percent?: number | null,
    slowest?: number | null,

},
    groups: ({
    service_id: number,
    type: string,
    tags: (string)[],
    count: number,
    duration_max?: number | null,
    trace_id?: string | null,

})[],

},

})[],

}` description
*/
  export namespace WatchersIncidentsEventsList {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {
      /** @min 1 */
      page?: number;
      /**
       * @min 1
       * @max 200
       */
      per_page?: number;
    };
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: string;
        incident_id: string;
        occurred_at: string;
        payload: {
          settings: {
            threshold?: number | null;
            period_minutes?: number | null;
            window_minutes?: number | null;
            baseline_minutes?: number | null;
            growth_percent?: number | null;
            duration?: number | null;
          };
          measured: {
            buffer_count?: number | null;
            invalid_count?: number | null;
            since?: string | null;
            window_from?: string | null;
            window_to?: string | null;
            window_count?: number | null;
            window_per_minute?: number | null;
            baseline_per_minute?: number | null;
            growth_percent?: number | null;
            slowest?: number | null;
          };
          groups: {
            service_id: number;
            type: string;
            tags: string[];
            count: number;
            duration_max?: number | null;
            trace_id?: string | null;
          }[];
        };
      }[];
    };
  }

  /**
   * No description
   * @name WatchersIncidentsClosePartialUpdate
   * @request PATCH:/admin-api/watchers/incidents/{id}/close
   * @secure
   * @response `200` `any` description
   */
  export namespace WatchersIncidentsClosePartialUpdate {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = any;
  }

  /**
 * No description
 * @name WatchersBufferOverflowCreate
 * @request POST:/admin-api/watchers/buffer-overflow
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace WatchersBufferOverflowCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /** @min 1 */
        threshold?: number;
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        name: string;
        type: string;
        enabled: boolean;
        cooldown_seconds: number;
        collect_since?: string | null;
        last_checked_at?: string | null;
        last_triggered_at?: string | null;
        created_at: string;
        updated_at: string;
      };
    };
  }

  /**
 * No description
 * @name WatchersBufferOverflowDetail
 * @request GET:/admin-api/watchers/buffer-overflow/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    threshold: number,

},

}` description
*/
  export namespace WatchersBufferOverflowDetail {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        threshold: number;
      };
    };
  }

  /**
   * No description
   * @name WatchersBufferOverflowPartialUpdate
   * @request PATCH:/admin-api/watchers/buffer-overflow/{id}
   * @secure
   * @response `200` `any` description
   */
  export namespace WatchersBufferOverflowPartialUpdate {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /** @min 1 */
        threshold?: number;
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = any;
  }

  /**
 * No description
 * @name WatchersInvalidBufferGrownCreate
 * @request POST:/admin-api/watchers/invalid-buffer-grown
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace WatchersInvalidBufferGrownCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /** @min 1 */
        threshold?: number;
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        name: string;
        type: string;
        enabled: boolean;
        cooldown_seconds: number;
        collect_since?: string | null;
        last_checked_at?: string | null;
        last_triggered_at?: string | null;
        created_at: string;
        updated_at: string;
      };
    };
  }

  /**
 * No description
 * @name WatchersInvalidBufferGrownDetail
 * @request GET:/admin-api/watchers/invalid-buffer-grown/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    threshold: number,

},

}` description
*/
  export namespace WatchersInvalidBufferGrownDetail {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        threshold: number;
      };
    };
  }

  /**
   * No description
   * @name WatchersInvalidBufferGrownPartialUpdate
   * @request PATCH:/admin-api/watchers/invalid-buffer-grown/{id}
   * @secure
   * @response `200` `any` description
   */
  export namespace WatchersInvalidBufferGrownPartialUpdate {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /** @min 1 */
        threshold?: number;
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = any;
  }

  /**
 * No description
 * @name WatchersNoNewTracesCreate
 * @request POST:/admin-api/watchers/no-new-traces
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace WatchersNoNewTracesCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /**
         * @min 1
         * @max 175
         */
        period_minutes?: number;
        filter?: {
          service_ids?: number[];
          types?: string[];
          tags?: string[];
        };
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        name: string;
        type: string;
        enabled: boolean;
        cooldown_seconds: number;
        collect_since?: string | null;
        last_checked_at?: string | null;
        last_triggered_at?: string | null;
        created_at: string;
        updated_at: string;
      };
    };
  }

  /**
 * No description
 * @name WatchersNoNewTracesDetail
 * @request GET:/admin-api/watchers/no-new-traces/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    period_minutes: number,
    filter: {
    service_ids: (number)[],
    types: (string)[],
    tags: (string)[],

},

},

}` description
*/
  export namespace WatchersNoNewTracesDetail {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        period_minutes: number;
        filter: {
          service_ids: number[];
          types: string[];
          tags: string[];
        };
      };
    };
  }

  /**
   * No description
   * @name WatchersNoNewTracesPartialUpdate
   * @request PATCH:/admin-api/watchers/no-new-traces/{id}
   * @secure
   * @response `200` `any` description
   */
  export namespace WatchersNoNewTracesPartialUpdate {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /**
         * @min 1
         * @max 175
         */
        period_minutes?: number;
        filter?: {
          service_ids?: number[];
          types?: string[];
          tags?: string[];
        };
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = any;
  }

  /**
 * No description
 * @name WatchersTracesSpikeCreate
 * @request POST:/admin-api/watchers/traces-spike
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace WatchersTracesSpikeCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /**
         * @min 1
         * @max 175
         */
        window_minutes?: number;
        /**
         * @min 1
         * @max 175
         */
        baseline_minutes?: number;
        /** @min 1 */
        growth_percent?: number;
        filter?: {
          service_ids?: number[];
          types?: string[];
          tags?: string[];
        };
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        name: string;
        type: string;
        enabled: boolean;
        cooldown_seconds: number;
        collect_since?: string | null;
        last_checked_at?: string | null;
        last_triggered_at?: string | null;
        created_at: string;
        updated_at: string;
      };
    };
  }

  /**
 * No description
 * @name WatchersTracesSpikeDetail
 * @request GET:/admin-api/watchers/traces-spike/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    window_minutes: number,
    baseline_minutes: number,
    growth_percent: number,
    filter: {
    service_ids: (number)[],
    types: (string)[],
    tags: (string)[],

},

},

}` description
*/
  export namespace WatchersTracesSpikeDetail {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        window_minutes: number;
        baseline_minutes: number;
        growth_percent: number;
        filter: {
          service_ids: number[];
          types: string[];
          tags: string[];
        };
      };
    };
  }

  /**
   * No description
   * @name WatchersTracesSpikePartialUpdate
   * @request PATCH:/admin-api/watchers/traces-spike/{id}
   * @secure
   * @response `200` `any` description
   */
  export namespace WatchersTracesSpikePartialUpdate {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /**
         * @min 1
         * @max 175
         */
        window_minutes?: number;
        /**
         * @min 1
         * @max 175
         */
        baseline_minutes?: number;
        /** @min 1 */
        growth_percent?: number;
        filter?: {
          service_ids?: number[];
          types?: string[];
          tags?: string[];
        };
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = any;
  }

  /**
 * No description
 * @name WatchersSlowTracesCreate
 * @request POST:/admin-api/watchers/slow-traces
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace WatchersSlowTracesCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /**
         * @format float
         * @min 0
         */
        duration?: number;
        /**
         * @min 1
         * @max 175
         */
        window_minutes?: number;
        filter?: {
          service_ids?: number[];
          types?: string[];
          tags?: string[];
        };
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        name: string;
        type: string;
        enabled: boolean;
        cooldown_seconds: number;
        collect_since?: string | null;
        last_checked_at?: string | null;
        last_triggered_at?: string | null;
        created_at: string;
        updated_at: string;
      };
    };
  }

  /**
 * No description
 * @name WatchersSlowTracesDetail
 * @request GET:/admin-api/watchers/slow-traces/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    duration: number,
    window_minutes: number,
    filter: {
    service_ids: (number)[],
    types: (string)[],
    tags: (string)[],

},

},

}` description
*/
  export namespace WatchersSlowTracesDetail {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        duration: number;
        window_minutes: number;
        filter: {
          service_ids: number[];
          types: string[];
          tags: string[];
        };
      };
    };
  }

  /**
   * No description
   * @name WatchersSlowTracesPartialUpdate
   * @request PATCH:/admin-api/watchers/slow-traces/{id}
   * @secure
   * @response `200` `any` description
   */
  export namespace WatchersSlowTracesPartialUpdate {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      /**
       * @min 1
       * @max 86400
       */
      cooldown_seconds: number;
      settings?: {
        /**
         * @format float
         * @min 0
         */
        duration?: number;
        /**
         * @min 1
         * @max 175
         */
        window_minutes?: number;
        filter?: {
          service_ids?: number[];
          types?: string[];
          tags?: string[];
        };
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = any;
  }

  /**
   * No description
   * @name WatchersDelete
   * @request DELETE:/admin-api/watchers/{id}
   * @secure
   * @response `200` `void` description
   */
  export namespace WatchersDelete {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = void;
  }

  /**
 * No description
 * @name NotificationChannelsList
 * @request GET:/admin-api/notification-channels
 * @secure
 * @response `200` `{
    data: ({
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    on_opened: boolean,
    on_event: boolean,
    on_closed: boolean,
    created_at: string,
    updated_at: string,

})[],

}` description
*/
  export namespace NotificationChannelsList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        name: string;
        type: string;
        enabled: boolean;
        on_opened: boolean;
        on_event: boolean;
        on_closed: boolean;
        created_at: string;
        updated_at: string;
      }[];
    };
  }

  /**
 * No description
 * @name NotificationChannelsTypesList
 * @request GET:/admin-api/notification-channels/types
 * @secure
 * @response `200` `{
    data: ({
    type: string,
    title: string,
    description: string,
    fields: ({
    key: string,
    title: string,
    description: string,
    secret: boolean,
    max_length: number,

})[],

})[],

}` description
*/
  export namespace NotificationChannelsTypesList {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        type: string;
        title: string;
        description: string;
        fields: {
          key: string;
          title: string;
          description: string;
          secret: boolean;
          max_length: number;
        }[];
      }[];
    };
  }

  /**
 * No description
 * @name NotificationChannelsTelegramCreate
 * @request POST:/admin-api/notification-channels/telegram
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    on_opened: boolean,
    on_event: boolean,
    on_closed: boolean,
    created_at: string,
    updated_at: string,

},

}` description
*/
  export namespace NotificationChannelsTelegramCreate {
    export type RequestParams = {};
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      on_opened: boolean;
      on_event: boolean;
      on_closed: boolean;
      settings?: {
        /**
         * @minLength 10
         * @maxLength 255
         */
        bot_token?: string;
        /**
         * @minLength 5
         * @maxLength 255
         */
        chat_id?: string;
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        name: string;
        type: string;
        enabled: boolean;
        on_opened: boolean;
        on_event: boolean;
        on_closed: boolean;
        created_at: string;
        updated_at: string;
      };
    };
  }

  /**
 * No description
 * @name NotificationChannelsTelegramDetail
 * @request GET:/admin-api/notification-channels/telegram/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    bot_token_mask: string,
    chat_id: string,

},

}` description
*/
  export namespace NotificationChannelsTelegramDetail {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: number;
        bot_token_mask: string;
        chat_id: string;
      };
    };
  }

  /**
   * No description
   * @name NotificationChannelsTelegramPartialUpdate
   * @request PATCH:/admin-api/notification-channels/telegram/{id}
   * @secure
   * @response `200` `any` description
   */
  export namespace NotificationChannelsTelegramPartialUpdate {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = {
      /**
       * @minLength 1
       * @maxLength 255
       */
      name: string;
      enabled: boolean;
      on_opened: boolean;
      on_event: boolean;
      on_closed: boolean;
      settings?: {
        /**
         * @minLength 10
         * @maxLength 255
         */
        bot_token?: string | null;
        /**
         * @minLength 5
         * @maxLength 255
         */
        chat_id?: string;
      };
    };
    export type RequestHeaders = {};
    export type ResponseBody = any;
  }

  /**
 * No description
 * @name NotificationChannelsDeliveriesList
 * @request GET:/admin-api/notification-channels/{id}/deliveries
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    watcher_id?: number | null,
    incident_id?: string | null,
    kind: string,
    text: string,
    sent_at?: string | null,
    error?: string | null,
    created_at: string,

})[],

}` description
*/
  export namespace NotificationChannelsDeliveriesList {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        id: string;
        watcher_id?: number | null;
        incident_id?: string | null;
        kind: string;
        text: string;
        sent_at?: string | null;
        error?: string | null;
        created_at: string;
      }[];
    };
  }

  /**
 * No description
 * @name NotificationChannelsTestCreate
 * @request POST:/admin-api/notification-channels/{id}/test
 * @secure
 * @response `200` `{
    data: {
    delivered: boolean,
    permanent: boolean,
    error?: string | null,
    retry_after_seconds?: number | null,

},

}` description
*/
  export namespace NotificationChannelsTestCreate {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = {
      data: {
        delivered: boolean;
        permanent: boolean;
        error?: string | null;
        retry_after_seconds?: number | null;
      };
    };
  }

  /**
   * No description
   * @name NotificationChannelsDelete
   * @request DELETE:/admin-api/notification-channels/{id}
   * @secure
   * @response `200` `void` description
   */
  export namespace NotificationChannelsDelete {
    export type RequestParams = {
      id: any;
    };
    export type RequestQuery = {};
    export type RequestBody = never;
    export type RequestHeaders = {};
    export type ResponseBody = void;
  }

  /**
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

export type RequestParams = Omit<
  FullRequestParams,
  "body" | "method" | "query" | "path"
>;

export interface ApiConfig<SecurityDataType = unknown> {
  baseUrl?: string;
  baseApiParams?: Omit<RequestParams, "baseUrl" | "cancelToken" | "signal">;
  securityWorker?: (
    securityData: SecurityDataType | null,
  ) => Promise<RequestParams | void> | RequestParams | void;
  customFetch?: typeof fetch;
}

export interface HttpResponse<D extends unknown, E extends unknown = unknown>
  extends Response {
  data: D;
  error: E;
}

type CancelToken = Symbol | string | number;

export enum ContentType {
  Json = "application/json",
  JsonApi = "application/vnd.api+json",
  FormData = "multipart/form-data",
  UrlEncoded = "application/x-www-form-urlencoded",
  Text = "text/plain",
}

export class HttpClient<SecurityDataType = unknown> {
  public baseUrl: string = "";
  private securityData: SecurityDataType | null = null;
  private securityWorker?: ApiConfig<SecurityDataType>["securityWorker"];
  private abortControllers = new Map<CancelToken, AbortController>();
  private customFetch = (...fetchParams: Parameters<typeof fetch>) =>
    fetch(...fetchParams);

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
    const keys = Object.keys(query).filter(
      (key) => "undefined" !== typeof query[key],
    );
    return keys
      .map((key) =>
        Array.isArray(query[key])
          ? this.addArrayQueryParam(query, key)
          : this.addQueryParam(query, key),
      )
      .join("&");
  }

  protected addQueryParams(rawQuery?: QueryParamsType): string {
    const queryString = this.toQueryString(rawQuery);
    return queryString ? `?${queryString}` : "";
  }

  private contentFormatters: Record<ContentType, (input: any) => any> = {
    [ContentType.Json]: (input: any) =>
      input !== null && (typeof input === "object" || typeof input === "string")
        ? JSON.stringify(input)
        : input,
    [ContentType.JsonApi]: (input: any) =>
      input !== null && (typeof input === "object" || typeof input === "string")
        ? JSON.stringify(input)
        : input,
    [ContentType.Text]: (input: any) =>
      input !== null && typeof input !== "string"
        ? JSON.stringify(input)
        : input,
    [ContentType.FormData]: (input: any) => {
      if (input instanceof FormData) {
        return input;
      }

      return Object.keys(input || {}).reduce((formData, key) => {
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
      }, new FormData());
    },
    [ContentType.UrlEncoded]: (input: any) => this.toQueryString(input),
  };

  protected mergeRequestParams(
    params1: RequestParams,
    params2?: RequestParams,
  ): RequestParams {
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

  protected createAbortSignal = (
    cancelToken: CancelToken,
  ): AbortSignal | undefined => {
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

    return this.customFetch(
      `${baseUrl || this.baseUrl || ""}${path}${queryString ? `?${queryString}` : ""}`,
      {
        ...requestParams,
        headers: {
          ...(requestParams.headers || {}),
          ...(type && type !== ContentType.FormData
            ? { "Content-Type": type }
            : {}),
        },
        signal:
          (cancelToken
            ? this.createAbortSignal(cancelToken)
            : requestParams.signal) || null,
        body:
          typeof body === "undefined" || body === null
            ? null
            : payloadFormatter(body),
      },
    ).then(async (response) => {
      const r = response as HttpResponse<T, E>;
      r.data = null as unknown as T;
      r.error = null as unknown as E;

      const responseToParse = responseFormat ? response.clone() : response;
      const data = !responseFormat
        ? r
        : await responseToParse[responseFormat]()
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
export class Api<
  SecurityDataType extends unknown,
> extends HttpClient<SecurityDataType> {
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
     * @name AuthLogoutCreate
     * @request POST:/admin-api/auth/logout
     * @secure
     * @response `200` `any` description
     */
    authLogoutCreate: (params: RequestParams = {}) =>
      this.request<any, any>({
        path: `/admin-api/auth/logout`,
        method: "POST",
        secure: true,
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
    data: {
    cached_at: string,
    items: ({
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

},

}` description
 */
    dashboardDatabaseList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            cached_at: string;
            items: {
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
 * @name DashboardSconcurList
 * @request GET:/admin-api/dashboard/sconcur
 * @secure
 * @response `200` `{
    data: {
    available: boolean,
    name: string,
    workers_total: number,
    workers_hung: number,
    cpu_percent: number,
    memory_rss_bytes: number,
    runtime_tasks: number,
    work?: {
    in_process: number,
    "in_process_1_to_5s": number,
    "in_process_5_to_15s": number,
    "in_process_over_15s": number,
    finished: number,
    refused: number,
    measured: number,
    avg_ms: number,

},
    master_cpu_percent: number,
    master_memory_rss_bytes: number,
    groups: ({
    name: string,
    workers_total: number,
    workers_hung: number,
    cpu_percent: number,
    memory_rss_bytes: number,
    runtime_tasks: number,
    work?: {
    in_process: number,
    "in_process_1_to_5s": number,
    "in_process_5_to_15s": number,
    "in_process_over_15s": number,
    finished: number,
    refused: number,
    measured: number,
    avg_ms: number,

},

})[],
    workers: ({
    pid: number,
    group: string,
    hung: boolean,
    uptime_seconds: number,
    cpu_percent: number,
    memory_rss_bytes: number,
    runtime_tasks: number,
    work?: {
    in_process: number,
    "in_process_1_to_5s": number,
    "in_process_5_to_15s": number,
    "in_process_over_15s": number,
    finished: number,
    refused: number,
    measured: number,
    avg_ms: number,

},

})[],

},

}` description
 */
    dashboardSconcurList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            available: boolean;
            name: string;
            workers_total: number;
            workers_hung: number;
            cpu_percent: number;
            memory_rss_bytes: number;
            runtime_tasks: number;
            work?: {
              in_process: number;
              in_process_1_to_5s: number;
              in_process_5_to_15s: number;
              in_process_over_15s: number;
              finished: number;
              refused: number;
              measured: number;
              avg_ms: number;
            };
            master_cpu_percent: number;
            master_memory_rss_bytes: number;
            groups: {
              name: string;
              workers_total: number;
              workers_hung: number;
              cpu_percent: number;
              memory_rss_bytes: number;
              runtime_tasks: number;
              work?: {
                in_process: number;
                in_process_1_to_5s: number;
                in_process_5_to_15s: number;
                in_process_over_15s: number;
                finished: number;
                refused: number;
                measured: number;
                avg_ms: number;
              };
            }[];
            workers: {
              pid: number;
              group: string;
              hung: boolean;
              uptime_seconds: number;
              cpu_percent: number;
              memory_rss_bytes: number;
              runtime_tasks: number;
              work?: {
                in_process: number;
                in_process_1_to_5s: number;
                in_process_5_to_15s: number;
                in_process_over_15s: number;
                finished: number;
                refused: number;
                measured: number;
                avg_ms: number;
              };
            }[];
          };
        },
        any
      >({
        path: `/admin-api/dashboard/sconcur`,
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
        logging_from_preset?: LoggingFromPresetEnum;
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
              comp?: DataCompEnum;
            };
            string?: {
              value?: string;
              comp?: DataCompEnum1;
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
 * @name TraceAggregatorTracesTreeCreate
 * @request POST:/admin-api/trace-aggregator/traces/tree
 * @secure
 * @response `200` `{
    data: ({
    service_id: number,
    parent_trace_id?: string | null,
    trace_id: string,
    type: string,
    tags: (string)[],
    status: string,
    duration?: number | null,
    memory?: number | null,
    cpu?: number | null,
    logged_at: string,

})[],

}` description
 */
    traceAggregatorTracesTreeCreate: (
      data: {
        trace_id: string;
        fresh: boolean;
        is_child: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            service_id: number;
            parent_trace_id?: string | null;
            trace_id: string;
            type: string;
            tags: string[];
            status: string;
            duration?: number | null;
            memory?: number | null;
            cpu?: number | null;
            logged_at: string;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces/tree`,
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
 * @name TraceAggregatorTracesTreeContentCreate
 * @request POST:/admin-api/trace-aggregator/traces/tree/content
 * @secure
 * @response `200` `{
    data: {
    state: {
    root_trace_id: string,
    version: string,
    status: string,
    count: number,
    error?: string | null,
    started_at?: string | null,
    finished_at?: string | null,
    created_at: string,
    updated_at: string,

},
    content?: {
    count: number,
    services: ({
    id: number,
    name: string,
    traces_count: number,

})[],
    types: ({
    name: string,
    traces_count: number,

})[],
    tags: ({
    name: string,
    traces_count: number,

})[],
    statuses: ({
    name: string,
    traces_count: number,

})[],

},

},

}` description
 */
    traceAggregatorTracesTreeContentCreate: (
      data: {
        trace_id: string;
        is_child: boolean;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            state: {
              root_trace_id: string;
              version: string;
              status: string;
              count: number;
              error?: string | null;
              started_at?: string | null;
              finished_at?: string | null;
              created_at: string;
              updated_at: string;
            };
            content?: {
              count: number;
              services: {
                id: number;
                name: string;
                traces_count: number;
              }[];
              types: {
                name: string;
                traces_count: number;
              }[];
              tags: {
                name: string;
                traces_count: number;
              }[];
              statuses: {
                name: string;
                traces_count: number;
              }[];
            };
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces/tree/content`,
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
 * @name TraceAggregatorTracesTreeProcessesList
 * @request GET:/admin-api/trace-aggregator/traces/tree/processes
 * @secure
 * @response `200` `{
    data: ({
    root_trace_id: string,
    version: string,
    status: string,
    count: number,
    error?: string | null,
    started_at?: string | null,
    finished_at?: string | null,
    created_at: string,
    updated_at: string,

})[],

}` description
 */
    traceAggregatorTracesTreeProcessesList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            root_trace_id: string;
            version: string;
            status: string;
            count: number;
            error?: string | null;
            started_at?: string | null;
            finished_at?: string | null;
            created_at: string;
            updated_at: string;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces/tree/processes`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name TraceAggregatorTracesTreeProcessesCancelPartialUpdate
 * @request PATCH:/admin-api/trace-aggregator/traces/tree/processes/cancel
 * @secure
 * @response `200` `{
    data: {
    root_trace_id: string,
    version: string,
    status: string,
    count: number,
    error?: string | null,
    started_at?: string | null,
    finished_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
 */
    traceAggregatorTracesTreeProcessesCancelPartialUpdate: (
      data: {
        root_trace_id: string;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            root_trace_id: string;
            version: string;
            status: string;
            count: number;
            error?: string | null;
            started_at?: string | null;
            finished_at?: string | null;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/trace-aggregator/traces/tree/processes/cancel`,
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
     * @name TraceAggregatorTracesTreeProcessesDeletePartialUpdate
     * @request PATCH:/admin-api/trace-aggregator/traces/tree/processes/delete
     * @secure
     * @response `200` `any` description
     */
    traceAggregatorTracesTreeProcessesDeletePartialUpdate: (
      data: {
        root_trace_id: string;
      },
      params: RequestParams = {},
    ) =>
      this.request<any, any>({
        path: `/admin-api/trace-aggregator/traces/tree/processes/delete`,
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
  /** @maxItems 0 *\/
    children?: (string)[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

})[] | null,
    can_be_filtered: boolean,

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
              children?:
                | {
                    key: string;
                    value: string;
                    children?:
                      | {
                          key: string;
                          value: string;
                          children?:
                            | {
                                key: string;
                                value: string;
                                children?:
                                  | {
                                      key: string;
                                      value: string;
                                      children?:
                                        | {
                                            key: string;
                                            value: string;
                                            /** @maxItems 0 */
                                            children?: string[] | null;
                                            can_be_filtered: boolean;
                                          }[]
                                        | null;
                                      can_be_filtered: boolean;
                                    }[]
                                  | null;
                                can_be_filtered: boolean;
                              }[]
                            | null;
                          can_be_filtered: boolean;
                        }[]
                      | null;
                    can_be_filtered: boolean;
                  }[]
                | null;
              can_be_filtered: boolean;
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
  /** @maxItems 0 *\/
    data: (string)[],
    recursionNodeId?: number | null,
  /** @maxItems 0 *\/
    children?: (string)[] | null,

})[] | null,

})[] | null,

})[] | null,

})[] | null,

})[] | null,

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
              children?:
                | {
                    id: number;
                    calling: string;
                    data: {
                      name: string;
                      value: number;
                      weight_percent: number;
                    }[];
                    recursionNodeId?: number | null;
                    children?:
                      | {
                          id: number;
                          calling: string;
                          data: {
                            name: string;
                            value: number;
                            weight_percent: number;
                          }[];
                          recursionNodeId?: number | null;
                          children?:
                            | {
                                id: number;
                                calling: string;
                                data: {
                                  name: string;
                                  value: number;
                                  weight_percent: number;
                                }[];
                                recursionNodeId?: number | null;
                                children?:
                                  | {
                                      id: number;
                                      calling: string;
                                      data: {
                                        name: string;
                                        value: number;
                                        weight_percent: number;
                                      }[];
                                      recursionNodeId?: number | null;
                                      children?:
                                        | {
                                            id: number;
                                            calling: string;
                                            /** @maxItems 0 */
                                            data: string[];
                                            recursionNodeId?: number | null;
                                            /** @maxItems 0 */
                                            children?: string[] | null;
                                          }[]
                                        | null;
                                    }[]
                                  | null;
                              }[]
                            | null;
                        }[]
                      | null;
                  }[]
                | null;
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
        logging_from_preset?: LoggingFromPresetEnum1;
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
              comp?: DataCompEnum2;
            };
            string?: {
              value?: string;
              comp?: DataCompEnum3;
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
        logging_from_preset?: LoggingFromPresetEnum2;
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
              comp?: DataCompEnum4;
            };
            string?: {
              value?: string;
              comp?: DataCompEnum5;
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
        logging_from_preset?: LoggingFromPresetEnum3;
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
              comp?: DataCompEnum6;
            };
            string?: {
              value?: string;
              comp?: DataCompEnum7;
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
        timestamp_period: TimestampPeriodEnum;
        timestamp_step: TimestampStepEnum;
        fields?: FieldsEnum[];
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
              comp?: DataCompEnum8;
            };
            string?: {
              value?: string;
              comp?: DataCompEnum9;
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
    traceAggregatorDynamicIndexesDelete: (
      id: any,
      params: RequestParams = {},
    ) =>
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
 * @name TraceCleanerProcessesList
 * @request GET:/admin-api/trace-cleaner/processes
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    cleared_collections_count: number,
    cleared_traces_count: number,
    error?: string | null,
    cleared_at?: string | null,
    created_at: string,
    updated_at: string,

})[],

}` description
 */
    traceCleanerProcessesList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: string;
            cleared_collections_count: number;
            cleared_traces_count: number;
            error?: string | null;
            cleared_at?: string | null;
            created_at: string;
            updated_at: string;
          }[];
        },
        any
      >({
        path: `/admin-api/trace-cleaner/processes`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name WatchersList
 * @request GET:/admin-api/watchers
 * @secure
 * @response `200` `{
    data: ({
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

})[],

}` description
 */
    watchersList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            name: string;
            type: string;
            enabled: boolean;
            cooldown_seconds: number;
            collect_since?: string | null;
            last_checked_at?: string | null;
            last_triggered_at?: string | null;
            created_at: string;
            updated_at: string;
          }[];
        },
        any
      >({
        path: `/admin-api/watchers`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name WatchersTypesList
 * @request GET:/admin-api/watchers/types
 * @secure
 * @response `200` `{
    data: ({
    type: string,
    title: string,
    description: string,
    default_cooldown_seconds: number,
    has_trace_filter: boolean,
    fields: ({
    key: string,
    title: string,
    value_type: string,
    default: number,
    min: number,
    max?: number | null,

})[],

})[],

}` description
 */
    watchersTypesList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            type: string;
            title: string;
            description: string;
            default_cooldown_seconds: number;
            has_trace_filter: boolean;
            fields: {
              key: string;
              title: string;
              value_type: string;
              default: number;
              min: number;
              max?: number | null;
            }[];
          }[];
        },
        any
      >({
        path: `/admin-api/watchers/types`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name WatchersIncidentsList
 * @request GET:/admin-api/watchers/incidents
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    watcher_id: number,
    status: string,
    first_event_at: string,
    last_event_at: string,
    events_count: number,
    closed_at?: string | null,
    closed_by_user_id?: number | null,

})[],

}` description
 */
    watchersIncidentsList: (
      query?: {
        /** @min 1 */
        page?: number;
        /**
         * @min 1
         * @max 200
         */
        per_page?: number;
        status?: WatchersIncidentsListParamsStatusEnum | null;
        watcher_id?: number | null;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: string;
            watcher_id: number;
            status: string;
            first_event_at: string;
            last_event_at: string;
            events_count: number;
            closed_at?: string | null;
            closed_by_user_id?: number | null;
          }[];
        },
        any
      >({
        path: `/admin-api/watchers/incidents`,
        method: "GET",
        query: query,
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name WatchersIncidentsStatList
 * @request GET:/admin-api/watchers/incidents/stat
 * @secure
 * @response `200` `{
    data: {
    opened_count: number,

},

}` description
 */
    watchersIncidentsStatList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            opened_count: number;
          };
        },
        any
      >({
        path: `/admin-api/watchers/incidents/stat`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name WatchersIncidentsEventsList
 * @request GET:/admin-api/watchers/incidents/{id}/events
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    incident_id: string,
    occurred_at: string,
    payload: {
    settings: {
    threshold?: number | null,
    period_minutes?: number | null,
    window_minutes?: number | null,
    baseline_minutes?: number | null,
    growth_percent?: number | null,
    duration?: number | null,

},
    measured: {
    buffer_count?: number | null,
    invalid_count?: number | null,
    since?: string | null,
    window_from?: string | null,
    window_to?: string | null,
    window_count?: number | null,
    window_per_minute?: number | null,
    baseline_per_minute?: number | null,
    growth_percent?: number | null,
    slowest?: number | null,

},
    groups: ({
    service_id: number,
    type: string,
    tags: (string)[],
    count: number,
    duration_max?: number | null,
    trace_id?: string | null,

})[],

},

})[],

}` description
 */
    watchersIncidentsEventsList: (
      id: any,
      query?: {
        /** @min 1 */
        page?: number;
        /**
         * @min 1
         * @max 200
         */
        per_page?: number;
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: string;
            incident_id: string;
            occurred_at: string;
            payload: {
              settings: {
                threshold?: number | null;
                period_minutes?: number | null;
                window_minutes?: number | null;
                baseline_minutes?: number | null;
                growth_percent?: number | null;
                duration?: number | null;
              };
              measured: {
                buffer_count?: number | null;
                invalid_count?: number | null;
                since?: string | null;
                window_from?: string | null;
                window_to?: string | null;
                window_count?: number | null;
                window_per_minute?: number | null;
                baseline_per_minute?: number | null;
                growth_percent?: number | null;
                slowest?: number | null;
              };
              groups: {
                service_id: number;
                type: string;
                tags: string[];
                count: number;
                duration_max?: number | null;
                trace_id?: string | null;
              }[];
            };
          }[];
        },
        any
      >({
        path: `/admin-api/watchers/incidents/${id}/events`,
        method: "GET",
        query: query,
        secure: true,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name WatchersIncidentsClosePartialUpdate
     * @request PATCH:/admin-api/watchers/incidents/{id}/close
     * @secure
     * @response `200` `any` description
     */
    watchersIncidentsClosePartialUpdate: (
      id: any,
      params: RequestParams = {},
    ) =>
      this.request<any, any>({
        path: `/admin-api/watchers/incidents/${id}/close`,
        method: "PATCH",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name WatchersBufferOverflowCreate
 * @request POST:/admin-api/watchers/buffer-overflow
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
 */
    watchersBufferOverflowCreate: (
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /** @min 1 */
          threshold?: number;
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: number;
            name: string;
            type: string;
            enabled: boolean;
            cooldown_seconds: number;
            collect_since?: string | null;
            last_checked_at?: string | null;
            last_triggered_at?: string | null;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/watchers/buffer-overflow`,
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
 * @name WatchersBufferOverflowDetail
 * @request GET:/admin-api/watchers/buffer-overflow/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    threshold: number,

},

}` description
 */
    watchersBufferOverflowDetail: (id: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            threshold: number;
          };
        },
        any
      >({
        path: `/admin-api/watchers/buffer-overflow/${id}`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name WatchersBufferOverflowPartialUpdate
     * @request PATCH:/admin-api/watchers/buffer-overflow/{id}
     * @secure
     * @response `200` `any` description
     */
    watchersBufferOverflowPartialUpdate: (
      id: any,
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /** @min 1 */
          threshold?: number;
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<any, any>({
        path: `/admin-api/watchers/buffer-overflow/${id}`,
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
 * @name WatchersInvalidBufferGrownCreate
 * @request POST:/admin-api/watchers/invalid-buffer-grown
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
 */
    watchersInvalidBufferGrownCreate: (
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /** @min 1 */
          threshold?: number;
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: number;
            name: string;
            type: string;
            enabled: boolean;
            cooldown_seconds: number;
            collect_since?: string | null;
            last_checked_at?: string | null;
            last_triggered_at?: string | null;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/watchers/invalid-buffer-grown`,
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
 * @name WatchersInvalidBufferGrownDetail
 * @request GET:/admin-api/watchers/invalid-buffer-grown/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    threshold: number,

},

}` description
 */
    watchersInvalidBufferGrownDetail: (id: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            threshold: number;
          };
        },
        any
      >({
        path: `/admin-api/watchers/invalid-buffer-grown/${id}`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name WatchersInvalidBufferGrownPartialUpdate
     * @request PATCH:/admin-api/watchers/invalid-buffer-grown/{id}
     * @secure
     * @response `200` `any` description
     */
    watchersInvalidBufferGrownPartialUpdate: (
      id: any,
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /** @min 1 */
          threshold?: number;
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<any, any>({
        path: `/admin-api/watchers/invalid-buffer-grown/${id}`,
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
 * @name WatchersNoNewTracesCreate
 * @request POST:/admin-api/watchers/no-new-traces
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
 */
    watchersNoNewTracesCreate: (
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /**
           * @min 1
           * @max 175
           */
          period_minutes?: number;
          filter?: {
            service_ids?: number[];
            types?: string[];
            tags?: string[];
          };
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: number;
            name: string;
            type: string;
            enabled: boolean;
            cooldown_seconds: number;
            collect_since?: string | null;
            last_checked_at?: string | null;
            last_triggered_at?: string | null;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/watchers/no-new-traces`,
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
 * @name WatchersNoNewTracesDetail
 * @request GET:/admin-api/watchers/no-new-traces/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    period_minutes: number,
    filter: {
    service_ids: (number)[],
    types: (string)[],
    tags: (string)[],

},

},

}` description
 */
    watchersNoNewTracesDetail: (id: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            period_minutes: number;
            filter: {
              service_ids: number[];
              types: string[];
              tags: string[];
            };
          };
        },
        any
      >({
        path: `/admin-api/watchers/no-new-traces/${id}`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name WatchersNoNewTracesPartialUpdate
     * @request PATCH:/admin-api/watchers/no-new-traces/{id}
     * @secure
     * @response `200` `any` description
     */
    watchersNoNewTracesPartialUpdate: (
      id: any,
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /**
           * @min 1
           * @max 175
           */
          period_minutes?: number;
          filter?: {
            service_ids?: number[];
            types?: string[];
            tags?: string[];
          };
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<any, any>({
        path: `/admin-api/watchers/no-new-traces/${id}`,
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
 * @name WatchersTracesSpikeCreate
 * @request POST:/admin-api/watchers/traces-spike
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
 */
    watchersTracesSpikeCreate: (
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /**
           * @min 1
           * @max 175
           */
          window_minutes?: number;
          /**
           * @min 1
           * @max 175
           */
          baseline_minutes?: number;
          /** @min 1 */
          growth_percent?: number;
          filter?: {
            service_ids?: number[];
            types?: string[];
            tags?: string[];
          };
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: number;
            name: string;
            type: string;
            enabled: boolean;
            cooldown_seconds: number;
            collect_since?: string | null;
            last_checked_at?: string | null;
            last_triggered_at?: string | null;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/watchers/traces-spike`,
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
 * @name WatchersTracesSpikeDetail
 * @request GET:/admin-api/watchers/traces-spike/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    window_minutes: number,
    baseline_minutes: number,
    growth_percent: number,
    filter: {
    service_ids: (number)[],
    types: (string)[],
    tags: (string)[],

},

},

}` description
 */
    watchersTracesSpikeDetail: (id: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            window_minutes: number;
            baseline_minutes: number;
            growth_percent: number;
            filter: {
              service_ids: number[];
              types: string[];
              tags: string[];
            };
          };
        },
        any
      >({
        path: `/admin-api/watchers/traces-spike/${id}`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name WatchersTracesSpikePartialUpdate
     * @request PATCH:/admin-api/watchers/traces-spike/{id}
     * @secure
     * @response `200` `any` description
     */
    watchersTracesSpikePartialUpdate: (
      id: any,
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /**
           * @min 1
           * @max 175
           */
          window_minutes?: number;
          /**
           * @min 1
           * @max 175
           */
          baseline_minutes?: number;
          /** @min 1 */
          growth_percent?: number;
          filter?: {
            service_ids?: number[];
            types?: string[];
            tags?: string[];
          };
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<any, any>({
        path: `/admin-api/watchers/traces-spike/${id}`,
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
 * @name WatchersSlowTracesCreate
 * @request POST:/admin-api/watchers/slow-traces
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    cooldown_seconds: number,
    collect_since?: string | null,
    last_checked_at?: string | null,
    last_triggered_at?: string | null,
    created_at: string,
    updated_at: string,

},

}` description
 */
    watchersSlowTracesCreate: (
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /**
           * @format float
           * @min 0
           */
          duration?: number;
          /**
           * @min 1
           * @max 175
           */
          window_minutes?: number;
          filter?: {
            service_ids?: number[];
            types?: string[];
            tags?: string[];
          };
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: number;
            name: string;
            type: string;
            enabled: boolean;
            cooldown_seconds: number;
            collect_since?: string | null;
            last_checked_at?: string | null;
            last_triggered_at?: string | null;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/watchers/slow-traces`,
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
 * @name WatchersSlowTracesDetail
 * @request GET:/admin-api/watchers/slow-traces/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    duration: number,
    window_minutes: number,
    filter: {
    service_ids: (number)[],
    types: (string)[],
    tags: (string)[],

},

},

}` description
 */
    watchersSlowTracesDetail: (id: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            duration: number;
            window_minutes: number;
            filter: {
              service_ids: number[];
              types: string[];
              tags: string[];
            };
          };
        },
        any
      >({
        path: `/admin-api/watchers/slow-traces/${id}`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name WatchersSlowTracesPartialUpdate
     * @request PATCH:/admin-api/watchers/slow-traces/{id}
     * @secure
     * @response `200` `any` description
     */
    watchersSlowTracesPartialUpdate: (
      id: any,
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        /**
         * @min 1
         * @max 86400
         */
        cooldown_seconds: number;
        settings?: {
          /**
           * @format float
           * @min 0
           */
          duration?: number;
          /**
           * @min 1
           * @max 175
           */
          window_minutes?: number;
          filter?: {
            service_ids?: number[];
            types?: string[];
            tags?: string[];
          };
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<any, any>({
        path: `/admin-api/watchers/slow-traces/${id}`,
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
     * @name WatchersDelete
     * @request DELETE:/admin-api/watchers/{id}
     * @secure
     * @response `200` `void` description
     */
    watchersDelete: (id: any, params: RequestParams = {}) =>
      this.request<void, any>({
        path: `/admin-api/watchers/${id}`,
        method: "DELETE",
        secure: true,
        ...params,
      }),

    /**
 * No description
 *
 * @name NotificationChannelsList
 * @request GET:/admin-api/notification-channels
 * @secure
 * @response `200` `{
    data: ({
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    on_opened: boolean,
    on_event: boolean,
    on_closed: boolean,
    created_at: string,
    updated_at: string,

})[],

}` description
 */
    notificationChannelsList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            name: string;
            type: string;
            enabled: boolean;
            on_opened: boolean;
            on_event: boolean;
            on_closed: boolean;
            created_at: string;
            updated_at: string;
          }[];
        },
        any
      >({
        path: `/admin-api/notification-channels`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name NotificationChannelsTypesList
 * @request GET:/admin-api/notification-channels/types
 * @secure
 * @response `200` `{
    data: ({
    type: string,
    title: string,
    description: string,
    fields: ({
    key: string,
    title: string,
    description: string,
    secret: boolean,
    max_length: number,

})[],

})[],

}` description
 */
    notificationChannelsTypesList: (params: RequestParams = {}) =>
      this.request<
        {
          data: {
            type: string;
            title: string;
            description: string;
            fields: {
              key: string;
              title: string;
              description: string;
              secret: boolean;
              max_length: number;
            }[];
          }[];
        },
        any
      >({
        path: `/admin-api/notification-channels/types`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name NotificationChannelsTelegramCreate
 * @request POST:/admin-api/notification-channels/telegram
 * @secure
 * @response `200` `{
    data: {
    id: number,
    name: string,
    type: string,
    enabled: boolean,
    on_opened: boolean,
    on_event: boolean,
    on_closed: boolean,
    created_at: string,
    updated_at: string,

},

}` description
 */
    notificationChannelsTelegramCreate: (
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        on_opened: boolean;
        on_event: boolean;
        on_closed: boolean;
        settings?: {
          /**
           * @minLength 10
           * @maxLength 255
           */
          bot_token?: string;
          /**
           * @minLength 5
           * @maxLength 255
           */
          chat_id?: string;
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<
        {
          data: {
            id: number;
            name: string;
            type: string;
            enabled: boolean;
            on_opened: boolean;
            on_event: boolean;
            on_closed: boolean;
            created_at: string;
            updated_at: string;
          };
        },
        any
      >({
        path: `/admin-api/notification-channels/telegram`,
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
 * @name NotificationChannelsTelegramDetail
 * @request GET:/admin-api/notification-channels/telegram/{id}
 * @secure
 * @response `200` `{
    data: {
    id: number,
    bot_token_mask: string,
    chat_id: string,

},

}` description
 */
    notificationChannelsTelegramDetail: (id: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: number;
            bot_token_mask: string;
            chat_id: string;
          };
        },
        any
      >({
        path: `/admin-api/notification-channels/telegram/${id}`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name NotificationChannelsTelegramPartialUpdate
     * @request PATCH:/admin-api/notification-channels/telegram/{id}
     * @secure
     * @response `200` `any` description
     */
    notificationChannelsTelegramPartialUpdate: (
      id: any,
      data: {
        /**
         * @minLength 1
         * @maxLength 255
         */
        name: string;
        enabled: boolean;
        on_opened: boolean;
        on_event: boolean;
        on_closed: boolean;
        settings?: {
          /**
           * @minLength 10
           * @maxLength 255
           */
          bot_token?: string | null;
          /**
           * @minLength 5
           * @maxLength 255
           */
          chat_id?: string;
        };
      },
      params: RequestParams = {},
    ) =>
      this.request<any, any>({
        path: `/admin-api/notification-channels/telegram/${id}`,
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
 * @name NotificationChannelsDeliveriesList
 * @request GET:/admin-api/notification-channels/{id}/deliveries
 * @secure
 * @response `200` `{
    data: ({
    id: string,
    watcher_id?: number | null,
    incident_id?: string | null,
    kind: string,
    text: string,
    sent_at?: string | null,
    error?: string | null,
    created_at: string,

})[],

}` description
 */
    notificationChannelsDeliveriesList: (id: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            id: string;
            watcher_id?: number | null;
            incident_id?: string | null;
            kind: string;
            text: string;
            sent_at?: string | null;
            error?: string | null;
            created_at: string;
          }[];
        },
        any
      >({
        path: `/admin-api/notification-channels/${id}/deliveries`,
        method: "GET",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
 * No description
 *
 * @name NotificationChannelsTestCreate
 * @request POST:/admin-api/notification-channels/{id}/test
 * @secure
 * @response `200` `{
    data: {
    delivered: boolean,
    permanent: boolean,
    error?: string | null,
    retry_after_seconds?: number | null,

},

}` description
 */
    notificationChannelsTestCreate: (id: any, params: RequestParams = {}) =>
      this.request<
        {
          data: {
            delivered: boolean;
            permanent: boolean;
            error?: string | null;
            retry_after_seconds?: number | null;
          };
        },
        any
      >({
        path: `/admin-api/notification-channels/${id}/test`,
        method: "POST",
        secure: true,
        format: "json",
        ...params,
      }),

    /**
     * No description
     *
     * @name NotificationChannelsDelete
     * @request DELETE:/admin-api/notification-channels/{id}
     * @secure
     * @response `200` `void` description
     */
    notificationChannelsDelete: (id: any, params: RequestParams = {}) =>
      this.request<void, any>({
        path: `/admin-api/notification-channels/${id}`,
        method: "DELETE",
        secure: true,
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
