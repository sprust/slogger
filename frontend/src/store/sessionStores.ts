import type {PiniaPluginContext, Store} from "pinia";

/**
 * Every store the panel has created, so that a session can let go of all of them.
 *
 * The list used to be written out by hand in endSession(), and a store added later and
 * not added there kept the previous person's filters, results and flags after a sign-out
 * — a trace page whose live graph went on asking for data the moment it was open again.
 */
const stores = new Set<Store>()

/** The pinia plugin that fills the set: each store is passed through it once, when first used. */
export function trackSessionStores({store}: PiniaPluginContext): void {
    stores.add(store)
}

/**
 * Puts every store but the ones named back to its initial state.
 *
 * Only the state: a timer or a loop a store runs is not in it, so whatever keeps asking
 * the API is stopped by its own store first (endSession does that), and only then is the
 * state it ran on thrown away.
 */
export function resetSessionStores(except: string[]): void {
    for (const store of stores) {
        if (!except.includes(store.$id)) {
            store.$reset()
        }
    }
}
