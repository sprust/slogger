import type {
  BufferOverflowEvent,
  InvalidBufferGrownEvent,
  LogErrorsEvent,
  ManyTracesEvent,
  NoNewTracesEvent,
  ReceiverErrorsEvent,
  SlowTracesEvent,
  WatcherIncidentEvent,
} from "../../store/incidentsStore.ts";

export type PayloadBucket = 'settings' | 'measured'

export type PayloadColumn = {
  bucket: PayloadBucket,
  key: string,
  title: string,
}

/**
 * A column of one watcher type's payload, named against the type the server answers with.
 *
 * The key is checked at build time: a number renamed on the server and regenerated into
 * the schema fails here rather than showing an empty column nobody notices.
 */
export function setting<E extends WatcherIncidentEvent>(
    key: keyof NonNullable<E['payload']>['settings'] & string,
    title: string,
): PayloadColumn {
  return {bucket: 'settings', key, title}
}

export function measured<E extends WatcherIncidentEvent>(
    key: keyof NonNullable<E['payload']>['measured'] & string,
    title: string,
): PayloadColumn {
  return {bucket: 'measured', key, title}
}

/**
 * The columns each watcher type's events fill, in the order the server writes them.
 *
 * One list per type rather than one list probed for non-null values: an incident's events
 * all come from the same watcher, so the columns are known before the first row is read.
 */
export const columnsByType: Record<string, Array<PayloadColumn>> = {
  bufferOverflow: [
    setting<BufferOverflowEvent>('threshold', 'Limit'),
    measured<BufferOverflowEvent>('buffer_count', 'Traces in the buffer'),
  ],
  invalidBufferGrown: [
    setting<InvalidBufferGrownEvent>('threshold', 'Limit'),
    measured<InvalidBufferGrownEvent>('invalid_count', 'Invalid traces'),
    measured<InvalidBufferGrownEvent>('since', 'Counted since'),
  ],
  noNewTraces: [
    setting<NoNewTracesEvent>('period_minutes', 'Minutes without traces'),
    measured<NoNewTracesEvent>('window_from', 'From'),
    measured<NoNewTracesEvent>('window_to', 'To'),
  ],
  manyTraces: [
    setting<ManyTracesEvent>('window_minutes', 'Minutes counted'),
    setting<ManyTracesEvent>('threshold', 'More traces than'),
    measured<ManyTracesEvent>('window_count', 'Traces counted'),
  ],
  slowTraces: [
    setting<SlowTracesEvent>('duration', 'Longer than, sec'),
    setting<SlowTracesEvent>('window_minutes', 'Minutes counted'),
    measured<SlowTracesEvent>('slowest', 'Slowest trace, sec'),
  ],
  logErrors: [
    setting<LogErrorsEvent>('threshold', 'Limit'),
    measured<LogErrorsEvent>('error_count', 'Errors'),
    measured<LogErrorsEvent>('since', 'Counted since'),
    measured<LogErrorsEvent>('last_message', 'Last error'),
  ],
  receiverErrors: [
    setting<ReceiverErrorsEvent>('threshold', 'Limit'),
    measured<ReceiverErrorsEvent>('error_count', 'Errors'),
    measured<ReceiverErrorsEvent>('since', 'Counted since'),
    measured<ReceiverErrorsEvent>('last_message', 'Last error'),
  ],
}
