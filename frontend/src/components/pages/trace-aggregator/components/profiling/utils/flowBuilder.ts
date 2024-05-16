import {FlowItems, ProfilingItem} from "../../../../../../store/traceAggregatorProfilingStore.ts";

interface FlowMap {
    [key: string]: ProfilingItem
}

export class FlowBuilder {
    private readonly profilingItems: Array<ProfilingItem>
    private readonly hardestItemIds: Array<string>

    private posX: number = 50
    private posY: number = 50

    private stepX: number = 400 // horizontal
    private stepY: number = 400 // vertical

    private flowMap: FlowMap = {}

    private flowItems: FlowItems = {
        nodes: [],
        edges: []
    }

    constructor(profilingItems: Array<ProfilingItem>, hardestItemIds: Array<string>) {
        this.profilingItems = profilingItems
        this.hardestItemIds = hardestItemIds
    }

    public build(caller: string): FlowItems {
        this.posX = 0
        this.posY = 0

        this.flowItems = {
            nodes: [],
            edges: []
        }

        this.flowMap = {}

        const root: ProfilingItem = {
            id: (new Date()).getMilliseconds().toString(),
            calling: caller,
            callable: caller,
            data: [],
        }

        this.flowItems.nodes.push({
            id: root.id,
            label: caller,
            type: 'custom',
            data: root,
            position: {
                x: this.posX,
                y: this.posY
            },
        })

        this.flowMap[caller] = root

        this.buildRecursive(caller, root)

        return this.flowItems
    }

    private buildRecursive(caller: string, parent: null | ProfilingItem): void {
        this.posY += this.stepY

        let isFirstItem = true

        this.findCallables(caller).forEach((item: ProfilingItem) => {
            if (!isFirstItem) {
                this.posX += this.stepX
            }

            isFirstItem = false

            this.flowItems.nodes.push({
                id: item.id,
                label: item.callable,
                type: 'custom',
                data: item,
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
                    style: {stroke: this.isHardestItemIds(parent.id, item.id) ? 'red' : 'green'},
                })
            }

            const link = this.flowMap[item.callable]

            if (link) {
                this.flowItems.edges.push({
                    id: `${item.id}-${link.id}`,
                    source: item.id,
                    target: link.id,
                    style: {stroke: this.isHardestItemIds(item.id, link.id) ? 'red' : 'gray'},
                })

                return
            }

            this.flowMap[item.callable] = item

            this.buildRecursive(item.callable, item)
        })

        this.posY -= this.stepY
    }

    private isHardestItemIds(sourceId: string, targetId: string): boolean {
        return (this.hardestItemIds.indexOf(sourceId) !== -1
            && this.hardestItemIds.indexOf(targetId) !== -1
        ) || (this.hardestItemIds.indexOf(sourceId) === -1
            && this.hardestItemIds.indexOf(targetId) !== -1)
    }

    private findCallables(calling: string): Array<ProfilingItem> {
        return this.profilingItems.filter(item => {
            return item.calling === calling
        })
    }
}
