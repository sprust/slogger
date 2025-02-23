import {ProfilingNode, ProfilingTreeNodeV2} from "../../../store/traceAggregatorProfilingStore.ts";

export class TreeBuilder {
    private readonly nodes: Array<ProfilingNode>

    private dept: number = 0
    private tree: Array<ProfilingTreeNodeV2> = []

    constructor(nodes: Array<ProfilingNode>) {
        this.nodes = nodes
    }

    public build(): Array<ProfilingTreeNodeV2> {
        this.dept = 0
        this.tree = []

        // @ts-ignore
        this.buildRecursive(this.nodes)

        return this.tree
    }

    private buildRecursive(nodes: Array<ProfilingNode>, parentId: null | number): void {
        ++this.dept

        nodes.map((node: ProfilingNode) => {
            this.tree.push({
                dept: this.dept,
                id: node.id,
                parentId: parentId,
                calling: node.calling,
                data: node.data,
                recursionNodeId: node.recursionNodeId ?? null,
                primary: node,
                hide: false
            })

            if (!node.children) {
                return
            }

            // @ts-ignore
            this.buildRecursive(node.children, node.id)
        })

        --this.dept
    }
}
