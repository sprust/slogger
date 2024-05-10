import {FlowItems, ProfilingItem} from "../../../../../../store/traceAggregatorProfilingStore.ts";

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
                id: item.id,
                label: item.call.substring(item.call.length - 20),
                position: {
                    x: this.posX,
                    y: this.posY
                },
            })

            if (parent) {
                this.flowItems.edges.push({
                    id: `${parent.id}-${item.id}`,
                    source: parent.id,
                    target: item.id,
                    animated: false
                })
            }

            // @ts-ignore // todo: recursive oa scheme
            this.buildRecursive(item.callables, item)

            this.posX += this.stepX
        })

        this.posY -= this.stepY
    }
}
