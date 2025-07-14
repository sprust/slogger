import {TraceTreeNode} from "./traceAggregatorTreeStore.ts";

export class IndicatorSetter {
    private max: number = 0;

    constructor(private allRows: TraceTreeNode[], private row: TraceTreeNode) {
    }

    public fill() {
        this.clearIndicatorsRecursive(this.allRows)
        this.collectMaxIndicator([this.row])
        this.setIndicatorPercentRecursive([this.row])
    }

    private clearIndicatorsRecursive(rows: TraceTreeNode[]) {
        rows.forEach(row => {
            row.indicatorPercent = 0;

            this.clearIndicatorsRecursive(row.children);
        })
    }

    private collectMaxIndicator(rows: TraceTreeNode[]) {
        rows.forEach(row => {
            if (row.primary.duration) {
                this.max = Math.max(this.max, row.primary.duration)
            }

            this.collectMaxIndicator(row.children);
        })
    }

    private setIndicatorPercentRecursive(rows: TraceTreeNode[]) {
        rows.forEach(row => {
            if (row.primary.duration) {
                row.indicatorPercent = Math.round(row.primary.duration / this.max * 100);
            }

            this.setIndicatorPercentRecursive(row.children);
        })
    }
}
