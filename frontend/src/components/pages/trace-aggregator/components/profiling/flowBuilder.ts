import {ProfilingItem} from "../../../../../store/traceAggregatorProfilingStore.ts";

interface FlowNode {
    id: string,
    label: string,
    position: {
        x: number,
        y: number
    }
}

interface FlowEdge {
    id: string,
    source: string,
    target: string,
    animated: boolean
}

interface FlowItems {
    nodes: Array<FlowNode>,
    edges: Array<FlowEdge>,
}

export class FlowBuilder {
    private readonly profilingItems: Array<ProfilingItem>

    private posX: number = 50
    private posY: number = 50

    private stepX: number = 200
    private stepY: number = 200

    private flowItems: FlowItems = {
        nodes: [],
        edges: []
    }

    constructor(profilingItems: Array<ProfilingItem>) {
        this.profilingItems = profilingItems
    }

    public build(): FlowItems {
        this.posX = 0
        this.posY = 0

        this.flowItems = {
            nodes: [],
            edges: []
        }

        this.buildRecursive(this.profilingItems, null)

        return this.flowItems
    }

    private buildRecursive(items: Array<ProfilingItem>, parent: null | ProfilingItem): void {
        this.posY += this.stepY

        items.map((item: ProfilingItem) => {
            this.flowItems.nodes.push({
                id: item.call,
                label: item.call.substring(item.call.length - 20),
                position: {
                    x: this.posX,
                    y: this.posY
                }
            })

            if (parent) {
                this.flowItems.edges.push({
                    id: `${parent.call}-${item.call}`,
                    source: parent.call,
                    target: item.call,
                    animated: false
                })
            }

            this.buildRecursive(item.callables, item)

            this.posX += this.stepX
        })

        this.posY -= this.stepY
    }
}
