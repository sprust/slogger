import {ProfilingTreeNode} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class TreeFilter {
    public includes(pattern: string, data: ProfilingTreeNode): boolean {
        const masks: Array<string> = pattern.split(';')

        const subject: string = data.label

        for (let i = 0; i < masks.length; i++) {
            let mask: string = masks[i]

            const needExclude = (mask.substring(0, 1) === '!')

            if (needExclude) {
                mask = mask.substring(1)
            }

            let includes

            if (!mask.includes('*')) {
                includes = subject.includes(mask);
            } else {
                const regex = '^' + mask.replace(/\./g, '\\.').replace(/\*/g, '.*') + '$'

                includes = !!subject.match(regex);
            }

            if (needExclude) {
                if (includes) {
                    return false
                }
            } else {
                if (!includes) {
                    return false
                }
            }
        }

        return true
    }
}
