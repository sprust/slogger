import {FlowItems, ProfilingNode} from "../../../store/traceAggregatorProfilingStore.ts";

export class FlowBuilder {
    private readonly profilingNode: ProfilingNode

    private posX: number = 50
    private posY: number = 50

    private stepX: number = 300 // horizontal
    private stepY: number = 300 // vertical

    private flowItems: FlowItems = {
        nodes: [],
        edges: []
    }

    constructor(profilingNode: ProfilingNode) {
        this.profilingNode = profilingNode
    }

    public build(): FlowItems {
        this.posX = 0
        this.posY = 0

        this.flowItems = {
            nodes: [],
            edges: []
        }

        this.flowItems.nodes.push({
            id: this.profilingNode.id.toString(),
            label: this.profilingNode.calling,
            type: 'custom',
            data: this.profilingNode,
            position: {
                x: this.posX,
                y: this.posY
            },
        })

        this.buildRecursive(this.profilingNode)

        return this.flowItems
    }

    private buildRecursive(parent: ProfilingNode): void {
        this.posY += this.stepY

        let isFirstItem = true

        // @ts-ignore
        parent.children?.map((item: ProfilingNode) => {
            if (!isFirstItem) {
                this.posX += this.stepX
            }

            isFirstItem = false

            this.flowItems.nodes.push({
                id: item.id.toString(),
                label: item.calling,
                type: 'custom',
                data: item,
                position: {
                    x: this.posX,
                    y: this.posY
                },
            })

            this.flowItems.edges.push({
                id: `${parent.id}-${item.id}`,
                source: parent.id.toString(),
                target: item.id.toString(),
                type: 'custom',
                style: {stroke: 'green'},
            })

            if (item.recursionNodeId) {
                this.flowItems.edges.push({
                    id: `${item.id}-${item.recursionNodeId}`,
                    source: item.id.toString(),
                    target: item.recursionNodeId.toString(),
                    type: 'custom',
                    style: {stroke: 'gray'},
                })

                return
            }

            this.buildRecursive(item)
        })

        this.posY -= this.stepY
    }
}
