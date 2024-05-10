import {ProfilingMetrics, ProfilingItem} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class MetricsBuilder {
    public build(profilingItems: Array<ProfilingItem>): ProfilingMetrics {
        const profilingMetrics: ProfilingMetrics = {
            numberOfCalls: 0,
            waitTimeInUs: 0,
            cpuTime: 0,
            memoryUsageInBytes: 0,
            peakMemoryUsageInBytes: 0,
            totalCount: 0,
            hardestCpuItemIds: []
        }

        this.buildRecursive(profilingItems, profilingMetrics, true)

        return profilingMetrics
    }

    private buildRecursive(
        items: Array<ProfilingItem>,
        profilingMetrics: ProfilingMetrics,
        isHardestCpu: boolean
    ): void {
        if (items.length === 0) {
            return
        }

        const itemWithMaxCpuTime = items.reduce((prev, current) => {
            return (prev.cpu_time > current.cpu_time) ? prev : current
        })

        items.map((item: ProfilingItem) => {
            profilingMetrics.numberOfCalls = Math.max(
                profilingMetrics.numberOfCalls,
                item.number_of_calls
            )
            profilingMetrics.waitTimeInUs = Math.max(
                profilingMetrics.waitTimeInUs,
                item.wait_time_in_us
            )
            profilingMetrics.cpuTime = Math.max(
                profilingMetrics.cpuTime,
                item.cpu_time
            )
            profilingMetrics.memoryUsageInBytes = Math.max(
                profilingMetrics.memoryUsageInBytes,
                item.memory_usage_in_bytes
            )
            profilingMetrics.peakMemoryUsageInBytes = Math.max(
                profilingMetrics.peakMemoryUsageInBytes,
                item.peak_memory_usage_in_bytes
            )
            ++profilingMetrics.totalCount

            // @ts-ignore // todo: recursive oa scheme
            this.buildRecursive(item.callables, profilingMetrics, isHardestCpu && itemWithMaxCpuTime.id === item.id)
        })

        if (isHardestCpu) {
            profilingMetrics.hardestCpuItemIds.push(itemWithMaxCpuTime?.id)
        }
    }
}
